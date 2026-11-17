@extends('layouts.manajer')

@section('title', 'Laporan - Kopi Senja')

@section('konten')
    <style>
        .filter { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 22px; }
        .filter label { display: block; font-size: 13px; color: #65574b; margin-bottom: 4px; }
        .filter input { padding: 10px 12px; border: 1.5px solid #d8d2cb; border-radius: 8px; font-size: 14px; }
        .ringkas { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
        .ringkas .kotak { border: 1.5px solid #e2ddd7; border-radius: 12px; padding: 16px; background: #fff; }
        .ringkas .kotak .ket { font-size: 12px; color: #877c72; }
        .ringkas .kotak .nilai { font-size: 20px; font-weight: 700; margin-top: 6px; color: #29231f; }
        h2.bagian { font-size: 18px; margin: 28px 0 12px; }
        table.tabel .kanan { text-align: right; }
        .galat { background: #fdecea; border: 1px solid #f3c1bb; border-radius: 8px; padding: 10px 14px;
                 margin-bottom: 14px; font-size: 13px; color: #8e2a20; }
    </style>

    <h1 class="judul">Laporan</h1>
    <p class="sub">Penjualan per menu, margin, dan bahan yang hampir habis</p>

    @if ($errors->any())
        <div class="galat">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="/manajer/laporan" class="filter">
        <div>
            <label for="dari">Dari tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari }}">
        </div>
        <div>
            <label for="sampai">Sampai tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai }}">
        </div>
        <button type="submit" class="btn btn-isi btn-kecil">Terapkan</button>
        <a href="/manajer/laporan" class="btn btn-garis btn-kecil">Reset</a>
    </form>

    <div class="ringkas">
        <div class="kotak"><div class="ket">Pesanan (tanpa Batal)</div><div class="nilai">{{ number_format($ringkasan['pesanan'], 0, ',', '.') }}</div></div>
        <div class="kotak"><div class="ket">Porsi terjual</div><div class="nilai">{{ number_format($ringkasan['terjual'], 0, ',', '.') }}</div></div>
        <div class="kotak"><div class="ket">Pendapatan menu</div><div class="nilai">Rp {{ number_format($ringkasan['pendapatan'], 0, ',', '.') }}</div></div>
        <div class="kotak">
            <div class="ket">Keuntungan (pendapatan - HPP)</div>
            <div class="nilai {{ $ringkasan['keuntungan'] >= 0 ? 'margin-plus' : 'margin-minus' }}">Rp {{ number_format($ringkasan['keuntungan'], 0, ',', '.') }}</div>
        </div>
    </div>

    <h2 class="bagian">Penjualan per menu</h2>
    <table class="tabel">
        <thead>
            <tr>
                <th>Menu</th>
                <th>Kategori</th>
                <th class="kanan">Harga jual</th>
                <th class="kanan">HPP</th>
                <th class="kanan">Margin / porsi</th>
                <th class="kanan">Terjual</th>
                <th class="kanan">Pendapatan</th>
                <th class="kanan">Keuntungan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $b)
                <tr>
                    <td class="nama-menu">{{ $b->nama }}</td>
                    <td><span class="pil pil-kat">{{ $b->kategori }}</span></td>
                    <td class="angka kanan">Rp {{ number_format($b->harga, 0, ',', '.') }}</td>
                    <td class="angka kanan">Rp {{ number_format($b->hpp, 0, ',', '.') }}</td>
                    <td class="angka kanan {{ $b->margin >= 0 ? 'margin-plus' : 'margin-minus' }}">
                        Rp {{ number_format($b->margin, 0, ',', '.') }} ({{ number_format($b->persen, 0) }}%)
                    </td>
                    <td class="angka kanan">{{ number_format($b->terjual, 0, ',', '.') }}</td>
                    <td class="angka kanan">Rp {{ number_format($b->pendapatan, 0, ',', '.') }}</td>
                    <td class="angka kanan {{ $b->keuntungan >= 0 ? 'margin-plus' : 'margin-minus' }}">
                        Rp {{ number_format($b->keuntungan, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada menu.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="sub" style="margin-top:8px">
        Pesanan berstatus Batal tidak dihitung. Pendapatan memakai harga saat dipesan (tanpa service dan pajak).
        HPP dihitung dari resep dan harga bahan terbaru.
    </p>

    <h2 class="bagian">Bahan menipis</h2>
    <table class="tabel">
        <thead>
            <tr>
                <th>Bahan</th>
                <th class="kanan">Stok saat ini</th>
                <th class="kanan">Batas minimum</th>
                <th>Satuan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($menipis as $b)
                <tr class="menipis">
                    <td class="nama-menu">{{ $b->nama }}</td>
                    <td class="angka kanan">{{ rtrim(rtrim(number_format($b->stok, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="angka kanan">{{ rtrim(rtrim(number_format($b->batas_minimum, 2, ',', '.'), '0'), ',') }}</td>
                    <td>{{ $b->satuan_dasar }}</td>
                    <td><span class="badge">Menipis</span></td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada bahan yang menipis.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection