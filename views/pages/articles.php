<?php
// views/pages/articles.php
// CMS Articles & Community News Hub
$pageTitle = "Kabar Komunitas & Panduan";
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  
  <div class="max-w-3xl mb-10">
    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">CMS & Publikasi</span>
    <h1 class="text-3xl sm:text-4xl font-black font-display text-white mt-1">Kabar Komunitas & Panduan</h1>
    <p class="text-sm text-slate-300 mt-2 leading-relaxed">
      Pengumuman resmi jadwal mabar, ketentuan shuttlecock, etika lapangan, dan artikel panduan teknis permainan.
    </p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($articles as $art): ?>
      <article class="bg-slate-900/80 border border-white/[0.08] rounded-2xl overflow-hidden hover:border-white/[0.2] transition-all flex flex-col justify-between group">
        <?php if (!empty($art['cover_image'])): ?>
          <div class="h-48 w-full overflow-hidden bg-slate-800">
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
            <h2 class="font-bold text-base text-white font-display leading-snug mb-2 group-hover:text-emerald-400 transition-colors">
              <a href="index.php?page=article&slug=<?= $art['slug'] ?>">
                <?= htmlspecialchars($art['title']) ?>
              </a>
            </h2>
            <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-4">
              <?= htmlspecialchars($art['excerpt']) ?>
            </p>
          </div>
          <div class="flex items-center justify-between pt-4 border-t border-white/[0.06] text-xs">
            <span class="text-slate-500 font-mono text-[11px]"><?= $art['views_count'] ?> views</span>
            <a href="index.php?page=article&slug=<?= $art['slug'] ?>" class="font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
              <span>Baca Artikel</span>
              <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
