<?php
// views/admin/articles.php
// CMS Article & Announcement Editor
$pageTitle = "Editor Artikel CMS";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="flex items-center justify-between mb-8 pb-4 border-b border-white/[0.08]">
    <div>
      <a href="index.php?page=admin" class="text-xs text-emerald-400 font-semibold hover:underline">← Kembali ke Dashboard</a>
      <h1 class="text-2xl font-bold font-display text-white mt-1">Editor Artikel & Pengumuman Komunitas</h1>
    </div>
    <button onclick="document.getElementById('new-article-form').classList.toggle('hidden')" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold transition-all shadow-md">
      + Tulis Artikel Baru
    </button>
  </div>

  <?php if (!empty($success)): ?>
    <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-xs mb-6">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <!-- New Article Form -->
  <div id="new-article-form" class="hidden bg-slate-900 border border-white/[0.1] rounded-2xl p-6 mb-8 shadow-xl">
    <h3 class="text-base font-bold font-display text-white mb-4 pb-2 border-b border-white/[0.06]">Formulir Artikel Baru</h3>
    
    <form method="POST" action="index.php?page=admin-articles" class="space-y-4">
      <input type="hidden" name="action" value="create">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-300 mb-1">Judul Artikel *</label>
          <input type="text" name="title" required placeholder="Judul artikel atau pengumuman..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Kategori *</label>
          <select name="category" class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            <option value="Pengumuman">Pengumuman</option>
            <option value="Sistem & Aturan">Sistem & Aturan</option>
            <option value="Turnamen">Turnamen</option>
            <option value="Tips & Teknik">Tips & Teknik</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Ringkasan / Excerpt *</label>
        <textarea name="excerpt" rows="2" required placeholder="Ringkasan singkat yang muncul di kartu artikel..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"></textarea>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Isi Konten Lengkap *</label>
        <textarea name="content" rows="6" required placeholder="Tulis isi artikel secara detail..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"></textarea>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">URL Cover Gambar (Opsional)</label>
        <input type="url" name="cover_image" placeholder="https://images.unsplash.com/..." class="w-full bg-slate-800 border border-white/[0.1] rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" name="is_published" id="is_published" value="1" checked class="rounded bg-slate-800 border-white/[0.2] text-emerald-500 focus:ring-0">
        <label for="is_published" class="text-xs text-slate-300">Langsung publikasikan ke Landing Page</label>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('new-article-form').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold">Terbitkan Artikel</button>
      </div>
    </form>
  </div>

  <!-- Articles List -->
  <div class="bg-slate-900/90 border border-white/[0.08] rounded-2xl p-6 shadow-xl">
    <div class="divide-y divide-white/[0.06]">
      <?php foreach ($articles as $art): ?>
        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/[0.06] text-emerald-400 uppercase">
                <?= htmlspecialchars($art['category']) ?>
              </span>
              <span class="text-[11px] text-slate-500"><?= date('d M Y', strtotime($art['created_at'])) ?></span>
              <span class="text-[11px] text-slate-500 font-mono">• <?= $art['views_count'] ?> views</span>
            </div>
            <h4 class="font-bold text-sm text-white"><?= htmlspecialchars($art['title']) ?></h4>
            <p class="text-xs text-slate-400 line-clamp-1"><?= htmlspecialchars($art['excerpt']) ?></p>
          </div>

          <div class="flex items-center gap-2">
            <a href="index.php?page=article&slug=<?= $art['slug'] ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-white/[0.05] hover:bg-white/[0.1] text-xs font-semibold text-slate-300 hover:text-white">
              Lihat
            </a>
            <form method="POST" action="index.php?page=admin-articles" onsubmit="return confirm('Hapus artikel ini?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $art['id'] ?>">
              <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-xs font-semibold text-red-400">
                Hapus
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
