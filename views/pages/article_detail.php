<?php
// views/pages/article_detail.php
// Editorial Article Reader
$pageTitle = $article['title'];
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  
  <div class="mb-6">
    <a href="index.php?page=articles" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 hover:text-emerald-300">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>
      <span>Kembali ke Berita & Tips</span>
    </a>
  </div>

  <article class="bg-slate-900/90 border border-white/[0.08] rounded-3xl p-6 sm:p-10 shadow-2xl">
    
    <div class="flex items-center gap-2 mb-4">
      <span class="text-xs font-bold uppercase px-2.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
        <?= htmlspecialchars($article['category']) ?>
      </span>
      <span class="text-xs text-slate-400">•</span>
      <span class="text-xs text-slate-400"><?= date('d F Y', strtotime($article['created_at'])) ?></span>
      <span class="text-xs text-slate-400">•</span>
      <span class="text-xs text-slate-400 font-mono"><?= $article['views_count'] ?> views</span>
    </div>

    <h1 class="text-3xl sm:text-4xl font-black font-display text-white leading-tight mb-6">
      <?= htmlspecialchars($article['title']) ?>
    </h1>

    <div class="p-4 rounded-2xl bg-white/[0.03] border-l-4 border-emerald-500 text-sm text-slate-200 italic mb-8">
      <?= htmlspecialchars($article['excerpt']) ?>
    </div>

    <?php if (!empty($article['cover_image'])): ?>
      <div class="rounded-2xl overflow-hidden mb-8 max-h-96 w-full bg-slate-800">
        <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="w-full h-full object-cover">
      </div>
    <?php endif; ?>

    <div class="prose prose-invert max-w-none text-slate-300 text-sm sm:text-base leading-relaxed space-y-4">
      <?= nl2br(htmlspecialchars($article['content'])) ?>
    </div>

    <div class="mt-12 pt-6 border-t border-white/[0.08] flex items-center justify-between text-xs text-slate-400">
      <span>Diterbitkan oleh Admin Komunitas SMASHPOINT</span>
      <a href="index.php?page=schedule" class="text-emerald-400 font-bold hover:underline">
        Lihat Jadwal Mabar Terdekat →
      </a>
    </div>

  </article>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
