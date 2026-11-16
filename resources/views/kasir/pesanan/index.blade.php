@extends('layouts.kasir')

@section('title', 'Daftar Pesanan - Kopi Senja')

@section('konten')
    <h1 class="judul">Daftar Pesanan</h1>
    <p class="sub">Secara bawaan menampilkan pesanan berstatus Baru dan Diproses</p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    @if ($errors->any())
        <div style="background:#fdecea;border:1px solid #f3c1bb;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;color:#8e2a20">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <a class="btn-mini {{ $status ? 'abu' : '' }}" href="/kasir/pesanan">Baru &amp; Diproses</a>
        @foreach ($daftarStatus as $s)
            <a class="btn-mini {{ $status === $s ? '' : 'abu' }}" href="/kasir/pesanan?status={{ $s }}">{{ $s }}</a>
        @endforeach
    </div>

    <table class="tabel">
        <thead>
            <tr>
                <th>No</th>
                <th>Meja</th>
                <th>Waktu</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pesanans as $p)
                <tr>
                    <td class="nama-menu">#{{ $p->id }}</td>
                    <td>{{ $p->nomor_meja }}</td>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td class="angka">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $kelas = match ($p->status) {
                                'Baru'     => 'pil-kat',
                                'Diproses' => 'pil-aktif',
                                'Selesai'  => 'pil-nonaktif',
                                default    => 'pil-habis',
                            };
                        @endphp
                        <span class="pil {{ $kelas }}">{{ $p->status }}</span>
                    </td>
                    <td>
                        <div class="aksi-grup">
                            @if ($p->status === 'Baru')
                                <form method="POST" action="/kasir/pesanan/{{ $p->id }}/status">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="Diproses">
                                    <button type="submit" class="btn-mini">Proses</button>
                                </form>
                                <form method="POST" action="/kasir/pesanan/{{ $p->id }}/batal"
                                      onsubmit="return confirm('Batalkan pesanan #{{ $p->id }}? Stok bahan akan dikembalikan.')">
                                    @csrf
                                    <button type="submit" class="btn-mini abu">Batalkan</button>
                                </form>
                            @elseif ($p->status === 'Diproses')
                                <form method="POST" action="/kasir/pesanan/{{ $p->id }}/status">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="Selesai">
                                    <button type="submit" class="btn-mini">Selesaikan</button>
                                </form>
                            @endif
                            <a class="btn-mini abu" href="/kasir/pesanan/{{ $p->id }}">Struk</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada pesanan</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection