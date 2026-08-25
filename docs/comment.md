# Domínio Comment

## Para que serve

`Comment` é um comentário de texto livre postado por um usuário numa
receita. Diferente de `Rating`, um usuário pode postar **quantos comentários
quiser** na mesma receita — não há restrição de "um por usuário". Cada
comentário tem um corpo (até 1000 caracteres), pertence a um autor
(`userId`) e a uma receita (`recipeId`), e guarda `editedAt` (nulo até a
primeira edição).

Código-fonte de referência: `app/Domain/Comment/Comment.php`,
`app/Domain/Comment/Contracts/CommentRepositoryInterface.php`,
`app/Domain/Comment/Exceptions/CommentNotFoundException.php`,
`app/Domain/Comment/Exceptions/CommentNotOwnedException.php`,
`app/Application/Comment/UseCases/PostComment.php`,
`app/Application/Comment/UseCases/EditComment.php`,
`app/Application/Comment/UseCases/DeleteComment.php`,
`app/Application/Comment/UseCases/ListComments.php`,
`app/Infrastructure/Persistence/Eloquent/Repositories/EloquentCommentRepository.php`.

## Regra de dono: só o autor edita/apaga (explicitamente não o dono da receita)

`Comment::assertOwnedBy(Ulid $userId)` é a única checagem de posse do
domínio, e ela compara contra `$this->userId` — **o autor do comentário**,
não `$this->recipeId`/dono da receita comentada:

```php
public function assertOwnedBy(Ulid $userId): void
{
    if (! $this->userId->equals($userId)) {
        throw CommentNotOwnedException::forComment($this->id);
    }
}
```

`EditComment` e `DeleteComment` (`app/Application/Comment/UseCases/`) chamam
essa checagem antes de qualquer efeito. Isso significa, de forma
deliberada: **o dono da receita não tem nenhum poder especial sobre os
comentários postados nela** — não pode editar nem apagar um comentário de
outra pessoa só por ser dono da receita comentada. Só quem escreveu aquele
comentário específico pode editá-lo ou apagá-lo. Se quem chama
`PATCH /comments/{id}` ou `DELETE /comments/{id}` não é o autor,
`CommentNotOwnedException` é lançada e o `CommentController` traduz em
**`403 Forbidden`** (não `404` — diferente da leitura de receita, aqui não há
anti-enumeração: se o comentário existe mas não é seu, você recebe 403,
porque o comentário já foi carregado do repositório antes da checagem de
posse).

Não existe moderação de comentário por parte do dono da receita nem por
administrador nesta versão — a única forma de um comentário sumir é o
próprio autor apagá-lo.

## Corpo do comentário: validação em duas camadas

Igual ao padrão de `Rating`, o limite de tamanho é reforçado tanto na borda
HTTP quanto no domínio:

1. **FormRequest** (`ValidatesCommentContent`,
   `app/Http/Requests/Concerns/ValidatesCommentContent.php`, usado por
   `PostCommentRequest` e `UpdateCommentRequest`):
   ```php
   'body' => ['required', 'string', 'max:1000'],
   ```
2. **Domínio** (`Comment::applyBody()`, privado, chamado por `post()` e
   `edit()`): faz `trim()` do corpo antes de validar, rejeita corpo vazio
   após o trim (`InvalidArgumentException`) e rejeita mais de 1000
   caracteres via `mb_strlen()` (não `strlen()` — conta caracteres
   multi-byte corretamente, então acentos/emoji não estouram o limite antes
   da hora).

O FormRequest não faz `trim()` antes de contar — só o domínio garante que
uma string só de espaços (que passaria em `'required', 'string'` do
FormRequest, já que não é vazia) é rejeitada como comentário vazio.

## Visibilidade ao postar (mesma regra da receita, mas não ao listar)

`PostComment::__invoke()` usa exatamente a mesma checagem de `RateRecipe`
(ver [docs/rating.md](./rating.md#visibilidade)): só é possível comentar numa
receita `pending_review`/`published`, ou numa receita própria de qualquer
status. Receita inexistente ou não visível → `RecipeNotFoundException` →
`404` no `CommentController::store()`.

**`ListComments` (`GET /recipes/{recipe}/comments`) é diferente**: ela só
verifica que a receita **existe**, não que é **visível** para quem está
pedindo:

```php
// ListComments::__invoke()
if ($this->recipes->findById($recipeUlid) === null) {
    throw RecipeNotFoundException::forId($recipeUlid);
}
```

Isso é deliberado, não um descuido — a rota é pública
(`GET /recipes/{recipe}/comments` não está no grupo `auth:sanctum` de
`routes/api.php`) e o controller não passa nenhuma identidade de visitante
para `ListComments`, então não há como aplicar a mesma regra de
"`pending_review`/`published`, ou o próprio dono" que `Recipe::isVisibleTo()`
usa em outros lugares. Na prática, isso significa: listar comentários de uma
receita `draft`/`rejected` de outra pessoa **não** devolve `404` como
`GET /recipes/{id}` devolveria — devolve os comentários normalmente, desde
que a receita exista. É uma pequena inconsistência de superfície com o resto
do domínio `Recipe` (documentada aqui para não ser lida como comportamento
de anti-enumeração garantido), não uma vulnerabilidade grave: o pior caso é
alguém descobrir que uma receita rascunho existe e ver os comentários dela,
não alterar nem apagar nada.

## Paginação de listagem

`GET /recipes/{recipe}/comments` aceita `?page=N` (inteiro, mínimo 1;
padrão 1 quando omitido). O tamanho de página é fixo em **20** por chamada
(`CommentController::index()` passa `$perPage = 20` para `ListComments`
diretamente no código — não é configurável via query string). Não há
metadados de paginação (total de páginas, total de itens) na resposta — o
corpo é só o array de comentários daquela página; um cliente não tem como
saber, pela resposta, se existe uma página seguinte, exceto por inferência
(devolveu menos de 20 itens → provavelmente é a última página).

Ordenação: mais recente primeiro (`created_at DESC`), com desempate por `id`
descendente (`orderByDesc('id')`) para comentários postados dentro do mesmo
segundo — a coluna `created_at` tem precisão de segundo (sem frações), então
sem esse desempate a ordem entre comentários simultâneos seria
indeterminada. Como ULIDs são ordenáveis lexicograficamente por tempo de
geração, ordenar por `id` desc resolve o empate na ordem real de inserção.

## Referência de endpoints

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`.

| Método | Rota | Auth | Corpo (request) | Query params | Sucesso | Erros |
|---|---|---|---|---|---|---|
| GET | `/recipes/{recipe}/comments` | Pública | — | `page` (inteiro ≥ 1) | `200` — lista de comentários da página (`CommentResource` cada) | `404` se a receita não existe · `422` em `page` inválido |
| POST | `/recipes/{recipe}/comments` | `auth:sanctum` | `{ "body": "texto" }` (obrigatório, string, máx. 1000) | — | `201` — comentário criado (`CommentResource`) | `401` sem autenticação · `404` se a receita não existe ou não é visível para quem comenta · `422` em validação |
| PATCH | `/comments/{comment}` | `auth:sanctum` | `{ "body": "texto" }` (obrigatório, string, máx. 1000) | — | `200` — comentário editado, `editedAt` atualizado | `401` sem autenticação · `403` se não é o autor · `404` se o comentário não existe · `422` em validação |
| DELETE | `/comments/{comment}` | `auth:sanctum` | — | — | `204` — sem corpo | `401` sem autenticação · `403` se não é o autor · `404` se o comentário não existe |

### Forma da resposta (`CommentResource`)

```json
{
  "id": "01J...",
  "recipeId": "01J...",
  "userId": "01J...",
  "body": "Muito bom, ficou perfeito!",
  "editedAt": null
}
```

`editedAt` é `null` até a primeira edição; depois de um `PATCH`, vira uma
string ISO 8601 (`DATE_ATOM`) do momento da última edição — não há histórico
de edições anteriores, só o timestamp da mais recente.

## Comentário e nota são domínios independentes

Um usuário pode comentar sem avaliar, avaliar sem comentar, ou os dois —
`Comment` e `Rating` não se referenciam entre si em nenhuma direção, e
postar/editar/apagar um comentário não afeta `averageRating`/`ratingsCount`
da receita (só `Rating` afeta esse agregado — ver
[docs/rating.md](./rating.md#como-a-agregação-averagerating-ratingscount-é-calculada-e-exposta)).
