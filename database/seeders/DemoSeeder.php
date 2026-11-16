<?php

namespace Database\Seeders;

use App\Models\Bahan;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Sesuai seed_stok_demo.csv: Susu UHT 0,3 liter = 300 ml
        $susu = Bahan::whereRaw('LOWER(nama) = ?', ['susu uht'])->first();
        if ($susu) {
            $susu->update(['stok' => 0.3 * 1000]);
        }
    }
}