<?php

use App\Application\Follow\UseCases\FollowUser;
use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\DTOs\UploadMediaInput;
use App\Application\Media\UseCases\UploadMedia;
use App\Application\Pantry\DTOs\PantryOutput;
use App\Application\Pantry\UseCases\CreatePantry;
use App\Application\Pantry\UseCases\InvitePantryMember;
use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeOutput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeStep;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\GoogleIdTokenVerifierInterface;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\GoogleIdentity;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function authenticatedToken(TestCase $test): string
{
    $test->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);

    return $test->postJson('/api/login', [
        'username' => 'gabriel',
        'password' => 'senha-forte-123',
    ])->json('token');
}

function authenticatedTokenFor(TestCase $test, string $username): string
{
    $test->postJson('/api/register', [
        'username' => $username,
        'display_name' => ucfirst($username),
        'password' => 'senha-forte-123',
    ]);

    return $test->postJson('/api/login', [
        'username' => $username,
        'password' => 'senha-forte-123',
    ])->json('token');
}

function aLine(): RecipeIngredient
{
    return RecipeIngredient::create(Ulid::generate(), 2.0, MeasurementUnit::Gram, 0);
}

function aStep(): RecipeStep
{
    return RecipeStep::create(0, 'Misture tudo.');
}

function anOwner(): Ulid
{
    // Suffixed so a single test can register more than one distinct owner
    // (e.g. an uploader and a reviewer) without a duplicate-username clash.
    $username = 'gabriel_'.mb_strtolower(substr((string) Ulid::generate()->value(), -8));
    $profile = app(RegisterUser::class)(new RegisterUserInput($username, 'Gabriel', 'senha-forte-123'));

    return Ulid::fromString($profile->id);
}

function anIngredientId(string $name = 'Tomate'): Ulid
{
    return app(ResolveIngredient::class)(new ResolveIngredientInput(null, $name))->id();
}

function createADraft(string $ownerId): RecipeOutput
{
    return app(CreateRecipe::class)(new CreateRecipeInput(
        $ownerId, 'Bolo', 'Bolo simples', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Misture.')],
    ));
}

function recipePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Bolo de cenoura',
        'description' => 'Bolo simples e rápido',
        'portions' => 8,
        'prep_time_minutes' => 60,
        'ingredients' => [
            ['ingredient_name' => 'Cenoura', 'quantity' => 3, 'unit' => 'unidade', 'position' => 0],
        ],
        'steps' => [
            ['position' => 0, 'instruction' => 'Bata tudo no liquidificador.'],
        ],
    ], $overrides);
}

function anAdmin(): Ulid
{
    $owner = anOwner();

    EloquentUser::query()
        ->whereKey($owner->value())
        ->update(['role' => 'admin']);

    return $owner;
}

function anApprovedAvatar(string $ownerId): MediaOutput
{
    Storage::fake('media');

    return app(UploadMedia::class)(
        new UploadMediaInput($ownerId, 'avatar', base_path('tests/fixtures/media/valid.jpg'), null, null),
    );
}

function aPendingRecipePhoto(string $ownerId): MediaOutput
{
    Storage::fake('media');

    return app(UploadMedia::class)(
        new UploadMediaInput($ownerId, 'recipe_photo', base_path('tests/fixtures/media/valid.jpg'), null, null),
    );
}

function createAPendingReviewRecipe(string $ownerId): RecipeOutput
{
    $draft = createADraft($ownerId);

    return app(PublishRecipe::class)($draft->id, $ownerId);
}

function aFollow(string $followerId, string $followeeId): void
{
    $followeeUsername = EloquentUser::query()->whereKey($followeeId)->value('username');

    app(FollowUser::class)($followerId, $followeeUsername);
}

function aPantry(string $ownerId, string $name = 'Minha despensa'): PantryOutput
{
    return app(CreatePantry::class)($ownerId, $name);
}

function aPantryMember(string $pantryId, string $ownerId, string $inviteeUsername): void
{
    app(InvitePantryMember::class)($pantryId, $ownerId, $inviteeUsername);
}

function aGoogleIdentity(?string $googleId = null, ?string $email = null, string $name = 'Google User'): GoogleIdentity
{
    return new GoogleIdentity($googleId ?? (string) Ulid::generate(), $email, $name);
}

function fakeGoogleVerifier(GoogleIdentity $identity): void
{
    app()->instance(GoogleIdTokenVerifierInterface::class, new class($identity) implements GoogleIdTokenVerifierInterface
    {
        public function __construct(private GoogleIdentity $identity) {}

        public function verify(string $idToken): GoogleIdentity
        {
            return $this->identity;
        }
    });
}

function fakeInvalidGoogleVerifier(): void
{
    app()->instance(GoogleIdTokenVerifierInterface::class, new class implements GoogleIdTokenVerifierInterface
    {
        public function verify(string $idToken): GoogleIdentity
        {
            throw InvalidGoogleTokenException::create();
        }
    });
}
