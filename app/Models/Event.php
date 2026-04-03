<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'theme',
        'event_date',
        'event_type',
        'activity',
        'program_by',
        'absent',
        'notes',
    ];
}
