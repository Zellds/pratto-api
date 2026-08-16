<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentMedia extends Model
{
    use HasUlids;

    protected $table = 'media';

    protected $fillable = [
        'id', 'owner_user_id', 'kind', 'storage_key', 'focal_x', 'focal_y',
        'width', 'height', 'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
    ];
}
