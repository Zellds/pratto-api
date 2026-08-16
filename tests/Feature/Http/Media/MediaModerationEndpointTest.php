<?php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

function uploadRecipePhotoViaHttp(TestCase $test, string $token): string
{
    Storage::fake('media');

    return $test->withToken($token)->post('/api/media', [
        'file' => UploadedFile::fake()->image('recipe.jpg', 800, 600),
        'kind' => 'recipe_photo',
    ])->json('id');
}

it('lets an admin approve pending media', function () {
    $ownerToken = authenticatedToken($this);
    $mediaId = uploadRecipePhotoViaHttp($this, $ownerToken);

    $adminToken = authenticatedTokenFor($this, 'admin_user');
    EloquentUser::query()
        ->where('username', 'admin_user')->update(['role' => 'admin']);
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($adminToken)->patch("/api/media/{$mediaId}/approve");

    $response->assertOk()->assertJsonPath('status', 'approved');
});

it('lets an admin reject media with a reason', function () {
    $ownerToken = authenticatedToken($this);
    $mediaId = uploadRecipePhotoViaHttp($this, $ownerToken);

    $adminToken = authenticatedTokenFor($this, 'admin_user_2');
    EloquentUser::query()
        ->where('username', 'admin_user_2')->update(['role' => 'admin']);
    $this->app['auth']->forgetGuards();

    $response = $this->withToken($adminToken)->patch("/api/media/{$mediaId}/reject", ['reason' => 'blurry']);

    $response->assertOk()
        ->assertJsonPath('status', 'rejected')
        ->assertJsonPath('rejectionReason', 'blurry');
});

it('forbids a non-admin from approving media', function () {
    $ownerToken = authenticatedToken($this);
    $mediaId = uploadRecipePhotoViaHttp($this, $ownerToken);

    $response = $this->withToken($ownerToken)->patch("/api/media/{$mediaId}/approve");

    $response->assertStatus(403);
});
