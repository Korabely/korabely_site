<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — TG пост</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{ --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb; }
    *{ box-sizing:border-box }
    html,body{ margin:0; height:100%; background:var(--bg); color:var(--text); font-family:Arial, sans-serif; font-size:14px }
    a{ color:#fff; text-decoration:none }
    .wrap{ max-width:960px; margin:0 auto; padding:18px }
    .top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:14px }
    .title{ font-size:20px; font-weight:700 }
    .panel{ background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:14px }
    .row{ display:grid; grid-template-columns:160px 1fr; gap:10px; align-items:flex-start; margin-bottom:10px }
    label{ color:#ddd; margin-top:8px }
    input[type="text"], textarea, select{
      width:100%; background:#222; color:#fff; border:1px solid var(--border); border-radius:8px; padding:8px 10px; outline:none;
    }
    textarea{ min-height:140px; resize:vertical }
    input[type="file"]{ color:#ccc }
    .btn{
      background:#222; border:1px solid var(--border); color:#fff; cursor:pointer; border-radius:8px;
      padding:8px 12px; font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.6); font-size:14px; line-height:1; min-height:34px;
    }
    .btn:hover{ background:#fff; color:#111; border-color:#aaa }
    .muted{ color:var(--muted) }
    .hr{ height:1px; background:#2a2a2a; margin:12px 0 }
    .end{ display:flex; justify-content:flex-end; gap:10px }
    .row.inline{ grid-template-columns:1fr 1fr; gap:8px }
    .hints{ font-size:12px; color:#aaa }
    .ok{ color:#7bd88f }
    .err{ color:#ff7b7b }
    .preview{ margin-top:8px; border:1px dashed #444; border-radius:8px; padding:8px; display:none }
    .preview img{ max-width:100%; border-radius:6px; display:block }
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="title">Telegram пост</div>
    <div><a class="btn" href="/admin/">← Назад</a></div>
  </div>

  <div class="panel">
    <div id="guard_err" class="err" style="display:none; margin-bottom:10px">Доступ лише для адміністраторів</div>
    <div id="submit_msg" class="ok" style="display:none; margin-bottom:10px">Надіслано ✅</div>
    <div id="submit_err" class="err" style="display:none; margin-bottom:10px"></div>

    <div class="row">
      <label for="text">Текст</label>
      <div>
        <textarea id="text" placeholder="Текст посту (HTML/Markdown підтримується)"></textarea>
        <div class="hints">Якщо додаєш фото — текст стане підписом.</div>
      </div>
    </div>

    <div class="row">
      <label for="photo_url">Фото (URL)</label>
      <input id="photo_url" type="text" placeholder="https://… (необовʼязково)">
    </div>

    <div class="row">
      <label for="photo">Фото (файл)</label>
      <input id="photo" type="file" accept="image/*">
    </div>

    <div class="row inline">
      <div>
        <label for="parse_mode">Parse mode</label>
        <select id="parse_mode">
          <option value="">Без розмітки</option>
          <option value="HTML" selected>HTML</option>
          <option value="MarkdownV2">MarkdownV2</option>
        </select>
      </div>
      <div>
        <label>Опції</label>
        <div class="hints">
          <label><input type="checkbox" id="silent"> Тихе сповіщення</label>&nbsp;&nbsp;
          <label><input type="checkbox" id="no_preview"> Без превʼю посилань</label>
        </div>
      </div>
    </div>

    <div id="preview" class="preview">
      <div class="muted" style="margin-bottom:6px">Превʼю зображення</div>
      <img id="preview_img" alt="">
    </div>

    <div class="hr"></div>
    <div class="end">
      <button class="btn" id="sendBtn">Надіслати</button>
    </div>
  </div>
</div>

<script>
// JS-гард как в других админ-страницах
(async function guard(){
  try{
    const r = await fetch('/api/me.php', {credentials:'include'});
    const j = await r.json();
    const role = j?.user?.role ?? j?.data?.role ?? 'user';
    if (role !== 'admin'){
      document.getElementById('guard_err').style.display = 'block';
      // Можно редиректить: location.href = '/';
    }
  }catch(e){
    document.getElementById('guard_err').style.display = 'block';
  }
})();

function setOk(msg){ const n=document.getElementById('submit_msg'); n.textContent=msg||'Надіслано ✅'; n.style.display='block';
  document.getElementById('submit_err').style.display='none'; }
function setErr(msg){ const n=document.getElementById('submit_err'); n.textContent=msg||'Помилка'; n.style.display='block';
  document.getElementById('submit_msg').style.display='none'; }

const img = document.getElementById('preview_img');
const box = document.getElementById('preview');
const urlInput = document.getElementById('photo_url');
const fileInput = document.getElementById('photo');

urlInput.addEventListener('input', ()=>{ showPreviewFromURL(urlInput.value.trim()); });
fileInput.addEventListener('change', ()=>{
  if (!fileInput.files || !fileInput.files[0]) { box.style.display='none'; img.src=''; return; }
  const f = fileInput.files[0];
  const r = new FileReader();
  r.onload = e => { img.src = e.target.result; box.style.display='block'; };
  r.readAsDataURL(f);
});
function showPreviewFromURL(u){
  if (!u){ if (!fileInput.value){ box.style.display='none'; img.src=''; } return; }
  img.src = u; box.style.display = 'block';
}

document.getElementById('sendBtn').onclick = async ()=>{
  setOk(''); document.getElementById('submit_msg').style.display='none';
  setErr(''); document.getElementById('submit_err').style.display='none';

  const text  = document.getElementById('text').value.trim();
  const purl  = urlInput.value.trim();
  const file  = fileInput.files[0] || null;
  const mode  = document.getElementById('parse_mode').value;
  const silent = document.getElementById('silent').checked ? 1 : 0;
  const noPrev = document.getElementById('no_preview').checked ? 1 : 0;

  const form = new FormData();
  if (text) form.append('text', text);
  if (purl) form.append('photo_url', purl);
  if (file) form.append('photo', file);
  if (mode) form.append('parse_mode', mode);
  if (silent) form.append('disable_notification', '1');
  if (noPrev) form.append('disable_web_page_preview', '1');

  try{
    const r = await fetch('/api/admin/telegram.php', { method:'POST', body: form, credentials:'include' });
    const j = await r.json().catch(()=>null);

    if (!r.ok){
      const code = r.status;
      const err  = (j && j.error) ? j.error : ('HTTP '+code);
      // Подсказки по частым ошибкам конфига:
      if (err === 'config_missing') setErr('Відсутній /config.telegram.php');
      else if (err === 'config_invalid') setErr('Невірний BOT_TOKEN або CHAT_ID в /config.telegram.php');
      else if (err.startsWith('telegram_http_')) setErr('Telegram HTTP: ' + err.replace('telegram_http_',''));
      else setErr('Помилка: ' + err);
      return;
    }
    if (!j || j.ok === false){
      setErr('Помилка: ' + (j && j.error ? j.error : 'невідома'));
      return;
    }

    setOk('Надіслано ✅');
    document.getElementById('text').value='';
    urlInput.value=''; fileInput.value='';
    box.style.display='none'; img.src='';
  }catch(e){
    setErr('Помилка мережі: ' + (e.message || 'невідома'));
  }
};
</script>
</body>
</html>
