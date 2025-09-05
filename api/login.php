<?php
require_once __DIR__ . '/bootstrap.php';
require_method('POST');

$body = read_json_body();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
  json_response(['ok'=>false, 'error'=>'Empty credentials'], 400);
}

$row = user_row_by_username($username);
if (!$row || !hash_equals((string)$row['password'], $password)) {
  json_response(['ok'=>false, 'error'=>'Invalid credentials'], 401);
}

$_SESSION['uid']       = (int)$row['id'];
$_SESSION['username']  = $row['username'];
$_SESSION['full_name'] = $row['full_name'] ?: 'Користувач';
$_SESSION['role']      = $row['role'] ?: 'user';

$csrf = issue_csrf();

json_response([
  'ok' => true,
  'auth' => true,
  'user' => [
    'id'        => (int)$row['id'],
    'username'  => $row['username'],
    'full_name' => $_SESSION['full_name'],
    'role'      => $_SESSION['role'],
    'info'      => $row['info'] ?? (($_SESSION['role']==='admin')?'Системний Адміністратор':'Звичайний користувач'),
  ],
  'full_name' => $_SESSION['full_name'],
  'csrf'      => $csrf,
]);
