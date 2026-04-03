<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leader extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'address',
        'postal_code',
        'city',
        'birthday',
        'phone_number',
        'email',
        'bijzonderheden',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
        ];
    }
}
