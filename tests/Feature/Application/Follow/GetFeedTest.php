<?php

use App\Application\Follow\UseCases\GetFeed;
use App\Application\Rating\UseCases\RateRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns an empty feed when the user follows no one', function () {
    $viewer = anOwner();

    $results = app(GetFeed::class)($viewer->value(), 1, 20);

    expect($results)->toBe([]);
});

it('returns only published/pending_review recipes from followed users, with rating aggregates', function () {
    $viewer = anOwner();
    $followed = anOwner();
    aFollow($viewer->value(), $followed->value());

    $draft = createADraft($followed->value());
    $published = app(PublishRecipe::class)($draft->id, $followed->value());
    app(RateRecipe::class)($published->id, anOwner()->value(), 4.0);

    $notFollowedDraft = createADraft(anOwner()->value());
    app(PublishRecipe::class)($notFollowedDraft->id, $notFollowedDraft->ownerId);

    $results = app(GetFeed::class)($viewer->value(), 1, 20);

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe($published->id)
        ->and($results[0]->averageRating)->toBe(4.0)
        ->and($results[0]->ratingsCount)->toBe(1);
});
