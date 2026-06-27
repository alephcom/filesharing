<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use InvalidArgumentException;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    public function test_assign_role_is_idempotent(): void
    {
        $user = User::factory()->create();

        $user->assignRole(UserRole::Reviewer);
        $user->assignRole(UserRole::Reviewer);

        $this->assertTrue($user->hasRole(UserRole::Reviewer));
        $this->assertSame(2, $user->roles()->count());
    }

    public function test_sync_roles_always_includes_user_role(): void
    {
        $user = User::factory()->create();

        $user->syncRoles([UserRole::Admin]);

        $this->assertTrue($user->hasRole(UserRole::User));
        $this->assertTrue($user->hasRole(UserRole::Admin));
    }

    public function test_revoke_role_throws_for_user_role(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $user->revokeRole(UserRole::User);
    }

    public function test_has_any_role(): void
    {
        $user = User::factory()->withRoles([UserRole::Reviewer])->create();

        $this->assertTrue($user->hasAnyRole(UserRole::Reviewer, UserRole::Admin));
        $this->assertFalse($user->hasAnyRole(UserRole::Admin));
    }
}
