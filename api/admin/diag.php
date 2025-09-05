<?php
// api/admin/diag.php — простая диагностика
ob_start();
require_once __DIR__ . '/_auth.php';
require_admin();

$pdo = $GLOBALS['pdo'] ?? null;

function out($data, $code=200){
  while (ob_get_level()) ob_end_clean();
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$info = [
  'ok' => true,
  'php' => PHP_VERSION,
  'db_connected' => $pdo instanceof PDO,
];

if (!($pdo instanceof PDO)) {
  out($info + ['error'=>'db_not_connected','hint'=>'Перевір DB у config.php']);
}

try{
  $st = $pdo->prepare("SHOW TABLES LIKE 'users'");
  $st->execute();
  $hasTable = (bool)$st->fetchColumn();
  $info['users_table'] = $hasTable;

  if ($hasTable) {
    $cols = [];
    $cst = $pdo->query("SHOW COLUMNS FROM `users`");
    while ($row = $cst->fetch(PDO::FETCH_ASSOC)) { $cols[] = $row['Field']; }
    $info['users_columns'] = $cols;

    // попробуем посчитать строки
    $cnt = $pdo->query("SELECT COUNT(*) FROM `users`");
    $info['users_count'] = $cnt ? (int)$cnt->fetchColumn() : null;
  }
  out($info);
}catch(Throwable $e){
  out(['ok'=>false,'error'=>'diag_failed','detail'=>$e->getMessage()], 500);
}
