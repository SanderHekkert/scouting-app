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
        'location',
        'time_slot',
        'invitees',
        'link_url',
        'attachments',
        'absent',
        'present_names',
        'notes',
        'task_item_ids',
        'shared_sections',
    ];

    protected $casts = [
        'present_names' => 'array',
        'task_item_ids' => 'array',
        'shared_sections' => 'array',
    ];
}
