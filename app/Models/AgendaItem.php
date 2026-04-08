<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class AgendaItem extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'theme',
        'event_date',
        'location',
        'time_slot',
        'invitees',
        'link_url',
        'attachments',
        'notes',
    ];
}
