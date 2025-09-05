<?php // /admin/tg-post.php ?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — Постинг у Telegram</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{ --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb; }
    html,body{margin:0;height:100%;background:var(--bg);color:var(--text);font-family:Arial, sans-serif;}
    .wrap{max-width:900px;margin:0 auto;padding:20px}
    .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .title{font-size:20px;font-weight:700}
    .btn{background:#222;border:1px solid var(--border);color:#fff;cursor:pointer;border-radius:8px;padding:10px 14px;font-weight:600;box-shadow:0 1px 4px rgba(0,0,0,.6);}
    .btn:hover{background:#fff;color:#111;border-color:#aaa}
    .panel{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:16px}
    textarea{
      width:100%; min-height:220px; resize:vertical; background:#222; color:#fff; border:1px solid var(--border);
      border-radius:10px; padding:12px; font-size:15px; outline:none;
    }
    .row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:10px}
    label{display:flex;align-items:center;gap:8px}
    select{height:38px;border-radius:8px;border:1px solid var(--border);background:#222;color:#fff;padding:0 10px}
    .muted{color:var(--muted);font-size:13px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div class="title">Постинг у Telegram</div>
      <div>
        <a class="btn" href="/admin/">← Назад</a>
      </div>
    </div>

    <div class="panel">
      <div class="muted" style="margin-bottom:10px">Повідомлення:</div>
      <textarea id="msg" placeholder="Текст повідомлення (підтримка HTML або MarkdownV2)"></textarea>

      <div class="row">
        <label><input type="checkbox" id="no_preview"> Без превʼю посилань</label>
        <label>Формат:
          <select id="parse">
            <option value="HTML">HTML</option>
            <option value="MarkdownV2">MarkdownV2</option>
            <option value="">Без форматування</option>
          </select>
        </label>
        <button class="btn" id="send">Надіслати</button>
      </div>

      <div id="status" class="muted" style="margin-top:10px"></div>
    </div>
  </div>

<script>
(async function guard(){
  try{
    const r = await fetch('/api/me.php', {credentials:'include'});
    const j = await r.json();
    const role = j?.user?.role ?? j?.data?.role ?? 'user';
    if (role !== 'admin') location.href = '/';
  }catch(e){ location.href = '/'; }
})();

const $ = id => document.getElementById(id);

$('send').onclick = async ()=>{
  const status = $('status');
  status.textContent = 'Надсилання...';

  const text = $('msg').value.trim();
  if (!text){ status.textContent = 'Введіть текст'; return; }

  const parse_mode = $('parse').value || undefined;
  const disable_web_page_preview = $('no_preview').checked;

  try{
    const r = await fetch('/api/admin/tg_post.php', {
      method:'POST', credentials:'include',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ text, parse_mode, disable_web_page_preview })
    });
    const j = await r.json();
    if (!r.ok || j.ok === false){
      status.textContent = 'Помилка: ' + (j.error || 'невідома');
      return;
    }
    status.textContent = 'Надіслано ✓';
  }catch(e){
    status.textContent = 'Помилка мережі';
  }
};
</script>
</body>
</html>
