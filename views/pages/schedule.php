<?php
// views/pages/schedule.php
// Public Weekly Schedule & Mabar RSVP Hub
$pageTitle = "Jadwal Main Bareng Mingguan";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="max-w-3xl mb-10">
    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Jadwal Sesi Terbuka</span>
    <h1 class="text-3xl sm:text-4xl font-black font-display text-white mt-1">Jadwal Main Bareng Mingguan</h1>
    <p class="text-sm text-slate-300 mt-2 leading-relaxed">
      Sesi mabar terbuka untuk seluruh member komunitas. Amankan slot sebelum kuota habis demi menjaga rasio pemain dan waktu bermain tetap optimal.
    </p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($upcomingSessions as $sess): ?>
      <?php 
        $quotaPct = min(100, round(($sess['participant_count'] / $sess['max_slots']) * 100));
        $isFull = $sess['participant_count'] >= $sess['max_slots'];
      ?>
      <div class="bg-slate-900/90 border border-white/[0.08] hover:border-emerald-500/30 rounded-2xl p-6 shadow-xl flex flex-col justify-between transition-all">
        <div>
          <div class="flex items-center justify-between mb-4">
            <span class="px-3 py-1 rounded-lg text-xs font-bold bg-white/[0.06] text-white">
              <?= $sess['day_of_week'] ?>, <?= date('d M Y', strtotime($sess['session_date'])) ?>
            </span>
            <span class="text-xs font-mono font-bold text-emerald-400">
              Rp <?= number_format($sess['fee_per_person'], 0, ',', '.') ?>
            </span>
          </div>

          <h2 class="font-bold text-lg text-white font-display mb-3">
            <?= htmlspecialchars($sess['title']) ?>
          </h2>

          <div class="space-y-2 text-xs text-slate-400 mb-6">
            <div class="flex items-center gap-2">
              <i data-lucide="clock" class="w-4 h-4 text-emerald-400"></i>
              <span><?= substr($sess['start_time'], 0, 5) ?> - <?= substr($sess['end_time'], 0, 5) ?> WIB</span>
            </div>
            <div class="flex items-center gap-2">
              <i data-lucide="map-pin" class="w-4 h-4 text-emerald-400"></i>
              <span class="truncate"><?= htmlspecialchars($sess['venue_name']) ?></span>
            </div>
            <div class="flex items-center gap-2">
              <i data-lucide="layout-grid" class="w-4 h-4 text-emerald-400"></i>
              <span><?= $sess['court_count'] ?> Lapangan Karpet</span>
            </div>
          </div>

          <?php if (!empty($sess['notes'])): ?>
            <div class="p-3 rounded-xl bg-slate-800/40 border border-white/[0.04] text-xs text-slate-300 mb-6">
              <?= nl2br(htmlspecialchars($sess['notes'])) ?>
            </div>
          <?php endif; ?>
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
            <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
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
            <a href="index.php?page=court&session_id=<?= $sess['id'] ?>" class="p-2.5 rounded-xl bg-white/[0.05] hover:bg-white/[0.1] text-slate-300 hover:text-white border border-white/[0.08]" title="Buka Court Board Sesi Ini">
              <i data-lucide="play" class="w-4 h-4"></i>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

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
