<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

class ReviewerPool
{
    public static function all(): Collection
    {
        return User::query()
            ->where('role', UserRole::Reviewer)
            ->orderBy('name')
            ->orderBy('username')
            ->get();
    }
}
