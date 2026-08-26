<?php

namespace App\Domain\Pantry;

final class PantryLimits
{
    public const int MAX_PANTRIES_PER_USER = 5;

    public const int MAX_MEMBERS_PER_PANTRY = 20;

    private function __construct() {}
}
