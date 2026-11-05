<?php
// guide.php
// Powershoot Badminton — Interactive User Manual & Operating Guide
// Verified against application behavior (version 2.4.0)

require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/models/Community.php';

date_default_timezone_set('Asia/Jakarta');
$settings = Community::getSettings();
$communityName = !empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club';

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
$landingUrl = $baseDir . '/';
$appUrl = $baseDir . '/app/';
$adminUrl = $baseDir . '/admin/';
$galleryUrl = $baseDir . '/gallery';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Buku Panduan Pengguna & SOP Operasional — <?= htmlspecialchars($communityName) ?></title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

  <style>
    body, h1, h2, h3, h4, h5, h6, p, span, button, a, td, th, li {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace !important;
    }

    .ui-label {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 4px;
      border: 1px solid #cbd5e1;
      background: #f8fafc;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.72rem;
      font-weight: 700;
      color: #0f172a;
      white-space: nowrap;
    }

    .guide-topic-card:target {
      outline: 3px solid #f59e0b;
      outline-offset: 4px;
    }

    @media print {
      .no-print { display: none !important; }
      body { background: #ffffff !important; color: #000000 !important; font-size: 10.5pt !important; }
      .guide-topic-card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important;
        margin-bottom: 12px !important;
      }
      .print-break { break-before: page; page-break-before: always; }
      .print-shadow-none { box-shadow: none !important; }
      a { color: #0f172a !important; text-decoration: none !important; }
      @page { size: A4 portrait; margin: 14mm; }
    }
  </style>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          }
        }
      }
    }
  </script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen selection:bg-amber-400 selection:text-slate-950">

  <!-- Header -->
  <header class="bg-[#0b1e36] text-white border-b border-slate-800 sticky top-0 z-50 no-print shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3">

      <div class="flex items-center gap-3">
        <a href="<?= htmlspecialchars($landingUrl) ?>" class="w-9 h-9 bg-amber-400 text-slate-950 font-black text-sm flex items-center justify-center font-mono hover:bg-amber-300 transition-colors">
          PB
        </a>
        <div>
          <h1 class="text-sm sm:text-base font-black text-white tracking-tight leading-none">
            Buku Panduan Pengguna &amp; SOP Operasional
          </h1>
          <p class="text-[11px] text-amber-400 font-mono mt-0.5">
            <?= htmlspecialchars($communityName) ?> &bull; Versi Aplikasi 2.4.0
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-xs flex items-center gap-1.5 active:scale-95">
          <span>Cetak / Simpan PDF</span>
        </button>
        <a href="<?= htmlspecialchars($adminUrl) ?>" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs transition-colors border border-slate-700">
          Kembali ke CMS
        </a>
      </div>

    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Hero -->
    <div class="bg-white border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
      <div class="flex flex-col md:flex-row md:items-start justify-between gap-5 border-b border-slate-100 pb-6">
        <div class="space-y-2 max-w-3xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-900 text-amber-400 font-mono font-bold text-xs uppercase tracking-wider border border-amber-400/30">
            <span>DOKUMENTASI RESMI OPERASIONAL</span>
            <span>&bull;</span>
            <span><?= htmlspecialchars(strtoupper($communityName)) ?></span>
          </div>
          <h2 class="text-2xl sm:text-4xl font-black text-slate-950 tracking-tight">
            Panduan Lengkap dari Landing Page sampai Kas Komunitas
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            Dokumen ini disusun berdasarkan perilaku aplikasi yang sebenarnya: nama menu, tombol, status, dan rumus perhitungan. Gunakan sebagai materi onboarding pengurus, PIC, dan panduan serah terima produk.
          </p>
          <p class="text-xs text-slate-500 leading-relaxed">
            <strong>Penting:</strong> dokumen ini tidak memuat kredensial. Password awal dibuat saat instalasi dan wajib diganti melalui menu <span class="ui-label">Keamanan Akun</span>.
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-200 p-4 space-y-2 shrink-0 w-full md:w-80 font-mono text-xs">
          <div class="text-slate-500 font-bold uppercase text-[10px]">Alamat Aplikasi:</div>
          <div class="space-y-1 break-all">
            <div><strong class="text-slate-900">Landing:</strong> <a href="<?= htmlspecialchars($landingUrl) ?>" class="text-blue-600 underline" target="_blank" rel="noopener"><?= htmlspecialchars($landingUrl) ?></a></div>
            <div><strong class="text-slate-900">Galeri:</strong> <a href="<?= htmlspecialchars($galleryUrl) ?>" class="text-blue-600 underline" target="_blank" rel="noopener"><?= htmlspecialchars($galleryUrl) ?></a></div>
            <div><strong class="text-slate-900">App PIC:</strong> <a href="<?= htmlspecialchars($appUrl) ?>" class="text-blue-600 underline" target="_blank" rel="noopener"><?= htmlspecialchars($appUrl) ?></a></div>
            <div><strong class="text-slate-900">CMS Admin:</strong> <a href="<?= htmlspecialchars($adminUrl) ?>" class="text-blue-600 underline" target="_blank" rel="noopener"><?= htmlspecialchars($adminUrl) ?></a></div>
          </div>
        </div>
      </div>

      <!-- Search & filters -->
      <div class="no-print flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-lg">
          <input type="text"
                 id="guideSearchInput"
                 onkeyup="filterGuideTopics()"
                 placeholder="Cari topik (misal: rotasi, waiting list, kas, install, skor)..."
                 class="w-full bg-slate-50 border border-slate-300 text-slate-900 px-4 py-2.5 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-500">
          <span class="absolute right-3 top-2.5 text-slate-400 text-xs font-mono">CARI</span>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 font-mono text-xs">
          <button type="button" onclick="switchGuideTab('all', this)" class="tab-filter-btn active px-3 py-1.5 bg-[#0b1e36] text-white font-bold uppercase shadow-2xs">Semua</button>
          <button type="button" onclick="switchGuideTab('publik', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">Publik</button>
          <button type="button" onclick="switchGuideTab('pic', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">PIC</button>
          <button type="button" onclick="switchGuideTab('cms', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">Admin CMS</button>
          <button type="button" onclick="switchGuideTab('teknis', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">Teknis</button>
        </div>
      </div>

      <!-- Table of contents -->
      <nav class="no-print pt-2 border-t border-slate-100">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
          <a href="#modul-akses" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">01 &bull; Peta Akses &amp; Peran</a>
          <a href="#modul-publik" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">02 &bull; Pengunjung &amp; Pendaftaran</a>
          <a href="#modul-pic" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">03 &bull; Aplikasi PIC</a>
          <a href="#modul-cms" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">04 &bull; CMS Admin</a>
          <a href="#modul-rumus" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">05 &bull; Rotasi, Poin &amp; Kas</a>
          <a href="#modul-instalasi" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">06 &bull; Instalasi &amp; Go-Live</a>
          <a href="#modul-sop" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">07 &bull; SOP Mingguan</a>
          <a href="#modul-troubleshooting" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">08 &bull; Troubleshooting</a>
          <a href="#modul-uat" class="px-3 py-2 bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition-colors font-semibold">09 &bull; UAT &amp; Batasan Versi</a>
        </div>
      </nav>
    </div>

    <!-- MODULE 1: ACCESS MAP & ROLES -->
    <section id="modul-akses" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="teknis" data-keywords="akses url peran role pic admin publik login domain routing instalasi peta">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">01</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">Peta Akses &amp; Peran Pengguna</h3>
          <p class="text-xs text-slate-500">Empat antarmuka utama dan siapa yang menggunakan masing-masing.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100 text-left">
              <th class="border border-slate-200 px-3 py-2 font-bold">Antarmuka</th>
              <th class="border border-slate-200 px-3 py-2 font-bold">URL</th>
              <th class="border border-slate-200 px-3 py-2 font-bold">Pengguna</th>
              <th class="border border-slate-200 px-3 py-2 font-bold">Fungsi</th>
            </tr>
          </thead>
          <tbody class="text-slate-700">
            <tr>
              <td class="border border-slate-200 px-3 py-2 font-bold">Landing page</td>
              <td class="border border-slate-200 px-3 py-2 font-mono">/</td>
              <td class="border border-slate-200 px-3 py-2">Publik</td>
              <td class="border border-slate-200 px-3 py-2">Jadwal, kuota, pendaftaran WA, klasemen, galeri, FAQ</td>
            </tr>
            <tr class="bg-slate-50">
              <td class="border border-slate-200 px-3 py-2 font-bold">Galeri</td>
              <td class="border border-slate-200 px-3 py-2 font-mono">/gallery &middot; /galeri</td>
              <td class="border border-slate-200 px-3 py-2">Publik</td>
              <td class="border border-slate-200 px-3 py-2">Album foto &amp; video per sesi</td>
            </tr>
            <tr>
              <td class="border border-slate-200 px-3 py-2 font-bold">Aplikasi lapangan</td>
              <td class="border border-slate-200 px-3 py-2 font-mono">/app/</td>
              <td class="border border-slate-200 px-3 py-2">PIC / host</td>
              <td class="border border-slate-200 px-3 py-2">Absensi, iuran, rotasi, skor, kas sesi, media</td>
            </tr>
            <tr class="bg-slate-50">
              <td class="border border-slate-200 px-3 py-2 font-bold">CMS Admin</td>
              <td class="border border-slate-200 px-3 py-2 font-mono">/admin/ &middot; /cms/</td>
              <td class="border border-slate-200 px-3 py-2">Admin / pengurus</td>
              <td class="border border-slate-200 px-3 py-2">Jadwal, keuangan, roster, leaderboard, PIC, profil web</td>
            </tr>
            <tr>
              <td class="border border-slate-200 px-3 py-2 font-bold">Panduan</td>
              <td class="border border-slate-200 px-3 py-2 font-mono">/guide.php</td>
              <td class="border border-slate-200 px-3 py-2">Admin &amp; PIC</td>
              <td class="border border-slate-200 px-3 py-2">Dokumen ini, dapat dicetak</td>
            </tr>
            <tr class="bg-slate-50">
              <td class="border border-slate-200 px-3 py-2 font-bold">API internal</td>
              <td class="border border-slate-200 px-3 py-2 font-mono">/api/</td>
              <td class="border border-slate-200 px-3 py-2">Aplikasi</td>
              <td class="border border-slate-200 px-3 py-2">Endpoint JSON untuk aplikasi PIC</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
        <div class="border border-slate-200 p-4 bg-slate-50">
          <span class="font-mono font-bold text-[10px] text-amber-700 uppercase">Peran PIC</span>
          <p class="mt-1 text-slate-700 leading-relaxed">Mengelola sesi yang ditugaskan. Akun <span class="ui-label">pic_foto</span> dibatasi ke tab Foto.</p>
        </div>
        <div class="border border-slate-200 p-4 bg-slate-50">
          <span class="font-mono font-bold text-[10px] text-amber-700 uppercase">Peran Admin</span>
          <p class="mt-1 text-slate-700 leading-relaxed">Akses penuh ke CMS, keuangan, pengaturan, dan seluruh sesi.</p>
        </div>
        <div class="border border-rose-200 p-4 bg-rose-50">
          <span class="font-mono font-bold text-[10px] text-rose-700 uppercase">Catatan Keamanan</span>
          <p class="mt-1 text-slate-700 leading-relaxed">Pembatasan halaman admin per peran belum granular pada versi ini. Jangan bagikan kredensial admin ke PIC.</p>
        </div>
      </div>
    </section>

    <!-- MODULE 2: PUBLIC -->
    <section id="modul-publik" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="publik" data-keywords="landing jadwal filter minggu ini minggu depan mendatang rentang tanggal kuota slot full whatsapp daftar klasemen leaderboard galeri faq">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">02</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">Pengunjung &amp; Calon Pemain</h3>
          <p class="text-xs text-slate-500">Alur melihat jadwal, membaca kuota, dan mendaftar lewat WhatsApp PIC.</p>
        </div>
      </div>

      <div class="space-y-4 text-xs text-slate-700 leading-relaxed">
        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">2.1 Filter jadwal</h4>
          <p>Pada bagian <strong>02 &bull; JADWAL CABANG MABAR</strong> tersedia:</p>
          <ul class="list-disc list-inside mt-1 space-y-1">
            <li><span class="ui-label">Semua Jadwal (n)</span> menampilkan seluruh sesi.</li>
            <li><span class="ui-label">Minggu Ini (n)</span> sesi pekan berjalan.</li>
            <li><span class="ui-label">Minggu Depan (n)</span> sesi pekan berikutnya.</li>
            <li><span class="ui-label">Mendatang (n)</span> sesi setelah pekan depan.</li>
            <li><span class="ui-label">Rentang Tanggal: ... s/d ...</span> filter dua tanggal, dengan <span class="ui-label">Reset</span>.</li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">2.2 Membaca kartu sesi</h4>
          <p>Kartu menampilkan badge <strong>HARI INI / BESOK</strong>, jumlah lapangan, <strong>Sisa n Slot (x/y)</strong> atau <strong>FULL (x/x)</strong>, status <strong>Open Booking / Full</strong>, tanggal, jam, iuran, PIC, dan fasilitas venue.</p>
          <p class="mt-1 text-slate-600">`x` adalah jumlah peserta terdaftar; `y` adalah kuota maksimal. Pendaftar setelah kuota penuh masuk <strong>Waiting List</strong>.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">2.3 Mendaftar via WhatsApp</h4>
          <p>Klik <span class="ui-label">Daftar via WhatsApp PIC Sesi &rarr;</span>. WhatsApp terbuka dengan pesan berisi nama komunitas, nama sesi, tanggal, dan venue. Setelah mengirim pesan, konfirmasi slot dan pembayaran dilakukan bersama PIC. Pendaftaran WA <strong>tidak otomatis</strong> menandai pembayaran lunas.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">2.4 Klasemen publik</h4>
          <p>Kolom: <strong>Rank, Nama, Level, Tanding, Menang, Kalah, Win Rate, Total Poin</strong>. Filter <span class="ui-label">Pilih Sesi:</span> mendukung <strong>Semua Sesi (Klasemen Overall)</strong> atau satu sesi. Peringkat diurutkan berdasarkan total poin, lalu jumlah menang, win rate, dan streak.</p>
          <div class="mt-2 p-3 bg-amber-50 border border-amber-200 text-amber-900">
            <strong>Catatan:</strong> label "Bulanan" adalah nama tampilan. Data overall memakai seluruh riwayat pertandingan yang tersimpan, bukan hanya bulan kalender berjalan. Reset periode dilakukan manual oleh Admin.
          </div>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">2.5 Galeri publik</h4>
          <p>Alamat <span class="ui-label">/gallery</span>. Tersedia pil sesi, pencarian <span class="ui-label">Cari foto, momen, venue...</span>, indikator <strong>Filter Aktif</strong>, lightbox, tombol <span class="ui-label">Buka Media Ukuran Asli</span>, dukungan video HTML5, navigasi keyboard, dan gestur geser. Galeri memuat maksimal <strong>120 media terbaru</strong>.</p>
        </div>
      </div>
    </section>

    <!-- MODULE 3: PIC APP -->
    <section id="modul-pic" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="pic" data-keywords="pic aplikasi lapangan login ganti sesi rotasi strategi fair level selevel kocok skor simpan skor waiting list walk in paste wa absensi lunas kas foto klasemen selesai sesi rekap">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">03</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">Aplikasi PIC Lapangan (<span class="font-mono">/app/</span>)</h3>
          <p class="text-xs text-slate-500">Absensi, rotasi, skor, kas sesi, dan dokumentasi dari smartphone.</p>
        </div>
      </div>

      <div class="space-y-5 text-xs text-slate-700 leading-relaxed">

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.1 Login</h4>
          <p>Isi <span class="ui-label">Username / WhatsApp:</span> dan <span class="ui-label">Password:</span>, lalu klik <span class="ui-label">Masuk ke Sistem Lapangan &rarr;</span>. Jika akun ditugaskan ke sesi, aplikasi langsung membuka sesi tersebut. Jika belum, PIC memilih sesi.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.2 Pindah sesi &amp; sesi terkunci</h4>
          <p>Klik <span class="ui-label">Ganti Sesi</span> lalu pilih pada panel <span class="ui-label">Pindah ke Sesi Lain</span>. Urutan sesi: pilihan terakhir di browser &rarr; sesi yang ditugaskan ke akun &rarr; sesi pertama yang tersedia. Sesi <strong>completed</strong> terkunci untuk PIC; Admin harus membuka statusnya dari CMS.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.3 Lima tab utama</h4>
          <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mt-2 font-mono text-[11px]">
            <div class="border border-slate-200 bg-slate-50 p-2 text-center font-bold">Rotasi</div>
            <div class="border border-slate-200 bg-slate-50 p-2 text-center font-bold">Pemain</div>
            <div class="border border-slate-200 bg-slate-50 p-2 text-center font-bold">Kas</div>
            <div class="border border-slate-200 bg-slate-50 p-2 text-center font-bold">Foto</div>
            <div class="border border-slate-200 bg-slate-50 p-2 text-center font-bold">Klasemen</div>
          </div>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.4 Generate rotasi</h4>
          <ul class="list-disc list-inside space-y-1">
            <li>Syarat: minimal <strong>4 pemain Hadir</strong>.</li>
            <li>Lapangan efektif = <code class="font-mono bg-slate-100 px-1">min(lapangan_sesi, floor(pemain_hadir / 4))</code>.</li>
            <li>Per lapangan: <span class="ui-label">+ Panggil 4 Pemain Berikutnya ke Lapangan n</span>.</li>
            <li>Semua lapangan: <span class="ui-label">Acak Semua Lapangan</span> pada bagian <strong>OPSI LAIN &mdash; Acak Semua Lapangan Sekaligus (Rotasi Serentak Ronde)</strong>.</li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.5 Tiga strategi pasangan</h4>
          <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse mt-1">
              <thead>
                <tr class="bg-slate-100 text-left">
                  <th class="border border-slate-200 px-3 py-2">Strategi</th>
                  <th class="border border-slate-200 px-3 py-2">Perilaku</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="border border-slate-200 px-3 py-2 font-bold">Fair Level (Kombinasi Seimbang)</td>
                  <td class="border border-slate-200 px-3 py-2">Menyeimbangkan total level kedua tim</td>
                </tr>
                <tr class="bg-slate-50">
                  <td class="border border-slate-200 px-3 py-2 font-bold">Selevel (Pure Tier Mahir/Pemula)</td>
                  <td class="border border-slate-200 px-3 py-2">Mengelompokkan pemain pada level yang sama</td>
                </tr>
                <tr>
                  <td class="border border-slate-200 px-3 py-2 font-bold">Kocok Campur Merata</td>
                  <td class="border border-slate-200 px-3 py-2">Mengacak tanpa preferensi level</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="mt-2 text-slate-600">Mesin mencoba hingga 200 kombinasi. Penalti: selisih level &times;50, pasangan berulang &times;20, lawan berulang &times;5. Bobot level: Beginner/Intermediate/Advance = 1/2/3.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.6 Input skor</h4>
          <p>Isi skor Tim A dan Tim B, lalu klik <span class="ui-label">Simpan Skor &amp; Selesaikan Lapangan n</span>. <strong>Skor tidak tersimpan otomatis.</strong></p>
          <div class="mt-2 p-3 bg-slate-900 text-slate-100 font-mono text-[11px] space-y-1">
            <div>Menang : skor sendiri + win bonus (standar +5)</div>
            <div>Kalah&nbsp; : skor sendiri</div>
            <div>Seri&nbsp;&nbsp; : dihitung kalah untuk kedua tim</div>
          </div>
          <p class="mt-2 text-slate-600">Contoh 21&ndash;19: pemenang 21 + 5 = <strong>26 poin</strong>; yang kalah <strong>19 poin</strong>.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.7 Tab Pemain</h4>
          <ul class="list-disc list-inside space-y-1">
            <li><span class="ui-label">+ Tambah Walk-In</span> untuk peserta di lokasi.</li>
            <li><span class="ui-label">Paste List WA</span> untuk menempel daftar bernomor; baris kosong/titik diabaikan, nama duplikat tidak ditambahkan.</li>
            <li>Klik badge untuk mengubah <strong>Hadir</strong> &harr; <strong>Tidak Hadir</strong>.</li>
            <li>Chip <strong>Lunas</strong> / <strong>Belum Bayar</strong> memengaruhi kas dan piutang.</li>
            <li>Peserta di atas kuota masuk <strong>Waiting List</strong> dan dapat dipromosikan manual.</li>
            <li>Hapus peserta dari sesi tidak menghapus master pemain dari roster.</li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.8 Tab Kas, Foto, Klasemen</h4>
          <ul class="list-disc list-inside space-y-1">
            <li><strong>Kas:</strong> <span class="ui-label">SALDO KAS SESI INI</span> = iuran terkumpul + pemasukan tambahan &minus; biaya sesi. Catat biaya lewat <span class="ui-label">+ Catat Biaya</span>.</li>
            <li><strong>Foto:</strong> <span class="ui-label">+ Unggah Foto Dokumentasi Mabar Baru</span>, pilih Foto/Video, isi judul, lalu unggah.</li>
            <li><strong>Klasemen:</strong> <span class="ui-label">Klasemen Poin Sesi</span> untuk sesi aktif.</li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">3.9 Menutup sesi</h4>
          <p>Klik <span class="ui-label">Selesai Sesi Mabar</span> lalu konfirmasi <span class="ui-label">Ya, Selesaikan Sesi Ini</span>. Banner <strong>SESI MABAR TELAH SELESAI &amp; TERKUNCI</strong> muncul. Setelah itu tersedia <span class="ui-label">Buat Sesi Pekan Depan &rarr;</span>, <span class="ui-label">Salin Rekap WA</span>, dan <span class="ui-label">Pilih Sesi Lain</span>.</p>
        </div>

      </div>
    </section>

    <!-- MODULE 4: ADMIN CMS -->
    <section id="modul-cms" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="cms" data-keywords="admin cms dashboard keuangan kas jadwal template wa import waiting list roster pemain leaderboard pic akun settings seo password keamanan">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">04</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">CMS Admin (<span class="font-mono">/admin/</span>)</h3>
          <p class="text-xs text-slate-500">Pusat komando jadwal, kas, roster, leaderboard, akun PIC, dan profil web.</p>
        </div>
      </div>

      <div class="space-y-5 text-xs text-slate-700 leading-relaxed">

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.1 Navigasi sidebar</h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
            <div class="border border-slate-200 bg-slate-50 p-3">
              <div class="font-mono text-[10px] font-bold text-amber-700 uppercase mb-1">Menu Utama</div>
              <ul class="space-y-0.5">
                <li>Dashboard Ringkasan</li>
                <li>Laporan Keuangan &amp; Kas</li>
                <li>Jadwal &amp; Kuota Sesi</li>
                <li>+ Tambah Jadwal Baru</li>
                <li>Galeri Dokumentasi</li>
                <li>Roster Pemain Cabang</li>
                <li>Leaderboard Bulanan</li>
              </ul>
            </div>
            <div class="border border-slate-200 bg-slate-50 p-3">
              <div class="font-mono text-[10px] font-bold text-amber-700 uppercase mb-1">Manajemen Sistem</div>
              <ul class="space-y-0.5">
                <li>Akun PIC &amp; Tim Media</li>
                <li>Profil &amp; Web Settings</li>
                <li>Keamanan Akun</li>
                <li>Buku Panduan &amp; Demo</li>
              </ul>
            </div>
            <div class="border border-slate-200 bg-slate-50 p-3">
              <div class="font-mono text-[10px] font-bold text-amber-700 uppercase mb-1">Akses Langsung</div>
              <ul class="space-y-0.5">
                <li>Web Depan Mabar</li>
                <li>App PIC Operasional</li>
              </ul>
            </div>
          </div>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.2 Laporan Keuangan &amp; Kas</h4>
          <p>Filter: chip bulan (12 bulan terakhir, <strong>Bulan ini</strong>, <strong>Semua</strong>), <strong>Semua sesi &amp; cabang</strong>, dan jenis <strong>Semua / Masuk / Keluar</strong>.</p>
          <p class="mt-1">KPI: <strong>Saldo bersih</strong> (badge SURPLUS/DEFISIT), <strong>Iuran lunas</strong>, <strong>Belum bayar</strong>, <strong>Pemasukan lain</strong>, dan <strong>Pengeluaran</strong>.</p>
          <p class="mt-1">Panel: <strong>Buku Kas</strong>, <strong>Per Sesi</strong>, <strong>Piutang</strong>, dan <strong>Analisis</strong>. Tombol <span class="ui-label">+ Catat Transaksi</span>, <span class="ui-label">Export CSV</span>, dan <span class="ui-label">Cetak</span> tersedia di header.</p>
          <p class="mt-1">Transaksi: pilih jenis (Pengeluaran / Pemasukan Tambahan), kategori, sesi terkait (opsional), judul, nominal, tanggal, catatan, lalu <span class="ui-label">Simpan</span>. Tombol <strong>Ingatkan</strong> pada piutang membuka WhatsApp penagihan.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.3 Jadwal &amp; kuota</h4>
          <ul class="list-disc list-inside space-y-1">
            <li><span class="ui-label">Template WA</span> membuka generator pesan dengan parameter live.</li>
            <li><span class="ui-label">+ Buka Sesi Pekan Depan (+7 Hari)</span> menduplikasi konfigurasi ke tanggal +7 hari.</li>
            <li><span class="ui-label">Hapus Sesi</span> menghapus sesi dan data terkait.</li>
            <li>Status sesi: <strong>open</strong>, <strong>in_progress</strong>, <strong>completed</strong>.</li>
          </ul>
          <div class="mt-2 p-3 bg-amber-50 border border-amber-200 text-amber-900">
            <strong>Penting:</strong> duplikasi +7 hari <strong>tidak menyalin peserta</strong> dan <strong>tidak menyalin penugasan akun PIC</strong>. Tugaskan ulang PIC pada sesi baru.
          </div>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.4 Template WA &amp; impor peserta</h4>
          <p>Parameter template: wilayah/judul sesi, hari &amp; sesi, pilihan lapangan, jam, iuran, kuota, nama GOR, link Maps, rekening, kontak konfirmasi, nama host, dan aturan cancel. Klik <span class="ui-label">Simpan Pengaturan ke Database Sesi</span> lalu <span class="ui-label">Salin Format Pesan WhatsApp</span>.</p>
          <p class="mt-1">Panel <strong>Input / Paste List Peserta dari WhatsApp</strong> menerima daftar bernomor. Sub-judul <strong>Waiting List:</strong> menandai peserta daftar tunggu. Klik <span class="ui-label">Import Nama ke Sesi Mabar Ini &rarr;</span>.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.5 Tambah / edit jadwal</h4>
          <p>Isi identitas sesi, tanggal, jam mulai/selesai, nama GOR, alamat, link Maps, fasilitas, jumlah lapangan (1&ndash;10), kuota (4&ndash;50), iuran, dan PIC. Simpan dengan <span class="ui-label">Buat Jadwal Baru</span> atau <span class="ui-label">Simpan Perubahan Jadwal</span>.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.6 Roster &amp; leaderboard</h4>
          <p><strong>Roster:</strong> filter cabang/sesi, tambah pemain, edit level, <strong>Hapus Terpilih</strong>, dan hapus per pemain. Penghapusan bersifat permanen dan dapat mengubah laporan historis.</p>
          <p class="mt-1"><strong>Leaderboard:</strong> <span class="ui-label">Hitung Ulang Leaderboard Sekarang</span> dengan bobot +5/+3/+10/0; <span class="ui-label">Reset Poin Roster Bulan Baru</span>; dan <strong>Koreksi / Penyesuaian Poin Pemain Manual</strong>.</p>
          <div class="mt-2 p-3 bg-rose-50 border border-rose-200 text-rose-900">
            <strong>Peringatan:</strong> reset hanya mengosongkan akumulasi. Jika hitung ulang dijalankan setelah reset, poin dihitung kembali dari riwayat pertandingan.
          </div>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.7 Akun PIC</h4>
          <p><span class="ui-label">1-Click Generate PIC Demo</span> membuat akun demo dengan password acak yang ditampilkan sekali. <span class="ui-label">+ Tambah Akun PIC</span> membuat akun manual (nama, username, password, WhatsApp, peran <strong>pic</strong>/<strong>pic_foto</strong>, sesi). Tersedia penugasan massal dan hapus akun. Uji login di <span class="font-mono">/app/</span> sebelum menyerahkan kredensial.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.8 Profil &amp; Web Settings</h4>
          <p>Empat sub-tab: <strong>Identitas &amp; Kontak</strong> (logo, nama, tagline, kota, WhatsApp default, Instagram, deskripsi footer), <strong>Hero &amp; Banner</strong>, <strong>Alur Ikut &amp; FAQ</strong>, dan <strong>Google SEO &amp; Sosmed</strong> (meta title/description, focus keyphrase, preview Google, share preview, OG image).</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">4.9 Keamanan akun</h4>
          <p>Ubah password melalui <strong>Keamanan Akun</strong> (password saat ini, baru, konfirmasi). Tidak ada fitur lupa password via email; reset dilakukan lewat database atau akun admin lain.</p>
        </div>

      </div>
    </section>

    <!-- MODULE 5: FORMULAS -->
    <section id="modul-rumus" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="teknis" data-keywords="rumus rotasi poin win bonus kas saldo piutang kuota waiting list status sesi perhitungan">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">05</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">Rotasi, Poin &amp; Kas</h3>
          <p class="text-xs text-slate-500">Rumus yang dipakai aplikasi, bukan sekadar penjelasan umum.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <div class="border border-slate-200 p-4 bg-slate-50 space-y-2">
          <span class="text-amber-600 font-mono font-bold block text-[11px]">ROTASI</span>
          <h5 class="font-bold text-slate-900 text-sm">Lapangan Efektif</h5>
          <pre class="bg-white border border-slate-200 p-2 font-mono text-[10px] overflow-x-auto">min(lapangan_sesi,
    floor(pemain_hadir / 4))</pre>
          <p class="text-slate-600 leading-relaxed">Minimal 4 pemain hadir. Antrean diurutkan: paling sedikit bermain &rarr; paling banyak istirahat &rarr; urutan daftar.</p>
        </div>

        <div class="border border-slate-200 p-4 bg-slate-50 space-y-2">
          <span class="text-amber-600 font-mono font-bold block text-[11px]">POIN</span>
          <h5 class="font-bold text-slate-900 text-sm">Poin Pertandingan</h5>
          <pre class="bg-white border border-slate-200 p-2 font-mono text-[10px] overflow-x-auto">menang = skor + bonus
kalah  = skor
seri   = kalah keduanya</pre>
          <p class="text-slate-600 leading-relaxed">Bonus standar +5. Total poin = poin reli + (menang &times; bonus).</p>
        </div>

        <div class="border border-slate-200 p-4 bg-slate-50 space-y-2">
          <span class="text-amber-600 font-mono font-bold block text-[11px]">KAS</span>
          <h5 class="font-bold text-slate-900 text-sm">Saldo &amp; Laba Sesi</h5>
          <pre class="bg-white border border-slate-200 p-2 font-mono text-[10px] overflow-x-auto">masuk = iuran_lunas
      + pemasukan_lain
saldo = masuk - pengeluaran
laba  = (iuran + lain) - biaya</pre>
          <p class="text-slate-600 leading-relaxed">Piutang = iuran peserta yang belum lunas.</p>
        </div>
      </div>

      <div class="text-xs text-slate-700 leading-relaxed space-y-2">
        <p><strong>Urutan peringkat:</strong> total poin &rarr; jumlah menang &rarr; win rate &rarr; streak.</p>
        <p><strong>Status sesi:</strong> <span class="ui-label">open</span> dapat menerima peserta; <span class="ui-label">in_progress</span> sedang berjalan; <span class="ui-label">completed</span> terkunci untuk PIC; <span class="ui-label">cancelled</span> ada di database tetapi bukan aksi UI utama.</p>
        <p><strong>Waiting list:</strong> peserta di atas kuota tidak otomatis menggantikan peserta yang batal; promosi dilakukan manual.</p>
      </div>
    </section>

    <!-- MODULE 6: INSTALLATION -->
    <section id="modul-instalasi" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs print-break" data-category="teknis" data-keywords="instalasi install database mysql config setup php apache htaccess nginx pwa maps backup hardening go live">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">06</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">Instalasi, Konfigurasi &amp; Go-Live</h3>
          <p class="text-xs text-slate-500">Langkah teknis agar aplikasi berjalan dan aman untuk produksi.</p>
        </div>
      </div>

      <div class="space-y-5 text-xs text-slate-700 leading-relaxed">

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">6.1 Prasyarat</h4>
          <ul class="list-disc list-inside space-y-1">
            <li>PHP 7.4+ dengan <code class="font-mono">pdo_mysql</code>, <code class="font-mono">mbstring</code>, <code class="font-mono">fileinfo</code>, <code class="font-mono">openssl</code>.</li>
            <li>MySQL 5.7+/8.x atau MariaDB 10.3+.</li>
            <li>Apache dengan <code class="font-mono">mod_rewrite</code> dan <code class="font-mono">AllowOverride All</code>, atau Nginx dengan rewrite ekuivalen.</li>
            <li>Akses tulis ke folder unggahan dan HTTPS untuk produksi.</li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">6.2 Database</h4>
          <p><strong>Opsi A &mdash; installer bersih (disarankan untuk produksi):</strong> sesuaikan <code class="font-mono">config/database.php</code>, lalu jalankan</p>
          <pre class="bg-slate-900 text-slate-100 p-3 font-mono text-[11px] overflow-x-auto">php config/setup.php</pre>
          <p class="mt-1">Installer membuat database, seluruh tabel, pengaturan awal, dan satu akun admin tanpa data demo. Password awal didefinisikan pada <code class="font-mono">config/setup.php</code> dan <strong>wajib diganti</strong> setelah login pertama. Hapus atau kunci file installer setelah selesai.</p>
          <p class="mt-2"><strong>Opsi B &mdash; dump demo:</strong></p>
          <pre class="bg-slate-900 text-slate-100 p-3 font-mono text-[11px] overflow-x-auto">mysql -u root -p powershoot_db &lt; database_backup.sql</pre>
          <p class="mt-1 text-slate-600">Dump berisi skema dan data demo (termasuk akun PIC contoh). Untuk instalasi dengan data demo melalui installer, jalankan <code class="font-mono">php config/setup.php --demo</code>. Bersihkan data demo sebelum produksi.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">6.3 Konfigurasi database</h4>
          <pre class="bg-slate-900 text-slate-100 p-3 font-mono text-[11px] overflow-x-auto">return [
  'host' =&gt; '127.0.0.1',
  'port' =&gt; '3306',
  'database' =&gt; 'powershoot_db',
  'username' =&gt; 'root',
  'password' =&gt; '',
  'charset' =&gt; 'utf8mb4',
];</pre>
          <p class="mt-1 text-slate-600">Nilai koneksi dapat dioverride lewat environment variable <code class="font-mono">SMASHPOINT_DB_HOST</code>, <code class="font-mono">SMASHPOINT_DB_PORT</code>, <code class="font-mono">SMASHPOINT_DB_NAME</code>, <code class="font-mono">SMASHPOINT_DB_USER</code>, dan <code class="font-mono">SMASHPOINT_DB_PASS</code>.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">6.4 Web server</h4>
          <p><strong>Apache:</strong> aktifkan <code class="font-mono">mod_rewrite</code>, arahkan document root ke folder aplikasi, dan pastikan <code class="font-mono">.htaccess</code> terbaca. Aplikasi dirancang untuk root domain.</p>
          <p><strong>Nginx:</strong></p>
          <pre class="bg-slate-900 text-slate-100 p-3 font-mono text-[11px] overflow-x-auto">location / {
    try_files $uri $uri/ /index.php?$query_string;
}</pre>
          <p><strong>Pengembangan:</strong> <code class="font-mono">php -S localhost:8000 router.php</code>.</p>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">6.5 Hardening wajib</h4>
          <ul class="list-disc list-inside space-y-1">
            <li>Ganti password admin dan akun PIC demo.</li>
            <li>Matikan <code class="font-mono">display_errors</code> di produksi.</li>
            <li>Aktifkan HTTPS dan cookie sesi <code class="font-mono">Secure</code>.</li>
            <li>Larang eksekusi PHP di folder <code class="font-mono">uploads/</code>.</li>
            <li>Batasi akses ke <code class="font-mono">database_backup.sql</code>, <code class="font-mono">config/</code>, dan file <code class="font-mono">.md</code> internal.</li>
            <li>Hapus <code class="font-mono">config/setup.php</code> setelah instalasi.</li>
            <li>Jadwalkan backup dan uji restore.</li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-1">6.6 PWA &amp; Google Maps</h4>
          <p>Manifest PWA hanya tersedia untuk <span class="font-mono">/app/</span> dan memerlukan HTTPS. Google Maps Places API Key bersifat opsional untuk autocomplete venue; tanpa key, alamat dan URL peta diisi manual.</p>
        </div>

      </div>
    </section>

    <!-- MODULE 7: SOP -->
    <section id="modul-sop" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="pic" data-keywords="sop operasional h-7 h-3 h-1 hari h tutup sesi rekap wa backup mingguan">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">07</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">SOP Operasional Mingguan</h3>
          <p class="text-xs text-slate-500">Urutan kerja dari perencanaan sampai rekap pasca-sesi.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        <div class="border border-slate-200 p-4">
          <span class="font-mono font-bold text-[10px] text-amber-700 uppercase">H-7 s/d H-3 &mdash; Perencanaan</span>
          <ul class="list-disc list-inside mt-2 space-y-1 text-slate-700">
            <li>Konfirmasi booking GOR dan jumlah lapangan.</li>
            <li>Buat jadwal, venue, peta, kuota, dan iuran.</li>
            <li>Tugaskan akun PIC.</li>
            <li>Broadcast template WA.</li>
          </ul>
        </div>
        <div class="border border-slate-200 p-4">
          <span class="font-mono font-bold text-[10px] text-amber-700 uppercase">H-3 s/d H-1 &mdash; Pendaftaran</span>
          <ul class="list-disc list-inside mt-2 space-y-1 text-slate-700">
            <li>Impor daftar WA.</li>
            <li>Verifikasi pembayaran.</li>
            <li>Kelola waiting list.</li>
            <li>Ingatkan yang belum bayar.</li>
          </ul>
        </div>
        <div class="border border-slate-200 p-4">
          <span class="font-mono font-bold text-[10px] text-amber-700 uppercase">Hari-H &mdash; Pelaksanaan</span>
          <ul class="list-disc list-inside mt-2 space-y-1 text-slate-700">
            <li>Login <span class="font-mono">/app/</span>, cek sesi.</li>
            <li>Tandai kehadiran dan pembayaran.</li>
            <li>Generate rotasi, simpan skor.</li>
            <li>Catat biaya dan unggah dokumentasi.</li>
          </ul>
        </div>
        <div class="border border-slate-200 p-4">
          <span class="font-mono font-bold text-[10px] text-amber-700 uppercase">Pasca-sesi &mdash; Closing</span>
          <ul class="list-disc list-inside mt-2 space-y-1 text-slate-700">
            <li>Pastikan semua skor tersimpan.</li>
            <li>Selesaikan dan kunci sesi.</li>
            <li>Salin Rekap WA.</li>
            <li>Buat sesi +7 hari dan tugaskan ulang PIC.</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- MODULE 8: TROUBLESHOOTING -->
    <section id="modul-troubleshooting" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs" data-category="teknis" data-keywords="troubleshooting error 500 404 login gagal skor tidak masuk upload gagal locked terkunci waiting list piutang klasemen bulanan">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">08</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">Troubleshooting &amp; FAQ</h3>
          <p class="text-xs text-slate-500">Gejala umum dan tindakan yang benar.</p>
        </div>
      </div>

      <div class="space-y-3 text-xs text-slate-700">
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Sesi tidak muncul di landing page</summary>
          <p class="mt-2 leading-relaxed">Periksa status sesi. Sesi <strong>completed</strong>/<strong>cancelled</strong> tidak tampil untuk pendaftaran. Pastikan tanggal belum lewat dan filter pekan/tanggal tidak menyembunyikannya.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">PIC masuk ke sesi yang salah</summary>
          <p class="mt-2 leading-relaxed">Gunakan <strong>Ganti Sesi</strong> &rarr; <strong>Pindah ke Sesi Lain</strong>. Jika penugasan akun salah, perbaiki di CMS pada menu <strong>Akun PIC &amp; Tim Media</strong>.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Sesi terkunci, PIC tidak bisa mengubah data</summary>
          <p class="mt-2 leading-relaxed">Sesi berstatus <strong>completed</strong>. Admin harus mengubah status menjadi <strong>open</strong>/<strong>in_progress</strong> di CMS. Lakukan hanya jika memang perlu koreksi.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Generate rotasi tidak berjalan</summary>
          <p class="mt-2 leading-relaxed">Pastikan minimal 4 pemain berstatus <strong>Hadir</strong>. Pemain <strong>Tidak Hadir</strong> tidak dihitung. Pastikan juga jumlah lapangan sesi minimal 1.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Skor sudah diubah tetapi poin tidak masuk</summary>
          <p class="mt-2 leading-relaxed">Skor hanya tersimpan setelah <strong>Simpan Skor &amp; Selesaikan Lapangan n</strong> ditekan. Setelah tersimpan, jalankan <strong>Hitung Ulang Leaderboard</strong> di CMS bila diperlukan.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Poin tidak nol setelah reset</summary>
          <p class="mt-2 leading-relaxed">Reset hanya mengosongkan akumulasi. Hitung ulang akan menghitung kembali dari riwayat pertandingan. Untuk periode benar-benar baru, arsipkan atau hapus riwayat pertandingan lebih dulu.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Peserta sudah bayar tetapi masih muncul di piutang</summary>
          <p class="mt-2 leading-relaxed">Periksa sesi yang benar. Satu pemain dapat terdaftar di beberapa sesi; pelunasan harus pada sesi yang dimaksud.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Upload foto/video gagal</summary>
          <p class="mt-2 leading-relaxed">Periksa <code class="font-mono">upload_max_filesize</code>, <code class="font-mono">post_max_size</code>, izin folder <code class="font-mono">uploads/</code>, dan format file. Gunakan <code class="font-mono">.mp4</code> H.264/AAC untuk video.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Error 500 atau routing 404 setelah pindah server</summary>
          <p class="mt-2 leading-relaxed">Periksa versi PHP, ekstensi <code class="font-mono">pdo_mysql</code>, kredensial database, izin folder, dan log web server. Untuk 404, pastikan <code class="font-mono">mod_rewrite</code> aktif, <code class="font-mono">.htaccess</code> terbaca, dan aplikasi berada di root domain.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Apakah PIC bisa melihat laporan keuangan admin?</summary>
          <p class="mt-2 leading-relaxed">Secara operasional PIC hanya diberi akses <span class="font-mono">/app/</span>. Namun pembatasan halaman admin per peran belum granular pada versi ini; jangan bagikan kredensial admin. Untuk kepatuhan penuh, tambahkan RBAC atau pembatasan web server.</p>
        </details>
        <details class="border border-slate-200 p-3 bg-slate-50">
          <summary class="font-bold cursor-pointer">Apakah ada aplikasi Android/iOS?</summary>
          <p class="mt-2 leading-relaxed">Tidak. Aplikasi PIC adalah web app yang dapat dipasang ke layar utama melalui PWA pada <span class="font-mono">/app/</span>.</p>
        </details>
      </div>
    </section>

    <!-- MODULE 9: UAT & LIMITATIONS -->
    <section id="modul-uat" class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-5 shadow-xs print-break" data-category="teknis" data-keywords="uat checklist go live batasan limitasi komersial rbac csrf keamanan">
      <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
        <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center shrink-0">09</span>
        <div>
          <h3 class="text-xl font-black text-slate-950 tracking-tight">UAT, Batasan Versi &amp; Serah Terima</h3>
          <p class="text-xs text-slate-500">Pastikan produk siap sebelum dijual atau diserahkan ke klien.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
        <div class="border border-slate-200 p-4">
          <h5 class="font-bold text-slate-900 text-sm mb-2">Checklist UAT minimum</h5>
          <ul class="space-y-1 text-slate-700">
            <li>&#9744; Landing page &amp; filter jadwal berfungsi.</li>
            <li>&#9744; Tombol WhatsApp membuka pesan yang benar.</li>
            <li>&#9744; PIC login, absensi, dan pembayaran tersimpan.</li>
            <li>&#9744; Rotasi per lapangan &amp; serentak berfungsi.</li>
            <li>&#9744; Skor tersimpan dan poin masuk.</li>
            <li>&#9744; Sesi selesai terkunci.</li>
            <li>&#9744; Kas admin dan piutang akurat.</li>
            <li>&#9744; Jadwal, WA template, dan impor peserta berfungsi.</li>
            <li>&#9744; Leaderboard hitung ulang berfungsi.</li>
            <li>&#9744; Akun PIC dibuat dan diuji login.</li>
            <li>&#9744; Pengaturan dan SEO tampil di landing.</li>
            <li>&#9744; Backup &amp; restore diuji.</li>
          </ul>
        </div>

        <div class="border border-rose-200 bg-rose-50 p-4">
          <h5 class="font-bold text-rose-900 text-sm mb-2">Batasan yang harus diketahui pembeli</h5>
          <ul class="space-y-1 text-rose-900">
            <li>Leaderboard bukan kalender otomatis; reset manual.</li>
            <li>RBAC admin per peran belum granular.</li>
            <li>Belum ada CSRF token dan autentikasi API.</li>
            <li>Tidak ada lupa password via email.</li>
            <li>Tidak ada aplikasi native; hanya PWA.</li>
            <li>Galeri publik maksimal 120 media terbaru.</li>
            <li>Skor tidak tersimpan otomatis.</li>
            <li>Duplikasi +7 hari tidak menyalin peserta/PIC.</li>
            <li>Hapus pemain bersifat permanen.</li>
            <li>Data demo wajib dibersihkan sebelum produksi.</li>
          </ul>
        </div>
      </div>

      <div class="p-4 bg-slate-900 text-white text-xs leading-relaxed">
        <strong class="text-amber-400 font-mono uppercase">Rekomendasi sebelum go-live:</strong>
        ganti seluruh data demo dan branding, hapus akun demo, terapkan HTTPS dan backup otomatis, tambahkan CSRF protection dan autentikasi API, serta pertimbangkan RBAC per halaman admin jika produk dijual ke banyak operator.
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border border-slate-200 p-6 text-center text-xs text-slate-500 space-y-2 no-print">
      <p class="font-bold text-slate-900"><?= htmlspecialchars($communityName) ?> &mdash; Badminton Management System</p>
      <p>Dokumen ini dapat dicetak ke format PDF standar A4 portrait untuk arsip pengurus dan PIC.</p>
      <p class="font-mono text-[10px] text-slate-400">Versi aplikasi 2.4.0 &bull; Panduan diverifikasi terhadap kode aplikasi</p>
    </footer>

  </main>

  <script>
    function switchGuideTab(tabCategory, btnEl) {
      document.querySelectorAll('.tab-filter-btn').forEach(function (btn) {
        btn.classList.remove('active', 'bg-[#0b1e36]', 'text-white');
        btn.classList.add('bg-slate-100', 'text-slate-800');
      });

      btnEl.classList.add('active', 'bg-[#0b1e36]', 'text-white');
      btnEl.classList.remove('bg-slate-100', 'text-slate-800');

      document.querySelectorAll('.guide-topic-card').forEach(function (card) {
        card.style.display = (tabCategory === 'all' || card.getAttribute('data-category') === tabCategory) ? 'block' : 'none';
      });
    }

    function filterGuideTopics() {
      var query = document.getElementById('guideSearchInput').value.toLowerCase().trim();
      document.querySelectorAll('.guide-topic-card').forEach(function (card) {
        var keywords = (card.getAttribute('data-keywords') || '').toLowerCase();
        var content = card.textContent.toLowerCase();
        card.style.display = (query === '' || keywords.indexOf(query) !== -1 || content.indexOf(query) !== -1) ? 'block' : 'none';
      });
    }
  </script>

</body>
</html>
