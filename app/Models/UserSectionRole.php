<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSectionRole extends Model
{
    public const SECTION_ALL = '*';

    public const SECTION_DOLFIJNEN = 'dolfijnen';

    public const SECTION_ZEEVERKENNERS = 'zeeverkenners';

    public const SECTION_BEVERS = 'bevers';

    public const SECTION_WILDE_VAART = 'wilde_vaart';

    public const SECTION_LOODSEN = 'loodsen';

    public const SECTION_BESTUUR = 'bestuur';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_BESTUURSLID = 'bestuurslid';

    public const ROLE_TEAMLEIDER = 'teamleider';

    public const ROLE_LEIDING = 'leiding';

    public const ROLE_OUDERCONTACT = 'ouder_contact';

    public const ROLE_LID = 'lid';

    public const ALL_SECTIONS = [
        self::SECTION_BEVERS,
        self::SECTION_DOLFIJNEN,
        self::SECTION_ZEEVERKENNERS,
        self::SECTION_WILDE_VAART,
        self::SECTION_LOODSEN,
        self::SECTION_BESTUUR,
    ];

    public const ALL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_BESTUURSLID,
        self::ROLE_TEAMLEIDER,
        self::ROLE_LEIDING,
        self::ROLE_OUDERCONTACT,
        self::ROLE_LID,
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
