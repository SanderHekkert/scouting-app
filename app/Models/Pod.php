<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pod extends Model
{
    protected $fillable = [
        'name',
    ];

    public function memberships()
    {
        return $this->hasMany(PodMembership::class);
    }
}
