<?php
// api/csrf.php
require_once __DIR__ . '/bootstrap.php';

if (!function_exists('ensure_csrf_token')) {
  function ensure_csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
  }
}

if (!function_exists('check_csrf')) {
  function check_csrf(): bool {
    $token = $_POST['_csrf'] ?? ($_GET['_csrf'] ?? null);
    if (!$token) {
      $json = read_json_body();
      $token = $json['_csrf'] ?? ($json['csrf'] ?? null);
    }
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
  }
}
