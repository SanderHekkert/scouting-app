<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class Pod extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'name',
    ];

    public function memberships()
    {
        return $this->hasMany(PodMembership::class);
    }
}
