<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'installed',
        'first_name',
        'last_name',
        'birthday',
        'age',
        'address',
        'postal_code',
        'city',
        'email_parents',
        'phone_mother',
        'phone_father',
        'bijzonderheden',
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
