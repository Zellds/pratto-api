<?php

// tests/Feature/Http/ReportEndpointTest.php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(RefreshDatabase::class);

function anAdminTokenForReports(TestCase $test): string
{
    $token = authenticatedTokenFor($test, 'report_admin');
    EloquentUser::query()->where('username', 'report_admin')->update(['role' => 'admin']);

    return $token;
}

it('lets an authenticated user report a recipe', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");

    $reporterToken = authenticatedTokenFor($this, 'reporter_one');
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$reporterToken}")->postJson('/api/reports', [
        'target_type' => 'recipe',
        'target_id' => $recipeId,
        'reason' => 'Foto imprópria.',
    ]);

    $response->assertCreated()->assertJsonPath('status', 'open')->assertJsonPath('targetType', 'recipe');
});

it('rejects reporting without authentication', function () {
    $this->postJson('/api/reports', ['target_type' => 'recipe', 'target_id' => (string) Str::ulid(), 'reason' => 'Motivo.'])
        ->assertStatus(401);
});

it('lets an admin list open reports', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");
    $reporterToken = authenticatedTokenFor($this, 'reporter_two');
    $this->app['auth']->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$reporterToken}")->postJson('/api/reports', [
        'target_type' => 'recipe', 'target_id' => $recipeId, 'reason' => 'Motivo.',
    ]);

    $adminToken = anAdminTokenForReports($this);
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->getJson('/api/reports')
        ->assertOk()
        ->assertJsonCount(1);
});

it('forbids a non-admin from listing reports', function () {
    $token = authenticatedToken($this);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/reports')->assertStatus(403);
});

it('lets an admin resolve a report', function () {
    $ownerToken = authenticatedToken($this);
    $recipeId = $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson('/api/recipes', recipePayload())->json('id');
    $this->withHeader('Authorization', "Bearer {$ownerToken}")->postJson("/api/recipes/{$recipeId}/publish");
    $reporterToken = authenticatedTokenFor($this, 'reporter_three');
    $this->app['auth']->forgetGuards();
    $reportId = $this->withHeader('Authorization', "Bearer {$reporterToken}")->postJson('/api/reports', [
        'target_type' => 'recipe', 'target_id' => $recipeId, 'reason' => 'Motivo.',
    ])->json('id');

    $adminToken = anAdminTokenForReports($this);
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson("/api/reports/{$reportId}", ['status' => 'dismissed', 'note' => 'Sem problema encontrado.']);

    $response->assertOk()->assertJsonPath('status', 'dismissed')->assertJsonPath('resolutionNote', 'Sem problema encontrado.');
});
