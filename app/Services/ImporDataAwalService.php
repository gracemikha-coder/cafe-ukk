<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\ResepItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImporDataAwalService
{
    private array $perbaikan = [];
    private array $dilewati  = [];
    private array $hitung    = ['kategori' => 0, 'bahan' => 0, 'menu' => 0, 'resep' => 0];

    public function impor(string $folder): array
    {
        $kategoriRows = $this->baca($folder . '/master_kategori_menu.csv');
        $bahanRows    = $this->baca($folder . '/bahan.csv');
        $menuRows     = $this->baca($folder . '/menu.csv');
        $resepRows    = $this->baca($folder . '/resep.csv');

        DB::transaction(function () use ($kategoriRows, $bahanRows, $menuRows, $resepRows) {
            $this->imporKategori($kategoriRows);
            $this->imporBahan($bahanRows);
            $menuMap = $this->imporMenu($menuRows);
            $this->imporResep($resepRows, $menuMap);
        });

        $tanpaResep = Menu::whereDoesntHave('resepItems')->orderBy('nama')->pluck('nama')->all();

        return [
            'hitung'     => $this->hitung,
            'perbaikan'  => $this->perbaikan,
            'dilewati'   => $this->dilewati,
            'tanpaResep' => $tanpaResep,
        ];
    }

    public static function normalNama(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
    }

    private function rapikan(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    // Hasil: [satuan dasar, faktor pengali] atau null bila tidak dikenal. 
    public static function normalSatuan(string $s): ?array
    {
        return match (mb_strtolower(trim($s))) {
            'kg', 'kilogram'              => ['g', 1000],
            'g', 'gr', 'gram'             => ['g', 1],
            'liter', 'l', 'lt', 'litre'   => ['ml', 1000],
            'ml', 'mililiter'             => ['ml', 1],
            'pcs', 'pc', 'buah'           => ['pcs', 1],
            default                       => null,
        };
    }

    private function angka(?string $s): ?float
    {
        $s = str_replace(',', '.', trim((string) $s));
        return is_numeric($s) ? (float) $s : null;
    }

    // Baca CSV menjadi [nomor baris => [kolom => nilai]]. 
    private function baca(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $h = fopen($path, 'r');
        $kepala = fgetcsv($h, 0, ',', '"', '');
        if (!$kepala) {
            throw new RuntimeException("File kosong: {$path}");
        }
        $kepala = array_map(fn ($k) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $k))), $kepala);

        $hasil = [];
        $no = 1;
        while (($baris = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $no++;
            if (count($baris) === 1 && trim((string) $baris[0]) === '') {
                continue; // baris kosong
            }
            $baris = array_pad($baris, count($kepala), '');
            $hasil[$no] = array_combine($kepala, array_slice($baris, 0, count($kepala)));
        }
        fclose($h);

        return $hasil;
    }

    private function lewati(string $berkas, int $no, string $alasan): void
    {
        $this->dilewati[] = "{$berkas} baris {$no}: {$alasan}";
    }

    private function imporKategori(array $rows): void
    {
        foreach ($rows as $no => $r) {
            $nama = $this->rapikan($r['nama_kategori'] ?? '');
            if ($nama === '') {
                $this->lewati('master_kategori_menu.csv', $no, 'nama kategori kosong');
                continue;
            }
            $ada = Kategori::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->exists();
            if (!$ada) {
                Kategori::create(['nama' => $nama]);
                $this->hitung['kategori']++;
            }
        }
    }

    private function imporBahan(array $rows): void
    {
        foreach ($rows as $no => $r) {
            $nama = $this->rapikan($r['nama_bahan'] ?? '');
            if ($nama === '') {
                $this->lewati('bahan.csv', $no, 'nama bahan kosong');
                continue;
            }

            $sat = self::normalSatuan($r['satuan'] ?? '');
            if (!$sat) {
                $this->lewati('bahan.csv', $no, "{$nama}: satuan '{$r['satuan']}' tidak dikenal");
                continue;
            }

            $stok  = $this->angka($r['stok_awal'] ?? '');
            $harga = $this->angka($r['harga_per_satuan'] ?? '');
            if ($stok === null || $stok < 0 || $harga === null || $harga < 0) {
                $this->lewati('bahan.csv', $no, "{$nama}: stok atau harga tidak valid");
                continue;
            }

            [$dasar, $faktor] = $sat;
            if ($faktor !== 1) {
                $this->perbaikan[] = "bahan.csv baris {$no}: {$nama} dari " . trim($r['satuan'])
                    . " ke {$dasar} (stok x{$faktor}, harga per {$dasar} dibagi {$faktor})";
            }

            $ada = Bahan::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->exists();
            if ($ada) {
                $this->lewati('bahan.csv', $no, "{$nama}: sudah ada, tidak ditimpa");
                continue;
            }

            $kode = trim($r['kode'] ?? '') ?: null;
            if ($kode && Bahan::where('kode', $kode)->exists()) {
                $kode = null;
            }

            Bahan::create([
                'kode'                  => $kode,
                'nama'                  => $nama,
                'satuan_dasar'          => $dasar,
                'stok'                  => round($stok * $faktor, 2),
                'batas_minimum'         => 0,
                'harga_per_satuan'      => round($harga / $faktor, 2),
                'harga_diperbarui_pada' => now()->toDateString(),
            ]);
            $this->hitung['bahan']++;
        }
    }

    // Hasil: [kode menu => Menu] 
    private function imporMenu(array $rows): array
    {
        $kategoriMap = Kategori::all()->keyBy(fn ($k) => self::normalNama($k->nama));
        $menuMap = [];

        foreach ($rows as $no => $r) {
            $kode  = trim($r['kode'] ?? '');
            $nama  = $this->rapikan($r['nama_menu'] ?? '');
            $harga = $this->angka($r['harga_jual'] ?? '');
            $kat   = $kategoriMap->get(self::normalNama($r['kategori'] ?? ''));

            if ($kode === '' || $nama === '') {
                $this->lewati('menu.csv', $no, 'kode atau nama menu kosong');
                continue;
            }
            if (!$kat) {
                $this->lewati('menu.csv', $no, "{$nama}: kategori '{$r['kategori']}' tidak dikenal");
                continue;
            }
            if ($harga === null || $harga < 0) {
                $this->lewati('menu.csv', $no, "{$nama}: harga jual tidak valid");
                continue;
            }

            $menu = Menu::where('kode', $kode)->first();
            if ($menu) {
                $this->lewati('menu.csv', $no, "{$nama}: kode {$kode} sudah ada, tidak ditimpa");
            } else {
                $menu = Menu::create([
                    'kode'        => $kode,
                    'nama'        => $nama,
                    'kategori_id' => $kat->id,
                    'harga'       => $harga,
                    'aktif'       => true,
                ]);
                $this->hitung['menu']++;
            }
            $menuMap[$kode] = $menu;
        }

        return $menuMap;
    }

    private function imporResep(array $rows, array $menuMap): void
    {
        $bahanMap = Bahan::all()->keyBy(fn ($b) => self::normalNama($b->nama));

        foreach ($rows as $no => $r) {
            $kode = trim($r['kode_menu'] ?? '');
            $menu = $menuMap[$kode] ?? Menu::where('kode', $kode)->first();
            if (!$menu) {
                $this->lewati('resep.csv', $no, "menu {$kode} tidak ditemukan");
                continue;
            }

            $namaAsli = $this->rapikan($r['nama_bahan'] ?? '');
            [$bahan, $dikoreksi] = $this->cocokkanBahan($namaAsli, $bahanMap);
            if (!$bahan) {
                $this->lewati('resep.csv', $no, "{$kode}: bahan '{$namaAsli}' tidak dikenal, tidak diimpor");
                continue;
            }
            if ($dikoreksi) {
                $this->perbaikan[] = "resep.csv baris {$no}: '{$namaAsli}' dicocokkan ke '{$bahan->nama}'";
            }

            $sat     = self::normalSatuan($r['satuan'] ?? '');
            $takaran = $this->angka($r['takaran'] ?? '');
            if (!$sat || $takaran === null || $takaran <= 0) {
                $this->lewati('resep.csv', $no, "{$kode} {$namaAsli}: takaran atau satuan tidak valid");
                continue;
            }

            [$dasar, $faktor] = $sat;
            if ($dasar !== $bahan->satuan_dasar) {
                $this->lewati('resep.csv', $no, "{$kode} {$bahan->nama}: satuan resep ({$r['satuan']}) tidak cocok dengan satuan bahan ({$bahan->satuan_dasar})");
                continue;
            }

            $sudah = ResepItem::where('menu_id', $menu->id)->where('bahan_id', $bahan->id)->exists();
            if ($sudah) {
                $this->lewati('resep.csv', $no, "{$kode} {$bahan->nama}: sudah ada di resep");
                continue;
            }

            ResepItem::create([
                'menu_id'  => $menu->id,
                'bahan_id' => $bahan->id,
                'takaran'  => round($takaran * $faktor, 2),
            ]);
            $this->hitung['resep']++;
        }
    }

    /** Hasil: [Bahan|null, dikoreksi?]. Cocok persis dulu, lalu yang paling mirip. */
    private function cocokkanBahan(string $nama, $bahanMap): array
    {
        $kunci = self::normalNama($nama);
        if ($kunci === '') {
            return [null, false];
        }
        if ($bahanMap->has($kunci)) {
            return [$bahanMap->get($kunci), false];
        }

        $terbaik = null;
        $jarak   = PHP_INT_MAX;
        $seri    = false;
        foreach ($bahanMap as $k => $b) {
            $d = levenshtein($kunci, (string) $k);
            if ($d < $jarak) {
                $jarak = $d;
                $terbaik = $b;
                $seri = false;
            } elseif ($d === $jarak) {
                $seri = true;
            }
        }

        $batas = mb_strlen($kunci) >= 15 ? 2 : 1;
        if ($terbaik && $jarak <= $batas && !$seri) {
            return [$terbaik, true];
        }

        return [null, false];
    }
}