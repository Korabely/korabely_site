<?php
// api/admin/tg_post.php
require_once __DIR__ . '/_auth.php';

require_admin();

$body = read_json_body();
$text = trim((string)($body['text'] ?? ''));
$parse = (string)($body['parse_mode'] ?? 'HTML');
$disablePreview = !empty($body['disable_web_page_preview']);

if ($text === '') {
  send_json(['ok'=>false,'error'=>'empty_text'], 400);
}

if (!defined('TG_BOT_TOKEN') || !defined('TG_CHAT_ID') || TG_BOT_TOKEN === 'PUT_YOUR_BOT_TOKEN_HERE') {
  send_json(['ok'=>false,'error'=>'tg_not_configured'], 500);
}

$apiUrl = 'https://api.telegram.org/bot'.rawurlencode(TG_BOT_TOKEN).'/sendMessage';

$payload = [
  'chat_id' => TG_CHAT_ID,
  'text' => $text,
  'parse_mode' => $parse,
  'disable_web_page_preview' => $disablePreview ? 'true' : 'false'
];

// cURL запрос
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($res === false) {
  send_json(['ok'=>false,'error'=>'curl_failed','detail'=>$err], 502);
}
$j = json_decode($res, true);
if (!$j || empty($j['ok'])) {
  send_json(['ok'=>false,'error'=>'tg_error','code'=>$code,'resp'=>$j], 502);
}
send_json(['ok'=>true, 'tg'=>$j]);
