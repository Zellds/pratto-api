<?php

// tests/Feature/Console/UnbanUserCommandTest.php

use App\Application\User\UseCases\BanUser;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('unbans a banned user via the console', function () {
    $adminId = anAdmin();
    $userId = anOwner();
    $username = EloquentUser::query()->whereKey($userId->value())->value('username');

    app(BanUser::class)($username, $adminId->value(), 'Motivo.');

    $this->artisan('user:unban', ['username' => $username])
        ->assertExitCode(0);

    expect(EloquentUser::query()->where('username', $username)->value('status'))->toBe('active');
});

it('fails with a clear message for a non-existent username', function () {
    $this->artisan('user:unban', ['username' => 'ninguem'])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});
