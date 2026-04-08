<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'leader_name',
    'email',
    'password',
    'first_name',
    'last_name',
    'address',
    'postal_code',
    'city',
    'birthday',
    'phone_number',
    'bijzonderheden',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sectionRoles(): HasMany
    {
        return $this->hasMany(UserSectionRole::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function hasRoleInSection(string $section, array $roles = []): bool
    {
        if ($this->isGlobalAdmin() || $this->isGlobalBoardMember()) {
            return true;
        }

        $query = $this->sectionRoles()->where('section', $section);
        if ($roles !== []) {
            $query->whereIn('role', $roles);
        }

        return $query->exists();
    }

    public function isGlobalAdmin(): bool
    {
        return $this->sectionRoles()
            ->where('section', UserSectionRole::SECTION_ALL)
            ->where('role', UserSectionRole::ROLE_ADMIN)
            ->exists();
    }

    public function roleInSection(string $section): ?string
    {
        if ($this->isGlobalAdmin()) {
            return UserSectionRole::ROLE_ADMIN;
        }
        if ($this->isGlobalBoardMember()) {
            return UserSectionRole::ROLE_BESTUURSLID;
        }

        return $this->sectionRoles()
            ->where('section', $section)
            ->value('role');
    }

    public function isGlobalBoardMember(): bool
    {
        return $this->sectionRoles()
            ->where('section', UserSectionRole::SECTION_ALL)
            ->where('role', UserSectionRole::ROLE_BESTUURSLID)
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
        ];
    }
}
