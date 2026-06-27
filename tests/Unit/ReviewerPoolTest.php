<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ReviewerPool;
use Tests\TestCase;

class ReviewerPoolTest extends TestCase
{
    public function test_returns_only_reviewer_role_users(): void
    {
        $reviewer = User::factory()->create([
            'username' => 'reviewer1',
            'name' => 'Alice Reviewer',
            'role' => UserRole::Reviewer,
        ]);
        User::factory()->create([
            'username' => 'admin1',
            'role' => UserRole::Admin,
        ]);
        User::factory()->create([
            'username' => 'user1',
            'role' => UserRole::User,
        ]);

        $pool = ReviewerPool::all();

        $this->assertCount(1, $pool);
        $this->assertTrue($pool->first()->is($reviewer));
    }
}
