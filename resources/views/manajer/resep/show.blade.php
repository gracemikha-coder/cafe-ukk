@extends('layouts.manajer')

@section('title', 'Editor Resep ' . $menu->nama)

@section('konten')
    <h1 class="judul">Editor Resep</h1>
    <p class="sub">
        Menu: <strong>{{ $menu->nama }}</strong> &nbsp;|&nbsp;
        <a class="link-aksi" href="/manajer/resep">&larr; Kembali ke daftar resep</a>
    </p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    <table class="tabel" style="margin-bottom:16px">
        <thead>
            <tr>
                <th>Bahan</th>
                <th>Takaran</th>
                <th>Satuan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($menu->resepItems as $item)
                <tr>
                    <td>{{ $item->bahan->nama }}</td>
                    <td>{{ $item->takaran + 0 }}</td>
                    <td>{{ $item->bahan->satuan_dasar }}</td>
                    <td>
                        <form action="/manajer/resep/{{ $menu->id }}/{{ $item->id }}" method="POST"
                              onsubmit="return confirm('Hapus bahan ini dari resep?')">
                            @csrf
                            @method('DELETE')
                            <button class="link-aksi hapus" style="margin-left:0" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Resep belum ada. Tambahkan bahan di bawah.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="font-size:14px">
        <strong>HPP:</strong> Rp {{ number_format($menu->hitungHPP(), 0, ',', '.') }}
        &nbsp;|&nbsp; <strong>Harga jual:</strong> Rp {{ number_format($menu->harga, 0, ',', '.') }}
        &nbsp;|&nbsp; <strong>Margin:</strong> Rp {{ number_format($menu->hitungMargin(), 0, ',', '.') }}
    </p>

    <form class="kartu" method="POST" action="/manajer/resep/{{ $menu->id }}" style="max-width:640px">
        @csrf
        <strong>Tambah bahan ke resep</strong>

        @if ($errors->any())
            @foreach ($errors->all() as $e)
                <div class="error">{{ $e }}</div>
            @endforeach
        @endif

        <div id="daftar-baris"></div>

        <button type="button" class="btn btn-garis btn-kecil" id="tambah-baris" style="margin-top:14px">
            + Tambah baris bahan
        </button>

        <div style="margin-top:20px">
            <button class="btn btn-isi" type="submit">Simpan Resep</button>
        </div>
    </form>

    <template id="tpl-baris">
        <div class="baris-resep" style="display:flex;gap:10px;align-items:flex-end;margin-top:12px">
            <div style="flex:2">
                <label>Bahan</label>
                <select name="items[__i__][bahan_id]">
                    @foreach ($bahans as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->satuan_dasar }})</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1">
                <label>Takaran</label>
                <input type="number" step="0.01" min="0" name="items[__i__][takaran]" placeholder="mis. 18">
            </div>
            <button type="button" class="link-aksi hapus hapus-baris" style="margin-bottom:10px">Hapus</button>
        </div>
    </template>

    <script>
        (function () {
            const daftar = document.getElementById('daftar-baris');
            const tpl = document.getElementById('tpl-baris').innerHTML;
            let i = 0;

            function tambah() {
                daftar.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', i++));
            }

            document.getElementById('tambah-baris').addEventListener('click', tambah);
            daftar.addEventListener('click', function (e) {
                if (e.target.classList.contains('hapus-baris') && daftar.children.length > 1) {
                    e.target.closest('.baris-resep').remove();
                }
            });

            tambah(); // satu baris awal
        })();
    </script>
@endsection