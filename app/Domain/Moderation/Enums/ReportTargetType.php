<?php

namespace App\Domain\Moderation\Enums;

enum ReportTargetType: string
{
    case Recipe = 'recipe';
    case User = 'user';
    case Comment = 'comment';
}
