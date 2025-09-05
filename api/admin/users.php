<?php
// api/admin/users.php — надёжный JSON + проверки PDO/таблиц/колонок
ob_start();

require_once __DIR__ . '/_auth.php';
$me = require_admin();

$pdo = $GLOBALS['pdo'] ?? null;
header('Cache-Control: no-store');

/* ===== JSON helper ===== */
function json_send($data, int $status = 200): void {
  while (ob_get_level()) ob_end_clean();
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/* ===== DB helpers ===== */
function db_ok($pdo): bool { return $pdo instanceof PDO; }

function table_exists(PDO $pdo, string $name): bool {
  try{
    $st = $pdo->prepare("SHOW TABLES LIKE :t");
    $st->execute([':t'=>$name]);
    return (bool)$st->fetchColumn();
  }catch(Throwable $e){ return false; }
}

function get_columns(PDO $pdo, string $table = 'users'): array {
  try{
    $st = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    if (!$st) return [];
    $cols = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      if (!empty($row['Field'])) $cols[] = $row['Field'];
    }
    return $cols;
  }catch(Throwable $e){ return []; }
}

function ensure_column(PDO $pdo, string $table, string $col, string $ddl): void {
  $cols = get_columns($pdo, $table);
  if (!in_array($col, $cols, true)) {
    try { $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$ddl}"); } catch(Throwable $e) {}
  }
}
function ensure_min_schema(PDO $pdo): void {
  if (!table_exists($pdo, 'users')) return;
  ensure_column($pdo, 'users', 'full_name', " `full_name` VARCHAR(255) NOT NULL DEFAULT 'Користувач' ");
  ensure_column($pdo, 'users', 'role',      " `role` VARCHAR(16) NOT NULL DEFAULT 'user' ");
  ensure_column($pdo, 'users', 'info',      " `info` VARCHAR(255) NOT NULL DEFAULT 'Звичайний користувач' ");
}

/* ===== Normalizers ===== */
function norm_role($v){ $v = strtolower(trim((string)$v)); return in_array($v, ['admin','user'], true) ? $v : 'user'; }
function norm_info($v){ $v = trim((string)$v); return $v === '' ? 'Звичайний користувач' : $v; }

/* ===== Early checks ===== */
if (!db_ok($pdo)) {
  json_send(['ok'=>false,'error'=>'db_not_connected','hint'=>'Перевір DB у config.php (PDO не ініціалізований)'], 500);
}
if (!table_exists($pdo, 'users')) {
  json_send([
    'ok'=>false,
    'error'=>'table_users_missing',
    'hint'=>'Створи таблицю users (SQL нижче)',
    'sql'=>"CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL DEFAULT 'Користувач',
  `role` VARCHAR(16) NOT NULL DEFAULT 'user',
  `info` VARCHAR(255) NOT NULL DEFAULT 'Звичайний користувач',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
  ], 500);
}

/* ===== Try soft-migrate columns (best effort) ===== */
try { ensure_min_schema($pdo); } catch(Throwable $e) {}

/* ===== Routing ===== */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* GET — list users */
if ($method === 'GET') {
  try{
    $cols = get_columns($pdo, 'users');
    $want = ['id','username','full_name','role','info'];
    $sel = array_values(array_intersect($want, $cols));
    if (!$sel) $sel = ['id','username']; // fallback очень старой схемы

    $sql = 'SELECT '.implode(',', array_map(fn($c)=>"`$c`", $sel)).' FROM `users` ORDER BY `id` DESC';
    $q = $pdo->query($sql);
    if ($q === false) {
      $ei = $pdo->errorInfo();
      json_send(['ok'=>false,'error'=>'db_query_failed','detail'=>$ei[2] ?? ''], 500);
    }
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    if (!in_array('info',$sel,true)) { foreach ($rows as &$r) { $r['info'] = $r['info'] ?? 'Звичайний користувач'; } }
    json_send(['ok'=>true,'users'=>$rows]);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_query_throw','detail'=>$e->getMessage()], 500);
  }
}

/* POST — create user */
if ($method === 'POST') {
  $b = read_json_body();
  $username  = trim((string)($b['username'] ?? ''));
  $password  = (string)($b['password'] ?? '');
  $full_name = trim((string)($b['full_name'] ?? 'Користувач'));
  $role      = norm_role($b['role'] ?? 'user');
  $info      = norm_info($b['info'] ?? 'Звичайний користувач');

  if ($username === '' || $password === '') {
    json_send(['ok'=>false,'error'=>'username_or_password_empty'], 400);
  }

  try{
    $st = $pdo->prepare('SELECT COUNT(*) FROM `users` WHERE `username` = :u');
    $st->execute([':u'=>$username]);
    if ((int)$st->fetchColumn() > 0) {
      json_send(['ok'=>false,'error'=>'username_taken'], 409);
    }

    $cols = get_columns($pdo, 'users');
    $hasF = in_array('full_name',$cols,true);
    $hasR = in_array('role',$cols,true);
    $hasI = in_array('info',$cols,true);

    $fields=['username','password']; $vals=[':u',':p']; $args=[':u'=>$username, ':p'=>$password];
    if ($hasF){ $fields[]='full_name'; $vals[]=':f'; $args[':f']=$full_name; }
    if ($hasR){ $fields[]='role';      $vals[]=':r'; $args[':r']=$role; }
    if ($hasI){ $fields[]='info';      $vals[]=':i'; $args[':i']=$info; }

    $sql = 'INSERT INTO `users`('.implode(',',array_map(fn($c)=>"`$c`",$fields)).') VALUES ('.implode(',',$vals).')';
    $st2 = $pdo->prepare($sql);
    if (!$st2->execute($args)) {
      $ei = $st2->errorInfo();
      json_send(['ok'=>false,'error'=>'db_insert_failed','detail'=>$ei[2] ?? ''], 500);
    }

    $id = (int)$pdo->lastInsertId();
    json_send(['ok'=>true,'user'=>[
      'id'=>$id,'username'=>$username,'full_name'=>$full_name,'role'=>$role,'info'=>$info
    ]], 201);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_insert_throw','detail'=>$e->getMessage()], 500);
  }
}

/* PUT/PATCH — update */
if ($method === 'PUT' || $method === 'PATCH') {
  $b = read_json_body();
  $id = (int)($b['id'] ?? 0);
  if ($id <= 0) json_send(['ok'=>false,'error'=>'bad_id'], 400);

  try{
    $cols = get_columns($pdo, 'users');
    $hasF = in_array('full_name',$cols,true);
    $hasR = in_array('role',$cols,true);
    $hasI = in_array('info',$cols,true);

    $fields=[]; $args=[':id'=>$id];

    if (array_key_exists('username',$b)) {
      $u = trim((string)$b['username']); if ($u==='') json_send(['ok'=>false,'error'=>'empty_username'], 400);
      $st = $pdo->prepare('SELECT COUNT(*) FROM `users` WHERE `username` = :u AND `id` <> :id');
      $st->execute([':u'=>$u, ':id'=>$id]);
      if ((int)$st->fetchColumn() > 0) json_send(['ok'=>false,'error'=>'username_taken'], 409);
      $fields[]='`username` = :u'; $args[':u']=$u;
    }
    if (array_key_exists('password',$b)) {
      $fields[]='`password` = :p'; $args[':p']=(string)$b['password'];
    }
    if ($hasF && array_key_exists('full_name',$b)) {
      $f = trim((string)$b['full_name']); if ($f==='') $f='Користувач';
      $fields[]='`full_name` = :f'; $args[':f']=$f;
    }
    if ($hasR && array_key_exists('role',$b)) {
      $fields[]='`role` = :r'; $args[':r']=norm_role($b['role']);
    }
    if ($hasI && array_key_exists('info',$b)) {
      $fields[]='`info` = :i'; $args[':i']=norm_info($b['info']);
    }

    if (!$fields) json_send(['ok'=>false,'error'=>'nothing_to_update'], 400);

    $sql = 'UPDATE `users` SET '.implode(', ',$fields).' WHERE `id` = :id';
    $st = $pdo->prepare($sql);
    if (!$st->execute($args)) {
      $ei = $st->errorInfo();
      json_send(['ok'=>false,'error'=>'db_update_failed','detail'=>$ei[2] ?? ''], 500);
    }
    json_send(['ok'=>true]);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_update_throw','detail'=>$e->getMessage()], 500);
  }
}

/* DELETE — remove */
if ($method === 'DELETE') {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)(read_json_body()['id'] ?? 0);
  if ($id <= 0) json_send(['ok'=>false,'error'=>'bad_id'], 400);
  if ($id === (int)$me['id']) json_send(['ok'=>false,'error'=>'cant_delete_self'], 400);

  try{
    $st = $pdo->prepare('DELETE FROM `users` WHERE `id` = :id');
    if (!$st->execute([':id'=>$id])) {
      $ei = $st->errorInfo();
      json_send(['ok'=>false,'error'=>'db_delete_failed','detail'=>$ei[2] ?? ''], 500);
    }
    json_send(['ok'=>true]);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_delete_throw','detail'=>$e->getMessage()], 500);
  }
}

json_send(['ok'=>false,'error'=>'method_not_allowed'], 405);
