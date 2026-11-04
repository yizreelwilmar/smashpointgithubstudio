# Alur Selesai Sesi Mabar PIC, Linking Akun PIC, Pencarian GOR Indonesia & Animasi Badminton Hero

Menyelesaikan sesi mabar dengan rekapitulasi WhatsApp otomatis, linking akun PIC ke sesi lapangan, sistem pencarian GOR seluruh Indonesia berbasis geocoding cerdas tanpa API key, dan peningkatan visual Hero landing page dengan animasi action jump smash bulutangkis yang energetik.

## User Review & Critical Decisions

> [!IMPORTANT]
> Seluruh fitur dirancang untuk kemudahan operasional langsung di smartphone (Mobile/Android First) tanpa ketergantungan API berbayar.
> Query SQL untuk phpMyAdmin disiapkan secara aman (`IF NOT EXISTS`) sehingga tidak akan merusak data yang sudah ada di hosting.

- **Keputusan 1 (Alur Selesai Sesi PIC)**: Tombol "Selesaikan Sesi Mabar" dilengkapi konfirmasi keamanan dan modal ringkasan akhir sesi yang langsung menyediakan tombol 1-ketuk "Salin Rekap WhatsApp" untuk dikirim ke grup member.
- **Keputusan 2 (Linking Akun PIC ke Sesi)**: Form tambah/edit jadwal di Admin CMS menyediakan dropdown pilihan akun PIC terdaftar, yang otomatis mengisi nama dan nomor WhatsApp PIC. Di aplikasi lapangan (`/app/`), PIC dan Admin dapat berganti sesi lewat drawer "Pilih Sesi Mabar" tanpa perlu logout.
- **Keputusan 3 (Pencarian GOR Seluruh Indonesia)**: Menggunakan engine pencarian hibrida (database terkurasi GOR nasional + live OpenStreetMap Nominatim Indonesia) yang dapat mencari GOR di kota mana pun di Indonesia, mengisi alamat jalan lengkap, kota/cabang, dan link Google Maps secara instan tanpa biaya API.
- **Keputusan 4 (Animasi Action Badminton Hero)**: Mengintegrasikan ilustrasi vektor dinamis jump smash atlet bulutangkis (terinspirasi dari referensi Pinterest) dengan animasi CSS 60fps (shuttlecock trajectory, net perspective, motion trails) yang dapat diaktifkan atau dialihkan ke foto di Admin CMS.

---

## 1. Overview & Core Concept

1. **Alur Pasca-Selesai Sesi Mabar (PIC Courtside)**:
   - Ketika mabar selesai, PIC menekan tombol **"Selesaikan Sesi Mabar Ini"**.
   - Sistem menandai status sesi menjadi `'completed'`, mengunci pertandingan, dan memunculkan **Kartu Rekap Sesi**:
     - Total pertandingan selesai & total skor.
     - Total kas terkumpul, jumlah pemain lunas vs belum lunas.
     - Top Players / MVP (Menang terbanyak & main terbanyak).
     - **Tombol "Salin Format Rekap WhatsApp"**: Meng-copy teks rapi siap kirim ke WhatsApp grup mabar.
     - **Tombol "Pilih Sesi Mabar Lainnya"** untuk berpindah ke sesi berikutnya.
2. **Menu Linking PIC ke Sesi**:
   - Di Admin CMS (`tab=schedule_form` dan `tab=pic_accounts`), Admin dapat memilih PIC penanggung jawab untuk setiap sesi.
   - Di aplikasi lapangan (`/app/index.php`), disediakan tombol switcher **"Pilih Sesi Mabar"** di bagian atas untuk berpindah antar sesi yang ditugaskan.
3. **Pencarian GOR Seluruh Indonesia**:
   - Menggantikan batas 10 GOR Jakarta dengan live search geocoding olahraga Indonesia.
   - Ketik "GOR Djarum", "GOR Bulutangkis Bandung", "Arena Badminton Surabaya", dll., langsung muncul pilihan dengan nama resmi, alamat jalan, kecamatan, kota, dan tautan Google Maps akurat.
4. **Animasi Action Badminton Hero di Landing Page**:
   - Ilustrasi dinamis atlet badminton melompat melakukan *jump smash*, jalur laju shuttlecock (*speed trail*), garis perspektif lapangan, dan efek visual benturan raket.
   - Ringan, tajam, anti-slop, dan responsif di smartphone.

---

## 2. User Experience & Visual Design

### Key User Flows

```
[ADMIN CMS: Tambah/Edit Sesi]
  │
  ├─► Ketik Nama GOR di Seluruh Indonesia ──► Auto-Fill Nama, Alamat, Wilayah & Link Google Maps
  │
  └─► Pilih Akun PIC dari Dropdown ────────► Auto-Fill Nama & No WA PIC Penanggung Jawab
                                                     │
                                                     ▼
                                          [APLIKASI PIC COURTSIDE]
                                                     │
                                                     ├─► PIC Login ──► Memilih Sesi yang Ditugaskan
                                                     │
                                                     ├─► Jalankan Pertandingan & Rotasi Lapangan
                                                     │
                                                     └─► Klik "Selesaikan Sesi Mabar Ini"
                                                               │
                                                               ▼
                                                  [MODAL REKAP AKHIR SESI]
                                                  • Ringkasan Kas & Pertandingan
                                                  • 1-Tap "Salin Rekap WhatsApp"
                                                  • Pindah ke Sesi Lain / Selesai
```

### Visual Identity
- **Desain Hero**: Visual siluet aksi atlet *jump smash* bernuansa navy `#0b1e36` dengan aksen emas `#fbbf24` dan putih, dilengkapi garis lapangan bulutangkis dan kok yang melesat halus.
- **Tipografi**: Plus Jakarta Sans untuk teks instruksional, JetBrains Mono untuk metrik, skor, dan waktu.
- **Clean Anti-Slop**: Bebas dari icon emoji berantakan, menggunakan badge status teks tegas (`[SELESAI]`, `[KAS LUNAS]`, `[SALIN REKAP]`).

---

## 3. Technical Architecture & Data Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                    SMASHPOINT ARCHITECTURE                  │
├──────────────────────────────┬──────────────────────────────┤
│       ADMIN CMS PANEL        │      APP PIC COURTSIDE       │
│  (admin/index.php)           │  (app/index.php)             │
│  • GOR Search Indonesia      │  • Multi-Court Rolling       │
│  • Link PIC to Schedules     │  • Selesaikan Sesi Mabar     │
│  • Setting Hero Style        │  • Salin Rekap WhatsApp      │
│  • Mobile Segmented Tabs     │  • Switcher Sesi Mabar       │
├──────────────────────────────┴──────────────────────────────┤
│                    CORE LOGIC & API                         │
│  • core/RotationEngine.php (Single-Court Rolling)            │
│  • models/Schedule.php (completeSession, findByPic)         │
│  • models/MatchRecord.php (Session Recap & Stats)           │
│  • api/index.php (generate_single_court, gor_search)        │
├─────────────────────────────────────────────────────────────┤
│                    LANDING PAGE                             │
│  • views/landing/index.php (Hero Jump Smash Action Animation)│
└─────────────────────────────────────────────────────────────┘
```

### Query SQL phpMyAdmin Hosting

Pengguna dapat menyalin dan mengeksekusi query berikut di phpMyAdmin hosting mereka:

```sql
-- ========================================================
-- QUERY TAMBAHAN SMASHPOINT (AMAN & TIDAK MENGHAPUS DATA)
-- Jalankan di menu SQL phpMyAdmin database Anda:
-- ========================================================

-- 1. Tambah kolom pic_id dan completed_at pada tabel schedules jika belum ada
ALTER TABLE `schedules` 
  ADD COLUMN IF NOT EXISTS `pic_id` INT NULL AFTER `pic_whatsapp`,
  ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL AFTER `status`;

-- 2. Pastikan kolom phone dan assigned_session_id tersedia pada tabel admins
ALTER TABLE `admins` 
  ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `assigned_session_id` INT NULL AFTER `role`;

-- 3. Tambahkan kolom hero_animation_enabled pada tabel settings
ALTER TABLE `settings`
  ADD COLUMN IF NOT EXISTS `hero_animation_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `hero_image_url`;
```

---

## 4. Rencana Kerja & File yang Akan Diubah

1. **`models/Schedule.php`**:
   - Menambahkan method `getPicSchedules($picId)` untuk mengambil daftar sesi yang ditugaskan kepada PIC tertentu.
   - Memastikan `completeSession($sessionId)` mencatat `completed_at = NOW()` dan `status = 'completed'`.
2. **`app/index.php`**:
   - Menambahkan tombol "Selesaikan Sesi Mabar" pada tab Court dan Leaderboard dengan modal konfirmasi aman.
   - Menambahkan Modal Rekap Akhir Sesi yang otomatis menghitung statistik lengkap (total laga, top win/point, kas masuk) dan tombol "Salin Format Rekap WhatsApp".
   - Menambahkan Drawer / Modal "Pilih Sesi Mabar" di bar navigasi atas agar PIC/Admin dapat berpindah sesi dengan cepat.
3. **`admin/index.php`**:
   - Pada form Jadwal (`tab=schedule_form`), menambahkan dropdown "Pilih Akun PIC Penanggung Jawab" yang otomatis sinkron dengan nama dan No WA PIC.
   - Memperbarui sistem pencarian GOR dengan engine pencarian seluruh Indonesia (Nominatim OpenStreetMap + curated database nasional).
   - Menambahkan opsi toggle animasi Hero pada Sub-Tab Tampilan Web.
4. **`views/landing/index.php`**:
   - Mengintegrasikan komponen visual aksi atlet jump smash badminton dengan efek animasi CSS kecepatan tinggi (*speed lines*, laju kok, dan *court perspective*).
