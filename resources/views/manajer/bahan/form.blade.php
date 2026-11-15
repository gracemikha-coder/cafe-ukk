<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $bahan->exists ? 'Ubah' : 'Tambah' }} Bahan</title>
    <style>
        body { font-family: sans-serif; margin: 24px; max-width: 420px; }
        label { display: block; margin-top: 12px; }
        input { width: 100%; padding: 6px; box-sizing: border-box; }
        .error { color: #c00; font-size: 13px; }
        .btn { margin-top: 16px; padding: 6px 14px; }
    </style>
</head>
<body>
    <h1>{{ $bahan->exists ? 'Ubah' : 'Tambah' }} Bahan</h1>

    <form method="POST" action="{{ $bahan->exists ? '/manajer/bahan/'.$bahan->id : '/manajer/bahan' }}">
        @csrf
        @if ($bahan->exists) @method('PUT') @endif

        @foreach ([
            'nama' => 'Nama',
            'satuan_dasar' => 'Satuan dasar (gram, ml, pcs)',
            'stok' => 'Stok',
            'batas_minimum' => 'Batas minimum',
            'harga_per_satuan' => 'Harga per satuan (Rp)',
        ] as $field => $label)
            <label for="{{ $field }}">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}"
                   type="{{ in_array($field, ['nama','satuan_dasar']) ? 'text' : 'number' }}"
                   step="0.01" value="{{ old($field, $bahan->$field) }}">
            @error($field) <div class="error">{{ $message }}</div> @enderror
        @endforeach

        <button class="btn" type="submit">Simpan</button>
        <a href="/manajer/bahan">Batal</a>
    </form>
</body>
</html>