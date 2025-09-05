<?php
// api/me.php — статус сессии, всегда JSON
ob_start();

require_once __DIR__ . '/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  @session_start();
}

function json_send($data, int $status = 200): void {
  while (ob_get_level()) ob_end_clean();
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
if ($uid <= 0) {
  json_send(['ok'=>true,'auth'=>false]);
}

$pdo = $GLOBALS['pdo'] ?? null;
if ($pdo instanceof PDO) {
  try {
    $st = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE id = :id LIMIT 1');
    $st->execute([':id'=>$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      json_send(['ok'=>true,'auth'=>true,'user'=>$row]);
    } else {
      // uid в сессии, но юзер удалён — сбрасываем сессию
      $_SESSION = []; @session_destroy();
      json_send(['ok'=>true,'auth'=>false]);
    }
  } catch (Throwable $e) {
    // деградация: не роняем фронт, просто считаем неавторизованным
    json_send(['ok'=>false,'auth'=>false,'error'=>'me_failed','detail'=>$e->getMessage()], 500);
  }
} else {
  // БД недоступна — не роняем фронт
  json_send(['ok'=>false,'auth'=>false,'error'=>'db_not_connected'], 500);
}
