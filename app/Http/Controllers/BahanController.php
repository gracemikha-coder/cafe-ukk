<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $data = $this->validasi($request, $bahan);

        // catat tanggal kalau harga berubah
        if ((float) $data['harga_per_satuan'] !== (float) $bahan->harga_per_satuan) {
            $data['harga_diperbarui_pada'] = now()->toDateString();
        }

        $bahan->update($data);
        return redirect('/manajer/bahan')->with('sukses', 'Bahan berhasil diperbarui');
    }

    private function validasi(Request $request, ?Bahan $bahan = null): array
    {
        // spasi di awal/akhir nama dibuang
        $request->merge(['nama' => trim((string) $request->nama)]);

        return $request->validate([
            'nama' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($bahan) {
                    $sudahAda = Bahan::whereRaw('LOWER(nama) = ?', [mb_strtolower($value)])
                        ->when($bahan, fn ($q) => $q->where('id', '!=', $bahan->id))
                        ->exists();
                    if ($sudahAda) {
                        $fail('Nama bahan sudah ada.');
                    }
                },
            ],
            'satuan_dasar'     => ['required', Rule::in(['g', 'ml', 'pcs'])],
            'stok'             => 'required|numeric|min:0',
            'batas_minimum'    => 'required|numeric|min:0',
            'harga_per_satuan' => 'required|numeric|min:0',
        ]);
    }
}