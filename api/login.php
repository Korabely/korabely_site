<?php
// api/login.php — безопасный логин (чистый JSON, без 500)
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

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if (!is_string($raw) || $raw === '') return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
  json_send(['ok'=>false,'error'=>'method_not_allowed'], 405);
}

$pdo = $GLOBALS['pdo'] ?? null;
if (!($pdo instanceof PDO)) {
  json_send(['ok'=>false,'error'=>'db_not_connected'], 500);
}

$body = read_json_body();
if (!$body && !empty($_POST)) $body = $_POST;

$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
  json_send(['ok'=>false,'error'=>'username_or_password_empty'], 400);
}

try {
  // пароли — в открытом виде, как вы и хотели
  $st = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE username = :u AND password = :p LIMIT 1');
  $st->execute([':u'=>$username, ':p'=>$password]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    json_send(['ok'=>false,'auth'=>false,'error'=>'bad_credentials'], 401);
  }

  $_SESSION['uid'] = (int)$row['id'];

  json_send([
    'ok'=>true,
    'auth'=>true,
    'user'=>[
      'id' => (int)$row['id'],
      'username' => $row['username'],
      'full_name' => $row['full_name'] ?: 'Користувач',
      'role' => $row['role'] ?: 'user'
    ]
  ]);
} catch (Throwable $e) {
  json_send(['ok'=>false,'error'=>'login_failed','detail'=>$e->getMessage()], 500);
}
