<?php

// tests/Feature/Console/PromoteUserToAdminCommandTest.php

use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('promotes an existing user to admin via the console', function () {
    anOwner();
    $username = EloquentUser::query()->value('username');

    $this->artisan('user:promote', ['username' => $username])
        ->assertExitCode(0);

    expect(EloquentUser::query()->where('username', $username)->value('role'))->toBe('admin');
});

it('fails with a clear message for a non-existent username', function () {
    $this->artisan('user:promote', ['username' => 'ninguem'])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});
