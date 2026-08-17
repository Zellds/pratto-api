<?php

namespace App\Domain\Recipe\Enums;

enum RecipeStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
}
