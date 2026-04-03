<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'installed',
        'first_name',
        'last_name',
        'birthday',
        'age',
        'address',
        'phone_mother',
        'phone_father',
        'active',
    ];
}
