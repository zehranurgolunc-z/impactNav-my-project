<?php

require_once __DIR__ . '/db.php';

$config = require __DIR__ . '/config.php';

// ─── Session ────────────────────────────────────────────────────────────────

function session_init(string $name = 'impactnav_session'): void
{
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    session_name($name);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    if (current_user()['role'] !== $role) {
        redirect('/dashboard.php');
    }
}

function flash(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

function get_flash(string $key): ?string
{
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ─── CSRF ────────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

// ─── Utilities ───────────────────────────────────────────────────────────────

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function base_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

function pdo(): PDO
{
    global $config;
    return Db::connect($config['db']);
}

// ─── OpenAI ──────────────────────────────────────────────────────────────────

function openai_embedding(string $text, array $cfg): array
{
    $payload = json_encode([
        'model' => $cfg['embed_model'],
        'input' => mb_substr($text, 0, 8192),
    ]);

    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['api_key'],
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException('OpenAI embedding error: ' . $response);
    }

    $data = json_decode($response, true);
    return $data['data'][0]['embedding'];
}

function openai_chat(array $messages, array $cfg, float $temperature = 0.5): string
{
    $payload = json_encode([
        'model'       => $cfg['model'],
        'messages'    => $messages,
        'temperature' => $temperature,
        'max_tokens'  => 600,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['api_key'],
        ],
        CURLOPT_TIMEOUT        => 45,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException('OpenAI chat error: ' . $response);
    }

    $data = json_decode($response, true);
    return trim($data['choices'][0]['message']['content'] ?? '');
}

// ─── Vector Math ─────────────────────────────────────────────────────────────

function cosine_similarity(array $a, array $b): float
{
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;
    $len = min(count($a), count($b));
    for ($i = 0; $i < $len; $i++) {
        $dot   += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }
    $denom = sqrt($normA) * sqrt($normB);
    return $denom > 1e-10 ? $dot / $denom : 0.0;
}

// ─── Text Builders ───────────────────────────────────────────────────────────

function build_volunteer_text(array $profile, string $full_name, array $categoryLabels): string
{
    $parts = ["Gönüllü: $full_name"];
    if ($profile['city'])     $parts[] = "Şehir: {$profile['city']}";
    if ($profile['district']) $parts[] = "İlçe: {$profile['district']}";
    if ($profile['remote_ok']) $parts[] = "Uzaktan çalışabilir";
    if ($profile['skills'])   $parts[] = "Beceriler: {$profile['skills']}";
    if ($profile['interests']) $parts[] = "İlgi alanları: {$profile['interests']}";
    if ($categoryLabels)      $parts[] = "Kategoriler: " . implode(', ', $categoryLabels);
    if ($profile['bio'])      $parts[] = "Hakkında: {$profile['bio']}";
    return implode('. ', $parts);
}

function build_listing_text(array $listing, string $org_name, array $categoryLabels): string
{
    $parts = ["İlan: {$listing['title']}", "Kurum: $org_name"];
    if ($listing['description'])    $parts[] = $listing['description'];
    if ($listing['required_skills']) $parts[] = "Aranan beceriler: {$listing['required_skills']}";
    if ($listing['city'])            $parts[] = "Şehir: {$listing['city']}";
    if ($listing['remote_ok'])       $parts[] = "Uzaktan çalışılabilir";
    if ($categoryLabels)             $parts[] = "Kategoriler: " . implode(', ', $categoryLabels);
    return implode('. ', $parts);
}

// ─── Matching ────────────────────────────────────────────────────────────────

function save_match_scores(int $volunteer_id, PDO $pdo, array $openaiCfg): int
{
    // Load volunteer profile + embedding
    $stmt = $pdo->prepare('SELECT vp.*, u.full_name FROM volunteer_profiles vp JOIN users u ON u.id = vp.user_id WHERE vp.id = ?');
    $stmt->execute([$volunteer_id]);
    $volunteer = $stmt->fetch();
    if (!$volunteer || !$volunteer['embedding']) return 0;

    $volEmbed = json_decode($volunteer['embedding'], true);

    // Load volunteer categories
    $stmt = $pdo->prepare('SELECT c.label FROM volunteer_categories vc JOIN categories c ON c.id = vc.category_id WHERE vc.volunteer_id = ?');
    $stmt->execute([$volunteer_id]);
    $volCategories = array_column($stmt->fetchAll(), 'label');

    // Load active listings with embeddings
    $stmt = $pdo->query('SELECT l.*, o.org_name FROM listings l JOIN organizations o ON o.id = l.org_id WHERE l.status = \'active\' AND l.embedding IS NOT NULL');
    $listings = $stmt->fetchAll();

    $scores = [];
    foreach ($listings as $listing) {
        $listEmbed = json_decode($listing['embedding'], true);
        if (!$listEmbed) continue;

        $score = cosine_similarity($volEmbed, $listEmbed);
        $scores[$listing['id']] = ['score' => $score, 'listing' => $listing];
    }

    // Sort by score descending
    uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

    $count = 0;
    $rank  = 0;
    foreach ($scores as $listingId => $data) {
        $score = round($data['score'], 4);
        $rationale = null;

        // Generate GPT rationale for top 5
        if ($rank < 5 && !empty($openaiCfg['api_key'])) {
            try {
                $stmt2 = $pdo->prepare('SELECT c.label FROM listing_categories lc JOIN categories c ON c.id = lc.category_id WHERE lc.listing_id = ?');
                $stmt2->execute([$listingId]);
                $listCategories = array_column($stmt2->fetchAll(), 'label');

                $volText  = build_volunteer_text($volunteer, $volunteer['full_name'], $volCategories);
                $listText = build_listing_text($data['listing'], $data['listing']['org_name'], $listCategories);

                $rationale = openai_chat([
                    ['role' => 'system', 'content' => 'Sen bir STK gönüllü eşleştirme asistanısın. Türkçe 2 cümle ile bu gönüllünün bu ilana neden uygun olduğunu açıkla. Samimi ve motive edici bir dil kullan.'],
                    ['role' => 'user',   'content' => "Gönüllü: $volText\n\nİlan: $listText\n\nUyum skoru: " . round($score * 100) . "%"],
                ], $openaiCfg, 0.4);
                usleep(200000); // 200ms rate limit buffer
            } catch (Exception $e) {
                $rationale = null;
            }
        }

        $stmt = $pdo->prepare('
            INSERT INTO match_scores (volunteer_id, listing_id, score, rationale, method)
            VALUES (?, ?, ?, ?, \'hybrid\')
            ON DUPLICATE KEY UPDATE score = VALUES(score), rationale = VALUES(rationale), created_at = NOW()
        ');
        $stmt->execute([$volunteer_id, $listingId, $score, $rationale]);
        $count++;
        $rank++;
    }

    return $count;
}

function get_volunteer_matches(int $volunteer_id, PDO $pdo, int $limit = 20): array
{
    $stmt = $pdo->prepare('
        SELECT ms.score, ms.rationale,
               l.id AS listing_id, l.title, l.description, l.city, l.remote_ok, l.weekly_hours, l.created_at,
               o.org_name, o.org_type
        FROM match_scores ms
        JOIN listings l ON l.id = ms.listing_id
        JOIN organizations o ON o.id = l.org_id
        WHERE ms.volunteer_id = ? AND l.status = \'active\'
        ORDER BY ms.score DESC
        LIMIT ?
    ');
    $stmt->execute([$volunteer_id, $limit]);
    return $stmt->fetchAll();
}

// ─── Chatbot Session ─────────────────────────────────────────────────────────

function get_or_create_chat_session(PDO $pdo, ?int $user_id): array
{
    $key = $_COOKIE['chat_session'] ?? null;

    if ($key) {
        $stmt = $pdo->prepare('SELECT * FROM chat_sessions WHERE session_key = ?');
        $stmt->execute([$key]);
        $session = $stmt->fetch();
        if ($session) return $session;
    }

    // Create new session
    $key = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('INSERT INTO chat_sessions (user_id, session_key) VALUES (?, ?)');
    $stmt->execute([$user_id, $key]);
    $id = (int)$pdo->lastInsertId();

    setcookie('chat_session', $key, time() + 86400 * 30, '/', '', false, true);

    return ['id' => $id, 'user_id' => $user_id, 'session_key' => $key];
}

function get_chat_history(int $session_id, PDO $pdo, int $limit = 20): array
{
    $stmt = $pdo->prepare('
        SELECT role, content FROM chat_messages
        WHERE session_id = ? AND role != \'system\'
        ORDER BY created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$session_id, $limit]);
    return array_reverse($stmt->fetchAll());
}

function save_chat_message(int $session_id, string $role, string $content, PDO $pdo): void
{
    $stmt = $pdo->prepare('INSERT INTO chat_messages (session_id, role, content) VALUES (?, ?, ?)');
    $stmt->execute([$session_id, $role, $content]);
}

function search_listings_by_text(string $query, PDO $pdo, array $openaiCfg, int $limit = 3): array
{
    if (empty($openaiCfg['api_key'])) return [];

    try {
        $queryEmbed = openai_embedding($query, $openaiCfg);
    } catch (Exception $e) {
        return [];
    }

    $stmt = $pdo->query('SELECT l.*, o.org_name FROM listings l JOIN organizations o ON o.id = l.org_id WHERE l.status = \'active\' AND l.embedding IS NOT NULL');
    $listings = $stmt->fetchAll();

    $scored = [];
    foreach ($listings as $listing) {
        $embed = json_decode($listing['embedding'], true);
        if (!$embed) continue;
        $scored[] = array_merge($listing, ['_score' => cosine_similarity($queryEmbed, $embed)]);
    }

    usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
    return array_slice($scored, 0, $limit);
}

// ─── Categories Helper ───────────────────────────────────────────────────────

function all_categories(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categories ORDER BY label')->fetchAll();
}
