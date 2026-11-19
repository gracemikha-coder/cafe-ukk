@extends('layouts.kasir')

@section('title', 'Akun Pelanggan - Kopi Senja')

@section('konten')
    <h1 class="judul">Akun Pelanggan</h1>
    <p class="sub">Reset password pelanggan yang lupa. Pelanggan wajib menggantinya saat login berikutnya.</p>

    @if (session('password_sementara'))
        <div class="sukses">
            Password sementara untuk <strong>{{ session('nama_reset') }}</strong>:
            <strong style="font-size:18px;letter-spacing:1px">{{ session('password_sementara') }}</strong>
            <div style="margin-top:4px;font-size:13px">Berikan ke pelanggan. Password ini tidak akan ditampilkan lagi.</div>
        </div>
    @endif

    <form method="GET" action="/kasir/pelanggan" style="display:flex;gap:8px;margin-bottom:16px;max-width:520px">
        <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama atau nomor HP"
               style="flex:1;padding:10px;border:1px solid #ded7d0;border-radius:6px;font-size:14px">
        <button type="submit" class="btn-mini">Cari</button>
        <a href="/kasir/pelanggan" class="btn-mini abu">Reset</a>
    </form>

    <table class="tabel">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Nomor HP</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pelanggans as $p)
                <tr>
                    <td class="nama-menu">{{ $p->nama }}</td>
                    <td>{{ $p->username }}</td>
                    <td>
                        @if ($p->wajib_ganti_password)
                            <span class="pil pil-habis">Wajib ganti password</span>
                        @else
                            <span class="pil pil-aktif">Normal</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="/kasir/pelanggan/{{ $p->id }}/reset-password"
                              onsubmit="return confirm('Reset password pelanggan ini? Password lama tidak berlaku lagi.')">
                            @csrf
                            <button type="submit" class="btn-mini">Reset Password</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada akun pelanggan.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection