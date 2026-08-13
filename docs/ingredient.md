# Domínio Ingredient

## Para que serve

`Ingredient` é um catálogo compartilhado e deduplicado de ingredientes,
referenciado pelas receitas do domínio `Recipe` (ver
[docs/recipe.md](./recipe.md)). Em vez de cada receita guardar o nome do
ingrediente como texto livre, cada linha de ingrediente de uma receita aponta
para um `Ingredient` único no catálogo — assim "farinha de trigo" cadastrada
numa receita é o mesmo registro usado por outra receita, o que futuramente
sustenta funcionalidades como preço regional e cálculo de macros por
ingrediente sem duplicação de dados.

Código-fonte de referência: `app/Domain/Ingredient/Ingredient.php`,
`app/Domain/Ingredient/IngredientStatus.php`,
`app/Domain/Ingredient/IngredientName.php`,
`app/Application/Ingredient/UseCases/ResolveIngredient.php`,
`app/Application/Ingredient/UseCases/SearchIngredients.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentIngredientRepository.php`.

## Status e aceitação provisória

`IngredientStatus` (`app/Domain/Ingredient/IngredientStatus.php`) tem dois
valores:

| Status | Valor | Significado |
|---|---|---|
| Provisional | `provisional` | Ingrediente criado automaticamente a partir de um nome digitado por um usuário, ainda não revisado por moderação. |
| Approved | `approved` | Ingrediente aprovado. Hoje não existe nenhum caso de uso que promova um ingrediente de `provisional` para `approved` — o valor existe no enum e é reconstituído a partir do banco, mas nada na camada de aplicação atual faz essa transição. |

Quando um usuário cria ou edita uma receita e digita o nome de um ingrediente
que ainda não existe no catálogo, `Ingredient::createProvisional()` cria um
novo registro **imediatamente**, com status `provisional`, sem passar por
nenhuma aprovação antes:

```php
public static function createProvisional(Ulid $id, IngredientName $name): self
{
    return new self($id, $name, IngredientStatus::Provisional);
}
```

Essa é uma decisão de produto deliberada: o cadastro de uma receita nunca
deve ficar bloqueado esperando um moderador aprovar um nome de ingrediente
novo. O ingrediente entra provisório, a receita é salva normalmente, e uma
eventual moderação (ainda não implementada) cuidaria de revisar/mesclar
ingredientes provisórios depois. O campo `status` é exposto nas respostas da
API (`IngredientResource` inclui `"status": "provisional"` ou
`"status": "approved"`), então qualquer cliente que queira sinalizar
visualmente "este ingrediente ainda não foi revisado" pode fazer isso sem
precisar de outra chamada.

## Normalização de nome e deduplicação

`IngredientName` (`app/Domain/Ingredient/IngredientName.php`) guarda dois
valores: o nome como foi digitado (`value()`) e uma versão normalizada
(`normalized()`) usada só para comparação/deduplicação:

```php
public static function normalize(string $value): string
{
    $lower = mb_strtolower(trim($value));
    $withoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
    $collapsed = preg_replace('/\s+/', ' ', $withoutAccents !== false ? $withoutAccents : $lower);

    return trim($collapsed ?? $lower);
}
```

A normalização, nessa ordem: remove espaços nas pontas, deixa tudo minúsculo,
remove acentos (transliteração ASCII), e colapsa sequências de espaços em um
único espaço. Isso significa que `"Tomate"`, `"tomate "`, `"TOMATE"` e
`"Tòmate"` normalizam para o mesmo valor (`"tomate"`) e são tratados como o
mesmo ingrediente. A coluna `ingredients.normalized_name` tem uma constraint
`unique` no banco (migration `2026_08_09_233405_create_ingredients_table.php`).

A resolução de nome para ingrediente acontece em `ResolveIngredient`
(`app/Application/Ingredient/UseCases/ResolveIngredient.php`):

1. Se veio um `ingredientId`, busca por ID; se não encontrar, lança
   `RuntimeException`.
2. Senão, normaliza o `ingredientName` recebido e busca por
   `findByNormalizedName()`. Se já existir um ingrediente com esse nome
   normalizado, reaproveita ele (mesmo que o texto digitado seja diferente do
   nome já cadastrado).
3. Se não existir, cria um novo `Ingredient` provisório e salva.

**Limitação conhecida (condição de corrida)**: os passos 2 e 3 acima não são
atômicos — não há lock nem transação cobrindo o "buscar, e se não achar,
criar". Se duas requisições simultâneas tentam introduzir o mesmo nome novo
(por exemplo, dois usuários criando receitas diferentes e digitando
"Cominho" ao mesmo tempo, sem esse ingrediente ainda existir), ambas podem
passar pela checagem de "não existe" antes de qualquer uma delas ter
salvado, e ambas tentam inserir. A segunda inserção viola a constraint
`unique` de `normalized_name` no banco. Essa violação **não é tratada** em
nenhum lugar do código hoje — nem `ResolveIngredient`, nem os casos de uso de
`Recipe` que o chamam, nem o `RecipeController` capturam essa exceção. Ela
sobe como uma exceção de banco não tratada (`QueryException` do Laravel) até
o handler padrão do framework. Isso é uma lacuna conhecida, não uma
funcionalidade — não deve ser lida como "condição de corrida tratada com
elegância".

## Endpoint de busca/autocomplete

| Método | Rota | Auth | Query params | Sucesso | Erros |
|---|---|---|---|---|---|
| GET | `/ingredients` | Pública | `q` (obrigatório, string, mínimo 1 caractere) | `200` — lista de ingredientes (`IngredientResource` cada) | `422` se `q` ausente ou vazio |

Implementado por `IngredientController::index()` →
`SearchIngredients::__invoke(string $term)` →
`EloquentIngredientRepository::search()`.

A busca (`EloquentIngredientRepository::search()`) normaliza o termo da mesma
forma que a deduplicação (`IngredientName::normalize()`) e busca linhas onde:

- `normalized_name ILIKE '{termo}%'` (prefixo, case-insensitive), **ou**
- `normalized_name % {termo}` — operador de similaridade de trigramas do
  Postgres (extensão `pg_trgm`, habilitada na migration de `ingredients`),
  que casa nomes parecidos mesmo sem ser prefixo exato (tolera erros de
  digitação).

Os resultados são ordenados por `similarity(normalized_name, termo)`
decrescente (mais parecido primeiro) e limitados a 10 registros — não há
paginação nesse endpoint.

### Forma da resposta (`IngredientResource`)

```json
[
  { "id": "01J...", "name": "Tomate", "status": "provisional" }
]
```

## Como o Recipe resolve ingredientes

Ao criar (`POST /recipes`) ou atualizar (`PATCH /recipes/{id}`) uma receita,
cada item da lista `ingredients` do corpo da requisição pode vir com
`ingredient_id` (referenciando um ingrediente já existente) **ou**
`ingredient_name` (texto livre — obrigatório apenas quando `ingredient_id`
não é enviado). O `CreateRecipe`/`UpdateRecipe` (casos de uso de `Recipe`)
chamam `ResolveIngredient` para cada linha, que aplica exatamente as regras
descritas acima (busca por ID, ou normalização + busca-ou-cria por nome)
antes de montar o `RecipeIngredient` do domínio. O formato completo do corpo
de requisição e da resposta de receita está documentado em
[docs/recipe.md](./recipe.md#corpo-de-criaçãoedição).
