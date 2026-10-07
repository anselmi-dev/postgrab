<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('downloader.admin_email');
        $password = config('downloader.admin_password');

        if (! $email || ! $password) {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $user->is_admin = true;
        $user->email_verified_at ??= now();
        $user->save();
    }
}
