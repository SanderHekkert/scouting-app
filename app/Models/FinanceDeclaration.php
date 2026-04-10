<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceDeclaration extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'section',
        'created_by_user_id',
        'pot_id',
        'status',
        'amount',
        'iban',
        'account_name',
        'description_total',
        'description_lines',
        'receipt_path',
        'receipt_name',
        'receipt_mime',
        'receipt_size',
        'declared_at',
        'processed_by_user_id',
        'processed_at',
        'review_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'declared_at' => 'date',
        'processed_at' => 'datetime',
    ];

    public function pot(): BelongsTo
    {
        return $this->belongsTo(FinancePot::class, 'pot_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
