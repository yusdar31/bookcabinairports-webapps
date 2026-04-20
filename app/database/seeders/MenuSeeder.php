<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'Warung Kopi Losari' => [
                ['name' => 'Kopi Susu Toraja',   'price' => 25000, 'category' => 'minuman', 'description' => 'Kopi arabika Toraja dengan susu segar'],
                ['name' => 'Es Kopi Aren',       'price' => 28000, 'category' => 'minuman', 'description' => 'Kopi dingin dengan gula aren asli'],
                ['name' => 'Roti Bakar Coklat',  'price' => 18000, 'category' => 'snack',   'description' => 'Roti bakar dengan coklat leleh'],
                ['name' => 'Pisang Goreng Keju', 'price' => 15000, 'category' => 'snack',   'description' => 'Pisang goreng tabur keju mozarella'],
            ],
            'Coto Makassar Daeng' => [
                ['name' => 'Coto Makassar',      'price' => 35000, 'category' => 'makanan', 'description' => 'Coto khas Makassar dengan ketupat'],
                ['name' => 'Sop Konro',          'price' => 45000, 'category' => 'makanan', 'description' => 'Sop iga sapi bumbu rempah Makassar'],
                ['name' => 'Pallubasa',          'price' => 38000, 'category' => 'makanan', 'description' => 'Pallubasa daging sapi khas Sulawesi'],
                ['name' => 'Es Jeruk Nipis',     'price' => 10000, 'category' => 'minuman', 'description' => 'Es jeruk nipis segar'],
                ['name' => 'Teh Manis',          'price' => 8000,  'category' => 'minuman', 'description' => 'Teh manis hangat / dingin'],
            ],
            'Pisang Epe Premium' => [
                ['name' => 'Pisang Epe Original',  'price' => 15000, 'category' => 'makanan', 'description' => 'Pisang epe bakar gula merah'],
                ['name' => 'Pisang Epe Coklat',    'price' => 18000, 'category' => 'makanan', 'description' => 'Pisang epe bakar saus coklat'],
                ['name' => 'Pisang Epe Keju Susu', 'price' => 20000, 'category' => 'makanan', 'description' => 'Pisang epe bakar keju dan susu'],
                ['name' => 'Es Kelapa Muda',       'price' => 15000, 'category' => 'minuman', 'description' => 'Kelapa muda segar asli Sulawesi'],
            ],
            'Resto Pantai Losari' => [
                ['name' => 'Ikan Bakar Parape',    'price' => 65000, 'category' => 'makanan', 'description' => 'Ikan bakar bumbu parape khas Makassar'],
                ['name' => 'Konro Bakar',          'price' => 75000, 'category' => 'makanan', 'description' => 'Iga sapi bakar bumbu konro premium'],
                ['name' => 'Nasi Goreng Seafood',  'price' => 45000, 'category' => 'makanan', 'description' => 'Nasi goreng dengan udang, cumi, dan kerang'],
                ['name' => 'Jus Markisa',          'price' => 20000, 'category' => 'minuman', 'description' => 'Jus markisa asli Sulawesi'],
                ['name' => 'Es Pallu Butung',      'price' => 18000, 'category' => 'minuman', 'description' => 'Es pisang ijo khas Makassar'],
            ],
        ];

        foreach ($menus as $outletName => $items) {
            $outlet = Outlet::where('name', $outletName)->first();
            if (!$outlet) continue;

            foreach ($items as $item) {
                Menu::updateOrCreate(
                    ['outlet_id' => $outlet->id, 'name' => $item['name']],
                    $item
                );
            }
        }
    }
}
