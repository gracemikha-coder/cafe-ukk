import './style.css';
import '@ionic/core/css/core.css';
import '@ionic/core/css/normalize.css';
import '@ionic/core/css/structure.css';
import '@ionic/core/css/typography.css';
import { defineCustomElements } from '@ionic/core/loader';
import { requestAPI, ambilAlamatServer, simpanAlamatServer } from './api.js';

defineCustomElements(window);

const app = document.querySelector('#app');

// ===== DATA SEMENTARA DI APLIKASI =====
let semuaMenu = [];       // daftar menu terakhir dari API
const keranjang = {};     // { idMenu: jumlah }
let mejaDipilih = null;   // nomor meja 1-10
let metodeBayar = '';     // 'Tunai' atau 'QRIS'

// ===== FUNGSI BANTU =====

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

function rupiah(angka) {
  return 'Rp ' + Number(angka).toLocaleString('id-ID');
}

function ambilToken() {
  return localStorage.getItem('token_pelanggan');
}

// MENGAMBIL NAMA KATEGORI
function ambilKategori(menu) {
  if (typeof menu.kategori === 'string') {
    return menu.kategori;
  }

  return menu.kategori?.nama || menu.kategori_nama || 'Lainnya';
}

function jumlahKeranjang() {
  return Object.values(keranjang).reduce((total, jumlah) => total + jumlah, 0);
}

function kosongkanKeranjang() {
  Object.keys(keranjang).forEach(id => delete keranjang[id]);
  mejaDipilih = null;
  metodeBayar = '';
}

// MEMBERSIHKAN DATA LOGIN DI HP
function keluarLokal() {
  ['token_pelanggan', 'nama_pelanggan', 'nomor_hp', 'wajib_ganti_password']
    .forEach(kunci => localStorage.removeItem(kunci));

  kosongkanKeranjang();
  semuaMenu = [];
}

// MEMANGGIL API DENGAN TOKEN. Sesi habis -> kembali ke login.
async function panggil(endpoint, data = null, method = 'GET') {
  try {
    return await requestAPI(endpoint, data, method, ambilToken());
  } catch (error) {
    if (error.status === 401) {
      keluarLokal();
      tampilkanHalaman('login');
      error.sudahDitangani = true;
    } else if (error.status === 403 && error.data?.wajib_ganti_password) {
      localStorage.setItem('wajib_ganti_password', 'true');
      tampilkanGantiPassword(true);
      error.sudahDitangani = true;
    }
    throw error;
  }
}

function tampilkanGalat(elemen, error) {
  if (error.sudahDitangani || !elemen) return;
  elemen.textContent = error.message;
}

// ===== KERANGKA HALAMAN (header, isi, navigasi bawah) =====
function kerangka({ isi, aktif = null, kembaliKe = null }) {
  const jumlah = jumlahKeranjang();

  const itemNav = (kunci, label) => `
    <button class="nav-item ${aktif === kunci ? 'active' : ''}" data-nav="${kunci}">
      ${label}
    </button>
  `;

  app.innerHTML = `
    <main class="mobile-frame menu-page">

      <header class="app-header">
        ${kembaliKe
          ? `<button id="btn-kembali" class="nav-item">← Kembali</button>
             <span class="brand-name">KOPI SENJA</span>`
          : `<span class="brand-name">KOPI SENJA</span>
             <span class="user-role">PELANGGAN • MOBILE</span>`}
      </header>

      <section class="main-content">
        ${isi}
      </section>

      ${aktif ? `
        <nav class="bottom-nav">
          ${itemNav('menu', 'Menu')}
          ${itemNav('keranjang', jumlah ? `Keranjang (${jumlah})` : 'Keranjang')}
          ${itemNav('pesanan', 'Pesanan')}
          ${itemNav('profil', 'Profil')}
        </nav>
      ` : ''}

    </main>
  `;

  if (kembaliKe) {
    app.querySelector('#btn-kembali').addEventListener('click', kembaliKe);
  }

  app.querySelectorAll('[data-nav]').forEach(tombol => {
    tombol.addEventListener('click', () => {
      const tujuan = tombol.dataset.nav;

      if (tujuan === 'menu') tampilkanMenu();
      else if (tujuan === 'keranjang') tampilkanKeranjang();
      else if (tujuan === 'pesanan') tampilkanPesanan();
      else if (tujuan === 'profil') tampilkanProfil();
    });
  });
}

// ===== HALAMAN DAFTAR MENU =====
async function tampilkanMenu() {
  kerangka({
    aktif: 'menu',
    isi: `
      <h1 class="page-title">Daftar Menu</h1>

      <p class="welcome-text">
        Selamat datang, ${escapeHTML(localStorage.getItem('nama_pelanggan') || '')}!
      </p>

      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24"
          fill="none" stroke="currentColor" stroke-width="2">
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
    `
  });

  const daftarMenu = app.querySelector('#daftar-menu');
  const inputCari = app.querySelector('#cari-menu');
  const daftarKategori = app.querySelector('#daftar-kategori');
  let kategoriAktif = 'Semua';

  // AMBIL DATA MENU DARI API
  try {
    const hasil = await panggil('menu');

    // menu nonaktif dan menu tanpa resep tidak ditampilkan; menu Habis tetap tampil
    semuaMenu = (hasil.data || []).filter(
      menu => menu.status === 'Tersedia' || menu.status === 'Habis'
    );
  } catch (error) {
    tampilkanGalat(daftarMenu, error);
    return;
  }

  if (semuaMenu.length === 0) {
    daftarMenu.innerHTML = `<p class="description">Belum ada menu tersedia.</p>`;
    return;
  }

  // MEMBUAT DAFTAR KATEGORI
  const kategori = [...new Set(semuaMenu.map(menu => ambilKategori(menu)))];

  daftarKategori.innerHTML = `
    <button class="cat-pill active" data-category="Semua">Semua</button>
    ${kategori.map(item => `
      <button class="cat-pill" data-category="${escapeHTML(item)}">
        ${escapeHTML(item)}
      </button>
    `).join('')}
  `;

  // MENAMPILKAN CARD MENU
  function tampilkanKartu(dataMenu) {
    if (dataMenu.length === 0) {
      daftarMenu.innerHTML = `<p class="description">Menu tidak ditemukan.</p>`;
      return;
    }

    daftarMenu.innerHTML = dataMenu.map(menu => {
      const tersedia = menu.status === 'Tersedia';

      return `
        <article class="menu-card ${tersedia ? '' : 'habis'}" data-menu-id="${menu.id}">
          <div class="product-img">[ foto produk ]</div>
          <div class="product-name">${escapeHTML(menu.nama)}</div>
          <div class="product-price">${rupiah(menu.harga)}</div>
          <span class="${tersedia ? 'badge-available' : 'badge-habis'}">
            ${escapeHTML(menu.status)}
          </span>
        </article>
      `;
    }).join('');
  }

  // FILTER PENCARIAN DAN KATEGORI
  function filterMenu() {
    const kataKunci = inputCari.value.toLowerCase().trim();

    const hasilFilter = semuaMenu.filter(menu => {
      const cocokNama = String(menu.nama || '').toLowerCase().includes(kataKunci);
      const cocokKategori = kategoriAktif === 'Semua' || ambilKategori(menu) === kategoriAktif;

      return cocokNama && cocokKategori;
    });

    tampilkanKartu(hasilFilter);
  }

  inputCari.addEventListener('input', filterMenu);

  daftarKategori.addEventListener('click', event => {
    const tombol = event.target.closest('.cat-pill');
    if (!tombol) return;

    kategoriAktif = tombol.dataset.category;

    daftarKategori.querySelectorAll('.cat-pill')
      .forEach(item => item.classList.remove('active'));
    tombol.classList.add('active');

    filterMenu();
  });

  daftarMenu.addEventListener('click', event => {
    const card = event.target.closest('.menu-card');
    if (!card) return;

    tampilkanDetail(Number(card.dataset.menuId));
  });

  tampilkanKartu(semuaMenu);
}

// ===== DETAIL MENU =====
function tampilkanDetail(idMenu) {
  const menu = semuaMenu.find(item => Number(item.id) === Number(idMenu));
  if (!menu) return;

  const bisaDipesan = menu.bisa_dipesan === true;
  let jumlah = 1;

  kerangka({
    kembaliKe: tampilkanMenu,
    isi: `
      <div class="product-img detail-image">[ foto produk ]</div>

      <h1 class="page-title">${escapeHTML(menu.nama)}</h1>
      <p class="product-price detail-price">${rupiah(menu.harga)}</p>

      <p class="welcome-text">
        ${bisaDipesan ? 'Produk tersedia dan siap dipesan.' : `Status: ${escapeHTML(menu.status)}. Menu belum bisa dipesan.`}
      </p>

      <div class="quantity-section">
        <label>Jumlah Pesanan</label>
        <div class="quantity-control">
          <button id="kurangi-jumlah">−</button>
          <span id="jumlah-produk">1</span>
          <button id="tambah-jumlah">+</button>
        </div>
      </div>

      <p id="info" class="pesan-info"></p>

      <button id="tambah-keranjang" class="btn-primary" ${bisaDipesan ? '' : 'disabled'}>
        Tambahkan ke Keranjang
      </button>
    `
  });

  const jumlahProduk = app.querySelector('#jumlah-produk');
  const info = app.querySelector('#info');

  app.querySelector('#kurangi-jumlah').addEventListener('click', () => {
    if (jumlah > 1) {
      jumlah--;
      jumlahProduk.textContent = jumlah;
    }
  });

  app.querySelector('#tambah-jumlah').addEventListener('click', () => {
    jumlah++;
    jumlahProduk.textContent = jumlah;
  });

  app.querySelector('#tambah-keranjang').addEventListener('click', () => {
    keranjang[menu.id] = (keranjang[menu.id] || 0) + jumlah;
    info.textContent = `Ditambahkan. ${menu.nama} di keranjang: ${keranjang[menu.id]} porsi.`;
  });
}

// ===== KERANJANG, PILIH MEJA, PESAN =====
function tampilkanKeranjang() {
  const baris = Object.keys(keranjang)
    .map(id => ({
      menu: semuaMenu.find(item => String(item.id) === String(id)),
      id,
      jumlah: keranjang[id]
    }))
    .filter(item => item.menu);

  if (baris.length === 0) {
    kerangka({
      aktif: 'keranjang',
      isi: `
        <h1 class="page-title">Keranjang</h1>
        <p class="welcome-text">Keranjang masih kosong. Pilih menu dulu.</p>
        <button id="ke-menu" class="btn-primary">Lihat Menu</button>
      `
    });
    app.querySelector('#ke-menu').addEventListener('click', tampilkanMenu);
    return;
  }

  // perkiraan biaya (nilai akhir dihitung server)
  const subtotal = baris.reduce((total, item) => total + item.menu.harga * item.jumlah, 0);
  const service = Math.round(subtotal * 0.05);
  const pajak = Math.round((subtotal + service) * 0.10);

  kerangka({
    aktif: 'keranjang',
    isi: `
      <h1 class="page-title">Keranjang</h1>
      <p class="welcome-text">Periksa pesanan, lalu pilih meja dan cara bayar.</p>

      <div id="daftar-keranjang">
        ${baris.map(item => `
          <div class="cart-item">
            <div class="cart-info">
              <div class="product-name">${escapeHTML(item.menu.nama)}</div>
              <div class="product-price">${rupiah(item.menu.harga)}</div>
            </div>
            <div class="quantity-control">
              <button data-aksi="kurang" data-id="${item.id}">−</button>
              <span>${item.jumlah}</span>
              <button data-aksi="tambah" data-id="${item.id}">+</button>
            </div>
          </div>
        `).join('')}
      </div>

      <div class="ringkas">
        <div class="ringkas-baris"><span>Subtotal</span><span>${rupiah(subtotal)}</span></div>
        <div class="ringkas-baris"><span>Service 5%</span><span>${rupiah(service)}</span></div>
        <div class="ringkas-baris"><span>Pajak 10%</span><span>${rupiah(pajak)}</span></div>
        <div class="ringkas-baris ringkas-total"><span>Total (perkiraan)</span><span>${rupiah(subtotal + service + pajak)}</span></div>
      </div>

      <h3 class="bagian-judul">Pilih Meja</h3>
      <div class="meja-grid" id="meja-grid">
        ${Array.from({ length: 10 }, (_, i) => i + 1).map(nomor => `
          <button type="button" class="meja-btn ${mejaDipilih === nomor ? 'active' : ''}" data-meja="${nomor}">
            ${nomor}
          </button>
        `).join('')}
      </div>

      <h3 class="bagian-judul">Metode Bayar</h3>
      <div class="pilihan-grup" id="bayar-grup">
        ${['Tunai', 'QRIS'].map(nama => `
          <button type="button" class="pilihan-btn ${metodeBayar === nama ? 'active' : ''}" data-bayar="${nama}">
            ${nama}
          </button>
        `).join('')}
      </div>

      <p id="pesan-galat" class="pesan-galat"></p>

      <button id="btn-pesan" class="btn-primary">Pesan Sekarang</button>
    `
  });

  // UBAH JUMLAH
  app.querySelector('#daftar-keranjang').addEventListener('click', event => {
    const tombol = event.target.closest('[data-aksi]');
    if (!tombol) return;

    const id = tombol.dataset.id;
    const perubahan = tombol.dataset.aksi === 'tambah' ? 1 : -1;
    const baru = (keranjang[id] || 0) + perubahan;

    if (baru <= 0) delete keranjang[id];
    else keranjang[id] = baru;

    tampilkanKeranjang();
  });

  // PILIH MEJA
  app.querySelector('#meja-grid').addEventListener('click', event => {
    const tombol = event.target.closest('[data-meja]');
    if (!tombol) return;

    mejaDipilih = Number(tombol.dataset.meja);

    app.querySelectorAll('.meja-btn').forEach(item => item.classList.remove('active'));
    tombol.classList.add('active');
  });

  // PILIH METODE BAYAR
  app.querySelector('#bayar-grup').addEventListener('click', event => {
    const tombol = event.target.closest('[data-bayar]');
    if (!tombol) return;

    metodeBayar = tombol.dataset.bayar;

    app.querySelectorAll('.pilihan-btn').forEach(item => item.classList.remove('active'));
    tombol.classList.add('active');
  });

  // KIRIM PESANAN KE SERVER (server yang memeriksa meja, stok, dan aturan lain)
  const tombolPesan = app.querySelector('#btn-pesan');
  const pesan = app.querySelector('#pesan-galat');

  tombolPesan.addEventListener('click', async () => {
    tombolPesan.disabled = true;
    tombolPesan.textContent = 'Memproses...';
    pesan.textContent = '';

    try {
      const hasil = await panggil('pesanan', {
        nomor_meja: mejaDipilih,
        metode_bayar: metodeBayar || null,
        items: baris.map(item => ({ menu_id: Number(item.id), jumlah: item.jumlah }))
      }, 'POST');

      kosongkanKeranjang();
      tampilkanStruk(hasil.data.id);

    } catch (error) {
      tampilkanGalat(pesan, error);

      if (document.body.contains(tombolPesan)) {
        tombolPesan.disabled = false;
        tombolPesan.textContent = 'Pesan Sekarang';
      }
    }
  });
}

// ===== RIWAYAT PESANAN =====
async function tampilkanPesanan() {
  kerangka({
    aktif: 'pesanan',
    isi: `
      <h1 class="page-title">Pesanan Saya</h1>
      <p class="welcome-text">Ketuk pesanan untuk melihat struk dan status.</p>
      <button id="btn-muat" class="btn-secondary">Muat Ulang</button>
      <div id="daftar-pesanan"><p class="description">Memuat pesanan...</p></div>
    `
  });

  const daftar = app.querySelector('#daftar-pesanan');
  app.querySelector('#btn-muat').addEventListener('click', tampilkanPesanan);

  try {
    const hasil = await panggil('pesanan');
    const data = hasil.data || [];

    if (data.length === 0) {
      daftar.innerHTML = `<p class="description">Belum ada pesanan.</p>`;
      return;
    }

    daftar.innerHTML = data.map(item => `
      <article class="order-card" data-id="${item.id}">
        <div class="order-atas">
          <strong>Pesanan #${item.id}</strong>
          <span class="status-pil ${kelasStatus(item.status)}">${escapeHTML(item.status)}</span>
        </div>
        <div class="order-bawah">
          <span>Meja ${escapeHTML(item.nomor_meja ?? '-')} • ${escapeHTML(item.waktu)}</span>
          <strong>${rupiah(item.total)}</strong>
        </div>
      </article>
    `).join('');

    daftar.addEventListener('click', event => {
      const kartu = event.target.closest('.order-card');
      if (!kartu) return;

      tampilkanStruk(Number(kartu.dataset.id));
    });

  } catch (error) {
    tampilkanGalat(daftar, error);
  }
}

function kelasStatus(status) {
  return {
    Baru: 'status-baru',
    Diproses: 'status-proses',
    Selesai: 'status-selesai',
    Batal: 'status-batal'
  }[status] || '';
}

// ===== STRUK DAN STATUS SATU PESANAN =====
async function tampilkanStruk(idPesanan) {
  kerangka({
    kembaliKe: tampilkanPesanan,
    isi: `<p class="description">Memuat struk...</p>`
  });

  const isi = app.querySelector('.main-content');

  try {
    const hasil = await panggil(`pesanan/${idPesanan}`);
    const p = hasil.data;

    isi.innerHTML = `
      <h1 class="page-title">Struk Pesanan #${p.id}</h1>
      <p class="welcome-text">
        Status: <span class="status-pil ${kelasStatus(p.status)}">${escapeHTML(p.status)}</span>
      </p>

      <div class="struk-box">
        <div class="ringkas-baris"><span>Tanggal</span><span>${escapeHTML(p.waktu)}</span></div>
        ${p.nomor_meja ? `<div class="ringkas-baris"><span>Meja</span><span>${escapeHTML(p.nomor_meja)}</span></div>` : ''}
        <div class="ringkas-baris"><span>Metode bayar</span><span>${escapeHTML(p.metode_bayar)}</span></div>

        <hr class="garis">

        ${p.items.map(item => `
          <div class="ringkas-baris">
            <span>${escapeHTML(item.nama)} x${item.jumlah}</span>
            <span>${rupiah(item.subtotal)}</span>
          </div>
          <div class="harga-satuan">@ ${rupiah(item.harga_satuan)}</div>
        `).join('')}

        <hr class="garis">

        <div class="ringkas-baris"><span>Subtotal</span><span>${rupiah(p.subtotal)}</span></div>
        <div class="ringkas-baris"><span>Service 5%</span><span>${rupiah(p.service)}</span></div>
        <div class="ringkas-baris"><span>Pajak 10%</span><span>${rupiah(p.pajak)}</span></div>
        <div class="ringkas-baris ringkas-total"><span>Total</span><span>${rupiah(p.total)}</span></div>
      </div>

      <button id="btn-muat-struk" class="btn-secondary">Muat Ulang Status</button>
    `;

    app.querySelector('#btn-muat-struk').addEventListener('click', () => tampilkanStruk(idPesanan));

  } catch (error) {
    tampilkanGalat(isi, error);
  }
}

// ===== PROFIL =====
function tampilkanProfil() {
  kerangka({
    aktif: 'profil',
    isi: `
      <h1 class="page-title">Profil</h1>

      <div class="profil-kartu">
        <div class="profil-nama">${escapeHTML(localStorage.getItem('nama_pelanggan') || '')}</div>
        <div class="description">${escapeHTML(localStorage.getItem('nomor_hp') || '')}</div>
      </div>

      <button id="btn-ganti" class="btn-secondary">Ganti Password</button>
      <button id="btn-keluar" class="btn-danger">Keluar</button>
    `
  });

  app.querySelector('#btn-ganti').addEventListener('click', () => tampilkanGantiPassword(false));
  app.querySelector('#btn-keluar').addEventListener('click', keluar);
}

async function keluar() {
  try {
    await requestAPI('logout', null, 'POST', ambilToken());
  } catch (error) {
    // tetap keluar dari aplikasi walau server tidak terjangkau
  }

  keluarLokal();
  tampilkanHalaman('login');
}

// ===== GANTI PASSWORD =====
function tampilkanGantiPassword(wajib = false) {
  kerangka({
    kembaliKe: wajib ? null : tampilkanProfil,
    isi: `
      <h1 class="page-title">Ganti Password</h1>

      <p class="welcome-text">
        ${wajib
          ? 'Password Anda masih sementara. Ganti dulu sebelum memesan.'
          : 'Masukkan password lama dan password baru.'}
      </p>

      <form class="login-form" id="form-password">
        <label>Password Lama</label>
        <input class="input-field" name="password_lama" type="password" required>

        <label>Password Baru</label>
        <input class="input-field" name="password_baru" type="password" minlength="8" required>

        <label>Konfirmasi Password Baru</label>
        <input class="input-field" name="konfirmasi" type="password" minlength="8" required>

        <p id="pesan-galat" class="pesan-galat"></p>

        <button class="btn-primary" type="submit">Simpan Password</button>
        ${wajib ? '<button type="button" id="btn-keluar" class="btn-secondary">Keluar</button>' : ''}
      </form>
    `
  });

  if (wajib) {
    app.querySelector('#btn-keluar').addEventListener('click', keluar);
  }

  app.querySelector('#form-password').addEventListener('submit', async event => {
    event.preventDefault();

    const form = event.currentTarget;
    const tombol = form.querySelector('button[type="submit"]');
    const pesan = app.querySelector('#pesan-galat');
    const data = Object.fromEntries(new FormData(form).entries());

    if (data.password_baru !== data.konfirmasi) {
      pesan.textContent = 'Konfirmasi password tidak sama.';
      return;
    }

    tombol.disabled = true;
    tombol.textContent = 'Memproses...';
    pesan.textContent = '';

    try {
      await requestAPI('ganti-password', {
        password_lama: data.password_lama,
        password_baru: data.password_baru
      }, 'POST', ambilToken());

      localStorage.setItem('wajib_ganti_password', 'false');
      alert('Password berhasil diganti.');
      tampilkanMenu();

    } catch (error) {
      if (error.status === 401) {
        keluarLokal();
        tampilkanHalaman('login');
        return;
      }

      pesan.textContent = error.message;
      tombol.disabled = false;
      tombol.textContent = 'Simpan Password';
    }
  });
}

// ===== HALAMAN LOGIN DAN DAFTAR =====
function tampilkanHalaman(halaman = 'login') {
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

        <form class="login-form" id="auth-form">

          ${halaman === 'daftar' ? `
            <label>Nama Lengkap</label>
            <input class="input-field" name="nama" type="text"
              placeholder="Masukkan nama lengkap" required>
          ` : ''}

          <label>Nomor HP</label>
          <input class="input-field" name="nomor_hp" type="tel"
            placeholder="Masukkan nomor HP" required>

          <label>Password</label>
          <input class="input-field" name="password" type="password"
            placeholder="Masukkan password" minlength="8" required>

          ${halaman === 'daftar' ? `
            <label>Konfirmasi Password</label>
            <input class="input-field" name="konfirmasi" type="password"
              placeholder="Ulangi password" minlength="8" required>
          ` : ''}

          <p id="pesan-error" class="pesan-galat"></p>

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
          <input class="input-field" id="alamat-server" type="url"
            value="${escapeHTML(ambilAlamatServer())}">
          <button type="button" class="btn-secondary" id="simpan-server">Simpan Alamat</button>
          <p class="description" id="info-server"></p>
        </details>

      </section>
    </main>
  `;

  // SIMPAN ALAMAT SERVER (untuk HP: isi alamat IP komputer)
  app.querySelector('#simpan-server').addEventListener('click', () => {
    const tersimpan = simpanAlamatServer(app.querySelector('#alamat-server').value);
    app.querySelector('#alamat-server').value = tersimpan;
    app.querySelector('#info-server').textContent = `Tersimpan: ${tersimpan}`;
  });

  // PINDAH LOGIN / DAFTAR
  app.querySelector('[data-page]').addEventListener('click', () => {
    tampilkanHalaman(halaman === 'login' ? 'daftar' : 'login');
  });

  // PROSES FORM
  app.querySelector('#auth-form').addEventListener('submit', async event => {
    event.preventDefault();

    const form = event.currentTarget;
    const tombol = form.querySelector('button[type="submit"]');
    const pesan = app.querySelector('#pesan-error');
    const data = Object.fromEntries(new FormData(form).entries());

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

        alert('Pendaftaran berhasil. Silakan login.');
        tampilkanHalaman('login');

      } else {
        const hasil = await requestAPI('login', {
          nomor_hp: data.nomor_hp,
          password: data.password
        });

        localStorage.setItem('token_pelanggan', hasil.token);
        localStorage.setItem('nama_pelanggan', hasil.pengguna.nama);
        localStorage.setItem('nomor_hp', hasil.pengguna.nomor_hp);
        localStorage.setItem('wajib_ganti_password', String(hasil.wajib_ganti_password));

        if (hasil.wajib_ganti_password) {
          tampilkanGantiPassword(true);
        } else {
          tampilkanMenu();
        }
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
  if (localStorage.getItem('wajib_ganti_password') === 'true') {
    tampilkanGantiPassword(true);
  } else {
    tampilkanMenu();
  }
} else {
  tampilkanHalaman();
}