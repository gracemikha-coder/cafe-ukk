<?php

namespace Database\Seeders;

use App\Models\Bahan;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 0,3 liter = 300 ml (satuan dasar)
        Bahan::updateOrCreate(['nama' => 'Susu UHT'], [
            'satuan_dasar' => 'ml',
            'stok' => 0.3 * 1000,
            'batas_minimum' => 1000,
            'harga_per_satuan' => 16,
            'harga_diperbarui_pada' => now()->toDateString(),
        ]);
    }
}