<?php

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\BanUser;
use App\Application\User\UseCases\LoginWithGoogle;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\DisplayName;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\Exceptions\UserBannedException;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new user on the first login via google', function () {
    fakeGoogleVerifier(aGoogleIdentity(name: 'Jane Doe', email: 'jane@example.com'));

    $output = app(LoginWithGoogle::class)('any-id-token');

    expect($output->token)->not->toBeEmpty();
    $user = app(UserRepositoryInterface::class)->findByUsername(Username::fromString('jane_doe'));
    expect($user)->not->toBeNull();
});

it('logs in an existing google user without creating a duplicate account', function () {
    $identity = aGoogleIdentity(name: 'Returning User');
    fakeGoogleVerifier($identity);
    app(LoginWithGoogle::class)('token-1');

    $output = app(LoginWithGoogle::class)('token-1');

    expect($output->token)->not->toBeEmpty();
    expect(EloquentUser::query()->where('google_id', $identity->googleId)->count())->toBe(1);
});

it('resolves a username collision by appending a numeric suffix', function () {
    app(RegisterUser::class)(new RegisterUserInput('jane_doe', 'Existing Jane', 'senha-forte-123'));
    fakeGoogleVerifier(aGoogleIdentity(name: 'Jane Doe'));

    app(LoginWithGoogle::class)('some-token');

    expect(app(UserRepositoryInterface::class)->findByUsername(Username::fromString('jane_doe_2')))->not->toBeNull();
});

it('blocks a banned user from logging in via google', function () {
    $identity = aGoogleIdentity(name: 'Banned User');
    fakeGoogleVerifier($identity);
    app(LoginWithGoogle::class)('token-1');
    $user = app(UserRepositoryInterface::class)->findByGoogleId($identity->googleId);
    $admin = anAdmin();
    app(BanUser::class)($user->username()->value(), $admin->value(), 'Motivo.');

    app(LoginWithGoogle::class)('token-1');
})->throws(UserBannedException::class);

it('throws InvalidGoogleTokenException for an invalid token', function () {
    fakeInvalidGoogleVerifier();

    app(LoginWithGoogle::class)('bad-token');
})->throws(InvalidGoogleTokenException::class);

it('truncates an over-long google display name instead of crashing', function () {
    $longName = str_repeat('a', 90);
    $identity = aGoogleIdentity(name: $longName);
    fakeGoogleVerifier($identity);

    $output = app(LoginWithGoogle::class)('token-long-name');

    expect($output->token)->not->toBeEmpty();
    $user = app(UserRepositoryInterface::class)->findByGoogleId($identity->googleId);
    expect($user)->not->toBeNull();
    expect(mb_strlen($user->displayName()->value()))->toBe(80);
});

it('resolves a concurrent google_id collision by logging in as the account that won the race', function () {
    $googleId = 'race-google-id-1';
    $realRepository = app(UserRepositoryInterface::class);
    $winner = User::register(Ulid::generate(), Username::fromString('race_winner'), DisplayName::fromString('Race Winner'));
    $realRepository->registerWithGoogle($winner, $googleId, 'winner@example.com');

    // Simulates the race window: findByGoogleId() reports "not found" only
    // on the very first call (the check LoginWithGoogle performs before
    // registering), just as a concurrent request that won the race would
    // make the real check-then-act lookup miss the just-inserted row.
    $decorator = new class($realRepository) implements UserRepositoryInterface
    {
        private int $findByGoogleIdCalls = 0;

        public function __construct(private UserRepositoryInterface $inner) {}

        public function findByUsername(Username $username): ?User
        {
            return $this->inner->findByUsername($username);
        }

        public function findById(Ulid $id): ?User
        {
            return $this->inner->findById($id);
        }

        public function save(User $user): void
        {
            $this->inner->save($user);
        }

        public function registerWithPassword(User $user, string $plainPassword): void
        {
            $this->inner->registerWithPassword($user, $plainPassword);
        }

        public function setPassword(Ulid $id, string $plainPassword): void
        {
            $this->inner->setPassword($id, $plainPassword);
        }

        public function verifyCredentials(Username $username, string $plainPassword): ?User
        {
            return $this->inner->verifyCredentials($username, $plainPassword);
        }

        public function findByGoogleId(string $googleId): ?User
        {
            $this->findByGoogleIdCalls++;

            if ($this->findByGoogleIdCalls === 1) {
                return null;
            }

            return $this->inner->findByGoogleId($googleId);
        }

        public function registerWithGoogle(User $user, string $googleId, ?string $email): void
        {
            $this->inner->registerWithGoogle($user, $googleId, $email);
        }

        public function linkGoogleId(Ulid $id, string $googleId, ?string $email): void
        {
            $this->inner->linkGoogleId($id, $googleId, $email);
        }
    };

    app()->instance(UserRepositoryInterface::class, $decorator);
    fakeGoogleVerifier(aGoogleIdentity(googleId: $googleId, name: 'Different Name'));

    $output = app(LoginWithGoogle::class)('token-race-google-id');

    expect($output->token)->not->toBeEmpty();
    expect(EloquentUser::query()->where('google_id', $googleId)->count())->toBe(1);
});

it('resolves a concurrent username collision on registration by retrying with a fresh username', function () {
    $realRepository = app(UserRepositoryInterface::class);
    $realRepository->registerWithPassword(
        User::register(Ulid::generate(), Username::fromString('racer'), DisplayName::fromString('Racer')),
        'senha-forte-123',
    );

    // Simulates the race window: findByUsername() reports "not found" only
    // on the very first call (the check generateUsername() performs before
    // registering), just as a concurrent request taking that exact
    // username would make the real check-then-insert lookup miss it.
    $decorator = new class($realRepository) implements UserRepositoryInterface
    {
        private int $findByUsernameCalls = 0;

        public function __construct(private UserRepositoryInterface $inner) {}

        public function findByUsername(Username $username): ?User
        {
            $this->findByUsernameCalls++;

            if ($this->findByUsernameCalls === 1) {
                return null;
            }

            return $this->inner->findByUsername($username);
        }

        public function findById(Ulid $id): ?User
        {
            return $this->inner->findById($id);
        }

        public function save(User $user): void
        {
            $this->inner->save($user);
        }

        public function registerWithPassword(User $user, string $plainPassword): void
        {
            $this->inner->registerWithPassword($user, $plainPassword);
        }

        public function setPassword(Ulid $id, string $plainPassword): void
        {
            $this->inner->setPassword($id, $plainPassword);
        }

        public function verifyCredentials(Username $username, string $plainPassword): ?User
        {
            return $this->inner->verifyCredentials($username, $plainPassword);
        }

        public function findByGoogleId(string $googleId): ?User
        {
            return $this->inner->findByGoogleId($googleId);
        }

        public function registerWithGoogle(User $user, string $googleId, ?string $email): void
        {
            $this->inner->registerWithGoogle($user, $googleId, $email);
        }

        public function linkGoogleId(Ulid $id, string $googleId, ?string $email): void
        {
            $this->inner->linkGoogleId($id, $googleId, $email);
        }
    };

    app()->instance(UserRepositoryInterface::class, $decorator);
    fakeGoogleVerifier(aGoogleIdentity(name: 'Racer'));

    $output = app(LoginWithGoogle::class)('token-race-username');

    expect($output->token)->not->toBeEmpty();
    expect(app(UserRepositoryInterface::class)->findByUsername(Username::fromString('racer_2')))->not->toBeNull();
});
