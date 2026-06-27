<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])->get('/_test/admin', fn () => response('admin'));
        Route::middleware(['web', 'auth', 'role:reviewer'])->get('/_test/reviewer', fn () => response('reviewer'));
    }

    public function test_admin_route_allows_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAsUser($admin)
            ->get('/_test/admin')
            ->assertOk()
            ->assertSee('admin');
    }

    public function test_admin_route_blocks_standard_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAsUser($user)
            ->get('/_test/admin')
            ->assertForbidden();
    }

    public function test_reviewer_route_allows_reviewer(): void
    {
        $reviewer = User::factory()->create(['role' => UserRole::Reviewer]);

        $this->actingAsUser($reviewer)
            ->get('/_test/reviewer')
            ->assertOk()
            ->assertSee('reviewer');
    }

    public function test_reviewer_route_allows_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAsUser($admin)
            ->get('/_test/reviewer')
            ->assertOk()
            ->assertSee('reviewer');
    }

    public function test_reviewer_route_blocks_standard_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAsUser($user)
            ->get('/_test/reviewer')
            ->assertForbidden();
    }

    public function test_role_route_blocks_guest(): void
    {
        $this->get('/_test/admin')->assertRedirect(route('login'));
    }
}
