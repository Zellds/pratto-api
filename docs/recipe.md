# Domínio Recipe

## Para que serve

Uma `Recipe` (receita) pertence a um dono (`ownerId`, um `User`) e descreve um prato:
título, descrição, número de porções, tempo de preparo, uma lista de ingredientes
(quantidade + unidade de medida + posição) e uma lista de passos (instrução +
posição). Toda receita nasce em rascunho e passa por um ciclo de vida curto antes
de ficar publicamente visível — hoje esse ciclo tem só o primeiro degrau
implementado (enviar para revisão); a aprovação final ainda não existe via API.

Código-fonte de referência: `app/Domain/Recipe/Recipe.php`,
`app/Domain/Recipe/RecipeStatus.php`, `app/Domain/Recipe/RecipeIngredient.php`,
`app/Domain/Recipe/RecipeStep.php`, `app/Domain/Recipe/MeasurementUnit.php`.

A resposta de receita (`RecipeResource`) também expõe campos agregados dos
domínios `Rating` e `Comment` — `averageRating`/`ratingsCount` (ver
[docs/rating.md](./rating.md), inclusive a limitação documentada de que só
`GET /recipes`/`GET /recipes/{id}` calculam esses valores de verdade) e a
listagem de comentários de uma receita (ver [docs/comment.md](./comment.md)).

## Ciclo de vida (status)

`RecipeStatus` (`app/Domain/Recipe/RecipeStatus.php`) define quatro estados:

| Status | Valor no banco/JSON | Quando ocorre |
|---|---|---|
| Draft | `draft` | Estado inicial, ao criar. Também é para onde a receita volta sempre que é atualizada (`update()`), mesmo que estivesse em `pending_review`. |
| PendingReview | `pending_review` | Depois que o dono chama `publish()` num rascunho. |
| Published | `published` | Reservado para um fluxo de moderação/aprovação futuro. Nenhum caso de uso hoje leva uma receita a este estado — não existe endpoint que faça essa transição. |
| Rejected | `rejected` | Reservado do mesmo jeito, para um fluxo de moderação que rejeita a receita. Também não é atingível hoje. |

A única transição implementada é `Draft → PendingReview`, via `Recipe::publish()`:

```php
public function publish(): void
{
    if ($this->status !== RecipeStatus::Draft) {
        throw new InvalidRecipeStatusTransitionException($this->status, RecipeStatus::PendingReview);
    }

    $this->status = RecipeStatus::PendingReview;
}
```

- **Transição legal**: chamar `publish()` numa receita `draft`. Resultado: status vira
  `pending_review`, resposta HTTP `200 OK` com a receita atualizada.
- **Transição ilegal**: chamar `publish()` numa receita que já não está em `draft`
  (por exemplo, já `pending_review`). `Recipe::publish()` lança
  `InvalidRecipeStatusTransitionException` (`app/Domain/Recipe/InvalidRecipeStatusTransitionException.php`).
  O `RecipeController::publish()` captura essa exceção e responde
  **`409 Conflict`** com a mensagem da exceção (`Cannot transition recipe from "..." to "...".`).
  Antes da correção que motivou esta documentação, esse caso escapava sem handler
  e virava um `500` — hoje está coberto por teste em
  `tests/Feature/Http/Recipe/RecipeCrudEndpointTest.php`.

Note que `update()` sempre reseta o status para `draft`, mesmo que a receita
estivesse `pending_review` — editar o conteúdo manda a receita de volta para
revisão do zero.

## Regras de visibilidade

`Recipe::isVisibleTo(?Ulid $viewerId): bool` decide se um visitante enxerga a receita:

- Se o status é `pending_review` ou `published`, **qualquer um** enxerga (autenticado ou não).
- Caso contrário (`draft` ou `rejected`), só o dono enxerga.

Isso vale tanto para leitura individual (`GET /recipes/{id}`) quanto para a
listagem pública (`GET /recipes` sem `?mine`).

**Anti-enumeração**: quando a receita não existe, ou existe mas não é visível
para quem está pedindo, a resposta é a mesma: `404 Not Found`. Isso é
proposital — impede que alguém descubra, por tentativa e erro, que um
determinado ID de receita existe mas está em rascunho de outra pessoa. Ver
`GetRecipe::__invoke()` (`app/Application/Recipe/UseCases/GetRecipe.php`):

```php
if ($recipe === null || ! $recipe->isVisibleTo($viewer)) {
    throw RecipeNotFoundException::forId(Ulid::fromString($recipeId));
}
```

## Regras de posse (ownership)

Toda mutação (`update`, `publish`, `destroy`) verifica posse **antes** de
qualquer efeito, via `Recipe::assertOwnedBy(Ulid $userId)`:

```php
public function assertOwnedBy(Ulid $userId): void
{
    if (! $this->ownerId->equals($userId)) {
        throw RecipeNotOwnedException::forRecipe($this->id);
    }
}
```

Se quem está autenticado não é o dono, a exceção `RecipeNotOwnedException` é
capturada no controller e retorna **`403 Forbidden`**. Diferente da leitura, aqui
não há anti-enumeração: se a receita existe mas você não é dono, você recebe
403 (não 404) — o controller já sabe que ela existe porque a carregou do
repositório antes de checar posse.

## Escala de porções (`?portions=N`)

`GET /recipes/{id}` aceita um parâmetro de query opcional `portions` (inteiro,
mínimo 1). Quando informado, `Recipe::scaledIngredients()` recalcula a
quantidade de cada ingrediente proporcionalmente:

```php
public function scaledIngredients(?int $requestedPortions): array
{
    if ($requestedPortions === null || $requestedPortions === $this->portions) {
        return $this->ingredients;
    }

    if ($requestedPortions <= 0) {
        throw new InvalidArgumentException('Requested portions must be greater than zero.');
    }

    $ratio = $requestedPortions / $this->portions;

    return array_map(static fn (RecipeIngredient $ingredient) => $ingredient->scaledBy($ratio), $this->ingredients);
}
```

- Sem `?portions` (ou com o mesmo valor da receita), as quantidades originais
  são devolvidas sem modificação.
- Com um valor diferente, cada quantidade é multiplicada por
  `portionsPedidas / portionsOriginais`. A unidade de medida não muda — só a
  quantidade numérica é escalada linearmente.
- O branch de `portions <= 0` existe na camada de domínio mas hoje é
  inalcançável via HTTP: a validação do controller (`'portions' => ['nullable', 'integer', 'min:1']`)
  já rejeita valores menores que 1 com `422` antes de chegar ao domínio.
- A escala é aplicada só na leitura (`GET /recipes/{id}`); ela não persiste —
  a receita salva continua com as quantidades originais para o número de
  porções cadastrado.

Exemplo: um ingrediente com `quantity: 2.0` numa receita de 4 porções, lido
com `?portions=8`, volta como `quantity: 4.0` na resposta.

## Referência de endpoints

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`.

| Método | Rota | Auth | Corpo (request) | Query params | Sucesso | Erros |
|---|---|---|---|---|---|---|
| GET | `/recipes` | Pública (opcionalmente `auth:sanctum` via `?mine`) | — | `q` (string, máx. 120), `mine` (`0`\|`1`\|`true`\|`false`), `page` (inteiro ≥ 1) | `200` — lista de receitas (`RecipeResource` cada) | `401` se `mine` truthy sem autenticação · `422` em query inválida |
| GET | `/recipes/{id}` | Pública (viewer opcional via Sanctum) | — | `portions` (inteiro ≥ 1) | `200` — receita (`RecipeResource`) | `404` se não existe ou não é visível para quem pede · `422` em `portions` inválido |
| POST | `/recipes` | `auth:sanctum` | ver [corpo de criação/edição](#corpo-de-criaçãoedição) | — | `201` — receita criada, status `draft` | `401` sem autenticação · `422` em validação |
| PATCH | `/recipes/{id}` | `auth:sanctum` | ver [corpo de criação/edição](#corpo-de-criaçãoedição) | — | `200` — receita atualizada, status volta para `draft` | `401` sem autenticação · `403` se não é o dono · `404` se não existe · `422` em validação |
| POST | `/recipes/{id}/publish` | `auth:sanctum` | — | — | `200` — receita com status `pending_review` | `401` sem autenticação · `403` se não é o dono · `404` se não existe · `409` se o status atual não é `draft` |
| DELETE | `/recipes/{id}` | `auth:sanctum` | — | — | `204` — sem corpo (soft delete) | `401` sem autenticação · `403` se não é o dono · `404` se não existe |

### Corpo de criação/edição

Usado por `POST /recipes` e `PATCH /recipes/{id}` (validado por
`StoreRecipeRequest` / `UpdateRecipeRequest`, ambos usando o trait
`App\Http\Requests\Concerns\ValidatesRecipeContent`):

```json
{
  "title": "Bolo de cenoura",
  "description": "Bolo simples e rápido",
  "portions": 8,
  "prep_time_minutes": 60,
  "ingredients": [
    { "ingredient_name": "Cenoura", "quantity": 3, "unit": "unidade", "position": 0 }
  ],
  "steps": [
    { "position": 0, "instruction": "Bata tudo no liquidificador." }
  ]
}
```

Regras de validação:

- `title`: obrigatório, string, máx. 120.
- `description`: obrigatório, string, máx. 2000.
- `portions`: obrigatório, inteiro, mínimo 1.
- `prep_time_minutes`: obrigatório, inteiro, mínimo 1.
- `ingredients`: obrigatório, array, mínimo 1 item. Cada item:
  - `ingredient_id`: opcional, string, deve existir em `ingredients.id`.
  - `ingredient_name`: obrigatório **se** `ingredient_id` não for enviado; string, máx. 80.
  - `quantity`: obrigatório, numérico, mínimo 0.01.
  - `unit`: obrigatório, uma das opções de `MeasurementUnit` (`g`, `kg`, `ml`, `l`, `unidade`, `xicara`, `colher_sopa`, `colher_cha`, `pitada`, `a_gosto`).
  - `position`: obrigatório, inteiro, mínimo 0.
- `steps`: obrigatório, array, mínimo 1 item. Cada item:
  - `position`: obrigatório, inteiro, mínimo 0.
  - `instruction`: obrigatório, string, máx. 1000.

Como um item de ingrediente é resolvido (`ingredient_id` vs. `ingredient_name`)
é explicado em [docs/ingredient.md](./ingredient.md) — a regra de negócio vive
no domínio `Ingredient`, não no `Recipe`.

### Forma da resposta (`RecipeResource`)

```json
{
  "id": "01J...",
  "ownerId": "01J...",
  "title": "Bolo de cenoura",
  "description": "Bolo simples e rápido",
  "portions": 8,
  "prepTimeMinutes": 60,
  "status": "draft",
  "ingredients": [
    { "ingredientId": "01J...", "quantity": 3, "unit": "unidade", "position": 0 }
  ],
  "steps": [
    { "position": 0, "instruction": "Bata tudo no liquidificador." }
  ]
}
```

## O filtro `?mine` em `GET /recipes`

- `?mine=1` (ou `true`): exige autenticação (`401` se não autenticado). Retorna
  **todas** as receitas do usuário autenticado, de qualquer status — incluindo
  rascunhos —, ignorando o filtro público de visibilidade. Ou seja, `mine`
  troca completamente o critério de "quem pode ver o quê": em vez de "status
  público", vira "pertence a mim".
- `?mine=0` (ou `false`), ou ausência do parâmetro: aplica o filtro público
  normal (só `pending_review`/`published`), funciona sem autenticação.
- O parâmetro `q` continua se aplicando normalmente em conjunto com `mine`.

Ver `RecipeController::index()` e `EloquentRecipeRepository::search()` — quando
um `ownerId` é passado, a query nem filtra por status; quando não é, ela
restringe a `whereIn('status', [pending_review, published])`.

## Busca por texto (`?q=`)

`?q=` faz busca full-text em português sobre **título + descrição da receita +
nomes dos ingredientes** (não busca em passos/instruções). Isso é implementado
via uma coluna `search_vector` (tipo `tsvector` do Postgres) na tabela
`recipes`, recalculada a cada `save()` (`EloquentRecipeRepository::refreshSearchVector()`)
concatenando `title`, `description` e os nomes dos ingredientes ligados àquela
receita.

A consulta usa `plainto_tsquery('portuguese', ?)` com bind de parâmetro — não
`to_tsquery`. Isso importa porque `plainto_tsquery` aceita **qualquer texto
livre do usuário** e o converte automaticamente numa consulta de busca válida
(sem operadores especiais), enquanto `to_tsquery` exigiria uma sintaxe
específica e lançaria erro de sintaxe SQL em entradas arbitrárias. Ou seja,
`?q=qualquer coisa digitada por um usuário` nunca quebra a query por causa de
caracteres especiais.

## Como ingredientes são referenciados

Ao criar ou atualizar uma receita, cada item de `ingredients` referencia um
ingrediente do catálogo compartilhado do domínio `Ingredient`, seja por
`ingredient_id` (um ingrediente já existente) ou por `ingredient_name` (texto
livre, que é resolvido/criado automaticamente se ainda não existir). Essa
resolução, a normalização de nomes e as regras de deduplicação estão
documentadas em [docs/ingredient.md](./ingredient.md).
