<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Menu;

class HppController extends Controller
{
    public function index()
    {
        $menus = Menu::with(['kategori', 'resepItems.bahan'])->orderBy('nama')->get();

        $baris = $menus->map(function ($m) {
            $rincian = $m->resepItems->map(fn ($r) => [
                'bahan'   => $r->bahan->nama,
                'satuan'  => $r->bahan->satuan_dasar,
                'takaran' => (float) $r->takaran,
                'harga'   => (float) $r->bahan->harga_per_satuan,
                'biaya'   => (float) $r->takaran * (float) $r->bahan->harga_per_satuan,
            ])->values();

            $punyaResep = $rincian->isNotEmpty();
            $hpp        = $rincian->sum('biaya');
            $harga      = (float) $m->harga;

            return (object) [
                'nama'       => $m->nama,
                'kategori'   => $m->kategori->nama ?? '-',
                'aktif'      => (bool) $m->aktif,
                'punyaResep' => $punyaResep,
                'harga'      => $harga,
                'hpp'        => $hpp,
                'margin'     => $harga - $hpp,
                'persen'     => $harga > 0 ? ($harga - $hpp) / $harga * 100 : 0,
                'rincian'    => $rincian,
            ];
        });

        $tanggalHarga = Bahan::max('harga_diperbarui_pada');

        return view('manajer.hpp', compact('baris', 'tanggalHarga'));
    }
}