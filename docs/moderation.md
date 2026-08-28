# Domínio Moderation

## Para que serve

Moderação cobre quatro mecanismos distintos que, juntos, mantêm o conteúdo
e os usuários do Pratto dentro do esperado: aprovação/rejeição real de
`Recipe` e `Ingredient` (os Planos 2/3 só tinham o *gate* de status, sem
quem de fato aprova), banimento real de `User` (o Plano 3 deixava
`role`/`status` como coluna crua, nunca hidratada pelo Domain), remoção de
comentário por admin, e um sistema de denúncia (`Report`) — **desacoplado
de propósito** da ação de moderação em si: denunciar só registra que
alguém sinalizou algo; agir sobre o alvo denunciado (rejeitar a receita,
banir o usuário, apagar o comentário) é sempre uma chamada separada e não
acontece automaticamente.

Código-fonte de referência: `app/Domain/Moderation/Report.php`,
`app/Domain/Moderation/Enums/ReportTargetType.php`,
`app/Domain/Moderation/Enums/ReportStatus.php`,
`app/Domain/Moderation/Contracts/ReportRepositoryInterface.php`,
`app/Domain/Recipe/Recipe.php` (`approve()`/`reject()`/`isVisibleTo()`),
`app/Domain/Ingredient/Ingredient.php` (`approve()`/`reject()`),
`app/Domain/User/User.php` (`role`/`status`/`ban()`/`unban()`),
`app/Application/Recipe/UseCases/ApproveRecipe.php`,
`app/Application/Recipe/UseCases/RejectRecipe.php`,
`app/Application/Ingredient/UseCases/ApproveIngredient.php`,
`app/Application/Ingredient/UseCases/RejectIngredient.php`,
`app/Application/User/UseCases/PromoteToAdmin.php`,
`app/Application/User/UseCases/BanUser.php`,
`app/Application/User/UseCases/UnbanUser.php`,
`app/Application/Comment/UseCases/AdminDeleteComment.php`,
`app/Application/Moderation/UseCases/ReportContent.php`,
`app/Application/Moderation/UseCases/ListReports.php`,
`app/Application/Moderation/UseCases/ResolveReport.php`.

## O loophole de reposting, e por que `wasEverRejected` existe

Aprovar uma receita não é "torná-la visível" — ela já é pública desde que
vira `pending_review` (todo o resto do sistema, incluindo busca e feed,
sempre tratou `pending_review` como público). Aprovação serve pra um admin
confirmar que o conteúdo não é impróprio; rejeição é o que de fato **some**
a receita do público, deixando-a visível só pro dono, com o motivo.

Isso cria um problema: se `update()` sempre reseta a receita pra `draft` e
publicá-la de novo (`publish()`) leva de volta a `pending_review` — que já
é público — um dono que teve a receita rejeitada por conteúdo impróprio
(ex.: foto íntima) poderia reenviar o exato mesmo conteúdo e ele voltaria a
ficar público imediatamente, sem passar por revisão de novo.

A flag `wasEverRejected` fecha esse buraco: `Recipe::reject()` a marca como
`true` **permanentemente** — nem `approve()` nem `update()` a resetam.
`Recipe::isVisibleTo(?Ulid $viewerId)` só mostra uma receita `pending_review`
pra quem não é dono quando `wasEverRejected === false`. Uma vez rejeitada,
a receita só volta a ser pública de verdade depois que um admin a aprova
explicitamente (`approve()`) de novo.

**Ponto crítico:** essa regra precisa valer tanto no `Recipe::isVisibleTo()`
quanto nos filtros SQL de `EloquentRecipeRepository::search()` (branch sem
dono, usada por descoberta pública) e `::forOwners()` (usada pelo feed de
quem eu sigo) — ambos os métodos filtram por `status` direto em SQL, por
performance (evitar N+1 chamando `isVisibleTo()` por linha), então corrigir
só o método de Domain deixaria o loophole aberto exatamente nos dois
lugares onde um estranho descobre a receita de outro usuário.

## `approve()`/`reject()` não têm guarda de transição de status, de propósito

Ao contrário do que se poderia esperar, `Recipe::approve()`/`reject()` e
`Media::approve()`/`reject()` (que já existia desde o Plano 3) **não**
lançam uma exceção de transição inválida — podem ser chamados a partir de
qualquer status. Isso é deliberado: permite revogar/reaprovar conteúdo
depois do fato, por exemplo rejeitar uma receita já `published` que foi
denunciada e, olhando de novo, tinha conteúdo impróprio. `wasEverRejected`
nunca é resetada por isso — mesmo uma receita aprovada de novo depois de
já ter sido rejeitada uma vez continua carregando a flag.

`Ingredient::approve()`/`reject()` seguem a mesma permissividade, mas com
uma diferença de implementação: `Ingredient` é `final readonly class`, então
os dois métodos **devolvem uma nova instância** em vez de mutar o objeto
existente — quem chama precisa salvar o valor de retorno, não o original.

## Banimento de usuário — sem histórico, com revogação de token imediata

`User::ban(Ulid $bannedBy, string $reason)`/`unban()` sobrescrevem o estado
atual de banimento (`bannedAt`/`banReason`/`bannedBy`) — não existe uma
tabela de histórico de banimentos, só o banimento vigente, guardado como
colunas em `users`. Essa foi uma escolha explícita: o histórico de "quem
foi banido quando e por quê, todas as vezes" não é um requisito do produto
hoje, e adicionar uma tabela separada só pra isso seria complexidade sem
necessidade comprovada (YAGNI).

Banir alguém revoga todos os tokens de acesso já emitidos
(`AccessTokenIssuerInterface::revokeAllFor()`), então uma sessão já aberta
para de funcionar imediatamente — o usuário banido não precisa esperar o
token expirar nem fazer logout. Tentar logar de novo devolve `403` com o
motivo do banimento (`LoginUser` lança `UserBannedException`, traduzida
pelo `LoginController`).

`User::restoreModerationState()` é um método de **hidratação**, não uma
ação de domínio — existe só pra `EloquentUserRepository` reconstituir o
estado exato vindo do banco, incluindo o `bannedAt` histórico original, que
`ban()` não conseguiria replayar (sempre grava "agora").

## Report — desacoplado da ação de moderação

`Report::create()` só grava que alguém denunciou algo (`recipe`, `user` ou
`comment`), com um motivo obrigatório, e nasce com `status = open`.
`Report::resolve()` marca a denúncia como `reviewed` (o admin agiu sobre o
alvo) ou `dismissed` (o admin olhou e decidiu que não era procedente) —
mas **não aciona nenhuma ação automaticamente**. Se o conteúdo denunciado
precisa ser removido, isso é uma chamada separada e explícita a
`RejectRecipe`/`BanUser`/`AdminDeleteComment` — a decisão de "resolver a
denúncia" e a decisão de "agir sobre o conteúdo" são independentes.

`ReportContent` valida que o alvo denunciado existe de verdade (olhando o
repositório certo conforme `targetType`) e que ninguém denuncia a si mesmo
(só possível pra `targetType = user`). Denunciar o próprio `Recipe`/
`Comment` é permitido — não há necessidade de bloquear isso.

## Endpoints HTTP

| Método | Rota | Quem pode | Observação |
|---|---|---|---|
| `PATCH` | `/recipes/{recipe}/approve` | admin | sem corpo |
| `PATCH` | `/recipes/{recipe}/reject` | admin | `{ "reason": "..." }`, obrigatório |
| `PATCH` | `/ingredients/{ingredient}/approve` | admin | sem corpo |
| `PATCH` | `/ingredients/{ingredient}/reject` | admin | sem corpo |
| `PATCH` | `/users/{username}/promote` | admin | sem corpo |
| `PATCH` | `/users/{username}/ban` | admin | `{ "reason": "..." }`, obrigatório |
| `PATCH` | `/users/{username}/unban` | admin | sem corpo |
| `DELETE` | `/comments/{comment}` | dono OU admin | rota já existia (Plano 4); admin ignora posse |
| `POST` | `/reports` | qualquer usuário autenticado | `{ "target_type", "target_id", "reason" }` |
| `GET` | `/reports?status=` | admin | `status` default `open` |
| `PATCH` | `/reports/{report}` | admin | `{ "status": "reviewed"\|"dismissed", "note": "..." }` |

## Limitação conhecida: admin não tem leitura de receita já escondida

Uma vez que uma receita é rejeitada (`reject()`, status `rejected`) — ou
volta a `pending_review` depois de já ter sido rejeitada uma vez
(`wasEverRejected === true`, ver acima) — `GetRecipe` (`GET /recipes/{id}`) e
`SearchRecipes` (`GET /recipes`) escondem essa receita de **todo mundo** que
não seja o dono, **incluindo admins**. `Recipe::isVisibleTo()` não faz
nenhuma exceção para papel de usuário; ela só compara `viewerId` contra
`ownerId`.

Na prática, isso significa que um admin resolvendo uma denúncia
(`Report`) sobre uma receita que o dono reenviou depois de rejeitada não tem
como dar um `GET` nessa receita para revisá-la antes de decidir — só
consegue chamar `PATCH /recipes/{id}/approve`/`reject` "às cegas", sem ver o
conteúdo atual pela API. Na prática hoje isso é contornado consultando o
banco diretamente ou confiando no que a denúncia (`Report::reason()`) e o
histórico de contato com o autor relatam, o que está longe de ideal para um
fluxo de moderação sério.

Isso é uma lacuna real, não um bug do que foi construído neste plano — dar
a admins um caminho de leitura para conteúdo escondido é uma mudança de
comportamento em `GetRecipe`/`SearchRecipes`/`isVisibleTo()` que merece seu
próprio desenho (por exemplo: um parâmetro explícito "ver como admin", ou um
endpoint de leitura só-para-moderação) em vez de ser encaixada como
correção lateral aqui. Fica registrada para não ser descoberta por surpresa
depois — resolver isso é trabalho de um plano futuro.

## Bootstrap do primeiro admin

Não existe endpoint HTTP pra criar o primeiro admin (seria um jeito de um
usuário comum se promover). O primeiro admin é criado via linha de
comando, direto no servidor:

```bash
php artisan user:promote <username>
```

Depois que existe pelo menos um admin, promoções seguintes podem usar o
endpoint HTTP (`PATCH /users/{username}/promote`), já que aí exigem um
admin autenticado.
