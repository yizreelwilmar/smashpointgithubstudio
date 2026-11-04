<?php
// admin/index.php
// Powershoot Badminton CMS Panel (Dribbble-Grade Light Theme)
// Full Features: Schedule Management with Maps Autocomplete, WA Template Generator & Roster Importer, Leaderboard of the Month

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../models/Community.php';
require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/Participant.php';
require_once __DIR__ . '/../models/Finance.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Global URL ID Sanitizer: Strictly redirect any raw numeric IDs to cryptographic hashes
$reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
if ($reqMethod === 'GET') {
    $hasRawId = false;
    $redirectParams = $_GET;
    if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
        $redirectParams['edit'] = Security::hashId((int)$_GET['edit_id']);
        unset($redirectParams['edit_id']);
        $hasRawId = true;
    }
    if (isset($_GET['wa_session_id']) && is_numeric($_GET['wa_session_id'])) {
        $redirectParams['wa'] = Security::hashId((int)$_GET['wa_session_id']);
        unset($redirectParams['wa_session_id']);
        $hasRawId = true;
    }
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $redirectParams['ref'] = Security::hashId((int)$_GET['id']);
        unset($redirectParams['id']);
        $hasRawId = true;
    }
    if (isset($_GET['session_id']) && is_numeric($_GET['session_id'])) {
        $redirectParams['session'] = Security::hashId((int)$_GET['session_id']);
        unset($redirectParams['session_id']);
        $hasRawId = true;
    }
    if ($hasRawId) {
        $newQuery = http_build_query($redirectParams);
        header("Location: ?{$newQuery}", true, 301);
        exit;
    }
}

// Dynamic URLs
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
if (substr($baseDir, -6) === '/admin') $baseDir = substr($baseDir, 0, -6);

$landingUrl = $baseDir . '/';
$appUrl = $baseDir . '/app/';
$adminUrl = $baseDir . '/admin/';

// Handle Auth Actions
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    header("Location: ?tab=login");
    exit;
}

$reqMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$loginError = null;
if ($reqMethod === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (Auth::attempt($username, $password)) {
        header("Location: ?tab=dashboard");
        exit;
    } else {
        $loginError = "Username atau password salah.";
    }
}

// Ensure logged in
$isLoggedIn = Auth::check();
if (!$isLoggedIn && $tab !== 'login') {
    $tab = 'login';
}

$successMsg = null;
$errorMsg = null;

if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['flash_success'])) {
    $successMsg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($isLoggedIn && $reqMethod === 'POST' && isset($_POST['form_action'])) {
    $formAction = $_POST['form_action'];
    try {
        if ($formAction === 'create_schedule') {
            $dayName = trim($_POST['day_name']);
            Schedule::create([
                'title' => trim($_POST['venue_name']),
                'day_name' => $dayName,
                'branch_area' => trim($_POST['branch_area'] ?: 'Jakarta'),
                'schedule_date' => !empty($_POST['schedule_date']) ? trim($_POST['schedule_date']) : date('Y-m-d', strtotime('+1 day')),
                'time_start' => !empty($_POST['time_start']) ? $_POST['time_start'] : (!empty($_POST['start_time']) ? $_POST['start_time'] : '19:00:00'),
                'time_end' => !empty($_POST['time_end']) ? $_POST['time_end'] : (!empty($_POST['end_time']) ? $_POST['end_time'] : '22:00:00'),
                'venue_name' => trim($_POST['venue_name']),
                'venue_address' => trim($_POST['venue_address']),
                'venue_maps_url' => trim($_POST['maps_url']),
                'court_count' => (int)$_POST['court_count'],
                'max_slots' => (int)(!empty($_POST['max_quota']) ? $_POST['max_quota'] : (!empty($_POST['max_participants']) ? $_POST['max_participants'] : 16)),
                'fee_per_person' => (int)preg_replace('/[^0-9]/', '', (string)(!empty($_POST['fee']) ? $_POST['fee'] : (!empty($_POST['fee_per_person']) ? $_POST['fee_per_person'] : '35000'))),
                'facilities' => trim(!empty($_POST['shuttlecock']) ? $_POST['shuttlecock'] : (!empty($_POST['facilities']) ? $_POST['facilities'] : 'Bulu Angsa Berkualitas')),
                'host_name' => !empty($_POST['host_name']) ? trim($_POST['host_name']) : 'PIC Lapangan',
                'bank_info' => !empty($_POST['bank_info']) ? trim($_POST['bank_info']) : 'BCA 1234567890 a.n Bendahara',
                'cancellation_rules' => !empty($_POST['cancellation_rules']) ? trim($_POST['cancellation_rules']) : 'Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.',
                'pic_name' => !empty($_POST['pic_name']) ? trim($_POST['pic_name']) : ('PIC ' . $dayName),
                'pic_whatsapp' => !empty($_POST['pic_whatsapp']) ? trim($_POST['pic_whatsapp']) : (!empty($settings['whatsapp_default']) ? $settings['whatsapp_default'] : '081299887766'),
                'status' => 'open',
                'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null
            ]);
            $successMsg = "Jadwal mabar cabang baru berhasil dibuat!";
        } elseif ($formAction === 'update_schedule') {
            $schId = (int)$_POST['schedule_id'];
            Schedule::update($schId, [
                'title' => trim($_POST['venue_name']),
                'day_name' => trim($_POST['day_name']),
                'branch_area' => trim($_POST['branch_area'] ?: 'Jakarta'),
                'schedule_date' => !empty($_POST['schedule_date']) ? trim($_POST['schedule_date']) : date('Y-m-d'),
                'time_start' => !empty($_POST['time_start']) ? $_POST['time_start'] : (!empty($_POST['start_time']) ? $_POST['start_time'] : '19:00:00'),
                'time_end' => !empty($_POST['time_end']) ? $_POST['time_end'] : (!empty($_POST['end_time']) ? $_POST['end_time'] : '22:00:00'),
                'venue_name' => trim($_POST['venue_name']),
                'venue_address' => trim($_POST['venue_address']),
                'venue_maps_url' => trim($_POST['maps_url']),
                'court_count' => (int)$_POST['court_count'],
                'max_slots' => (int)(!empty($_POST['max_quota']) ? $_POST['max_quota'] : (!empty($_POST['max_participants']) ? $_POST['max_participants'] : 16)),
                'fee_per_person' => (int)preg_replace('/[^0-9]/', '', (string)(!empty($_POST['fee']) ? $_POST['fee'] : (!empty($_POST['fee_per_person']) ? $_POST['fee_per_person'] : '35000'))),
                'facilities' => trim(!empty($_POST['shuttlecock']) ? $_POST['shuttlecock'] : (!empty($_POST['facilities']) ? $_POST['facilities'] : 'Bulu Angsa Berkualitas')),
                'host_name' => !empty($_POST['host_name']) ? trim($_POST['host_name']) : 'PIC Lapangan',
                'bank_info' => !empty($_POST['bank_info']) ? trim($_POST['bank_info']) : 'BCA 1234567890 a.n Bendahara',
                'cancellation_rules' => !empty($_POST['cancellation_rules']) ? trim($_POST['cancellation_rules']) : 'Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.',
                'pic_name' => !empty($_POST['pic_name']) ? trim($_POST['pic_name']) : ('PIC ' . trim($_POST['day_name'])),
                'pic_whatsapp' => !empty($_POST['pic_whatsapp']) ? trim($_POST['pic_whatsapp']) : (!empty($settings['whatsapp_default']) ? $settings['whatsapp_default'] : '081299887766'),
                'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null
            ]);
            $successMsg = "Data sesi mabar & kuota berhasil diperbarui!";
        } elseif ($formAction === 'delete_schedule') {
            Schedule::delete((int)$_POST['id']);
            $successMsg = "Jadwal sesi mabar berhasil dihapus.";
        } elseif ($formAction === 'update_schedule_wa_params') {
            $schId = (int)$_POST['session_id'];
            $sch = Schedule::find($schId);
            if ($sch) {
                $timeRaw = trim($_POST['custom_time']);
                $timeParts = explode('-', str_replace('.', ':', $timeRaw));
                $tStart = isset($timeParts[0]) ? trim($timeParts[0]) . ':00' : $sch['time_start'];
                $tEnd = isset($timeParts[1]) ? trim($timeParts[1]) . ':00' : $sch['time_end'];
                
                $feeRaw = trim($_POST['custom_fee']);
                $feeNum = (int)preg_replace('/[^0-9]/', '', $feeRaw);
                if ($feeNum < 1000 && $feeNum > 0) $feeNum = $feeNum * 1000;
                if ($feeNum <= 0) $feeNum = (int)$sch['fee_per_person'];

                $cleanWa = preg_replace('/[^0-9]/', '', $_POST['custom_pic_whatsapp']);

                Schedule::update($schId, [
                    'title' => $sch['title'],
                    'day_name' => trim($_POST['custom_day'] ?: $sch['day_name']),
                    'branch_area' => trim($_POST['custom_branch'] ?: $sch['branch_area']),
                    'schedule_date' => $sch['schedule_date'],
                    'time_start' => $tStart,
                    'time_end' => $tEnd,
                    'venue_name' => trim($_POST['custom_venue'] ?: $sch['venue_name']),
                    'venue_address' => $sch['venue_address'],
                    'venue_maps_url' => trim($_POST['custom_maps'] ?: $sch['venue_maps_url']),
                    'court_count' => (int)$sch['court_count'],
                    'court_desc' => trim($_POST['custom_court'] ?: (!empty($sch['court_desc']) ? $sch['court_desc'] : 'Lapangan 1 - ' . $sch['court_count'])),
                    'fee_per_person' => $feeNum,
                    'max_slots' => (int)$_POST['custom_max_slots'] > 0 ? (int)$_POST['custom_max_slots'] : (int)$sch['max_slots'],
                    'facilities' => $sch['facilities'],
                    'pic_name' => $sch['pic_name'],
                    'pic_whatsapp' => !empty($cleanWa) ? $cleanWa : $sch['pic_whatsapp'],
                    'host_name' => trim($_POST['custom_host'] ?: $sch['host_name']),
                    'bank_info' => trim($_POST['custom_bank'] ?: $sch['bank_info']),
                    'cancellation_rules' => trim($_POST['custom_cancel_rules'] ?: $sch['cancellation_rules']),
                    'notes' => $sch['notes']
                ]);
                $successMsg = "Parameter template WhatsApp & sesi mabar berhasil disimpan!";
            }
        } elseif ($formAction === 'update_schedule_status') {
            Schedule::updateStatus((int)$_POST['id'], $_POST['status']);
            $successMsg = "Status sesi mabar berhasil diperbarui!";
        } elseif ($formAction === 'import_wa_list') {
            $schId = (int)$_POST['session_id'];
            $rawText = $_POST['raw_wa_text'];
            $sch = Schedule::find($schId);
            $fee = $sch ? (int)$sch['fee_per_person'] : 35000;
            $imported = Participant::importFromWhatsappList($schId, $rawText, $fee);
            $successMsg = "Berhasil mengimpor " . count($imported) . " peserta mabar ke sesi dari list WhatsApp!";
        } elseif ($formAction === 'remove_participant') {
            Participant::remove((int)$_POST['session_id'], (int)$_POST['player_id']);
            $successMsg = "Pemain berhasil dihapus dari sesi mabar.";
        } elseif ($formAction === 'add_gallery') {
            $title = trim($_POST['title']);
            $caption = trim($_POST['caption']);
            $sessionId = isset($_POST['session_id']) && (int)$_POST['session_id'] > 0 ? (int)$_POST['session_id'] : null;
            $activityDate = !empty($_POST['activity_date']) ? $_POST['activity_date'] : date('Y-m-d');
            $imageUrl = trim(isset($_POST['image_url']) ? $_POST['image_url'] : '');

            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = $_FILES['photo_file'];
                $ext = strtolower(pathinfo($fileUpload['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed)) {
                    $targetDir = __DIR__ . '/../public/uploads/gallery/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }
                    $filename = 'mabar_' . ($sessionId ? $sessionId : 'adm') . '_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                    $targetFile = $targetDir . $filename;
                    if (move_uploaded_file($fileUpload['tmp_name'], $targetFile)) {
                        $imageUrl = $baseDir . '/public/uploads/gallery/' . $filename;
                    }
                }
            }

            if (!empty($imageUrl)) {
                Community::addGalleryPhoto($title, $imageUrl, $caption, $sessionId, $activityDate);
                $successMsg = "Foto dokumentasi mabar berhasil ditambahkan ke Galeri!";
            } else {
                $errorMsg = "Harap upload file foto atau masukkan URL gambar yang valid.";
            }
        } elseif ($formAction === 'delete_gallery') {
            Community::deleteGalleryPhoto((int)$_POST['id']);
            $successMsg = "Foto galeri berhasil dihapus.";
        } elseif ($formAction === 'update_settings') {
            $logoUrl = trim(isset($_POST['logo_url']) ? $_POST['logo_url'] : '');
            $heroImageUrl = trim(isset($_POST['hero_image_url']) ? $_POST['hero_image_url'] : '');

            $uploadDir = __DIR__ . '/../public/uploads/brand/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            // Handle Logo File Upload
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                    $fn = 'logo_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . $fn)) {
                        $logoUrl = $baseDir . '/public/uploads/brand/' . $fn;
                    }
                }
            }

            // Handle Hero Image File Upload
            if (isset($_FILES['hero_file']) && $_FILES['hero_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['hero_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $fn = 'hero_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['hero_file']['tmp_name'], $uploadDir . $fn)) {
                        $heroImageUrl = $baseDir . '/public/uploads/brand/' . $fn;
                    }
                }
            }

            // Handle OG Social Image File Upload
            $ogImageUrl = trim(isset($_POST['og_image_url']) ? $_POST['og_image_url'] : '');
            if (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['og_image_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $fn = 'og_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['og_image_file']['tmp_name'], $uploadDir . $fn)) {
                        $ogImageUrl = $baseDir . '/public/uploads/brand/' . $fn;
                    }
                }
            }

            // Process FAQs if submitted
            $faqs = [];
            if (isset($_POST['faq_questions']) && is_array($_POST['faq_questions'])) {
                foreach ($_POST['faq_questions'] as $fIdx => $q) {
                    $q = trim($q);
                    $a = trim($_POST['faq_answers'][$fIdx] ?? '');
                    if ($q !== '' && $a !== '') {
                        $faqs[] = ['question' => $q, 'answer' => $a];
                    }
                }
            }
            $faqJson = !empty($faqs) ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : null;

            Community::updateSettings([
                'community_name' => trim($_POST['community_name']),
                'tagline' => trim($_POST['tagline']),
                'description' => trim($_POST['description']),
                'instagram' => trim($_POST['instagram']),
                'whatsapp_default' => trim($_POST['whatsapp_default']),
                'base_city' => trim($_POST['base_city']),
                'logo_url' => $logoUrl,
                'hero_image_url' => $heroImageUrl,
                'hero_title' => isset($_POST['hero_title']) ? trim($_POST['hero_title']) : '',
                'hero_subtitle' => isset($_POST['hero_subtitle']) ? trim($_POST['hero_subtitle']) : '',
                'hero_cta_text' => isset($_POST['hero_cta_text']) ? trim($_POST['hero_cta_text']) : '',
                'announcement_text' => isset($_POST['announcement_text']) ? trim($_POST['announcement_text']) : '',
                'announcement_subtext' => isset($_POST['announcement_subtext']) ? trim($_POST['announcement_subtext']) : '',
                'step_1_title' => isset($_POST['step_1_title']) ? trim($_POST['step_1_title']) : '',
                'step_1_desc' => isset($_POST['step_1_desc']) ? trim($_POST['step_1_desc']) : '',
                'step_2_title' => isset($_POST['step_2_title']) ? trim($_POST['step_2_title']) : '',
                'step_2_desc' => isset($_POST['step_2_desc']) ? trim($_POST['step_2_desc']) : '',
                'step_3_title' => isset($_POST['step_3_title']) ? trim($_POST['step_3_title']) : '',
                'step_3_desc' => isset($_POST['step_3_desc']) ? trim($_POST['step_3_desc']) : '',
                'faq_json' => $faqJson,
                // SEO & Social Graph Settings (Yoast-style)
                'meta_title' => isset($_POST['meta_title']) ? trim($_POST['meta_title']) : null,
                'meta_description' => isset($_POST['meta_description']) ? trim($_POST['meta_description']) : null,
                'focus_keyword' => isset($_POST['focus_keyword']) ? trim($_POST['focus_keyword']) : null,
                'meta_keywords' => isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : null,
                'og_title' => isset($_POST['og_title']) ? trim($_POST['og_title']) : null,
                'og_description' => isset($_POST['og_description']) ? trim($_POST['og_description']) : null,
                'og_image_url' => $ogImageUrl,
                'canonical_url' => isset($_POST['canonical_url']) ? trim($_POST['canonical_url']) : null,
                'robots_meta' => isset($_POST['robots_meta']) ? trim($_POST['robots_meta']) : 'index, follow',
            ]);
            $settings = Community::getSettings();
            $successMsg = "Pengaturan profil komunitas, konfigurasi SEO Yoast, dan konten web berhasil diperbarui!";
        } elseif ($formAction === 'create_player') {
            Player::create($_POST['name'], $_POST['phone'], $_POST['level']);
            $successMsg = "Pemain baru berhasil didaftarkan!";
        } elseif ($formAction === 'delete_player') {
            $pId = (int)$_POST['player_id'];
            $targetPlayer = Player::find($pId);
            $pName = $targetPlayer ? $targetPlayer['name'] : 'Pemain';
            Player::delete($pId);
            $successMsg = "Pemain '{$pName}' berhasil dihapus dari master roster!";
        } elseif ($formAction === 'bulk_delete_players') {
            $rawIds = isset($_POST['player_ids']) ? $_POST['player_ids'] : [];
            if (!is_array($rawIds) && is_string($rawIds)) {
                $rawIds = explode(',', $rawIds);
            }
            $playerIds = array_filter(array_map('intval', (array)$rawIds));
            if (!empty($playerIds)) {
                $countDeleted = Player::deleteBatch($playerIds);
                $successMsg = "{$countDeleted} pemain terpilih berhasil dihapus dari master roster!";
            } else {
                $errorMsg = "Tidak ada pemain yang dipilih untuk dihapus.";
            }
        } elseif ($formAction === 'reset_leaderboard') {
            $pdo = Database::getConnection();
            $pdo->exec("UPDATE `players` SET `total_matches` = 0, `wins` = 0, `losses` = 0, `total_points` = 0, `points_conceded` = 0, `streak` = 0");
            $successMsg = "Poin dan rekor kemenangan seluruh pemain berhasil direset untuk bulan baru!";
        } elseif ($formAction === 'recalculate_leaderboard') {
            $winBonus = isset($_POST['win_bonus']) ? (int)$_POST['win_bonus'] : 5;
            $res = Player::recalculateAllStats($winBonus);
            $successMsg = "Kalkulasi ulang leaderboard sukses! {$res['total_players']} pemain diproses dari {$res['total_matches_processed']} pertandingan selesai (Bonus Menang: +{$res['win_bonus_applied']} poin).";
        } elseif ($formAction === 'adjust_player_points') {
            $pId = (int)$_POST['player_id'];
            $delta = (int)$_POST['points_delta'];
            $reason = trim($_POST['reason']);
            Player::adjustPoints($pId, $delta, $reason);
            $successMsg = "Poin pemain berhasil disesuaikan (" . ($delta >= 0 ? "+$delta" : "$delta") . " poin) untuk alasan: $reason.";
        } elseif ($formAction === 'create_pic_account') {
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $assignedSessionId = (int)$_POST['assigned_session_id'];

            if (empty($name) || empty($username) || empty($password)) {
                throw new Exception("Nama, Username, dan Password wajib diisi.");
            }
            if ($assignedSessionId <= 0) {
                throw new Exception("Pilih sesi mabar yang akan ditugaskan ke PIC ini.");
            }

            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE LOWER(username) = LOWER(?) LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("Username '{$username}' sudah digunakan. Silakan gunakan username lain.");
            }

            $role = (isset($_POST['role']) && $_POST['role'] === 'pic_foto') ? 'pic_foto' : 'pic';
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, name, phone, role, assigned_session_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $name, $phone, $role, $assignedSessionId]);

            // Sync assigned session's PIC contact and host name in schedules table
            if ($role === 'pic' && $assignedSessionId > 0) {
                $stmtSync = $pdo->prepare("UPDATE schedules SET pic_name = ?, host_name = ?, pic_whatsapp = ? WHERE id = ?");
                $stmtSync->execute([$name, $name, $phone, $assignedSessionId]);
            }

            $roleLabel = ($role === 'pic_foto') ? 'PIC Foto / Dokumentasi' : 'PIC Lapangan';
            $successMsg = "Akun {$roleLabel} '{$name}' (@{$username}) berhasil dibuat!";
        } elseif ($formAction === 'delete_pic_account') {
            $adminId = (int)$_POST['admin_id'];
            if ($adminId <= 1) {
                throw new Exception("Akun Super Admin utama tidak dapat dihapus.");
            }
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND role IN ('pic', 'pic_foto')");
            $stmt->execute([$adminId]);
            $successMsg = "Akun PIC berhasil dihapus dari sistem.";
        } elseif ($formAction === 'generate_next_session') {
            $sourceId = (int)$_POST['session_id'];
            $newId = Schedule::duplicateForNextWeek($sourceId);
            if ($newId) {
                $newSch = Schedule::find($newId);
                $successMsg = "Jadwal sesi pekan berikutnya berhasil dibuka otomatis untuk {$newSch['venue_name']} pada tanggal {$newSch['schedule_date']}!";
            } else {
                $errorMsg = "Gagal membuat sesi baru dari jadwal tersebut.";
            }
        } elseif ($formAction === 'update_player_level') {
            $pId = (int)$_POST['player_id'];
            $pName = trim($_POST['name']);
            $pPhone = trim($_POST['phone']);
            $pLevel = trim($_POST['level']);
            Player::update($pId, $pName, $pPhone, $pLevel);
            $successMsg = "Data pemain & level permainan '{$pName}' berhasil diperbarui!";
        } elseif ($formAction === 'create_financial_record' || $formAction === 'update_financial_record') {
            $amount = (int)preg_replace('/[^0-9]/', '', (string)($_POST['amount'] ?? ''));
            $title = trim((string)($_POST['title'] ?? ''));
            $recDate = (string)($_POST['record_date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recDate)) $recDate = date('Y-m-d');
            $allowedCats = ['Sewa Lapangan', 'Shuttlecock', 'Konsumsi', 'Honor PIC', 'Iuran Tambahan', 'Sponsor', 'Peralatan', 'Lain-lain'];
            $category = in_array($_POST['category'] ?? '', $allowedCats, true) ? $_POST['category'] : 'Lain-lain';

            if ($amount <= 0 || $title === '') {
                throw new Exception('Nominal dan judul transaksi wajib diisi.');
            }

            $payload = [
                'session_id' => !empty($_POST['session_id']) ? (int)$_POST['session_id'] : null,
                'type' => ($_POST['type'] ?? '') === 'income' ? 'income' : 'expense',
                'category' => $category,
                'amount' => $amount,
                'title' => mb_substr($title, 0, 150),
                'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
                'record_date' => $recDate,
                'created_by' => Auth::id()
            ];

            if ($formAction === 'update_financial_record') {
                $recId = Security::decodeId((string)($_POST['record_ref'] ?? ''));
                if ($recId <= 0 || !Finance::find($recId)) throw new Exception('Transaksi tidak ditemukan.');
                Finance::update($recId, $payload);
                $successMsg = "Transaksi \"{$payload['title']}\" berhasil diperbarui.";
            } else {
                Finance::create($payload);
                $successMsg = "Transaksi \"{$payload['title']}\" berhasil dicatat.";
            }
        } elseif ($formAction === 'delete_financial_record') {
            $recId = Security::decodeId((string)($_POST['record_ref'] ?? ''));
            if ($recId > 0) Finance::delete($recId);
            $successMsg = "Catatan transaksi kas berhasil dihapus.";
        } elseif ($formAction === 'mark_participant_paid') {
            $spSession = Security::decodeId((string)($_POST['session_ref'] ?? ''));
            $spPlayer = Security::decodeId((string)($_POST['player_ref'] ?? ''));
            if ($spSession > 0 && $spPlayer > 0) Finance::markParticipantPaid($spSession, $spPlayer);
            $successMsg = "Iuran " . trim((string)($_POST['player_name'] ?? 'pemain')) . " ditandai lunas.";
        }

        // Post/Redirect/Get for finance actions: refreshing on mobile never re-submits a transaction
        if (in_array($formAction, ['create_financial_record', 'update_financial_record', 'delete_financial_record', 'mark_participant_paid'], true)) {
            if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
            $_SESSION['flash_success'] = $successMsg;
            $back = ['tab' => 'finance'];
            foreach (['fin_month', 'fin_session', 'fin_type'] as $k) {
                if (!empty($_POST['ret_' . $k])) $back[$k] = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$_POST['ret_' . $k]);
            }
            $panel = preg_replace('/[^a-z]/', '', (string)($_POST['ret_panel'] ?? ''));
            header('Location: ?' . http_build_query($back) . ($panel ? '#' . $panel : ''), true, 303);
            exit;
        }
    } catch (Exception $e) {
        $errorMsg = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Data fetching
$settings = Community::getSettings();
$gallery = Community::getGallery(30);
$allSchedules = Schedule::all();
$players = Player::all(false);
$user = Auth::user();

// Fetch PIC & Media accounts
$pdo = Database::getConnection();
$picAccounts = $pdo->query("SELECT a.*, s.venue_name, s.day_name, s.court_count, s.time_start FROM admins a LEFT JOIN schedules s ON a.assigned_session_id = s.id WHERE a.role IN ('pic', 'pic_foto') ORDER BY a.id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Roster Filter state (Supports Hash Token)
$rawRosterSession = isset($_GET['roster_session']) ? (string)$_GET['roster_session'] : '';
$filterRosterSession = is_numeric($rawRosterSession) ? (int)$rawRosterSession : Security::decodeId($rawRosterSession);
$filterRosterBranch = isset($_GET['roster_branch']) ? trim($_GET['roster_branch']) : '';
$currentFilterSession = $filterRosterSession > 0 ? Schedule::find($filterRosterSession) : null;
$rosterPlayers = Player::getPlayersWithBranches($filterRosterSession, $filterRosterBranch);

// Edit Schedule state (Supports Hash Token)
$editSchedule = null;
$rawEditParam = isset($_GET['edit']) ? (string)$_GET['edit'] : (isset($_GET['edit_id']) ? (string)$_GET['edit_id'] : '');
$resolvedEditId = is_numeric($rawEditParam) ? (int)$rawEditParam : Security::decodeId($rawEditParam);
if ($resolvedEditId > 0) {
    $editSchedule = Schedule::find($resolvedEditId);
    $tab = 'schedule_form';
}

// WA Template & Roster Workspace state (Supports Hash Token)
$rawWaParam = isset($_GET['wa']) ? (string)$_GET['wa'] : (isset($_GET['wa_session_id']) ? (string)$_GET['wa_session_id'] : '');
$waSessionId = is_numeric($rawWaParam) ? (int)$rawWaParam : Security::decodeId($rawWaParam);
$waSession = null;
$waParticipants = [];
if ($waSessionId > 0) {
    $waSession = Schedule::find($waSessionId);
    if ($waSession) {
        $waParticipants = Participant::getBySession($waSessionId);
    }
}

// Leaderboard of the Month Filters (Supports Hash Token)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$rawSessionFilter = isset($_GET['session_filter']) ? (string)$_GET['session_filter'] : '';
$selectedSession = is_numeric($rawSessionFilter) ? (int)$rawSessionFilter : Security::decodeId($rawSessionFilter);
$monthlyLeaderboard = Player::getMonthlyLeaderboard($selectedMonth === 'all' ? null : $selectedMonth, $selectedSession, 100);
$topScorer = !empty($monthlyLeaderboard) ? $monthlyLeaderboard[0] : null;

// Financial Management state (Supports Hash Token)
$finMonthFilter = isset($_GET['fin_month']) ? (string)$_GET['fin_month'] : date('Y-m');
if ($finMonthFilter !== 'all' && !preg_match('/^\d{4}-\d{2}$/', $finMonthFilter)) $finMonthFilter = date('Y-m');
$rawFinSession = isset($_GET['fin_session']) ? (string)$_GET['fin_session'] : '';
$finSessionFilter = is_numeric($rawFinSession) ? (int)$rawFinSession : Security::decodeId($rawFinSession);
$finTypeFilter = isset($_GET['fin_type']) && in_array($_GET['fin_type'], ['income', 'expense'], true) ? $_GET['fin_type'] : '';
$finMonthSql = $finMonthFilter === 'all' ? '' : $finMonthFilter;

$financialSummary = Finance::getSummary($finSessionFilter, $finMonthSql);
$financialRecords = Finance::getAll([
    'session_id' => $finSessionFilter,
    'month' => $finMonthSql,
    'type' => $finTypeFilter
]);
$sessionFinBreakdown = Finance::getSessionBreakdown(15);

// Extended finance datasets (only computed on the finance tab)
$finPrevSummary = null;
$finTrend = [];
$finExpenseCats = [];
$finUnpaid = [];
if ($tab === 'finance') {
    if ($finMonthSql !== '') {
        $finPrevSummary = Finance::getSummary($finSessionFilter, date('Y-m', strtotime($finMonthSql . '-01 -1 month')));
    }
    $finTrend = Finance::getMonthlyTrend(6);
    $finExpenseCats = Finance::getCategoryBreakdown($finSessionFilter, $finMonthSql, 'expense');
    $finUnpaid = Finance::getUnpaidParticipants($finSessionFilter, $finMonthSql);
}

if (!function_exists('idMonthLabel')) {
    function idMonthLabel(string $ym, bool $short = false): string {
        $full = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $abbr = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $ts = strtotime($ym . '-01');
        if (!$ts) return $ym;
        $m = (int)date('n', $ts);
        return ($short ? $abbr[$m] : $full[$m]) . ' ' . date('Y', $ts);
    }
}
if (!function_exists('rupiah')) {
    function rupiah($n): string {
        return 'Rp ' . number_format((int)$n, 0, ',', '.');
    }
}

// CSV export of the currently filtered cash book (opens directly in Excel / Google Sheets)
if ($isLoggedIn && $tab === 'finance' && isset($_GET['export']) && $_GET['export'] === 'csv') {
    $fname = 'laporan-kas-' . ($finMonthSql ?: 'semua') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Laporan Kas ' . ($settings['community_name'] ?? ''), $finMonthSql ? idMonthLabel($finMonthSql) : 'Semua Periode'], ';');
    fputcsv($out, [], ';');
    fputcsv($out, ['Ringkasan', 'Nominal'], ';');
    fputcsv($out, ['Iuran pemain lunas', $financialSummary['total_iuran_collected']], ';');
    fputcsv($out, ['Pemasukan lain', $financialSummary['total_extra_income']], ';');
    fputcsv($out, ['Total pemasukan', $financialSummary['total_income']], ';');
    fputcsv($out, ['Total pengeluaran', $financialSummary['total_expense']], ';');
    fputcsv($out, ['Saldo bersih', $financialSummary['net_cashflow']], ';');
    fputcsv($out, ['Iuran belum dibayar', $financialSummary['total_iuran_pending']], ';');
    fputcsv($out, [], ';');
    fputcsv($out, ['Tanggal', 'Jenis', 'Kategori', 'Judul', 'Catatan', 'Sesi', 'Nominal'], ';');
    foreach ($financialRecords as $fr) {
        fputcsv($out, [
            date('d/m/Y', strtotime($fr['record_date'])),
            $fr['type'] === 'income' ? 'Masuk' : 'Keluar',
            $fr['category'],
            $fr['title'],
            (string)$fr['notes'],
            !empty($fr['venue_name']) ? $fr['venue_name'] . ' (' . date('d/m/Y', strtotime($fr['schedule_date'])) . ')' : 'Kas umum',
            ($fr['type'] === 'income' ? 1 : -1) * (int)$fr['amount']
        ], ';');
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMS Pengurus — <?= htmlspecialchars($settings['community_name']) ?></title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= $baseDir ?>/shared/assets/css/style.css?v=<?= time() ?>">
  <style>
    * {
      font-stretch: normal !important;
      -webkit-tap-highlight-color: transparent;
    }
    body, h1, h2, h3, h4, h5, h6, p, span, button, input, select, textarea {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      font-stretch: normal !important;
    }

    /* Prevent mobile zoom on Android & iOS on inputs */
    @media (max-width: 640px) {
      input[type="text"], input[type="search"], input[type="number"], input[type="tel"], input[type="email"], input[type="password"], textarea, select {
        font-size: 16px !important;
      }
    }

    /* Clean mobile scrollbar */
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
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
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
<body class="bg-[#f8fafc] text-slate-900 min-h-screen flex flex-col font-sans antialiased selection:bg-amber-400 selection:text-slate-950">

  <?php if (!$isLoggedIn): ?>
    <!-- Login Screen -->
    <div class="min-h-screen flex items-center justify-center p-4">
      <div class="max-w-md w-full bg-white rounded-lg border border-slate-200 p-8 shadow-xl">
        <div class="text-center mb-6">
          <div class="w-12 h-12 rounded-lg bg-brand-50 border border-brand-200 text-brand-700 font-sans font-black text-xl flex items-center justify-center mx-auto mb-3">
            PB
          </div>
          <h1 class="text-2xl font-bold font-sans text-slate-900">Login Admin CMS</h1>
          <p class="text-xs text-slate-500 mt-1">Kelola Jadwal Mabar, List WA & Leaderboard</p>
        </div>

        <?php if ($loginError): ?>
          <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
            <?= htmlspecialchars($loginError) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
          <input type="hidden" name="form_action" value="login">

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Username Admin:</label>
            <input type="text" name="username" required value="admin" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Password:</label>
            <input type="password" name="password" required value="admin123" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700">
            <span class="text-[11px] text-slate-400 mt-1 block">Default: admin / admin123</span>
          </div>

          <button type="submit" class="w-full py-3 rounded-md bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 border border-amber-400/30 font-black text-xs uppercase tracking-wider transition-all btn-tactile shadow-md">
            Masuk ke Panel Admin →
          </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center">
          <a href="<?= $landingUrl ?>" class="text-xs text-slate-500 hover:text-brand-700">← Kembali ke Landing Page</a>
        </div>
      </div>
    </div>

  <?php else: ?>

    <!-- AdminLTE Style Panel Shell -->
    <div class="min-h-screen flex bg-slate-100/70 text-slate-900 font-sans antialiased">

      <!-- Mobile Sidebar Backdrop -->
      <div id="sidebarBackdrop" onclick="toggleSidebar(false)" class="fixed inset-0 bg-slate-950/60 z-40 lg:hidden hidden backdrop-blur-xs transition-opacity duration-200"></div>

      <!-- Left Sidebar (AdminLTE Dark Slate Navigation) -->
      <aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col justify-between border-r border-slate-800 shadow-xl lg:shadow-none transition-transform duration-200 -translate-x-full lg:translate-x-0 lg:static lg:h-screen lg:shrink-0 select-none">
        
        <div class="flex flex-col flex-1 min-h-0">
          
          <!-- Brand Logo Header -->
          <div class="h-16 px-5 border-b border-slate-800 flex items-center justify-between bg-slate-950/40 shrink-0">
            <a href="?tab=dashboard" class="flex items-center gap-3 group min-w-0">
              <?php if (!empty($settings['logo_url'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="w-8 h-8 rounded object-contain border border-slate-700 bg-white/90 p-0.5 shrink-0">
              <?php else: ?>
                <div class="w-8 h-8 rounded bg-[#0b1e36] border border-amber-400/40 text-amber-400 font-black text-xs flex items-center justify-center shrink-0">
                  PB
                </div>
              <?php endif; ?>
              <div class="min-w-0">
                <div class="font-extrabold text-sm text-white tracking-tight truncate leading-tight group-hover:text-amber-400 transition-colors">
                  <?= htmlspecialchars($settings['community_name']) ?>
                </div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-amber-400 font-bold">
                  CONTROL PANEL
                </div>
              </div>
            </a>

            <!-- Mobile Close Button -->
            <button type="button" onclick="toggleSidebar(false)" class="lg:hidden text-slate-400 hover:text-white p-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- User Mini Profile Bar (AdminLTE Style) -->
          <div class="px-5 py-3 border-b border-slate-800/80 bg-slate-950/20 flex items-center gap-3 shrink-0">
            <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-200 font-bold text-xs shrink-0">
              AD
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-xs font-bold text-white truncate">Administrator</div>
              <div class="text-[10px] text-amber-400 font-medium flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Online &bull; Super Admin</span>
              </div>
            </div>
          </div>

          <!-- Categorized Navigation Links (Scrollable if needed) -->
          <nav class="p-3 space-y-5 overflow-y-auto flex-1">
            
            <!-- Group: Main -->
            <div>
              <div class="px-3 pb-2 text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">
                Menu Utama
              </div>
              <div class="space-y-1">
                <a href="?tab=dashboard" class="flex items-center gap-3 px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'dashboard' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                  <span>Dashboard Ringkasan</span>
                </a>

                <a href="?tab=finance" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'finance' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Laporan Keuangan & Kas</span>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded <?= $tab === 'finance' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-emerald-950 text-emerald-400 border border-emerald-800' ?>">
                    KAS
                  </span>
                </a>

                <a href="?tab=schedules" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'schedules' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Jadwal & Kuota Sesi</span>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded <?= $tab === 'schedules' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-800 text-slate-300' ?>">
                    <?= count($allSchedules) ?>
                  </span>
                </a>

                <a href="?tab=schedule_form" class="flex items-center gap-3 px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'schedule_form' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                  <span>+ Tambah Jadwal Baru</span>
                </a>

                <a href="?tab=gallery" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'gallery' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Galeri Dokumentasi</span>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded <?= $tab === 'gallery' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-800 text-slate-300' ?>">
                    <?= count($gallery) ?>
                  </span>
                </a>

                <a href="?tab=roster" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'roster' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Roster Pemain Cabang</span>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded <?= $tab === 'roster' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-800 text-slate-300' ?>">
                    <?= count($players) ?>
                  </span>
                </a>

                <a href="?tab=players" class="flex items-center gap-3 px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'players' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                  <span>Leaderboard Bulanan</span>
                </a>
              </div>
            </div>

            <!-- Group: System -->
            <div>
              <div class="px-3 pb-2 text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">
                Manajemen Sistem
              </div>
              <div class="space-y-1">
                <a href="?tab=pic_accounts" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'pic_accounts' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Akun PIC & Tim Media</span>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded <?= $tab === 'pic_accounts' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-800 text-slate-300' ?>">
                    <?= count($picAccounts) ?>
                  </span>
                </a>

                <a href="?tab=settings" class="flex items-center gap-3 px-3 py-2 rounded-md text-xs font-semibold transition-colors <?= $tab === 'settings' ? 'bg-[#132b4f] text-amber-400 font-bold border-l-4 border-amber-400 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                  <svg class="w-4 h-4 shrink-0 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <span>Profil & Web Settings</span>
                </a>
              </div>
            </div>

            <!-- Group: External -->
            <div>
              <div class="px-3 pb-2 text-[10px] font-mono uppercase tracking-wider text-slate-400 font-bold">
                Akses Langsung
              </div>
              <div class="space-y-1">
                <a href="<?= $landingUrl ?>" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <span>Web Depan Mabar</span>
                  </div>
                  <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <a href="<?= $appUrl ?>" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-md text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>App PIC Operasional</span>
                  </div>
                  <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
              </div>
            </div>

          </nav>

          <!-- Sidebar Footer & Logout -->
          <div class="p-3 border-t border-slate-800 bg-slate-950/40 shrink-0">
            <a href="?action=logout" class="flex items-center gap-3 px-3 py-2 rounded-md text-xs font-bold text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition-colors">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
              <span>Keluar Sistem</span>
            </a>
            <div class="px-3 pt-2 text-[10px] font-mono text-slate-400">
              Admin Panel &bull; v2.4.0
            </div>
          </div>

        </div>

      </aside>

      <!-- Main Panel Wrapper (Topbar + Main Content + Footer) -->
      <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
        
        <!-- Top App Bar (AdminLTE Header) -->
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30 shadow-xs shrink-0">
          
          <div class="flex items-center gap-3">
            <!-- Mobile Hamburger Toggle -->
            <button type="button" onclick="toggleSidebar(true)" class="lg:hidden p-2 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none" aria-label="Buka Menu Sidebar">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs">
              <span class="text-slate-400 font-mono">Control Panel</span>
              <span class="text-slate-300">/</span>
              <?php
                $tabTitles = [
                  'dashboard' => 'Dashboard Ringkasan',
                  'finance' => 'Laporan Keuangan & Kas Komunitas',
                  'schedules' => 'Jadwal Sesi & List Peserta WA',
                  'schedule_form' => ($editSchedule ? 'Edit Jadwal Sesi' : 'Tambah Jadwal Baru'),
                  'gallery' => 'Galeri Dokumentasi Foto',
                  'roster' => 'Roster Pemain Cabang',
                  'players' => 'Leaderboard of the Month',
                  'pic_accounts' => 'Akun Login PIC Sesi',
                  'settings' => 'Profil Komunitas & Web Settings',
                ];
                $activeTabLabel = isset($tabTitles[$tab]) ? $tabTitles[$tab] : 'Dashboard';
              ?>
              <span class="font-extrabold text-slate-900">
                <?= $activeTabLabel ?>
              </span>
            </div>
          </div>

          <!-- Topbar Right Utilities -->
          <div class="flex items-center gap-3">
            <a href="<?= $landingUrl ?>" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors">
              <span>Web Depan</span>
              <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>

            <a href="<?= $appUrl ?>" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors">
              <span>App PIC Lapangan</span>
              <svg class="w-3 h-3 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>

            <a href="?action=logout" class="px-3 py-1.5 rounded-md text-xs font-bold text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors">
              Keluar
            </a>
          </div>

        </header>

        <!-- Main Body Content (Mobile Thumb Optimized) -->
        <main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto pb-24 lg:pb-8">
      
      <?php if ($successMsg): ?>
        <div class="mb-6 p-4 rounded-md bg-blue-50 border border-blue-200 text-blue-900 text-xs font-bold flex items-center justify-between">
          <span><?= htmlspecialchars($successMsg) ?></span>
          <button onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-950 font-bold ml-4">&times;</button>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="mb-6 p-4 rounded-md bg-red-50 border border-red-200 text-red-800 text-xs font-bold flex items-center justify-between">
          <span><?= htmlspecialchars($errorMsg) ?></span>
          <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900 font-bold ml-4">&times;</button>
        </div>
      <?php endif; ?>


      <!-- TAB 1: DASHBOARD -->
      <?php if ($tab === 'dashboard'): ?>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
          <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Total Pemain Terdaftar</span>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 font-sans font-bold"><?= count($players) ?></div>
            <span class="text-[11px] text-slate-400 mt-1 block">Roster aktif komunitas</span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Cabang Sesi Mabar</span>
            <div class="text-2xl sm:text-3xl font-black text-brand-700 font-sans font-bold"><?= count($allSchedules) ?></div>
            <span class="text-[11px] text-slate-400 mt-1 block">Jadwal rutin mingguan</span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Saldo Kas Bersih</span>
              <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-full <?= $financialSummary['net_cashflow'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?> font-black">
                <?= $financialSummary['net_cashflow'] >= 0 ? 'SURPLUS' : 'DEFISIT' ?>
              </span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-mono <?= $financialSummary['net_cashflow'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>">
              Rp <?= number_format($financialSummary['net_cashflow'], 0, ',', '.') ?>
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">Kas Masuk - Keluar</span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Foto Galeri Depan</span>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 font-sans font-bold"><?= count($gallery) ?></div>
            <span class="text-[11px] text-slate-400 mt-1 block">Tampil di Landing Page</span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Top Player of Month</span>
            <div class="text-lg sm:text-xl font-black text-blue-700 font-sans font-bold truncate">
              <?= $topScorer ? htmlspecialchars($topScorer['name']) : '-' ?>
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block"><?= $topScorer ? number_format($topScorer['total_points']) . ' Poin' : 'Belum ada poin' ?></span>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-5 sm:p-6 shadow-sm mb-6">
          <h3 class="text-lg font-bold text-slate-900 mb-2">Panduan Pengurus <?= htmlspecialchars(!empty($settings['community_name']) ? $settings['community_name'] : 'Badminton Club') ?></h3>
          <p class="text-xs text-slate-600 leading-relaxed max-w-2xl mb-4">
            Di panel ini Anda dapat mengelola cabang jadwal mabar mingguan (dengan pencarian Google Maps untuk alamat GOR, nomor WhatsApp PIC, dan kuota slot), memantau laporan keuangan arus kas, membuat template broadcast WhatsApp otomatis, menempelkan (*paste*) list nama peserta langsung dari WA grup, serta memantau Leaderboard of the Month.
          </p>
          <div class="flex flex-wrap gap-2.5">
            <a href="?tab=finance" class="px-4 py-2 rounded-md bg-[#0b1e36] text-amber-400 font-bold text-xs uppercase tracking-wider hover:bg-[#132b4f] transition-all border border-amber-400/30">
              Lihat Laporan Keuangan Kas →
            </a>
            <a href="?tab=schedules" class="px-4 py-2 rounded-md bg-slate-900 text-white font-bold text-xs uppercase tracking-wider hover:bg-slate-800 transition-all">
              Kelola Sesi & Peserta WA →
            </a>
            <a href="?tab=players" class="px-4 py-2 rounded-md bg-emerald-50 text-blue-900 border border-blue-200 font-bold text-xs uppercase tracking-wider hover:bg-amber-100 transition-all">
              Lihat Leaderboard Bulanan →
            </a>
          </div>
        </div>

      <!-- TAB: LAPORAN KEUANGAN & KAS KOMUNITAS -->
      <?php elseif ($tab === 'finance'):
        $finSessionHash = $finSessionFilter > 0 ? Security::hashId($finSessionFilter) : '';
        $finLink = function (array $over = []) use ($finMonthFilter, $finSessionHash, $finTypeFilter) {
            $q = array_merge(['tab' => 'finance', 'fin_month' => $finMonthFilter, 'fin_session' => $finSessionHash, 'fin_type' => $finTypeFilter], $over);
            $q = array_filter($q, function ($v) { return $v !== '' && $v !== null; });
            return '?' . http_build_query($q);
        };
        $finRetInputs = '<input type="hidden" name="ret_fin_month" value="' . htmlspecialchars($finMonthFilter) . '">'
            . '<input type="hidden" name="ret_fin_session" value="' . htmlspecialchars($finSessionHash) . '">'
            . '<input type="hidden" name="ret_fin_type" value="' . htmlspecialchars($finTypeFilter) . '">'
            . '<input type="hidden" name="ret_panel" value="kas" class="js-ret-panel">';

        $finHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $finBlnShort = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $finDateLabel = function (string $date, bool $withDay = true) use ($finHari, $finBlnShort) {
            $ts = strtotime($date);
            if (!$ts) return $date;
            $txt = date('j', $ts) . ' ' . $finBlnShort[(int)date('n', $ts)] . ' ' . date('Y', $ts);
            return $withDay ? $finHari[(int)date('w', $ts)] . ', ' . $txt : $txt;
        };

        $sum = $financialSummary;
        $periodLabel = $finMonthSql !== '' ? idMonthLabel($finMonthSql) : 'Semua periode';
        $finActiveSession = null;
        if ($finSessionFilter > 0) {
            foreach ($allSchedules as $sch) { if ((int)$sch['id'] === $finSessionFilter) { $finActiveSession = $sch; break; } }
        }
        $isSurplus = $sum['net_cashflow'] >= 0;
        $flowBase = max(1, $sum['total_income'] + $sum['total_expense']);
        $incPct = round($sum['total_income'] / $flowBase * 100);
        $expPct = 100 - $incPct;
        if ($sum['total_income'] + $sum['total_expense'] === 0) { $incPct = 0; $expPct = 0; }

        $finDelta = null;
        if ($finPrevSummary !== null) {
            $finDelta = $sum['net_cashflow'] - $finPrevSummary['net_cashflow'];
            $finPrevLabel = idMonthLabel(date('Y-m', strtotime($finMonthSql . '-01 -1 month')));
        }

        $finGrouped = [];
        foreach ($financialRecords as $fr) { $finGrouped[$fr['record_date']][] = $fr; }

        $finUnpaidGroups = [];
        $finUnpaidTotal = 0;
        foreach ($finUnpaid as $u) {
            $finUnpaidGroups[$u['session_id']]['meta'] = $u;
            $finUnpaidGroups[$u['session_id']]['rows'][] = $u;
            $finUnpaidTotal += (int)$u['payment_amount'];
        }

        $trendMax = 1;
        foreach ($finTrend as $t) { $trendMax = max($trendMax, $t['income'], $t['expense']); }
        $catTotal = 0;
        foreach ($finExpenseCats as $c) { $catTotal += (int)$c['total']; }

        $finCategories = [
            ['Sewa Lapangan', 'Sewa lapangan', 'expense'],
            ['Shuttlecock', 'Shuttlecock', 'expense'],
            ['Konsumsi', 'Konsumsi', 'expense'],
            ['Honor PIC', 'Honor PIC', 'expense'],
            ['Peralatan', 'Peralatan', 'expense'],
            ['Iuran Tambahan', 'Iuran tambahan', 'income'],
            ['Sponsor', 'Sponsor', 'income'],
            ['Lain-lain', 'Lain-lain', 'both'],
        ];
      ?>

        <style>
          .fin-scroll { scrollbar-width: none; -webkit-overflow-scrolling: touch; }
          .fin-scroll::-webkit-scrollbar { display: none; }
          .fin-tap { -webkit-tap-highlight-color: transparent; }
          .fin-sheet-panel { animation: finSheetUp .22s cubic-bezier(.2,.8,.2,1); }
          @keyframes finSheetUp { from { transform: translateY(24px); opacity: 0; } to { transform: none; opacity: 1; } }
          @media (prefers-reduced-motion: reduce) { .fin-sheet-panel { animation: none; } }
          @media print {
            #adminSidebar, #sidebarBackdrop, header, .no-print, #modalAddFinance, #finRecordSheet { display: none !important; }
            [data-fin-panel] { display: block !important; break-inside: avoid; margin-bottom: 16px; }
            .fin-wrap { padding-bottom: 0 !important; }
            .fin-hero { background: #fff !important; color: #0f172a !important; border: 1px solid #cbd5e1; }
          }
        </style>

        <div class="fin-wrap pb-28 lg:pb-0">

          <!-- Header -->
          <div class="flex items-start justify-between gap-3 mb-4">
            <div class="min-w-0">
              <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-600">Keuangan</p>
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">Laporan Kas Komunitas</h2>
              <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                <?= htmlspecialchars($periodLabel) ?>
                <?php if ($finActiveSession): ?>
                  · <?= htmlspecialchars($finActiveSession['venue_name']) ?> (<?= htmlspecialchars($finDateLabel($finActiveSession['schedule_date'], false)) ?>)
                <?php else: ?>
                  · Semua sesi
                <?php endif; ?>
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0 no-print">
              <a href="<?= htmlspecialchars($finLink(['export' => 'csv'])) ?>" class="h-11 min-w-[44px] px-3 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="Unduh laporan CSV">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/></svg>
                <span class="hidden sm:inline">Export CSV</span>
              </a>
              <button type="button" onclick="window.print()" class="h-11 min-w-[44px] px-3 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="Cetak laporan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 9V4h10v5M7 17H5a2 2 0 01-2-2v-4a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2h-2M7 14h10v6H7z"/></svg>
                <span class="hidden sm:inline">Cetak</span>
              </button>
              <button type="button" onclick="openFinanceForm()" class="hidden lg:inline-flex h-11 px-4 items-center gap-1.5 rounded-xl bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 text-xs font-black uppercase tracking-wider active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2">
                + Catat Transaksi
              </button>
            </div>
          </div>

          <!-- Period chips -->
          <nav aria-label="Pilih periode laporan" class="no-print -mx-4 px-4 sm:mx-0 sm:px-0 mb-3 overflow-x-auto fin-scroll">
            <div class="flex gap-2 w-max">
              <?php
                $chipBase = 'h-11 px-4 inline-flex items-center rounded-full border text-xs font-bold whitespace-nowrap transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400';
                $chipOn = 'bg-[#0b1e36] border-[#0b1e36] text-amber-400';
                $chipOff = 'bg-white border-slate-200 text-slate-600 hover:border-slate-300 hover:text-slate-900';
                for ($m = 0; $m < 12; $m++):
                  $mVal = date('Y-m', strtotime(date('Y-m-01') . " -{$m} months"));
              ?>
                <a href="<?= htmlspecialchars($finLink(['fin_month' => $mVal])) ?>" class="<?= $chipBase ?> <?= $finMonthFilter === $mVal ? $chipOn : $chipOff ?>" <?= $finMonthFilter === $mVal ? 'aria-current="page"' : '' ?>>
                  <?= $m === 0 ? 'Bulan ini' : idMonthLabel($mVal, true) ?>
                </a>
              <?php endfor; ?>
              <a href="<?= htmlspecialchars($finLink(['fin_month' => 'all'])) ?>" class="<?= $chipBase ?> <?= $finMonthFilter === 'all' ? $chipOn : $chipOff ?>">Semua</a>
            </div>
          </nav>

          <!-- Session filter -->
          <form method="GET" action="" class="no-print mb-4 flex gap-2 items-center">
            <input type="hidden" name="tab" value="finance">
            <input type="hidden" name="fin_month" value="<?= htmlspecialchars($finMonthFilter) ?>">
            <?php if ($finTypeFilter): ?><input type="hidden" name="fin_type" value="<?= htmlspecialchars($finTypeFilter) ?>"><?php endif; ?>
            <label for="finSessionSelect" class="sr-only">Filter sesi mabar</label>
            <div class="flex-1 min-w-0">
              <select id="finSessionSelect" name="fin_session" onchange="this.form.submit()" class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-sm font-semibold text-slate-900">
                <option value="">Semua sesi & cabang</option>
                <?php foreach ($allSchedules as $sch): ?>
                  <option value="<?= Security::hashId((int)$sch['id']) ?>" <?= $finSessionFilter === (int)$sch['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sch['venue_name']) ?> · <?= htmlspecialchars($finDateLabel($sch['schedule_date'], false)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($finSessionFilter > 0): ?>
              <a href="<?= htmlspecialchars($finLink(['fin_session' => ''])) ?>" class="h-11 px-4 inline-flex items-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold active:scale-95 transition">Reset</a>
            <?php endif; ?>
          </form>

          <!-- Hero: net balance -->
          <section class="fin-hero rounded-2xl bg-[#0b1e36] text-white p-5 sm:p-6 mb-3" aria-labelledby="finHeroLabel">
            <div class="flex items-center justify-between gap-3">
              <h3 id="finHeroLabel" class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Saldo bersih</h3>
              <span class="text-[10px] font-mono font-bold px-2 py-1 rounded-md <?= $isSurplus ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' ?>">
                <?= $isSurplus ? 'SURPLUS' : 'DEFISIT' ?>
              </span>
            </div>
            <p class="mt-2 font-mono text-[28px] leading-none sm:text-4xl font-bold tracking-tight <?= $isSurplus ? 'text-white' : 'text-rose-300' ?>">
              <?= $isSurplus ? '' : '−' ?><?= rupiah(abs($sum['net_cashflow'])) ?>
            </p>
            <?php if ($finDelta !== null): ?>
              <p class="mt-2 text-xs text-slate-400">
                <?php if ($finDelta === 0): ?>
                  Sama dengan <?= htmlspecialchars($finPrevLabel) ?>
                <?php else: ?>
                  <span class="font-bold <?= $finDelta > 0 ? 'text-emerald-300' : 'text-rose-300' ?>"><?= $finDelta > 0 ? 'Naik' : 'Turun' ?> <?= rupiah(abs($finDelta)) ?></span>
                  dibanding <?= htmlspecialchars($finPrevLabel) ?>
                <?php endif; ?>
              </p>
            <?php endif; ?>

            <div class="mt-5 h-2 rounded-full bg-white/10 overflow-hidden flex" role="img" aria-label="Perbandingan uang masuk <?= $incPct ?> persen dan uang keluar <?= $expPct ?> persen">
              <div class="h-full bg-emerald-400" style="width: <?= $incPct ?>%"></div>
              <div class="h-full bg-rose-400" style="width: <?= $expPct ?>%"></div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-4">
              <div>
                <p class="text-[11px] text-slate-400 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400"></span>Uang masuk</p>
                <p class="font-mono text-sm sm:text-base font-bold text-emerald-300 mt-0.5"><?= rupiah($sum['total_income']) ?></p>
              </div>
              <div class="text-right">
                <p class="text-[11px] text-slate-400 flex items-center justify-end gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-400"></span>Uang keluar</p>
                <p class="font-mono text-sm sm:text-base font-bold text-rose-300 mt-0.5"><?= rupiah($sum['total_expense']) ?></p>
              </div>
            </div>
          </section>

          <!-- Mini stats -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 mb-5">
            <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 sm:p-4">
              <p class="text-[11px] font-semibold text-slate-500">Iuran lunas</p>
              <p class="font-mono text-[15px] sm:text-xl font-bold text-slate-900 mt-1"><?= rupiah($sum['total_iuran_collected']) ?></p>
              <p class="text-[11px] text-slate-400 mt-0.5"><?= (int)$sum['count_paid'] ?> pemain</p>
            </div>
            <button type="button" data-fin-goto="piutang" class="fin-tap text-left bg-white border rounded-xl p-3.5 sm:p-4 transition active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 <?= $sum['count_unpaid'] > 0 ? 'border-amber-300 bg-amber-50/60 hover:bg-amber-50' : 'border-slate-200/80 hover:bg-slate-50' ?>">
              <p class="text-[11px] font-semibold <?= $sum['count_unpaid'] > 0 ? 'text-amber-800' : 'text-slate-500' ?>">Belum bayar</p>
              <p class="font-mono text-[15px] sm:text-xl font-bold mt-1 <?= $sum['count_unpaid'] > 0 ? 'text-amber-700' : 'text-slate-900' ?>"><?= rupiah($sum['total_iuran_pending']) ?></p>
              <p class="text-[11px] mt-0.5 <?= $sum['count_unpaid'] > 0 ? 'text-amber-700 font-semibold' : 'text-slate-400' ?>"><?= (int)$sum['count_unpaid'] ?> orang<?= $sum['count_unpaid'] > 0 ? ' · lihat daftar' : '' ?></p>
            </button>
            <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 sm:p-4">
              <p class="text-[11px] font-semibold text-slate-500">Pemasukan lain</p>
              <p class="font-mono text-[15px] sm:text-xl font-bold text-emerald-700 mt-1"><?= rupiah($sum['total_extra_income']) ?></p>
              <p class="text-[11px] text-slate-400 mt-0.5">Sponsor, denda, dll</p>
            </div>
            <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 sm:p-4">
              <p class="text-[11px] font-semibold text-slate-500">Pengeluaran</p>
              <p class="font-mono text-[15px] sm:text-xl font-bold text-rose-600 mt-1"><?= rupiah($sum['total_expense']) ?></p>
              <p class="text-[11px] text-slate-400 mt-0.5">GOR, kok, operasional</p>
            </div>
          </div>

          <!-- Panel switcher -->
          <div role="tablist" aria-label="Bagian laporan" class="no-print grid grid-cols-4 gap-1 p-1 bg-slate-100 rounded-xl mb-4">
            <?php
              $finTabs = ['kas' => 'Buku Kas', 'sesi' => 'Per Sesi', 'piutang' => 'Piutang', 'analisis' => 'Analisis'];
              foreach ($finTabs as $key => $label):
            ?>
              <button type="button" role="tab" data-fin-tab="<?= $key ?>" aria-controls="finPanel-<?= $key ?>" class="fin-tap h-11 rounded-lg text-[12px] sm:text-xs font-bold text-slate-500 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 relative">
                <?= $label ?>
                <?php if ($key === 'piutang' && count($finUnpaid) > 0): ?>
                  <span class="absolute top-1 right-1 sm:static sm:ml-1 inline-flex min-w-[18px] h-[18px] px-1 items-center justify-center rounded-full bg-amber-400 text-slate-950 text-[10px] font-black"><?= count($finUnpaid) ?></span>
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>

          <!-- PANEL: Buku Kas -->
          <section id="finPanel-kas" data-fin-panel="kas" role="tabpanel" class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3">
              <div class="flex gap-1 p-1 bg-slate-100 rounded-lg no-print" role="group" aria-label="Jenis transaksi">
                <?php foreach (['' => 'Semua', 'income' => 'Masuk', 'expense' => 'Keluar'] as $tv => $tl): ?>
                  <a href="<?= htmlspecialchars($finLink(['fin_type' => $tv])) ?>#kas" class="h-9 px-3 inline-flex items-center rounded-md text-xs font-bold transition <?= $finTypeFilter === $tv ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>"><?= $tl ?></a>
                <?php endforeach; ?>
              </div>
              <span class="text-[11px] font-mono text-slate-400 whitespace-nowrap"><?= count($financialRecords) ?> transaksi</span>
            </div>

            <?php if (empty($financialRecords)): ?>
              <div class="px-6 py-12 text-center">
                <p class="text-sm font-bold text-slate-800">Belum ada transaksi di periode ini</p>
                <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Catat sewa lapangan, pembelian kok, atau pemasukan sponsor supaya saldo kas selalu akurat.</p>
                <button type="button" onclick="openFinanceForm()" class="no-print mt-4 h-11 px-5 rounded-xl bg-[#0b1e36] text-amber-400 text-xs font-black uppercase tracking-wider active:scale-95 transition">Catat transaksi</button>
              </div>
            <?php else: ?>
              <?php foreach ($finGrouped as $gDate => $gRows):
                $daySum = 0;
                foreach ($gRows as $r) { $daySum += ($r['type'] === 'income' ? 1 : -1) * (int)$r['amount']; }
              ?>
                <div class="px-4 sm:px-5 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between sticky top-0 z-[1]">
                  <span class="text-[11px] font-bold text-slate-600"><?= htmlspecialchars($finDateLabel($gDate)) ?></span>
                  <span class="text-[11px] font-mono font-bold <?= $daySum >= 0 ? 'text-emerald-700' : 'text-rose-600' ?>"><?= $daySum >= 0 ? '+' : '−' ?><?= rupiah(abs($daySum)) ?></span>
                </div>
                <ul class="divide-y divide-slate-100">
                  <?php foreach ($gRows as $fr):
                    $isExp = $fr['type'] === 'expense';
                    $sessLabel = !empty($fr['venue_name']) ? $fr['venue_name'] . ' · ' . $finDateLabel($fr['schedule_date'], false) : 'Kas umum';
                    $payload = [
                      'ref' => Security::hashId((int)$fr['id']),
                      'type' => $fr['type'],
                      'category' => $fr['category'],
                      'amount' => (int)$fr['amount'],
                      'title' => $fr['title'],
                      'notes' => (string)$fr['notes'],
                      'record_date' => $fr['record_date'],
                      'date_label' => $finDateLabel($fr['record_date']),
                      'session_id' => $fr['session_id'] ? (int)$fr['session_id'] : '',
                      'session_label' => $sessLabel,
                      'creator' => (string)($fr['creator_name'] ?? ''),
                    ];
                  ?>
                    <li>
                      <button type="button" data-fin-record="<?= htmlspecialchars(json_encode($payload), ENT_QUOTES) ?>" class="fin-tap w-full text-left px-4 sm:px-5 py-3.5 flex items-start gap-3 hover:bg-slate-50 active:bg-slate-100 transition focus-visible:outline-none focus-visible:bg-amber-50">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0 <?= $isExp ? 'bg-rose-500' : 'bg-emerald-500' ?>" aria-hidden="true"></span>
                        <span class="flex-1 min-w-0">
                          <span class="block text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($fr['title']) ?></span>
                          <span class="block text-xs text-slate-500 truncate mt-0.5"><?= htmlspecialchars($fr['category']) ?> · <?= htmlspecialchars($sessLabel) ?></span>
                          <?php if (!empty($fr['notes'])): ?>
                            <span class="block text-xs text-slate-400 truncate mt-0.5"><?= htmlspecialchars($fr['notes']) ?></span>
                          <?php endif; ?>
                        </span>
                        <span class="font-mono text-sm font-bold whitespace-nowrap <?= $isExp ? 'text-rose-600' : 'text-emerald-700' ?>">
                          <span class="sr-only"><?= $isExp ? 'Keluar' : 'Masuk' ?></span><?= $isExp ? '−' : '+' ?><?= rupiah($fr['amount']) ?>
                        </span>
                      </button>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endforeach; ?>
              <p class="no-print px-4 sm:px-5 py-3 text-[11px] text-slate-400 border-t border-slate-100">Ketuk transaksi untuk melihat detail, mengubah, atau menghapus.</p>
            <?php endif; ?>
          </section>

          <!-- PANEL: Per Sesi -->
          <section id="finPanel-sesi" data-fin-panel="sesi" role="tabpanel" class="hidden">
            <div class="flex items-baseline justify-between mb-3 px-1">
              <h3 class="text-sm font-black text-slate-900">Laba / rugi per sesi</h3>
              <span class="text-[11px] font-mono text-slate-400">15 sesi terakhir</span>
            </div>
            <?php if (empty($sessionFinBreakdown)): ?>
              <div class="bg-white border border-slate-200/80 rounded-2xl px-6 py-12 text-center text-sm text-slate-500">Belum ada data sesi mabar.</div>
            <?php else: ?>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($sessionFinBreakdown as $sb):
                  $isProfit = $sb['net_profit'] >= 0;
                  $cover = (int)$sb['expense_total'] > 0 ? min(100, round($sb['total_income'] / max(1, $sb['expense_total']) * 100)) : 100;
                ?>
                  <article class="bg-white border border-slate-200/80 rounded-2xl p-4">
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <h4 class="text-sm font-black text-slate-900 truncate"><?= htmlspecialchars($sb['venue_name']) ?></h4>
                        <p class="text-[11px] text-slate-500 mt-0.5"><?= htmlspecialchars($finDateLabel($sb['schedule_date'])) ?> · <?= (int)$sb['court_count'] ?> lap · <?= (int)$sb['total_players'] ?> pemain</p>
                      </div>
                      <span class="shrink-0 text-[10px] font-mono font-bold px-2 py-1 rounded-md <?= $isProfit ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>"><?= $isProfit ? 'SURPLUS' : 'DEFISIT' ?></span>
                    </div>
                    <dl class="grid grid-cols-3 gap-2 mt-3.5">
                      <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                        <dt class="text-[10px] font-semibold text-slate-500">Masuk</dt>
                        <dd class="font-mono text-[12px] sm:text-sm font-bold text-slate-900 mt-0.5 truncate"><?= rupiah($sb['total_income']) ?></dd>
                      </div>
                      <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                        <dt class="text-[10px] font-semibold text-slate-500">Biaya</dt>
                        <dd class="font-mono text-[12px] sm:text-sm font-bold text-rose-600 mt-0.5 truncate"><?= rupiah($sb['expense_total']) ?></dd>
                      </div>
                      <div class="rounded-lg px-2.5 py-2 <?= $isProfit ? 'bg-emerald-50' : 'bg-rose-50' ?>">
                        <dt class="text-[10px] font-semibold <?= $isProfit ? 'text-emerald-700' : 'text-rose-700' ?>">Hasil</dt>
                        <dd class="font-mono text-[12px] sm:text-sm font-bold mt-0.5 truncate <?= $isProfit ? 'text-emerald-700' : 'text-rose-700' ?>"><?= $isProfit ? '+' : '−' ?><?= rupiah(abs($sb['net_profit'])) ?></dd>
                      </div>
                    </dl>
                    <div class="mt-3">
                      <div class="flex justify-between text-[10px] text-slate-500 mb-1">
                        <span>Biaya tertutup iuran</span>
                        <span class="font-mono font-bold text-slate-700"><?= (int)$sb['expense_total'] > 0 ? $cover . '%' : 'Belum ada biaya' ?></span>
                      </div>
                      <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full <?= $cover >= 100 ? 'bg-emerald-500' : ($cover >= 70 ? 'bg-amber-400' : 'bg-rose-500') ?>" style="width: <?= $cover ?>%"></div>
                      </div>
                    </div>
                    <?php if ($sb['pending_iuran'] > 0): ?>
                      <p class="mt-2.5 text-[11px] font-semibold text-amber-700">Masih ada iuran belum bayar <?= rupiah($sb['pending_iuran']) ?></p>
                    <?php endif; ?>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <!-- PANEL: Piutang (iuran belum dibayar) -->
          <section id="finPanel-piutang" data-fin-panel="piutang" role="tabpanel" class="hidden">
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3.5 mb-3 flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Total piutang iuran</p>
                <p class="font-mono text-lg font-bold text-amber-900 mt-0.5"><?= rupiah($finUnpaidTotal) ?></p>
              </div>
              <p class="text-xs font-semibold text-amber-800 text-right"><?= count($finUnpaid) ?> pemain<br><span class="font-normal text-amber-700"><?= htmlspecialchars($periodLabel) ?></span></p>
            </div>

            <?php if (empty($finUnpaidGroups)): ?>
              <div class="bg-white border border-slate-200/80 rounded-2xl px-6 py-12 text-center">
                <p class="text-sm font-bold text-slate-800">Semua iuran sudah lunas</p>
                <p class="text-xs text-slate-500 mt-1">Tidak ada pemain yang menunggak di periode ini.</p>
              </div>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($finUnpaidGroups as $grp):
                  $meta = $grp['meta'];
                  $grpTotal = array_sum(array_map(function ($r) { return (int)$r['payment_amount']; }, $grp['rows']));
                ?>
                  <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                      <div class="min-w-0">
                        <h4 class="text-sm font-black text-slate-900 truncate"><?= htmlspecialchars($meta['venue_name']) ?></h4>
                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($finDateLabel($meta['schedule_date'])) ?></p>
                      </div>
                      <span class="font-mono text-xs font-bold text-amber-700 whitespace-nowrap"><?= rupiah($grpTotal) ?></span>
                    </div>
                    <ul class="divide-y divide-slate-100">
                      <?php foreach ($grp['rows'] as $u):
                        $digits = preg_replace('/\D/', '', (string)$u['player_phone']);
                        if (strpos($digits, '0') === 0) $digits = '62' . substr($digits, 1);
                        elseif (strpos($digits, '8') === 0) $digits = '62' . $digits;
                        $waMsg = "Halo {$u['player_name']}, pengingat iuran mabar {$u['venue_name']} tanggal " . $finDateLabel($u['schedule_date'], false) . " sebesar " . rupiah($u['payment_amount']) . " belum tercatat. Mohon konfirmasi pembayarannya ya. Terima kasih!";
                      ?>
                        <li class="px-4 py-3 flex items-center gap-3">
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($u['player_name']) ?></p>
                            <p class="text-[11px] font-mono text-slate-500"><?= rupiah($u['payment_amount']) ?><?= $u['player_phone'] ? ' · ' . htmlspecialchars($u['player_phone']) : '' ?></p>
                          </div>
                          <?php if ($digits !== ''): ?>
                            <a href="https://wa.me/<?= $digits ?>?text=<?= rawurlencode($waMsg) ?>" target="_blank" rel="noopener" class="no-print h-11 px-3 inline-flex items-center rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="Ingatkan <?= htmlspecialchars($u['player_name']) ?> via WhatsApp">Ingatkan</a>
                          <?php endif; ?>
                          <form method="POST" action="" class="no-print">
                            <input type="hidden" name="form_action" value="mark_participant_paid">
                            <input type="hidden" name="session_ref" value="<?= Security::hashId((int)$u['session_id']) ?>">
                            <input type="hidden" name="player_ref" value="<?= Security::hashId((int)$u['player_id']) ?>">
                            <input type="hidden" name="player_name" value="<?= htmlspecialchars($u['player_name']) ?>">
                            <?= $finRetInputs ?>
                            <button type="submit" class="h-11 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2">Lunas</button>
                          </form>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <!-- PANEL: Analisis -->
          <section id="finPanel-analisis" data-fin-panel="analisis" role="tabpanel" class="hidden space-y-3">
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5">
              <div class="flex items-baseline justify-between mb-4">
                <h3 class="text-sm font-black text-slate-900">Arus kas 6 bulan</h3>
                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                  <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span>Masuk</span>
                  <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-rose-400"></span>Keluar</span>
                </div>
              </div>
              <div class="grid grid-cols-6 gap-2 h-36 items-end" role="img" aria-label="Grafik pemasukan dan pengeluaran 6 bulan terakhir">
                <?php foreach ($finTrend as $t):
                  $hi = round($t['income'] / $trendMax * 100);
                  $he = round($t['expense'] / $trendMax * 100);
                  $isCur = $t['month'] === $finMonthSql;
                ?>
                  <a href="<?= htmlspecialchars($finLink(['fin_month' => $t['month']])) ?>#analisis" class="fin-tap h-full flex flex-col justify-end rounded-lg <?= $isCur ? 'bg-amber-50 ring-1 ring-amber-300' : 'hover:bg-slate-50' ?> px-1 pt-1" title="<?= idMonthLabel($t['month']) ?>: masuk <?= rupiah($t['income']) ?>, keluar <?= rupiah($t['expense']) ?>">
                    <div class="flex-1 flex items-end justify-center gap-0.5">
                      <div class="w-1/2 max-w-[14px] rounded-t bg-emerald-500" style="height: <?= max($t['income'] > 0 ? 3 : 0, $hi) ?>%"></div>
                      <div class="w-1/2 max-w-[14px] rounded-t bg-rose-400" style="height: <?= max($t['expense'] > 0 ? 3 : 0, $he) ?>%"></div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
              <div class="grid grid-cols-6 gap-2 mt-1.5">
                <?php foreach ($finTrend as $t): ?>
                  <span class="text-center text-[10px] font-bold <?= $t['month'] === $finMonthSql ? 'text-amber-700' : 'text-slate-500' ?>"><?= $finBlnShort[(int)date('n', strtotime($t['month'] . '-01'))] ?></span>
                <?php endforeach; ?>
              </div>

              <ul class="mt-4 divide-y divide-slate-100 border-t border-slate-100">
                <?php foreach (array_reverse($finTrend) as $t):
                  $net = $t['income'] - $t['expense'];
                ?>
                  <li class="py-2.5 grid grid-cols-[1fr_auto] gap-x-3 text-xs">
                    <span class="font-bold text-slate-800"><?= idMonthLabel($t['month']) ?></span>
                    <span class="font-mono font-bold text-right <?= $net >= 0 ? 'text-emerald-700' : 'text-rose-600' ?>"><?= $net >= 0 ? '+' : '−' ?><?= rupiah(abs($net)) ?></span>
                    <span class="text-[11px] text-slate-400 font-mono col-span-2">masuk <?= rupiah($t['income']) ?> · keluar <?= rupiah($t['expense']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5">
              <div class="flex items-baseline justify-between mb-3">
                <h3 class="text-sm font-black text-slate-900">Pengeluaran per kategori</h3>
                <span class="text-[11px] text-slate-400"><?= htmlspecialchars($periodLabel) ?></span>
              </div>
              <?php if (empty($finExpenseCats)): ?>
                <p class="text-xs text-slate-500 py-6 text-center">Belum ada pengeluaran tercatat.</p>
              <?php else: ?>
                <ul class="space-y-3.5">
                  <?php foreach ($finExpenseCats as $c):
                    $pct = $catTotal > 0 ? round($c['total'] / $catTotal * 100) : 0;
                  ?>
                    <li>
                      <div class="flex items-baseline justify-between gap-3 text-xs">
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($c['category']) ?> <span class="font-normal text-slate-400">· <?= (int)$c['trx_count'] ?>×</span></span>
                        <span class="font-mono font-bold text-slate-900 whitespace-nowrap"><?= rupiah($c['total']) ?> <span class="text-slate-400 font-semibold"><?= $pct ?>%</span></span>
                      </div>
                      <div class="mt-1.5 h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-[#0b1e36]" style="width: <?= $pct ?>%"></div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <!-- Mobile primary action -->
        <div class="no-print lg:hidden fixed inset-x-0 bottom-0 z-30 px-4 pt-3 bg-gradient-to-t from-slate-50 via-slate-50/95 to-transparent" style="padding-bottom: max(16px, env(safe-area-inset-bottom));">
          <button type="button" onclick="openFinanceForm()" class="w-full h-14 rounded-2xl bg-[#0b1e36] text-amber-400 text-sm font-black uppercase tracking-wider shadow-lg shadow-slate-900/20 active:scale-[0.98] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2">
            + Catat Transaksi
          </button>
        </div>

        <!-- Sheet: Catat / Ubah Transaksi -->
        <div id="modalAddFinance" class="hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-labelledby="finFormTitle">
          <div class="absolute inset-0 bg-slate-950/60" data-fin-close></div>
          <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4 pointer-events-none">
            <div class="fin-sheet-panel pointer-events-auto bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-2xl max-h-[92vh] flex flex-col overflow-hidden">
              <div class="px-5 pt-2.5 pb-3 border-b border-slate-100 shrink-0">
                <div class="sm:hidden mx-auto mb-2.5 w-10 h-1 rounded-full bg-slate-300" aria-hidden="true"></div>
                <div class="flex items-center justify-between gap-3">
                  <h3 id="finFormTitle" class="text-base font-black text-slate-900">Catat transaksi</h3>
                  <button type="button" data-fin-close class="w-11 h-11 -mr-2 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-800 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>

              <form id="finForm" method="POST" action="" class="flex-1 flex flex-col min-h-0">
                <input type="hidden" name="form_action" id="finFormAction" value="create_financial_record">
                <input type="hidden" name="record_ref" id="finRecordRef" value="">
                <?= $finRetInputs ?>

                <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-4 space-y-5">
                  <fieldset>
                    <legend class="sr-only">Jenis transaksi</legend>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                      <label class="cursor-pointer">
                        <input type="radio" name="type" value="expense" class="peer sr-only" checked>
                        <span class="h-11 flex items-center justify-center rounded-lg text-sm font-bold text-slate-500 transition peer-checked:bg-white peer-checked:text-rose-600 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400">Uang keluar</span>
                      </label>
                      <label class="cursor-pointer">
                        <input type="radio" name="type" value="income" class="peer sr-only">
                        <span class="h-11 flex items-center justify-center rounded-lg text-sm font-bold text-slate-500 transition peer-checked:bg-white peer-checked:text-emerald-700 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400">Uang masuk</span>
                      </label>
                    </div>
                  </fieldset>

                  <div>
                    <label for="finAmount" class="block text-xs font-bold text-slate-700 mb-1.5">Nominal</label>
                    <div class="flex items-center h-14 rounded-xl border border-slate-300 bg-slate-50 focus-within:border-[#0b1e36] focus-within:ring-2 focus-within:ring-amber-400/60 px-4">
                      <span class="font-mono text-base font-bold text-slate-400 mr-2">Rp</span>
                      <input id="finAmount" name="amount" type="text" inputmode="numeric" autocomplete="off" required placeholder="0" class="flex-1 min-w-0 bg-transparent font-mono text-2xl font-bold text-slate-900 focus:outline-none">
                    </div>
                    <div class="flex gap-2 mt-2 overflow-x-auto fin-scroll">
                      <?php foreach ([50000, 100000, 150000, 200000, 300000, 500000] as $qa): ?>
                        <button type="button" data-fin-quick="<?= $qa ?>" class="shrink-0 h-9 px-3 rounded-full border border-slate-200 bg-white text-[11px] font-mono font-bold text-slate-600 hover:border-slate-400 active:scale-95 transition"><?= number_format($qa / 1000, 0) ?>rb</button>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <fieldset>
                    <legend class="block text-xs font-bold text-slate-700 mb-1.5">Kategori</legend>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                      <?php foreach ($finCategories as $i => $cat): ?>
                        <label class="cursor-pointer" data-fin-cat-for="<?= $cat[2] ?>">
                          <input type="radio" name="category" value="<?= htmlspecialchars($cat[0]) ?>" class="peer sr-only" <?= $i === 0 ? 'checked' : '' ?>>
                          <span class="h-11 px-3 flex items-center justify-center text-center rounded-xl border border-slate-200 text-xs font-bold text-slate-600 transition peer-checked:border-[#0b1e36] peer-checked:bg-[#0b1e36] peer-checked:text-amber-400 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400"><?= $cat[1] ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  </fieldset>

                  <div>
                    <label for="finTitle" class="block text-xs font-bold text-slate-700 mb-1.5">Keterangan</label>
                    <input id="finTitle" name="title" type="text" required maxlength="150" placeholder="Contoh: Sewa 3 lapangan x 2 jam" class="w-full h-12 rounded-xl border border-slate-300 bg-slate-50 px-4 text-base font-semibold text-slate-900 focus:outline-none focus:border-[#0b1e36] focus:ring-2 focus:ring-amber-400/60">
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label for="finDate" class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal</label>
                      <input id="finDate" name="record_date" type="date" value="<?= date('Y-m-d') ?>" class="w-full h-12 rounded-xl border border-slate-300 bg-slate-50 px-4 text-base font-semibold text-slate-900 focus:outline-none focus:border-[#0b1e36] focus:ring-2 focus:ring-amber-400/60">
                    </div>
                    <div>
                      <label for="finSession" class="block text-xs font-bold text-slate-700 mb-1.5">Sesi terkait</label>
                      <select id="finSession" name="session_id" data-no-search class="w-full h-12 rounded-xl border border-slate-300 bg-slate-50 px-3 text-base font-semibold text-slate-900 focus:outline-none focus:border-[#0b1e36] focus:ring-2 focus:ring-amber-400/60">
                        <option value="">Kas umum (tanpa sesi)</option>
                        <?php foreach ($allSchedules as $sch): ?>
                          <option value="<?= (int)$sch['id'] ?>"><?= htmlspecialchars($sch['venue_name']) ?> · <?= htmlspecialchars($finDateLabel($sch['schedule_date'], false)) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div>
                    <label for="finNotes" class="block text-xs font-bold text-slate-700 mb-1.5">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea id="finNotes" name="notes" rows="2" placeholder="No. transfer, nama kasir GOR, dll" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base text-slate-900 focus:outline-none focus:border-[#0b1e36] focus:ring-2 focus:ring-amber-400/60"></textarea>
                  </div>
                </div>

                <div class="shrink-0 px-5 pt-3 border-t border-slate-100 bg-white grid grid-cols-[auto_1fr] gap-2" style="padding-bottom: max(16px, env(safe-area-inset-bottom));">
                  <button type="button" data-fin-close class="h-12 px-5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold active:scale-95 transition">Batal</button>
                  <button type="submit" id="finSubmitBtn" class="h-12 rounded-xl bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 text-sm font-black uppercase tracking-wider active:scale-[0.98] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2">Simpan</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Sheet: Detail Transaksi -->
        <div id="finRecordSheet" class="hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-labelledby="finDetailTitle">
          <div class="absolute inset-0 bg-slate-950/60" data-fin-close></div>
          <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4 pointer-events-none">
            <div class="fin-sheet-panel pointer-events-auto bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl max-h-[92vh] overflow-y-auto">
              <div class="px-5 pt-2.5 pb-4">
                <div class="sm:hidden mx-auto mb-3 w-10 h-1 rounded-full bg-slate-300" aria-hidden="true"></div>
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p id="finDetailType" class="text-[11px] font-bold uppercase tracking-wider"></p>
                    <h3 id="finDetailTitle" class="text-base font-black text-slate-900 mt-0.5 break-words"></h3>
                  </div>
                  <button type="button" data-fin-close class="w-11 h-11 -mr-2 -mt-1 shrink-0 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-800 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
                <p id="finDetailAmount" class="font-mono text-3xl font-bold mt-3"></p>
                <dl class="mt-4 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                  <div class="py-2.5 flex justify-between gap-4"><dt class="text-slate-500">Tanggal</dt><dd id="finDetailDate" class="font-semibold text-slate-900 text-right"></dd></div>
                  <div class="py-2.5 flex justify-between gap-4"><dt class="text-slate-500">Kategori</dt><dd id="finDetailCat" class="font-semibold text-slate-900 text-right"></dd></div>
                  <div class="py-2.5 flex justify-between gap-4"><dt class="text-slate-500">Sesi</dt><dd id="finDetailSession" class="font-semibold text-slate-900 text-right"></dd></div>
                  <div class="py-2.5 flex justify-between gap-4" id="finDetailNotesRow"><dt class="text-slate-500">Catatan</dt><dd id="finDetailNotes" class="text-slate-700 text-right break-words"></dd></div>
                  <div class="py-2.5 flex justify-between gap-4" id="finDetailCreatorRow"><dt class="text-slate-500">Dicatat oleh</dt><dd id="finDetailCreator" class="text-slate-700 text-right"></dd></div>
                </dl>
              </div>
              <div class="px-5 pt-1 grid grid-cols-2 gap-2" style="padding-bottom: max(16px, env(safe-area-inset-bottom));">
                <form method="POST" action="" id="finDeleteForm" onsubmit="handleAdminFormConfirm(event, this, 'Hapus transaksi?', 'Catatan ini akan dihapus permanen dan saldo kas dihitung ulang.'); return false;">
                  <input type="hidden" name="form_action" value="delete_financial_record">
                  <input type="hidden" name="record_ref" id="finDeleteRef" value="">
                  <?= $finRetInputs ?>
                  <button type="submit" class="w-full h-12 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-sm font-bold active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400">Hapus</button>
                </form>
                <button type="button" id="finEditBtn" class="h-12 rounded-xl bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 text-sm font-black uppercase tracking-wider active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2">Ubah</button>
              </div>
            </div>
          </div>
        </div>

        <script>
          (function () {
            var PANELS = ['kas', 'sesi', 'piutang', 'analisis'];
            var lastFocus = null;
            var currentRecord = null;

            function showPanel(name) {
              if (PANELS.indexOf(name) === -1) name = 'kas';
              document.querySelectorAll('[data-fin-panel]').forEach(function (p) {
                p.classList.toggle('hidden', p.getAttribute('data-fin-panel') !== name);
              });
              document.querySelectorAll('[data-fin-tab]').forEach(function (b) {
                var on = b.getAttribute('data-fin-tab') === name;
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                b.classList.toggle('bg-white', on);
                b.classList.toggle('text-slate-900', on);
                b.classList.toggle('shadow-sm', on);
                b.classList.toggle('text-slate-500', !on);
              });
              document.querySelectorAll('.js-ret-panel').forEach(function (i) { i.value = name; });
              try { localStorage.setItem('finPanel', name); } catch (e) {}
              if (location.hash !== '#' + name) history.replaceState(null, '', location.pathname + location.search + '#' + name);
            }

            function openSheet(id) {
              lastFocus = document.activeElement;
              var el = document.getElementById(id);
              el.classList.remove('hidden');
              document.body.style.overflow = 'hidden';
              var first = el.querySelector('[data-fin-autofocus]') || el.querySelector('button[data-fin-close]');
              if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 50);
            }
            function closeSheets() {
              ['modalAddFinance', 'finRecordSheet'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.classList.add('hidden');
              });
              document.body.style.overflow = '';
              if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
            }

            var amountInput = document.getElementById('finAmount');
            function formatAmount(v) {
              var d = String(v).replace(/\D/g, '').replace(/^0+/, '');
              return d ? d.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            }
            amountInput.addEventListener('input', function () { this.value = formatAmount(this.value); });
            document.querySelectorAll('[data-fin-quick]').forEach(function (b) {
              b.addEventListener('click', function () { amountInput.value = formatAmount(b.getAttribute('data-fin-quick')); });
            });

            function syncCategories() {
              var type = document.querySelector('#finForm input[name="type"]:checked').value;
              var firstVisible = null, checkedVisible = false;
              document.querySelectorAll('[data-fin-cat-for]').forEach(function (l) {
                var f = l.getAttribute('data-fin-cat-for');
                var show = f === 'both' || f === type;
                l.classList.toggle('hidden', !show);
                var r = l.querySelector('input');
                if (show && !firstVisible) firstVisible = r;
                if (show && r.checked) checkedVisible = true;
              });
              if (!checkedVisible && firstVisible) firstVisible.checked = true;
            }
            document.querySelectorAll('#finForm input[name="type"]').forEach(function (r) { r.addEventListener('change', syncCategories); });

            window.openFinanceForm = function (rec) {
              var form = document.getElementById('finForm');
              form.reset();
              var isEdit = !!(rec && rec.ref);
              document.getElementById('finFormAction').value = isEdit ? 'update_financial_record' : 'create_financial_record';
              document.getElementById('finRecordRef').value = isEdit ? rec.ref : '';
              document.getElementById('finFormTitle').textContent = isEdit ? 'Ubah transaksi' : 'Catat transaksi';
              document.getElementById('finSubmitBtn').textContent = isEdit ? 'Simpan perubahan' : 'Simpan';
              document.getElementById('finDate').value = isEdit ? rec.record_date : '<?= date('Y-m-d') ?>';
              if (isEdit) {
                form.querySelector('input[name="type"][value="' + rec.type + '"]').checked = true;
                amountInput.value = formatAmount(rec.amount);
                document.getElementById('finTitle').value = rec.title;
                document.getElementById('finNotes').value = rec.notes || '';
                document.getElementById('finSession').value = rec.session_id ? String(rec.session_id) : '';
              } else {
                document.getElementById('finSession').value = '<?= $finSessionFilter > 0 ? (int)$finSessionFilter : '' ?>';
              }
              syncCategories();
              if (isEdit) {
                var cat = form.querySelector('input[name="category"][value="' + rec.category.replace(/"/g, '') + '"]');
                if (cat) cat.checked = true;
              }
              document.querySelectorAll('.js-ret-panel').forEach(function (i) { i.value = 'kas'; });
              closeSheets();
              openSheet('modalAddFinance');
              if (!isEdit) setTimeout(function () { amountInput.focus(); }, 120);
            };

            document.querySelectorAll('[data-fin-record]').forEach(function (btn) {
              btn.addEventListener('click', function () {
                var rec;
                try { rec = JSON.parse(btn.getAttribute('data-fin-record')); } catch (e) { return; }
                currentRecord = rec;
                var isExp = rec.type === 'expense';
                var typeEl = document.getElementById('finDetailType');
                typeEl.textContent = isExp ? 'Uang keluar' : 'Uang masuk';
                typeEl.className = 'text-[11px] font-bold uppercase tracking-wider ' + (isExp ? 'text-rose-600' : 'text-emerald-700');
                document.getElementById('finDetailTitle').textContent = rec.title;
                var amt = document.getElementById('finDetailAmount');
                amt.textContent = (isExp ? '−' : '+') + 'Rp ' + formatAmount(rec.amount);
                amt.className = 'font-mono text-3xl font-bold mt-3 ' + (isExp ? 'text-rose-600' : 'text-emerald-700');
                document.getElementById('finDetailDate').textContent = rec.date_label;
                document.getElementById('finDetailCat').textContent = rec.category;
                document.getElementById('finDetailSession').textContent = rec.session_label;
                document.getElementById('finDetailNotes').textContent = rec.notes;
                document.getElementById('finDetailNotesRow').classList.toggle('hidden', !rec.notes);
                document.getElementById('finDetailCreator').textContent = rec.creator;
                document.getElementById('finDetailCreatorRow').classList.toggle('hidden', !rec.creator);
                document.getElementById('finDeleteRef').value = rec.ref;
                openSheet('finRecordSheet');
              });
            });

            document.getElementById('finEditBtn').addEventListener('click', function () {
              if (currentRecord) window.openFinanceForm(currentRecord);
            });

            document.getElementById('finForm').addEventListener('submit', function (e) {
              var digits = amountInput.value.replace(/\D/g, '');
              if (!digits || parseInt(digits, 10) <= 0) {
                e.preventDefault();
                amountInput.focus();
                return;
              }
              var btn = document.getElementById('finSubmitBtn');
              btn.disabled = true;
              btn.textContent = 'Menyimpan…';
            });

            document.querySelectorAll('[data-fin-close]').forEach(function (el) { el.addEventListener('click', closeSheets); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheets(); });

            document.querySelectorAll('[data-fin-tab]').forEach(function (b) {
              b.addEventListener('click', function () { showPanel(b.getAttribute('data-fin-tab')); });
            });
            document.querySelectorAll('[data-fin-goto]').forEach(function (b) {
              b.addEventListener('click', function () {
                showPanel(b.getAttribute('data-fin-goto'));
                var tl = document.querySelector('[role="tablist"][aria-label="Bagian laporan"]');
                if (tl) tl.scrollIntoView({ behavior: 'smooth', block: 'start' });
              });
            });

            var initial = (location.hash || '').replace('#', '');
            if (PANELS.indexOf(initial) === -1) {
              try { initial = localStorage.getItem('finPanel') || 'kas'; } catch (e) { initial = 'kas'; }
            }
            showPanel(initial);
          })();
        </script>


      <!-- TAB 2: KELOLA GALERI FOTO -->
      <?php elseif ($tab === 'gallery'): ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          <!-- Add Photo Form -->
          <div class="lg:col-span-5 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
            <h3 class="text-base font-black text-slate-900 mb-1">Tambah Foto Dokumentasi Mabar</h3>
            <p class="text-xs text-slate-500 mb-4">Foto akan langsung ditampilkan di galeri depan Landing Page publik lengkap dengan tanggal & sesi mabar.</p>

            <form method="POST" action="?tab=gallery" enctype="multipart/form-data" class="space-y-3.5">
              <input type="hidden" name="form_action" value="add_gallery">

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Mabar:</label>
                  <input type="date" name="activity_date" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-700">
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Sesi Lapangan (GOR):</label>
                  <select name="session_id" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    <option value="">-- Umum / Semua Sesi --</option>
                    <?php foreach ($allSchedules as $sch): ?>
                      <option value="<?= $sch['id'] ?>"><?= htmlspecialchars($sch['venue_name']) ?> (<?= htmlspecialchars($sch['day_name']) ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Dokumentasi:</label>
                <input type="text" name="title" required placeholder="Contoh: Keseruan Game Final & Foto Bersama" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-base sm:text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-700 min-h-[44px]">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                  <span>Pilih File Foto Dokumentasi:</span>
                  <span class="text-[10px] text-slate-400 font-normal">Kamera / Album (Maks 10MB)</span>
                </label>
                <input type="file" name="photo_file" accept="image/*" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-700 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-[#0b1e36] file:text-amber-400 hover:file:bg-slate-900 cursor-pointer min-h-[44px]">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Atau Masukkan URL Gambar (Opsional jika foto di cloud):</label>
                <input type="url" name="image_url" placeholder="https://images.unsplash.com/..." class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2 text-base sm:text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-700 min-h-[44px]">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan Singkat (Caption):</label>
                <textarea name="caption" rows="2" placeholder="Keterangan suasana bermain, peserta, atau momen pertandingan..." class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2 text-base sm:text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-700"></textarea>
              </div>

              <button type="submit" class="w-full min-h-[46px] rounded-lg bg-[#0b1e36] hover:bg-slate-900 active:scale-[0.98] text-amber-400 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/30 shadow-xs flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 text-amber-400 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Unggah Foto ke Galeri</span>
              </button>
            </form>
          </div>

          <!-- Gallery List Grid -->
          <div class="lg:col-span-7 bg-white rounded-lg border border-slate-200 p-4 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-slate-900">Daftar Foto Dokumentasi (<?= count($gallery) ?>)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Dikelola oleh Admin & PIC Lapangan</p>
              </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <?php foreach ($gallery as $pic): 
                $fDate = !empty($pic['activity_date']) ? formatIndoDate($pic['activity_date']) : substr($pic['created_at'], 0, 10);
                $venueLabel = !empty($pic['venue_name']) ? $pic['venue_name'] : 'Semua Sesi';
              ?>
                <div class="border border-slate-200 rounded-lg overflow-hidden bg-slate-50 group flex flex-col justify-between shadow-xs">
                  <div class="aspect-video bg-slate-200 overflow-hidden relative">
                    <img src="<?= htmlspecialchars($pic['image_url']) ?>" alt="<?= htmlspecialchars($pic['title']) ?>" class="w-full h-full object-cover">
                    <div class="absolute top-2 left-2">
                      <span class="text-[10px] font-mono font-bold text-slate-900 bg-white/95 px-2 py-0.5 rounded-sm border border-slate-200 shadow-xs">
                        <?= htmlspecialchars($fDate) ?>
                      </span>
                    </div>
                  </div>
                  <div class="p-3.5 flex-1 flex flex-col justify-between">
                    <div>
                      <div class="text-[10px] font-mono font-bold text-blue-900 uppercase tracking-wider mb-1 truncate">
                        <?= htmlspecialchars($venueLabel) ?>
                      </div>
                      <h4 class="font-bold text-slate-900 text-xs truncate"><?= htmlspecialchars($pic['title']) ?></h4>
                      <p class="text-[11px] text-slate-500 line-clamp-2 mt-1"><?= htmlspecialchars(!empty($pic['caption']) ? $pic['caption'] : 'Dokumentasi foto mabar.') ?></p>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-slate-200 flex items-center justify-between text-xs">
                      <span class="font-mono text-slate-400 text-[10px]">#<?= $pic['id'] ?></span>
                      <form method="POST" action="?tab=gallery" onsubmit="handleAdminFormConfirm(event, this, 'Hapus Foto Dokumentasi?', 'Foto ini akan dihapus dari galeri dan landing page.'); return false;">
                        <input type="hidden" name="form_action" value="delete_gallery">
                        <input type="hidden" name="id" value="<?= $pic['id'] ?>">
                        <button type="submit" class="min-h-[36px] px-3 py-1 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white font-bold text-xs rounded border border-rose-200 transition-all flex items-center justify-center">
                          Hapus Foto
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>


      <!-- TAB 3: JADWAL SESI & LIST PESERTA WA -->
      <?php elseif ($tab === 'schedules'): ?>

        <?php if ($waSession): ?>
          <!-- WhatsApp Interactive Template Generator & Participant Roster Workspace -->
          <div id="waSection" class="bg-white rounded-lg border-2 border-emerald-500 p-6 sm:p-8 shadow-xl mb-10 transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-200 gap-4">
              <div>
                <span class="px-3 py-1 rounded-full bg-amber-100 text-blue-900 font-bold text-xs uppercase tracking-wider">
                  Generator Broadcast WhatsApp & List Peserta Sesi
                </span>
                <h2 class="text-2xl font-black text-slate-950 font-sans font-bold mt-1.5">
                  <?= htmlspecialchars($waSession['venue_name']) ?> (<?= htmlspecialchars($waSession['day_name']) ?>)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                  Kuota Terisi: <strong class="text-blue-700"><?= count($waParticipants) ?> / <?= $waSession['max_slots'] ?> Peserta</strong> • PIC: <?= htmlspecialchars($waSession['pic_whatsapp']) ?>
                </p>
              </div>

              <div class="flex items-center gap-2">
                <a href="?tab=schedules" class="px-4 py-2 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition-all">
                  &times; Tutup Workspace WA
                </a>
              </div>
            </div>

            <?php
              $sSlots = (int)$waSession['max_slots'];
              $tStart = substr($waSession['time_start'], 0, 5);
              $tEnd = substr($waSession['time_end'], 0, 5);
              $timeDisplay = "{$tStart} - {$tEnd}";
              $feeK = (int)($waSession['fee_per_person'] / 1000);
              $feeDisplay = "{$feeK}K/Person";
              $mapsLink = !empty($waSession['venue_maps_url']) ? $waSession['venue_maps_url'] : 'https://maps.app.goo.gl/...';
              $branchU = strtoupper(!empty($waSession['branch_area']) ? $waSession['branch_area'] : (!empty($settings['base_city']) ? $settings['base_city'] : 'KOMUNITAS'));
              $courtRange = !empty($waSession['court_desc']) ? $waSession['court_desc'] : "Lapangan 1 - {$waSession['court_count']}";
              $cancelRule = !empty($waSession['cancellation_rules']) ? $waSession['cancellation_rules'] : 'Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.';
              $bank = !empty($waSession['bank_info']) ? $waSession['bank_info'] : 'BCA 1234567890 a.n Bendahara';
              $konfirmasi = "Admin PIC " . (!empty($waSession['pic_whatsapp']) ? $waSession['pic_whatsapp'] : '081234567890');
              $host = !empty($waSession['host_name']) ? $waSession['host_name'] : 'PIC Lapangan';

              // Prepare JSON array of participants for real-time live preview
              $participantNamesOnly = array_map(function($p) { return $p['name']; }, $waParticipants);
              $participantsJson = json_encode($participantNamesOnly);
            ?>

            <!-- 2-Column Responsive Workspace: Interactive Inputs on Left, Live WA Preview on Right -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mt-6">
              
              <!-- Left Column: Interactive Inputs for ALL Template Parameters -->
              <div class="lg:col-span-6 space-y-6">
                
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
                  <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-black uppercase tracking-wider text-blue-900 font-mono">
                      Pengaturan Parameter Pesan WhatsApp
                    </h3>
                    <span class="text-[11px] text-slate-400">Live Generator</span>
                  </div>
                  <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">
                    Ubah lapangan (misal: <em>Lapangan 1 - 3</em>), jam, iuran, maps, rekening BCA, atau kontak konfirmasi di bawah. Teks template di sebelah kanan akan berubah seketika!
                  </p>

                  <form method="POST" action="?tab=schedules&wa=<?= Security::hashId($waSessionId) ?>#waSection" class="space-y-3.5">
                    <input type="hidden" name="form_action" value="update_schedule_wa_params">
                    <input type="hidden" name="session_id" value="<?= $waSessionId ?>">

                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Wilayah / Judul Sesi:</label>
                        <input type="text" id="tplBranch" name="custom_branch" value="<?= htmlspecialchars($branchU) ?>" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900 font-bold" oninput="updateLiveWaTemplate()">
                      </div>
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Hari & Sesi:</label>
                        <input type="text" id="tplDay" name="custom_day" value="<?= htmlspecialchars($waSession['day_name']) ?>" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900" oninput="updateLiveWaTemplate()">
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                          Pilihan Lapangan (Contoh: Court 1 & 2):
                        </label>
                        <input type="text" id="tplCourt" name="custom_court" value="<?= htmlspecialchars($courtRange) ?>" placeholder="Contoh: Lapangan 1 - 2 / Court A & B" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs font-bold text-blue-900" oninput="updateLiveWaTemplate()">
                      </div>
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                          ⏰ Jam Berapa (Waktu Main):
                        </label>
                        <input type="text" id="tplTime" name="custom_time" value="<?= htmlspecialchars($timeDisplay) ?>" placeholder="Contoh: 19.00 - 22.00 WIB" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs font-bold text-slate-900" oninput="updateLiveWaTemplate()">
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                          Biaya Iuran per Orang (Rp):
                        </label>
                        <input type="text" id="tplFee" name="custom_fee" value="<?= htmlspecialchars($feeDisplay) ?>" placeholder="Contoh: 35.000 / orang" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs font-bold text-blue-700" oninput="updateLiveWaTemplate()">
                      </div>
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                          Maksimal Kuota Peserta:
                        </label>
                        <input type="number" id="tplMaxSlots" name="custom_max_slots" value="<?= $sSlots ?>" min="4" max="60" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs font-bold text-slate-900" oninput="updateLiveWaTemplate()">
                      </div>
                    </div>

                    <div>
                      <label class="block text-[11px] font-bold text-slate-700 mb-1">Nama Tempat / GOR:</label>
                      <input type="text" id="tplVenue" name="custom_venue" value="<?= htmlspecialchars($waSession['venue_name']) ?>" placeholder="Nama GOR venue mabar..." class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900 font-bold" oninput="updateLiveWaTemplate()">
                    </div>

                    <div>
                      <label class="block text-[11px] font-bold text-slate-700 mb-1">Link Google Maps:</label>
                      <input type="url" id="tplMaps" name="custom_maps" value="<?= htmlspecialchars($mapsLink) ?>" placeholder="https://maps.app.goo.gl/... (tautan peta)" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900 font-mono" oninput="updateLiveWaTemplate()">
                    </div>

                    <div>
                      <label class="block text-[11px] font-bold text-slate-700 mb-1">Info Rekening Pembayaran (Payment):</label>
                      <input type="text" id="tplBank" name="custom_bank" value="<?= htmlspecialchars($bank) ?>" placeholder="Contoh: BCA 1234567890 a.n Bendahara" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900" oninput="updateLiveWaTemplate()">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Kontak Konfirmasi (WhatsApp):</label>
                        <input type="text" id="tplKonfirmasi" name="custom_pic_whatsapp" value="<?= htmlspecialchars($konfirmasi) ?>" placeholder="Contoh: Admin PIC 081234567890" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900 font-mono" oninput="updateLiveWaTemplate()">
                      </div>
                      <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nama Host di WA:</label>
                        <input type="text" id="tplHost" name="custom_host" value="<?= htmlspecialchars($host) ?>" placeholder="Contoh: PIC Lapangan" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900" oninput="updateLiveWaTemplate()">
                      </div>
                    </div>

                    <div>
                      <label class="block text-[11px] font-bold text-slate-700 mb-1">Aturan Cancel Sesi:</label>
                      <input type="text" id="tplCancel" name="custom_cancel_rules" value="<?= htmlspecialchars($cancelRule) ?>" placeholder="Contoh: Batas pembatalan slot maks H-1 atau cari pemain pengganti." class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-900" oninput="updateLiveWaTemplate()">
                    </div>

                    <div class="pt-2 flex items-center gap-3">
                      <button type="submit" class="px-4 py-2.5 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition-all btn-tactile shadow-sm">
                        Simpan Pengaturan ke Database Sesi
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Quick Paste WA List Form -->
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
                  <h3 class="text-sm font-black text-slate-900 font-sans font-bold uppercase tracking-wider mb-1">
                    Input / Paste List Peserta dari WhatsApp
                  </h3>
                  <p class="text-xs text-slate-500 mb-3">
                    Copy list nama dari chat WhatsApp grup, lalu paste ke kotak di bawah. Sistem otomatis mengabaikan tanda titik dan menambah nama ke kuota sesi mabar ini!
                  </p>

                  <form method="POST" action="?tab=schedules&wa=<?= Security::hashId($waSessionId) ?>#waSection" class="space-y-3">
                    <input type="hidden" name="form_action" value="import_wa_list">
                    <input type="hidden" name="session_id" value="<?= $waSessionId ?>">

                    <textarea name="raw_wa_text" rows="4" required placeholder="Contoh format paste dari WhatsApp:&#10;1. Budi&#10;2. Andi&#10;3. .&#10;4. .&#10;8. Dodi&#10;16. Hendra" class="w-full bg-white border border-slate-300 rounded-md p-3 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>

                    <button type="submit" class="w-full py-2.5 rounded-md bg-emerald-700 hover:bg-amber-400 text-slate-950 font-black font-bold text-xs uppercase tracking-wider transition-all btn-tactile shadow-sm">
                      Import Nama ke Sesi Mabar Ini →
                    </button>
                  </form>
                </div>

              </div>

              <!-- Right Column: Live Generated Pre-Formatted WA Template & Enrolled Table -->
              <div class="lg:col-span-6 space-y-6">
                
                <!-- Live WhatsApp Text Display Card -->
                <div class="bg-slate-950 text-emerald-300 rounded-lg p-6 flex flex-col justify-between font-mono text-xs shadow-2xl border border-slate-800">
                  <div>
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 text-slate-400">
                      <span class="font-bold text-white uppercase tracking-wider text-xs">Live Generated WhatsApp Template</span>
                      <span class="text-[10px] bg-emerald-950 text-amber-400 px-2 py-0.5 rounded border border-emerald-800">Real-Time Update</span>
                    </div>
                    <pre id="waLivePreview" class="whitespace-pre-wrap leading-relaxed text-slate-100 select-all max-h-[460px] overflow-y-auto"></pre>
                  </div>

                  <div class="mt-4 pt-3 border-t border-slate-800">
                    <button onclick="copyWaTemplate()" id="copyBtn" class="w-full py-3.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-lg flex items-center justify-center gap-2">
                      <span>Salin Format Pesan WhatsApp</span>
                    </button>
                  </div>
                </div>

                <!-- Enrolled Participants Table for this Session -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-sm">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                      Daftar Peserta Terdaftar (<?= count($waParticipants) ?>)
                    </h4>
                    <span class="text-[11px] font-mono text-slate-500 font-bold" id="enrolledSlotsStatus">
                      Sisa: <?= max(0, $sSlots - count($waParticipants)) ?> Slot
                    </span>
                  </div>

                  <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 text-xs">
                    <?php if (empty($waParticipants)): ?>
                      <p class="text-slate-400 py-4 text-center italic text-xs">Belum ada peserta yang dimasukkan. Silakan paste list WA di sebelah kiri.</p>
                    <?php else: ?>
                      <?php foreach ($waParticipants as $idx => $wp): ?>
                        <div class="py-2 flex items-center justify-between gap-2">
                          <div class="flex items-center gap-2">
                            <span class="w-5 text-slate-400 font-mono text-[11px] font-bold"><?= $idx + 1 ?>.</span>
                            <span class="font-bold text-slate-900"><?= htmlspecialchars($wp['name']) ?></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full <?= $wp['payment_status'] === 'paid' ? 'bg-amber-100 text-blue-900' : 'bg-amber-100 text-amber-800' ?>">
                              <?= $wp['payment_status'] === 'paid' ? 'LUNAS' : 'BELUM' ?>
                            </span>
                          </div>
                          <form method="POST" action="?tab=schedules&wa=<?= Security::hashId($waSessionId) ?>#waSection" onsubmit="handleAdminFormConfirm(event, this, 'Keluarkan Pemain?', 'Pemain ini akan dikeluarkan dari sesi ini.'); return false;">
                            <input type="hidden" name="form_action" value="remove_participant">
                            <input type="hidden" name="session_id" value="<?= $waSessionId ?>">
                            <input type="hidden" name="player_id" value="<?= $wp['player_id'] ?>">
                            <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold text-xs p-1">&times;</button>
                          </form>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>

              </div>

            </div>
          </div>

          <script>
            // Live WhatsApp Template Reactive Updater
            const enrolledParticipants = <?= $participantsJson ?>;

            function updateLiveWaTemplate() {
              const branch = (document.getElementById('tplBranch')?.value || <?= json_encode(!empty($settings['base_city']) ? strtoupper($settings['base_city']) : 'KOMUNITAS') ?>).toUpperCase();
              const day = document.getElementById('tplDay')?.value || 'Jadwal Rutin Mabar';
              const time = document.getElementById('tplTime')?.value || '19.00 - 22.00 WIB';
              const venue = document.getElementById('tplVenue')?.value || 'GOR Badminton Arena';
              const maps = document.getElementById('tplMaps')?.value || 'https://maps.app.goo.gl/...';
              const court = document.getElementById('tplCourt')?.value || 'Lapangan 1 - 2';
              const fee = document.getElementById('tplFee')?.value || '35K / Orang';
              const cancel = document.getElementById('tplCancel')?.value || 'Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.';
              const bank = document.getElementById('tplBank')?.value || 'BCA 1234567890 a.n Bendahara';
              const konfirmasi = document.getElementById('tplKonfirmasi')?.value || 'Admin PIC 081234567890';
              const host = document.getElementById('tplHost')?.value || 'PIC Lapangan';
              const maxSlots = parseInt(document.getElementById('tplMaxSlots')?.value || '16', 10);

              // Build Numbered List based on enrolled participants & max slots
              let listLines = [];
              for (let i = 1; i <= maxSlots; i++) {
                const name = enrolledParticipants[i - 1];
                if (name) {
                  listLines.push(`${i}. ${name}`);
                } else {
                  listLines.push(`${i}. .`);
                }
              }

              const isFull = enrolledParticipants.length >= maxSlots;
              const fullTag = isFull ? "\n*‼️FULL‼️*" : "";

              const fullTemplate = 
`*<?= strtoupper(htmlspecialchars(!empty($settings['community_name']) ? $settings['community_name'] : 'KOMUNITAS BADMINTON')) ?> SESSION ${branch}*

Yuk mabar! Slot terbatas, segera amankan tempatmu.
Detail:
Hari/Tgl: ${day}
⏰ ${time} WIB
Lokasi: ${venue}
(${maps})
Lapangan: ${court}
Iuran: ${fee}
*‼️ ${cancel} ‼️*

_Follow instagram resmi kami_
<?= !empty($settings['instagram']) ? 'https://www.instagram.com/' . ltrim($settings['instagram'], '@') : 'https://instagram.com/komunitas' ?>

Payment :: ${bank}
Konfirmasi :: ${konfirmasi}
*Host: ${host}*

*List Nama : MAX ${maxSlots} ORANG*
${listLines.join('\n')}${fullTag}`;

              const previewElem = document.getElementById('waLivePreview');
              if (previewElem) {
                previewElem.innerText = fullTemplate;
              }

              const statusElem = document.getElementById('enrolledSlotsStatus');
              if (statusElem) {
                const left = Math.max(0, maxSlots - enrolledParticipants.length);
                statusElem.innerText = `Sisa: ${left} Slot (${enrolledParticipants.length}/${maxSlots})`;
              }
            }

            // Run initial render on load
            document.addEventListener('DOMContentLoaded', () => {
              updateLiveWaTemplate();
            });
            updateLiveWaTemplate();
          </script>
        <?php endif; ?>


        <?php
          $schFilterStatus = isset($_GET['sch_status']) ? $_GET['sch_status'] : 'active';
          $activeSchedules = array_filter($allSchedules, function($x) { return $x['status'] !== 'completed'; });
          $completedSchedules = array_filter($allSchedules, function($x) { return $x['status'] === 'completed'; });
          $displayedSchedules = ($schFilterStatus === 'completed') ? $completedSchedules : $activeSchedules;
        ?>

        <!-- Header & Action Bar for Schedules List -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
          <div>
            <h2 class="text-2xl font-black text-slate-950 font-sans">
              Jadwal Sesi Mabar Komunitas
            </h2>
            <p class="text-xs text-slate-500 mt-1">
              Kelola jadwal cabang, kuota pemain, broadcast format WhatsApp, dan arsip riwayat mabar selesai.
            </p>
          </div>
          <a href="?tab=schedule_form" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-emerald-700 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-lg shadow-emerald-700/20 active:scale-95 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>+ Buat Jadwal Sesi Baru</span>
          </a>
        </div>

        <!-- Filter Sub-Tabs: Sesi Aktif vs Riwayat Selesai -->
        <div class="flex items-center gap-2 mb-6 border-b border-slate-200 pb-3 flex-wrap">
          <a href="?tab=schedules&sch_status=active" class="px-4 py-2 rounded-md font-bold text-xs transition-colors <?= $schFilterStatus !== 'completed' ? 'bg-[#0b1e36] text-amber-400 font-black shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Sesi Aktif & Mendatang (<?= count($activeSchedules) ?>)
          </a>
          <a href="?tab=schedules&sch_status=completed" class="px-4 py-2 rounded-md font-bold text-xs transition-colors <?= $schFilterStatus === 'completed' ? 'bg-[#0b1e36] text-amber-400 font-black shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Riwayat Mabar Selesai / Arsip (<?= count($completedSchedules) ?>)
          </a>
        </div>

        <!-- Schedule List Grid (Full Width & Mobile Responsive) -->
        <?php if (empty($displayedSchedules)): ?>
          <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
            <h3 class="text-base font-bold text-slate-900 mb-1">
              <?= $schFilterStatus === 'completed' ? 'Belum Ada Riwayat Sesi Mabar Selesai' : 'Belum Ada Jadwal Sesi Mabar Aktif' ?>
            </h3>
            <p class="text-xs text-slate-500 mb-5 max-w-sm mx-auto">
              <?= $schFilterStatus === 'completed' ? 'Sesi yang diflag selesai oleh PIC Lapangan akan otomatis diarsipkan di halaman ini.' : 'Mulai tambahkan jadwal cabang sesi mabar mingguan untuk komunitas Anda.' ?>
            </p>
            <?php if ($schFilterStatus !== 'completed'): ?>
              <a href="?tab=schedule_form" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md bg-emerald-700 hover:bg-amber-400 text-slate-950 font-black font-bold text-xs uppercase tracking-wider transition-all">
                + Buat Jadwal Baru Sekarang
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($displayedSchedules as $s): 
              $sStart = isset($s['time_start']) ? $s['time_start'] : '20:00:00';
              $sEnd = isset($s['time_end']) ? $s['time_end'] : '23:00:00';
              $sSlots = isset($s['max_slots']) ? (int)$s['max_slots'] : 16;
              $sAddr = !empty($s['venue_address']) ? $s['venue_address'] : (!empty($s['branch_area']) ? $s['branch_area'] : 'Jakarta');
              $pCount = isset($s['participant_count']) ? (int)$s['participant_count'] : 0;
              $pct = min(100, round(($pCount / max(1, $sSlots)) * 100));
            ?>
              <div class="bg-white border-2 <?= $s['status'] === 'completed' ? 'border-slate-300 opacity-95' : 'border-slate-200/90 hover:border-amber-400' ?> rounded-lg p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="px-3 py-1 rounded-full bg-slate-950 text-white font-black text-[11px] uppercase tracking-wide">
                        <?= htmlspecialchars($s['day_name']) ?>
                      </span>
                      <span class="text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">
                        <?= htmlspecialchars($s['branch_area']) ?>
                      </span>
                      <?php if ($s['status'] === 'completed'): ?>
                        <span class="text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-300 px-2 py-0.5 rounded-full">
                          Selesai<?= !empty($s['completed_at']) ? ' • ' . date('d/m', strtotime($s['completed_at'])) : '' ?>
                        </span>
                      <?php endif; ?>
                    </div>
                    <span class="text-xs font-black text-[#0b1e36] font-mono">
                      Rp <?= number_format($s['fee_per_person'], 0, ',', '.') ?>
                    </span>
                  </div>

                  <h4 class="font-black text-slate-950 text-lg leading-snug"><?= htmlspecialchars($s['venue_name']) ?></h4>
                  <p class="text-xs text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($sAddr) ?></p>

                  <!-- Quota Bar -->
                  <div class="mt-4 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                      <span class="text-slate-600 font-medium">Slot Terisi:</span>
                      <span class="font-bold <?= $pCount >= $sSlots ? 'text-rose-700' : 'text-blue-700' ?>">
                        <?= $pCount ?> / <?= $sSlots ?> Peserta (<?= $pct ?>%)
                      </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                      <div class="h-2 rounded-full <?= $pCount >= $sSlots ? 'bg-rose-600' : 'bg-emerald-600' ?>" style="width: <?= $pct ?>%"></div>
                    </div>
                  </div>

                  <div class="mt-4 pt-2 text-xs text-slate-600 space-y-1.5">
                    <div class="flex items-center justify-between">
                      <span class="text-slate-500">Tanggal Sesi:</span>
                      <strong class="font-mono text-slate-900"><?= date('d M Y', strtotime($s['schedule_date'])) ?></strong>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-slate-500">Waktu Main:</span>
                      <strong><?= substr($sStart, 0, 5) ?> - <?= substr($sEnd, 0, 5) ?> WIB</strong>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-slate-500">Jumlah Court:</span>
                      <strong><?= $s['court_count'] ?> Lapangan</strong>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-slate-500">WhatsApp PIC:</span>
                      <strong class="font-mono text-slate-900"><?= htmlspecialchars($s['pic_whatsapp']) ?></strong>
                    </div>
                  </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col gap-2.5">
                  
                  <!-- Primary Action: Broadcast WhatsApp & Edit -->
                  <div class="flex items-center gap-2">
                    <a href="?tab=schedules&wa=<?= Security::hashId((int)$s['id']) ?>#waSection" class="flex-1 py-2 px-3 text-center rounded-md bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 border border-amber-400/30 font-black text-xs uppercase tracking-wider transition-all shadow-xs flex items-center justify-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.539 1.777.781 2.79.781 3.182 0 5.768-2.587 5.768-5.766 0-3.18-2.586-5.767-5.768-5.767z"/></svg>
                      <span>Template WA</span>
                    </a>
                    <a href="?tab=schedule_form&edit=<?= Security::hashId((int)$s['id']) ?>" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition-colors border border-slate-200 flex items-center gap-1" title="Edit Jadwal">
                      <svg class="w-3.5 h-3.5 text-current inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                      <span>Edit</span>
                    </a>
                  </div>

                  <!-- 1-Click Generator: Jadwalkan Sesi Pekan Depan (+7 Hari) -->
                  <form method="POST" action="?tab=schedules" class="w-full">
                    <input type="hidden" name="form_action" value="generate_next_session">
                    <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="w-full py-2 px-3 rounded-md bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-xs uppercase tracking-wider transition-all border border-amber-300 flex items-center justify-center gap-1.5 shadow-xs">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                      <span>+ Buka Sesi Pekan Depan (+7 Hari)</span>
                    </button>
                  </form>

                  <!-- Status Selector & Delete Button -->
                  <div class="flex items-center justify-between gap-2 pt-2 border-t border-dashed border-slate-200">
                    <div class="flex items-center gap-1.5">
                      <span class="text-slate-400 text-[11px] font-semibold">Status:</span>
                      <form method="POST" action="?tab=schedules" class="inline">
                        <input type="hidden" name="form_action" value="update_schedule_status">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <select name="status" data-no-search onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 rounded px-2 py-1 text-xs font-bold text-slate-800 cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-700">
                          <option value="open" <?= $s['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                          <option value="in_progress" <?= $s['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                          <option value="completed" <?= $s['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                          <option value="cancelled" <?= $s['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                      </form>
                    </div>

                    <form method="POST" action="?tab=schedules" onsubmit="handleAdminFormConfirm(event, this, 'Hapus Jadwal Sesi?', 'Jadwal mabar ini akan dihapus permanen.'); return false;" class="inline">
                      <input type="hidden" name="form_action" value="delete_schedule">
                      <input type="hidden" name="id" value="<?= $s['id'] ?>">
                      <button type="submit" class="text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded text-xs font-bold px-2 py-1 transition-colors">
                        Hapus Sesi
                      </button>
                    </form>
                  </div>

                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>


      <!-- TAB: SCHEDULE FORM (HALAMAN KHUSUS TAMBAH / EDIT JADWAL) -->
      <?php elseif ($tab === 'schedule_form'): ?>

        <div class="max-w-3xl mx-auto space-y-6">
          
          <!-- Back Link & Header -->
          <div class="flex items-center justify-between gap-4">
            <a href="?tab=schedules" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-white border border-slate-200 text-slate-700 hover:text-blue-700 hover:border-amber-300 font-bold text-xs transition-all shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              <span>← Kembali ke Daftar Jadwal</span>
            </a>
            <span class="text-xs font-bold text-slate-500">
              <?= $editSchedule ? 'Mode Edit Sesi' : 'Tambah Sesi Baru' ?>
            </span>
          </div>

          <!-- Form Container Card -->
          <div class="bg-white rounded-lg border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="mb-5 pb-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="text-2xl font-black text-slate-950 font-sans">
                  <?= $editSchedule ? 'Edit Jadwal & Kuota Sesi' : 'Buat Jadwal Cabang Sesi Baru' ?>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                  Gunakan pencarian Google Maps untuk auto-fill nama GOR & alamat lengkap secara instan.
                </p>
              </div>
              <?php if ($editSchedule): ?>
                <span class="px-3 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-full text-xs font-bold">
                  Editing: #<?= $editSchedule['id'] ?>
                </span>
              <?php endif; ?>
            </div>

            <!-- Google Maps GOR Search & Autocomplete Widget -->
            <div class="mb-6 bg-slate-50 border border-slate-200 rounded-lg p-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                <label class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                  <span class="w-6 h-6 rounded bg-slate-800 text-white flex items-center justify-center text-xs">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </span>
                  <span>Cari Nama GOR (Google Maps Auto-Fill):</span>
                </label>
                
                <div class="flex items-center gap-2">
                  <button type="button" id="openInGmapsBtn" class="text-[11px] font-mono font-bold text-slate-700 hover:text-blue-900 bg-white border border-slate-300 px-2.5 py-1 rounded transition-colors flex items-center gap-1 shadow-xs">
                    <span>Buka Google Maps</span>
                    <span class="text-xs">&nearr;</span>
                  </button>
                  <button type="button" id="gmapsApiKeyToggle" class="text-[11px] font-mono font-bold text-slate-700 hover:text-slate-900 bg-white border border-slate-300 px-2.5 py-1 rounded transition-colors flex items-center gap-1 shadow-xs">
                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    <span>API Key</span>
                  </button>
                </div>
              </div>

              <!-- Optional Google Maps API Key Input Bar -->
              <div id="gmapsApiKeyBox" class="hidden mb-3 p-3 bg-white border border-slate-200 rounded-md text-xs space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-800">Kunci Google Maps Places API (Opsional):</span>
                  <span id="gmapsKeyStatus" class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600">Belum Disimpan</span>
                </div>
                <div class="flex gap-2">
                  <input type="text" id="gmapsApiKeyInput" placeholder="Masukkan Google Maps API Key (AIzaSy...)..." class="flex-1 bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-xs font-mono text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                  <button type="button" id="saveGmapsApiKeyBtn" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded transition-colors">
                    Simpan Key
                  </button>
                </div>
                <p class="text-[11px] text-slate-500">
                  Jika diisi, pencarian langsung menggunakan Google Places Autocomplete resmi Google Maps. Jika dikosongkan, pencarian menggunakan database GOR terkurasi dan geocoding instan yang otomatis men-generate link Google Maps.
                </p>
              </div>

              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" id="venueSearch" placeholder="Ketik nama GOR atau kawasan untuk auto-fill alamat & maps (opsional, bisa ketik manual di bawah)..." autocomplete="off" class="w-full bg-white border border-slate-300 focus:border-slate-800 rounded-md pl-10 pr-10 py-2.5 text-xs text-slate-900 font-semibold focus:outline-none focus:ring-1 focus:ring-slate-800 shadow-xs transition-all">
                
                <!-- Clear Button & Loader -->
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-1.5">
                  <div id="venueSearchSpinner" class="hidden animate-spin w-3.5 h-3.5 border-2 border-slate-800 border-t-transparent rounded-full"></div>
                  <button type="button" id="venueClearBtn" class="hidden text-slate-400 hover:text-slate-700 text-xs font-bold w-5 h-5 flex items-center justify-center rounded hover:bg-slate-100 transition-colors">&times;</button>
                </div>
                
                <!-- Autocomplete Dropdown (Google Style) -->
                <div id="venueResults" class="absolute z-50 left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-md shadow-xl overflow-hidden hidden max-h-72 overflow-y-auto divide-y divide-slate-100"></div>
              </div>
              
              <div id="venueSelectedFeedback" class="hidden mt-2 p-2.5 bg-emerald-50 border border-blue-200 rounded-md text-[11px] text-blue-950 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span id="venueSelectedFeedbackText"></span>
              </div>
              
              <div class="text-[11px] text-slate-500 mt-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Ketik nama GOR atau kawasan lalu klik hasil pada daftar untuk mengisi otomatis form nama, alamat lengkap, wilayah & tautan Google Maps.</span>
              </div>
            </div>

            <!-- Form -->
            <form method="POST" action="?tab=schedules" class="space-y-4">
              <input type="hidden" name="form_action" value="<?= $editSchedule ? 'update_schedule' : 'create_schedule' ?>">
              <?php if ($editSchedule): ?>
                <input type="hidden" name="schedule_id" value="<?= $editSchedule['id'] ?>">
              <?php endif; ?>

              <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <!-- Tanggal Sesi Input -->
                <div class="sm:col-span-4">
                  <label class="block text-xs font-bold text-slate-800 mb-1 flex items-center justify-between">
                    <span>Tanggal Sesi: <span class="text-red-500">*</span></span>
                    <span class="text-[10px] text-emerald-700 font-mono font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Auto-Hari ⚡</span>
                  </label>
                  <input type="date" id="formScheduleDate" name="schedule_date" required 
                         value="<?= $editSchedule ? htmlspecialchars($editSchedule['schedule_date']) : date('Y-m-d', strtotime('+1 day')) ?>" 
                         class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none shadow-xs">
                  <span id="dateFormattedHint" class="text-[10px] text-slate-500 mt-1 block truncate font-mono"></span>
                </div>

                <!-- Hari & Sesi (Auto-generated from date) -->
                <div class="sm:col-span-4">
                  <label class="block text-xs font-bold text-slate-800 mb-1 flex items-center justify-between">
                    <span>Hari & Sesi: <span class="text-red-500">*</span></span>
                    <span class="text-[10px] text-slate-400">Otomatis Terisi</span>
                  </label>
                  <input type="text" id="formDayName" name="day_name" required 
                         value="<?= $editSchedule ? htmlspecialchars($editSchedule['day_name']) : '' ?>" 
                         placeholder="Otomatis terisi saat pilih tanggal..." 
                         class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none shadow-xs">
                </div>

                <!-- Wilayah / Cabang -->
                <div class="sm:col-span-4">
                  <label class="block text-xs font-bold text-slate-800 mb-1">Wilayah / Cabang: <span class="text-red-500">*</span></label>
                  <input type="text" id="formBranchArea" name="branch_area" required 
                         value="<?= $editSchedule ? htmlspecialchars($editSchedule['branch_area']) : (!empty($settings['base_city']) ? htmlspecialchars($settings['base_city']) : '') ?>" 
                         placeholder="Contoh: <?= htmlspecialchars(!empty($settings['base_city']) ? $settings['base_city'] : 'Jakarta Selatan') ?>" 
                         class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none shadow-xs">
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Nama Tempat / GOR: <span class="text-red-500">*</span></label>
                <input type="text" id="formVenueName" name="venue_name" required value="<?= $editSchedule ? htmlspecialchars($editSchedule['venue_name']) : '' ?>" placeholder="Ketik nama GOR (misal: GOR Bulungan, Cempaka Arena, dsb)" class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none shadow-xs">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Alamat Lengkap Venue (GOR): <span class="text-red-500">*</span></label>
                <input type="text" id="formVenueAddress" name="venue_address" required value="<?= $editSchedule ? htmlspecialchars(!empty($editSchedule['venue_address']) ? $editSchedule['venue_address'] : $editSchedule['branch_area']) : '' ?>" placeholder="Alamat jalan lengkap venue GOR (nama jalan, nomor, kecamatan)..." class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none shadow-xs">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Link Google Maps (Opsional):</label>
                <input type="url" id="formMapsUrl" name="maps_url" value="<?= $editSchedule ? htmlspecialchars($editSchedule['venue_maps_url']) : '' ?>" placeholder="https://maps.app.goo.gl/... (otomatis terisi jika pilih GOR di atas)" class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 font-mono focus:outline-none shadow-xs">
              </div>

              <?php
                $valStart = $editSchedule ? substr($editSchedule['time_start'], 0, 5) : '19:00';
                $valStartHour = substr($valStart, 0, 2);
                $valStartMin = substr($valStart, 3, 2);

                $valEnd = $editSchedule ? substr($editSchedule['time_end'], 0, 5) : '22:00';
                $valEndHour = substr($valEnd, 0, 2);
                $valEndMin = substr($valEnd, 3, 2);
              ?>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-800">Jam Mulai: <span class="text-red-500">*</span></label>
                    <span class="text-[10px] font-mono text-amber-700 font-semibold bg-amber-50 px-1 rounded">24 Jam</span>
                  </div>
                  <div class="flex items-center gap-1 bg-white border border-slate-300 focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 rounded-md px-2 py-1.5 shadow-xs">
                    <select id="selectStartHour" class="bg-transparent text-xs font-mono font-bold text-slate-900 focus:outline-none cursor-pointer py-0.5 rounded w-full" onchange="syncTimeStart()">
                      <?php for ($h = 0; $h <= 23; $h++): 
                        $hStr = sprintf('%02d', $h);
                      ?>
                        <option value="<?= $hStr ?>" <?= $valStartHour === $hStr ? 'selected' : '' ?>><?= $hStr ?></option>
                      <?php endfor; ?>
                    </select>
                    <span class="text-xs font-black text-slate-400 select-none">:</span>
                    <select id="selectStartMin" class="bg-transparent text-xs font-mono font-bold text-slate-900 focus:outline-none cursor-pointer py-0.5 rounded w-full" onchange="syncTimeStart()">
                      <?php for ($m = 0; $m <= 59; $m++): 
                        $mStr = sprintf('%02d', $m);
                      ?>
                        <option value="<?= $mStr ?>" <?= $valStartMin === $mStr ? 'selected' : '' ?>><?= $mStr ?></option>
                      <?php endfor; ?>
                    </select>
                    <span class="text-[10px] font-mono text-slate-400 font-bold ml-1 select-none">WIB</span>
                  </div>
                  <input type="hidden" name="time_start" id="inputTimeStart" value="<?= htmlspecialchars($valStart) ?>" required>
                  
                  <!-- Quick Preset Pills -->
                  <div class="flex flex-wrap items-center gap-1 mt-1.5">
                    <button type="button" onclick="setTime('start', '07', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">07:00</button>
                    <button type="button" onclick="setTime('start', '16', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">16:00</button>
                    <button type="button" onclick="setTime('start', '19', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">19:00</button>
                    <button type="button" onclick="setTime('start', '20', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">20:00</button>
                  </div>
                </div>

                <div>
                  <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-800">Jam Selesai: <span class="text-red-500">*</span></label>
                    <span class="text-[10px] font-mono text-amber-700 font-semibold bg-amber-50 px-1 rounded">Maks 23.59</span>
                  </div>
                  <div class="flex items-center gap-1 bg-white border border-slate-300 focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 rounded-md px-2 py-1.5 shadow-xs">
                    <select id="selectEndHour" class="bg-transparent text-xs font-mono font-bold text-slate-900 focus:outline-none cursor-pointer py-0.5 rounded w-full" onchange="syncTimeEnd()">
                      <?php for ($h = 0; $h <= 23; $h++): 
                        $hStr = sprintf('%02d', $h);
                      ?>
                        <option value="<?= $hStr ?>" <?= $valEndHour === $hStr ? 'selected' : '' ?>><?= $hStr ?></option>
                      <?php endfor; ?>
                    </select>
                    <span class="text-xs font-black text-slate-400 select-none">:</span>
                    <select id="selectEndMin" class="bg-transparent text-xs font-mono font-bold text-slate-900 focus:outline-none cursor-pointer py-0.5 rounded w-full" onchange="syncTimeEnd()">
                      <?php for ($m = 0; $m <= 59; $m++): 
                        $mStr = sprintf('%02d', $m);
                      ?>
                        <option value="<?= $mStr ?>" <?= $valEndMin === $mStr ? 'selected' : '' ?>><?= $mStr ?></option>
                      <?php endfor; ?>
                    </select>
                    <span class="text-[10px] font-mono text-slate-400 font-bold ml-1 select-none">WIB</span>
                  </div>
                  <input type="hidden" name="time_end" id="inputTimeEnd" value="<?= htmlspecialchars($valEnd) ?>" required>
                  
                  <!-- Quick Preset Pills -->
                  <div class="flex flex-wrap items-center gap-1 mt-1.5">
                    <button type="button" onclick="setTime('end', '21', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">21:00</button>
                    <button type="button" onclick="setTime('end', '22', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">22:00</button>
                    <button type="button" onclick="setTime('end', '23', '00')" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">23:00</button>
                    <button type="button" onclick="setTime('end', '23', '59')" class="px-1.5 py-0.5 text-[10px] font-mono bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold rounded border border-amber-300 transition-colors">23:59</button>
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-800 mb-1">Jumlah Lapangan: <span class="text-red-500">*</span></label>
                  <input type="number" name="court_count" min="1" max="10" required value="<?= $editSchedule ? htmlspecialchars($editSchedule['court_count']) : '' ?>" placeholder="Misal: 2" class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none shadow-xs">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-800 mb-1">Maks Kuota: <span class="text-red-500">*</span></label>
                  <input type="number" name="max_quota" min="4" max="50" required value="<?= $editSchedule ? htmlspecialchars($editSchedule['max_quota']) : '' ?>" placeholder="Misal: 16" class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none shadow-xs">
                </div>
              </div>

              <?php 
                $existingFee = $editSchedule ? (int)(!empty($editSchedule['fee_per_person']) ? $editSchedule['fee_per_person'] : (!empty($editSchedule['fee']) ? $editSchedule['fee'] : 35000)) : 35000;
              ?>
              <div>
                <div class="flex items-center justify-between mb-1 max-w-sm">
                  <label class="block text-xs font-bold text-slate-800">Iuran / Biaya: <span class="text-red-500">*</span></label>
                  <span class="text-[10px] font-mono text-slate-500">Separator Ribuan (.)</span>
                </div>
                <div class="relative flex rounded-md shadow-xs max-w-sm">
                  <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-slate-300 bg-slate-50 text-slate-700 text-xs font-bold select-none">
                    Rp
                  </span>
                  <input type="text" 
                         id="inputFeeDisplay" 
                         name="fee" 
                         required 
                         inputmode="numeric"
                         value="<?= number_format($existingFee, 0, ',', '.') ?>" 
                         placeholder="Misal: 40.000" 
                         oninput="formatRupiahInput(this)" 
                         class="w-full bg-white border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-r-md px-3 py-2 text-xs font-mono font-bold text-slate-900 focus:outline-none">
                </div>
                <!-- Quick Fee Presets -->
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                  <span class="text-[10px] text-slate-400 font-medium">Cepat:</span>
                  <button type="button" onclick="setFeePreset(30000)" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">30.000</button>
                  <button type="button" onclick="setFeePreset(35000)" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">35.000</button>
                  <button type="button" onclick="setFeePreset(40000)" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">40.000</button>
                  <button type="button" onclick="setFeePreset(45000)" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">45.000</button>
                  <button type="button" onclick="setFeePreset(50000)" class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 hover:bg-amber-100 hover:text-amber-900 text-slate-600 rounded border border-slate-200 transition-colors">50.000</button>
                </div>
              </div>

              <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="?tab=schedules" class="px-4 py-2 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                  Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-md bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 border border-amber-400/30 font-black text-xs uppercase tracking-wider transition-all shadow-xs">
                  <?= $editSchedule ? 'Simpan Perubahan Jadwal' : 'Buat Jadwal Baru' ?>
                </button>
              </div>
            </form>
          </div>

        </div>


      <!-- ======================================================== -->
      <!-- TAB ROSTER: PEMAIN TERDAFTAR PER CABANG & LEVEL         -->
      <!-- ======================================================== -->
      <?php elseif ($tab === 'roster'): 
        $countBeg = 0;
        $countInt = 0;
        $countAdv = 0;
        foreach ($players as $pl) {
            $lvl = strtolower($pl['level']);
            if (strpos($lvl, 'beg') !== false) $countBeg++;
            elseif (strpos($lvl, 'adv') !== false) $countAdv++;
            else $countInt++;
        }
      ?>

        <!-- Roster Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold mb-1.5">
              <span>Roster Master Komunitas</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-950 font-sans tracking-tight">
              Daftar Pemain & Cabang Mabar
            </h2>
            <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
              Pantau seluruh pemain terdaftar di komunitas, cabang GOR yang aktif diikuti, status kehadiran, rekam iuran kas, dan penyesuaian level permainan untuk pembagian pertandingan yang adil.
            </p>
          </div>

          <button type="button" onclick="document.getElementById('modalAddPlayer').classList.remove('hidden')" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 font-black text-xs uppercase tracking-wider transition-all border border-amber-400/30 active:scale-95 shadow-md shrink-0">
            <span>+ Tambah Pemain Baru</span>
          </button>
        </div>

        <!-- Roster Stat Highlights -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Roster Komunitas</span>
            <div class="text-2xl font-black text-slate-900 mt-0.5"><?= count($players) ?> <span class="text-xs font-semibold text-slate-400 font-sans">Pemain</span></div>
            <span class="text-[10px] text-slate-500 mt-1 block">Terdaftar di master data</span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Hasil Filter Cabang</span>
            <div class="text-2xl font-black text-blue-900 mt-0.5"><?= count($rosterPlayers) ?> <span class="text-xs font-semibold text-slate-400 font-sans">Pemain</span></div>
            <span class="text-[10px] text-slate-500 mt-1 block"><?= !empty($filterRosterBranch) ? htmlspecialchars($filterRosterBranch) : 'Semua Cabang' ?></span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Distribusi Level Permainan</span>
            <div class="flex items-center gap-2 mt-1 text-xs font-bold">
              <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200"><?= $countBeg ?> Beg</span>
              <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200"><?= $countInt ?> Int</span>
              <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-200"><?= $countAdv ?> Adv</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Untuk rotasi adil & imbang</span>
          </div>

          <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Cabang Sesi Terdaftar</span>
            <div class="text-2xl font-black text-amber-600 mt-0.5"><?= count($allSchedules) ?> <span class="text-xs font-semibold text-slate-400 font-sans">Sesi</span></div>
            <span class="text-[10px] text-slate-500 mt-1 block">Rutin tiap pekan</span>
          </div>
        </div>

        <!-- Filter & Search Bar for Roster -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-xs mb-6">
          <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <input type="hidden" name="tab" value="roster">

            <div class="sm:col-span-6">
              <label class="block text-xs font-bold text-slate-700 mb-1">Filter Berdasarkan Sesi / GOR & Tanggal Main:</label>
              <select name="roster_session" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-amber-500">
                <option value="all">-- Seluruh Cabang (Buku Kontak Master Komunitas) --</option>
                <?php foreach ($allSchedules as $sch): 
                  $fDate = !empty($sch['schedule_date']) ? date('d M Y', strtotime($sch['schedule_date'])) : '-';
                  $stTag = ($sch['status'] === 'completed') ? '[Selesai]' : (($sch['status'] === 'in_progress') ? '[Main]' : '[Aktif]');
                  $pCount = isset($sch['participant_count']) ? (int)$sch['participant_count'] : 0;
                ?>
                  <option value="<?= Security::hashId((int)$sch['id']) ?>" <?= $filterRosterSession === (int)$sch['id'] ? 'selected' : '' ?>>
                    <?= $stTag ?> <?= $fDate ?> — <?= htmlspecialchars($sch['venue_name']) ?> (<?= htmlspecialchars($sch['day_name']) ?> • <?= $pCount ?>/<?= $sch['max_slots'] ?> Pemain)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="sm:col-span-4">
              <label class="block text-xs font-bold text-slate-700 mb-1">Filter Kata Kunci Cabang / Area:</label>
              <input type="text" name="roster_branch" value="<?= htmlspecialchars($filterRosterBranch) ?>" placeholder="Misal: Pancoran, Fatmawati, Tebet..." class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 font-semibold focus:outline-none">
            </div>

            <div class="sm:col-span-2 flex gap-2">
              <button type="submit" class="w-full py-2.5 bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 font-bold text-xs uppercase tracking-wider rounded-md transition-all border border-amber-400/30">
                Filter
              </button>
              <?php if ($filterRosterSession > 0 || !empty($filterRosterBranch)): ?>
                <a href="?tab=roster" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-xs font-bold transition-all" title="Reset Filter">
                  Reset
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- Session Specific Info Banner (if filtered by session) -->
        <?php if ($currentFilterSession): 
          $sDateFull = Community::formatIndoDate($currentFilterSession['schedule_date']);
          $isCompleted = ($currentFilterSession['status'] === 'completed');
        ?>
          <div class="bg-gradient-to-r from-[#0b1e36] to-[#132b4f] rounded-lg p-5 text-white mb-6 shadow-md border border-amber-400/20">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                  <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider <?= $isCompleted ? 'bg-slate-700 text-slate-300' : 'bg-emerald-500 text-slate-950 font-black' ?>">
                    <?= $isCompleted ? '✓ Riwayat Mabar Selesai' : '⚡ Sesi Aktif / Mendatang' ?>
                  </span>
                  <span class="text-xs font-mono text-amber-400 font-bold">
                    📅 <?= $sDateFull ?>
                  </span>
                  <span class="text-xs text-slate-300 font-mono">
                    ⏰ <?= substr($currentFilterSession['time_start'], 0, 5) ?> - <?= substr($currentFilterSession['time_end'], 0, 5) ?> WIB
                  </span>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-white font-sans">
                  <?= htmlspecialchars($currentFilterSession['venue_name']) ?>
                </h3>
                <p class="text-xs text-slate-300 mt-1">
                  <?= htmlspecialchars($currentFilterSession['day_name']) ?> • Cabang: <?= htmlspecialchars($currentFilterSession['branch_area']) ?> • PIC: <?= htmlspecialchars($currentFilterSession['pic_whatsapp']) ?>
                </p>
                <div class="mt-2.5 inline-flex items-center gap-2 text-xs font-bold bg-white/10 px-3 py-1 rounded-md border border-white/10">
                  <span>Daftar Peserta Khusus Tanggal Ini:</span>
                  <strong class="text-amber-400"><?= count($rosterPlayers) ?> / <?= $currentFilterSession['max_slots'] ?> Pemain Terdaftar</strong>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap shrink-0">
                <a href="?tab=schedules&wa=<?= Security::hashId((int)$currentFilterSession['id']) ?>#waSection" class="px-3.5 py-2 rounded bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-xs">
                  Template WA
                </a>
                <a href="<?= $appUrl ?>?switch=<?= urlencode(Security::hashId((int)$currentFilterSession['id'])) ?>" target="_blank" class="px-3.5 py-2 rounded bg-white/10 hover:bg-white/20 text-white font-bold text-xs uppercase tracking-wider transition-all border border-white/20">
                  Buka di App Lapangan ↗
                </a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="bg-amber-50/80 border border-amber-200/80 rounded-lg p-3.5 mb-6 text-xs text-amber-950 leading-relaxed flex items-start gap-2.5">
            <span class="text-base shrink-0">💡</span>
            <div>
              <strong>Siklus Roster Pemain Mabar:</strong> Setiap sesi mabar memiliki tanggal dan daftar peserta yang terisolasi. Pilih sesi pada dropdown <strong>"Filter Berdasarkan Sesi / GOR & Tanggal Main"</strong> di atas untuk melihat siapa saja yang terdaftar pada tanggal mabar tertentu. Jika tidak difilter, tabel di bawah menampilkan <strong>Buku Induk Master Pemain</strong> (akumulasi seluruh anggota).
            </div>
          </div>
        <?php endif; ?>

        <!-- Roster Master Table -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm mb-8">
          <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-3">
                <h3 class="text-base font-black text-slate-900">
                  <?= $currentFilterSession ? ('Daftar Peserta Sesi: ' . htmlspecialchars($currentFilterSession['venue_name']) . ' (' . count($rosterPlayers) . ' Pemain)') : ('Daftar Pemain Master (' . count($rosterPlayers) . ')') ?>
                </h3>
                <!-- Bulk Delete Action Button (Appears when checkboxes are selected) -->
                <button type="button" id="btnBulkDeletePlayers" onclick="submitBulkDeletePlayers()" class="hidden px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold text-xs flex items-center gap-1.5 transition-all shadow-xs active:scale-95">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  <span>Hapus Terpilih (<span id="selectedPlayersCount">0</span>)</span>
                </button>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Kelola pemain komunitas, sesuaikan level permainan, atau hapus pemain yang tidak aktif.
              </p>
            </div>
            <span class="text-xs font-mono font-bold text-slate-400">
              Sorted by Total Sesi & Poin
            </span>
          </div>

          <?php if (empty($rosterPlayers)): ?>
            <div class="p-12 text-center">
              <h4 class="text-sm font-bold text-slate-800">Tidak Ada Pemain Ditemukan</h4>
              <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                Tidak ada data pemain untuk filter cabang tersebut. Silakan reset filter atau tambahkan pemain baru.
              </p>
            </div>
          <?php else: ?>
            <!-- Hidden Bulk Delete Form -->
            <form id="bulkDeletePlayersForm" method="POST" action="?tab=roster" class="hidden">
              <input type="hidden" name="form_action" value="bulk_delete_players">
              <input type="hidden" name="player_ids" id="bulkDeletePlayerIds">
            </form>

            <!-- Mobile Responsive Card View for Android & Mobile Phones -->
            <div class="block md:hidden divide-y divide-slate-100">
              <?php foreach ($rosterPlayers as $idx => $rp): 
                $venues = !empty($rp['branch_venues']) ? explode('||', $rp['branch_venues']) : [];
                $lvlLower = strtolower($rp['level']);
                $lvlBadge = (strpos($lvlLower, 'beg') !== false) 
                  ? 'bg-emerald-50 text-emerald-800 border-emerald-300' 
                  : ((strpos($lvlLower, 'adv') !== false) ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-blue-50 text-blue-800 border-blue-300');
              ?>
                <div class="p-3.5 bg-white space-y-2.5">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <input type="checkbox" value="<?= $rp['id'] ?>" onchange="updateSelectedPlayers()" class="player-checkbox w-5 h-5 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer shrink-0">
                      <div class="min-w-0">
                        <div class="font-black text-slate-900 text-sm truncate"><?= htmlspecialchars($rp['name']) ?></div>
                        <div class="flex items-center gap-2 mt-0.5">
                          <span class="text-[10px] font-mono font-bold text-slate-400">#<?= $idx + 1 ?></span>
                          <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase tracking-wider <?= $lvlBadge ?>"><?= htmlspecialchars($rp['level']) ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="text-right shrink-0">
                      <div class="font-mono font-black text-slate-900 text-sm"><?= number_format($rp['total_points']) ?> Pts</div>
                      <div class="text-[10px] font-mono text-slate-500"><?= (int)$rp['total_sessions_joined'] ?>x main</div>
                    </div>
                  </div>

                  <div class="flex items-center justify-between gap-2 text-xs pt-1 border-t border-slate-100">
                    <?php if (!empty($rp['phone'])): ?>
                      <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rp['phone']) ?>" target="_blank" class="min-h-[38px] px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded font-mono font-bold text-[11px] flex items-center gap-1 active:scale-95">
                        <span>WA: <?= htmlspecialchars($rp['phone']) ?></span>
                      </a>
                    <?php else: ?>
                      <span class="text-[10px] text-slate-400 italic">No WA belum ada</span>
                    <?php endif; ?>

                    <div class="flex items-center gap-1.5">
                      <button type="button" onclick="openEditPlayerModal(<?= htmlspecialchars(json_encode([
                        'id' => $rp['id'],
                        'name' => $rp['name'],
                        'phone' => $rp['phone'],
                        'level' => $rp['level']
                      ])) ?>)" class="min-h-[38px] px-3 py-1 bg-slate-100 hover:bg-[#0b1e36] hover:text-amber-400 text-slate-800 font-bold text-xs rounded border border-slate-300 transition-all flex items-center justify-center">
                        Edit
                      </button>
                      <form method="POST" action="?tab=roster" onsubmit="handleAdminFormConfirm(event, this, 'Hapus Pemain <?= htmlspecialchars(addslashes($rp['name'])) ?>?', 'Pemain ini akan dihapus permanen dari daftar master komunitas.'); return false;" class="inline">
                        <input type="hidden" name="form_action" value="delete_player">
                        <input type="hidden" name="player_id" value="<?= $rp['id'] ?>">
                        <button type="submit" class="min-h-[38px] px-3 py-1 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white font-bold text-xs rounded border border-rose-200 transition-all flex items-center justify-center">
                          Hapus
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Desktop Tabular View -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left text-sm font-sans min-w-[750px]">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                  <tr>
                    <th class="py-3 px-3 w-10 text-center">
                      <input type="checkbox" id="selectAllPlayers" onchange="toggleSelectAllPlayers(this)" title="Pilih Semua Pemain" class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer">
                    </th>
                    <th class="py-3 px-3">#</th>
                    <th class="py-3 px-4">Nama Pemain</th>
                    <th class="py-3 px-4">Level</th>
                    <th class="py-3 px-4">Cabang Mabar yang Diikuti</th>
                    <th class="py-3 px-4 text-center">Sesi Mabar</th>
                    <th class="py-3 px-4 text-center">Iuran Kas</th>
                    <th class="py-3 px-4 text-right">Total Poin</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <?php foreach ($rosterPlayers as $idx => $rp): 
                    $venues = !empty($rp['branch_venues']) ? explode('||', $rp['branch_venues']) : [];
                    $areas = !empty($rp['branch_areas']) ? explode('||', $rp['branch_areas']) : [];
                    $lvlLower = strtolower($rp['level']);
                    $lvlBadge = (strpos($lvlLower, 'beg') !== false) 
                      ? 'bg-emerald-50 text-emerald-800 border-emerald-300' 
                      : ((strpos($lvlLower, 'adv') !== false) ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-blue-50 text-blue-800 border-blue-300');
                  ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                      <td class="py-3.5 px-3 text-center">
                        <input type="checkbox" value="<?= $rp['id'] ?>" onchange="updateSelectedPlayers()" class="player-checkbox w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer">
                      </td>
                      <td class="py-3.5 px-3 font-mono text-xs font-bold text-slate-400">
                        <?= $idx + 1 ?>
                      </td>
                      <td class="py-3.5 px-4">
                        <div class="font-extrabold text-slate-900 text-sm">
                          <?= htmlspecialchars($rp['name']) ?>
                        </div>
                        <?php if (!empty($rp['phone'])): ?>
                          <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rp['phone']) ?>" target="_blank" class="text-[11px] font-mono text-emerald-700 hover:underline flex items-center gap-1 mt-0.5">
                            <span>WA: <?= htmlspecialchars($rp['phone']) ?></span>
                          </a>
                        <?php else: ?>
                          <span class="text-[10px] text-slate-400 italic">No WA belum ada</span>
                        <?php endif; ?>
                      </td>

                      <td class="py-3.5 px-4">
                        <span class="text-xs font-black px-2.5 py-1 rounded border uppercase tracking-wider <?= $lvlBadge ?>">
                          <?= htmlspecialchars($rp['level']) ?>
                        </span>
                      </td>

                      <td class="py-3.5 px-4">
                        <?php if (empty($venues)): ?>
                          <span class="text-xs text-slate-400 italic">Belum pernah join sesi</span>
                        <?php else: ?>
                          <div class="flex flex-wrap gap-1.5 max-w-xs">
                            <?php foreach (array_unique($venues) as $vName): ?>
                              <span class="text-[11px] font-semibold bg-[#0b1e36] text-amber-400 px-2 py-0.5 rounded border border-amber-400/30">
                                <?= htmlspecialchars($vName) ?>
                              </span>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </td>

                      <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-700">
                        <span class="inline-block px-2.5 py-1 bg-slate-100 rounded text-xs">
                          <?= (int)$rp['total_sessions_joined'] ?>x main
                        </span>
                      </td>

                      <td class="py-3.5 px-4 text-center">
                        <div class="flex items-center justify-center gap-1.5 text-xs font-mono font-bold">
                          <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200" title="Sesi Lunas">
                            <?= (int)$rp['paid_sessions'] ?> Lunas
                          </span>
                          <?php if ((int)$rp['unpaid_sessions'] > 0): ?>
                            <span class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200" title="Sesi Belum Bayar">
                              <?= (int)$rp['unpaid_sessions'] ?> Belum
                            </span>
                          <?php endif; ?>
                        </div>
                      </td>

                      <td class="py-3.5 px-4 text-right font-mono font-black text-slate-900 text-sm">
                        <?= number_format($rp['total_points']) ?> Pts
                      </td>

                      <td class="py-3.5 px-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                          <button type="button" onclick="openEditPlayerModal(<?= htmlspecialchars(json_encode([
                            'id' => $rp['id'],
                            'name' => $rp['name'],
                            'phone' => $rp['phone'],
                            'level' => $rp['level']
                          ])) ?>)" class="px-2.5 py-1 bg-slate-100 hover:bg-[#0b1e36] hover:text-amber-400 text-slate-700 font-bold text-xs rounded border border-slate-300 transition-all">
                            Edit
                          </button>
                          <form method="POST" action="?tab=roster" onsubmit="handleAdminFormConfirm(event, this, 'Hapus Pemain <?= htmlspecialchars(addslashes($rp['name'])) ?>?', 'Pemain ini akan dihapus permanen dari daftar master komunitas beserta riwayat sesinya.'); return false;" class="inline">
                            <input type="hidden" name="form_action" value="delete_player">
                            <input type="hidden" name="player_id" value="<?= $rp['id'] ?>">
                            <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white font-bold text-xs rounded border border-rose-200 transition-all" title="Hapus pemain">
                              Hapus
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- Modal 1: Add New Player -->
        <div id="modalAddPlayer" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
          <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
              <h3 class="text-base font-black text-slate-900">+ Tambah Pemain Baru ke Roster</h3>
              <button type="button" onclick="document.getElementById('modalAddPlayer').classList.add('hidden')" class="px-2 py-1 text-slate-400 hover:text-slate-800 font-bold text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="?tab=roster" class="space-y-3.5">
              <input type="hidden" name="form_action" value="create_player">

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemain:</label>
                <input type="text" name="name" required placeholder="Nama lengkap pemain (Contoh: Budi Santoso)" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp:</label>
                <input type="text" name="phone" placeholder="Contoh: 081234567890" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-slate-900">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Level Permainan:</label>
                <select name="level" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                  <option value="Beginner">Beginner (Pemula)</option>
                  <option value="Intermediate" selected>Intermediate (Menengah)</option>
                  <option value="Advance">Advance (Mahir / Kompetitif)</option>
                </select>
              </div>

              <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddPlayer').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-xs font-bold">
                  Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 rounded-md text-xs font-black uppercase tracking-wider transition-all border border-amber-400/30">
                  Simpan Pemain Baru
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Modal 2: Edit Player & Level -->
        <div id="modalEditPlayer" class="hidden fixed inset-0 bg-slate-950/70 z-50 flex items-center justify-center p-4">
          <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
              <h3 class="text-base font-black text-slate-900">Edit Level & Data Pemain</h3>
              <button type="button" onclick="document.getElementById('modalEditPlayer').classList.add('hidden')" class="px-2 py-1 text-slate-400 hover:text-slate-800 font-bold text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="?tab=roster" class="space-y-3.5">
              <input type="hidden" name="form_action" value="update_player_level">
              <input type="hidden" name="player_id" id="editPlayerId">

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemain:</label>
                <input type="text" name="name" id="editPlayerName" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp:</label>
                <input type="text" name="phone" id="editPlayerPhone" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-slate-900">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Level Permainan (Untuk Penentuan Lapangan & Rotasi):</label>
                <select name="level" id="editPlayerLevel" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                  <option value="Beginner">Beginner (Pemula)</option>
                  <option value="Intermediate">Intermediate (Menengah)</option>
                  <option value="Advance">Advance (Mahir / Kompetitif)</option>
                </select>
                <span class="text-[10px] text-slate-400 mt-1 block">Level ini dipakai sistem untuk mengacak rotasi berimbang (Fair Level) di aplikasi lapangan.</span>
              </div>

              <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="deletePlayerFromModal()" class="text-rose-600 hover:text-rose-800 text-xs font-bold hover:underline">
                  Hapus Pemain Ini
                </button>
                <div class="flex items-center gap-2">
                  <button type="button" onclick="document.getElementById('modalEditPlayer').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-xs font-bold">
                    Batal
                  </button>
                  <button type="submit" class="px-5 py-2 bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 rounded-md text-xs font-black uppercase tracking-wider transition-all border border-amber-400/30">
                    Perbarui Level Pemain
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <script>
          function openEditPlayerModal(player) {
            document.getElementById('editPlayerId').value = player.id;
            document.getElementById('editPlayerName').value = player.name;
            document.getElementById('editPlayerPhone').value = player.phone || '';
            document.getElementById('editPlayerLevel').value = player.level;
            document.getElementById('modalEditPlayer').classList.remove('hidden');
          }

          function toggleSelectAllPlayers(master) {
            document.querySelectorAll('.player-checkbox').forEach(cb => {
              cb.checked = master.checked;
            });
            updateSelectedPlayers();
          }

          function updateSelectedPlayers() {
            const selected = Array.from(document.querySelectorAll('.player-checkbox:checked')).map(cb => cb.value);
            const btn = document.getElementById('btnBulkDeletePlayers');
            const counter = document.getElementById('selectedPlayersCount');
            if (counter) counter.textContent = selected.length;
            if (btn) {
              if (selected.length > 0) {
                btn.classList.remove('hidden');
              } else {
                btn.classList.add('hidden');
              }
            }
          }

          async function submitBulkDeletePlayers() {
            const selected = Array.from(document.querySelectorAll('.player-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;
            
            const ok = await showAdminConfirm({
              title: `Hapus ${selected.length} Pemain Terpilih?`,
              message: `PERINGATAN: ${selected.length} pemain yang Anda pilih akan dihapus permanen dari master roster beserta seluruh riwayat sesinya. Tindakan ini tidak dapat dibatalkan.`,
              confirmText: `Ya, Hapus ${selected.length} Pemain`,
              confirmColor: 'bg-rose-600 hover:bg-rose-700 text-white'
            });
            if (ok) {
              document.getElementById('bulkDeletePlayerIds').value = selected.join(',');
              document.getElementById('bulkDeletePlayersForm').submit();
            }
          }

          async function deletePlayerFromModal() {
            const pId = document.getElementById('editPlayerId')?.value;
            const pName = document.getElementById('editPlayerName')?.value || 'Pemain';
            if (!pId) return;

            const ok = await showAdminConfirm({
              title: `Hapus Pemain "${pName}"?`,
              message: `Pemain ini akan dihapus permanen dari daftar master komunitas beserta riwayat sesinya.`,
              confirmText: 'Ya, Hapus Pemain',
              confirmColor: 'bg-rose-600 hover:bg-rose-700 text-white'
            });
            if (ok) {
              const form = document.createElement('form');
              form.method = 'POST';
              form.action = '?tab=roster';
              form.innerHTML = `
                <input type="hidden" name="form_action" value="delete_player">
                <input type="hidden" name="player_id" value="${pId}">
              `;
              document.body.appendChild(form);
              form.submit();
            }
          }
        </script>

      <!-- TAB 4: LEADERBOARD OF THE MONTH (KLASEMEN BULANAN & SESI) -->
      <?php elseif ($tab === 'players'): ?>
        
        <div class="bg-white rounded-lg border border-slate-200 p-6 sm:p-8 shadow-sm mb-8">
          
          <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-slate-200 gap-4">
            <div>
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold mb-1">
                <svg class="w-4 h-4 text-amber-500 inline" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                <span>Klasemen Pemain Bulanan & Sesi</span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-black text-slate-950 font-sans font-bold">
                Leaderboard of the Month
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Akumulasi poin pertandingan per bulan dari seluruh sesi mabar yang diadakan komunitas.
              </p>
            </div>

            <!-- Month & Sesi Filter Bar -->
            <form method="GET" action="" class="flex flex-wrap items-center gap-3">
              <input type="hidden" name="tab" value="players">

              <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pilih Bulan:</label>
                <select name="month" onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-800">
                  <option value="2026-11" <?= $selectedMonth === '2026-11' ? 'selected' : '' ?>>November 2026 (Bulan Ini)</option>
                  <option value="2026-10" <?= $selectedMonth === '2026-10' ? 'selected' : '' ?>>Oktober 2026</option>
                  <option value="2026-09" <?= $selectedMonth === '2026-09' ? 'selected' : '' ?>>September 2026</option>
                  <option value="all" <?= $selectedMonth === 'all' ? 'selected' : '' ?>>Sepanjang Waktu (All Time)</option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Sesi GOR:</label>
                <select name="session_filter" onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-800">
                  <option value="all" <?= $selectedSession === 0 ? 'selected' : '' ?>>Semua Cabang Sesi</option>
                  <?php foreach ($allSchedules as $sch): ?>
                    <option value="<?= Security::hashId((int)$sch['id']) ?>" <?= $selectedSession === (int)$sch['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($sch['day_name']) ?>: <?= htmlspecialchars($sch['venue_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>
          </div>

          <!-- Mobile Responsive Ranking Card View for Android & Mobile Phones -->
          <div class="block md:hidden mt-4 divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
            <?php foreach ($monthlyLeaderboard as $idx => $p): 
              $wr = $p['total_matches'] > 0 ? round(($p['wins'] / $p['total_matches']) * 100) : 0;
            ?>
              <div class="p-3.5 space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center font-mono font-black text-xs shrink-0 <?= $idx === 0 ? 'bg-amber-400 text-slate-950 font-bold' : ($idx === 1 ? 'bg-slate-200 text-slate-800' : 'bg-slate-100 text-slate-600') ?>">
                      #<?= $idx + 1 ?>
                    </span>
                    <div class="min-w-0">
                      <div class="font-extrabold text-slate-900 text-sm truncate">
                        <?= htmlspecialchars($p['name']) ?>
                      </div>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $p['level'] === 'Advance' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($p['level'] === 'Beginner' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-blue-50 text-blue-800 border border-blue-200') ?>">
                          <?= htmlspecialchars($p['level']) ?>
                        </span>
                        <?php if ($idx === 0): ?>
                          <span class="text-[9px] bg-amber-100 text-amber-900 font-black px-1.5 py-0.2 rounded uppercase">MVP</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <div class="text-right shrink-0">
                    <div class="font-mono font-black text-blue-900 text-base leading-none">
                      <?= number_format($p['total_points']) ?> Pts
                    </div>
                    <div class="text-[10px] font-mono text-slate-500 mt-1">
                      WR: <strong><?= $wr ?>%</strong>
                    </div>
                  </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-mono text-slate-600">
                  <span>Main: <strong><?= $p['total_matches'] ?></strong> match</span>
                  <span>Menang: <strong class="text-emerald-700"><?= $p['wins'] ?></strong> / Kalah: <strong class="text-rose-600"><?= $p['losses'] ?></strong></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Desktop Tabular View -->
          <div class="hidden md:block mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm font-sans min-w-[650px]">
              <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-6">Rank</th>
                  <th class="py-3.5 px-6">Nama Pemain</th>
                  <th class="py-3.5 px-6">Level</th>
                  <th class="py-3.5 px-6 text-center">Tanding Bulan Ini</th>
                  <th class="py-3.5 px-6 text-center">Menang / Kalah</th>
                  <th class="py-3.5 px-6 text-center">Win Rate</th>
                  <th class="py-3.5 px-6 text-right text-blue-700 font-black">Total Poin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($monthlyLeaderboard as $idx => $p): 
                  $wr = $p['total_matches'] > 0 ? round(($p['wins'] / $p['total_matches']) * 100) : 0;
                ?>
                  <tr class="hover:bg-slate-50 transition-colors">
                    <td class="py-3.5 px-6 font-extrabold text-base <?= $idx === 0 ? 'text-amber-500' : ($idx === 1 ? 'text-slate-600' : 'text-slate-400') ?>">
                      <?= $idx === 0 ? 'TOP #1' : '#' . ($idx + 1) ?>
                    </td>
                    <td class="py-3.5 px-6 font-bold text-slate-900">
                      <?= htmlspecialchars($p['name']) ?>
                      <?php if ($idx === 0): ?>
                        <span class="ml-2 text-[10px] bg-amber-100 text-amber-800 font-black px-2 py-0.5 rounded-full uppercase">Player of the Month</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-3.5 px-6">
                      <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full <?= $p['level'] === 'Advance' ? 'bg-red-50 text-red-700 border border-red-200' : ($p['level'] === 'Beginner' ? 'bg-emerald-50 text-blue-700 border border-blue-200' : 'bg-blue-50 text-blue-700 border border-blue-200') ?>">
                        <?= htmlspecialchars($p['level']) ?>
                      </span>
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono font-semibold text-slate-600">
                      <?= $p['total_matches'] ?> mch
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono font-semibold">
                      <span class="text-blue-700 font-bold"><?= $p['wins'] ?></span> / <span class="text-rose-600"><?= $p['losses'] ?></span>
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono font-black text-slate-900">
                      <?= $wr ?>%
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono-num font-black text-base text-blue-700">
                      <?= number_format($p['total_points']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>

        <!-- Leaderboard Recalculation Engine & Manual Point Adjustment -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          <div class="lg:col-span-6 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
            <h3 class="text-base font-black text-slate-900 font-sans font-bold mb-1">
              Kalkulasi Ulang Leaderboard Berdasarkan Match Records
            </h3>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
              Rumus: <strong>Total Poin = (Akumulasi Poin Reli Pertandingan) + (Jumlah Menang × Win Bonus)</strong>.
            </p>

            <form method="POST" action="?tab=players" class="space-y-4">
              <input type="hidden" name="form_action" value="recalculate_leaderboard">
              
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Bobot Win Bonus Per Kemenangan:</label>
                <select name="win_bonus" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-800">
                  <option value="5" selected>+5 Poin (Standar Komunitas)</option>
                  <option value="3">+3 Poin (Kompetitif Moderat)</option>
                  <option value="10">+10 Poin (Prioritas Kemenangan)</option>
                  <option value="0">+0 Poin (Murni Poin Reli Saja)</option>
                </select>
              </div>

              <button type="submit" class="w-full py-2.5 rounded-md bg-emerald-700 hover:bg-amber-400 text-slate-950 font-black font-bold text-xs uppercase tracking-wider transition-all btn-tactile shadow-sm">
                Hitung Ulang Leaderboard Sekarang
              </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-200">
              <form method="POST" action="?tab=players" onsubmit="handleAdminFormConfirm(event, this, 'Reset Poin Pemain?', 'PERINGATAN: Seluruh akumulasi poin pemain akan direset untuk awal bulan baru.'); return false;">
                <input type="hidden" name="form_action" value="reset_leaderboard">
                <button type="submit" class="w-full py-2 rounded-md bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 font-bold text-xs uppercase tracking-wider transition-all">
                  Reset Poin Roster Bulan Baru
                </button>
              </form>
            </div>
          </div>

          <div class="lg:col-span-6 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
            <h3 class="text-base font-black text-slate-900 font-sans font-bold mb-1">
              Koreksi / Penyesuaian Poin Pemain Manual
            </h3>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
              Tambahkan bonus khusus (misal juara mini-turnamen internal atau penalti absensi).
            </p>

            <form method="POST" action="?tab=players" class="space-y-4">
              <input type="hidden" name="form_action" value="adjust_player_points">

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Pemain:</label>
                <select name="player_id" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-800">
                  <?php foreach ($players as $pl): ?>
                    <option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['name']) ?> (<?= number_format($pl['total_points']) ?> Pts)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Poin Tambahan / Pengurangan (+/-):</label>
                <input type="number" name="points_delta" required placeholder="Contoh: 15 atau -5" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-800">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penyesuaian:</label>
                <input type="text" name="reason" required placeholder="Contoh: Juara Turnamen Internal / Bonus Kehadiran Rutin" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-800">
              </div>

              <button type="submit" class="w-full py-2.5 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition-all btn-tactile shadow-sm">
                Terapkan Koreksi Poin
              </button>
            </form>
          </div>

        </div>


      <!-- TAB: AKUN PIC SESI (ISOLATED SESSION AUTH) -->
      <?php elseif ($tab === 'pic_accounts'): ?>
        
        <div class="mb-8">
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded bg-amber-100 text-blue-900 text-[10px] font-bold uppercase tracking-wider">Akses Lapangan PIC</span>
            <span class="text-xs text-slate-400 font-mono">• Session Isolation System</span>
          </div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">
            Manajemen Akun Login PIC Sesi
          </h2>
          <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
            Setiap PIC hanya memiliki akses ke <strong>1 sesi mabar yang ditugaskan</strong>. Saat PIC login di HP mereka pada Aplikasi Lapangan (<code>/app/</code>), sesi mereka otomatis terkunci sehingga tidak akan terjadi tabrakan atau kesalahan input skor dengan sesi lain yang berlangsung di jam yang sama.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          
          <!-- Form Buat Akun PIC Baru -->
          <div class="lg:col-span-5">
            <div class="bg-white rounded-lg border border-slate-200 p-6 sm:p-7 shadow-sm sticky top-24">
              <h3 class="text-base font-black text-slate-900 mb-1">
                + Tambah Akun PIC Baru
              </h3>
              <p class="text-xs text-slate-500 mb-5">
                Akun ini digunakan oleh PIC untuk login ke App Operasional Lapangan di HP.
              </p>

              <form method="POST" action="?tab=pic_accounts" class="space-y-4">
                <input type="hidden" name="form_action" value="create_pic_account">

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap PIC:</label>
                  <input type="text" name="name" required placeholder="Nama lengkap PIC (Contoh: Budi Santoso)" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700">
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp PIC:</label>
                  <input type="text" name="phone" required placeholder="Contoh: 081234567890" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700">
                  <span class="text-[10px] text-slate-400 mt-0.5 block">PIC dapat login menggunakan Username ATAU No. WhatsApp.</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username Login:</label>
                    <input type="text" name="username" required placeholder="Contoh: pic_lapangan1" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700 font-mono">
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password:</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700">
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Penugasan / Peran Akun:</label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label class="border border-slate-300 rounded-md p-2.5 flex items-start gap-2.5 cursor-pointer hover:bg-slate-50 transition-colors">
                      <input type="radio" name="role" value="pic" checked class="mt-0.5 text-[#0b1e36]">
                      <div>
                        <div class="text-xs font-black text-slate-900">PIC Lapangan</div>
                        <div class="text-[10px] text-slate-500 leading-tight">Operasional rotasi, papan skor, kas, dan pemain.</div>
                      </div>
                    </label>
                    <label class="border border-slate-300 rounded-md p-2.5 flex items-start gap-2.5 cursor-pointer hover:bg-slate-50 transition-colors">
                      <input type="radio" name="role" value="pic_foto" class="mt-0.5 text-[#0b1e36]">
                      <div>
                        <div class="text-xs font-black text-slate-900">PIC Foto / Media</div>
                        <div class="text-[10px] text-slate-500 leading-tight">Khusus upload foto dokumentasi & galeri mabar.</div>
                      </div>
                    </label>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Tugaskan ke Sesi Mabar:</label>
                  <select name="assigned_session_id" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-700">
                    <option value="">-- Pilih Sesi Mabar yang Ditugaskan --</option>
                    <?php foreach ($allSchedules as $sch): ?>
                      <?php 
                        $tS = isset($sch['time_start']) ? $sch['time_start'] : (isset($sch['start_time']) ? $sch['start_time'] : '20:00:00');
                      ?>
                      <option value="<?= $sch['id'] ?>">
                        <?= htmlspecialchars($sch['day_name']) ?> — <?= htmlspecialchars($sch['venue_name']) ?> (<?= substr($tS, 0, 5) ?> WIB)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <span class="text-[10px] text-blue-700 mt-1 block font-medium">
                    Akun ini otomatis terhubung khusus untuk sesi ini pada App PIC.
                  </span>
                </div>

                <button type="submit" class="w-full py-3 rounded-md bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition-all btn-tactile shadow-sm mt-2">
                  Daftarkan Akun PIC Sesi →
                </button>
              </form>
            </div>
          </div>

          <!-- Daftar Akun PIC Terdaftar -->
          <div class="lg:col-span-7">
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm">
              <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <div>
                  <h3 class="text-base font-black text-slate-900">
                    Daftar Akun PIC Lapangan (<?= count($picAccounts) ?>)
                  </h3>
                  <p class="text-xs text-slate-500 mt-0.5">PIC yang bertugas memandu rotasi, absensi, dan skor di GOR.</p>
                </div>
              </div>

              <?php if (empty($picAccounts)): ?>
                <div class="p-12 text-center">
                  <div class="w-12 h-12 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mx-auto text-xl font-bold mb-3">
                    
                  </div>
                  <h4 class="text-sm font-bold text-slate-800">Belum Ada Akun PIC</h4>
                  <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Silakan daftarkan akun PIC di form sebelah kiri untuk memberikan akses operasional lapangan pada PIC di masing-masing GOR.
                  </p>
                </div>
              <?php else: ?>
                <div class="divide-y divide-slate-100">
                  <?php foreach ($picAccounts as $pic): ?>
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/80 transition-colors">
                      <div class="flex items-start gap-3.5">
                        <div class="w-11 h-11 rounded-lg bg-amber-100 text-blue-900 flex items-center justify-center font-black text-sm shrink-0 border border-blue-200">
                          <?= strtoupper(substr($pic['name'], 0, 2)) ?>
                        </div>
                        <div>
                          <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-sm text-slate-900"><?= htmlspecialchars($pic['name']) ?></span>
                            <span class="text-xs font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded">@<?= htmlspecialchars($pic['username']) ?></span>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider <?= (isset($pic['role']) && $pic['role'] === 'pic_foto') ? 'bg-cyan-100 text-cyan-900 border border-cyan-300' : 'bg-[#0b1e36] text-amber-400' ?>">
                              <?= (isset($pic['role']) && $pic['role'] === 'pic_foto') ? 'PIC Foto / Media' : 'PIC Lapangan' ?>
                            </span>
                          </div>
                          
                          <div class="text-xs text-slate-600 mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="text-blue-700 font-bold"> <?= htmlspecialchars($pic['phone'] ?: 'Tidak ada No WA') ?></span>
                            <span>•</span>
                            <span class="font-semibold text-slate-700">
                              Sesi GOR: <?= htmlspecialchars($pic['day_name'] ?: 'Semua') ?> (<?= htmlspecialchars($pic['venue_name'] ?: 'Belum di-assign') ?>)
                            </span>
                          </div>
                        </div>
                      </div>

                      <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                        <form method="POST" action="?tab=pic_accounts" onsubmit="handleAdminFormConfirm(event, this, 'Hapus Akun PIC?', 'Akun PIC ini tidak akan dapat login lagi ke aplikasi lapangan.'); return false;">
                          <input type="hidden" name="form_action" value="delete_pic_account">
                          <input type="hidden" name="admin_id" value="<?= $pic['id'] ?>">
                          <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold transition-colors">
                            Hapus Akun
                          </button>
                        </form>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

            </div>

            <!-- Catatan Penggunaan HP Courtside -->
            <div class="mt-6 p-5 rounded-lg bg-amber-50 border border-amber-200 text-amber-950 text-xs leading-relaxed space-y-2">
              <div class="font-black text-amber-900 uppercase tracking-wider font-mono flex items-center gap-2">
                
                <span>Panduan Operasional Lapangan (Mobile Courtside)</span>
              </div>
              <p>
                Bagikan URL <strong><code><?= $appUrl ?></code></strong> beserta Username & Password ke masing-masing PIC.
              </p>
              <p>
                Ketika PIC membuka link di browser smartphone mereka dan login, sistem secara otomatis mengunci tampilan hanya ke sesi yang menjadi tanggung jawab mereka. Menu aplikasi didesain khusus super-lite dan responsif untuk pengoperasian cepat dengan satu tangan di pinggir lapangan.
              </p>
            </div>

          </div>

        </div>

      <!-- TAB 5: PROFIL KOMUNITAS, LOGO & FOTO DEPAN -->
      <?php elseif ($tab === 'settings'): 
        $currentHero = !empty($settings['hero_image_url']) ? $settings['hero_image_url'] : 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=80';
        $currentLogo = !empty($settings['logo_url']) ? $settings['logo_url'] : '';
        $currentFaqs = !empty($settings['faq_json']) ? json_decode($settings['faq_json'], true) : [];
        if (empty($currentFaqs) || !is_array($currentFaqs)) {
          $currentFaqs = [
            ['question' => 'Apakah pemain pemula (beginner) boleh ikut bergabung?', 'answer' => 'Sangat boleh! Komunitas kami terbuka untuk semua tingkatan dari Beginner, Intermediate, hingga Advance. Sistem rotasi digital kami akan membagi pasangan dan lawan secara berimbang agar reli tetap menyenangkan bagi semua orang.'],
            ['question' => 'Bagaimana jika saya mendaftar sendirian tanpa pasangan ganda?', 'answer' => 'Mayoritas peserta kami mendaftar secara individu! Di lapangan GOR, Anda akan dipasangkan secara bergantian dengan peserta lain oleh sistem rotasi otomatis, sehingga Anda pasti mendapat partner di setiap ronde.'],
            ['question' => 'Apakah saya perlu membawa shuttlecock sendiri?', 'answer' => 'Tidak perlu. Seluruh biaya iuran mabar sudah mencakup penyediaan shuttlecock bulu angsa resmi berkualitas yang disediakan panitia. Anda hanya perlu membawa raket dan mengenakan sepatu badminton non-marking.'],
            ['question' => 'Bagaimana alur pembayaran iuran mabar?', 'answer' => 'Pembayaran iuran dapat dilakukan secara cashless via QRIS/Transfer bank ke rekening komunitas atau tunai langsung kepada PIC sesi saat tiba di venue lapangan GOR.'],
            ['question' => 'Bagaimana jika saya berhalangan hadir setelah mendaftar?', 'answer' => 'Mohon segera memberitahu PIC sesi WhatsApp minimal 4 jam sebelum jadwal sesi dimulai agar kuota Anda dapat dialihkan ke peserta waiting list.']
          ];
        }
      ?>
        
        <div class="max-w-4xl bg-white rounded-lg border border-slate-200 p-6 sm:p-8 shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
              <h3 class="text-base font-black text-slate-900">Kelola Semua Konten Landing Page & Profil Komunitas</h3>
              <p class="text-xs text-slate-500 mt-0.5">Ubah seluruh teks, judul sambutan, 3 langkah pendaftaran, FAQ, logo dan foto banner depan.</p>
            </div>
            <span class="text-xs font-mono font-bold text-blue-900 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded">
              CMS Konten Publik
            </span>
          </div>

          <form method="POST" action="?tab=settings" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="form_action" value="update_settings">

            <!-- BAGIAN 1: TEKS SAMBUTAN & HERO SECTION (KATA-KATA UTAMA) -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <div>
                  <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900">
                    1. Hero Section & Sambutan Utama (Bagian Paling Atas Web)
                  </h4>
                  <p class="text-[11px] text-slate-500 mt-0.5">Teks utama yang pertama kali dibaca pengunjung saat membuka landing page.</p>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600 font-bold">Hero Banner</span>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Besar Hero (Headline Utama):</label>
                <input type="text" name="hero_title" required value="<?= htmlspecialchars(!empty($settings['hero_title']) ? $settings['hero_title'] : 'Main Badminton Bareng Tanpa Repot Cari Lawan & Lapangan.') ?>" class="w-full bg-white border border-slate-300 rounded-md px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Paragraf Sambutan & Deskripsi Hero (Teks Penjelasan):
                </label>
                <textarea name="hero_subtitle" rows="3" required class="w-full bg-white border border-slate-300 rounded-md px-3.5 py-2.5 text-xs text-slate-900 leading-relaxed focus:outline-none focus:ring-1 focus:ring-slate-900"><?= htmlspecialchars(!empty($settings['hero_subtitle']) ? $settings['hero_subtitle'] : 'Selamat datang di ' . $settings['community_name'] . '. Kami menyelenggarakan sesi main bareng ganda setiap pekan di berbagai GOR terbaik ' . (!empty($settings['base_city']) ? $settings['base_city'] : 'kota Anda') . '. Cukup daftar ke PIC WhatsApp, datang ke arena, dan nikmati pertandingan dengan pembagian giliran main yang teratur.') ?></textarea>
                <span class="text-[11px] text-slate-400 mt-1 block">Teks sambutan ini tampil tepat di bawah judul besar pada halaman utama.</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Teks Tombol Aksi Pendaftaran (CTA):</label>
                  <input type="text" name="hero_cta_text" required value="<?= htmlspecialchars(!empty($settings['hero_cta_text']) ? $settings['hero_cta_text'] : 'Pilih Jadwal & Daftar via WhatsApp PIC →') ?>" placeholder="Contoh: Pilih Jadwal & Daftar via WhatsApp PIC →" class="w-full bg-white border border-slate-300 rounded-md px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Teks Info Pengumuman Atas (Announcement Bar):</label>
                  <input type="text" name="announcement_text" value="<?= htmlspecialchars(!empty($settings['announcement_text']) ? $settings['announcement_text'] : 'Komunitas Badminton Resmi ' . (!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia') . ' (Est. ' . date('Y') . ')') ?>" placeholder="Contoh: Komunitas Badminton Terbuka & Ramah Pemula" class="w-full bg-white border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
              </div>

              <!-- Foto Banner Hero -->
              <div class="pt-2 border-t border-slate-200/60">
                <label class="block text-xs font-bold text-slate-700 mb-2">Foto Atlet / Banner Hero (Samping Judul):</label>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                  <div class="sm:col-span-3">
                    <div class="aspect-[4/3] rounded-md overflow-hidden border border-slate-300 bg-slate-200 shadow-xs">
                      <img id="heroPreviewImg" src="<?= htmlspecialchars($currentHero) ?>" alt="Foto Depan Hero" class="w-full h-full object-cover">
                    </div>
                  </div>
                  <div class="sm:col-span-9 space-y-2">
                    <div>
                      <input type="file" name="hero_file" accept="image/*" onchange="previewFile(this, 'heroPreviewImg')" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-700 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white cursor-pointer">
                    </div>
                    <div>
                      <input type="url" name="hero_image_url" id="heroUrlInput" value="<?= htmlspecialchars($currentHero) ?>" placeholder="Atau paste URL foto..." oninput="document.getElementById('heroPreviewImg').src = this.value || '<?= htmlspecialchars($currentHero) ?>'" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs font-mono text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- BAGIAN 2: 3 LANGKAH CARA IKUT MABAR -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <div>
                  <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900">
                    2. Konten 3 Langkah Alur Ikut Mabar
                  </h4>
                  <p class="text-[11px] text-slate-500 mt-0.5">Teks pada 3 kartu alur pendaftaran peserta di landing page.</p>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600 font-bold">Alur Ikut</span>
              </div>

              <!-- Langkah 1 -->
              <div class="p-3 bg-white border border-slate-200 rounded-md space-y-2">
                <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                  <span class="w-5 h-5 rounded bg-slate-900 text-amber-400 font-mono text-[10px] flex items-center justify-center font-bold">01</span>
                  <span>Langkah 1 (Pilih Jadwal)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <input type="text" name="step_1_title" required value="<?= htmlspecialchars(!empty($settings['step_1_title']) ? $settings['step_1_title'] : 'Pilih Jadwal & Lokasi GOR') ?>" placeholder="Judul Langkah 1" class="w-full bg-slate-50 border border-slate-300 rounded px-2.5 py-1.5 text-xs font-bold text-slate-900">
                  <input type="text" name="step_1_desc" required value="<?= htmlspecialchars(!empty($settings['step_1_desc']) ? $settings['step_1_desc'] : 'Lihat daftar sesi mingguan kami di bawah. Pilih hari, jam main, dan lokasi GOR yang paling dekat dengan domisili Anda.') ?>" placeholder="Penjelasan Langkah 1" class="w-full bg-slate-50 border border-slate-300 rounded px-2.5 py-1.5 text-xs text-slate-900">
                </div>
              </div>

              <!-- Langkah 2 -->
              <div class="p-3 bg-white border border-slate-200 rounded-md space-y-2">
                <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                  <span class="w-5 h-5 rounded bg-slate-900 text-amber-400 font-mono text-[10px] flex items-center justify-center font-bold">02</span>
                  <span>Langkah 2 (Kontak PIC & Bayar)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <input type="text" name="step_2_title" required value="<?= htmlspecialchars(!empty($settings['step_2_title']) ? $settings['step_2_title'] : 'Hubungi PIC & Bayar Iuran') ?>" placeholder="Judul Langkah 2" class="w-full bg-slate-50 border border-slate-300 rounded px-2.5 py-1.5 text-xs font-bold text-slate-900">
                  <input type="text" name="step_2_desc" required value="<?= htmlspecialchars(!empty($settings['step_2_desc']) ? $settings['step_2_desc'] : 'Klik link daftar pada jadwal untuk langsung terhubung dengan PIC sesi di WhatsApp guna konfirmasi slot dan menyelesaikan pembayaran iuran.') ?>" placeholder="Penjelasan Langkah 2" class="w-full bg-slate-50 border border-slate-300 rounded px-2.5 py-1.5 text-xs text-slate-900">
                </div>
              </div>

              <!-- Langkah 3 -->
              <div class="p-3 bg-white border border-slate-200 rounded-md space-y-2">
                <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                  <span class="w-5 h-5 rounded bg-slate-900 text-amber-400 font-mono text-[10px] flex items-center justify-center font-bold">03</span>
                  <span>Langkah 3 (Main & Rotasi)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <input type="text" name="step_3_title" required value="<?= htmlspecialchars(!empty($settings['step_3_title']) ? $settings['step_3_title'] : 'Datang & Main Sesuai Rotasi') ?>" placeholder="Judul Langkah 3" class="w-full bg-slate-50 border border-slate-300 rounded px-2.5 py-1.5 text-xs font-bold text-slate-900">
                  <input type="text" name="step_3_desc" required value="<?= htmlspecialchars(!empty($settings['step_3_desc']) ? $settings['step_3_desc'] : 'Cukup bawa raket pribadi dan tiba di lapangan tepat waktu. Kok bulu angsa berkualitas dan rotasi ganda sudah diatur oleh sistem aplikasi.') ?>" placeholder="Penjelasan Langkah 3" class="w-full bg-slate-50 border border-slate-300 rounded px-2.5 py-1.5 text-xs text-slate-900">
                </div>
              </div>
            </div>

            <!-- BAGIAN 3: TANYA JAWAB (FAQ SEPUTAR MABAR) -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <div>
                  <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900">
                    3. Pertanyaan & Jawaban (FAQ Accordion)
                  </h4>
                  <p class="text-[11px] text-slate-500 mt-0.5">Kelola poin tanya jawab yang sering ditanyakan peserta baru.</p>
                </div>
                <button type="button" onclick="addFaqRow()" class="px-2.5 py-1 rounded bg-slate-900 text-amber-400 text-xs font-bold hover:bg-slate-800 transition-colors">
                  + Tambah FAQ
                </button>
              </div>

              <div id="faqContainer" class="space-y-3">
                <?php foreach ($currentFaqs as $idx => $faq): ?>
                  <div class="faq-row p-3.5 bg-white border border-slate-200 rounded-md space-y-2">
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-xs font-bold text-slate-700 font-mono">FAQ #<?= $idx + 1 ?></span>
                      <button type="button" onclick="this.closest('.faq-row').remove()" class="text-rose-600 hover:text-rose-800 text-xs font-bold">Hapus</button>
                    </div>
                    <div>
                      <input type="text" name="faq_questions[]" required value="<?= htmlspecialchars($faq['question']) ?>" placeholder="Tuliskan pertanyaan..." class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-xs font-bold text-slate-900">
                    </div>
                    <div>
                      <textarea name="faq_answers[]" rows="2" required placeholder="Tuliskan jawaban lengkap..." class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-900"><?= htmlspecialchars($faq['answer']) ?></textarea>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- BAGIAN 4: IDENTITAS BRAND, LOGO & KONTAK -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <div>
                  <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-900">
                    4. Profil Komunitas & Identitas Brand
                  </h4>
                  <p class="text-[11px] text-slate-500 mt-0.5">Logo, nama komunitas, sosial media, dan informasi kontak resmi.</p>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600 font-bold">Branding</span>
              </div>

              <!-- Logo File & URL -->
              <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                <div class="sm:col-span-3 flex flex-col items-center">
                  <div class="w-20 h-20 rounded-md overflow-hidden border border-slate-300 bg-white flex items-center justify-center p-1 shadow-xs">
                    <img id="logoPreviewImg" src="<?= htmlspecialchars($currentLogo ? $currentLogo : '') ?>" alt="Logo Komunitas" class="<?= empty($currentLogo) ? 'hidden' : '' ?> w-full h-full object-contain">
                    <span id="logoPlaceholder" class="<?= !empty($currentLogo) ? 'hidden' : '' ?> text-xs font-black font-mono text-blue-900 bg-amber-100/80 w-full h-full rounded flex items-center justify-center">
                      PB
                    </span>
                  </div>
                  <span class="text-[10px] text-slate-400 mt-1 font-mono">Logo Komunitas</span>
                </div>

                <div class="sm:col-span-9 space-y-2">
                  <input type="file" name="logo_file" accept="image/*" onchange="previewLogoFile(this)" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-700 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white cursor-pointer">
                  <input type="text" name="logo_url" id="logoUrlInput" value="<?= htmlspecialchars($currentLogo) ?>" placeholder="Atau URL logo..." oninput="updateLogoPreview(this.value)" class="w-full bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs font-mono text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Nama Komunitas:</label>
                  <input type="text" name="community_name" required value="<?= htmlspecialchars($settings['community_name']) ?>" placeholder="Contoh: Smash Club Badminton" class="w-full bg-white border border-slate-300 rounded-md px-3 py-2 text-xs font-bold text-slate-900">
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Slogan / Tagline Komunitas:</label>
                  <input type="text" name="tagline" required value="<?= htmlspecialchars($settings['tagline']) ?>" placeholder="Contoh: Komunitas Badminton Terbuka & Mabar Rutin" class="w-full bg-white border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900">
                </div>
              </div>

              <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-md text-xs text-amber-950 leading-relaxed">
                <span class="font-bold text-amber-900">💡 Pengaturan Kota & Profil Komunitas:</span> Mengubah <strong>Kota / Basis Area</strong> dan <strong>Deskripsi Footer</strong> di bawah ini akan secara otomatis memperbarui seluruh teks kota di website publik, galeri foto, meta tag SEO, judul Cabang GOR di footer, serta structured data komunitas.
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Deskripsi Footer Landing Page (Tentang Komunitas):</label>
                <textarea name="description" rows="3" placeholder="Deskripsi singkat profil dan kegiatan komunitas badminton Anda..." class="w-full bg-white border border-slate-300 rounded-md px-3 py-2.5 text-xs text-slate-900 leading-relaxed focus:outline-none focus:ring-1 focus:ring-slate-900"><?= htmlspecialchars($settings['description']) ?></textarea>
                <p class="text-[11px] text-slate-500 mt-1">Teks profil komunitas yang tampil di kolom kiri footer landing page.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Instagram (@handle):</label>
                  <input type="text" name="instagram" value="<?= htmlspecialchars($settings['instagram']) ?>" placeholder="Contoh: @namakomunitas" class="w-full bg-white border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp Default PIC:</label>
                  <input type="text" name="whatsapp_default" value="<?= htmlspecialchars($settings['whatsapp_default']) ?>" placeholder="Contoh: 081234567890" class="w-full bg-white border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900">
                </div>
                <div>
                  <label class="block text-xs font-bold text-amber-700 mb-1">Kota / Basis Wilayah: <span class="text-red-500">*</span></label>
                  <input type="text" name="base_city" required value="<?= htmlspecialchars($settings['base_city']) ?>" placeholder="Contoh: Jakarta Selatan, Surabaya, Bandung" class="w-full bg-white border border-amber-300 focus:border-amber-500 rounded-md px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none focus:ring-1 focus:ring-amber-500 shadow-xs">
                  <p class="text-[11px] text-slate-500 mt-1">Ditampilkan di footer "Cabang GOR [Kota]" & SEO.</p>
                </div>
              </div>
            </div>

            <!-- BAGIAN 5: YOAST SEO & SEARCH ENGINE OPTIMIZATION MANAGER -->
            <?php
              $siteHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'smashpoint.test';
              $currentOgImage = !empty($settings['og_image_url']) ? $settings['og_image_url'] : $currentHero;
            ?>
            <div class="bg-gradient-to-br from-slate-50 to-emerald-50/20 border-2 border-emerald-500/80 rounded-xl p-5 sm:p-6 space-y-6 shadow-sm">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200/80 pb-4 gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded bg-emerald-600 text-white flex items-center justify-center font-black text-xs shadow-xs">Y</span>
                    <h4 class="text-sm font-black uppercase tracking-wider text-slate-900 font-sans">
                      5. Optimasi Mesin Pencari Google & SEO (Yoast-Style)
                    </h4>
                  </div>
                  <p class="text-[11px] text-slate-500 mt-1">Kelola judul Google, deskripsi meta, kata kunci fokus, dan pratinjau thumbnail saat tautan dibagikan ke WhatsApp & media sosial.</p>
                </div>
                
                <!-- Yoast Overall SEO Health Score Badge -->
                <div class="flex items-center gap-2">
                  <div id="yoastScoreBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black shadow-xs bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span id="yoastScoreDot" class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span id="yoastScoreText">SEO: 100/100 (Sangat Baik)</span>
                  </div>
                </div>
              </div>

              <!-- Focus Keyphrase Input -->
              <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1.5">
                  <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <span class="text-amber-500 font-bold">★</span>
                    <span>Kata Kunci Target / Focus Keyphrase:</span>
                  </label>
                  <span class="text-[10px] text-slate-400 font-mono">Kata kunci pencarian yang ingin dimenangkan di Google</span>
                </div>
                <div class="relative">
                  <input type="text" id="seoFocusKeyword" name="focus_keyword" value="<?= htmlspecialchars(!empty($settings['focus_keyword']) ? $settings['focus_keyword'] : ('mabar badminton ' . strtolower(!empty($settings['base_city']) ? $settings['base_city'] : 'indonesia'))) ?>" placeholder="Misal: mabar badminton jakarta selatan" class="w-full bg-slate-50 border border-slate-300 focus:border-emerald-600 focus:bg-white rounded-md px-3.5 py-2 text-xs text-slate-900 font-semibold focus:outline-none transition-all">
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                  Yoast SEO akan menganalisis apakah kata kunci ini terdapat di judul utama (SEO Title) dan meta deskripsi untuk memastikan visibilitas pencarian optimal di Google.
                </p>
              </div>

              <!-- Preview Tabs: Google Search Result vs WhatsApp / Social Share Card -->
              <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                  <div class="flex items-center gap-2">
                    <button type="button" id="tabGoogleBtn" onclick="switchSeoPreviewTab('google')" class="px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-[#0b1e36] text-amber-400 shadow-xs flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                      <span>Google Search Snippet</span>
                    </button>
                    <button type="button" id="tabSocialBtn" onclick="switchSeoPreviewTab('social')" class="px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/></svg>
                      <span>WhatsApp & Medsos Card</span>
                    </button>
                  </div>

                  <!-- Google Mobile vs Desktop Toggle -->
                  <div id="googleViewToggle" class="flex items-center gap-1 bg-slate-200/80 p-0.5 rounded-md text-[10px] font-bold">
                    <button type="button" id="btnViewDesktop" onclick="setGoogleViewMode('desktop')" class="px-2.5 py-1 rounded bg-white text-slate-900 shadow-2xs">Desktop</button>
                    <button type="button" id="btnViewMobile" onclick="setGoogleViewMode('mobile')" class="px-2.5 py-1 rounded text-slate-600 hover:text-slate-900">Mobile</button>
                  </div>
                </div>

                <!-- Google Snippet Simulation Container -->
                <div id="googlePreviewContainer" class="p-4 sm:p-5 bg-white border border-slate-200 rounded-lg shadow-sm">
                  <div id="googleSnippetCard" class="max-w-2xl font-sans text-left space-y-1">
                    <!-- URL & Breadcrumb (Google Style) -->
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                      <div class="w-5 h-5 rounded-full bg-slate-100 border border-slate-300 flex items-center justify-center text-[10px] text-blue-900 font-bold shrink-0">
                        🏸
                      </div>
                      <div class="truncate">
                        <span class="text-xs font-semibold text-slate-900"><?= htmlspecialchars($siteHost) ?></span>
                        <span class="text-slate-400 mx-1">›</span>
                        <span id="googleSlugPreview" class="text-slate-500 font-mono text-[11px]"><?= strtolower(urlencode(!empty($settings['base_city']) ? $settings['base_city'] : 'komunitas')) ?> › mabar</span>
                      </div>
                    </div>
                    
                    <!-- Search Title Link -->
                    <div class="pt-0.5">
                      <a href="javascript:void(0)" id="googleTitlePreview" class="text-base sm:text-lg font-medium text-[#1a0dab] hover:underline cursor-pointer leading-snug line-clamp-2">
                        <?= htmlspecialchars(!empty($settings['meta_title']) ? $settings['meta_title'] : ($settings['community_name'] . ' — Mabar Badminton & Rotasi Lapangan ' . (!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia'))) ?>
                      </a>
                    </div>

                    <!-- Meta Description -->
                    <div class="pt-0.5">
                      <p id="googleDescPreview" class="text-xs sm:text-[13px] text-[#4d5156] leading-relaxed line-clamp-3">
                        <?= htmlspecialchars(!empty($settings['meta_description']) ? $settings['meta_description'] : (!empty($settings['description']) ? strip_tags($settings['description']) : ('Komunitas mabar badminton resmi di ' . (!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia') . ' dengan sistem rotasi ganda fair level, jadwal rutin setiap pekan, dan kok bulu angsa turnamen.'))) ?>
                      </p>
                    </div>
                  </div>
                </div>

                <!-- WhatsApp / Facebook Share Preview Container -->
                <div id="socialPreviewContainer" class="hidden p-4 sm:p-5 bg-white border border-slate-200 rounded-lg shadow-sm">
                  <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2 font-mono">Pratinjau Tampilan Saat Link Dishare di WhatsApp / Facebook:</span>
                  <div class="max-w-md bg-slate-50 border border-slate-300 rounded-lg overflow-hidden shadow-sm">
                    <div class="aspect-[1.91/1] bg-slate-200 relative overflow-hidden">
                      <img id="socialPreviewImg" src="<?= htmlspecialchars($currentOgImage) ?>" alt="Social Banner Preview" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3 bg-white border-t border-slate-200 space-y-1">
                      <div class="text-[10px] font-mono uppercase tracking-wider text-slate-400 truncate"><?= htmlspecialchars(strtoupper($siteHost)) ?></div>
                      <h5 id="socialTitlePreview" class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-2">
                        <?= htmlspecialchars(!empty($settings['og_title']) ? $settings['og_title'] : (!empty($settings['meta_title']) ? $settings['meta_title'] : $settings['community_name'])) ?>
                      </h5>
                      <p id="socialDescPreview" class="text-[11px] text-slate-500 line-clamp-2">
                        <?= htmlspecialchars(!empty($settings['og_description']) ? $settings['og_description'] : (!empty($settings['meta_description']) ? $settings['meta_description'] : $settings['tagline'])) ?>
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- SEO Meta Title Input & Live Snippet Variables -->
              <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                  <label class="text-xs font-bold text-slate-800">
                    SEO Meta Title (Judul Pencarian Google): <span class="text-red-500">*</span>
                  </label>
                  
                  <!-- Snippet Variable Insertion Chips -->
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[10px] text-slate-400">Sisipkan Variabel:</span>
                    <button type="button" onclick="insertSeoVariable('seoTitleInput', '%%sitename%%')" class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-900 text-[10px] font-bold font-mono border border-amber-200 transition-colors">+ Judul Situs</button>
                    <button type="button" onclick="insertSeoVariable('seoTitleInput', '%%city%%')" class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-900 text-[10px] font-bold font-mono border border-blue-200 transition-colors">+ Kota</button>
                    <button type="button" onclick="insertSeoVariable('seoTitleInput', '%%tagline%%')" class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-900 text-[10px] font-bold font-mono border border-emerald-200 transition-colors">+ Tagline</button>
                    <button type="button" onclick="insertSeoVariable('seoTitleInput', '%%sep%%')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold font-mono border border-slate-300 transition-colors">+ Sep (—)</button>
                  </div>
                </div>

                <input type="text" id="seoTitleInput" name="meta_title" required value="<?= htmlspecialchars(!empty($settings['meta_title']) ? $settings['meta_title'] : ($settings['community_name'] . ' — Mabar Badminton & Rotasi Lapangan ' . (!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia'))) ?>" placeholder="%%sitename%% %%sep%% Mabar Badminton & Rotasi Lapangan %%city%%" class="w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-md px-3.5 py-2.5 text-xs text-slate-900 font-bold focus:outline-none focus:ring-1 focus:ring-emerald-600 shadow-2xs">

                <!-- Yoast Progress Bar for Title -->
                <div class="space-y-1">
                  <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                    <div id="titleProgressBar" class="h-full bg-emerald-500 transition-all duration-300" style="width: 80%;"></div>
                  </div>
                  <div class="flex items-center justify-between text-[10px]">
                    <span id="titleStatusText" class="text-emerald-700 font-bold">Panjang judul optimal untuk Google.</span>
                    <span id="titleCharCount" class="font-mono text-slate-500 font-bold">54 / 60 karakter</span>
                  </div>
                </div>
              </div>

              <!-- SEO Meta Description Input -->
              <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                  <label class="text-xs font-bold text-slate-800">
                    SEO Meta Description (Deskripsi Cuplikan Snippet): <span class="text-red-500">*</span>
                  </label>
                  
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[10px] text-slate-400">Sisipkan:</span>
                    <button type="button" onclick="insertSeoVariable('seoDescInput', '%%sitename%%')" class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-900 text-[10px] font-bold font-mono border border-amber-200 transition-colors">+ Judul</button>
                    <button type="button" onclick="insertSeoVariable('seoDescInput', '%%city%%')" class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-900 text-[10px] font-bold font-mono border border-blue-200 transition-colors">+ Kota</button>
                    <button type="button" onclick="insertSeoVariable('seoDescInput', '%%tagline%%')" class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-900 text-[10px] font-bold font-mono border border-emerald-200 transition-colors">+ Tagline</button>
                  </div>
                </div>

                <textarea id="seoDescInput" name="meta_description" rows="3" required placeholder="Tuliskan rangkuman menarik tentang komunitas mabar badminton Anda..." class="w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-md px-3.5 py-2.5 text-xs text-slate-900 leading-relaxed focus:outline-none focus:ring-1 focus:ring-emerald-600 shadow-2xs"><?= htmlspecialchars(!empty($settings['meta_description']) ? $settings['meta_description'] : (!empty($settings['description']) ? strip_tags($settings['description']) : ('Komunitas mabar badminton resmi di ' . (!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia') . ' dengan sistem rotasi ganda fair level, jadwal rutin setiap pekan, dan kok bulu angsa turnamen.'))) ?></textarea>

                <!-- Yoast Progress Bar for Description -->
                <div class="space-y-1">
                  <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                    <div id="descProgressBar" class="h-full bg-emerald-500 transition-all duration-300" style="width: 85%;"></div>
                  </div>
                  <div class="flex items-center justify-between text-[10px]">
                    <span id="descStatusText" class="text-emerald-700 font-bold">Panjang deskripsi meta optimal (120-160 karakter).</span>
                    <span id="descCharCount" class="font-mono text-slate-500 font-bold">148 / 160 karakter</span>
                  </div>
                </div>
              </div>

              <!-- Yoast SEO Real-time Content & Health Analysis Checklist -->
              <div class="bg-white border border-slate-200 rounded-lg p-4 sm:p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-900 font-mono">Hasil Analisis Yoast SEO</span>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold">Audit Real-Time</span>
                  </div>
                  <span id="yoastScoreSummary" class="text-xs font-bold text-emerald-700">6 dari 6 kriteria terpenuhi</span>
                </div>

                <div class="space-y-2 text-xs" id="yoastChecklist">
                  <div id="checkKwTitle" class="flex items-start gap-2.5 py-1">
                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                    <span class="text-slate-700"><strong>Kata Kunci Target di Judul SEO:</strong> Kata kunci fokus ditemukan di judul Google Anda.</span>
                  </div>
                  <div id="checkKwDesc" class="flex items-start gap-2.5 py-1">
                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                    <span class="text-slate-700"><strong>Kata Kunci Target di Deskripsi:</strong> Kata kunci fokus tercantum di meta description.</span>
                  </div>
                  <div id="checkTitleLength" class="flex items-start gap-2.5 py-1">
                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                    <span class="text-slate-700"><strong>Panjang Judul SEO:</strong> Ukuran judul berada dalam rekomendasi ideal 40-65 karakter.</span>
                  </div>
                  <div id="checkDescLength" class="flex items-start gap-2.5 py-1">
                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                    <span class="text-slate-700"><strong>Panjang Meta Description:</strong> Ukuran deskripsi berada dalam rekomendasi ideal 120-160 karakter.</span>
                  </div>
                  <div id="checkOgImage" class="flex items-start gap-2.5 py-1">
                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                    <span class="text-slate-700"><strong>Banner OpenGraph / Media Sosial:</strong> Thumbnail share untuk WhatsApp & Facebook siap tampil.</span>
                  </div>
                  <div id="checkRobots" class="flex items-start gap-2.5 py-1">
                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                    <span class="text-slate-700"><strong>Pengaturan Indexing:</strong> Robot Google diizinkan mengindeks halaman ini (`index, follow`).</span>
                  </div>
                </div>
              </div>

              <!-- OpenGraph & Media Sosial Specific Customizations (Custom Title, Desc, and Banner) -->
              <div class="bg-white border border-slate-200 rounded-lg p-4 sm:p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                  <h5 class="text-xs font-black uppercase tracking-wider text-slate-900 font-mono flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    <span>Kustomisasi OpenGraph Media Sosial & WhatsApp</span>
                  </h5>
                  <span class="text-[10px] text-slate-400">Opsional (Default otomatis dari SEO Google)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Judul Khusus Medsos (OG Title):</label>
                    <input type="text" id="ogTitleInput" name="og_title" value="<?= htmlspecialchars(!empty($settings['og_title']) ? $settings['og_title'] : '') ?>" placeholder="Kosongkan jika sama dengan Judul SEO Google..." class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900">
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Khusus Medsos (OG Description):</label>
                    <input type="text" id="ogDescInput" name="og_description" value="<?= htmlspecialchars(!empty($settings['og_description']) ? $settings['og_description'] : '') ?>" placeholder="Kosongkan jika sama dengan Deskripsi SEO Google..." class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900">
                  </div>
                </div>

                <!-- Banner Share Upload -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Gambar Banner Thumbnail Share (Ukuran Ideal 1200 x 630 px):</label>
                  <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    <div class="sm:col-span-3">
                      <div class="aspect-[1.91/1] rounded border border-slate-300 overflow-hidden bg-slate-100">
                        <img id="ogPreviewThumb" src="<?= htmlspecialchars($currentOgImage) ?>" alt="OG Banner" class="w-full h-full object-cover">
                      </div>
                    </div>
                    <div class="sm:col-span-9 space-y-2">
                      <input type="file" name="og_image_file" accept="image/*" onchange="previewFile(this, 'ogPreviewThumb'); previewFile(this, 'socialPreviewImg');" class="w-full bg-white border border-slate-300 rounded px-2.5 py-1 text-xs file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white cursor-pointer">
                      <input type="url" id="ogUrlInput" name="og_image_url" value="<?= htmlspecialchars(!empty($settings['og_image_url']) ? $settings['og_image_url'] : '') ?>" placeholder="Atau paste URL gambar..." oninput="document.getElementById('ogPreviewThumb').src = this.value; document.getElementById('socialPreviewImg').src = this.value;" class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-xs font-mono text-slate-900">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Advanced SEO Accordion -->
              <details class="bg-white border border-slate-200 rounded-lg group shadow-xs">
                <summary class="p-4 text-xs font-bold text-slate-800 cursor-pointer flex items-center justify-between list-none hover:bg-slate-50 transition-colors">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Pengaturan SEO Tingkat Lanjut (Robots Indexing & Canonical)</span>
                  </div>
                  <span class="text-xs text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                
                <div class="p-4 border-t border-slate-100 space-y-3 bg-slate-50/50">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1">Pengaturan Robot Pencari (Robots Meta Tag):</label>
                      <select id="seoRobotsSelect" name="robots_meta" class="w-full bg-white border border-slate-300 rounded px-3 py-2 text-xs font-bold text-slate-800">
                        <option value="index, follow" <?= (empty($settings['robots_meta']) || $settings['robots_meta'] === 'index, follow') ? 'selected' : '' ?>>Izinkan Google mengindeks & mengikuti link (index, follow - Direkomendasikan)</option>
                        <option value="noindex, follow" <?= ($settings['robots_meta'] === 'noindex, follow') ? 'selected' : '' ?>>Jangan indeks di Google, tapi ikuti link (noindex, follow)</option>
                        <option value="noindex, nofollow" <?= ($settings['robots_meta'] === 'noindex, nofollow') ? 'selected' : '' ?>>Blokir sepenuhnya dari pencarian (noindex, nofollow)</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1">Canonical URL Kustom (Opsional):</label>
                      <input type="url" name="canonical_url" value="<?= htmlspecialchars(!empty($settings['canonical_url']) ? $settings['canonical_url'] : '') ?>" placeholder="https://domainanda.com/" class="w-full bg-white border border-slate-300 rounded px-3 py-2 text-xs font-mono text-slate-800">
                      <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan jika menggunakan domain default.</span>
                    </div>
                  </div>

                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Kunci Meta Tambahan (Meta Keywords):</label>
                    <input type="text" name="meta_keywords" value="<?= htmlspecialchars(!empty($settings['meta_keywords']) ? $settings['meta_keywords'] : '') ?>" placeholder="Pisahkan dengan koma: mabar badminton, gor bulutangkis, sewa court..." class="w-full bg-white border border-slate-300 rounded px-3 py-2 text-xs text-slate-800">
                  </div>
                </div>
              </details>

            </div>

            <div class="pt-4 border-t border-slate-200">
              <button type="submit" class="w-full py-3.5 rounded-md bg-[#0b1e36] hover:bg-[#132b4f] text-amber-400 border border-amber-400/40 font-black text-xs uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2">
                <span>Simpan Seluruh Perubahan Konten & Pengaturan SEO</span>
              </button>
            </div>
          </form>
        </div>

        <script>
          function previewFile(input, targetId) {
            if (input.files && input.files[0]) {
              const reader = new FileReader();
              reader.onload = function(e) {
                const img = document.getElementById(targetId);
                if (img) img.src = e.target.result;
              }
              reader.readAsDataURL(input.files[0]);
            }
          }

          function previewLogoFile(input) {
            if (input.files && input.files[0]) {
              const reader = new FileReader();
              reader.onload = function(e) {
                const img = document.getElementById('logoPreviewImg');
                const placeholder = document.getElementById('logoPlaceholder');
                if (img) {
                  img.src = e.target.result;
                  img.classList.remove('hidden');
                }
                if (placeholder) placeholder.classList.add('hidden');
              }
              reader.readAsDataURL(input.files[0]);
            }
          }

          function updateLogoPreview(val) {
            const img = document.getElementById('logoPreviewImg');
            const placeholder = document.getElementById('logoPlaceholder');
            if (val && val.trim() !== '') {
              img.src = val;
              img.classList.remove('hidden');
              placeholder.classList.add('hidden');
            } else {
              img.classList.add('hidden');
              placeholder.classList.remove('hidden');
            }
          }

          function addFaqRow() {
            const container = document.getElementById('faqContainer');
            const count = container.querySelectorAll('.faq-row').length + 1;
            const div = document.createElement('div');
            div.className = 'faq-row p-3.5 bg-white border border-slate-200 rounded-md space-y-2';
            div.innerHTML = `
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-700 font-mono">FAQ #${count} (Baru)</span>
                <button type="button" onclick="this.closest('.faq-row').remove()" class="text-rose-600 hover:text-rose-800 text-xs font-bold">Hapus</button>
              </div>
              <div>
                <input type="text" name="faq_questions[]" required placeholder="Tuliskan pertanyaan..." class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-xs font-bold text-slate-900">
              </div>
              <div>
                <textarea name="faq_answers[]" rows="2" required placeholder="Tuliskan jawaban lengkap..." class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-900"></textarea>
              </div>
            `;
            container.appendChild(div);
          }

          // ==========================================
          // YOAST SEO INTERACTIVE ENGINE
          // ==========================================
          function insertSeoVariable(inputId, variable) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const start = input.selectionStart || input.value.length;
            const end = input.selectionEnd || input.value.length;
            const text = input.value;
            input.value = text.substring(0, start) + ' ' + variable + ' ' + text.substring(end);
            input.value = input.value.replace(/\s+/g, ' ').trim();
            input.focus();
            input.selectionStart = input.selectionEnd = start + variable.length + 2;
            evaluateYoastSeo();
          }

          function switchSeoPreviewTab(tab) {
            const googleTab = document.getElementById('googlePreviewContainer');
            const socialTab = document.getElementById('socialPreviewContainer');
            const btnGoogle = document.getElementById('tabGoogleBtn');
            const btnSocial = document.getElementById('tabSocialBtn');
            const toggle = document.getElementById('googleViewToggle');

            if (tab === 'google') {
              if (googleTab) googleTab.classList.remove('hidden');
              if (socialTab) socialTab.classList.add('hidden');
              if (toggle) toggle.classList.remove('hidden');
              if (btnGoogle) btnGoogle.className = 'px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-[#0b1e36] text-amber-400 shadow-xs flex items-center gap-1.5';
              if (btnSocial) btnSocial.className = 'px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center gap-1.5';
            } else {
              if (googleTab) googleTab.classList.add('hidden');
              if (socialTab) socialTab.classList.remove('hidden');
              if (toggle) toggle.classList.add('hidden');
              if (btnSocial) btnSocial.className = 'px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-[#0b1e36] text-amber-400 shadow-xs flex items-center gap-1.5';
              if (btnGoogle) btnGoogle.className = 'px-3.5 py-1.5 rounded-md text-xs font-bold transition-all bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center gap-1.5';
            }
          }

          function setGoogleViewMode(mode) {
            const card = document.getElementById('googleSnippetCard');
            const btnD = document.getElementById('btnViewDesktop');
            const btnM = document.getElementById('btnViewMobile');
            if (mode === 'mobile') {
              if (card) {
                card.classList.add('max-w-xs', 'border', 'border-slate-200', 'p-3', 'rounded-lg', 'bg-white', 'shadow-xs');
                card.classList.remove('max-w-2xl');
              }
              if (btnM) btnM.className = 'px-2.5 py-1 rounded bg-white text-slate-900 shadow-2xs font-bold';
              if (btnD) btnD.className = 'px-2.5 py-1 rounded text-slate-600 hover:text-slate-900 font-bold';
            } else {
              if (card) {
                card.classList.remove('max-w-xs', 'border', 'border-slate-200', 'p-3', 'rounded-lg', 'bg-white', 'shadow-xs');
                card.classList.add('max-w-2xl');
              }
              if (btnD) btnD.className = 'px-2.5 py-1 rounded bg-white text-slate-900 shadow-2xs font-bold';
              if (btnM) btnM.className = 'px-2.5 py-1 rounded text-slate-600 hover:text-slate-900 font-bold';
            }
          }

          function resolveSeoTemplate(str) {
            if (!str) return '';
            const commName = document.querySelector('input[name="community_name"]')?.value || 'Komunitas Badminton';
            const city = document.querySelector('input[name="base_city"]')?.value || 'Indonesia';
            const tagline = document.querySelector('input[name="tagline"]')?.value || '';
            return str
              .replace(/%%sitename%%/gi, commName)
              .replace(/%%title%%/gi, commName)
              .replace(/%%city%%/gi, city)
              .replace(/%%tagline%%/gi, tagline)
              .replace(/%%sep%%/gi, '—')
              .replace(/%%year%%/gi, new Date().getFullYear())
              .replace(/\s+/g, ' ')
              .trim();
          }

          function evaluateYoastSeo() {
            const focusKw = (document.getElementById('seoFocusKeyword')?.value || '').trim().toLowerCase();
            const rawTitle = document.getElementById('seoTitleInput')?.value || '';
            const rawDesc = document.getElementById('seoDescInput')?.value || '';
            const resolvedTitle = resolveSeoTemplate(rawTitle);
            const resolvedDesc = resolveSeoTemplate(rawDesc);
            const ogTitle = document.getElementById('ogTitleInput')?.value.trim() || resolvedTitle;
            const ogDesc = document.getElementById('ogDescInput')?.value.trim() || resolvedDesc;
            const robotsVal = document.getElementById('seoRobotsSelect')?.value || 'index, follow';
            const cityVal = document.querySelector('input[name="base_city"]')?.value || 'komunitas';

            // 1. Update Google Snippet Text
            const titleEl = document.getElementById('googleTitlePreview');
            const descEl = document.getElementById('googleDescPreview');
            const socialTitleEl = document.getElementById('socialTitlePreview');
            const socialDescEl = document.getElementById('socialDescPreview');
            const slugEl = document.getElementById('googleSlugPreview');

            if (slugEl) slugEl.textContent = encodeURIComponent(cityVal.toLowerCase()) + ' › mabar';
            if (titleEl) titleEl.textContent = resolvedTitle || 'Judul Halaman Belum Diatur';
            if (descEl) descEl.textContent = resolvedDesc || 'Deskripsi halaman belum diatur.';
            if (socialTitleEl) socialTitleEl.textContent = ogTitle || resolvedTitle;
            if (socialDescEl) socialDescEl.textContent = ogDesc || resolvedDesc;

            // 2. Title length evaluation (ideal: 40-65 chars)
            const titleLen = resolvedTitle.length;
            const titleBar = document.getElementById('titleProgressBar');
            const titleStatus = document.getElementById('titleStatusText');
            const titleCount = document.getElementById('titleCharCount');
            let titleScore = 0;

            if (titleCount) titleCount.textContent = `${titleLen} / 60 karakter`;
            const titlePct = Math.min(100, Math.round((titleLen / 60) * 100));
            if (titleBar) titleBar.style.width = `${titlePct}%`;

            if (titleLen === 0) {
              if (titleBar) titleBar.className = 'h-full bg-slate-300 transition-all duration-300';
              if (titleStatus) { titleStatus.textContent = 'Judul masih kosong.'; titleStatus.className = 'text-rose-600 font-bold'; }
            } else if (titleLen < 35) {
              if (titleBar) titleBar.className = 'h-full bg-amber-500 transition-all duration-300';
              if (titleStatus) { titleStatus.textContent = 'Terlalu pendek (rekomendasi: 40 - 65 karakter).'; titleStatus.className = 'text-amber-600 font-bold'; }
              titleScore = 15;
            } else if (titleLen <= 65) {
              if (titleBar) titleBar.className = 'h-full bg-emerald-500 transition-all duration-300';
              if (titleStatus) { titleStatus.textContent = 'Panjang judul optimal untuk Google.'; titleStatus.className = 'text-emerald-700 font-bold'; }
              titleScore = 25;
            } else {
              if (titleBar) titleBar.className = 'h-full bg-rose-500 transition-all duration-300';
              if (titleStatus) { titleStatus.textContent = 'Terlalu panjang (Google akan memotong dengan ...).'; titleStatus.className = 'text-rose-600 font-bold'; }
              titleScore = 10;
            }

            // 3. Description length evaluation (ideal: 120-160 chars)
            const descLen = resolvedDesc.length;
            const descBar = document.getElementById('descProgressBar');
            const descStatus = document.getElementById('descStatusText');
            const descCount = document.getElementById('descCharCount');
            let descScore = 0;

            if (descCount) descCount.textContent = `${descLen} / 160 karakter`;
            const descPct = Math.min(100, Math.round((descLen / 160) * 100));
            if (descBar) descBar.style.width = `${descPct}%`;

            if (descLen === 0) {
              if (descBar) descBar.className = 'h-full bg-slate-300 transition-all duration-300';
              if (descStatus) { descStatus.textContent = 'Deskripsi masih kosong.'; descStatus.className = 'text-rose-600 font-bold'; }
            } else if (descLen < 110) {
              if (descBar) descBar.className = 'h-full bg-amber-500 transition-all duration-300';
              if (descStatus) { descStatus.textContent = 'Terlalu singkat (rekomendasi: 120 - 160 karakter).'; descStatus.className = 'text-amber-600 font-bold'; }
              descScore = 15;
            } else if (descLen <= 165) {
              if (descBar) descBar.className = 'h-full bg-emerald-500 transition-all duration-300';
              if (descStatus) { descStatus.textContent = 'Panjang deskripsi meta optimal (120-160 karakter).'; descStatus.className = 'text-emerald-700 font-bold'; }
              descScore = 25;
            } else {
              if (descBar) descBar.className = 'h-full bg-rose-500 transition-all duration-300';
              if (descStatus) { descStatus.textContent = 'Terlalu panjang (Google akan memotong deskripsi).'; descStatus.className = 'text-rose-600 font-bold'; }
              descScore = 10;
            }

            // 4. Focus keyword in Title
            let hasKwInTitle = false;
            if (focusKw && resolvedTitle.toLowerCase().includes(focusKw)) {
              hasKwInTitle = true;
            }
            updateCheckItem('checkKwTitle', hasKwInTitle, 'Kata Kunci Target di Judul SEO', 'Kata kunci fokus ditemukan di judul Google Anda.', 'Kata kunci fokus belum terdapat di judul SEO.');

            // 5. Focus keyword in Description
            let hasKwInDesc = false;
            if (focusKw && resolvedDesc.toLowerCase().includes(focusKw)) {
              hasKwInDesc = true;
            }
            updateCheckItem('checkKwDesc', hasKwInDesc, 'Kata Kunci Target di Deskripsi', 'Kata kunci fokus tercantum di meta description.', 'Kata kunci fokus belum dimasukkan ke meta description.');

            // 6. Title Length Check Item
            updateCheckItem('checkTitleLength', titleScore === 25, 'Panjang Judul SEO', 'Ukuran judul berada dalam rentang ideal 40-65 karakter.', 'Ukuran judul belum ideal (saat ini ' + titleLen + ' karakter).');

            // 7. Desc Length Check Item
            updateCheckItem('checkDescLength', descScore === 25, 'Panjang Meta Description', 'Ukuran deskripsi berada dalam rentang ideal 120-160 karakter.', 'Ukuran deskripsi belum ideal (saat ini ' + descLen + ' karakter).');

            // 8. OG Image Check Item
            const ogImgSrc = document.getElementById('ogPreviewThumb')?.getAttribute('src') || '';
            const hasOgImage = ogImgSrc.trim() !== '' && !ogImgSrc.includes('placeholder');
            updateCheckItem('checkOgImage', hasOgImage, 'Banner OpenGraph / Media Sosial', 'Thumbnail share untuk WhatsApp & Facebook siap tampil.', 'Thumbnail share media sosial belum dipilih.');

            // 9. Robots Indexing Check
            const isIndexed = robotsVal.includes('index');
            updateCheckItem('checkRobots', isIndexed, 'Pengaturan Indexing', 'Robot Google diizinkan mengindeks halaman ini (`index, follow`).', 'Halaman diset noindex (Google tidak akan menampilkan web ini).');

            // Overall Score Calculation (out of 100)
            let totalScore = 0;
            totalScore += titleScore;
            totalScore += descScore;
            if (hasKwInTitle) totalScore += 15;
            if (hasKwInDesc) totalScore += 15;
            if (hasOgImage) totalScore += 10;
            if (isIndexed) totalScore += 10;

            // Update Score Badge & Summary
            const badge = document.getElementById('yoastScoreBadge');
            const dot = document.getElementById('yoastScoreDot');
            const text = document.getElementById('yoastScoreText');
            const summary = document.getElementById('yoastScoreSummary');

            let passedCount = [hasKwInTitle, hasKwInDesc, titleScore === 25, descScore === 25, hasOgImage, isIndexed].filter(Boolean).length;
            if (summary) summary.textContent = `${passedCount} dari 6 kriteria terpenuhi`;

            if (totalScore >= 80) {
              if (badge) badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black shadow-xs bg-emerald-100 text-emerald-800 border border-emerald-300';
              if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse';
              if (text) text.textContent = `🟢 SEO: ${totalScore}/100 (Sangat Baik)`;
            } else if (totalScore >= 50) {
              if (badge) badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black shadow-xs bg-amber-100 text-amber-800 border border-amber-300';
              if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-amber-600 animate-pulse';
              if (text) text.textContent = `🟡 SEO: ${totalScore}/100 (Cukup Baik)`;
            } else {
              if (badge) badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black shadow-xs bg-rose-100 text-rose-800 border border-rose-300';
              if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-rose-600 animate-pulse';
              if (text) text.textContent = `🔴 SEO: ${totalScore}/100 (Perlu Ditingkatkan)`;
            }
          }

          function updateCheckItem(elementId, isPassed, title, successMsg, failMsg) {
            const el = document.getElementById(elementId);
            if (!el) return;
            if (isPassed) {
              el.innerHTML = `
                <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                <span class="text-slate-700"><strong>${title}:</strong> ${successMsg}</span>
              `;
            } else {
              el.innerHTML = `
                <span class="w-4 h-4 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">&times;</span>
                <span class="text-rose-700"><strong>${title}:</strong> ${failMsg}</span>
              `;
            }
          }

          // Attach event listeners for real-time Yoast SEO evaluation
          ['seoFocusKeyword', 'seoTitleInput', 'seoDescInput', 'ogTitleInput', 'ogDescInput', 'seoRobotsSelect'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
              el.addEventListener('input', evaluateYoastSeo);
              el.addEventListener('change', evaluateYoastSeo);
            }
          });

          ['community_name', 'base_city', 'tagline'].forEach(name => {
            const el = document.querySelector(`input[name="${name}"]`);
            if (el) {
              el.addEventListener('input', evaluateYoastSeo);
              el.addEventListener('change', evaluateYoastSeo);
            }
          });

          // Run initial Yoast SEO evaluation on load
          document.addEventListener('DOMContentLoaded', () => {
            evaluateYoastSeo();
          });
          evaluateYoastSeo();
        </script>

      <?php endif; ?>

    </main>

        <!-- AdminLTE Panel Footer -->
        <footer class="px-4 sm:px-6 lg:px-8 py-3.5 bg-white border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 mt-auto shrink-0">
          <div>
            &copy; <?= date('Y') ?> <strong class="text-slate-700"><?= htmlspecialchars($settings['community_name']) ?></strong> &bull; Panel Administrasi & Operasional
          </div>
          <div class="font-mono text-[11px] text-slate-400">
            Powershoot Control Panel System
          </div>
        </footer>

        <!-- ========================================== -->
        <!-- ANDROID MOBILE BOTTOM NAVIGATION BAR       -->
        <!-- ========================================== -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-xl" style="padding-bottom: max(6px, env(safe-area-inset-bottom));">
          <div class="grid grid-cols-5 items-center text-center py-1">
            <a href="?tab=dashboard" class="flex flex-col items-center justify-center py-1.5 <?= $tab === 'dashboard' ? 'text-[#0b1e36] font-black' : 'text-slate-500 font-semibold' ?>">
              <span class="text-[11px] font-mono leading-none">Ringkas</span>
              <span class="text-[9px] text-slate-400 mt-0.5 font-sans">Panel</span>
            </a>
            <a href="?tab=schedules" class="flex flex-col items-center justify-center py-1.5 <?= ($tab === 'schedules' || $tab === 'schedule_form') ? 'text-[#0b1e36] font-black' : 'text-slate-500 font-semibold' ?>">
              <span class="text-[11px] font-mono leading-none">Jadwal</span>
              <span class="text-[9px] text-slate-400 mt-0.5 font-sans">Sesi GOR</span>
            </a>
            <a href="?tab=roster" class="flex flex-col items-center justify-center py-1.5 <?= $tab === 'roster' ? 'text-[#0b1e36] font-black' : 'text-slate-500 font-semibold' ?>">
              <span class="text-[11px] font-mono leading-none">Roster</span>
              <span class="text-[9px] text-slate-400 mt-0.5 font-sans">Pemain</span>
            </a>
            <a href="?tab=gallery" class="flex flex-col items-center justify-center py-1.5 <?= $tab === 'gallery' ? 'text-[#0b1e36] font-black' : 'text-slate-500 font-semibold' ?>">
              <span class="text-[11px] font-mono leading-none">Galeri</span>
              <span class="text-[9px] text-slate-400 mt-0.5 font-sans">Dokumentasi</span>
            </a>
            <button type="button" onclick="toggleSidebar(true)" class="flex flex-col items-center justify-center py-1.5 text-slate-500 hover:text-slate-900 font-semibold">
              <span class="text-[11px] font-mono leading-none font-black text-amber-600">Menu</span>
              <span class="text-[9px] text-slate-400 mt-0.5 font-sans">Lainnya</span>
            </button>
          </div>
        </nav>

      </div> <!-- /Main Panel Wrapper -->
    </div> <!-- /Full Screen Control Panel Shell -->

    <!-- Mobile Sidebar Drawer Script -->
    <script>
      function toggleSidebar(open) {
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!sidebar || !backdrop) return;
        if (open) {
          sidebar.classList.remove('-translate-x-full');
          backdrop.classList.remove('hidden');
        } else {
          sidebar.classList.add('-translate-x-full');
          backdrop.classList.add('hidden');
        }
      }
    </script>
  <?php endif; ?>

  <!-- Google Maps GOR Autocomplete & Clipboard Copy Script -->
  <script>
    // 1. Curated Database of Popular Badminton GORs in Jakarta
    const POPULAR_GORS = [
      {
        name: "GOR Badminton Arena Utama",
        address: "Jl. Olahraga Sport Center No. 12",
        area: <?= json_encode(!empty($settings['base_city']) ? $settings['base_city'] : 'Jakarta Selatan') ?>,
        maps: "https://maps.google.com/?q=GOR+Badminton+Arena"
      },
      {
        name: "GOR Bintang Badminton Arena",
        address: "Jl. Tebet Barat Dalam Raya No. 45, Tebet Barat, Jakarta Selatan",
        area: "Jakarta Selatan",
        maps: "https://maps.google.com/?q=GOR+Bintang+Badminton+Arena+Tebet"
      },
      {
        name: "GOR Master Sport Arena",
        address: "Jl. RS. Fatmawati Raya No. 15, Gandaria Selatan, Cilandak, Jakarta Selatan",
        area: "Jakarta Selatan",
        maps: "https://maps.google.com/?q=GOR+Master+Sport+Arena+Fatmawati"
      },
      {
        name: "GOR Cempaka Court",
        address: "Jl. Cempaka Putih Tengah No. 20, Cempaka Putih / Pancoran, Jakarta",
        area: "Jakarta Selatan",
        maps: "https://maps.google.com/?q=GOR+Cempaka+Court+Jakarta"
      },
      {
        name: "Kemang Sport Hub",
        address: "Jl. Kemang Raya No. 88, Bangka, Mampang Prapatan, Jakarta Selatan",
        area: "Jakarta Selatan",
        maps: "https://maps.google.com/?q=Kemang+Sport+Hub+Jakarta"
      },
      {
        name: "GOR Bulungan",
        address: "Jl. Bulungan No. 1, Kramat Pela, Kebayoran Baru, Jakarta Selatan",
        area: "Jakarta Selatan",
        maps: "https://maps.google.com/?q=GOR+Bulungan+Jakarta"
      },
      {
        name: "GOR Otista",
        address: "Jl. Otto Iskandardinata No. 121, Bidara Cina, Jatinegara, Jakarta Timur",
        area: "Jakarta Timur",
        maps: "https://maps.google.com/?q=GOR+Otista+Jatinegara"
      },
      {
        name: "GOR Pulogadung",
        address: "Jl. Pemuda No. 10, Rawamangun, Pulo Gadung, Jakarta Timur",
        area: "Jakarta Timur",
        maps: "https://maps.google.com/?q=GOR+Pulogadung+Rawamangun"
      },
      {
        name: "GOR Tanjung Duren",
        address: "Jl. Tanjung Duren Barat No. 5, Grogol Petamburan, Jakarta Barat",
        area: "Jakarta Barat",
        maps: "https://maps.google.com/?q=GOR+Tanjung+Duren"
      },
      {
        name: "GOR Sunter Arena",
        address: "Jl. Danau Sunter Selatan No. 1, Sunter Agung, Tanjung Priok, Jakarta Utara",
        area: "Jakarta Utara",
        maps: "https://maps.google.com/?q=GOR+Danau+Sunter"
      }
    ];

    const vSearch = document.getElementById('venueSearch');
    const vResults = document.getElementById('venueResults');
    const vClearBtn = document.getElementById('venueClearBtn');
    const vSpinner = document.getElementById('venueSearchSpinner');
    const vFeedback = document.getElementById('venueSelectedFeedback');
    const vFeedbackText = document.getElementById('venueSelectedFeedbackText');
    const openInGmapsBtn = document.getElementById('openInGmapsBtn');
    const gmapsApiKeyToggle = document.getElementById('gmapsApiKeyToggle');
    const gmapsApiKeyBox = document.getElementById('gmapsApiKeyBox');
    const gmapsApiKeyInput = document.getElementById('gmapsApiKeyInput');
    const saveGmapsApiKeyBtn = document.getElementById('saveGmapsApiKeyBtn');
    const gmapsKeyStatus = document.getElementById('gmapsKeyStatus');

    if (vSearch) {
      let isGoogleMapsLoaded = false;
      let gAutocomplete = null;
      let debounceTimer = null;
      let activeIndex = -1;
      let currentItems = [];

      // 1. Direct "Buka di Google Maps" button
      if (openInGmapsBtn) {
        openInGmapsBtn.addEventListener('click', function() {
          const q = vSearch.value.trim() || document.getElementById('formVenueName').value.trim() || ('GOR Badminton ' + <?= json_encode(!empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia') ?>);
          window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`, '_blank');
        });
      }

      // 2. Google Maps API Key Setup & Dynamic Loader
      function checkGmapsKey() {
        const savedKey = localStorage.getItem('smashpoint_gmaps_api_key');
        if (savedKey) {
          if (gmapsKeyStatus) {
            gmapsKeyStatus.textContent = 'Google Places API Aktif';
            gmapsKeyStatus.className = 'text-[10px] font-mono px-2 py-0.5 rounded-sm bg-amber-100 text-blue-900 font-bold';
          }
          if (gmapsApiKeyInput) gmapsApiKeyInput.value = savedKey;
          loadGoogleMapsScript(savedKey);
        }
      }

      if (gmapsApiKeyToggle && gmapsApiKeyBox) {
        gmapsApiKeyToggle.addEventListener('click', () => {
          gmapsApiKeyBox.classList.toggle('hidden');
        });
      }

      if (saveGmapsApiKeyBtn && gmapsApiKeyInput) {
        saveGmapsApiKeyBtn.addEventListener('click', () => {
          const key = gmapsApiKeyInput.value.trim();
          if (key) {
            localStorage.setItem('smashpoint_gmaps_api_key', key);
            checkGmapsKey();
            showAdminToast('Google Maps API Key berhasil disimpan! Fitur Places Autocomplete aktif.', true);
            if (gmapsApiKeyBox) gmapsApiKeyBox.classList.add('hidden');
          } else {
            localStorage.removeItem('smashpoint_gmaps_api_key');
            if (gmapsKeyStatus) {
              gmapsKeyStatus.textContent = 'Belum Disimpan';
              gmapsKeyStatus.className = 'text-[10px] font-mono px-2 py-0.5 rounded-sm bg-slate-100 text-slate-600';
            }
            showAdminToast('Google Maps API Key dihapus. Sistem kembali ke mode default.', true);
          }
        });
      }

      function loadGoogleMapsScript(apiKey) {
        if (window.google && window.google.maps && window.google.maps.places) {
          initGooglePlacesAutocomplete();
          return;
        }
        if (document.getElementById('googleMapsScriptTag')) return;

        const script = document.createElement('script');
        script.id = 'googleMapsScriptTag';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
        script.async = true;
        script.defer = true;
        script.onload = () => {
          initGooglePlacesAutocomplete();
        };
        script.onerror = () => {
          console.warn('Gagal memuat Google Maps SDK.');
        };
        document.head.appendChild(script);
      }

      function initGooglePlacesAutocomplete() {
        if (!window.google || !window.google.maps || !window.google.maps.places) return;
        try {
          gAutocomplete = new google.maps.places.Autocomplete(vSearch, {
            componentRestrictions: { country: 'id' },
            fields: ['name', 'formatted_address', 'address_components', 'url', 'geometry']
          });

          isGoogleMapsLoaded = true;

          gAutocomplete.addListener('place_changed', function() {
            const place = gAutocomplete.getPlace();
            if (!place || !place.name) return;

            let area = 'Jakarta';
            if (place.address_components) {
              for (const comp of place.address_components) {
                if (comp.types.includes('administrative_area_level_2') || comp.types.includes('locality')) {
                  area = comp.long_name;
                  break;
                }
                if (comp.types.includes('sublocality') || comp.types.includes('administrative_area_level_3')) {
                  area = comp.long_name;
                }
              }
            }

            const mapsUrl = place.url || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(place.name + ' ' + (place.formatted_address || ''))}`;

            selectVenue({
              name: place.name,
              address: place.formatted_address || place.name,
              area: area,
              maps: mapsUrl,
              source: 'Google Places API'
            });
          });
        } catch (e) {
          console.warn('Google Places Autocomplete init error:', e);
        }
      }

      checkGmapsKey();

      // Update clear button visibility
      function updateClearBtn() {
        if (vClearBtn) {
          if (vSearch.value.trim().length > 0) {
            vClearBtn.classList.remove('hidden');
          } else {
            vClearBtn.classList.add('hidden');
          }
        }
      }

      if (vClearBtn) {
        vClearBtn.addEventListener('click', function() {
          vSearch.value = '';
          updateClearBtn();
          if (vResults) {
            vResults.classList.add('hidden');
            vResults.innerHTML = '';
          }
          vSearch.focus();
        });
      }

      // Input handler with debounce
      vSearch.addEventListener('input', function() {
        if (isGoogleMapsLoaded) {
          updateClearBtn();
          return;
        }

        const query = this.value.trim();
        updateClearBtn();

        if (query.length < 2) {
          if (vResults) {
            vResults.classList.add('hidden');
            vResults.innerHTML = '';
          }
          if (vSpinner) vSpinner.classList.add('hidden');
          return;
        }

        clearTimeout(debounceTimer);
        if (vSpinner) vSpinner.classList.remove('hidden');

        debounceTimer = setTimeout(() => {
          performVenueSearch(query);
        }, 250);
      });

      // Keyboard navigation (Google style)
      vSearch.addEventListener('keydown', function(e) {
        const items = vResults.querySelectorAll('.venue-item');
        if (items.length === 0 || vResults.classList.contains('hidden')) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          activeIndex = (activeIndex + 1) % items.length;
          highlightItem(items, activeIndex);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          activeIndex = (activeIndex - 1 + items.length) % items.length;
          highlightItem(items, activeIndex);
        } else if (e.key === 'Enter') {
          if (activeIndex >= 0 && activeIndex < currentItems.length) {
            e.preventDefault();
            selectVenue(currentItems[activeIndex]);
          }
        } else if (e.key === 'Escape') {
          vResults.classList.add('hidden');
        }
      });

      function highlightItem(items, index) {
        items.forEach((el, idx) => {
          if (idx === index) {
            el.classList.add('bg-emerald-50', 'border-l-4', 'border-emerald-600');
            el.scrollIntoView({ block: 'nearest' });
          } else {
            el.classList.remove('bg-emerald-50', 'border-l-4', 'border-emerald-600');
          }
        });
      }

      // Perform Search: Combine Local Curated List & Photon API
      async function performVenueSearch(rawQuery) {
        const qLower = rawQuery.toLowerCase();
        activeIndex = -1;

        // 1. Instant local curated matches
        const localMatches = POPULAR_GORS.filter(g => 
          g.name.toLowerCase().includes(qLower) || 
          g.address.toLowerCase().includes(qLower) || 
          g.area.toLowerCase().includes(qLower)
        ).map(g => ({
          ...g,
          source: 'Curated'
        }));

        let photonMatches = [];

        try {
          // 2. Fetch Photon API (OpenStreetMap geocoding with Jakarta bias)
          const photonUrl = `https://photon.komoot.io/api/?q=${encodeURIComponent(rawQuery)}&lat=-6.2088&lon=106.8456&limit=8`;
          const res = await fetch(photonUrl);
          if (res.ok) {
            const data = await res.json();
            if (data && data.features && data.features.length > 0) {
              photonMatches = data.features
                .map(feat => {
                  const p = feat.properties || {};
                  const title = p.name || p.street || rawQuery;
                  
                  // Build clean Indonesian address components
                  const parts = [];
                  if (p.street) parts.push(p.street + (p.housenumber ? ' No. ' + p.housenumber : ''));
                  if (p.locality && p.locality !== p.street) parts.push(p.locality);
                  if (p.district) parts.push(p.district);
                  if (p.city) parts.push(p.city);
                  if (p.state && p.state !== p.city) parts.push(p.state);

                  const fullAddress = parts.length > 0 ? parts.join(', ') : (p.name || rawQuery);
                  const area = p.district || p.city || 'DKI Jakarta';
                  const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(title + ' ' + fullAddress)}`;

                  return {
                    name: title,
                    address: fullAddress,
                    area: area,
                    maps: mapsUrl,
                    countrycode: p.countrycode || '',
                    source: 'Photon API'
                  };
                })
                // Prioritize Indonesian results
                .filter(item => !item.countrycode || item.countrycode === 'ID');
            }
          }
        } catch (err) {
          console.warn('Photon API fetch error:', err);
        } finally {
          if (vSpinner) vSpinner.classList.add('hidden');
        }

        // Merge & deduplicate results by normalized name
        const combined = [];
        const seen = new Set();

        [...localMatches, ...photonMatches].forEach(item => {
          const normKey = item.name.toLowerCase().replace(/[^a-z0-9]/g, '');
          if (!seen.has(normKey)) {
            seen.add(normKey);
            combined.push(item);
          }
        });

        currentItems = combined.slice(0, 8);
        renderVenueSuggestions(currentItems, rawQuery);
      }

      function renderVenueSuggestions(list, query) {
        vResults.innerHTML = '';
        if (list.length === 0) {
          vResults.innerHTML = `
            <div class="p-4 text-center">
              <p class="text-xs font-semibold text-slate-700">Tidak ada GOR / lokasi ditemukan untuk "${query}"</p>
              <p class="text-[11px] text-slate-400 mt-1">Anda tetap dapat mengetikkan nama GOR & alamat secara manual pada kolom di bawah.</p>
            </div>
          `;
          vResults.classList.remove('hidden');
          return;
        }

        // Header
        const header = document.createElement('div');
        header.className = 'px-3.5 py-1.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[10px] font-bold text-slate-500 uppercase tracking-wider';
        header.innerHTML = `
          <span>Hasil Pencarian Lokasi</span>
          <span class="text-blue-700 font-semibold flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Photon & Google Maps
          </span>
        `;
        vResults.appendChild(header);

        // Result items (Google style)
        list.forEach((item, idx) => {
          const div = document.createElement('div');
          div.className = 'venue-item px-3.5 py-2.5 hover:bg-emerald-50/70 cursor-pointer transition-colors flex items-start gap-3';
          div.setAttribute('data-index', idx);

          // Google-style pin icon
          div.innerHTML = `
            <div class="w-8 h-8 rounded-md bg-amber-100 text-blue-900 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="font-bold text-xs text-slate-900 truncate">${item.name}</span>
                <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full ${item.source === 'Curated' ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-amber-100 text-blue-950'}">${item.area}</span>
              </div>
              <div class="text-[11px] text-slate-500 truncate mt-0.5">${item.address}</div>
            </div>
          `;

          div.addEventListener('click', () => {
            selectVenue(item);
          });

          vResults.appendChild(div);
        });

        vResults.classList.remove('hidden');
      }

      function selectVenue(item) {
        document.getElementById('formVenueName').value = item.name;
        document.getElementById('formVenueAddress').value = item.address;
        document.getElementById('formBranchArea').value = item.area;
        document.getElementById('formMapsUrl').value = item.maps;
        vSearch.value = item.name;
        updateClearBtn();
        vResults.classList.add('hidden');

        // Show feedback message
        if (vFeedback && vFeedbackText) {
          const sourceLabel = (item && item.source) ? item.source : 'Google Maps';
          vFeedbackText.textContent = `✓ Lokasi terpilih (${sourceLabel}): "${item.name}" — Kolom nama GOR, alamat, wilayah & link Google Maps telah diisi otomatis!`;
          vFeedback.classList.remove('hidden');
          setTimeout(() => {
            vFeedback.classList.add('hidden');
          }, 6000);
        }
      }

      // Close dropdown when clicked outside
      document.addEventListener('click', function(e) {
        if (!vSearch.contains(e.target) && !vResults.contains(e.target)) {
          vResults.classList.add('hidden');
        }
      });
    }

    // 2. WhatsApp Template Copy to Clipboard
    function copyWaTemplate() {
      const textElem = document.getElementById('waTemplateText');
      const btn = document.getElementById('copyBtn');
      if (!textElem) return;

      const text = textElem.innerText;
      navigator.clipboard.writeText(text).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span>&check; Berhasil Disalin ke Clipboard!</span>';
        btn.classList.remove('bg-emerald-500');
        btn.classList.add('bg-white', 'text-emerald-950');
        setTimeout(() => {
          btn.innerHTML = originalHtml;
          btn.classList.remove('bg-white', 'text-emerald-950');
          btn.classList.add('bg-emerald-500');
        }, 2500);
      }).catch(err => {
        showAdminToast('Gagal menyalin, silakan blok teks dan copy manual.', false);
      });
    }

    // 2.5 24-Hour Timepicker Synchronization (00:00 to 23:59)
    function syncTimeStart() {
      const hEl = document.getElementById('selectStartHour');
      const mEl = document.getElementById('selectStartMin');
      const input = document.getElementById('inputTimeStart');
      if (hEl && mEl && input) {
        input.value = `${hEl.value}:${mEl.value}`;
        input.dispatchEvent(new Event('change'));
      }
    }

    function syncTimeEnd() {
      const hEl = document.getElementById('selectEndHour');
      const mEl = document.getElementById('selectEndMin');
      const input = document.getElementById('inputTimeEnd');
      if (hEl && mEl && input) {
        input.value = `${hEl.value}:${mEl.value}`;
        input.dispatchEvent(new Event('change'));
      }
    }

    function setTime(target, hour, min) {
      if (target === 'start') {
        const hEl = document.getElementById('selectStartHour');
        const mEl = document.getElementById('selectStartMin');
        if (hEl) hEl.value = hour;
        if (mEl) mEl.value = min;
        syncTimeStart();
      } else if (target === 'end') {
        const hEl = document.getElementById('selectEndHour');
        const mEl = document.getElementById('selectEndMin');
        if (hEl) hEl.value = hour;
        if (mEl) mEl.value = min;
        syncTimeEnd();
      }
    }

    // 2.6 Rupiah Thousand Separator Formatter
    function formatRupiahInput(el) {
      if (!el) return;
      let rawVal = el.value.replace(/[^0-9]/g, '');
      if (!rawVal) {
        el.value = '';
        return;
      }
      el.value = new Intl.NumberFormat('id-ID').format(parseInt(rawVal, 10));
    }

    function setFeePreset(amount) {
      const el = document.getElementById('inputFeeDisplay');
      if (el) {
        el.value = new Intl.NumberFormat('id-ID').format(amount);
      }
    }

    // 3. Auto-Generate "Hari & Sesi" from Schedule Date Picker
    function updateDayNameFromDate(forceUpdate = false) {
      const dateInput = document.getElementById('formScheduleDate');
      const dayNameInput = document.getElementById('formDayName');
      const timeStartInput = document.querySelector('input[name="time_start"]');
      if (!dateInput || !dateInput.value || !dayNameInput) return;

      const dateVal = dateInput.value; // "YYYY-MM-DD"
      const parts = dateVal.split('-');
      if (parts.length !== 3) return;

      const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
      const daysIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      const dayName = daysIndo[d.getDay()];

      let timeOfDay = '';
      if (timeStartInput && timeStartInput.value) {
        const hour = parseInt(timeStartInput.value.split(':')[0], 10);
        if (hour < 11) timeOfDay = ' Pagi';
        else if (hour < 15) timeOfDay = ' Siang';
        else if (hour < 18) timeOfDay = ' Sore';
        else timeOfDay = ' Malam';
      } else {
        timeOfDay = ' Malam';
      }

      // If empty or user changed the date explicitly
      if (!dayNameInput.value.trim() || forceUpdate) {
        dayNameInput.value = `${dayName}${timeOfDay}`;
      }

      // Live formatted Indonesian date hint
      const dateFeedback = document.getElementById('dateFormattedHint');
      if (dateFeedback) {
        const monthsIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        dateFeedback.textContent = `📅 ${dayName}, ${parseInt(parts[2], 10)} ${monthsIndo[parseInt(parts[1], 10) - 1]} ${parts[0]}`;
      }
    }

    // Attach listeners for schedule date picker
    const dateInputEl = document.getElementById('formScheduleDate');
    if (dateInputEl) {
      dateInputEl.addEventListener('change', () => updateDayNameFromDate(true));
      const timeStartEl = document.querySelector('input[name="time_start"]');
      if (timeStartEl) {
        timeStartEl.addEventListener('change', () => {
          const dayNameInput = document.getElementById('formDayName');
          // If current value contains day name, refresh suffix
          if (dayNameInput && dayNameInput.value) {
            updateDayNameFromDate(true);
          }
        });
      }
      // Run once on load to show formatted hint & populate if empty
      updateDayNameFromDate(false);
    }
  </script>

  <!-- ========================================== -->
  <!-- CUSTOM ATHLETIC DIALOG MODAL (CONFIRM/ALERT) -->
  <!-- ========================================== -->
  <div id="adminDialogModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target===this) closeAdminDialog(false)">
    <div class="bg-white border border-slate-300 max-w-sm w-full p-5 shadow-2xl space-y-4" onclick="event.stopPropagation()">
      <div class="flex items-start gap-3">
        <div id="adminDialogIconContainer" class="w-10 h-10 shrink-0 flex items-center justify-center border">
          <!-- Icon injected by JS -->
        </div>
        <div class="flex-1 min-w-0">
          <h3 id="adminDialogTitle" class="text-sm font-black text-slate-900 leading-snug"></h3>
          <p id="adminDialogMessage" class="text-xs text-slate-600 mt-1 leading-relaxed"></p>
        </div>
      </div>
      <div id="adminDialogActions" class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" id="adminDialogCancelBtn" onclick="closeAdminDialog(false)" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition-colors">
          Batal
        </button>
        <button type="button" id="adminDialogConfirmBtn" onclick="closeAdminDialog(true)" class="px-4 py-2 font-black text-xs uppercase tracking-wider transition-colors border">
          Konfirmasi
        </button>
      </div>
    </div>
  </div>

  <!-- Toast Notification System (Top Center) -->
  <div id="adminToast" class="hidden fixed top-4 inset-x-4 max-w-sm mx-auto z-50 px-4 py-3 shadow-2xl text-xs font-bold flex items-center justify-between border transition-all">
    <div class="flex items-center gap-2">
      <span id="adminToastIcon"></span>
      <span id="adminToastText"></span>
    </div>
    <button onclick="document.getElementById('adminToast').classList.add('hidden')" class="text-xs font-bold ml-2 opacity-70 hover:opacity-100">✕</button>
  </div>

  <script>
    let adminDialogResolve = null;

    function showAdminToast(msg, isSuccess = true) {
      const toast = document.getElementById('adminToast');
      const toastText = document.getElementById('adminToastText');
      const toastIcon = document.getElementById('adminToastIcon');
      if (!toast) return;
      if (toastText) toastText.innerText = msg;
      if (toastIcon) toastIcon.innerText = isSuccess ? '✓' : '⚠️';
      toast.className = `fixed top-4 inset-x-4 max-w-sm mx-auto z-50 px-4 py-3 shadow-2xl text-xs font-bold flex items-center justify-between border transition-all ${isSuccess ? 'bg-[#0b1e36] border-amber-400 text-amber-400' : 'bg-rose-950 border-rose-600 text-rose-200'}`;
      toast.classList.remove('hidden');
      setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    function showAdminConfirm({ title, message, confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', isDanger = true }) {
      return new Promise((resolve) => {
        adminDialogResolve = resolve;
        const modal = document.getElementById('adminDialogModal');
        const titleEl = document.getElementById('adminDialogTitle');
        const msgEl = document.getElementById('adminDialogMessage');
        const confirmBtn = document.getElementById('adminDialogConfirmBtn');
        const cancelBtn = document.getElementById('adminDialogCancelBtn');
        const iconContainer = document.getElementById('adminDialogIconContainer');

        titleEl.innerText = title;
        msgEl.innerHTML = message;
        confirmBtn.innerText = confirmText;
        cancelBtn.innerText = cancelText;
        cancelBtn.style.display = 'inline-block';

        if (isDanger) {
          confirmBtn.className = 'px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs uppercase tracking-wider transition-colors border border-rose-700';
          iconContainer.className = 'w-10 h-10 shrink-0 flex items-center justify-center border border-rose-300 bg-rose-50 text-rose-600';
          iconContainer.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
        } else {
          confirmBtn.className = 'px-4 py-2 bg-[#0b1e36] hover:bg-[#071324] active:scale-95 text-amber-400 font-bold text-xs uppercase tracking-wider transition-colors border border-amber-400/40';
          iconContainer.className = 'w-10 h-10 shrink-0 flex items-center justify-center border border-amber-300 bg-amber-50 text-amber-600';
          iconContainer.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
        }

        modal.classList.remove('hidden');
      });
    }

    function showAdminAlert({ title, message, buttonText = 'Mengerti', type = 'success' }) {
      return new Promise((resolve) => {
        adminDialogResolve = resolve;
        const modal = document.getElementById('adminDialogModal');
        const titleEl = document.getElementById('adminDialogTitle');
        const msgEl = document.getElementById('adminDialogMessage');
        const confirmBtn = document.getElementById('adminDialogConfirmBtn');
        const cancelBtn = document.getElementById('adminDialogCancelBtn');
        const iconContainer = document.getElementById('adminDialogIconContainer');

        titleEl.innerText = title;
        msgEl.innerHTML = message;
        confirmBtn.innerText = buttonText;
        cancelBtn.style.display = 'none';

        if (type === 'success') {
          confirmBtn.className = 'px-4 py-2 bg-[#0b1e36] hover:bg-[#071324] active:scale-95 text-amber-400 font-bold text-xs uppercase tracking-wider transition-colors border border-amber-400/40';
          iconContainer.className = 'w-10 h-10 shrink-0 flex items-center justify-center border border-amber-300 bg-amber-50 text-amber-600';
          iconContainer.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        } else {
          confirmBtn.className = 'px-4 py-2 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-bold text-xs uppercase tracking-wider transition-colors';
          iconContainer.className = 'w-10 h-10 shrink-0 flex items-center justify-center border border-rose-300 bg-rose-50 text-rose-600';
          iconContainer.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        }

        modal.classList.remove('hidden');
      });
    }

    function closeAdminDialog(result) {
      const modal = document.getElementById('adminDialogModal');
      if (modal) modal.classList.add('hidden');
      if (adminDialogResolve) {
        adminDialogResolve(result);
        adminDialogResolve = null;
      }
    }

    async function handleAdminFormConfirm(e, form, title, msg) {
      e.preventDefault();
      const ok = await showAdminConfirm({
        title: title,
        message: msg,
        confirmText: 'Ya, Lanjutkan',
        cancelText: 'Batal',
        isDanger: true
      });
      if (ok) {
        form.submit();
      }
    }
  </script>

  <!-- Mobile-First SmartSelect Dropdown Engine (No Keyboard Flash on Android) -->
  <script src="<?= $baseDir ?>/shared/assets/js/smart-select.js"></script>
  <script>
    function initSelectSearch() {
      if (typeof SmartSelect === 'undefined') return;

      const selects = document.querySelectorAll('select:not([data-no-search])');
      selects.forEach(function(sel) {
        if (sel._smartSelect) return;
        // Skip tiny clock hour/minute selectors
        if (sel.id === 'selectStartHour' || sel.id === 'selectStartMin' || sel.id === 'selectEndHour' || sel.id === 'selectEndMin') return;

        try {
          new SmartSelect(sel);
        } catch (e) {
          console.warn('SmartSelect init skipped for', sel, e);
        }
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSelectSearch);
    } else {
      initSelectSearch();
    }
  </script>
</body>
</html>
