<?php
// views/admin/sessions.php
// CMS Session Manager
$pageTitle = "Kelola Sesi Mabar";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="flex items-center justify-between mb-8 pb-4 border-b border-white/[0.08]">
    <div>
      <a href="index.php?page=admin" class="text-xs text-emerald-400 font-semibold hover:underline">← Kembali ke Dashboard</a>
      <h1 class="text-2xl font-bold font-display text-white mt-1">Kelola Sesi & Jadwal Main Bareng</h1>
    </div>
    <button onclick="document.getElementById('new-session-form').classList.toggle('hidden')" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all shadow-md">
      + Buat Sesi Baru
    </button>
  </div>

  <?php if (!empty($success)): ?>
    <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-xs mb-6">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <!-- New Session Form (Collapsible) -->
  <div id="new-session-form" class="hidden bg-slate-900 border border-white/[0.1] rounded-2xl p-6 mb-8 shadow-xl">
    <h3 class="text-base font-bold font-display text-white mb-4 pb-2 border-b border-white/[0.06]">Formulir Tambah Sesi Mabar</h3>
    
    <form method="POST" action="index.php?page=admin-sessions" class="space-y-4">
      <input type="hidden" name="action" value="create">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Judul Sesi *</label>
          <input type="text" name="title" required placeholder="Contoh: Mabar Rutin Jumat Malam" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Hari Main *</label>
          <select name="day_of_week" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            <option value="Senin">Senin</option>
            <option value="Selasa">Selasa</option>
            <option value="Rabu">Rabu</option>
            <option value="Kamis">Kamis</option>
            <option value="Jumat">Jumat</option>
            <option value="Sabtu">Sabtu</option>
            <option value="Minggu">Minggu</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Tanggal Sesi *</label>
          <input type="date" name="session_date" required value="<?= date('Y-m-d') ?>" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Jam Mulai *</label>
          <input type="time" name="start_time" required value="19:00" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Jam Selesai *</label>
          <input type="time" name="end_time" required value="22:00" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Tempat / GOR *</label>
          <input type="text" name="venue_name" required placeholder="Contoh: GOR Bintang Arena (Court 1 & 2)" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Link Google Maps (Opsional)</label>
          <input type="url" name="venue_maps_url" placeholder="https://maps.google.com/..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Jumlah Lapangan *</label>
          <input type="number" name="court_count" value="2" min="1" max="8" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Iuran per Orang (Rp) *</label>
          <input type="number" name="fee_per_person" value="35000" step="5000" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Kuota Maksimal (Slot) *</label>
          <input type="number" name="max_slots" value="16" min="4" max="64" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Catatan Tambahan (Shuttlecock, Sepatu, dll)</label>
        <textarea name="notes" rows="2" placeholder="Ketentuan kok, iuran, info parkir..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"></textarea>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('new-session-form').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold">Simpan & Terbitkan Sesi</button>
      </div>
    </form>
  </div>

  <!-- Sessions List Table -->
  <div class="bg-slate-900/90 border border-white/[0.08] rounded-2xl p-6 shadow-xl">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="text-slate-400 uppercase tracking-wider border-b border-white/[0.06]">
          <tr>
            <th class="py-3 px-3">Tanggal & Hari</th>
            <th class="py-3 px-3">Judul Sesi</th>
            <th class="py-3 px-3">Tempat</th>
            <th class="py-3 px-3 text-center">Lap</th>
            <th class="py-3 px-3 text-center">Peserta</th>
            <th class="py-3 px-3 text-center">Biaya</th>
            <th class="py-3 px-3 text-center">Status</th>
            <th class="py-3 px-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
          <?php foreach ($sessions as $s): ?>
            <tr class="hover:bg-white/[0.02]">
              <td class="py-3 px-3">
                <div class="font-bold text-white"><?= $s['day_of_week'] ?></div>
                <div class="text-[11px] text-slate-400 font-mono"><?= date('d/m/Y', strtotime($s['session_date'])) ?></div>
              </td>
              <td class="py-3 px-3 font-semibold text-slate-200"><?= htmlspecialchars($s['title']) ?></td>
              <td class="py-3 px-3 text-slate-400"><?= htmlspecialchars($s['venue_name']) ?></td>
              <td class="py-3 px-3 text-center font-mono"><?= $s['court_count'] ?></td>
              <td class="py-3 px-3 text-center font-mono">
                <span class="text-emerald-400 font-bold"><?= $s['participant_count'] ?></span> / <?= $s['max_slots'] ?>
              </td>
              <td class="py-3 px-3 text-center font-mono">Rp <?= number_format($s['fee_per_person'], 0, ',', '.') ?></td>
              <td class="py-3 px-3 text-center">
                <form method="POST" action="index.php?page=admin-sessions" class="inline">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                  <select name="status" onchange="this.form.submit()" class="bg-slate-800 text-[11px] font-bold px-2 py-1 rounded border border-white/[0.1] focus:outline-none">
                    <option value="scheduled" <?= $s['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="in_progress" <?= $s['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $s['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $s['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                </form>
              </td>
              <td class="py-3 px-3 text-right">
                <a href="index.php?page=court&session_id=<?= $s['id'] ?>" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30 text-[11px]">
                  Court App
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
