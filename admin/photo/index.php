<?php
// /admin/photo/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../api/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

// Server-side guard: only admin
$uid = (int)($_SESSION['uid'] ?? 0);
$isAdmin = false;
try {
  /** @var PDO|null $pdo */
  $pdo = $GLOBALS['pdo'] ?? null;
  if ($uid > 0 && $pdo instanceof PDO) {
    $st = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $st->execute([':id' => $uid]);
    $role = $st->fetchColumn();
    if ($role === 'admin') $isAdmin = true;
  }
} catch (Throwable $e) {}
if (!$isAdmin) { header('Location: /'); exit; }
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — Водяні знаки</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{
      --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb;
      --btn:#2a2a2a; --send:#6b6b6b; --send-hover:#8a8a8a;
    }
    *{ box-sizing:border-box; -webkit-tap-highlight-color: transparent; }
    html,body{ margin:0; height:100%; background:var(--bg); color:var(--text); font-family:Arial, sans-serif; }
    a{ color:#fff; text-decoration:none }
    .wrap{ max-width:1100px; margin:0 auto; padding:18px; position:relative; min-height:100dvh; }

    .top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; }
    .title{ font-size:20px; font-weight:700; letter-spacing:.02em }
    .btn{
      background:var(--btn); border:1px solid var(--border); color:#fff; cursor:pointer; border-radius:10px;
      padding:12px 14px; font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.6); font-size:14px; line-height:1;
    }
    .btn:hover{ background:#fff; color:#111; border-color:#aaa }

    .grid{ display:grid; grid-template-columns: 1.1fr 0.9fr; gap:16px; }
    @media (max-width: 900px){ .grid{ grid-template-columns: 1fr; } }

    .panel{ background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:14px; position:relative; }
    .h{ font-weight:700; margin:0 0 10px; font-size:16px; color:#eee; }

    .row{ display:grid; grid-template-columns:180px 1fr; gap:10px; align-items:center; margin-bottom:12px; }
    .row label{ color:#ddd; font-size:14px; }
    .sub{ color:var(--muted); font-size:12px; }

    input[type="file"]{ color:#ddd; }
    .radio-line{ display:flex; gap:10px; flex-wrap:wrap }
    .chip{
      background:#222; border:1px solid var(--border); color:#fff; border-radius:10px; padding:10px 12px;
      cursor:pointer; font-weight:600; font-size:14px; line-height:1; min-height:40px; display:inline-flex; align-items:center;
    }
    .chip input{ margin-right:8px }

    .drop{
      border:1px dashed #555; border-radius:12px; padding:14px; text-align:center; color:#ccc;
      background:#1b1b1b; cursor:pointer;
    }
    .drop.drag{ background:#242424; border-color:#888; }

    .preview{
      background:#0f0f0f; border:1px solid #222; border-radius:12px; padding:10px; text-align:center; min-height:220px;
      display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative;
    }
    .preview img, .preview canvas{ max-width:100%; max-height:64vh; display:block; border-radius:8px; }

    .panel-actions{
      display:flex; justify-content:flex-end; gap:8px; margin-top:12px;
      position: sticky; bottom: 12px; /* робимо "липкою" на мобільному */
    }
    .gen-btn{
      height:46px; min-width:160px; border-radius:12px; border:1px solid #555; background:var(--send); color:#fff;
      font-weight:700; font-size:14px; box-shadow:0 2px 8px rgba(0,0,0,.45); cursor:pointer; padding:0 16px;
    }
    .gen-btn:hover{ background:var(--send-hover) }
    .gen-btn:disabled{ opacity:.6; cursor:not-allowed }

    /* slider */
    .range-wrap{ display:flex; align-items:center; gap:12px }
    .range-wrap input[type="range"]{ width:100%; height:32px; }
    .range-val{ min-width:48px; text-align:right; color:#ddd; font-variant-numeric: tabular-nums; font-size:14px; }

    .sys{
      position:fixed; left:16px; bottom:16px; right:220px; color:#ddd; font-size:13px;
      background:#222; border:1px solid var(--border); border-radius:10px; padding:10px 12px; display:none;
    }
    .sys.show{ display:block; }
    .sys.ok{ border-color:#2c6; color:#bff2cf; background:#162a20; }
    .sys.err{ border-color:#c44; color:#ffd0d0; background:#2a1616; }

    /* ======= Мобільна адаптація ======= */
    @media (max-width: 680px){
      .wrap{ padding:14px; }
      .title{ font-size:18px; }
      .btn{ padding:10px 12px; border-radius:12px; }
      .panel{ padding:12px; border-radius:14px; }
      .h{ font-size:15px; margin-bottom:8px; }

      .row{ grid-template-columns: 1fr; gap:6px; margin-bottom:10px; }
      .row label{ margin-bottom:2px; font-size:13px; }

      .chip{ min-height:44px; padding:10px 12px; font-size:14px; border-radius:12px; }
      .drop{ padding:16px; border-radius:12px; }

      .preview{ min-height:38vh; }
      .preview img, .preview canvas{ max-height:58vh; }

      .panel-actions{ bottom: max(12px, env(safe-area-inset-bottom)); }
      .gen-btn{ width:100%; height:50px; font-size:15px; }
      .sys{ left:12px; right:12px; bottom: calc(12px + env(safe-area-inset-bottom)); }
    }

    @media (max-width: 420px){
      .title{ font-size:17px; }
      .chip{ font-size:13px; }
      .range-val{ min-width:42px; font-size:13px; }
    }

    /* інпут/селект/textarea — трохи більші торк-мішені */
    textarea{
      width:100%; min-height:160px; resize:vertical;
      background:#222; color:#fff; border:1px solid var(--border); border-radius:12px; padding:12px;
      outline:none; font-size:14px;
    }
    input[type="text"], select{
      width:100%; background:#222; color:#fff; border:1px solid var(--border); border-radius:12px; padding:12px;
      outline:none; font-size:14px;
    }
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="title">Водяні знаки для фото</div>
    <div class="nav">
      <a class="btn" href="/admin/">← Назад</a>
    </div>
  </div>

  <div class="grid">
    <!-- Ліва: налаштування -->
    <div class="panel">
      <h3 class="h">Джерело зображення</h3>

      <div class="row">
        <label>Фото (файл)</label>
        <div><input id="src_file" type="file" accept="image/*"></div>
      </div>

      <div class="row">
        <label>Швидке завантаження</label>
        <div>
          <div id="drop" class="drop">Перетягніть сюди файл або натисніть</div>
        </div>
      </div>

      <div class="row">
        <label>Варіант</label>
        <div class="radio-line">
          <label class="chip"><input type="radio" name="variant" value="1" checked> 1) Лого по центру + сітка</label>
          <label class="chip"><input type="radio" name="variant" value="2"> 2) Тільки сітка</label>
          <label class="chip"><input type="radio" name="variant" value="3"> 3) Лого по центру + підпис знизу</label>
        </div>
      </div>

      <div class="row">
        <label>Прозорість</label>
        <div class="range-wrap">
          <input id="alpha_slider" type="range" min="5" max="100" value="30" step="1" />
          <div class="range-val"><span id="alpha_val">30</span>%</div>
        </div>
        <div class="sub" style="grid-column:1/-1;">Впливає на лого, сітку та підпис знизу (для варіанту 3).</div>
      </div>

      <div class="sub" style="margin-top:6px">
        У варіанті 3 сітки немає. Лого та підпис — значно прозоріші за замовчуванням.
      </div>
    </div>

    <!-- Права: прев’ю + кнопка -->
    <div class="panel">
      <h3 class="h">Прев’ю</h3>
      <div id="preview" class="preview"><span class="sub">Завантажте фото — тут з’явиться готовий результат</span></div>
      <div class="panel-actions">
        <button id="gen" class="gen-btn" disabled>Згенерувати</button>
      </div>
    </div>
  </div>
</div>

<!-- Системні повідомлення -->
<div id="sys" class="sys"></div>

<script>
const $ = s => document.querySelector(s);
const sys = $('#sys');
function toast(msg, kind='ok'){ sys.textContent = msg; sys.className = 'sys show ' + (kind==='ok'?'ok':'err'); setTimeout(()=>{ sys.classList.remove('show'); }, 2600); }

// ===== Константи (бази для 30%) =====
const LOGO_URL         = 'https://i.postimg.cc/W1ZcFpbK/image.png';
const LOGO_PCT         = 28;    // % від меншої сторони

const BASE_LOGO_ALPHA  = 0.28;  // при повзунку 30%
const BASE_TILE_ALPHA  = 12;    // % при повзунку 30%
const BASE_SIGN_ALPHA  = 0.32;  // при повзунку 30%

const TILE_X           = 3;
const TILE_Y           = 4;
const TILE_CHESS       = true;

const SIGN_SIZE_K      = 0.028;

const srcFile   = $('#src_file');
const dropEl    = $('#drop');
const genBtn    = $('#gen');
const preview   = $('#preview');
const alphaSl   = $('#alpha_slider');
const alphaVal  = $('#alpha_val');

let srcImage = null;   // HTMLImageElement
let logoImage = null;  // HTMLImageElement | null
let lastBlobUrl = null;

// helpers
function loadImageFromFile(file){
  return new Promise((resolve, reject)=>{
    const fr = new FileReader();
    fr.onload = () => {
      const img = new Image();
      img.onload = ()=> resolve(img);
      img.onerror = ()=> reject(new Error('Не вдалося завантажити зображення'));
      img.src = fr.result;
    };
    fr.onerror = ()=> reject(new Error('Помилка читання файлу'));
    fr.readAsDataURL(file);
  });
}
function loadImageFromUrl(url){
  return new Promise((resolve, reject)=>{
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = ()=> resolve(img);
    img.onerror = ()=> reject(new Error('Не вдалося завантажити зображення (URL)'));
    img.src = url;
  });
}
async function ensureLogoLoaded(){
  if (logoImage) return true;
  try{
    logoImage = await loadImageFromUrl(LOGO_URL);
    return true;
  }catch(e){
    logoImage = null;
    return false;
  }
}

function setPreviewNode(node){
  preview.innerHTML = '';
  preview.appendChild(node);
}

// drag&drop
dropEl.addEventListener('click', ()=> srcFile.click());
dropEl.addEventListener('dragover', (e)=>{ e.preventDefault(); dropEl.classList.add('drag'); });
dropEl.addEventListener('dragleave', ()=> dropEl.classList.remove('drag'));
dropEl.addEventListener('drop', async (e)=>{
  e.preventDefault(); dropEl.classList.remove('drag');
  const f = e.dataTransfer.files && e.dataTransfer.files[0];
  if (!f) return;
  srcFile.files = e.dataTransfer.files;
  await onSourceSelected();
});
srcFile.addEventListener('change', onSourceSelected);

// сітка (шахматка)
function drawTiledWatermarks(ctx, w, h, countX, countY, alphaPct, chess){
  const countx = Math.max(2, Math.min(12, parseInt(countX||3,10)));
  const county = Math.max(2, Math.min(12, parseInt(countY||4,10)));
  const alpha  = Math.max(2, Math.min(50, parseInt(alphaPct||12,10))) / 100;

  const cellW = w / countx;
  const cellH = h / county;

  const base = Math.min(w, h);
  const line1 = Math.max(16, Math.round(base * 0.040)); // "КОРАБЕЛИ"
  const line2 = Math.max(10, Math.round(line1 * 0.55)); // "t.me/korabely_media"

  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = `rgba(255,255,255,${alpha})`;
  ctx.shadowColor = 'rgba(0,0,0,0.25)';

  for (let y=0; y<county; y++){
    for (let x=0; x<countx; x++){
      if (chess && ((x + y) % 2 === 1)) continue;

      const cx = (x + 0.5) * cellW;
      const cy = (y + 0.5) * cellH;

      ctx.save();
      ctx.shadowBlur = Math.max(1, Math.round(line1 * 0.06));
      ctx.font = `bold ${line1}px Arial`;
      ctx.fillText('КОРАБЕЛИ', cx, cy - line2*0.45);
      ctx.restore();

      ctx.save();
      ctx.shadowBlur = Math.max(1, Math.round(line2 * 0.06));
      ctx.font = `bold ${line2}px Arial`;
      ctx.fillText('t.me/korabely_media', cx, cy + line2*0.85);
      ctx.restore();
    }
  }
}

function drawCenteredLogo(ctx, w, h, img, percent, alpha){
  if (!img) return false;
  const s = Math.max(5, Math.min(90, percent)) / 100;
  const target = Math.min(w, h) * s;
  const iw = img.naturalWidth || img.width;
  const ih = img.naturalHeight || img.height;
  const ratio = iw/ih;
  let tw = target, th = target;
  if (ratio >= 1) th = target/ratio; else tw = target*ratio;

  ctx.save();
  ctx.globalAlpha = Math.max(0.06, Math.min(1, alpha ?? 0.28));
  ctx.translate(w/2, h/2);
  ctx.shadowColor = 'rgba(0,0,0,0.30)';
  ctx.shadowBlur = Math.max(6, Math.round(target*0.05));
  ctx.drawImage(img, -tw/2, -th/2, tw, th);
  ctx.restore();
  return true;
}

function drawBottomSignature(ctx, w, h, alpha){
  const margin = Math.max(8, Math.round(Math.min(w,h) * 0.02));
  const size = Math.max(14, Math.round(Math.min(w,h) * SIGN_SIZE_K));
  ctx.save();
  ctx.globalAlpha = Math.max(0.06, Math.min(1, alpha ?? 0.32));
  ctx.textAlign = 'center';
  ctx.textBaseline = 'alphabetic';
  ctx.font = `600 ${size}px Arial`;
  ctx.shadowColor = 'rgba(0,0,0,0.30)';
  ctx.shadowBlur = Math.max(1, Math.round(size * 0.10));
  ctx.fillStyle = 'rgba(255,255,255,1)';
  ctx.fillText('t.me/korabely_media', w/2, h - margin);
  ctx.restore();
}

// маппінг повзунка → альфи
function getAlphas(){
  const v = parseInt(alphaSl.value || '30', 10); // 5..100
  const scale = v / 30; // 30% = базове
  const clamp = (x, a, b) => Math.max(a, Math.min(b, x));

  const logoAlpha = clamp(BASE_LOGO_ALPHA * scale, 0.06, 0.90);
  const tileAlpha = clamp(BASE_TILE_ALPHA * scale, 4, 40); // % для сітки
  const signAlpha = clamp(BASE_SIGN_ALPHA * scale, 0.06, 0.90);

  return { logoAlpha, tileAlpha, signAlpha };
}

// Рендер
async function render({forOpen=false} = {}){
  if (!srcImage) return null;
  await ensureLogoLoaded();

  const w = srcImage.naturalWidth || srcImage.width;
  const h = srcImage.naturalHeight || srcImage.height;
  const maxSide = 8000;
  if (Math.max(w,h) > maxSide){
    toast('Занадто велике зображення (обмеження ~8000px)', 'err');
    return null;
  }

  const variant = (document.querySelector('input[name="variant"]:checked')?.value) || '1';
  const { logoAlpha, tileAlpha, signAlpha } = getAlphas();

  const canvas = document.createElement('canvas');
  canvas.width = w; canvas.height = h;
  const ctx = canvas.getContext('2d');

  // фон
  ctx.drawImage(srcImage, 0, 0, w, h);

  // лого по центру (1 і 3)
  if (variant === '1' || variant === '3'){
    if (!drawCenteredLogo(ctx, w, h, logoImage, LOGO_PCT, logoAlpha)){
      // fallback: текст
      ctx.save();
      ctx.globalAlpha = logoAlpha;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      const big = Math.max(28, Math.round(Math.min(w,h) * 0.12));
      ctx.font = `800 ${big}px Arial`;
      ctx.shadowColor = 'rgba(0,0,0,0.30)';
      ctx.shadowBlur = Math.max(6, Math.round(big*0.06));
      ctx.fillStyle = 'rgba(255,255,255,1)';
      ctx.fillText('КОРАБЕЛИ', w/2, h/2);
      ctx.restore();
    }
  }

  // сітка — ТІЛЬКИ для 1 і 2
  if (variant === '1' || variant === '2'){
    drawTiledWatermarks(ctx, w, h, TILE_X, TILE_Y, tileAlpha, TILE_CHESS);
  }

  // підпис знизу — ТІЛЬКИ для 3
  if (variant === '3'){
    drawBottomSignature(ctx, w, h, signAlpha);
  }

  if (!forOpen){
    setPreviewNode(canvas);
    return canvas;
  } else {
    try{
      const blob = await new Promise((res, rej)=> canvas.toBlob(b=> b?res(b):rej(new Error('toBlob failed')), 'image/png'));
      if (lastBlobUrl) URL.revokeObjectURL(lastBlobUrl);
      lastBlobUrl = URL.createObjectURL(blob);
      return lastBlobUrl;
    }catch(e){
      toast('Не вдалося сформувати зображення (CORS/пам’ять)', 'err');
      return null;
    }
  }
}

async function onSourceSelected(){
  try{
    if (srcFile.files && srcFile.files[0]) {
      srcImage = await loadImageFromFile(srcFile.files[0]);
    } else {
      srcImage = null;
      genBtn.disabled = true;
      preview.innerHTML='<span class="sub">Завантажте фото — тут з’явиться готовий результат</span>';
      return;
    }
    genBtn.disabled = false;
    await render({forOpen:false});
    toast('Готово: прев’ю з водяними знаками', 'ok');
  }catch(e){
    genBtn.disabled = true;
    preview.innerHTML = '<span class="sub">Помилка завантаження фото</span>';
    toast(e.message || 'Помилка фото', 'err');
  }
}

// події
document.querySelectorAll('input[name="variant"]').forEach(r=>{
  r.addEventListener('change', ()=>{ if (srcImage) render({forOpen:false}); });
});

alphaSl.addEventListener('input', ()=>{
  alphaVal.textContent = String(alphaSl.value);
  if (srcImage) render({forOpen:false});
});

// Згенерувати у новій вкладці
genBtn.addEventListener('click', async ()=>{
  if (!srcImage) return;
  const blobUrl = await render({forOpen:true});
  if (blobUrl) {
    window.open(blobUrl, '_blank', 'noopener');
    toast('Готово. Відкрив у новій вкладці.', 'ok');
  }
});
</script>
</body>
</html>
