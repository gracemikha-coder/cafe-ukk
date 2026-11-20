@extends('layouts.manajer')

@section('title', 'HPP & Margin - Kopi Senja')

@section('konten')
    <h1 class="judul">HPP &amp; Margin</h1>
    <p class="sub">
        HPP = jumlah (takaran x harga bahan) di resep. Margin = harga jual - HPP.
        Harga bahan terakhir diperbarui:
        {{ $tanggalHarga ? \Illuminate\Support\Carbon::parse($tanggalHarga)->format('d/m/Y') : '-' }}.
        <a class="link-aksi" href="/manajer/bahan">Perbarui harga di Kelola Bahan</a>
    </p>

    <table class="tabel">
        <thead>
            <tr>
                <th>Menu</th>
                <th>Kategori</th>
                <th style="text-align:right">Harga Jual</th>
                <th style="text-align:right">HPP</th>
                <th style="text-align:right">Margin (Rp)</th>
                <th style="text-align:right">Margin (%)</th>
                <th>Rincian Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $b)
                <tr>
                    <td class="nama-menu">
                        {{ $b->nama }}
                        @if (!$b->aktif) <span class="badge">Nonaktif</span> @endif
                    </td>
                    <td>{{ $b->kategori }}</td>
                    <td style="text-align:right">Rp {{ number_format($b->harga, 0, ',', '.') }}</td>
                    @if ($b->punyaResep)
                        <td style="text-align:right">Rp {{ number_format($b->hpp, 0, ',', '.') }}</td>
                        <td style="text-align:right" class="{{ $b->margin >= 0 ? 'margin-plus' : 'margin-minus' }}">
                            Rp {{ number_format($b->margin, 0, ',', '.') }}
                        </td>
                        <td style="text-align:right" class="{{ $b->margin >= 0 ? 'margin-plus' : 'margin-minus' }}">
                            {{ number_format($b->persen, 1, ',', '.') }}%
                        </td>
                        <td>
                            <details>
                                <summary style="cursor:pointer">Lihat ({{ $b->rincian->count() }} bahan)</summary>
                                <table style="margin-top:8px;font-size:13px;width:100%">
                                    <thead>
                                        <tr><th>Bahan</th><th>Takaran</th><th>Harga/satuan</th><th style="text-align:right">Biaya</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($b->rincian as $r)
                                            <tr>
                                                <td>{{ $r['bahan'] }}</td>
                                                <td>{{ $r['takaran'] + 0 }} {{ $r['satuan'] }}</td>
                                                <td>Rp {{ number_format($r['harga'], 2, ',', '.') }}</td>
                                                <td style="text-align:right">Rp {{ number_format($r['biaya'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </details>
                        </td>
                    @else
                        <td colspan="4"><span class="badge">Belum ada resep</span></td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7">Belum ada menu.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection