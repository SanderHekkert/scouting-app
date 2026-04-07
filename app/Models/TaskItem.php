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
        'category',
        'title',
        'owner',
        'owner_user_id',
        'owner_user_ids',
        'description',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'date',
        'owner_user_ids' => 'array',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
