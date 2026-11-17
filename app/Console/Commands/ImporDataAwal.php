<?php

namespace App\Console\Commands;

use App\Services\ImporDataAwalService;
use Illuminate\Console\Command;
use RuntimeException;

class ImporDataAwal extends Command
{
    protected $signature = 'impor:data-awal {--folder=database/data-awal : Folder berisi file CSV}';
    protected $description = 'impor data awal CSV: kategori, bahan, menu, dan resep';

    public function handle (ImporDataAwalService $impor): int
    {
        $folder = $this->option('folder');
        if (!preg_match('~^([A-Za-z]:[\\\\/]|/)~', $folder)){
            $folder = base_path($folder);
        }

        try{
            $hasil = $impor->impor($folder);
        }catch (RuntimeException $e){
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Impor selesai. ');
        $this->table(['Data', 'Ditambahkan'],[
            ['Kategori', $hasil['hitung']['kategori']],
            ['Bahan', $hasil['hitung']['bahan']],
            ['Menu', $hasil['hitung']['menu']],
            ['Resep (baris)', $hasil['hitung']['resep']],
        ]);

         if ($hasil['perbaikan']) {
            $this->newLine();
            $this->comment('Diperbaiki otomatis:');
            foreach ($hasil['perbaikan'] as $p) {
                $this->line(' - ' . $p);
            }
        }

        if($hasil['dilewati']){
            $this->newLine();
            $this->warn('Tidak diimpor:');
            foreach ($hasil['dilewati'] as $d) {
                $this->line(' - ' . $d);
            }
        }

        if ($hasil['tanpaResep']){
            $this -> newLine();
            $this->warn('Menu tanpa resep (belum bisa dipesan): ' . implode(', ', $hasil['tanpaResep']));

        }

        return self::SUCCESS;
    }

}