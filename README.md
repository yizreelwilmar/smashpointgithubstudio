# 🏸 POWERSHOOT BADMINTON — Badminton Community & Mabar Management System

Sistem manajemen komunitas bulutangkis mandiri (*self-hosted ready*) yang dirancang khusus dalam **1 root folder** di Laragon (`C:\laragon\www\smashpoint\`) untuk mengelola operasional mabar rutin tanpa kertas atau catatan manual.

Sistem ini terbagi dalam 3 antarmuka yang terintegrasi penuh:
1. **Landing Page Komunitas** (Informasi profil, jadwal mabar mingguan multi-cabang/hari, galeri dokumentasi, leaderboard poin teratas, dan artikel klub).
2. **Aplikasi Operasional Mabar (`/app/`)** (Rotasi otomatis pasangan ganda di lapangan, flag pembayaran lunas/belum lunas per mabar dengan kalkulasi kas, leaderboard akumulasi total poin, dan arsip riwayat mabar).
3. **CMS Panel Admin (`/admin/`)** (Kelola foto galeri dokumentasi, buat & edit jadwal mabar multi-cabang dengan nomor WhatsApp PIC, tulis artikel aturan main, serta kelola roster member).

---

## 🏛️ Arsitektur 1 Root Folder & Subdomain Routing

Semua kode aplikasi dibungkus rapi dalam 1 folder root:
```
C:\laragon\www\smashpoint\
```

Ketika di-hosting di server/rumah, Anda cukup mengarahkan DNS record atau virtual host Apache ke subdomain masing-masing:

| Subdomain Produksi | Folder / Route | Fungsi Utama |
| :--- | :--- | :--- |
| **`powershootbadminton.com`** *(Domain Utama)* | `.../views/landing/` atau root `/` | **Landing Page Publik**: Profil, 4 sesi jadwal mingguan, galeri foto, top 5 leaderboard, dan artikel komunitas. |
| **`app.powershootbadminton.com`** | `.../app/` *(atau `?view=app`)* | **Aplikasi Operasional**: Papan rotasi lapangan, papan skor, **flag pembayaran peserta (lunas/pending)**, dan leaderboard. |
| **`admin.powershootbadminton.com`** | `.../admin/` *(atau `?view=admin`)* | **CMS Pengurus**: Tambah/hapus galeri, atur jadwal sesi cabang, tulis artikel, dan reset musim poin. |
| **API Endpoints** | `.../api/` | **REST API**: Rotasi ronde otomatis, simpan skor, toggle status bayar, toggle kehadiran, quick add pemain. |

### 🚀 Cara Akses di Laragon Lokal (Port 8080)
Sistem sudah dilengkapi **Top Navigation Pill Bar** di bagian atas setiap halaman untuk berpindah antar antarmuka dengan 1 klik:
- **Landing Page**: [http://localhost:8080/smashpoint/](http://localhost:8080/smashpoint/)
- **Aplikasi Operasional Mabar**: [http://localhost:8080/smashpoint/app/](http://localhost:8080/smashpoint/app/)
- **CMS Admin**: [http://localhost:8080/smashpoint/admin/](http://localhost:8080/smashpoint/admin/)
  - **Username**: `admin`
  - **Password**: `admin123`

---

## 🏸 Fitur Utama Sistem

### 1. Multi-Branch Mabar Scheduling (3-4x Seminggu)
Komunitas bulutangkis aktif biasanya mengadakan sesi mabar di beberapa hari dan GOR berbeda dalam satu minggu:
- **Rabu Malam**: GOR Smash Tebet (20:00 - 23:00) • PIC: Capt. Hendra (WhatsApp)
- **Jumat Malam**: GOR Supreme Pancoran (19:00 - 22:00) • PIC: Coach Kevin (WhatsApp)
- **Sabtu Pagi**: Champion Badminton Fatmawati (08:00 - 11:00) • PIC: Fajar (WhatsApp)
- **Minggu Senja**: Cilandak Arena Sports (16:00 - 19:00) • PIC: Rian (WhatsApp)

Setiap jadwal memiliki kuota peserta, nominal iuran kok/lapangan, link Google Maps venue, dan tombol langsung chat WhatsApp ke PIC sesi tersebut.

### 2. Flag Pembayaran Peserta & Rekap Kas Real-Time (`/app/?view=payments`)
Pengurus/kapten tidak perlu mencatat uang masuk di secarik kertas:
- **Toggle 1-Klik**: Ubah status pemain dari *Belum Bayar* menjadi **LUNAS (Paid)** seketika via AJAX.
- **Kartu Ringkasan Keuangan Sesi**:
  - Total Peserta Terdaftar
  - Peserta Hadir di GOR
  - Jumlah Sudah Lunas vs Belum Lunas
  - **Total Kas Terkumpul (Cash In)**
  - **Total Kas Tertunda (Pending Out)**

### 3. Papan Rotasi Lapangan Cerdas (`/app/?view=court`)
- **Simulasi Visual Lapangan Ganda**: Lapangan hijau dengan net line, service box, Tim A vs Tim B.
- **Algoritma Rotasi Anti-Slop (Fair Social)**:
  - Mengacak 4 pemain per lapangan ganda secara otomatis.
  - Meminimalkan pasangan pemain yang sama berulang kali (Monte-Carlo partner matching).
  - Mengatur rotasi bangku istirahat (*bye bench queue*) secara adil bagi sesi dengan jumlah pemain ganjil atau lebih dari 4 pemain per lapangan.
  - Tersedia opsi mode **Mexicano / Ladder** (memasangkan pemain berdasarkan peringkat poin).
- **Papan Skor Sentuh (+ / -)**: Mudah ditekan langsung dari tablet atau layar smartphone di pinggir lapangan GOR.

### 4. Leaderboard Akumulasi Total Poin ("Banyak-Banyakan Poin") (`/app/?view=leaderboard`)
- Poin setiap pertandingan yang dicetak pemain otomatis diakumulasikan ke profil member (`total_points += match_score`).
- Papan peringkat mengurutkan pemain berdasarkan **Total Poin**, dilengkapi statistik rasio kemenangan (*win rate %*), total main, menang/kalah, dan selisih angka (+/-).

### 5. Panel CMS Pengurus (`/admin/`)
- **Manajemen Galeri**: Upload / input foto dokumentasi mabar dengan caption dan tanggal.
- **Manajemen Jadwal Sesi**: Buat jadwal sesi mabar baru di berbagai cabang GOR dengan kuota dan nomor WhatsApp PIC.
- **Penerbit Artikel & Aturan**: Publikasi tips bermain, aturan sopan santun komunitas, dan pengumuman turnamen internal.
- **Roster Pemain & Reset Musim**: Tambah member baru dan tombol reset leaderboard saat pergantian musim.

---

## 🗄️ Database & Instalasi

Database menggunakan MySQL dengan skema murni `powershoot_db`:

1. **Konfigurasi Database** di file [`config/database.php`](file:///C:/laragon/www/smashpoint/config/database.php):
   - Host: `127.0.0.1`
   - Port: `3306`
   - DB Name: `powershoot_db`
   - User: `root`
   - Password: `(kosong)`

2. **Migrasi & Seed Data**:
   File migrasi dan seeder awal siap dijalankan kapan saja jika ingin inisialisasi ulang:
   ```bash
   php C:\laragon\www\smashpoint\config\setup.php
   ```

---

## 📁 Struktur Direktori File

```
C:\laragon\www\smashpoint\
├── index.php                 # Master Subdomain & Query Router
├── .htaccess                 # Apache rewrite & headers
├── config/
│   ├── database.php          # Database PDO Config
│   └── setup.php             # Schema migration & mock data seeder
├── core/
│   ├── Database.php          # Singleton PDO connection (PHP 7.2+ poly-compatible)
│   ├── Auth.php              # Session-based admin authentication
│   └── RotationEngine.php    # Algoritma rotasi Fair Social & Mexicano
├── models/
│   ├── Community.php         # Model profil klub & galeri foto
│   ├── Schedule.php          # Model jadwal mabar multi-cabang
│   ├── Player.php            # Model pemain & leaderboard poin
│   ├── Participant.php       # Model peserta sesi & flag pembayaran
│   ├── MatchRecord.php       # Model pertandingan & scorekeeper
│   └── Article.php           # Model artikel & aturan mabar
├── views/
│   └── landing/
│       └── index.php         # Landing page publik Powershoot Badminton
├── app/
│   └── index.php             # Aplikasi mabar: Rotasi lapangan, Pembayaran, Leaderboard, Riwayat
├── admin/
│   └── index.php             # CMS panel: Galeri, Jadwal cabang, Artikel, Roster
├── api/
│   └── index.php             # REST API Controller (JSON & Form-Data compatible)
└── shared/
    └── assets/
        └── css/style.css     # Styling tema athletic emerald & court board
```

---

## 🛠️ Kompatibilitas Sistem
- **Server**: Apache (Laragon default port `8080`)
- **PHP**: Kompatibel penuh dengan PHP 7.2 hingga PHP 8.3 (syntax clean, no typed properties or arrow functions yang menyebabkan parse error pada versi legacy)
- **Database**: MySQL 5.7 / 8.0 / MariaDB
- **UI Framework**: Tailwind CSS (CDN) + Lucide Icons + Custom Athletic Dark Theme
