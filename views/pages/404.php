<?php
// views/pages/404.php
$pageTitle = "Halaman Tidak Ditemukan";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-md mx-auto px-4 py-24 text-center">
  <div class="w-16 h-16 rounded-2xl bg-white/[0.05] border border-white/[0.1] text-emerald-400 font-mono font-bold text-2xl flex items-center justify-center mx-auto mb-4">
    404
  </div>
  <h1 class="text-2xl font-bold font-display text-white mb-2">Halaman Tidak Ditemukan</h1>
  <p class="text-sm text-slate-400 mb-6">
    Halaman atau konten yang kamu cari tidak tersedia atau tautan telah dipindahkan.
  </p>
  <a href="index.php?page=home" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all">
    <i data-lucide="home" class="w-4 h-4"></i>
    <span>Kembali ke Beranda</span>
  </a>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
