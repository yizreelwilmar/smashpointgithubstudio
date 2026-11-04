<?php
// admin/manual-book-pdf.php
// Buku Panduan Operasional & Playbook Demo Komunitas Badminton
// Format: Standar Cetak PDF ISO A4 Portrait (Print-Ready Layout)
// Disusun untuk presentasi ke calon klien (Komunitas, Pemilik GOR, Event Organizer)

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Community.php';
require_once __DIR__ . '/../models/Schedule.php';

date_default_timezone_set('Asia/Jakarta');

$settings = Community::getSettings();
$communityName = !empty($settings['community_name']) ? $settings['community_name'] : 'Powershoot Badminton Club';
$baseCity = !empty($settings['base_city']) ? $settings['base_city'] : 'Jakarta Selatan';
$defaultWa = !empty($settings['whatsapp_default']) ? $settings['whatsapp_default'] : '081299887766';
$allSchedules = Schedule::all();
$printDate = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buku Panduan & Demo Playbook - <?= htmlspecialchars($communityName) ?></title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: #0f172a;
      background-color: #f1f5f9;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    .font-mono {
      font-family: 'JetBrains Mono', monospace;
    }

    /* A4 Dimensions for On-Screen Preview */
    .a4-page {
      width: 210mm;
      min-height: 297mm;
      padding: 18mm 18mm 18mm 18mm;
      margin: 15px auto;
      background: #ffffff;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      position: relative;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    /* Print Stylesheet for Pure PDF Generation */
    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .no-print {
        display: none !important;
      }
      .a4-page {
        width: 100% !important;
        min-height: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 14mm 16mm !important;
        box-shadow: none !important;
        page-break-after: always !important;
        break-after: page !important;
      }
      .a4-page:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
      }
      .page-break-inside-avoid {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
      @page {
        size: A4 portrait;
        margin: 0;
      }
    }
  </style>
</head>
<body class="antialiased">

  <!-- Top Floating Action Bar (Hidden in Print) -->
  <aside aria-label="Toolbar Aksi Dokumen" class="no-print sticky top-0 z-50 bg-slate-900 text-white border-b border-slate-800 px-4 py-3 shadow-lg">
    <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <a href="index.php?tab=manual_book" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg border border-slate-700 transition-colors flex items-center gap-1.5">
          <span>&larr; Kembali ke Admin</span>
        </a>
        <div class="hidden sm:block">
          <span class="text-xs font-bold text-amber-400 font-mono">STANDAR CETAK A4</span>
          <span class="text-slate-400 text-xs ml-2">Preview PDF Manual Book & Pitch Script</span>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()" class="px-5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider rounded-lg shadow-md transition-all active:scale-95 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          <span>Cetak / Simpan PDF Sekarang</span>
        </button>
      </div>
    </div>
  </aside>

  <!-- ========================================================================= -->
  <!-- HALAMAN 1: COVER EKSEKUTIF RESMI                                          -->
  <!-- ========================================================================= -->
  <div class="a4-page">
    
    <!-- Header Cover -->
    <div class="border-b-2 border-slate-900 pb-6">
      <div class="flex items-center justify-between">
        <div>
          <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-amber-600 block">
            MANUAL OPERASIONAL & PLAYBOOK DEMO
          </span>
          <span class="text-xs font-semibold text-slate-500">
            Platform Manajemen Komunitas & Sesi Mabar Badminton
          </span>
        </div>
        <div class="text-right">
          <span class="text-xs font-mono font-bold bg-slate-100 text-slate-800 px-2.5 py-1 rounded border border-slate-300">
            EDISI <?= date('Y') ?> • V2.4
          </span>
        </div>
      </div>
    </div>

    <!-- Center Hero Cover -->
    <div class="my-auto py-12">
      <div class="inline-block px-3 py-1 bg-[#0b1e36] text-amber-400 text-xs font-mono font-bold uppercase tracking-wider mb-5">
        PANDUAN LENGKAP & ALUR DEMO KE KLIEN
      </div>

      <h1 class="text-4xl sm:text-5xl font-black text-slate-950 tracking-tight leading-[1.15] mb-6">
        BUKU PANDUAN PENGOPERASIAN SISTEM & SKRIP DEMO 1-BY-1
      </h1>

      <p class="text-base text-slate-700 leading-relaxed max-w-xl mb-8">
        Panduan langkah demi langkah menjalankan sistem mabar badminton, manajemen akun PIC per cabang, alur rotasi lapangan otomatis, serta strategi mempresentasikan platform ini kepada calon klien (pemilik GOR, pengurus komunitas, dan EO olahraga) agar langsung tertarik menggunakan.
      </p>

      <div class="grid grid-cols-2 gap-4 max-w-md pt-4 border-t border-slate-200 text-xs">
        <div>
          <span class="text-slate-400 font-mono uppercase text-[10px] block">Disusun Untuk:</span>
          <span class="font-bold text-slate-900"><?= htmlspecialchars($communityName) ?></span>
        </div>
        <div>
          <span class="text-slate-400 font-mono uppercase text-[10px] block">Basis Wilayah:</span>
          <span class="font-bold text-slate-900"><?= htmlspecialchars($baseCity) ?></span>
        </div>
      </div>
    </div>

    <!-- Footer Cover -->
    <div class="border-t border-slate-200 pt-4 flex items-center justify-between text-[11px] text-slate-500 font-mono">
      <span>Diterbitkan: <?= $printDate ?></span>
      <span>Halaman 1 dari 5</span>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- HALAMAN 2: VALUE PROPOSITION & PERMASALAHAN KOMUNITAS                     -->
  <!-- ========================================================================= -->
  <div class="a4-page">
    
    <!-- Header -->
    <div class="border-b border-slate-200 pb-3 flex items-center justify-between text-xs text-slate-500 font-mono">
      <span>01 • VALUE PROPOSITION & LATAR BELAKANG</span>
      <span><?= htmlspecialchars($communityName) ?></span>
    </div>

    <!-- Body -->
    <div class="my-auto py-4 space-y-6">
      
      <div>
        <h2 class="text-2xl font-black text-slate-950 tracking-tight">
          Mengapa Komunitas Badminton Membutuhkan Sistem Ini?
        </h2>
        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
          Mengelola main bareng badminton dengan metode chat WhatsApp manual menimbulkan 4 kendala kritis yang menguras waktu pengurus:
        </p>
      </div>

      <!-- 4 Pain Points Grid -->
      <div class="grid grid-cols-2 gap-3.5">
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="text-rose-600 font-black text-xs font-mono uppercase">01. Rekap Chat Berantakan</div>
          <p class="text-xs text-slate-700 mt-1 leading-relaxed">
            Format list di grup sering tertumpuk chat obrolan, salah hitung kuota, dan anggota bingung apakah masih ada slot tersisa.
          </p>
        </div>

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="text-rose-600 font-black text-xs font-mono uppercase">02. Drama Rotasi di Lapangan</div>
          <p class="text-xs text-slate-700 mt-1 leading-relaxed">
            Pemain merasa ada yang main terlalu sering sementara pemain lain terlalu lama menunggu giliran. Rawan memicu perselisihan.
          </p>
        </div>

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="text-rose-600 font-black text-xs font-mono uppercase">03. Pembatalan Mendadak & Kas Minus</div>
          <p class="text-xs text-slate-700 mt-1 leading-relaxed">
            Pemain cancel H-1 jam tanpa bayar, biaya sewa GOR dan kok tetap harus dibayar, mengakibatkan kas komunitas sering nombok.
          </p>
        </div>

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="text-rose-600 font-black text-xs font-mono uppercase">04. Sulit Ekspansi Cabang</div>
          <p class="text-xs text-slate-700 mt-1 leading-relaxed">
            Pengurus tidak memiliki reporting jelas tentang PIC mana yang memegang sesi apa, berapa pemain aktif, dan rating kepuasan.
          </p>
        </div>
      </div>

      <!-- Solution Overview -->
      <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-lg">
        <span class="text-amber-800 font-mono font-bold text-xs uppercase tracking-wider block">
          SOLUSI TOTAL: EKOSISTEM DIGITAL BADMINTON
        </span>
        <h3 class="text-base font-black text-slate-900 mt-1">
          Satu Platform, Tiga Komponen Terintegrasi
        </h3>
        <div class="grid grid-cols-3 gap-3 mt-3 text-xs">
          <div>
            <span class="font-bold text-slate-900 block">1. Landing Page Publik</span>
            <p class="text-slate-600 text-[11px] mt-0.5">Showcase video sinematik, jadwal real-time, transparansi slot, dan tombol pendaftaran 1-tap WhatsApp PIC.</p>
          </div>
          <div>
            <span class="font-bold text-slate-900 block">2. Admin CMS Pusat</span>
            <p class="text-slate-600 text-[11px] mt-0.5">Manajemen sesi mabar, fasilitas venue, keuangan kas, database pemain, dan penugasan PIC per cabang.</p>
          </div>
          <div>
            <span class="font-bold text-slate-900 block">3. Aplikasi PIC Lapangan</span>
            <p class="text-slate-600 text-[11px] mt-0.5">Alat mobile PIC untuk absensi, pengacak rotasi ganda berkeadilan, input skor pertandingan, dan klasemen poin.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <div class="border-t border-slate-200 pt-3 flex items-center justify-between text-[11px] text-slate-500 font-mono">
      <span>Buku Panduan & Playbook Demo</span>
      <span>Halaman 2 dari 5</span>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- HALAMAN 3: ALUR DEMO 1-BY-1 KE CALON CUSTOMER (THE SALES PITCH)           -->
  <!-- ========================================================================= -->
  <div class="a4-page">
    
    <!-- Header -->
    <div class="border-b border-slate-200 pb-3 flex items-center justify-between text-xs text-slate-500 font-mono">
      <span>02 • ALUR DEMO 1-BY-1 KE CALON CUSTOMER</span>
      <span>SKRIP & FLOW PRESENTASI</span>
    </div>

    <!-- Body -->
    <div class="my-auto py-2 space-y-4">
      
      <div>
        <h2 class="text-2xl font-black text-slate-950 tracking-tight">
          Alur Presentasi Demo 1-by-1 (6 Langkah Memikat Customer)
        </h2>
        <p class="text-xs text-slate-600 mt-1">
          Gunakan urutan ini saat mendemokan aplikasi ke pengurus komunitas, pemilik GOR, atau calon mitra:
        </p>
      </div>

      <!-- Step by Step Demo Script -->
      <div class="space-y-3 text-xs">
        
        <!-- Step 1 -->
        <div class="border border-slate-200 p-3 rounded-lg bg-white">
          <div class="flex items-center justify-between font-mono font-bold text-[11px] text-amber-600 mb-1">
            <span>LANGKAH 1 • PEMBUKAAN (THE PAIN HOOK) — Waktu: 2 Menit</span>
            <span class="bg-amber-100 px-2 py-0.5 rounded text-amber-800">Tanya Jawab</span>
          </div>
          <p class="text-slate-700 leading-relaxed">
            <b>Kalimat Demo:</b> <i>"Halo Mas/Mba, selama ini kalau buka sesi mabar, sering pusing ga rekap list di WhatsApp, ngurusin transferan yang belum masuk, sampai komplain pemain yang merasa kurang banyak main di lapangan? Hari ini saya tunjukkan bagaimana sistem ini menyelesaikan semuanya secara otomatis."</i>
          </p>
        </div>

        <!-- Step 2 -->
        <div class="border border-slate-200 p-3 rounded-lg bg-white">
          <div class="flex items-center justify-between font-mono font-bold text-[11px] text-amber-600 mb-1">
            <span>LANGKAH 2 • TAMPILKAN LANDING PAGE (FIRST IMPRESSION) — Waktu: 3 Menit</span>
            <span class="bg-emerald-100 px-2 py-0.5 rounded text-emerald-800">Visual Impact</span>
          </div>
          <p class="text-slate-700 leading-relaxed">
            <b>Aksi Demo:</b> Buka Web Depan. Tunjukkan background video badminton yang hidup, status komunitas resmi, dan kartu <b>"Sesi Mabar Terdekat"</b>. Tunjukkan bahwa pemain bisa langsung melihat sisa kuota tanpa harus tanya berulang-ulang di grup chat.
          </p>
        </div>

        <!-- Step 3 -->
        <div class="border border-slate-200 p-3 rounded-lg bg-white">
          <div class="flex items-center justify-between font-mono font-bold text-[11px] text-amber-600 mb-1">
            <span>LANGKAH 3 • DEMO ADMIN: 1-CLICK GENERATE PIC & JADWAL — Waktu: 3 Menit</span>
            <span class="bg-blue-100 px-2 py-0.5 rounded text-blue-800">Admin Control</span>
          </div>
          <p class="text-slate-700 leading-relaxed">
            <b>Aksi Demo:</b> Buka <code>/admin/?tab=pic_accounts</code>. Klik tombol hijau <b>"1-Click Generate Akun PIC Demo"</b>. Tunjukkan kepada klien: dalam 1 detik akun PIC langsung terbit, otomatis terhubung ke jadwal GOR, dan siap dipakai login. Jelaskan betapa mudahnya menugaskan PIC lapangan.
          </p>
        </div>

        <!-- Step 4 -->
        <div class="border border-slate-200 p-3 rounded-lg bg-white">
          <div class="flex items-center justify-between font-mono font-bold text-[11px] text-amber-600 mb-1">
            <span>LANGKAH 4 • DAFTAR VIA WHATSAPP PIC (ZERO FRICTION) — Waktu: 2 Menit</span>
            <span class="bg-purple-100 px-2 py-0.5 rounded text-purple-800">User Flow</span>
          </div>
          <p class="text-slate-700 leading-relaxed">
            <b>Aksi Demo:</b> Klik tombol "Daftar Sesi" di landing page. Tunjukkan pesan WhatsApp yang otomatis terformat rapi dengan nama sesi, tanggal, dan GOR. Pemain tinggal kirim, PIC tinggal konfirmasi.
          </p>
        </div>

        <!-- Step 5 -->
        <div class="border border-slate-200 p-3 rounded-lg bg-white">
          <div class="flex items-center justify-between font-mono font-bold text-[11px] text-amber-600 mb-1">
            <span>LANGKAH 5 • APLIKASI LAPANGAN & ROTASI DIGITAL ADIL — Waktu: 3 Menit</span>
            <span class="bg-indigo-100 px-2 py-0.5 rounded text-indigo-800">The "Aha!" Moment</span>
          </div>
          <p class="text-slate-700 leading-relaxed">
            <b>Aksi Demo:</b> Buka <code>/app/</code>. Tunjukkan daftar pemain hadir, klik "Acak Rotasi Ganda". Tunjukkan bahwa sistem membagi giliran main secara adil berdasarkan riwayat bertanding. Tidak ada lagi pemain yang merasa dianaktirikan!
          </p>
        </div>

        <!-- Step 6 -->
        <div class="border border-slate-200 p-3 rounded-lg bg-white">
          <div class="flex items-center justify-between font-mono font-bold text-[11px] text-amber-600 mb-1">
            <span>LANGKAH 6 • CLOSING & REKAP KAS (THE WINNING PROMISE) — Waktu: 2 Menit</span>
            <span class="bg-amber-100 px-2 py-0.5 rounded text-amber-800">Closing Deal</span>
          </div>
          <p class="text-slate-700 leading-relaxed">
            <b>Kalimat Closing:</b> <i>"Dengan platform ini, mabar Anda terlihat profesional seperti klub resmi, slot mabar selalu penuh, uang iuran tercatat rapi, dan Anda bisa santai ikut main tanpa capek ngurusin antrean lapangan."</i>
          </p>
        </div>

      </div>

    </div>

    <!-- Footer -->
    <div class="border-t border-slate-200 pt-3 flex items-center justify-between text-[11px] text-slate-500 font-mono">
      <span>Buku Panduan & Playbook Demo</span>
      <span>Halaman 3 dari 5</span>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- HALAMAN 4: PANDUAN TEKNIS OPERASIONAL PIC & ADMIN                         -->
  <!-- ========================================================================= -->
  <div class="a4-page">
    
    <!-- Header -->
    <div class="border-b border-slate-200 pb-3 flex items-center justify-between text-xs text-slate-500 font-mono">
      <span>03 • PANDUAN TEKNIS & SOP OPERASIONAL</span>
      <span>STANDAR OPERASI LAPANGAN</span>
    </div>

    <!-- Body -->
    <div class="my-auto py-2 space-y-4">
      
      <div>
        <h2 class="text-2xl font-black text-slate-950 tracking-tight">
          SOP Operasional PIC Lapangan (Hari-H Mabar)
        </h2>
        <p class="text-xs text-slate-600 mt-1">
          Panduan langkah operasional bagi PIC yang ditugaskan di lapangan:
        </p>
      </div>

      <!-- SOP Timeline -->
      <div class="space-y-3 text-xs">
        
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="font-mono font-bold text-slate-900 text-[11px] mb-1">
            FASE 1: H-3 S/D H-1 (RESERVASI & PENETAPAN JADWAL)
          </div>
          <ul class="list-disc list-inside space-y-1 text-slate-600 text-[11px]">
            <li>Admin mengonfirmasi booking lapangan di GOR mitra dan memasukkan fasilitas venue (Karpet Vinyl, Lampu LED, Kantin).</li>
            <li>Admin membuat/menugaskan akun PIC pada tab <code>admin/?tab=pic_accounts</code>.</li>
            <li>Pemain mendaftar melalui tombol WhatsApp PIC di landing page. PIC memverifikasi bukti transfer iuran.</li>
          </ul>
        </div>

        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="font-mono font-bold text-slate-900 text-[11px] mb-1">
            FASE 2: HARI-H 30 MENIT SEBELUM MAIN (PERSIAPAN ARENA)
          </div>
          <ul class="list-disc list-inside space-y-1 text-slate-600 text-[11px]">
            <li>PIC tiba di venue 30 menit sebelum jadwal, mengecek kondisi karpet, jaring net, dan penerangan.</li>
            <li>PIC membuka <code>/app/</code> di smartphone dan login menggunakan akun PIC yang sudah dibuat.</li>
            <li>Menyiapkan shuttlecock bulu angsa turnamen dan kotak shuttlecock cadangan.</li>
          </ul>
        </div>

        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="font-mono font-bold text-slate-900 text-[11px] mb-1">
            FASE 3: SAAT SESI BERLANGSUNG (ROTASI DIGITAL & MATCH)
          </div>
          <ul class="list-disc list-inside space-y-1 text-slate-600 text-[11px]">
            <li>Lakukan centang absensi kehadiran pemain di menu Roster Sesi.</li>
            <li>Tekan tombol "Acak Rotasi Pertandingan" untuk membuat match ganda secara otomatis.</li>
            <li>Panggil nomor lapangan dan nama pasangan yang bertanding sesuai tampilan di layar HP.</li>
            <li>Input skor pertandingan setelah match selesai untuk memperbarui poin klasemen top scorer.</li>
          </ul>
        </div>

        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
          <div class="font-mono font-bold text-slate-900 text-[11px] mb-1">
            FASE 4: PASCA MAIN & LAPORAN (CLOSE SESSION)
          </div>
          <ul class="list-disc list-inside space-y-1 text-slate-600 text-[11px]">
            <li>Sesi foto bersama pemain dan upload dokumentasi ke Galeri Web via menu PIC Media.</li>
            <li>Status sesi otomatis berubah menjadi "Selesai" dan poin pertandingan tercatat di Leaderboard Bulanan.</li>
            <li>Admin dapat melihat laporan jumlah peserta hadir dan rekapitulasi kas masuk secara instan.</li>
          </ul>
        </div>

      </div>

    </div>

    <!-- Footer -->
    <div class="border-t border-slate-200 pt-3 flex items-center justify-between text-[11px] text-slate-500 font-mono">
      <span>Buku Panduan & Playbook Demo</span>
      <span>Halaman 4 dari 5</span>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- HALAMAN 5: MATRIKS KOMPARASI & PENANGANAN KEBERATAN KLIEN                 -->
  <!-- ========================================================================= -->
  <div class="a4-page">
    
    <!-- Header -->
    <div class="border-b border-slate-200 pb-3 flex items-center justify-between text-xs text-slate-500 font-mono">
      <span>04 • MATRIKS PERBANDINGAN & FAQ KEBERATAN</span>
      <span>OBJECTION HANDLING</span>
    </div>

    <!-- Body -->
    <div class="my-auto py-2 space-y-4">
      
      <div>
        <h2 class="text-xl font-black text-slate-950 tracking-tight">
          Perbandingan: Metode WhatsApp Manual vs. Platform Digital
        </h2>
      </div>

      <!-- Comparison Table -->
      <div class="border border-slate-200 rounded-lg overflow-hidden text-xs">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-900 text-white font-mono text-[10px] uppercase">
              <th class="p-2.5 border-b border-slate-800">Parameter</th>
              <th class="p-2.5 border-b border-slate-800 text-rose-300">Cara Lama (Manual Grup WA)</th>
              <th class="p-2.5 border-b border-slate-800 text-amber-300">Platform Powershoot</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 text-[11px]">
            <tr>
              <td class="p-2.5 font-bold text-slate-800">Pendaftaran Slot</td>
              <td class="p-2.5 text-slate-600">Ketik list manual, sering bentrok/terlewat</td>
              <td class="p-2.5 text-emerald-700 font-bold">1-tap booking, kuota terupdate otomatis</td>
            </tr>
            <tr class="bg-slate-50">
              <td class="p-2.5 font-bold text-slate-800">Pembagian Rotasi</td>
              <td class="p-2.5 text-slate-600">Tunjuk manual, rawan pilih kasih/protes</td>
              <td class="p-2.5 text-emerald-700 font-bold">Algoritma otomatis adil (Fair Play)</td>
            </tr>
            <tr>
              <td class="p-2.5 font-bold text-slate-800">Pencatatan Keuangan</td>
              <td class="p-2.5 text-slate-600">Catat di buku / Excel terpisah, rawan lupa</td>
              <td class="p-2.5 text-emerald-700 font-bold">Rekap kas realtime per sesi & bulanan</td>
            </tr>
            <tr class="bg-slate-50">
              <td class="p-2.5 font-bold text-slate-800">Manajemen Multi-GOR</td>
              <td class="p-2.5 text-slate-600">Bikin grup WA terpisah, koordinasi kacau</td>
              <td class="p-2.5 text-emerald-700 font-bold">Satu admin, penugasan PIC per cabang rapi</td>
            </tr>
            <tr>
              <td class="p-2.5 font-bold text-slate-800">Daya Tarik Member</td>
              <td class="p-2.5 text-slate-600">Biasa saja, seperti mabar rumahan</td>
              <td class="p-2.5 text-emerald-700 font-bold">Sangat prestisius, ada leaderboard & web resmi</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- FAQ Handling -->
      <div class="pt-2">
        <h3 class="text-sm font-black text-slate-900 mb-2">
          Jawaban Atas Keberatan Calon Klien (FAQ Demo)
        </h3>
        <div class="space-y-2 text-[11px] text-slate-700">
          <div class="p-2.5 bg-slate-50 border border-slate-200 rounded">
            <span class="font-bold text-slate-900 block">Q: "Pemain kami banyak yang sudah berumur dan gaptek, apakah sulit?"</span>
            <span class="text-slate-600 block mt-0.5">A: Pemain sama sekali tidak perlu mengunduh aplikasi atau membuat akun rumit. Di sisi pemain, mereka hanya klik link di website yang langsung membuka WhatsApp seperti biasa. Sistem canggihnya bekerja di sisi Admin dan PIC.</span>
          </div>

          <div class="p-2.5 bg-slate-50 border border-slate-200 rounded">
            <span class="font-bold text-slate-900 block">Q: "Bagaimana jika ada PIC yang berhalangan hadir di hari-H?"</span>
            <span class="text-slate-600 block mt-0.5">A: Admin cukup membuka menu Penugasan PIC di CMS, lalu klik ganti PIC untuk jadwal tersebut dalam 5 detik. Nomor WhatsApp di landing page akan otomatis berpindah ke PIC pengganti.</span>
          </div>

          <div class="p-2.5 bg-slate-50 border border-slate-200 rounded">
            <span class="font-bold text-slate-900 block">Q: "Apakah bisa untuk mabar di lebih dari 5 GOR berbeda?"</span>
            <span class="text-slate-600 block mt-0.5">A: Sangat bisa. Sistem didesain tanpa batas jumlah venue, jadwal, maupun akun PIC per cabang.</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <div class="border-t border-slate-200 pt-3 flex items-center justify-between text-[11px] text-slate-500 font-mono">
      <span>Dokumen Resmi <?= htmlspecialchars($communityName) ?></span>
      <span>Halaman 5 dari 5 (Selesai)</span>
    </div>

  </div>

</body>
</html>
