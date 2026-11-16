<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\ItemPesanan;
use App\Models\Menu;
use App\Models\PemakaianBahan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PesananService
{
    /**
     * Hitung kebutuhan bahan GABUNGAN semua item (bahan sama dijumlahkan).
     * $items = [ ['menu_id' => 1, 'jumlah' => 2], ... ]
     * Hasil: [bahan_id => total kebutuhan]
     */
    public function hitungKebutuhan(array $items): array
    {
        $kebutuhan = [];
        foreach ($items as $it) {
            $menu = Menu::with('resepItems')->findOrFail($it['menu_id']);
            foreach ($menu->resepItems as $r) {
                $kebutuhan[$r->bahan_id] = ($kebutuhan[$r->bahan_id] ?? 0)
                    + (float) $r->takaran * (int) $it['jumlah'];
            }
        }
        return $kebutuhan;
    }

    /**
     * Cek menu valid (NTF-03, 04, 05, 09) dan stok cukup (NTF-01).
     * Melempar ValidationException berisi pesan notifikasi.
     */
    public function validasi(array $items, bool $kunci = false): void
    {
        if (count($items) === 0) {
            $this->gagal('Pilih minimal satu menu dengan jumlah lebih dari 0.');
        }

        foreach ($items as $it) {
            if (!isset($it['jumlah']) || !ctype_digit((string) $it['jumlah']) || (int) $it['jumlah'] < 1) {
                $this->gagal('Pilih minimal satu menu dengan jumlah lebih dari 0.');
            }

            $menu = Menu::find($it['menu_id']);
            if (!$menu) {
                $this->gagal('Pilih minimal satu menu dengan jumlah lebih dari 0.');
            }
            if (!$menu->aktif) {
                $this->gagal("Menu {$menu->nama} tidak tersedia.");
            }
            if (!$menu->hasRecipe()) {
                $this->gagal("Menu {$menu->nama} belum dapat dipesan.");
            }
            if ($menu->isHabis()) {
                $this->gagal("Menu {$menu->nama} sedang habis.");
            }
        }

        // NTF-01: kebutuhan gabungan vs stok
        $kurang = [];
        foreach ($this->hitungKebutuhan($items) as $bahanId => $butuh) {
            $q = Bahan::where('id', $bahanId);
            if ($kunci) {
                $q->lockForUpdate();
            }
            $bahan = $q->first();
            if ((float) $bahan->stok < $butuh) {
                $selisih = $butuh - (float) $bahan->stok;
                $kurang[] = "Stok tidak memadai: {$bahan->nama} kurang "
                    . ($selisih + 0) . " {$bahan->satuan_dasar}. Pesanan tidak dapat diproses.";
            }
        }
        if ($kurang) {
            throw ValidationException::withMessages(['items' => $kurang]);
        }
    }

    /**
     * Biaya: service 5% x subtotal, pajak 10% x (subtotal + service), dibulatkan ke rupiah.
     */
    public function hitungBiaya(float $subtotal): array
    {
        $service = round($subtotal * 0.05);
        $pajak   = round(($subtotal + $service) * 0.10);
        $total   = $subtotal + $service + $pajak;

        return compact('subtotal', 'service', 'pajak', 'total');
    }

    /**
     * Simpan pesanan dalam satu transaksi.
     */
    public function buat(User $user, array $data): Pesanan
    {
        return DB::transaction(function () use ($user, $data) {
            // cek ulang stok di dalam transaksi (dengan kunci baris)
            $this->validasi($data['items'], true);

            $subtotal = 0;
            $baris = [];
            foreach ($data['items'] as $it) {
                $menu = Menu::findOrFail($it['menu_id']);
                $sub = (float) $menu->harga * (int) $it['jumlah'];
                $subtotal += $sub;
                $baris[] = [$menu, (int) $it['jumlah'], $sub];
            }

            $biaya = $this->hitungBiaya($subtotal);

            $pesanan = Pesanan::create([
                'user_id'      => $user->id,
                'nomor_meja'   => $data['nomor_meja'],
                'metode_bayar' => $data['metode_bayar'],
                'status'       => 'Baru',
                'subtotal'     => $biaya['subtotal'],
                'service'      => $biaya['service'],
                'pajak'        => $biaya['pajak'],
                'total'        => $biaya['total'],
            ]);

            // salin nama & harga saat pesan (butir 7)
            foreach ($baris as [$menu, $jumlah, $sub]) {
                ItemPesanan::create([
                    'pesanan_id'              => $pesanan->id,
                    'menu_id'                 => $menu->id,
                    'nama_menu_saat_pesan'    => $menu->nama,
                    'harga_satuan_saat_pesan' => $menu->harga,
                    'jumlah'                  => $jumlah,
                    'subtotal_item'           => $sub,
                ]);
            }

            // potong stok + catat pemakaian (dipakai saat pembatalan)
            foreach ($this->hitungKebutuhan($data['items']) as $bahanId => $butuh) {
                $bahan = Bahan::lockForUpdate()->find($bahanId);
                $bahan->update(['stok' => (float) $bahan->stok - $butuh]);

                PemakaianBahan::create([
                    'pesanan_id' => $pesanan->id,
                    'bahan_id'   => $bahanId,
                    'jumlah'     => $butuh,
                ]);
            }

            return $pesanan;
        });
    }
        /**
     * Ubah status berurutan: Baru -> Diproses -> Selesai.
     */
    public function ubahStatus(Pesanan $pesanan, string $tujuan): Pesanan
    {
        return DB::transaction(function () use ($pesanan, $tujuan) {
            $p = Pesanan::lockForUpdate()->findOrFail($pesanan->id);

            if (in_array($p->status, ['Selesai', 'Batal'])) {
                $this->gagal("Pesanan sudah {$p->status} dan tidak dapat diubah.");   // NTF-11
            }

            $berikut = ['Baru' => 'Diproses', 'Diproses' => 'Selesai'][$p->status] ?? null;
            if ($tujuan !== $berikut) {
                $this->gagal('Status hanya dapat berubah berurutan: Baru, Diproses, Selesai.');   // NTF-10
            }

            $p->update(['status' => $tujuan]);
            return $p;
        });
    }

    /**
     * Batalkan pesanan (hanya saat Baru), stok dikembalikan dari catatan pemakaian.
     */
    public function batalkan(Pesanan $pesanan): Pesanan
    {
        return DB::transaction(function () use ($pesanan) {
            $p = Pesanan::lockForUpdate()->findOrFail($pesanan->id);

            if ($p->status === 'Batal') {
                $this->gagal('Pesanan sudah Batal dan tidak dapat diubah.');   // NTF-11
            }
            if ($p->status !== 'Baru') {
                $this->gagal('Pesanan hanya dapat dibatalkan saat berstatus Baru.');   // NTF-12
            }

            foreach ($p->pemakaianBahans as $pakai) {
                $bahan = Bahan::lockForUpdate()->find($pakai->bahan_id);
                $bahan->update(['stok' => (float) $bahan->stok + (float) $pakai->jumlah]);
            }

            $p->update(['status' => 'Batal']);
            return $p;
        });
    }

    private function gagal(string $pesan): void
    {
        throw ValidationException::withMessages(['items' => [$pesan]]);
    }
}