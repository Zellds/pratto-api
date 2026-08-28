# Domínio Follow

## Para que serve

`Follow` representa uma relação direcionada e binária entre dois usuários:
"A segue B". Não tem estado editável — ou a relação existe, ou não existe —
então não há `update()`, só criar (`create`) e reconstituir a partir do banco
(`reconstitute`). É a base de duas features: o feed (`GET /feed`, receitas de
quem você segue) e os contadores públicos de seguidores/seguindo expostos no
perfil (`docs/user.md`).

Código-fonte de referência: `app/Domain/Follow/Follow.php`,
`app/Domain/Follow/Contracts/FollowRepositoryInterface.php`,
`app/Domain/Follow/Exceptions/CannotFollowSelfException.php`,
`app/Application/Follow/UseCases/FollowUser.php`,
`app/Application/Follow/UseCases/UnfollowUser.php`,
`app/Application/Follow/UseCases/GetFeed.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentFollowRepository.php`.

## Idempotência de `FollowUser`/`UnfollowUser`

Repetir a ação nunca é erro — nem seguir quem você já segue, nem deixar de
seguir quem você não segue.

`FollowUser::__invoke()` (`app/Application/Follow/UseCases/FollowUser.php`)
checa existência antes de criar:

```php
if (! $this->follows->exists($followerUlid, $followeeUlid)) {
    $this->follows->save(Follow::create(Ulid::generate(), $followerUlid, $followeeUlid));
}
```

- Primeiro `POST /users/{username}/follow`: cria a linha `Follow`.
- `POST` seguinte para o mesmo par: `exists()` retorna `true`, nada é
  inserido de novo — a resposta continua `204`, sem erro.
- Reforço no banco: `follows` tem `unique(['follower_id', 'followee_id'])`
  (migration `2026_08_25_193847_create_follows_table.php`) — mesmo que a
  camada de aplicação tivesse um bug e tentasse inserir duas vezes, o banco
  rejeitaria a segunda.

`UnfollowUser::__invoke()` (`app/Application/Follow/UseCases/UnfollowUser.php`)
é ainda mais direto: sempre chama `delete()`, sem checar existência antes.
`EloquentFollowRepository::delete()` é um `DELETE ... WHERE follower_id = ?
AND followee_id = ?` que simplesmente não afeta nenhuma linha quando a
relação não existe — não lança exceção, não muda o código de resposta.
`DELETE /users/{username}/follow` para alguém que você nunca seguiu devolve
`204` normalmente, igual a quando a relação existia e foi removida.

## Autovalidação de self-follow (`CannotFollowSelfException`, 422)

Ninguém pode seguir a si mesmo. A checagem vive no domínio, não na camada de
aplicação nem no FormRequest — `Follow::create()`
(`app/Domain/Follow/Follow.php`):

```php
public static function create(Ulid $id, Ulid $followerId, Ulid $followeeId): self
{
    if ($followerId->equals($followeeId)) {
        throw CannotFollowSelfException::forUser($followerId);
    }

    return new self($id, $followerId, $followeeId, new DateTimeImmutable);
}
```

`FollowController::store()` traduz `CannotFollowSelfException` em `422`. Como
a checagem de idempotência (`exists()`) roda **antes** de `Follow::create()`,
e uma linha de self-follow nunca consegue existir no banco (ver abaixo), toda
tentativa de `POST /users/{seu_próprio_username}/follow` cai em
`Follow::create()` e lança a exceção — não existe um caminho onde "seguir a
si mesmo" vira um no-op silencioso.

Reforço no banco, igual ao padrão de `Score`/`Rating`
(ver [docs/rating.md](./rating.md#validação-de-passo-05-nos-dois-lugares)):
a migration adiciona um `CHECK` explícito —
`CHECK (follower_id <> followee_id)` — como última linha de defesa contra
qualquer inserção que não passe por `Follow::create()`.

**Assimetria deliberada com `UnfollowUser`**: `DELETE
/users/{seu_próprio_username}/follow` **não** devolve `422`. `UnfollowUser`
não faz nenhuma checagem de self-follow — ele só monta o `DELETE` e o
executa. Como uma linha de self-follow nunca existe (o `CHECK` do banco
impede que ela seja criada), o `DELETE` não afeta nenhuma linha e a resposta
é `204`, seguindo a mesma regra de idempotência do restante do domínio:
"deixar de seguir alguém que você não segue não é erro" — mesmo quando esse
"alguém" é você mesmo.

## Regra de visibilidade do feed (`forOwners`, mesma regra pública de `SearchRecipes`)

`GetFeed::__invoke()` (`app/Application/Follow/UseCases/GetFeed.php`) busca
quem o usuário segue (`followeeIdsFor`) e, se a lista estiver vazia, devolve
`[]` sem nem consultar a tabela `recipes` — evita uma query desnecessária
para quem não segue ninguém. Caso contrário, chama
`RecipeRepositoryInterface::forOwners($followeeIds, $page, $perPage)`
(`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentRecipeRepository.php`):

```php
$records = EloquentRecipe::query()
    ->with(['ingredients', 'steps'])
    ->whereIn('user_id', array_map(static fn (Ulid $id) => $id->value(), $ownerIds))
    ->where(function ($publicQuery) {
        $publicQuery->where('status', RecipeStatus::Published->value)
            ->orWhere(function ($pendingQuery) {
                $pendingQuery->where('status', RecipeStatus::PendingReview->value)
                    ->where('was_ever_rejected', false);
            });
    })
    ->orderByDesc('created_at')
    ->forPage($page, $perPage)
    ->get();
```

Isso é **exatamente** a mesma regra pública que `SearchRecipes` aplica em
`GET /recipes` sem `?mine`
(ver [docs/recipe.md](./recipe.md#o-filtro-mine-em-get-recipes)): `published`
sempre aparece, `pending_review` só aparece se a receita nunca foi rejeitada
(`was_ever_rejected = false`), e `draft`/`rejected` nunca aparecem — mesmo que
a receita seja de alguém que você segue. O feed não tem um conceito de "veja
os rascunhos de quem eu sigo"; ele é estritamente "conteúdo publicamente
visível, filtrado pelo grafo de quem eu sigo". O porquê desse filtro (e não o
antigo `whereIn(['pending_review', 'published'])` simples) é o mesmo loophole
de reposting fechado pelo Plano de Moderation — ver
[docs/moderation.md](./moderation.md#o-loophole-de-reposting-e-por-que-waseverrejected-existe).

Como em `SearchRecipes`, o agregado de rating (`averageRating`/
`ratingsCount`) é calculado numa única query em lote via
`RatingRepositoryInterface::averagesAndCountsFor()`, evitando N+1 — uma
query de agregação por página do feed, não uma por receita
(ver [docs/rating.md](./rating.md#como-a-agregação-averagerating-ratingscount-é-calculada-e-exposta)).

### Aprovação real de `pending_review → published` já existe (Plano de Moderation)

A transição `pending_review → published` agora existe de fato:
`PATCH /recipes/{id}/approve` (admin) chama `Recipe::approve()`. O que **não**
muda é o fato de que `pending_review` já era público antes da aprovação —
aprovar uma receita não é "torná-la visível pela primeira vez", é um admin
confirmando que o conteúdo está de acordo. `SearchRecipes` (`GET /recipes`),
`GetRecipe` (`GET /recipes/{id}`) e `GetFeed` (`GET /feed`) continuam tratando
`pending_review` e `published` como igualmente públicos — com uma exceção:
uma receita `pending_review` que já foi rejeitada alguma vez
(`wasEverRejected === true`) fica escondida de quem não é dono até um admin
aprová-la explicitamente. Ver
[docs/moderation.md](./moderation.md#o-loophole-de-reposting-e-por-que-waseverrejected-existe)
para o porquê dessa flag existir e
[docs/recipe.md](./recipe.md#regras-de-visibilidade) para a regra completa de
`isVisibleTo()`.

Na prática, isso significa que a busca pública e o feed continuam incluindo
receitas que ainda não passaram por revisão humana — qualquer receita que o
dono publicou (`POST /recipes/{id}/publish`, que só move `draft →
pending_review`) já aparece para todo mundo, inclusive no feed de quem o
segue, antes de qualquer aprovação explícita. Isso continua sendo o
comportamento esperado do sistema: moderação é reativa (rejeitar o que for
denunciado ou notado), não um gate bloqueante antes da publicação.

## Exclusões deliberadas de escopo

- **Sem endpoint de listar seguidores/seguidos.** `FollowRepositoryInterface`
  expõe `countFollowers()`/`countFollowing()` (usados por `GetUserProfile`,
  ver [docs/user.md](./user.md)) e `followeeIdsFor()` (usado internamente por
  `GetFeed`), mas não existe nenhum `GET /users/{username}/followers` ou
  `/following`. Um cliente sabe **quantos** seguidores/seguindo alguém tem,
  nunca **quem** são — a não ser que já saiba o username de antemão e teste
  se essa pessoa aparece no próprio feed.
- **Sem conceito de perfil privado.** Toda relação de follow é pública no
  sentido de que qualquer usuário autenticado pode seguir qualquer outro sem
  aprovação — não existe "solicitação de follow pendente" nem opção de
  tornar a própria conta privada. Seguir alguém é sempre um efeito imediato
  (`204` direto, sem estado intermediário).

## Referência de endpoints

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`.

| Método | Rota | Auth | Corpo (request) | Sucesso | Erros |
|---|---|---|---|---|---|
| POST | `/users/{username}/follow` | `auth:sanctum` | — | `204` — passou a seguir (ou já seguia — idempotente) | `401` sem autenticação · `404` se o username não existe · `422` se `{username}` for o próprio usuário autenticado (`CannotFollowSelfException`) |
| DELETE | `/users/{username}/follow` | `auth:sanctum` | — | `204` — deixou de seguir (ou já não seguia — idempotente, inclusive para o próprio usuário) | `401` sem autenticação · `404` se o username não existe |
| GET | `/feed` | `auth:sanctum` | — (query `page`, inteiro ≥ 1, opcional, padrão 1) | `200` — lista de receitas (`RecipeResource`, tamanho de página fixo 20) de quem o usuário autenticado segue, `pending_review`/`published`, mais recentes primeiro | `401` sem autenticação · `422` em `page` inválido |
| GET | `/users/{username}` | Pública | — | `200` — perfil público, incluindo `followersCount`/`followingCount` | `404` se o username não existe |

A última linha (`GET /users/{username}`) é o que torna possível descobrir
quem seguir a partir do perfil de alguém — sua documentação completa
(forma da resposta, alias `GET /me`) está em [docs/user.md](./user.md).

### Forma da resposta do feed

`GET /feed` devolve um array de receitas no mesmo formato de
`GET /recipes`/`GET /recipes/{id}` — ver
[docs/recipe.md](./recipe.md#forma-da-resposta-reciperesource) para o corpo
completo de `RecipeResource`, e [docs/rating.md](./rating.md) para como
`averageRating`/`ratingsCount` são calculados e arredondados.
