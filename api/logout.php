<?php
// /api/logout.php
declare(strict_types=1);

// Если у вас есть общий bootstrap — подключите его.
// Он может уже стартовать сессию; учтём это безопасно.
$bootstrap = __DIR__ . '/bootstrap.php';
if (is_file($bootstrap)) {
    require $bootstrap;
}

// Стартуем сессию только если ещё не стартовала
if (session_status() !== PHP_SESSION_ACTIVE) {
    // На всякий случай зададим одинаковые параметры куки,
    // чтобы их же использовать при очистке
    $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $domain   = $_SERVER['HTTP_HOST'] ?? '';
    // Если домен содержит порт — убираем его из cookie domain
    if (strpos($domain, ':') !== false) {
        $domain = preg_replace('~:\d+$~', '', $domain);
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Отключим кеширование ответа
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Полностью очищаем данные сессии
$_SESSION = [];

// Стираем cookie сессии с теми же параметрами, что и ставили
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    // PHP 8+: можно через массив
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path']     ?? '/',
        'domain'   => $params['domain']   ?? '',
        'secure'   => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

// Разрушить серверную сторону сессии
session_destroy();

// На всякий случай — выдать минимальный ответ
echo json_encode(['ok' => true, 'auth' => false], JSON_UNESCAPED_UNICODE);
