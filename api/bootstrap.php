<?php
// api/bootstrap.php — безопасная инициализация PDO без фаталов
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  @session_start();
}

$pdo = null;

// Подтягиваем конфиг, но не фейлимся, если его нет
$configFile = __DIR__ . '/config.php';
if (is_file($configFile)) {
  require_once $configFile;
}

// Достаём параметры подключения, если заданы
$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$db   = defined('DB_NAME') ? DB_NAME : 'rovin231_korabely';
$user = defined('DB_USER') ? DB_USER : 'rovin231_korabely';
$pass = defined('DB_PASS') ? DB_PASS : 'DCEVVWtLP8TYJ7Q95UuJ';

if ($db && $user) {
  try {
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
  } catch (Throwable $e) {
    // Оставляем $pdo = null. Все API-скрипты должны уметь жить с этим
    // и отдавать JSON-ошибку вместо HTTP 500.
  }
}
