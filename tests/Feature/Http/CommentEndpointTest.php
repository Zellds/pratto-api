<?php

// tests/Feature/Http/CommentEndpointTest.php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('posts a comment', function () {
    $ownerToken = authenticatedToken($this);
    $ownerId = EloquentUser::query()->where('username', 'gabriel')->value('id');
    $recipe = createAPendingReviewRecipe($ownerId);

    $authorToken = authenticatedTokenFor($this, 'comment_author');

    $response = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Muito bom!']);

    $response->assertCreated()->assertJsonPath('body', 'Muito bom!');
});

it('rejects posting a comment without authentication', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);

    $response = $this->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Oi.']);

    $response->assertStatus(401);
});

it('lets the author edit their own comment', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'editor_author');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Original.'])->json('id');

    $response = $this->withToken($authorToken)->patchJson("/api/comments/{$commentId}", ['body' => 'Editado.']);

    $response->assertOk()->assertJsonPath('body', 'Editado.');
});

it('forbids a non-author from editing a comment', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'owner_author');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Original.'])->json('id');
    $intruderToken = authenticatedTokenFor($this, 'intruder_author');
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($intruderToken)->patchJson("/api/comments/{$commentId}", ['body' => 'Hackeado.']);

    $response->assertStatus(403);
});

it('forbids the recipe owner from editing a comment they did not author', function () {
    $ownerToken = authenticatedTokenFor($this, 'moderating_owner');
    $ownerId = EloquentUser::query()->where('username', 'moderating_owner')->value('id');
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'moderated_comment_author');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Original.'])->json('id');
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($ownerToken)->patchJson("/api/comments/{$commentId}", ['body' => 'Editado pelo dono.']);

    $response->assertStatus(403);
});

it('forbids the recipe owner from deleting a comment they did not author', function () {
    $ownerToken = authenticatedTokenFor($this, 'moderating_owner_2');
    $ownerId = EloquentUser::query()->where('username', 'moderating_owner_2')->value('id');
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'moderated_comment_author_2');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Não apagar.'])->json('id');
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($ownerToken)->deleteJson("/api/comments/{$commentId}");

    $response->assertStatus(403);
});

it('lets the author delete their own comment', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'deleter_author');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Apagar.'])->json('id');

    $response = $this->withToken($authorToken)->deleteJson("/api/comments/{$commentId}");

    $response->assertNoContent();
});

it('forbids a non-author from deleting a comment', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'owner_author_2');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Não apagar.'])->json('id');
    $intruderToken = authenticatedTokenFor($this, 'intruder_author_2');
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($intruderToken)->deleteJson("/api/comments/{$commentId}");

    $response->assertStatus(403);
});

it('lists comments without authentication', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'lister_author');
    $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Público.']);

    $response = $this->getJson("/api/recipes/{$recipe->id}/comments");

    $response->assertOk()->assertJsonCount(1);
});

it('returns 404 listing comments for a non-existent recipe', function () {
    $response = $this->getJson('/api/recipes/'.(string) new Ulid.'/comments');

    $response->assertStatus(404);
});

it('lets an admin delete any comment, even one they do not own', function () {
    $ownerId = anOwner()->value();
    $recipe = createAPendingReviewRecipe($ownerId);
    $authorToken = authenticatedTokenFor($this, 'moderated_author');
    $commentId = $this->withToken($authorToken)->postJson("/api/recipes/{$recipe->id}/comments", ['body' => 'Impróprio.'])->json('id');

    $adminToken = authenticatedTokenFor($this, 'comment_admin');
    EloquentUser::query()->where('username', 'comment_admin')->update(['role' => 'admin']);
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($adminToken)->deleteJson("/api/comments/{$commentId}");

    $response->assertNoContent();
});
