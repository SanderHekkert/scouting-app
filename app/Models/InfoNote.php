<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoNote extends Model
{
    protected $fillable = [
        'category',
        'content',
        'link',
    ];
}
