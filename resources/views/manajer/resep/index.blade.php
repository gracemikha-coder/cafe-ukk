@extends('layouts.manajer')

@section('title', 'Pengelolaan Resep - Kopi Senja')

@section('konten')
    <h1 class="judul">Pengelolaan Resep</h1>
    <p class="sub">Kelola operasional Kedai Kopi Senja</p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

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
                            -
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
                    <td><a class="link-aksi" href="/manajer/resep/{{ $m->id }}">Kelola</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada menu.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection