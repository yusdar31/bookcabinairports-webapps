<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // 10 kamar Standard (C-101 s/d C-110)
        for ($i = 1; $i <= 10; $i++) {
            Room::updateOrCreate(
                ['room_number' => sprintf('C-%03d', $i)],
                [
                    'type'            => 'standard',
                    'floor'           => '1',
                    'price_per_hour'  => 35000,
                    'price_per_night' => 150000,
                    'status'          => 'available',
                    'amenities'       => json_encode(['wifi', 'ac', 'locker', 'reading_light']),
                ]
            );
        }

        // 5 kamar VIP (V-201 s/d V-205)
        for ($i = 1; $i <= 5; $i++) {
            Room::updateOrCreate(
                ['room_number' => sprintf('V-%03d', 200 + $i)],
                [
                    'type'            => 'vip',
                    'floor'           => '2',
                    'price_per_hour'  => 75000,
                    'price_per_night' => 300000,
                    'status'          => 'available',
                    'amenities'       => json_encode(['wifi', 'ac', 'locker', 'reading_light', 'tv', 'power_outlet', 'usb_charger']),
                ]
            );
        }
    }
}
