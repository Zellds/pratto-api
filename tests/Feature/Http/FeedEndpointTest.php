<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns an empty feed when following no one', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->getJson('/api/feed');

    $response->assertOk()->assertJsonCount(0);
});

it('returns recipes from followed users', function () {
    $token = authenticatedToken($this);
    $followerId = App\Infrastructure\Persistence\Eloquent\Models\EloquentUser::query()->where('username', 'gabriel')->value('id');

    $followed = anOwner();
    aFollow($followerId, $followed->value());

    $draft = createADraft($followed->value());
    $published = app(App\Application\Recipe\UseCases\PublishRecipe::class)($draft->id, $followed->value());

    $response = $this->withToken($token)->getJson('/api/feed');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $published->id);
});

it('requires authentication', function () {
    $response = $this->getJson('/api/feed');

    $response->assertStatus(401);
});
