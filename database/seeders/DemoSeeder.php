<?php

namespace Database\Seeders;

use App\Models\Bahan;
use App\Services\ImporDataAwalService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data-awal/seed_stok_demo.csv');
        if(!is_file($path)){
            $this->command?->error("File  tidak ditemukan: {$path}");
            return;
        }

        $h = fopen($path, 'r');
        fgetcsv($h, 0, ',', '"', ''); //lewati judul kolom

        while (($r = fgetcsv($h, 0, ',', '"','')) !== false ){
            if (count($r) < 3){
                continue;
            }
            [$nama, $jumlah, $satuan] = array_map('trim', $r);
            $sat = ImporDataAwalService::normalSatuan($satuan);
            $bahan = Bahan::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();

            if ($bahan && $sat && is_numeric(str_replace(',', '.', $jumlah))){
                $bahan->update([
                    'stok' => round((float) str_replace(',', '.', $jumlah) * $sat[1], 2),
                ]);
            }
        }
        fclose($h);
    }
}