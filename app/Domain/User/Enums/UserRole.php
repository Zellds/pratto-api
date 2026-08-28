<?php

namespace App\Domain\User\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
