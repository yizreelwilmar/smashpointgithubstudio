<?php
// guide.php
// Powershoot Badminton — Interactive User Manual & Operating Guide
// Features: Searchable Manual, Annotated Visual UI Screenshot Cards, PDF Print Layout

require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/models/Community.php';

date_default_timezone_set('Asia/Jakarta');
$settings = Community::getSettings();

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
$landingUrl = $baseDir . '/';
$appUrl = $baseDir . '/app/';
$adminUrl = $baseDir . '/admin/';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Buku Panduan Pengguna & SOP Operasional — <?= htmlspecialchars($settings['community_name']) ?></title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  
  <style>
    body, h1, h2, h3, h4, h5, h6, p, span, button, a {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace !important;
    }
    
    /* Print Layout Configuration for Clean A4 PDF Export */
    @media print {
      .no-print {
        display: none !important;
      }
      body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
      }
      .print-page-break {
        page-break-before: always !important;
      }
      .print-shadow-none {
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important;
      }
      .print-expand-all {
        display: block !important;
      }
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

  <!-- Top Announcement Bar / Header (no-print) -->
  <header class="bg-[#0b1e36] text-white border-b border-slate-800 sticky top-0 z-50 no-print shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3">
      
      <!-- Brand & Title -->
      <div class="flex items-center gap-3">
        <a href="<?= $landingUrl ?>" class="w-9 h-9 bg-amber-400 text-slate-950 font-black text-sm flex items-center justify-center font-mono hover:bg-amber-300 transition-colors">
          PB
        </a>
        <div>
          <h1 class="text-sm sm:text-base font-black text-white tracking-tight leading-none">
            Buku Panduan Pengguna (User Manual Book)
          </h1>
          <p class="text-[11px] text-amber-400 font-mono mt-0.5">
            SOP Operasional CMS Admin & Aplikasi Lapangan PIC • Versi 2.4
          </p>
        </div>
      </div>

      <!-- Quick Action Buttons -->
      <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-xs flex items-center gap-1.5 active:scale-95">
          <span>🖨️ Cetak / Download PDF</span>
        </button>
        <a href="<?= $adminUrl ?>" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs transition-colors border border-slate-700">
          Kembali ke Admin CMS →
        </a>
      </div>

    </div>
  </header>

  <!-- Main Container -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Hero / Title Card -->
    <div class="bg-white border border-slate-200 p-6 sm:p-8 shadow-xs rounded-none space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-6">
        <div class="space-y-2 max-w-3xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-900 text-amber-400 font-mono font-bold text-xs uppercase tracking-wider border border-amber-400/30">
            <span>DOKUMENTASI RESMI OPERASIONAL</span>
            <span>•</span>
            <span><?= htmlspecialchars(strtoupper($settings['community_name'])) ?></span>
          </div>
          <h2 class="text-2xl sm:text-4xl font-black text-slate-950 tracking-tight">
            Panduan Lengkap Admin CMS & PIC Lapangan
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed font-normal">
            Selamat datang di buku petunjuk penggunaan aplikasi operasional badminton. Dokumentasi ini disusun bertahap dilengkapi <strong>tangkapan layar UI visual</strong>, alur transaksi kas, pengelolaan jadwal mabar, absensi pemain, rotasi ganda otomatis, input skor, hingga upload galeri foto/video.
          </p>
        </div>

        <div class="bg-slate-50 border border-slate-200 p-4 space-y-2 shrink-0 max-w-xs font-mono text-xs">
          <div class="text-slate-500 font-bold uppercase text-[10px]">Akses Alamat Aplikasi:</div>
          <div class="space-y-1">
            <div><strong class="text-slate-900">Web Landing:</strong> <a href="<?= $landingUrl ?>" class="text-blue-600 underline" target="_blank"><?= $_SERVER['HTTP_HOST'] ?>/</a></div>
            <div><strong class="text-slate-900">Aplikasi PIC:</strong> <a href="<?= $appUrl ?>" class="text-blue-600 underline" target="_blank"><?= $_SERVER['HTTP_HOST'] ?>/app/</a></div>
            <div><strong class="text-slate-900">CMS Admin:</strong> <a href="<?= $adminUrl ?>" class="text-blue-600 underline" target="_blank"><?= $_SERVER['HTTP_HOST'] ?>/admin/</a></div>
          </div>
        </div>
      </div>

      <!-- Search & Category Filters (no-print) -->
      <div class="no-print pt-2 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Live Keyword Search -->
        <div class="relative flex-1 max-w-md">
          <input type="text" 
                 id="guideSearchInput" 
                 onkeyup="filterGuideTopics()" 
                 placeholder="Cari topik (misal: rotasi, lunas, video, seo, wa)..." 
                 class="w-full bg-slate-50 border border-slate-300 text-slate-900 px-4 py-2.5 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-2xs">
          <span class="absolute right-3 top-2.5 text-slate-400 text-xs font-mono">🔍</span>
        </div>

        <!-- Filter Tab Buttons -->
        <div class="flex flex-wrap items-center gap-1.5 font-mono text-xs">
          <button type="button" onclick="switchGuideTab('all', this)" class="tab-filter-btn active px-3 py-1.5 bg-[#0b1e36] text-white font-bold uppercase shadow-2xs">
            Semua Modul
          </button>
          <button type="button" onclick="switchGuideTab('cms', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">
            Admin CMS
          </button>
          <button type="button" onclick="switchGuideTab('pic', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">
            PIC Sesi Lapangan
          </button>
          <button type="button" onclick="switchGuideTab('media', this)" class="tab-filter-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold uppercase border border-slate-200">
            Foto & Video Galeri
          </button>
        </div>
      </div>

    </div>

    <!-- MODULE 1: SUPER ADMIN CMS CONTROL PANEL -->
    <section class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-6 shadow-xs" data-category="cms" data-keywords="admin cms pengaturan seo profil website komunitas jadwal sesi mabar iuran kas piutang roster pemain">
      
      <div class="flex items-center justify-between border-b border-slate-100 pb-4">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center">
            01
          </span>
          <div>
            <h3 class="text-xl font-black text-slate-950 tracking-tight">
              MODUL 1: Super Admin CMS Control Panel
            </h3>
            <p class="text-xs text-slate-500 font-normal">
              Pusat komando pengelolaan komunitas dari profil web, jadwal sesi, keuangan, hingga daftar roster pemain.
            </p>
          </div>
        </div>
      </div>

      <div class="space-y-6 text-xs text-slate-700 leading-relaxed">
        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">1.1 Mengedit Profil Website & Branding</h4>
          <p>Buka tab <strong>Pengaturan</strong>. Anda dapat mengubah: Nama komunitas, tagline, deskripsi web, logo, foto hero banner, tautan Instagram, serta nomor WhatsApp default untuk pendaftaran. Perubahan ini langsung tampil di landing page publik.</p>
        </div>
        
        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">1.2 Kelola Jadwal Sesi Mabar</h4>
          <p>Pada tab <strong>Jadwal</strong>, klik "+ Buat Jadwal" untuk mengatur lokasi GOR, jam main, harga iuran per orang, dan kuota maksimal. Gunakan fitur "Duplikasi Sesi" untuk sesi mabar rutin pekan berikutnya agar hemat waktu.</p>
        </div>

        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">1.3 Manajemen PIC & Akun Operasional</h4>
          <p>Pada tab <strong>Akun PIC</strong>, buat akun baru untuk setiap PIC lapangan. Penugasan sesi dilakukan secara 1-to-1: pilih PIC lalu hubungkan dengan jadwal sesi tertentu. PIC akan login menggunakan akun ini di aplikasi lapangan (`/app/`).</p>
        </div>

        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">1.4 Keuangan Kas & Buku Piutang</h4>
          <p>Pada tab <strong>Laporan Keuangan</strong>, catat setiap pemasukan (uang iuran lunas) dan pengeluaran (sewa lapangan, kok). Sistem otomatis menghitung saldo surplus/defisit dan menampilkan piutang pemain yang belum bayar iuran.</p>
        </div>
      </div>
    </section>

    <!-- MODULE 2: PIC SESI LAPANGAN (COURTSIDE APP) -->
    <section class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-6 shadow-xs" data-category="pic" data-keywords="pic aplikasi lapangan absensi lunas unpaid rotasi skor skor ganda">
      
      <div class="flex items-center justify-between border-b border-slate-100 pb-4">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center">
            02
          </span>
          <div>
            <h3 class="text-xl font-black text-slate-950 tracking-tight">
              MODUL 2: Aplikasi Field PIC (`/app/`)
            </h3>
            <p class="text-xs text-slate-500 font-normal">
              Tool operasional mobile-first di lapangan: absensi, rotasi ganda, dan input skor.
            </p>
          </div>
        </div>
      </div>

      <div class="space-y-6 text-xs text-slate-700 leading-relaxed">
        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">2.1 Absensi & Status Pembayaran</h4>
          <p>PIC login ke `/app/` menggunakan kredensial PIC. Daftar pemain hadir terlihat otomatis. Tandai pemain yang hadir dan konfirmasi status pembayaran (LUNAS/BELUM LUNAS). Data ini otomatis memperbarui laporan kas admin.</p>
        </div>
        
        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">2.2 Sistem Rotasi Ganda Otomatis</h4>
          <p>Setelah absensi, klik "Acak Rotasi". Sistem akan mengelompokkan 4 pemain per court secara adil (berdasarkan level & riwayat main). PIC cukup membacakan pasangan lapangan dari layar HP.</p>
        </div>

        <div class="space-y-2">
          <h4 class="font-bold text-slate-900 text-sm">2.3 Input Skor & Selesai Sesi</h4>
          <p>Setelah reli selesai, masukkan skor pertandingan di aplikasi. Poin otomatis terakumulasi ke Leaderboard Pemain. Sesi otomatis terkunci saat ditandai selesai oleh PIC.</p>
        </div>
      </div>
    </section>

    <!-- MODULE 3: FOTO & VIDEO DOKUMENTASI GALERI -->
    <section class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-6 shadow-xs" data-category="media" data-keywords="galeri dokumentasi foto video mp4 highlight poster pic">
      
      <div class="flex items-center justify-between border-b border-slate-100 pb-4">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center">
            03
          </span>
          <div>
            <h3 class="text-xl font-black text-slate-950 tracking-tight">
              MODUL 3: Upload Dokumentasi Galeri (Foto & Video MP4)
            </h3>
            <p class="text-xs text-slate-500 font-normal">
              Panduan PIC dan Admin dalam mengunggah dokumentasi foto mabar serta video highlight smash pertandingan.
            </p>
          </div>
        </div>
        <span class="text-xs font-mono font-bold text-blue-700 bg-blue-50 px-2.5 py-1 border border-blue-200">
          Format: Photo & Video
        </span>
      </div>

      <div class="space-y-4">
        <h4 class="text-base font-bold text-slate-900">3.1 Mengunggah Foto & Video Highlight Mabar</h4>
        <p class="text-xs text-slate-600 leading-relaxed font-normal">
          PIC dapat langsung mengambil foto kegiatan atau mengunggah video pendek highlight mabar (`.mp4`, `.mov`, `.webm`) dari HP saat sesi berlangsung. Video akan tampil dengan icon play dan Lightbox Cinema Player interaktif di `/gallery.php`.
        </p>

        <!-- Visual Screenshot Mockup Card -->
        <div class="border border-slate-300 bg-slate-900 text-white p-4 sm:p-6 rounded-none space-y-4 font-sans shadow-md">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3 text-xs font-mono">
            <span class="text-amber-400 font-bold">🎬 TANGKAPAN LAYAR UI: Panel Upload Media & Cinema Lightbox Player</span>
            <span class="text-slate-400">/gallery.php</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
            <!-- Form Upload -->
            <div class="bg-slate-950 p-4 border border-slate-800 space-y-3">
              <span class="text-amber-400 font-bold block text-[11px]">[1] Form Upload Foto/Video</span>
              <div class="space-y-2 text-slate-300">
                <div>
                  <label class="text-[10px] text-slate-500 block">JENIS MEDIA:</label>
                  <select class="w-full bg-slate-900 border border-slate-700 px-2 py-1 text-white text-xs font-bold">
                    <option>📹 Video Highlight (.mp4)</option>
                    <option>📷 Foto Dokumentasi</option>
                  </select>
                </div>
                <div>
                  <label class="text-[10px] text-slate-500 block">JUDUL VIDEO / CAPTION:</label>
                  <input type="text" value="Smash Keras Ronde 3 GOR Main Court" class="w-full bg-slate-900 border border-slate-700 px-2 py-1 text-white text-xs" readonly>
                </div>
              </div>
            </div>

            <!-- Cinema Lightbox Preview -->
            <div class="bg-slate-950 p-4 border border-slate-800 space-y-2 text-center">
              <span class="text-emerald-400 font-bold block text-[11px]">[2] Lightbox Cinema Player</span>
              <div class="aspect-video bg-black border border-slate-700 flex items-center justify-center relative">
                <span class="text-amber-400 text-2xl">▶</span>
                <span class="absolute bottom-2 left-2 text-[10px] text-white bg-black/80 px-2 py-0.5">00:45 • MP4 HD</span>
              </div>
              <p class="text-[10px] text-slate-400">Mendukung kontrol putar HTML5 & fullscreen layar penuh.</p>
            </div>
          </div>
        </div>
      </div>

    </section>

    <!-- MODULE 4: FORMULA PERHITUNGAN LEADERBOARD -->
    <section class="guide-topic-card bg-white border border-slate-200 p-6 sm:p-8 space-y-6 shadow-xs" data-category="cms" data-keywords="leaderboard poin klasemen menang rally win bonus">
      
      <div class="flex items-center justify-between border-b border-slate-100 pb-4">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 bg-slate-950 text-amber-400 font-mono font-black text-sm flex items-center justify-center">
            04
          </span>
          <div>
            <h3 class="text-xl font-black text-slate-950 tracking-tight">
              MODUL 4: Standar & Rumus Poin Leaderboard Bulanan
            </h3>
            <p class="text-xs text-slate-500 font-normal">
              Sistem perhitungan klasemen "Banyak-Banyakan Poin" fair play secara transparan.
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <div class="border border-slate-200 p-4 bg-slate-50 space-y-2">
          <span class="text-amber-600 font-mono font-bold block text-[11px]">RUMUS 01</span>
          <h5 class="font-bold text-slate-900 text-sm">Poin Reli Murni</h5>
          <p class="text-slate-600 leading-relaxed">
            Setiap angka yang dicetak pasangan di lapangan langsung masuk ke profil akumulasi pemain (misal skor 21-18, pemenang dapat 21 dan kalah mengantongi 18).
          </p>
        </div>

        <div class="border border-slate-200 p-4 bg-slate-50 space-y-2">
          <span class="text-amber-600 font-mono font-bold block text-[11px]">RUMUS 02</span>
          <h5 class="font-bold text-slate-900 text-sm">Win Bonus (+5 Poin)</h5>
          <p class="text-slate-600 leading-relaxed">
            Pasangan yang berhasil memenangkan ronde pertandingan memperoleh bonus kemenangan otomatis sebesar <strong>+5 poin tambahan</strong>.
          </p>
        </div>

        <div class="border border-slate-200 p-4 bg-slate-50 space-y-2">
          <span class="text-amber-600 font-mono font-bold block text-[11px]">RUMUS 03</span>
          <h5 class="font-bold text-slate-900 text-sm">Pembeda Seri (Tie-Breaker)</h5>
          <p class="text-slate-600 leading-relaxed">
            Jika total poin sama, peringkat ditentukan berturut-turut melalui: 1) Selisih Poin (+/-) → 2) Win Rate (%) → 3) Total Kemenangan (Wins).
          </p>
        </div>
      </div>

    </section>

    <!-- Footer Summary (no-print) -->
    <footer class="bg-white border border-slate-200 p-6 text-center text-xs text-slate-500 space-y-2 no-print">
      <p class="font-bold text-slate-900">
        <?= htmlspecialchars($settings['community_name']) ?> — Badminton Management System
      </p>
      <p>
        Dokumen panduan operasional ini siap dicetak ke format PDF standar A4 portrait untuk arsip pengurus dan PIC.
      </p>
    </footer>

  </main>

  <script>
    // Tab switching filter
    function switchGuideTab(tabCategory, btnEl) {
      document.querySelectorAll('.tab-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-[#0b1e36]', 'text-white');
        btn.classList.add('bg-slate-100', 'text-slate-800');
      });

      btnEl.classList.add('active', 'bg-[#0b1e36]', 'text-white');
      btnEl.classList.remove('bg-slate-100', 'text-slate-800');

      const cards = document.querySelectorAll('.guide-topic-card');
      cards.forEach(card => {
        if (tabCategory === 'all' || card.getAttribute('data-category') === tabCategory) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Keyword Search filter
    function filterGuideTopics() {
      const query = document.getElementById('guideSearchInput').value.toLowerCase().trim();
      const cards = document.querySelectorAll('.guide-topic-card');

      cards.forEach(card => {
        const keywords = (card.getAttribute('data-keywords') || '').toLowerCase();
        const content = card.textContent.toLowerCase();

        if (query === '' || keywords.includes(query) || content.includes(query)) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }
  </script>

</body>
</html>
