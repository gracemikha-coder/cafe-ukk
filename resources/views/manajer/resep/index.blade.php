@extends('layouts.manajer')

@section('title', 'Pengelolaan Resep - Kopi Senja')

@section('konten')
    @php
        // menu yang belum punya resep: belum bisa dipesan sampai resepnya ditambahkan
        $tanpaResep = $menus->filter(fn ($m) => $m->resepItems->isEmpty());
    @endphp

    <h1 class="judul">Pengelolaan Resep</h1>
    <p class="sub">Menu yang belum punya resep tidak dapat dipesan, baik oleh Kasir maupun Pelanggan.</p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    <div style="margin-bottom:18px">
        @if ($tanpaResep->isNotEmpty())
            <form onsubmit="location.href='/manajer/resep/' + this.menu.value; return false;"
                  style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <label for="menu-baru" style="font-size:14px">Tambah resep untuk menu:</label>
                <select id="menu-baru" name="menu"
                        style="padding:10px;border:1.5px solid #d8d2cb;border-radius:8px;font-size:14px;background:#fff">
                    @foreach ($tanpaResep as $m)
                        <option value="{{ $m->id }}">{{ $m->nama }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-isi btn-kecil">+ Tambah Resep</button>
            </form>
        @else
            <span style="font-size:14px;color:#877c72">Semua menu sudah punya resep.</span>
        @endif
    </div>

    <table class="tabel">
        <thead>
            <tr>
                <th>Nama Menu</th>
                <th>Jumlah Bahan</th>
                <th>Komposisi Ringkas</th>
                <th>HPP</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($menus as $m)
                <tr>
                    <td>{{ $m->nama }}</td>
                    <td>{{ $m->resepItems->count() }}</td>
                    <td>
                        @if ($m->resepItems->isEmpty())
                            <span class="badge">Belum ada resep</span>
                        @else
                            {{ $m->resepItems->pluck('bahan.nama')->implode(', ') }}
                        @endif
                    </td>
                    <td>
                        @if ($m->resepItems->isEmpty())
                            -
                        @else
                            Rp {{ number_format($m->hitungHPP(), 0, ',', '.') }}
                        @endif
                    </td>
                    <td>
                        <a class="link-aksi" href="/manajer/resep/{{ $m->id }}">
                            {{ $m->resepItems->isEmpty() ? 'Tambah Resep' : 'Ubah Resep' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada menu.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection