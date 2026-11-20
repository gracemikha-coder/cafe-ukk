import './style.css';
import '@ionic/core/css/core.css';
import '@ionic/core/css/normalize.css';
import '@ionic/core/css/structure.css';
import '@ionic/core/css/typography.css';
import { defineCustomElements } from '@ionic/core/loader';
import { requestAPI, getApiUrl, setApiUrl } from './api.js';

defineCustomElements(window);

const app = document.querySelector('#app');

// ===== STATE =====
const state = {
  nama: localStorage.getItem('nama_pelanggan') || '',
  menu: [],          // cache daftar menu dari API
  keranjang: {},     // { idMenu: jumlah }
  meja: '1',
  metode: 'Tunai'
};

const ambilToken = () => localStorage.getItem('token_pelanggan');
const rupiah = angka => 'Rp ' + Number(angka || 0).toLocaleString('id-ID');

// MENGAMANKAN TEKS DARI DATABASE
function escapeHTML(teks) {
  return String(teks ?? '').replace(/[&<>"']/g, karakter => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  })[karakter]);
}

// MENGAMBIL NAMA KATEGORI
function ambilKategori(menu) {
  if (typeof menu.kategori === 'string') return menu.kategori;
  return menu.kategori?.nama || menu.kategori_nama || 'Lainnya';
}

// ===== SESI =====
function keLogin(pesan = '') {
  localStorage.removeItem('token_pelanggan');
  localStorage.removeItem('wajib_ganti_password');
  state.keranjang = {};
  state.menu = [];
  tampilkanHalaman('login', pesan);
}

// true bila error sudah ditangani (sesi habis / wajib ganti password)
function sesiBerakhir(error) {
  if (error.status === 401) {
    keLogin('Sesi berakhir, silakan login kembali.');
    return true;
  }
  if (error.status === 403 && localStorage.getItem('wajib_ganti_password') === 'true') {
    tampilkanGantiPassword(true);
    return true;
  }
  return false;
}

// ===== KERANGKA HALAMAN (header + navigasi bawah) =====
function kerangka(aktif, isi) {
  const jumlah = Object.values(state.keranjang).reduce((a, b) => a + b, 0);
  const nav = [
    ['Menu', 'Menu'],
    ['Keranjang', `Keranjang${jumlah ? ` (${jumlah})` : ''}`],
    ['Pesanan', 'Pesanan'],
    ['Profil', 'Profil']
  ];

  app.innerHTML = `
    <main class="mobile-frame menu-page">
      <header class="app-header">
        <span class="brand-name">KOPI SENJA</span>
        <span class="user-role">PELANGGAN • MOBILE</span>
      </header>

      <section class="main-content">${isi}</section>

      <nav class="bottom-nav">
        ${nav.map(([kunci, label]) => `
          <button class="nav-item ${aktif === kunci ? 'active' : ''}" data-nav="${kunci}">
            ${label}
          </button>`).join('')}
      </nav>
    </main>
  `;

  app.querySelectorAll('[data-nav]').forEach(tombol => {
    tombol.addEventListener('click', () => {
      ({
        Menu: tampilkanBeranda,
        Keranjang: () => tampilkanKeranjang(),
        Pesanan: tampilkanPesanan,
        Profil: () => tampilkanProfil()
      })[tombol.dataset.nav]();
    });
  });
}

// ===== HALAMAN DAFTAR MENU =====
async function tampilkanBeranda() {
  kerangka('Menu', `
    <h1 class="page-title">Daftar Menu</h1>
    <p class="welcome-text">Selamat datang, ${escapeHTML(state.nama)}!</p>

    <div class="search-box">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input type="text" id="cari-menu" placeholder="Cari menu..." class="search-input">
    </div>

    <div class="category-list" id="daftar-kategori">
      <button class="cat-pill active" data-category="Semua">Semua</button>
    </div>

    <div id="daftar-menu" class="menu-grid">
      <p class="description">Memuat menu...</p>
    </div>
  `);

  const daftarMenu = app.querySelector('#daftar-menu');
  const inputCari = app.querySelector('#cari-menu');
  const daftarKategori = app.querySelector('#daftar-kategori');
  let kategoriAktif = 'Semua';

  try {
    const hasil = await requestAPI('menu', null, 'GET', ambilToken());
    state.menu = hasil.data || [];

    if (state.menu.length === 0) {
      daftarMenu.innerHTML = '<p class="description">Belum ada menu tersedia.</p>';
      return;
    }

    const kategori = [...new Set(state.menu.map(menu => ambilKategori(menu)))];

    daftarKategori.innerHTML = `
      <button class="cat-pill active" data-category="Semua">Semua</button>
      ${kategori.map(item => `
        <button class="cat-pill" data-category="${escapeHTML(item)}">${escapeHTML(item)}</button>
      `).join('')}
    `;

    function tampilkanMenu(dataMenu) {
      if (dataMenu.length === 0) {
        daftarMenu.innerHTML = '<p class="description">Menu tidak ditemukan.</p>';
        return;
      }

      daftarMenu.innerHTML = dataMenu.map(menu => {
        const bisa = menu.bisa_dipesan !== false;
        const dikeranjang = state.keranjang[menu.id] || 0;

        return `
          <article class="menu-card ${bisa ? '' : 'off'}" data-menu-id="${menu.id}">
            <div class="product-img">[ foto produk ]</div>
            <div class="product-name">${escapeHTML(menu.nama)}</div>
            <div class="product-price">${rupiah(menu.harga)}</div>
            <span class="${bisa ? 'badge-available' : 'badge-habis'}">
              ${bisa ? 'Tersedia' : escapeHTML(menu.status || 'Habis')}
            </span>
            ${dikeranjang ? `<span class="badge-cart">${dikeranjang} di keranjang</span>` : ''}
          </article>`;
      }).join('');
    }

    function filterMenu() {
      const kataKunci = inputCari.value.toLowerCase().trim();
      tampilkanMenu(state.menu.filter(menu =>
        String(menu.nama || '').toLowerCase().includes(kataKunci) &&
        (kategoriAktif === 'Semua' || ambilKategori(menu) === kategoriAktif)
      ));
    }

    inputCari.addEventListener('input', filterMenu);

    daftarKategori.addEventListener('click', event => {
      const tombol = event.target.closest('.cat-pill');
      if (!tombol) return;
      kategoriAktif = tombol.dataset.category;
      daftarKategori.querySelectorAll('.cat-pill').forEach(item => item.classList.remove('active'));
      tombol.classList.add('active');
      filterMenu();
    });

    daftarMenu.addEventListener('click', event => {
      const card = event.target.closest('.menu-card');
      if (!card || card.classList.contains('off')) return; // menu habis tidak bisa dipilih
      tampilkanDetail(Number(card.dataset.menuId));
    });

    tampilkanMenu(state.menu);

  } catch (error) {
    if (sesiBerakhir(error)) return;
    daftarMenu.innerHTML = `<p class="description">${escapeHTML(error.message)}</p>`;
  }
}

// ===== DETAIL PRODUK =====
function tampilkanDetail(idMenu) {
  const menu = state.menu.find(item => Number(item.id) === Number(idMenu));
  if (!menu) return;

  let jumlahPesanan = state.keranjang[menu.id] || 1;

  kerangka('Menu', `
    <button id="btn-kembali" class="btn-back">← Kembali</button>

    <div class="product-img detail-image">[ foto produk ]</div>

    <h1 class="page-title">${escapeHTML(menu.nama)}</h1>
    <p class="product-price detail-price">${rupiah(menu.harga)}</p>
    <p class="welcome-text">Produk tersedia dan siap dipesan.</p>

    <div class="quantity-section">
      <label>Jumlah Pesanan</label>
      <div class="quantity-control">
        <button id="kurangi-jumlah">−</button>
        <span id="jumlah-produk">${jumlahPesanan}</span>
        <button id="tambah-jumlah">+</button>
      </div>
    </div>

    <button id="tambah-keranjang" class="btn-primary">Simpan ke Keranjang</button>
  `);

  const jumlahProduk = app.querySelector('#jumlah-produk');

  app.querySelector('#kurangi-jumlah').addEventListener('click', () => {
    if (jumlahPesanan > 1) { jumlahPesanan--; jumlahProduk.textContent = jumlahPesanan; }
  });

  app.querySelector('#tambah-jumlah').addEventListener('click', () => {
    jumlahPesanan++;
    jumlahProduk.textContent = jumlahPesanan;
  });

  app.querySelector('#btn-kembali').addEventListener('click', tampilkanBeranda);

  app.querySelector('#tambah-keranjang').addEventListener('click', () => {
    state.keranjang[menu.id] = jumlahPesanan;
    tampilkanBeranda();
  });
}

// ===== KERANJANG + PESAN =====
async function tampilkanKeranjang(pesanError = '') {
  if (state.menu.length === 0) {
    try {
      const hasil = await requestAPI('menu', null, 'GET', ambilToken());
      state.menu = hasil.data || [];
    } catch (error) {
      if (sesiBerakhir(error)) return;
    }
  }

  const baris = Object.entries(state.keranjang)
    .map(([id, jml]) => ({ menu: state.menu.find(m => Number(m.id) === Number(id)), jml }))
    .filter(x => x.menu && x.jml > 0);

  const subtotal = baris.reduce((t, x) => t + Number(x.menu.harga) * x.jml, 0);

  const opsiMeja = Array.from({ length: 10 }, (_, i) =>
    `<option value="${i + 1}" ${state.meja === String(i + 1) ? 'selected' : ''}>Meja ${i + 1}</option>`
  ).join('');

  kerangka('Keranjang', `
    <h1 class="page-title">Keranjang</h1>
    <p class="welcome-text">Periksa pesanan sebelum dikirim</p>

    ${baris.length === 0 ? '<p class="description">Keranjang masih kosong. Pilih menu terlebih dahulu.</p>' : `
      ${baris.map(x => `
        <div class="cart-item" data-id="${x.menu.id}">
          <div>
            <div class="product-name">${escapeHTML(x.menu.nama)}</div>
            <div class="product-price">${rupiah(x.menu.harga)}</div>
          </div>
          <div class="quantity-control">
            <button data-aksi="kurang">−</button>
            <span>${x.jml}</span>
            <button data-aksi="tambah">+</button>
          </div>
        </div>`).join('')}

      <div class="summary-row"><span>Subtotal</span><b>${rupiah(subtotal)}</b></div>
      <p class="description">Service 5% dan pajak 10% dihitung pada struk.</p>

      <label class="field-label">Nomor meja</label>
      <select id="pilih-meja" class="input-field">${opsiMeja}</select>

      <label class="field-label">Metode bayar (hanya dicatat)</label>
      <select id="pilih-metode" class="input-field">
        <option ${state.metode === 'Tunai' ? 'selected' : ''}>Tunai</option>
        <option ${state.metode === 'QRIS' ? 'selected' : ''}>QRIS</option>
      </select>

      <p id="pesan-error" class="error-text">${escapeHTML(pesanError)}</p>
      <button id="btn-pesan" class="btn-primary">Pesan Sekarang</button>
    `}
  `);

  if (baris.length === 0) return;

  app.querySelectorAll('.cart-item [data-aksi]').forEach(tombol => {
    tombol.addEventListener('click', () => {
      const id = tombol.closest('.cart-item').dataset.id;
      const baru = (state.keranjang[id] || 0) + (tombol.dataset.aksi === 'tambah' ? 1 : -1);
      if (baru <= 0) delete state.keranjang[id]; else state.keranjang[id] = baru;
      tampilkanKeranjang();
    });
  });

  app.querySelector('#pilih-meja').addEventListener('change', e => (state.meja = e.target.value));
  app.querySelector('#pilih-metode').addEventListener('change', e => (state.metode = e.target.value));

  const tombolPesan = app.querySelector('#btn-pesan');
  const pesan = app.querySelector('#pesan-error');

  tombolPesan.addEventListener('click', async () => {
    tombolPesan.disabled = true;
    tombolPesan.textContent = 'Memproses...';
    pesan.textContent = '';

    try {
      const hasil = await requestAPI('pesanan', {
        nomor_meja: Number(state.meja),
        metode_bayar: state.metode,
        items: baris.map(x => ({ menu_id: Number(x.menu.id), jumlah: x.jml }))
      }, 'POST', ambilToken());

      state.keranjang = {};
      tampilkanStruk(hasil.data.id, hasil.message || 'Pesanan berhasil dibuat.');

    } catch (error) {
      if (sesiBerakhir(error)) return;
      pesan.textContent = error.message;
      tombolPesan.disabled = false;
      tombolPesan.textContent = 'Pesan Sekarang';
    }
  });
}

// ===== RIWAYAT PESANAN =====
async function tampilkanPesanan() {
  kerangka('Pesanan', `
    <h1 class="page-title">Pesanan Saya</h1>
    <p class="welcome-text">Ketuk pesanan untuk melihat struk</p>
    <div id="daftar-pesanan"><p class="description">Memuat pesanan...</p></div>
  `);

  const wadah = app.querySelector('#daftar-pesanan');

  try {
    const hasil = await requestAPI('pesanan', null, 'GET', ambilToken());
    const daftar = hasil.data || [];

    if (daftar.length === 0) {
      wadah.innerHTML = '<p class="description">Belum ada pesanan.</p>';
      return;
    }

    wadah.innerHTML = daftar.map(p => `
      <div class="order-card" data-id="${p.id}">
        <div>
          <div class="product-name">Pesanan #${p.id} • Meja ${escapeHTML(p.nomor_meja ?? '-')}</div>
          <div class="description" style="margin:4px 0 0">${escapeHTML(p.waktu)} • ${rupiah(p.total)}</div>
        </div>
        <span class="status-pill status-${escapeHTML(String(p.status).toLowerCase())}">${escapeHTML(p.status)}</span>
      </div>`).join('');

    wadah.querySelectorAll('.order-card').forEach(card =>
      card.addEventListener('click', () => tampilkanStruk(card.dataset.id))
    );

  } catch (error) {
    if (sesiBerakhir(error)) return;
    wadah.innerHTML = `<p class="error-text">${escapeHTML(error.message)}</p>`;
  }
}

// ===== STRUK =====
async function tampilkanStruk(id, pesanSukses = '') {
  kerangka('Pesanan', '<p class="description">Memuat struk...</p>');

  try {
    const hasil = await requestAPI(`pesanan/${id}`, null, 'GET', ambilToken());
    const p = hasil.data;

    kerangka('Pesanan', `
      <button id="btn-kembali" class="btn-back">← Daftar Pesanan</button>
      <h1 class="page-title">Struk #${p.id}</h1>
      <p class="welcome-text">${escapeHTML(p.waktu)} • Meja ${escapeHTML(p.nomor_meja ?? '-')} • ${escapeHTML(p.metode_bayar)}</p>

      ${pesanSukses ? `<p class="success-text">${escapeHTML(pesanSukses)}</p>` : ''}

      <div class="summary-row">
        <span>Status</span>
        <span class="status-pill status-${escapeHTML(String(p.status).toLowerCase())}">${escapeHTML(p.status)}</span>
      </div>

      <div class="struk-box">
        ${(p.items || []).map(i => `
          <div class="summary-row">
            <span>${escapeHTML(i.nama)}<br><small>${i.jumlah} × ${rupiah(i.harga_satuan)}</small></span>
            <span>${rupiah(i.subtotal)}</span>
          </div>`).join('')}

        <div class="struk-total">
          <div class="summary-row"><span>Subtotal</span><span>${rupiah(p.subtotal)}</span></div>
          <div class="summary-row"><span>Service 5%</span><span>${rupiah(p.service)}</span></div>
          <div class="summary-row"><span>Pajak 10%</span><span>${rupiah(p.pajak)}</span></div>
          <div class="summary-row grand"><span>Total</span><span>${rupiah(p.total)}</span></div>
        </div>
      </div>

      <button id="btn-segarkan" class="btn-outline">Perbarui Status</button>
    `);

    app.querySelector('#btn-kembali').addEventListener('click', tampilkanPesanan);
    app.querySelector('#btn-segarkan').addEventListener('click', () => tampilkanStruk(id));

  } catch (error) {
    if (sesiBerakhir(error)) return;
    kerangka('Pesanan', `
      <button id="btn-kembali" class="btn-back">← Daftar Pesanan</button>
      <p class="error-text">${escapeHTML(error.message)}</p>`);
    app.querySelector('#btn-kembali').addEventListener('click', tampilkanPesanan);
  }
}

// ===== PROFIL =====
function tampilkanProfil(pesanSukses = '') {
  kerangka('Profil', `
    <h1 class="page-title">Profil</h1>
    <p class="welcome-text">${escapeHTML(state.nama)}</p>
    ${pesanSukses ? `<p class="success-text">${escapeHTML(pesanSukses)}</p>` : ''}
    <button id="btn-ganti" class="btn-outline">Ganti Password</button>
    <button id="btn-logout" class="btn-primary">Keluar</button>
  `);

  app.querySelector('#btn-ganti').addEventListener('click', () => tampilkanGantiPassword(false));

  app.querySelector('#btn-logout').addEventListener('click', async () => {
    try { await requestAPI('logout', null, 'POST', ambilToken()); } catch (e) { /* tetap keluar */ }
    keLogin('Anda sudah keluar.');
  });
}

// ===== GANTI PASSWORD (wajib = true setelah reset oleh Kasir) =====
function tampilkanGantiPassword(wajib = false) {
  const isi = `
    <h1 class="page-title">Ganti Password</h1>
    <p class="welcome-text">${wajib
      ? 'Anda memakai password sementara. Ganti password sebelum melanjutkan.'
      : 'Ubah password akun Anda.'}</p>

    <form class="login-form" id="form-ganti">
      <label>Password Lama</label>
      <input class="input-field" name="password_lama" type="password" required>

      <label>Password Baru (minimal 8 karakter)</label>
      <input class="input-field" name="password_baru" type="password" minlength="8" required>

      <p id="pesan-error" class="error-text"></p>
      <button class="btn-primary" type="submit">Simpan</button>
      ${wajib ? '<button type="button" id="btn-keluar" class="btn-outline">Keluar</button>' : ''}
    </form>`;

  if (wajib) {
    app.innerHTML = `<main class="mobile-frame menu-page"><header class="app-header">
      <span class="brand-name">KOPI SENJA</span></header>
      <section class="main-content">${isi}</section></main>`;
    app.querySelector('#btn-keluar').addEventListener('click', () => keLogin());
  } else {
    kerangka('Profil', isi);
  }

  app.querySelector('#form-ganti').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const tombol = form.querySelector('button[type="submit"]');
    const pesan = app.querySelector('#pesan-error');
    const data = Object.fromEntries(new FormData(form).entries());

    tombol.disabled = true;
    pesan.textContent = '';

    try {
      await requestAPI('ganti-password', data, 'POST', ambilToken());
      localStorage.setItem('wajib_ganti_password', 'false');
      if (wajib) tampilkanBeranda(); else tampilkanProfil('Password berhasil diganti.');
    } catch (error) {
      if (error.status === 401) return keLogin('Sesi berakhir, silakan login kembali.');
      pesan.textContent = error.message;
      tombol.disabled = false;
    }
  });
}

// ===== HALAMAN LOGIN DAN DAFTAR =====
function tampilkanHalaman(halaman = 'login', info = '') {
  app.innerHTML = `
    <main class="mobile-frame">

      <div class="login-logo-box">
        <div class="logo-placeholder"></div>
        <h1>Cafe UKK</h1>
      </div>

      <section class="login-container">

        <h2>${halaman === 'login' ? 'Masuk' : 'Daftar Akun'}</h2>

        <p class="description">
          ${halaman === 'login'
            ? 'Silakan masuk ke akun pelanggan Anda.'
            : 'Buat akun untuk mulai memesan.'}
        </p>

        ${info ? `<p class="success-text">${escapeHTML(info)}</p>` : ''}

        <form class="login-form" id="auth-form">

          ${halaman === 'daftar' ? `
            <label>Nama Lengkap</label>
            <input class="input-field" name="nama" type="text" placeholder="Masukkan nama lengkap" required>
          ` : ''}

          <label>Nomor HP</label>
          <input class="input-field" name="nomor_hp" type="tel" placeholder="Masukkan nomor HP" required>

          <label>Password</label>
          <input class="input-field" name="password" type="password" placeholder="Masukkan password" minlength="8" required>

          ${halaman === 'daftar' ? `
            <label>Konfirmasi Password</label>
            <input class="input-field" name="konfirmasi" type="password" placeholder="Ulangi password" minlength="8" required>
          ` : ''}

          <p id="pesan-error" class="error-text"></p>

          <button class="btn-primary" type="submit">
            ${halaman === 'login' ? 'Masuk' : 'Daftar'}
          </button>

        </form>

        <p class="switch-page">
          ${halaman === 'login' ? 'Belum punya akun?' : 'Sudah punya akun?'}
          <span data-page="${halaman === 'login' ? 'daftar' : 'login'}">
            ${halaman === 'login' ? 'Daftar' : 'Masuk'}
          </span>
        </p>

        <details class="server-box">
          <summary>Alamat server</summary>
          <input class="input-field" id="alamat-server" value="${escapeHTML(getApiUrl())}">
          <p class="description" style="margin:6px 0 0">Di HP isi dengan IP laptop, contoh http://192.168.1.10:8000/api</p>
        </details>

      </section>
    </main>
  `;

  app.querySelector('[data-page]').addEventListener('click', () => {
    tampilkanHalaman(halaman === 'login' ? 'daftar' : 'login');
  });

  app.querySelector('#auth-form').addEventListener('submit', async event => {
    event.preventDefault();

    const form = event.currentTarget;
    const tombol = form.querySelector('button[type="submit"]');
    const pesan = app.querySelector('#pesan-error');
    const data = Object.fromEntries(new FormData(form).entries());

    setApiUrl(app.querySelector('#alamat-server').value);

    if (halaman === 'daftar' && data.password !== data.konfirmasi) {
      pesan.textContent = 'Konfirmasi password tidak sama.';
      return;
    }

    tombol.disabled = true;
    tombol.textContent = 'Memproses...';
    pesan.textContent = '';

    try {
      if (halaman === 'daftar') {
        await requestAPI('daftar', {
          nama: data.nama,
          nomor_hp: data.nomor_hp,
          password: data.password
        });

        tampilkanHalaman('login', 'Pendaftaran berhasil. Silakan login.');

      } else {
        const hasil = await requestAPI('login', {
          nomor_hp: data.nomor_hp,
          password: data.password
        });

        localStorage.setItem('token_pelanggan', hasil.token);
        localStorage.setItem('nama_pelanggan', hasil.pengguna.nama);
        localStorage.setItem('wajib_ganti_password', hasil.wajib_ganti_password ? 'true' : 'false');
        state.nama = hasil.pengguna.nama;

        if (hasil.wajib_ganti_password) tampilkanGantiPassword(true);
        else tampilkanBeranda();
      }

    } catch (error) {
      pesan.textContent = error.message;

    } finally {
      if (document.body.contains(tombol)) {
        tombol.disabled = false;
        tombol.textContent = halaman === 'login' ? 'Masuk' : 'Daftar';
      }
    }
  });
}

// ===== HALAMAN AWAL =====
if (ambilToken()) {
  if (localStorage.getItem('wajib_ganti_password') === 'true') tampilkanGantiPassword(true);
  else tampilkanBeranda();
} else {
  tampilkanHalaman();
}