# BUKU PANDUAN PENGGUNA & OPERASIONAL

## Powershoot Badminton Management System

**Versi aplikasi:** 2.4.0  
**Edisi dokumen:** November 2026  
**Cakupan:** Landing page publik, Galeri, Aplikasi PIC `/app/`, CMS Admin `/admin/`, rumus rotasi & poin, kas komunitas, instalasi, SOP, dan troubleshooting.

> Dokumen ini disusun berdasarkan perilaku aplikasi yang benar-benar ada di kode sumber (bukan materi promosi). Setiap nama menu, tombol, status, dan rumus di bawah ini dapat diverifikasi langsung di aplikasi. Jika aplikasi dikustomisasi, perbarui bagian yang terdampak agar dokumen tetap akurat.

---

## DAFTAR ISI

1. [Tentang Sistem & Peta Akses](#1-tentang-sistem--peta-akses)
2. [Panduan Pengunjung & Calon Pemain](#2-panduan-pengunjung--calon-pemain)
3. [Aplikasi PIC Lapangan (`/app/`)](#3-aplikasi-pic-lapangan-app)
4. [CMS Admin (`/admin/`)](#4-cms-admin-admin)
5. [Aturan Perhitungan: Rotasi, Poin, dan Kas](#5-aturan-perhitungan-rotasi-poin-dan-kas)
6. [Instalasi, Konfigurasi & Go-Live](#6-instalasi-konfigurasi--go-live)
7. [SOP Operasional Mingguan](#7-sop-operasional-mingguan)
8. [Troubleshooting & FAQ](#8-troubleshooting--faq)
9. [Lampiran: UAT, Glosarium & Batasan Versi](#9-lampiran-uat-glosarium--batasan-versi)

---

## 1. TENTANG SISTEM & PETA AKSES

### 1.1 Gambaran singkat

Aplikasi ini adalah sistem manajemen komunitas badminton berbasis PHP + MySQL/MariaDB tanpa framework. Sistem menangani:

- Publikasi jadwal mabar dan kuota secara publik.
- Pendaftaran peserta melalui WhatsApp PIC.
- Absensi, status iuran, dan rotasi ganda otomatis di lapangan.
- Pencatatan skor dan akumulasi poin pemain.
- Kas komunitas, piutang iuran, dan laba/rugi per sesi.
- Galeri dokumentasi foto dan video.
- Pengelolaan akun PIC per sesi.

Tidak ada aplikasi mobile native. Aplikasi PIC adalah web app mobile-first yang dapat dipasang ke layar utama (PWA) pada `/app/`.

### 1.2 Peta URL

| Antarmuka | URL (relatif terhadap domain) | Pengguna utama | Fungsi |
|---|---|---|---|
| Landing page | `/` | Publik | Jadwal, kuota, pendaftaran WA, klasemen, galeri, FAQ |
| Galeri publik | `/gallery` atau `/galeri` | Publik | Album foto & video per sesi |
| Aplikasi lapangan | `/app/` | PIC / host | Absensi, iuran, rotasi, skor, kas sesi, upload media |
| CMS Admin | `/admin/` atau `/cms/` | Admin / pengurus | Jadwal, keuangan, roster, leaderboard, PIC, profil web |
| Buku panduan interaktif | `/guide.php` | Admin & PIC | Manual yang dapat dicetak |
| API internal | `/api/` | Aplikasi | Endpoint JSON yang dipanggil antarmuka |

Contoh penerapan pada domain produksi:

- `https://domain-anda.com/`
- `https://domain-anda.com/app/`
- `https://domain-anda.com/admin/`
- `https://domain-anda.com/gallery`

### 1.3 Peran pengguna

| Peran | Cara masuk | Hak akses utama |
|---|---|---|
| Pengunjung | Tanpa login | Melihat jadwal, kuota, klasemen, galeri, dan membuka WhatsApp PIC |
| PIC (`role = pic`) | Login di `/app/` | Mengelola sesi yang ditugaskan: absensi, iuran, rotasi, skor, kas sesi, media |
| PIC Foto (`role = pic_foto`) | Login di `/app/` | Dibatasi ke tab Foto (unggah dokumentasi) |
| Admin (`role = admin`) | Login di `/admin/` | Seluruh fitur CMS, termasuk keuangan dan pengaturan |

Catatan penting:

- Akun PIC dibuat oleh Admin dan dapat ditugaskan ke satu sesi (`assigned_session_id`) atau dihubungkan ke sesi melalui kolom PIC pada jadwal.
- Pada versi ini, pembatasan halaman admin per peran belum granular. Jangan membagikan kredensial admin kepada PIC. PIC seharusnya hanya menggunakan `/app/`.
- Setiap akun sebaiknya memiliki password sendiri. Jangan menggunakan satu akun untuk banyak orang.

### 1.4 Kebutuhan teknis

| Komponen | Minimum yang didukung |
|---|---|
| PHP | 7.4 atau 8.x dengan ekstensi `pdo_mysql`, `mbstring`, `fileinfo` |
| Database | MySQL 5.7+/8.x atau MariaDB 10.3+ |
| Web server | Apache dengan `mod_rewrite`, atau Nginx dengan konfigurasi ekuivalen |
| HTTPS | Sangat disarankan untuk produksi |
| Zona waktu | `Asia/Jakarta` (disetel oleh aplikasi) |
| Browser | Chrome, Safari, Edge, Firefox versi modern |

Aplikasi memuat Tailwind CSS, Google Fonts, dan beberapa pustaka dari CDN. Koneksi internet diperlukan untuk tampilan optimal. Aplikasi PIC tetap dapat digunakan pada koneksi lambat, tetapi menyimpan data tetap memerlukan koneksi ke server.

---

## 2. PANDUAN PENGUNJUNG & CALON PEMAIN

### 2.1 Struktur landing page

Landing page memiliki bagian berurutan:

1. **Announcement bar** (jika diisi) dan navigasi lengket.
2. **Hero sinematik** dengan video/foto, nama komunitas, ajakan daftar, dan kartu sesi terdekat.
3. **01 • CARA IKUT MABAR** — tiga langkah pendaftaran.
4. **02 • JADWAL CABANG MABAR** — daftar sesi dan filter.
5. **03 • KLASEMEN BULANAN** — leaderboard.
6. **04 • DOKUMENTASI MABAR** — galeri.
7. **05 • TANYA JAWAB (FAQ)** — accordion pertanyaan.
8. **CTA penutup** dan footer.

### 2.2 Memfilter jadwal

Pada bagian **02 • JADWAL CABANG MABAR** tersedia tombol filter berikut:

| Tombol | Fungsi |
|---|---|
| **Semua Jadwal (n)** | Menampilkan seluruh sesi yang tersedia |
| **Minggu Ini (n)** | Sesi pada pekan berjalan |
| **Minggu Depan (n)** | Sesi pada pekan berikutnya |
| **Mendatang (n)** | Sesi setelah pekan depan |
| **Rentang Tanggal** | Filter dua tanggal: tanggal awal **s/d** tanggal akhir |
| **Reset** | Menghapus filter tanggal |

Catatan:

- Angka `(n)` adalah jumlah sesi pada kategori tersebut.
- Filter tanggal dan tab pekan dapat saling melengkapi. Jika hasil kosong, klik **Reset**.
- Tombol **Tampilkan Semua Jadwal** muncul ketika filter tidak menghasilkan sesi.

### 2.3 Membaca kartu sesi

Setiap kartu sesi menampilkan:

- Badge waktu relatif: **HARI INI**, **BESOK**, atau hitung mundur.
- Badge pekan: **MINGGU INI** atau **MINGGU DEPAN**.
- Jumlah lapangan, misalnya **3 LAPANGAN**.
- Status kuota:
  - **Sisa n Slot (x/y)** jika masih tersedia.
  - **FULL (x/x)** jika kuota penuh.
- **Status Kuota**: **Open Booking** atau **Full**.
- **Tanggal Main**, **Waktu Main**, **Iuran & Kok**, **PIC Sesi**, dan **Fasilitas Venue**.
- Tombol **Daftar via WhatsApp PIC Sesi →**.
- Tautan **Lihat Lokasi GOR di Google Maps ↗** jika URL peta diisi.

Keterangan `x/y`:

- `x` = jumlah peserta yang sudah terdaftar.
- `y` = kuota maksimal sesi (`max_slots`).
- Pendaftar setelah kuota penuh masuk ke **Waiting List** dan tidak otomatis dihitung sebagai peserta sesi utama.

### 2.4 Mendaftar melalui WhatsApp PIC

1. Pilih sesi yang diinginkan.
2. Klik **Daftar via WhatsApp PIC Sesi →**.
3. WhatsApp terbuka dengan pesan yang sudah diformat. Contoh:

   > Halo Kak {nama PIC}, saya ingin mendaftar Mabar {nama komunitas} untuk sesi {nama sesi} ({tanggal}) di {nama venue}.

4. Kirim pesan dan ikuti instruksi PIC untuk konfirmasi slot serta pembayaran.
5. Pembayaran dan status **Lunas** dicatat oleh PIC di `/app/` atau oleh Admin. Pendaftaran melalui WhatsApp tidak otomatis menandai pembayaran sebagai lunas.

### 2.5 Klasemen publik

Bagian **03 • KLASEMEN BULANAN** menampilkan tabel dengan kolom:

**Rank · Nama · Level · Tanding · Menang · Kalah · Win Rate · Total Poin**

Filter yang tersedia:

- **Pilih Sesi:** pilihan **Semua Sesi (Klasemen Overall)** atau satu sesi tertentu.
- Pilihan **Semua Sesi** menampilkan akumulasi seluruh riwayat pertandingan yang tersimpan, bukan hanya bulan kalender berjalan.
- Pilihan per sesi menampilkan klasemen khusus sesi tersebut.

Cara peringkat ditentukan (lihat juga Bagian 5.2):

1. **Total Poin** tertinggi.
2. Jika sama, **jumlah kemenangan** lebih banyak.
3. Jika sama, **win rate** lebih tinggi.
4. Jika sama, **streak** lebih tinggi.

Label "Bulanan" adalah nama tampilan. Untuk benar-benar memulai periode baru, Admin harus menjalankan reset poin secara manual di CMS (lihat Bagian 4.8).

### 2.6 Galeri publik

Alamat: `/gallery` atau `/galeri`.

Fitur:

- Pil sesi untuk memfilter album berdasarkan sesi mabar, lengkap dengan jumlah media.
- Pencarian dengan placeholder **"Cari foto, momen, venue..."**.
- Indikator **Filter Aktif:** jika ada filter yang dipakai.
- Lightbox untuk memperbesar media.
- Tombol **Buka Media Ukuran Asli**.
- Dukungan video dengan badge play dan pemutar HTML5.
- Navigasi lightbox dengan tombol sebelumnya/berikutnya, tombol keyboard, dan gestur geser pada perangkat sentuh.
- Galeri memuat maksimal 120 media terbaru.

### 2.7 FAQ publik

FAQ diambil dari pengaturan komunitas. Admin dapat menambah, mengubah, dan menghapus pertanyaan pada CMS. Jika daftar FAQ kosong, bagian FAQ tidak menampilkan konten.

---

## 3. APLIKASI PIC LAPANGAN (`/app/`)

Aplikasi ini dirancang untuk smartphone PIC di tepi lapangan. Semua aksi penting dilakukan dengan tombol besar dan konfirmasi.

### 3.1 Login PIC

1. Buka `https://domain-anda.com/app/`.
2. Isi **Username / WhatsApp:** dengan username atau nomor WhatsApp yang terdaftar.
3. Isi **Password:**.
4. Klik **Masuk ke Sistem Lapangan →**.

Jika akun belum ditugaskan ke sesi mana pun, aplikasi menampilkan pilihan sesi. Jika akun sudah ditugaskan, aplikasi langsung membuka sesi tersebut.

### 3.2 Sesi yang aktif dan pindah sesi

Urutan penentuan sesi:

1. Sesi yang terakhir dipilih di browser (jika masih tersedia).
2. Sesi yang ditugaskan ke akun PIC (`assigned_session_id`).
3. Sesi pertama yang tersedia.

PIC dapat berpindah sesi:

- Klik **Ganti Sesi** pada header.
- Pilih sesi pada panel **Pindah ke Sesi Lain**.
- Sesi yang sudah **completed** terkunci untuk PIC non-admin. Untuk membukanya kembali, Admin harus mengubah status sesi melalui CMS.

### 3.3 Navigasi bawah

Aplikasi PIC memiliki lima tab:

| Tab | Isi |
|---|---|
| **Rotasi** | Generate ronde, daftar pertandingan, input skor, antrean istirahat |
| **Pemain** | Daftar peserta, absensi, status iuran, walk-in, paste WA, waiting list |
| **Kas** | Saldo kas sesi, iuran terkumpul, biaya operasional |
| **Foto** | Unggah dokumentasi foto/video sesi |
| **Klasemen** | Klasemen poin sesi berjalan |

### 3.4 Tab Rotasi

#### A. Syarat generate

- Minimal **4 pemain berstatus Hadir**.
- Jumlah lapangan efektif = `min(jumlah lapangan sesi, floor(jumlah pemain hadir / 4))`.
- Jika pemain hadir kurang dari 4, tombol generate tidak dapat menghasilkan pertandingan.

#### B. Generate ronde per lapangan

Pada setiap kartu lapangan kosong, klik:

**+ Panggil 4 Pemain Berikutnya ke Lapangan n**

Sistem memilih 4 pemain dari antrean, menyusun pasangan, lalu menampilkan kartu pertandingan.

#### C. Generate semua lapangan sekaligus

Di bagian **OPSI LAIN — Acak Semua Lapangan Sekaligus (Rotasi Serentak Ronde)**, klik **Acak Semua Lapangan**.

Gunakan opsi ini untuk mengisi seluruh lapangan pada ronde yang sama.

#### D. Strategi penyusunan pasangan

Tersedia tiga strategi:

| Strategi | Perilaku |
|---|---|
| **Fair Level (Kombinasi Seimbang)** | Menyeimbangkan total level kedua tim; cocok untuk komunitas campuran |
| **Selevel (Pure Tier Mahir/Pemula)** | Mengelompokkan pemain pada tingkat level yang sama |
| **Kocok Campur Merata** | Mengacak tanpa preferensi level |

Bobot level yang dipakai mesin:

- **Advance / Mahir** = 3
- **Intermediate** = 2
- **Beginner / Pemula** = 1

Mesin mencoba hingga 200 kombinasi acak, lalu memilih kombinasi dengan penalti terkecil:

- Selisih level antar tim dikali 50.
- Pasangan yang pernah berpasangan sebelumnya dikali 20.
- Lawan yang pernah bertemu sebelumnya dikali 5.

#### E. Antrean pemain istirahat

Pemain yang tidak masuk lapangan pada ronde tertentu masuk ke **Antrean Pemain Istirahat**. Antrean diurutkan berdasarkan:

1. Jumlah bermain paling sedikit.
2. Jumlah istirahat (bench) paling banyak.
3. Urutan pendaftaran.

Pemain yang paling lama menunggu diprioritaskan pada ronde berikutnya.

#### F. Input dan simpan skor

1. Pada kartu pertandingan, isi skor Tim A dan Tim B.
2. Klik **Simpan Skor & Selesaikan Lapangan n**.
3. Skor **tidak tersimpan otomatis**. Perubahan angka baru tersimpan setelah tombol simpan ditekan.
4. Setelah tersimpan, pertandingan berstatus selesai dan poin masuk ke perhitungan pemain.
5. Untuk mengoreksi, gunakan tombol edit pada kartu pertandingan atau hapus ronde/pertandingan melalui kontrol yang tersedia.

Poin yang dicatat:

- Tim pemenang mendapat **skor tim sendiri + bonus kemenangan**.
- Tim yang kalah mendapat **skor tim sendiri**.
- Bonus kemenangan standar = **5 poin** per kemenangan.
- Skor seri dihitung sebagai kekalahan bagi kedua tim.

Contoh: skor 21–19.

- Tim A (menang): 21 + 5 = **26 poin** per pemain.
- Tim B (kalah): **19 poin** per pemain.

#### G. Hapus ronde / pertandingan

Gunakan tombol hapus pada kartu pertandingan atau bagian riwayat ronde. Konfirmasi diperlukan. Penghapusan pertandingan akan mengurangi statistik pemain terkait; jalankan **Hitung Ulang Leaderboard** di CMS jika diperlukan.

### 3.5 Tab Pemain

#### A. Menambah pemain walk-in

1. Klik **+ Tambah Walk-In**.
2. Isi nama, level, dan status awal (**Pemain Sesi Utama** atau **Waiting List**).
3. Simpan. Pemain otomatis masuk ke sesi aktif.

#### B. Paste daftar dari WhatsApp

1. Klik **Paste List WA**.
2. Tempel teks daftar dari grup WhatsApp. Format yang didukung:
   - Baris bernomor: `1. Budi`, `2. Andi`, `3. .`
   - Baris dengan level: `4. Dodi (Advance)`
   - Baris dengan nomor telepon
   - Sub-judul **Waiting List:** untuk menandai peserta daftar tunggu
3. Klik impor. Baris kosong dan tanda titik diabaikan.
4. Nama yang sudah ada di sesi tidak diduplikasi.

#### C. Absensi

- Klik badge status pemain untuk mengubah **Hadir** ↔ **Tidak Hadir**.
- Konfirmasi perubahan diperlukan.
- Pemain **Tidak Hadir** tidak dimasukkan ke rotasi, tetapi tetap tercatat pada daftar sesi.

#### D. Status pembayaran

- Gunakan chip **Lunas** / **Belum Bayar** pada tab Pemain.
- Status **Lunas** menambah pemasukan iuran sesi dan laporan kas admin.
- Status **Belum Bayar** masuk ke daftar piutang.
- Iuran pemain **Waiting List** dan **Tidak Hadir** tetap dapat muncul pada perhitungan piutang sampai dilunasi atau dihapus dari sesi.

#### E. Waiting list

- Peserta yang melebihi kuota masuk ke bagian **Waiting List (n)**.
- Gunakan tombol edit pemain untuk memindahkan status antara **Pemain Sesi Utama** dan **Waiting List**.
- Gunakan aksi promosi untuk memindahkan peserta waiting list menjadi peserta utama ketika ada slot kosong.
- Waiting list tidak dihitung sebagai peserta utama pada kuota, tetapi tetap terlihat oleh PIC.

#### F. Menghapus peserta dari sesi

Gunakan tombol hapus pada baris peserta. Konfirmasi diperlukan. Menghapus peserta dari sesi tidak menghapus master pemain dari roster.

### 3.6 Tab Kas

Menampilkan:

- **SALDO KAS SESI INI** = iuran terkumpul + pemasukan tambahan − biaya sesi.
- **Iuran Pemain** — total lunas, belum bayar, dan jumlah orang.
- **Biaya & Kas Sesi** — daftar pengeluaran sesi.
- Tombol **+ Catat Biaya** untuk mencatat pengeluaran atau pemasukan tambahan sesi.

Kategori biaya yang tersedia di aplikasi PIC:

**Sewa Lapangan · Shuttlecock · Konsumsi · Honor PIC · Iuran Tambahan · Sponsor · Peralatan · Lain-lain**

Catatan:

- Biaya yang dicatat PIC langsung memengaruhi laporan kas admin.
- Gunakan kategori yang konsisten agar laporan per sesi mudah dibaca.
- Transaksi non-sesi (kas umum) dicatat oleh Admin di CMS, bukan dari aplikasi PIC.

### 3.7 Tab Foto

1. Klik **+ Unggah Foto Dokumentasi Mabar Baru**.
2. Pilih jenis media: **Foto** atau **Video**.
3. Isi judul, keterangan/caption, dan tanggal aktivitas bila perlu.
4. Pilih file dari perangkat.
5. Klik **Unggah Dokumentasi Foto & Video ke Web**.

Ketentuan:

- Foto dan video yang diunggah dari sesi otomatis dikaitkan ke sesi tersebut.
- Media tampil di galeri publik setelah berhasil diunggah.
- Format video yang didukung bergantung pada browser dan konfigurasi server; format aman adalah `.mp4` (H.264/AAC).
- Ukuran file dibatasi oleh konfigurasi PHP (`upload_max_filesize`, `post_max_size`). Jika unggahan gagal, periksa kedua nilai tersebut.

### 3.8 Tab Klasemen

Menampilkan **Klasemen Poin Sesi** untuk sesi aktif. Gunakan tab ini untuk membacakan peringkat sementara kepada pemain tanpa membuka CMS.

### 3.9 Menutup sesi

1. Setelah seluruh pertandingan selesai, klik **Selesai Sesi Mabar**.
2. Konfirmasi dengan **Ya, Selesaikan Sesi Ini**.
3. Sesi berstatus **completed** dan terkunci.
4. Banner **SESI MABAR TELAH SELESAI & TERKUNCI** muncul.
5. Pada kondisi terkunci, PIC tidak dapat mengubah absensi, rotasi, atau skor. Admin masih dapat membuka kunci melalui CMS.

Setelah sesi selesai, tersedia:

- **⚡ Buat Sesi Pekan Depan →** untuk menduplikasi konfigurasi sesi ke tanggal +7 hari.
- **Salin Rekap WA** untuk menyalin ringkasan sesi (jumlah hadir, iuran masuk, pemain belum lunas) ke WhatsApp.
- **Pilih Sesi Lain** untuk berpindah sesi.

---

## 4. CMS ADMIN (`/admin/`)

### 4.1 Login dan struktur navigasi

1. Buka `https://domain-anda.com/admin/`.
2. Masukkan username dan password admin.
3. Klik login.

Sidebar dibagi menjadi tiga grup:

**Menu Utama**

- Dashboard Ringkasan
- Laporan Keuangan & Kas
- Jadwal & Kuota Sesi
- + Tambah Jadwal Baru
- Galeri Dokumentasi
- Roster Pemain Cabang
- Leaderboard Bulanan

**Manajemen Sistem**

- Akun PIC & Tim Media
- Profil & Web Settings
- Keamanan Akun
- Buku Panduan & Demo

**Akses Langsung**

- Web Depan Mabar
- App PIC Operasional

### 4.2 Dashboard Ringkasan

Menampilkan ringkasan operasional komunitas, antara lain:

- Jumlah pemain pada roster.
- Jumlah sesi jadwal.
- Ringkasan kas.
- Pemain teratas.
- Akses cepat ke panduan pengurus dan menu utama.

Gunakan dashboard sebagai pemeriksaan harian sebelum membuka detail.

### 4.3 Laporan Keuangan & Kas

#### A. Filter laporan

- **Chip bulan:** 12 bulan terakhir, **Bulan ini**, dan **Semua**.
- **Filter sesi:** **Semua sesi & cabang** atau satu sesi tertentu.
- **Jenis transaksi:** **Semua**, **Masuk**, **Keluar**.
- Tombol **Reset** untuk menghapus filter sesi.

#### B. KPI utama

| KPI | Arti |
|---|---|
| **Saldo bersih** | Uang masuk − uang keluar; badge **SURPLUS** atau **DEFISIT** |
| **Iuran lunas** | Total iuran peserta berstatus lunas dan jumlah pemain |
| **Belum bayar** | Total piutang iuran dan jumlah orang; klik untuk melihat daftar |
| **Pemasukan lain** | Sponsor, denda, donasi, dan pemasukan non-iuran |
| **Pengeluaran** | Sewa GOR, kok, operasional |

Perbandingan dengan periode sebelumnya ditampilkan sebagai **Naik/Turun** bila data tersedia.

#### C. Empat panel laporan

1. **Buku Kas** — daftar transaksi per tanggal, dengan total harian.
2. **Per Sesi** — laba/rugi setiap sesi.
3. **Piutang** — daftar pemain belum bayar, dengan tombol **Ingatkan** via WhatsApp.
4. **Analisis** — ringkasan komposisi pemasukan dan pengeluaran.

#### D. Mencatat transaksi

1. Klik **+ Catat Transaksi**.
2. Pilih jenis: **Pengeluaran** atau **Pemasukan Tambahan**.
3. Pilih kategori: **Sewa Lapangan, Shuttlecock, Konsumsi, Honor PIC, Lain-lain** (pilihan dapat berbeda pada aplikasi PIC).
4. Pilih sesi terkait, atau biarkan kosong untuk kas umum.
5. Isi judul, nominal, tanggal, dan catatan.
6. Klik **Simpan**.

Untuk mengubah transaksi, gunakan aksi edit pada baris transaksi. Untuk menghapus, gunakan tombol **Hapus** dan konfirmasi.

#### E. Ekspor dan cetak

- **Export CSV** mengunduh laporan sesuai filter aktif.
- **Cetak** membuka dialog cetak browser dengan tata letak bebas sidebar.

#### F. Piutang

Panel **Piutang** menampilkan peserta yang belum lunas. Tombol **Ingatkan** membuka WhatsApp dengan pesan penagihan. Setelah pembayaran diterima, tandai lunas dari aplikasi PIC atau dari aksi pembayaran pada sesi terkait.

### 4.4 Jadwal & Kuota Sesi

Halaman ini menampilkan daftar sesi dan aksi per sesi:

- **Template WA** — membuka generator pesan WhatsApp.
- **+ Buka Sesi Pekan Depan (+7 Hari)** — menduplikasi sesi ke tanggal +7 hari.
- **Hapus Sesi** — menghapus sesi beserta data terkait (konfirmasi diperlukan).
- Ubah status sesi: **open**, **in_progress**, **completed**.

Catatan penting tentang duplikasi:

- Tanggal ditambah 7 hari.
- Status sesi baru = **open**.
- Konfigurasi venue, jam, lapangan, kuota, iuran, dan kontak PIC ikut tersalin.
- **Peserta tidak ikut tersalin.**
- **Penugasan akun PIC (`pic_id`) tidak ikut tersalin**; tugaskan ulang PIC pada sesi baru.

#### Generator Broadcast WhatsApp

1. Klik **Template WA** pada sesi.
2. Sesuaikan parameter:
   - Wilayah / Judul Sesi
   - Hari & Sesi
   - Pilihan Lapangan
   - Jam Berapa (Waktu Main)
   - Biaya Iuran per Orang
   - Maksimal Kuota Peserta
   - Nama Tempat / GOR
   - Link Google Maps
   - Info Rekening Pembayaran
   - Kontak Konfirmasi (WhatsApp)
   - Nama Host di WA
   - Aturan Cancel Sesi
3. Pratinjau **Live Generated WhatsApp Template** berubah seketika.
4. Klik **Simpan Pengaturan ke Database Sesi** agar parameter tersimpan.
5. Klik **Salin Format Pesan WhatsApp** untuk menyalin teks.

#### Impor daftar peserta dari WhatsApp

1. Pada panel **Input / Paste List Peserta dari WhatsApp**, tempel teks daftar.
2. Klik **Import Nama ke Sesi Mabar Ini →**.
3. Sistem mengabaikan nomor urut dan tanda titik, lalu menambahkan nama ke sesi.
4. Nama yang sudah ada tidak diduplikasi.
5. Peserta di bawah sub-judul **Waiting List:** dimasukkan ke daftar tunggu.

Pembersihan peserta:

- Gunakan aksi hapus pada baris peserta untuk mengeluarkan peserta dari sesi.
- Menghapus peserta dari sesi tidak menghapus pemain dari roster.

### 4.5 Tambah / Edit Jadwal

Menu **+ Tambah Jadwal Baru** membuka formulir:

| Bagian | Isi |
|---|---|
| Identitas sesi | Nama hari/sesi, wilayah/cabang, tanggal |
| Waktu | Jam mulai dan jam selesai |
| Venue | Nama GOR, alamat, link Google Maps, fasilitas |
| Kapasitas | Jumlah lapangan, kuota maksimal, iuran per orang |
| PIC | Akun PIC dan/atau nama + nomor WhatsApp PIC |
| Catatan | Informasi tambahan sesi |

Tombol simpan:

- **Buat Jadwal Baru** untuk jadwal baru.
- **Simpan Perubahan Jadwal** untuk edit.

Validasi umum:

- Kuota minimal 4 dan maksimal 50.
- Jumlah lapangan 1–10.
- Nama venue, tanggal, jam, dan iuran wajib diisi.

Google Maps:

- Pencarian venue dapat mengisi alamat dan link peta secara otomatis jika **Google Maps Places API Key** diisi.
- Tanpa API key, isi alamat dan URL peta secara manual.

### 4.6 Galeri Dokumentasi

- Menampilkan seluruh media.
- **Tambah media**: isi judul, URL gambar/video atau unggah file, caption, sesi terkait, tanggal aktivitas, dan tipe media.
- **Hapus Foto**: menghapus media dari galeri dan landing page.
- Media dapat disematkan ke sesi tertentu.

Catatan:

- Gunakan rasio foto landscape untuk hasil terbaik.
- Untuk video, gunakan URL publik atau unggahan yang dapat diakses browser.
- Galeri publik membatasi tampilan maksimal 120 media terbaru; media lama tetap tersimpan di database.

### 4.7 Roster Pemain Cabang

Fitur:

- Daftar master pemain.
- Filter berdasarkan cabang/wilayah dan sesi.
- Tambah pemain baru: nama, level, nomor WhatsApp.
- Edit level dan data pemain.
- **Hapus Terpilih** untuk hapus masal.
- **Hapus** per pemain.

Peringatan:

- Menghapus pemain bersifat permanen dan juga menghapus keterkaitannya dengan sesi/pertandingan.
- Penghapusan pemain dapat mengubah laporan historis. Lakukan hanya untuk data uji atau data yang memang harus dihapus.
- Untuk pemain yang berhenti bermain, pertimbangkan menonaktifkan daripada menghapus.

### 4.8 Leaderboard Bulanan

Halaman ini menampilkan klasemen serta alat koreksi:

1. **Kalkulasi Ulang Leaderboard Berdasarkan Match Records**
   - Pilih bobot **Win Bonus**: +5 (standar), +3, +10, atau 0.
   - Klik **Hitung Ulang Leaderboard Sekarang**.
   - Rumus: `Total Poin = Akumulasi Poin Reli + (Jumlah Menang × Win Bonus)`.

2. **Reset Poin Roster Bulan Baru**
   - Mengosongkan akumulasi poin pemain.
   - Konfirmasi diperlukan.
   - Reset tidak menghapus riwayat pertandingan. Jika **Hitung Ulang** dijalankan setelah reset, poin akan dihitung kembali dari riwayat pertandingan. Untuk benar-benar memulai periode baru, arsipkan atau bersihkan riwayat pertandingan terlebih dahulu, atau gunakan penyesuaian manual.

3. **Koreksi / Penyesuaian Poin Pemain Manual**
   - Pilih pemain, masukkan delta poin (positif/negatif), dan alasan.
   - Berguna untuk bonus turnamen internal atau penalti.

### 4.9 Akun PIC & Tim Media

Fitur:

- KPI: **Total Akun PIC**, **Total Sesi Mabar**, **Sudah Ada PIC**, **Belum Ada PIC**.
- **1-Click Generate PIC Demo** — membuat akun PIC demo dengan password acak dan menampilkannya sekali di pesan sukses.
- **+ Tambah Akun PIC** — membuat akun manual: nama, username, password, nomor WhatsApp, peran (`pic` atau `pic_foto`), dan sesi yang ditugaskan.
- **Penugasan massal** — memilih beberapa sesi sekaligus untuk satu PIC.
- Hapus akun PIC.

Ketentuan:

- Username harus unik.
- Akun `pic_foto` hanya diarahkan ke tab Foto.
- Setelah membuat akun, uji login di `/app/` sebelum menyerahkan kredensial kepada PIC.
- Ganti password akun demo sebelum digunakan pada acara nyata.

### 4.10 Profil & Web Settings

Empat sub-tab:

1. **Identitas & Kontak**
   - Logo (unggah file atau URL).
   - Nama komunitas.
   - Slogan/tagline.
   - Kota/basis wilayah.
   - Nomor WhatsApp PIC default.
   - Instagram resmi.
   - Deskripsi profil di footer.

2. **Hero & Banner**
   - Paragraf sambutan.
   - Teks tombol CTA.
   - Foto banner hero (unggah atau URL).

3. **Alur Ikut & FAQ**
   - Judul dan deskripsi tiga langkah pendaftaran.
   - Tambah/hapus FAQ (pertanyaan dan jawaban).

4. **Google SEO & Sosmed**
   - Meta title, meta description, focus keyphrase.
   - Google Search Preview.
   - Facebook/WhatsApp share preview.
   - OG image.
   - Pengaturan indeks (`index, follow` dan sejenisnya).

Setelah mengubah pengaturan, klik tombol simpan di bagian bawah formulir. Perubahan langsung tampil di landing page.

### 4.11 Keamanan Akun

- Form ubah password admin: password saat ini, password baru, konfirmasi.
- Password disimpan sebagai hash bcrypt.
- Tidak ada fitur lupa password via email. Jika password hilang, reset melalui database atau buat ulang akun admin dengan akses server.

### 4.12 Buku Panduan & Demo

Tab ini menautkan:

- `/guide.php` — buku panduan interaktif yang dapat dicetak.
- `admin/manual-book-pdf.php` — playbook demo dan skrip presentasi dalam tata letak A4.

Gunakan halaman ini sebagai materi onboarding pengurus baru dan PIC.

---

## 5. ATURAN PERHITUNGAN: ROTASI, POIN, DAN KAS

### 5.1 Rotasi

**Jumlah lapangan efektif**

```text
lapangan_efektif = min(jumlah_lapangan_sesi, floor(jumlah_pemain_hadir / 4))
```

**Syarat:** minimal 4 pemain hadir.

**Pemilihan pemain**

1. Pemain diurutkan berdasarkan jumlah bermain paling sedikit.
2. Jika sama, jumlah istirahat paling banyak.
3. Jika sama, urutan pendaftaran.

**Penyusunan pasangan**

- Empat pemain per lapangan.
- Tim A terdiri dari 2 pemain, Tim B terdiri dari 2 pemain.
- Mesin mencoba hingga 200 kombinasi acak.
- Penalti:
  - Selisih total level Tim A vs Tim B × 50.
  - Pasangan yang pernah berpasangan × 20.
  - Lawan yang pernah bertemu × 5.
- Kombinasi dengan penalti terkecil dipilih.

**Level**

| Label | Bobot |
|---|---|
| Beginner / Pemula | 1 |
| Intermediate | 2 |
| Advance / Mahir | 3 |

### 5.2 Poin dan leaderboard

**Poin per pertandingan**

```text
Jika menang : poin = skor_tim_sendiri + win_bonus
Jika kalah : poin = skor_tim_sendiri
Jika seri  : dihitung kalah untuk kedua tim
```

`win_bonus` standar = 5.

**Total poin**

```text
total_poin = akumulasi_poin_reli + (jumlah_menang × win_bonus)
```

**Urutan peringkat**

1. Total poin.
2. Jumlah menang.
3. Win rate.
4. Streak.

**Statistik lain yang disimpan**

- Total pertandingan.
- Menang.
- Kalah.
- Poin yang kebobolan.
- Streak saat ini.

### 5.3 Kas komunitas

**Kas admin**

```text
total_uang_masuk = total_iuran_lunas + total_pemasukan_tambahan
saldo_bersih     = total_uang_masuk − total_pengeluaran
```

**Kas per sesi**

```text
laba_sesi = (iuran_lunas + pemasukan_tambahan_sesi) − pengeluaran_sesi
```

**Piutang**

```text
piutang = jumlah iuran peserta yang belum lunas
```

Catatan:

- Peserta waiting list dan tidak hadir dapat tetap muncul pada piutang sampai statusnya dilunasi atau peserta dihapus dari sesi.
- Status lunas mencatat waktu pembayaran; status belum lunas tidak menambah pemasukan.
- Penghapusan pemain atau peserta dapat mengubah laporan historis.

### 5.4 Kuota, waiting list, dan status sesi

**Kuota**

- Peserta utama dihitung terhadap `max_slots`.
- Peserta setelah kuota penuh menjadi **waiting_list**.
- Waiting list tidak otomatis menggantikan peserta yang batal; PIC/Admin harus memindahkannya secara manual.

**Status sesi**

| Status | Arti |
|---|---|
| `open` | Sesi dibuka dan dapat menerima peserta |
| `in_progress` | Sesi sedang berjalan; aplikasi lapangan aktif |
| `completed` | Sesi selesai dan terkunci untuk PIC |
| `cancelled` | Tersedia di database, tetapi tidak diekspos sebagai aksi utama di UI |

---

## 6. INSTALASI, KONFIGURASI & GO-LIVE

### 6.1 Prasyarat

- PHP 7.4+ dengan `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`.
- MySQL 5.7+/8.x atau MariaDB 10.3+.
- Apache dengan `mod_rewrite` dan `AllowOverride All`.
- Akses tulis ke folder unggahan (logo, hero, galeri).
- HTTPS untuk produksi.

### 6.2 Struktur folder penting

```text
/
├── index.php                 # Dispatcher landing & routing
├── router.php                # Router untuk PHP built-in server
├── .htaccess                 # Rewrite Apache
├── admin/                    # CMS Admin
├── app/                      # Aplikasi PIC + manifest PWA
├── api/                      # Endpoint JSON internal
├── config/                   # database.php, setup.php
├── core/                     # Database, Auth, Security, RotationEngine
├── models/                   # Community, Schedule, Participant, MatchRecord, Player, Finance, Article
├── views/landing/            # Tampilan landing page
├── gallery.php               # Galeri publik
├── guide.php                 # Buku panduan interaktif
├── database_backup.sql       # Dump skema + data demo (opsional, bukan installer utama)
└── public/uploads/           # Media unggahan (pastikan writable)
```

### 6.3 Menyiapkan database

**Opsi A — Installer bersih (disarankan untuk produksi)**

1. Sesuaikan kredensial database di `config/database.php`.
2. Jalankan installer sekali:

   ```bash
   php config/setup.php
   ```

   Installer membuat database, seluruh tabel, pengaturan awal, dan satu akun admin. Tidak ada data demo.
3. Untuk instalasi dengan data demo (presentasi/internal), jalankan:

   ```bash
   php config/setup.php --demo
   ```

4. Password admin awal didefinisikan sebagai `SMASHPOINT_INITIAL_ADMIN_PASSWORD` di bagian atas `config/setup.php` (default `admin123`). **Ganti segera setelah login pertama.**
5. Hapus atau kunci akses ke `config/setup.php` setelah selesai.

**Opsi B — Impor dump demo**

1. Buat database baru, misalnya `powershoot_db`, dengan charset `utf8mb4`.
2. Impor `database_backup.sql` melalui phpMyAdmin, Adminer, atau CLI:

   ```bash
   mysql -u root -p powershoot_db < database_backup.sql
   ```

3. Dump berisi skema dan data demo, termasuk akun `admin` dengan password `admin123` dan akun PIC contoh. **Ganti password dan hapus data demo sebelum produksi** (lihat 6.7).

Catatan: `config/setup.php` kini membuat skema lengkap yang dipakai aplikasi. Dump tetap berguna untuk demo, tetapi bukan satu-satunya sumber skema. Selalu uji instalasi di lingkungan staging sebelum go-live.

### 6.4 Konfigurasi database

Edit `config/database.php`:

```php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'powershoot_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

Jangan mengunggah kredensial produksi ke repositori publik.

Nilai host, port, nama database, user, dan password juga dapat dioverride lewat environment variable `SMASHPOINT_DB_HOST`, `SMASHPOINT_DB_PORT`, `SMASHPOINT_DB_NAME`, `SMASHPOINT_DB_USER`, dan `SMASHPOINT_DB_PASS` tanpa mengubah file.

### 6.5 Konfigurasi web server

**Apache**

- Aktifkan `mod_rewrite`.
- Arahkan document root ke folder aplikasi.
- Pastikan `.htaccess` terbaca (`AllowOverride All`).
- Aplikasi dirancang untuk berjalan di root domain. Jika dipasang di subfolder, sesuaikan `RewriteBase` dan basis URL aplikasi.

**Nginx**

Terapkan rewrite ekuivalen:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**PHP built-in server (pengembangan)**

```bash
php -S localhost:8000 router.php
```

### 6.6 Login pertama dan hardening

1. Buka `/admin/`.
2. Login dengan akun admin yang dibuat installer.
3. Segera buka **Keamanan Akun** dan ganti password.
4. Hapus akun/demo PIC yang tidak dipakai.
5. Hapus data contoh: pemain, jadwal, pertandingan, transaksi, galeri.
6. Isi **Profil & Web Settings** dengan identitas komunitas yang sebenarnya.
7. Uji alur publik → PIC → admin.

Checklist hardening minimum:

- [ ] Password admin diganti dan unik.
- [ ] Akun PIC demo dihapus atau password-nya diganti.
- [ ] `display_errors` dimatikan di produksi.
- [ ] HTTPS aktif dan cookie sesi `Secure`.
- [ ] Folder `uploads/` tidak mengeksekusi PHP.
- [ ] Akses langsung ke `database_backup.sql`, `config/`, dan file `.md` internal dibatasi.
- [ ] `config/setup.php` dihapus atau dikunci setelah instalasi.
- [ ] Backup database terjadwal.
- [ ] Uji restore backup.

### 6.7 Membersihkan data demo

Data demo hanya masuk jika Anda memilih salah satu jalur berikut:

- Menjalankan `php config/setup.php --demo`.
- Mengimpor `database_backup.sql`.

Data demo tersebut mencakup:

- Akun admin `admin` dengan password awal `admin123` dan akun PIC contoh.
- Sesi contoh, peserta, pemain, pertandingan.
- Transaksi keuangan contoh.
- Artikel, galeri, dan pengaturan contoh.

Langkah pembersihan yang aman:

1. Backup database.
2. Buat akun admin baru dengan password kuat.
3. Hapus akun demo setelah akun baru berfungsi.
4. Kosongkan tabel transaksi, pertandingan, peserta, jadwal, pemain, galeri, dan artikel jika ingin memulai dari nol.
5. Pertahankan tabel `settings`, lalu sesuaikan isinya melalui CMS.

Urutan penghapusan harus menghormati foreign key: hapus `matches` dan `session_participants` sebelum `schedules`, lalu `players`.

### 6.8 Google Maps (opsional)

- Isi **Google Maps Places API Key** pada formulir jadwal untuk autocomplete venue.
- Tanpa API key, alamat dan URL peta diisi manual.
- Batasi API key pada domain produksi dan aktifkan hanya Places API yang diperlukan.

### 6.9 PWA

- Manifest PWA tersedia untuk `/app/`.
- Landing page dan galeri tidak memiliki manifest terpisah.
- PWA memerlukan HTTPS (kecuali `localhost`) agar dapat dipasang.

---

## 7. SOP OPERASIONAL MINGGUAN

### 7.1 H-7 sampai H-3 — Perencanaan

- [ ] Konfirmasi booking GOR dan jumlah lapangan.
- [ ] Buat jadwal di CMS: tanggal, jam, venue, alamat, peta, lapangan, kuota, iuran.
- [ ] Tugaskan akun PIC ke sesi.
- [ ] Siapkan parameter WA: rekening, aturan cancel, kontak konfirmasi.
- [ ] Salin template WA dan broadcast ke grup.
- [ ] Buka pendaftaran.

### 7.2 H-3 sampai H-1 — Pendaftaran

- [ ] Impor daftar WA ke sesi.
- [ ] Verifikasi pembayaran dan tandai lunas.
- [ ] Kelola waiting list jika kuota penuh.
- [ ] Kirim pengingat kepada peserta yang belum bayar.
- [ ] Konfirmasi ulang jumlah lapangan dengan GOR.

### 7.3 Hari-H, 30 menit sebelum sesi

- [ ] PIC tiba di venue dan mengecek lapangan, net, pencahayaan.
- [ ] Login `/app/` dan pastikan sesi yang terbuka benar.
- [ ] Cek daftar peserta dan status hadir.
- [ ] Siapkan kok dan peralatan.
- [ ] Tambahkan walk-in jika ada.

### 7.4 Selama sesi

- [ ] Tandai kehadiran peserta.
- [ ] Konfirmasi pembayaran.
- [ ] Generate rotasi per lapangan atau serentak.
- [ ] Input dan simpan skor setiap ronde.
- [ ] Catat biaya sesi bila ada.
- [ ] Unggah foto/video dokumentasi.

### 7.5 Setelah sesi

- [ ] Pastikan semua skor tersimpan.
- [ ] Periksa kas sesi dan piutang.
- [ ] Klik **Selesai Sesi Mabar**.
- [ ] Salin **Rekap WA** dan kirim ke grup.
- [ ] Buat sesi pekan depan dengan **+7 Hari**.
- [ ] Tugaskan ulang PIC pada sesi baru.
- [ ] Rekap laporan kas di CMS.

### 7.6 Akhir bulan

- [ ] Periksa laporan kas dan laba/rugi per sesi.
- [ ] Ekspor CSV untuk arsip.
- [ ] Tinjau leaderboard.
- [ ] Tentukan apakah periode poin perlu direset.
- [ ] Backup database.

---

## 8. TROUBLESHOOTING & FAQ

**Sesi tidak muncul di landing page.**  
Periksa status sesi. Sesi `completed` dan `cancelled` tidak ditampilkan sebagai sesi yang dapat didaftari. Pastikan tanggal sesi belum lewat dan filter pekan/tanggal tidak menyembunyikannya.

**Tombol daftar WhatsApp tidak membuka WhatsApp.**  
Pastikan nomor PIC diisi dengan format internasional yang benar (misalnya `62812...`) atau nomor lokal yang dapat dinormalisasi. Periksa juga pemblokiran pop-up pada browser.

**PIC login tetapi masuk ke sesi yang salah.**  
PIC memilih sesi terakhir di browser. Gunakan **Ganti Sesi** → **Pindah ke Sesi Lain**. Jika akun PIC ditugaskan ke sesi yang salah, perbaiki penugasan di CMS.

**PIC tidak bisa mengubah data karena sesi terkunci.**  
Sesi berstatus `completed`. Admin harus mengubah status sesi menjadi `open` atau `in_progress` di CMS. Pastikan perubahan status disengaja karena membuka kembali sesi memungkinkan perubahan skor.

**Generate rotasi tidak berjalan.**  
Pastikan minimal 4 pemain berstatus **Hadir** dan jumlah lapangan sesi minimal 1. Pemain **Tidak Hadir** tidak dihitung.

**Pasangan terasa tidak seimbang.**  
Gunakan strategi **Fair Level (Kombinasi Seimbang)** dan pastikan level pemain di roster benar. Mesin hanya sebaik data level yang diisi.

**Skor sudah diubah tetapi tidak masuk leaderboard.**  
Skor hanya tersimpan setelah **Simpan Skor & Selesaikan Lapangan n** ditekan. Setelah itu, jalankan **Hitung Ulang Leaderboard** di CMS bila perlu.

**Poin tidak kembali nol setelah reset.**  
Reset hanya mengosongkan akumulasi. **Hitung Ulang Leaderboard** akan menghitung kembali dari riwayat pertandingan. Untuk periode baru yang benar-benar bersih, arsipkan atau hapus riwayat pertandingan terlebih dahulu.

**Peserta sudah bayar tetapi masih muncul di piutang.**  
Periksa status pembayaran peserta pada sesi yang benar. Satu pemain dapat terdaftar di beberapa sesi; pelunasan harus dilakukan pada sesi yang dimaksud.

**Peserta batal hadir di hari-H.**  
Ubah status menjadi **Tidak Hadir** agar tidak masuk rotasi. Jika kebijakan komunitas menagih iuran, biarkan status pembayaran belum lunas. Jika peserta diganti, pindahkan peserta waiting list menjadi peserta utama.

**Peserta melebihi kuota.**  
Peserta otomatis masuk **Waiting List**. Promosikan secara manual saat ada slot kosong. Jangan menaikkan kuota tanpa mempertimbangkan kapasitas lapangan.

**Unggahan foto/video gagal.**  
Periksa `upload_max_filesize`, `post_max_size`, izin folder `uploads/`, dan format file. Untuk video, gunakan `.mp4` H.264/AAC. Untuk file besar, unggah melalui URL publik atau naikkan batas PHP.

**Galeri publik tidak menampilkan media baru.**  
Pastikan media berhasil tersimpan, tidak terhapus, dan berada dalam 120 media terbaru. Periksa juga filter sesi pada halaman galeri.

**Halaman admin menampilkan error 500 setelah pindah server.**  
Periksa versi PHP, ekstensi `pdo_mysql`, kredensial `config/database.php`, izin folder, dan log error web server. Pastikan skema database lengkap.

**Routing `/app/` atau `/admin/` 404.**  
Pastikan `mod_rewrite` aktif, `.htaccess` terbaca, dan aplikasi berada di root domain. Jika di subfolder, sesuaikan `RewriteBase`.

**Login gagal setelah instalasi.**  
Pastikan akun admin benar-benar dibuat. Installer bersih dan dump sama-sama memakai kredensial awal `admin` / `admin123`; segera ganti setelah login pertama. Jika lupa, reset password melalui hash bcrypt baru di database.

**Apakah PIC bisa membuka laporan keuangan admin?**  
Pada versi ini, pembatasan menu admin per peran belum granular. Secara operasional, PIC hanya diberi akses `/app/`, dan kredensial admin tidak dibagikan. Untuk kepatuhan penuh, terapkan pembatasan akses di web server atau tambahkan RBAC sebelum produksi.

**Apakah klasemen benar-benar bulanan?**  
Label "Bulanan" adalah nama tampilan. Perhitungan memakai seluruh riwayat pertandingan yang tersimpan, atau satu sesi bila filter sesi dipilih. Reset periode dilakukan manual oleh Admin.

**Apakah ada aplikasi Android/iOS?**  
Tidak. Aplikasi PIC adalah web app yang dapat dipasang ke layar utama melalui fitur PWA pada `/app/`.

---

## 9. LAMPIRAN: UAT, GLOSARIUM & BATASAN VERSI

### 9.1 Checklist UAT sebelum go-live

**Publik**

- [ ] Landing page tampil di desktop dan mobile.
- [ ] Filter Semua/Minggu Ini/Minggu Depan/Mendatang berfungsi.
- [ ] Filter rentang tanggal dan Reset berfungsi.
- [ ] Kartu sesi menampilkan kuota yang benar.
- [ ] Tombol WhatsApp membuka pesan dengan venue, tanggal, dan PIC yang benar.
- [ ] Klasemen overall dan per sesi tampil.
- [ ] Galeri, pencarian, filter sesi, dan lightbox berfungsi.
- [ ] FAQ tampil dan accordion berfungsi.

**PIC**

- [ ] Login dengan username dan nomor WhatsApp.
- [ ] Sesi yang ditugaskan terbuka otomatis.
- [ ] Ganti sesi berfungsi.
- [ ] Tambah walk-in dan paste list WA berfungsi.
- [ ] Absensi Hadir/Tidak Hadir tersimpan.
- [ ] Status Lunas/Belum Bayar tersimpan dan memengaruhi kas.
- [ ] Waiting list dapat dipromosikan.
- [ ] Generate satu lapangan dan semua lapangan berfungsi.
- [ ] Ketiga strategi rotasi dapat dipilih.
- [ ] Skor tersimpan dan poin masuk.
- [ ] Hapus pertandingan/ronde berfungsi.
- [ ] Kas sesi menampilkan saldo yang benar.
- [ ] Upload foto dan video tampil di galeri.
- [ ] Selesai sesi mengunci data.
- [ ] Rekap WA dan buat sesi +7 hari berfungsi.

**Admin**

- [ ] Login, logout, dan ganti password.
- [ ] Dashboard menampilkan ringkasan.
- [ ] Filter keuangan (bulan, sesi, jenis) berfungsi.
- [ ] Catat/edit/hapus transaksi berfungsi.
- [ ] Export CSV dan cetak berfungsi.
- [ ] Piutang dan tombol Ingatkan berfungsi.
- [ ] Buat/edit/hapus jadwal berfungsi.
- [ ] Duplikasi +7 hari tidak menyalin peserta dan `pic_id`.
- [ ] Template WA dan simpan parameter berfungsi.
- [ ] Impor daftar WA berfungsi.
- [ ] Tambah/edit/hapus media berfungsi.
- [ ] Filter roster, edit level, dan hapus masal berfungsi.
- [ ] Hitung ulang leaderboard dan reset poin berfungsi.
- [ ] Buat akun PIC, tugaskan sesi, dan hapus akun berfungsi.
- [ ] Semua sub-tab pengaturan tersimpan dan tampil di landing.
- [ ] Logo/hero dapat diunggah.

**Non-fungsional**

- [ ] HTTPS aktif.
- [ ] Backup dan restore diuji.
- [ ] Halaman utama < 3 detik pada koneksi 4G.
- [ ] Tidak ada error PHP di log.
- [ ] Folder unggahan tidak mengeksekusi PHP.
- [ ] File instalasi dan dump database tidak dapat diakses publik.

### 9.2 Glosarium

| Istilah | Arti |
|---|---|
| **Mabar** | Main bareng badminton |
| **PIC** | Person in charge; penanggung jawab sesi di lapangan |
| **GOR** | Gedung olahraga |
| **Court / Lapangan** | Satu lapangan badminton; 4 pemain per ronde |
| **Ronde** | Satu putaran pertandingan pada seluruh lapangan |
| **Bench / Istirahat** | Pemain yang tidak bermain pada ronde tertentu |
| **Waiting List** | Daftar tunggu ketika kuota sesi penuh |
| **Walk-in** | Peserta yang mendaftar langsung di lokasi |
| **Win Bonus** | Tambahan poin untuk tim pemenang (standar +5) |
| **Piutang** | Iuran peserta yang belum lunas |
| **Net Cashflow** | Uang masuk dikurangi uang keluar |
| **PWA** | Progressive Web App; web app yang dapat dipasang ke layar utama |

### 9.3 Batasan versi ini

Dokumen ini jujur mencantumkan batasan agar ekspektasi pembeli dan operator sesuai:

1. **Leaderboard bukan periode kalender otomatis.** Reset periode dilakukan manual; hitung ulang dapat mengembalikan poin dari riwayat pertandingan.
2. **Pembatasan admin per peran belum granular.** Akun PIC secara teknis dapat membuka `/admin/` jika mengetahui URL. Lindungi dengan kredensial dan pembatasan web server.
3. **Tidak ada CSRF token pada formulir admin dan API internal tidak memakai autentikasi token.** Untuk produksi, tambahkan perlindungan CSRF dan pembatasan akses API.
4. **Tidak ada fitur lupa password via email.** Reset dilakukan melalui database atau akun admin lain.
5. **Tidak ada aplikasi mobile native.** Hanya PWA untuk `/app/`.
6. **Galeri publik membatasi 120 media terbaru.**
7. **Status `cancelled` ada di database tetapi tidak diekspos sebagai aksi UI utama.**
8. **Penghapusan pemain bersifat permanen dan dapat mengubah laporan historis.**
9. **Skor tidak tersimpan otomatis.** Harus menekan tombol simpan.
10. **Duplikasi sesi +7 hari tidak menyalin peserta dan penugasan akun PIC.**
11. **Unggahan file dibatasi konfigurasi PHP dan bergantung pada izin folder.**
12. **Branding dan nomor kontak contoh pada dump harus diganti sebelum produksi.**

### 9.4 Rekomendasi sebelum dijual sebagai produk komersial

- Ganti seluruh data demo dan branding contoh.
- Hapus akun demo dan file instalasi dari server produksi.
- Terapkan HTTPS, backup otomatis, dan monitoring uptime.
- Tambahkan CSRF protection dan autentikasi API.
- Tambahkan RBAC per halaman admin jika akan dijual ke banyak operator.
- Dokumentasikan versi PHP/database yang diuji dan sediakan changelog.
- Sertakan kontrak dukungan, cakupan kustomisasi, dan kebijakan pembaruan.

---

**Akhir dokumen.**  
Untuk panduan yang dapat dicetak dan tampilan interaktif, buka `/guide.php` dari CMS pada menu **Buku Panduan & Demo**.
