<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class PodMembership extends Model
{
    use BelongsToSection;

    protected $fillable = [
        'pod_id',
        'member_id',
        'role',
    ];

    public function pod()
    {
        return $this->belongsTo(Pod::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
