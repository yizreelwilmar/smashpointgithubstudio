<?php
// views/layout/header.php
$currentPage = $_GET['page'] ?? 'home';
$base = 'index.php';
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' • ' : '' ?>SMASHPOINT — Komunitas & Rotasi Lapangan Mabar</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            court: {
              dark: '#090e17',
              surface: '#111827',
              card: '#161f31',
              border: 'rgba(255, 255, 255, 0.08)',
              volt: '#10e599',
              voltHover: '#059669',
              amber: '#f59e0b',
            }
          }
        }
      }
    }
  </script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Custom Tokens -->
  <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body class="bg-[#090e17] text-slate-100 min-h-screen flex flex-col antialiased selection:bg-[#10e599] selection:text-black">

  <!-- Top Announcement / Live Session Ticker -->
  <header class="hide-in-tv sticky top-0 z-40 bg-[#090e17]/90 backdrop-blur-md border-b border-white/[0.08]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        
        <!-- Brand Logo -->
        <a href="<?= $base ?>?page=home" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-black font-black text-xl shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
            ⚡
          </div>
          <div>
            <div class="font-extrabold text-lg tracking-tight font-display flex items-center gap-1.5">
              <span>SMASHPOINT</span>
              <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">PRO</span>
            </div>
            <p class="text-[11px] text-slate-400 leading-none">Court Matchmaker & Community CMS</p>
          </div>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-1">
          <a href="<?= $base ?>?page=home" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'home' ? 'text-emerald-400 bg-white/[0.05]' : 'text-slate-300 hover:text-white hover:bg-white/[0.03]' ?>">
            Beranda
          </a>
          <a href="<?= $base ?>?page=schedule" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'schedule' ? 'text-emerald-400 bg-white/[0.05]' : 'text-slate-300 hover:text-white hover:bg-white/[0.03]' ?>">
            Jadwal Mabar
          </a>
          <a href="<?= $base ?>?page=court" class="px-3.5 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 <?= $currentPage === 'court' ? 'text-emerald-400 bg-emerald-500/10 border border-emerald-500/30' : 'text-slate-200 hover:text-emerald-400 hover:bg-white/[0.03]' ?>">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            Court Rotation App
          </a>
          <a href="<?= $base ?>?page=leaderboard" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'leaderboard' ? 'text-emerald-400 bg-white/[0.05]' : 'text-slate-300 hover:text-white hover:bg-white/[0.03]' ?>">
            Leaderboard
          </a>
          <a href="<?= $base ?>?page=articles" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'articles' ? 'text-emerald-400 bg-white/[0.05]' : 'text-slate-300 hover:text-white hover:bg-white/[0.03]' ?>">
            Kabar & Tips
          </a>
        </nav>

        <!-- Right Action Button & Admin Link -->
        <div class="hidden sm:flex items-center gap-3">
          <a href="<?= $base ?>?page=court" class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-black text-sm font-bold shadow-md shadow-emerald-500/20 active:scale-95 transition-all flex items-center gap-1.5">
            <i data-lucide="play" class="w-4 h-4 fill-current"></i>
            <span>Mulai Rotasi</span>
          </a>
          <a href="<?= $base ?>?page=admin" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/[0.05] transition-colors" title="Admin CMS Panel">
            <i data-lucide="settings" class="w-5 h-5"></i>
          </a>
        </div>

        <!-- Mobile Menu Toggle Button -->
        <div class="flex md:hidden items-center gap-2">
          <a href="<?= $base ?>?page=court" class="px-3 py-1.5 rounded-lg bg-emerald-500 text-black text-xs font-bold">
            Rotasi
          </a>
          <button id="mobile-menu-btn" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/[0.05]" aria-label="Buka Menu">
            <i data-lucide="menu" class="w-6 h-6"></i>
          </button>
        </div>

      </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-white/[0.08] bg-[#090e17] px-4 pt-2 pb-4 space-y-1">
      <a href="<?= $base ?>?page=home" class="block px-3 py-2 rounded-md text-base font-medium text-slate-200 hover:bg-white/[0.05]">Beranda</a>
      <a href="<?= $base ?>?page=schedule" class="block px-3 py-2 rounded-md text-base font-medium text-slate-200 hover:bg-white/[0.05]">Jadwal Mabar</a>
      <a href="<?= $base ?>?page=court" class="block px-3 py-2 rounded-md text-base font-semibold text-emerald-400 bg-emerald-500/10">Court Rotation App (Live)</a>
      <a href="<?= $base ?>?page=leaderboard" class="block px-3 py-2 rounded-md text-base font-medium text-slate-200 hover:bg-white/[0.05]">Leaderboard</a>
      <a href="<?= $base ?>?page=articles" class="block px-3 py-2 rounded-md text-base font-medium text-slate-200 hover:bg-white/[0.05]">Kabar & Tips</a>
      <a href="<?= $base ?>?page=admin" class="block px-3 py-2 rounded-md text-base font-medium text-slate-400 hover:bg-white/[0.05]">Admin CMS Panel</a>
    </div>
  </header>

  <script>
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
      document.getElementById('mobile-menu')?.classList.toggle('hidden');
    });
  </script>

  <!-- Main Content Container -->
  <main class="flex-1">
