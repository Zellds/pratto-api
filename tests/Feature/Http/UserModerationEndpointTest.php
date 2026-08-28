<?php

// tests/Feature/Http/UserModerationEndpointTest.php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function anAdminTokenForModeration(TestCase $test): string
{
    $token = authenticatedTokenFor($test, 'user_mod_admin');
    EloquentUser::query()->where('username', 'user_mod_admin')->update(['role' => 'admin']);

    return $token;
}

it('lets an admin promote a user to admin', function () {
    authenticatedTokenFor($this, 'future_admin');
    $adminToken = anAdminTokenForModeration($this);
    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer {$adminToken}")->patchJson('/api/users/future_admin/promote');

    $response->assertOk();
    expect(EloquentUser::query()->where('username', 'future_admin')->value('role'))->toBe('admin');
});

it('lets an admin ban a user with a reason, and the banned user can no longer log in', function () {
    authenticatedTokenFor($this, 'to_be_banned');
    $adminToken = anAdminTokenForModeration($this);
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson('/api/users/to_be_banned/ban', ['reason' => 'Conteúdo impróprio.'])
        ->assertOk();

    $this->app['auth']->forgetGuards();
    $this->postJson('/api/login', ['username' => 'to_be_banned', 'password' => 'senha-forte-123'])
        ->assertStatus(403);
});

it('lets an admin unban a user', function () {
    authenticatedTokenFor($this, 'unban_target');
    $adminToken = anAdminTokenForModeration($this);
    $this->app['auth']->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson('/api/users/unban_target/ban', ['reason' => 'Motivo.']);

    $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson('/api/users/unban_target/unban')
        ->assertOk();

    $this->app['auth']->forgetGuards();
    $this->postJson('/api/login', ['username' => 'unban_target', 'password' => 'senha-forte-123'])
        ->assertOk();
});

it('returns 404 for a non-existent username', function () {
    $adminToken = anAdminTokenForModeration($this);
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$adminToken}")
        ->patchJson('/api/users/ninguem/promote')
        ->assertStatus(404);
});

it('forbids a non-admin from banning a user', function () {
    authenticatedTokenFor($this, 'some_target');
    $token = authenticatedToken($this);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/users/some_target/ban', ['reason' => 'Motivo.'])
        ->assertStatus(403);
});
