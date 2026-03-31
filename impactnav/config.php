<?php
// Load .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

return [
    'db' => [
        'host'    => $_ENV['DB_HOST']    ?? 'localhost',
        'name'    => $_ENV['DB_NAME']    ?? 'impactnav',
        'user'    => $_ENV['DB_USER']    ?? 'root',
        'pass'    => $_ENV['DB_PASS']    ?? '',
        'charset' => 'utf8mb4',
    ],
    'openai' => [
        'api_key'     => $_ENV['OPENAI_API_KEY'] ?? '',
        'model'       => $_ENV['OPENAI_MODEL']   ?? 'gpt-4o-mini',
        'embed_model' => 'text-embedding-3-small',
    ],
    'app' => [
        'name'         => 'impactNav',
        'session_name' => 'impactnav_session',
        'debug'        => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    ],
];
