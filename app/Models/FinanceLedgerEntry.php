<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceLedgerEntry extends Model
{
    public const TYPE_DEBIT = 'debit';

    public const TYPE_CREDIT = 'credit';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'section',
        'pot_id',
        'declaration_id',
        'type',
        'amount',
        'balance_after',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function pot(): BelongsTo
    {
        return $this->belongsTo(FinancePot::class, 'pot_id');
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(FinanceDeclaration::class, 'declaration_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
