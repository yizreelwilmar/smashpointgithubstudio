# Manual Book SaaS Komprehensif & Fitur Ubah Kata Sandi Admin

Rencana pengembangan **Panduan Operasional SaaS Lengkap (Manual Book)** yang mencakup seluruh alur kerja CMS Admin dan Aplikasi Field PIC, serta pembuatan **Fitur Ubah Kata Sandi Admin** yang aman di portal CMS.

## User Review & Critical Decisions

> [!IMPORTANT]
> Berdasarkan jawaban klarifikasi yang telah disetujui:
> 1. **Format Manual Book**: Kombinasi **Panduan Interaktif Bertahap** di portal Admin + **Opsi Export/Cetak PDF Standalone** (`/admin/manual-book-pdf.php`) yang siap cetak/simpan PDF dengan tata letak visual profesional.
> 2. **Lokasi Ubah Kata Sandi**: Tab khusus **Profil & Keamanan Akun** di CMS Admin (`/admin/?tab=security`) dengan validasi kata sandi lama, indikator kekuatan kata sandi baru, dan pembaharuan hash kata sandi aman.

---

## 1. Overview & Core Concept

- **Tujuan Utama**: Menyediakan dokumentasi panduan penggunaan aplikasi end-to-end (White-Label / Ready-to-Sell) sehingga pembeli aplikasi dapat langsung menjalankan komunitas badminton tanpa perlu bantuan penjelasan manual.
- **Cakupan Manual Book**:
  1. **CMS Website Profile**: Mengedit nama komunitas, hero banner, teks promosi, FAQ, link WhatsApp, dan galeri foto.
  2. **Kelola Jadwal & Sesi Mabar**: Membuat jadwal mabar, kuota slot, harga iuran, lokasi GOR, dan jumlah lapangan.
  3. **Manajemen PIC & Akun Lapangan**: Membuat akun PIC, penugasan sesi tunggal/bulk, dan hak akses operasional.
  4. **Keuangan & Buku Kas Komunitas**: Mencatat uang masuk/keluar, rekapitulasi laba-rugi per sesi, dan tindak lanjut piutang pemain.
  5. **Master Roster & Leaderboard**: Mengelola database pemain, level keahlian (Beginner/Intermediate/Advance), dan reset poin leaderboard bulanan.
  6. **Aplikasi Operasional Field PIC (`/app`)**: Cara PIC membuka sesi di GOR, check-in kehadiran, sistem rotasi ganda otomatis, pencatatan iuran lunas, dan penguncian sesi selesai.
- **Fitur Ubah Kata Sandi**:
  - Menyediakan tab "Keamanan Akun" di Admin Portal.
  - Memungkinkan Admin mengubah password login dengan verifikasi password lama & validasi minimal 6 karakter.

---

## 2. User Experience & Visual Design

### Manual Book Interaktif (`/guide.php` & Embed Admin `/admin/?tab=manual_book`)
- **Visual Mockups & UI Step-by-Step**: Menggunakan UI mockup card dengan tangkapan layar simulasi interface asli, badge bernomor bertahap (Langkah 1, 2, 3), serta tips khusus ("Tips Admin" & "Peringatan").
- **Navigasi Cepat per Modul**: Sidebar/Tab navigasi sticky di sebelah kiri untuk berpindah modul secara instan (Profile CMS, Sesi Mabar, PIC, Kas, Roster, Aplikasi Field PIC).
- **Cetak & Export PDF**: Tombol "Cetak / Export PDF" yang membuka tampilan khusus print-ready tanpa sidebar/header interaktif (`@media print` clean layout).

### Fitur Ubah Kata Sandi Admin (`/admin/?tab=security`)
- **Formulir Keamanan Ringkas**: Card dengan desain athletic slate khas aplikasi (`bg-white` dengan border halus `border-slate-200`).
- **Input Fields**:
  - Kata Sandi Saat Ini (`current_password`)
  - Kata Sandi Baru (`new_password`)
  - Konfirmasi Kata Sandi Baru (`confirm_password`)
- **Sistem Umpan Balik**: Toast notification sukses "✓ Kata sandi admin berhasil diperbarui" atau pesan error merah yang jelas jika kata sandi lama salah atau konfirmasi tidak cocok.

---

## 3. Key Product Decisions & Trade-Offs

- **Penggabungan PDF & Interaktif**:
  - *Keputusan*: Membuat satu modul data manual yang merender baik tampilan interaktif berbasis web (`/guide.php` & embed tab admin) maupun tampilan khusus cetak PDF (`/admin/manual-book-pdf.php`).
  - *Alasan*: Menjamin pembeli aplikasi selalu mendapatkan informasi yang 100% konsisten baik saat membaca di layar komputer/HP maupun saat mencetaknya sebagai dokumen fisik/PDF.
- **Penyimpanan Password Hash Aman**:
  - *Keputusan*: Menggunakan `password_hash($newPassword, PASSWORD_DEFAULT)` dan verifikasi `password_verify($currentPassword, $hashFromDb)`.
  - *Alasan*: Menjamin standar keamanan industri untuk akun super admin.

---

## 4. Technical Architecture & Data Strategy

```
┌────────────────────────────────────────────────────────────────────────┐
│                        ADMIN CMS PORTAL (/admin)                       │
├────────────────────────────────────────────────────────────────────────┤
│ ├── Tab "Keamanan Akun" (?tab=security)                                │
│ │   └── POST form_action=update_admin_password                         │
│ │       ├── Validasi current_password via password_verify()            │
│ │       └── Update password_hash di database                           │
│ │                                                                      │
│ ├── Tab "Manual Book" (?tab=manual_book)                               │
│ │   ├── Navigasi Modul Interaktif (CMS, Sesi, PIC, Kas, Roster, App)  │
│ │   └── Tombol Export PDF -> Redirect ke /admin/manual-book-pdf.php    │
└────────────────────────────────────────────────────────────────────────┘
```

### Rencana Perubahan Berkas:
1. **`/admin/index.php`**:
   - Tambahkan handler `form_action === 'update_admin_password'` untuk memproses pengubahan kata sandi.
   - Tambahkan menu navigasi tab `security` (Keamanan Akun) di sidebar Admin.
   - Perbarui konten Tab `manual_book` dengan modul panduan lengkap 100% dari CMS hingga Aplikasi Field.
2. **`/admin/manual-book-pdf.php`**:
   - Perbarui dokumen cetak PDF agar menyajikan seluruh langkah lengkap penggunaan CMS, Pengaturan Profile, PIC, Kas, Roster, dan Aplikasi Lapangan.
3. **`/guide.php`**:
   - Perbarui halaman standalone manual book interaktif.
