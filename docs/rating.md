# Domínio Rating

## Para que serve

`Rating` guarda a nota que um usuário deu a uma receita: um valor único de
1.0 a 5.0, em passos de 0.5. Cada par `(recipe, user)` tem no máximo um
`Rating` — não existe "histórico de notas" nem múltiplas avaliações do mesmo
usuário para a mesma receita. Avaliar de novo **substitui** a nota anterior
(upsert), nunca cria uma segunda linha nem apaga o rating por outra via.

Código-fonte de referência: `app/Domain/Rating/Rating.php`,
`app/Domain/Rating/Score.php`,
`app/Domain/Rating/Contracts/RatingRepositoryInterface.php`,
`app/Application/Rating/UseCases/RateRecipe.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentRatingRepository.php`.

## Regra de 1 nota por usuário (upsert, sem delete)

`RateRecipe::__invoke()` (`app/Application/Rating/UseCases/RateRecipe.php`) é
o único caso de uso do domínio, e ele é a única porta de entrada — não existe
`DeleteRating`/`UnrateRecipe`:

```php
$existing = $this->ratings->findByRecipeAndUser($recipeUlid, $userUlid);

if ($existing !== null) {
    $existing->changeScore($score);
    $rating = $existing;
} else {
    $rating = Rating::create(Ulid::generate(), $recipeUlid, $userUlid, $score);
}

$this->ratings->save($rating);
```

- Primeira avaliação de um usuário para uma receita: cria um `Rating` novo.
- Avaliação seguinte do mesmo usuário para a mesma receita: reaproveita a
  linha existente e só troca o `score` (`Rating::changeScore()`) — o `id` do
  registro não muda.
- `EloquentRatingRepository::save()` usa `updateOrCreate` por `id`, e a
  tabela `ratings` tem uma constraint `unique(['recipe_id', 'user_id'])`
  (migration `2026_08_25_071149_create_ratings_table.php`) como reforço no
  banco — mesmo que a camada de aplicação tivesse um bug e tentasse criar uma
  segunda linha para o mesmo par, o banco rejeitaria.
- Não há endpoint para remover uma nota. Se um usuário quiser "desfazer" a
  própria avaliação, a única forma via API hoje é reavaliar com outra nota —
  a nota não pode ser apagada, só substituída.

## Validação de passo 0.5 (nos dois lugares)

O valor permitido é 1.0 a 5.0 em incrementos de 0.5 (`1.0, 1.5, 2.0, ..., 5.0`
— 9 valores possíveis), validado em **três** camadas independentes:

1. **FormRequest** (`RateRecipeRequest`, `app/Http/Requests/RateRecipeRequest.php`):
   ```php
   'score' => ['required', 'numeric', Rule::in([1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0])],
   ```
   Rejeita valores fora do conjunto com `422` antes de qualquer código de
   aplicação/domínio rodar.
2. **Domínio** (`Score::create()`, `app/Domain/Rating/Score.php`): mesmo que
   a camada HTTP fosse contornada (por exemplo, um teste de Application layer
   chamando `RateRecipe` diretamente), o value object reforça a mesma regra —
   fora de 1.0–5.0 lança `InvalidArgumentException`, e fora do passo 0.5
   (`fmod($value * 2, 1.0) !== 0.0`) lança outra `InvalidArgumentException`
   distinta.
3. **Banco de dados**: a coluna `score` é `decimal(2,1)`, e a migration
   adiciona um `CHECK` explícito —
   `CHECK (score IN (1.0,1.5,2.0,2.5,3.0,3.5,4.0,4.5,5.0))` — como última
   linha de defesa caso alguma inserção pule as duas camadas acima.

Nenhuma dessas três camadas é redundante o suficiente para eliminar as
outras: o FormRequest é o que dá `422` amigável ao cliente HTTP; o domínio
protege qualquer chamador que não passe pelo FormRequest; o `CHECK` do banco
protege contra qualquer inserção que não passe pelo `Score::create()` (por
exemplo, uma migração de dados futura).

## Visibilidade

`RateRecipe` usa a mesma regra de visibilidade do domínio `Recipe`
(`Recipe::isVisibleTo()`, documentada em
[docs/recipe.md](./recipe.md#regras-de-visibilidade)): só é possível avaliar
uma receita `pending_review`/`published`, ou uma receita própria de qualquer
status. Se a receita não existe ou não é visível para quem está avaliando,
`RateRecipe` lança `RecipeNotFoundException`, que o `RatingController`
traduz em `404` — mesma política anti-enumeração do `Recipe` (não revela se
o ID existe mas está em rascunho de outra pessoa).

## Como a agregação (`averageRating`/`ratingsCount`) é calculada e exposta

A agregação não é mantida em uma coluna desnormalizada em `recipes` — ela é
calculada sob demanda a partir da tabela `ratings`, via
`RatingRepositoryInterface`:

- `averageAndCountFor(Ulid $recipeId)`: usada por `GetRecipe`
  (`app/Application/Recipe/UseCases/GetRecipe.php`), roda uma query só para
  aquela receita (`avg(score)`, `count(*)`).
- `averagesAndCountsFor(list<Ulid> $recipeIds)`: usada por `SearchRecipes`,
  roda **uma única query agregada** (`GROUP BY recipe_id`) para todas as
  receitas da página de resultados, evitando N+1 — uma query por página de
  listagem, não uma por receita.

```php
// GetRecipe::__invoke()
$aggregate = $this->ratings->averageAndCountFor($recipe->id());

return RecipeOutput::fromDomain($recipe, $requestedPortions)
    ->withRatingAggregate($aggregate['average'], $aggregate['count']);
```

Quando nenhuma receita tem rating, `averageAndCountFor`/`averagesAndCountsFor`
devolvem `average: null, count: 0` — não `0.0`. `RecipeOutput` reflete isso:
`averageRating` é `float|null` (nulo = "sem nenhuma nota ainda"), `ratingsCount`
é sempre um inteiro (`0` quando não há notas).

**Sem arredondamento**: o valor devolvido é o `avg(score)` cru do Postgres —
nenhum `round()` é aplicado em nenhuma camada. Uma receita com notas `4.0` e
`5.0` de dois usuários diferentes devolve `averageRating: 4.5`; combinações
que gerem dízimas (por exemplo três notas `4.0, 4.5, 5.0`) devolvem a média
exata sem truncamento. Um cliente que queira exibir só uma casa decimal
precisa arredondar no front.

`json_encode` do PHP colapsa float "redondo" (`4.0`) para `4` na resposta
(sem `JSON_PRESERVE_ZERO_FRACTION`) — `averageRating: 4.0` no DTO PHP chega
como `4` (número JSON sem parte decimal) no corpo da resposta, não como
string nem como `4.0` literal. Isso é convenção já usada em outros campos
numéricos do projeto (ver `RecipeCrudEndpointTest`), não um comportamento
específico de Rating.

## Limitação documentada: `averageRating`/`ratingsCount` em `POST`/`PATCH`/`publish`

**Isto é uma decisão de escopo deliberada e aprovada durante o planejamento,
não um bug.**

`POST /recipes`, `PATCH /recipes/{id}` e `POST /recipes/{id}/publish` sempre
devolvem `averageRating: null` e `ratingsCount: 0` no corpo da resposta —
**mesmo quando a receita editada/publicada já tem notas reais de outros
usuários**. Só `GET /recipes` e `GET /recipes/{id}` calculam e devolvem os
valores agregados de verdade.

A razão é estrutural, não uma omissão: `RecipeOutput::fromDomain()`
(`app/Application/Recipe/DTOs/RecipeOutput.php`) constrói o DTO com
`averageRating: null` e `ratingsCount: 0` como valores-padrão do construtor,
e só o método `withRatingAggregate()` os sobrescreve. Os casos de uso de
escrita — `CreateRecipe`, `UpdateRecipe`, `PublishRecipe` — chamam apenas
`RecipeOutput::fromDomain($recipe)` e nunca chamam `withRatingAggregate()`.
Só `GetRecipe` e `SearchRecipes` (os dois casos de uso de leitura, atrás de
`GET /recipes/{id}` e `GET /recipes`) buscam a agregação no
`RatingRepositoryInterface` e a aplicam ao DTO.

**Consequência prática para um cliente**: se um usuário edita
(`PATCH /recipes/{id}`) uma receita que já tem, digamos, `averageRating: 4.2`
e `ratingsCount: 15`, a resposta daquele `PATCH` mostra `averageRating: null`
e `ratingsCount: 0` — não porque as notas sumiram, mas porque esse endpoint
nunca as calcula. O cliente precisa fazer um `GET /recipes/{id}` separado
depois do `PATCH`/`POST`/`publish` para ver o valor agregado atualizado e
correto. Um front que confiar cegamente no `averageRating` devolvido por
esses três endpoints de escrita vai exibir "sem notas" para uma receita que
na verdade já foi avaliada.

## Referência de endpoints

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`.

| Método | Rota | Auth | Corpo (request) | Sucesso | Erros |
|---|---|---|---|---|---|
| PUT | `/recipes/{recipe}/rating` | `auth:sanctum` | `{ "score": 4.5 }` (obrigatório, numérico, um dos 9 valores válidos) | `200` — nota criada ou substituída (`RatingResource`) | `401` sem autenticação · `404` se a receita não existe ou não é visível para quem avalia · `422` se `score` for omitido ou fora dos valores válidos |

### Forma da resposta (`RatingResource`)

```json
{
  "id": "01J...",
  "recipeId": "01J...",
  "userId": "01J...",
  "score": 4.5
}
```

`id` é o ID do registro `Rating` (estável entre reavaliações do mesmo
usuário — não muda quando `score` é atualizado). `userId` é sempre quem
avaliou (o autenticado que chamou o endpoint), não o dono da receita.

## Como o agregado aparece na receita

O formato completo da resposta de receita (`RecipeResource`), incluindo
`averageRating`/`ratingsCount`, está documentado em
[docs/recipe.md](./recipe.md#forma-da-resposta-reciperesource).
