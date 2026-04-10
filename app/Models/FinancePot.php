<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancePot extends Model
{
    protected $fillable = [
        'section',
        'name',
        'starting_amount',
        'current_amount',
        'active',
    ];

    protected $casts = [
        'starting_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function declarations(): HasMany
    {
        return $this->hasMany(FinanceDeclaration::class, 'pot_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FinanceLedgerEntry::class, 'pot_id');
    }
}
