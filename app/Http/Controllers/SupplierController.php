<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use Illuminate\Support\Carbon;

class SupplierController extends Controller
{
    public function perbarui(SupplierService $supplier)
    {
        $hasil = $supplier->perbaruiHarga();

        if ($hasil['berhasil']) {
            $pesan = $hasil['jumlah'] . ' harga bahan diperbarui dari Supplier (harga per '
                . Carbon::parse($hasil['tanggal'])->format('d/m/Y') . ').';
            if ($hasil['dilewati'] > 0) {
                $pesan .= ' ' . $hasil['dilewati'] . ' item dilewati karena tidak cocok dengan data bahan.';
            }
            return back()->with('sukses', $pesan);
        }

        $terakhir = $hasil['terakhir']
            ? ' (diperbarui ' . Carbon::parse($hasil['terakhir'])->format('d/m/Y') . ')'
            : '';

        return back()->with(
            'peringatan',
            $hasil['pesan'] . ' Sistem tetap berjalan memakai harga terakhir' . $terakhir . '.'
        );
    }
}