# Domínio Media

## Para que serve

`Media` é o bounded context de upload e processamento de imagens do Pratto:
avatar de usuário e foto de capa de receita. Um `Media` guarda quem enviou o
arquivo (`ownerId`), o tipo (`avatar` ou `recipe_photo`), a localização no
storage, o ponto focal usado para recortar a miniatura, as dimensões da
imagem processada e o status de moderação. Ele é referenciado pelos domínios
`Recipe` (campo `cover_media_id`) e `User` (campo `avatar_media_id`) — ver
[docs/recipe.md](./recipe.md) — mas não conhece nenhum dos dois: a ligação é
feita de fora para dentro, por casos de uso de `Recipe`/`User` que validam um
ID de mídia antes de aceitá-lo.

Código-fonte de referência: `app/Domain/Media/Media.php`,
`app/Domain/Media/FocalPoint.php`, `app/Domain/Media/Enums/MediaKind.php`,
`app/Domain/Media/Enums/MediaStatus.php`,
`app/Infrastructure/Media/ImagePipeline.php`,
`app/Infrastructure/Media/TemporaryMediaUrlSigner.php`,
`app/Application/Media/UseCases/UploadMedia.php`,
`app/Application/Media/UseCases/AdjustFocalPoint.php`,
`app/Application/Media/UseCases/ApproveMedia.php`,
`app/Application/Media/UseCases/RejectMedia.php`.

## Storage

O disco `media` (`config/filesystems.php`) é um bucket S3-compatível privado
(Garage, self-hosted — ver `docker/garage.toml` e `docker-compose.yml`), com
`visibility => private` e `throw => true`. Cada upload gera uma pasta nomeada
com um ULID novo (`storageKey`, gerado em `UploadMedia`, **não** é o ID do
registro `Media` — são dois ULIDs diferentes) contendo três arquivos:

```
{storageKey}/original.jpg
{storageKey}/display.jpg
{storageKey}/thumbnail.jpg
```

Como o bucket é privado, nenhum desses arquivos é acessível por URL direta —
toda entrega ao cliente passa por `TemporaryMediaUrlSigner`
(`app/Infrastructure/Media/TemporaryMediaUrlSigner.php`), que gera URLs
assinadas com validade de 15 minutos (`Storage::disk('media')->temporaryUrl()`)
para `display` e `thumbnail`. O `original.jpg` nunca é exposto por URL — ele
só é lido de volta pelo próprio pipeline, no reprocessamento de ponto focal.

## O pipeline de upload

`ImagePipeline::process()` (`app/Infrastructure/Media/ImagePipeline.php`),
chamado por `UploadMedia`, roda nesta ordem:

1. **Validação de dimensão pré-decode**: `assertDimensionsAreSafe()` usa
   `getimagesize()` — que lê só o cabeçalho do arquivo, sem decodificar os
   pixels — para rejeitar, antes de qualquer processamento pesado, imagens
   com lado maior que 8000px ou mais de 40 milhões de pixels totais
   (`MAX_DIMENSION_PX`, `MAX_TOTAL_PIXELS`). Isso existe para não dar a um
   upload malicioso a chance de forçar a decodificação de uma imagem
   gigante (decompression bomb) antes de qualquer checagem de tamanho.
   Falha aqui lança `InvalidImageException`, que o `MediaController::store()`
   traduz em **`422`**.
2. **Reencode**: a imagem é lida (`ImageManager::read()`) e, a partir daí, as
   três variantes abaixo são sempre geradas por reencode via Intervention
   Image/GD — nunca é feita uma cópia byte-a-byte do arquivo original.
3. **Remoção de EXIF**: reencodar para JPEG via `toJpeg()` não copia os
   metadados EXIF do arquivo de origem (orientação, geolocalização, modelo de
   câmera etc.) — o resultado gravado no storage não carrega esses dados.
   Isso não é uma etapa separada e explícita no código; é uma consequência do
   passo de reencode.
4. **Três variantes gravadas**:
   - `original.jpg`: a imagem decodificada, reencodada em JPEG, sem
     redimensionar — serve só como fonte para reprocessamento futuro do
     ponto focal (nunca é exposta ao cliente).
   - `display.jpg`: redimensionada para caber em 1200×1200
     (`scaleDown`, mantém proporção, não recorta).
   - `thumbnail.jpg`: recorte quadrado de 300×300, centrado no ponto focal
     (ver seção seguinte).
5. **Compressão**: todas as três variantes são salvas como JPEG qualidade 82
   (`JPEG_QUALITY`), fixo — não configurável por requisição.

Resultado devolvido ao caso de uso: `width`/`height` da variante `display`
(não do original) — são essas dimensões que ficam gravadas em
`media.width`/`media.height`.

### Como o recorte do thumbnail usa o ponto focal

`buildThumbnail()` recorta um quadrado do lado igual ao menor lado da imagem
(`min(width, height)`), centrado o mais próximo possível do ponto focal
(`focalPoint->x()`/`y()`, frações de 0.0 a 1.0 da largura/altura), sem deixar
a janela de recorte sair dos limites da imagem (`max`/`min` de clamp). Se
nenhum ponto focal for enviado no upload, `FocalPoint::center()` (0.5, 0.5)
é usado — recorte centralizado, comportamento equivalente a um "center crop"
clássico.

## Os dois tipos (`MediaKind`)

`MediaKind` (`app/Domain/Media/Enums/MediaKind.php`) tem dois valores, e o
tipo decide o status inicial em `Media::upload()`:

| Kind | Valor | Status inicial | Uso pretendido |
|---|---|---|---|
| Avatar | `avatar` | `approved` (auto-aprovado) | Foto de perfil de usuário — liga-se a `users.avatar_media_id`. |
| RecipePhoto | `recipe_photo` | `pending_review` (fila de moderação) | Foto de capa de receita — liga-se a `recipes.cover_media_id`. |

Avatar não passa por fila porque é uma decisão de produto: o usuário não pode
ficar bloqueado esperando aprovação para trocar a própria foto de perfil.
Isso não significa que um avatar impróprio fica permanentemente sem
moderação — ver a seção de rejeição abaixo, que funciona a partir de
qualquer status, inclusive `approved`.

## Ciclo de vida (status) e moderação

`MediaStatus` (`app/Domain/Media/Enums/MediaStatus.php`) tem três valores:
`pending_review`, `approved`, `rejected`.

- **`approve(reviewerId)`**: só é chamável, hoje, por um endpoint atrás do
  middleware `admin` — ver abaixo. Não valida o status atual (uma mídia já
  aprovada pode ser "reaprovada" sem erro); grava `reviewedBy` e limpa
  `rejectionReason`.
- **`reject(reviewerId, reason)`**: funciona **a partir de qualquer status**,
  inclusive `approved` — este é o mecanismo de revogação pós-hoc. Como
  avatares nascem já aprovados sem revisão humana, é assim que uma foto de
  perfil imprópria é derrubada depois do fato: um admin chama
  `PATCH /media/{id}/reject` numa mídia que já está `approved`, e ela vira
  `rejected` normalmente. `reason` é opcional (string, máx. 500).

```php
/**
 * Works from any status, including approved — this is also how an
 * already-approved avatar gets revoked after the fact...
 */
public function reject(Ulid $reviewerId, ?string $reason): void
```

**Quem pode aprovar/rejeitar**: as rotas `PATCH /media/{id}/approve` e
`PATCH /media/{id}/reject` estão atrás de `auth:sanctum` **e** do middleware
`admin` (`App\Http\Middleware\EnsureUserIsAdmin`, registrado como alias
`admin` em `bootstrap/app.php`), que exige `$user->role === 'admin'`. Um
usuário autenticado sem esse papel recebe `403` ao tentar.

**Rejeitar/aprovar não valida ownership** — `ApproveMedia`/`RejectMedia` não
chamam `assertOwnedBy()` (diferente de `AdjustFocalPoint`). Isso é esperado:
moderação é, por definição, uma ação de terceiro sobre a mídia de outra
pessoa.

## Ajuste de ponto focal (`PATCH /media/{id}/focal-point`)

Permite ao dono da mídia mover o ponto focal **sem reenviar o arquivo**.
`AdjustFocalPoint` (`app/Application/Media/UseCases/AdjustFocalPoint.php`):

1. Carrega a mídia por ID; `404` se não existir.
2. `assertOwnedBy()` — `403` se quem chama não é o dono.
3. Recorta um **novo `thumbnail.jpg`** a partir do `original.jpg` já salvo no
   storage (`ImagePipeline::reprocessThumbnail()`), usando o ponto focal
   novo.
4. Persiste o novo `focalPoint` no registro `Media`.

**Por que só o `thumbnail` é reprocessado, e o `display` não**: o `display`
não depende do ponto focal — ele é gerado por `scaleDown` (mantém a imagem
inteira, sem recorte), então o ponto focal não muda nada no resultado. Só o
`thumbnail` recorta um quadrado ao redor de um ponto, então só ele precisa
ser regerado quando esse ponto muda. Reprocessar o `display` seria trabalho
sem efeito visível.

## Como `cover_media_id`/`avatar_media_id` se ligam a Recipe/User

Nenhum dos dois é uma FK "livre" — cada um passa por uma validação de posse
antes de ser aceito, no próprio caso de uso do domínio consumidor (não em
`Media`):

- **Recipe**: `ValidatesCoverMedia::assertCoverUsable()`
  (`app/Application/Recipe/Concerns/ValidatesCoverMedia.php`), usado por
  `CreateRecipe`/`UpdateRecipe`. Busca a mídia por ID; se não existir **ou**
  não pertencer ao mesmo usuário que está criando/editando a receita, lança
  `CoverMediaNotOwnedException`, que o `RecipeController` traduz em
  **`422`**. Note que "não existe" e "existe mas não é sua" retornam o mesmo
  erro — não há distinção de mensagem/status entre os dois casos.
- **User**: `UpdateProfile::assertAvatarUsable()`
  (`app/Application/User/UseCases/UpdateProfile.php`), mesma lógica —
  `AvatarMediaNotOwnedException` também vira **`422`** em
  `UserProfileController::update()`.

Em ambos os casos, o campo é opcional e `null`-ável: enviar `null` (ou
omitir o campo, no caso de `PATCH /me`, que distingue "não enviado" de
"enviado como null" via `avatarMediaIdProvided`) desassocia a capa/avatar
sem tocar em `Media`.

Nas migrations, `recipes.cover_media_id` e `users.avatar_media_id` têm FK
real para `media.id` com `nullOnDelete()` — se a linha de `Media`
referenciada for apagada, o campo na receita/usuário volta para `null`
automaticamente (mas note: hoje não existe nenhum endpoint que apague uma
`Media`, então esse `nullOnDelete()` é defensivo, não exercitado pela API
atual).

**Capa de receita já resolve URL assinada; avatar ainda não**: isso mudou de
comportamento e hoje é diferente para cada um dos dois consumidores de
`Media`:

- **Recipe**: `RecipeResource` expõe `coverMediaId` **e** também
  `coverThumbnailUrl`/`coverDisplayUrl`, resolvidas a cada leitura, dentro do
  próprio resource (`resolveCoverUrls()`), via
  `MediaRepositoryInterface::findById()` seguido de
  `MediaUrlSignerInterface::signedUrlsFor()`. Não há cache — uma URL nova é
  assinada em toda resposta — então a expiração de 15 minutos das URLs de
  `POST /media` **não** é um problema para a capa de uma receita: o cliente
  sempre recebe uma URL fresca junto com a receita, sem precisar guardar a
  resposta do upload original. Quando não há capa, ou a mídia referenciada
  não existe, ou está `rejected` (reprovada na moderação), os dois campos
  voltam `null` (ver [docs/recipe.md](./recipe.md)).
- **User**: `UserProfileResource` continua expondo só `avatarMediaId` como
  string — nenhuma `thumbnailUrl`/`displayUrl` assinada é resolvida para o
  avatar. Como não existe endpoint `GET /media/{id}`, o único jeito de o
  cliente obter as URLs assinadas do avatar de um usuário é ter guardado a
  resposta do `POST /media` original daquele avatar (cujas URLs expiram em 15
  minutos) — ver limitação conhecida abaixo.

## Referência de endpoints

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`, todas atrás
de `auth:sanctum`.

| Método | Rota | Auth | Corpo (request) | Sucesso | Erros |
|---|---|---|---|---|---|
| POST | `/media` | `auth:sanctum` | `multipart/form-data`: `file` (obrigatório, jpeg/png/webp, máx. 8MB), `kind` (obrigatório, `avatar`\|`recipe_photo`), `focal_x`/`focal_y` (opcionais, numérico 0–1) | `201` — mídia criada (`MediaResource`) | `401` sem autenticação · `422` em validação ou imagem inválida/dimensões excessivas |
| PATCH | `/media/{id}/focal-point` | `auth:sanctum` | `{ "focal_x": 0.3, "focal_y": 0.6 }` (ambos obrigatórios, 0–1) | `200` — mídia com `thumbnail` recortado de novo | `401` sem autenticação · `403` se não é o dono · `404` se não existe · `422` em validação |
| PATCH | `/media/{id}/approve` | `auth:sanctum` + `admin` | — | `200` — mídia com status `approved` | `401` sem autenticação · `403` se não é admin · `404` se não existe |
| PATCH | `/media/{id}/reject` | `auth:sanctum` + `admin` | `{ "reason": "texto opcional" }` (opcional, string, máx. 500) | `200` — mídia com status `rejected` | `401` sem autenticação · `403` se não é admin · `404` se não existe · `422` em validação |

### Forma da resposta (`MediaResource`)

```json
{
  "id": "01J...",
  "ownerId": "01J...",
  "kind": "recipe_photo",
  "status": "pending_review",
  "focalX": 0.3,
  "focalY": 0.6,
  "width": 800,
  "height": 600,
  "thumbnailUrl": "https://.../thumbnail.jpg?X-Amz-Signature=...",
  "displayUrl": "https://.../display.jpg?X-Amz-Signature=...",
  "rejectionReason": null
}
```

`width`/`height` são as dimensões da variante `display` (após `scaleDown`),
não do arquivo original enviado. `thumbnailUrl`/`displayUrl` são assinadas
com validade de 15 minutos a partir do momento da resposta.

## Limitações conhecidas

- **Promover um usuário a `admin` já é possível, mas exige um mecanismo de
  bootstrap.** A coluna `users.role` existe (`string`, default `'user'`), e o
  middleware `admin` (`EnsureUserIsAdmin`) confere `$user->role === 'admin'`
  corretamente. O Plano de Moderation acrescentou dois caminhos reais para
  promover: `php artisan user:promote {username}` (comando de console, sem
  exigir autenticação — é assim que o **primeiro** admin nasce, direto no
  servidor) e `PATCH /users/{username}/promote` (HTTP, exige um admin
  autenticado — só funciona depois que já existe pelo menos um). Ver
  [docs/moderation.md](./moderation.md#bootstrap-do-primeiro-admin) para o
  fluxo completo. Promoção manual direto no banco (`UPDATE users SET role =
  'admin' ...`) **não** é mais a forma esperada de fazer isso — é exatamente
  o buraco de bootstrap que este mecanismo substituiu; só os testes antigos
  ainda usavam esse atalho antes deste plano.
- **Não há endpoint para reobter as URLs assinadas de uma mídia já
  existente.** `GET /media/{id}` não existe. As URLs assinadas só saem nas
  respostas de `POST /media`, `PATCH /media/{id}/focal-point`,
  `PATCH /media/{id}/approve` e `PATCH /media/{id}/reject`, e expiram em 15
  minutos. Um cliente que só guardou `cover_media_id`/`avatar_media_id` (via
  `Recipe`/`User`) e perdeu a resposta original de upload não tem, hoje, como
  recuperar uma URL válida para exibir aquela imagem.
  **Atualização**: para foto de capa de receita, essa limitação está fechada —
  `RecipeResource` resolve `coverThumbnailUrl`/`coverDisplayUrl` frescas a
  cada leitura de `GET /recipes`/`GET /recipes/{id}`, então o cliente nunca
  precisa ter guardado a resposta de upload original para exibir a capa (ver
  seção acima). A limitação continua valendo integralmente para
  `avatarMediaId` (via `User`) e para qualquer cliente que só tenha um ID de
  `Media` bruto sem uma `Recipe` que o referencie para resolver a URL através
  dela.
- **`approve`/`reject` não verificam o status atual antes de agir** — chamar
  `approve` numa mídia já `approved`, ou `reject` numa já `rejected`, não é
  tratado como erro; a ação simplesmente é reaplicada (idempotente na
  prática, mas não por checagem explícita de transição, diferente do
  `Recipe::publish()` documentado em [docs/recipe.md](./recipe.md)).
