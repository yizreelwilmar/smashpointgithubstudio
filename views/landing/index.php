<?php
// views/landing/index.php
// Powershoot Badminton — Modern Responsive Landing Page
// Architecture: elayadesign/ai-design-skills (Floating Island Nav, Section Rhythms, FAQ Accordion) + GoSport Sports UI
// Clean Anti-Slop: Plus Jakarta Sans, Zero Icons, Full Mobile Responsive Drawer, Real Indonesian Badminton Copy

require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../../models/Community.php';
require_once __DIR__ . '/../../models/Schedule.php';
require_once __DIR__ . '/../../models/Player.php';

date_default_timezone_set('Asia/Jakarta');

$settings = Community::getSettings();
$gallery = Community::getGallery(24);
$upcomingSchedules = Schedule::upcoming(12);
if (empty($upcomingSchedules)) {
    $upcomingSchedules = Schedule::all();
}
$allSchedules = Schedule::all();
$topScorers = Player::getLeaderboard(10);

// Week classifier, relative day countdown & Indonesian date formatting helper
if (!function_exists('getScheduleWeekInfo')) {
    function getScheduleWeekInfo($dateStr) {
        if (empty($dateStr)) {
            return [
                'code' => 'all',
                'filter' => 'all',
                'label' => 'JADWAL RUTIN',
                'week_label' => '',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-300'
            ];
        }

        $cleanDate = substr(trim($dateStr), 0, 10);
        $today = date('Y-m-d');
        $todayTs = strtotime($today);
        $targetTs = strtotime($cleanDate);
        $diffDays = (int)round(($targetTs - $todayTs) / 86400);

        $dayOfWeek = (int)date('N', $todayTs); // 1 = Mon, 7 = Sun
        $startThisWeek = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days', $todayTs));
        $endThisWeek = date('Y-m-d', strtotime('+' . (7 - $dayOfWeek) . ' days', $todayTs));
        
        $startNextWeek = date('Y-m-d', strtotime('+1 day', strtotime($endThisWeek)));
        $endNextWeek = date('Y-m-d', strtotime('+7 days', strtotime($endThisWeek)));

        // Determine week filter category
        $weekFilter = 'all';
        $weekLabel = '';
        if ($cleanDate >= $startThisWeek && $cleanDate <= $endThisWeek) {
            $weekFilter = 'this_week';
            $weekLabel = 'MINGGU INI';
        } elseif ($cleanDate >= $startNextWeek && $cleanDate <= $endNextWeek) {
            $weekFilter = 'next_week';
            $weekLabel = 'MINGGU DEPAN';
        } elseif ($cleanDate > $endNextWeek) {
            $weekFilter = 'upcoming';
            $weekLabel = 'MENDATANG';
        }

        // Determine automatic countdown label (Hari Ini / Besok / X Hari Lagi)
        if ($diffDays === 0) {
            return [
                'code' => 'today',
                'filter' => $weekFilter !== 'all' ? $weekFilter : 'this_week',
                'label' => 'HARI INI',
                'week_label' => $weekLabel,
                'badge_class' => 'bg-rose-600 text-white border-rose-700'
            ];
        }

        if ($diffDays === 1) {
            return [
                'code' => 'tomorrow',
                'filter' => $weekFilter !== 'all' ? $weekFilter : 'this_week',
                'label' => 'BESOK',
                'week_label' => $weekLabel,
                'badge_class' => 'bg-amber-400 text-slate-950 border-amber-500'
            ];
        }

        if ($diffDays === 2) {
            return [
                'code' => 'in_2_days',
                'filter' => $weekFilter,
                'label' => '2 HARI LAGI',
                'week_label' => $weekLabel,
                'badge_class' => 'bg-emerald-600 text-white border-emerald-700'
            ];
        }

        if ($diffDays > 2) {
            return [
                'code' => 'in_days',
                'filter' => $weekFilter,
                'label' => $diffDays . ' HARI LAGI',
                'week_label' => $weekLabel,
                'badge_class' => 'bg-blue-600 text-white border-blue-700'
            ];
        }

        return [
            'code' => 'past',
            'filter' => 'all',
            'label' => 'SELESAI',
            'week_label' => '',
            'badge_class' => 'bg-slate-100 text-slate-500 border-slate-300'
        ];
    }
}

// Compute Date Range & Week Counters for schedule filtering
$minScheduleDate = null;
$maxScheduleDate = null;
$countThisWeek = 0;
$countNextWeek = 0;
$countUpcoming = 0;
$distinctDates = [];

foreach ($upcomingSchedules as $s) {
    if (!empty($s['schedule_date'])) {
        if ($minScheduleDate === null || $s['schedule_date'] < $minScheduleDate) $minScheduleDate = $s['schedule_date'];
        if ($maxScheduleDate === null || $s['schedule_date'] > $maxScheduleDate) $maxScheduleDate = $s['schedule_date'];
        
        $wInfo = getScheduleWeekInfo($s['schedule_date']);
        if ($wInfo['filter'] === 'this_week') $countThisWeek++;
        elseif ($wInfo['filter'] === 'next_week') $countNextWeek++;
        elseif ($wInfo['filter'] === 'upcoming') $countUpcoming++;
        
        $distinctDates[$s['schedule_date']] = formatIndoDate($s['schedule_date']);
    }
}
ksort($distinctDates);

$dateRangeDisplay = '';
if ($minScheduleDate && $maxScheduleDate) {
    if ($minScheduleDate === $maxScheduleDate) {
        $dateRangeDisplay = formatIndoDate($minScheduleDate);
    } else {
        $tMin = strtotime($minScheduleDate);
        $tMax = strtotime($maxScheduleDate);
        $bulanArr = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $minDay = date('j', $tMin);
        $minMonth = $bulanArr[(int)date('n', $tMin)] ?? date('M', $tMin);
        $minYear = date('Y', $tMin);
        $maxDay = date('j', $tMax);
        $maxMonth = $bulanArr[(int)date('n', $tMax)] ?? date('M', $tMax);
        $maxYear = date('Y', $tMax);

        if ($minYear === $maxYear) {
            if ($minMonth === $maxMonth) {
                $dateRangeDisplay = "{$minDay} – {$maxDay} {$minMonth} {$minYear}";
            } else {
                $dateRangeDisplay = "{$minDay} {$minMonth} – {$maxDay} {$maxMonth} {$minYear}";
            }
        } else {
            $dateRangeDisplay = "{$minDay} {$minMonth} {$minYear} – {$maxDay} {$maxMonth} {$maxYear}";
        }
    }
}

// Dynamic URLs for port / subdomain routing
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
if (substr($baseDir, -8) === '/landing') $baseDir = substr($baseDir, 0, -8);
if (substr($baseDir, -4) === '/app') $baseDir = substr($baseDir, 0, -4);
if (substr($baseDir, -6) === '/admin') $baseDir = substr($baseDir, 0, -6);

$appUrl = $baseDir . '/app/';
$adminUrl = $baseDir . '/admin/';
$landingUrl = $baseDir . '/';

// Instagram link safety fix
$igRaw = isset($settings['instagram']) ? $settings['instagram'] : '';
$igUrl = (strpos($igRaw, 'http') === 0) ? $igRaw : 'https://instagram.com/' . ltrim($igRaw, '@');
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  
  <!-- Technical SEO & Social Graph Meta Tags (Powered by Yoast-style SEO Engine) -->
  <?php 
    $baseCityName = !empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia';
    $rawTitle = !empty($settings['meta_title']) ? $settings['meta_title'] : ($settings['community_name'] . ' — Mabar Badminton & Rotasi Lapangan ' . $baseCityName);
    $seoTitle = Community::parseSeoVariables($rawTitle, $settings);

    $rawDesc = !empty($settings['meta_description']) ? $settings['meta_description'] : (!empty($settings['description']) ? strip_tags($settings['description']) : ('Komunitas mabar badminton teratur di ' . $baseCityName . ' dengan sistem rotasi ganda adil dan kok bulu angsa berkualitas turnamen.'));
    $seoDesc = Community::parseSeoVariables($rawDesc, $settings);

    $rawKeywords = !empty($settings['meta_keywords']) ? $settings['meta_keywords'] : ('mabar badminton ' . strtolower($baseCityName) . ', komunitas badminton ' . strtolower($baseCityName) . ', main badminton bersama, jadwal mabar badminton, gor badminton, sewa lapangan badminton, sparring bulutangkis');
    $seoKeywords = Community::parseSeoVariables($rawKeywords, $settings);

    $rawOgTitle = !empty($settings['og_title']) ? $settings['og_title'] : $seoTitle;
    $ogTitle = Community::parseSeoVariables($rawOgTitle, $settings);

    $rawOgDesc = !empty($settings['og_description']) ? $settings['og_description'] : $seoDesc;
    $ogDesc = Community::parseSeoVariables($rawOgDesc, $settings);

    $ogImage = !empty($settings['og_image_url']) ? $settings['og_image_url'] : (!empty($settings['hero_image_url']) ? $settings['hero_image_url'] : 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80');

    $robotsMeta = !empty($settings['robots_meta']) ? $settings['robots_meta'] : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $canonicalUrl = !empty($settings['canonical_url']) ? $settings['canonical_url'] : '';
  ?>
  <title><?= htmlspecialchars($seoTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($seoKeywords) ?>">
  <meta name="author" content="<?= htmlspecialchars($settings['community_name']) ?>">
  <meta name="robots" content="<?= htmlspecialchars($robotsMeta) ?>">
  <?php if (!empty($canonicalUrl)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="#0b1e36">
  <meta name="format-detection" content="telephone=no">

  <!-- OpenGraph Social Tags -->
  <meta property="og:type" content="website">
  <meta property="og:locale" content="id_ID">
  <meta property="og:site_name" content="<?= htmlspecialchars($settings['community_name']) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
  <?php if (!empty($canonicalUrl)): ?>
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
  <?php endif; ?>

  <!-- Twitter Card Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

  <!-- Schema.org SportsClub Structured Data (JSON-LD) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SportsClub",
    "name": "<?= addslashes($settings['community_name']) ?>",
    "alternateName": "<?= addslashes($settings['community_name']) ?>",
    "description": "<?= addslashes($siteDesc) ?>",
    "sport": "Badminton",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "<?= addslashes($baseCityName) ?>",
      "addressRegion": "<?= addslashes($baseCityName) ?>",
      "addressCountry": "ID"
    },
    "telephone": "<?= !empty($settings['whatsapp_default']) ? addslashes($settings['whatsapp_default']) : '081299887766' ?>",
    "sameAs": [
      "<?= htmlspecialchars($igUrl) ?>"
    ]
  }
  </script>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= $baseDir ?>/shared/assets/css/style.css?v=<?= file_exists(__DIR__ . '/../../shared/assets/css/style.css') ? filemtime(__DIR__ . '/../../shared/assets/css/style.css') : time() ?>">
  <style>
    * {
      font-stretch: normal !important;
      -webkit-tap-highlight-color: transparent;
    }
    html {
      scroll-behavior: smooth !important;
      scroll-padding-top: 5rem !important; /* Offset for seamless sticky header */
    }
    body, h1, h2, h3, h4, h5, h6, p, span, button, a {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      font-stretch: normal !important;
    }
    .safe-top {
      padding-top: env(safe-area-inset-top, 0px);
    }
    .safe-bottom {
      padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    /* Sticky Header Transition & Active Scrollspy Effects */
    #mainHeader {
      transition: background-color 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
    #mainHeader.scrolled {
      box-shadow: 0 4px 20px -2px rgba(11, 30, 54, 0.08);
      border-bottom-color: rgba(226, 232, 240, 0.95);
    }
    .nav-item {
      position: relative;
      transition: color 0.2s ease;
    }
    .nav-item.active {
      color: #b45309 !important; /* amber-700 */
    }
    .nav-item.active .nav-indicator {
      opacity: 1 !important;
      transform: scaleX(1) !important;
    }
    .nav-indicator {
      transform: scaleX(0);
      transform-origin: center;
      transition: transform 0.25s ease, opacity 0.25s ease;
    }
    .nav-item:hover .nav-indicator {
      opacity: 0.6;
      transform: scaleX(0.7);
    }
  </style>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          },
          screens: {
            'xs': '420px',
          },
          colors: {
            brand: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              500: '#0284c7',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e3a8a',
              900: '#0b1e36',
              950: '#071324',
            },
            gold: {
              50: '#fefce8',
              100: '#fef9c3',
              200: '#fef08a',
              300: '#fde047',
              400: '#facc15',
              500: '#f59e0b',
              600: '#d97706',
              700: '#b45309',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-[#f8fafc] text-slate-900 min-h-screen flex flex-col antialiased selection:bg-amber-400 selection:text-slate-950 font-sans">

  <!-- Top Announcement Bar (Community & Registrasi Info - Responsive Stacking) -->
  <div class="bg-slate-950 text-white px-3 sm:px-4 py-2 text-xs font-mono border-b border-slate-800">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-1.5 sm:gap-3 text-center sm:text-left">
            <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-amber-400 pulse-live shrink-0"></span>
        <span class="font-bold text-white tracking-wide uppercase text-[11px] sm:text-xs"><?= htmlspecialchars($settings['community_name']) ?></span>
        <span class="text-slate-400 hidden md:inline">• <?= htmlspecialchars(!empty($settings['announcement_text']) ? $settings['announcement_text'] : 'Komunitas Badminton Resmi ' . $settings['base_city'] . ' (Est. ' . $settings['established_year'] . ')') ?></span>
      </div>
      <div class="flex items-center gap-3 text-[11px] font-sans">
        <span class="text-amber-400 font-bold hidden xs:inline"><?= htmlspecialchars(!empty($settings['announcement_subtext']) ? $settings['announcement_subtext'] : 'Slot Dibuka Setiap Pekan') ?></span>
        <?php 
          $adminWa = !empty($settings['whatsapp_default']) ? $settings['whatsapp_default'] : '083831198543';
          $waDigits = preg_replace('/[^0-9]/', '', $adminWa);
          if (strpos($waDigits, '0') === 0) {
              $waDigits = '62' . substr($waDigits, 1);
          }
        ?>
        <a href="https://wa.me/<?= $waDigits ?>" target="_blank" rel="noopener noreferrer" class="text-slate-300 hover:text-white underline underline-offset-2 font-medium">
          Hubungi Admin WA: <?= htmlspecialchars($adminWa) ?>
        </a>
      </div>
    </div>
  </div>

  <!-- NAVIGATION BAR (Unified Full-Width, Sharp Architectural, Smooth UX) -->
  <header id="mainHeader" class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-md border-b border-slate-200 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <nav class="flex items-center justify-between h-16 sm:h-20 transition-all">
        
        <!-- Brand Logo -->
        <a href="<?= $landingUrl ?>" class="flex items-center gap-2.5 sm:gap-3 group shrink-0">
          <?php if (!empty($settings['logo_url'])): ?>
            <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo <?= htmlspecialchars($settings['community_name']) ?>" class="w-9 h-9 sm:w-10 sm:h-10 rounded-none object-contain border border-slate-200 bg-white shadow-xs">
          <?php else: ?>
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-none bg-[#0b1e36] flex items-center justify-center text-white font-black text-sm sm:text-base shadow-xs group-hover:bg-[#071324] transition-colors">
              PB
            </div>
          <?php endif; ?>
          <div>
            <div class="font-black text-sm sm:text-lg tracking-tight text-slate-950 leading-none">
              <?= htmlspecialchars(strtoupper($settings['community_name'])) ?>
            </div>
            <div class="text-[9px] sm:text-[10px] font-mono font-bold text-amber-600 tracking-wider uppercase mt-0.5">
              Badminton Club
            </div>
          </div>
        </a>

        <!-- Desktop Nav Links (Clean, Architectural, Scrollspy Active Indicators) -->
        <div id="desktopNavLinks" class="hidden lg:flex items-center gap-1 xl:gap-2 text-xs font-bold text-slate-700 h-full">
          <a href="#jadwal" class="nav-item relative px-3 py-2 rounded-none hover:text-amber-600 transition-colors flex items-center h-full">
            <span>Jadwal Mabar</span>
            <span class="nav-indicator absolute bottom-0 left-3 right-3 h-0.5 bg-amber-500 opacity-0 transition-all"></span>
          </a>
          <a href="#cara-ikut" class="nav-item relative px-3 py-2 rounded-none hover:text-amber-600 transition-colors flex items-center h-full">
            <span>Cara Ikut Mabar</span>
            <span class="nav-indicator absolute bottom-0 left-3 right-3 h-0.5 bg-amber-500 opacity-0 transition-all"></span>
          </a>
          <a href="#leaderboard" class="nav-item relative px-3 py-2 rounded-none hover:text-amber-600 transition-colors flex items-center h-full">
            <span>Top Scorers Leaderboard</span>
            <span class="nav-indicator absolute bottom-0 left-3 right-3 h-0.5 bg-amber-500 opacity-0 transition-all"></span>
          </a>
          <a href="#galeri" class="nav-item relative px-3 py-2 rounded-none hover:text-amber-600 transition-colors flex items-center h-full">
            <span>Dokumentasi</span>
            <span class="nav-indicator absolute bottom-0 left-3 right-3 h-0.5 bg-amber-500 opacity-0 transition-all"></span>
          </a>
          <a href="#faq" class="nav-item relative px-3 py-2 rounded-none hover:text-amber-600 transition-colors flex items-center h-full">
            <span>Tanya Jawab</span>
            <span class="nav-indicator absolute bottom-0 left-3 right-3 h-0.5 bg-amber-500 opacity-0 transition-all"></span>
          </a>
        </div>

        <!-- Right CTAs & Mobile Hamburger Toggle -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
          <a href="#jadwal" class="hidden sm:inline-flex px-5 py-2.5 rounded-none bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider transition-all btn-tactile shadow-xs border border-amber-400/30">
            Daftar via WA →
          </a>

          <!-- Mobile Hamburger Button with Min 44x44px Touch Target -->
          <button id="mobileMenuBtn" aria-label="Toggle Navigation Menu" class="lg:hidden w-10 h-10 rounded-none bg-slate-100 hover:bg-slate-200 active:scale-95 flex flex-col items-center justify-center gap-1.5 focus:outline-none transition-all border border-slate-200">
            <span class="hamburger-line line-1 w-5 h-0.5 bg-slate-900 transition-transform"></span>
            <span class="hamburger-line line-2 w-5 h-0.5 bg-slate-900 transition-opacity"></span>
            <span class="hamburger-line line-3 w-5 h-0.5 bg-slate-900 transition-transform"></span>
          </button>
        </div>

      </nav>
    </div>

    <!-- Mobile Drawer Panel (Clean, Flush with Header, Smooth Collapse) -->
    <div id="mobileDrawer" class="hidden lg:hidden border-t border-slate-200 bg-white/98 backdrop-blur-md px-4 sm:px-6 py-4 shadow-xl space-y-2 transition-all">
      <div class="flex flex-col space-y-1 text-sm font-bold text-slate-800">
        <a href="#jadwal" onclick="closeMobileMenu()" class="min-h-[44px] px-3 py-2.5 rounded-none hover:bg-amber-50/70 hover:text-amber-700 transition-colors flex items-center justify-between">
          <span>Jadwal Mabar Mingguan</span>
          <span class="text-xs text-slate-700 font-mono font-bold bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-none">4 GOR</span>
        </a>
        <a href="#cara-ikut" onclick="closeMobileMenu()" class="min-h-[44px] px-3 py-2.5 rounded-none hover:bg-amber-50/70 hover:text-amber-700 transition-colors flex items-center">
          Cara Ikut Mabar (3 Langkah)
        </a>
        <a href="#leaderboard" onclick="closeMobileMenu()" class="min-h-[44px] px-3 py-2.5 rounded-none hover:bg-amber-50/70 hover:text-amber-700 transition-colors flex items-center">
          Top Scorers Bulan Ini
        </a>
        <a href="#galeri" onclick="closeMobileMenu()" class="min-h-[44px] px-3 py-2.5 rounded-none hover:bg-amber-50/70 hover:text-amber-700 transition-colors flex items-center">
          Dokumentasi
        </a>
        <a href="#faq" onclick="closeMobileMenu()" class="min-h-[44px] px-3 py-2.5 rounded-none hover:bg-amber-50/70 hover:text-amber-700 transition-colors flex items-center">
          Tanya Jawab (FAQ)
        </a>
      </div>

      <div class="pt-3 border-t border-slate-200">
        <a href="#jadwal" onclick="closeMobileMenu()" class="w-full min-h-[44px] py-3 rounded-none bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 font-extrabold text-xs uppercase tracking-wider text-center flex items-center justify-center transition-all btn-tactile border border-amber-400/30">
          Daftar Mabar via WhatsApp PIC →
        </a>
      </div>
    </div>
  </header>

  <!-- Hero Section: Immersive Cinematic Video Background Teaser (Anti-AI Slop, Zero Clutter) -->
  <section class="relative min-h-[580px] sm:min-h-[640px] lg:min-h-[700px] flex items-center justify-center border-b border-slate-900 bg-slate-950 text-white overflow-hidden">
    
    <!-- Video Background Loop (Full Width & Height Ambient Teaser) -->
    <div class="absolute inset-0 w-full h-full overflow-hidden select-none pointer-events-none">
      <video id="bgTeaserVideo" 
             class="w-full h-full object-cover object-center transition-opacity duration-1000 opacity-75"
             autoplay muted loop playsinline preload="auto"
             poster="<?= $baseDir ?>/public/assets/videos/badmin1_poster.jpg">
        <source src="<?= $baseDir ?>/public/assets/videos/badmin1.mp4" type="video/mp4">
        <source src="<?= $baseDir ?>/assets/videos/badmin1.mp4" type="video/mp4">
      </video>
    </div>

    <!-- Cinematic Overlays: Dual Gradient & Vignette for Crystal Clear Text Contrast -->
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/80 to-slate-950/50 pointer-events-none"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-slate-950/60 pointer-events-none"></div>

    <!-- Sound Control Floating Pill (Top-Right of Hero) -->
    <div class="absolute top-4 right-4 sm:top-6 sm:right-8 z-30">
      <button type="button" id="bgSoundToggle" onclick="toggleTeaserSound()" aria-label="Toggle Audio" class="px-3.5 py-1.5 bg-black/60 hover:bg-black/85 text-white text-xs font-mono font-bold border border-white/20 backdrop-blur-md transition-all active:scale-95 flex items-center gap-2">
        <span id="bgSoundIcon">🔇</span>
        <span id="bgSoundLabel" class="text-[11px] uppercase tracking-wider">Audio Muted</span>
      </button>
    </div>

    <!-- Hero Content Overlay (Editorial, High-Contrast, Direct Impact) -->
    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 w-full">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
        
        <!-- Left Column: Typography, Badge & Direct CTA -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- Live Community Status Badge -->
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-400 text-slate-950 text-xs font-black font-mono uppercase tracking-wider border border-amber-300 shadow-md">
            <span class="w-2 h-2 rounded-full bg-slate-950 animate-pulse"></span>
            <span>KOMUNITAS MABAR BADMINTON RESMI • <?= strtoupper(htmlspecialchars($baseCityName)) ?></span>
          </div>

          <!-- Main Headline -->
          <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.08] drop-shadow-md">
            <?= htmlspecialchars(!empty($settings['hero_title']) ? $settings['hero_title'] : 'Main Badminton Bareng Tanpa Repot Cari Lawan & Lapangan.') ?>
          </h1>

          <!-- Subtitle -->
          <p class="text-base sm:text-xl text-slate-200 leading-relaxed font-normal max-w-2xl drop-shadow-sm">
            <?= nl2br(htmlspecialchars(!empty($settings['hero_subtitle']) ? $settings['hero_subtitle'] : 'Selamat datang di Powershoot Badminton Club. Kami menyelenggarakan sesi main bareng ganda di berbagai GOR terbaik Jakarta Selatan. Cukup daftar ke PIC WhatsApp, datang ke arena, dan nikmati pertandingan seru dengan pembagian giliran main yang teratur.')) ?>
          </p>

          <!-- CTA Buttons -->
          <div class="pt-2 flex flex-wrap items-center gap-3">
            <a href="#jadwal" class="px-7 py-4 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all btn-tactile shadow-xl border border-amber-300">
              <?= htmlspecialchars(!empty($settings['hero_cta_text']) ? $settings['hero_cta_text'] : 'Pilih Jadwal & Daftar via WhatsApp PIC →') ?>
            </a>
            <a href="#cara-ikut" class="px-6 py-4 bg-slate-900/80 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition-all border border-slate-700 backdrop-blur-md">
              Cara Kerja Mabar
            </a>
          </div>

          <!-- Highlights Bar: Clean, Direct, High-Trust -->
          <div class="pt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-300 font-medium">
            <div class="inline-flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              <span>Semua Level Selamat Datang</span>
            </div>
            <div class="inline-flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
              <span>Daftar Sendiri atau Berpasangan</span>
            </div>
            <div class="inline-flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
              <span>Langsung Datang & Bertanding</span>
            </div>
          </div>

        </div>

        <!-- Right Column: Interactive Upcoming Match Spotlight Card -->
        <?php 
          $heroSpotlight = !empty($upcomingSchedules) ? $upcomingSchedules[0] : null;
          if ($heroSpotlight): 
            $hsRawDate = !empty($heroSpotlight['schedule_date']) ? $heroSpotlight['schedule_date'] : '';
            $hsWInfo = getScheduleWeekInfo($hsRawDate);
            $hsDateFormatted = !empty($hsRawDate) ? formatIndoDate($hsRawDate) : '';
            $hsQuota = isset($heroSpotlight['max_slots']) ? (int)$heroSpotlight['max_slots'] : (isset($heroSpotlight['max_participants']) ? (int)$heroSpotlight['max_participants'] : 16);
            $hsEnrolled = isset($heroSpotlight['participant_count']) ? (int)$heroSpotlight['participant_count'] : 0;
            $hsSlotsLeft = max(0, $hsQuota - $hsEnrolled);
            $hsFeeFormatted = 'Rp ' . number_format($heroSpotlight['fee_per_person'], 0, ',', '.');
            $hsTStart = isset($heroSpotlight['time_start']) ? $heroSpotlight['time_start'] : (isset($heroSpotlight['start_time']) ? $heroSpotlight['start_time'] : '19:00:00');
            $hsTEnd = isset($heroSpotlight['time_end']) ? $heroSpotlight['time_end'] : (isset($heroSpotlight['end_time']) ? $heroSpotlight['end_time'] : '22:00:00');
            $hsTime = substr($hsTStart, 0, 5) . ' - ' . substr($hsTEnd, 0, 5) . ' WIB';

            $hsDefaultWa = !empty($settings['whatsapp_default']) ? $settings['whatsapp_default'] : '081299887766';
            $hsPicContact = !empty($heroSpotlight['pic_whatsapp']) ? $heroSpotlight['pic_whatsapp'] : $hsDefaultWa;
            $hsHostName = !empty($heroSpotlight['host_name']) ? $heroSpotlight['host_name'] : (!empty($heroSpotlight['pic_name']) ? $heroSpotlight['pic_name'] : 'PIC Sesi');
            $hsCleanWa = preg_replace('/[^0-9]/', '', $hsPicContact);
            if (substr($hsCleanWa, 0, 1) === '0') $hsCleanWa = '62' . substr($hsCleanWa, 1);
            $hsGreeting = ($hsHostName !== 'PIC Sesi' && $hsHostName !== 'PIC Lapangan') ? "Kak {$hsHostName}" : "PIC Sesi";
            $hsDateInWa = !empty($hsDateFormatted) ? " ({$hsDateFormatted})" : "";
            $hsWaMsg = rawurlencode("Halo {$hsGreeting}, saya ingin mendaftar Mabar " . $settings['community_name'] . " untuk sesi " . $heroSpotlight['day_name'] . $hsDateInWa . " di " . $heroSpotlight['venue_name'] . ".");
            $hsWaUrl = "https://wa.me/" . $hsCleanWa . "?text=" . $hsWaMsg;
        ?>
        <div class="lg:col-span-5 w-full">
          <div class="relative bg-slate-900/85 backdrop-blur-xl border border-white/20 p-6 sm:p-7 shadow-2xl transition-transform hover:-translate-y-1 duration-300">
            
            <!-- Card Top Header -->
            <div class="flex items-center justify-between pb-3.5 border-b border-white/10 mb-4">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-amber-400">SESI MABAR TERDEKAT</span>
              </div>
              <span class="text-xs font-mono font-black text-amber-400 px-2.5 py-1 bg-amber-400/10 border border-amber-400/30">
                <?= $hsFeeFormatted ?>
              </span>
            </div>

            <!-- Match Venue & Day Details -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase <?= $hsWInfo['badge_class'] ?>">
                  <?= htmlspecialchars($hsWInfo['label']) ?>
                </span>
                <span class="text-xs font-mono font-bold text-slate-300 uppercase">
                  <?= htmlspecialchars($heroSpotlight['day_name']) ?><?= !empty($hsDateFormatted) ? ', ' . htmlspecialchars($hsDateFormatted) : '' ?>
                </span>
              </div>

              <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-tight">
                <?= htmlspecialchars($heroSpotlight['venue_name']) ?>
              </h3>

              <div class="text-xs text-slate-300 font-mono flex items-center gap-2 pt-1">
                <span class="text-amber-400">Jam Main:</span>
                <span><?= htmlspecialchars($hsTime) ?></span>
              </div>
            </div>

            <!-- Slots Remaining Indicator -->
            <div class="bg-black/50 border border-white/10 p-3.5 mb-5 flex items-center justify-between">
              <div>
                <span class="text-[11px] text-slate-400 uppercase tracking-wider font-mono">Status Slot</span>
                <div class="text-white font-mono font-bold text-sm">
                  <?= $hsSlotsLeft > 0 ? $hsSlotsLeft . ' Slot Tersedia' : 'Penuh (Waiting List)' ?>
                </div>
              </div>
              <span class="text-[10px] font-mono font-bold uppercase px-2.5 py-1 <?= $hsSlotsLeft > 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border border-rose-500/40' ?>">
                <?= $hsSlotsLeft > 0 ? 'Open Booking' : 'Full' ?>
              </span>
            </div>

            <!-- Direct Instant WhatsApp Booking Action -->
            <a href="<?= htmlspecialchars($hsWaUrl) ?>" target="_blank" rel="noopener noreferrer" class="w-full py-3.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider text-center block transition-all btn-tactile shadow-lg border border-amber-300">
              Daftar Sesi Ini Sekarang via WhatsApp →
            </a>

            <div class="mt-3.5 text-center">
              <a href="#jadwal" class="text-[11px] font-mono text-slate-400 hover:text-white transition-colors underline decoration-slate-600 underline-offset-4">
                Lihat Seluruh <?= count($upcomingSchedules) ?> Jadwal Tersedia ↓
              </a>
            </div>

          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </section>

  <!-- Section 1: Cara Ikut Mabar (3 Langkah Mudah) -->
  <section id="cara-ikut" class="py-16 bg-[#f8fafc] border-b border-slate-200 reveal-init">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-10">
        <p class="text-xs font-mono font-bold tracking-wider text-amber-600 uppercase mb-1">
          01 • CARA IKUT MABAR
        </p>
        <h2 class="text-3xl font-black text-slate-950 tracking-tight">
          3 Langkah Mudah Ikut Mabar
        </h2>
        <p class="text-slate-600 text-sm mt-2 font-normal">
          Tidak perlu repot mencari teman tanding. Anda bisa daftar sendirian maupun berdua dengan partner.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Step 01: Clickable Card that smoothly scrolls to #jadwal -->
        <a href="#jadwal" class="group block bg-white rounded-none border border-slate-200 p-6 shadow-xs hover:border-slate-400 transition-all relative">
          <div class="flex items-center justify-between mb-4">
            <div class="w-9 h-9 rounded-none bg-slate-950 text-amber-400 font-black text-sm flex items-center justify-center font-mono group-hover:bg-[#0b1e36] group-hover:text-amber-300 transition-colors">
              01
            </div>
            <span class="text-[10px] font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-none border border-slate-200">
              Pilih Jadwal &darr;
            </span>
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-2 group-hover:text-amber-600 transition-colors">
            <?= htmlspecialchars(!empty($settings['step_1_title']) ? $settings['step_1_title'] : 'Pilih Jadwal & Lokasi GOR') ?>
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed font-normal">
            <?= htmlspecialchars(!empty($settings['step_1_desc']) ? $settings['step_1_desc'] : 'Lihat daftar 4 sesi mingguan kami di bawah. Pilih hari, jam main, dan lokasi GOR yang paling dekat dengan domisili Anda.') ?>
          </p>
          <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-amber-600">
            <span>Lihat Jadwal Sesi</span>
            <span class="text-sm">&darr;</span>
          </div>
        </a>

        <!-- Step 02: WhatsApp PIC Contact -->
        <div class="bg-white rounded-none border border-slate-200 p-6 shadow-xs relative">
          <div class="flex items-center justify-between mb-4">
            <div class="w-9 h-9 rounded-none bg-slate-950 text-amber-400 font-black text-sm flex items-center justify-center font-mono">
              02
            </div>
            <span class="text-[10px] font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-none border border-slate-200">
              WhatsApp PIC
            </span>
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-2">
            <?= htmlspecialchars(!empty($settings['step_2_title']) ? $settings['step_2_title'] : 'Hubungi PIC & Bayar Iuran') ?>
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed font-normal">
            <?= htmlspecialchars(!empty($settings['step_2_desc']) ? $settings['step_2_desc'] : 'Klik link daftar pada jadwal untuk langsung terhubung dengan PIC sesi di WhatsApp guna konfirmasi slot dan menyelesaikan pembayaran iuran.') ?>
          </p>
          <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-mono text-slate-400">
            Konfirmasi Slot Cepat & Transparan
          </div>
        </div>

        <!-- Step 03: Play & Automatic Digital Rotation -->
        <div class="bg-white rounded-none border border-slate-200 p-6 shadow-xs relative">
          <div class="flex items-center justify-between mb-4">
            <div class="w-9 h-9 rounded-none bg-slate-950 text-amber-400 font-black text-sm flex items-center justify-center font-mono">
              03
            </div>
            <span class="text-[10px] font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-none border border-slate-200">
              Rotasi Digital
            </span>
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-2">
            <?= htmlspecialchars(!empty($settings['step_3_title']) ? $settings['step_3_title'] : 'Datang & Main Sesuai Rotasi') ?>
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed font-normal">
            <?= htmlspecialchars(!empty($settings['step_3_desc']) ? $settings['step_3_desc'] : 'Cukup bawa raket pribadi dan tiba di lapangan tepat waktu. Kok bulu angsa berkualitas dan rotasi ganda sudah diatur oleh sistem aplikasi.') ?>
          </p>
          <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-mono text-slate-400">
            Fasilitas Match & Rotasi Terkelola
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- Section 2: Jadwal Sesi Mabar Mingguan (Clean Sports Schedule Cards) -->
  <section id="jadwal" class="py-16 bg-white border-b border-slate-200 reveal-init">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div class="max-w-2xl">
          <p class="text-xs font-mono font-bold tracking-wider text-amber-600 uppercase mb-1">
            02 • JADWAL CABANG MABAR
          </p>
          <h2 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight">
            Pilih Sesi & Hubungi PIC WhatsApp
          </h2>
          <p class="text-slate-600 text-sm mt-1.5 font-normal">
            Pendaftaran dibuka setiap pekan. Hubungi PIC WhatsApp di bawah untuk memastikan sisa kuota pemain sebelum datang ke venue.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0">
          <?php if (!empty($dateRangeDisplay)): ?>
            <div class="text-xs font-mono font-bold text-slate-800 bg-amber-50 px-3.5 py-2 border border-amber-200 flex items-center gap-2">
              <span>Periode: <strong class="text-slate-950"><?= htmlspecialchars($dateRangeDisplay) ?></strong></span>
            </div>
          <?php endif; ?>
          <div class="text-xs font-mono font-semibold text-slate-700 bg-slate-50 px-3.5 py-2 rounded-none border border-slate-200 shrink-0">
            Status Kuota: <strong class="text-amber-600">Terbuka untuk Umum</strong>
          </div>
        </div>
      </div>

      <!-- Schedule Filter Bar: Tab Filter Pekan & Pencarian Rentang Tanggal -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-6 p-2.5 bg-slate-50 border border-slate-200">
        <!-- Week Filter Tabs (Clean Typography, Zero Icons) -->
        <div class="flex flex-wrap items-center gap-1.5">
          <button type="button" onclick="filterSchedules('all', this)" class="schedule-tab-btn active px-3.5 py-2 text-xs font-mono font-bold uppercase transition-all bg-[#0b1e36] text-white shadow-2xs">
            Semua Jadwal (<?= count($upcomingSchedules) ?>)
          </button>
          <button type="button" onclick="filterSchedules('this_week', this)" class="schedule-tab-btn px-3.5 py-2 text-xs font-mono font-bold uppercase transition-all bg-white hover:bg-slate-200 text-slate-800 border border-slate-200">
            Minggu Ini (<?= $countThisWeek ?>)
          </button>
          <button type="button" onclick="filterSchedules('next_week', this)" class="schedule-tab-btn px-3.5 py-2 text-xs font-mono font-bold uppercase transition-all bg-white hover:bg-slate-200 text-slate-800 border border-slate-200">
            Minggu Depan (<?= $countNextWeek ?>)
          </button>
          <?php if ($countUpcoming > 0): ?>
            <button type="button" onclick="filterSchedules('upcoming', this)" class="schedule-tab-btn px-3.5 py-2 text-xs font-mono font-bold uppercase transition-all bg-white hover:bg-slate-200 text-slate-800 border border-slate-200">
              Mendatang (<?= $countUpcoming ?>)
            </button>
          <?php endif; ?>
        </div>

        <!-- Date Range Search Filter (Dari Tanggal s/d Sampai Tanggal) -->
        <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-200">
          <span class="text-[11px] font-mono font-bold text-slate-500 uppercase tracking-wider shrink-0">Rentang Tanggal:</span>
          <div class="flex flex-wrap items-center gap-1.5">
            <input type="date" id="scheduleDateStart" onchange="filterScheduleByDateRange()" aria-label="Dari Tanggal" class="text-xs font-mono font-bold bg-white border border-slate-300 text-slate-900 px-2.5 py-1.5 rounded-none focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer shadow-2xs">
            <span class="text-xs font-mono text-slate-400 font-bold">s/d</span>
            <input type="date" id="scheduleDateEnd" onchange="filterScheduleByDateRange()" aria-label="Sampai Tanggal" class="text-xs font-mono font-bold bg-white border border-slate-300 text-slate-900 px-2.5 py-1.5 rounded-none focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer shadow-2xs">
            <button type="button" id="btnResetDateRange" onclick="resetScheduleFilters()" class="hidden px-2.5 py-1.5 text-[11px] font-mono font-bold uppercase bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 transition-colors">
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Schedule Cards (Structured, Crisp, Architectural Design) -->
      <div id="scheduleCardsGrid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($upcomingSchedules as $index => $sch): 
          $quota = isset($sch['max_slots']) ? (int)$sch['max_slots'] : (isset($sch['max_participants']) ? (int)$sch['max_participants'] : 16);
          $enrolled = isset($sch['participant_count']) ? (int)$sch['participant_count'] : 0;
          $slotsLeft = max(0, $quota - $enrolled);
          $fillPct = min(100, round(($enrolled / max(1, $quota)) * 100));

          $tStart = isset($sch['time_start']) ? $sch['time_start'] : (isset($sch['start_time']) ? $sch['start_time'] : '19:00:00');
          $tEnd = isset($sch['time_end']) ? $sch['time_end'] : (isset($sch['end_time']) ? $sch['end_time'] : '22:00:00');
          $timeRange = substr($tStart, 0, 5) . ' - ' . substr($tEnd, 0, 5) . ' WIB';
          $feeFormatted = 'Rp ' . number_format($sch['fee_per_person'], 0, ',', '.');
          
          $venueAddress = !empty($sch['venue_address']) ? $sch['venue_address'] : (!empty($sch['branch_area']) ? $sch['branch_area'] : (!empty($sch['title']) ? $sch['title'] : 'Jakarta'));
          $mapsUrl = !empty($sch['venue_maps_url']) ? $sch['venue_maps_url'] : (!empty($sch['maps_url']) ? $sch['maps_url'] : '');
          $defaultWa = !empty($settings['whatsapp_default']) ? $settings['whatsapp_default'] : '081299887766';
          $picContact = !empty($sch['pic_whatsapp']) ? $sch['pic_whatsapp'] : $defaultWa;
          $hostName = !empty($sch['host_name']) ? $sch['host_name'] : (!empty($sch['pic_name']) ? $sch['pic_name'] : 'PIC Sesi');

          // Week info & Formatted Calendar Date
          $rawSchDate = !empty($sch['schedule_date']) ? $sch['schedule_date'] : '';
          $wInfo = getScheduleWeekInfo($rawSchDate);
          $fullDateFormatted = !empty($rawSchDate) ? formatIndoDate($rawSchDate) : '';

          // Facilities items from CMS
          $facRaw = !empty($sch['facilities']) ? $sch['facilities'] : 'Karpet Vinyl BWF, Kok Bulu Angsa Berkualitas, Lampu LED Anti-Silau, Toilet & Kantin';
          $facItems = array_map('trim', explode(',', $facRaw));

          $cleanWa = preg_replace('/[^0-9]/', '', $picContact);
          if (substr($cleanWa, 0, 1) === '0') $cleanWa = '62' . substr($cleanWa, 1);
          $waGreeting = ($hostName !== 'PIC Sesi' && $hostName !== 'PIC Lapangan') ? "Kak {$hostName}" : "PIC Sesi";
          $dateInWa = !empty($fullDateFormatted) ? " ({$fullDateFormatted})" : "";
          $waMessage = rawurlencode("Halo {$waGreeting}, saya ingin mendaftar Mabar " . $settings['community_name'] . " untuk sesi " . $sch['day_name'] . $dateInWa . " di " . $sch['venue_name'] . ".");
          $waLink = "https://wa.me/" . $cleanWa . "?text=" . $waMessage;
        ?>
          <div class="schedule-card bg-white rounded-none border border-slate-200 hover:border-slate-400 shadow-xs transition-all p-5 sm:p-6 flex flex-col justify-between"
               data-week="<?= $wInfo['filter'] ?>"
               data-date="<?= htmlspecialchars($rawSchDate) ?>">
            <div>
              
              <!-- Top Badges & Live Quota Status -->
              <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div class="flex items-center flex-wrap gap-2">
                  <!-- Dynamic Countdown Badge: HARI INI / BESOK / X HARI LAGI -->
                  <span class="js-countdown-badge px-2.5 py-1 text-[11px] font-mono font-bold tracking-wider uppercase border <?= $wInfo['badge_class'] ?>">
                    <?= htmlspecialchars($wInfo['label']) ?>
                  </span>

                  <?php if (!empty($wInfo['week_label'])): ?>
                    <span class="px-2 py-1 text-[10px] font-mono font-bold tracking-wider uppercase bg-slate-100 text-slate-700 border border-slate-300">
                      <?= htmlspecialchars($wInfo['week_label']) ?>
                    </span>
                  <?php endif; ?>

                  <!-- Day Name Badge -->
                  <span class="px-2.5 py-1 rounded-none bg-slate-950 text-white text-[11px] font-mono font-bold tracking-wide uppercase">
                    <?= htmlspecialchars($sch['day_name']) ?>
                  </span>

                  <span class="text-[11px] font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-none border border-slate-200">
                    <?= htmlspecialchars($sch['court_count']) ?> LAPANGAN
                  </span>
                </div>

                <div>
                  <?php if ($slotsLeft <= 0): ?>
                    <span class="px-2.5 py-1 rounded-none bg-rose-50 text-rose-800 text-[11px] font-mono font-bold uppercase tracking-wider border border-rose-200">
                      FULL (<?= $quota ?>/<?= $quota ?>)
                    </span>
                  <?php else: ?>
                    <span class="px-2.5 py-1 rounded-none bg-amber-50 text-amber-600 text-[11px] font-mono font-bold border border-amber-200">
                      Sisa <?= $slotsLeft ?> Slot (<?= $enrolled ?>/<?= $quota ?>)
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Prominent Calendar Date Banner (Clean Typography without Unnecessary Icon) -->
              <?php if (!empty($fullDateFormatted)): ?>
                <div class="flex items-center justify-between text-xs sm:text-sm font-bold text-slate-900 mb-3 bg-amber-50/80 px-3 py-2 border-l-4 border-amber-500 border-y border-r border-amber-200">
                  <span class="font-mono tracking-tight text-slate-950 font-extrabold"><?= htmlspecialchars($fullDateFormatted) ?></span>
                </div>
              <?php endif; ?>

              <!-- Venue Header -->
              <h3 class="text-xl sm:text-2xl font-black text-slate-950 tracking-tight mb-1">
                <?= htmlspecialchars($sch['venue_name']) ?>
              </h3>
              <p class="text-xs text-slate-500 font-medium mb-4 line-clamp-1">
                <?= htmlspecialchars($venueAddress) ?>
              </p>

              <!-- Quota Progress Indicator -->
              <div class="bg-slate-50 rounded-none p-3.5 border border-slate-200 space-y-2 text-xs mb-4">
                <div class="flex justify-between items-center text-slate-600 font-mono text-[11px]">
                  <span>Status Kuota:</span>
                  <strong class="text-slate-900 font-bold"><?= $enrolled ?> / Maks <?= $quota ?> Pemain</strong>
                </div>
                <div class="w-full bg-slate-200 rounded-none h-1.5 overflow-hidden">
                  <div class="h-1.5 <?= $slotsLeft <= 0 ? 'bg-rose-600' : 'bg-amber-400' ?>" style="width: <?= $fillPct ?>%"></div>
                </div>

                <div class="pt-2 border-t border-slate-200 space-y-1.5">
                  <?php if (!empty($fullDateFormatted)): ?>
                    <div class="flex justify-between items-center text-slate-600 text-xs">
                      <span>Tanggal Main:</span>
                      <strong class="text-slate-900 font-bold"><?= htmlspecialchars($fullDateFormatted) ?></strong>
                    </div>
                  <?php endif; ?>
                  <div class="flex justify-between items-center text-slate-600 text-xs">
                    <span>Waktu Main:</span>
                    <strong class="text-slate-900 font-bold"><?= $timeRange ?></strong>
                  </div>
                  <div class="flex justify-between items-center text-slate-600 text-xs">
                    <span>Iuran & Kok:</span>
                    <strong class="text-amber-600 font-bold font-mono text-sm"><?= $feeFormatted ?> / orang</strong>
                  </div>
                  <div class="flex justify-between items-center text-slate-600 text-xs">
                    <span>PIC Sesi:</span>
                    <strong class="text-slate-900 font-mono font-bold"><?= htmlspecialchars($picContact) ?> (Host: <?= htmlspecialchars($hostName) ?>)</strong>
                  </div>
                </div>
              </div>

              <!-- Dynamic Facility Tags (Managed via CMS) -->
              <div class="mt-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5 font-mono">Fasilitas Venue:</span>
                <div class="flex flex-wrap gap-1.5 text-[11px] font-mono text-slate-700">
                  <?php foreach ($facItems as $fac): if (empty($fac)) continue; ?>
                    <span class="px-2 py-0.5 rounded-none bg-white border border-slate-200">
                      <?= htmlspecialchars($fac) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>

              <?php if (!empty($sch['notes'])): ?>
                <div class="mt-3 text-xs text-slate-600 bg-amber-50/50 p-2.5 rounded-none border border-slate-200 italic">
                  <?= htmlspecialchars($sch['notes']) ?>
                </div>
              <?php endif; ?>

            </div>

            <!-- Action WhatsApp Button -->
            <div class="mt-5 pt-3.5 border-t border-slate-100 space-y-2">
              <a href="<?= $waLink ?>" target="_blank" rel="noopener noreferrer" class="w-full min-h-[44px] py-3 px-4 rounded-none bg-[#0b1e36] hover:bg-slate-900 active:translate-y-0.5 text-white font-bold text-xs uppercase tracking-wider text-center flex items-center justify-center transition-all btn-tactile shadow-xs">
                Daftar via WhatsApp PIC Sesi →
              </a>

              <?php if (!empty($mapsUrl)): ?>
                <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="w-full min-h-[38px] py-2 px-3 rounded-none bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs text-center flex items-center justify-center transition-all border border-slate-200">
                  Lihat Lokasi GOR di Google Maps ↗
                </a>
              <?php endif; ?>
            </div>

          </div>
        <?php endforeach; ?>

        <!-- Empty State for filtered results -->
        <div id="scheduleEmptyState" class="hidden col-span-full py-12 text-center bg-slate-50 border border-dashed border-slate-300 p-8">
          <p class="text-slate-600 font-mono text-sm font-bold mb-2">Tidak ada sesi mabar pada filter tanggal yang dipilih.</p>
          <p class="text-slate-400 text-xs mb-4">Silakan pilih tab pekan lain atau lihat semua jadwal.</p>
          <button type="button" onclick="filterSchedules('all', document.querySelector('.schedule-tab-btn'))" class="px-4 py-2 bg-[#0b1e36] text-white text-xs font-mono font-bold uppercase hover:bg-slate-900 transition-all">
            Tampilkan Semua Jadwal
          </button>
        </div>
      </div>

    </div>
  </section>

  <!-- Section: Leaderboard Poin Showcase ("Banyak-Banyakan Poin") -->
  <section id="leaderboard" class="py-20 bg-white border-b border-slate-200 reveal-init">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
        <div class="max-w-2xl">
          <p class="text-xs font-mono font-bold tracking-wider text-amber-600 uppercase mb-1">
            03 • KLASEMEN BULANAN
          </p>
          <h2 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight mt-1">
            Top Scorers Bulan Ini
          </h2>
          <p class="text-slate-600 text-sm mt-2">
            Peringkat dihitung berdasarkan akumulasi <strong>total poin pertandingan</strong> yang dicetak pemain di setiap ronde mabar ("banyak-banyakan poin").
          </p>
        </div>

        <!-- Dual Scope Filter: Semua Sesi vs Per Sesi Mabar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider font-mono">Pilih Sesi:</label>
          <div class="relative w-full sm:w-auto">
            <select id="leaderboardSessionFilter" onchange="fetchLeaderboard(this.value)" class="w-full sm:w-auto bg-slate-50 hover:bg-slate-100 border border-slate-300 text-slate-900 text-xs font-bold rounded-none pl-4 pr-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all appearance-none cursor-pointer shadow-xs">
              <option value="0">Semua Sesi (Klasemen Overall)</option>
              <?php foreach ($allSchedules as $sch): ?>
                <option value="<?= $sch['id'] ?>"><?= htmlspecialchars($sch['day_name']) ?><?php if (!empty($sch['schedule_date'])): ?> (<?= date('d/m/Y', strtotime($sch['schedule_date'])) ?>)<?php endif; ?> — <?= htmlspecialchars($sch['venue_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 text-xs font-bold">
              ▼
            </div>
          </div>
        </div>
      </div>

      <!-- Leaderboard Table -->
      <div class="bg-white rounded-none border border-slate-200 shadow-sm overflow-hidden mb-8">
        <!-- Mobile Horizontal Scroll Cue -->
        <div class="sm:hidden px-4 py-2.5 bg-slate-50 border-b border-slate-200 text-[11px] text-slate-500 font-medium flex items-center justify-between">
          <span>Klasemen Poin Pemain</span>
          <span class="text-amber-600 font-bold flex items-center gap-1">
            <span>Geser detail</span>
            <span class="text-xs">➔</span>
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm font-sans min-w-[620px]">
            <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-4 px-6">Rank</th>
                <th class="py-4 px-6">Nama Pemain</th>
                <th class="py-4 px-6">Level</th>
                <th class="py-4 px-6 text-center">Tanding</th>
                <th class="py-4 px-6 text-center">Menang / Kalah</th>
                <th class="py-4 px-6 text-center">Win Rate</th>
                <th class="py-4 px-6 text-right text-amber-600 font-black">Total Poin</th>
              </tr>
            </thead>
            <tbody id="leaderboardBody" class="divide-y divide-slate-100">
              <?php 
                $activeScorers = array_filter($topScorers, function($s) {
                  return (int)$s['total_points'] > 0 || (int)$s['total_matches'] > 0;
                });
              ?>
              <?php if (empty($activeScorers)): ?>
                <tr>
                  <td colspan="7" class="py-12 px-6 text-center">
                    <div class="max-w-md mx-auto text-center space-y-2">
                      <div class="w-12 h-12 rounded-none bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-lg font-mono font-bold border border-slate-200">
                        
                      </div>
                      <h4 class="text-sm font-bold text-slate-800">Klasemen Bulan Ini Baru Dimulai</h4>
                      <p class="text-xs text-slate-500 leading-relaxed">
                        Belum ada pertandingan yang selesai dicatat. Peringkat dan akumulasi poin akan otomatis tampil di sini begitu PIC Sesi menginput skor di lapangan.
                      </p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($activeScorers as $rank => $scorer): 
                  $winRate = $scorer['total_matches'] > 0 ? round(($scorer['wins'] / $scorer['total_matches']) * 100) : 0;
                ?>
                  <tr class="hover:bg-slate-50 transition-colors">
                    <td class="py-4 px-6 font-extrabold text-base <?= $rank === 0 ? 'text-amber-500' : ($rank === 1 ? 'text-slate-600' : 'text-slate-400') ?>">
                      #<?= $rank + 1 ?>
                    </td>
                    <td class="py-4 px-6">
                      <span class="font-bold text-slate-900 text-base"><?= htmlspecialchars($scorer['name']) ?></span>
                    </td>
                    <td class="py-4 px-6">
                      <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-none <?= $scorer['level'] === 'Advance' ? 'bg-red-50 text-red-700 border border-red-200' : ($scorer['level'] === 'Beginner' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200') ?>">
                        <?= htmlspecialchars($scorer['level']) ?>
                      </span>
                    </td>
                    <td class="py-4 px-6 text-center font-mono font-semibold text-slate-600">
                      <?= $scorer['total_matches'] ?>
                    </td>
                    <td class="py-4 px-6 text-center font-mono font-semibold">
                      <span class="text-amber-600 font-bold"><?= $scorer['wins'] ?></span> / <span class="text-rose-600"><?= $scorer['losses'] ?></span>
                    </td>
                    <td class="py-4 px-6 text-center font-mono font-black text-slate-900">
                      <?= $winRate ?>%
                    </td>
                    <td class="py-4 px-6 text-right font-mono font-black text-lg text-amber-600">
                      <?= number_format($scorer['total_points']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-2">
          <span>Skor pertandingan diinput langsung oleh PIC Sesi saat mabar berlangsung.</span>
          <span class="font-medium text-amber-600">Poin terupdate otomatis ke papan klasemen</span>
        </div>
      </div>

      <!-- Formula Perhitungan Leaderboard (Transparan & Detail) -->
      <div class="bg-[#f8fafc] border border-slate-200 rounded-none p-6 sm:p-7">
        <div class="flex items-center gap-2.5 mb-4">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 font-mono">
            Standar & Rumus Perhitungan Poin Leaderboard
          </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-none p-5 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-600 mb-1 font-mono">
              01 • Poin Reli Murni
            </div>
            <div class="font-bold text-slate-900 text-sm mb-1.5">
              Akumulasi Setiap Angka
            </div>
            <p class="text-xs text-slate-600 leading-relaxed font-normal">
              Setiap poin reli yang dicetak tim di lapangan langsung masuk ke poin pemain. Misal game berakhir <strong>21–18</strong>, pemenang mendapat 21 poin dan pihak kalah tetap mengantongi 18 poin nyata.
            </p>
          </div>

          <div class="bg-white rounded-none p-5 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-600 mb-1 font-mono">
              02 • Win Bonus (+5 Poin)
            </div>
            <div class="font-bold text-slate-900 text-sm mb-1.5">
              Reward Kemenangan Ronde
            </div>
            <p class="text-xs text-slate-600 leading-relaxed font-normal">
              Sebagai apresiasi kompetitif, pasangan yang memenangkan ronde pertandingan memperoleh bonus kemenangan tambahan <strong>+5 poin</strong> yang dihitung otomatis oleh sistem rotasi.
            </p>
          </div>

          <div class="bg-white rounded-none p-5 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-600 mb-1 font-mono">
              03 • Pembeda Peringkat (Tie-Breaker)
            </div>
            <div class="font-bold text-slate-900 text-sm mb-1.5">
              Urutan Prioritas Seri
            </div>
            <p class="text-xs text-slate-600 leading-relaxed font-normal">
              Jika total poin antar pemain bernilai sama, urutan peringkat ditentukan secara adil melalui: <strong>1) Selisih Poin Reli (+/-)</strong> → <strong>2) Persentase Win Rate (%)</strong> → <strong>3) Total Kemenangan (Wins)</strong>.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- Section 5: Galeri Dokumentasi Foto Mabar -->
  <section id="galeri" class="py-20 bg-[#f8fafc] border-b border-slate-200 reveal-init">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div class="max-w-2xl">
          <p class="text-xs font-mono font-bold tracking-wider text-amber-600 uppercase mb-1">
            04 • DOKUMENTASI MABAR
          </p>
          <h2 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight mt-1">
            Keseruan dan Kebersamaan di Lapangan
          </h2>
          <p class="text-slate-600 text-sm mt-2">
            Klik salah satu foto dokumentasi untuk membuka arsip mabar berdasarkan tanggal pelaksanaan dan venue GOR.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <a href="gallery.php" target="_blank" class="inline-block px-5 py-2.5 rounded-none bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider hover:bg-amber-300 transition-all border border-amber-300 shadow-xs active:scale-95">
            Lihat Foto Dokumentasi (Buka Galeri) →
          </a>
          <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-block px-5 py-2.5 rounded-none bg-slate-950 text-white font-bold text-xs uppercase tracking-wider hover:bg-slate-800 transition-all shadow-xs">
            Instagram Resmi →
          </a>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
        <?php foreach ($gallery as $idx => $photo): 
          $rawDate = !empty($photo['activity_date']) ? $photo['activity_date'] : substr($photo['created_at'], 0, 10);
          $formattedDate = formatIndoDate($rawDate);
          $venueText = !empty($photo['venue_name']) ? $photo['venue_name'] : (!empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club');
          $cardSessionHash = !empty($photo['session_id']) ? Security::hashId((int)$photo['session_id']) : '';
        ?>
          <a href="gallery.php<?= !empty($cardSessionHash) ? '?session=' . urlencode($cardSessionHash) : '' ?>" 
             target="_blank"
             class="gallery-card group rounded-none overflow-hidden border border-slate-200 bg-white hover:border-amber-500 hover:shadow-lg transition-all flex flex-col cursor-pointer block"
             aria-label="Buka galeri foto dokumentasi mabar tanggal <?= htmlspecialchars($formattedDate) ?>">
            <div class="aspect-[4/3] overflow-hidden bg-slate-200 relative">
              <img src="<?= htmlspecialchars($photo['image_url']) ?>" alt="<?= htmlspecialchars($photo['title']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
              
              <!-- Subtle Hover Overlay -->
              <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-3">
                <span class="text-[11px] font-mono font-bold text-white bg-black/75 px-3 py-1.5 rounded-none border border-white/20 backdrop-blur flex items-center gap-1.5">
                  <span>Lihat Foto Dokumentasi</span>
                  <span>↗</span>
                </span>
              </div>

              <!-- Date Badge on Photo -->
              <div class="absolute top-2 left-2">
                <span class="text-[10px] font-mono font-bold text-slate-900 bg-white/95 px-2 py-0.5 rounded-none border border-slate-200 shadow-xs">
                  <?= htmlspecialchars($formattedDate) ?>
                </span>
              </div>
            </div>
            
            <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between">
              <div>
                <div class="text-[10px] font-mono font-bold text-amber-600 uppercase tracking-wider mb-1 truncate">
                  <?= htmlspecialchars($venueText) ?>
                </div>
                <h4 class="font-bold text-slate-900 text-xs sm:text-sm mb-1 truncate sm:whitespace-normal group-hover:text-amber-600 transition-colors">
                  <?= htmlspecialchars($photo['title']) ?>
                </h4>
                <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-1 sm:line-clamp-2">
                  <?= htmlspecialchars(!empty($photo['caption']) ? $photo['caption'] : 'Dokumentasi mabar mingguan.') ?>
                </p>
              </div>
              <div class="mt-2.5 pt-2.5 border-t border-slate-100 text-[10px] sm:text-[11px] font-mono text-amber-600 font-bold flex items-center justify-between">
                <span>Dokumentasi:</span>
                <span class="text-slate-700"><?= htmlspecialchars($formattedDate) ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- Section 6: FAQ Accordion (Clean Architectural Standard) -->
  <section id="faq" class="py-20 bg-white border-b border-slate-200 reveal-init">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center mb-12">
        <p class="text-xs font-mono font-bold tracking-wider text-amber-600 uppercase mb-1">
          05 • TANYA JAWAB (FAQ)
        </p>
        <h2 class="text-3xl font-black text-slate-950 tracking-tight mt-1">
          Tanya Jawab Seputar Mabar
        </h2>
        <p class="text-slate-600 text-sm mt-2">
          Hal-hal yang sering ditanyakan sebelum pertama kali bergabung main bersama <?= htmlspecialchars(!empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club') ?>.
        </p>
      </div>

      <div class="space-y-3">
        <?php
          $faqItems = [];
          if (!empty($settings['faq_json'])) {
              $decodedFaq = json_decode($settings['faq_json'], true);
              if (is_array($decodedFaq) && !empty($decodedFaq)) {
                  $faqItems = $decodedFaq;
              }
          }
          if (empty($faqItems)) {
              $faqItems = [
                  ['question' => 'Apakah pemain pemula (beginner) boleh ikut bergabung?', 'answer' => 'Sangat boleh! Komunitas kami terbuka untuk semua tingkatan dari Beginner, Intermediate, hingga Advance. Sistem rotasi digital kami akan membagi pasangan dan lawan secara berimbang agar reli tetap menyenangkan bagi semua orang.'],
                  ['question' => 'Bagaimana jika saya mendaftar sendirian tanpa pasangan ganda?', 'answer' => 'Mayoritas peserta kami mendaftar secara individu! Di lapangan GOR, Anda akan dipasangkan secara bergantian dengan peserta lain oleh sistem rotasi otomatis, sehingga Anda pasti mendapat partner di setiap ronde.'],
                  ['question' => 'Apakah saya perlu membawa shuttlecock sendiri?', 'answer' => 'Tidak perlu. Seluruh biaya iuran mabar sudah mencakup penyediaan shuttlecock bulu angsa berkualitas tinggi standar turnamen. Anda hanya perlu membawa raket dan mengenakan sepatu badminton non-marking.'],
                  ['question' => 'Bagaimana alur pembayaran iuran mabar?', 'answer' => 'Pembayaran dapat ditransfer via bank/QRIS setelah konfirmasi slot ke WhatsApp PIC sesi, atau diserahkan secara tunai saat tiba di GOR. PIC Sesi akan langsung menandai status Anda sebagai LUNAS di aplikasi operasional.']
              ];
          }
          foreach ($faqItems as $fi):
        ?>
        <div class="faq-card bg-white border border-slate-200 p-4 sm:p-5 cursor-pointer transition-all hover:border-[#0b1e36] min-h-[52px] select-none focus:outline-none focus:ring-1 focus:ring-[#0b1e36]" tabindex="0" role="button" aria-expanded="false" onclick="toggleFaq(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleFaq(this);}">
          <div class="flex items-center justify-between gap-4">
            <h3 class="text-xs sm:text-base font-bold text-slate-900 leading-snug">
              <?= htmlspecialchars($fi['question']) ?>
            </h3>
            <span class="faq-icon text-slate-500 font-bold transition-transform duration-300 shrink-0 text-xs sm:text-sm">▼</span>
          </div>
          <div class="faq-answer text-xs sm:text-sm text-slate-600 leading-relaxed">
            <?= nl2br(htmlspecialchars($fi['answer'])) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- Bottom CTA Banner (Clean Sports Club Standard) -->
  <section class="py-16 bg-[#0b1e36] text-white border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
      <div class="inline-block px-3 py-1 rounded-none bg-amber-400/10 border border-amber-400/30 text-amber-300 text-[11px] font-mono font-bold uppercase tracking-wider">
        GABUNG MABAR PEKAN INI
      </div>
      <h2 class="text-3xl sm:text-4xl font-black tracking-tight">
        Siap Smash dan Bermain Bersama Kami?
      </h2>
      <p class="text-slate-300 text-sm sm:text-base max-w-xl mx-auto font-normal">
        Hubungi PIC WhatsApp sesi mabar pilihan Anda sekarang untuk memastikan nama Anda masuk ke daftar pemain sebelum kuota penuh.
      </p>
      <div class="pt-2">
        <a href="#jadwal" class="px-7 py-3.5 rounded-none bg-amber-400 hover:bg-amber-300 text-slate-950 border border-amber-300 font-black text-xs uppercase tracking-wider transition-all shadow-md inline-block btn-tactile">
          Pilih Jadwal & Kontak WhatsApp PIC Sesi →
        </a>
      </div>
    </div>
  </section>

  <!-- Premium Enterprise Sports Footer (Clean, High Craftsmanship, Zero AI Slop) -->
  <footer class="bg-white border-t border-slate-200 pt-16 pb-12 text-slate-600 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Top Grid: 4 Thoughtful Columns -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-100">
        
        <!-- Col 1: Club Identity & Real World Profile (4 Cols) -->
        <div class="lg:col-span-4 space-y-4">
          <div class="flex items-center gap-3">
            <?php if (!empty($settings['logo_url'])): ?>
              <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo <?= htmlspecialchars($settings['community_name']) ?>" class="w-10 h-10 rounded-none object-contain border border-slate-200 bg-white shadow-xs">
            <?php else: ?>
              <div class="w-10 h-10 rounded-none bg-[#0b1e36] flex items-center justify-center text-white font-black text-base shadow-xs">
                PB
              </div>
            <?php endif; ?>
            <div>
              <div class="font-black text-lg tracking-tight text-slate-950 leading-none">
                <?= htmlspecialchars($settings['community_name']) ?>
              </div>
              <div class="text-[10px] font-bold text-amber-600 tracking-wider uppercase mt-0.5">
                Badminton Management System
              </div>
            </div>
          </div>
          
          <p class="text-xs text-slate-500 leading-relaxed font-normal max-w-sm">
            <?= nl2br(htmlspecialchars(!empty($settings['description']) ? $settings['description'] : ('Komunitas bulutangkis ganda ' . $baseCityName . ' dengan standar kok Bulu Angsa Berkualitas dan aplikasi rotasi lapangan mandiri. Berkomitmen menghadirkan sesi main bareng yang adil, disiplin waktu, dan transparan.'))) ?>
          </p>

          <div class="flex items-center gap-2 pt-1">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
            <span class="text-xs font-bold text-slate-800"><?= count(!empty($upcomingSchedules) ? $upcomingSchedules : (isset($allSchedules) ? $allSchedules : [])) ?> Sesi Mabar Aktif Setiap Pekan</span>
          </div>
        </div>

        <!-- Col 2: Navigasi & Alur Mabar (2 Cols) -->
        <div class="lg:col-span-2 space-y-3">
          <div class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">
            Navigasi Utama
          </div>
          <ul class="space-y-2.5 text-xs font-medium text-slate-600">
            <li><a href="#jadwal" class="hover:text-amber-600 transition-colors">Jadwal Mabar Mingguan</a></li>
            <li><a href="#cara-ikut" class="hover:text-amber-600 transition-colors">3 Langkah Ikut Mabar</a></li>
            <li><a href="#leaderboard" class="hover:text-amber-600 transition-colors">Top Scorers Leaderboard</a></li>
            <li><a href="#galeri" class="hover:text-amber-600 transition-colors">Dokumentasi</a></li>
            <li><a href="#faq" class="hover:text-amber-600 transition-colors">Tanya Jawab (FAQ)</a></li>
          </ul>
        </div>

        <!-- Col 3: Cabang & Lokasi GOR (3 Cols) -->
        <div class="lg:col-span-3 space-y-3">
          <div class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">
            Cabang GOR <?= htmlspecialchars($baseCityName) ?>
          </div>
          <ul class="space-y-2 text-xs text-slate-600">
            <?php 
              $footerSchedules = !empty($upcomingSchedules) ? $upcomingSchedules : (isset($allSchedules) ? $allSchedules : []);
              if (!empty($footerSchedules)): 
                foreach (array_slice($footerSchedules, 0, 5) as $sch): 
                  $mUrl = !empty($sch['venue_maps_url']) ? $sch['venue_maps_url'] : '#jadwal';
            ?>
              <li class="border-b border-slate-100/80 pb-1.5 last:border-none">
                <a href="<?= htmlspecialchars($mUrl) ?>" target="_blank" rel="noopener noreferrer" class="group flex items-start justify-between gap-2 hover:text-amber-600 transition-colors">
                  <div>
                    <strong class="font-bold text-slate-800 group-hover:text-amber-600 block text-xs">
                      <?= htmlspecialchars($sch['venue_name']) ?>
                    </strong>
                    <span class="text-[11px] text-slate-400 font-mono">
                      <?= htmlspecialchars($sch['day_name']) ?> • <?= htmlspecialchars($sch['court_count']) ?> Court
                    </span>
                  </div>
                  <span class="text-blue-700 text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity">↗</span>
                </a>
              </li>
            <?php endforeach; else: ?>
              <li class="text-xs text-slate-400 italic">Jadwal sesi cabang segera dibuka.</li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Col 4: Hubungi PIC & Info Komunitas (3 Cols) -->
        <div class="lg:col-span-3 space-y-3">
          <div class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">
            Registrasi & Informasi
          </div>
          
          <div class="space-y-2 text-xs">
            <a href="#jadwal" class="flex items-center justify-between p-3 rounded-none bg-amber-50 border border-amber-200 text-amber-600 font-bold hover:bg-amber-100 transition-colors">
              <span>Daftar Sesi via WhatsApp PIC</span>
              <span>→</span>
            </a>

            <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between p-3 rounded-none bg-slate-100 border border-slate-200 text-slate-800 font-bold hover:bg-slate-200 transition-colors">
              <span>Instagram @powershoot.badmin</span>
              <span class="text-blue-700 font-bold">↗</span>
            </a>

            <div class="pt-2 text-xs text-slate-500 leading-relaxed">
              Jadwal mabar dibuka rutin setiap pekan. Konfirmasi slot & pembayaran iuran langsung ke PIC Sesi masing-masing GOR.
            </div>
          </div>
        </div>

      </div>

      <!-- Bottom Bar: Legal, Standards & Community Identity -->
      <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-[11px] text-slate-400">
        <div class="flex flex-wrap items-center gap-4">
          <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['community_name']) ?>. Seluruh hak cipta dilindungi.</span>
          <span class="hidden sm:inline text-slate-300">•</span>
          <span class="text-slate-500 font-medium">Standar Kok Resmi: Bulu Angsa Berkualitas</span>
        </div>
        <div class="font-mono text-slate-400">
          <?= htmlspecialchars(!empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club') ?> • Fair Play & Community
        </div>
      </div>

    </div>
  </footer>

  <!-- Interactive JavaScript System (Mobile Drawer, Accordion, Scroll Reveal, Dynamic Leaderboard) -->
  <script>
    window.API_BASE = '<?= $baseDir ?>/api/';

    // 0. Cinematic Background Video Teaser Engine (Auto-Rotates badmin1 & badmin2 seamlessly)
    const bgTeaserVideo = document.getElementById('bgTeaserVideo');
    const bgSoundBtn = document.getElementById('bgSoundToggle');
    const bgSoundIcon = document.getElementById('bgSoundIcon');
    const bgSoundLabel = document.getElementById('bgSoundLabel');

    const videoTeaserSources = [
      '<?= $baseDir ?>/public/assets/videos/badmin1.mp4',
      '<?= $baseDir ?>/public/assets/videos/badmin2.mp4'
    ];
    let currentTeaserIndex = 0;
    let isTeaserMuted = true;

    if (bgTeaserVideo) {
      // Loop smoothly through badmin1 -> badmin2 -> badmin1
      bgTeaserVideo.addEventListener('ended', () => {
        currentTeaserIndex = (currentTeaserIndex + 1) % videoTeaserSources.length;
        bgTeaserVideo.style.opacity = '0.4';
        setTimeout(() => {
          bgTeaserVideo.src = videoTeaserSources[currentTeaserIndex];
          bgTeaserVideo.load();
          bgTeaserVideo.muted = isTeaserMuted;
          bgTeaserVideo.play().then(() => {
            bgTeaserVideo.style.opacity = '0.75';
          }).catch(() => {});
        }, 300);
      });
    }

    function toggleTeaserSound() {
      if (!bgTeaserVideo) return;
      isTeaserMuted = !isTeaserMuted;
      bgTeaserVideo.muted = isTeaserMuted;
      if (bgSoundIcon && bgSoundLabel) {
        if (isTeaserMuted) {
          bgSoundIcon.textContent = '🔇';
          bgSoundLabel.textContent = 'Audio Muted';
        } else {
          bgSoundIcon.textContent = '🔊';
          bgSoundLabel.textContent = 'Audio On';
        }
      }
    }

    // 1. Mobile Menu Drawer Toggle with Morphing Hamburger
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');

    if (mobileBtn && mobileDrawer) {
      mobileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = mobileBtn.classList.toggle('menu-open');
        mobileBtn.classList.toggle('hamburger-active', isOpen);
        mobileDrawer.classList.toggle('hidden', !isOpen);
      });

      // Close mobile drawer when clicking outside
      document.addEventListener('click', (e) => {
        if (!mobileDrawer.contains(e.target) && !mobileBtn.contains(e.target) && !mobileDrawer.classList.contains('hidden')) {
          closeMobileMenu();
        }
      });
    }

    function closeMobileMenu() {
      if (mobileBtn && mobileDrawer) {
        mobileBtn.classList.remove('menu-open', 'hamburger-active');
        mobileDrawer.classList.add('hidden');
      }
    }

    // 1b. Butter-Smooth Anchor Scrolling & Dynamic Sticky Header Elevation
    const mainHeader = document.getElementById('mainHeader');
    const sections = document.querySelectorAll('section[id]');
    const navItems = document.querySelectorAll('#desktopNavLinks .nav-item');

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (!targetId || targetId === '#') return;
        const targetElem = document.querySelector(targetId);
        if (targetElem) {
          e.preventDefault();
          const headerHeight = mainHeader ? mainHeader.offsetHeight : 70;
          const elementPosition = targetElem.getBoundingClientRect().top + window.pageYOffset;
          const offsetPosition = elementPosition - headerHeight;

          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });

          if (history.pushState) {
            history.pushState(null, null, targetId);
          }
        }
      });
    });

    function handleHeaderScroll() {
      if (!mainHeader) return;
      if (window.scrollY > 15) {
        mainHeader.classList.add('scrolled');
      } else {
        mainHeader.classList.remove('scrolled');
      }

      // Scrollspy active indicator
      const scrollPos = window.scrollY + 130;
      let activeId = '';
      sections.forEach(sec => {
        const top = sec.offsetTop;
        const h = sec.offsetHeight;
        if (scrollPos >= top && scrollPos < top + h) {
          activeId = sec.getAttribute('id');
        }
      });

      navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === '#' + activeId) {
          item.classList.add('active');
        } else {
          item.classList.remove('active');
        }
      });
    }

    window.addEventListener('scroll', handleHeaderScroll, { passive: true });
    handleHeaderScroll();

    // 2. Dynamic Dual-Scope Leaderboard (Overall & Per Sesi)
    function fetchLeaderboard(sessionId) {
      const tbody = document.getElementById('leaderboardBody');
      if (!tbody) return;

      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="py-12 px-6 text-center text-slate-400">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full mb-2"></div>
            <p class="text-xs font-semibold">Memuat data klasemen...</p>
          </td>
        </tr>
      `;

      fetch(`${window.API_BASE}?action=get_leaderboard&session_id=${encodeURIComponent(sessionId)}&limit=15`)
        .then(res => res.json())
        .then(res => {
          if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = `
              <tr>
                <td colspan="7" class="py-12 px-6 text-center">
                  <div class="max-w-md mx-auto text-center space-y-2">
                    <div class="w-12 h-12 rounded-none bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-lg font-mono font-bold border border-slate-200">
                      
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Belum Ada Pertandingan Selesai</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">
                      Sesi ini belum memiliki catatan skor pertandingan yang selesai. Begitu ronde dimainkan dan diinput skornya oleh PIC, peringkat poin akan otomatis tampil di sini.
                    </p>
                  </div>
                </td>
              </tr>
            `;
            return;
          }

          // Filter players who have played or have points
          const active = res.data.filter(p => parseInt(p.total_points || 0) > 0 || parseInt(p.total_matches || 0) > 0);
          if (active.length === 0) {
            tbody.innerHTML = `
              <tr>
                <td colspan="7" class="py-12 px-6 text-center">
                  <div class="max-w-md mx-auto text-center space-y-2">
                    <div class="w-12 h-12 rounded-none bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-lg font-mono font-bold border border-slate-200">
                      
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Klasemen Sesi Belum Ada Poin</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">
                      Belum ada poin reli yang terkumpul untuk sesi ini.
                    </p>
                  </div>
                </td>
              </tr>
            `;
            return;
          }

          let html = '';
          active.forEach((scorer, idx) => {
            const matches = parseInt(scorer.total_matches || 0);
            const wins = parseInt(scorer.wins || 0);
            const losses = parseInt(scorer.losses || 0);
            const points = parseInt(scorer.total_points || 0);
            const winRate = matches > 0 ? Math.round((wins / matches) * 100) : 0;
            const rankClass = idx === 0 ? 'text-amber-500 font-black' : (idx === 1 ? 'text-slate-600 font-extrabold' : 'text-slate-400 font-bold');
            
            let levelBadge = 'bg-blue-50 text-blue-700 border-blue-200';
            if (scorer.level === 'Advance') levelBadge = 'bg-red-50 text-red-700 border-red-200';
            if (scorer.level === 'Beginner') levelBadge = 'bg-amber-50 text-amber-600 border-amber-200';

            html += `
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="py-4 px-6 text-base ${rankClass}">
                  #${idx + 1}
                </td>
                <td class="py-4 px-6">
                  <span class="font-bold text-slate-900 text-base">${escapeHtml(scorer.name)}</span>
                </td>
                <td class="py-4 px-6">
                  <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-none border ${levelBadge}">
                    ${escapeHtml(scorer.level || 'Intermediate')}
                  </span>
                </td>
                <td class="py-4 px-6 text-center font-mono font-semibold text-slate-600">
                  ${matches}
                </td>
                <td class="py-4 px-6 text-center font-mono font-semibold">
                  <span class="text-amber-600 font-bold">${wins}</span> / <span class="text-rose-600">${losses}</span>
                </td>
                <td class="py-4 px-6 text-center font-mono font-black text-slate-900">
                  ${winRate}%
                </td>
                <td class="py-4 px-6 text-right font-mono font-black text-lg text-amber-600">
                  ${points.toLocaleString('id-ID')}
                </td>
              </tr>
            `;
          });

          tbody.innerHTML = html;
        })
        .catch(err => {
          tbody.innerHTML = `
            <tr>
              <td colspan="7" class="py-6 px-6 text-center text-xs text-rose-500 font-semibold">
                Gagal memuat data klasemen. Silakan muat ulang halaman.
              </td>
            </tr>
          `;
        });
    }

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // 3. FAQ Accordion Toggle (Accessible & Animated)
    function toggleFaq(card) {
      const isOpen = card.classList.contains('faq-open');
      // Close other accordions
      document.querySelectorAll('.faq-card').forEach(c => {
        c.classList.remove('faq-open');
        c.setAttribute('aria-expanded', 'false');
      });
      // Toggle current
      if (!isOpen) {
        card.classList.add('faq-open');
        card.setAttribute('aria-expanded', 'true');
      }
    }

    // 4. Scroll Reveal & Real-Time Countdown Badge Sync
    document.addEventListener('DOMContentLoaded', () => {
      const revealElements = document.querySelectorAll('.reveal-init');
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('reveal-active');
            obs.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.1,
        rootMargin: '0px 0px -40px 0px'
      });

      revealElements.forEach(el => observer.observe(el));

      // Sync Countdown Badge (Hari Ini / Besok / X Hari Lagi) with visitor's local device clock
      const nowLocal = new Date();
      const todayMidnight = new Date(nowLocal.getFullYear(), nowLocal.getMonth(), nowLocal.getDate());
      document.querySelectorAll('.schedule-card').forEach(card => {
        const rawDate = card.getAttribute('data-date');
        const badgeEl = card.querySelector('.js-countdown-badge');
        if (!rawDate || !badgeEl) return;
        const parts = rawDate.split('-');
        if (parts.length !== 3) return;
        const targetMidnight = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        const diffDays = Math.round((targetMidnight - todayMidnight) / 86400000);

        badgeEl.className = 'js-countdown-badge px-2.5 py-1 text-[11px] font-mono font-bold tracking-wider uppercase border ';
        if (diffDays === 0) {
          badgeEl.textContent = 'HARI INI';
          badgeEl.className += 'bg-rose-600 text-white border-rose-700';
        } else if (diffDays === 1) {
          badgeEl.textContent = 'BESOK';
          badgeEl.className += 'bg-amber-400 text-slate-950 border-amber-500';
        } else if (diffDays === 2) {
          badgeEl.textContent = '2 HARI LAGI';
          badgeEl.className += 'bg-emerald-600 text-white border-emerald-700';
        } else if (diffDays > 2) {
          badgeEl.textContent = diffDays + ' HARI LAGI';
          badgeEl.className += 'bg-blue-600 text-white border-blue-700';
        } else {
          badgeEl.textContent = 'SELESAI';
          badgeEl.className += 'bg-slate-100 text-slate-500 border-slate-300';
        }
      });
    });

    // 4b. Schedule Week & Date Range Dynamic Filtering
    function filterSchedules(weekType, btnElement) {
      // Update Tab Buttons UI
      document.querySelectorAll('.schedule-tab-btn').forEach(btn => {
        btn.classList.remove('bg-[#0b1e36]', 'text-white', 'shadow-2xs');
        btn.classList.add('bg-white', 'text-slate-800', 'border-slate-200');
      });
      if (btnElement) {
        btnElement.classList.remove('bg-white', 'text-slate-800', 'border-slate-200');
        btnElement.classList.add('bg-[#0b1e36]', 'text-white', 'shadow-2xs');
      }

      // Reset date range inputs
      const startInput = document.getElementById('scheduleDateStart');
      const endInput = document.getElementById('scheduleDateEnd');
      const resetBtn = document.getElementById('btnResetDateRange');
      if (startInput) startInput.value = '';
      if (endInput) endInput.value = '';
      if (resetBtn) resetBtn.classList.add('hidden');

      let visibleCount = 0;
      document.querySelectorAll('.schedule-card').forEach(card => {
        const cardWeek = card.getAttribute('data-week');
        if (weekType === 'all' || cardWeek === weekType) {
          card.style.display = 'flex';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      const emptyState = document.getElementById('scheduleEmptyState');
      if (emptyState) {
        if (visibleCount === 0) {
          emptyState.classList.remove('hidden');
        } else {
          emptyState.classList.add('hidden');
        }
      }
    }

    function filterScheduleByDateRange() {
      const startInput = document.getElementById('scheduleDateStart');
      const endInput = document.getElementById('scheduleDateEnd');
      const resetBtn = document.getElementById('btnResetDateRange');
      const startDate = startInput ? startInput.value : '';
      const endDate = endInput ? endInput.value : '';

      if (!startDate && !endDate) {
        resetScheduleFilters();
        return;
      }

      if (resetBtn) resetBtn.classList.remove('hidden');

      // Reset active tabs highlight when using custom date range
      document.querySelectorAll('.schedule-tab-btn').forEach(btn => {
        btn.classList.remove('bg-[#0b1e36]', 'text-white', 'shadow-2xs');
        btn.classList.add('bg-white', 'text-slate-800', 'border-slate-200');
      });

      let visibleCount = 0;
      document.querySelectorAll('.schedule-card').forEach(card => {
        const cardDate = card.getAttribute('data-date') || '';
        let match = true;
        if (startDate && cardDate < startDate) match = false;
        if (endDate && cardDate > endDate) match = false;

        if (match) {
          card.style.display = 'flex';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      const emptyState = document.getElementById('scheduleEmptyState');
      if (emptyState) {
        if (visibleCount === 0) {
          emptyState.classList.remove('hidden');
        } else {
          emptyState.classList.add('hidden');
        }
      }
    }

    function resetScheduleFilters() {
      const firstBtn = document.querySelector('.schedule-tab-btn');
      filterSchedules('all', firstBtn);
    }

    // 5. Documentation Gallery Lightbox Modal
    const galleryItems = <?= json_encode(array_values(array_map(function($p) use ($settings) {
        $rawDate = !empty($p['activity_date']) ? $p['activity_date'] : substr($p['created_at'], 0, 10);
        return [
            'id' => (int)$p['id'],
            'title' => $p['title'],
            'image_url' => $p['image_url'],
            'caption' => !empty($p['caption']) ? $p['caption'] : 'Foto dokumentasi sesi mabar.',
            'date_formatted' => formatIndoDate($rawDate),
            'venue_name' => !empty($p['venue_name']) ? $p['venue_name'] : (!empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club'),
            'day_name' => !empty($p['day_name']) ? $p['day_name'] : ''
        ];
    }, $gallery))) ?>;

    let currentPhotoIdx = 0;
    const modalEl = document.getElementById('photoLightboxModal');

    function openPhotoModal(idx) {
      if (!galleryItems || galleryItems.length === 0) return;
      currentPhotoIdx = idx >= 0 && idx < galleryItems.length ? idx : 0;
      renderPhotoModal();
      if (modalEl) {
        modalEl.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      }
    }

    function closePhotoModal(e) {
      if (e && e.target !== modalEl && !e.target.closest('button[onclick="closePhotoModal()"]')) return;
      if (modalEl) {
        modalEl.classList.add('hidden');
        document.body.style.overflow = '';
      }
    }

    function renderPhotoModal() {
      const item = galleryItems[currentPhotoIdx];
      if (!item) return;

      const imgEl = document.getElementById('modalImg');
      const titleEl = document.getElementById('modalTitle');
      const venueEl = document.getElementById('modalVenue');
      const dateBadgeEl = document.getElementById('modalDateBadge');
      const infoEl = document.getElementById('modalScheduleInfo');
      const captionEl = document.getElementById('modalCaption');
      const counterEl = document.getElementById('modalCounter');

      if (imgEl) imgEl.src = item.image_url;
      if (titleEl) titleEl.textContent = `Dokumentasi Mabar — ${item.date_formatted}`;
      if (venueEl) venueEl.textContent = item.venue_name ? `${item.venue_name} ${item.day_name ? '• ' + item.day_name : ''}` : 'DOKUMENTASI MABAR';
      if (dateBadgeEl) dateBadgeEl.textContent = `${item.date_formatted}`;
      if (infoEl) infoEl.textContent = item.title;
      if (captionEl) captionEl.textContent = item.caption;
      if (counterEl) counterEl.textContent = `Foto ${currentPhotoIdx + 1} dari ${galleryItems.length}`;
    }

    function prevPhoto() {
      if (!galleryItems.length) return;
      currentPhotoIdx = (currentPhotoIdx - 1 + galleryItems.length) % galleryItems.length;
      renderPhotoModal();
    }

    function nextPhoto() {
      if (!galleryItems.length) return;
      currentPhotoIdx = (currentPhotoIdx + 1) % galleryItems.length;
      renderPhotoModal();
    }

    // Keyboard support for Lightbox Modal (Escape, ArrowLeft, ArrowRight)
    document.addEventListener('keydown', (e) => {
      if (!modalEl || modalEl.classList.contains('hidden')) return;
      if (e.key === 'Escape') closePhotoModal();
      if (e.key === 'ArrowLeft') prevPhoto();
      if (e.key === 'ArrowRight') nextPhoto();
    });
  </script>

  <!-- Interactive Documentation Modal (Lightbox) -->
  <div id="photoLightboxModal" class="hidden fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-3 sm:p-6" onclick="closePhotoModal(event)">
    <div class="bg-white rounded-none max-w-3xl w-full max-h-[92vh] overflow-hidden flex flex-col shadow-2xl border border-slate-700" onclick="event.stopPropagation()">
      
      <!-- Modal Header -->
      <div class="px-4 sm:px-6 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
        <div>
          <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-amber-400" id="modalVenue">
            DOKUMENTASI MABAR RESMI
          </div>
          <h3 class="text-sm sm:text-base font-black tracking-tight text-white mt-0.5" id="modalTitle">
            Dokumentasi Mabar
          </h3>
        </div>
        <button type="button" onclick="closePhotoModal()" aria-label="Tutup" class="w-8 h-8 rounded-none bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-sm font-bold transition-colors">
          &times;
        </button>
      </div>

      <!-- Modal Body -->
      <div class="flex-1 overflow-y-auto">
        <div class="relative bg-slate-950 flex items-center justify-center max-h-[55vh] sm:max-h-[60vh] overflow-hidden select-none">
          <img id="modalImg" src="" alt="Dokumentasi Mabar" class="max-h-[55vh] sm:max-h-[60vh] w-auto max-w-full object-contain">
          
          <button type="button" onclick="prevPhoto()" aria-label="Foto Sebelumnya" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/90 text-white text-base font-bold flex items-center justify-center backdrop-blur transition-all">
            ‹
          </button>
          <button type="button" onclick="nextPhoto()" aria-label="Foto Selanjutnya" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/90 text-white text-base font-bold flex items-center justify-center backdrop-blur transition-all">
            ›
          </button>
        </div>

        <div class="p-4 sm:p-5 bg-white space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-none bg-amber-50 border border-amber-200 text-amber-600 font-mono font-bold text-xs" id="modalDateBadge">
                Tanggal Mabar
              </span>
              <span class="text-xs text-slate-500 font-medium font-mono" id="modalScheduleInfo"></span>
            </div>
            <span class="text-[11px] font-mono text-slate-400" id="modalCounter"></span>
          </div>

          <p class="text-xs sm:text-sm text-slate-700 leading-relaxed font-normal" id="modalCaption"></p>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-4 sm:px-6 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
        <span class="text-[11px] text-slate-400">Diupload oleh Admin / PIC Sesi Lapangan</span>
        <div class="flex items-center gap-2">
          <button type="button" onclick="prevPhoto()" class="px-3 py-1.5 rounded-none bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold transition-colors">
            ← Sebelumnya
          </button>
          <button type="button" onclick="nextPhoto()" class="px-3 py-1.5 rounded-none bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold transition-colors">
            Berikutnya →
          </button>
        </div>
      </div>

    </div>
  </div>

</body>
</html>
