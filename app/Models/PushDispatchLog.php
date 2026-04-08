<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushDispatchLog extends Model
{
    protected $fillable = [
        'dispatch_key',
        'kind',
        'scheduled_for',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'meta' => 'array',
        ];
    }
}
