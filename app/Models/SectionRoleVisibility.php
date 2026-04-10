<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionRoleVisibility extends Model
{
    protected $fillable = [
        'section',
        'role',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * @return list<string>
     */
    public static function defaultsForSection(string $section): array
    {
        if ($section === UserSectionRole::SECTION_BESTUUR) {
            return UserSectionRole::BESTUUR_ROLES;
        }

        return [
            UserSectionRole::ROLE_TEAMLEIDER,
            UserSectionRole::ROLE_LEIDING,
            UserSectionRole::ROLE_OUDERCONTACT,
            UserSectionRole::ROLE_LID,
        ];
    }

    /**
     * @return array<string,bool>
     */
    public static function visibilityMapForSection(string $section): array
    {
        $defaults = self::defaultsForSection($section);
        $rows = self::query()
            ->where('section', $section)
            ->whereIn('role', $defaults)
            ->get(['role', 'is_enabled'])
            ->keyBy('role');

        $map = [];
        foreach ($defaults as $role) {
            $map[$role] = array_key_exists($role, $rows->all()) ? (bool) $rows[$role]->is_enabled : true;
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function enabledRolesForSection(string $section): array
    {
        return collect(self::visibilityMapForSection($section))
            ->filter(fn (bool $enabled): bool => $enabled)
            ->keys()
            ->values()
            ->all();
    }
}
