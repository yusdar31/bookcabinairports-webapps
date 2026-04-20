<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Jalankan: php artisan db:seed
     * Atau via Docker: docker run --rm -v "$(pwd):/app" -w /app php:8.3-cli php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            OutletSeeder::class,
            RoomSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
