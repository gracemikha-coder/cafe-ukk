<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Kopi Senja')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; color: #29231f; background: #fff; }

        .topbar { height: 76px; border-bottom: 1px solid #e2ddd7; display: flex;
                  align-items: center; justify-content: space-between; padding: 0 30px;
                  position: fixed; top: 0; left: 0; right: 0; background: #fff; z-index: 10; }
        .brand { font-size: 22px; font-weight: 700; color: #60442f; }
        .halo { font-size: 15px; color: #5c5148; }

        .sidebar { position: fixed; top: 76px; bottom: 0; left: 0; width: 250px;
                   background: #f7f4f1; padding: 24px 12px; display: flex; flex-direction: column; }
        .sidebar .label { font-size: 13px; font-weight: 700; color: #9a8878; padding: 0 12px 16px; }
        .sidebar a.menu { display: block; padding: 13px 18px; margin-bottom: 10px; border-radius: 8px;
                          font-size: 15px; color: #6d6258; text-decoration: none; }
        .sidebar a.menu:hover { background: #efe8e1; }
        .sidebar a.menu.aktif { background: #e9ded4; color: #60442f; }
        .sidebar .keluar { margin-top: auto; padding: 0 12px; font-size: 13px; color: #8a6251; text-decoration: none; }

        .konten { margin: 76px 0 0 250px; padding: 32px; }
        h1.judul { font-size: 26px; margin: 0 0 6px; }
        .sub { font-size: 14px; color: #877c72; margin: 0 0 24px; }

        .aksi-atas { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 14px; }
        .btn { display: inline-block; padding: 12px 22px; border-radius: 8px; font-size: 15px;
               text-decoration: none; cursor: pointer; border: 2px solid #6f4e37; }
        .btn-garis { background: #fff; color: #5a4030; }
        .btn-isi { background: #6f4e37; color: #fff; }
        .btn-kecil { padding: 5px 10px; font-size: 13px; border-width: 1px; border-radius: 6px; }

        .cari { width: 100%; max-width: 700px; padding: 12px 14px; background: #f5f2ef;
                border: 1px solid #ded7d0; border-radius: 8px; font-size: 14px; margin-bottom: 18px; }

        table.tabel { width: 100%; border-collapse: collapse; border: 1px solid #e2ddd7; }
        table.tabel th { background: #f4f0ec; color: #65574b; font-size: 13px; text-align: left; padding: 14px 12px; }
        table.tabel td { font-size: 13px; color: #453c35; padding: 24px 12px; border-top: 1px solid #e9e4df; }
        tr.menipis td { background: #fff5f3; }
        .badge { background: #c0392b; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        .aman { color: #2e7d32; }
        .link-aksi { color: #6f4e37; text-decoration: none; background: none; border: 0; cursor: pointer; font-size: 13px; padding: 0; }
        .link-aksi.hapus { color: #b03a2e; margin-left: 10px; }

        .sukses { background: #e7f3e4; border: 1px solid #c6e0c0; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }

        .kartu { border: 1.5px solid #e2ddd7; border-radius: 12px; padding: 20px; max-width: 520px; }
        .kartu label { display: block; margin-top: 14px; font-size: 13px; color: #65574b; }
        .kartu input, .kartu select { width: 100%; padding: 10px; margin-top: 4px; border: 1px solid #ded7d0; border-radius: 6px; font-size: 14px; background: #fff; }
        .error { color: #b03a2e; font-size: 13px; margin-top: 4px; }
        .baris-form { display: flex; gap: 20px; align-items: flex-end; }
        .baris-form > div { flex: 1; }
        .baris-form label { display: block; font-size: 14px; color: #51463d; margin-bottom: 6px; }
        .baris-form input, .baris-form select { width: 100%; padding: 11px 12px; border: 1.5px solid #d8d2cb; border-radius: 8px; font-size: 14px; background: #fff; }

        /* ---- percantik tabel ---- */
        table.tabel { border-radius: 10px; overflow: hidden; background: #fff; }
        table.tabel td { padding: 16px 14px; vertical-align: middle; }
        table.tabel th { padding: 14px; text-transform: uppercase; letter-spacing: .4px; font-size: 12px; }
        table.tabel tbody tr:hover td { background: #fbf9f7; }
        .angka { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .nama-menu { font-weight: 700; font-size: 14px; color: #29231f; }

        .pil { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .pil-kat { background: #efe8e1; color: #60442f; }
        .pil-aktif { background: #e3f1e0; color: #2e7d32; }
        .pil-habis { background: #fdecea; color: #b03a2e; }
        .pil-nonaktif { background: #ececec; color: #6b6b6b; }

        .chips { display: flex; flex-wrap: wrap; gap: 6px; max-width: 320px; }
        .chip { background: #f4f0ec; border: 1px solid #e2ddd7; border-radius: 6px; padding: 3px 8px; font-size: 12px; color: #453c35; white-space: nowrap; }
        .chip b { color: #60442f; }
        .kosong { color: #a09488; font-style: italic; font-size: 12px; }

        .margin-plus { color: #2e7d32; font-weight: 700; }
        .margin-minus { color: #b03a2e; font-weight: 700; }

        .aksi-grup { display: flex; gap: 8px; align-items: center; }
        .btn-mini { padding: 6px 12px; border: 1px solid #6f4e37; border-radius: 6px; background: #fff; color: #5a4030; font-size: 12px; cursor: pointer; text-decoration: none; }
        .btn-mini:hover { background: #f4f0ec; }
        .btn-mini.abu { border-color: #cfc7bf; color: #7a6f65; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">KOPI SENJA</div>
        <div class="halo">Halo, {{ ucfirst(auth()->user()->role) }}</div>
    </header>

    <aside class="sidebar">
        <div class="label">MANAJER</div>
        <a class="menu {{ request()->is('manajer/bahan*') ? 'aktif' : '' }}" href="/manajer/bahan">Kelola Bahan</a>
        <a class="menu {{ request()->is('manajer/menu*') ? 'aktif' : '' }}" href="/manajer/menu">Menu</a>
        <a class="menu {{ request()->is('manajer/resep*') ? 'aktif' : '' }}" href="/manajer/resep">Resep</a>
        <a class="menu {{ request()->is('manajer/stok-masuk*') ? 'aktif' : '' }}" href="/manajer/stok-masuk">Stok Masuk</a>
        <a class="menu {{ request()->is('manajer/laporan*') ? 'aktif' : '' }}" href="/manajer/laporan">Laporan</a>
        <a class="keluar" href="/logout">Keluar dari sistem</a>
    </aside>

    <main class="konten">
        @yield('konten')
    </main>
</body>
</html>