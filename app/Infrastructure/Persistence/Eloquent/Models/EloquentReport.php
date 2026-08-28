<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EloquentReport extends Model
{
    use HasUlids;

    protected $table = 'reports';

    protected $fillable = [
        'id', 'reporter_id', 'target_type', 'target_id', 'reason',
        'status', 'resolution_note', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
