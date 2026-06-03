<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskItem extends Model
{
    use BelongsToSection;

    protected $table = 'task_items';

    protected $fillable = [
        'section',
        'category',
        'title',
        'owner',
        'owner_user_id',
        'owner_user_ids',
        'description',
        'deadlines',
        'completed_at',
        'shared_sections',
    ];

    protected $casts = [
        'owner_user_ids' => 'array',
        'deadlines' => 'array',
        'shared_sections' => 'array',
        'completed_at' => 'datetime',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
