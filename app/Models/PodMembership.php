<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PodMembership extends Model
{
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
