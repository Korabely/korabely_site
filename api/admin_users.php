<?php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// любая операция админки требует админа
require_admin();

switch ($method) {
  case 'GET': // список пользователей
    $st = pdo()->query('SELECT id, username, full_name, role, info, created_at FROM users ORDER BY id DESC');
    $list = $st->fetchAll();
    json_response(['ok'=>true, 'users'=>$list]);

  case 'POST': // создать
    ensure_csrf();
    $b = read_json_body();
    $username  = trim((string)($b['username'] ?? ''));
    $password  = (string)($b['password'] ?? '');
    $full_name = trim((string)($b['full_name'] ?? 'Користувач'));
    $role      = ($b['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

    if ($username === '' || $password === '') {
      json_response(['ok'=>false, 'error'=>'username/password required'], 400);
    }

    $st = pdo()->prepare('INSERT INTO users (username,password,full_name,role) VALUES (:u,:p,:f,:r)');
    try {
      $st->execute([':u'=>$username, ':p'=>$password, ':f'=>$full_name ?: 'Користувач', ':r'=>$role]);
    } catch (PDOException $e) {
      json_response(['ok'=>false, 'error'=>'duplicate or db error'], 400);
    }
    json_response(['ok'=>true, 'id'=>(int)pdo()->lastInsertId()]);
    break;

  case 'PUT': // редактировать
    ensure_csrf();
    $b = read_json_body();
    $id = (int)($b['id'] ?? 0);
    if ($id<=0) json_response(['ok'=>false,'error'=>'id required'],400);

    // собираем апдейты
    $sets = [];
    $args = [':id'=>$id];
    if (isset($b['username']))  { $sets[]='username=:u';  $args[':u']=trim((string)$b['username']); }
    if (isset($b['password']))  { $sets[]='password=:p';  $args[':p']=(string)$b['password']; }
    if (isset($b['full_name'])) { $sets[]='full_name=:f'; $args[':f']=trim((string)$b['full_name']) ?: 'Користувач'; }
    if (isset($b['role']))      { $r = $b['role']==='admin'?'admin':'user'; $sets[]='role=:r'; $args[':r']=$r; }

    if (!$sets) json_response(['ok'=>false,'error'=>'nothing to update'],400);

    $sql = 'UPDATE users SET '.implode(',', $sets).' WHERE id=:id LIMIT 1';
    $st = pdo()->prepare($sql);
    $st->execute($args);
    json_response(['ok'=>true]);
    break;

  case 'DELETE': // удалить
    ensure_csrf();
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if ($id<=0) json_response(['ok'=>false,'error'=>'id required'],400);
    // нельзя удалить себя-админа (опционально)
    if ($id === (int)($_SESSION['uid'] ?? 0)) {
      json_response(['ok'=>false,'error'=>'Неможливо видалити поточного адміністратора'],400);
    }
    $st = pdo()->prepare('DELETE FROM users WHERE id=:id LIMIT 1');
    $st->execute([':id'=>$id]);
    json_response(['ok'=>true]);
    break;

  default:
    json_response(['ok'=>false,'error'=>'Method Not Allowed'],405);
}
