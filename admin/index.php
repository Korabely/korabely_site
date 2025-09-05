<?php // /admin/index.php
// Отдаем страницу; проверку роли делаем на клиенте + на API-уровне
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — Головна</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{ --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb; }
    html,body{margin:0;height:100%;background:var(--bg);color:var(--text);font-family:Arial, sans-serif;}
    a{color:#fff;text-decoration:none}
    .wrap{max-width:1024px;margin:0 auto;padding:20px}
    .topbar{
      display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;
    }
    .title{font-size:20px;font-weight:700;letter-spacing:.02em}
    .nav{display:flex;gap:8px}
    .btn{
      background:#222;border:1px solid var(--border);color:#fff;cursor:pointer;border-radius:8px;
      padding:10px 14px; font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.6);
    }
    .btn:hover{background:#fff;color:#111;border-color:#aaa}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
    .card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:18px}
    .card h3{margin:0 0 8px}
    .muted{color:var(--muted);font-size:14px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">Адміністративна панель</div>
      <div class="nav">
        <a class="btn" href="/admin/users.php">Користувачі</a>
        <a class="btn" href="/admin/tg-post.php">Постинг у Telegram</a>
        <a class="btn" href="/">Перейти на мапу</a>
      </div>
    </div>

    <div class="grid">
      <a class="card" href="/admin/users.php">
        <h3>Користувачі</h3>
        <div class="muted">Перегляд, створення, редагування та видалення.</div>
      </a>
      <a class="card" href="/admin/tg-post.php">
        <h3>Постинг у Telegram</h3>
        <div class="muted">Надсилання повідомлень у канал / групу через Bot API.</div>
      </a>
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
</script>
</body>
</html>
