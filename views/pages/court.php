<?php
// views/pages/court.php
// Flagship Interactive Court Rotation Application
$pageTitle = "Interactive Court Rotation App";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  
  <!-- App Control Header -->
  <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5 mb-8 backdrop-blur-md shadow-xl">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
      
      <!-- Session Selector & Info -->
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            Live Session
          </span>
          <span class="text-xs text-slate-400">Ronde Berjalan: <strong class="text-emerald-400 font-mono text-sm"><?= $latestRound ?></strong></span>
        </div>

        <form method="GET" action="index.php" class="flex flex-wrap items-center gap-3">
          <input type="hidden" name="page" value="court">
          <select name="session_id" onchange="this.form.submit()" class="bg-slate-800 text-white border border-white/[0.1] rounded-xl px-3.5 py-2 text-sm font-semibold focus:outline-none focus:border-emerald-500">
            <?php foreach ($allSessions as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($currentSession && $currentSession['id'] == $s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['title']) ?> (<?= date('d M', strtotime($s['session_date'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </form>

        <?php if ($currentSession): ?>
          <div class="text-xs text-slate-400 flex flex-wrap items-center gap-x-4 gap-y-1">
            <span>📍 <?= htmlspecialchars($currentSession['venue_name']) ?></span>
            <span>⏰ <?= substr($currentSession['start_time'], 0, 5) ?> - <?= substr($currentSession['end_time'], 0, 5) ?> WIB</span>
            <span>👥 <?= count($participants) ?> Pemain Terdaftar</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Rotation Controls -->
      <div class="flex flex-wrap items-center gap-3 hide-in-tv">
        
        <!-- Algorithm Select -->
        <div class="flex items-center bg-slate-800 border border-white/[0.1] rounded-xl px-3 py-1.5 text-xs text-slate-300">
          <span class="text-slate-400 mr-2">Mode:</span>
          <select id="algorithm-select" class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer">
            <option value="fair_social" selected>Fair Social (Rata & Anti-Repeat)</option>
            <option value="mexicano">Mexicano (Ladder / Poin Ranking)</option>
          </select>
        </div>

        <!-- Court Count Select -->
        <div class="flex items-center bg-slate-800 border border-white/[0.1] rounded-xl px-3 py-1.5 text-xs text-slate-300">
          <span class="text-slate-400 mr-2">Lap:</span>
          <select id="court-count-select" class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer">
            <option value="1" <?= ($currentSession['court_count'] ?? 2) == 1 ? 'selected' : '' ?>>1 Lapangan</option>
            <option value="2" <?= ($currentSession['court_count'] ?? 2) == 2 ? 'selected' : '' ?>>2 Lapangan</option>
            <option value="3" <?= ($currentSession['court_count'] ?? 2) == 3 ? 'selected' : '' ?>>3 Lapangan</option>
            <option value="4" <?= ($currentSession['court_count'] ?? 2) == 4 ? 'selected' : '' ?>>4 Lapangan</option>
          </select>
        </div>

        <!-- Quick Add Player Button -->
        <button onclick="SMASH.openModal('quick-player-modal')" class="px-3 py-2 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] border border-white/[0.1] text-xs font-semibold text-slate-200 hover:text-white transition-all flex items-center gap-1.5">
          <i data-lucide="user-plus" class="w-4 h-4 text-emerald-400"></i>
          <span>Tambah Pemain</span>
        </button>

        <!-- TV Mode Toggle -->
        <button onclick="SMASH.toggleTVMode()" class="px-3 py-2 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] border border-white/[0.1] text-xs font-semibold text-slate-200 hover:text-white transition-all flex items-center gap-1.5" title="Mode Layar Penuh TV GOR">
          <i data-lucide="tv" class="w-4 h-4 text-amber-400"></i>
          <span>Mode TV</span>
        </button>

        <!-- Generate Round Action -->
        <?php if ($currentSession): ?>
          <button id="btn-generate-round" onclick="SMASH.generateRound(<?= $currentSession['id'] ?>)" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-black shadow-lg shadow-emerald-500/20 active:scale-95 transition-all flex items-center gap-2">
            <i data-lucide="shuffle" class="w-4 h-4"></i>
            <span>Generate Ronde Baru</span>
          </button>
        <?php endif; ?>

      </div>

    </div>
  </div>

  <?php if (empty($activeRoundMatches)): ?>
    <!-- Empty State: No matches yet in this session -->
    <div class="bg-slate-900/50 border border-dashed border-white/[0.15] rounded-3xl p-12 text-center max-w-xl mx-auto my-12">
      <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mx-auto mb-4">
        <i data-lucide="play-circle" class="w-8 h-8"></i>
      </div>
      <h3 class="text-xl font-bold font-display text-white mb-2">Ronde Belum Dimulai</h3>
      <p class="text-sm text-slate-400 mb-6 leading-relaxed">
        Pilih jumlah lapangan dan algoritma yang diinginkan di atas, lalu klik <strong>Generate Ronde Baru</strong> untuk menyusun rotasi ganda pertama secara otomatis.
      </p>
      <?php if ($currentSession): ?>
        <button onclick="SMASH.generateRound(<?= $currentSession['id'] ?>)" class="px-6 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-extrabold text-sm shadow-xl shadow-emerald-500/20 transition-all inline-flex items-center gap-2">
          <i data-lucide="zap" class="w-4 h-4"></i>
          <span>Mulai Ronde 1 Sekarang</span>
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>

    <!-- Active Courts Grid -->
    <div class="mb-10">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <h2 class="text-lg font-bold font-display text-white flex items-center gap-2">
            <span>Lapangan Aktif — Ronde <?= $latestRound ?></span>
          </h2>
          <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 font-mono font-medium">
            <?= count($activeRoundMatches) ?> Pertandingan
          </span>
        </div>
        <div class="text-xs text-slate-400">
          * Masukkan skor & klik simpan untuk langsung memperbarui Leaderboard.
        </div>
      </div>

      <div class="grid grid-cols-1 <?= count($activeRoundMatches) > 1 ? 'lg:grid-cols-2' : '' ?> gap-6">
        <?php foreach ($activeRoundMatches as $match): ?>
          <div class="match-card bg-slate-900/90 border border-white/[0.08] hover:border-white/[0.15] rounded-2xl p-5 shadow-xl transition-all">
            
            <!-- Court Header -->
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/[0.06]">
              <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 font-black text-xs flex items-center justify-center font-mono">
                  C<?= $match['court_number'] ?>
                </span>
                <span class="font-bold text-sm text-white font-display">Lapangan <?= $match['court_number'] ?></span>
              </div>
              <div>
                <?php if ($match['status'] === 'completed'): ?>
                  <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Selesai (<?= $match['score_a'] ?> - <?= $match['score_b'] ?>)
                  </span>
                <?php else: ?>
                  <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5 animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Live Playing
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Court Visual Canvas Simulation -->
            <div class="court-canvas p-4 mb-4 rounded-xl relative">
              <div class="court-net"></div>
              <div class="court-net-label">NET</div>

              <!-- Team A (Top Half) -->
              <div class="mb-7 pb-2">
                <div class="text-[10px] font-bold tracking-wider uppercase text-emerald-300/70 mb-2 flex items-center justify-between">
                  <span>TIM A</span>
                  <?php if ($match['winner_team'] === 'team_a'): ?>
                    <span class="px-1.5 py-0.2 rounded bg-emerald-400 text-black font-black text-[9px]">WINNER</span>
                  <?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div class="bg-black/40 border border-white/[0.08] rounded-lg p-2.5 backdrop-blur-sm">
                    <div class="font-bold text-sm text-white truncate"><?= htmlspecialchars($match['p1_name']) ?></div>
                    <span class="inline-block text-[10px] px-1.5 py-0.5 rounded font-semibold mt-1 badge-<?= strtolower($match['p1_level']) ?>">
                      <?= $match['p1_level'] ?>
                    </span>
                  </div>
                  <div class="bg-black/40 border border-white/[0.08] rounded-lg p-2.5 backdrop-blur-sm">
                    <div class="font-bold text-sm text-white truncate"><?= htmlspecialchars($match['p2_name']) ?></div>
                    <span class="inline-block text-[10px] px-1.5 py-0.5 rounded font-semibold mt-1 badge-<?= strtolower($match['p2_level']) ?>">
                      <?= $match['p2_level'] ?>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Team B (Bottom Half) -->
              <div class="mt-7 pt-2">
                <div class="text-[10px] font-bold tracking-wider uppercase text-emerald-300/70 mb-2 flex items-center justify-between">
                  <span>TIM B</span>
                  <?php if ($match['winner_team'] === 'team_b'): ?>
                    <span class="px-1.5 py-0.2 rounded bg-emerald-400 text-black font-black text-[9px]">WINNER</span>
                  <?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div class="bg-black/40 border border-white/[0.08] rounded-lg p-2.5 backdrop-blur-sm">
                    <div class="font-bold text-sm text-white truncate"><?= htmlspecialchars($match['p3_name']) ?></div>
                    <span class="inline-block text-[10px] px-1.5 py-0.5 rounded font-semibold mt-1 badge-<?= strtolower($match['p3_level']) ?>">
                      <?= $match['p3_level'] ?>
                    </span>
                  </div>
                  <div class="bg-black/40 border border-white/[0.08] rounded-lg p-2.5 backdrop-blur-sm">
                    <div class="font-bold text-sm text-white truncate"><?= htmlspecialchars($match['p4_name']) ?></div>
                    <span class="inline-block text-[10px] px-1.5 py-0.5 rounded font-semibold mt-1 badge-<?= strtolower($match['p4_level']) ?>">
                      <?= $match['p4_level'] ?>
                    </span>
                  </div>
                </div>
              </div>

            </div>

            <!-- Scoreboard Input Controls -->
            <div class="bg-slate-950/60 border border-white/[0.06] rounded-xl p-3.5 flex flex-col sm:flex-row items-center justify-between gap-4">
              
              <div class="flex items-center gap-4">
                
                <!-- Score Team A -->
                <div class="text-center">
                  <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Skor Tim A</span>
                  <div class="flex items-center gap-1.5">
                    <button type="button" onclick="SMASH.adjustScore('input[name=score_a_<?= $match['id'] ?>]', -1)" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm">-</button>
                    <input type="number" name="score_a_<?= $match['id'] ?>" value="<?= $match['score_a'] ?>" min="0" max="99" class="w-14 h-9 bg-slate-900 border border-white/[0.15] rounded-lg text-center font-mono font-bold text-lg text-white focus:outline-none focus:border-emerald-500">
                    <button type="button" onclick="SMASH.adjustScore('input[name=score_a_<?= $match['id'] ?>]', 1)" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm">+</button>
                  </div>
                </div>

                <div class="font-mono font-bold text-xl text-slate-500 pt-4">:</div>

                <!-- Score Team B -->
                <div class="text-center">
                  <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Skor Tim B</span>
                  <div class="flex items-center gap-1.5">
                    <button type="button" onclick="SMASH.adjustScore('input[name=score_b_<?= $match['id'] ?>]', -1)" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm">-</button>
                    <input type="number" name="score_b_<?= $match['id'] ?>" value="<?= $match['score_b'] ?>" min="0" max="99" class="w-14 h-9 bg-slate-900 border border-white/[0.15] rounded-lg text-center font-mono font-bold text-lg text-white focus:outline-none focus:border-emerald-500">
                    <button type="button" onclick="SMASH.adjustScore('input[name=score_b_<?= $match['id'] ?>]', 1)" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm">+</button>
                  </div>
                </div>

              </div>

              <!-- Save Score Action -->
              <button onclick="SMASH.saveMatchScore(this, <?= $match['id'] ?>)" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all shadow-md shadow-emerald-500/20 active:scale-95 flex items-center justify-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Simpan Skor</span>
              </button>

            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Resting Players Bench & Session Roster -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-12">
      
      <!-- Resting Players Bench Card -->
      <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5 shadow-lg">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-white/[0.06]">
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
            <h3 class="font-bold text-sm text-white font-display">Bangku Istirahat (Bench / Bye)</h3>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 font-mono font-bold">
            <?= count($restingPlayerIds) ?> Orang
          </span>
        </div>

        <?php if (empty($restingPlayerIds)): ?>
          <p class="text-xs text-slate-400 py-4 text-center">Semua pemain yang hadir saat ini sedang bermain di lapangan.</p>
        <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($restingPlayerIds as $rp): ?>
              <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-800/60 border border-white/[0.04]">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-amber-500/20 text-amber-300 font-bold text-xs flex items-center justify-center">
                    ☕
                  </div>
                  <div>
                    <div class="font-bold text-xs text-slate-200"><?= htmlspecialchars($rp['name']) ?></div>
                    <span class="text-[10px] text-slate-400">Pemain istirahat di ronde ini</span>
                  </div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/30">
                  Antre Ronde <?= $latestRound + 1 ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Session Player Attendance Roster -->
      <div class="lg:col-span-2 bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5 shadow-lg">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-white/[0.06]">
          <div class="flex items-center gap-2">
            <i data-lucide="users" class="w-4 h-4 text-emerald-400"></i>
            <h3 class="font-bold text-sm text-white font-display">Daftar Kehadiran Sesi (<?= count($participants) ?> Pemain)</h3>
          </div>
          <span class="text-xs text-slate-400">Klik status untuk izin / istirahat</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto custom-scrollbar pr-1">
          <?php foreach ($participants as $part): ?>
            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-800/40 border border-white/[0.04] text-xs">
              <div class="flex items-center gap-2 truncate">
                <span class="w-2 h-2 rounded-full <?= $part['is_present'] == 1 ? 'bg-emerald-400' : 'bg-slate-500' ?>"></span>
                <span class="font-medium text-slate-200 truncate"><?= htmlspecialchars($part['name']) ?></span>
                <span class="text-[9px] px-1.5 py-0.2 rounded font-semibold badge-<?= strtolower($part['level']) ?>"><?= $part['level'] ?></span>
              </div>
              <button onclick="SMASH.toggleAttendance(<?= $currentSession['id'] ?>, <?= $part['player_id'] ?>)" class="px-2 py-1 rounded text-[10px] font-bold uppercase transition-all <?= $part['is_present'] == 1 ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' : 'bg-red-500/10 text-red-400 hover:bg-red-500/20' ?>">
                <?= $part['is_present'] == 1 ? 'Hadir' : 'Izin' ?>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- Match Recap of Previous Rounds in this Session -->
    <div class="bg-slate-900/80 border border-white/[0.08] rounded-2xl p-5 shadow-lg">
      <h3 class="font-bold text-sm text-white font-display mb-4 pb-2 border-b border-white/[0.06] flex items-center gap-2">
        <i data-lucide="history" class="w-4 h-4 text-slate-400"></i>
        <span>Riwayat Pertandingan Sesi Ini</span>
      </h3>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="text-slate-400 uppercase tracking-wider border-b border-white/[0.06]">
            <tr>
              <th class="py-2.5 px-3">Ronde</th>
              <th class="py-2.5 px-3">Lap</th>
              <th class="py-2.5 px-3">Pasangan Tim A</th>
              <th class="py-2.5 px-3 text-center">Skor</th>
              <th class="py-2.5 px-3">Pasangan Tim B</th>
              <th class="py-2.5 px-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/[0.04]">
            <?php foreach (array_reverse($matches) as $pm): ?>
              <tr class="hover:bg-white/[0.02]">
                <td class="py-2.5 px-3 font-mono font-bold text-emerald-400">R<?= $pm['round_number'] ?></td>
                <td class="py-2.5 px-3 font-semibold text-slate-300">C<?= $pm['court_number'] ?></td>
                <td class="py-2.5 px-3 <?= $pm['winner_team'] === 'team_a' ? 'font-bold text-emerald-400' : 'text-slate-200' ?>">
                  <?= htmlspecialchars($pm['p1_name']) ?> & <?= htmlspecialchars($pm['p2_name']) ?>
                </td>
                <td class="py-2.5 px-3 text-center font-mono font-bold text-sm">
                  <span class="<?= $pm['winner_team'] === 'team_a' ? 'text-emerald-400' : 'text-slate-300' ?>"><?= $pm['score_a'] ?></span>
                  <span class="text-slate-500 mx-1">-</span>
                  <span class="<?= $pm['winner_team'] === 'team_b' ? 'text-emerald-400' : 'text-slate-300' ?>"><?= $pm['score_b'] ?></span>
                </td>
                <td class="py-2.5 px-3 <?= $pm['winner_team'] === 'team_b' ? 'font-bold text-emerald-400' : 'text-slate-200' ?>">
                  <?= htmlspecialchars($pm['p3_name']) ?> & <?= htmlspecialchars($pm['p4_name']) ?>
                </td>
                <td class="py-2.5 px-3">
                  <?php if ($pm['status'] === 'completed'): ?>
                    <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-slate-800 text-slate-300">Selesai</span>
                  <?php else: ?>
                    <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-emerald-500/20 text-emerald-400 animate-pulse">Playing</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>

</div>

<!-- Quick Add Player Modal -->
<div id="quick-player-modal" class="modal-overlay hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm items-center justify-center p-4">
  <div class="bg-slate-900 border border-white/[0.1] rounded-2xl max-w-md w-full p-6 shadow-2xl">
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/[0.08]">
      <h4 class="font-bold text-base text-white font-display">Tambah Pemain ke Sesi</h4>
      <button onclick="SMASH.closeModal('quick-player-modal')" class="text-slate-400 hover:text-white">&times;</button>
    </div>
    
    <form onsubmit="SMASH.submitQuickAddPlayer(event, this)" class="space-y-4">
      <input type="hidden" name="session_id" value="<?= $currentSession['id'] ?? 1 ?>">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Pemain *</label>
        <input type="text" name="name" required placeholder="Contoh: Ricky Subagja" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Skill Level</label>
          <select name="level" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
            <option value="Beginner">Beginner</option>
            <option value="Intermediate" selected>Intermediate</option>
            <option value="Advance">Advance</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">No. WhatsApp</label>
          <input type="text" name="phone" placeholder="0812..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-white/[0.08]">
        <button type="button" onclick="SMASH.closeModal('quick-player-modal')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all shadow-md">
          Tambahkan Sekarang
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
