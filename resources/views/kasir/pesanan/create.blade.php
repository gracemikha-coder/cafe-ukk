@extends('layouts.kasir')

@section('title', 'Buat Pesanan - Kopi Senja')

@section('konten')
    @php
        $dataMenu = $menus->map(function ($m) {
            $status = !$m->aktif ? 'nonaktif'
                : ($m->resepItems->isEmpty() ? 'tanpa_resep'
                : ($m->isHabis() ? 'habis' : 'tersedia'));
            return [
                'id'     => $m->id,
                'nama'   => $m->nama,
                'harga'  => (float) $m->harga,
                'status' => $status,
                'resep'  => $m->resepItems->map(fn ($r) => [
                    'bahan_id' => $r->bahan_id,
                    'takaran'  => (float) $r->takaran,
                ])->values(),
            ];
        })->values();

        $dataBahan = $menus->flatMap(fn ($m) => $m->resepItems)
            ->map(fn ($r) => $r->bahan)->unique('id')
            ->mapWithKeys(fn ($b) => [$b->id => [
                'nama' => $b->nama, 'satuan' => $b->satuan_dasar, 'stok' => (float) $b->stok,
            ]]);
    @endphp

    <style>
        .grid-pesan { display: grid; grid-template-columns: 1fr 380px; gap: 28px; align-items: start; }
        .grid-menu { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 14px; }
        .kartu-menu { border: 1.5px solid #e2ddd7; border-radius: 12px; padding: 12px; background: #fff;
                      cursor: pointer; transition: .15s; }
        .kartu-menu:hover { border-color: #6f4e37; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .kartu-menu.mati { opacity: .55; cursor: not-allowed; }
        .kartu-menu .foto { height: 76px; border-radius: 8px; background: #e9ded4; color: #60442f;
                            display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; }
        .kartu-menu .nm { margin-top: 10px; font-weight: 700; font-size: 14px; }
        .kartu-menu .hg { color: #8a6251; font-size: 13px; margin-top: 2px; }
        .kartu-menu .st { margin-top: 8px; text-align: right; }

        .ringkasan { border: 1.5px solid #e2ddd7; border-radius: 14px; padding: 18px; background: #fff;
                     position: sticky; top: 100px; }
        .ringkasan h2 { font-size: 16px; margin: 0 0 2px; }
        .ringkasan .kecil { font-size: 12px; color: #877c72; margin-bottom: 12px; }
        .ringkasan label { display: block; font-size: 13px; color: #65574b; margin: 10px 0 4px; }
        .ringkasan input[type=number], .ringkasan select { width: 100%; padding: 10px; border: 1.5px solid #d8d2cb;
                     border-radius: 8px; font-size: 14px; background: #fff; }
        .tbl-item { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 13px; }
        .tbl-item th { background: #f4f0ec; padding: 8px; text-align: left; font-size: 12px; color: #65574b; }
        .tbl-item td { padding: 8px; border-bottom: 1px solid #e9e4df; vertical-align: middle; }
        .qty { display: inline-flex; align-items: center; gap: 6px; }
        .qty button { width: 24px; height: 24px; border: 1px solid #cfc7bf; background: #fff; border-radius: 6px; cursor: pointer; }
        .baris-total { display: flex; justify-content: space-between; font-size: 13px; margin-top: 10px; color: #453c35; }
        .baris-total.besar { font-size: 18px; font-weight: 700; color: #29231f; margin-top: 14px; }
        .notif { background: #fdecea; border: 1px solid #f3c1bb; border-radius: 8px; padding: 10px 14px;
                 margin-bottom: 14px; font-size: 13px; color: #8e2a20; }
        .notif:empty { display: none; }
        .btn-simpan { width: 100%; margin-top: 18px; padding: 13px; border: 0; border-radius: 8px;
                      background: #6f4e37; color: #fff; font-size: 15px; cursor: pointer; }
    </style>

    <h1 class="judul">Buat Pesanan</h1>
    <p class="sub">Pilih menu untuk pesanan</p>

    <div class="notif" id="notif">@foreach ($errors->all() as $pesan)<div>{{ $pesan }}</div>@endforeach</div>

    <form method="POST" action="/kasir/pesanan" id="form-pesanan">
        @csrf
        <div class="grid-pesan">

            {{-- Kiri: kartu menu --}}
            <div class="grid-menu">
                @forelse ($menus as $m)
                    @php
                        $status = !$m->aktif ? 'nonaktif'
                            : ($m->resepItems->isEmpty() ? 'tanpa_resep'
                            : ($m->isHabis() ? 'habis' : 'tersedia'));
                    @endphp
                    <div class="kartu-menu {{ $status !== 'tersedia' ? 'mati' : '' }}" data-id="{{ $m->id }}">
                        <div class="foto">{{ mb_strtoupper(mb_substr($m->nama, 0, 1)) }}</div>
                        <div class="nm">{{ $m->nama }}</div>
                        <div class="hg">Rp {{ number_format($m->harga, 0, ',', '.') }}</div>
                        <div class="st">
                            @if ($status === 'tersedia')
                                <span class="pil pil-aktif">Tersedia</span>
                            @elseif ($status === 'habis')
                                <span class="pil pil-habis">Habis</span>
                            @else
                                <span class="pil pil-nonaktif">{{ $status === 'nonaktif' ? 'Nonaktif' : 'Belum ada resep' }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p>Belum ada menu.</p>
                @endforelse
            </div>

            {{-- Kanan: ringkasan --}}
            <div class="ringkasan">
                <h2>Ringkasan Pesanan</h2>
                <div class="kecil">Item pesanan</div>

                <label for="nomor_meja">Nomor meja</label>
                <select id="nomor_meja" name="nomor_meja">
                    <option value="">Pilih meja</option>
                    @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}" @selected((string) old('nomor_meja') === (string) $i)>Meja {{ $i }}</option>
                    @endfor
                </select>

                <label for="metode_bayar">Metode bayar</label>
                <select id="metode_bayar" name="metode_bayar">
                    <option value="">Pilih metode bayar</option>
                    @foreach (['Tunai', 'QRIS'] as $mb)
                        <option value="{{ $mb }}" @selected(old('metode_bayar') === $mb)>{{ $mb }}</option>
                    @endforeach
                </select>

                <table class="tbl-item">
                    <thead><tr><th>Item</th><th>Qty</th><th style="text-align:right">Subtotal</th></tr></thead>
                    <tbody id="isi-item"></tbody>
                </table>

                <div class="baris-total"><span>Subtotal</span><span id="t-sub">Rp 0</span></div>
                <div class="baris-total"><span>Service 5%</span><span id="t-svc">Rp 0</span></div>
                <div class="baris-total"><span>Pajak 10%</span><span id="t-pjk">Rp 0</span></div>
                <div class="baris-total besar"><span>Total</span><span id="t-tot">Rp 0</span></div>

                <div id="hidden-jumlah"></div>
                <button type="submit" class="btn-simpan">Simpan Pesanan</button>
            </div>
        </div>
    </form>

    <script>
        const MENUS = @json($dataMenu);
        const BAHAN = @json($dataBahan);
        const cart = {};   // menu_id -> jumlah

        const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        const cariMenu = id => MENUS.find(m => m.id == id);
        const angka = n => parseFloat(n.toFixed(2)).toString();

        function tampilNotif(daftar) {
            document.getElementById('notif').innerHTML = daftar.map(t => '<div>' + t + '</div>').join('');
        }

        function kebutuhan(c) {
            const k = {};
            for (const id in c) {
                cariMenu(id).resep.forEach(r => {
                    k[r.bahan_id] = (k[r.bahan_id] || 0) + r.takaran * c[id];
                });
            }
            return k;
        }

        // NTF-01 (gabungan) dan NTF-02 (satu menu melebihi stok)
        function cekStok(c) {
            const k = kebutuhan(c), kurang = [];
            for (const b in k) {
                if (k[b] > BAHAN[b].stok + 1e-9) {
                    kurang.push('Stok tidak memadai: ' + BAHAN[b].nama + ' kurang ' +
                        angka(k[b] - BAHAN[b].stok) + ' ' + BAHAN[b].satuan + '. Pesanan tidak dapat diproses.');
                }
            }
            if (!kurang.length) return [];
            const ids = Object.keys(c);
            if (ids.length === 1) {
                const m = cariMenu(ids[0]);
                const maks = Math.min(...m.resep.map(r => Math.floor(BAHAN[r.bahan_id].stok / r.takaran)));
                return ['Jumlah melebihi stok. ' + m.nama + ' maksimal ' + maks + ' porsi.'];
            }
            return kurang;
        }

        function ubahJumlah(id, jumlah) {
            const salinan = Object.assign({}, cart);
            if (jumlah <= 0) delete salinan[id]; else salinan[id] = jumlah;
            const galat = cekStok(salinan);
            if (galat.length) { tampilNotif(galat); return; }
            tampilNotif([]);
            if (jumlah <= 0) delete cart[id]; else cart[id] = jumlah;
            gambar();
        }

        function gambar() {
            const isi = document.getElementById('isi-item');
            const hidden = document.getElementById('hidden-jumlah');
            isi.innerHTML = ''; hidden.innerHTML = '';
            let sub = 0;

            for (const id in cart) {
                const m = cariMenu(id), q = cart[id], s = m.harga * q;
                sub += s;
                isi.insertAdjacentHTML('beforeend',
                    '<tr><td>' + m.nama + '</td>' +
                    '<td><span class="qty"><button type="button" data-aksi="kurang" data-id="' + id + '">-</button>' + q +
                    '<button type="button" data-aksi="tambah" data-id="' + id + '">+</button></span></td>' +
                    '<td style="text-align:right">' + Math.round(s).toLocaleString('id-ID') + '</td></tr>');
                hidden.insertAdjacentHTML('beforeend',
                    '<input type="hidden" name="jumlah[' + id + ']" value="' + q + '">');
            }
            if (!Object.keys(cart).length) {
                isi.innerHTML = '<tr><td colspan="3" style="color:#a09488">Belum ada item</td></tr>';
            }

            // aturan biaya sama dengan server: dibulatkan ke rupiah terdekat
            const svc = Math.round(sub * 0.05);
            const pjk = Math.round((sub + svc) * 0.10);
            document.getElementById('t-sub').textContent = rp(sub);
            document.getElementById('t-svc').textContent = rp(svc);
            document.getElementById('t-pjk').textContent = rp(pjk);
            document.getElementById('t-tot').textContent = rp(sub + svc + pjk);
        }


        document.querySelectorAll('.kartu-menu').forEach(el => {
            el.addEventListener('click', function () {
                const m = cariMenu(this.dataset.id);
                if (m.status === 'nonaktif')    return tampilNotif(['Menu ' + m.nama + ' tidak tersedia.']);
                if (m.status === 'tanpa_resep') return tampilNotif(['Menu ' + m.nama + ' belum dapat dipesan.']);
                if (m.status === 'habis')       return tampilNotif(['Menu ' + m.nama + ' sedang habis.']);
                ubahJumlah(m.id, (cart[m.id] || 0) + 1);
            });
        });

        document.getElementById('isi-item').addEventListener('click', function (e) {
            const id = e.target.dataset.id;
            if (!id) return;
            ubahJumlah(id, (cart[id] || 0) + (e.target.dataset.aksi === 'tambah' ? 1 : -1));
        });

        // pulihkan keranjang bila server menolak (keranjang dipertahankan)
        const lama = @json(old('jumlah', []));
        for (const id in lama) {
            const q = parseInt(lama[id]);
            if (q > 0 && cariMenu(id)) cart[id] = q;
        }
        gambar();
    </script>
@endsection