<?php
// app/index.php
// Powershoot Badminton — Mobile-First Courtside Operational App
// Multi-Session Isolation: PIC accounts are locked strictly to their designated session
// Super-Lite UX: Designed for fast one-thumb smartphone operation at the venue

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../models/Community.php';
require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/Participant.php';
require_once __DIR__ . '/../models/MatchRecord.php';
require_once __DIR__ . '/../models/Finance.php';
require_once __DIR__ . '/../core/RotationEngine.php';

Auth::init();

// Global URL Sanitizer: Strictly strip any raw ?session_id= or ?id= from browser URL
if (isset($_GET['session_id']) || isset($_GET['id'])) {
    $rawParam = isset($_GET['session_id']) ? $_GET['session_id'] : $_GET['id'];
    $resolvedId = is_numeric($rawParam) ? (int)$rawParam : Security::decodeId((string)$rawParam);
    if ($resolvedId > 0) {
        $_SESSION['courtside_session_id'] = $resolvedId;
    }
    $cleanView = isset($_GET['view']) ? $_GET['view'] : 'court';
    header("Location: ?view={$cleanView}", true, 301);
    exit;
}

// Clean Session Switch via Cryptographic Token (auto-redirects back to clean URL)
if (isset($_GET['switch'])) {
    $decoded = Security::decodeId((string)$_GET['switch']);
    if ($decoded > 0) {
        $_SESSION['courtside_session_id'] = $decoded;
    }
    $cleanView = isset($_GET['view']) ? $_GET['view'] : 'court';
    header("Location: ?view={$cleanView}", true, 302);
    exit;
}

// Helper: Format Indonesian Date
if (!function_exists('formatIndoDate')) {
    function formatIndoDate($dateStr) {
        if (empty($dateStr)) return '-';
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $ts = strtotime($dateStr);
        if (!$ts) return $dateStr;
        $d = date('j', $ts);
        $m = (int)date('n', $ts);
        $y = date('Y', $ts);
        return $d . ' ' . (isset($months[$m]) ? $months[$m] : '') . ' ' . $y;
    }
}

// Dynamic Base Directories
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
if (substr($baseDir, -4) === '/app') $baseDir = substr($baseDir, 0, -4);

$appUrl = $baseDir . '/app/';
$apiUrl = $baseDir . '/api/';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    header("Location: index.php");
    exit;
}

// Handle Login
$loginError = null;
$reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
if ($reqMethod === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (Auth::attempt($username, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $loginError = "Username / No. WhatsApp atau password salah.";
    }
}

$isLoggedIn = Auth::check();
$user = Auth::user();
$settings = Community::getSettings();
$allSchedules = Schedule::all();

$brandName = !empty($settings['community_name']) ? trim($settings['community_name']) : 'Badminton Club';
$brandWords = preg_split('/\s+/', $brandName);
$brandInitials = strtoupper(substr($brandWords[0] ?? 'B', 0, 1) . substr($brandWords[1] ?? ($brandWords[0] ?? 'C'), 0, 1));

// ==========================================
// 1. RENDER LOGIN SCREEN IF NOT AUTHENTICATED
// ==========================================
if (!$isLoggedIn):
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Login PIC Lapangan — <?= htmlspecialchars($settings['community_name']) ?></title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  
  <style>
    * { font-stretch: normal !important; -webkit-tap-highlight-color: transparent; }
    body, input, button { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important; }
  </style>
</head>
<body class="bg-[#071324] text-slate-100 min-h-screen flex flex-col justify-center items-center p-4">

  <div class="w-full max-w-sm bg-[#0b1e36] border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-6">
    
    <!-- Brand Icon & App Title -->
    <div class="text-center space-y-2">
      <?php if (!empty($settings['logo_url'])): ?>
        <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="w-14 h-14 object-contain bg-white p-1 mx-auto border border-amber-400/40">
      <?php else: ?>
        <div class="w-14 h-14 bg-[#071324] border border-amber-400/40 flex items-center justify-center text-amber-400 font-black text-xl mx-auto shadow-sm">
          <?= htmlspecialchars($brandInitials) ?>
        </div>
      <?php endif; ?>
      <div>
        <h1 class="text-lg font-black text-white tracking-tight uppercase">
          <?= htmlspecialchars($settings['community_name']) ?>
        </h1>
        <p class="text-xs font-semibold text-amber-400 font-mono tracking-wider font-bold">
          APLIKASI LAPANGAN & PIC SESI
        </p>
      </div>
    </div>

    <?php if ($loginError): ?>
      <div class="p-3.5 bg-rose-950/80 border border-rose-800 text-rose-300 text-xs font-bold text-center">
        <?= htmlspecialchars($loginError) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
      <input type="hidden" name="form_action" value="login">

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5 font-mono">
          Username / WhatsApp:
        </label>
        <input type="text" name="username" required autocomplete="username" placeholder="Username PIC atau nomor HP..." class="w-full bg-slate-900 border border-slate-700 text-white px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400 transition-all placeholder:text-slate-500">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5 font-mono">
          Password:
        </label>
        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 text-white px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400 transition-all placeholder:text-slate-500">
      </div>

      <button type="submit" class="w-full py-3.5 bg-amber-400 hover:bg-amber-300 active:scale-[0.98] text-slate-950 font-black text-xs uppercase tracking-wider transition-all border border-amber-300">
        Masuk ke Sistem Lapangan →
      </button>
    </form>

    <div class="pt-4 border-t border-slate-800 text-center text-[11px] text-slate-400">
      Akun PIC sesi dibuat oleh Admin melalui CMS.<br>
      Sesi mabar Anda akan otomatis terkunci saat login.
    </div>

  </div>

</body>
</html>
<?php
exit;
endif;

// ==========================================
// 2. AUTHENTICATED SESSION CONTEXT RESOLUTION
// ==========================================
$view = isset($_GET['view']) ? $_GET['view'] : 'court';
$isPic = Auth::isPic();
$isAdmin = Auth::isAdmin();
$isPicFoto = Auth::isPicFoto();

// Restrict pic_foto strictly to documentation view
if ($isPicFoto) {
    $view = 'documentation';
}

// Multi-Session Isolation Logic (Zero URL ID Exposure):
if ($isPic) {
    $assignedId = Auth::assignedSessionId();
    $sessionId = ($assignedId && $assignedId > 0) ? $assignedId : 0;
    $_SESSION['courtside_session_id'] = $sessionId;
} elseif ($isPicFoto) {
    $assignedId = Auth::assignedSessionId();
    $sessStored = isset($_SESSION['courtside_session_id']) ? (int)$_SESSION['courtside_session_id'] : 0;
    if ($sessStored > 0) {
        $sessionId = $sessStored;
    } elseif ($assignedId && $assignedId > 0) {
        $sessionId = $assignedId;
    } else {
        $sessionId = !empty($allSchedules) ? (int)$allSchedules[0]['id'] : 0;
    }
    $_SESSION['courtside_session_id'] = $sessionId;
} else {
    $sessStored = isset($_SESSION['courtside_session_id']) ? (int)$_SESSION['courtside_session_id'] : 0;
    if ($sessStored > 0) {
        $sessionId = $sessStored;
    } else {
        $sessionId = !empty($allSchedules) ? (int)$allSchedules[0]['id'] : 0;
        $_SESSION['courtside_session_id'] = $sessionId;
    }
}

$currentSession = $sessionId > 0 ? Schedule::find($sessionId) : null;

// Fetch active session data
$participants = [];
$matches = [];
$latestRound = 1;
$activeRoundMatches = [];
$completedMatches = [];
$restingPlayerIds = [];
$paymentSummary = [
    'total_registered' => 0,
    'total_present' => 0,
    'total_waiting_list' => 0,
    'total_confirmed' => 0,
    'total_paid' => 0,
    'total_unpaid' => 0,
    'total_cash_collected' => 0,
    'total_cash_pending' => 0
];

if ($currentSession) {
    // Handle Documentation Upload / Delete Actions by Session PIC
    $appMessage = null;
    $appError = null;
    if ($reqMethod === 'POST' && isset($_POST['form_action'])) {
        if ($_POST['form_action'] === 'add_documentation') {
            $docTitle = trim(isset($_POST['title']) ? $_POST['title'] : '');
            $docCaption = trim(isset($_POST['caption']) ? $_POST['caption'] : '');
            $docDate = !empty($_POST['activity_date']) ? $_POST['activity_date'] : date('Y-m-d');
            $docSessionId = (int)$sessionId;
            $uploadedCount = 0;
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $targetDir = __DIR__ . '/../public/uploads/gallery/';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0777, true);
            }

            if (empty($docTitle)) {
                $docTitle = "Dokumentasi Mabar " . ($currentSession ? $currentSession['venue_name'] : '');
            }

            // 1. Handle Multiple Photo Files (photo_files[])
            if (isset($_FILES['photo_files']) && is_array($_FILES['photo_files']['name'])) {
                $totalFiles = count($_FILES['photo_files']['name']);
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['photo_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $origName = $_FILES['photo_files']['name'][$i];
                        $tmpName = $_FILES['photo_files']['tmp_name'][$i];
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            $filename = 'mabar_' . $docSessionId . '_' . date('Ymd_His') . '_' . $i . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                            $targetFile = $targetDir . $filename;
                            if (move_uploaded_file($tmpName, $targetFile)) {
                                $imgUrl = $baseDir . '/public/uploads/gallery/' . $filename;
                                $subTitle = $totalFiles > 1 ? "{$docTitle} (#" . ($uploadedCount + 1) . ")" : $docTitle;
                                Community::addGalleryPhoto($subTitle, $imgUrl, $docCaption, $docSessionId, $docDate);
                                $uploadedCount++;
                            }
                        }
                    }
                }
            }

            // 2. Fallback: Single Photo File (photo_file)
            if ($uploadedCount === 0 && isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = 'mabar_' . $docSessionId . '_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                    $targetFile = $targetDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        $imgUrl = $baseDir . '/public/uploads/gallery/' . $filename;
                        Community::addGalleryPhoto($docTitle, $imgUrl, $docCaption, $docSessionId, $docDate);
                        $uploadedCount++;
                    }
                }
            }

            // 3. Fallback: Direct Image URL
            $imageUrl = trim(isset($_POST['image_url']) ? $_POST['image_url'] : '');
            if ($uploadedCount === 0 && !empty($imageUrl)) {
                Community::addGalleryPhoto($docTitle, $imageUrl, $docCaption, $docSessionId, $docDate);
                $uploadedCount++;
            }

            if ($uploadedCount > 0) {
                $appMessage = "Berhasil mengunggah {$uploadedCount} foto dokumentasi mabar! Langsung tayang di Landing Page publik.";
            } elseif (!$appError) {
                $appError = "Pilih 1 atau beberapa file foto dari kamera/galeri HP (bisa pilih banyak sekaligus) atau masukkan URL gambar.";
            }
        } elseif ($_POST['form_action'] === 'delete_documentation') {
            $photoId = (int)$_POST['photo_id'];
            Community::deleteGalleryPhoto($photoId);
            $appMessage = "Foto dokumentasi mabar berhasil dihapus.";
        } elseif ($_POST['form_action'] === 'complete_session') {
            Schedule::completeSession($sessionId);
            $appMessage = "Sesi mabar berhasil diflag selesai! Rekapitulasi kas dan riwayat pertandingan telah dicatat di sistem.";
            $currentSession = Schedule::find($sessionId);
        } elseif ($_POST['form_action'] === 'add_session_finance') {
            $amount = (int)preg_replace('/[^0-9]/', '', (string)($_POST['amount'] ?? ''));
            $title = trim((string)($_POST['title'] ?? ''));
            $allowedCats = ['Sewa Lapangan', 'Shuttlecock', 'Konsumsi', 'Honor PIC', 'Iuran Tambahan', 'Sponsor', 'Peralatan', 'Lain-lain'];
            $category = in_array($_POST['category'] ?? '', $allowedCats, true) ? $_POST['category'] : 'Lain-lain';
            if ($amount > 0 && $title !== '') {
                Finance::create([
                    'session_id' => $sessionId,
                    'type' => ($_POST['type'] ?? 'expense') === 'income' ? 'income' : 'expense',
                    'category' => $category,
                    'amount' => $amount,
                    'title' => mb_substr($title, 0, 150),
                    'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
                    'record_date' => !empty($currentSession['schedule_date']) ? $currentSession['schedule_date'] : date('Y-m-d'),
                    'created_by' => Auth::id()
                ]);
                $_SESSION['courtside_flash_msg'] = "Catatan kas \"{$title}\" berhasil disimpan.";
            }
            header("Location: ?view=payments#biaya", true, 303);
            exit;
        } elseif ($_POST['form_action'] === 'delete_session_finance') {
            $recId = Security::decodeId((string)($_POST['record_ref'] ?? ''));
            if ($recId > 0) {
                Finance::delete($recId);
                $_SESSION['courtside_flash_msg'] = "Catatan transaksi kas berhasil dihapus.";
            }
            header("Location: ?view=payments#biaya", true, 303);
            exit;
        }
    }

    if (!empty($_SESSION['courtside_flash_msg'])) {
        $appMessage = $_SESSION['courtside_flash_msg'];
        unset($_SESSION['courtside_flash_msg']);
    }

    $participants = Participant::getBySession($sessionId);
    $matches = MatchRecord::getBySession($sessionId);
    $paymentSummary = Participant::getPaymentSummary($sessionId);
    $sessionPhotos = Community::getGallery(50, $sessionId);
    $sessionFinSummary = Finance::getSummary($sessionId);
    $sessionFinRecords = Finance::getAll(['session_id' => $sessionId]);

    if (!empty($matches)) {
        foreach ($matches as $m) {
            if ($m['round_number'] > $latestRound) $latestRound = (int)$m['round_number'];
        }
        $activeRoundMatches = array_filter($matches, function($m) use ($latestRound) {
            return $m['round_number'] == $latestRound;
        });
        $completedMatches = array_filter($matches, function($m) {
            return $m['status'] === 'completed';
        });

        $playingIds = [];
        foreach ($activeRoundMatches as $arm) {
            $playingIds[] = (int)$arm['team_a_p1'];
            $playingIds[] = (int)$arm['team_a_p2'];
            $playingIds[] = (int)$arm['team_b_p1'];
            $playingIds[] = (int)$arm['team_b_p2'];
        }

        foreach ($participants as $p) {
            if ($p['is_present'] == 1 && !in_array((int)$p['player_id'], $playingIds)) {
                $restingPlayerIds[] = $p;
            }
        }
    }
} else {
    $sessionPhotos = [];
    $sessionFinSummary = ['total_iuran_collected' => 0, 'total_iuran_pending' => 0, 'count_paid' => 0, 'count_unpaid' => 0, 'total_extra_income' => 0, 'total_income' => 0, 'total_expense' => 0, 'net_cashflow' => 0];
    $sessionFinRecords = [];
}

// Session Leaderboard for Klasemen tab
$sessionLeaderboard = Player::getMonthlyLeaderboard(null, $sessionId, 50);

// Group participants: confirmed vs waiting_list
$confirmedParticipants = array_filter($participants, function($p) {
    return ($p['status'] ?? 'confirmed') !== 'waiting_list';
});
$waitingListParticipants = array_filter($participants, function($p) {
    return ($p['status'] ?? 'confirmed') === 'waiting_list';
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= htmlspecialchars($currentSession ? $currentSession['venue_name'] : 'Operasional Lapangan') ?> — <?= htmlspecialchars($brandName) ?></title>
  
  <!-- PWA & Mobile WebApp Capabilities -->
  <link rel="manifest" href="<?= $baseDir ?>/manifest.json">
  <meta name="theme-color" content="#0b1e36">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($brandName) ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(!empty($settings['logo_url']) ? $settings['logo_url'] : ($baseDir . '/public/uploads/powershoot_logo.jpg')) ?>">

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  
  <style>
    * { font-stretch: normal !important; -webkit-tap-highlight-color: transparent; }
    body, h1, h2, h3, h4, p, span, button, input, select, textarea {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      letter-spacing: -0.015em;
    }
    .pb-safe {
      padding-bottom: env(safe-area-inset-bottom, 16px);
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace !important;
    }
  </style>
  <script>
    window.API_BASE = '<?= $apiUrl ?>';
    window.CURRENT_SESSION_ID = <?= (int)$sessionId ?>;
  </script>
</head>
<body class="bg-[#f8fafc] text-slate-900 min-h-screen flex flex-col antialiased pb-24">

  <!-- ========================================== -->
  <!-- COMPACT STICKY APP HEADER (ANDROID OPTIMIZED) -->
  <!-- ========================================== -->
  <header class="bg-[#0b1e36] text-white border-b border-slate-800 sticky top-0 z-30 shadow-md">
    <!-- Tier 1: App Identity, Role & Logout -->
    <div class="max-w-5xl mx-auto px-3.5 py-2 flex items-center justify-between gap-2 border-b border-slate-800/80">
      <div class="flex items-center gap-2 min-w-0">
        <?php if (!empty($settings['logo_url'])): ?>
          <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="w-7 h-7 object-contain border border-amber-400/40 bg-white p-0.5 shrink-0">
        <?php else: ?>
          <div class="w-7 h-7 bg-[#071324] border border-amber-400/40 flex items-center justify-center text-amber-400 font-black text-xs shrink-0">
            <?= htmlspecialchars($brandInitials) ?>
          </div>
        <?php endif; ?>
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-xs font-black tracking-wider text-white uppercase truncate font-mono max-w-[165px] sm:max-w-xs"><?= htmlspecialchars($brandName) ?></span>
            <span class="px-1.5 py-0.2 <?= $isPicFoto ? 'bg-cyan-400 text-cyan-950' : 'bg-amber-400 text-slate-950' ?> text-[9px] font-black uppercase tracking-wider shrink-0">
              <?= $isPicFoto ? 'PIC FOTO' : ($isPic ? 'PIC LAPANGAN' : 'ADMIN') ?>
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <span class="text-[11px] font-semibold text-slate-300 hidden sm:inline-block max-w-[120px] truncate">
          <?= htmlspecialchars($user['name']) ?>
        </span>
        <a href="?action=logout" onclick="handleLogoutConfirm(event)" class="px-2.5 py-1 bg-slate-800/90 hover:bg-rose-900 text-slate-200 hover:text-white text-[11px] font-bold transition-colors border border-slate-700 flex items-center gap-1 active:scale-95" title="Keluar">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span>Keluar</span>
        </a>
      </div>
    </div>

    <!-- Tier 2: Selected Venue & Session Switcher Bar -->
    <div class="max-w-5xl mx-auto px-3.5 py-1.5 bg-[#071324] flex items-center justify-between gap-2">
      <div class="flex items-center gap-1.5 min-w-0">
        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
        </svg>
        <span class="text-xs font-black text-white truncate leading-tight">
          <?= htmlspecialchars($currentSession ? $currentSession['venue_name'] : 'Tidak Ada Sesi') ?>
        </span>
        <span class="text-[10px] font-mono font-bold text-amber-400 bg-amber-400/10 px-1.5 py-0.5 border border-amber-400/30 shrink-0">
          <?= htmlspecialchars($currentSession ? $currentSession['day_name'] : '-') ?> • <?= substr($currentSession ? (isset($currentSession['time_start']) ? $currentSession['time_start'] : $currentSession['start_time']) : '20:00', 0, 5) ?>
        </span>
      </div>

      <?php if ($isAdmin || $isPicFoto): ?>
        <button type="button" onclick="openSessionSheet()" class="shrink-0 px-2.5 py-1 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-[11px] uppercase tracking-wider rounded shadow-xs flex items-center gap-1 active:scale-95 transition-all">
          <span>Ganti Sesi</span>
          <svg class="w-3 h-3 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </button>
      <?php endif; ?>
    </div>
  </header>

  <!-- ========================================== -->
  <!-- MAIN WORKSPACE -->
  <!-- ========================================== -->
  <main class="max-w-5xl mx-auto px-4 py-3 flex-1 w-full">

    <?php if (!$currentSession): ?>
      <div class="p-8 bg-white border border-slate-200 text-center space-y-2 mt-4">
        <h3 class="text-sm font-bold text-slate-800">Akun Belum Ditugaskan ke Sesi Mabar</h3>
        <p class="text-xs text-slate-500 leading-relaxed max-w-sm mx-auto">
          Akun PIC ini belum memiliki sesi yang ditentukan oleh Admin. Silakan hubungi Admin CMS untuk menetapkan sesi mabar Anda.
        </p>
        <div class="pt-2">
          <a href="?action=logout" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold inline-block">
            Keluar & Login Ulang
          </a>
        </div>
      </div>
    <?php else: ?>

      <?php if (!empty($appMessage)): ?>
        <div class="mb-3 p-3 bg-amber-50 border border-amber-300 text-amber-950 text-xs font-semibold flex items-center justify-between">
          <span><?= htmlspecialchars($appMessage) ?></span>
          <button onclick="this.parentElement.remove()" class="text-amber-900 font-bold ml-3 text-xs">&times;</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($appError)): ?>
        <div class="mb-3 p-3 bg-rose-50 border border-rose-300 text-rose-950 text-xs font-semibold flex items-center justify-between">
          <span><?= htmlspecialchars($appError) ?></span>
          <button onclick="this.parentElement.remove()" class="text-rose-800 font-bold ml-3 text-xs">&times;</button>
        </div>
      <?php endif; ?>

      <?php if ($currentSession['status'] === 'completed'): ?>
        <div class="mb-4 p-3.5 bg-emerald-50 border-2 border-emerald-400 text-emerald-950 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
          <div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="text-xs font-black uppercase tracking-wider font-mono text-emerald-900">SESI MABAR INI TELAH SELESAI</span>
            </div>
            <div class="text-[11px] text-emerald-800 mt-0.5">
              Ditutup pada <?= !empty($currentSession['completed_at']) ? date('d M Y, H:i', strtotime($currentSession['completed_at'])) . ' WIB' : 'Hari ini' ?>. Rekapitulasi kas dan pertandingan telah dicatat di sistem.
            </div>
          </div>
          <div class="text-xs font-mono font-bold text-emerald-900 bg-white px-3 py-1.5 border border-emerald-300 self-start sm:self-center">
            Kas: Rp <?= number_format($paymentSummary['total_cash_collected'], 0, ',', '.') ?> (<?= $paymentSummary['total_paid'] ?>/<?= $paymentSummary['total_present'] ?> Lunas)
          </div>
        </div>
      <?php endif; ?>

      <!-- ======================================================== -->
      <!-- TAB 1: ROTASI & PAPAN PERTANDINGAN (view=court)          -->
      <!-- ======================================================== -->
      <?php if ($view === 'court'): ?>
        
        <!-- Rotation Action Bar -->
        <?php 
          $sessionCourts = max(1, (int)($currentSession['court_count'] ?? 1));
          $presentCount = (int)$paymentSummary['total_present'];
          $maxPossibleCourts = (int)floor($presentCount / 4);
          $neededPlayers = $sessionCourts * 4;
        ?>

        <div class="bg-white border border-slate-200 p-4 mb-4 shadow-xs space-y-3">
          
          <!-- Court Capacity & Attendance Status Banner -->
          <div class="p-3 bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2.5 py-1 bg-[#0b1e36] text-amber-400 font-mono font-black text-xs border border-amber-400/30 flex items-center gap-1.5">
                <span>🏸 <?= $sessionCourts ?> Lapangan Disewa</span>
              </span>
              <span class="text-xs font-bold text-slate-800">
                <?= $presentCount ?> Pemain Hadir (Maks <?= $maxPossibleCourts ?> Lapangan Aktif)
              </span>
            </div>
            
            <?php if ($presentCount < $neededPlayers): ?>
              <div class="text-[11px] font-mono text-amber-800 bg-amber-50 px-2.5 py-1 border border-amber-300">
                ℹ️ <strong>1 Lapangan Ganda = 4 Pemain</strong>. Saat ini <?= $presentCount ?> hadir (1 lapangan main, <?= $presentCount - 4 ?> istirahat). Butuh min. <strong><?= $neededPlayers ?> pemain hadir</strong> di tab <em>Pemain</em> untuk membuka <strong><?= $sessionCourts ?> lapangan sekaligus</strong>!
              </div>
            <?php else: ?>
              <div class="text-[11px] font-mono text-emerald-800 bg-emerald-50 px-2.5 py-1 border border-emerald-300 font-bold">
                ✓ Kuota pemain cukup untuk mengisi <?= $sessionCourts ?> lapangan (<?= $neededPlayers ?> pemain)!
              </div>
            <?php endif; ?>
          </div>

          <!-- Row 1: Match Status & Selesai Sesi Button -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1 border-b border-slate-100 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 bg-[#0b1e36] text-amber-400 text-xs font-black font-mono border border-amber-400/30">
                  RONDE #<?= $latestRound ?>
                </span>
                <span class="text-xs font-bold text-slate-800">
                  <?= count($activeRoundMatches) ?> Match Lapangan Berjalan
                </span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">
                Rotasi otomatis menyusun pasangan & lawan secara adil berdasarkan rating dan riwayat main.
              </p>
            </div>

            <?php if ($currentSession['status'] !== 'completed' && !$isPicFoto): ?>
              <button type="button" onclick="document.getElementById('modalCompleteSession').classList.remove('hidden')" class="self-start sm:self-center px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 font-black text-xs uppercase tracking-wider transition-all active:scale-95 whitespace-nowrap min-h-[40px] flex items-center justify-center gap-1.5 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Selesai Sesi Mabar</span>
              </button>
            <?php endif; ?>
          </div>

          <!-- Row 2: Control Toolbar (Responsive Grid) -->
          <form id="generateRoundForm" class="pt-1">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
              
              <!-- Court Count Selector -->
              <div class="md:col-span-4">
                <label for="courtCountSelect" class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">
                  Pilih Lapangan Aktif:
                </label>
                <select name="court_count" id="courtCountSelect" data-no-search class="w-full bg-slate-50 hover:bg-white border border-slate-300 focus:border-[#0b1e36] px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none min-h-[44px] transition-colors shadow-2xs cursor-pointer">
                  <?php for ($c = 1; $c <= $sessionCourts; $c++): 
                    $reqP = $c * 4;
                    $canPlay = ($presentCount >= $reqP);
                  ?>
                    <option value="<?= $c ?>" <?= ($c === min($sessionCourts, max(1, $maxPossibleCourts))) ? 'selected' : '' ?>>
                      <?= $c ?> Lapangan <?= $canPlay ? "(Tersedia - {$reqP} Pemain)" : "(Kurang Pemain - Butuh {$reqP})" ?>
                    </option>
                  <?php endfor; ?>
                </select>
              </div>

              <!-- Algorithm Selector -->
              <div class="md:col-span-4">
                <label for="algorithmSelect" class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">
                  Strategi Pembagian Tim:
                </label>
                <select name="algorithm" id="algorithmSelect" data-no-search class="w-full bg-slate-50 hover:bg-white border border-slate-300 focus:border-[#0b1e36] px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none min-h-[44px] transition-colors shadow-2xs cursor-pointer">
                  <option value="balanced_level" selected>Fair Level (Kombinasi Seimbang)</option>
                  <option value="tiered_level">Selevel (Pure Tier Mahir/Pemula)</option>
                  <option value="fair_social">Kocok Campur Merata</option>
                </select>
              </div>

              <!-- Action Buttons (Acak Ronde Baru & Reset) -->
              <div class="md:col-span-4 flex items-center gap-2">
                <button type="submit" id="btnGenerate" class="flex-1 py-2.5 px-4 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/30 active:scale-95 whitespace-nowrap min-h-[44px] flex items-center justify-center gap-2 shadow-xs">
                  <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Acak Ronde Baru</span>
                </button>
                <?php if (!empty($activeRoundMatches)): ?>
                  <button type="button" onclick="confirmDeleteRound(<?= $sessionId ?>, <?= $latestRound ?>)" class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 active:scale-95 min-h-[44px] shrink-0" title="Hapus dan reset ronde ini">
                    Reset
                  </button>
                <?php endif; ?>
              </div>

            </div>
          </form>
        </div>

        <!-- Matches in Current Round -->
        <div class="space-y-4">
          <?php if (empty($activeRoundMatches)): ?>
            <div class="bg-white border border-slate-200 p-8 text-center space-y-3">
              <h3 class="text-sm font-bold text-slate-800">Belum Ada Ronde Aktif</h3>
              <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                Pastikan pemain sudah dicentang hadir di tab <strong>Pemain</strong>, lalu klik tombol <strong>Acak Ronde Baru</strong> di atas untuk membagi pasangan otomatis ke lapangan secara adil.
              </p>
            </div>
          <?php else: ?>
            <?php foreach ($activeRoundMatches as $match): 
              $isCompleted = ($match['status'] === 'completed');
              
              // Calculate weights
              $wA1 = RotationEngine::levelWeight($match['team_a_p1_level']);
              $wA2 = RotationEngine::levelWeight($match['team_a_p2_level']);
              $wB1 = RotationEngine::levelWeight($match['team_b_p1_level']);
              $wB2 = RotationEngine::levelWeight($match['team_b_p2_level']);

              $ratingA = $wA1 + $wA2;
              $ratingB = $wB1 + $wB2;
              $diff = abs($ratingA - $ratingB);

              $balanceStatus = ($diff === 0) ? 'Sangat Seimbang' : (($diff === 1) ? 'Seimbang' : 'Timpang +' . $diff);
              $balanceBadgeClass = ($diff === 0) ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : (($diff === 1) ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-amber-50 text-amber-800 border-amber-300');
            ?>
              <!-- TOURNAMENT-GRADE BADMINTON MATCH CARD -->
              <div class="bg-white border-2 <?= $isCompleted ? 'border-blue-400 shadow-md' : 'border-slate-300 shadow-sm' ?> rounded-md overflow-hidden" id="match_card_<?= $match['id'] ?>">
                
                <!-- Match Top Bar -->
                <div class="bg-[#0b1e36] text-white px-3.5 py-2.5 flex items-center justify-between gap-2 border-b border-slate-800">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-400 text-slate-950 font-black font-mono text-xs uppercase tracking-wider rounded-xs">
                      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 11H9v-2h2v2zm0-4H9V5h2v4z"/></svg>
                      LAPANGAN <?= $match['court_number'] ?>
                    </span>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-xs border <?= $balanceBadgeClass ?>">
                      <?= $balanceStatus ?> (<?= $ratingA ?> vs <?= $ratingB ?>)
                    </span>
                  </div>

                  <div class="flex items-center gap-2">
                    <?php if ($isCompleted): ?>
                      <span class="px-2.5 py-1 bg-blue-500/20 text-blue-300 border border-blue-400/40 text-[10px] font-mono font-black uppercase tracking-wider rounded-xs flex items-center gap-1">
                        ✓ Skor: <?= $match['score_team_a'] ?> - <?= $match['score_team_b'] ?>
                      </span>
                    <?php else: ?>
                      <span class="px-2.5 py-1 bg-amber-400/20 text-amber-400 border border-amber-400/40 text-[10px] font-mono font-black uppercase tracking-wider rounded-xs flex items-center gap-1.5 animate-pulse">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        Sedang Tanding
                      </span>
                    <?php endif; ?>
                    <button type="button" onclick="confirmDeleteMatch(<?= $match['id'] ?>, <?= $match['court_number'] ?>)" class="text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 text-xs font-bold px-2 py-1 border border-rose-500/40 rounded-xs transition-colors" title="Hapus match ini">
                      ✕ Hapus
                    </button>
                  </div>
                </div>

                <!-- Match Teams & Stepper Score Form -->
                <form class="score-form p-3.5 space-y-3" data-match-id="<?= $match['id'] ?>" onsubmit="handleScoreSubmit(event, <?= $match['id'] ?>)">
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    
                    <!-- Team A (Athletic Navy) -->
                    <div class="bg-gradient-to-b from-blue-50/60 to-slate-50 p-3.5 border-2 <?= ((int)$match['score_team_a'] > (int)$match['score_team_b'] && $isCompleted) ? 'border-amber-400 ring-2 ring-amber-300' : 'border-blue-200' ?> rounded-sm flex flex-col justify-between">
                      <div>
                        <div class="flex items-center justify-between text-xs font-mono font-black text-blue-950 uppercase tracking-wider border-b border-blue-200/80 pb-2 mb-2">
                          <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 bg-blue-600 rounded-full"></span>
                            <span>TIM A</span>
                          </span>
                          <span class="text-blue-900 bg-blue-100/90 px-2 py-0.5 border border-blue-200 text-[10px] rounded-xs font-bold">Rating: <?= $ratingA ?></span>
                        </div>

                        <!-- Player 1 -->
                        <div class="flex items-center justify-between gap-1.5 py-1.5 border-b border-slate-200/60">
                          <div class="min-w-0">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_a_p1_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-blue-700 bg-blue-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_a_p1_level']) ?>]</span>
                          </div>
                          <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_a_p1', '<?= htmlspecialchars(addslashes($match['team_a_p1_name'])) ?>', <?= (int)$match['team_a_p1'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded-xs shadow-2xs" title="Tukar Pemain">Tukar</button>
                        </div>

                        <!-- Player 2 -->
                        <div class="flex items-center justify-between gap-1.5 py-1.5">
                          <div class="min-w-0">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_a_p2_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-blue-700 bg-blue-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_a_p2_level']) ?>]</span>
                          </div>
                          <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_a_p2', '<?= htmlspecialchars(addslashes($match['team_a_p2_name'])) ?>', <?= (int)$match['team_a_p2'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded-xs shadow-2xs" title="Tukar Pemain">Tukar</button>
                        </div>
                      </div>

                      <!-- Stepper Controller Score A (Zero Auto-Clear on Focus/Type) -->
                      <div class="flex items-center justify-center gap-2 mt-3 pt-2.5 border-t border-blue-200/60 bg-white p-2 rounded-xs shadow-inner">
                        <span class="text-xs font-mono font-bold text-slate-600 mr-1">Skor:</span>
                        <button type="button" onclick="decrementScore('a', <?= $match['id'] ?>)" class="w-12 h-11 bg-slate-100 hover:bg-slate-200 active:scale-90 text-slate-900 font-black text-2xl flex items-center justify-center select-none border border-slate-300 rounded-xs transition-transform">-</button>
                        <input type="number" 
                               id="score_a_<?= $match['id'] ?>" 
                               value="<?= (int)$match['score_team_a'] ?>" 
                               onclick="this.select()"
                               onfocus="if(this.value==='0') this.value='';" 
                               onblur="if(this.value==='') this.value='0';" 
                               oninput="if(this.value.length > 1 && this.value.startsWith('0')) this.value = parseInt(this.value, 10);"
                               class="w-16 h-11 text-center bg-white border-2 border-slate-400 font-mono font-black text-2xl text-slate-900 focus:outline-none focus:border-[#0b1e36] rounded-xs shadow-xs">
                        <button type="button" onclick="incrementScore('a', <?= $match['id'] ?>)" class="w-12 h-11 bg-[#0b1e36] hover:bg-[#071324] active:scale-90 text-amber-400 font-black text-2xl flex items-center justify-center select-none border border-amber-400/40 rounded-xs transition-transform">+</button>
                      </div>
                    </div>

                    <!-- Team B (Athletic Crimson) -->
                    <div class="bg-gradient-to-b from-rose-50/60 to-slate-50 p-3.5 border-2 <?= ((int)$match['score_team_b'] > (int)$match['score_team_a'] && $isCompleted) ? 'border-amber-400 ring-2 ring-amber-300' : 'border-rose-200' ?> rounded-sm flex flex-col justify-between">
                      <div>
                        <div class="flex items-center justify-between text-xs font-mono font-black text-rose-950 uppercase tracking-wider border-b border-rose-200/80 pb-2 mb-2">
                          <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 bg-rose-600 rounded-full"></span>
                            <span>TIM B</span>
                          </span>
                          <span class="text-rose-900 bg-rose-100/90 px-2 py-0.5 border border-rose-200 text-[10px] rounded-xs font-bold">Rating: <?= $ratingB ?></span>
                        </div>

                        <!-- Player 1 -->
                        <div class="flex items-center justify-between gap-1.5 py-1.5 border-b border-slate-200/60">
                          <div class="min-w-0">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_b_p1_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-rose-700 bg-rose-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_b_p1_level']) ?>]</span>
                          </div>
                          <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_b_p1', '<?= htmlspecialchars(addslashes($match['team_b_p1_name'])) ?>', <?= (int)$match['team_b_p1'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded-xs shadow-2xs" title="Tukar Pemain">Tukar</button>
                        </div>

                        <!-- Player 2 -->
                        <div class="flex items-center justify-between gap-1.5 py-1.5">
                          <div class="min-w-0">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_b_p2_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-rose-700 bg-rose-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_b_p2_level']) ?>]</span>
                          </div>
                          <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_b_p2', '<?= htmlspecialchars(addslashes($match['team_b_p2_name'])) ?>', <?= (int)$match['team_b_p2'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded-xs shadow-2xs" title="Tukar Pemain">Tukar</button>
                        </div>
                      </div>

                      <!-- Stepper Controller Score B (Zero Auto-Clear on Focus/Type) -->
                      <div class="flex items-center justify-center gap-2 mt-3 pt-2.5 border-t border-rose-200/60 bg-white p-2 rounded-xs shadow-inner">
                        <span class="text-xs font-mono font-bold text-slate-600 mr-1">Skor:</span>
                        <button type="button" onclick="decrementScore('b', <?= $match['id'] ?>)" class="w-12 h-11 bg-slate-100 hover:bg-slate-200 active:scale-90 text-slate-900 font-black text-2xl flex items-center justify-center select-none border border-slate-300 rounded-xs transition-transform">-</button>
                        <input type="number" 
                               id="score_b_<?= $match['id'] ?>" 
                               value="<?= (int)$match['score_team_b'] ?>" 
                               onclick="this.select()"
                               onfocus="if(this.value==='0') this.value='';" 
                               onblur="if(this.value==='') this.value='0';" 
                               oninput="if(this.value.length > 1 && this.value.startsWith('0')) this.value = parseInt(this.value, 10);"
                               class="w-16 h-11 text-center bg-white border-2 border-slate-400 font-mono font-black text-2xl text-slate-900 focus:outline-none focus:border-[#0b1e36] rounded-xs shadow-xs">
                        <button type="button" onclick="incrementScore('b', <?= $match['id'] ?>)" class="w-12 h-11 bg-[#0b1e36] hover:bg-[#071324] active:scale-90 text-amber-400 font-black text-2xl flex items-center justify-center select-none border border-amber-400/40 rounded-xs transition-transform">+</button>
                      </div>
                    </div>

                  </div>

                  <!-- Save / Update Score Button (Min 48px Touch Target) -->
                  <button type="submit" class="w-full min-h-[48px] py-3 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/30 active:scale-[0.99] flex items-center justify-center gap-2 shadow-sm rounded-xs">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span><?= $isCompleted ? 'Perbarui Skor Pertandingan' : 'Simpan Skor Ronde Ini' ?></span>
                  </button>
                </form>

              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Resting / Queue Players (Pemain Istirahat) -->
        <?php if (!empty($restingPlayerIds)): ?>
          <div class="mt-5 p-3.5 bg-amber-50 border border-amber-200">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-black text-amber-950 uppercase tracking-wider font-mono">
                Istirahat Ronde Ini (<?= count($restingPlayerIds) ?> Orang)
              </span>
              <span class="text-[10px] text-amber-800 font-semibold font-mono">Prioritas main di ronde berikutnya</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <?php foreach ($restingPlayerIds as $rp): ?>
                <span class="px-2 py-1 bg-white border border-amber-300 text-slate-800 text-xs font-bold">
                  <?= htmlspecialchars($rp['name']) ?> <span class="text-[9px] font-mono text-slate-500 font-normal">[<?= htmlspecialchars($rp['level']) ?>]</span>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      <!-- ======================================================== -->
      <!-- TAB 2: PEMAIN & ABSENSI KEHADIRAN (view=players)        -->
      <!-- ======================================================== -->
      <?php elseif ($view === 'players'): ?>

        <!-- Action Header Bar -->
        <div class="bg-white border border-slate-200 p-3.5 mb-4 space-y-3 shadow-xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div>
              <h2 class="text-sm font-black text-slate-900">
                Daftar Pemain & Absensi Lapangan
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                <strong id="kpiPresentCount"><?= $paymentSummary['total_present'] ?></strong> hadir • <strong><?= count($confirmedParticipants) ?></strong> sesi utama<?php if (!empty($waitingListParticipants)): ?> • <strong><?= count($waitingListParticipants) ?></strong> waiting list<?php endif; ?>
              </p>
            </div>
            
            <div class="flex items-center gap-2 w-full sm:w-auto">
              <!-- Paste WA Template Button -->
              <button onclick="document.getElementById('whatsappModal').classList.remove('hidden')" class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider active:scale-95 border border-amber-400 flex items-center justify-center gap-1.5 shadow-xs min-h-[44px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Paste List WA</span>
              </button>
              
              <!-- Quick Add Walk-In Button -->
              <button onclick="document.getElementById('quickAddModal').classList.remove('hidden')" class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider active:scale-95 border border-amber-400/30 flex items-center justify-center gap-1.5 shadow-xs min-h-[44px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>+ Walk-In</span>
              </button>
            </div>
          </div>

          <!-- Filter Search with Icon -->
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input type="text" id="filterPlayerInput" onkeyup="filterParticipants()" placeholder="Cari nama pemain..." class="w-full bg-slate-50 border border-slate-300 pl-9 pr-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 min-h-[44px]">
          </div>
        </div>

        <!-- 1. SECTION: PEMAIN SESI UTAMA -->
        <div class="space-y-3 mb-6">
          <div class="flex items-center justify-between px-1 border-b border-slate-200 pb-2">
            <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
              <span>Pemain Sesi Utama (<?= count($confirmedParticipants) ?>)</span>
            </h3>
            <span class="text-[11px] font-mono text-slate-500">Klik Edit untuk ubah Level / Waiting List</span>
          </div>

          <?php if (empty($confirmedParticipants)): ?>
            <div class="p-6 bg-white border border-slate-200 rounded-lg text-center text-xs text-slate-400">
              Belum ada pemain di Sesi Utama. Gunakan tombol <strong>Paste List WA</strong> atau <strong>+ Walk-In</strong> di atas.
            </div>
          <?php else: ?>
            <div class="space-y-2.5">
              <?php foreach (array_values($confirmedParticipants) as $idx => $p): 
                $isPresent = ((int)$p['is_present'] === 1);
                $lvl = $p['level'] ?: 'Intermediate';
                $lvlBadge = ($lvl === 'Advance') ? 'bg-amber-100 text-amber-900 border-amber-300' : (($lvl === 'Beginner') ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-blue-100 text-blue-900 border-blue-300');
                $pid = (int)$p['player_id'];
              ?>
                <div class="participant-row bg-white border border-slate-200 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-xs" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                  
                  <div class="flex items-start justify-between gap-2.5 min-w-0">
                    <div class="flex items-start gap-2.5 min-w-0">
                      <span class="w-7 h-7 rounded-md bg-slate-100 text-slate-800 text-xs font-mono font-bold flex items-center justify-center shrink-0 border border-slate-200 mt-0.5">
                        <?= $idx + 1 ?>
                      </span>
                      <div class="min-w-0">
                        <div class="font-extrabold text-sm text-slate-900 break-words leading-snug"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="text-[11px] text-slate-500 flex items-center flex-wrap gap-1.5 mt-1">
                          <span class="px-1.5 py-0.5 rounded border text-[9px] font-mono font-bold <?= $lvlBadge ?>"><?= htmlspecialchars($lvl) ?></span>
                          <?php if (!empty($p['phone'])): ?>
                            <span class="font-mono text-[10px] text-slate-500"><?= htmlspecialchars($p['phone']) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <span id="att_badge_<?= $pid ?>" class=" shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase <?= $isPresent ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                      <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                    </span>
                  </div>

                  <div class="grid grid-cols-4 sm:flex items-center gap-1.5 pt-2.5 sm:pt-0 border-t sm:border-t-0 border-slate-100 shrink-0">
                    <!-- Hadir Button -->
                    <button type="button" id="btn_hadir_<?= $pid ?>" onclick="setAttendance(<?= $sessionId ?>, <?= $pid ?>, 1)" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center <?= $isPresent ? 'bg-[#0b1e36] text-amber-400 border border-amber-400/40 shadow-xs' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' ?>">
                      Hadir
                    </button>

                    <!-- Tidak Hadir Button -->
                    <button type="button" id="btn_absen_<?= $pid ?>" onclick="setAttendance(<?= $sessionId ?>, <?= $pid ?>, 0)" class="min-h-[40px] px-2 rounded-lg text-[11px] sm:text-xs font-bold active:scale-95 transition-all flex items-center justify-center whitespace-nowrap <?= !$isPresent ? 'bg-rose-600 text-white border border-rose-700 shadow-xs' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' ?>">
                      Tidak Hadir
                    </button>

                    <!-- Edit Player Button (Level & Waiting List) -->
                    <button type="button" onclick="openEditPlayerModal(<?= $pid ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= htmlspecialchars($lvl) ?>', '<?= htmlspecialchars($p['phone'] ?: '') ?>', 'confirmed')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-slate-700 hover:text-slate-900 border border-slate-300 bg-slate-50 active:scale-95 flex items-center justify-center" title="Ubah Level / Pindahkan ke Waiting List">
                      Edit
                    </button>

                    <!-- Remove Player Button -->
                    <button type="button" onclick="confirmRemoveParticipant(<?= $sessionId ?>, <?= $pid ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 bg-white active:scale-95 flex items-center justify-center" title="Keluarkan dari sesi">
                      Hapus
                    </button>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- 2. SECTION: WAITING LIST (DAFTAR TUNGGU) - Tampil otomatis bila ada pemain Waiting List -->
        <?php if (!empty($waitingListParticipants)): ?>
        <div class="space-y-3">
          <div class="flex items-center justify-between px-1 border-b border-amber-300 pb-2">
            <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-amber-950 flex items-center gap-1.5">
              <span>Waiting List (<?= count($waitingListParticipants) ?>)</span>
            </h3>
            <span class="text-[11px] font-mono text-amber-800">Ubah ke Sesi Utama lewat tombol Edit</span>
          </div>

          <div class="space-y-2.5">
            <?php foreach (array_values($waitingListParticipants) as $idx => $wp): 
              $isPresent = ((int)$wp['is_present'] === 1);
              $lvl = $wp['level'] ?: 'Intermediate';
              $lvlBadge = ($lvl === 'Advance') ? 'bg-amber-100 text-amber-900 border-amber-300' : (($lvl === 'Beginner') ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-blue-100 text-blue-900 border-blue-300');
              $pid = (int)$wp['player_id'];
            ?>
              <div class="participant-row bg-amber-50/40 border border-amber-200 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-xs" data-name="<?= strtolower(htmlspecialchars($wp['name'])) ?>">
                
                <div class="flex items-start justify-between gap-2.5 min-w-0">
                  <div class="flex items-start gap-2.5 min-w-0">
                    <span class="w-7 h-7 rounded-md bg-amber-400 text-slate-950 text-xs font-mono font-bold flex items-center justify-center shrink-0 mt-0.5">
                      W<?= $idx + 1 ?>
                    </span>
                    <div class="min-w-0">
                      <div class="font-extrabold text-sm text-slate-900 break-words leading-snug"><?= htmlspecialchars($wp['name']) ?></div>
                      <div class="text-[11px] text-slate-500 flex items-center flex-wrap gap-1.5 mt-1">
                        <span class="px-1.5 py-0.5 rounded border text-[9px] font-mono font-bold <?= $lvlBadge ?>"><?= htmlspecialchars($lvl) ?></span>
                        <span class="text-[9px] font-mono font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded border border-amber-300">WAITING LIST</span>
                        <?php if (!empty($wp['phone'])): ?>
                          <span class="font-mono text-[10px] text-slate-500"><?= htmlspecialchars($wp['phone']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <span id="att_badge_<?= $pid ?>" class="shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase <?= $isPresent ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                    <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                  </span>
                </div>

                <div class="grid grid-cols-4 sm:flex items-center gap-1.5 pt-2.5 sm:pt-0 border-t sm:border-t-0 border-amber-200/60 shrink-0">
                  <!-- Hadir Button -->
                  <button type="button" id="btn_hadir_<?= $pid ?>" onclick="setAttendance(<?= $sessionId ?>, <?= $pid ?>, 1)" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center <?= $isPresent ? 'bg-[#0b1e36] text-amber-400 border border-amber-400/40 shadow-xs' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-100' ?>">
                    Hadir
                  </button>

                  <!-- Tidak Hadir Button -->
                  <button type="button" id="btn_absen_<?= $pid ?>" onclick="setAttendance(<?= $sessionId ?>, <?= $pid ?>, 0)" class="min-h-[40px] px-2 rounded-lg text-[11px] sm:text-xs font-bold active:scale-95 transition-all flex items-center justify-center whitespace-nowrap <?= !$isPresent ? 'bg-rose-600 text-white border border-rose-700 shadow-xs' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-100' ?>">
                    Tidak Hadir
                  </button>

                  <!-- Edit Player Button -->
                  <button type="button" onclick="openEditPlayerModal(<?= $pid ?>, '<?= htmlspecialchars(addslashes($wp['name'])) ?>', '<?= htmlspecialchars($lvl) ?>', '<?= htmlspecialchars($wp['phone'] ?: '') ?>', 'waiting_list')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-slate-700 border border-slate-300 bg-white active:scale-95 flex items-center justify-center">
                    Edit
                  </button>

                  <!-- Remove Player Button -->
                  <button type="button" onclick="confirmRemoveParticipant(<?= $sessionId ?>, <?= $pid ?>, '<?= htmlspecialchars(addslashes($wp['name'])) ?>')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 bg-white active:scale-95 flex items-center justify-center">
                    Hapus
                  </button>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      <!-- ======================================================== -->
      <!-- TAB 3: KAS & PEMBAYARAN IURAN (view=payments)            -->
      <!-- ======================================================== -->
      <?php elseif ($view === 'payments'):
        $pctPaid = $paymentSummary['total_registered'] > 0 ? round(($paymentSummary['total_paid'] / $paymentSummary['total_registered']) * 100) : 0;
        $sessExpense = (int)($sessionFinSummary['total_expense'] ?? 0);
        $sessExtraInc = (int)($sessionFinSummary['total_extra_income'] ?? 0);
        $sessNet = (int)$paymentSummary['total_cash_collected'] + $sessExtraInc - $sessExpense;
      ?>

        <!-- Hero Card: Kas & Saldo Sesi Ini -->
        <div class="bg-[#0b1e36] text-white rounded-xl border border-slate-800 p-4 sm:p-5 shadow-md mb-3 space-y-3.5">
          <div class="flex items-start justify-between gap-2">
            <div>
              <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 block">SALDO KAS SESI INI</span>
              <div id="kpiSessionNet" class="text-2xl sm:text-3xl font-black font-mono mt-0.5 <?= $sessNet >= 0 ? 'text-white' : 'text-rose-300' ?>">
                <?= $sessNet < 0 ? '−' : '' ?>Rp <?= number_format(abs($sessNet), 0, ',', '.') ?>
              </div>
            </div>
            <span id="kpiPaidRatioBadge" class="px-2.5 py-1 rounded bg-amber-400/15 border border-amber-400/30 text-amber-300 text-[11px] font-mono font-bold shrink-0">
              <?= $paymentSummary['total_paid'] ?>/<?= $paymentSummary['total_registered'] ?> Lunas (<?= $pctPaid ?>%)
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden">
            <div id="kpiPaidProgressBar" class="bg-amber-400 h-2 transition-all duration-300" style="width: <?= $pctPaid ?>%"></div>
          </div>

          <div class="grid grid-cols-3 gap-2 pt-1 border-t border-slate-800/90">
            <div>
              <div class="text-[10px] text-slate-400">Iuran Masuk</div>
              <div id="kpiCashCollected" class="text-xs sm:text-sm font-black text-emerald-300 font-mono mt-0.5">
                Rp <?= number_format($paymentSummary['total_cash_collected'], 0, ',', '.') ?>
              </div>
            </div>
            <div>
              <div class="text-[10px] text-slate-400">Belum Bayar</div>
              <div id="kpiCashPending" class="text-xs sm:text-sm font-black text-amber-300 font-mono mt-0.5">
                Rp <?= number_format($paymentSummary['total_cash_pending'], 0, ',', '.') ?>
              </div>
            </div>
            <div class="text-right">
              <div class="text-[10px] text-slate-400">Biaya Sesi</div>
              <div id="kpiSessionExpense" data-expense="<?= $sessExpense ?>" data-extra="<?= $sessExtraInc ?>" class="text-xs sm:text-sm font-black text-rose-300 font-mono mt-0.5">
                Rp <?= number_format($sessExpense, 0, ',', '.') ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Sub-Tab Switcher: Iuran Pemain vs Biaya Sesi -->
        <div class="grid grid-cols-2 gap-1 p-1 bg-slate-200/80 rounded-xl mb-3">
          <button type="button" id="btnPayTabIuran" onclick="switchPaymentSubTab('iuran')" class="h-10 rounded-lg text-xs font-black transition-all bg-white text-slate-900 shadow-xs">
            Iuran Pemain (<?= count($participants) ?>)
          </button>
          <button type="button" id="btnPayTabBiaya" onclick="switchPaymentSubTab('biaya')" class="h-10 rounded-lg text-xs font-bold transition-all text-slate-600">
            Biaya & Kas Sesi (<?= count($sessionFinRecords) ?>)
          </button>
        </div>

        <!-- PANEL A: IURAN PEMAIN -->
        <div id="payPanelIuran" class="space-y-3">
          <!-- Filter Chips & Search Bar -->
          <div class="bg-white border border-slate-200 rounded-xl p-3 space-y-2.5 shadow-2xs">
            <div class="flex items-center gap-1.5 overflow-x-auto">
              <button type="button" data-pay-filter="all" onclick="filterPaymentsByStatus('all')" class="pay-filter-chip h-9 px-3 rounded-lg text-xs font-bold bg-[#0b1e36] text-amber-400 shrink-0">
                Semua (<?= count($participants) ?>)
              </button>
              <button type="button" data-pay-filter="unpaid" onclick="filterPaymentsByStatus('unpaid')" class="pay-filter-chip h-9 px-3 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 shrink-0">
                Belum Bayar (<span id="chipUnpaidCount"><?= $paymentSummary['total_unpaid'] ?></span>)
              </button>
              <button type="button" data-pay-filter="paid" onclick="filterPaymentsByStatus('paid')" class="pay-filter-chip h-9 px-3 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 shrink-0">
                Lunas (<span id="chipPaidCount"><?= $paymentSummary['total_paid'] ?></span>)
              </button>
            </div>
            <div class="relative">
              <input type="text" id="searchPaymentInput" oninput="applyPaymentFilters()" placeholder="Cari nama pemain di daftar kas..." class="w-full h-10 bg-slate-50 border border-slate-300 rounded-lg px-3 text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#0b1e36]">
            </div>
          </div>

          <!-- Participant Payment List -->
          <div class="space-y-2">
            <?php if (empty($participants)): ?>
              <div class="p-6 bg-white border border-slate-200 rounded-xl text-center text-xs text-slate-400">
                Belum ada pemain terdaftar di sesi ini.
              </div>
            <?php else: ?>
              <?php foreach ($participants as $idx => $p): 
                $isPaid = ($p['payment_status'] === 'paid');
                $feeAmt = !empty($p['payment_amount']) ? (int)$p['payment_amount'] : (!empty($currentSession['fee_per_person']) ? (int)$currentSession['fee_per_person'] : 35000);
                $isWL = (($p['status'] ?? 'confirmed') === 'waiting_list');
              ?>
                <div class="payment-row bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between gap-2.5 shadow-2xs" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>" data-status="<?= $isPaid ? 'paid' : 'unpaid' ?>">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-7 h-7 rounded text-xs font-mono font-bold flex items-center justify-center shrink-0 <?= $isWL ? 'bg-amber-400 text-slate-950' : 'bg-slate-100 text-slate-700 border border-slate-200' ?>">
                      <?= $isWL ? 'W' : ($idx + 1) ?>
                    </span>
                    <div class="min-w-0">
                      <div class="font-extrabold text-sm text-slate-900 break-words leading-snug"><?= htmlspecialchars($p['name']) ?></div>
                      <div class="text-[11px] font-mono font-semibold text-slate-500 mt-0.5 flex items-center flex-wrap gap-1.5">
                        <span class="text-slate-800 font-bold">Rp <?= number_format($feeAmt, 0, ',', '.') ?></span>
                        <?php if ($isWL): ?>
                          <span class="text-[9px] px-1.5 py-0.2 bg-amber-50 text-amber-800 border border-amber-200 rounded-xs">WL</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- 1-Tap Payment Toggle -->
                  <div class="flex items-center gap-1.5 shrink-0">
                    <span id="badge_<?= $p['player_id'] ?>" class="text-[10px] font-mono font-black px-2 py-1 rounded border <?= $isPaid ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-800 border-rose-300' ?>">
                      <?= $isPaid ? 'LUNAS' : 'BELUM' ?>
                    </span>
                    <button type="button" id="btn_pay_<?= $p['player_id'] ?>" onclick="togglePayment(<?= $sessionId ?>, <?= $p['player_id'] ?>)" class="min-h-[42px] min-w-[74px] px-3 py-2 rounded-lg text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center <?= $isPaid ? 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300' : 'bg-[#0b1e36] text-amber-400 hover:bg-[#071324] border border-amber-400/40 shadow-xs' ?>">
                      <?= $isPaid ? 'Batal' : 'Bayar' ?>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- PANEL B: BIAYA & KAS SESI (SEWA GOR, KOK, OPERASIONAL) -->
        <div id="payPanelBiaya" class="hidden space-y-3">
          <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center justify-between gap-3">
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Biaya Operasional Sesi</h3>
              <p class="text-[11px] text-slate-500 mt-0.5">Catat sewa GOR, kok, atau air mineral sesi ini. Otomatis tersinkron ke Laporan Keuangan Admin.</p>
            </div>
            <button type="button" onclick="document.getElementById('modalAppSessionFinance').classList.remove('hidden')" class="shrink-0 h-10 px-3.5 rounded-lg bg-[#0b1e36] text-amber-400 font-black text-xs uppercase tracking-wider border border-amber-400/30 active:scale-95">
              + Catat Biaya
            </button>
          </div>

          <?php if (empty($sessionFinRecords)): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-6 text-center space-y-2">
              <p class="text-xs font-bold text-slate-700">Belum ada biaya operasional tercatat untuk sesi ini</p>
              <p class="text-[11px] text-slate-400">Klik tombol <strong>+ Catat Biaya</strong> untuk mencatat sewa lapangan GOR atau pembelian kok.</p>
            </div>
          <?php else: ?>
            <div class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100 overflow-hidden">
              <?php foreach ($sessionFinRecords as $sfr):
                $isExp = ($sfr['type'] === 'expense');
              ?>
                <div class="p-3.5 flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                      <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase <?= $isExp ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                        <?= htmlspecialchars($sfr['category']) ?>
                      </span>
                      <span class="text-[10px] font-mono text-slate-400"><?= date('d/m/Y', strtotime($sfr['record_date'])) ?></span>
                    </div>
                    <div class="font-bold text-sm text-slate-900 mt-1 break-words"><?= htmlspecialchars($sfr['title']) ?></div>
                    <?php if (!empty($sfr['notes'])): ?>
                      <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($sfr['notes']) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class="font-mono text-sm font-black <?= $isExp ? 'text-rose-600' : 'text-emerald-700' ?>">
                      <?= $isExp ? '−' : '+' ?>Rp <?= number_format($sfr['amount'], 0, ',', '.') ?>
                    </span>
                    <form method="POST" action="?view=payments" onsubmit="return confirm('Hapus catatan kas sesi ini?');">
                      <input type="hidden" name="form_action" value="delete_session_finance">
                      <input type="hidden" name="record_ref" value="<?= Security::hashId((int)$sfr['id']) ?>">
                      <button type="submit" class="w-8 h-8 rounded bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 flex items-center justify-center text-xs font-bold" title="Hapus">&times;</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Modal Bottom-Sheet: Catat Biaya / Kas Sesi di App Lapangan -->
        <div id="modalAppSessionFinance" class="hidden fixed inset-0 z-50 bg-slate-950/70 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="if(event.target===this)this.classList.add('hidden')">
          <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl p-5 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-200">
              <div>
                <h3 class="text-sm font-black text-slate-900">Catat Biaya / Kas Sesi</h3>
                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($currentSession['venue_name']) ?></p>
              </div>
              <button type="button" onclick="document.getElementById('modalAppSessionFinance').classList.add('hidden')" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 font-bold text-sm">&times;</button>
            </div>
            <form method="POST" action="?view=payments" class="space-y-3.5">
              <input type="hidden" name="form_action" value="add_session_finance">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Jenis:</label>
                  <select name="type" data-no-search class="w-full h-11 bg-slate-50 border border-slate-300 rounded-lg px-2.5 text-xs font-bold text-slate-900">
                    <option value="expense">Pengeluaran (Biaya)</option>
                    <option value="income">Pemasukan Tambahan</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Kategori:</label>
                  <select name="category" data-no-search class="w-full h-11 bg-slate-50 border border-slate-300 rounded-lg px-2.5 text-xs font-bold text-slate-900">
                    <option value="Sewa Lapangan">Sewa Lapangan GOR</option>
                    <option value="Shuttlecock">Shuttlecock / Kok</option>
                    <option value="Konsumsi">Konsumsi / Air</option>
                    <option value="Honor PIC">Honor PIC</option>
                    <option value="Iuran Tambahan">Iuran Tambahan</option>
                    <option value="Lain-lain">Lain-lain</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nominal (Rp):</label>
                <input type="text" inputmode="numeric" id="appFinAmount" name="amount" required placeholder="Contoh: 240.000" oninput="this.value=this.value.replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.')" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-lg px-3 text-base font-mono font-bold text-slate-900">
                <div class="flex gap-1.5 mt-1.5 overflow-x-auto">
                  <?php foreach ([50000, 100000, 150000, 240000, 300000] as $qa): ?>
                    <button type="button" onclick="document.getElementById('appFinAmount').value='<?= number_format($qa, 0, ',', '.') ?>'" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-[11px] font-mono font-bold text-slate-700 shrink-0"><?= ($qa/1000) ?>rb</button>
                  <?php endforeach; ?>
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan:</label>
                <input type="text" name="title" required placeholder="Contoh: Sewa 2 Lapangan x 3 Jam" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-lg px-3 text-xs font-semibold text-slate-900">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan (Opsional):</label>
                <input type="text" name="notes" placeholder="No. kuitansi / transfer..." class="w-full h-10 bg-slate-50 border border-slate-300 rounded-lg px-3 text-xs text-slate-900">
              </div>
              <button type="submit" class="w-full h-12 rounded-xl bg-[#0b1e36] text-amber-400 font-black text-xs uppercase tracking-wider border border-amber-400/30 active:scale-95">
                Simpan Catatan Kas Sesi
              </button>
            </form>
          </div>
        </div>

      <!-- ======================================================== -->
      <!-- TAB 4: KLASEMEN & RIWAYAT SESI (view=leaderboard)        -->
      <!-- ======================================================== -->
      <?php elseif ($view === 'leaderboard'): ?>

        <div class="bg-white border border-slate-200 p-4 shadow-xs mb-4">
          <h2 class="text-sm font-black text-slate-900">
            Klasemen Poin Sesi: <?= htmlspecialchars($currentSession['venue_name']) ?>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">Akumulasi poin pertandingan mabar di GOR ini.</p>
        </div>

        <!-- Leaderboard Table (Mobile Smooth Horizontal Scroll) -->
        <div class="bg-white border border-slate-200 overflow-x-auto shadow-xs mb-6">
          <table class="w-full text-left text-xs font-sans min-w-[340px]">
            <thead class="bg-[#0b1e36] border-b border-slate-800 text-white uppercase font-mono font-bold text-[10px]">
              <tr>
                <th class="py-2.5 px-3">#</th>
                <th class="py-2.5 px-3">Pemain</th>
                <th class="py-2.5 px-2 text-center">Main</th>
                <th class="py-2.5 px-2 text-center">W/L</th>
                <th class="py-2.5 px-3 text-right text-amber-400">Total Poin</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php if (empty($sessionLeaderboard)): ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                    Belum ada pertandingan selesai dicatat untuk sesi ini.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($sessionLeaderboard as $idx => $row): ?>
                  <tr class="hover:bg-slate-50">
                    <td class="py-2.5 px-3 font-black text-slate-900">#<?= $idx + 1 ?></td>
                    <td class="py-2.5 px-3 font-bold text-slate-900">
                      <?= htmlspecialchars($row['name']) ?>
                      <span class="text-[9px] font-mono text-slate-400 font-normal">[<?= htmlspecialchars($row['level']) ?>]</span>
                    </td>
                    <td class="py-2.5 px-2 text-center font-mono text-slate-600"><?= $row['total_matches'] ?></td>
                    <td class="py-2.5 px-2 text-center font-mono">
                      <span class="text-emerald-700 font-bold"><?= $row['wins'] ?></span>/<span class="text-rose-600"><?= $row['losses'] ?></span>
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono font-black text-[#0b1e36] text-sm">
                      <?= number_format($row['total_points']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- History of Completed Matches Tonight -->
        <div class="space-y-3">
          <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 font-mono px-1">
            Riwayat Pertandingan Selesai (<?= count($completedMatches) ?>)
          </h3>
          <?php if (empty($completedMatches)): ?>
            <div class="p-6 bg-white border border-slate-200 text-center text-xs text-slate-400">
              Belum ada skor pertandingan yang selesai.
            </div>
          <?php else: ?>
            <?php foreach (array_reverse($completedMatches) as $cm): ?>
              <div class="bg-white border border-slate-200 p-3 text-xs flex items-center justify-between gap-2 shadow-xs">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-mono font-bold text-[10px] border border-slate-200">
                  R#<?= $cm['round_number'] ?> • Lap <?= $cm['court_number'] ?>
                </span>
                <div class="flex-1 text-center font-bold text-slate-800 truncate">
                  <span><?= htmlspecialchars($cm['team_a_p1_name']) ?> & <?= htmlspecialchars($cm['team_a_p2_name']) ?></span>
                  <span class="mx-2 text-[#0b1e36] font-mono font-black text-sm"><?= $cm['score_team_a'] ?> - <?= $cm['score_team_b'] ?></span>
                  <span><?= htmlspecialchars($cm['team_b_p1_name']) ?> & <?= htmlspecialchars($cm['team_b_p2_name']) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      <!-- ======================================================== -->
      <!-- TAB 5: DOKUMENTASI MABAR (view=documentation)            -->
      <!-- ======================================================== -->
      <?php elseif ($view === 'documentation'): ?>

        <!-- Documentation Header Card -->
        <div class="bg-white border border-slate-200 p-4 shadow-xs mb-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-[#0b1e36]"></span>
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-[#0b1e36]">
                  DOKUMENTASI FOTO MABAR SESI
                </span>
              </div>
              <h2 class="text-base font-black text-slate-900 mt-0.5">
                <?= htmlspecialchars($currentSession['venue_name']) ?>
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Foto yang diupload PIC akan otomatis tampil di Landing Page publik lengkap dengan tanggal pelaksanaan mabar.
              </p>
            </div>
            <span class="text-xs font-mono font-bold bg-[#0b1e36] text-amber-400 border border-amber-400/30 px-2.5 py-1 shrink-0">
              <?= count($sessionPhotos) ?> Foto
            </span>
          </div>
        </div>

        <!-- Form Upload Foto Baru -->
        <div class="bg-white border border-slate-200 p-4 shadow-xs mb-6">
          <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900 mb-3 flex items-center gap-1.5">
            <span>+ Unggah Foto Dokumentasi Mabar Baru</span>
          </h3>

          <form method="POST" action="?view=documentation" enctype="multipart/form-data" class="space-y-3.5">
            <input type="hidden" name="form_action" value="add_documentation">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Tanggal Mabar:
                </label>
                <input type="date" name="activity_date" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono font-bold text-slate-900 focus:outline-none">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Judul Foto / Momen:
                </label>
                <input type="text" name="title" placeholder="Misal: Foto Bersama Akhir Sesi" required class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none">
              </div>
            </div>

            <!-- Upload Photo File (Direct Camera / Gallery on Mobile - Multi-Photo Selection) -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-slate-800">
                  File Foto (Kamera / Galeri HP):
                </label>
                <span class="text-[10px] font-mono font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                  ✓ Bisa Pilih Banyak Foto Sekaligus
                </span>
              </div>
              <input type="file" name="photo_files[]" multiple accept="image/*" class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-700 file:mr-3 file:py-1 file:px-2.5 file:border-0 file:text-xs file:font-bold file:bg-[#0b1e36] file:text-white cursor-pointer rounded-xs">
              <span class="text-[10px] text-slate-500 mt-1 block font-sans">
                💡 <em>Tip di HP:</em> Anda bisa tap beberapa foto atau tahan foto untuk memilih lebih dari 1 file sekaligus. Format: JPG/PNG/WEBP (Maks 10MB per foto).
              </span>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-slate-500 mb-1">
                Atau Masukkan URL Gambar (Opsional jika foto sudah di cloud):
              </label>
              <input type="url" name="image_url" placeholder="https://..." class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-900">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Catatan / Keterangan Sesi (Caption):
              </label>
              <textarea name="caption" rows="2" placeholder="Tuliskan catatan keseruan mabar..." class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-900"></textarea>
            </div>

            <button type="submit" class="w-full py-2.5 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-bold text-xs uppercase tracking-wider transition-all border border-amber-400/30 flex items-center justify-center">
              <span>Unggah Foto ke Dokumentasi Web</span>
            </button>
          </form>
        </div>

        <!-- Daftar Foto Dokumentasi Sesi Ini -->
        <div>
          <div class="flex items-center justify-between mb-3 px-1">
            <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-800">
              Foto Dokumentasi Mabar (<?= count($sessionPhotos) ?>)
            </h3>
            <span class="text-[11px] text-slate-500">Tampil di Landing Page Publik</span>
          </div>

          <?php if (empty($sessionPhotos)): ?>
            <div class="bg-white border border-slate-200 p-8 text-center text-slate-400 space-y-2">
              <h4 class="text-xs font-bold text-slate-700">Belum Ada Foto Dokumentasi untuk Sesi Ini</h4>
              <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                Gunakan form di atas untuk mengunggah foto mabar langsung dari HP Anda.
              </p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <?php foreach ($sessionPhotos as $pic): 
                $picDate = !empty($pic['activity_date']) ? formatIndoDate($pic['activity_date']) : substr($pic['created_at'], 0, 10);
              ?>
                <div class="bg-white border border-slate-200 overflow-hidden shadow-xs flex flex-col justify-between">
                  <div class="aspect-video bg-slate-100 overflow-hidden relative">
                    <img src="<?= htmlspecialchars($pic['image_url']) ?>" alt="<?= htmlspecialchars($pic['title']) ?>" class="w-full h-full object-cover">
                    <div class="absolute top-2 left-2">
                      <span class="text-[10px] font-mono font-bold text-slate-900 bg-white/95 border border-slate-200 px-2 py-0.5">
                        Tgl: <?= htmlspecialchars($picDate) ?>
                      </span>
                    </div>
                  </div>
                  <div class="p-3 flex-1 flex flex-col justify-between">
                    <div>
                      <h4 class="font-bold text-slate-900 text-xs mb-1 line-clamp-1"><?= htmlspecialchars($pic['title']) ?></h4>
                      <p class="text-[11px] text-slate-500 line-clamp-2"><?= htmlspecialchars($pic['caption'] ? $pic['caption'] : 'Foto dokumentasi sesi.') ?></p>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                      <span class="font-mono text-slate-400 text-[10px]">
                        DOK-<?= strtoupper(substr(md5((string)$pic['id']), 0, 5)) ?>
                      </span>
                      <form method="POST" action="?view=documentation" onsubmit="handleDeletePhotoConfirm(event, this); return false;">
                        <input type="hidden" name="form_action" value="delete_documentation">
                        <input type="hidden" name="photo_id" value="<?= $pic['id'] ?>">
                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold hover:underline">
                          Hapus Foto
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      <?php endif; ?>

    <?php endif; ?>

  </main>

  <!-- ========================================== -->
  <!-- STICKY BOTTOM NAVIGATION BAR (ANDROID APP) -->
  <!-- ========================================== -->
  <?php if ($isPicFoto): ?>
    <nav class="fixed bottom-0 inset-x-0 bg-[#0b1e36] text-white border-t border-slate-800 z-40 pb-safe shadow-lg">
      <div class="max-w-4xl mx-auto px-4 py-2.5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 bg-cyan-400 rounded-full animate-pulse"></span>
          <span class="text-xs font-black uppercase tracking-wider text-cyan-300">Akses Tim Media & Dokumentasi</span>
        </div>
        <a href="?action=logout" onclick="handleLogoutConfirm(event)" class="px-3.5 py-1.5 bg-slate-800 hover:bg-rose-900 text-white font-bold text-xs uppercase tracking-wider border border-slate-700 transition-colors">
          Keluar Akun
        </a>
      </div>
    </nav>
  <?php else: ?>
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-40 pb-safe shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
      <div class="max-w-5xl mx-auto px-1 flex items-center justify-around">
        
        <!-- Tab 1: Rotasi -->
        <a href="?view=court" class="flex-1 py-2 px-1 text-center transition-all flex flex-col items-center justify-center border-t-2 <?= $view === 'court' ? 'border-amber-400 text-[#0b1e36]' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
          <svg class="w-5 h-5 mb-0.5 <?= $view === 'court' ? 'text-[#0b1e36]' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="16" rx="1.5" stroke-width="2"/>
            <line x1="3" y1="12" x2="21" y2="12" stroke-width="2"/>
            <line x1="12" y1="4" x2="12" y2="20" stroke-width="1.5" stroke-dasharray="2 2"/>
          </svg>
          <span class="text-[10px] tracking-wide block leading-none <?= $view === 'court' ? 'font-black text-[#0b1e36]' : 'font-semibold' ?>">Rotasi</span>
        </a>

        <!-- Tab 2: Pemain -->
        <a href="?view=players" class="flex-1 py-2 px-1 text-center transition-all relative flex flex-col items-center justify-center border-t-2 <?= $view === 'players' ? 'border-amber-400 text-[#0b1e36]' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
          <div class="relative">
            <svg class="w-5 h-5 mb-0.5 <?= $view === 'players' ? 'text-[#0b1e36]' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <?php if ($paymentSummary['total_present'] > 0): ?>
              <span class="absolute -top-1 -right-2.5 px-1 py-0.2 bg-amber-400 text-slate-950 font-black text-[9px] font-mono leading-none rounded-full min-w-[14px]">
                <?= $paymentSummary['total_present'] ?>
              </span>
            <?php endif; ?>
          </div>
          <span class="text-[10px] tracking-wide block leading-none <?= $view === 'players' ? 'font-black text-[#0b1e36]' : 'font-semibold' ?>">Pemain</span>
        </a>

        <!-- Tab 3: Kas -->
        <a href="?view=payments" class="flex-1 py-2 px-1 text-center transition-all relative flex flex-col items-center justify-center border-t-2 <?= $view === 'payments' ? 'border-amber-400 text-[#0b1e36]' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
          <div class="relative">
            <svg class="w-5 h-5 mb-0.5 <?= $view === 'payments' ? 'text-[#0b1e36]' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <?php if ($paymentSummary['total_unpaid'] > 0): ?>
              <span class="absolute -top-1 -right-2.5 px-1 py-0.2 bg-rose-600 text-white font-black text-[9px] font-mono leading-none rounded-full min-w-[14px]">
                <?= $paymentSummary['total_unpaid'] ?>
              </span>
            <?php endif; ?>
          </div>
          <span class="text-[10px] tracking-wide block leading-none <?= $view === 'payments' ? 'font-black text-[#0b1e36]' : 'font-semibold' ?>">Kas</span>
        </a>

        <!-- Tab 4: Foto Dokumentasi -->
        <a href="?view=documentation" class="flex-1 py-2 px-1 text-center transition-all relative flex flex-col items-center justify-center border-t-2 <?= $view === 'documentation' ? 'border-amber-400 text-[#0b1e36]' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
          <div class="relative">
            <svg class="w-5 h-5 mb-0.5 <?= $view === 'documentation' ? 'text-[#0b1e36]' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <?php if (!empty($sessionPhotos)): ?>
              <span class="absolute -top-1 -right-2.5 px-1 py-0.2 bg-slate-700 text-white font-black text-[9px] font-mono leading-none rounded-full min-w-[14px]">
                <?= count($sessionPhotos) ?>
              </span>
            <?php endif; ?>
          </div>
          <span class="text-[10px] tracking-wide block leading-none <?= $view === 'documentation' ? 'font-black text-[#0b1e36]' : 'font-semibold' ?>">Foto</span>
        </a>

        <!-- Tab 5: Klasemen -->
        <a href="?view=leaderboard" class="flex-1 py-2 px-1 text-center transition-all flex flex-col items-center justify-center border-t-2 <?= $view === 'leaderboard' ? 'border-amber-400 text-[#0b1e36]' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
          <svg class="w-5 h-5 mb-0.5 <?= $view === 'leaderboard' ? 'text-[#0b1e36]' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <span class="text-[10px] tracking-wide block leading-none <?= $view === 'leaderboard' ? 'font-black text-[#0b1e36]' : 'font-semibold' ?>">Klasemen</span>
        </a>

      </div>
    </nav>
  <?php endif; ?>

  <!-- ========================================== -->
  <!-- MODERN MOBILE BOTTOM SHEET: GANTI SESI      -->
  <!-- ========================================== -->
  <?php
    $otherSessions = array_filter($allSchedules, function($sch) use ($sessionId) {
        return (int)$sch['id'] !== (int)$sessionId;
    });
  ?>
  <div id="sessionBottomSheet" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-950/80 backdrop-blur-sm transition-opacity duration-200" onclick="if(event.target===this) closeSessionSheet()">
    <div class="bg-[#0b1e36] text-white border-t sm:border border-slate-700 w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl p-5 shadow-2xl max-h-[85vh] flex flex-col transition-transform duration-300 transform translate-y-0" onclick="event.stopPropagation()">
      
      <!-- Drag Handle Indicator for Mobile -->
      <div class="w-12 h-1.5 bg-slate-600 rounded-full mx-auto mb-3 sm:hidden"></div>

      <!-- Header -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-700/80">
        <div>
          <h3 class="text-sm font-black text-white uppercase tracking-wider font-mono">
            Pindah ke Sesi Lain
          </h3>
          <p class="text-[11px] text-slate-400 mt-0.5">
            Sesi aktif: <strong class="text-amber-400"><?= htmlspecialchars($currentSession ? $currentSession['venue_name'] : '-') ?></strong>
          </p>
        </div>
        <button type="button" onclick="closeSessionSheet()" class="w-8 h-8 rounded-full bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center font-bold text-sm" aria-label="Tutup">
          &times;
        </button>
      </div>

      <!-- Sessions List (Excluding Already Selected Session) -->
      <div class="overflow-y-auto py-3 space-y-2.5 max-h-[55vh]">
        <?php if (empty($otherSessions)): ?>
          <div class="p-6 text-center text-xs text-slate-400">
            Tidak ada sesi mabar lain yang tersedia saat ini.
          </div>
        <?php else: ?>
          <?php foreach ($otherSessions as $sch): 
            $tS = isset($sch['time_start']) ? $sch['time_start'] : (isset($sch['start_time']) ? $sch['start_time'] : '20:00');
            $tDisplay = substr($tS, 0, 5) . ' WIB';
          ?>
            <a href="?view=<?= htmlspecialchars($view) ?>&switch=<?= urlencode(Security::hashId((int)$sch['id'])) ?>" class="flex items-center justify-between p-3.5 bg-[#071324] hover:bg-slate-800 border border-slate-700/80 rounded-xl active:scale-98 transition-all group shadow-xs">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-lg bg-amber-400/10 border border-amber-400/30 text-amber-400 flex items-center justify-center font-black text-xs font-mono shrink-0">
                  <?= strtoupper(substr($sch['day_name'], 0, 3)) ?>
                </div>
                <div class="min-w-0 text-left">
                  <div class="font-extrabold text-xs sm:text-sm text-white group-hover:text-amber-400 transition-colors truncate">
                    <?= htmlspecialchars($sch['venue_name']) ?>
                  </div>
                  <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 font-mono truncate">
                    <span class="text-amber-300 font-bold"><?= htmlspecialchars($sch['day_name']) ?></span>
                    <span>•</span>
                    <span><?= $tDisplay ?></span>
                    <?php if (!empty($sch['branch_area'])): ?>
                      <span>•</span>
                      <span class="truncate"><?= htmlspecialchars($sch['branch_area']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="text-amber-400 text-xs font-black shrink-0 flex items-center gap-1 group-hover:translate-x-1 transition-transform pl-2">
                <span>Pindah</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Footer Close Button -->
      <div class="pt-3 border-t border-slate-700/80">
        <button type="button" onclick="closeSessionSheet()" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition-colors">
          Batal / Tetap di Sesi Ini
        </button>
      </div>

    </div>
  </div>

  <!-- ========================================== -->
  <!-- MODAL: SELESAIKAN & KUNCI SESI MABAR       -->
  <!-- ========================================== -->
  <div id="modalCompleteSession" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white max-w-sm w-full p-5 border border-slate-300 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Selesaikan Sesi Mabar</h3>
        <button type="button" onclick="document.getElementById('modalCompleteSession').classList.add('hidden')" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs">&times; Tutup</button>
      </div>

      <div class="space-y-2.5 text-xs text-slate-700">
        <p class="font-semibold text-slate-900">
          Apakah seluruh pertandingan untuk sesi di <strong><?= htmlspecialchars($currentSession['venue_name']) ?></strong> telah usai?
        </p>

        <div class="bg-slate-50 border border-slate-200 p-3 space-y-1.5 font-mono text-[11px]">
          <div class="flex justify-between">
            <span class="text-slate-500">Ronde Pertandingan:</span>
            <span class="font-bold text-slate-900"><?= $latestRound ?> Ronde (<?= count($matches) ?> Match)</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Total Pemain Hadir:</span>
            <span class="font-bold text-slate-900"><?= $paymentSummary['total_present'] ?> Orang</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Total Kas Terkumpul:</span>
            <span class="font-black text-emerald-700">Rp <?= number_format($paymentSummary['total_cash_collected'], 0, ',', '.') ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Iuran Belum Bayar:</span>
            <span class="font-bold text-rose-700">Rp <?= number_format($paymentSummary['total_cash_pending'], 0, ',', '.') ?> (<?= $paymentSummary['total_unpaid'] ?> Org)</span>
          </div>
        </div>

        <p class="text-[11px] text-slate-500 leading-tight">
          Menyelesaikan sesi akan mengunci skor dan mengarsipkan status sesi ini di CMS Admin sebagai sesi yang selesai.
        </p>
      </div>

      <form method="POST" action="?view=court" class="pt-2 flex items-center justify-end gap-2 border-t border-slate-200">
        <input type="hidden" name="form_action" value="complete_session">
        <button type="button" onclick="document.getElementById('modalCompleteSession').classList.add('hidden')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 font-black text-xs uppercase tracking-wider border border-amber-400/40">
          Ya, Selesaikan Sesi Ini
        </button>
      </form>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- MODAL 1: QUICK ADD WALK-IN PLAYER          -->
  <!-- ========================================== -->
  <div id="quickAddModal" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white max-w-sm w-full p-5 rounded-2xl border border-slate-300 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-2.5 border-b border-slate-200">
        <h3 class="text-sm font-black text-slate-900">+ Tambah Pemain Walk-In</h3>
        <button type="button" onclick="document.getElementById('quickAddModal').classList.add('hidden')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs">&times; Tutup</button>
      </div>

      <form id="quickAddForm" class="space-y-3.5">
        <input type="hidden" name="session_id" value="<?= $sessionId ?>">
        <input type="hidden" name="level" id="add_level" value="Intermediate">
        <input type="hidden" name="status" id="add_status" value="confirmed">

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemain:</label>
          <input type="text" name="name" required placeholder="Nama lengkap pemain..." class="w-full h-11 rounded-lg bg-slate-50 border border-slate-300 px-3 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Level Permainan (Tap untuk Pilih):</label>
          <div class="grid grid-cols-3 gap-1.5">
            <button type="button" data-add-level="Beginner" onclick="selectAddLevel('Beginner')" class="add-lvl-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Beginner
            </button>
            <button type="button" data-add-level="Intermediate" onclick="selectAddLevel('Intermediate')" class="add-lvl-btn h-11 rounded-lg text-xs font-black border border-amber-400/40 bg-[#0b1e36] text-amber-400 active:scale-95 transition-all">
              Intermediate
            </button>
            <button type="button" data-add-level="Advance" onclick="selectAddLevel('Advance')" class="add-lvl-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Advance
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Sesi:</label>
          <div class="grid grid-cols-2 gap-1.5">
            <button type="button" data-add-status="confirmed" onclick="selectAddStatus('confirmed')" class="add-st-btn h-11 rounded-lg text-xs font-black border border-amber-400/40 bg-[#0b1e36] text-amber-400 active:scale-95 transition-all">
              Sesi Utama
            </button>
            <button type="button" data-add-status="waiting_list" onclick="selectAddStatus('waiting_list')" class="add-st-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Waiting List
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp (Opsional):</label>
          <input type="tel" name="phone" placeholder="Contoh: 081234567890" class="w-full h-11 rounded-lg bg-slate-50 border border-slate-300 px-3 text-xs text-slate-900">
        </div>

        <button type="submit" class="w-full h-11 rounded-xl bg-[#0b1e36] hover:bg-[#071324] text-amber-400 font-extrabold text-xs uppercase tracking-wider border border-amber-400/30 transition-all mt-2 active:scale-95">
          Simpan Pemain
        </button>
      </form>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- MODAL 2: PASTE LIST WHATSAPP               -->
  <!-- ========================================== -->
  <div id="whatsappModal" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white max-w-md w-full p-5 rounded-2xl border border-slate-300 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <div>
          <h3 class="text-sm font-black text-slate-900">Paste List WhatsApp</h3>
          <p class="text-[11px] text-slate-500">Impor daftar nama langsung ke Pemain Sesi Utama</p>
        </div>
        <button type="button" onclick="document.getElementById('whatsappModal').classList.add('hidden')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs">&times; Tutup</button>
      </div>

      <form id="whatsappImportForm" class="space-y-3">
        <input type="hidden" name="session_id" value="<?= $sessionId ?>">

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Tempel (Paste) Teks Pesan WhatsApp:
          </label>
          <textarea name="raw_text" rows="8" required placeholder="Contoh format WhatsApp:&#10;List Mabar Komunitas:&#10;1. Budi (Inter)&#10;2. Andi 0812345678 (Pemula)&#10;3. Reza (Adv)&#10;4. Dimas" class="w-full rounded-lg bg-slate-50 border border-slate-300 p-2.5 text-xs font-mono text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 leading-relaxed"></textarea>
          <p class="text-[10px] text-slate-500 mt-1">
            Catatan: Semua nama nomor urut otomatis masuk ke <strong>Pemain Sesi Utama</strong> (kecuali di bagian bawah teks WA memang tertulis sub-judul khusus <em>Waiting List:</em>).
          </p>
        </div>

        <button type="submit" id="btnImportWa" class="w-full h-11 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all border border-amber-300 active:scale-95">
          Proses & Impor ke Sesi Ini →
        </button>
      </form>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- MODAL 3: EDIT DATA PEMAIN                  -->
  <!-- ========================================== -->
  <div id="editPlayerModal" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white max-w-sm w-full p-5 rounded-2xl border border-slate-300 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-2.5 border-b border-slate-200">
        <h3 class="text-sm font-black text-slate-900">Edit Pemain, Level & Status</h3>
        <button type="button" onclick="document.getElementById('editPlayerModal').classList.add('hidden')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs">&times; Tutup</button>
      </div>

      <form id="editPlayerForm" class="space-y-3.5">
        <input type="hidden" name="player_id" id="edit_player_id">
        <input type="hidden" name="session_id" value="<?= $sessionId ?>">
        <input type="hidden" name="level" id="edit_level" value="Intermediate">
        <input type="hidden" name="status" id="edit_status" value="confirmed">

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemain:</label>
          <input type="text" name="name" id="edit_name" required class="w-full h-11 rounded-lg bg-slate-50 border border-slate-300 px-3 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Level Permainan (Tap untuk Pilih):</label>
          <div class="grid grid-cols-3 gap-1.5">
            <button type="button" data-edit-level="Beginner" onclick="selectEditLevel('Beginner')" class="edit-lvl-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Beginner
            </button>
            <button type="button" data-edit-level="Intermediate" onclick="selectEditLevel('Intermediate')" class="edit-lvl-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Intermediate
            </button>
            <button type="button" data-edit-level="Advance" onclick="selectEditLevel('Advance')" class="edit-lvl-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Advance
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Sesi (Pindahkan Slot):</label>
          <div class="grid grid-cols-2 gap-1.5">
            <button type="button" data-edit-status="confirmed" onclick="selectEditStatus('confirmed')" class="edit-st-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Pemain Sesi Utama
            </button>
            <button type="button" data-edit-status="waiting_list" onclick="selectEditStatus('waiting_list')" class="edit-st-btn h-11 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600 active:scale-95 transition-all">
              Waiting List
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp:</label>
          <input type="tel" name="phone" id="edit_phone" placeholder="Contoh: 081234567890" class="w-full h-11 rounded-lg bg-slate-50 border border-slate-300 px-3 text-xs text-slate-900">
        </div>

        <button type="submit" class="w-full h-11 rounded-xl bg-[#0b1e36] hover:bg-[#071324] text-amber-400 font-extrabold text-xs uppercase tracking-wider border border-amber-400/30 transition-all mt-2 active:scale-95">
          Simpan Perubahan
        </button>
      </form>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- MODAL 4: TUKAR PEMAIN DI LAPANGAN          -->
  <!-- ========================================== -->
  <div id="swapPlayerModal" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white max-w-sm w-full p-5 border border-slate-300 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <div>
          <h3 class="text-sm font-black text-slate-900">Intervensi: Tukar Pemain</h3>
          <p class="text-[11px] text-slate-500">Ganti posisi pemain untuk menyeimbangkan level</p>
        </div>
        <button onclick="document.getElementById('swapPlayerModal').classList.add('hidden')" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs">&times; Tutup</button>
      </div>

      <div class="bg-amber-50 p-2.5 border border-amber-200 text-xs">
        Mengganti: <strong id="swap_current_player_name" class="text-amber-950"></strong>
      </div>

      <form id="swapPlayerForm" class="space-y-3">
        <input type="hidden" name="match_id" id="swap_match_id">
        <input type="hidden" name="slot" id="swap_slot">

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Pilih Pemain Pengganti (Dari Bench / Sesi):
          </label>
          <select name="new_player_id" id="swap_new_player_id" required class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-bold text-slate-900">
            <option value="">-- Pilih Pemain --</option>
            <?php foreach ($participants as $p): ?>
              <option value="<?= $p['player_id'] ?>">
                <?= htmlspecialchars($p['name']) ?> [<?= htmlspecialchars($p['level']) ?>] <?= $p['is_present'] ? '(Hadir)' : '(Absen)' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="w-full py-2.5 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 font-extrabold text-xs uppercase tracking-wider border border-amber-400/30 transition-all mt-2">
          Tukar Posisi Sekarang
        </button>
      </form>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- CUSTOM ATHLETIC DIALOG MODAL (CONFIRM/ALERT) -->
  <!-- ========================================== -->
  <div id="customDialogModal" class="hidden fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target===this) closeCustomDialog(false)">
    <div class="bg-white border border-slate-300 max-w-sm w-full p-5 shadow-2xl space-y-4" onclick="event.stopPropagation()">
      <div>
        <h3 id="dialogTitle" class="text-sm font-black text-slate-900 leading-snug"></h3>
        <p id="dialogMessage" class="text-xs text-slate-600 mt-2 leading-relaxed"></p>
      </div>
      <div id="dialogActions" class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" id="dialogCancelBtn" onclick="closeCustomDialog(false)" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition-colors">
          Batal
        </button>
        <button type="button" id="dialogConfirmBtn" onclick="closeCustomDialog(true)" class="px-4 py-2 font-black text-xs uppercase tracking-wider transition-colors border">
          Konfirmasi
        </button>
      </div>
    </div>
  </div>

  <!-- ======================================================== -->
  <!-- TOURNAMENT-GRADE GLOBAL ACTION LOADING OVERLAY           -->
  <!-- ======================================================== -->
  <div id="globalAppLoader" class="hidden fixed inset-0 z-[9999] bg-[#071324]/85 backdrop-blur-xs flex items-center justify-center p-4 transition-all">
    <div class="bg-white border-2 border-[#0b1e36] shadow-2xl max-w-sm w-full p-6 text-center space-y-4 rounded-sm">
      
      <!-- Animated Badminton Loader Icon -->
      <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
        <!-- Outer Pulsing Ring -->
        <div class="absolute inset-0 rounded-full border-4 border-amber-400/30 animate-ping"></div>
        <!-- Spinning Ring -->
        <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-[#0b1e36] border-r-amber-400 animate-spin"></div>
        <!-- Center Icon -->
        <div class="w-10 h-10 bg-[#0b1e36] rounded-full flex items-center justify-center shadow-inner select-none text-xl">
          🏸
        </div>
      </div>

      <!-- Live Process Text -->
      <div>
        <h4 id="globalAppLoaderTitle" class="text-sm font-black text-slate-950 font-sans tracking-tight">
          Memproses Tindakan...
        </h4>
        <p id="globalAppLoaderDesc" class="text-xs text-slate-500 mt-1.5 leading-relaxed font-sans">
          Mohon tunggu sebentar, sistem sedang memproses data...
        </p>
      </div>

      <!-- Shimmer Progress Bar -->
      <div class="w-full bg-slate-100 h-1.5 overflow-hidden rounded-full">
        <div class="h-full bg-gradient-to-r from-[#0b1e36] via-amber-400 to-[#0b1e36] animate-pulse w-full"></div>
      </div>

      <div class="text-[10px] font-mono text-slate-400 uppercase tracking-widest font-bold">
        <?= htmlspecialchars(strtoupper($brandName)) ?> • LIVE SYSTEM
      </div>
    </div>
  </div>

  <!-- Toast Notification System (Mobile Center Top) -->
  <div id="toast" class="hidden fixed top-4 inset-x-4 max-w-sm mx-auto z-50 px-4 py-3 shadow-2xl text-xs font-bold flex items-center justify-between border transition-all">
    <span id="toastText"></span>
    <button onclick="document.getElementById('toast').classList.add('hidden')" class="text-xs font-bold ml-2 opacity-70 hover:opacity-100">&times;</button>
  </div>

  <!-- JavaScript Courtside Operational System -->
  <script>
    let dialogResolve = null;

    // Tournament-Grade Global Action Loader Helpers
    function showAppLoader(title = 'Memproses Tindakan...', desc = 'Mohon tunggu sebentar, sistem sedang memproses data...') {
      const loader = document.getElementById('globalAppLoader');
      if (!loader) return;
      const t = document.getElementById('globalAppLoaderTitle');
      const d = document.getElementById('globalAppLoaderDesc');
      if (t) t.innerText = title;
      if (d) d.innerText = desc;
      loader.classList.remove('hidden');
    }

    function hideAppLoader() {
      const loader = document.getElementById('globalAppLoader');
      if (loader) loader.classList.add('hidden');
    }

    function openSessionSheet() {
      const el = document.getElementById('sessionBottomSheet');
      if (el) el.classList.remove('hidden');
    }

    function closeSessionSheet() {
      const el = document.getElementById('sessionBottomSheet');
      if (el) el.classList.add('hidden');
    }

    function showToast(msg, isSuccess = true) {
      const toast = document.getElementById('toast');
      const toastText = document.getElementById('toastText');
      if (!toast) return;
      if (toastText) toastText.innerText = msg;
      toast.className = `fixed top-4 inset-x-4 max-w-sm mx-auto z-50 px-4 py-3 shadow-2xl text-xs font-bold flex items-center justify-between border transition-all ${isSuccess ? 'bg-[#0b1e36] border-amber-400 text-amber-400' : 'bg-rose-950 border-rose-600 text-rose-200'}`;
      toast.classList.remove('hidden');
      setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    function showConfirm({ title, message, confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', isDanger = true }) {
      hideAppLoader();
      return new Promise((resolve) => {
        dialogResolve = resolve;
        const modal = document.getElementById('customDialogModal');
        const titleEl = document.getElementById('dialogTitle');
        const msgEl = document.getElementById('dialogMessage');
        const confirmBtn = document.getElementById('dialogConfirmBtn');
        const cancelBtn = document.getElementById('dialogCancelBtn');

        titleEl.innerText = title;
        msgEl.innerHTML = message;
        confirmBtn.innerText = confirmText;
        cancelBtn.innerText = cancelText;
        cancelBtn.style.display = 'inline-block';

        if (isDanger) {
          confirmBtn.className = 'px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs uppercase tracking-wider transition-colors border border-rose-700';
        } else {
          confirmBtn.className = 'px-4 py-2 bg-[#0b1e36] hover:bg-[#071324] active:scale-95 text-amber-400 font-bold text-xs uppercase tracking-wider transition-colors border border-amber-400/40';
        }

        modal.classList.remove('hidden');
      });
    }

    function showAlert({ title, message, buttonText = 'Mengerti', type = 'success' }) {
      hideAppLoader();
      return new Promise((resolve) => {
        dialogResolve = resolve;
        const modal = document.getElementById('customDialogModal');
        const titleEl = document.getElementById('dialogTitle');
        const msgEl = document.getElementById('dialogMessage');
        const confirmBtn = document.getElementById('dialogConfirmBtn');
        const cancelBtn = document.getElementById('dialogCancelBtn');

        titleEl.innerText = title;
        msgEl.innerHTML = message;
        confirmBtn.innerText = buttonText;
        cancelBtn.style.display = 'none';

        if (type === 'success') {
          confirmBtn.className = 'px-4 py-2 bg-[#0b1e36] hover:bg-[#071324] active:scale-95 text-amber-400 font-bold text-xs uppercase tracking-wider transition-colors border border-amber-400/40';
        } else {
          confirmBtn.className = 'px-4 py-2 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-bold text-xs uppercase tracking-wider transition-colors';
        }

        modal.classList.remove('hidden');
      });
    }

    function closeCustomDialog(result) {
      const modal = document.getElementById('customDialogModal');
      if (modal) modal.classList.add('hidden');
      if (dialogResolve) {
        dialogResolve(result);
        dialogResolve = null;
      }
    }

    // Custom Logout Handler
    async function handleLogoutConfirm(e) {
      e.preventDefault();
      const ok = await showConfirm({
        title: 'Keluar dari Sistem?',
        message: 'Sesi operasional PIC lapangan Anda akan diakhiri.',
        confirmText: 'Ya, Keluar',
        cancelText: 'Tetap di Sini',
        isDanger: false
      });
      if (ok) {
        window.location.href = '?action=logout';
      }
    }

    // Custom Photo Delete Handler
    async function handleDeletePhotoConfirm(e, form) {
      const ok = await showConfirm({
        title: 'Hapus Foto Dokumentasi?',
        message: 'Foto ini akan dihapus secara permanen dari galeri sesi dan landing page publik.',
        confirmText: 'Ya, Hapus Foto',
        cancelText: 'Batal',
        isDanger: true
      });
      if (ok) {
        form.submit();
      }
    }

    // Score Stepper Handlers
    function incrementScore(team, matchId) {
      const el = document.getElementById('score_' + team + '_' + matchId);
      if (el) el.value = parseInt(el.value || 0) + 1;
    }
    function decrementScore(team, matchId) {
      const el = document.getElementById('score_' + team + '_' + matchId);
      if (el) el.value = Math.max(0, parseInt(el.value || 0) - 1);
    }

    // Save Score AJAX
    async function handleScoreSubmit(e, matchId) {
      e.preventDefault();
      const scoreA = document.getElementById('score_a_' + matchId).value;
      const scoreB = document.getElementById('score_b_' + matchId).value;

      showAppLoader('Menyimpan Skor...', 'Memperbarui skor pertandingan & klasemen poin...');

      const fd = new FormData();
      fd.append('match_id', matchId);
      fd.append('score_a', scoreA);
      fd.append('score_b', scoreB);

      try {
        const res = await fetch(API_BASE + '?action=save_score', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, true);
          setTimeout(() => window.location.reload(), 500);
        } else {
          hideAppLoader();
          showToast(data.error ? data.error.message : 'Gagal menyimpan skor.', false);
        }
      } catch (err) {
        hideAppLoader();
        showToast('Koneksi terputus.', false);
      }
    }

    // Delete Single Match
    async function confirmDeleteMatch(matchId, courtNum) {
      const ok = await showConfirm({
        title: `Hapus Match Lapangan ${courtNum}?`,
        message: 'Pertandingan ini akan dibatalkan dan pemain kembali ke status istirahat ronde ini.',
        confirmText: 'Ya, Hapus Match',
        cancelText: 'Batal',
        isDanger: true
      });
      if (!ok) return;

      showAppLoader('Menghapus Match Lapangan...', 'Mengembalikan pemain ke antrean istirahat...');

      try {
        const fd = new FormData();
        fd.append('match_id', matchId);
        const res = await fetch(API_BASE + '?action=delete_match', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, true);
          const el = document.getElementById('match_card_' + matchId);
          if (el) el.remove();
          setTimeout(() => window.location.reload(), 500);
        } else {
          hideAppLoader();
          showToast(data.error ? data.error.message : 'Gagal menghapus match.', false);
        }
      } catch (err) {
        hideAppLoader();
        showToast('Koneksi terputus.', false);
      }
    }

    // Delete / Reset Entire Round
    async function confirmDeleteRound(sessionId, roundNum) {
      const ok = await showConfirm({
        title: `Reset & Hapus Ronde #${roundNum}?`,
        message: `Seluruh pertandingan pada Ronde #${roundNum} akan dibatalkan dan dihapus dari jadwal rotasi.`,
        confirmText: 'Ya, Reset Ronde',
        cancelText: 'Batal',
        isDanger: true
      });
      if (!ok) return;

      showAppLoader('Mereset Ronde...', 'Membatalkan seluruh pertandingan pada ronde ini...');

      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('round_number', roundNum);
        const res = await fetch(API_BASE + '?action=delete_round', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, true);
          setTimeout(() => window.location.reload(), 500);
        } else {
          hideAppLoader();
          showToast(data.error ? data.error.message : 'Gagal mereset ronde.', false);
        }
      } catch (err) {
        hideAppLoader();
        showToast('Koneksi terputus.', false);
      }
    }

    // Generate Round AJAX
    const generateForm = document.getElementById('generateRoundForm');
    if (generateForm) {
      generateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnGenerate');
        if (btn) btn.disabled = true;

        showAppLoader('Menyusun Rotasi Lapangan...', 'Sistem sedang membagi pemain ke lapangan berdasarkan rating & riwayat main...');

        try {
          const fd = new FormData(generateForm);
          const res = await fetch(API_BASE + '?action=generate_round', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          if (data.success) {
            window.location.reload();
          } else {
            hideAppLoader();
            showToast(data.error ? data.error.message : 'Gagal menyusun ronde.', false);
            if (btn) btn.disabled = false;
          }
        } catch (err) {
          hideAppLoader();
          showToast('Koneksi terputus.', false);
          if (btn) btn.disabled = false;
        }
      });
    }

    // Open Swap Player Modal (Admin Intervention)
    function openSwapModal(matchId, slot, playerName, currentPlayerId) {
      document.getElementById('swap_match_id').value = matchId;
      document.getElementById('swap_slot').value = slot;
      document.getElementById('swap_current_player_name').innerText = playerName;
      document.getElementById('swap_new_player_id').value = '';
      document.getElementById('swapPlayerModal').classList.remove('hidden');
    }

    const swapForm = document.getElementById('swapPlayerForm');
    if (swapForm) {
      swapForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAppLoader('Menukar Posisi Pemain...', 'Memperbarui susunan tim pada lapangan...');

        const fd = new FormData(swapForm);
        try {
          const res = await fetch(API_BASE + '?action=swap_match_player', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          if (data.success) {
            showToast(data.message, true);
            setTimeout(() => window.location.reload(), 500);
          } else {
            hideAppLoader();
            showToast(data.error ? data.error.message : 'Gagal menukar pemain.', false);
          }
        } catch (err) {
          hideAppLoader();
          showToast('Koneksi terputus.', false);
        }
      });
    }

    // 1-Tap Choice Buttons for Edit & Walk-In Modals (No Search Select!)
    function selectEditLevel(level) {
      document.getElementById('edit_level').value = level;
      document.querySelectorAll('.edit-lvl-btn').forEach(btn => {
        const active = btn.getAttribute('data-edit-level') === level;
        btn.className = 'edit-lvl-btn h-11 rounded-lg text-xs active:scale-95 transition-all ' + (active
          ? 'font-black border border-amber-400/40 bg-[#0b1e36] text-amber-400 shadow-xs'
          : 'font-bold border border-slate-200 bg-slate-50 text-slate-600');
      });
    }

    function selectEditStatus(status) {
      document.getElementById('edit_status').value = status;
      document.querySelectorAll('.edit-st-btn').forEach(btn => {
        const active = btn.getAttribute('data-edit-status') === status;
        btn.className = 'edit-st-btn h-11 rounded-lg text-xs active:scale-95 transition-all ' + (active
          ? 'font-black border border-amber-400/40 bg-[#0b1e36] text-amber-400 shadow-xs'
          : 'font-bold border border-slate-200 bg-slate-50 text-slate-600');
      });
    }

    function selectAddLevel(level) {
      document.getElementById('add_level').value = level;
      document.querySelectorAll('.add-lvl-btn').forEach(btn => {
        const active = btn.getAttribute('data-add-level') === level;
        btn.className = 'add-lvl-btn h-11 rounded-lg text-xs active:scale-95 transition-all ' + (active
          ? 'font-black border border-amber-400/40 bg-[#0b1e36] text-amber-400 shadow-xs'
          : 'font-bold border border-slate-200 bg-slate-50 text-slate-600');
      });
    }

    function selectAddStatus(status) {
      document.getElementById('add_status').value = status;
      document.querySelectorAll('.add-st-btn').forEach(btn => {
        const active = btn.getAttribute('data-add-status') === status;
        btn.className = 'add-st-btn h-11 rounded-lg text-xs active:scale-95 transition-all ' + (active
          ? 'font-black border border-amber-400/40 bg-[#0b1e36] text-amber-400 shadow-xs'
          : 'font-bold border border-slate-200 bg-slate-50 text-slate-600');
      });
    }

    // Open Edit Player Modal
    function openEditPlayerModal(playerId, name, level, phone, status) {
      document.getElementById('edit_player_id').value = playerId;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_phone').value = phone;
      selectEditLevel(level || 'Intermediate');
      selectEditStatus(status || 'confirmed');
      document.getElementById('editPlayerModal').classList.remove('hidden');
    }

    const editPlayerForm = document.getElementById('editPlayerForm');
    if (editPlayerForm) {
      editPlayerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAppLoader('Memperbarui Profil Pemain...', 'Menyimpan data pemain...');

        const fd = new FormData(editPlayerForm);
        try {
          const res = await fetch(API_BASE + '?action=edit_player', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          if (data.success) {
            showToast(data.message, true);
            setTimeout(() => window.location.reload(), 500);
          } else {
            hideAppLoader();
            showToast(data.error ? data.error.message : 'Gagal memperbarui pemain.', false);
          }
        } catch (err) {
          hideAppLoader();
          showToast('Koneksi terputus.', false);
        }
      });
    }

    // Promote Waiting List to Confirmed Active
    async function promoteWaitingList(sessionId, playerId) {
      showAppLoader('Mempromosikan Pemain...', 'Memindahkan pemain ke Sesi Utama...');
      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('player_id', playerId);

        const res = await fetch(API_BASE + '?action=promote_waiting_list', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, true);
          setTimeout(() => window.location.reload(), 500);
        } else {
          hideAppLoader();
          showToast(data.error ? data.error.message : 'Gagal mempromosikan pemain.', false);
        }
      } catch (err) {
        hideAppLoader();
        showToast('Koneksi terputus.', false);
      }
    }

    // Remove Participant from Session
    async function confirmRemoveParticipant(sessionId, playerId, name) {
      const ok = await showConfirm({
        title: 'Keluarkan Pemain?',
        message: `Apakah Anda yakin ingin mengeluarkan <strong>${name}</strong> dari daftar sesi mabar ini?`,
        confirmText: 'Ya, Keluarkan',
        cancelText: 'Batal',
        isDanger: true
      });
      if (!ok) return;

      showAppLoader('Mengeluarkan Pemain...', 'Memperbarui daftar sesi mabar...');

      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('player_id', playerId);

        const res = await fetch(API_BASE + '?action=remove_participant', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, true);
          setTimeout(() => window.location.reload(), 500);
        } else {
          hideAppLoader();
          showToast(data.error ? data.error.message : 'Gagal menghapus pemain.', false);
        }
      } catch (err) {
        hideAppLoader();
        showToast('Koneksi terputus.', false);
      }
    }

    // WhatsApp Import Form
    const waForm = document.getElementById('whatsappImportForm');
    if (waForm) {
      waForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnImportWa');
        if (btn) btn.disabled = true;

        showAppLoader('Mengimpor Pemain dari WA...', 'Sistem sedang membaca format nama, nomor, dan level pemain...');

        try {
          const fd = new FormData(waForm);
          const res = await fetch(API_BASE + '?action=import_whatsapp', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          hideAppLoader();
          if (data.success) {
            await showAlert({
              title: 'Impor WhatsApp Berhasil!',
              message: `Sebanyak <strong>${data.imported_count || ''} pemain</strong> berhasil ditambahkan ke sesi ini.`,
              buttonText: 'Tutup & Muat Ulang',
              type: 'success'
            });
            window.location.reload();
          } else {
            showToast(data.error ? data.error.message : 'Gagal mengimpor list WA.', false);
            if (btn) btn.disabled = false;
          }
        } catch (err) {
          hideAppLoader();
          showToast('Koneksi terputus.', false);
          if (btn) btn.disabled = false;
        }
      });
    }

    // Quick Add Player AJAX
    const quickAddForm = document.getElementById('quickAddForm');
    if (quickAddForm) {
      quickAddForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAppLoader('Menambahkan Pemain...', 'Mendaftarkan pemain walk-in ke sesi...');

        const fd = new FormData(quickAddForm);
        try {
          const res = await fetch(API_BASE + '?action=quick_add_player', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          if (data.success) {
            showToast(data.message, true);
            setTimeout(() => window.location.reload(), 500);
          } else {
            hideAppLoader();
            showToast(data.error ? data.error.message : 'Gagal menambah pemain.', false);
          }
        } catch (err) {
          hideAppLoader();
          showToast('Gagal menghubungi server.', false);
        }
      });
    }

    // Explicit Attendance Setter (Hadir = 1, Tidak Hadir = 0)
    async function setAttendance(sessionId, playerId, isPresent) {
      const btnHadir = document.getElementById('btn_hadir_' + playerId);
      const btnAbsen = document.getElementById('btn_absen_' + playerId);
      const badge = document.getElementById('att_badge_' + playerId);
      if (btnHadir) btnHadir.disabled = true;
      if (btnAbsen) btnAbsen.disabled = true;

      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('player_id', playerId);
        fd.append('is_present', isPresent);

        const res = await fetch(API_BASE + '?action=toggle_attendance', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(isPresent === 1 ? 'Pemain ditandai Hadir' : 'Pemain ditandai Tidak Hadir', true);
          if (btnHadir) {
            btnHadir.className = 'min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center ' + (isPresent === 1
              ? 'bg-[#0b1e36] text-amber-400 border border-amber-400/40 shadow-xs'
              : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200');
          }
          if (btnAbsen) {
            btnAbsen.className = 'min-h-[40px] px-2 rounded-lg text-[11px] sm:text-xs font-bold active:scale-95 transition-all flex items-center justify-center whitespace-nowrap ' + (isPresent === 0
              ? 'bg-rose-600 text-white border border-rose-700 shadow-xs'
              : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200');
          }
          if (badge) {
            badge.innerText = isPresent === 1 ? 'Hadir' : 'Tidak Hadir';
            badge.className = 'shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase ' + (isPresent === 1
              ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
              : 'bg-rose-50 text-rose-700 border border-rose-200');
          }
          if (data.summary && document.getElementById('kpiPresentCount')) {
            document.getElementById('kpiPresentCount').innerText = data.summary.total_present;
          }
        } else {
          showToast(data.error ? data.error.message : 'Gagal update kehadiran.', false);
        }
      } catch (err) {
        showToast('Gagal update kehadiran.', false);
      } finally {
        if (btnHadir) btnHadir.disabled = false;
        if (btnAbsen) btnAbsen.disabled = false;
      }
    }

    // Toggle Payment AJAX
    async function togglePayment(sessionId, playerId) {
      const btn = document.getElementById('btn_pay_' + playerId);
      const badge = document.getElementById('badge_' + playerId);
      if (!btn || btn.disabled) return;
      btn.disabled = true;
      const origText = btn.innerText;
      btn.innerHTML = '<span class="inline-block animate-spin mr-1">⌛</span>';

      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('player_id', playerId);

        const res = await fetch(API_BASE + '?action=toggle_payment', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast('Status pembayaran kas diperbarui', true);
          const s = data.summary;
          if (document.getElementById('kpiCashCollected')) {
            document.getElementById('kpiCashCollected').innerText = 'Rp ' + Number(s.total_cash_collected).toLocaleString('id-ID');
          }
          if (document.getElementById('kpiCashPending')) {
            document.getElementById('kpiCashPending').innerText = 'Rp ' + Number(s.total_cash_pending).toLocaleString('id-ID');
          }
          const expEl = document.getElementById('kpiSessionExpense');
          const netEl = document.getElementById('kpiSessionNet');
          if (expEl && netEl) {
            const exp = Number(expEl.getAttribute('data-expense') || 0);
            const ext = Number(expEl.getAttribute('data-extra') || 0);
            const net = Number(s.total_cash_collected) + ext - exp;
            netEl.innerText = (net < 0 ? '−' : '') + 'Rp ' + Math.abs(net).toLocaleString('id-ID');
            netEl.className = 'text-2xl sm:text-3xl font-black font-mono mt-0.5 ' + (net >= 0 ? 'text-white' : 'text-rose-300');
          }
          const pct = s.total_registered > 0 ? Math.round((s.total_paid / s.total_registered) * 100) : 0;
          if (document.getElementById('kpiPaidRatioBadge')) {
            document.getElementById('kpiPaidRatioBadge').innerText = `${s.total_paid}/${s.total_registered} Lunas (${pct}%)`;
          }
          if (document.getElementById('kpiPaidProgressBar')) {
            document.getElementById('kpiPaidProgressBar').style.width = pct + '%';
          }
          if (document.getElementById('chipUnpaidCount')) {
            document.getElementById('chipUnpaidCount').innerText = s.total_unpaid;
          }
          if (document.getElementById('chipPaidCount')) {
            document.getElementById('chipPaidCount').innerText = s.total_paid;
          }

          const row = btn.closest('.payment-row');
          if (badge.innerText.trim() === 'LUNAS') {
            badge.innerText = 'BELUM';
            badge.className = 'text-[10px] font-mono font-black px-2 py-1 rounded border bg-rose-50 text-rose-800 border-rose-300';
            btn.innerText = 'Bayar';
            btn.className = 'min-h-[42px] min-w-[74px] px-3 py-2 rounded-lg text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center bg-[#0b1e36] text-amber-400 hover:bg-[#071324] border border-amber-400/40 shadow-xs';
            if (row) row.setAttribute('data-status', 'unpaid');
          } else {
            badge.innerText = 'LUNAS';
            badge.className = 'text-[10px] font-mono font-black px-2 py-1 rounded border bg-emerald-50 text-emerald-800 border-emerald-300';
            btn.innerText = 'Batal';
            btn.className = 'min-h-[42px] min-w-[74px] px-3 py-2 rounded-lg text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300';
            if (row) row.setAttribute('data-status', 'paid');
          }
          applyPaymentFilters();
        } else {
          btn.innerText = origText;
          showToast(data.error ? data.error.message : 'Gagal update pembayaran.', false);
        }
      } catch (err) {
        btn.innerText = origText;
        showToast('Gagal update pembayaran.', false);
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    // Payment Sub-Tab & Filter Helpers
    let currentPayStatusFilter = 'all';
    function switchPaymentSubTab(tab) {
      const pIuran = document.getElementById('payPanelIuran');
      const pBiaya = document.getElementById('payPanelBiaya');
      const bIuran = document.getElementById('btnPayTabIuran');
      const bBiaya = document.getElementById('btnPayTabBiaya');
      if (!pIuran || !pBiaya) return;
      const isBiaya = (tab === 'biaya');
      pIuran.classList.toggle('hidden', isBiaya);
      pBiaya.classList.toggle('hidden', !isBiaya);
      if (bIuran && bBiaya) {
        bIuran.className = 'h-10 rounded-lg text-xs transition-all ' + (!isBiaya ? 'font-black bg-white text-slate-900 shadow-xs' : 'font-bold text-slate-600');
        bBiaya.className = 'h-10 rounded-lg text-xs transition-all ' + (isBiaya ? 'font-black bg-white text-slate-900 shadow-xs' : 'font-bold text-slate-600');
      }
    }

    function filterPaymentsByStatus(status) {
      currentPayStatusFilter = status;
      document.querySelectorAll('.pay-filter-chip').forEach(btn => {
        const active = btn.getAttribute('data-pay-filter') === status;
        btn.className = 'pay-filter-chip h-9 px-3 rounded-lg text-xs font-bold shrink-0 ' + (active ? 'bg-[#0b1e36] text-amber-400' : 'bg-slate-100 text-slate-600');
      });
      applyPaymentFilters();
    }

    function applyPaymentFilters() {
      const inp = document.getElementById('searchPaymentInput');
      const q = inp ? inp.value.toLowerCase().trim() : '';
      document.querySelectorAll('.payment-row').forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const st = row.getAttribute('data-status') || '';
        const matchQ = !q || name.includes(q);
        const matchSt = currentPayStatusFilter === 'all' || st === currentPayStatusFilter;
        row.style.display = (matchQ && matchSt) ? '' : 'none';
      });
    }

    if (window.location.hash === '#biaya') {
      document.addEventListener('DOMContentLoaded', () => switchPaymentSubTab('biaya'));
    }

    // Participant Search Filter
    function filterParticipants() {
      const q = document.getElementById('filterPlayerInput').value.toLowerCase();
      document.querySelectorAll('.participant-row').forEach(row => {
        const name = row.getAttribute('data-name') || '';
        row.style.display = name.includes(q) ? '' : 'none';
      });
    }

    // Global Form Submit Loaders
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Complete Session form
      const completeForm = document.querySelector('#modalCompleteSession form');
      if (completeForm) {
        completeForm.addEventListener('submit', () => {
          showAppLoader('Menutup Sesi Mabar...', 'Merekam total kas iuran dan memfinalisasi data sesi...');
        });
      }

      // 2. Multi-Photo Documentation Upload form
      const docForms = document.querySelectorAll('form');
      docForms.forEach(f => {
        if (f.querySelector('input[name="photo_files[]"]')) {
          f.addEventListener('submit', () => {
            showAppLoader('Mengunggah Dokumentasi Mabar...', 'Mohon tunggu, file foto sedang diunggah ke server...');
          });
        }
      });
    });
  </script>

  <script>
    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= $baseDir ?>/sw.js').catch(() => {});
      });
    }
  </script>

</body>
</html>