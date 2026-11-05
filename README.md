# 🏸 POWERSHOOT BADMINTON — Badminton Community & Mabar Management System

Sistem manajemen komunitas bulutangkis mandiri (*self-hosted ready*) yang dirancang dalam **1 root folder** untuk mengelola operasional mabar rutin tanpa kertas atau catatan manual.

Sistem ini terbagi dalam 3 antarmuka yang terintegrasi penuh:
1. **Landing Page Komunitas** (Informasi profil, jadwal mabar mingguan multi-cabang/hari, galeri dokumentasi, leaderboard poin teratas, dan artikel klub).
2. **Aplikasi Operasional Mabar (`/app/`)** (Rotasi otomatis pasangan ganda di lapangan, flag pembayaran lunas/belum lunas per mabar dengan kalkulasi kas, leaderboard akumulasi total poin, dan arsip riwayat mabar).
3. **CMS Panel Admin (`/admin/`)** (Kelola foto galeri dokumentasi, buat & edit jadwal mabar multi-cabang dengan nomor WhatsApp PIC, tulis artikel aturan main, serta kelola roster member).

---

## 🏛️ Arsitektur 1 Root Folder

Seluruh aplikasi PHP berjalan dari satu document root. Tidak perlu memisahkan subdomain per folder.

| Route | Fungsi Utama |
| :--- | :--- |
| `/` | **Landing Page Publik**: profil komunitas, jadwal mabar, galeri, leaderboard, dan artikel. |
| `/app/` | **Aplikasi PIC**: rotasi lapangan, absensi, status pembayaran, input skor, kas sesi, dan dokumentasi. |
| `/admin/` atau `/cms/` | **CMS Pengurus**: jadwal, roster, akun PIC, galeri, keuangan, pengaturan komunitas, dan SEO. |
| `/api/` | **Endpoint JSON internal** untuk aksi aplikasi. |
| `/guide.php` | **Buku panduan interaktif** dan versi cetak A4. |

### 🚀 Cara Akses di Laragon Lokal
- **Landing Page**: `http://localhost:8080/<nama-folder>/`
- **Aplikasi PIC**: `http://localhost:8080/<nama-folder>/app/`
- **CMS Admin**: `http://localhost:8080/<nama-folder>/admin/`
  - Kredensial awal: `admin` / `admin123` (wajib diganti setelah login pertama).
- **Buku Panduan**: `http://localhost:8080/<nama-folder>/guide.php`

Jika dipasang di root domain, hilangkan `<nama-folder>/`.

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
  - Tersedia 3 mode: **Kombinasi Seimbang (Fair Level)**, **Selevel**, dan **Kocok Campur Merata**.
- **Input Skor Manual**: PIC mengisi skor tiap tim lalu menekan tombol simpan; poin langsung masuk ke leaderboard.

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

1. **Konfigurasi Database** di file [`config/database.php`](config/database.php):
   - Host: `127.0.0.1`
   - Port: `3306`
   - DB Name: `powershoot_db`
   - User: `root`
   - Password: `(kosong)`

2. **Instalasi**:
   - **Produksi (bersih):** sesuaikan [`config/database.php`](config/database.php), lalu jalankan:
     ```bash
     php config/setup.php
     ```
     Installer membuat database, seluruh tabel, pengaturan awal, dan satu akun admin.
   - **Demo:** jalankan `php config/setup.php --demo`, atau impor `database_backup.sql`.
   - **Password admin awal** ditentukan oleh `SMASHPOINT_INITIAL_ADMIN_PASSWORD` di `config/setup.php` (default `admin123`) dan wajib diganti setelah login pertama.
   - Hapus atau kunci `config/setup.php` setelah instalasi selesai.

---

## 📁 Struktur Direktori File

```
C:\laragon\www\smashpoint\
├── index.php                 # Master Subdomain & Query Router
├── .htaccess                 # Apache rewrite & headers
├── config/
│   ├── database.php          # Database PDO Config
│   └── setup.php             # Installer skema lengkap + demo opsional (--demo)
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
