<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Super Admin',
                'email'    => 'admin@bookcabin.id',
                'password' => Hash::make('password123'),
                'role'     => 'super_admin',
            ],
            [
                'name'     => 'Manajer Bandara',
                'email'    => 'manajer@bookcabin.id',
                'password' => Hash::make('password123'),
                'role'     => 'manajer',
            ],
            [
                'name'     => 'Kasir Terminal 1',
                'email'    => 'kasir1@bookcabin.id',
                'password' => Hash::make('password123'),
                'role'     => 'kasir',
            ],
            [
                'name'     => 'Kasir Terminal 2',
                'email'    => 'kasir2@bookcabin.id',
                'password' => Hash::make('password123'),
                'role'     => 'kasir',
            ],
            [
                'name'     => 'Resepsionis Kapsul',
                'email'    => 'resepsionis@bookcabin.id',
                'password' => Hash::make('password123'),
                'role'     => 'resepsionis',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
