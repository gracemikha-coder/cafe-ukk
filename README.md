## 1. Ringkasan
**Kopi Senja** adalah sistem pengelolaan untuk kedai Kopi Senja milik Kak Rani. Penggunanya ada tiga peran: **Manajer** yang mengelola bahan, menu, resep, stok masuk, dan membaca laporan, **Pelanggan** yang membuat pesanan, mengubah status pesanan dan mereset password pelanggan(belum jadi) lewat tampilan web , **pelanggan** yang memesan sendiri lewat APK(baru sampai login dan masuk ke halaman tampilan menu belum sampai selesai)

Masalah utama klien : Pesanan masih ditulis di kertas, stok bahan baru dihitung setelah kedai tutup sehingga sering salah, harga bahan berubah ubah sehingga biaya pokok menu tidak jelas, dan data bahan, menu, serta resep tersebar di berkas Excel yang tidak rapi.  

Alur kerja yang saya bangun: Manajer mengisi bahan dan harganya (bisa diperbarui dari layanan Supplier), lalu menyusun menu dan resepnya, sehingga **HPP dan margin** tiap menu dihitung otomatis. Saat Kasir membuat pesanan, sistem memeriksa stok bahan dari resep (kebutuhan gabungan seluruh item), mengurangi stok, dan membuat struk berisi service 5% dan pajak 10%. Pesanan berjalan Baru, Diproses, Selesai, dan hanya bisa dibatalkan saat Baru (stok dikembalikan). Manajer melihat laporan penjualan per menu dan bahan yang hampir habis.

## 2. Teknologi

| Bagian | Pilihan | Alasan singkat |
|---|---|---|
| Bahasa dan framework server | PHP 8.3 dan Laravel 13 | Menyediakan routing, migrasi, validasi, dan hak akses (middleware) bawaan |
| Database | MySQL (lewat XAMPP) | Database relasional, sesuai syarat soal |
| Tampilan web | Blade, CSS, dan sedikit JavaScript | Halaman Kasir (keranjang dan hitung biaya) berjalan di sisi browser dan divalidasi ulang di server |
| Layanan Supplier | HTTP client bawaan Laravel | Memanggil layanan dari server, bukan dari browser |
| APK | Ionic capicitor vite | baru hanya login dan menampilkan daftarmenu  |

## 3. Cara menjalanka serve 
Langkah ini dibuat dan diuji di **Windows** dengan **XAMPP** (PHP 8.3 dan MySQL) dan **Composer**.

1. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buat database kosong bernama `cafe` (lewat phpMyAdmin, `http://localhost/phpmyadmin`).
3. Ambil kode dan pasang kebutuhan:
   ```bash
   git clone https://github.com/gracemikha-coder/cafe-ukk.git
   cd cafe-ukk
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
4. Buka berkas `.env`, lalu atur database dan layanan Supplier:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cafe
   DB_USERNAME=root
   DB_PASSWORD=

   LAYANAN_URL=(alamat layanan Varian C dari penguji, berakhir di /kafe)
   LAYANAN_API_KEY=(API key dari penguji)
   ```
   Alamat dan API key hanya ditulis di `.env`, tidak ada di kode. `.env` ada di `.gitignore` dan tidak ikut repositori.
5. Jalankan migrasi dan seeder dasar (akun staf dan kategori):
   ```bash
   php artisan migrate --seed
   ```
6. Impor data awal CSV (bahan, menu, resep):
   ```bash
   php artisan impor:data-awal
   ```
7. Jalankan seeder data demo (keadaan awal demo):
   ```bash
   php artisan db:seed --class=DemoSeeder
   ```
8. Jalankan server, lalu buka `http://127.0.0.1:8000`:
   ```bash
   php artisan serve

## 4 Cara memasang APK
APK ada di folder `apk/` (file `cafe-ukk.apk`, versi debug). Syarat: HP Android 8.0 (API 26) atau lebih baru.

**Memasang**
1. Salin `apk/cafe-ukk.apk` ke HP (kabel USB, Google Drive, atau pesan WhatsApp). Jika WhatsApp menolak file `.apk`, ganti nama jadi `.zip` lalu kembalikan ke `.apk` di HP.
2. Buka file tersebut di HP, lalu ketuk **Install**. Jika muncul peringatan sumber tidak dikenal atau Play Protect, izinkan atau pilih **Install anyway**.

**Mengatur alamat server**
1. Pastikan server sudah berjalan dengan `php artisan serve --host=0.0.0.0` (lihat bagian 3). Opsi `--host=0.0.0.0` wajib agar HP bisa menjangkau server.
2. Pastikan HP dan komputer server berada di **Wi-Fi yang sama** (atau HP tersambung ke hotspot komputer/HP lain yang sama).
3. Cari IP komputer server: jalankan `ipconfig` (Windows) atau `ip a` (Linux), lalu lihat IPv4 pada adapter Wi-Fi, misalnya `192.168.1.7`.
4. Buka aplikasi, pada layar login buka bagian **Alamat server**, lalu isi dengan IP tersebut, contoh `192.168.1.7:8000`. Aplikasi otomatis melengkapinya menjadi `http://192.168.1.7:8000/api`. Alamat ini tersimpan, jadi tidak perlu membangun ulang APK.
5. Login memakai akun uji pada bagian 5, atau daftar akun pelanggan baru.

**Catatan penting**
- **Jangan** memakai `localhost` atau `127.0.0.1` sebagai alamat server: di HP, alamat itu berarti HP itu sendiri, bukan server, sehingga koneksi gagal.
- Jika muncul pesan "Tidak dapat terhubung ke server", periksa: (a) HP dan komputer satu Wi-Fi, (b) server berjalan dengan `--host=0.0.0.0`, (c) firewall komputer mengizinkan port 8000 (atau nonaktifkan sementara untuk uji), (d) IP komputer belum berubah.
- Uji cepat koneksi: buka `http://<IP-komputer>:8000/api/menu` di browser HP. Jika muncul `{"message":"Silakan login terlebih dahulu."}`, koneksi sudah benar.

## 5. Akun Uji

| Peran | Username / No HP | Password | Dipakai di |
|---|---|---|---|
| Kasir | kasir | kasir12345 | Web |
| Manajer | manajer | manajer12345 | Web |
| Pelanggan |  |  |  |

Password di atas khusus untuk uji.

## 6. Letak berkas
cafe-ukk/
│
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── ImporDataAwal.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthApiController.php
│   │   │   │   ├── MenuApiController.php
│   │   │   │   └── PesananApiController.php
│   │   │   ├── AuthController.php
│   │   │   ├── BahanController.php
│   │   │   ├── KasirPelangganController.php
│   │   │   ├── KasirPesananController.php
│   │   │   ├── LaporanController.php
│   │   │   ├── MenuController.php
│   │   │   ├── ResepController.php
│   │   │   ├── StokMasukController.php
│   │   │   └── SupplierController.php
│   │   │
│   │   └── Middleware/
│   │       ├── ApiToken.php
│   │       └── RoleMiddleware.php
│   │
│   ├── Models/
│   │   ├── Bahan.php
│   │   ├── ItemPesanan.php
│   │   ├── Kategori.php
│   │   ├── Menu.php
│   │   ├── PemakaianBahan.php
│   │   ├── Pesanan.php
│   │   ├── ResepItem.php
│   │   ├── StokMasuk.php
│   │   └── User.php
│   │
│   ├── Services/
│   │   ├── ImporDataAwalService.php
│   │   ├── PesananService.php
│   │   └── SupplierService.php
│   │
│   └── Providers/
│       └── AppServiceProvider.php
│
├── database/
│   ├── data-awal/
│   │   ├── bahan.csv
│   │   ├── master_kategori_menu.csv
│   │   ├── menu.csv
│   │   ├── resep.csv
│   │   └── seed_stok_demo.csv
│   │
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_11_13_045407_create_bahans_table.php
│   │   ├── 2026_11_13_051500_create_kategoris_table.php
│   │   ├── 2026_11_13_051522_create_menus_table.php
│   │   ├── 2026_11_13_051550_create_resep_items_table.php
│   │   ├── 2026_11_13_051559_create_stok_masuks_table.php
│   │   ├── 2026_11_13_051608_create_pesanans_table.php
│   │   ├── 2026_11_13_051617_create_item_pesanans_table.php
│   │   ├── 2026_11_13_051625_create_pemakaian_bahans_table.php
│   │   └── 2026_11_17_000001_add_api_token_to_users_table.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── DemoSeeder.php
│
├── resources/
│   └── views/
│       ├── auth/
│       ├── kasir/
│       ├── layouts/
│       └── manajer/
│
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
│
├── mobile/
│   ├── src/
│   │   ├── assets/
│   │   ├── api.js
│   │   ├── main.js
│   │   └── style.css
│   ├── android/
│   ├── package.json
│   └── capacitor.config.json
│
├── apk/
│   └── cafe-ukk.apk
│
├── bootstrap/
├── config/
├── public/
├── storage/
├── tests/
├── artisan
├── composer.json
└── package.json

## 7. Keputusan anda

### Penanganan data awal yang belum rapi

| Masalah di CSV | Penanganan |
|---|---|
| Satuan campur (kg dan liter, padahal resep memakai g dan ml) | Diubah ke satuan dasar: kg ke g dan liter ke ml. Stok dikali 1000, harga per satuan dibagi 1000 |
| Nama bahan di resep salah ketik (`Biji kopi arabica`) | Dicocokkan ke bahan yang paling mirip (selisih 1 huruf, atau 2 huruf untuk nama panjang) bila hasilnya hanya satu |
| Bahan di resep yang tidak dikenal (`Susu oat`, `Sirup vanila`) | **Tidak diimpor**, dicatat di laporan impor di terminal |
| Batas minimum awal tidak ada | Diisi 0 |

Hasil impor: 20 bahan, 14 menu, 63 baris resep. Dua baris resep dilewati. Satu baris dikoreksi namanya. Delapan bahan dikonversi satuannya. Satuan stok demo (misalnya Susu UHT 0,3 liter) juga dikonversi, jadi menjadi 300 ml.

### Keputusan lain

- **Nomor meja** hanya untuk pesanan dari APK. Pesanan yang dibuat Kasir tidak memakai meja, jadi kolom `nomor_meja` boleh kosong. Struk dan daftar pesanan menampilkan meja hanya bila ada.
- **Biaya:** service 5% dari subtotal dan pajak 10% dari (subtotal + service), masing-masing dibulatkan ke rupiah terdekat. Contoh: subtotal Rp100.000, service Rp5.000, pajak Rp10.500, total Rp115.500.
- **Struk** menyimpan nama menu dan harga satuan saat pesanan dibuat, jadi struk lama tidak berubah bila harga menu diubah.
- **Menu** tidak dihapus, hanya dinonaktifkan. Menu tanpa resep tidak bisa dipesan.
- **Bahan** tidak bisa dihapus. Nama bahan harus unik, dan perbedaan huruf besar/kecil atau spasi di awal/akhir dianggap sama.
- **Laporan:** pesanan berstatus Batal tidak dihitung. Pendapatan memakai harga saat dipesan (sebelum service dan pajak). HPP memakai harga bahan terbaru, jadi keuntungan pesanan lama ikut menyesuaikan saat harga Supplier berubah.
- **Layanan Supplier:** harga diperbarui lewat tombol *Perbarui Harga dari Supplier* di halaman Kelola Bahan (hanya Manajer). Bahan dicocokkan lewat kode bahan, lalu lewat nama. Bila layanan tidak terhubung, API key ditolak (401), atau responsnya tidak dikenali, sistem **tidak error**: harga terakhir tetap dipakai dan tanggal pembaruan terakhirnya ditampilkan. Batas waktu tunggu 5 detik.

## 8. Lembar self-check

Klaim saya hanya untuk butir yang sudah saya uji sendiri. Butir 12-14 saya tulis **Tidak** karena APK belum dikerjakan.

| No | Butir penerimaan | Klaim | Langkah uji singkat |
|---|---|---|---|
| 1 | Kelola bahan (satuan dasar, nama tidak ganda), menu (bisa dinonaktifkan), dan resep (multi bahan, takaran) | Ya | Login Manajer. Tambah bahan dengan nama yang sudah ada (huruf beda besar/kecil), harus ditolak. Menu: klik Nonaktifkan, menu hilang dari halaman Kasir. Resep: tambah beberapa bahan dengan takaran, HPP berubah |
| 2 | Pencatatan stok masuk; batas minimum per bahan diatur Manajer, dan daftar bahan menipis langsung mengikutinya | Ya | Menu Stok Masuk, catat pembelian, stok bertambah. Ubah batas minimum bahan di atas stoknya, bahan muncul menipis di Kelola Bahan dan Laporan |
| 3 | Pesanan dengan beberapa item; stok berkurang sesuai resep x jumlah porsi; menu tanpa resep atau nonaktif tidak dapat dipesan | Ya | Login Kasir, buat pesanan dua menu, cek stok bahan di Kelola Bahan. Menu nonaktif atau tanpa resep tidak bisa dipilih |
| 4 | Pesanan ditolak bila bahan tidak cukup, kebutuhan gabungan antar item dihitung benar (butir inti) | Ya | Buat pesanan dua menu yang memakai bahan sama dengan jumlah melebihi stok gabungan, harus ditolak dengan pesan bahan yang kurang |
| 5 | Menu otomatis berstatus Habis bila bahan tidak cukup untuk 1 porsi | Ya | Kurangi stok satu bahan hingga di bawah takaran 1 porsi, menu berstatus Habis di Kasir dan halaman Menu |
| 6 | Status pesanan berjenjang, loncat ditolak; batal hanya saat Baru, hanya oleh Kasir, dan stok kembali | Ya | Daftar Pesanan: Baru ke Diproses ke Selesai. Pesanan Diproses tidak bisa dibatalkan. Batalkan pesanan Baru, stok bahan kembali |
| 7 | Struk: subtotal, service 5%, pajak 10%, total; struk lama tidak berubah bila harga jual menu diubah (butir inti) | Ya | Buka struk, cek angkanya. Ubah harga menu di halaman Menu, buka lagi struk lama, angkanya tetap |
| 8 | HPP dan margin per menu dari resep | Ya | Halaman Menu dan Resep menampilkan HPP dan margin. Ubah takaran atau harga bahan, nilainya ikut berubah |
| 9 | Laporan penjualan per menu, margin per menu, bahan menipis; hak akses sesuai peran | Ya | Manajer membuka Laporan. Kasir yang membuka alamat `/manajer/laporan`, `/manajer/menu`, `/manajer/resep`, dan lainnya ditolak (403). Manajer tidak punya halaman buat pesanan dan ubah status |
| 10 | Data awal CSV: satuan dinormalkan, nama bahan salah ketik dicocokkan, bahan tidak dikenal tidak diimpor, batas minimum awal 0 | Ya | Jalankan `php artisan impor:data-awal`. Cek laporan di terminal dan satuan bahan di Kelola Bahan. Jalankan ulang, tidak ada data ganda |
| 11 | Integrasi Supplier (dengan API key), fallback harga terakhir + tanggal | Ya | Klik Perbarui Harga dari Supplier, harga berubah. Ubah satu huruf API key atau alamat di `.env` (lalu `php artisan config:clear`), klik lagi: muncul pesan kuning, harga terakhir dan tanggalnya tetap tampil, tidak ada error |

## 9. Belum selesai atau keterbatasan 
tampilan atau UI nya gak sesuai dengan yang di perancangan ada beberapa hal yang berbeda di bagian mobile maupun web

## 10 


<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
