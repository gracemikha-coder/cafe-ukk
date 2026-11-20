@extends('layouts.manajer')

@section('title', ($menu->exists ? 'Ubah' : 'Tambah') . ' Menu')

@section('konten')
    <h1 class="judul">{{ $menu->exists ? 'Ubah' : 'Tambah' }} Menu</h1>
    <p class="sub">
        Isi data menu. Resep (bahan dan takaran per 1 porsi) dikelola di halaman Resep.
        @if ($menu->exists)
            &nbsp;|&nbsp; <a class="link-aksi" href="/manajer/resep/{{ $menu->id }}">Kelola resep menu ini</a>
        @endif
    </p>

    <form class="kartu" method="POST" style="max-width:720px"
          action="{{ $menu->exists ? '/manajer/menu/'.$menu->id : '/manajer/menu' }}">
        @csrf
        @if ($menu->exists)
            @method('PUT')
        @endif

        <label for="nama">Nama menu</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $menu->nama) }}">
        @error('nama') <div class="error">{{ $message }}</div> @enderror

        <label for="kategori_id">Kategori</label>
        <select id="kategori_id" name="kategori_id">
            @foreach ($kategoris as $k)
                <option value="{{ $k->id }}" @selected(old('kategori_id', $menu->kategori_id) == $k->id)>{{ $k->nama }}</option>
            @endforeach
        </select>
        @error('kategori_id') <div class="error">{{ $message }}</div> @enderror

        <label for="harga">Harga jual (Rp)</label>
        <input id="harga" name="harga" type="number" step="1" min="0" value="{{ old('harga', $menu->harga) }}">
        @error('harga') <div class="error">{{ $message }}</div> @enderror

        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="aktif" value="1" style="width:auto;margin:0"
                   @checked(old('aktif', $menu->exists ? $menu->aktif : true))>
            Menu aktif
        </label>

        <div style="margin-top:22px">
            <button class="btn btn-isi" type="submit">Simpan</button>
            <a class="btn btn-garis" href="/manajer/menu">Batal</a>
        </div>
    </form>
@endsection