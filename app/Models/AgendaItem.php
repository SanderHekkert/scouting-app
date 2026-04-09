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
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
