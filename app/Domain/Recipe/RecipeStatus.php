<?php

namespace App\Domain\Recipe;

enum RecipeStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
}
