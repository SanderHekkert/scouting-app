<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthForm extends Model
{
    protected $fillable = [
        'section',
        'member_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size',
        'uploaded_by_user_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
