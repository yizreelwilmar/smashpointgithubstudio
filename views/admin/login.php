<?php
// views/admin/login.php
$pageTitle = "Login Admin CMS";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-md mx-auto px-4 py-16">
  <div class="bg-slate-900/90 border border-white/[0.08] rounded-3xl p-8 shadow-2xl">
    
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center mx-auto mb-3">
        <i data-lucide="shield" class="w-6 h-6"></i>
      </div>
      <h1 class="text-2xl font-bold font-display text-white">Login Admin CMS</h1>
      <p class="text-xs text-slate-400 mt-1">Kelola sesi mabar, artikel pengumuman, dan data pemain.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="p-3.5 rounded-xl bg-red-950/80 border border-red-500/40 text-red-300 text-xs mb-5">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=admin-login" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
        <input type="text" name="username" required autocomplete="username" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500 font-mono">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
        <input type="password" name="password" required autocomplete="current-password" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500 font-mono">
      </div>

      <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
        Masuk ke Admin Panel
      </button>
    </form>

  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
