<?php
// views/admin/dashboard.php
// CMS Admin Management Overview
$pageTitle = "Admin CMS Dashboard";
require __DIR__ . '/../layout/header.php';
$user = Auth::user();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-white/[0.08]">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Control Panel</span>
      <h1 class="text-2xl sm:text-3xl font-black font-display text-white mt-1">Dashboard Admin SMASHPOINT</h1>
      <p class="text-xs text-slate-400 mt-1">Halo, <strong class="text-slate-200"><?= htmlspecialchars($user['name']) ?></strong> (<?= htmlspecialchars($user['role']) ?>)</p>
    </div>
    
    <div class="flex items-center gap-3">
      <a href="index.php?page=court" class="px-3.5 py-2 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] text-xs font-semibold text-slate-200 border border-white/[0.1] flex items-center gap-1.5">
        <i data-lucide="play" class="w-4 h-4 text-emerald-400"></i>
        <span>Buka Court Board</span>
      </a>
      <a href="index.php?page=admin-logout" class="px-3.5 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-xs font-bold flex items-center gap-1.5">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    
    <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5">
      <span class="text-xs font-medium text-slate-400">Total Sesi Mabar</span>
      <div class="text-2xl font-black font-display text-white mt-1"><?= count($sessions) ?></div>
      <a href="index.php?page=admin-sessions" class="text-[11px] text-emerald-400 font-semibold mt-2 inline-block hover:underline">Kelola Sesi →</a>
    </div>

    <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5">
      <span class="text-xs font-medium text-slate-400">Pemain Terdaftar</span>
      <div class="text-2xl font-black font-display text-emerald-400 mt-1"><?= count($players) ?></div>
      <a href="index.php?page=admin-players" class="text-[11px] text-emerald-400 font-semibold mt-2 inline-block hover:underline">Kelola Pemain →</a>
    </div>

    <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5">
      <span class="text-xs font-medium text-slate-400">Match Terekam</span>
      <div class="text-2xl font-black font-display text-white mt-1"><?= $totalMatches ?></div>
      <span class="text-[11px] text-slate-500 mt-2 inline-block font-mono">Tersinkron Database</span>
    </div>

    <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5">
      <span class="text-xs font-medium text-slate-400">Artikel / Berita</span>
      <div class="text-2xl font-black font-display text-white mt-1"><?= count($articles) ?></div>
      <a href="index.php?page=admin-articles" class="text-[11px] text-emerald-400 font-semibold mt-2 inline-block hover:underline">Editor CMS →</a>
    </div>

  </div>

  <!-- Management Modules Navigation -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
    
    <a href="index.php?page=admin-sessions" class="bg-slate-900/80 border border-white/[0.08] hover:border-emerald-500/40 rounded-2xl p-6 group transition-all">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
        <i data-lucide="calendar" class="w-5 h-5"></i>
      </div>
      <h3 class="font-bold text-base text-white font-display mb-1">Kelola Sesi & Jadwal Mabar</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Buat jadwal mabar mingguan baru, tentukan kuota pemain (slots), harga iuran, lokasi GOR, dan jumlah lapangan.
      </p>
    </a>

    <a href="index.php?page=admin-articles" class="bg-slate-900/80 border border-white/[0.08] hover:border-emerald-500/40 rounded-2xl p-6 group transition-all">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
        <i data-lucide="file-text" class="w-5 h-5"></i>
      </div>
      <h3 class="font-bold text-base text-white font-display mb-1">Editor Artikel & Pengumuman</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Terbitkan artikel CMS seputar tata tertib mabar, review shuttlecock, rekap turnamen mini, dan berita komunitas.
      </p>
    </a>

    <a href="index.php?page=admin-players" class="bg-slate-900/80 border border-white/[0.08] hover:border-emerald-500/40 rounded-2xl p-6 group transition-all">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
        <i data-lucide="users" class="w-5 h-5"></i>
      </div>
      <h3 class="font-bold text-base text-white font-display mb-1">Direktori Pemain & Reset Musim</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Lihat seluruh member komunitas, atur skill tier (Beginner/Intermediate/Advance), dan reset poin untuk musim baru.
      </p>
    </a>

  </div>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
