<?php
// api/admin/_auth.php
require_once __DIR__ . '/../bootstrap.php';

// Безопасные хелперы JSON с защитой от повторного объявления
if (!function_exists('send_json')) {
  function send_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }
}

if (!function_exists('read_json_body')) {
  function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
  }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  @session_start();
}

function current_user_row(): ?array {
  if (empty($_SESSION['uid'])) return null;
  $uid = (int)$_SESSION['uid'];
  $pdo = $GLOBALS['pdo'] ?? null;
  if (!$pdo instanceof PDO) return null;
  $st = $pdo->prepare('SELECT id, username, full_name, role, info FROM users WHERE id = :id LIMIT 1');
  $st->execute([':id' => $uid]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function require_admin(): array {
  $me = current_user_row();
  if (!$me || strtolower((string)$me['role']) !== 'admin') {
    send_json(['ok' => false, 'error' => 'forbidden'], 403);
  }
  return $me;
}
