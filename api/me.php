<?php
// /api/me.php — статус сессии, завжди JSON
ob_start();

require_once __DIR__ . '/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  @session_start();
}

/**
 * Унифицированная отправка JSON + анти-кеш заголовки.
 */
function json_send($data, int $status = 200): void {
  // Анти-кеш — чтобы после логаута не прилипал старый ответ
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  // Чтобы прокси/браузеры не склеивали ответы разных сессий
  header('Vary: Cookie');

  while (ob_get_level()) ob_end_clean();
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

// Читаем uid из сессии
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
if ($uid <= 0) {
  json_send(['ok' => true, 'auth' => false]);
}

$pdo = $GLOBALS['pdo'] ?? null;
if ($pdo instanceof PDO) {
  try {
    $st = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE id = :id LIMIT 1');
    $st->execute([':id' => $uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      // Нормализуем поля (id — строкой, чтобы фронт стабильно работал)
      $user = [
        'id'        => (string)$row['id'],
        'username'  => (string)($row['username'] ?? ''),
        'full_name' => (string)($row['full_name'] ?? ''),
        'role'      => (string)($row['role'] ?? 'user'),
      ];
      json_send(['ok' => true, 'auth' => true, 'user' => $user]);
    } else {
      // uid в сессии, но пользователь удалён — сбросить сессию
      $_SESSION = [];
      @session_destroy();
      json_send(['ok' => true, 'auth' => false]);
    }
  } catch (Throwable $e) {
    // Деградация: не роняем фронт, возвращаем 500 + флаг неавторизован
    json_send(['ok' => false, 'auth' => false, 'error' => 'me_failed', 'detail' => $e->getMessage()], 500);
  }
} else {
  // БД недоступна — не роняем фронт
  json_send(['ok' => false, 'auth' => false, 'error' => 'db_not_connected'], 500);
}
