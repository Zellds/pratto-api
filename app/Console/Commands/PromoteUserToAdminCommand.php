<?php

namespace App\Console\Commands;

use App\Application\User\UseCases\PromoteToAdmin;
use App\Domain\User\Exceptions\UserNotFoundException;
use Illuminate\Console\Command;

class PromoteUserToAdminCommand extends Command
{
    protected $signature = 'user:promote {username : The username of the account to promote}';

    protected $description = 'Promotes an existing user to admin — used to bootstrap the first admin account';

    public function handle(PromoteToAdmin $promoteToAdmin): int
    {
        $username = (string) $this->argument('username');

        try {
            $promoteToAdmin($username);
        } catch (UserNotFoundException) {
            $this->error("User \"{$username}\" not found.");

            return self::FAILURE;
        }

        $this->info("User \"{$username}\" is now an admin.");

        return self::SUCCESS;
    }
}
