<?php // /admin/users.php ?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Адмінка — Користувачі</title>
  <link rel="icon" type="image/png" href="https://i.postimg.cc/3JsmKdws/favicon.png">
  <style>
    :root{ --bg:#111; --panel:#1b1b1b; --border:#333; --text:#fff; --muted:#bbb; }
    html,body{margin:0;height:100%;background:var(--bg);color:var(--text);font-family:Arial, sans-serif;}
    .wrap{max-width:1100px;margin:0 auto;padding:20px}
    .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .title{font-size:20px;font-weight:700}
    .btn{background:#222;border:1px solid var(--border);color:#fff;cursor:pointer;border-radius:8px;padding:10px 14px;font-weight:600;box-shadow:0 1px 4px rgba(0,0,0,.6);}
    .btn:hover{background:#fff;color:#111;border-color:#aaa}
    .panel{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:16px}
    .row{display:grid;grid-template-columns:60px 1fr 1fr 140px 1.2fr 220px;gap:8px;align-items:center;border-bottom:1px solid var(--border);padding:8px 0}
    .row.header{font-weight:700;border-bottom-color:#444}
    input,select{
      height:38px;border-radius:8px;border:1px solid var(--border);background:#222;color:#fff;padding:0 10px;outline:none;
    }
    .muted{color:var(--muted);font-size:13px}
    .actions{display:flex;gap:6px}
    .danger{border-color:#803; background:#300}
    .danger:hover{background:#fff;color:#111;border-color:#a66}
    .grid2{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px}
    @media (max-width:1000px){ .row{grid-template-columns:60px 1fr 1fr 120px 1fr 220px} .grid2{grid-template-columns:1fr} }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div class="title">Користувачі</div>
      <div>
        <a class="btn" href="/admin/">← Назад</a>
      </div>
    </div>

    <div class="panel">
      <div class="muted" style="margin-bottom:10px">Створити нового користувача</div>
      <div class="grid2">
        <input id="new_username" placeholder="Логін" />
        <input id="new_password" placeholder="Пароль" />
        <input id="new_fullname" placeholder="Імʼя (повне)" />
        <select id="new_role">
          <option value="user">Звичайний</option>
          <option value="admin">Адмін</option>
        </select>
        <select id="new_info">
          <option>Звичайний користувач</option>
          <option>Системний Адміністратор</option>
        </select>
      </div>
      <button id="btn_create" class="btn">Створити</button>
    </div>

    <div class="panel" style="margin-top:16px">
      <div class="row header">
        <div>ID</div><div>Логін</div><div>Імʼя</div><div>Роль</div><div>Info</div><div>Дії</div>
      </div>
      <div id="list"></div>
      <div id="error" class="muted" style="margin-top:10px;color:#f88"></div>
    </div>
  </div>

<script>
// ===== Гард: пускаем только admin =====
(async function guard(){
  try{
    const r = await fetch('/api/me.php', {credentials:'include'});
    const j = await r.json();
    const role = j?.user?.role ?? j?.data?.role ?? 'user';
    if (role !== 'admin') location.href = '/';
  }catch(e){ location.href = '/'; }
})();

// ===== Утилиты =====
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

function showError(msg){
  const box = document.getElementById('error');
  box.textContent = msg || '';
}

// API-обёртка (показывает причину ошибки, если что-то не так)
async function api(method, body){
  const opt = { method, credentials:'include', headers:{'Content-Type':'application/json'} };
  if (body) opt.body = JSON.stringify(body);
  const r = await fetch('/api/admin/users.php', opt);
  let j = null;
  try { j = await r.json(); } catch(e) { j = { ok:false, error:'bad_json' }; }
  if (!r.ok || j.ok === false) {
    const msg = 'HTTP ' + r.status + (j && j.error ? (': ' + j.error) : '');
    throw (j && typeof j === 'object') ? j : { ok:false, error: msg };
  }
  return j;
}

// ===== Загрузка / рендер =====
async function load(){
  const list = document.getElementById('list');
  list.innerHTML = '<div class="muted" style="padding:10px 0">Завантаження...</div>';
  showError('');
  try{
    const j = await api('GET');
    list.innerHTML = '';
    if (!j.users || !j.users.length){
      list.innerHTML = '<div class="muted" style="padding:10px 0">Немає користувачів</div>';
      return;
    }
    for (const u of j.users) list.appendChild(userRow(u));
  }catch(e){
    list.innerHTML = '<div class="muted" style="padding:10px 0;color:#f88">Помилка завантаження</div>';
    showError('Деталі: ' + (e.error || 'невідома помилка'));
  }
}

function userRow(u){
  const row = el('div', {class:'row'});

  const id = el('div', {}, [String(u.id)]);
  const inUser = el('input', {value:u.username});
  const inName = el('input', {value:u.full_name});
  const selRole = el('select', {}, [
    el('option', {value:'user', selected: u.role==='user'}, ['Звичайний']),
    el('option', {value:'admin', selected: u.role==='admin'}, ['Адмін'])
  ]);
  const selInfo = el('select', {}, [
    el('option', {selected: u.info==='Звичайний користувач'}, ['Звичайний користувач']),
    el('option', {selected: u.info==='Системний Адміністратор'}, ['Системний Адміністратор'])
  ]);

  const actions = el('div', {class:'actions'});
  const btnSave = el('button', {class:'btn'}, ['Зберегти']);
  const btnPass = el('button', {class:'btn'}, ['Скинути пароль']);
  const btnDel  = el('button', {class:'btn danger'}, ['Видалити']);

  btnSave.onclick = async ()=>{
    try{
      await api('PUT', {
        id: u.id,
        username: inUser.value.trim(),
        full_name: inName.value.trim(),
        role: selRole.value,
        info: selInfo.value
      });
      btnSave.textContent = 'Збережено ✓';
      setTimeout(()=>btnSave.textContent='Зберегти', 1200);
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
  row.append(id, inUser, inName, selRole, selInfo, actions);
  return row;
}

// ===== Создание =====
document.getElementById('btn_create').onclick = async ()=>{
  const u = document.getElementById('new_username').value.trim();
  const p = document.getElementById('new_password').value;
  const f = document.getElementById('new_fullname').value.trim();
  const r = document.getElementById('new_role').value;
  const i = document.getElementById('new_info').value;

  if (!u || !p){ alert('Логін і пароль обовʼязкові'); return; }
  try{
    await api('POST', { username:u, password:p, full_name:f || 'Користувач', role:r, info:i });
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

// старт
load().catch(console.error);
</script>
</body>
</html>
