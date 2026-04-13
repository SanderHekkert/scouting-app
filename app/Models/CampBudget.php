<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampBudget extends Model
{
    use BelongsToSection;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_NEEDS_CHANGES = 'needs_changes';

    protected $fillable = [
        'section',
        'camp_year',
        'title',
        'content',
        'meta',
        'status',
        'review_note',
        'processed_by_user_id',
        'processed_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'camp_year' => 'integer',
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
