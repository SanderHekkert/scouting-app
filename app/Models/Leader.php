<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leader extends Model
{
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

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
