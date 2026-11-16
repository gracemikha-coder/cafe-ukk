<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\StokMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokMasukController extends Controller
{
    public function index()
    {
        $riwayat = StokMasuk::with(['bahan', 'user'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();
        $bahans = Bahan::orderBy('nama')->get();

        return view('manajer.stok.index', compact('riwayat', 'bahans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bahan_id' => 'required|exists:bahans,id',
            'tanggal'  => 'required|date',
            'jumlah'   => 'required|numeric|gt:0',
        ]);

        // catat + tambah stok dalam satu transaksi
        DB::transaction(function () use ($data, $request) {
            $bahan = Bahan::lockForUpdate()->findOrFail($data['bahan_id']);

            StokMasuk::create([
                'bahan_id' => $bahan->id,
                'user_id'  => $request->user()->id,
                'tanggal'  => $data['tanggal'],
                'jumlah'   => $data['jumlah'],
            ]);

            $bahan->update(['stok' => (float) $bahan->stok + (float) $data['jumlah']]);
        });

        return redirect('/manajer/stok-masuk')->with('sukses', 'Stok masuk dicatat, stok bahan bertambah');
    }
}