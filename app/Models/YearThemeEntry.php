<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class YearThemeEntry extends Model
{
    use BelongsToSection;

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
