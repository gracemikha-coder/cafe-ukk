@extends('layouts.manajer')

@section('title', 'Stok Masuk - Kopi Senja')

@section('konten')
    <h1 class="judul">Stok Masuk</h1>
    <p class="sub">Kelola operasional Kedai Kopi Senja</p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    <form method="POST" action="/manajer/stok-masuk"
          style="border:1.5px solid #e2ddd7;border-radius:12px;padding:18px 20px;margin-bottom:32px">
        @csrf
        <div style="font-size:16px">Form Pembelian Bahan</div>
        <div style="font-size:13px;color:#82776e;margin:6px 0 16px">
            Tanggal pembelian, pilih bahan, jumlah. Stok bertambah otomatis.
        </div>

        <div class="baris-form">
            <div>
                <label for="tanggal">Tanggal Pembelian</label>
                <input id="tanggal" name="tanggal" type="date"
                       value="{{ old('tanggal', now()->toDateString()) }}">
                @error('tanggal') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="flex:1.2">
                <label for="bahan_id">Bahan</label>
                <select id="bahan_id" name="bahan_id">
                    <option value="">Pilih bahan baku</option>
                    @foreach ($bahans as $b)
                        <option value="{{ $b->id }}" @selected(old('bahan_id') == $b->id)>
                            {{ $b->nama }} ({{ $b->satuan_dasar }})
                        </option>
                    @endforeach
                </select>
                @error('bahan_id') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="jumlah">Jumlah</label>
                <input id="jumlah" name="jumlah" type="number" step="0.01" min="0"
                       placeholder="Masukkan jumlah" value="{{ old('jumlah') }}">
                @error('jumlah') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="flex:0 0 auto">
                <button class="btn btn-isi" type="submit">Simpan</button>
            </div>
        </div>
    </form>

    <h2 style="font-size:18px;margin:0 0 14px">Riwayat Stok Masuk</h2>

    <table class="tabel">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Bahan</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayat as $r)
                <tr>
                    <td>{{ $r->tanggal->format('d M Y') }}</td>
                    <td>{{ $r->bahan->nama }}</td>
                    <td>{{ number_format($r->jumlah, 0, ',', '.') }}</td>
                    <td>{{ $r->bahan->satuan_dasar }}</td>
                    <td>{{ $r->user->nama }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada stok masuk.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection