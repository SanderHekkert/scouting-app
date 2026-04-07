<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSectionRole extends Model
{
    public const SECTION_ALL = '*';
    public const SECTION_DOLFIJNEN = 'dolfijnen';
    public const SECTION_ZEEVERKENNERS = 'zeeverkenners';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEAMLEIDER = 'teamleider';
    public const ROLE_LEIDING = 'leiding';
    public const ROLE_OUDERCONTACT = 'ouder_contact';

    public const ALL_SECTIONS = [
        self::SECTION_DOLFIJNEN,
        self::SECTION_ZEEVERKENNERS,
    ];

    public const ALL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_TEAMLEIDER,
        self::ROLE_LEIDING,
        self::ROLE_OUDERCONTACT,
    ];

    protected $fillable = [
        'user_id',
        'section',
        'role',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
