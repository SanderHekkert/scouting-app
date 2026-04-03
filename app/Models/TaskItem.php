<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskItem extends Model
{
    protected $table = 'task_items';

    protected $fillable = [
        'category',
        'title',
        'owner',
        'owner_user_id',
        'owner_leader_id',
        'description',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function ownerLeader(): BelongsTo
    {
        return $this->belongsTo(Leader::class, 'owner_leader_id');
    }
}
