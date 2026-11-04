<?php
// views/admin/players.php
// CMS Player Directory & Season Reset
$pageTitle = "Direktori Pemain & Musim";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-white/[0.08]">
    <div>
      <a href="index.php?page=admin" class="text-xs text-emerald-400 font-semibold hover:underline">← Kembali ke Dashboard</a>
      <h1 class="text-2xl font-bold font-display text-white mt-1">Direktori Pemain & Pengaturan Musim</h1>
    </div>
    
    <div class="flex items-center gap-3">
      <button onclick="document.getElementById('new-player-form').classList.toggle('hidden')" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold shadow-md">
        + Daftarkan Pemain
      </button>
      <form method="POST" action="index.php?page=admin-players" onsubmit="return confirm('PERINGATAN: Ini akan mereset kemenangan, kekalahan, dan poin seluruh pemain menjadi 0 untuk memulai musim baru. Lanjutkan?')">
        <input type="hidden" name="action" value="reset_leaderboard">
        <button type="submit" class="px-4 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-xs font-bold">
          Reset Musim Baru
        </button>
      </form>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-xs mb-6">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <!-- New Player Form -->
  <div id="new-player-form" class="hidden bg-slate-900 border border-white/[0.1] rounded-2xl p-6 mb-8 shadow-xl">
    <h3 class="text-base font-bold font-display text-white mb-4 pb-2 border-b border-white/[0.06]">Tambah Pemain Baru</h3>
    
    <form method="POST" action="index.php?page=admin-players" class="space-y-4">
      <input type="hidden" name="action" value="create">

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap *</label>
          <input type="text" name="name" required placeholder="Nama pemain..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">No. WhatsApp</label>
          <input type="text" name="phone" placeholder="08123456789" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Skill Tier</label>
          <select name="level" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            <option value="Beginner">Beginner</option>
            <option value="Intermediate" selected>Intermediate</option>
            <option value="Advance">Advance</option>
          </select>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('new-player-form').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold">Simpan Pemain</button>
      </div>
    </form>
  </div>

  <!-- Players Table -->
  <div class="bg-slate-900/90 border border-white/[0.08] rounded-2xl p-6 shadow-xl">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="text-slate-400 uppercase tracking-wider border-b border-white/[0.06]">
          <tr>
            <th class="py-3 px-3">Nama Pemain</th>
            <th class="py-3 px-3">WhatsApp</th>
            <th class="py-3 px-3 text-center">Tier</th>
            <th class="py-3 px-3 text-center">Tanding</th>
            <th class="py-3 px-3 text-center">Menang</th>
            <th class="py-3 px-3 text-center">Kalah</th>
            <th class="py-3 px-3 text-center">Streak</th>
            <th class="py-3 px-3 text-right">Poin Won / Lost</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
          <?php foreach ($players as $p): ?>
            <tr class="hover:bg-white/[0.02]">
              <td class="py-3 px-3 font-bold text-white"><?= htmlspecialchars($p['name']) ?></td>
              <td class="py-3 px-3 font-mono text-slate-400"><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
              <td class="py-3 px-3 text-center">
                <span class="text-[10px] px-2 py-0.5 rounded font-semibold badge-<?= strtolower($p['level']) ?>">
                  <?= $p['level'] ?>
                </span>
              </td>
              <td class="py-3 px-3 text-center font-mono text-slate-200"><?= $p['total_matches'] ?></td>
              <td class="py-3 px-3 text-center font-mono font-bold text-emerald-400"><?= $p['wins'] ?></td>
              <td class="py-3 px-3 text-center font-mono font-bold text-red-400"><?= $p['losses'] ?></td>
              <td class="py-3 px-3 text-center font-mono text-amber-400">
                <?= $p['streak'] > 0 ? "🔥 {$p['streak']}W" : '-' ?>
              </td>
              <td class="py-3 px-3 text-right font-mono">
                <span class="text-emerald-400"><?= $p['total_points_won'] ?></span> / <span class="text-slate-400"><?= $p['total_points_lost'] ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
