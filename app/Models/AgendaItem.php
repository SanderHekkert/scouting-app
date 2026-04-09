<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaItem extends Model
{
    protected $fillable = [
        'owner_user_id',
        'audience_scope',
        'target_user_ids',
        'theme',
        'event_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'time_slot',
        'invitees',
        'link_url',
        'attachments',
        'notes',
        'section',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'event_date' => 'date',
        'end_date' => 'date',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
