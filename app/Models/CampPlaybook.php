<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampPlaybook extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'section',
        'camp_year',
        'title',
        'content',
        'meta',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'camp_year' => 'integer',
        'meta' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
