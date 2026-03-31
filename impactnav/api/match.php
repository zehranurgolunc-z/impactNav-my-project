<?php
require_once dirname(__DIR__) . '/functions.php';
session_init($config['app']['session_name']);

header('Content-Type: application/json; charset=utf-8');

$pdo = pdo();

// Accept both POST (from dashboard form) and internal calls
$volunteerUserId = (int)($_POST['volunteer_user_id'] ?? 0);

// If called from dashboard form with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!csrf_verify()) {
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    // Must be logged in as the same user or just use user ID from session
    if (!is_logged_in()) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    $volunteerUserId = (int)current_user()['id'];
}

if (!$volunteerUserId) {
    echo json_encode(['error' => 'volunteer_user_id required']);
    exit;
}

if (empty($config['openai']['api_key']) || $config['openai']['api_key'] === 'your-openai-api-key-here') {
    echo json_encode(['error' => 'OpenAI API key not configured', 'success' => false]);
    exit;
}

try {
    // Get volunteer profile
    $stmt = $pdo->prepare('SELECT vp.*, u.full_name FROM volunteer_profiles vp JOIN users u ON u.id = vp.user_id WHERE vp.user_id = ?');
    $stmt->execute([$volunteerUserId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        echo json_encode(['error' => 'Volunteer profile not found']);
        exit;
    }

    $volId = (int)$profile['id'];

    // Generate/update volunteer embedding if needed
    $stmt = $pdo->prepare('SELECT c.label FROM volunteer_categories vc JOIN categories c ON c.id = vc.category_id WHERE vc.volunteer_id = ?');
    $stmt->execute([$volId]);
    $catLabels = array_column($stmt->fetchAll(), 'label');

    $volText = build_volunteer_text($profile, $profile['full_name'], $catLabels);
    $volEmbed = openai_embedding($volText, $config['openai']);

    $stmt = $pdo->prepare('UPDATE volunteer_profiles SET embedding = ?, embedding_at = NOW() WHERE id = ?');
    $stmt->execute([json_encode($volEmbed), $volId]);

    // Generate embeddings for listings that don't have them yet
    $stmt = $pdo->query('SELECT l.*, o.org_name FROM listings l JOIN organizations o ON o.id = l.org_id WHERE l.status = \'active\' AND l.embedding IS NULL');
    $pendingListings = $stmt->fetchAll();

    foreach ($pendingListings as $l) {
        $stmt2 = $pdo->prepare('SELECT c.label FROM listing_categories lc JOIN categories c ON c.id = lc.category_id WHERE lc.listing_id = ?');
        $stmt2->execute([$l['id']]);
        $lCats = array_column($stmt2->fetchAll(), 'label');

        $lText  = build_listing_text($l, $l['org_name'], $lCats);
        try {
            $lEmbed = openai_embedding($lText, $config['openai']);
            $stmt3  = $pdo->prepare('UPDATE listings SET embedding = ?, embedding_at = NOW() WHERE id = ?');
            $stmt3->execute([json_encode($lEmbed), $l['id']]);
            usleep(200000); // 200ms between embedding calls
        } catch (Exception $e) {
            continue;
        }
    }

    // Run matching
    $count = save_match_scores($volId, $pdo, $config['openai']);

    // If called from dashboard form, redirect back
    if (isset($_POST['csrf_token'])) {
        flash('success', "Eşleşmeler güncellendi! ($count ilan analiz edildi)");
        redirect('/dashboard.php');
    }

    echo json_encode(['success' => true, 'count' => $count]);

} catch (Exception $e) {
    error_log('match.php error: ' . $e->getMessage());

    if (isset($_POST['csrf_token'])) {
        flash('error', 'Eşleşme güncellenirken hata oluştu: ' . $e->getMessage());
        redirect('/dashboard.php');
    }

    echo json_encode(['error' => $e->getMessage(), 'success' => false]);
}
