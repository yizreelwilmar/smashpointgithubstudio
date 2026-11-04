# Buku Panduan Manual Book (PDF) & Fitur Demo Akun PIC Admin

Rencana implementasi Buku Panduan Operasional & Pitch Demo berformat PDF siap cetak/unduh, dilengkapi fitur **1-Click Quick Demo PIC** di Admin CMS serta panduan alur presentasi interaktif step-by-step (1 by 1) untuk memikat calon klien (pengelola komunitas, pemilik GOR, atau event organizer).

---

## User Review & Critical Decisions

> [!IMPORTANT]
> User telah mengonfirmasi bahwa Manual Book dibutuhkan **dalam bentuk PDF**. Kami akan menyediakan modul khusus di Admin CMS yang merender dokumen format A4 berstandar korporat dengan layout `@media print` teroptimasi dan tombol **"Unduh / Cetak PDF"**, sehingga langsung menghasilkan file PDF berkualitas tinggi tanpa ketergantungan library eksternal yang lambat.

- **Format PDF**: HTML-to-PDF / Browser-native print stylesheet berstandar A4 dengan styling tipografi profesional (Plus Jakarta Sans), page break otomatis, cover resmi, dan no watermark/slop.
- **Fitur Demo Akun PIC di Admin**: Menyediakan tombol pintas **"Quick Generate PIC Demo"** di `admin/?tab=pic_accounts` yang langsung membuat PIC contoh lengkap dengan penugasan jadwal dan nomor WhatsApp demo hanya dalam 1 klik.
- **Alur Presentasi (Demo Script)**: Disusun terstruktur 1 by 1 dengan fokus *Problem-Agitation-Solution* untuk meyakinkan calon pelanggan.

---

## 1. Overview & Core Concept

- **Apa yang Dibangun**:
  1. Halaman **Buku Panduan & Playbook Demo** di Admin (`/admin/?tab=manual_book` & `/admin/manual-book-pdf.php`) dengan tombol ekspor PDF instan.
  2. Modul **Quick Demo PIC Generator** di Admin CMS (`admin/?tab=pic_accounts`) untuk membuat akun PIC simulasi secara instan saat sesi presentasi tanpa mengetik manual yang membuang waktu audiens.
  3. Dokumen **Alur Demo Step-by-Step (1 by 1)** yang mencakup skrip pembuka, demonstrasi fitur, penanganan keberatan, hingga *closing*.
- **Target Audiens Demo**:
  - Founder / Pengurus Komunitas Badminton (yang kewalahan atur antrean & transfer iuran).
  - Pemilik / Pengelola GOR Badminton (yang ingin lapangannya selalu penuh terisi sesi rutin).
  - Event Organizer Mabar / Turnamen Mini.
- **Nilai Utama (Value Proposition)**:
  - Mengubah operasional mabar yang manual, berantakan di grup chat, dan rawan salah hitung rotasi menjadi otomatis, transparan, dan profesional.

---

## 2. User Experience & Visual Design

### A. Fitur Quick Demo PIC di Admin (`admin/?tab=pic_accounts`)
- **Letak**: Di atas daftar akun PIC dan di form tambah PIC, ditambahkan tombol aksi tajam:
  - `[+ Quick Demo PIC: Coach Bagas (GOR Kuningan)]`
- **Interaksi**: Sekali klik, form langsung terisi otomatis dengan profil PIC realistis, nomor WhatsApp demo, cabang GOR terkait, dan tanggal penugasan aktif, lalu otomatis tersimpan ke database.
- **Feedback**: Notifikasi toast bersih: *"Akun PIC Demo berhasil diaktifkan dan terhubung ke jadwal mabar terdekat."*

### B. Modul Manual Book PDF (`/admin/?tab=manual_book` & Cetak PDF)
- **Tampilan di Admin**: Preview dokumen editorial yang elegan dengan tab navigasi cepat per bab.
- **Header Aksi**: Tombol dominan warna amber `[Cetak / Unduh PDF Sekarang]` dan opsi salin tautan ringkasan.
- **Tampilan Hasil Cetak PDF**:
  - Ukuran halaman standar ISO A4 Portrait dengan margin 15mm.
  - Halaman 1: Cover Eksekutif (Logo Komunitas, Judul Dokumen, Metadata Komunitas, Tanggal Cetak).
  - Halaman 2: Value Proposition & Solusi Bisnis untuk Komunitas Badminton.
  - Halaman 3: Alur Demo 1 by 1 (Opening, Landing Page, Pembuatan PIC & Jadwal, Player Experience, Rotasi Lapangan, Pelaporan Finansial).
  - Halaman 4: Panduan Operasional PIC Lapangan (SOP Hari-H).
  - Halaman 5: Tabel Perbandingan (Cara Lama via Chat vs. Ekosistem Digital).
  - Dilengkapi CSS `@media print` (`@page { size: A4 portrait; margin: 1.5cm; }`), menyembunyikan sidebar admin, tombol navigasi, dan elemen interaktif saat dicetak.

---

## 3. Alur Demo 1 by 1 ke Calon Customer

Alur ini dirancang khusus untuk memikat calon customer secara psikologis dan praktis:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ LANGKAH 1: IDENTIFIKASI MASALAH (The Pain Point)                            │
│ "Berapa lama waktu yang terbuang rekap chat WA, rebutan slot, & atur antre?"│
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LANGKAH 2: SHOWCASE LANDING PAGE (First Impression)                         │
│ - Tunjukkan video background hidup, jadwal real-time, & slot transparan.    │
│ - Tunjukkan betapa mudahnya calon pemain melihat ketersediaan lapangan.     │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LANGKAH 3: ADMIN SETUP DALAM 60 DETIK (Instant Control)                     │
│ - Buat jadwal baru & fasilitas venue.                                       │
│ - Klik "Quick Demo PIC": Akun PIC langsung terbit & terhubung ke cabang.    │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LANGKAH 4: PLAYER REGISTRATION VIA WHATSAPP (Zero Friction)                 │
│ - Klik "Daftar Sesi" di Hero/Jadwal: otomatis buka pesan WA format rapi.    │
│ - PIC cukup konfirmasi sekali klik, slot langsung berkurang otomatis.      │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LANGKAH 5: HARI-H & ROTASI DIGITAL (The Game Changer)                       │
│ - Buka aplikasi lapangan: pemain melihat giliran main tanpa ribut.          │
│ - Sistem mengacak pasangan ganda adil berdasarkan rating & histori.         │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LANGKAH 6: CLOSING & REKAP KEUANGAN (The Value)                             │
│ - Buka dashboard: rekap iuran masuk, kehadiran member, & top scorer.        │
│ - "Dengan sistem ini, mabar Anda naik kelas & bisa buka cabang baru!"       │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Technical Architecture & Data Strategy

### A. Arsitektur Komponen

```
                  ┌────────────────────────────────────────┐
                  │              ADMIN PANEL               │
                  └───────────────┬────────────────────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         │                        │                        │
┌────────▼──────────┐    ┌────────▼──────────┐    ┌────────▼──────────┐
│ Tab pic_accounts  │    │  Tab manual_book  │    │  manual-book-pdf  │
│ - List & Link PIC │    │ - Preview Layar   │    │ - Clean Print CSS │
│ - 1-Click Demo PIC│    │ - Quick Navigasi  │    │ - PDF Generator   │
└────────┬──────────┘    └───────────────────┘    └───────────────────┘
         │
┌────────▼─────────────────────────────────────────────────┐
│ Database SQLite (pic_users, schedules, community_settings)│
└──────────────────────────────────────────────────────────┘
```

### B. Perubahan File yang Direncanakan
1. **`admin/manual-book-pdf.php`**: View mandiri khusus cetak PDF dengan layout editorial majalah/panduan bisnis profesional.
2. **`admin/index.php`**:
   - Menambahkan tab navigasi `manual_book` pada sidebar & mobile nav.
   - Menambahkan tombol aksi 1-Click Demo PIC di tab `pic_accounts`.
3. **`api/index.php` / Handler Admin**:
   - Menambahkan endpoint aksi `action=quick_demo_pic` untuk menyisipkan data PIC demo yang terhubung langsung ke jadwal sesi berikutnya.

---

## Rencana Verifikasi
- Uji cetak/unduh PDF di browser via window.print() dan pastikan tata letak A4 rapi tanpa pemotongan teks (*page-break-inside: avoid*).
- Uji tombol 1-Click Demo PIC di Admin untuk memastikan akun langsung muncul di tabel dan tertaut ke jadwal mabar.
- Jalankan `php -l` pada semua file PHP yang dimodifikasi.
- Jalankan `compile_applet` untuk memastikan build tetap sukses.
