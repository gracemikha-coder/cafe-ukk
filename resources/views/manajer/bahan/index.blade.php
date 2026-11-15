<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Bahan</title>
    <style>
        body { font-family: sans-serif; margin: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f3f3f3; }
        .menipis { background: #ffe5e5; }
        .badge { background: #d9534f; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .sukses { background: #dff0d8; padding: 8px; margin-bottom: 12px; }
        .btn { padding: 4px 10px; text-decoration: none; border: 1px solid #333; background: #fff; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Data Bahan</h1>
    <p><a href="/manajer/dashboard">&larr; Dashboard</a></p>

    @if (session('sukses'))
        <div class="sukses">{{ session('sukses') }}</div>
    @endif

    <p><a class="btn" href="/manajer/bahan/create">+ Tambah Bahan</a></p>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Satuan</th>
                <th>Stok</th>
                <th>Batas Minimum</th>
                <th>Harga/Satuan</th>
                <th>Harga Diperbarui</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bahans as $b)
                <tr class="{{ $b->isMenipis() ? 'menipis' : '' }}">
                    <td>{{ $b->nama }}</td>
                    <td>{{ $b->satuan_dasar }}</td>
                    <td>{{ $b->stok + 0 }}</td>
                    <td>{{ $b->batas_minimum + 0 }}</td>
                    <td>Rp{{ number_format($b->harga_per_satuan, 0, ',', '.') }}</td>
                    <td>{{ $b->harga_diperbarui_pada?->format('d/m/Y') ?? '-' }}</td>
                    <td>@if ($b->isMenipis()) <span class="badge">Menipis</span> @else Aman @endif</td>
                    <td>
                        <a class="btn" href="/manajer/bahan/{{ $b->id }}/edit">Ubah</a>
                        <form action="/manajer/bahan/{{ $b->id }}" method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus bahan ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada bahan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>