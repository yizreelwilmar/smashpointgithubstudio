# Penyesuaian Operasional Lapangan Mabar Badminton & Rotasi Adil

Rencana komprehensif untuk menyempurnakan alur kerja aplikasi operasional mabar bulutangkis berbasis PHP: menghilangkan dropdown pencarian (*select-search*) yang menyulitkan di layar smartphone, memastikan impor teks WhatsApp langsung masuk ke Pemain Sesi Utama tanpa kuota otomatis, menyederhanakan tombol aksi kehadiran dan pengaturan level/waiting list lewat modal edit, memperkuat algoritma rotasi berkeadilan, serta menampilkan indikator berapa kali pemain sudah bertanding baik di daftar pemain maupun di papan ronde acak.

---

### User Review & Critical Decisions

> [!IMPORTANT]
> **Keputusan Kunci Sesuai Permintaan Pengguna:**
> 1. **Anti-Select-Search di HP**: Seluruh kontrol input pemilihan level, status sesi, dan aksi operasional diubah menjadi tombol tap fisik besar (*touch-friendly button groups* min. 44px) yang nyaman ditekan dengan jempol tanpa memicu popup keyboard atau pencarian dropdown yang rumit.
> 2. **Impor List WhatsApp Murni ke Sesi Utama**: Teks list mabar yang ditempel (*paste*) akan langsung memasukkan seluruh nama bernomor ke **Pemain Sesi Utama** secara transparan tanpa pembatasan kuota otomatis. Hanya jika teks WA secara eksplisit memiliki sub-header "Waiting List:" barulah nama di bawahnya masuk waiting list.
> 3. **Aksi Kehadiran & Edit**: Baris pemain di Sesi Utama menyediakan tombol **Hadir** dan **Ga Hadir** langsung dengan indikator warna tegas, serta tombol **Edit** untuk mengatur Nama, No. HP, Level (Beginner / Intermediate / Advance), dan status (Pemain Sesi Utama vs Waiting List).
> 4. **Pelacakan "Berapa Kali Main"**: Menghitung dan menampilkan secara real-time frekuensi bermain (*games played counter*, misal `2x Main`) di:
>    - **Tab Pemain**: Di samping nama pemain pada daftar Sesi Utama dan Waiting List.
>    - **Tab Rotasi / Lapangan**: Pada kartu pertandingan Tim A & Tim B setelah diacak, serta pada daftar pemain istirahat (*bench queue*).

---

### 1. Overview & Core Concept

- **What It Does**: Memberikan kapten/PIC lapangan kendali operasional mabar bulutangkis yang cepat, ringan, dan adil langsung dari smartphone di pinggir GOR tanpa hambatan input, dengan visibilitas penuh terhadap keadilan menit bermain setiap peserta.
- **Target Audience / Persona**: Pengurus klub, kapten sesi, dan PIC lapangan yang mengelola rotasi 12–30 peserta mabar dengan smartphone satu tangan di tengah kebisingan dan dinamika lapangan bulutangkis.
- **Key Value**: Operasional super cepat tanpa friksi input select-search, transparansi pembagian tim yang seimbang, dan kepastian bahwa semua anggota yang hadir mendapatkan jatah bermain yang setara.

---

### 2. User Experience & Visual Design

#### A. Alur Pengguna (User Flows)
1. **Impor List WhatsApp**:
   - PIC membuka tab **Pemain** -> tekan tombol **Paste List WA**.
   - Menempelkan pesan WA -> tekan **Proses & Impor**.
   - Seluruh peserta bernomor langsung masuk ke **Pemain Sesi Utama** (status kehadiran otomatis default Hadir atau siap ditandai).
2. **Absensi & Pengaturan Status/Level**:
   - Setiap baris pemain memiliki tombol cepat: **Hadir** (hijau/navy) dan **Ga Hadir** (merah/slate).
   - Menekan **Edit** membuka sheet modal bersih berisi:
     - Input nama pemain.
     - 3 Tombol Tap Level: `[Beginner]` `[Intermediate]` `[Advance]`.
     - 2 Tombol Tap Status: `[Pemain Sesi Utama]` `[Waiting List]`.
     - Input No. WhatsApp (opsional).
     - Tombol Simpan (min. 44px touch target).
   - Jika dipindahkan ke Waiting List, pemain otomatis berpindah ke kelompok tabel Waiting List di bawahnya.
3. **Pengecekan Frekuensi Bermain (Tab Pemain)**:
   - Setiap kartu pemain menampilkan badge kuantitas main, misal: `2x Main` atau `0x Main (Belum Main)`.
4. **Acak Ronde Adil & Pengecekan Lapangan (Tab Rotasi)**:
   - PIC memilih strategi: **Fair Level** (kombinasi seimbang) atau **Kocok Acak Merata**.
   - Sistem memprioritaskan pemain dengan jumlah main paling sedikit untuk masuk ke lapangan terlebih dahulu.
   - Pada kartu Lapangan 1, Lapangan 2, dst., di sebelah nama setiap pemain Tim A dan Tim B tertera jelas: misal `Budi [Inter] · 2x Main`.
   - Pada daftar pemain istirahat (*bench*), PIC dapat melihat berapa kali pemain cadangan tersebut sudah main, sehingga memastikan pergantian di ronde berikutnya tepat sasaran.

#### B. Visual & Ergonomic Polish
- **Mobile Thumb Zone**: Semua tombol interaktif berukuran tinggi minimal 42–48px dengan respon sentuh halus (`active:scale-95`).
- **Typography & Clean Metadata**: Mengikuti prinsip anti-AI slop tanpa pill berlebihan. Indikator `2x Main` menggunakan font monospace bersih (`JetBrains Mono`) dengan pemisah tipografis elegan.
- **Zero Annoying Dropdowns**: Menghindari elemen `<select>` dengan pencarian pihak ketiga yang sering kali *buggy* di keyboard virtual Android/iOS.

---

### 3. Key Product Decisions & Trade-Offs

| Keputusan | Pendekatan Terpilih | Alasan & Manfaat | Alternatif Ditolak |
| :--- | :--- | :--- | :--- |
| **Kontrol Level & Status** | Segmented Tap Buttons (Tombol Fisik Touch) | Tidak perlu membuka dropdown virtual, instan 1-tap di layar HP | Dropdown select standar / searchable select2 |
| **Import WhatsApp Capping** | Semua masuk Sesi Utama kecuali ada sub-header "Waiting List" di teks WA | Menghindari sistem menebak-nebak kuota yang membuat PIC bingung mencari nama peserta | Memotong otomatis peserta ke-17 dst. ke waiting list |
| **Kalkulasi Frekuensi Main** | Akumulasi dinamis dari seluruh match record ronde dalam sesi aktif | Akurat, real-time, langsung tersinkron saat ronde baru diacak maupun saat ronde dihapus | Menyimpan kolom statis yang rawan tidak sinkron |
| **Strategi Pembagian Tim** | Workload-first sorting + Level Balancing Engine | Menjamin kesetaraan jatah bermain (yang jarang main diprioritaskan) sekaligus menjaga pertandingan tetap seru | Acak buta murni tanpa memperhatikan riwayat main |

---

### 4. Technical Architecture & Data Strategy

```
┌────────────────────────────────────────────────────────────────────────┐
│                        APLIKASI OPERASIONAL (PHP)                      │
├───────────────────────────────────┬────────────────────────────────────┤
│           TAB PEMAIN              │        TAB ROTASI LAPANGAN         │
│  - Daftar Pemain Sesi Utama       │  - Kartu Lapangan (Tim A vs Tim B) │
│  - Daftar Waiting List            │    └─ [Nama] [Level] [Main: Nx]   │
│  - Badge Frekuensi Main (Nx Main) │  - Bench Istirahat (Main: Nx)      │
│  - Aksi: Hadir / Ga Hadir / Edit  │  - Selector Strategi Rotasi        │
└─────────────────┬─────────────────┴──────────────────┬─────────────────┘
                  │                                    │
                  ▼                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                         CORE & MODELS (PHP)                            │
├───────────────────────────────────┬────────────────────────────────────┤
│ Participant::getBySession()       │ RotationEngine::generateRound()    │
│  └─ Hitung $sessionMatchCounts    │  ├─ Workload priority (fewest play)│
│ Participant::importFromWhatsapp() │  ├─ Level balance (Adv+Beg vs...)  │
│  └─ No auto-cap to waiting list   │  └─ Annotate players with playCount│
└───────────────────────────────────┴────────────────────────────────────┘
```

#### Modifikasi File yang Direncanakan:
1. **`models/Participant.php`**:
   - Perbaiki `importFromWhatsappList`: Pastikan seluruh nama bernomor masuk ke status `'confirmed'` (Sesi Utama) kecuali baris tersebut berada di bawah header teks eksplisit seperti `Waiting List:` / `Daftar Tunggu:`.
2. **`core/RotationEngine.php`**:
   - Pastikan algoritma `generateRound` selalu menyortir pemain hadir berdasarkan jumlah pertandingan paling sedikit (`played asc`) sebelum memasukkan ke slot lapangan.
   - Sertakan metadata `played_count` di setiap objek pemain dalam match agar langsung siap dirender di view.
3. **`app/index.php`**:
   - Hitung frekuensi main per pemain (`$sessionMatchCounts`) untuk sesi aktif.
   - Tampilkan badge jumlah main (`Nx Main`) di baris pemain pada Tab Pemain.
   - Ubah label tombol kehadiran menjadi `Hadir` dan `Ga Hadir` yang jelas dan responsif.
   - Pastikan modal Edit pemain menggunakan tombol segmented tap (Level & Status Sesi Utama/Waiting List) tanpa dropdown search.
   - Tampilkan badge jumlah main (`Nx Main`) pada kartu pertandingan di Tab Rotasi (Tim A & Tim B) dan di daftar pemain istirahat (*bench*).
4. **`api/index.php`**:
   - Pastikan endpoint `edit_player` dan `toggle_attendance` merespons dengan data terbaru secara mulus.
