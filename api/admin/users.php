<?php
// api/admin/users.php — без SHOW TABLES, с created_at, чистый JSON и method-override
// info: только читаем (из БД), не обновляем и не требуем при создании
ob_start();

require_once __DIR__ . '/_auth.php';
$me = require_admin();

$pdo = $GLOBALS['pdo'] ?? null;
header('Cache-Control: no-store');

/* ---------- JSON helper ---------- */
function json_send($data, int $status = 200): void {
  while (ob_get_level()) ob_end_clean();
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/* ---------- Normalizers ---------- */
function norm_role($v){ $v = strtolower(trim((string)$v)); return in_array($v, ['admin','user'], true) ? $v : 'user'; }

/* ---------- Method override ---------- */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_GET['_method'] ?? null);
if ($override) $method = strtoupper($override);

/* ---------- Early checks ---------- */
if (!($pdo instanceof PDO)) {
  json_send(['ok'=>false,'error'=>'db_not_connected'], 500);
}

/* ---------- Helpers: мягкая проверка наличия таблицы по SELECT ---------- */
function users_table_exists(PDO $pdo): array {
  try { $pdo->query('SELECT 1 FROM `users` LIMIT 1'); return ['exists'=>true]; }
  catch (PDOException $e) {
    $info = $e->errorInfo ?? [];
    $sqlState = $e->getCode(); $mysqlCode = $info[1] ?? null;
    if ($sqlState === '42S02' || $mysqlCode === 1146) return ['exists'=>false];
    return ['exists'=>null, 'error'=>$e->getMessage(), 'sqlstate'=>$sqlState, 'mysql'=>$mysqlCode];
  } catch (Throwable $e) { return ['exists'=>null, 'error'=>$e->getMessage()]; }
}

/* ---------- GET: list users (возвращаем info как есть) ---------- */
if ($method === 'GET') {
  $t = users_table_exists($pdo);
  if ($t['exists'] === false) {
    json_send([
      'ok'=>false,'error'=>'table_users_missing_real',
      'sql'=>"CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL DEFAULT 'Користувач',
  `role` VARCHAR(16) NOT NULL DEFAULT 'user',
  `info` VARCHAR(255) NOT NULL DEFAULT 'Звичайний користувач',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ], 500);
  }
  if ($t['exists'] === null && !empty($t['error'])) {
    json_send(['ok'=>false,'error'=>'users_probe_failed','detail'=>$t['error'],'sqlstate'=>$t['sqlstate']??null,'mysql'=>$t['mysql']??null], 500);
  }

  $candidateSets = [
    ['id','username','full_name','role','info','created_at'],
    ['id','username','full_name','role','info'],
    ['id','username','full_name','role'],
    ['id','username']
  ];

  foreach ($candidateSets as $cols) {
    $sql = 'SELECT '.implode(',', array_map(fn($c)=>"`$c`", $cols)).' FROM `users` ORDER BY `id` DESC';
    try {
      $q = $pdo->query($sql);
      $rows = $q->fetchAll(PDO::FETCH_ASSOC);
      // если info нет в схеме — просто пусто
      if (!in_array('info',$cols,true)) { foreach ($rows as &$r) { $r['info'] = $r['info'] ?? ''; } }
      // если created_at нет — тоже пусто
      if (!in_array('created_at',$cols,true)) { foreach ($rows as &$r) { $r['created_at'] = $r['created_at'] ?? null; } }
      json_send(['ok'=>true,'users'=>$rows]);
    } catch (PDOException $e) {
      if (($e->errorInfo[1] ?? null) === 1054) continue;
      json_send(['ok'=>false,'error'=>'db_query_failed','detail'=>$e->getMessage()], 500);
    }
  }

  json_send(['ok'=>false,'error'=>'db_query_no_variant'], 500);
}

/* ---------- POST: create user (info НЕ трогаем) ---------- */
if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true) ?: [];
  $username  = trim((string)($body['username'] ?? ''));
  $password  = (string)($body['password'] ?? '');
  $full_name = trim((string)($body['full_name'] ?? 'Користувач'));
  $role      = norm_role($body['role'] ?? 'user');

  if ($username === '' || $password === '') {
    json_send(['ok'=>false,'error'=>'username_or_password_empty'], 400);
  }

  $t = users_table_exists($pdo);
  if ($t['exists'] === false) json_send(['ok'=>false,'error'=>'table_users_missing_real'], 500);
  if ($t['exists'] === null && !empty($t['error'])) json_send(['ok'=>false,'error'=>'users_probe_failed','detail'=>$t['error']], 500);

  try{
    $st = $pdo->prepare('SELECT COUNT(*) FROM `users` WHERE `username` = :u');
    $st->execute([':u'=>$username]);
    if ((int)$st->fetchColumn() > 0) json_send(['ok'=>false,'error'=>'username_taken'], 409);

    // пробуем без info; если БД ругнётся на отсутствие default — попробуем с дефолтом
    $variants = [
      ['fields'=>['username','password','full_name','role'], 'args'=>[':u'=>$username,':p'=>$password,':f'=>$full_name,':r'=>$role]],
      ['fields'=>['username','password','full_name','role','info'], 'args'=>[':u'=>$username,':p'=>$password,':f'=>$full_name,':r'=>$role,':i'=>'Звичайний користувач']],
    ];

    foreach ($variants as $v) {
      $sql = 'INSERT INTO `users` ('.implode(',', array_map(fn($c)=>"`$c`", $v['fields'])).') VALUES ('.implode(',', array_keys($v['args'])).')';
      try{
        $st2 = $pdo->prepare($sql);
        $st2->execute($v['args']);
        $id = (int)$pdo->lastInsertId();

        $created = null;
        try { $q2 = $pdo->prepare('SELECT created_at FROM `users` WHERE id = :id'); $q2->execute([':id'=>$id]); $created = ($q2->fetch(PDO::FETCH_ASSOC)['created_at'] ?? null); } catch (Throwable $e) {}

        // вернём info как в базе
        $info = '';
        try { $qi = $pdo->prepare('SELECT info FROM `users` WHERE id = :id'); $qi->execute([':id'=>$id]); $info = ($qi->fetch(PDO::FETCH_ASSOC)['info'] ?? ''); } catch (Throwable $e) {}

        json_send(['ok'=>true, 'user'=>[
          'id'=>$id,'username'=>$username,'full_name'=>$full_name,'role'=>$role,'info'=>$info,'created_at'=>$created
        ]], 201);
      }catch (PDOException $e){
        $code = $e->errorInfo[1] ?? null;
        if ($code === 1364 || $code === 1054) { // Field doesn't have a default / unknown column
          continue;
        }
        json_send(['ok'=>false,'error'=>'db_insert_failed','detail'=>$e->getMessage()], 500);
      }
    }

    json_send(['ok'=>false,'error'=>'db_insert_no_variant'], 500);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_insert_throw','detail'=>$e->getMessage()], 500);
  }
}

/* ---------- PUT/PATCH: update user (info НЕ обновляем) ---------- */
if ($method === 'PUT' || $method === 'PATCH') {
  $b = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($b['id'] ?? 0);
  if ($id <= 0) json_send(['ok'=>false,'error'=>'bad_id'], 400);

  try{
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
    if (array_key_exists('full_name',$b)) {
      $f = trim((string)$b['full_name']); if ($f==='') $f='Користувач';
      $fields[]='`full_name` = :f'; $args[':f']=$f;
    }
    if (array_key_exists('role',$b)) {
      $fields[]='`role` = :r'; $args[':r']=norm_role($b['role']);
    }

    if (!$fields) json_send(['ok'=>false,'error'=>'nothing_to_update'], 400);

    $sql = 'UPDATE `users` SET '.implode(', ',$fields).' WHERE `id` = :id';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    json_send(['ok'=>true]);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_update_throw','detail'=>$e->getMessage()], 500);
  }
}

/* ---------- DELETE: remove user ---------- */
if ($method === 'DELETE') {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)(json_decode(file_get_contents('php://input'), true)['id'] ?? 0);
  if ($id <= 0) json_send(['ok'=>false,'error'=>'bad_id'], 400);
  if ($id === (int)$me['id']) json_send(['ok'=>false,'error'=>'cant_delete_self'], 400);

  try{
    $st = $pdo->prepare('DELETE FROM `users` WHERE `id` = :id');
    $st->execute([':id'=>$id]);
    json_send(['ok'=>true]);
  }catch(Throwable $e){
    json_send(['ok'=>false,'error'=>'db_delete_throw','detail'=>$e->getMessage()], 500);
  }
}

json_send(['ok'=>false,'error'=>'method_not_allowed'], 405);
