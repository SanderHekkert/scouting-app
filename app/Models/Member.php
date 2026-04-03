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
        'tipper_topper_opkomst',
        'tipper_topper_opkomst_order',
    ];

    protected function casts(): array
    {
        return [
            'installed' => 'boolean',
            'active' => 'boolean',
            'tipper_topper_opkomst' => 'boolean',
        ];
    }

    public function podMemberships()
    {
        return $this->hasMany(PodMembership::class);
    }
}
