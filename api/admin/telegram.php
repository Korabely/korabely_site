<?php
// /api/admin/telegram.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

session_start();

// ==== подключаем общий бутстрап, как в других API ====
$bootstrap = __DIR__ . '/../bootstrap.php';
if (file_exists($bootstrap)) {
  require_once $bootstrap;
}

// ==== единый guard admin (как в /api/admin/users.php) ====
$me = null;
if (function_exists('require_admin')) {
  // если в твоём bootstrap есть require_admin() — используем его
  $me = require_admin(); // обычно выбрасывает/делает http_response_code(403) сам
} elseif (function_exists('api_require_admin')) {
  $me = api_require_admin();
} else {
  // мягкий фолбэк на сессию (если нет функций из bootstrap)
  $role = $_SESSION['user']['role'] ?? 'user';
  if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'forbidden']);
    exit;
  }
  $me = $_SESSION['user'];
}

// ==== конфиг Telegram ====
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

// Пинг для проверки (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo json_encode(['ok'=>true,'ping'=>'telegram','user'=>$me['username'] ?? null,'role'=>$me['role'] ?? null]);
  exit;
}

// ==== читаем вход ====
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

// ==== вызов Telegram ====
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

// приоритет: файл > dataURL > URL > текст
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

[$code, $errno, $err, $resp] = tg_call($BOT_TOKEN, $method, $fields, $files);

if ($errno) { http_response_code(502); echo json_encode(['ok'=>false,'error'=>'curl_error','details'=>$err]); exit; }
if ($code < 200 || $code >= 300) { http_response_code($code ?: 500); echo json_encode(['ok'=>false,'error'=>'telegram_http_'.$code,'body'=>$resp]); exit; }

$out = json_decode((string)$resp, true);
if (!is_array($out) || empty($out['ok'])) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'telegram_api','body'=>$resp]); exit; }

echo json_encode(['ok'=>true,'result'=>$out['result'] ?? null]);
