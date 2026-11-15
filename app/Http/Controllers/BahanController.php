<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use Illuminate\Http\Request;

class BahanController extends Controller
{
    public function index()
    {
        $bahans = Bahan::orderBy('nama')->get();
        return view('manajer.bahan.index', compact('bahans'));
    }

    public function create()
    {
        return view('manajer.bahan.form', ['bahan' => new Bahan()]);
    }

    public function store(Request $request)
    {
        Bahan::create($this->validasi($request));
        return redirect('/manajer/bahan')->with('sukses', 'Bahan berhasil ditambahkan');
    }
    public function edit(Bahan $bahan)
    {
        return view('manajer.bahan.form', compact('bahan'));
    }

    public function update(Request $request, Bahan $bahan)
    {
        $data = $this->validasi($request);

        //catat tanggal kalau harga berubah
        if ((float)$data['harga_per_satuan'] !== (float) $bahan->harga_per_satuan){
            $data['harga_diperbarui_pada'] = now()->toDateString();
        }

        $bahan->update($data);
        return redirect('/manajer/bahan')->with('success', 'Bahan berhasil diperbarui');
    }
    public function destroy(Bahan $bahan)
    {
        $bahan->delete();
        return redirect('/manajer/bahan')->with('success', 'Bahan berhasil dihapus');
    }

    private function validasi(Request $request)
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'satuan_dasar' => 'required|string|max:50',
            'stok' => 'required|numeric|min:0',
            'batas_minimum' => 'required|numeric|min:0',
         'harga_per_satuan' => 'required|numeric|min:0',
    ]);


    }
}
