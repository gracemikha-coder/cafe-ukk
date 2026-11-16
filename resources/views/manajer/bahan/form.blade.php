@extends('layouts.manajer')

@section('title', ($bahan->exists ? 'Ubah' : 'Tambah') . ' Bahan')

@section('konten')
    <h1 class="judul">{{ $bahan->exists ? 'Ubah' : 'Tambah' }} Bahan</h1>
    <p class="sub">Nama bahan, satuan dasar (g/ml/pcs), harga per satuan, batas minimum</p>

    <form class="kartu" method="POST"
          action="{{ $bahan->exists ? '/manajer/bahan/'.$bahan->id : '/manajer/bahan' }}">
        @csrf
        @if ($bahan->exists) @method('PUT') @endif

        @foreach ([
            'nama' => 'Nama bahan',
            'satuan_dasar' => 'Satuan dasar (g, ml, pcs)',
            'stok' => 'Stok saat ini',
            'batas_minimum' => 'Batas minimum',
            'harga_per_satuan' => 'Harga per satuan (Rp)',
        ] as $field => $label)
            <label for="{{ $field }}">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}"
                   type="{{ in_array($field, ['nama', 'satuan_dasar']) ? 'text' : 'number' }}"
                   step="0.01" value="{{ old($field, $bahan->$field) }}">
            @error($field) <div class="error">{{ $message }}</div> @enderror
        @endforeach

        <div style="margin-top:20px">
            <button class="btn btn-isi" type="submit">Simpan</button>
            <a class="btn btn-garis" href="/manajer/bahan">Batal</a>
        </div>
    </form>
@endsection