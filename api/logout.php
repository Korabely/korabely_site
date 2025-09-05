<?php
// api/logout.php — выход, всегда JSON
ob_start();

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

$_SESSION = [];
@session_unset();
@session_destroy();

json_send(['ok'=>true]);
