<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        $outlets = [
            [
                'name'       => 'Warung Kopi Losari',
                'location'   => 'Terminal 1, Lantai 2 - Gate A',
                'type'       => 'cafe',
                'phone'      => '0411-1234567',
                'open_time'  => '05:00',
                'close_time' => '23:00',
            ],
            [
                'name'       => 'Coto Makassar Daeng',
                'location'   => 'Terminal 1, Lantai 1 - Food Court',
                'type'       => 'food_court',
                'phone'      => '0411-2345678',
                'open_time'  => '06:00',
                'close_time' => '22:00',
            ],
            [
                'name'       => 'Pisang Epe Premium',
                'location'   => 'Terminal 2, Lantai 2 - Gate B',
                'type'       => 'kiosk',
                'phone'      => '0411-3456789',
                'open_time'  => '07:00',
                'close_time' => '21:00',
            ],
            [
                'name'       => 'Resto Pantai Losari',
                'location'   => 'Terminal 2, Lantai 3 - VIP Lounge',
                'type'       => 'restaurant',
                'phone'      => '0411-4567890',
                'open_time'  => '10:00',
                'close_time' => '22:00',
            ],
        ];

        foreach ($outlets as $outlet) {
            Outlet::updateOrCreate(['name' => $outlet['name']], $outlet);
        }
    }
}
