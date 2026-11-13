<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'nama' => 'Kasir',
                'password' => 'kasir12345',
                'role' => 'kasir',
            ]
        );

        User::updateOrCreate(
            ['username' => 'manajer'],
            [
                'nama' => 'Manajer',
                'password' => 'manajer12345',
                'role' => 'manajer',
            ]
        );
    }
}