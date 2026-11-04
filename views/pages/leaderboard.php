<?php
// views/pages/leaderboard.php
// Official Community Hall of Fame & Leaderboard
$pageTitle = "Papan Peringkat Resmi (Leaderboard)";
require __DIR__ . '/../layout/header.php';

$topThree = array_slice($leaderboard, 0, 3);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="max-w-3xl mb-8">
    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Papan Peringkat Resmi</span>
    <h1 class="text-3xl sm:text-4xl font-black font-display text-white mt-1">Hall of Fame Komunitas</h1>
    <p class="text-sm text-slate-300 mt-2 leading-relaxed">
      Akumulasi statistik pertandingan resmi seluruh sesi main bareng. Diperbarui otomatis secara real-time setiap kali skor pertandingan disimpan.
    </p>
  </div>

  <!-- Top 3 Podium Cards -->
  <?php if (count($topThree) >= 3): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 items-end">
      
      <!-- Rank 2 (Silver) -->
      <div class="bg-slate-900/80 border border-slate-700/60 rounded-2xl p-6 text-center shadow-xl order-2 md:order-1">
        <div class="w-12 h-12 rounded-full bg-slate-700 text-slate-200 font-black text-xl flex items-center justify-center mx-auto mb-3 shadow-md">
          🥈
        </div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Peringkat 2</span>
        <h3 class="font-extrabold text-lg text-white font-display mt-1"><?= htmlspecialchars($topThree[1]['name']) ?></h3>
        <span class="inline-block text-[10px] px-2 py-0.5 rounded font-semibold my-2 badge-<?= strtolower($topThree[1]['level']) ?>">
          <?= $topThree[1]['level'] ?>
        </span>

        <div class="grid grid-cols-3 gap-2 pt-4 border-t border-white/[0.06] text-xs">
          <div>
            <div class="font-bold text-white font-mono"><?= $topThree[1]['win_rate'] ?>%</div>
            <div class="text-[10px] text-slate-400">Win Rate</div>
          </div>
          <div>
            <div class="font-bold text-emerald-400 font-mono"><?= $topThree[1]['wins'] ?>W - <?= $topThree[1]['losses'] ?>L</div>
            <div class="text-[10px] text-slate-400">Rekor</div>
          </div>
          <div>
            <div class="font-bold font-mono <?= $topThree[1]['point_diff'] >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
              +<?= $topThree[1]['point_diff'] ?>
            </div>
            <div class="text-[10px] text-slate-400">Diff Poin</div>
          </div>
        </div>
      </div>

      <!-- Rank 1 (Gold) - Elevated -->
      <div class="bg-slate-900/90 border-2 border-emerald-500/50 rounded-3xl p-8 text-center shadow-2xl relative order-1 md:order-2 md:-translate-y-4">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-emerald-500 text-black text-[10px] font-black uppercase tracking-wider shadow-lg">
          MVP Bulan Ini
        </div>
        <div class="w-16 h-16 rounded-full bg-amber-400/20 border-2 border-amber-400/50 text-amber-300 font-black text-3xl flex items-center justify-center mx-auto mb-3 shadow-lg">
          🥇
        </div>
        <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Peringkat 1</span>
        <h3 class="font-black text-2xl text-white font-display mt-1"><?= htmlspecialchars($topThree[0]['name']) ?></h3>
        <span class="inline-block text-xs px-2.5 py-0.5 rounded font-semibold my-2 badge-<?= strtolower($topThree[0]['level']) ?>">
          <?= $topThree[0]['level'] ?>
        </span>

        <div class="grid grid-cols-3 gap-2 pt-4 border-t border-white/[0.08] text-xs">
          <div>
            <div class="font-bold text-emerald-400 font-mono text-base"><?= $topThree[0]['win_rate'] ?>%</div>
            <div class="text-[10px] text-slate-400">Win Rate</div>
          </div>
          <div>
            <div class="font-bold text-white font-mono text-base"><?= $topThree[0]['wins'] ?>W - <?= $topThree[0]['losses'] ?>L</div>
            <div class="text-[10px] text-slate-400">Rekor</div>
          </div>
          <div>
            <div class="font-bold font-mono text-base text-emerald-400">
              +<?= $topThree[0]['point_diff'] ?>
            </div>
            <div class="text-[10px] text-slate-400">Diff Poin</div>
          </div>
        </div>
      </div>

      <!-- Rank 3 (Bronze) -->
      <div class="bg-slate-900/80 border border-amber-800/40 rounded-2xl p-6 text-center shadow-xl order-3">
        <div class="w-12 h-12 rounded-full bg-amber-900/40 text-amber-500 font-black text-xl flex items-center justify-center mx-auto mb-3 shadow-md">
          🥉
        </div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-500/80">Peringkat 3</span>
        <h3 class="font-extrabold text-lg text-white font-display mt-1"><?= htmlspecialchars($topThree[2]['name']) ?></h3>
        <span class="inline-block text-[10px] px-2 py-0.5 rounded font-semibold my-2 badge-<?= strtolower($topThree[2]['level']) ?>">
          <?= $topThree[2]['level'] ?>
        </span>

        <div class="grid grid-cols-3 gap-2 pt-4 border-t border-white/[0.06] text-xs">
          <div>
            <div class="font-bold text-white font-mono"><?= $topThree[2]['win_rate'] ?>%</div>
            <div class="text-[10px] text-slate-400">Win Rate</div>
          </div>
          <div>
            <div class="font-bold text-emerald-400 font-mono"><?= $topThree[2]['wins'] ?>W - <?= $topThree[2]['losses'] ?>L</div>
            <div class="text-[10px] text-slate-400">Rekor</div>
          </div>
          <div>
            <div class="font-bold font-mono <?= $topThree[2]['point_diff'] >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
              +<?= $topThree[2]['point_diff'] ?>
            </div>
            <div class="text-[10px] text-slate-400">Diff Poin</div>
          </div>
        </div>
      </div>

    </div>
  <?php endif; ?>

  <!-- Full Table & Search Bar -->
  <div class="bg-slate-900/90 border border-white/[0.08] rounded-2xl p-6 shadow-xl">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div class="relative flex-1 max-w-sm">
        <input type="text" id="leaderboard-search" placeholder="Cari nama pemain..." onkeyup="filterLeaderboard()" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl pl-9 pr-4 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
      </div>

      <div class="flex items-center gap-1.5 text-xs text-slate-400">
        <span class="mr-1">Filter Level:</span>
        <button onclick="filterByLevel('ALL')" class="level-filter-btn px-2.5 py-1 rounded-lg bg-emerald-500 text-black font-bold">Semua</button>
        <button onclick="filterByLevel('Advance')" class="level-filter-btn px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 hover:text-white">Advance</button>
        <button onclick="filterByLevel('Intermediate')" class="level-filter-btn px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 hover:text-white">Intermediate</button>
        <button onclick="filterByLevel('Beginner')" class="level-filter-btn px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 hover:text-white">Beginner</button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left" id="leaderboard-table">
        <thead class="text-slate-400 uppercase tracking-wider border-b border-white/[0.06]">
          <tr>
            <th class="py-3 px-3">Peringkat</th>
            <th class="py-3 px-3">Nama Pemain</th>
            <th class="py-3 px-3 text-center">Skill Tier</th>
            <th class="py-3 px-3 text-center">Tanding</th>
            <th class="py-3 px-3 text-center">Menang</th>
            <th class="py-3 px-3 text-center">Kalah</th>
            <th class="py-3 px-3 text-center">Win Rate</th>
            <th class="py-3 px-3 text-center">Poin Masuk</th>
            <th class="py-3 px-3 text-center">Poin Kebobolan</th>
            <th class="py-3 px-3 text-right">Selisih (+/-)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
          <?php foreach ($leaderboard as $idx => $player): ?>
            <tr class="leaderboard-row hover:bg-white/[0.02] transition-colors" data-level="<?= $player['level'] ?>">
              <td class="py-3 px-3 font-mono font-bold text-slate-400">
                <?php if ($idx === 0): ?>
                  🥇
                <?php elseif ($idx === 1): ?>
                  🥈
                <?php elseif ($idx === 2): ?>
                  🥉
                <?php else: ?>
                  #<?= $idx + 1 ?>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3">
                <div class="font-bold text-white text-sm player-name"><?= htmlspecialchars($player['name']) ?></div>
                <?php if ($player['streak'] > 1): ?>
                  <span class="text-[10px] text-amber-400 font-semibold font-mono">🔥 <?= $player['streak'] ?> Win Streak</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 text-center">
                <span class="text-[10px] px-2 py-0.5 rounded font-semibold badge-<?= strtolower($player['level']) ?>">
                  <?= $player['level'] ?>
                </span>
              </td>
              <td class="py-3 px-3 text-center font-mono text-slate-200"><?= $player['total_matches'] ?></td>
              <td class="py-3 px-3 text-center font-mono font-bold text-emerald-400"><?= $player['wins'] ?></td>
              <td class="py-3 px-3 text-center font-mono font-bold text-red-400"><?= $player['losses'] ?></td>
              <td class="py-3 px-3 text-center font-mono font-bold text-white"><?= $player['win_rate'] ?>%</td>
              <td class="py-3 px-3 text-center font-mono text-slate-300"><?= $player['total_points_won'] ?></td>
              <td class="py-3 px-3 text-center font-mono text-slate-400"><?= $player['total_points_lost'] ?></td>
              <td class="py-3 px-3 text-right font-mono font-bold <?= $player['point_diff'] >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                <?= $player['point_diff'] > 0 ? '+' : '' ?><?= $player['point_diff'] ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

<script>
  let currentLevelFilter = 'ALL';

  function filterLeaderboard() {
    const q = document.getElementById('leaderboard-search').value.toLowerCase();
    const rows = document.querySelectorAll('.leaderboard-row');

    rows.forEach(r => {
      const name = r.querySelector('.player-name').innerText.toLowerCase();
      const level = r.getAttribute('data-level');

      const matchesSearch = name.includes(q);
      const matchesLevel = (currentLevelFilter === 'ALL' || level === currentLevelFilter);

      if (matchesSearch && matchesLevel) {
        r.style.display = '';
      } else {
        r.style.display = 'none';
      }
    });
  }

  function filterByLevel(level) {
    currentLevelFilter = level;
    document.querySelectorAll('.level-filter-btn').forEach(btn => {
      if (btn.innerText.toUpperCase() === level.toUpperCase() || (level === 'ALL' && btn.innerText === 'Semua')) {
        btn.className = 'level-filter-btn px-2.5 py-1 rounded-lg bg-emerald-500 text-black font-bold';
      } else {
        btn.className = 'level-filter-btn px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 hover:text-white';
      }
    });
    filterLeaderboard();
  }
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
