<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class InfoNote extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'category',
        'content',
        'link',
        'section',
    ];
}
