# 🏸 BUKU PANDUAN PENGGUNA (USER MANUAL BOOK)
## Powershoot Badminton Club & Tournament Rotation System

Selamat datang di sistem manajemen komunitas **Powershoot Badminton**. Aplikasi ini dirancang untuk memudahkan operasional mabar bulutangkis: mulai dari promosi jadwal di landing page, generator pesan WhatsApp, rotasi ganda otomatis yang adil, pencatatan skor real-time, laporan keuangan kas, hingga klasemen bulanan (*Leaderboard*).

---

## 📑 DAFTAR ISI
1. [Struktur Akses & Peran Pengguna](#1-struktur-akses--peran-pengguna)
2. [Panduan Calon Pemain / Member](#2-panduan-calon-pemain--member)
3. [Panduan PIC Sesi Lapangan (Courtside App)](#3-panduan-pic-sesi-lapangan-courtside-app)
4. [Panduan Super Admin (CMS Control Panel)](#4-panduan-super-admin-cms-control-panel)
5. [Panduan Laporan Keuangan & Kas Komunitas](#5-panduan-laporan-keuangan--kas-komunitas)
6. [Troubleshooting & Tanya Jawab Operasional](#6-troubleshooting--tanya-jawab-operasional)

---

## 1. STRUKTUR AKSES & PERAN PENGGUNA

| Halaman / Fitur | Alamat URL Bersih | Akses Pengguna | Fungsi Utama |
|---|---|---|---|
| **Landing Page Publik** | `https://powershoot.freehosting.dev/` | Umum / Calon Pemain | Melihat jadwal mabar, filter pekan, booking via WA, Leaderboard, & Galeri. |
| **Aplikasi Lapangan (App)** | `https://powershoot.freehosting.dev/app/` | PIC Sesi Lapangan / Host | Absensi kehadiran, status bayar iuran, generator ronde rotasi, input skor. |
| **CMS Control Panel (Admin)**| `https://powershoot.freehosting.dev/admin/` | Super Admin / Pengurus | Laporan keuangan kas, kelola jadwal GOR, roster master pemain, akun PIC, SEO. |
| **Galeri Dokumentasi** | `https://powershoot.freehosting.dev/gallery` | Umum & Member | Melihat album foto dokumentasi mabar per tanggal & sesi. |

---

## 2. PANDUAN CALON PEMAIN / MEMBER

### A. Melihat Jadwal Mabar & Filter Pekan
1. Buka browser HP atau laptop, akses alamat utama: `https://powershoot.freehosting.dev/`.
2. Scroll ke bagian **"02 • JADWAL CABANG MABAR"**.
3. Gunakan tab filter di atas kartu:
   - **Semua Jadwal**: Menampilkan seluruh sesi mabar yang tersedia.
   - **🔥 Minggu Ini**: Hanya menampilkan sesi mabar yang diadakan pada pekan berjalan (termasuk badge *HARI INI* atau *BESOK*).
   - **📅 Minggu Depan**: Menampilkan sesi untuk pekan berikutnya.
   - **Dropdown Pilih Tanggal**: Memilih tanggal kalender spesifik.
4. Setiap kartu menampilkan informasi transparan:
   - Tanggal kalender lengkap (misal: `📅 Sabtu, 07 November 2026`).
   - Nama venue GOR & alamat lengkap.
   - Jam main (misal: `20:00 - 22:00 WIB`).
   - Biaya iuran & kok (misal: `Rp 40.000 / orang`).
   - Sisa kuota slot pemain secara *live*.

### B. Mendaftar via WhatsApp PIC
1. Pada kartu sesi yang diinginkan, klik tombol **"Daftar via WhatsApp PIC Sesi →"**.
2. WhatsApp akan terbuka otomatis dengan pesan yang sudah diformat rapi menyertakan tanggal mabar dan nama PIC:
   > *"Halo Kak Yizreel, saya ingin mendaftar Mabar POWERSHOOT BADMINTON CLUB untuk sesi Sabtu Malam (Sabtu, 07 November 2026) di Main Court Badminton."*
3. Kirim pesan tersebut dan lakukan konfirmasi pembayaran iuran ke nomor rekening yang diberikan PIC.

### C. Memantau Klasemen & Poin Pemain
1. Scroll ke bagian **"03 • KLASEMEN BULANAN (Top Scorers)"**.
2. Di sistem Powershoot, peringkat dihitung berdasarkan **total poin reli pertandingan** yang dicetak pemain ("banyak-banyakan poin"), bukan sekadar menang/kalah.
3. Anda dapat memfilter klasemen:
   - **Semua Sesi**: Klasemen akumulasi komunitas keseluruhan.
   - **Per Sesi GOR**: Klasemen khusus sesi lapangan tertentu.

---

## 3. PANDUAN PIC SESI LAPANGAN (COURTSIDE APP)

Aplikasi lapangan diakses via `https://powershoot.freehosting.dev/app/`. Aplikasi ini dioptimalkan khusus untuk layar smartphone PIC di tepi lapangan badminton.

### A. Login Akun PIC
1. Masukkan **Username** atau **Nomor WhatsApp** PIC yang didaftarkan Admin.
2. Masukkan **Password** akun PIC Anda.
3. Begitu login, aplikasi akan **otomatis mengunci sesi mabar Anda** sesuai jadwal yang ditugaskan tanpa perlu memilih sesi secara manual.

### B. Mengelola Peserta & Status Iuran (Courtside Toolbar)
1. **Menambah Peserta di Lokasi**:
   - Ketik nama pemain pada kolom input *Nama Pemain Baru*.
   - Pilih level permainan (*Beginner*, *Intermediate*, *Advance*).
   - Klik **Tambah**.
2. **Absensi Kehadiran (Check-In)**:
   - Klik badge status kehadiran pemain untuk mengubah antara **Hadir** (aktif masuk rotasi) atau **Izin/Absen** (tidak akan diikutsertakan ke dalam drawing pertandingan).
3. **Konfirmasi Pembayaran Kas (Paid / Unpaid)**:
   - Klik tombol **Lunas / Belum** pada baris pemain.
   - Pemain berstatus **Lunas** (*Paid*) otomatis masuk ke dalam kalkulasi pemasukan di laporan keuangan admin.

### C. Menjalankan Rotasi Otomatis (Smart Fair Rotation)
1. Setelah semua pemain yang hadir tercentang, klik tombol **"⚡ Generate Ronde Berikutnya"**.
2. Sistem akan menghitung algoritma rotasi cerdas:
   - **Pemerataan Bermain**: Pemain yang ronde sebelumnya istirahat (*bench*) diprioritaskan main.
   - **Penyeimbangan Level**: Pasangan ganda disusun berimbang (*Advance + Beginner* vs *Intermediate + Intermediate*).
   - **Anti-Duplikasi**: Menghindari pemain berpasangan dengan orang yang sama berulang kali.
3. Lapangan 1, Lapangan 2, dan Lapangan 3 otomatis terisi oleh 4 pemain di setiap court.

### D. Input Skor Pertandingan
1. Di setiap kartu pertandingan, terdapat kontrol skor tim A dan tim B.
2. PIC cukup mengetuk tombol angka skor (misal: `21` - `19`).
3. Klik tombol centang hijau **"Simpan Skor"** (atau skor otomatis tersimpan saat angka diubah).
4. Poin reli kedua tim langsung terakumulasi ke profil pemain dan mengupdate Leaderboard publik seketika.

### E. Upload Foto Dokumentasi Lapangan
1. Di bagian bawah aplikasi lapangan, terdapat panel **"Upload Dokumentasi Sesi"**.
2. PIC dapat mengambil foto bersama (*shuttlecock huddle* atau foto grup pemain) langsung dari kamera HP.
3. Beri judul/caption singkat lalu klik **Upload Foto**.
4. Foto otomatis terbit di galeri landing page publik dengan cap tanggal sesi.

---

## 4. PANDUAN SUPER ADMIN (CMS CONTROL PANEL)

Super Admin mengelola keseluruhan sistem melalui alamat `https://powershoot.freehosting.dev/admin/`.

### A. Menu Dashboard
* Menampilkan KPI statistik komunitas: Total Pemain Roster, Jumlah Sesi Aktif, Saldo Kas Komunitas, dan Top Player of the Month.
* Akses cepat ke laporan keuangan dan manajemen jadwal.

### B. Menu Jadwal & Kuota Sesi
1. **Membuat Jadwal Baru**:
   - Klik menu **"+ Tambah Jadwal Baru"**.
   - Masukkan nama GOR venue, hari & sesi, tanggal kalender mabar, jam mulai - selesai, jumlah court/lapangan, kuota maksimal, dan iuran per orang.
   - Masukkan nomor WhatsApp PIC yang bertanggung jawab.
   - Klik **Simpan Jadwal**.
2. **Buka Sesi Pekan Depan (+7 Hari) 1-Klik**:
   - Pada kartu sesi mabar rutin, cukup klik tombol **"+ Buka Sesi Pekan Depan (+7 Hari)"**.
   - Sistem otomatis membuat jadwal baru untuk tanggal 7 hari ke depan dengan konfigurasi venue yang sama persis tanpa perlu input ulang dari nol!
3. **Workspace Broadcast WhatsApp & Paste Roster**:
   - Klik tombol **"Template WA"** pada sesi yang diinginkan.
   - Anda dapat mengubah parameter pesan secara *live* (lapangan, rekening transfer, jam, aturan cancel).
   - Terdapat fitur **"Tempel / Paste List WA"**: Salin (*copy*) daftar nama peserta dari grup WhatsApp, tempel ke kotak yang tersedia, lalu klik **Impor Peserta**. Sistem akan otomatis membedah nomor urut dan mendaftarkan nama-nama tersebut ke dalam sesi!

### C. Menu Roster Pemain Cabang
* Menampilkan buku induk seluruh pemain yang pernah mendaftar di komunitas.
* Fitur filter berdasarkan cabang GOR atau tanggal sesi mabar tertentu.
* Dapat mengubah level pemain (*Beginner*, *Intermediate*, *Advance*).
* Fitur **Hapus Masal (Bulk Delete)** dengan mencentang kotak pemain.

### D. Menu Akun PIC Sesi
* Membuat akun login untuk PIC / Host lapangan.
* Setiap akun PIC dapat ditugaskan (*assigned*) ke sesi mabar tertentu sehingga saat login mereka langsung masuk ke sesinya masing-masing.

### E. Menu Galeri Dokumentasi
* Menampilkan seluruh foto dokumentasi mabar.
* Admin dapat menambah foto baru, menghapus foto lama, atau menyematkan foto ke sesi mabar tertentu.

### F. Menu Profil Komunitas & Pengaturan
* Mengubah nama komunitas (misal: *Powershoot Badminton Club*).
* Mengubah nomor WhatsApp default dan akun Instagram resmi.
* Mengatur Yoast-style SEO (Meta Title, Meta Description, Google Search Preview, dan Facebook/WA Share Preview).

---

## 5. PANDUAN LAPORAN KEUANGAN & KAS KOMUNITAS

Fitur ini dapat diakses melalui menu **"Laporan Keuangan & Kas"** di CMS Admin.

### A. Indikator Keuangan Utama (KPI Cards)
1. **Saldo Kas Bersih (Net Cashflow)**:
   - Dihitung dari: *(Total Iuran Lunas + Pemasukan Tambahan) – Total Pengeluaran*.
   - Badge hijau **SURPLUS** menandakan kas komunitas bertambah.
2. **Iuran Pemain Lunas**:
   - Menghitung secara otomatis seluruh iuran peserta berstatus *paid* di sesi mabar.
3. **Iuran Belum Bayar (Pending)**:
   - Menampilkan total tagihan iuran pemain yang belum melunasi pembayaran.
4. **Total Pengeluaran**:
   - Total biaya operasional yang dikeluarkan komunitas.

### B. Mencatat Transaksi Kas Baru
1. Klik tombol **"+ Catat Transaksi Kas"**.
2. Pilih **Jenis Transaksi**:
   - **Pengeluaran (Kas Keluar)**: Untuk sewa GOR, beli kok, air mineral, honor PIC.
   - **Pemasukan Tambahan (Kas Masuk)**: Untuk sponsor, denda pembatalan terlambat, atau donasi kok.
3. Pilih **Kategori** (Sewa Lapangan, Shuttlecock, Konsumsi, Honor PIC, Lain-lain).
4. Pilih **Terkait Sesi Mabar** (atau pilih *Umum / Kas Komunitas* jika non-sesi).
5. Masukkan **Judul Transaksi** (misal: *Sewa 3 Lapangan x 2 Jam Main Court*).
6. Masukkan **Nominal (Rp)** dan **Tanggal Transaksi**.
7. Masukkan catatan bukti transfer / kuitansi jika ada, lalu klik **Simpan Transaksi Kas**.

### C. Analisis Laba / Rugi per Sesi Mabar (P&L per Sesi)
* Tabel ini menganalisis secara otomatis apakah suatu sesi mabar menghasilkan **Surplus** (untung) atau **Defisit** (nombok).
* Rumus: `Laba Sesi = Iuran Terkumpul – (Biaya Sewa GOR + Pembelian Kok)`.

### D. Cetak / Print Laporan Keuangan
* Klik tombol **"Cetak Laporan"** di pojok kanan atas.
* Browser akan membuka dialog cetak yang sudah didesain rapi bebas dari navigasi sidebar (siap dijadikan PDF atau dicetak fisik untuk laporan pertanggungjawaban bendahara).

---

## 6. TROUBLESHOOTING & TANYA JAWAB OPERASIONAL

**Q: Pemain membatalkan kehadiran di hari H, bagaimana cara mengaturnya?**  
A: Buka Aplikasi Lapangan atau CMS Roster, ubah status pemain menjadi *Izin / Absen* agar tidak masuk drawing rotasi. Jika pembatalan melewati batas waktu toleransi, status iuran tetap bisa ditagihkan sesuai peraturan komunitas.

**Q: Bagaimana jika koneksi internet di dalam GOR lambat?**  
A: Aplikasi lapangan Powershoot dirancang sangat ringan (tanpa library berat). Jika terjadi kendala jaringan, PIC tetap dapat menginput skor ronde dan data akan tersimpan begitu tombol simpan ditekan.

**Q: Apakah PIC lapangan bisa melihat laporan keuangan kas admin?**  
A: Tidak. Akun PIC sesi dibatasi hak aksesnya (*Role-Based Access Control*) hanya untuk mengelola rotasi, absensi, dan skor lapangan. Laporan keuangan kas hanya dapat dibuka oleh Super Admin.
