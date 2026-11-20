@extends('layouts.manajer')

@section('title', 'Impor CSV - Kopi Senja')

@section('konten')
    <h1 class="judul">Impor CSV</h1>
    <p class="sub">
        Unggah data awal bahan, menu, dan resep. Satuan kg dan liter diubah ke g dan ml, nama bahan yang salah ketik
        dicocokkan, bahan yang tidak dikenal tidak diimpor, dan data yang sudah ada tidak ditimpa.
    </p>

    @error('impor')
        <div class="error" style="margin-bottom:14px">{{ $message }}</div>
    @enderror

    @if ($errors->any() && !$errors->has('impor'))
        <div class="error" style="margin-bottom:14px">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    <form class="kartu" method="POST" action="/manajer/impor-csv" enctype="multipart/form-data" style="max-width:720px">
        @csrf

        <label for="kategori">Kategori menu (master_kategori_menu.csv)</label>
        <input id="kategori" name="kategori" type="file" accept=".csv,.txt">

        <label for="bahan">Bahan (bahan.csv)</label>
        <input id="bahan" name="bahan" type="file" accept=".csv,.txt">

        <label for="menu">Menu (menu.csv)</label>
        <input id="menu" name="menu" type="file" accept=".csv,.txt">

        <label for="resep">Resep (resep.csv)</label>
        <input id="resep" name="resep" type="file" accept=".csv,.txt">

        <div style="margin-top:22px">
            <button class="btn btn-isi" type="submit">Impor</button>
        </div>
    </form>

    @if (session('hasil_impor'))
        @php $h = session('hasil_impor'); @endphp

        <h2 style="font-size:18px;margin:28px 0 10px">Hasil impor</h2>

        <table class="tabel" style="max-width:420px">
            <thead><tr><th>Data</th><th>Ditambahkan</th></tr></thead>
            <tbody>
                <tr><td>Kategori</td><td>{{ $h['hitung']['kategori'] }}</td></tr>
                <tr><td>Bahan</td><td>{{ $h['hitung']['bahan'] }}</td></tr>
                <tr><td>Menu</td><td>{{ $h['hitung']['menu'] }}</td></tr>
                <tr><td>Resep (baris)</td><td>{{ $h['hitung']['resep'] }}</td></tr>
            </tbody>
        </table>

        @if (!empty($h['perbaikan']))
            <h3 style="font-size:15px;margin:18px 0 6px">Diperbaiki otomatis</h3>
            <ul style="font-size:13px;line-height:1.7">
                @foreach ($h['perbaikan'] as $p)
                    <li>{{ $p }}</li>
                @endforeach
            </ul>
        @endif

        @if (!empty($h['dilewati']))
            <h3 style="font-size:15px;margin:18px 0 6px">Tidak diimpor</h3>
            <ul style="font-size:13px;line-height:1.7">
                @foreach ($h['dilewati'] as $d)
                    <li>{{ $d }}</li>
                @endforeach
            </ul>
        @endif

        @if (!empty($h['tanpaResep']))
            <p style="font-size:13px;margin-top:14px">
                Menu tanpa resep (belum bisa dipesan): {{ implode(', ', $h['tanpaResep']) }}
            </p>
        @endif
    @endif
@endsection