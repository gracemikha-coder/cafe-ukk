@extends('layouts.manajer')

@section('title', ($bahan->exists ? 'Ubah' : 'Tambah') . ' Bahan')

@section('konten')
    <h1 class="judul">{{ $bahan->exists ? 'Ubah' : 'Tambah' }} Bahan</h1>
    <p class="sub">Nama bahan, satuan dasar (g/ml/pcs), stok, batas minimum, harga per satuan</p>

    <form class="kartu" method="POST"
          action="{{ $bahan->exists ? '/manajer/bahan/'.$bahan->id : '/manajer/bahan' }}">
        @csrf
        @if ($bahan->exists)
            @method('PUT')
        @endif

        <label for="nama">Nama bahan</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $bahan->nama) }}">
        @error('nama') <div class="error">{{ $message }}</div> @enderror

        <label for="satuan_dasar">Satuan dasar</label>
        <select id="satuan_dasar" name="satuan_dasar">
            @foreach (['g', 'ml', 'pcs'] as $s)
                <option value="{{ $s }}" @selected(old('satuan_dasar', $bahan->satuan_dasar) === $s)>{{ $s }}</option>
            @endforeach
        </select>
        @error('satuan_dasar') <div class="error">{{ $message }}</div> @enderror

        <label for="stok">Stok saat ini</label>
        <input id="stok" name="stok" type="number" step="0.01" min="0"
               value="{{ old('stok', $bahan->exists ? $bahan->stok + 0 : 0) }}">
        @error('stok') <div class="error">{{ $message }}</div> @enderror

        <label for="batas_minimum">Batas minimum</label>
        <input id="batas_minimum" name="batas_minimum" type="number" step="0.01" min="0"
               value="{{ old('batas_minimum', $bahan->exists ? $bahan->batas_minimum + 0 : 0) }}">
        @error('batas_minimum') <div class="error">{{ $message }}</div> @enderror

        <label for="harga_per_satuan">Harga per satuan (Rp)</label>
        <input id="harga_per_satuan" name="harga_per_satuan" type="number" step="0.01" min="0"
               value="{{ old('harga_per_satuan', $bahan->exists ? $bahan->harga_per_satuan + 0 : '') }}">
        @error('harga_per_satuan') <div class="error">{{ $message }}</div> @enderror

        <div style="margin-top:20px">
            <button class="btn btn-isi" type="submit">Simpan</button>
            <a class="btn btn-garis" href="/manajer/bahan">Batal</a>
        </div>
    </form>
@endsection