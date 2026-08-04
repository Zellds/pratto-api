<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function findByUsername(Username $username): ?User;

    public function save(User $user): void;
}
