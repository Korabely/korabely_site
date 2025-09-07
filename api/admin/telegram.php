<?php
// /api/admin/telegram.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// 1) Підключаємо bootstrap, щоб підхопити session_name(), PDO тощо
$bootstrap = __DIR__ . '/../bootstrap.php';
if (file_exists($bootstrap)) {
  require_once $bootstrap;
}

// 2) Стартуємо сесію, якщо ще ні
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// === helpers: дістати PDO з bootstrap різними способами ===
function _get_pdo(): ?PDO {
  if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
  if (function_exists('pdo')) { $p = pdo(); if ($p instanceof PDO) return $p; }
  if (function_exists('db'))  { $p = db();  if ($p instanceof PDO) return $p; }
  if (function_exists('get_db')){ $p = get_db(); if ($p instanceof PDO) return $p; }
  return null;
}

// === завантажуємо користувача з БД за сесією (uid/username) ===
function current_user_from_db(): ?array {
  $pdo = _get_pdo();
  if (!$pdo) return null;

  $uid = $_SESSION['uid'] ?? $_SESSION['user']['id'] ?? null;
  $uname = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? null;

  try {
    if ($uid !== null && $uid !== '') {
      $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE id = ? LIMIT 1');
      $stmt->execute([ (int)$uid ]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) return $row;
    }
    if ($uname) {
      $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE username = ? LIMIT 1');
      $stmt->execute([ (string)$uname ]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) return $row;
    }
  } catch (Throwable $e) {
    // Падаємо мʼяко — далі вернемо 500 з помилкою БД
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'db_error','details'=>$e->getMessage()]);
    exit;
  }
  return null;
}

// === guard: тільки admin з БД ===
$user = current_user_from_db();
if (!$user) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'forbidden','hint'=>'no user by session']);
  exit;
}
if (($user['role'] ?? 'user') !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'forbidden','hint'=>'role='.$user['role']]);
  exit;
}

// 4) Конфіг Telegram
$cfgPath = __DIR__ . '/../../config.telegram.php';
if (!file_exists($cfgPath)) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'config_missing']);
  exit;
}
$tg = require $cfgPath;
$BOT_TOKEN = trim((string)($tg['BOT_TOKEN'] ?? ''));
$CHAT_ID   = (string)($tg['CHAT_ID'] ?? '');
$DEF_PARSE = (string)($tg['DEFAULT_PARSE_MODE'] ?? 'HTML');
if ($BOT_TOKEN === '' || $CHAT_ID === '') {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'config_invalid']);
  exit;
}

// 5) Пінг (GET) — швидка перевірка
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo json_encode([
    'ok'=>true,'ping'=>'telegram',
    'user'=>$user['username'] ?? null,
    'role'=>$user['role'] ?? null
  ]);
  exit;
}

// 6) Вхідні дані
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = stripos($ct, 'application/json') !== false;

if ($isJson) {
  $in  = json_decode(file_get_contents('php://input'), true) ?: [];
  $text   = trim((string)($in['text'] ?? ''));
  $photoUrl = trim((string)($in['photo_url'] ?? ''));
  $photoDataURL = trim((string)($in['photo_data_url'] ?? ''));
  $parse  = $in['parse_mode'] ?? $DEF_PARSE;
  $silent = !empty($in['disable_notification']) ? 1 : 0;
  $noPrev = !empty($in['disable_web_page_preview']) ? 1 : 0;
} else {
  $text   = trim((string)($_POST['text'] ?? ''));
  $photoUrl = trim((string)($_POST['photo_url'] ?? ''));
  $photoDataURL = trim((string)($_POST['photo_data_url'] ?? ''));
  $parse  = $_POST['parse_mode'] ?? $DEF_PARSE;
  $silent = !empty($_POST['disable_notification']) ? 1 : 0;
  $noPrev = !empty($_POST['disable_web_page_preview']) ? 1 : 0;
}

// 7) Виклик Telegram
function tg_call(string $token, string $method, array $fields, array $files = []){
  if (!function_exists('curl_init')) {
    return [500, 0, 'php-curl not installed', null];
  }
  $url = "https://api.telegram.org/bot{$token}/{$method}";
  $ch = curl_init($url);
  if ($files) foreach ($files as $k=>$f) $fields[$k] = $f;
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_POSTFIELDS     => $fields,
  ]);
  $resp = curl_exec($ch);
  $errno = curl_errno($ch);
  $err   = curl_error($ch);
  $code  = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  return [$code, $errno, $err, $resp];
}

// 8) Пріоритет: файл > dataURL > URL > текст
$method = 'sendMessage';
$fields = [
  'chat_id' => $CHAT_ID,
  'disable_notification'      => $silent,
  'disable_web_page_preview'  => $noPrev,
];
if (!empty($parse)) $fields['parse_mode'] = $parse;
$files = [];

if (!empty($_FILES['photo']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
  $mime = mime_content_type($_FILES['photo']['tmp_name']) ?: 'image/jpeg';
  $files['photo'] = new CURLFile($_FILES['photo']['tmp_name'], $mime, $_FILES['photo']['name']);
  $method = 'sendPhoto';
  if ($text !== '') $fields['caption'] = $text;
} elseif ($photoDataURL && preg_match('~^data:(image/\w+);base64,(.+)$~', $photoDataURL, $m)) {
  $mime = $m[1];
  $bin  = base64_decode($m[2], true);
  if ($bin !== false) {
    $tmp = tempnam(sys_get_temp_dir(), 'tg_');
    file_put_contents($tmp, $bin);
    $files['photo'] = new CURLFile($tmp, $mime, 'image');
    $method = 'sendPhoto';
    if ($text !== '') $fields['caption'] = $text;
  }
} elseif ($photoUrl) {
  $method = 'sendPhoto';
  $fields['photo'] = $photoUrl;
  if ($text !== '') $fields['caption'] = $text;
} else {
  $method = 'sendMessage';
  $fields['text'] = ($text !== '') ? $text : ' ';
}

// 9) Відправка
[$code, $errno, $err, $resp] = tg_call($BOT_TOKEN, $method, $fields, $files);

if ($errno) { http_response_code(502); echo json_encode(['ok'=>false,'error'=>'curl_error','details'=>$err]); exit; }
if ($code < 200 || $code >= 300) { http_response_code($code ?: 500); echo json_encode(['ok'=>false,'error'=>'telegram_http_'.$code,'body'=>$resp]); exit; }

$out = json_decode((string)$resp, true);
if (!is_array($out) || empty($out['ok'])) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'telegram_api','body'=>$resp]); exit; }

echo json_encode(['ok'=>true,'result'=>$out['result'] ?? null]);
