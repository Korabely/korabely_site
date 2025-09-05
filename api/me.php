<?php
require_once __DIR__ . '/bootstrap.php';
require_method('GET');

$u = current_user();
if (!$u) {
  json_response(['auth'=>false, 'ok'=>false]);
}

if (empty($_SESSION['csrf'])) issue_csrf();

json_response([
  'auth' => true,
  'ok'   => true,
  'user' => [
    'id'        => (int)$u['id'],
    'username'  => $u['username'],
    'full_name' => $u['full_name'] ?: 'Користувач',
    'role'      => $u['role'],
    'info'      => $u['info'] ?? (($u['role']==='admin')?'Системний Адміністратор':'Звичайний користувач'),
  ],
  'full_name' => $u['full_name'] ?: 'Користувач',
  'csrf'      => $_SESSION['csrf'],
]);
