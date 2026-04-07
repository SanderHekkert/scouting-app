<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
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
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sectionRoles(): HasMany
    {
        return $this->hasMany(UserSectionRole::class);
    }

    public function hasRoleInSection(string $section, array $roles = []): bool
    {
        $isAdmin = $this->sectionRoles()
            ->where('section', UserSectionRole::SECTION_ALL)
            ->where('role', UserSectionRole::ROLE_ADMIN)
            ->exists();
        if ($isAdmin) {
            return true;
        }

        $query = $this->sectionRoles()->where('section', $section);
        if ($roles !== []) {
            $query->whereIn('role', $roles);
        }

        return $query->exists();
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
