<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'fs:user:list';

    protected $description = 'Listing of existing users';

    public function handle()
    {
        $users = User::query()
            ->orderBy('username')
            ->get(['username', 'email', 'role', 'last_login_at', 'created_at', 'updated_at']);

        $this->table([
            'username',
            'email',
            'role',
            'last_login_at',
            'created_at',
            'updated_at',
        ], $users->map(fn (User $user) => [
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]));

        return self::SUCCESS;
    }
}
