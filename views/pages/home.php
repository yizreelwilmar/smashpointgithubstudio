<?php
// views/pages/home.php
// Athletic Public Landing Page & Community CMS Showcase
$pageTitle = "Komunitas Mabar & Rotasi Lapangan Cerdas";
require __DIR__ . '/../layout/header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden pt-12 pb-20 border-b border-white/[0.06]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
    <div class="max-w-3xl">
      
      <!-- Live Session Status Pill -->
      <?php if (!empty($upcomingSessions)): ?>
        <?php $nextSess = $upcomingSessions[0]; ?>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold mb-6">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Sesi Berikutnya: <?= $nextSess['day_of_week'] ?>, <?= date('d M', strtotime($nextSess['session_date'])) ?> • <?= substr($nextSess['start_time'], 0, 5) ?> WIB (<?= $nextSess['participant_count'] ?>/<?= $nextSess['max_slots'] ?> Slot)</span>
        </div>
      <?php endif; ?>

      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black font-display tracking-tight text-white leading-[1.08] mb-6">
        Main Bareng Lebih Adil.<br>
        <span class="text-emerald-400">Rotasi Lapangan Cerdas &</span> Papan Peringkat Nyata.
      </h1>

      <p class="text-base sm:text-lg text-slate-300 leading-relaxed mb-8 max-w-2xl">
        Ucapkan selamat tinggal pada kocokan manual yang berat sebelah. SMASHPOINT menyusun rotasi ganda secara berimbang, membagi waktu istirahat secara rotatif, dan otomatis mencatat rekor kemenangan di leaderboard komunitas.
      </p>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-4">
        <a href="index.php?page=schedule" class="px-6 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-bold text-sm shadow-xl shadow-emerald-500/20 active:scale-95 transition-all flex items-center gap-2">
          <i data-lucide="calendar" class="w-4 h-4"></i>
          <span>Lihat Jadwal & Daftar Mabar</span>
        </a>
        <a href="index.php?page=court" class="px-6 py-3.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-white border border-white/[0.1] font-semibold text-sm active:scale-95 transition-all flex items-center gap-2">
          <i data-lucide="play" class="w-4 h-4 text-emerald-400"></i>
          <span>Buka Court Board App</span>
        </a>
      </div>

      <!-- Quick Metrics Strip -->
      <div class="grid grid-cols-3 gap-6 mt-14 pt-8 border-t border-white/[0.08] max-w-lg">
        <div>
          <div class="font-display font-black text-2xl text-white"><?= $stats['players'] ?>+</div>
          <div class="text-xs text-slate-400 mt-0.5">Pemain Aktif</div>
        </div>
        <div>
          <div class="font-display font-black text-2xl text-emerald-400"><?= $stats['matches'] ?></div>
          <div class="text-xs text-slate-400 mt-0.5">Match Terekam</div>
        </div>
        <div>
          <div class="font-display font-black text-2xl text-white"><?= $stats['sessions'] ?></div>
          <div class="text-xs text-slate-400 mt-0.5">Sesi Mabar</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Upcoming Mabar Schedule Section -->
<section class="py-16 border-b border-white/[0.06] bg-slate-950/40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10 gap-4">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Jadwal Komunitas</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold font-display text-white mt-1">Sesi Main Bareng Mingguan</h2>
        <p class="text-sm text-slate-400 mt-1">Slot terbatas setiap minggunya demi kenyamanan rotasi bermain.</p>
      </div>
      <a href="index.php?page=schedule" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
        <span>Semua Jadwal & Riwayat</span>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
      </a>
    </div>

    <!-- Sessions Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($upcomingSessions as $sess): ?>
        <?php 
          $quotaPct = min(100, round(($sess['participant_count'] / $sess['max_slots']) * 100));
          $isFull = $sess['participant_count'] >= $sess['max_slots'];
        ?>
        <div class="bg-slate-900/80 border border-white/[0.08] hover:border-emerald-500/30 rounded-2xl p-6 transition-all shadow-lg flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-4">
              <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white/[0.06] text-white">
                <?= $sess['day_of_week'] ?>, <?= date('d M Y', strtotime($sess['session_date'])) ?>
              </span>
              <span class="text-xs font-mono font-bold text-emerald-400">
                Rp <?= number_format($sess['fee_per_person'], 0, ',', '.') ?>
              </span>
            </div>

            <h3 class="font-bold text-base text-white font-display mb-2 leading-snug">
              <?= htmlspecialchars($sess['title']) ?>
            </h3>

            <div class="space-y-1.5 text-xs text-slate-400 mb-6">
              <div class="flex items-center gap-2">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                <span><?= substr($sess['start_time'], 0, 5) ?> - <?= substr($sess['end_time'], 0, 5) ?> WIB</span>
              </div>
              <div class="flex items-center gap-2">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                <span class="truncate"><?= htmlspecialchars($sess['venue_name']) ?></span>
              </div>
              <div class="flex items-center gap-2">
                <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-slate-400"></i>
                <span><?= $sess['court_count'] ?> Lapangan Karpet</span>
              </div>
            </div>
          </div>

          <div>
            <!-- Slot Progress Bar -->
            <div class="mb-4">
              <div class="flex items-center justify-between text-xs mb-1.5 font-medium">
                <span class="text-slate-400">Ketersediaan Slot</span>
                <span class="<?= $isFull ? 'text-red-400 font-bold' : 'text-slate-200' ?>">
                  <?= $sess['participant_count'] ?> / <?= $sess['max_slots'] ?> Terisi
                </span>
              </div>
              <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                <div class="h-full rounded-full <?= $isFull ? 'bg-red-500' : 'bg-emerald-500' ?>" style="width: <?= $quotaPct ?>%"></div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <?php if ($isFull): ?>
                <button disabled class="w-full py-2.5 rounded-xl bg-slate-800 text-slate-500 text-xs font-bold cursor-not-allowed text-center">
                  Slot Penuh
                </button>
              <?php else: ?>
                <button onclick="SMASH.openModal('rsvp-modal', <?= $sess['id'] ?>)" class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold shadow-md shadow-emerald-500/20 active:scale-95 transition-all text-center">
                  Daftar Slot Sekarang
                </button>
              <?php endif; ?>
              <a href="index.php?page=court&session_id=<?= $sess['id'] ?>" class="p-2.5 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] text-slate-300 hover:text-white border border-white/[0.08]" title="Buka Court Board">
                <i data-lucide="external-link" class="w-4 h-4"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Hall of Fame & Top Leaderboard Section -->
<section class="py-16 border-b border-white/[0.06]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
      
      <!-- Left Editorial Pitch -->
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Papan Peringkat Resmi</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold font-display text-white mt-1">Hall of Fame Komunitas</h2>
        <p class="text-sm text-slate-300 mt-3 leading-relaxed">
          Setiap poin kemenangan dan selisih skor dihitung secara matematis. Pemain dengan konsistensi tertinggi akan menduduki podium mingguan.
        </p>
        
        <div class="mt-6 p-4 rounded-xl bg-slate-900/60 border border-white/[0.08] space-y-2 text-xs text-slate-400">
          <div class="flex items-center gap-2 text-slate-200 font-semibold">
            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
            <span>Sistem Hitung Adil</span>
          </div>
          <p>Kemenangan dihitung dari rasio kemenangan (win rate), total menang, dan selisih poin (+/- differential).</p>
        </div>

        <a href="index.php?page=leaderboard" class="inline-flex items-center gap-2 mt-6 px-4 py-2.5 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] border border-white/[0.1] text-xs font-bold text-white transition-all">
          <span>Lihat Klasemen Lengkap (100 Pemain)</span>
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
      </div>

      <!-- Right Leaderboard Preview Table -->
      <div class="lg:col-span-2 bg-slate-900/80 border border-white/[0.08] rounded-2xl p-6 shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="text-slate-400 uppercase tracking-wider border-b border-white/[0.06]">
              <tr>
                <th class="py-3 px-3">Peringkat</th>
                <th class="py-3 px-3">Pemain</th>
                <th class="py-3 px-3 text-center">Level</th>
                <th class="py-3 px-3 text-center">Main</th>
                <th class="py-3 px-3 text-center">M / K</th>
                <th class="py-3 px-3 text-center">Win Rate</th>
                <th class="py-3 px-3 text-right">Diff Poin</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
              <?php foreach ($topPlayers as $idx => $player): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                  <td class="py-3 px-3">
                    <?php if ($idx === 0): ?>
                      <span class="w-6 h-6 rounded-full bg-amber-400/20 text-amber-300 font-bold flex items-center justify-center font-mono">🥇</span>
                    <?php elseif ($idx === 1): ?>
                      <span class="w-6 h-6 rounded-full bg-slate-400/20 text-slate-300 font-bold flex items-center justify-center font-mono">🥈</span>
                    <?php elseif ($idx === 2): ?>
                      <span class="w-6 h-6 rounded-full bg-amber-700/20 text-amber-500 font-bold flex items-center justify-center font-mono">🥉</span>
                    <?php else: ?>
                      <span class="font-mono text-slate-400 font-bold pl-2">#<?= $idx + 1 ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-bold text-white text-sm"><?= htmlspecialchars($player['name']) ?></div>
                    <?php if ($player['streak'] > 1): ?>
                      <span class="text-[10px] text-amber-400 font-semibold font-mono">🔥 <?= $player['streak'] ?> Win Streak</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span class="text-[10px] px-2 py-0.5 rounded font-semibold badge-<?= strtolower($player['level']) ?>">
                      <?= $player['level'] ?>
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center font-mono text-slate-300"><?= $player['total_matches'] ?></td>
                  <td class="py-3 px-3 text-center font-mono font-bold">
                    <span class="text-emerald-400"><?= $player['wins'] ?></span>
                    <span class="text-slate-500">/</span>
                    <span class="text-red-400"><?= $player['losses'] ?></span>
                  </td>
                  <td class="py-3 px-3 text-center font-mono font-bold text-white">
                    <?= $player['win_rate'] ?>%
                  </td>
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
  </div>
</section>

<!-- Latest Articles & Guidelines Section -->
<section class="py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">CMS & Publikasi</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold font-display text-white mt-1">Kabar Komunitas & Tips Lapangan</h2>
      </div>
      <a href="index.php?page=articles" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
        <span>Semua Artikel</span>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($latestArticles as $art): ?>
        <article class="bg-slate-900/80 border border-white/[0.08] rounded-2xl overflow-hidden hover:border-white/[0.2] transition-all flex flex-col justify-between group">
          <?php if (!empty($art['cover_image'])): ?>
            <div class="h-44 w-full overflow-hidden bg-slate-800">
              <img src="<?= htmlspecialchars($art['cover_image']) ?>" alt="<?= htmlspecialchars($art['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            </div>
          <?php endif; ?>
          <div class="p-6 flex-1 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-white/[0.06] text-emerald-400">
                  <?= htmlspecialchars($art['category']) ?>
                </span>
                <span class="text-[11px] text-slate-500"><?= date('d M Y', strtotime($art['created_at'])) ?></span>
              </div>
              <h3 class="font-bold text-base text-white font-display leading-snug mb-2 group-hover:text-emerald-400 transition-colors">
                <a href="index.php?page=article&slug=<?= $art['slug'] ?>">
                  <?= htmlspecialchars($art['title']) ?>
                </a>
              </h3>
              <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-4">
                <?= htmlspecialchars($art['excerpt']) ?>
              </p>
            </div>
            <a href="index.php?page=article&slug=<?= $art['slug'] ?>" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
              <span>Baca Selengkapnya</span>
              <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- RSVP Modal -->
<div id="rsvp-modal" class="modal-overlay hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm items-center justify-center p-4">
  <div class="bg-slate-900 border border-white/[0.1] rounded-2xl max-w-md w-full p-6 shadow-2xl">
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/[0.08]">
      <h4 class="font-bold text-base text-white font-display">Daftar Slot Main Bareng</h4>
      <button onclick="SMASH.closeModal('rsvp-modal')" class="text-slate-400 hover:text-white">&times;</button>
    </div>
    
    <form onsubmit="SMASH.submitRSVP(event, this)" class="space-y-4">
      <input type="hidden" name="session_id" value="">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap / Panggilan *</label>
        <input type="text" name="name" required placeholder="Contoh: Hendra Setiawan" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Nomor WhatsApp Aktif *</label>
        <input type="text" name="phone" required placeholder="08123456789" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Tingkat Kemahiran (Skill Tier)</label>
        <select name="level" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
          <option value="Beginner">Beginner (Dasar / Pemula)</option>
          <option value="Intermediate" selected>Intermediate (Bisa rally & rotasi ganda)</option>
          <option value="Advance">Advance (Eks atlet / smash tajam & kompetitif)</option>
        </select>
      </div>

      <div class="p-3 rounded-xl bg-slate-800/60 border border-white/[0.06] text-[11px] text-slate-400">
        Pembayaran iuran kok & sewa lapangan dilakukan saat di lokasi GOR kepada admin / bendahara komunitas.
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-white/[0.08]">
        <button type="button" onclick="SMASH.closeModal('rsvp-modal')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all shadow-md">
          Konfirmasi Pendaftaran
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
