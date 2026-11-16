@extends('layouts.manajer')

@section('title', 'Inventaris - Kopi Senja')

@section('konten')
    <h1 class="judul">Inventaris / Kelola Bahan</h1>
    <p class="sub">Kelola operasional Kedai Kopi Senja</p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    <div class="aksi-atas">
        <a class="btn btn-garis" href="#">Impor CSV</a>
        <a class="btn btn-isi" href="/manajer/bahan/create">Tambah Bahan</a>
    </div>

    <input class="cari" type="text" id="cari" placeholder="Cari bahan...">

    <table class="tabel" id="tabel-bahan">
        <thead>
            <tr>
                <th>Nama Bahan</th>
                <th>Satuan</th>
                <th>Stok Saat Ini</th>
                <th>Batas Minimum</th>
                <th>Harga / Satuan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bahans as $b)
                <tr class="{{ $b->isMenipis() ? 'menipis' : '' }}">
                    <td class="nama">{{ $b->nama }}</td>
                    <td>{{ $b->satuan_dasar }}</td>
                    <td>{{ number_format($b->stok, 0, ',', '.') }}</td>
                    <td>{{ number_format($b->batas_minimum, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($b->harga_per_satuan, 0, ',', '.') }}</td>
                    <td>
                        @if ($b->isMenipis())
                            <span class="badge">Menipis</span>
                        @else
                            <span class="aman">Aman</span>
                        @endif
                    </td>
                    <td>
                        <a class="link-aksi" href="/manajer/bahan/{{ $b->id }}/edit">Edit</a>
                        <form action="/manajer/bahan/{{ $b->id }}" method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus bahan ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="link-aksi hapus" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada bahan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <script>
        // pencarian langsung (tanpa reload)
        document.getElementById('cari').addEventListener('input', function () {
            const kata = this.value.toLowerCase();
            document.querySelectorAll('#tabel-bahan tbody tr').forEach(function (tr) {
                const nama = tr.querySelector('.nama');
                if (nama) tr.style.display = nama.textContent.toLowerCase().includes(kata) ? '' : 'none';
            });
        });
    </script>
@endsection