<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('uploads an avatar and returns signed urls', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->post('/api/media', [
        'file' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
        'kind' => 'avatar',
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'approved')
        ->assertJsonPath('kind', 'avatar')
        ->assertJsonStructure(['id', 'thumbnailUrl', 'displayUrl']);
});

it('uploads a recipe photo pending review', function () {
    Storage::fake('media');
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->post('/api/media', [
        'file' => UploadedFile::fake()->image('recipe.jpg', 800, 600),
        'kind' => 'recipe_photo',
        'focal_x' => 0.3,
        'focal_y' => 0.6,
    ]);

    $response->assertCreated()->assertJsonPath('status', 'pending_review');
});

it('rejects an unsupported mime type', function () {
    $token = authenticatedToken($this);

    $response = $this->withToken($token)->post('/api/media', [
        'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        'kind' => 'avatar',
    ]);

    $response->assertStatus(422);
});

it('requires authentication', function () {
    Storage::fake('media');

    $response = $this->post('/api/media', [
        'file' => UploadedFile::fake()->image('avatar.jpg'),
        'kind' => 'avatar',
    ]);

    $response->assertStatus(401);
});
