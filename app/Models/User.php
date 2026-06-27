<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property bool|null $requires_approval When null, approval requirement is inherited from groups and config default.
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory;

    protected $fillable = [
        'username',
        'name',
        'email',
        'azure_oid',
        'password',
        'requires_approval',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(Bundle::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(UserRole $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $role->value);
        }

        return $this->roles()->where('slug', $role->value)->exists();
    }

    public function hasAnyRole(UserRole ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(UserRole $role): void
    {
        $roleId = Role::idFor($role);

        $this->roles()->syncWithoutDetaching([$roleId]);
    }

    public function revokeRole(UserRole $role): void
    {
        if ($role === UserRole::User) {
            throw new InvalidArgumentException('The user role cannot be revoked.');
        }

        $roleId = Role::idFor($role);

        $this->roles()->detach($roleId);
    }

    /**
     * @return Collection<int, string>
     */
    public function roleSlugs(): Collection
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('slug');
        }

        return $this->roles()->pluck('slug');
    }

    public function syncRoles(array $roles): void
    {
        $slugs = collect($roles)
            ->map(function ($role) {
                if ($role instanceof UserRole) {
                    return $role->value;
                }

                return UserRole::from($role)->value;
            })
            ->unique()
            ->values();

        if (! $slugs->contains(UserRole::User->value)) {
            $slugs->prepend(UserRole::User->value);
        }

        $roleIds = Role::query()
            ->whereIn('slug', $slugs)
            ->pluck('id');

        $this->roles()->sync($roleIds);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(UserRole::Admin);
    }
}
