<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('lets the owner adjust the focal point', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);
    $mediaId = $this->withToken($token)->post('/api/media', [
        'file' => UploadedFile::fake()->image('recipe.jpg', 800, 600),
        'kind' => 'recipe_photo',
    ])->json('id');

    $response = $this->withToken($token)->patch("/api/media/{$mediaId}/focal-point", [
        'focal_x' => 0.1,
        'focal_y' => 0.9,
    ]);

    $response->assertOk()
        ->assertJsonPath('focalX', 0.1)
        ->assertJsonPath('focalY', 0.9);
});

it('forbids a non-owner from adjusting the focal point', function () {
    Storage::fake('media');
    $ownerToken = authenticatedToken($this);
    $mediaId = $this->withToken($ownerToken)->post('/api/media', [
        'file' => UploadedFile::fake()->image('recipe.jpg', 800, 600),
        'kind' => 'recipe_photo',
    ])->json('id');

    $intruderToken = authenticatedTokenFor($this, 'intruder');
    $this->app['auth']->forgetGuards();
    $response = $this->withToken($intruderToken)->patch("/api/media/{$mediaId}/focal-point", [
        'focal_x' => 0.1,
        'focal_y' => 0.9,
    ]);

    $response->assertStatus(403);
});

it('returns 404 for a non-existent media', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->patch('/api/media/'.(string) new Ulid.'/focal-point', [
        'focal_x' => 0.1,
        'focal_y' => 0.9,
    ]);

    $response->assertStatus(404);
});
