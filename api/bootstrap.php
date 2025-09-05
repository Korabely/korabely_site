<?php
require_once __DIR__ . '/config.php';

if (APP_DEBUG) {
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
} else {
  error_reporting(E_ERROR | E_PARSE);
  ini_set('display_errors', '0');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  // SameSite=Lax, secure флаг ставится, если https
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function pdo(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function json_response($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function require_method(string $method): void {
  if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', $method) !== 0) {
    json_response(['ok'=>false,'error'=>'Method Not Allowed'], 405);
  }
}

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || $raw === '') return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

// CSRF: токен хранится в сессии и передаётся в заголовке X-CSRF-Token или в JSON {csrf:...}
function ensure_csrf(): void {
  $sess = $_SESSION['csrf'] ?? null;
  $hdr  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
  $body = null;
  if (!$hdr) {
    $body = read_json_body();
    $hdr = $body['csrf'] ?? null;
  }
  if (!$sess || !$hdr || !hash_equals($sess, $hdr)) {
    json_response(['ok'=>false, 'error'=>'CSRF'], 403);
  }
}
function issue_csrf(): string {
  $t = bin2hex(random_bytes(16));
  $_SESSION['csrf'] = $t;
  return $t;
}

function user_row_by_username(string $username) {
  $st = pdo()->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
  $st->execute([':u'=>$username]);
  return $st->fetch();
}

function current_user(): ?array {
  if (!isset($_SESSION['uid'])) return null;
  $st = pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
  $st->execute([':id'=>(int)$_SESSION['uid']]);
  return $st->fetch() ?: null;
}

function require_auth(): array {
  $u = current_user();
  if (!$u) json_response(['auth'=>false,'ok'=>false], 401);
  return $u;
}

function require_admin(): array {
  $u = require_auth();
  if (($u['role'] ?? 'user') !== 'admin') {
    json_response(['ok'=>false,'error'=>'Forbidden'], 403);
  }
  return $u;
}
