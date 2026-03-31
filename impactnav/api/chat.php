<?php
require_once dirname(__DIR__) . '/functions.php';
session_init($config['app']['session_name']);

// Only accept AJAX JSON POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(400);
    exit(json_encode(['error' => 'Bad request']));
}

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['message'])) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

$userMessage = mb_substr(trim($input['message']), 0, 500);
$pdo         = pdo();
$user        = current_user();
$userId      = $user ? (int)$user['id'] : null;

// Get or create chat session
$session = get_or_create_chat_session($pdo, $userId);
$sessionId = (int)$session['id'];

// Load last 20 messages for context
$history = get_chat_history($sessionId, $pdo, 20);

// Build system prompt
$systemPrompt = <<<SYS
Sen impactNav'ın AI asistanısın. Adın impactAI.
Türkçe konuş. Kısa, samimi ve motive edici bir dil kullan.
Amacın: Kullanıcıya en uygun gönüllülük fırsatlarını bulmak.

Sohbet sırasında şu bilgileri öğren:
- Bulunduğu şehir
- Becerileri (tasarım, yazılım, öğretim, vb.)
- İlgi alanları (eğitim, çevre, sağlık, vb.)
- Uzaktan çalışabilir mi?
- Haftalık ayırabildiği süre

Yeterli bilgiyi topladığında yanıtının sonuna tam olarak [RECOMMEND_LISTINGS] yaz.
Bu token backend tarafından tespit edilecek ve uygun ilanlar gösterilecek.

Mevcut kategoriler: Nitelikli Eğitim, Eşitsizliklerin Azaltılması, Sürdürülebilir Şehirler ve Topluluklar, Çevre ve İklim, Sağlık ve İyilik Hali, Gençlik ve Spor, Kadın ve Haklar, Hayvan Hakları, Mültecilik ve Göç, Kültür ve Sanat, Dijital Haklar, Engellilik ve Erişilebilirlik.

Önemli: Sadece gönüllülük ve sosyal etki konularında yardımcı ol. Konu dışı istekleri kibarca reddet.
SYS;

// Build messages array for OpenAI
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
];

foreach ($history as $h) {
    $messages[] = ['role' => $h['role'], 'content' => $h['content']];
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

// Save user message to DB
save_chat_message($sessionId, 'user', $userMessage, $pdo);

$reply    = '';
$listings = [];

if (empty($config['openai']['api_key']) || $config['openai']['api_key'] === 'your-openai-api-key-here') {
    // Demo mode without API key
    $reply = 'Merhaba! impactAI demo modunda çalışıyor. OpenAI API anahtarı yapılandırılmamış. .env dosyanıza OPENAI_API_KEY ekleyin.';
} else {
    try {
        $reply = openai_chat($messages, $config['openai'], 0.7);

        // Detect recommendation trigger
        if (str_contains($reply, '[RECOMMEND_LISTINGS]')) {
            // Build query from last messages
            $queryParts = [];
            foreach (array_slice($history, -6) as $h) {
                $queryParts[] = $h['content'];
            }
            $queryParts[] = $userMessage;
            $query = implode(' ', $queryParts);

            $listings = search_listings_by_text($query, $pdo, $config['openai'], 3);
        }
    } catch (Exception $e) {
        $reply = 'Üzgünüm, şu an bir hata oluştu. Lütfen tekrar deneyin.';
        error_log('impactAI chat error: ' . $e->getMessage());
    }
}

// Save assistant reply
save_chat_message($sessionId, 'assistant', $reply, $pdo);

// Remove internal token from displayed reply
$cleanReply = trim(str_replace('[RECOMMEND_LISTINGS]', '', $reply));

echo json_encode([
    'reply'    => $cleanReply,
    'listings' => $listings,
], JSON_UNESCAPED_UNICODE);
