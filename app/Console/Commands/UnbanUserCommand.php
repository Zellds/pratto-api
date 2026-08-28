<?php

namespace App\Console\Commands;

use App\Application\User\UseCases\UnbanUser;
use App\Domain\User\Exceptions\UserNotFoundException;
use Illuminate\Console\Command;

class UnbanUserCommand extends Command
{
    protected $signature = 'user:unban {username : The username of the account to unban}';

    protected $description = 'Unbans an existing user — used as a recovery path when there is no admin left to do it via the API';

    public function handle(UnbanUser $unbanUser): int
    {
        $username = (string) $this->argument('username');

        try {
            $unbanUser($username);
        } catch (UserNotFoundException) {
            $this->error("User \"{$username}\" not found.");

            return self::FAILURE;
        }

        $this->info("User \"{$username}\" is now unbanned.");

        return self::SUCCESS;
    }
}
