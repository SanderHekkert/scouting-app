<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use BelongsToSection;

    protected static function booted(): void
    {
        static::saving(function (self $member): void {
            $member->age = self::calculateAgeFromBirthday($member->birthday);
        });
    }

    public static function calculateAgeFromBirthday(mixed $birthday): ?int
    {
        if (empty($birthday)) {
            return null;
        }

        try {
            $birthdayDate = Carbon::parse((string) $birthday)->startOfDay();
            $today = now()->startOfDay();

            return $birthdayDate->isFuture() ? 0 : $birthdayDate->diffInYears($today);
        } catch (\Throwable) {
            return null;
        }
    }

    protected $fillable = [
        'section',
        'installed',
        'gedoopt',
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
        'tipper_topper_opkomst',
        'tipper_topper_opkomst_order',
    ];

    protected function casts(): array
    {
        return [
            'installed' => 'boolean',
            'gedoopt' => 'boolean',
            'tipper_topper_opkomst' => 'boolean',
        ];
    }

    public function podMemberships()
    {
        return $this->hasMany(PodMembership::class);
    }
}
