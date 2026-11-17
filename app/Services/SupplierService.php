<?php

namespace App\Services;

use App\Models\Bahan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Integrasi layanan Supplier (GET {LAYANAN_URL}/harga-bahan, header X-API-Key).
 *
 * Alamat dan API key dibaca dari .env (lewat config/services.php).
 * Bila layanan mati, timeout, API key ditolak (401), atau respons tidak dikenali,
 * TIDAK ada yang dilempar: harga di database dibiarkan (harga terakhir) dan
 * hasil berisi berhasil=false beserta tanggal harga terakhir diperbarui.
 */
class SupplierService
{
    public function perbaruiHarga(): array
    {
        $url = rtrim((string) config('services.supplier.url'), '/');
        $key = (string) config('services.supplier.key');

        if ($url === '' || $key === '') {
            return $this->gagal('Konfigurasi layanan Supplier belum diisi.');
        }

        try {
            $respons = Http::withHeaders(['X-API-Key' => $key])
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout((int) config('services.supplier.timeout', 5))
                ->get($url . '/harga-bahan');
        } catch (Throwable $e) {
            Log::warning('Supplier tidak dapat dihubungi: ' . $e->getMessage());
            return $this->gagal('Layanan Supplier tidak dapat dihubungi.');
        }

        if ($respons->status() === 401) {
            return $this->gagal('API key Supplier tidak valid.');
        }
        if (!$respons->successful()) {
            return $this->gagal('Layanan Supplier sedang bermasalah (status ' . $respons->status() . ').');
        }

        $json = $respons->json();
        if (!is_array($json) || !isset($json['data']) || !is_array($json['data'])) {
            return $this->gagal('Respons layanan Supplier tidak dikenali.');
        }

        try {
            $tanggal = Carbon::parse($json['diperbarui'] ?? now())->toDateString();
        } catch (Throwable $e) {
            $tanggal = now()->toDateString();
        }

        $bahans  = Bahan::all();
        $perKode = $bahans->filter(fn ($b) => $b->kode)->keyBy('kode');
        $perNama = $bahans->keyBy(fn ($b) => ImporDataAwalService::normalNama($b->nama));

        $diperbarui = 0;
        $dilewati   = 0;

        DB::transaction(function () use ($json, $perKode, $perNama, $tanggal, &$diperbarui, &$dilewati) {
            foreach ($json['data'] as $row) {
                if (!is_array($row)) {
                    $dilewati++;
                    continue;
                }

                $kode  = trim((string) ($row['kode'] ?? ''));
                $nama  = (string) ($row['nama'] ?? '');
                $bahan = $perKode->get($kode) ?? $perNama->get(ImporDataAwalService::normalNama($nama));
                $harga = $row['harga_per_satuan'] ?? null;
                $sat   = mb_strtolower(trim((string) ($row['satuan_dasar'] ?? '')));

                // bahan tidak dikenal, harga tidak valid, atau satuan beda: lewati saja
                if (!$bahan || !is_numeric($harga) || (float) $harga < 0 || ($sat !== '' && $sat !== $bahan->satuan_dasar)) {
                    $dilewati++;
                    continue;
                }

                $bahan->update([
                    'harga_per_satuan'      => round((float) $harga, 2),
                    'harga_diperbarui_pada' => $tanggal,
                ]);
                $diperbarui++;
            }
        });

        return [
            'berhasil'   => true,
            'jumlah'     => $diperbarui,
            'dilewati'   => $dilewati,
            'tanggal'    => $tanggal,
        ];
    }

    private function gagal(string $pesan): array
    {
        return [
            'berhasil' => false,
            'pesan'    => $pesan,
            'terakhir' => Bahan::max('harga_diperbarui_pada'),
        ];
    }
}