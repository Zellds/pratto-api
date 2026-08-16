<?php

namespace App\Domain\Media\Enums;

enum MediaStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
