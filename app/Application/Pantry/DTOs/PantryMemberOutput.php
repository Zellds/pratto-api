<?php

// app/Application/Pantry/DTOs/PantryMemberOutput.php

namespace App\Application\Pantry\DTOs;

final readonly class PantryMemberOutput
{
    public function __construct(
        public string $userId,
        public string $username,
        public string $displayName,
        public string $role,
    ) {}
}
