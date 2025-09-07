<?php
// /api/alerts.php — прокси до ubilling AerialAlerts, з легким кешем
declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$urls = [
  'https://ubilling.net.ua/aerialalerts/',
  'http://ubilling.net.ua/aerialalerts/',
];

$cacheDir  = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/alerts.json';
$cacheTtl  = 20; // секунд

function json_send($data, int $status = 200): void {
  while (ob_get_level()) ob_end_clean();
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function fetch_url(string $url, int $timeout = 4): ?string {
  // пробуем cURL
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => $timeout,
      CURLOPT_TIMEOUT        => $timeout,
      CURLOPT_USERAGENT      => 'KorabelyAlertsProxy/1.0',
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300 && $body) return $body;
  }
  // fallback: file_get_contents
  $ctx = stream_context_create([
    'http' => ['timeout' => $timeout, 'header' => "User-Agent: KorabelyAlertsProxy/1.0\r\n"],
    'https'=> ['timeout' => $timeout, 'header' => "User-Agent: KorabelyAlertsProxy/1.0\r\n"],
  ]);
  $body = @file_get_contents($url, false, $ctx);
  return $body ?: null;
}

// свежий кеш?
if (is_file($cacheFile) && (time() - filemtime($cacheFile) <= $cacheTtl)) {
  $raw = @file_get_contents($cacheFile);
  if ($raw) {
    $j = json_decode($raw, true);
    if (is_array($j)) {
      $j['_meta']['cached'] = true;
      json_send($j);
    }
  }
}

// тянем с апи (https -> http)
foreach ($urls as $url) {
  $raw = fetch_url($url);
  if (!$raw) continue;
  $j = json_decode($raw, true);
  if (!is_array($j)) continue;

  // ожидаем объект с ключём states
  if (!isset($j['states']) || !is_array($j['states'])) continue;

  // добавим мету и закешируем
  $j['_meta'] = [
    'source' => $url,
    'cached' => false,
    'ts'     => time(),
  ];
  if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
  @file_put_contents($cacheFile, json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

  json_send($j);
}

// если совсем плохо — отдадим «протухший» кеш, если есть
if (is_file($cacheFile)) {
  $raw = @file_get_contents($cacheFile);
  if ($raw) {
    $j = json_decode($raw, true);
    if (is_array($j)) {
      $j['_meta']['cached'] = true;
      $j['_meta']['stale']  = true;
      json_send($j);
    }
  }
}

// финальный фейл
json_send(['error' => 'unavailable', 'message' => 'alerts API unreachable'], 502);
