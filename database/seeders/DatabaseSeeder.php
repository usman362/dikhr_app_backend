<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@communitydhikr.test'],
            [
                'name' => 'Ahmad Ibrahim',
                'password' => 'password',
                'is_admin' => true,
            ]
        );
    }
}
