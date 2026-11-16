@extends('layouts.manajer')

@section('title', 'Menu & Resep - Kopi Senja')

@section('konten')
    <h1 class="judul">Menu &amp; Resep</h1>
    <p class="sub">Kelola menu, resep, HPP, dan margin</p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    <div class="aksi-atas">
        <a class="btn btn-isi" href="/manajer/menu/create">Tambah Menu</a>
    </div>

    <table class="tabel">
        <thead>
            <tr>
                <th>Nama Menu</th>
                <th>Kategori</th>
                <th>Resep</th>
                <th>Harga Jual</th>
                <th>HPP</th>
                <th>Margin</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($menus as $m)
                <tr>
                    <td>{{ $m->nama }}</td>
                    <td>{{ $m->kategori->nama }}</td>
                    <td>
                        @forelse ($m->resepItems as $r)
                            <div>{{ $r->bahan->nama }}: {{ $r->takaran + 0 }} {{ $r->bahan->satuan_dasar }}</div>
                        @empty
                            <span style="color:#a09488">Belum ada resep</span>
                        @endforelse
                    </td>
                    <td>Rp {{ number_format($m->harga, 0, ',', '.') }}</td>
                    <td>
                        @if ($m->resepItems->isNotEmpty())
                            Rp {{ number_format($m->hitungHPP(), 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($m->resepItems->isNotEmpty())
                            Rp {{ number_format($m->hitungMargin(), 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if (!$m->aktif)
                            <span class="badge">Nonaktif</span>
                        @elseif ($m->resepItems->isNotEmpty() && $m->isHabis())
                            <span class="badge">Habis</span>
                        @else
                            <span class="aman">Aktif</span>
                        @endif
                    </td>
                    <td>
                        <a class="link-aksi" href="/manajer/menu/{{ $m->id }}/edit">Edit</a>
                        <form action="/manajer/menu/{{ $m->id }}/toggle" method="POST" style="display:inline">
                            @csrf
                            @method('PATCH')
                            <button class="link-aksi" style="margin-left:10px" type="submit">
                                {{ $m->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada menu.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection