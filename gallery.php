<?php
// gallery.php
// Powershoot Badminton — Dedicated Public Photo Gallery & Documentation Archive
// Modernized UI/UX with smooth branch filtering, accurate photo metrics & sleek lightbox

declare(strict_types=1);

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/models/Community.php';
require_once __DIR__ . '/models/Schedule.php';

// Seamless 301 Redirect if raw integer session_id or id is requested (strictly hide numeric IDs from public URLs)
$rawNumId = 0;
if (isset($_GET['session_id']) && is_numeric($_GET['session_id'])) {
    $rawNumId = (int)$_GET['session_id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $rawNumId = (int)$_GET['id'];
}
if ($rawNumId > 0) {
    $hash = Security::hashId($rawNumId);
    $extraQ = !empty($_GET['q']) ? '&q=' . urlencode(Security::sanitizeQuery((string)$_GET['q'])) : '';
    header("Location: gallery.php?session={$hash}{$extraQ}", true, 301);
    exit;
}

$settings = Community::getSettings();
$allSchedules = Schedule::all();

// Filter parameters: securely decode hash token, strictly preventing SQL injection & ID exposure
$sessionHash = isset($_GET['session']) ? (string)$_GET['session'] : (isset($_GET['sesi']) ? (string)$_GET['sesi'] : '');
$filterSessionId = Security::decodeId($sessionHash);
$currentSessionHash = $filterSessionId > 0 ? Security::hashId($filterSessionId) : '';

$filterQuery = isset($_GET['q']) ? Security::sanitizeQuery((string)$_GET['q']) : '';

// Fetch gallery items
$pdo = Database::getConnection();

// 1. Overall Total Photos Count across the entire community
$totalAllPhotosStmt = $pdo->query("SELECT COUNT(*) FROM `gallery`");
$totalAllPhotos = (int)$totalAllPhotosStmt->fetchColumn();

// 2. Count photos grouped by session_id for accurate pill badges
$branchCountsStmt = $pdo->query("SELECT session_id, COUNT(*) as cnt FROM `gallery` GROUP BY session_id");
$branchPhotoCounts = $branchCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [session_id => count]

// 3. Main Query for filtered photo list
$sql = "
    SELECT g.*, s.venue_name, s.day_name, s.branch_area
    FROM `gallery` g
    LEFT JOIN `schedules` s ON g.session_id = s.id
";

$where = [];
$params = [];

if ($filterSessionId > 0) {
    $where[] = "g.session_id = :sessionId";
    $params[':sessionId'] = $filterSessionId;
}

if (!empty($filterQuery)) {
    $where[] = "(g.title LIKE :q OR g.caption LIKE :q OR s.venue_name LIKE :q OR s.branch_area LIKE :q)";
    $safeQuery = addcslashes($filterQuery, '%_\\');
    $params[':q'] = "%{$safeQuery}%";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY g.activity_date DESC, g.id DESC LIMIT 120";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Auto-adapt base directory and photo URLs for root domain / cPanel hosting
$scriptDir = dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
foreach ($photos as &$p) {
    if (!empty($p['image_url']) && strpos($p['image_url'], 'http') !== 0) {
        if (strpos($p['image_url'], '/smashpoint/') === 0 && ($baseDir === '' || $baseDir === '/')) {
            $p['image_url'] = substr($p['image_url'], 11);
        }
    }
}
unset($p);

// Identify active schedule object if filtered
$activeSchedule = null;
if ($filterSessionId > 0) {
    foreach ($allSchedules as $s) {
        if ((int)$s['id'] === $filterSessionId) {
            $activeSchedule = $s;
            break;
        }
    }
}

// Format date helper
function formatPhotoDate($dateStr) {
    if (empty($dateStr)) return '-';
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $ts = strtotime($dateStr);
    $d = date('d', $ts);
    $m = date('m', $ts);
    $y = date('Y', $ts);
    return $d . ' ' . (isset($months[$m]) ? $months[$m] : '') . ' ' . $y;
}

// Prepare JSON for Lightbox
$galleryJson = json_encode(array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'title' => $p['title'],
        'caption' => $p['caption'] ?: '',
        'image_url' => $p['image_url'],
        'date' => formatPhotoDate($p['activity_date']),
        'venue' => $p['venue_name'] ?: (!empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club'),
        'area' => $p['branch_area'] ?: (!empty($settings['base_city']) ? $settings['base_city'] : 'Komunitas')
    ];
}, $photos), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Galeri Dokumentasi Mabar — <?= htmlspecialchars($settings['community_name']) ?></title>
  <meta name="description" content="Koleksi foto dan dokumentasi kegiatan sesi main bareng bulutangkis ganda mingguan komunitas <?= htmlspecialchars($settings['community_name']) ?> di <?= htmlspecialchars(!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia') ?>.">

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

  <style>
    * { font-stretch: normal !important; -webkit-tap-highlight-color: transparent; }
    body, h1, h2, h3, h4, p, span, button, input, select {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      letter-spacing: -0.015em;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace !important;
    }

    /* Modern Anti-Slop Scrollbar Elimination (No ugly gray scrollbars) */
    .no-scrollbar::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
    }
    .no-scrollbar {
      -ms-overflow-style: none !important;
      scrollbar-width: none !important;
    }
  </style>
</head>
<body class="bg-[#06111e] text-slate-100 min-h-screen flex flex-col antialiased selection:bg-amber-400 selection:text-slate-950">

  <!-- ========================================== -->
  <!-- NAVBAR                                     -->
  <!-- ========================================== -->
  <header class="sticky top-0 z-40 bg-[#0b1e36]/95 backdrop-blur-md text-white border-b border-slate-800 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
      
      <!-- Brand Logo & Name -->
      <a href="index.php" class="flex items-center gap-3 group">
        <?php if (!empty($settings['logo_url'])): ?>
          <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="w-9 h-9 object-contain bg-white p-0.5 border border-amber-400/50 shadow-xs">
        <?php else: ?>
          <div class="w-9 h-9 bg-amber-400 text-slate-950 font-black text-sm flex items-center justify-center border border-amber-300">
            PB
          </div>
        <?php endif; ?>
        <div class="leading-tight">
          <div class="font-black text-sm sm:text-base text-white group-hover:text-amber-400 transition-colors uppercase tracking-tight">
            <?= htmlspecialchars($settings['community_name']) ?>
          </div>
          <div class="text-[10px] font-mono text-amber-400 uppercase tracking-wider font-semibold">
            Galeri Dokumentasi Resmi
          </div>
        </div>
      </a>

      <!-- Quick Actions -->
      <div class="flex items-center gap-2 sm:gap-3">
        <a href="index.php" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-900/60 hover:bg-slate-800 border border-slate-700/80 transition-all flex items-center gap-2">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          <span>Kembali ke Beranda</span>
        </a>
      </div>

    </div>
  </header>

  <!-- ========================================== -->
  <!-- HERO BANNER (PREMIUM ATHLETIC SHOWCASE)    -->
  <!-- ========================================== -->
  <section class="bg-gradient-to-b from-[#0b1e36] to-[#071324] text-white pt-10 pb-10 sm:pt-14 sm:pb-14 border-b border-slate-800 relative overflow-hidden">
    <!-- Subtle Background Grid -->
    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#facc15 1px, transparent 1px); background-size: 28px 28px;"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="max-w-2xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-400/10 border border-amber-400/30 text-amber-400 text-xs font-mono font-bold uppercase tracking-wider mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
            <span>Arsip Dokumentasi & Momen Komunitas</span>
          </div>
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight">
            Momen & Keseruan Lapangan Bulutangkis
          </h1>
          <p class="text-slate-300 text-xs sm:text-sm mt-3 leading-relaxed">
            Foto autentik seluruh sesi mabar ganda mingguan di berbagai GOR terbaik <?= htmlspecialchars(!empty($settings['base_city']) ? $settings['base_city'] : 'komunitas') ?>. Didokumentasikan langsung oleh tim media lapangan.
          </p>
        </div>

        <!-- Metric Badges -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="bg-[#0b1e36]/80 border border-slate-700/80 px-4 py-2.5 shadow-sm text-left">
            <div class="text-[10px] font-mono text-slate-400 uppercase">Total Foto</div>
            <div class="text-xl sm:text-2xl font-black text-amber-400 font-mono"><?= $totalAllPhotos ?></div>
          </div>
          <div class="bg-[#0b1e36]/80 border border-slate-700/80 px-4 py-2.5 shadow-sm text-left">
            <div class="text-[10px] font-mono text-slate-400 uppercase">Cabang GOR</div>
            <div class="text-xl sm:text-2xl font-black text-white font-mono"><?= count($allSchedules) ?></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========================================== -->
  <!-- MODERN FILTER NAVIGATION BAR               -->
  <!-- ========================================== -->
  <section class="sticky top-16 z-30 bg-[#0b1e36]/90 backdrop-blur-md border-b border-slate-800 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        
        <!-- Filter Tabs / Pills Carousel with Smooth Buttons -->
        <div class="relative flex items-center min-w-0 flex-1">
          
          <!-- Scroll Container -->
          <div id="pillsScrollContainer" class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 scroll-smooth w-full">
            
            <!-- Tab: Semua Cabang -->
            <a href="gallery.php<?= !empty($filterQuery) ? '?q=' . urlencode($filterQuery) : '' ?>" class="flex items-center gap-2 px-4 py-2 text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap active:scale-95 shrink-0 <?= $filterSessionId === 0 ? 'bg-amber-400 text-slate-950 shadow-md ring-2 ring-amber-300' : 'bg-slate-800/90 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700' ?>">
              <span class="w-2 h-2 rounded-full <?= $filterSessionId === 0 ? 'bg-slate-950' : 'bg-slate-500' ?>"></span>
              <span>Semua Cabang</span>
              <span class="px-1.5 py-0.2 text-[10px] font-mono rounded-full font-black <?= $filterSessionId === 0 ? 'bg-slate-950 text-amber-400' : 'bg-slate-900 text-slate-400 border border-slate-700' ?>">
                <?= $totalAllPhotos ?>
              </span>
            </a>

            <!-- Tabs: Per Cabang Schedule -->
            <?php foreach ($allSchedules as $sch): 
              $isActive = ($filterSessionId === (int)$sch['id']);
              $schCount = isset($branchPhotoCounts[$sch['id']]) ? (int)$branchPhotoCounts[$sch['id']] : 0;
              $schHash = Security::hashId((int)$sch['id']);
            ?>
              <a href="gallery.php?session=<?= urlencode($schHash) ?><?= !empty($filterQuery) ? '&q=' . urlencode($filterQuery) : '' ?>" class="flex items-center gap-2 px-4 py-2 text-xs font-black tracking-wide transition-all whitespace-nowrap active:scale-95 shrink-0 <?= $isActive ? 'bg-amber-400 text-slate-950 shadow-md ring-2 ring-amber-300' : 'bg-slate-800/90 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700' ?>">
                <svg class="w-3.5 h-3.5 <?= $isActive ? 'text-slate-950' : 'text-amber-400' ?> shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($sch['venue_name']) ?></span>
                <span class="px-1.5 py-0.2 text-[10px] font-mono rounded-full font-black <?= $isActive ? 'bg-slate-950 text-amber-400' : 'bg-slate-900 text-slate-400 border border-slate-700' ?>">
                  <?= $schCount ?>
                </span>
              </a>
            <?php endforeach; ?>

          </div>

        </div>

        <!-- Integrated Search Bar -->
        <form method="GET" action="gallery.php" class="flex items-center gap-2 shrink-0">
          <?php if (!empty($currentSessionHash)): ?>
            <input type="hidden" name="session" value="<?= htmlspecialchars($currentSessionHash) ?>">
          <?php endif; ?>
          
          <div class="relative w-full sm:w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input type="text" name="q" value="<?= htmlspecialchars($filterQuery) ?>" placeholder="Cari foto, momen, venue..." class="bg-[#071324] border border-slate-700 pl-8 pr-8 py-2.5 sm:py-2 text-base sm:text-xs text-white placeholder-slate-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 w-full min-h-[44px]">
            <?php if (!empty($filterQuery)): ?>
              <a href="gallery.php<?= !empty($currentSessionHash) ? '?session=' . urlencode($currentSessionHash) : '' ?>" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white text-base" title="Hapus pencarian">&times;</a>
            <?php endif; ?>
          </div>

          <button type="submit" class="min-h-[44px] px-4 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all border border-amber-400 active:scale-95 shrink-0 flex items-center justify-center">
            Cari
          </button>
          
          <?php if ($filterSessionId > 0 || !empty($filterQuery)): ?>
            <a href="gallery.php" class="min-h-[44px] px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs uppercase transition-colors border border-slate-700 shrink-0 flex items-center justify-center" title="Tampilkan Semua">
              Reset
            </a>
          <?php endif; ?>
        </form>

      </div>

      <!-- Active Filter Pill Summary -->
      <?php if ($filterSessionId > 0 || !empty($filterQuery)): ?>
        <div class="mt-2.5 pt-2 border-t border-slate-800 flex items-center justify-between text-xs text-slate-300 font-mono">
          <div class="flex items-center gap-2 truncate">
            <span class="text-slate-400">Filter Aktif:</span>
            <?php if ($activeSchedule): ?>
              <span class="px-2 py-0.5 bg-amber-400/20 text-amber-300 border border-amber-400/40 text-[11px] font-bold font-sans">
                📍 <?= htmlspecialchars($activeSchedule['venue_name']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($filterQuery)): ?>
              <span class="px-2 py-0.5 bg-cyan-400/20 text-cyan-300 border border-cyan-400/40 text-[11px] font-bold font-sans">
                "<?= htmlspecialchars($filterQuery) ?>"
              </span>
            <?php endif; ?>
            <span class="text-slate-400 font-sans">• Ditemukan <strong><?= count($photos) ?></strong> foto</span>
          </div>

          <a href="gallery.php" class="text-amber-400 hover:text-amber-300 font-bold text-xs underline shrink-0 font-sans">
            Tampilkan Semua Foto →
          </a>
        </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- ========================================== -->
  <!-- PHOTO SHOWCASE GRID                        -->
  <!-- ========================================== -->
  <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 w-full">

    <?php if (empty($photos)): ?>
      <!-- Elegant Empty State -->
      <div class="bg-[#0b1e36] border border-slate-800 p-12 text-center max-w-lg mx-auto shadow-xl">
        <div class="w-14 h-14 bg-amber-400/10 text-amber-400 border border-amber-400/30 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <h3 class="text-lg font-black text-white">Belum Ada Foto untuk Kategori Ini</h3>
        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
          Foto dokumentasi pada sesi ini belum diunggah oleh PIC Lapangan atau tim dokumentasi media.
        </p>
        <div class="pt-5 flex items-center justify-center gap-3">
          <a href="gallery.php" class="px-5 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all border border-amber-300 active:scale-95 shadow-md">
            Lihat Semua Foto (<?= $totalAllPhotos ?>)
          </a>
        </div>
      </div>
    <?php else: ?>

      <!-- High-End Photography Card Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-6">
        <?php foreach ($photos as $idx => $p): 
          $fmtDate = formatPhotoDate($p['activity_date']);
        ?>
          <div class="bg-[#0b1e36] border border-slate-800 overflow-hidden shadow-md hover:border-amber-400 hover:shadow-xl hover:shadow-amber-400/5 transition-all duration-300 group flex flex-col cursor-pointer" onclick="openLightbox(<?= $idx ?>)">
            
            <!-- Photo Aspect Container -->
            <div class="aspect-[4/3] bg-slate-900 overflow-hidden relative">
              <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-500 ease-out">
              
              <!-- Dark Vignette Gradient -->
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent opacity-80 group-hover:opacity-60 transition-opacity"></div>

              <!-- Top Left: Venue Badge -->
              <div class="absolute top-2.5 left-2.5">
                <span class="px-2.5 py-1 bg-[#071324]/90 backdrop-blur-md text-amber-400 text-[10px] font-mono font-black uppercase tracking-wider border border-amber-400/40 shadow-sm flex items-center gap-1">
                  <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                  <span class="truncate max-w-[150px]"><?= htmlspecialchars($p['venue_name'] ?: ($settings['community_name'] ?? 'Badminton Club')) ?></span>
                </span>
              </div>

              <!-- Top Right: Date Badge -->
              <div class="absolute top-2.5 right-2.5">
                <span class="px-2 py-0.5 bg-slate-950/80 backdrop-blur-md text-slate-300 text-[10px] font-mono font-semibold border border-slate-700">
                  <?= $fmtDate ?>
                </span>
              </div>

              <!-- Center Interactive Cue on Hover -->
              <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                <span class="px-3.5 py-2 bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-xl transform group-hover:scale-100 scale-90 transition-transform">
                  Perbesar Foto ↗
                </span>
              </div>
            </div>

            <!-- Card Bottom Content -->
            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
              <div>
                <h3 class="font-black text-white text-sm sm:text-base group-hover:text-amber-400 transition-colors line-clamp-1 leading-snug">
                  <?= htmlspecialchars($p['title']) ?>
                </h3>
                <?php if (!empty($p['caption'])): ?>
                  <p class="text-xs text-slate-300 mt-1.5 line-clamp-2 leading-relaxed">
                    <?= htmlspecialchars($p['caption']) ?>
                  </p>
                <?php else: ?>
                  <p class="text-xs text-slate-500 mt-1.5 italic">
                    Dokumentasi mabar mingguan ganda komunitas <?= htmlspecialchars($settings['community_name'] ?? 'Badminton Club') ?>.
                  </p>
                <?php endif; ?>
              </div>

              <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400 font-mono">
                <span class="truncate max-w-[170px]"><?= htmlspecialchars($p['branch_area'] ?: (!empty($settings['base_city']) ? $settings['base_city'] : 'Komunitas')) ?></span>
                <span class="text-amber-400 font-bold group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                  Lihat <span>→</span>
                </span>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </main>

  <!-- ========================================== -->
  <!-- ANDROID & DESKTOP PREMIUM LIGHTBOX MODAL   -->
  <!-- ========================================== -->
  <div id="lightboxModal" class="hidden fixed inset-0 bg-slate-950/95 z-50 flex items-center justify-center p-0 sm:p-6 backdrop-blur-md transition-opacity duration-200 select-none" onclick="handleLightboxBackdrop(event)">
    <div class="bg-[#0b1e36] max-w-5xl w-full h-full sm:h-auto sm:max-h-[94vh] overflow-hidden shadow-2xl flex flex-col border-0 sm:border border-slate-700" onclick="event.stopPropagation()">
      
      <!-- Lightbox Header Bar (Mobile Thumb-Optimized) -->
      <div class="bg-[#071324] text-white px-4 py-3 flex items-center justify-between border-b border-slate-800 shrink-0">
        <div class="min-w-0 pr-3">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-mono text-amber-400 uppercase tracking-wider font-black truncate max-w-[140px] sm:max-w-none" id="lbVenue">
              <?= htmlspecialchars(strtoupper($settings['community_name'] ?? 'BADMINTON CLUB')) ?>
            </span>
            <span class="text-slate-600">•</span>
            <span class="text-[10px] font-mono text-slate-400" id="lbDate">Tanggal</span>
          </div>
          <h2 class="text-xs sm:text-base font-black text-white truncate mt-0.5" id="lbTitle">
            Judul Dokumentasi
          </h2>
        </div>
        
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-[11px] sm:text-xs font-mono font-bold text-amber-400 bg-amber-400/10 px-2 sm:px-2.5 py-1 border border-amber-400/30" id="lbCounter">
            Foto 1 / 1
          </span>
          <button type="button" onclick="closeLightbox()" class="min-h-[44px] min-w-[44px] px-3 py-2 bg-slate-800 hover:bg-rose-900 active:scale-95 text-white font-bold text-xs uppercase tracking-wider border border-slate-700 transition-colors flex items-center justify-center" title="Tutup (Esc)">
            &times; Tutup
          </button>
        </div>
      </div>

      <!-- Lightbox Main Stage with Touch Gestures -->
      <div id="lbStage" class="bg-black flex-1 relative flex items-center justify-center min-h-[260px] max-h-[72vh] sm:max-h-[66vh] overflow-hidden touch-pan-y">
        <img id="lbImg" src="" alt="Dokumentasi" class="max-h-[72vh] sm:max-h-[66vh] max-w-full object-contain transition-all duration-200 pointer-events-none">

        <!-- Mobile Swipe Guidance Pill (Fades after 2s) -->
        <div id="lbSwipeHint" class="sm:hidden absolute top-3 px-3 py-1 rounded-full bg-slate-900/80 backdrop-blur text-slate-300 border border-slate-700 text-[10px] font-mono font-bold transition-opacity duration-500 pointer-events-none">
          ↔ Geser layar untuk foto lain
        </div>

        <!-- Desktop Navigation Arrows -->
        <button type="button" onclick="prevLightbox()" class="hidden sm:flex absolute left-3 top-1/2 -translate-y-1/2 w-12 h-12 bg-slate-950/80 hover:bg-amber-400 hover:text-slate-950 text-white text-2xl font-bold items-center justify-center backdrop-blur border border-slate-700 transition-all active:scale-90" aria-label="Foto Sebelumnya" title="Panah Kiri">
          ‹
        </button>
        <button type="button" onclick="nextLightbox()" class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 w-12 h-12 bg-slate-950/80 hover:bg-amber-400 hover:text-slate-950 text-white text-2xl font-bold items-center justify-center backdrop-blur border border-slate-700 transition-all active:scale-90" aria-label="Foto Selanjutnya" title="Panah Kanan">
          ›
        </button>
      </div>

      <!-- Lightbox Caption & Thumb Navigation Bar -->
      <div class="p-3.5 sm:p-4 bg-[#0b1e36] border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
        <div class="min-w-0 flex-1">
          <p class="text-xs text-slate-300 leading-relaxed line-clamp-2 sm:line-clamp-none" id="lbCaption">
            Keterangan foto dokumentasi.
          </p>
        </div>

        <!-- Mobile & Desktop Actions (All Touch Hitboxes >= 44px) -->
        <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-1 sm:pt-0 border-t sm:border-0 border-slate-800">
          <!-- Mobile Thumb Next/Prev -->
          <div class="flex sm:hidden items-center gap-1">
            <button type="button" onclick="prevLightbox()" class="min-h-[44px] min-w-[44px] px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white font-black text-sm border border-slate-700 active:scale-90 transition-all flex items-center justify-center">
              ‹ Prev
            </button>
            <button type="button" onclick="nextLightbox()" class="min-h-[44px] min-w-[44px] px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white font-black text-sm border border-slate-700 active:scale-90 transition-all flex items-center justify-center">
              Next ›
            </button>
          </div>

          <a id="lbDownloadLink" href="#" target="_blank" download class="min-h-[44px] px-4 py-2 bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-xs uppercase tracking-wider transition-all border border-amber-300 flex items-center justify-center gap-1.5 shadow-xs">
            <span>Buka Ukuran Asli</span>
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- ========================================== -->
  <!-- FOOTER                                     -->
  <!-- ========================================== -->
  <footer class="bg-[#071324] text-white border-t border-slate-800 py-8 text-center text-xs text-slate-400 mt-auto">
    <div class="max-w-7xl mx-auto px-4">
      <p class="font-bold text-slate-200">
        <?= htmlspecialchars($settings['community_name']) ?> — Komunitas Bulutangkis <?= htmlspecialchars(!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia') ?>
      </p>
      <p class="text-[11px] text-slate-500 mt-1">
        Dokumentasi resmi seluruh sesi mabar ganda mingguan. Foto hak cipta milik komunitas.
      </p>
    </div>
  </footer>

  <!-- Android & Desktop Touch-Optimized Lightbox Script -->
  <script>
    const galleryItems = <?= $galleryJson ?>;
    let currentIdx = 0;

    function openLightbox(idx) {
      if (!galleryItems || galleryItems.length === 0) return;
      currentIdx = idx >= 0 && idx < galleryItems.length ? idx : 0;
      renderLightbox();
      const modal = document.getElementById('lightboxModal');
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';

      // Show swipe hint temporarily on touch devices
      const hint = document.getElementById('lbSwipeHint');
      if (hint) {
        hint.style.opacity = '1';
        setTimeout(() => { hint.style.opacity = '0'; }, 2400);
      }
    }

    function closeLightbox() {
      const modal = document.getElementById('lightboxModal');
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }

    function handleLightboxBackdrop(e) {
      if (e.target.id === 'lightboxModal') {
        closeLightbox();
      }
    }

    function renderLightbox() {
      const item = galleryItems[currentIdx];
      if (!item) return;

      const imgEl = document.getElementById('lbImg');
      imgEl.style.opacity = '0.3';
      imgEl.src = item.image_url;
      imgEl.onload = () => { imgEl.style.opacity = '1'; };

      document.getElementById('lbTitle').textContent = item.title;
      document.getElementById('lbVenue').textContent = `${item.venue.toUpperCase()} • ${item.area.toUpperCase()}`;
      document.getElementById('lbDate').textContent = item.date;
      document.getElementById('lbCaption').textContent = item.caption || <?= json_encode('Dokumentasi resmi sesi mabar ' . ($settings['community_name'] ?? 'Badminton Club') . '.') ?>;
      document.getElementById('lbCounter').textContent = `Foto ${currentIdx + 1} dari ${galleryItems.length}`;
      document.getElementById('lbDownloadLink').href = item.image_url;
    }

    function prevLightbox() {
      if (!galleryItems.length) return;
      currentIdx = (currentIdx - 1 + galleryItems.length) % galleryItems.length;
      renderLightbox();
    }

    function nextLightbox() {
      if (!galleryItems.length) return;
      currentIdx = (currentIdx + 1) % galleryItems.length;
      renderLightbox();
    }

    // Touch Swipe Gesture Support (Android & Mobile)
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    const stageEl = document.getElementById('lbStage');
    if (stageEl) {
      stageEl.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
      }, { passive: true });

      stageEl.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleGesture();
      }, { passive: true });
    }

    function handleGesture() {
      const deltaX = touchEndX - touchStartX;
      const deltaY = touchEndY - touchStartY;
      const minSwipeDistance = 45;

      // Horizontal Swipe
      if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
        if (deltaX < 0) {
          // Swipe Left -> Next
          nextLightbox();
        } else {
          // Swipe Right -> Prev
          prevLightbox();
        }
      } 
      // Vertical Swipe Down -> Dismiss modal
      else if (deltaY > 90 && Math.abs(deltaX) < 60) {
        closeLightbox();
      }
    }

    // Keyboard Shortcuts (Left, Right, Escape)
    document.addEventListener('keydown', (e) => {
      const modal = document.getElementById('lightboxModal');
      if (modal && !modal.classList.contains('hidden')) {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') prevLightbox();
        if (e.key === 'ArrowRight') nextLightbox();
      }
    });
  </script>

</body>
</html>