# Domínio User

## Para que serve

`User` é a identidade de quem usa o sistema: username único, nome de
exibição, senha (autenticada via Sanctum), bio opcional e avatar opcional
(uma `Media` do tipo `avatar`, ver `docs/media.md`). Registro, login e edição
de perfil já existiam antes deste plano (Plano de Follow) — este documento
os descreve brevemente como contexto, e detalha em profundidade só o que o
Plano de Follow acrescentou: o perfil público por username e os contadores
de seguidores/seguindo.

Código-fonte de referência: `app/Domain/User/User.php`,
`app/Domain/User/Username.php`, `app/Domain/User/DisplayName.php`,
`app/Domain/User/Exceptions/UserNotFoundException.php`,
`app/Application/User/UseCases/RegisterUser.php`,
`app/Application/User/UseCases/LoginUser.php`,
`app/Application/User/UseCases/UpdateProfile.php`,
`app/Application/User/UseCases/GetUserProfile.php`,
`app/Application/User/DTOs/UserProfileOutput.php`,
`app/Http/Controllers/Auth/RegisterController.php`,
`app/Http/Controllers/Auth/LoginController.php`,
`app/Http/Controllers/UserProfileController.php`,
`app/Http/Resources/UserProfileResource.php`.

## Registro, login e edição de perfil (contexto pré-existente)

- `POST /register`: cria o usuário e já devolve um token Sanctum junto com o
  perfil (`RegisterUserRequest` valida `username`/`display_name`/`password`;
  username duplicado vira `422` via `DuplicateUsernameException`).
- `POST /login`: valida `username`+`password`, devolve só o token.
  Credenciais inválidas viram `422` (`InvalidCredentialsException`), sem
  distinguir "username não existe" de "senha errada" — anti-enumeração de
  contas.
- `PATCH /me` (`auth:sanctum`, via `UpdateProfile`): atualiza `bio` (sempre
  enviado) e, opcionalmente, `avatarMediaId` — só troca o avatar se o campo
  `avatar_media_id` for enviado no request (`avatarMediaIdProvided`,
  distingue "não mandei o campo" de "mandei `null` pra limpar o avatar"). O
  avatar precisa ser uma `Media` própria do usuário e do tipo `avatar`, senão
  `AvatarMediaNotOwnedException` vira `422`.
- `Username` (`app/Domain/User/Username.php`) só aceita letras minúsculas,
  números e `_`, mínimo 3 caracteres — é o identificador público usado nas
  rotas por username (`{username}` em vez de ULID), tanto aqui quanto em
  `docs/follow.md`.

Nenhuma dessas regras mudou neste plano; elas são descritas aqui só como pano
de fundo para as duas seções abaixo.

## Login via Google

Além de usuário/senha, o Pratto aceita login via Google (fluxo de ID token
do Google Identity Services — o frontend recebe um JWT do próprio
navegador e manda pro backend, que só verifica a assinatura contra as
chaves públicas do Google via a lib oficial `google/apiclient`).

- `POST /login/google` (público, `{ "id_token": "..." }`) — primeiro
  login cria a conta na hora, com um `username` gerado automaticamente a
  partir do nome/e-mail do Google (resolve colisão com sufixo numérico);
  logins seguintes com o mesmo `google_id` autenticam a conta existente,
  sem duplicar. Mesmo formato de resposta de `POST /login`
  (`{ "token": "..." }`). Bloqueia usuário banido (`403`, mesma
  `UserBannedException` do login por senha).
- Uma conta que nasceu via Google não tem senha usável até o dono definir
  uma explicitamente — `PATCH /me/password` (autenticado,
  `{ "password": "..." }`, mínimo 8 caracteres) define ou troca a senha
  local, habilitando login híbrido (usuário/senha **e** Google, os dois
  válidos ao mesmo tempo).
- Vincular uma conta Google a uma conta usuário/senha já existente é
  sempre manual, de dentro do perfil — `PATCH /me/google` (autenticado,
  `{ "id_token": "..." }`). Nunca automático por e-mail batendo, mesmo se
  o e-mail do Google coincidir com um já cadastrado — decisão de
  segurança: vincular automaticamente por e-mail abriria uma forma de
  sequestrar uma conta existente só tendo acesso (mesmo que temporário) à
  caixa de e-mail associada.
- `google_id`/`email` ficam só na Infrastructure (colunas em `users`),
  igual senha — nunca entram no agregado de domínio `User`. `email` é só
  metadado vindo do Google hoje, sem nenhum uso funcional (sem
  verificação, sem reset de senha por e-mail).
- Erros: `422` token do Google inválido/expirado ou senha fora da regra
  mínima; `409` `google_id` já vinculado a outra conta; `403` usuário
  banido; `401` sem autenticação nas rotas de `/me/*`.

### Configuração (Google Cloud Console)

Pra rodar de verdade contra o Google real (os testes automatizados usam
uma fake, não precisam disso), alguém com acesso ao Google Cloud precisa:

1. Criar um projeto no [Google Cloud Console](https://console.cloud.google.com/).
2. Configurar a tela de consentimento OAuth (nome do app, e-mail de suporte).
3. Criar uma credencial "OAuth 2.0 Client ID" do tipo "Web application",
   com as origens autorizadas (`http://localhost:5173` em dev, o domínio
   de produção quando existir).
4. Colocar o Client ID gerado na variável de ambiente
   `GOOGLE_OAUTH_CLIENT_ID` (backend, valida a audiência do token) — o
   Plano 9 (frontend) vai precisar do mesmo Client ID pra inicializar o
   botão "Sign in with Google".

## `GET /users/{username}`: perfil público (novidade deste plano)

Rota pública, sem autenticação (`routes/api.php`, fora do grupo
`auth:sanctum`). `UserProfileController::show()` busca o perfil via
`GetUserProfile::__invoke($username)`
(`app/Application/User/UseCases/GetUserProfile.php`):

```php
$user = $this->users->findByUsername(Username::fromString($username));

if ($user === null) {
    return null;
}
```

Se o username não existe, `GetUserProfile` devolve `null` e o controller
converte isso em `404`:

```php
$profile = $getUserProfile($username);

abort_if($profile === null, 404);
```

Não há anti-enumeração aqui como em `Recipe`
(ver [docs/recipe.md](./recipe.md#regras-de-visibilidade)) — não faz sentido
para perfis, já que todo perfil existente é público por definição (não há
conceito de "perfil privado", ver [docs/follow.md](./follow.md#exclusões-deliberadas-de-escopo)):
um `404` aqui significa, sem ambiguidade, "esse username não existe".

## `GET /me` é um alias fino do perfil público do próprio usuário

`GET /me` (`auth:sanctum`) não tem nenhuma lógica própria de montagem de
perfil — `UserProfileController::me()` simplesmente chama `show()` passando
o `username` de quem está autenticado:

```php
public function me(Request $request, GetUserProfile $getUserProfile): UserProfileResource
{
    return $this->show($request, $request->user()->username, $getUserProfile);
}
```

Ou seja: `GET /me` e `GET /users/{seu_username}` executam exatamente o mesmo
caminho de código (`GetUserProfile` → `UserProfileResource`) e devolvem
exatamente o mesmo corpo de resposta — a única diferença é que `/me` exige
autenticação e resolve o username a partir do usuário logado, em vez de
recebê-lo na URL. Um teste garante essa equivalência byte a byte
(`tests/Feature/Http/UserProfileEndpointTest.php`,
`'GET /me is an alias for the authenticated user\'s own public profile'`).

## `followersCount`/`followingCount` (novidade deste plano)

O perfil (`UserProfileOutput`/`UserProfileResource`) agora inclui
`followersCount` e `followingCount`. Eles não são colunas na tabela `users`
— são calculados **só na leitura**, dentro de `GetUserProfile::__invoke()`,
via `FollowRepositoryInterface`:

```php
return $profile->withFollowCounts(
    $this->follows->countFollowers($user->id()),
    $this->follows->countFollowing($user->id()),
);
```

Cada chamada a `GetUserProfile` (logo, cada `GET /users/{username}` e cada
`GET /me`) roda duas queries de contagem adicionais
(`EloquentFollowRepository::countFollowers()`/`countFollowing()`). A fonte
completa desses contadores — o que conta como "seguir", idempotência,
autovalidação de self-follow — está documentada em
[docs/follow.md](./follow.md).

### Limitação documentada: `PATCH /me` sempre devolve `followersCount`/`followingCount` zerados

**Isto é um efeito colateral estrutural do DTO, não uma regra de negócio —
documentado para não ser lido como bug isolado, no mesmo espírito da
limitação equivalente de `Rating` (ver
[docs/rating.md](./rating.md#limitação-documentada-averagerating-ratingscount-em-postpatchpublish)).**

`UserProfileOutput` tem `followersCount`/`followingCount` com valor-padrão
`0` no construtor
(`app/Application/User/DTOs/UserProfileOutput.php`), e só o método
`withFollowCounts()` os sobrescreve. `UpdateProfile::__invoke()`
(`app/Application/User/UseCases/UpdateProfile.php`) monta o `UserProfileOutput`
de retorno chamando o construtor diretamente, sem nunca chamar
`withFollowCounts()`:

```php
return new UserProfileOutput(
    $user->id()->value(),
    $user->username()->value(),
    $user->displayName()->value(),
    $user->bio(),
    $user->avatarMediaId()?->value(),
);
```

Consequência prática: a resposta de `PATCH /me` sempre traz
`followersCount: 0` e `followingCount: 0` — **mesmo que o usuário já tenha
seguidores/seguidos de verdade** — porque esse caso de uso nunca consulta o
`FollowRepositoryInterface`. Só `GetUserProfile` (atrás de `GET /me` e
`GET /users/{username}`) calcula os valores reais. Um cliente que atualiza a
bio via `PATCH /me` e confia cegamente nos contadores dessa resposta vai
mostrar "0 seguidores" para alguém que na verdade tem seguidores — precisa
fazer um `GET /me` (ou `GET /users/{username}`) separado depois do `PATCH`
para ver os números corretos. Para um usuário recém-registrado
(`POST /register`, que tem o mesmo comportamento de DTO) isso não é um
problema perceptível, já que um usuário novo realmente tem `0`/`0` — a
inconsistência só aparece em `PATCH /me` de um usuário que já tinha
seguidores antes de editar o perfil.

## `role`, `status` e banimento (Plano de Moderation)

Além dos campos já descritos acima, `User` guarda estado de moderação: um
papel (`role`) e um estado de conta (`status`), com banimento/desbanimento
como as ações que mudam o `status`.

- `role` (`app/Domain/User/Enums/UserRole.php`): `user` (padrão) ou `admin`.
  `admin` é o que o middleware `EnsureUserIsAdmin` exige para os endpoints de
  moderação (`/recipes/{id}/approve|reject`, `/ingredients/{id}/approve|reject`,
  `/users/{username}/promote|ban|unban`, `GET`/`PATCH /reports`...) — ver
  [docs/moderation.md](./moderation.md#endpoints-http).
- `status` (`app/Domain/User/Enums/UserStatus.php`): `active` (padrão) ou
  `banned`.

**Ban/unban não guardam histórico** — `User::ban(Ulid $bannedBy, string
$reason)`/`unban()` sobrescrevem o estado de banimento vigente
(`bannedAt`/`banReason`/`bannedBy`); não existe uma tabela separada
registrando banimentos passados. Banir também **revoga imediatamente todos
os tokens de acesso já emitidos** para aquele usuário
(`AccessTokenIssuerInterface::revokeAllFor()`) — uma sessão já aberta para de
funcionar na próxima requisição, sem esperar expiração ou logout. Um usuário
banido que tenta logar de novo recebe `403` com o motivo do banimento
(`POST /login` → `UserBannedException` → `{"message": "Account banned:
<reason>"}`), em vez do `422` genérico de credenciais inválidas.

O detalhamento completo (por que não há histórico, o loophole de reposting
de receita que motivou parte deste plano, o desacoplamento de `Report` da
ação de moderação em si) está em [docs/moderation.md](./moderation.md).

### Endpoints de moderação de usuário (admin)

| Método | Rota | Auth | Corpo | Sucesso | Erros |
|---|---|---|---|---|---|
| PATCH | `/users/{username}/promote` | `auth:sanctum` + `admin` | — | `200` — perfil com `role` `admin` | `401` sem autenticação · `403` se não é admin · `404` se o username não existe |
| PATCH | `/users/{username}/ban` | `auth:sanctum` + `admin` | `{ "reason": "texto" }` (obrigatório) | `200` — perfil com `status` `banned` | `401` sem autenticação · `403` se não é admin · `404` se o username não existe · `422` em validação |
| PATCH | `/users/{username}/unban` | `auth:sanctum` + `admin` | — | `200` — perfil com `status` `active` | `401` sem autenticação · `403` se não é admin · `404` se o username não existe |

Todos os três respondem com `UserProfileResource` — que **não** expõe
`role`/`status`/dados de banimento no corpo JSON (só `id`, `username`,
`displayName`, `bio`, `avatarMediaId`, `followersCount`, `followingCount`,
ver [Forma da resposta](#forma-da-resposta-userprofileresource) abaixo); a
mudança de papel/status precisa ser conferida no banco ou inferida
indiretamente (por exemplo, tentando logar como o usuário banido).

### Bootstrap do primeiro admin: `php artisan user:promote`

Não existe (de propósito) um endpoint HTTP para criar o **primeiro** admin —
isso permitiria qualquer usuário comum se autopromover. O primeiro admin
nasce via linha de comando, direto no servidor:

```bash
php artisan user:promote {username}
```

Depois que existe pelo menos um admin, promoções seguintes usam o endpoint
HTTP acima normalmente. Como recuperação para o caso de um único admin ficar
banido (por si mesmo ou por outro admin) sem ninguém autenticado para
desbanir via API, existe o comando simétrico:

```bash
php artisan user:unban {username}
```

Ver `app/Console/Commands/PromoteUserToAdminCommand.php` e
`app/Console/Commands/UnbanUserCommand.php`.

## Referência de endpoints (perfil)

Todas as rotas estão em `routes/api.php`, prefixadas por `/api`. Para os
endpoints de follow (`/users/{username}/follow`, `/feed`), ver
[docs/follow.md](./follow.md#referência-de-endpoints).

| Método | Rota | Auth | Corpo (request) | Sucesso | Erros |
|---|---|---|---|---|---|
| POST | `/register` | Pública | `{ username, display_name, password }` | `201` — `{ token, user }` | `422` username duplicado ou validação |
| POST | `/login` | Pública | `{ username, password }` | `200` — `{ token }` | `422` credenciais inválidas |
| GET | `/users/{username}` | Pública | — | `200` — perfil público (`UserProfileResource`) | `404` username não existe |
| GET | `/me` | `auth:sanctum` | — | `200` — alias do próprio perfil público | `401` sem autenticação |
| PATCH | `/me` | `auth:sanctum` | `{ bio, avatar_media_id? }` | `200` — perfil atualizado (`followersCount`/`followingCount` sempre `0`, ver limitação acima) | `401` sem autenticação · `422` avatar não pertence ao usuário ou não é do tipo `avatar` |

### Forma da resposta (`UserProfileResource`)

```json
{
  "id": "01J...",
  "username": "gabriel",
  "displayName": "Gabriel Medeiros",
  "bio": null,
  "avatarMediaId": null,
  "followersCount": 0,
  "followingCount": 0
}
```

`id` é o ULID do usuário. `bio`/`avatarMediaId` são `null` até serem
definidos via `PATCH /me`. `followersCount`/`followingCount` só refletem a
contagem real quando a resposta vem de `GET /users/{username}` ou
`GET /me` — ver limitação acima para `PATCH /me`.
