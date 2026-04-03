<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearThemeEntry extends Model
{
    protected $fillable = [
        'sort_order',
        'label',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
