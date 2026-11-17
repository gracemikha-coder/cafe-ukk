<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\ItemPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'dari'   => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
        ], [
            'dari.date'                => 'Tanggal awal tidak valid.',
            'sampai.date'              => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal'    => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $dari   = $request->query('dari');
        $sampai = $request->query('sampai');

        // penjualan per menu: pesanan Batal tidak dihitung
        $agregat = ItemPesanan::query()
            ->join('pesanans', 'pesanans.id', '=', 'item_pesanans.pesanan_id')
            ->where('pesanans.status', '!=', 'Batal')
            ->when($dari, fn ($q) => $q->where('pesanans.created_at', '>=', Carbon::parse($dari)->startOfDay()))
            ->when($sampai, fn ($q) => $q->where('pesanans.created_at', '<=', Carbon::parse($sampai)->endOfDay()))
            ->selectRaw('item_pesanans.menu_id, SUM(item_pesanans.jumlah) AS terjual, SUM(item_pesanans.subtotal_item) AS pendapatan')
            ->groupBy('item_pesanans.menu_id')
            ->get()
            ->keyBy('menu_id');

        $baris = Menu::with('kategori')->orderBy('nama')->get()->map(function ($m) use ($agregat) {
            $a          = $agregat->get($m->id);
            $terjual    = (int) ($a->terjual ?? 0);
            $pendapatan = (float) ($a->pendapatan ?? 0);
            $harga      = (float) $m->harga;
            $hpp        = $m->hitungHPP();

            return (object) [
                'nama'       => $m->nama,
                'kategori'   => $m->kategori->nama ?? '-',
                'harga'      => $harga,
                'hpp'        => $hpp,
                'margin'     => $harga - $hpp,
                'persen'     => $harga > 0 ? ($harga - $hpp) / $harga * 100 : 0,
                'terjual'    => $terjual,
                'pendapatan' => $pendapatan,
                'keuntungan' => $pendapatan - ($hpp * $terjual),
            ];
        })->sortByDesc('terjual')->values();

        $jumlahPesanan = Pesanan::where('status', '!=', 'Batal')
            ->when($dari, fn ($q) => $q->where('created_at', '>=', Carbon::parse($dari)->startOfDay()))
            ->when($sampai, fn ($q) => $q->where('created_at', '<=', Carbon::parse($sampai)->endOfDay()))
            ->count();

        $ringkasan = [
            'pesanan'    => $jumlahPesanan,
            'terjual'    => $baris->sum('terjual'),
            'pendapatan' => $baris->sum('pendapatan'),
            'keuntungan' => $baris->sum('keuntungan'),
        ];

        // bahan menipis: stok sama dengan atau di bawah batas minimum
        $menipis = Bahan::whereColumn('stok', '<=', 'batas_minimum')->orderBy('nama')->get();

       return view('manajer.laporan.index', compact('baris', 'ringkasan', 'menipis', 'dari', 'sampai'));
    }
}