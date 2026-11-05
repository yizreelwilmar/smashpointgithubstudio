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
$sessStored = isset($_SESSION['courtside_session_id']) ? (int)$_SESSION['courtside_session_id'] : 0;
if ($sessStored > 0) {
    $sessionId = $sessStored;
} elseif ($isPic || $isPicFoto) {
    $assignedId = Auth::assignedSessionId();
    if ($assignedId && $assignedId > 0) {
        $sessionId = $assignedId;
    } else {
        $sessionId = !empty($allSchedules) ? (int)$allSchedules[0]['id'] : 0;
    }
    $_SESSION['courtside_session_id'] = $sessionId;
} else {
    $sessionId = !empty($allSchedules) ? (int)$allSchedules[0]['id'] : 0;
    $_SESSION['courtside_session_id'] = $sessionId;
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
            $allowedImages = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowedVideos = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
            $targetDir = __DIR__ . '/../public/uploads/gallery/';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0777, true);
            }

            if (empty($docTitle)) {
                $docTitle = "Dokumentasi Mabar " . ($currentSession ? $currentSession['venue_name'] : '');
            }

            // 1. Handle Multiple Media Files Upload (Photos & Videos)
            if (isset($_FILES['photo_files']) && is_array($_FILES['photo_files']['name'])) {
                $totalFiles = count($_FILES['photo_files']['name']);
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['photo_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $origName = $_FILES['photo_files']['name'][$i];
                        $tmpName = $_FILES['photo_files']['tmp_name'][$i];
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowedVideos, true)) {
                            // Upload Video File
                            $filename = 'video_mabar_' . $docSessionId . '_' . date('Ymd_His') . '_' . $i . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                            $targetFile = $targetDir . $filename;
                            if (move_uploaded_file($tmpName, $targetFile)) {
                                $videoMediaUrl = $baseDir . '/public/uploads/gallery/' . $filename;
                                $posterFallback = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80';
                                $subTitle = $totalFiles > 1 ? "{$docTitle} Video (#" . ($uploadedCount + 1) . ")" : "{$docTitle} (Video Highlight)";
                                Community::addGalleryPhoto($subTitle, $posterFallback, $docCaption, $docSessionId, $docDate, 'video', $videoMediaUrl);
                                $uploadedCount++;
                            }
                        } elseif (in_array($ext, $allowedImages, true)) {
                            // Upload Photo File
                            $filename = 'mabar_' . $docSessionId . '_' . date('Ymd_His') . '_' . $i . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                            $targetFile = $targetDir . $filename;
                            if (move_uploaded_file($tmpName, $targetFile)) {
                                $imgUrl = $baseDir . '/public/uploads/gallery/' . $filename;
                                $subTitle = $totalFiles > 1 ? "{$docTitle} (#" . ($uploadedCount + 1) . ")" : $docTitle;
                                Community::addGalleryPhoto($subTitle, $imgUrl, $docCaption, $docSessionId, $docDate, 'photo');
                                $uploadedCount++;
                            }
                        }
                    }
                }
            }

            // 2. Direct Video URL Input (YouTube / TikTok / Reel / MP4 link)
            $directVideoUrl = trim(isset($_POST['video_url']) ? $_POST['video_url'] : '');
            if ($uploadedCount === 0 && !empty($directVideoUrl)) {
                $posterUrl = trim(isset($_POST['image_url']) ? $_POST['image_url'] : '');
                if (empty($posterUrl)) {
                    $posterUrl = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80';
                }
                Community::addGalleryPhoto($docTitle, $posterUrl, $docCaption, $docSessionId, $docDate, 'video', $directVideoUrl);
                $uploadedCount++;
            }

            // 3. Direct Image URL Input
            $imageUrl = trim(isset($_POST['image_url']) ? $_POST['image_url'] : '');
            if ($uploadedCount === 0 && !empty($imageUrl)) {
                Community::addGalleryPhoto($docTitle, $imageUrl, $docCaption, $docSessionId, $docDate, 'photo');
                $uploadedCount++;
            }

            if ($uploadedCount > 0) {
                $appMessage = "Berhasil mengunggah {$uploadedCount} file media (foto/video) dokumentasi mabar! Langsung tayang di Galeri publik.";
            } elseif (!$appError) {
                $appError = "Pilih file foto atau video dari HP (bisa pilih banyak sekaligus) atau masukkan link URL video/foto.";
            }
        } elseif ($_POST['form_action'] === 'delete_documentation') {
            $photoId = (int)$_POST['photo_id'];
            Community::deleteGalleryPhoto($photoId);
            $appMessage = "Foto dokumentasi mabar berhasil dihapus.";
        } elseif ($_POST['form_action'] === 'complete_session') {
            Schedule::completeSession($sessionId);
            $appMessage = "Sesi mabar berhasil diflag selesai! Rekapitulasi kas dan riwayat pertandingan telah dicatat di sistem.";
            $currentSession = Schedule::find($sessionId);
        } elseif ($_POST['form_action'] === 'duplicate_next_week') {
            $newId = Schedule::duplicateForNextWeek($sessionId);
            if ($newId) {
                $_SESSION['courtside_session_id'] = $newId;
                $_SESSION['courtside_flash_msg'] = "Berhasil membuat sesi mabar pekan depan! Sesi baru telah aktif dan siap menerima pendaftaran.";
                header("Location: ?view=court", true, 302);
                exit;
            } else {
                $appError = "Gagal menduplikasi sesi untuk pekan depan.";
            }
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

    // Hitung berapa kali main setiap pemain pada sesi aktif ini
    $sessionMatchCounts = [];
    if (!empty($matches)) {
        foreach ($matches as $m) {
            foreach ([$m['team_a_p1'], $m['team_a_p2'], $m['team_b_p1'], $m['team_b_p2']] as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $sessionMatchCounts[$pid] = ($sessionMatchCounts[$pid] ?? 0) + 1;
                }
            }
        }
    }

    $sessionCourts = max(1, (int)($currentSession['court_count'] ?? 1));
    $courtStates = [];
    $busyPlayerIds = [];
    $completedMatches = [];

    for ($c = 1; $c <= $sessionCourts; $c++) {
        $courtStates[$c] = [
            'court_number' => $c,
            'active_match' => null,
            'last_completed' => null,
            'total_matches' => 0
        ];
    }

    if (!empty($matches)) {
        foreach ($matches as $m) {
            if ((int)$m['round_number'] > $latestRound) $latestRound = (int)$m['round_number'];
            $cNum = (int)($m['court_number'] ?? 1);

            if ($m['status'] === 'playing') {
                if (isset($courtStates[$cNum]) && $courtStates[$cNum]['active_match'] === null) {
                    $courtStates[$cNum]['active_match'] = $m;
                }
                $busyPlayerIds[] = (int)$m['team_a_p1'];
                $busyPlayerIds[] = (int)$m['team_a_p2'];
                $busyPlayerIds[] = (int)$m['team_b_p1'];
                $busyPlayerIds[] = (int)$m['team_b_p2'];
            } elseif ($m['status'] === 'completed') {
                $completedMatches[] = $m;
                if (isset($courtStates[$cNum])) {
                    if ($courtStates[$cNum]['last_completed'] === null || (int)$m['id'] > (int)$courtStates[$cNum]['last_completed']['id']) {
                        $courtStates[$cNum]['last_completed'] = $m;
                    }
                }
            }

            if (isset($courtStates[$cNum])) {
                $courtStates[$cNum]['total_matches']++;
            }
        }
        $busyPlayerIds = array_unique($busyPlayerIds);

        $activeRoundMatches = array_filter($matches, function($m) use ($latestRound) {
            return $m['round_number'] == $latestRound;
        });
    }

    // Pemain yang hadir dan SEDANG TIDAK BERMAIN di lapangan aktif mana pun (Antrean Istirahat)
    $idlePresentPlayers = [];
    foreach ($participants as $p) {
        $pid = (int)$p['player_id'];
        if ((int)$p['is_present'] === 1 && !in_array($pid, $busyPlayerIds)) {
            $pCopy = $p;
            $pCopy['play_count'] = $sessionMatchCounts[$pid] ?? 0;
            $idlePresentPlayers[] = $pCopy;
        }
    }
    // Urutkan antrean: pemain dengan jumlah main paling sedikit berada di posisi teratas
    usort($idlePresentPlayers, function($a, $b) {
        $cA = $a['play_count'];
        $cB = $b['play_count'];
        if ($cA !== $cB) return $cA <=> $cB;
        return strcmp($a['name'], $b['name']);
    });
    $restingPlayerIds = $idlePresentPlayers;
} else {
    $sessionPhotos = [];
    $sessionFinSummary = ['total_iuran_collected' => 0, 'total_iuran_pending' => 0, 'count_paid' => 0, 'count_unpaid' => 0, 'total_extra_income' => 0, 'total_income' => 0, 'total_expense' => 0, 'net_cashflow' => 0];
    $sessionFinRecords = [];
    $sessionMatchCounts = [];
    $courtStates = [];
    $busyPlayerIds = [];
    $idlePresentPlayers = [];
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

// Build WhatsApp Final Recap Text for 1-Tap Copy
$waRecapLines = [];
if ($currentSession) {
    $waRecapLines[] = "*REKAPITULASI MABAR " . strtoupper($brandName) . "*";
    $waRecapLines[] = "Tempat: " . ($currentSession['venue_name'] ?? '-');
    $waRecapLines[] = "Tanggal: " . ($currentSession['day_name'] ?? '-') . ", " . formatIndoDate($currentSession['schedule_date'] ?? date('Y-m-d'));
    $tSt = isset($currentSession['time_start']) ? $currentSession['time_start'] : ($currentSession['start_time'] ?? '19:00');
    $tEn = isset($currentSession['time_end']) ? $currentSession['time_end'] : ($currentSession['end_time'] ?? '22:00');
    $waRecapLines[] = "Waktu: " . substr($tSt, 0, 5) . " - " . substr($tEn, 0, 5) . " WIB";
    $waRecapLines[] = "";
    $waRecapLines[] = "*STATISTIK SESI:*";
    $waRecapLines[] = "• Total Pertandingan: " . count($completedMatches) . " Match";
    $waRecapLines[] = "• Total Pemain Hadir: " . ($paymentSummary['total_present'] ?? 0) . " Orang";
    $waRecapLines[] = "• Kas Terkumpul: Rp " . number_format($paymentSummary['total_cash_collected'] ?? 0, 0, ',', '.') . " (" . ($paymentSummary['total_paid'] ?? 0) . "/" . ($paymentSummary['total_present'] ?? 0) . " Lunas)";
    if (!empty($sessionLeaderboard)) {
        $waRecapLines[] = "";
        $waRecapLines[] = "*TOP PERFORMERS / KLASEMEN SESI:*";
        foreach (array_slice($sessionLeaderboard, 0, 5) as $rank => $sb) {
            $rNum = $rank + 1;
            $waRecapLines[] = "{$rNum}. " . $sb['name'] . " (" . $sb['total_matches'] . "x Main | " . $sb['wins'] . "M-" . $sb['losses'] . "K | " . $sb['total_points'] . " Poin)";
        }
    }
    $waRecapLines[] = "";
    $waRecapLines[] = "Terima kasih atas kehadiran seluruh pemain! Sampai jumpa di mabar berikutnya!";
}
$waRecapFullText = implode("\n", $waRecapLines);
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
          <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="w-7 h-7 object-contain border border-amber-400/40 bg-white p-0.5 shrink-0 rounded">
        <?php else: ?>
          <div class="w-7 h-7 bg-[#071324] border border-amber-400/40 flex items-center justify-center text-amber-400 font-black text-xs shrink-0 rounded">
            <?= htmlspecialchars($brandInitials) ?>
          </div>
        <?php endif; ?>
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-xs font-black tracking-wider text-white uppercase truncate font-mono max-w-[165px] sm:max-w-xs"><?= htmlspecialchars($brandName) ?></span>
            <span class="px-1.5 py-0.2 <?= $isPicFoto ? 'bg-cyan-400 text-cyan-950' : 'bg-amber-400 text-slate-950' ?> text-[9px] font-black uppercase tracking-wider shrink-0 rounded-xs">
              <?= $isPicFoto ? 'PIC FOTO' : ($isPic ? 'PIC LAPANGAN' : 'ADMIN') ?>
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <span class="text-[11px] font-semibold text-slate-300 hidden sm:inline-block max-w-[120px] truncate">
          <?= htmlspecialchars($user['name']) ?>
        </span>
        <a href="?action=logout" onclick="handleLogoutConfirm(event)" class="px-2.5 py-1 bg-slate-800/90 hover:bg-rose-900 text-slate-200 hover:text-white text-[11px] font-black uppercase tracking-wider transition-all border border-slate-700 rounded active:scale-95" title="Keluar">
          Keluar
        </a>
      </div>
    </div>

    <!-- Tier 2: Selected Venue & Session Switcher Bar -->
    <div class="max-w-5xl mx-auto px-3.5 py-1.5 bg-[#071324] flex items-center justify-between gap-2">
      <div class="flex items-center gap-2 min-w-0">
        <span class="w-2 h-2 rounded-full bg-amber-400 inline-block shrink-0 animate-pulse"></span>
        <span class="text-xs font-black text-white truncate leading-tight">
          <?= htmlspecialchars($currentSession ? $currentSession['venue_name'] : 'Tidak Ada Sesi') ?>
        </span>
        <span class="text-[10px] font-mono font-bold text-amber-400 bg-amber-400/10 px-1.5 py-0.5 border border-amber-400/30 shrink-0 rounded-xs">
          <?= htmlspecialchars($currentSession ? $currentSession['day_name'] : '-') ?> • <?= substr($currentSession ? (isset($currentSession['time_start']) ? $currentSession['time_start'] : $currentSession['start_time']) : '20:00', 0, 5) ?>
        </span>
      </div>

      <?php if ($isLoggedIn): ?>
        <button type="button" onclick="openSessionSheet()" class="shrink-0 px-2.5 py-1 bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-[11px] uppercase tracking-wider rounded shadow-xs transition-all">
          Ganti Sesi
        </button>
      <?php endif; ?>
    </div>
  </header>

  <!-- ========================================== -->
  <!-- MAIN WORKSPACE -->
  <!-- ========================================== -->
  <main class="max-w-5xl mx-auto px-3.5 sm:px-4 py-3 flex-1 w-full">

    <?php 
      $isSessionCompleted = ($currentSession && ($currentSession['status'] ?? '') === 'completed');
    ?>

    <?php if (!$currentSession): ?>
      <div class="p-8 bg-white border border-slate-200 text-center space-y-2 mt-4 rounded-xl shadow-xs">
        <h3 class="text-sm font-bold text-slate-800">Akun Belum Ditugaskan ke Sesi Mabar</h3>
        <p class="text-xs text-slate-500 leading-relaxed max-w-sm mx-auto">
          Akun PIC ini belum memiliki sesi yang ditentukan oleh Admin. Silakan hubungi Admin CMS untuk menetapkan sesi mabar Anda.
        </p>
        <div class="pt-2">
          <a href="?action=logout" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold inline-block rounded">
            Keluar & Login Ulang
          </a>
        </div>
      </div>
    <?php else: ?>

      <?php if (!empty($appMessage)): ?>
        <div class="mb-3 p-3 bg-amber-50 border border-amber-300 text-amber-950 text-xs font-semibold flex items-center justify-between rounded-lg shadow-2xs">
          <span><?= htmlspecialchars($appMessage) ?></span>
          <button onclick="this.parentElement.remove()" class="text-amber-900 font-bold ml-3 text-xs">&times;</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($appError)): ?>
        <div class="mb-3 p-3 bg-rose-50 border border-rose-300 text-rose-950 text-xs font-semibold flex items-center justify-between rounded-lg shadow-2xs">
          <span><?= htmlspecialchars($appError) ?></span>
          <button onclick="this.parentElement.remove()" class="text-rose-800 font-bold ml-3 text-xs">&times;</button>
        </div>
      <?php endif; ?>

      <?php if ($isSessionCompleted): 
        // Build WhatsApp Recap Text for 1-Tap Copy
        $recapWaText = "*REKAP AKHIR SESI MABAR " . strtoupper($brandName) . "*\n";
        $recapWaText .= "GOR: " . ($currentSession['venue_name'] ?? '-') . "\n";
        $recapWaText .= "Hari/Tgl: " . ($currentSession['day_name'] ?? '-') . ", " . formatIndoDate($currentSession['schedule_date'] ?? date('Y-m-d')) . "\n";
        $recapWaText .= "Pukul: " . substr($currentSession['time_start'] ?? '20:00', 0, 5) . " - " . substr($currentSession['time_end'] ?? '23:00', 0, 5) . " WIB\n";
        $recapWaText .= "Total Hadir: " . $paymentSummary['total_present'] . " Pemain\n";
        $recapWaText .= "Total Game Selesai: " . count($completedMatches) . " Match\n\n";

        if (!empty($sessionLeaderboard)) {
            $recapWaText .= "*TOP 5 KLASEMEN POIN:*\n";
            $top5 = array_slice($sessionLeaderboard, 0, 5);
            foreach ($top5 as $i => $row) {
                $recapWaText .= ($i + 1) . ". " . $row['name'] . " - " . $row['wins'] . "W/" . $row['losses'] . "L (" . $row['total_points'] . " Pts)\n";
            }
            $recapWaText .= "\n";
        }

        $recapWaText .= "*REKAPITULASI KAS:*\n";
        $recapWaText .= "• Kas Iuran Masuk: Rp " . number_format($paymentSummary['total_cash_collected'], 0, ',', '.') . " (" . $paymentSummary['total_paid'] . "/" . $paymentSummary['total_present'] . " Lunas)\n";
        if ($paymentSummary['total_unpaid'] > 0) {
            $recapWaText .= "• Belum Lunas (" . $paymentSummary['total_unpaid'] . " Org): Rp " . number_format($paymentSummary['total_cash_pending'], 0, ',', '.') . "\n";
        }
        $recapWaText .= "\nTerima kasih semuanya sudah hadir & mabar seru malam ini! Sampai ketemu di sesi mabar berikutnya! 🏸";
      ?>
        <textarea id="whatsappRecapContent" class="hidden"><?= htmlspecialchars($recapWaText) ?></textarea>

        <div class="mb-4 bg-gradient-to-br from-emerald-50 via-white to-emerald-50/40 border-2 border-emerald-400 text-emerald-950 p-4 sm:p-5 rounded-xl shadow-sm space-y-3.5 transition-all">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 border-b border-emerald-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-black uppercase tracking-wider font-mono text-emerald-900">SESI MABAR TELAH SELESAI & TERKUNCI</span>
              </div>
              <div class="text-xs text-emerald-800 mt-1">
                Ditutup pada <?= !empty($currentSession['completed_at']) ? date('d M Y, H:i', strtotime($currentSession['completed_at'])) . ' WIB' : 'Hari ini' ?>. Seluruh rotasi pertandingan dan pencatatan skor kini dalam mode arsip (terkunci).
              </div>
            </div>
            
            <div class="flex items-center gap-2 flex-wrap">
              <form method="POST" action="" class="inline">
                <input type="hidden" name="form_action" value="duplicate_next_week">
                <button type="submit" class="px-3 py-2 bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-xs uppercase tracking-wider rounded-lg shadow-xs transition-all flex items-center justify-center gap-1 border border-amber-300" title="Buat & Pindah ke Sesi Pekan Depan">
                  ⚡ Buat Sesi Pekan Depan →
                </button>
              </form>
              <button type="button" onclick="copyWhatsappRecap()" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs uppercase tracking-wider rounded-lg shadow-xs transition-all flex items-center justify-center">
                Salin Rekap WA
              </button>
              <button type="button" onclick="openSessionSheet()" class="px-3 py-2 bg-[#0b1e36] hover:bg-[#071324] active:scale-95 text-amber-400 font-black text-xs uppercase tracking-wider rounded-lg border border-amber-400/30 transition-all flex items-center justify-center">
                Pilih Sesi Lain
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-0.5 text-xs font-mono">
            <div class="bg-white/90 border border-emerald-200 p-2.5 rounded-lg">
              <span class="text-[10px] text-emerald-700 font-bold block uppercase">Game Selesai</span>
              <span class="text-base font-black text-emerald-950"><?= count($completedMatches) ?> Match</span>
            </div>
            <div class="bg-white/90 border border-emerald-200 p-2.5 rounded-lg">
              <span class="text-[10px] text-emerald-700 font-bold block uppercase">Pemain Hadir</span>
              <span class="text-base font-black text-emerald-950"><?= $paymentSummary['total_present'] ?> Orang</span>
            </div>
            <div class="bg-white/90 border border-emerald-200 p-2.5 rounded-lg">
              <span class="text-[10px] text-emerald-700 font-bold block uppercase">Kas Masuk</span>
              <span class="text-base font-black text-emerald-950">Rp <?= number_format($paymentSummary['total_cash_collected'], 0, ',', '.') ?></span>
            </div>
            <div class="bg-white/90 border border-emerald-200 p-2.5 rounded-lg">
              <span class="text-[10px] text-emerald-700 font-bold block uppercase">Status Iuran</span>
              <span class="text-base font-black <?= $paymentSummary['total_unpaid'] === 0 ? 'text-emerald-700' : 'text-amber-800' ?>">
                <?= $paymentSummary['total_paid'] ?>/<?= $paymentSummary['total_present'] ?> Lunas
              </span>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- ======================================================== -->
      <!-- TAB 1: ROTASI & PAPAN PERTANDINGAN (view=court)          -->
      <!-- ======================================================== -->
      <?php if ($view === 'court'): ?>
        
        <?php 
          $presentCount = (int)$paymentSummary['total_present'];
          $maxPossibleCourts = (int)floor($presentCount / 4);
          $neededPlayers = $sessionCourts * 4;
          $busyCount = count($busyPlayerIds);
          $idleCount = count($idlePresentPlayers);
          $activeCourtCount = 0;
          foreach ($courtStates as $cs) {
              if ($cs['active_match'] !== null) $activeCourtCount++;
          }
        ?>

        <!-- 1. Live Operational Summary Header -->
        <div class="bg-white border border-slate-200 p-3.5 sm:p-4 mb-4 shadow-xs space-y-3">
          
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2.5 py-1 bg-[#0b1e36] text-amber-400 font-mono font-black text-xs border border-amber-400/30">
                  <?= $sessionCourts ?> LAPANGAN
                </span>
                <span class="text-xs font-black text-slate-900">
                  <?= $activeCourtCount ?> Bertanding • <?= $sessionCourts - $activeCourtCount ?> Kosong / Siap Rotasi
                </span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">
                Sistem rotasi bergulir independen: lapangan yang selesai duluan dapat langsung memanggil 4 pemain berikutnya tanpa menunggu lapangan lain.
              </p>
            </div>

            <?php if ($currentSession['status'] !== 'completed' && !$isPicFoto): ?>
              <button type="button" onclick="document.getElementById('modalCompleteSession').classList.remove('hidden')" class="self-start sm:self-center px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 font-black text-xs uppercase tracking-wider transition-all active:scale-95 whitespace-nowrap min-h-[40px] flex items-center justify-center gap-1.5 shadow-2xs">
                <span>Selesai Sesi Mabar</span>
              </button>
            <?php endif; ?>
          </div>

          <!-- Quick Capacity & Player Distribution Meter -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-0.5">
            <div class="bg-slate-50 border border-slate-200 p-2.5">
              <span class="text-[10px] font-mono uppercase text-slate-500 font-bold block">Pemain Hadir</span>
              <div class="text-base font-black font-mono text-slate-900 mt-0.5"><?= $presentCount ?> Orang</div>
            </div>
            <div class="bg-amber-50 border border-amber-200 p-2.5">
              <span class="text-[10px] font-mono uppercase text-amber-800 font-bold block">Sedang Main</span>
              <div class="text-base font-black font-mono text-amber-950 mt-0.5"><?= $busyCount ?> Orang</div>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 p-2.5">
              <span class="text-[10px] font-mono uppercase text-emerald-800 font-bold block">Antrean Bebas</span>
              <div class="text-base font-black font-mono text-emerald-950 mt-0.5"><?= $idleCount ?> Orang</div>
            </div>
            <div class="bg-slate-50 border border-slate-200 p-2.5">
              <span class="text-[10px] font-mono uppercase text-slate-500 font-bold block">Total Game Selesai</span>
              <div class="text-base font-black font-mono text-slate-900 mt-0.5"><?= count($completedMatches) ?> Match</div>
            </div>
          </div>

        </div>

        <!-- 2. PAPAN STATUS TIAP LAPANGAN (COURT CARDS) -->
        <div class="space-y-4 mb-5">
          <?php for ($c = 1; $c <= $sessionCourts; $c++): 
            $cState = $courtStates[$c] ?? ['active_match' => null, 'last_completed' => null, 'total_matches' => 0];
            $match = $cState['active_match'];
            $lastMatch = $cState['last_completed'];
            $hasActive = ($match !== null);
          ?>
            <?php if ($hasActive): 
              $isCompleted = ($match['status'] === 'completed');
              
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
              <!-- LAPANGAN ONGOING (SEDANG MAIN) -->
              <div class="bg-white border-2 border-slate-300 shadow-sm rounded-sm overflow-hidden" id="match_card_<?= $match['id'] ?>">
                
                <!-- Court Card Header -->
                <div class="bg-[#0b1e36] text-white px-3.5 py-2.5 flex items-center justify-between gap-2 border-b border-slate-800">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-400 text-slate-950 font-black font-mono text-xs uppercase tracking-wider">
                      LAPANGAN <?= $c ?>
                    </span>
                    <span class="px-2 py-0.5 text-[10px] font-mono font-black uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/40 animate-pulse">
                      [SEDANG MAIN]
                    </span>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 border <?= $balanceBadgeClass ?>">
                      <?= $balanceStatus ?> (Rating <?= $ratingA ?> vs <?= $ratingB ?>)
                    </span>
                  </div>

                  <div class="flex items-center gap-2">
                    <?php if (!$isSessionCompleted): ?>
                      <button type="button" onclick="confirmDeleteMatch(<?= $match['id'] ?>, <?= $c ?>)" class="text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 text-xs font-bold px-2 py-1 border border-rose-500/40 transition-colors rounded" title="Batalkan match lapangan ini">
                        Batalkan
                      </button>
                    <?php else: ?>
                      <span class="text-[10px] font-mono font-bold bg-slate-800 text-slate-300 px-2 py-0.5 border border-slate-700 rounded-xs">Terkunci</span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Match Teams & Stepper Score Form -->
                <form class="score-form p-3.5 space-y-3" data-match-id="<?= $match['id'] ?>" onsubmit="handleScoreSubmit(event, <?= $match['id'] ?>)">
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    
                    <!-- Team A -->
                    <div class="bg-gradient-to-b from-blue-50/60 to-slate-50 p-3.5 border-2 border-blue-200 rounded-lg flex flex-col justify-between">
                      <div>
                        <div class="flex items-center justify-between text-xs font-mono font-black text-blue-950 uppercase tracking-wider border-b border-blue-200/80 pb-2 mb-2">
                          <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 bg-blue-600 rounded-full"></span>
                            <span>TIM A</span>
                          </span>
                          <span class="text-blue-900 bg-blue-100/90 px-2 py-0.5 border border-blue-200 text-[10px] font-bold rounded-xs">Rating: <?= $ratingA ?></span>
                        </div>

                        <!-- Player 1 -->
                        <?php 
                          $pA1Played = $sessionMatchCounts[(int)$match['team_a_p1']] ?? 0;
                          $pA2Played = $sessionMatchCounts[(int)$match['team_a_p2']] ?? 0;
                        ?>
                        <div class="flex items-center justify-between gap-1.5 py-1.5 border-b border-slate-200/60">
                          <div class="min-w-0 flex items-center gap-1.5 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_a_p1_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-blue-700 bg-blue-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_a_p1_level']) ?>]</span>
                            <span class="text-[9px] font-mono font-bold bg-[#0b1e36] text-amber-300 border border-amber-400/40 px-1.5 py-0.2 rounded-xs" title="Jumlah main sesi ini"><?= $pA1Played ?>x Main</span>
                          </div>
                          <?php if (!$isSessionCompleted): ?>
                            <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_a_p1', '<?= htmlspecialchars(addslashes($match['team_a_p1_name'])) ?>', <?= (int)$match['team_a_p1'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded shadow-2xs" title="Tukar Pemain">Tukar</button>
                          <?php endif; ?>
                        </div>

                        <!-- Player 2 -->
                        <div class="flex items-center justify-between gap-1.5 py-1.5">
                          <div class="min-w-0 flex items-center gap-1.5 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_a_p2_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-blue-700 bg-blue-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_a_p2_level']) ?>]</span>
                            <span class="text-[9px] font-mono font-bold bg-[#0b1e36] text-amber-300 border border-amber-400/40 px-1.5 py-0.2 rounded-xs" title="Jumlah main sesi ini"><?= $pA2Played ?>x Main</span>
                          </div>
                          <?php if (!$isSessionCompleted): ?>
                            <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_a_p2', '<?= htmlspecialchars(addslashes($match['team_a_p2_name'])) ?>', <?= (int)$match['team_a_p2'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded shadow-2xs" title="Tukar Pemain">Tukar</button>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Stepper Controller Score A -->
                      <div class="flex items-center justify-center gap-2 mt-3 pt-2.5 border-t border-blue-200/60 bg-white p-2 rounded shadow-inner">
                        <span class="text-xs font-mono font-bold text-slate-600 mr-1">Skor:</span>
                        <?php if (!$isSessionCompleted): ?>
                          <button type="button" onclick="decrementScore('a', <?= $match['id'] ?>)" class="w-12 h-11 bg-slate-100 hover:bg-slate-200 active:scale-90 text-slate-900 font-black text-2xl flex items-center justify-center select-none border border-slate-300 rounded transition-transform">-</button>
                        <?php endif; ?>
                        <input type="number" 
                               id="score_a_<?= $match['id'] ?>" 
                               value="<?= (int)$match['score_team_a'] ?>" 
                               <?= $isSessionCompleted ? 'disabled' : '' ?>
                               onclick="this.select()"
                               onfocus="if(this.value==='0') this.value='';" 
                               onblur="if(this.value==='') this.value='0';" 
                               oninput="if(this.value.length > 1 && this.value.startsWith('0')) this.value = parseInt(this.value, 10);"
                               class="w-16 h-11 text-center bg-white border-2 border-slate-400 font-mono font-black text-2xl text-slate-900 focus:outline-none focus:border-[#0b1e36] rounded shadow-xs <?= $isSessionCompleted ? 'opacity-80 bg-slate-100' : '' ?>">
                        <?php if (!$isSessionCompleted): ?>
                          <button type="button" onclick="incrementScore('a', <?= $match['id'] ?>)" class="w-12 h-11 bg-[#0b1e36] hover:bg-[#071324] active:scale-90 text-amber-400 font-black text-2xl flex items-center justify-center select-none border border-amber-400/40 rounded transition-transform">+</button>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Team B -->
                    <div class="bg-gradient-to-b from-rose-50/60 to-slate-50 p-3.5 border-2 border-rose-200 rounded-lg flex flex-col justify-between">
                      <div>
                        <div class="flex items-center justify-between text-xs font-mono font-black text-rose-950 uppercase tracking-wider border-b border-rose-200/80 pb-2 mb-2">
                          <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 bg-rose-600 rounded-full"></span>
                            <span>TIM B</span>
                          </span>
                          <span class="text-rose-900 bg-rose-100/90 px-2 py-0.5 border border-rose-200 text-[10px] font-bold rounded-xs">Rating: <?= $ratingB ?></span>
                        </div>

                        <!-- Player 1 -->
                        <?php 
                          $pB1Played = $sessionMatchCounts[(int)$match['team_b_p1']] ?? 0;
                          $pB2Played = $sessionMatchCounts[(int)$match['team_b_p2']] ?? 0;
                        ?>
                        <div class="flex items-center justify-between gap-1.5 py-1.5 border-b border-slate-200/60">
                          <div class="min-w-0 flex items-center gap-1.5 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_b_p1_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-rose-700 bg-rose-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_b_p1_level']) ?>]</span>
                            <span class="text-[9px] font-mono font-bold bg-[#0b1e36] text-amber-300 border border-amber-400/40 px-1.5 py-0.2 rounded-xs" title="Jumlah main sesi ini"><?= $pB1Played ?>x Main</span>
                          </div>
                          <?php if (!$isSessionCompleted): ?>
                            <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_b_p1', '<?= htmlspecialchars(addslashes($match['team_b_p1_name'])) ?>', <?= (int)$match['team_b_p1'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded shadow-2xs" title="Tukar Pemain">Tukar</button>
                          <?php endif; ?>
                        </div>

                        <!-- Player 2 -->
                        <div class="flex items-center justify-between gap-1.5 py-1.5">
                          <div class="min-w-0 flex items-center gap-1.5 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-900 truncate block"><?= htmlspecialchars($match['team_b_p2_name']) ?></span>
                            <span class="text-[9px] font-bold font-mono uppercase text-rose-700 bg-rose-100/50 px-1 rounded-xs">[<?= htmlspecialchars($match['team_b_p2_level']) ?>]</span>
                            <span class="text-[9px] font-mono font-bold bg-[#0b1e36] text-amber-300 border border-amber-400/40 px-1.5 py-0.2 rounded-xs" title="Jumlah main sesi ini"><?= $pB2Played ?>x Main</span>
                          </div>
                          <?php if (!$isSessionCompleted): ?>
                            <button type="button" onclick="openSwapModal(<?= $match['id'] ?>, 'team_b_p2', '<?= htmlspecialchars(addslashes($match['team_b_p2_name'])) ?>', <?= (int)$match['team_b_p2'] ?>)" class="text-[11px] text-slate-700 hover:text-slate-950 font-bold px-2.5 py-1 border border-slate-300 bg-white hover:bg-slate-100 active:scale-95 shrink-0 rounded shadow-2xs" title="Tukar Pemain">Tukar</button>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Stepper Controller Score B -->
                      <div class="flex items-center justify-center gap-2 mt-3 pt-2.5 border-t border-rose-200/60 bg-white p-2 rounded shadow-inner">
                        <span class="text-xs font-mono font-bold text-slate-600 mr-1">Skor:</span>
                        <?php if (!$isSessionCompleted): ?>
                          <button type="button" onclick="decrementScore('b', <?= $match['id'] ?>)" class="w-12 h-11 bg-slate-100 hover:bg-slate-200 active:scale-90 text-slate-900 font-black text-2xl flex items-center justify-center select-none border border-slate-300 rounded transition-transform">-</button>
                        <?php endif; ?>
                        <input type="number" 
                               id="score_b_<?= $match['id'] ?>" 
                               value="<?= (int)$match['score_team_b'] ?>" 
                               <?= $isSessionCompleted ? 'disabled' : '' ?>
                               onclick="this.select()"
                               onfocus="if(this.value==='0') this.value='';" 
                               onblur="if(this.value==='') this.value='0';" 
                               oninput="if(this.value.length > 1 && this.value.startsWith('0')) this.value = parseInt(this.value, 10);"
                               class="w-16 h-11 text-center bg-white border-2 border-slate-400 font-mono font-black text-2xl text-slate-900 focus:outline-none focus:border-[#0b1e36] rounded shadow-xs <?= $isSessionCompleted ? 'opacity-80 bg-slate-100' : '' ?>">
                        <?php if (!$isSessionCompleted): ?>
                          <button type="button" onclick="incrementScore('b', <?= $match['id'] ?>)" class="w-12 h-11 bg-[#0b1e36] hover:bg-[#071324] active:scale-90 text-amber-400 font-black text-2xl flex items-center justify-center select-none border border-amber-400/40 rounded transition-transform">+</button>
                        <?php endif; ?>
                      </div>
                    </div>

                  </div>

                  <!-- Save Score Button -->
                  <?php if (!$isSessionCompleted): ?>
                    <button type="submit" class="w-full min-h-[48px] py-3 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/30 active:scale-[0.99] flex items-center justify-center gap-2 rounded-lg shadow-sm">
                      <span>Simpan Skor & Selesaikan Lapangan <?= $c ?></span>
                    </button>
                  <?php else: ?>
                    <div class="w-full py-2.5 bg-slate-100 text-slate-500 font-mono text-xs font-bold text-center border border-slate-200 rounded-lg">
                      Sesi Mabar Telah Ditutup • Pertandingan Ini Telah Diarsipkan
                    </div>
                  <?php endif; ?>
                </form>

              </div>
            <?php else: ?>
              <!-- LAPANGAN KOSONG / SIAP ROTASI BERGULIR -->
              <div class="bg-white border-2 border-dashed border-emerald-300 shadow-xs p-4 sm:p-5 space-y-4 rounded-xl">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 bg-[#0b1e36] text-amber-400 font-mono font-black text-xs uppercase tracking-wider rounded">
                      LAPANGAN <?= $c ?>
                    </span>
                    <span class="px-2.5 py-0.5 text-[10px] font-mono font-black uppercase tracking-wider <?= $isSessionCompleted ? 'bg-slate-100 text-slate-600 border border-slate-300' : 'bg-emerald-50 text-emerald-800 border border-emerald-300' ?> rounded-xs">
                      <?= $isSessionCompleted ? '[SELESAI / TERKUNCI]' : '[KOSONG / SIAP ROTASI]' ?>
                    </span>
                  </div>

                  <?php if ($lastMatch): ?>
                    <span class="text-[11px] font-mono text-slate-500">
                      Match Terakhir: <?= htmlspecialchars($lastMatch['team_a_p1_name']) ?>/<?= htmlspecialchars($lastMatch['team_a_p2_name']) ?> (<?= $lastMatch['score_team_a'] ?>) vs <?= htmlspecialchars($lastMatch['team_b_p1_name']) ?>/<?= htmlspecialchars($lastMatch['team_b_p2_name']) ?> (<?= $lastMatch['score_team_b'] ?>)
                    </span>
                  <?php endif; ?>
                </div>

                <?php if ($isSessionCompleted): ?>
                  <div class="p-4 bg-slate-50 border border-slate-200 text-center space-y-1 rounded-lg">
                    <span class="text-xs font-bold text-slate-700 block">Lapangan <?= $c ?> Selesai</span>
                    <span class="text-[11px] text-slate-400 font-mono">Sesi mabar telah ditutup & rotasi lapangan dikunci.</span>
                  </div>
                <?php else: ?>
                  <!-- Callout explanation & Priority Queue Preview -->
                  <div class="bg-emerald-50/60 border border-emerald-200 p-3.5 space-y-2.5 rounded-lg">
                    <div class="text-xs font-bold text-emerald-950 flex items-center justify-between">
                      <span>Lapangan <?= $c ?> Siap Digunakan!</span>
                      <span class="font-mono text-[11px] text-emerald-800"><?= $idleCount ?> pemain menganggur</span>
                    </div>

                    <?php 
                      $preview4 = array_slice($idlePresentPlayers, 0, 4);
                    ?>

                    <?php if (count($preview4) >= 4): ?>
                      <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-900 block mb-1">
                          4 Pemain Prioritas Utama Antrean (Paling Sedikit Main):
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                          <?php foreach ($preview4 as $pIdx => $pItem): ?>
                            <span class="px-2 py-1 bg-white border border-emerald-300 text-slate-900 text-xs font-bold inline-flex items-center gap-1 shadow-2xs rounded">
                              <span class="font-mono text-emerald-800 text-[10px] font-black">#<?= $pIdx + 1 ?></span>
                              <span><?= htmlspecialchars($pItem['name']) ?></span>
                              <span class="text-[9px] font-mono text-slate-500">[<?= htmlspecialchars($pItem['level'] ?: 'Int') ?>]</span>
                              <span class="text-[9px] font-mono font-bold bg-[#0b1e36] text-amber-300 px-1 rounded-xs"><?= $pItem['play_count'] ?>x</span>
                            </span>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <!-- Rolling Dispatch Action Button -->
                      <button type="button" onclick="generateSingleCourt(<?= $sessionId ?>, <?= $c ?>)" class="w-full min-h-[48px] py-3 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/40 active:scale-[0.99] flex items-center justify-center gap-2 rounded-lg shadow-md">
                        <span>+ Panggil 4 Pemain Berikutnya ke Lapangan <?= $c ?></span>
                      </button>
                    <?php else: ?>
                      <div class="text-xs text-amber-900 leading-relaxed">
                        Saat ini hanya tersisa <strong><?= count($preview4) ?> pemain bebas</strong> di antrean istirahat. Butuh minimal <strong>4 pemain bebas</strong> untuk mengisi Lapangan <?= $c ?>. Pemain dari lapangan lain akan otomatis masuk antrean segera setelah pertandingannya selesai dicatat skornya.
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

              </div>
            <?php endif; ?>
          <?php endfor; ?>
        </div>

        <!-- 3. LIVE ANTREAN ISTIRAHAT (QUEUE PANEL) -->
        <div class="bg-white border border-slate-200 p-4 mb-5 shadow-xs space-y-3 rounded-xl">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 border-b border-slate-100 pb-2.5">
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider font-mono text-slate-900">
                Antrean Pemain Istirahat (<?= count($idlePresentPlayers) ?> Orang)
              </h3>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Diurutkan otomatis dari yang paling sedikit main agar seluruh peserta mendapat porsi bertanding yang adil.
              </p>
            </div>
            <span class="text-[10px] font-mono text-slate-400 font-bold self-start sm:self-center">
              Top 4 = Prioritas Lapangan Berikutnya
            </span>
          </div>

          <?php if (empty($idlePresentPlayers)): ?>
            <div class="p-6 text-center text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg">
              Seluruh pemain yang hadir saat ini sedang berada di lapangan bertanding.
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
              <?php foreach ($idlePresentPlayers as $idx => $rp): 
                $isNextUp = ($idx < 4);
              ?>
                <div class="p-2.5 border <?= $isNextUp ? 'bg-amber-50/60 border-amber-300 shadow-2xs' : 'bg-slate-50 border-slate-200' ?> flex items-center justify-between gap-2 rounded-lg transition-all">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-6 h-6 flex items-center justify-center font-mono font-black text-xs <?= $isNextUp ? 'bg-[#0b1e36] text-amber-400' : 'bg-slate-200 text-slate-700' ?> shrink-0 rounded">
                      <?= $idx + 1 ?>
                    </span>
                    <div class="min-w-0">
                      <div class="font-bold text-xs text-slate-900 truncate"><?= htmlspecialchars($rp['name']) ?></div>
                      <div class="text-[10px] text-slate-500 font-mono flex items-center gap-1.5">
                        <span>[<?= htmlspecialchars($rp['level'] ?: 'Int') ?>]</span>
                        <span class="font-bold <?= $rp['play_count'] > 0 ? 'text-blue-900' : 'text-emerald-700' ?>"><?= $rp['play_count'] ?>x main</span>
                      </div>
                    </div>
                  </div>

                  <?php if ($isNextUp): ?>
                    <span class="text-[9px] font-mono font-black uppercase tracking-wider px-1.5 py-0.5 bg-amber-400 text-slate-950 shrink-0 rounded-xs">
                      NEXT
                    </span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- 4. PENGATURAN ROTASI SERENTAK (BATCH GENERATE SEMUA LAPANGAN) -->
        <?php if (!$isSessionCompleted): ?>
          <details class="bg-white border border-slate-200 shadow-xs mb-4 group rounded-xl overflow-hidden">
            <summary class="p-3.5 cursor-pointer flex items-center justify-between text-xs font-black uppercase tracking-wider font-mono text-slate-800 hover:bg-slate-50 transition-colors select-none">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-300 text-[10px] rounded-xs">OPSI LAIN</span>
                <span>Acak Semua Lapangan Sekaligus (Rotasi Serentak Ronde)</span>
              </div>
              <span class="text-xs text-slate-400 font-mono">PILIH</span>
            </summary>

            <div class="p-4 border-t border-slate-100 bg-slate-50/50 space-y-3">
              <p class="text-xs text-slate-600 leading-relaxed">
                Gunakan opsi ini hanya jika ingin memulai ronde serentak untuk semua lapangan sekaligus (misalnya di awal pembukaan sesi mabar).
              </p>

              <form id="generateRoundForm" class="pt-1">
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                  <div class="md:col-span-4">
                    <label for="courtCountSelect" class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">
                      Pilih Jumlah Lapangan:
                    </label>
                    <select name="court_count" id="courtCountSelect" data-no-search class="w-full bg-white border border-slate-300 px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none min-h-[44px] rounded-lg">
                      <?php for ($c = 1; $c <= $sessionCourts; $c++): 
                        $reqP = $c * 4;
                        $canPlay = ($presentCount >= $reqP);
                      ?>
                        <option value="<?= $c ?>" <?= ($c === min($sessionCourts, max(1, $maxPossibleCourts))) ? 'selected' : '' ?>>
                          <?= $c ?> Lapangan <?= $canPlay ? "(Cukup - {$reqP} Pemain)" : "(Kurang Pemain - Butuh {$reqP})" ?>
                        </option>
                      <?php endfor; ?>
                    </select>
                  </div>

                  <div class="md:col-span-4">
                    <label for="algorithmSelect" class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">
                      Strategi Pembagian Tim:
                    </label>
                    <select name="algorithm" id="algorithmSelect" data-no-search class="w-full bg-white border border-slate-300 px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none min-h-[44px] rounded-lg">
                      <option value="balanced_level" selected>Fair Level (Kombinasi Seimbang)</option>
                      <option value="tiered_level">Selevel (Pure Tier Mahir/Pemula)</option>
                      <option value="fair_social">Kocok Campur Merata</option>
                    </select>
                  </div>

                  <div class="md:col-span-4 flex items-center gap-2">
                    <button type="submit" id="btnGenerate" class="flex-1 py-2.5 px-4 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/30 active:scale-95 whitespace-nowrap min-h-[44px] flex items-center justify-center gap-2 shadow-xs rounded-lg">
                      <span>Acak Semua Lapangan</span>
                    </button>
                    <?php if (!empty($activeRoundMatches)): ?>
                      <button type="button" onclick="confirmDeleteRound(<?= $sessionId ?>, <?= $latestRound ?>)" class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 active:scale-95 min-h-[44px] shrink-0 rounded-lg" title="Hapus dan reset ronde ini">
                        Reset
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </form>
            </div>
          </details>
        <?php endif; ?>

        <!-- 5. RIWAYAT PERTANDINGAN SELESAI (COMPACT LEDGER LIST - NO BULKY CARDS) -->
        <?php if (!empty($completedMatches)): ?>
          <details <?= $isSessionCompleted ? 'open' : '' ?> class="bg-white border <?= $isSessionCompleted ? 'border-2 border-emerald-500 shadow-md' : 'border-slate-200 shadow-xs' ?> mb-5 rounded-xl overflow-hidden group">
            <summary class="p-3.5 cursor-pointer flex items-center justify-between text-xs font-black uppercase tracking-wider font-mono text-slate-800 hover:bg-slate-50 transition-colors select-none">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 <?= $isSessionCompleted ? 'bg-emerald-600 text-white' : 'bg-slate-900 text-amber-400' ?> text-[10px] font-black rounded">
                  <?= $isSessionCompleted ? 'HASIL RESMI' : 'SELESAI' ?>
                </span>
                <span>Riwayat Pertandingan Selesai (<?= count($completedMatches) ?> Match)</span>
              </div>
              <span class="text-[11px] font-mono text-slate-500 font-bold"><?= $isSessionCompleted ? 'TERBUKA' : 'LIHAT' ?></span>
            </summary>

            <div class="border-t border-slate-100 bg-slate-50/50">
              <div class="px-3.5 py-2 text-[10px] font-mono text-slate-500 flex items-center justify-between bg-white/70 border-b border-slate-100">
                <span>Daftar skor ringkas • Skor & nama pemenang ditandai hijau</span>
                <span class="font-bold text-slate-700">Total: <?= count($completedMatches) ?> Game</span>
              </div>

              <!-- Compact Scoreboard Ledger (Row-by-Row) -->
              <div class="divide-y divide-slate-100 bg-white">
                <?php foreach (array_reverse($completedMatches) as $cm): 
                  $scA = (int)$cm['score_team_a'];
                  $scB = (int)$cm['score_team_b'];
                  $isWinA = ($scA > $scB);
                  $isWinB = ($scB > $scA);
                ?>
                  <div class="p-2.5 sm:px-3.5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-2">
                    
                    <!-- Meta info: Court & Round Tag -->
                    <div class="shrink-0 w-16 sm:w-20">
                      <div class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-800 text-[10px] font-mono font-bold rounded border border-slate-200">
                        Lap <?= $cm['court_number'] ?>
                      </div>
                      <div class="text-[9px] font-mono text-slate-400 mt-0.5">
                        Game #<?= $cm['round_number'] ?>
                      </div>
                    </div>

                    <!-- Teams Matchup (Compact 2-Line Left vs Right) -->
                    <div class="flex-1 min-w-0 grid grid-cols-2 gap-2 text-xs">
                      <!-- Team A -->
                      <div class="min-w-0 pr-1 text-right flex flex-col justify-center <?= $isWinA ? 'text-emerald-950 font-bold' : 'text-slate-500' ?>">
                        <div class="truncate text-[11px] sm:text-xs <?= $isWinA ? 'font-black text-emerald-900' : 'font-medium text-slate-700' ?>">
                          <?= htmlspecialchars($cm['team_a_p1_name']) ?>
                        </div>
                        <div class="truncate text-[11px] sm:text-xs <?= $isWinA ? 'font-black text-emerald-900' : 'font-medium text-slate-600' ?>">
                          <?= htmlspecialchars($cm['team_a_p2_name']) ?>
                        </div>
                        <?php if ($isWinA): ?>
                          <span class="inline-block text-[8px] font-mono uppercase font-black text-emerald-700 tracking-wider">★ MENANG</span>
                        <?php endif; ?>
                      </div>

                      <!-- Team B -->
                      <div class="min-w-0 pl-1 text-left flex flex-col justify-center <?= $isWinB ? 'text-emerald-950 font-bold' : 'text-slate-500' ?>">
                        <div class="truncate text-[11px] sm:text-xs <?= $isWinB ? 'font-black text-emerald-900' : 'font-medium text-slate-700' ?>">
                          <?= htmlspecialchars($cm['team_b_p1_name']) ?>
                        </div>
                        <div class="truncate text-[11px] sm:text-xs <?= $isWinB ? 'font-black text-emerald-900' : 'font-medium text-slate-600' ?>">
                          <?= htmlspecialchars($cm['team_b_p2_name']) ?>
                        </div>
                        <?php if ($isWinB): ?>
                          <span class="inline-block text-[8px] font-mono uppercase font-black text-emerald-700 tracking-wider">★ MENANG</span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Center Pill Score -->
                    <div class="shrink-0 flex items-center justify-center">
                      <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md font-mono text-xs font-black border <?= ($isWinA || $isWinB) ? 'bg-slate-900 text-white border-slate-800' : 'bg-slate-100 text-slate-800 border-slate-200' ?>">
                        <span class="<?= $isWinA ? 'text-emerald-400 font-black' : 'text-slate-300' ?>"><?= $scA ?></span>
                        <span class="text-slate-500 font-normal">:</span>
                        <span class="<?= $isWinB ? 'text-emerald-400 font-black' : 'text-slate-300' ?>"><?= $scB ?></span>
                      </div>
                    </div>

                    <?php if (!$isSessionCompleted): ?>
                      <div class="shrink-0">
                        <button type="button" onclick="confirmDeleteMatch(<?= $cm['id'] ?>, <?= $cm['court_number'] ?>)" class="text-slate-400 hover:text-rose-600 text-xs p-1 hover:bg-rose-50 rounded transition-colors" title="Hapus skor pertandingan ini">
                          ✕
                        </button>
                      </div>
                    <?php endif; ?>

                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </details>
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
              <?php if (!$isSessionCompleted): ?>
                <!-- Paste WA Template Button -->
                <button onclick="document.getElementById('whatsappModal').classList.remove('hidden')" class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider active:scale-95 border border-amber-400 flex items-center justify-center rounded-lg shadow-xs min-h-[44px]">
                  <span>Paste List WA</span>
                </button>
                
                <!-- Quick Add Walk-In Button -->
                <button onclick="document.getElementById('quickAddModal').classList.remove('hidden')" class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-black text-xs uppercase tracking-wider active:scale-95 border border-amber-400/30 flex items-center justify-center rounded-lg shadow-xs min-h-[44px]">
                  <span>+ Tambah Walk-In</span>
                </button>
              <?php else: ?>
                <span class="px-3.5 py-2.5 bg-slate-100 text-slate-500 font-mono font-bold text-xs uppercase tracking-wider border border-slate-200 rounded-lg">
                  Sesi Selesai (Data Dikunci)
                </span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Filter Search (No Icons) -->
          <div>
            <input type="text" id="filterPlayerInput" onkeyup="filterParticipants()" placeholder="Ketik untuk cari nama pemain..." class="w-full bg-slate-50 border border-slate-300 px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 min-h-[44px] rounded-lg">
          </div>
        </div>

        <!-- 1. SECTION: PEMAIN SESI UTAMA -->
        <div class="space-y-3 mb-6">
          <div class="flex items-center justify-between px-1 border-b border-slate-200 pb-2">
            <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
              <span>Pemain Sesi Utama (<?= count($confirmedParticipants) ?>)</span>
            </h3>
            <span class="text-[11px] font-mono text-slate-500"><?= $isSessionCompleted ? 'Mode Terkunci' : 'Klik Edit untuk ubah Level / Waiting List' ?></span>
          </div>

          <?php if (empty($confirmedParticipants)): ?>
            <div class="p-6 bg-white border border-slate-200 rounded-lg text-center text-xs text-slate-400">
              Belum ada pemain di Sesi Utama.
            </div>
          <?php else: ?>
            <div class="space-y-2.5">
              <?php foreach (array_values($confirmedParticipants) as $idx => $p): 
                $isPresent = ((int)$p['is_present'] === 1);
                $lvl = $p['level'] ?: 'Intermediate';
                $lvlBadge = ($lvl === 'Advance') ? 'bg-amber-100 text-amber-900 border-amber-300' : (($lvl === 'Beginner') ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-blue-100 text-blue-900 border-blue-300');
                $pid = (int)$p['player_id'];
                $playCount = $sessionMatchCounts[$pid] ?? 0;
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
                          <span class="px-2 py-0.5 rounded border text-[9px] font-mono font-bold <?= $playCount > 0 ? 'bg-[#0b1e36] text-amber-300 border-amber-400/30' : 'bg-slate-100 text-slate-600 border-slate-200' ?>"><?= $playCount ?>x Main</span>
                          <?php if (!empty($p['phone'])): ?>
                            <span class="font-mono text-[10px] text-slate-500"><?= htmlspecialchars($p['phone']) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <span id="att_badge_<?= $pid ?>" class="shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase <?= $isPresent ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                      <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                    </span>
                  </div>

                  <div class="grid grid-cols-3 sm:flex items-center gap-1.5 pt-2.5 sm:pt-0 border-t sm:border-t-0 border-slate-100 shrink-0">
                    <?php if (!$isSessionCompleted): ?>
                      <!-- Single Unified Attendance Toggle Button -->
                      <button type="button" id="btn_att_<?= $pid ?>" onclick="promptToggleAttendance(<?= $sessionId ?>, <?= $pid ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $isPresent ? 1 : 0 ?>)" class="min-h-[40px] px-3 rounded-lg text-[11px] sm:text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center <?= $isPresent ? 'bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-700 shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300' ?>" title="Ubah status kehadiran">
                        <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                      </button>

                      <!-- Edit Player Button (Level & Waiting List) -->
                      <button type="button" onclick="openEditPlayerModal(<?= $pid ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= htmlspecialchars($lvl) ?>', '<?= htmlspecialchars($p['phone'] ?: '') ?>', 'confirmed')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-slate-700 hover:text-slate-900 border border-slate-300 bg-slate-50 active:scale-95 flex items-center justify-center" title="Ubah Level / Pindahkan ke Waiting List">
                        Edit
                      </button>

                      <!-- Remove Player Button -->
                      <button type="button" onclick="confirmRemoveParticipant(<?= $sessionId ?>, <?= $pid ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 bg-white active:scale-95 flex items-center justify-center" title="Keluarkan dari sesi">
                        Hapus
                      </button>
                    <?php else: ?>
                      <span class="col-span-3 min-h-[38px] px-3 rounded-lg text-[11px] font-mono font-bold uppercase flex items-center justify-center bg-slate-100 text-slate-500 border border-slate-200">
                        Status: <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                      </span>
                    <?php endif; ?>
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
            <span class="text-[11px] font-mono text-amber-800"><?= $isSessionCompleted ? 'Mode Terkunci' : 'Ubah ke Sesi Utama lewat tombol Edit' ?></span>
          </div>

          <div class="space-y-2.5">
            <?php foreach (array_values($waitingListParticipants) as $idx => $wp): 
              $isPresent = ((int)$wp['is_present'] === 1);
              $lvl = $wp['level'] ?: 'Intermediate';
              $lvlBadge = ($lvl === 'Advance') ? 'bg-amber-100 text-amber-900 border-amber-300' : (($lvl === 'Beginner') ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-blue-100 text-blue-900 border-blue-300');
              $pid = (int)$wp['player_id'];
              $playCount = $sessionMatchCounts[$pid] ?? 0;
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
                        <span class="px-2 py-0.5 rounded border text-[9px] font-mono font-bold <?= $playCount > 0 ? 'bg-[#0b1e36] text-amber-300 border-amber-400/30' : 'bg-slate-100 text-slate-600 border-slate-200' ?>"><?= $playCount ?>x Main</span>
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

                <div class="grid grid-cols-3 sm:flex items-center gap-1.5 pt-2.5 sm:pt-0 border-t sm:border-t-0 border-amber-200/60 shrink-0">
                  <?php if (!$isSessionCompleted): ?>
                    <!-- Single Unified Attendance Toggle Button -->
                    <button type="button" id="btn_att_<?= $pid ?>" onclick="promptToggleAttendance(<?= $sessionId ?>, <?= $pid ?>, '<?= htmlspecialchars(addslashes($wp['name'])) ?>', <?= $isPresent ? 1 : 0 ?>)" class="min-h-[40px] px-3 rounded-lg text-[11px] sm:text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center <?= $isPresent ? 'bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-700 shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-300' ?>" title="Ubah status kehadiran">
                      <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                    </button>

                    <!-- Edit Player Button -->
                    <button type="button" onclick="openEditPlayerModal(<?= $pid ?>, '<?= htmlspecialchars(addslashes($wp['name'])) ?>', '<?= htmlspecialchars($lvl) ?>', '<?= htmlspecialchars($wp['phone'] ?: '') ?>', 'waiting_list')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-slate-700 border border-slate-300 bg-white active:scale-95 flex items-center justify-center">
                      Edit
                    </button>

                    <!-- Remove Player Button -->
                    <button type="button" onclick="confirmRemoveParticipant(<?= $sessionId ?>, <?= $pid ?>, '<?= htmlspecialchars(addslashes($wp['name'])) ?>')" class="min-h-[40px] px-2.5 rounded-lg text-[11px] sm:text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 bg-white active:scale-95 flex items-center justify-center">
                      Hapus
                    </button>
                  <?php else: ?>
                    <span class="col-span-3 min-h-[38px] px-3 rounded-lg text-[11px] font-mono font-bold uppercase flex items-center justify-center bg-slate-100 text-slate-500 border border-slate-200">
                      Status: <?= $isPresent ? 'Hadir' : 'Tidak Hadir' ?>
                    </span>
                  <?php endif; ?>
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

        <!-- 1. KLASEMEN SESI POINT (CLEAN, POLISHED LEADERBOARD) -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs mb-6">
          <div class="p-3.5 sm:p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between gap-2">
            <div>
              <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-amber-700">Klasemen Perolehan Poin</div>
              <h3 class="text-sm font-black text-slate-950">
                <?= htmlspecialchars($currentSession['venue_name']) ?>
              </h3>
            </div>
            <span class="text-[10px] font-mono font-bold px-2.5 py-1 bg-[#0b1e36] text-amber-400 rounded">
              <?= count($sessionLeaderboard) ?> Pemain
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans min-w-[340px]">
              <thead class="bg-[#0b1e36] text-white uppercase font-mono font-bold text-[10px]">
                <tr>
                  <th class="py-2.5 px-3 text-center w-12">Rank</th>
                  <th class="py-2.5 px-3">Nama Pemain</th>
                  <th class="py-2.5 px-2 text-center w-14">Main</th>
                  <th class="py-2.5 px-2 text-center w-16">M / K</th>
                  <th class="py-2.5 px-3.5 text-right w-24 text-amber-400 font-black">Poin Sesi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php if (empty($sessionLeaderboard)): ?>
                  <tr>
                    <td colspan="5" class="py-10 text-center text-slate-400 text-xs">
                      Belum ada pertandingan selesai dicatat untuk sesi ini.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($sessionLeaderboard as $idx => $row): 
                    $rank = $idx + 1;
                    $rankBadge = 'bg-slate-100 text-slate-700 border-slate-200';
                    if ($rank === 1) $rankBadge = 'bg-amber-400 text-slate-950 font-black border-amber-500 shadow-xs';
                    elseif ($rank === 2) $rankBadge = 'bg-slate-200 text-slate-800 font-bold border-slate-300';
                    elseif ($rank === 3) $rankBadge = 'bg-amber-700/20 text-amber-900 font-bold border-amber-700/30';
                  ?>
                    <tr class="hover:bg-slate-50 transition-colors <?= $rank <= 3 ? 'bg-amber-50/20' : '' ?>">
                      <td class="py-2.5 px-3 text-center">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-md font-mono text-[11px] border <?= $rankBadge ?>">
                          <?= $rank ?>
                        </span>
                      </td>
                      <td class="py-2.5 px-3">
                        <div class="font-bold text-slate-900 leading-tight">
                          <?= htmlspecialchars($row['name']) ?>
                        </div>
                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                          Level: <span class="text-slate-600 font-semibold"><?= htmlspecialchars($row['level']) ?></span>
                        </div>
                      </td>
                      <td class="py-2.5 px-2 text-center font-mono font-bold text-slate-700">
                        <?= $row['total_matches'] ?>x
                      </td>
                      <td class="py-2.5 px-2 text-center font-mono text-xs">
                        <span class="text-emerald-700 font-black"><?= $row['wins'] ?></span>
                        <span class="text-slate-400">/</span>
                        <span class="text-rose-600 font-bold"><?= $row['losses'] ?></span>
                      </td>
                      <td class="py-2.5 px-3.5 text-right font-mono font-black text-slate-900 text-sm">
                        <span class="inline-block px-2 py-0.5 rounded bg-slate-100 border border-slate-200 <?= $rank === 1 ? 'bg-amber-100 border-amber-300 text-amber-950' : '' ?>">
                          <?= number_format($row['total_points']) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 2. RIWAYAT PERTANDINGAN SELESAI (COMPACT SCOREBOARD LEDGER - NO CARDS) -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
          <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 font-mono">
              Riwayat Pertandingan Selesai (<?= count($completedMatches) ?> Match)
            </h3>
            <span class="text-[10px] font-mono text-slate-500 font-bold">Skor Selesai</span>
          </div>

          <?php if (empty($completedMatches)): ?>
            <div class="p-8 text-center text-xs text-slate-400">
              Belum ada skor pertandingan yang selesai.
            </div>
          <?php else: ?>
            <div class="divide-y divide-slate-100">
              <?php foreach (array_reverse($completedMatches) as $cm): 
                $scA = (int)$cm['score_team_a'];
                $scB = (int)$cm['score_team_b'];
                $isWinA = ($scA > $scB);
                $isWinB = ($scB > $scA);
              ?>
                <div class="p-2.5 sm:px-3.5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-2">
                  
                  <!-- Lapangan & Game tag -->
                  <div class="shrink-0 w-16 sm:w-20">
                    <div class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-800 text-[10px] font-mono font-bold rounded border border-slate-200">
                      Lap <?= $cm['court_number'] ?>
                    </div>
                    <div class="text-[9px] font-mono text-slate-400 mt-0.5">
                      Game #<?= $cm['round_number'] ?>
                    </div>
                  </div>

                  <!-- Teams Matchup (Left vs Right) -->
                  <div class="flex-1 min-w-0 grid grid-cols-2 gap-2 text-xs">
                    <!-- Team A -->
                    <div class="min-w-0 pr-1 text-right flex flex-col justify-center <?= $isWinA ? 'text-emerald-950' : 'text-slate-500' ?>">
                      <div class="truncate text-[11px] sm:text-xs <?= $isWinA ? 'font-black text-emerald-900' : 'font-medium text-slate-700' ?>">
                        <?= htmlspecialchars($cm['team_a_p1_name']) ?>
                      </div>
                      <div class="truncate text-[11px] sm:text-xs <?= $isWinA ? 'font-black text-emerald-900' : 'font-medium text-slate-600' ?>">
                        <?= htmlspecialchars($cm['team_a_p2_name']) ?>
                      </div>
                      <?php if ($isWinA): ?>
                        <span class="inline-block text-[8px] font-mono uppercase font-black text-emerald-700 tracking-wider">★ MENANG</span>
                      <?php endif; ?>
                    </div>

                    <!-- Team B -->
                    <div class="min-w-0 pl-1 text-left flex flex-col justify-center <?= $isWinB ? 'text-emerald-950' : 'text-slate-500' ?>">
                      <div class="truncate text-[11px] sm:text-xs <?= $isWinB ? 'font-black text-emerald-900' : 'font-medium text-slate-700' ?>">
                        <?= htmlspecialchars($cm['team_b_p1_name']) ?>
                      </div>
                      <div class="truncate text-[11px] sm:text-xs <?= $isWinB ? 'font-black text-emerald-900' : 'font-medium text-slate-600' ?>">
                        <?= htmlspecialchars($cm['team_b_p2_name']) ?>
                      </div>
                      <?php if ($isWinB): ?>
                        <span class="inline-block text-[8px] font-mono uppercase font-black text-emerald-700 tracking-wider">★ MENANG</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Center Score -->
                  <div class="shrink-0 flex items-center justify-center">
                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md font-mono text-xs font-black border <?= ($isWinA || $isWinB) ? 'bg-slate-900 text-white border-slate-800' : 'bg-slate-100 text-slate-800 border-slate-200' ?>">
                      <span class="<?= $isWinA ? 'text-emerald-400 font-black' : 'text-slate-300' ?>"><?= $scA ?></span>
                      <span class="text-slate-500 font-normal">:</span>
                      <span class="<?= $isWinB ? 'text-emerald-400 font-black' : 'text-slate-300' ?>"><?= $scB ?></span>
                    </div>
                  </div>

                  <?php if (!$isSessionCompleted): ?>
                    <div class="shrink-0">
                      <button type="button" onclick="confirmDeleteMatch(<?= $cm['id'] ?>, <?= $cm['court_number'] ?>)" class="text-slate-400 hover:text-rose-600 text-xs p-1 hover:bg-rose-50 rounded transition-colors" title="Hapus skor pertandingan ini">
                        ✕
                      </button>
                    </div>
                  <?php endif; ?>

                </div>
              <?php endforeach; ?>
            </div>
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

            <!-- Upload Media File (Photos & Video Files supported!) -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-slate-800">
                  File Media (Foto / Video Kamera & Galeri HP):
                </label>
                <span class="text-[10px] font-mono font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                  ✓ Foto & Video (.mp4 / .mov)
                </span>
              </div>
              <input type="file" name="photo_files[]" multiple accept="image/*,video/*" class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-700 file:mr-3 file:py-1 file:px-2.5 file:border-0 file:text-xs file:font-bold file:bg-[#0b1e36] file:text-white cursor-pointer rounded-xs">
              <span class="text-[10px] text-slate-500 mt-1 block font-sans">
                <em>Tip di HP:</em> Pilih file Foto (JPG/PNG/WEBP) atau Video Rekaman Match Smash (.mp4 / .mov / .webm). Bisa pilih beberapa file sekaligus.
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">
                  Link URL Video Highlight (Opsional - YouTube Shorts / TikTok / Reel / MP4):
                </label>
                <input type="url" name="video_url" placeholder="https://youtube.com/shorts/... atau .mp4" class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">
                  URL Thumbnail Poster / Gambar (Opsional):
                </label>
                <input type="url" name="image_url" placeholder="https://..." class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none">
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Catatan / Keterangan Sesi (Caption):
              </label>
              <textarea name="caption" rows="2" placeholder="Tuliskan catatan keseruan mabar atau cuplikan smash..." class="w-full bg-slate-50 border border-slate-300 px-3 py-2 text-xs text-slate-900"></textarea>
            </div>

            <button type="submit" class="w-full py-2.5 bg-[#0b1e36] hover:bg-[#071324] text-amber-400 hover:text-amber-300 font-bold text-xs uppercase tracking-wider transition-all border border-amber-400/30 flex items-center justify-center gap-1.5">
              <span>Unggah Dokumentasi Foto & Video ke Web</span>
            </button>
          </form>
        </div>

        <!-- Daftar Media Dokumentasi Sesi Ini -->
        <div>
          <div class="flex items-center justify-between mb-3 px-1">
            <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-800">
              Dokumentasi Foto & Video Mabar (<?= count($sessionPhotos) ?>)
            </h3>
            <span class="text-[11px] text-slate-500">Tampil di Galeri Landing Page</span>
          </div>

          <?php if (empty($sessionPhotos)): ?>
            <div class="bg-white border border-slate-200 p-8 text-center text-slate-400 space-y-2">
              <h4 class="text-xs font-bold text-slate-700">Belum Ada Media Dokumentasi untuk Sesi Ini</h4>
              <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                Gunakan form di atas untuk mengunggah foto atau video mabar langsung dari HP Anda.
              </p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <?php foreach ($sessionPhotos as $pic): 
                $picDate = !empty($pic['activity_date']) ? formatIndoDate($pic['activity_date']) : substr($pic['created_at'], 0, 10);
                $isVideo = ($pic['media_type'] ?? 'photo') === 'video' || !empty($pic['video_url']);
              ?>
                <div class="bg-white border border-slate-200 overflow-hidden shadow-xs flex flex-col justify-between">
                  <div class="aspect-video bg-slate-950 overflow-hidden relative">
                    <?php if ($isVideo && !empty($pic['video_url']) && strpos($pic['video_url'], '.mp4') !== false): ?>
                      <video src="<?= htmlspecialchars($pic['video_url']) ?>" controls poster="<?= htmlspecialchars($pic['image_url']) ?>" class="w-full h-full object-cover"></video>
                    <?php else: ?>
                      <img src="<?= htmlspecialchars($pic['image_url']) ?>" alt="<?= htmlspecialchars($pic['title']) ?>" class="w-full h-full object-cover">
                    <?php endif; ?>

                    <div class="absolute top-2 left-2 flex items-center gap-1.5">
                      <span class="text-[10px] font-mono font-bold text-slate-900 bg-white/95 border border-slate-200 px-2 py-0.5">
                        <?= htmlspecialchars($picDate) ?>
                      </span>
                      <?php if ($isVideo): ?>
                        <span class="text-[9px] font-mono font-black text-slate-950 bg-amber-400 border border-amber-300 px-1.5 py-0.5 uppercase tracking-wider flex items-center gap-1">
                          <span>📹</span> VIDEO
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="p-3 flex-1 flex flex-col justify-between">
                    <div>
                      <h4 class="font-bold text-slate-900 text-xs mb-1 line-clamp-1"><?= htmlspecialchars($pic['title']) ?></h4>
                      <p class="text-[11px] text-slate-500 line-clamp-2"><?= htmlspecialchars($pic['caption'] ? $pic['caption'] : ($isVideo ? 'Video highlight mabar.' : 'Foto dokumentasi sesi.')) ?></p>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                      <span class="font-mono text-slate-400 text-[10px]">
                        <?= $isVideo ? 'VID' : 'DOK' ?>-<?= strtoupper(substr(md5((string)$pic['id']), 0, 5)) ?>
                      </span>
                      <form method="POST" action="?view=documentation" onsubmit="handleDeletePhotoConfirm(event, this); return false;">
                        <input type="hidden" name="form_action" value="delete_documentation">
                        <input type="hidden" name="photo_id" value="<?= $pic['id'] ?>">
                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold hover:underline">
                          Hapus <?= $isVideo ? 'Video' : 'Foto' ?>
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
    <nav class="fixed bottom-0 inset-x-0 bg-[#0b1e36] border-t border-slate-800 z-40 pb-safe shadow-[0_-4px_20px_rgba(0,0,0,0.35)] sm:bottom-3 sm:max-w-xl sm:mx-auto sm:rounded-2xl sm:border sm:border-slate-700/80 sm:shadow-2xl sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-full">
      <div class="px-2 py-1.5 flex items-center justify-around gap-1">
        
        <!-- Tab 1: Rotasi (Court) -->
        <a href="?view=court" class="flex-1 py-1.5 px-1 text-center transition-all duration-200 active:scale-95 flex flex-col items-center justify-center rounded-xl <?= $view === 'court' ? 'bg-amber-400 text-slate-950 font-black shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5 font-semibold' ?>">
          <svg class="w-5 h-5 mb-0.5 <?= $view === 'court' ? 'text-slate-950 stroke-[2.5]' : 'text-slate-400 stroke-2' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="12" y1="3" x2="12" y2="21" />
          </svg>
          <span class="text-[10px] uppercase tracking-wider block leading-tight">Rotasi</span>
        </a>

        <!-- Tab 2: Pemain -->
        <a href="?view=players" class="flex-1 py-1.5 px-1 text-center transition-all duration-200 active:scale-95 flex flex-col items-center justify-center rounded-xl relative <?= $view === 'players' ? 'bg-amber-400 text-slate-950 font-black shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5 font-semibold' ?>">
          <div class="relative">
            <svg class="w-5 h-5 mb-0.5 <?= $view === 'players' ? 'text-slate-950 stroke-[2.5]' : 'text-slate-400 stroke-2' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            <?php if (($paymentSummary['total_present'] ?? 0) > 0): ?>
              <span class="absolute -top-1 -right-2 px-1 text-[8px] font-mono font-black rounded-full <?= $view === 'players' ? 'bg-slate-950 text-amber-400' : 'bg-amber-400 text-slate-950' ?>">
                <?= $paymentSummary['total_present'] ?>
              </span>
            <?php endif; ?>
          </div>
          <span class="text-[10px] uppercase tracking-wider block leading-tight">Pemain</span>
        </a>

        <!-- Tab 3: Kas -->
        <a href="?view=payments" class="flex-1 py-1.5 px-1 text-center transition-all duration-200 active:scale-95 flex flex-col items-center justify-center rounded-xl relative <?= $view === 'payments' ? 'bg-amber-400 text-slate-950 font-black shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5 font-semibold' ?>">
          <div class="relative">
            <svg class="w-5 h-5 mb-0.5 <?= $view === 'payments' ? 'text-slate-950 stroke-[2.5]' : 'text-slate-400 stroke-2' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="2" y="4" width="20" height="16" rx="2" />
              <line x1="2" y1="10" x2="22" y2="10" />
              <circle cx="7" cy="15" r="1" />
            </svg>
            <?php if ($paymentSummary['total_unpaid'] > 0): ?>
              <span class="absolute -top-1 -right-2 px-1 text-[8px] font-mono font-black rounded-full bg-rose-600 text-white animate-pulse">
                <?= $paymentSummary['total_unpaid'] ?>
              </span>
            <?php endif; ?>
          </div>
          <span class="text-[10px] uppercase tracking-wider block leading-tight">Kas</span>
        </a>

        <!-- Tab 4: Foto Dokumentasi -->
        <a href="?view=documentation" class="flex-1 py-1.5 px-1 text-center transition-all duration-200 active:scale-95 flex flex-col items-center justify-center rounded-xl relative <?= $view === 'documentation' ? 'bg-amber-400 text-slate-950 font-black shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5 font-semibold' ?>">
          <svg class="w-5 h-5 mb-0.5 <?= $view === 'documentation' ? 'text-slate-950 stroke-[2.5]' : 'text-slate-400 stroke-2' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
            <circle cx="12" cy="13" r="4" />
          </svg>
          <span class="text-[10px] uppercase tracking-wider block leading-tight">Foto</span>
        </a>

        <!-- Tab 5: Klasemen -->
        <a href="?view=leaderboard" class="flex-1 py-1.5 px-1 text-center transition-all duration-200 active:scale-95 flex flex-col items-center justify-center rounded-xl <?= $view === 'leaderboard' ? 'bg-amber-400 text-slate-950 font-black shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5 font-semibold' ?>">
          <svg class="w-5 h-5 mb-0.5 <?= $view === 'leaderboard' ? 'text-slate-950 stroke-[2.5]' : 'text-slate-400 stroke-2' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <line x1="18" y1="20" x2="18" y2="10" />
            <line x1="12" y1="20" x2="12" y2="4" />
            <line x1="6" y1="20" x2="6" y2="14" />
          </svg>
          <span class="text-[10px] uppercase tracking-wider block leading-tight">Klasemen</span>
        </a>

      </div>
    </nav>
  <?php endif; ?>

  <!-- ========================================== -->
  <!-- MODERN MOBILE BOTTOM SHEET: GANTI SESI      -->
  <!-- ========================================== -->
  <?php
    $activeSessionsList = array_filter($allSchedules, function($sch) use ($sessionId) {
        return (int)$sch['id'] !== (int)$sessionId && ($sch['status'] ?? 'open') !== 'completed';
    });
    $completedSessionsList = array_filter($allSchedules, function($sch) use ($sessionId) {
        return (int)$sch['id'] !== (int)$sessionId && ($sch['status'] ?? 'open') === 'completed';
    });
  ?>
  <div id="sessionBottomSheet" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-950/80 backdrop-blur-sm transition-opacity duration-200" onclick="if(event.target===this) closeSessionSheet()">
    <div class="bg-[#0b1e36] text-white border-t sm:border border-slate-700 w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl p-5 shadow-2xl max-h-[85vh] flex flex-col transition-transform duration-300 transform translate-y-0" onclick="event.stopPropagation()">
      
      <!-- Drag Handle Indicator for Mobile -->
      <div class="w-12 h-1.5 bg-slate-600 rounded-full mx-auto mb-3 sm:hidden"></div>

      <!-- Header -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-700/80">
        <div>
          <h3 class="text-sm font-black text-white uppercase tracking-wider font-mono flex items-center gap-1.5">
            <span>Pindah ke Sesi Lain</span>
          </h3>
          <p class="text-[11px] text-slate-400 mt-0.5">
            Sesi aktif: <strong class="text-amber-400"><?= htmlspecialchars($currentSession ? $currentSession['venue_name'] : '-') ?></strong>
          </p>
        </div>
        <button type="button" onclick="closeSessionSheet()" class="w-8 h-8 rounded-full bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center font-bold text-sm" aria-label="Tutup">
          &times;
        </button>
      </div>

      <!-- Sessions List (Grouped: Active vs Completed) -->
      <div class="overflow-y-auto py-3 space-y-3 max-h-[55vh]">
        
        <!-- SECTION 1: SESI MABAR AKTIF -->
        <?php if (!empty($activeSessionsList)): ?>
          <div>
            <div class="text-[10px] font-mono font-black text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1.5 px-1">
              <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
              <span>⚡ SESI MABAR AKTIF LAPANGAN (<?= count($activeSessionsList) ?>)</span>
            </div>
            <div class="space-y-2">
              <?php foreach ($activeSessionsList as $sch): 
                $tS = isset($sch['time_start']) ? $sch['time_start'] : (isset($sch['start_time']) ? $sch['start_time'] : '20:00');
                $tDisplay = substr($tS, 0, 5) . ' WIB';
              ?>
                <a href="?view=<?= htmlspecialchars($view) ?>&switch=<?= urlencode(Security::hashId((int)$sch['id'])) ?>" class="flex items-center justify-between p-3.5 bg-[#071324] hover:bg-slate-800 border border-amber-400/30 rounded-xl active:scale-98 transition-all group shadow-xs">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-lg bg-amber-400/10 border border-amber-400/40 text-amber-400 flex items-center justify-center font-black text-xs font-mono shrink-0">
                      <?= strtoupper(substr($sch['day_name'], 0, 3)) ?>
                    </div>
                    <div class="min-w-0 text-left">
                      <div class="font-extrabold text-xs sm:text-sm text-white group-hover:text-amber-400 transition-colors truncate">
                        <?= htmlspecialchars($sch['venue_name']) ?>
                      </div>
                      <div class="text-[11px] text-slate-300 flex items-center gap-1.5 mt-0.5 font-mono truncate">
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

                  <div class="text-amber-400 text-xs font-black shrink-0 px-2.5 py-1 rounded bg-amber-400/10 border border-amber-400/30 group-hover:bg-amber-400 group-hover:text-slate-950 transition-all font-mono">
                    Pindah &rarr;
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- SECTION 2: RIWAYAT SESI SELESAI (ARCHIVED) -->
        <?php if (!empty($completedSessionsList)): ?>
          <div class="<?= !empty($activeSessionsList) ? 'pt-3 border-t border-slate-800' : '' ?>">
            <div class="text-[10px] font-mono font-black text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5 px-1">
              <span>📁 RIWAYAT SESI SELESAI / TERKUNCI (<?= count($completedSessionsList) ?>)</span>
            </div>
            <div class="space-y-2">
              <?php foreach ($completedSessionsList as $sch): 
                $tS = isset($sch['time_start']) ? $sch['time_start'] : (isset($sch['start_time']) ? $sch['start_time'] : '20:00');
                $tDisplay = substr($tS, 0, 5) . ' WIB';
              ?>
                <a href="?view=<?= htmlspecialchars($view) ?>&switch=<?= urlencode(Security::hashId((int)$sch['id'])) ?>" class="flex items-center justify-between p-3 bg-slate-900/80 hover:bg-slate-800 border border-slate-800 rounded-xl active:scale-98 transition-all group opacity-85">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-lg bg-slate-800 text-slate-400 border border-slate-700 flex items-center justify-center font-bold text-xs font-mono shrink-0">
                      ✓
                    </div>
                    <div class="min-w-0 text-left">
                      <div class="font-bold text-xs text-slate-300 group-hover:text-white transition-colors truncate">
                        <?= htmlspecialchars($sch['venue_name']) ?>
                      </div>
                      <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5 font-mono truncate">
                        <span><?= htmlspecialchars($sch['day_name']) ?></span>
                        <span>•</span>
                        <span><?= $tDisplay ?></span>
                        <span class="text-emerald-400 font-bold bg-emerald-950/60 px-1 py-0.2 border border-emerald-800/60 rounded-xs">[Selesai]</span>
                      </div>
                    </div>
                  </div>

                  <div class="text-slate-400 text-[11px] font-semibold shrink-0 px-2 py-1 rounded bg-slate-800 group-hover:text-white transition-all font-mono">
                    Lihat Arsip
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($activeSessionsList) && empty($completedSessionsList)): ?>
          <div class="p-6 text-center text-xs text-slate-400">
            Tidak ada sesi mabar lain yang tersedia saat ini.
          </div>
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

  <!-- ======================================================== -->
  <!-- MODAL: KONFIRMASI UBAH KEHADIRAN (DENGAN PROGRESS BAR UX) -->
  <!-- ======================================================== -->
  <div id="attendanceConfirmModal" class="hidden fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target===this) cancelAttendancePrompt(false)">
    <div class="bg-white border border-slate-300 max-w-sm w-full p-5 shadow-2xl space-y-4 rounded-xl" onclick="event.stopPropagation()">
      
      <!-- Modal Header -->
      <div class="flex items-start justify-between pb-2.5 border-b border-slate-100">
        <div>
          <span class="text-[10px] font-mono uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-bold">
            Konfirmasi Perubahan
          </span>
          <h3 class="text-sm font-black text-slate-900 mt-1">Ubah Status Kehadiran</h3>
        </div>
        <button type="button" onclick="cancelAttendancePrompt(false)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-700 rounded-md hover:bg-slate-100 text-sm font-bold">&times;</button>
      </div>

      <!-- Player & Status Change Info -->
      <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 space-y-2">
        <div class="text-[11px] text-slate-500 font-medium">Pemain:</div>
        <div id="attPromptPlayerName" class="text-sm font-extrabold text-slate-900 break-words leading-snug"></div>

        <div class="flex items-center gap-2 pt-2 border-t border-slate-200">
          <span id="attPromptFromBadge" class="px-2.5 py-1 rounded text-xs font-mono font-bold"></span>
          <span class="text-xs text-slate-400 font-bold">➔</span>
          <span id="attPromptToBadge" class="px-2.5 py-1 rounded text-xs font-mono font-bold"></span>
        </div>
      </div>

      <!-- UX Progress Bar Indicator -->
      <div class="space-y-1.5 pt-0.5">
        <div class="flex items-center justify-between text-[11px] font-mono text-slate-500">
          <span id="attPromptProgressLabel">Batal otomatis dalam</span>
          <span id="attPromptCountdown" class="font-bold text-slate-800">4.0s</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
          <div id="attPromptProgressBar" class="h-full bg-amber-400 rounded-full transition-all ease-linear" style="width: 100%;"></div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="grid grid-cols-2 gap-2 pt-1">
        <button type="button" onclick="cancelAttendancePrompt(false)" class="min-h-[44px] py-2.5 px-3 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-lg border border-slate-300 transition-all flex items-center justify-center">
          Batal
        </button>
        <button type="button" id="attPromptConfirmBtn" onclick="executeAttendanceChange()" class="min-h-[44px] py-2.5 px-3 bg-[#0b1e36] hover:bg-[#071324] active:scale-95 text-amber-400 font-black text-xs uppercase tracking-wider rounded-lg border border-amber-400/40 shadow-xs transition-all flex items-center justify-center">
          Ya, Ubah
        </button>
      </div>

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
        <!-- Center Core -->
        <div class="w-8 h-8 bg-[#0b1e36] rounded-full flex items-center justify-center shadow-inner">
          <div class="w-3 h-3 bg-amber-400 rounded-full animate-pulse"></div>
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

    function copyWhatsappRecap() {
      const sessionVenue = <?= json_encode($currentSession['venue_name'] ?? 'Badminton Court') ?>;
      const sessionDay = <?= json_encode($currentSession['day_name'] ?? '') ?>;
      const matchCount = <?= (int)count($completedMatches) ?>;
      const presentCount = <?= (int)$paymentSummary['total_present'] ?>;
      const cashCollected = <?= (int)$paymentSummary['total_cash_collected'] ?>;
      const unpaidCount = <?= (int)$paymentSummary['total_unpaid'] ?>;
      
      let text = `*REKAP SESI MABAR - ${sessionVenue.toUpperCase()}*\n`;
      text += `*Hari/Sesi:* ${sessionDay}\n`;
      text += `*Total Pemain Hadir:* ${presentCount} orang\n`;
      text += `*Pertandingan Selesai:* ${matchCount} match\n`;
      text += `*Total Kas Terkumpul:* Rp ${cashCollected.toLocaleString('id-ID')}\n`;
      if (unpaidCount > 0) {
        text += `*Catatan Iuran Belum Bayar:* ${unpaidCount} orang\n`;
      }
      text += `\nTerima kasih kepada seluruh teman-teman yang telah hadir dan meramaikan sesi mabar hari ini! Sampai jumpa di sesi berikutnya!`;
      
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
          showToast('Rekap WhatsApp berhasil disalin ke clipboard!', true);
        }).catch(() => {
          fallbackCopyText(text);
        });
      } else {
        fallbackCopyText(text);
      }
    }

    function fallbackCopyText(text) {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        showToast('Rekap WhatsApp berhasil disalin ke clipboard!', true);
      } catch (err) {
        showToast('Gagal menyalin rekap.', false);
      }
      document.body.removeChild(ta);
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

    // Generate Single Court (Rolling Dispatch per Lapangan)
    async function generateSingleCourt(sessionId, courtNum, algorithm = 'balanced_level') {
      const algoEl = document.getElementById('algorithmSelect');
      if (algoEl) algorithm = algoEl.value;

      showAppLoader(`Menyusun Lapangan ${courtNum}...`, 'Memilih 4 pemain dengan jumlah main paling sedikit dari antrean...');

      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('court_number', courtNum);
        fd.append('algorithm', algorithm);

        const res = await fetch(API_BASE + '?action=generate_single_court', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, true);
          setTimeout(() => window.location.reload(), 400);
        } else {
          hideAppLoader();
          showToast(data.error ? data.error.message : 'Gagal menyusun pemain lapangan.', false);
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

    // Single Button Attendance Toggle with Animated Progress Alert UX
    let pendingAttendance = null;
    let attendanceProgressInterval = null;

    function promptToggleAttendance(sessionId, playerId, playerName, currentIsPresent) {
      cancelAttendancePrompt(false);

      const curVal = parseInt(currentIsPresent, 10);
      const targetIsPresent = (curVal === 1) ? 0 : 1;
      pendingAttendance = {
        sessionId: sessionId,
        playerId: playerId,
        playerName: playerName,
        targetIsPresent: targetIsPresent,
        currentIsPresent: curVal
      };

      const modal = document.getElementById('attendanceConfirmModal');
      const nameEl = document.getElementById('attPromptPlayerName');
      const fromBadge = document.getElementById('attPromptFromBadge');
      const toBadge = document.getElementById('attPromptToBadge');
      const confirmBtn = document.getElementById('attPromptConfirmBtn');
      const progressLabel = document.getElementById('attPromptProgressLabel');
      const countdownEl = document.getElementById('attPromptCountdown');
      const progressBar = document.getElementById('attPromptProgressBar');

      if (!modal) return;

      nameEl.innerText = playerName;

      if (curVal === 1) {
        fromBadge.innerText = 'Hadir';
        fromBadge.className = 'px-2.5 py-1 rounded text-xs font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
        toBadge.innerText = 'Tidak Hadir';
        toBadge.className = 'px-2.5 py-1 rounded text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-300';
        confirmBtn.innerText = 'Ya, Ubah ke Tidak Hadir';
        confirmBtn.className = 'min-h-[44px] py-2.5 px-3 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-black text-xs uppercase tracking-wider rounded-lg border border-slate-700 shadow-xs transition-all flex items-center justify-center';
      } else {
        fromBadge.innerText = 'Tidak Hadir';
        fromBadge.className = 'px-2.5 py-1 rounded text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-300';
        toBadge.innerText = 'Hadir';
        toBadge.className = 'px-2.5 py-1 rounded text-xs font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
        confirmBtn.innerText = 'Ya, Ubah ke Hadir';
        confirmBtn.className = 'min-h-[44px] py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs uppercase tracking-wider rounded-lg border border-emerald-700 shadow-xs transition-all flex items-center justify-center';
      }

      confirmBtn.disabled = false;
      progressLabel.innerText = 'Batal otomatis dalam';
      countdownEl.innerText = '4.0s';
      progressBar.style.width = '100%';
      progressBar.className = 'h-full bg-amber-400 rounded-full transition-all ease-linear';

      modal.classList.remove('hidden');

      // 4-second safety countdown progress bar
      const totalDuration = 4000;
      const intervalMs = 50;
      let remainingMs = totalDuration;

      attendanceProgressInterval = setInterval(() => {
        remainingMs -= intervalMs;
        if (remainingMs <= 0) {
          clearInterval(attendanceProgressInterval);
          attendanceProgressInterval = null;
          cancelAttendancePrompt(true);
        } else {
          const pct = Math.max(0, (remainingMs / totalDuration) * 100);
          progressBar.style.width = pct + '%';
          countdownEl.innerText = (remainingMs / 1000).toFixed(1) + 's';
        }
      }, intervalMs);
    }

    function cancelAttendancePrompt(showToastMsg = false) {
      if (attendanceProgressInterval) {
        clearInterval(attendanceProgressInterval);
        attendanceProgressInterval = null;
      }
      const modal = document.getElementById('attendanceConfirmModal');
      if (modal) modal.classList.add('hidden');
      if (showToastMsg && pendingAttendance) {
        showToast('Waktu habis, perubahan kehadiran dibatalkan.', false);
      }
      pendingAttendance = null;
    }

    async function executeAttendanceChange() {
      if (!pendingAttendance) return;
      if (attendanceProgressInterval) {
        clearInterval(attendanceProgressInterval);
        attendanceProgressInterval = null;
      }

      const { sessionId, playerId, playerName, targetIsPresent } = pendingAttendance;
      const confirmBtn = document.getElementById('attPromptConfirmBtn');
      const progressLabel = document.getElementById('attPromptProgressLabel');
      const countdownEl = document.getElementById('attPromptCountdown');
      const progressBar = document.getElementById('attPromptProgressBar');

      if (confirmBtn) confirmBtn.disabled = true;
      if (progressLabel) progressLabel.innerText = 'Menerapkan perubahan status...';
      if (countdownEl) countdownEl.innerText = '100%';
      if (progressBar) {
        progressBar.style.width = '100%';
        progressBar.className = 'h-full bg-emerald-500 rounded-full animate-pulse';
      }

      try {
        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('player_id', playerId);
        fd.append('is_present', targetIsPresent);

        const res = await fetch(API_BASE + '?action=toggle_attendance', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();

        cancelAttendancePrompt(false);

        if (data.success) {
          const isHadir = (targetIsPresent === 1);
          showToast(`Status ${playerName} diubah menjadi ${isHadir ? 'Hadir' : 'Tidak Hadir'}`, true);

          // Update the single button
          const btn = document.getElementById('btn_att_' + playerId);
          if (btn) {
            btn.innerText = isHadir ? 'Hadir' : 'Tidak Hadir';
            btn.className = 'min-h-[40px] px-3 rounded-lg text-[11px] sm:text-xs font-black uppercase tracking-wider active:scale-95 transition-all flex items-center justify-center ' +
              (isHadir 
                ? 'bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-700 shadow-xs' 
                : 'bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300');
            btn.onclick = () => promptToggleAttendance(sessionId, playerId, playerName, targetIsPresent);
          }

          // Update row badge
          const badge = document.getElementById('att_badge_' + playerId);
          if (badge) {
            badge.innerText = isHadir ? 'Hadir' : 'Tidak Hadir';
            badge.className = 'shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase ' +
              (isHadir 
                ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' 
                : 'bg-rose-50 text-rose-700 border border-rose-200');
          }

          // Update header KPI count
          if (data.summary && document.getElementById('kpiPresentCount')) {
            document.getElementById('kpiPresentCount').innerText = data.summary.total_present;
          }
        } else {
          showToast(data.error ? data.error.message : 'Gagal update kehadiran.', false);
        }
      } catch (err) {
        cancelAttendancePrompt(false);
        showToast('Koneksi terputus.', false);
      }
    }

    // Fallback direct setter if ever invoked
    async function setAttendance(sessionId, playerId, isPresent) {
      promptToggleAttendance(sessionId, playerId, 'Pemain', isPresent === 1 ? 0 : 1);
    }

    // Toggle Payment AJAX
    async function togglePayment(sessionId, playerId) {
      const btn = document.getElementById('btn_pay_' + playerId);
      const badge = document.getElementById('badge_' + playerId);
      if (!btn || btn.disabled) return;
      btn.disabled = true;
      const origText = btn.innerText;
      btn.innerHTML = '...';

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

  <!-- Android-Friendly Smart Select -->
  <script src="<?= $baseDir ?>/shared/assets/js/smart-select.js"></script>

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