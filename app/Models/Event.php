<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'theme',
        'event_date',
        'event_type',
        'activity',
        'program_by',
        'absent',
        'notes',
        'task_item_ids',
    ];

    protected $casts = [
        'task_item_ids' => 'array',
    ];
}
