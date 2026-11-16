@extends('layouts.kasir')

@section('title', 'Struk Pesanan #' . $pesanan->id)

@section('konten')
    <style>
        .baris-struk { display: flex; justify-content: space-between; font-size: 13px; padding: 3px 0; }
        .tombol-aksi { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .tombol-aksi form { margin: 0; }
        @media print {
            .topbar, .sidebar, .no-print { display: none !important; }
            .konten { margin: 0 !important; padding: 0 !important; }
            .kartu { border: 0 !important; max-width: 100% !important; }
        }
    </style>

    <div class="no-print">
        <h1 class="judul">Detail Pesanan #{{ $pesanan->id }}</h1>
        <p class="sub"><a class="link-aksi" href="/kasir/pesanan">&larr; Kembali ke daftar pesanan</a></p>

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
    </div>

    <div class="kartu" style="max-width:420px">
        <div style="text-align:center;font-weight:700;font-size:18px;color:#60442f">KOPI SENJA</div>
        <div style="text-align:center;font-size:12px;color:#877c72;margin-bottom:14px">Struk Pesanan</div>

        <div class="baris-struk"><span>No. pesanan</span><span>#{{ $pesanan->id }}</span></div>
        <div class="baris-struk"><span>Tanggal</span><span>{{ $pesanan->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="baris-struk"><span>Meja</span><span>{{ $pesanan->nomor_meja }}</span></div>
        <div class="baris-struk"><span>Metode bayar</span><span>{{ $pesanan->metode_bayar }}</span></div>
        <div class="baris-struk"><span>Status</span><span>{{ $pesanan->status }}</span></div>

        <hr style="border:0;border-top:1px dashed #cfc7bf;margin:12px 0">

        @foreach ($pesanan->items as $it)
            <div class="baris-struk">
                <span>{{ $it->nama_menu_saat_pesan }} x{{ $it->jumlah }}</span>
                <span>Rp {{ number_format($it->subtotal_item, 0, ',', '.') }}</span>
            </div>
            <div style="font-size:11px;color:#877c72;margin:-2px 0 6px">
                @ Rp {{ number_format($it->harga_satuan_saat_pesan, 0, ',', '.') }}
            </div>
        @endforeach

        <hr style="border:0;border-top:1px dashed #cfc7bf;margin:12px 0">

        <div class="baris-struk"><span>Subtotal</span><span>Rp {{ number_format($pesanan->subtotal, 0, ',', '.') }}</span></div>
        <div class="baris-struk"><span>Service 5%</span><span>Rp {{ number_format($pesanan->service, 0, ',', '.') }}</span></div>
        <div class="baris-struk"><span>Pajak 10%</span><span>Rp {{ number_format($pesanan->pajak, 0, ',', '.') }}</span></div>
        <div class="baris-struk" style="font-weight:700;font-size:16px;margin-top:8px">
            <span>Total</span><span>Rp {{ number_format($pesanan->total, 0, ',', '.') }}</span>
        </div>

        <div style="text-align:center;font-size:12px;color:#877c72;margin-top:16px">Terima kasih</div>
    </div>

    <div class="tombol-aksi no-print">
        <button type="button" class="btn-mini" onclick="window.print()">Cetak Struk</button>

        @if ($pesanan->status === 'Baru')
            <form method="POST" action="/kasir/pesanan/{{ $pesanan->id }}/status">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="Diproses">
                <button type="submit" class="btn-mini">Proses</button>
            </form>
            <form method="POST" action="/kasir/pesanan/{{ $pesanan->id }}/batal"
                  onsubmit="return confirm('Batalkan pesanan ini? Stok bahan akan dikembalikan.')">
                @csrf
                <button type="submit" class="btn-mini abu">Batalkan</button>
            </form>
        @elseif ($pesanan->status === 'Diproses')
            <form method="POST" action="/kasir/pesanan/{{ $pesanan->id }}/status">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="Selesai">
                <button type="submit" class="btn-mini">Selesaikan</button>
            </form>
        @endif
    </div>
@endsection