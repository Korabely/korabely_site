<?php // /admin/index.php
// Віддаємо сторінку; перевірку ролі робимо на клієнті + на API-рівні
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — Головна</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{
      --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb;

      /* спокійна палітра для чипів */
      --ok-bg:#181818;      --ok-border:#2a2a2a;      --ok-text:#cfcfcf;      --ok-dot:#6d6d6d;
      --danger-bg:#2a1616;  --danger-border:#4a2a2a;  --danger-text:#f1d7d7;  --danger-dot:#e65b5b;
    }
    html,body{margin:0;height:100%;background:var(--bg);color:var(--text);font-family:Arial, sans-serif;}
    a{color:#fff;text-decoration:none}

    .wrap{position:relative; z-index:1; max-width:1024px;margin:0 auto;padding:20px}
    .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
    .title{font-size:20px;font-weight:700;letter-spacing:.02em}
    .nav{display:flex;gap:8px}
    .btn{
      background:#222;border:1px solid var(--border);color:#fff;cursor:pointer;border-radius:8px;
      padding:10px 14px; font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.6);
    }
    .btn:hover{background:#fff;color:#111;border-color:#aaa}

    /* ——— великий прозорий логотип на фоні ——— */
    .admin-watermark{
      position:fixed; inset:0; z-index:0; pointer-events:none; user-select:none;
      display:flex; align-items:center; justify-content:center; opacity:0.06;
    }
    .admin-watermark::before{
      content:""; width:min(85vmin, 920px); height:min(85vmin, 920px);
      background:url("https://i.postimg.cc/W1ZcFpbK/image.png") center / contain no-repeat;
      filter:grayscale(100%) contrast(110%);
    }

    /* ——— блок тривог: відцентровано, без рамок/планки ——— */
    .alerts{
      background:transparent; border:none; border-radius:0;
      padding:0; margin:6px auto 12px; /* легкий відступ зверху/знизу */
      max-width:640px; text-align:center;
    }
    .alerts-head{
      display:flex; flex-direction:column; align-items:center; gap:4px;
      margin-bottom:8px;
    }
    .alerts-title{font-weight:700;font-size:13px;color:#9a9a9a;letter-spacing:.01em}
    .alerts-time{color:#8a8a8a;font-size:11px}
    .pill-row{display:flex;gap:8px;flex-wrap:wrap;justify-content:center}

    .pill{
      display:inline-flex;align-items:center;gap:8px; padding:6px 10px;
      border-radius:999px; border:1px solid var(--ok-border);
      background:var(--ok-bg); color:var(--ok-text); font-weight:600; font-size:12px;
      line-height:1; letter-spacing:.01em; user-select:none;
    }
    .pill .dot{width:8px;height:8px;border-radius:50%;background:var(--ok-dot);flex:0 0 auto}
    .pill .name{opacity:.95}
    .pill.ok{background:var(--ok-bg);border-color:var(--ok-border);color:var(--ok-text)}
    .pill.ok .dot{background:var(--ok-dot)}
    .pill.danger{background:var(--danger-bg);border-color:var(--danger-border);color:var(--danger-text)}
    .pill.danger .dot{background:var(--danger-dot)}

    /* ——— карточки ——— */
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
    .card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:18px;display:block}
    .card h3{margin:0 0 8px}
    .muted{color:var(--muted);font-size:14px}
  </style>
</head>
<body>

  <!-- фон-логотип -->
  <div class="admin-watermark" aria-hidden="true"></div>

  <div class="wrap">
    <div class="topbar">
      <div class="title">Адміністративна панель</div>
      <div class="nav">
        <a class="btn" href="/">Перейти на мапу</a>
        <button id="logoutBtn" class="btn" type="button" title="Вийти">Вийти</button>
      </div>
    </div>

    <!-- ПОВІТРЯНІ ТРИВОГИ (без "(області)" та без рамки) -->
    <section class="alerts" aria-live="polite">
      <div class="alerts-head">
        <div class="alerts-title">Повітряні тривоги</div>
        <div id="alerts_time" class="alerts-time">—</div>
      </div>
      <div class="pill-row" id="pill_row">
        <!-- буде замінено JS -->
        <div class="pill ok"><span class="dot"></span><span class="name">Одеська обл</span></div>
        <div class="pill ok"><span class="dot"></span><span class="name">Миколаївська обл</span></div>
        <div class="pill ok"><span class="dot"></span><span class="name">Херсонська обл</span></div>
      </div>
    </section>

    <div class="grid">
      <a class="card" href="/admin/users.php">
        <h3>Користувачі</h3>
        <div class="muted">Перегляд, створення, редагування та видалення</div>
      </a>
      <a class="card" href="/admin/tg-post.php">
        <h3>Надіслати повідомлення</h3>
        <div class="muted">Надсилання повідомлень у канал через адмінку</div>
      </a>
      <a class="card" href="/admin/photo/">
        <h3>Водяні знаки</h3>
        <div class="muted">Обробка фото з водяними знаками для каналу</div>
      </a>
    </div>
  </div>

<script>
// м’яка перевірка ролі (клієнтська)
(async function guard(){
  try{
    const r = await fetch('/api/me.php', {credentials:'include'});
    const j = await r.json();
    const role = j?.user?.role ?? j?.data?.role ?? 'user';
    if (role !== 'admin') location.href = '/';
  }catch(e){ location.href = '/'; }
})();

// вихід
(function(){
  const btn = document.getElementById('logoutBtn');
  if(!btn) return;
  btn.addEventListener('click', async ()=>{
    btn.disabled = true;
    const prev = btn.textContent;
    btn.textContent = 'Вихід…';
    try{ await fetch('/api/logout.php', { method:'POST', credentials:'include' }); }catch(e){}
    location.href = '/';
  });
})();

// ====== ТРИВОГИ через локальний проксі /api/alerts.php ======
const API_LOCAL = '/api/alerts.php';
const pillsEl   = document.getElementById('pill_row');
const timeEl    = document.getElementById('alerts_time');

// цільові області: відображуване ім'я + варіанти ключів у джерелі
const TARGETS = [
  {label:'Одеська обл',     keys:['Одеська область','Одеська обл','Одеська']},
  {label:'Миколаївська обл',keys:['Миколаївська область','Миколаївська обл','Миколаївська']},
  {label:'Херсонська обл',  keys:['Херсонська область','Херсонська обл','Херсонська']},
];

function renderPills(statusByLabel){
  pillsEl.innerHTML = '';
  for(const t of TARGETS){
    const st = statusByLabel[t.label] || {alert:false};
    const pill = document.createElement('div');
    pill.className = 'pill ' + (st.alert ? 'danger' : 'ok');
    pill.innerHTML = `<span class="dot"></span><span class="name">${t.label}</span>`;
    pillsEl.appendChild(pill);
  }
}

function findInStates(states, keys){
  if (!states) return null;
  // точний збіг
  for(const k of keys){ if (k in states) return states[k]; }
  // ліберальний пошук
  const norm = s => String(s).toLowerCase().replace(/[’'`]/g,'').trim();
  const wanted = keys.map(norm);
  for (const [name,obj] of Object.entries(states)){
    const n = norm(name);
    if (wanted.some(w => n.includes(w))) return obj;
  }
  return null;
}

async function updateAlerts(){
  try{
    const r = await fetch(API_LOCAL, {cache:'no-store'});
    const j = await r.json();
    if (!j || !j.states) throw new Error('bad_json');

    const states = j.states;
    const status = {};
    for (const t of TARGETS){
      const found = findInStates(states, t.keys);
      status[t.label] = { alert: !!(found && found.alertnow) };
    }
    renderPills(status);
    const ts = new Date();
    timeEl.textContent = ts.toLocaleTimeString('uk-UA', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
  }catch(e){
    renderPills({}); // все сіре
    timeEl.textContent = 'API недоступне';
  }
}

// перший запуск + оновлення раз на хвилину
updateAlerts();
setInterval(updateAlerts, 60000);
</script>
</body>
</html>
