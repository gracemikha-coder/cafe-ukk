@extends('layouts.manajer')

@section('title', ($menu->exists ? 'Ubah' : 'Tambah') . ' Menu')

@section('konten')
    @php
        $barisAwal = old('items', $menu->resepItems->map(fn ($r) => [
            'bahan_id' => $r->bahan_id,
            'takaran'  => $r->takaran + 0,
        ])->values()->all());
    @endphp

    <h1 class="judul">{{ $menu->exists ? 'Ubah' : 'Tambah' }} Menu</h1>
    <p class="sub">Isi data menu dan resepnya (bahan dan takaran per 1 porsi) sekaligus.</p>

    <form class="kartu" method="POST" style="max-width:720px"
          action="{{ $menu->exists ? '/manajer/menu/'.$menu->id : '/manajer/menu' }}">
        @csrf
        @if ($menu->exists)
            @method('PUT')
        @endif

        <label for="nama">Nama menu</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $menu->nama) }}">
        @error('nama') <div class="error">{{ $message }}</div> @enderror

        <label for="kategori_id">Kategori</label>
        <select id="kategori_id" name="kategori_id">
            @foreach ($kategoris as $k)
                <option value="{{ $k->id }}" @selected(old('kategori_id', $menu->kategori_id) == $k->id)>{{ $k->nama }}</option>
            @endforeach
        </select>
        @error('kategori_id') <div class="error">{{ $message }}</div> @enderror

        <label for="harga">Harga jual (Rp)</label>
        <input id="harga" name="harga" type="number" step="1" min="0" value="{{ old('harga', $menu->harga) }}">
        @error('harga') <div class="error">{{ $message }}</div> @enderror

        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="aktif" value="1" style="width:auto;margin:0"
                   @checked(old('aktif', $menu->exists ? $menu->aktif : true))>
            Menu aktif
        </label>

        <hr style="border:0;border-top:1px solid #e2ddd7;margin:22px 0 10px">
        <strong>Resep (bahan per 1 porsi)</strong>

        @foreach ($errors->get('items.*') as $pesan)
            @foreach ($pesan as $p)
                <div class="error">{{ $p }}</div>
            @endforeach
        @endforeach

        <div id="daftar-baris"></div>

        <button type="button" class="btn btn-garis btn-kecil" id="tambah-baris" style="margin-top:14px">
            + Tambah bahan
        </button>

        <div style="margin-top:22px">
            <button class="btn btn-isi" type="submit">Simpan</button>
            <a class="btn btn-garis" href="/manajer/menu">Batal</a>
        </div>
    </form>

    <template id="tpl-baris">
        <div class="baris-resep" style="display:flex;gap:10px;align-items:flex-end;margin-top:12px">
            <div style="flex:2">
                <label>Bahan</label>
                <select class="pilih-bahan" name="items[__i__][bahan_id]">
                    @foreach ($bahans as $b)
                        <option value="{{ $b->id }}" data-satuan="{{ $b->satuan_dasar }}">{{ $b->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1">
                <label>Takaran <span class="satuan" style="color:#877c72"></span></label>
                <input type="number" step="0.01" min="0" name="items[__i__][takaran]" placeholder="mis. 18">
            </div>
            <button type="button" class="link-aksi hapus hapus-baris" style="margin-bottom:10px">Hapus</button>
        </div>
    </template>

    <script>
        (function () {
            const daftar = document.getElementById('daftar-baris');
            const tpl = document.getElementById('tpl-baris').innerHTML;
            const awal = @json($barisAwal);
            let i = 0;

            function updateSatuan(baris) {
                const opt = baris.querySelector('.pilih-bahan').selectedOptions[0];
                baris.querySelector('.satuan').textContent = opt ? '(' + opt.dataset.satuan + ')' : '';
            }

            function tambah(data) {
                daftar.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', i++));
                const baris = daftar.lastElementChild;
                if (data) {
                    baris.querySelector('.pilih-bahan').value = data.bahan_id;
                    baris.querySelector('input').value = data.takaran;
                }
                updateSatuan(baris);
            }

            document.getElementById('tambah-baris').addEventListener('click', function () { tambah(); });

            daftar.addEventListener('change', function (e) {
                if (e.target.classList.contains('pilih-bahan')) {
                    updateSatuan(e.target.closest('.baris-resep'));
                }
            });

            daftar.addEventListener('click', function (e) {
                if (e.target.classList.contains('hapus-baris')) {
                    e.target.closest('.baris-resep').remove();
                }
            });

            awal.forEach(tambah);
        })();
    </script>
@endsection