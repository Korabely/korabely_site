<?php // /admin/users.php ?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — Користувачі</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{
      --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb;
      /* колонки: ID | Логін | Імʼя | Роль | Info | (Дії) */
      --col-id:   64px;
      --col-log:  240px;
      --col-name: 1fr;
      --col-role: 160px;
      --col-info: 1.3fr;
      --col-act:  360px;
      --cols: var(--col-id) var(--col-log) var(--col-name) var(--col-role) var(--col-info) var(--col-act);

      /* сдвиг заголовков под контент (можно подкрутить) */
      --align-role: 18px;
      --align-info: 18px;
      --radius: 12px;
    }
    *, *::before, *::after { box-sizing: border-box; }
    html,body{margin:0;height:100%;background:var(--bg);color:var(--text);font-family:Arial, sans-serif;font-size:14px}
    a{color:#fff;text-decoration:none}
    .wrap{max-width:1440px;margin:0 auto;padding:18px}

    .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
    .title{font-size:20px;font-weight:700}
    .btn{
      background:#222;border:1px solid var(--border);color:#fff;cursor:pointer;border-radius:8px;
      padding:8px 12px;font-weight:600;box-shadow:0 1px 4px rgba(0,0,0,.6);font-size:13px;line-height:1;min-height:36px;
      white-space:nowrap;
    }
    .btn:hover{background:#fff;color:#111;border-color:#aaa}
    .btn.danger{ background:#2a0000; border-color:#5a0000; }
    .btn.danger:hover{ background:#ffdddd; color:#400; }

    .panel{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);padding:14px}

    /* список */
    .row{
      display:grid;
      grid-template-columns: var(--cols);
      gap:8px;align-items:center;border-bottom:1px solid var(--border);padding:8px 0;
    }
    .row > *{ min-width:0; }
    .row.header{font-weight:700;border-bottom-color:#444;padding:0}
    /* точный сдвиг заголовков */
    .row.header > div:nth-child(4){ padding-left: var(--align-role); } /* Роль */
    .row.header > div:nth-child(5){ padding-left: var(--align-info); } /* Info */

    input,select{
      height:36px;border-radius:8px;border:1px solid var(--border);background:#222;color:#fff;padding:0 10px;outline:none;width:100%;
    }
    .muted{color:var(--muted);font-size:12px}

    /* Дії: одна строка, прижать вправо */
    .actions{
      display:flex; gap:6px; flex-wrap:nowrap; align-items:center; justify-content:flex-end; overflow:hidden;
    }
    .actions .btn{ flex:0 0 auto; }

    /* форма создания */
    .new-grid{
      display:grid;
      grid-template-columns: var(--col-log) 260px var(--col-name) var(--col-role) minmax(120px, var(--col-act));
      gap:8px; align-items:center; margin-bottom:10px;
    }
    .new-grid .btn{ width:100%; }

    .toolbar{display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:8px}
    .filter{height:36px; border-radius:8px; border:1px solid var(--border); background:#222; color:#fff; padding:0 10px; width:260px}

    .truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

    /* ====== АДАПТИВ ====== */
    @media (max-width:1280px){
      :root{ --col-log:220px; --col-role:120px; --col-act:330px; }
      .new-grid{ grid-template-columns: var(--col-log) 240px var(--col-name) var(--col-role) minmax(110px, var(--col-act)); }
    }
    @media (max-width:1100px){
      :root{ --col-id:56px; --col-log:200px; --col-role:110px; --col-act:300px; }
      .new-grid{ grid-template-columns: var(--col-log) 220px var(--col-name) var(--col-role) minmax(100px, var(--col-act)); }
    }

    /* ===== Мобильный режим: строки превращаются в карточки ===== */
    @media (max-width: 680px){
      .wrap{ padding:12px; }

      .toolbar{
        flex-direction:column;
        align-items:stretch;
        gap:8px;
      }
      .filter{ width:100%; }

      /* форма создания — одна колонка */
      .new-grid{
        grid-template-columns: 1fr;
      }
      .new-grid .btn{ grid-column:auto; }

      /* шапка-ряд заголовков скрываем */
      .row.header{ display:none; }

      /* каждая строка — карточка */
      #list .row{
        grid-template-columns: 1fr;
        gap:10px;
        padding:12px;
        border:1px solid var(--border);
        border-radius:var(--radius);
        background:#171717;
        margin-bottom:10px;
      }

      /* контейнер поля с псевдо-подписью слева */
      .field{
        display:flex; align-items:center; gap:10px;
      }
      .field::before{
        content: attr(data-label);
        flex:0 0 42%;
        color: var(--muted);
        font-size:12px;
        line-height:1.2;
      }
      /* инпут/значение занимает остаток */
      .field > *:not(:first-child){
        flex:1 1 auto;
        min-width:0;
      }

      /* Info делаем многострочным */
      .field.info .truncate{ white-space:normal; }

      /* Блок действий: в конец, переносим кнопки */
      .actions{
        justify-content:flex-start;
        flex-wrap:wrap;
        gap:8px;
      }
      .actions .btn{
        min-height:34px;
        padding:8px 10px;
        font-size:13px;
      }
    }

    /* ещё чуть компактнее для узких телефонов */
    @media (max-width: 560px){
      .btn{ padding:8px 10px; }
      input,select{ height:34px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div class="title">Користувачі</div>
      <div><a class="btn" href="/admin/">← Назад</a></div>
    </div>

    <!-- Создание -->
    <div class="panel">
      <div class="muted" style="margin-bottom:8px">Створити нового користувача</div>
      <div class="new-grid">
        <input id="new_username" placeholder="Логін" />
        <input id="new_password" placeholder="Пароль" />
        <input id="new_fullname" placeholder="Імʼя (повне)" />
        <select id="new_role">
          <option value="user">Користувач</option>
          <option value="admin">Адмін</option>
        </select>
        <button id="btn_create" class="btn">Створити</button>
      </div>
    </div>

    <div class="panel" style="margin-top:14px">
      <div class="toolbar">
        <!-- Шапка: без «Дії», последняя колонка пустая -->
        <div class="row header" style="border-bottom:none;">
          <div>ID</div><div>Логін</div><div>Імʼя</div><div>Роль</div><div>Info</div><div></div>
        </div>
        <input id="filter" class="filter" placeholder="Пошук (логін / імʼя)">
      </div>

      <div id="list"></div>
      <div id="error" class="muted" style="margin-top:8px;color:#f88"></div>
    </div>
  </div>

<script>
// ===== Гард: тільки admin =====
(async function guard(){
  try{
    const r = await fetch('/api/me.php', {credentials:'include'});
    const j = await r.json();
    const role = j?.user?.role ?? j?.data?.role ?? 'user';
    if (role !== 'admin') location.href = '/';
  }catch(e){ location.href = '/'; }
})();

function el(tag, attrs = {}, children = []){
  const e = document.createElement(tag);
  for (const k in attrs){
    const v = attrs[k];
    if (k === 'class') e.className = v;
    else if (k in e) e[k] = v;
    else e.setAttribute(k, v);
  }
  for (const c of children){
    if (typeof c === 'string') e.appendChild(document.createTextNode(c));
    else if (c) e.appendChild(c);
  }
  return e;
}
function showError(msg){ document.getElementById('error').textContent = msg || ''; }

// helper: поле карточки с подписью (используется на мобилке через ::before)
function field(label, node, extraClass=''){
  return el('div', {class:`field ${extraClass}`.trim(), 'data-label':label}, [node]);
}

// API (method-override для PUT/DELETE)
async function api(method, body){
  const url = '/api/admin/users.php' + (method==='DELETE' ? '?_method=DELETE' : '');
  const headers = {'Content-Type':'application/json'};
  const opts = { credentials:'include', headers };

  if (method === 'GET') {
    opts.method = 'GET';
  } else if (method === 'POST') {
    opts.method = 'POST';
    if (body) opts.body = JSON.stringify(body);
  } else {
    opts.method = 'POST';
    headers['X-HTTP-Method-Override'] = method;
    if (body) opts.body = JSON.stringify(body);
  }

  const r = await fetch(url, opts);
  let j = null;
  try { j = await r.json(); } catch(e) { j = { ok:false, error:'bad_json' }; }
  if (!r.ok || j.ok === false) {
    const msg = 'HTTP ' + r.status + (j && j.error ? (': ' + j.error) : '');
    throw (j && typeof j === 'object') ? j : { ok:false, error: msg };
  }
  return j;
}

let ALL_USERS = [];

function userRow(u){
  const row = el('div', {class:'row'});

  // значения/инпуты
  const idTxt   = el('div', {}, [String(u.id)]);
  const inUser  = el('input', {value:u.username});
  const inName  = el('input', {value:u.full_name});
  const selRole = el('select', {}, [
    el('option', {value:'user', selected: (u.role||'user')==='user'}, ['Користувач']),
    el('option', {value:'admin', selected: u.role==='admin'}, ['Адмін'])
  ]);
  const infoRO  = el('div', {class:'truncate', title: (u.info || '')}, [u.info || '']);

  // действия
  const actions = el('div', {class:'actions'});
  const btnSave = el('button', {class:'btn', title:'Зберегти зміни'}, ['Зберегти']);
  const btnPass = el('button', {class:'btn', title:'Скинути пароль'}, ['Скинути']);
  const btnDel  = el('button', {class:'btn danger', title:'Видалити користувача'}, ['Видалити']);

  btnSave.onclick = async ()=>{
    try{
      await api('PUT', {
        id: u.id,
        username: inUser.value.trim(),
        full_name: inName.value.trim(),
        role: selRole.value
      });
      btnSave.textContent = 'Збережено ✓';
      setTimeout(()=>btnSave.textContent='Зберегти', 1000);
      showError('');
    }catch(e){
      showError('Зберегти не вдалося: ' + (e.error || 'невідома'));
      alert('Помилка: ' + (e.error || 'невідома'));
    }
  };

  btnPass.onclick = async ()=>{
    const p = prompt('Новий пароль для '+ inUser.value +':', '');
    if (p === null) return;
    try{
      await api('PUT', { id: u.id, password: p });
      alert('Пароль оновлено');
      showError('');
    }catch(e){
      showError('Пароль не змінено: ' + (e.error || 'невідома'));
      alert('Помилка: ' + (e.error || 'невідома'));
    }
  };

  btnDel.onclick = async ()=>{
    if (!confirm('Видалити користувача #' + u.id + ' ?')) return;
    try{
      await api('DELETE', { id: u.id });
      row.remove();
      showError('');
    }catch(e){
      showError('Видалити не вдалося: ' + (e.error || 'невідома'));
      alert('Помилка: ' + (e.error || 'невідома'));
    }
  };

  actions.append(btnSave, btnPass, btnDel);

  // Порядок детей важен для десктопной сетки (grid-columns),
  // на мобильном эти элементы обёрнуты в .field с подписью
  row.append(
    field('ID', idTxt,      'id'),
    field('Логін', inUser,  'login'),
    field('Імʼя', inName,   'name'),
    field('Роль', selRole,  'role'),
    field('Info', infoRO,   'info'),
    actions
  );
  return row;
}

function renderList(arr){
  const list = document.getElementById('list');
  list.innerHTML = '';
  if (!arr.length){
    list.innerHTML = '<div class="muted" style="padding:8px 0">Нічого не знайдено</div>';
    return;
  }
  for (const u of arr) list.appendChild(userRow(u));
}

async function load(){
  const list = document.getElementById('list');
  list.innerHTML = '<div class="muted" style="padding:8px 0">Завантаження...</div>';
  showError('');
  try{
    const j = await api('GET');
    ALL_USERS = j.users || [];
    renderList(ALL_USERS);
  }catch(e){
    list.innerHTML = '<div class="muted" style="padding:8px 0;color:#f88">Помилка завантаження</div>';
    showError('Деталі: ' + (e.error || 'невідома помилка'));
  }
}

document.getElementById('filter').addEventListener('input', (e)=>{
  const q = e.target.value.trim().toLowerCase();
  if (!q) { renderList(ALL_USERS); return; }
  const out = ALL_USERS.filter(u=>{
    const a = (u.username||'').toLowerCase();
    const b = (u.full_name||'').toLowerCase();
    return a.includes(q) || b.includes(q);
  });
  renderList(out);
});

document.getElementById('btn_create').onclick = async ()=>{
  const u = document.getElementById('new_username').value.trim();
  const p = document.getElementById('new_password').value;
  const f = document.getElementById('new_fullname').value.trim();
  const r = document.getElementById('new_role').value;

  if (!u || !p){ alert('Логін і пароль обовʼязкові'); return; }
  try{
    await api('POST', { username:u, password:p, full_name:f || 'Користувач', role:r });
    document.getElementById('new_username').value='';
    document.getElementById('new_password').value='';
    document.getElementById('new_fullname').value='';
    await load();
    showError('');
  }catch(e){
    showError('Створити не вдалося: ' + (e.error || 'невідома'));
    alert('Помилка: ' + (e.error || 'невідома'));
  }
};

load().catch(console.error);
</script>
</body>
</html>
