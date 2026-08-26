# Domínio Pantry

## Para que serve

`Pantry` é a despensa/lista de compras compartilhada do usuário: uma lista
de ingredientes reutilizável, sem reset mensal e sem histórico de "meses"
— ao contrário do que o nome sugere à primeira vista, não existe nenhum
conceito de período no domínio. Cada item (`PantryItem`) tem exatamente um
estado binário, `needsToBuy`: ou precisa comprar, ou já está em casa. Não há
um terceiro estado, nem quantidade "zerada" como sinônimo de "precisa
comprar" — os dois campos (`quantity` e `needsToBuy`) são independentes.

`isFixed` é só uma flag informativa ("este item costuma estar sempre na
minha despensa, ex.: sal, óleo") — nada no domínio ou na aplicação lê
`isFixed` para mudar comportamento (não afeta upsert, não afeta limites, não
afeta nenhuma regra de visibilidade). É armazenada e devolvida tal como
enviada, para o cliente decidir o que fazer com ela (por exemplo, destacar
esses itens numa UI).

Uma despensa tem um dono e, opcionalmente, membros convidados — dono e
membros têm acesso total e simétrico aos itens (ver
[Modelo de acesso](#modelo-de-acesso) abaixo). Um usuário pode ter várias
despensas próprias ao mesmo tempo; não existe o conceito de "despensa
principal" nem limite de uma por usuário.

Código-fonte de referência: `app/Domain/Pantry/Pantry.php`,
`app/Domain/Pantry/PantryMembership.php`, `app/Domain/Pantry/PantryItem.php`,
`app/Domain/Pantry/PantryLimits.php`,
`app/Domain/Pantry/Contracts/PantryRepositoryInterface.php`,
`app/Domain/Pantry/Contracts/PantryMembershipRepositoryInterface.php`,
`app/Domain/Pantry/Contracts/PantryItemRepositoryInterface.php`,
`app/Application/Pantry/UseCases/CreatePantry.php`,
`app/Application/Pantry/UseCases/DeletePantry.php`,
`app/Application/Pantry/UseCases/ListMyPantries.php`,
`app/Application/Pantry/UseCases/InvitePantryMember.php`,
`app/Application/Pantry/UseCases/RemovePantryMember.php`,
`app/Application/Pantry/UseCases/LeavePantryMembership.php`,
`app/Application/Pantry/UseCases/ListPantryMembers.php`,
`app/Application/Pantry/UseCases/AddPantryItem.php`,
`app/Application/Pantry/UseCases/UpdatePantryItem.php`,
`app/Application/Pantry/UseCases/DeletePantryItem.php`,
`app/Application/Pantry/UseCases/ListPantryItems.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPantryRepository.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPantryMembershipRepository.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPantryItemRepository.php`.

## Os dois limites, e por que só existem na camada de aplicação

`PantryLimits` (`app/Domain/Pantry/PantryLimits.php`) define duas
constantes:

```php
public const int MAX_PANTRIES_PER_USER = 5;
public const int MAX_MEMBERS_PER_PANTRY = 20;
```

**5 despensas por usuário, no total — próprias e como membro somadas, não
dois contadores separados.** `PantryRepositoryInterface::countPantriesForUser()`
(implementado em `EloquentPantryRepository::accessibleIdsFor()`) faz a união
de duas listas de IDs — despensas onde `owner_id` é o usuário, mais
despensas onde existe uma linha em `pantry_members` para o usuário — e conta
o resultado único (`->merge($memberPantryIds)->unique()`):

```php
private function accessibleIdsFor(Ulid $userId): array
{
    $ownedIds = EloquentPantry::query()->where('owner_id', $userId->value())->pluck('id');
    $memberPantryIds = EloquentPantryMember::query()->where('user_id', $userId->value())->pluck('pantry_id');

    return $ownedIds->merge($memberPantryIds)->unique()->values()->all();
}
```

Isso significa que um usuário que já é dono de 5 despensas não pode aceitar
mais nenhum convite, e um usuário que já é membro de 5 despensas de outras
pessoas não pode criar uma despensa própria — é um único orçamento de 5,
compartilhado entre "ter" e "participar de". O limite é checado em dois
lugares, um para cada lado da relação:

- `CreatePantry::__invoke()`: conta as despensas do **dono** antes de criar;
  excede o limite → `PantryLimitExceededException` (`422`).
- `InvitePantryMember::__invoke()`: conta as despensas do **convidado**
  antes de salvar o convite; excede o limite → a mesma
  `PantryLimitExceededException` (`422`), mas agora referente ao convidado,
  não a quem está convidando.

**20 membros por despensa.** `PantryMembershipRepositoryInterface::countMembers()`
conta só as linhas de `pantry_members` daquela despensa (o dono nunca é uma
linha em `pantry_members`, então não entra nessa contagem). Checado apenas em
`InvitePantryMember::__invoke()`, antes de salvar o convite; excede o limite
→ `PantryMemberLimitExceededException` (`422`).

**Por que os dois limites são checados só na aplicação, e não como
constraint de banco**: um `CHECK` de coluna, ou até uma constraint composta,
consegue expressar regras sobre os *valores* de uma linha (como o `CHECK
(score IN (...))` de `Rating`, ver [docs/rating.md](./rating.md#validação-de-passo-05-nos-dois-lugares)),
mas não consegue expressar "no máximo N linhas relacionadas a este `id`" —
isso é uma agregação sobre múltiplas linhas de outra tabela (ou da mesma
tabela, no caso das duas fontes somadas de `MAX_PANTRIES_PER_USER`), algo que
o modelo relacional só resolve com trigger (fora do padrão deste projeto,
que não usa triggers) ou verificação em nível de aplicação. Os dois limites
vivem inteiramente em `CreatePantry`/`InvitePantryMember`, sem nenhum reforço
equivalente no schema — diferente de casos como a nota única de `Rating` ou o
score-step de `Rating`, aqui não existe uma "última linha de defesa" no
banco.

## Modelo de acesso

Uma despensa é acessível a exatamente duas categorias de usuário: o dono
(`Pantry::ownerId()`) e quem tem uma linha em `pantry_members` para aquela
despensa. `PantryMembershipRepositoryInterface::hasAccess()`
(`EloquentPantryMembershipRepository::hasAccess()`) resolve isso com uma
checagem de dono OU checagem de membro:

```php
public function hasAccess(Ulid $pantryId, Ulid $userId): bool
{
    $isOwner = EloquentPantry::query()
        ->where('id', $pantryId->value())
        ->where('owner_id', $userId->value())
        ->exists();

    return $isOwner || $this->isMember($pantryId, $userId);
}
```

Todo caso de uso que opera sobre uma despensa, seus membros ou seus itens
segue o mesmo formato — busca a despensa (ou o item, que carrega seu próprio
`pantryId()`), e se ela não existe **ou** o ator não tem acesso, lança a
mesma exceção:

```php
$pantry = $this->pantries->findById($pantryUlid);

if ($pantry === null || ! $this->memberships->hasAccess($pantryUlid, $actorUlid)) {
    throw PantryNotFoundException::forId($pantryUlid);
}
```

`PantryNotFoundException` é deliberadamente a **mesma** exceção para "a
despensa não existe" e para "a despensa existe, mas você não tem acesso" —
uma política anti-enumeração igual à de `Recipe`/`Rating`: um usuário sem
acesso não consegue distinguir, pela resposta HTTP (sempre `404`), se o ID
que ele tentou é inválido ou se pertence a uma despensa real de outra
pessoa. Isso vale para `DeletePantry`, `InvitePantryMember`,
`RemovePantryMember`, `ListPantryMembers`, `AddPantryItem`,
`UpdatePantryItem`, `DeletePantryItem` e `ListPantryItems`.

Operações que exigem ser o dono (não basta ter acesso) checam isso **depois**
de já ter confirmado a existência/acesso, com `Pantry::assertOwnedBy()`:

```php
public function assertOwnedBy(Ulid $userId): void
{
    if (! $this->ownerId->equals($userId)) {
        throw PantryNotOwnedException::forPantry($this->id);
    }
}
```

`PantryNotOwnedException` vira `403` (não `404`) — porque, nesse ponto, o
ator já provou ter acesso à despensa (é membro), então não faz sentido
esconder a existência dela; a resposta só nega a ação específica. Isso se
aplica a `DeletePantry` (só o dono apaga a despensa),
`InvitePantryMember` (só o dono convida) e `RemovePantryMember` (só o dono
remove outra pessoa) — `PantryController`/`PantryMemberController` traduzem
`PantryNotOwnedException` em `403` nesses três casos.

**Exceção**: `LeavePantryMembership` não segue esse padrão — ver a seção
abaixo.

Os itens de uma despensa (`PantryItem`) não guardam nenhuma referência a
"quem os criou" — `UpdatePantryItem`/`DeletePantryItem` resolvem o item pelo
próprio `id` do item e checam `hasAccess()` contra o `pantryId()` que o item
já carrega, sem exigir que a rota `{pantry}` da URL bata com esse
`pantryId()`. Na prática, `PATCH`/`DELETE /pantries/{pantry}/items/{item}`
ignoram o `{pantry}` da URL para efeito de autorização — quem valida acesso
é sempre o `pantryId` real do item, encontrado pelo `{item}`. Isso nunca
muda o resultado observável para um cliente bem-comportado (que sempre usa o
`{pantry}` correto na URL de um item que pertence a ele), mas é uma
particularidade da implementação que vale registrar.

## `LeavePantryMembership`: idempotente mesmo pra quem nunca foi membro

`LeavePantryMembership::__invoke()` (`app/Application/Pantry/UseCases/LeavePantryMembership.php`)
é a única operação de despensa que **não** usa `hasAccess()`. Ela checa só
que a despensa existe, e então chama `removeMember()` incondicionalmente:

```php
public function __invoke(string $pantryId, string $actorId): void
{
    $pantryUlid = Ulid::fromString($pantryId);

    if ($this->pantries->findById($pantryUlid) === null) {
        throw PantryNotFoundException::forId($pantryUlid);
    }

    $this->memberships->removeMember($pantryUlid, Ulid::fromString($actorId));
}
```

`EloquentPantryMembershipRepository::removeMember()` é um
`DELETE ... WHERE pantry_id = ? AND user_id = ?` que simplesmente não afeta
nenhuma linha quando o par não existe — não lança exceção, não muda o código
de resposta. Consequência: "sair" de uma despensa da qual você nunca foi
membro devolve `204` normalmente, exatamente como sair de uma da qual você
de fato fazia parte. **Mesmo padrão do `UnfollowUser`** do domínio `Follow`
(ver [docs/follow.md](./follow.md#idempotência-de-followuserunfollowuser)):
a operação "reverter uma associação" nunca é erro, só um no-op quando não
havia associação para reverter.

Uma consequência não-óbvia dessa assimetria: como o `DELETE
/pantries/{pantry}/members/{username}` roteia para `LeavePantryMembership`
sempre que `{username}` é o próprio usuário autenticado (ver
[Referência de endpoints](#referência-de-endpoints)), um **dono** que chame
essa rota com o próprio username também cai em `LeavePantryMembership` — que
só mexe em `pantry_members`, tabela onde o dono nunca tem linha. O resultado
é um no-op silencioso (`204`, nada muda): a despensa não é apagada, e o dono
continua dono. Apagar a própria despensa exige o endpoint dedicado,
`DELETE /pantries/{pantry}`.

## Upsert de `AddPantryItem`

Adicionar um ingrediente que já está na despensa não cria uma segunda linha
— atualiza a existente. `AddPantryItem::__invoke()`
(`app/Application/Pantry/UseCases/AddPantryItem.php`) resolve o ingrediente
primeiro (reaproveitando `ResolveIngredient`, o mesmo caso de uso do Plano 2
usado por `CreateRecipe`/`UpdateRecipe` — ver
[docs/ingredient.md](./ingredient.md#normalização-de-nome-e-deduplicação)
para a resolução por `ingredient_id` ou por `ingredient_name`/normalização),
e só depois decide entre criar ou atualizar:

```php
$ingredient = ($this->resolveIngredient)(new ResolveIngredientInput($ingredientId, $ingredientName));
$existing = $this->items->findByPantryAndIngredient($pantryUlid, $ingredient->id());

if ($existing !== null) {
    $existing->updateQuantity($quantity, $unit);
    $existing->toggleNeedsToBuy(true);
    $this->items->save($existing);

    return PantryItemOutput::fromDomain($existing);
}

$item = PantryItem::create(Ulid::generate(), $pantryUlid, $ingredient->id(), $quantity, $unit, $isFixed);
$this->items->save($item);
```

- Primeiro `POST` para um ingrediente novo na despensa: cria um `PantryItem`
  novo, com `needsToBuy: true` sempre (`PantryItem::create()` fixa esse
  valor — não é possível criar um item já marcado como "em casa").
- `POST` seguinte para o **mesmo** `ingredient_id`/nome normalizado na
  **mesma** despensa: reaproveita a linha existente — troca `quantity`/`unit`
  para os valores recebidos (não soma à quantidade anterior) e força
  `needsToBuy` de volta para `true`, mesmo que o item estivesse marcado como
  "já tenho em casa". O `id` do registro não muda. `isFixed` do item
  existente **não** é tocado pelo upsert — só é setado na criação; para
  mudar `isFixed` depois é preciso `PATCH .../items/{item}`.
- Reforço no banco: a tabela `pantry_items` tem
  `unique(['pantry_id', 'ingredient_id'])`
  (migration `2026_08_26_000002_create_pantry_items_table.php`) — mesmo que
  a camada de aplicação tivesse um bug e tentasse inserir duas linhas para o
  mesmo par, o banco rejeitaria a segunda, no mesmo padrão de `Rating`
  (`unique(recipe_id, user_id)`) e `Follow`
  (`unique(follower_id, followee_id)`).

Não há endpoint para remover um ingrediente por nome/duplicata — a única
forma de tirar um item da despensa é `DELETE .../items/{item}` pelo `id` do
item.

## Referência de endpoints

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`, e exigem
`auth:sanctum` — não há nenhuma leitura pública de despensa (diferente de
`Recipe`/`Ingredient`/`User`, aqui toda a superfície de API é privada).

| Método | Rota | Corpo (request) | Sucesso | Erros |
|---|---|---|---|---|
| POST | `/pantries` | `{ "name": "..." }` (obrigatório, string, máx. 120) | `201` — despensa criada (`PantryResource`, `role: "owner"`) | `401` sem autenticação · `422` se `name` ausente/inválido, ou se o usuário já tem 5 despensas (próprias+membro) |
| GET | `/pantries` | — | `200` — lista de despensas do usuário (dono ou membro), mais recentes primeiro, cada uma com `role: "owner"`/`"member"` | `401` sem autenticação |
| DELETE | `/pantries/{pantry}` | — | `204` — despensa apagada | `401` sem autenticação · `404` se não existe ou sem acesso · `403` se tem acesso mas não é o dono |
| GET | `/pantries/{pantry}/members` | — | `200` — lista de membros, sempre incluindo o dono (`role: "owner"`) seguido dos convidados (`role: "member"`) | `401` sem autenticação · `404` se não existe ou sem acesso |
| POST | `/pantries/{pantry}/members` | `{ "username": "..." }` (obrigatório, string) | `204` — convidado adicionado (ou já era membro — idempotente) | `401` sem autenticação · `404` se a despensa não existe/sem acesso, ou se `username` não existe · `403` se tem acesso mas não é o dono · `422` se tentar convidar a si mesmo, se a despensa já tem 20 membros, ou se o convidado já tem 5 despensas |
| DELETE | `/pantries/{pantry}/members/{username}` | — | `204` — ver dupla função abaixo | `401` sem autenticação · `404`/`403` conforme o caminho tomado (ver abaixo) |
| GET | `/pantries/{pantry}/items` | — | `200` — lista de itens da despensa (`PantryItemResource` cada) | `401` sem autenticação · `404` se não existe ou sem acesso |
| POST | `/pantries/{pantry}/items` | `ingredient_id` **ou** `ingredient_name` (um dos dois obrigatório); `quantity` (numérico, opcional, padrão `1.0`, mín. `0.01`); `unit` (opcional, padrão `unidade`, um dos valores de `MeasurementUnit`); `is_fixed` (booleano, opcional, padrão `false`) | `201` — item criado, ou item existente atualizado (upsert, ver acima) | `401` sem autenticação · `404` se a despensa não existe/sem acesso · `422` em corpo inválido |
| PATCH | `/pantries/{pantry}/items/{item}` | Qualquer subconjunto de `needs_to_buy`, `quantity`, `unit`, `is_fixed` (todos opcionais — só os campos enviados são alterados) | `200` — item atualizado | `401` sem autenticação · `404` se o item não existe ou sem acesso à despensa dele · `422` em corpo inválido |
| DELETE | `/pantries/{pantry}/items/{item}` | — | `204` — item removido | `401` sem autenticação · `404` se o item não existe ou sem acesso à despensa dele |

### A dupla função do `DELETE /pantries/{pantry}/members/{username}`

Uma única rota cobre "sair da despensa" e "remover outro membro", decidido
por `PantryMemberController::destroy()` comparando `{username}` com o
usuário autenticado:

```php
if ($username === $request->user()->username) {
    $leavePantryMembership($pantry, $request->user()->id);
} else {
    $removePantryMember($pantry, $request->user()->id, $username);
}
```

- **`{username}` é o próprio usuário autenticado** → `LeavePantryMembership`.
  Só checa que a despensa existe (não checa acesso); idempotente mesmo para
  quem nunca foi membro (ver seção dedicada acima); nunca retorna `403`. Um
  dono que chame essa variante com o próprio username é um no-op (ver nota
  acima) — não apaga a despensa.
- **`{username}` é outra pessoa** → `RemovePantryMember`. Exige que quem
  chama seja o dono da despensa (`404` se não tem acesso, `403` se tem acesso
  mas não é dono); `404` se `{username}` não existe como usuário; remover
  alguém que não é membro é um no-op silencioso (mesma semântica de
  `removeMember()`, sem checagem de "era membro?" antes de deletar).

### Forma das respostas

`PantryResource`:

```json
{ "id": "01J...", "ownerId": "01J...", "name": "Minha despensa", "role": "owner" }
```

`role` é calculado por request (`"owner"` se `ownerId` bate com o usuário
autenticado, `"member"` caso contrário) — não é uma coluna persistida.

`PantryMemberResource` (um por linha em `GET /pantries/{pantry}/members`,
começando sempre pelo dono):

```json
{ "userId": "01J...", "username": "gabriel", "displayName": "Gabriel", "role": "owner" }
```

`PantryItemResource`:

```json
{
  "id": "01J...",
  "pantryId": "01J...",
  "ingredientId": "01J...",
  "quantity": 3.0,
  "unit": "unidade",
  "needsToBuy": true,
  "isFixed": false
}
```

`quantity` é serializado com `JSON_PRESERVE_ZERO_FRACTION` — uma quantidade
"redonda" como `3.0` chega como `3.0` no JSON, não `3` (mesma preocupação
documentada para `averageRating` em [docs/rating.md](./rating.md#como-a-agregação-averagerating-ratingscount-é-calculada-e-exposta),
mas resolvida aqui na direção oposta: aqui o objetivo é preservar a casa
decimal, lá o comportamento padrão do PHP de descartá-la é aceito).

## Exclusões deliberadas de escopo

- **Sem fluxo de convite com aceite.** `POST /pantries/{pantry}/members`
  adiciona o convidado imediatamente — não existe um estado "convite
  pendente" nem uma ação do convidado para aceitar/recusar. Ser adicionado a
  uma despensa é um efeito imediato e unilateral do dono, sem confirmação da
  outra parte (diferente de, por exemplo, um pedido de amizade). A única
  forma do convidado reverter isso é sair depois (`DELETE
  .../members/{seu_username}`).
- **Sem papel intermediário entre dono e membro.** Só existem dois papéis,
  `owner` e `member` — não há "administrador"/"editor com permissão de
  convidar" nem qualquer nível intermediário. Todo membro tem acesso de
  leitura e escrita total aos itens da despensa (adicionar, editar, remover),
  mas nenhuma ação de governança (convidar, remover outro membro, apagar a
  despensa) — essas três são exclusivas do dono.
- **Sem reset mensal ou qualquer noção de período.** Apesar do produto
  descrever a despensa como "reutilizável mês a mês", isso não é um
  comportamento do sistema — é apenas uma consequência de itens não
  expirarem nem serem limpos automaticamente. Não existe job, coluna de
  data de "ciclo" nem endpoint de "encerrar o mês" ou "resetar
  `needsToBuy`". O `isFixed` de um item não aciona nenhum comportamento
  automático (não é reposto, não é preservado especialmente em nenhuma
  rotina) — é só uma flag informativa que o cliente pode usar para destacar
  itens na UI.
- **Um usuário pode ter várias despensas próprias.** Não existe limite de
  "uma despensa por usuário" nem conceito de despensa principal/padrão — o
  único teto é o limite total de 5 despensas (próprias+membro somadas,
  ver acima).
