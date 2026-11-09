# PRIMKOP Pullahta Hankam — Redesain Website Koperasi

Proyek pengembangan ulang website PRIMKOP Pullahta Hankam di lingkungan Pusdatin Kemhan. Pengembangan mencakup halaman publik serta halaman layanan anggota, dengan tampilan responsif untuk desktop, tablet, dan ponsel.

Kode aplikasi Laravel berada di folder `koperasi-redesign/`. Semua perintah aplikasi di bawah dijalankan dari folder tersebut.

## Status pengembangan

Proyek masih dalam pengembangan. Tabel ini menggambarkan kode pada branch halaman publik, bukan seluruh fitur website lama yang sudah selesai dipindahkan.

| Bagian | Status |
| --- | --- |
| Beranda | Tampilan tersedia: hero, layanan, kategori, profil singkat, dan berita. |
| Tentang | Profil dan struktur pengurus tersedia. |
| Berita | Daftar, berita utama, paginasi, dan detail berita tersedia. |
| Kontak | Informasi kontak dan peta tersedia. |
| Produk | Antarmuka pencarian dan filter tersedia; data produk belum terhubung dan halaman masih dikembangkan. |
| Keranjang dan pemesanan | Belum selesai; belum tersedia alur transaksi yang terintegrasi pada branch ini. |
| Halaman anggota dan autentikasi | Dikerjakan terpisah pada branch anggota; belum terintegrasi pada branch publik ini. |
| Integrasi data koperasi | Belum terhubung ke database operasional website lama. |

Konten profil, berita, kategori, dan struktur pengurus saat ini berasal dari konfigurasi aplikasi. Data produk, akun anggota, simpanan, pinjaman, SHU, dan transaksi belum boleh dianggap sebagai data operasional yang sudah terintegrasi.

## Tech stack

| Kebutuhan | Teknologi |
| --- | --- |
| Framework aplikasi | Laravel 13 |
| Bahasa server | PHP 8.4 pada lingkungan pengembangan; persyaratan Composer minimal PHP 8.3 |
| Template | Laravel Blade dan komponen Blade |
| Styling | Tailwind CSS 4 dan CSS khusus proyek |
| Interaksi | JavaScript ES Modules |
| Build aset | Vite 8 dan Laravel Vite Plugin |
| Pengelola dependensi | Composer dan npm |
| Pengujian | PHPUnit 12 melalui Laravel Artisan |
| Format kode PHP | Laravel Pint |
| Database lokal awal | SQLite, sesuai `.env.example` |
| Database yang direncanakan | PostgreSQL; integrasi data koperasi belum selesai |

Laravel menangani routing dan rendering halaman. Penggunaan framework ini belum berarti seluruh backend layanan koperasi sudah diimplementasikan. PostgreSQL merupakan database yang direncanakan, bukan pengganti Laravel.

## Kebutuhan lokal

- PHP 8.3 atau lebih baru; proyek dikembangkan menggunakan PHP 8.4.
- Composer.
- Node.js yang kompatibel dengan Vite 8: `20.19+` pada seri 20, atau `22.12+`. Node.js 24 juga dapat digunakan.
- npm dan Git.
- Ekstensi PHP sesuai kebutuhan Laravel, termasuk PDO SQLite untuk konfigurasi database lokal awal.

XAMPP tidak diperlukan jika PHP sudah tersedia di terminal.

## Instalasi pertama setelah clone

Dari folder utama repository:

```powershell
cd koperasi-redesign
composer install
npm ci
```

Buat konfigurasi lokal jika belum ada:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

`key:generate` cukup dilakukan saat penyiapan awal dengan `APP_KEY` yang masih kosong. Jangan mengganti key aplikasi yang sudah digunakan tanpa kebutuhan yang jelas.

Konfigurasi awal menggunakan SQLite. Siapkan file database lokal dan tabel bawaan Laravel:

```powershell
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File -Path database/database.sqlite
}
php artisan migrate
```

Migrasi tersebut menyiapkan tabel bawaan seperti pengguna, sesi, cache, dan antrean. Migrasi tidak mengimpor data koperasi lama.

Pastikan `APP_URL` di `.env` sesuai alamat server, misalnya `http://127.0.0.1:8000`. Jika memakai PostgreSQL nanti, sesuaikan koneksi dan kredensial database pada `.env` masing-masing; jangan commit kredensial tersebut.

## Menjalankan proyek

### Melihat hasil build

Cara ini dipakai untuk menjalankan tampilan tanpa proses Vite yang terus aktif:

```powershell
npm run build
php artisan serve
```

Buka **http://127.0.0.1:8000** atau alamat yang tercetak pada terminal Laravel. Biarkan terminal server tetap berjalan.

Setelah mengubah CSS atau JavaScript, jalankan `npm run build` lagi, kemudian refresh browser. Perubahan Blade biasanya langsung terlihat saat refresh tanpa build ulang; jika menambahkan kelas utility Tailwind baru, build ulang asetnya.

Jika sebelumnya memakai Vite, hentikan proses `npm run dev` dengan `Ctrl + C` sebelum memakai mode build. Jika proses berhenti tidak normal dan meninggalkan file `public/hot`, hapus penanda lokal tersebut:

```powershell
Remove-Item -LiteralPath public/hot -ErrorAction SilentlyContinue
```

### Mengembangkan tampilan dengan live reload

Buka dua terminal di folder `koperasi-redesign`.

Terminal pertama:

```powershell
php artisan serve
```

Terminal kedua:

```powershell
npm run dev
```

Tetap buka alamat Laravel di browser, bukan alamat Vite. Vite menyediakan CSS/JavaScript dan pembaruan aset saat pengembangan. Tekan `Ctrl + C` pada masing-masing terminal untuk menghentikannya.

`composer install` dan `npm ci` tidak perlu diulang setiap kali menjalankan aplikasi. Jalankan lagi ketika penyiapan dependensi memang diperlukan, misalnya setelah perubahan file lock dari rekan tim.

## Struktur proyek

```text
koperasi-redesign/
├── app/                         # Kode aplikasi dan model
├── config/
│   ├── news.php                 # Konten berita
│   ├── organization.php         # Data struktur pengurus
│   └── product_categories.php   # Data kategori produk
├── database/migrations/         # Skema database
├── public/assets/               # Foto, logo, dan aset statis
├── resources/
│   ├── css/                     # Styling aplikasi
│   ├── js/                      # Modul interaksi halaman
│   └── views/                   # Layout, komponen, dan halaman Blade
├── routes/web.php               # Routing halaman web
├── tests/Feature/               # Pengujian halaman dan perilaku aplikasi
├── .env.example                 # Contoh konfigurasi lokal
├── composer.json                # Dependensi PHP
└── package.json                 # Dependensi dan perintah frontend
```

Halaman publik saat ini: `/`, `/tentang-kami`, `/produk`, `/berita`, `/berita/{slug}`, dan `/kontak`. Alamat `/tentang` diarahkan ke `/tentang-kami`.

## Kolaborasi tim

| Branch | Fokus |
| --- | --- |
| `main` | Hasil penggabungan pekerjaan yang sudah diperiksa |
| `feat/redesign-public-pages` | Halaman publik |
| `feat/redesign-anggota` | Halaman anggota |

Jika halaman publik belum masuk `main`, branch anggota dapat dibuat dari versi publik yang sudah di-push. Perintah ini hanya untuk pertama kali membuat branch tersebut:

```powershell
git fetch origin
git switch --no-track -c feat/redesign-anggota origin/feat/redesign-public-pages
```

Commit pekerjaan masing-masing di branch yang sesuai. Untuk push pertama branch anggota:

```powershell
git add .
git commit -m "feat: redesign halaman anggota"
git push -u origin feat/redesign-anggota
```

Setelah upstream disetel, push berikutnya cukup `git push`. Periksa `git status` sebelum commit agar file yang disertakan sesuai pekerjaan.

Gabungkan pekerjaan melalui Pull Request ke `main`. Jika branch anggota berangkat dari branch publik, gabungkan PR publik terlebih dahulu, kemudian PR anggota. Perubahan setelah percabangan tidak otomatis tersinkron; gabungkan pembaruan yang dibutuhkan dan selesaikan konflik bersama.

Koordinasikan perubahan pada file bersama seperti `routes/web.php`, `resources/css/app.css`, dan `resources/js/app.js`. Gunakan komponen dan modul khusus halaman anggota bila sesuai, agar pekerjaan publik dan anggota tidak saling mengganggu.

## Pengujian dan format kode

```powershell
php artisan test --compact
php vendor/bin/pint --dirty --format agent
npm run build
```

Untuk menguji bagian tertentu:

```powershell
php artisan test --compact tests/Feature/ProductCatalogTest.php
```

## Catatan pengembangan

- Gunakan konten dari website koperasi asli atau yang disediakan pemilik proyek. Jangan mengarang data, informasi kontak, angka, atau profil organisasi.
- Jika data belum tersedia, gunakan keadaan kosong yang sesuai atau sembunyikan bagiannya. Jangan menampilkan data contoh sebagai data resmi.
- `.env`, kredensial, `vendor/`, `node_modules/`, dan aset build lokal tidak dibagikan melalui commit. Ikuti aturan `.gitignore` yang tersedia.
- Konfigurasi PHP lokal, termasuk OPcache, tidak ikut terkirim melalui Git. Setiap pengembang perlu menyiapkan lingkungannya sendiri.
- Jika muncul HTTP 500, periksa pesan terminal Laravel dan `storage/logs/laravel.log`. Timeout pemuatan PHP dan Vite yang tidak berjalan perlu ditangani berdasarkan log, bukan dengan mengubah konten halaman.
- Integrasi PostgreSQL dan konfigurasi deployment masih perlu disiapkan serta diuji sebelum aplikasi digunakan untuk data atau transaksi operasional. 