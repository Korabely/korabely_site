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
    html,body{ margin:0; height:100%; background:var(--bg); color:var(--text);
      font-family:Arial, sans-serif; font-size:14px }
    a{ color:#fff; text-decoration:none }
    .wrap{ max-width:820px; margin:0 auto; padding:18px }
    .top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:14px }
    .title{ font-size:20px; font-weight:700 }
    .panel{
      position: relative;
      background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:14px;
      padding-bottom: 96px; /* место под системные сообщения + кнопку */
    }

    .row{ display:grid; grid-template-columns:120px 1fr; gap:10px; align-items:flex-start; margin-bottom:10px }
    label{ color:#ddd; margin-top:8px }

    textarea{
      width:100%; min-height:180px; resize:vertical;
      background:#222; color:#fff; border:1px solid var(--border); border-radius:8px; padding:10px;
      outline:none;
    }
    input[type="text"], select{
      width:100%; background:#222; color:#fff; border:1px solid var(--border); border-radius:8px; padding:8px 10px; outline:none;
    }

    .btn{
      background:#222; border:1px solid var(--border); color:#fff; cursor:pointer; border-radius:8px;
      padding:8px 12px; font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.6);
      font-size:14px; line-height:1; min-height:34px;
    }
    .btn:hover{ background:#fff; color:#111; border-color:#aaa }
    .icon-btn{
      display:inline-flex; align-items:center; justify-content:center; gap:6px;
      background:#222; border:1px solid var(--border); color:#fff; cursor:pointer; border-radius:8px;
      padding:8px 10px; min-height:34px;
    }
    .icon-btn:hover{ background:#fff; color:#111; border-color:#aaa }
    .icon-btn svg{ width:16px; height:16px; }

    .muted{ color:var(--muted) }
    .hr{ height:1px; background:#2a2a2a; margin:12px 0 }
    .opts{ display:flex; gap:16px; color:#aaa; font-size:12px; }

    /* ===== шаблон ===== */
    .tpl-head{ color:#ddd; font-weight:600; margin-bottom:8px }
    .tpl-row{ display:grid; grid-template-columns:120px 1fr; gap:10px; align-items:center; margin-bottom:8px }
    .chip-group{ display:flex; gap:8px; }
    .chip{
      background:#222; border:1px solid var(--border); color:#fff; cursor:pointer; border-radius:8px;
      padding:6px 10px; font-weight:700; min-height:32px;
    }
    .chip.active{ background:#fff; color:#111; border-color:#aaa }

    .tpl-grid{
      display:grid;
      grid-template-columns: 1fr 220px 1fr auto; /* корзина справа */
      gap:8px; align-items:center; margin-bottom:8px;
    }
    @media (max-width: 740px){
      .tpl-grid{ grid-template-columns: 1fr 1fr; }
      .tpl-grid .icon-btn{ grid-column: 1 / -1; }
    }
    .tpl-preview{
      margin-top:6px; padding:10px; border:1px dashed #444; border-radius:8px; color:#eee;
      word-break: break-word; background:#181818;
    }

    /* ===== автокомплит ===== */
    .ac-wrap{ position:relative; }
    .ac-list{
      position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:50;
      background:#222; border:1px solid var(--border); border-radius:8px;
      box-shadow:0 8px 16px rgba(0,0,0,.6);
      max-height:260px; overflow:auto; padding:4px; display:none;
    }
    .ac-list.show{ display:block; }
    .ac-item{
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      padding:8px 10px; border-radius:6px; cursor:pointer;
    }
    .ac-item:hover, .ac-item.active{ background:#2a2a2a; }
    .ac-name{ color:#fff }
    .ac-region{ color:#aaa; font-size:12px; white-space:nowrap }

    /* ===== подвал панели ===== */
    .panel-footer-left{
      position:absolute; left:14px; right:180px; bottom:14px;
      display:flex; align-items:center;
    }
    .sys-msg{
      display:none; padding:8px 10px; border-radius:8px; border:1px solid transparent;
      font-size:13px; line-height:1.3; max-width:100%;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .sys-msg.ok{ display:block; background:#142318; border-color:#24502c; color:#b8f0c1; }
    .sys-msg.err{ display:block; background:#261616; border-color:#5a2b2b; color:#ffbdbd; }

    #sendBtn{
      position:absolute; right:14px; bottom:14px; z-index:10;
      background:#6b6b6b; border-color:#7a7a7a; color:#fff;
    }
    #sendBtn:hover{ background:#8f8f8f; border-color:#b3b3b3; color:#111; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="title">Telegram пост</div>
    <div><a class="btn" href="/admin/">← Назад</a></div>
  </div>

  <div class="panel">
    <!-- ===== ШАБЛОН ПОВІДОМЛЕННЯ ===== -->
    <div class="tpl-head">Шаблон повідомлення</div>

    <div class="tpl-row">
      <label>Знак</label>
      <div class="chip-group" id="chip_group">
        <button type="button" class="chip active" data-mark="❗️">❗️</button>
        <button type="button" class="chip" data-mark="‼️">‼️</button>
      </div>
    </div>

    <div class="tpl-grid">
      <!-- Город + автокомплит -->
      <div class="ac-wrap">
        <input id="tpl_city" type="text" placeholder="Населений пункт (піде в жирний: <b>місто</b>)" autocomplete="off">
        <div id="ac_list" class="ac-list" role="listbox" aria-label="Підказки населених пунктів"></div>
      </div>

      <!-- Область -->
      <select id="tpl_region">
        <option value="(Миколаївська обл)">(Миколаївська обл)</option>
        <option value="(Одеська обл)">(Одеська обл)</option>
        <option value="(Херсонська обл)">(Херсонська обл)</option>
		<option value="(Одеса)">(Одеса)</option>
      </select>

      <!-- Хвіст -->
      <input id="tpl_tail" type="text" placeholder="Текст після області…">

      <!-- Кнопка-кошик (очистити) -->
      <button class="icon-btn" id="tpl_clear" type="button" title="Очистити">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
          <path d="M10 11v6"></path>
          <path d="M14 11v6"></path>
          <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
        </svg>
      </button>
    </div>

    <div id="tpl_preview" class="tpl-preview" aria-live="polite"></div>

    <div class="hr"></div>

    <!-- ===== ВІЛЬНИЙ ТЕКСТ ДЛЯ ВІДПРАВКИ (автозбір з шаблону) ===== -->
    <div class="row">
      <label for="text">Текст</label>
      <div>
        <textarea id="text" placeholder="Текст посту (за замовчанням HTML-розмітка з config.telegram.php)"></textarea>
        <div class="muted" style="margin-top:6px">
          Формується автоматично зі «Шаблону повідомлення».
        </div>
      </div>
    </div>

    <div class="row" style="grid-template-columns:120px 1fr">
      <label>Опції</label>
      <div class="opts">
        <label><input type="checkbox" id="silent"> Тихе сповіщення</label>
        <label><input type="checkbox" id="no_preview"> Без превʼю посилань</label>
      </div>
    </div>

    <!-- «подвал» панели -->
    <div class="panel-footer-left">
      <div id="sys_msg" class="sys-msg" role="status" aria-live="polite"></div>
    </div>
    <button class="btn" id="sendBtn">Надіслати</button>
  </div>
</div>

<script>
// guard (мʼяко)
(async function guard(){
  try{
    const r = await fetch('/api/me.php', {credentials:'include'});
    const j = await r.json();
    const role = j?.user?.role ?? j?.data?.role ?? 'user';
    if (role !== 'admin'){ showErr('Доступ лише для адміністраторів'); }
  }catch(e){ showErr('Помилка авторизації'); }
})();

const sys = document.getElementById('sys_msg');
function showOk(msg){ sys.className='sys-msg ok'; sys.textContent=msg||'Готово'; }
function showErr(msg){ sys.className='sys-msg err'; sys.textContent=msg||'Помилка'; }
function clearSys(){ sys.className='sys-msg'; sys.textContent=''; }

function esc(s){ return s.replace(/[&<>"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[ch])); }

// ❗️ за замовчанням
let selectedMark = '❗️';
const chipGroup = document.getElementById('chip_group');
chipGroup.addEventListener('click', (e)=>{
  const btn = e.target.closest('.chip'); if (!btn) return;
  [...chipGroup.querySelectorAll('.chip')].forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  selectedMark = btn.dataset.mark || '❗️';
  autoAssemble();
});

const city   = document.getElementById('tpl_city');
const region = document.getElementById('tpl_region');
const tail   = document.getElementById('tpl_tail');
const acList = document.getElementById('ac_list');
[city, tail].forEach(el => el.addEventListener('input', autoAssemble));
let regionTouchedManually = false;
region.addEventListener('change', ()=>{ regionTouchedManually = true; autoAssemble(); });

function buildMessage(){
  const c = city.value.trim();
  const r = region.value || '';
  const t = tail.value.trim();
  const html = (c || t) ? `${selectedMark}<b>${esc(c)}</b> ${r}${t ? ' ' + esc(t) : ''}` : '';
  return { html };
}

/* ======== Автокомплит и автообласть ======== */
function norm(s){
  return (s||'')
    .toLowerCase()
    .replaceAll('ё','е')
    .replaceAll('ъ','')
    .replaceAll('’',"'")
    .replace(/[()\[\].,;:!?]/g,' ')
    .replace(/\s+/g,' ')
    .trim();
}

function toSelectValue(regionName){
  // Приведём к виду "(Херсонська обл)"
  const clean = String(regionName||'').replace(/[()]/g,'').trim();
  return `(${clean})`;
}

// Базовый (встроенный) словарь на случай, если JSON не загрузится
const DEFAULT_CITIES = [
  {name:'Миколаїв', region:'Миколаївська обл', aliases:['Николаев','Mykolaiv']},
  {name:'Снігурівка', region:'Миколаївська обл', aliases:['Снигиревка','Снигирёвка']},
  {name:'Очаків/села', region:'Миколаївська обл', aliases:['Очаков']},
  {name:'Баштанка', region:'Миколаївська обл', aliases:[]},
  {name:'Возсіятське', region:'Миколаївська обл', aliases:[]},
  {name:'Єланець', region:'Миколаївська обл', aliases:[]},
  {name:'Володимирівка', region:'Миколаївська обл', aliases:[]},
  {name:'Казанка', region:'Миколаївська обл', aliases:[]},
  {name:'Вознесеньск', region:'Миколаївська обл', aliases:[]},
  {name:'Південноукраїнськ', region:'Миколаївська обл', aliases:[]},
  {name:'Первомайськ', region:'Миколаївська обл', aliases:[]},
  {name:'Арбузинка', region:'Миколаївська обл', aliases:[]},
  {name:'Братське', region:'Миколаївська обл', aliases:[]},
  {name:'Криве Озеро', region:'Миколаївська обл', aliases:[]},
  {name:'Врадіївка', region:'Миколаївська обл', aliases:[]},
  {name:'Токарівка', region:'Миколаївська обл', aliases:[]},
  {name:'Доманівка', region:'Миколаївська обл', aliases:[]},
  {name:'Веселинове', region:'Миколаївська обл', aliases:[]},
  {name:'Березанка', region:'Миколаївська обл', aliases:[]},
  {name:'Пересадівка', region:'Миколаївська обл', aliases:[]},
  {name:'Воскресенське', region:'Миколаївська обл', aliases:[]},
  {name:'Шевченкове', region:'Миколаївська обл', aliases:[]},
  {name:'Галицинове', region:'Миколаївська обл', aliases:[]},
  {name:'Лимани', region:'Миколаївська обл', aliases:[]},
  {name:'Лупареве', region:'Миколаївська обл', aliases:[]},
  {name:'Парутине', region:'Миколаївська обл', aliases:[]},
  {name:'Козирка', region:'Миколаївська обл', aliases:[]},
  {name:'Солончаки', region:'Миколаївська обл', aliases:[]},
  {name:'Рибаківка', region:'Миколаївська обл', aliases:[]},
  {name:'Коблево', region:'Миколаївська обл', aliases:[]},
  {name:'Одеса', region:'Одеська обл', aliases:['Одесса','Odesa']},
  {name:'Аркадія', region:'Одеса', aliases:[]},
  {name:'Великий Фонтан', region:'Одеса', aliases:[]},
  {name:'Чубаївка', region:'Одеса', aliases:[]},
  {name:'Малий Фонтан', region:'Одеса', aliases:[]},
  {name:'Ланжерон', region:'Одеса', aliases:[]},
  {name:'Пересип', region:'Одеса', aliases:[]},
  {name:'Центр', region:'Одеса', aliases:[]},
  {name:'Лузанівка', region:'Одеса', aliases:[]},
  {name:'Поскот', region:'Одеса', aliases:[]},
  {name:'Крива Балка', region:'Одеса', aliases:[]},
  {name:'Клеверний', region:'Одеса', aliases:[]},
  {name:'Радужний', region:'Одеса', aliases:[]},
  {name:'Школьний', region:'Одеса', aliases:[]},
  {name:'Авангард', region:'Одеська обл', aliases:[]},
  {name:'Чабанка', region:'Одеська обл', aliases:[]},
  {name:'Чорноморське', region:'Одеська обл', aliases:[]},
  {name:'Південне', region:'Одеська обл', aliases:[]},
  {name:'Нові Білярі', region:'Одеська обл', aliases:[]},
  {name:'Визирка', region:'Одеська обл', aliases:[]},
  {name:'Доброслав', region:'Одеська обл', aliases:[]},
  {name:'Курісове', region:'Одеська обл', aliases:[]},
  {name:'Березівка', region:'Одеська обл', aliases:[]},
  {name:'Ширяєве', region:'Одеська обл', aliases:[]},
  {name:'Ананьїв', region:'Одеська обл', aliases:[]},
  {name:'Любашівка', region:'Одеська обл', aliases:[]},
  {name:'Саврань', region:'Одеська обл', aliases:[]},
  {name:'Балта', region:'Одеська обл', aliases:[]},
  {name:'Кодима', region:'Одеська обл', aliases:[]},
  {name:'Роздільна', region:'Одеська обл', aliases:[]},
  {name:'Біляївка', region:'Одеська обл', aliases:[]},
  {name:'Затока', region:'Одеська обл', aliases:[]},
  {name:'Сергіївка', region:'Одеська обл', aliases:[]},
  {name:'Сарата', region:'Одеська обл', aliases:[]},
  {name:'Татарбунари', region:'Одеська обл', aliases:[]},
  {name:'Приморське', region:'Одеська обл', aliases:[]},
  {name:'Вилкове', region:'Одеська обл', aliases:[]},
  {name:'Кілія', region:'Одеська обл', aliases:[]},
  {name:'Ізмаїл', region:'Одеська обл', aliases:[]},
  {name:'Рені', region:'Одеська обл', aliases:[]},
  {name:'Тузли', region:'Одеська обл', aliases:['Тузли']},
  {name:'Лебедівка', region:'Одеська обл', aliases:['Лебедівка']},
  {name:'Фонтанка', region:'Одеська обл', aliases:[]},
  {name:'Чорноморськ', region:'Одеська обл', aliases:['Черноморск','Іллічівськ','Ильичевск']},
  {name:'Білгород-Дністровський', region:'Одеська обл', aliases:['Белгород-Днестровский']},
  {name:'Херсон', region:'Херсонська обл', aliases:['Kherson']},
  {name:'Нова Каховка', region:'Херсонська обл', aliases:['Новая Каховка']},
  {name:'Каховка', region:'Херсонська обл', aliases:[]},
  {name:'Берислав', region:'Херсонська обл', aliases:[]},
  {name:'Велика-Олександрівка', region:'Херсонська обл', aliases:[]},
  {name:'Посад-Покровське', region:'Херсонська обл', aliases:[]},
  {name:'Антонівка', region:'Херсонська обл', aliases:[]},
  {name:'Станіслав', region:'Херсонська обл', aliases:[]},
  {name:'Кізомис', region:'Херсонська обл', aliases:[]},
  {name:'Білозерка', region:'Херсонська обл', aliases:[]},
  {name:'Томина Балка', region:'Херсонська обл', aliases:[]},
  {name:'Киселівка', region:'Херсонська обл', aliases:[]},
  {name:'Чорнобаївка', region:'Херсонська обл', aliases:[]},
  {name:'Садове', region:'Херсонська обл', aliases:[]},
  {name:'Дар*ївка', region:'Херсонська обл', aliases:[]},
  {name:'Дудчани', region:'Херсонська обл', aliases:[]},
  {name:'Давидів Брід', region:'Херсонська обл', aliases:[]},
  {name:'Архангельське', region:'Херсонська обл', aliases:[]},
  {name:'Високопілля', region:'Херсонська обл', aliases:[]},
];

let CITY_INDEX = []; // {name, region, tokens[]}
async function loadCityDict(){
  let dict = DEFAULT_CITIES;
  try{
    const r = await fetch('/admin/cities.json', {credentials:'include', cache:'no-store'});
    if (r.ok){
      const j = await r.json();
      if (Array.isArray(j) && j.length){
        dict = j.concat(DEFAULT_CITIES); // JSON сверху, дефолт — запасом
      }
    }
  }catch(e){ /* fallback на дефолт */ }

  // Индексация
  const seen = new Set();
  CITY_INDEX = [];
  for (const it of dict){
    const name = String(it.name||'').trim();
    const regionName = String(it.region||'').trim();
    if (!name || !regionName) continue;
    const key = name + '|' + regionName;
    if (seen.has(key)) continue;
    seen.add(key);
    const aliases = Array.isArray(it.aliases) ? it.aliases : [];
    const tokens = [norm(name), ...aliases.map(norm)].filter(Boolean);
    CITY_INDEX.push({ name, region: regionName, tokens });
  }
}
loadCityDict().then(()=> autoAssemble());

// Подсказки
let acActiveIndex = -1;
function renderSuggestions(q){
  const qn = norm(q);
  if (qn.length < 2){ acList.classList.remove('show'); acList.innerHTML=''; return; }

  const out = [];
  for (const item of CITY_INDEX){
    if (item.tokens.some(t => t.includes(qn))){
      out.push(item);
      if (out.length >= 12) break;
    }
  }
  if (!out.length){
    acList.innerHTML = `<div class="ac-item"><span class="ac-name muted">Нічого не знайдено</span></div>`;
    acList.classList.add('show');
    acActiveIndex = -1;
    return;
  }

  acList.innerHTML = out.map((it,i)=>(
    `<div class="ac-item" data-i="${i}">
       <span class="ac-name">${it.name}</span>
       <span class="ac-region">${it.region}</span>
     </div>`
  )).join('');
  acList.classList.add('show');
  acActiveIndex = -1;

  // click
  [...acList.querySelectorAll('.ac-item')].forEach((el, i)=>{
    el.addEventListener('mousedown', (e)=>{ // mousedown, чтобы не потерять фокус инпута
      e.preventDefault();
      applySuggestion(out[i]);
    });
  });
}

function applySuggestion(item){
  city.value = item.name;
  // автообласть
  if (!regionTouchedManually || !region.value){
    region.value = toSelectValue(item.region);
  }
  acList.classList.remove('show');
  acList.innerHTML = '';
  autoAssemble();
}

city.addEventListener('input', ()=>{
  renderSuggestions(city.value);
  // Параллельно автообласть по точному совпадению
  const q = norm(city.value);
  if (q.length >= 2){
    const exact = CITY_INDEX.find(it => it.tokens.includes(q));
    if (exact && (!regionTouchedManually || !region.value)){
      region.value = toSelectValue(exact.region);
      showOk('Автоматично вибрано область: ' + region.value);
    }
  }
});

city.addEventListener('keydown', (e)=>{
  if (!acList.classList.contains('show')) return;
  const items = [...acList.querySelectorAll('.ac-item')];
  const max = items.length - 1;

  if (e.key === 'ArrowDown'){
    e.preventDefault();
    acActiveIndex = Math.min(max, acActiveIndex + 1);
    items.forEach(el => el.classList.remove('active'));
    if (acActiveIndex >= 0) items[acActiveIndex].classList.add('active');
  } else if (e.key === 'ArrowUp'){
    e.preventDefault();
    acActiveIndex = Math.max(-1, acActiveIndex - 1);
    items.forEach(el => el.classList.remove('active'));
    if (acActiveIndex >= 0) items[acActiveIndex].classList.add('active');
  } else if (e.key === 'Enter'){
    if (acActiveIndex >= 0){
      e.preventDefault();
      const name = items[acActiveIndex].querySelector('.ac-name')?.textContent || '';
      const entry = CITY_INDEX.find(it => it.name === name);
      if (entry) applySuggestion(entry);
    }
  } else if (e.key === 'Escape'){
    acList.classList.remove('show'); acList.innerHTML='';
  }
});

// Закрытие при уходе фокуса кликом
document.addEventListener('click', (e)=>{
  if (!acList.contains(e.target) && e.target !== city){
    acList.classList.remove('show');
  }
});

/* ======== Автосборка / предпросмотр ======== */
function autoAssemble(){
  const prev = document.getElementById('tpl_preview');
  const { html } = buildMessage();
  prev.innerHTML = html || '<span class="muted">Попередній перегляд буде тут…</span>';
  document.getElementById('text').value = html;
}
autoAssemble();

// Очистка
document.getElementById('tpl_clear').onclick = ()=>{
  city.value = '';
  tail.value = '';
  region.selectedIndex = 0;  // вернёт «(Миколаївська обл)»
  regionTouchedManually = false;
  selectedMark = '❗️';
  [...chipGroup.querySelectorAll('.chip')].forEach(b=>b.classList.remove('active'));
  chipGroup.querySelector('[data-mark="❗️"]').classList.add('active');
  acList.classList.remove('show'); acList.innerHTML='';
  autoAssemble();
  showOk('Очищено.');
};

// Отправка
document.getElementById('sendBtn').onclick = async ()=>{
  clearSys();
  const text  = document.getElementById('text').value.trim();
  const silent = document.getElementById('silent').checked ? 1 : 0;
  const noPrev = document.getElementById('no_preview').checked ? 1 : 0;

  const form = new FormData();
  if (text) form.append('text', text);
  if (silent) form.append('disable_notification', '1');
  if (noPrev) form.append('disable_web_page_preview', '1');

  try{
    const r = await fetch('/api/admin/telegram.php', { method:'POST', body: form, credentials:'include' });
    const j = await r.json().catch(()=>null);

    if (!r.ok){
      const code = r.status;
      const err  = (j && j.error) ? j.error : ('HTTP '+code);
      if (err === 'config_missing') showErr('Відсутній /config.telegram.php');
      else if (err === 'config_invalid') showErr('Невірний BOT_TOKEN або CHAT_ID у /config.telegram.php');
      else if (String(err).startsWith('telegram_http_')) showErr('Telegram HTTP: ' + String(err).replace('telegram_http_',''));
      else showErr('Помилка: ' + err);
      return;
    }
    if (!j || j.ok === false){ showErr('Помилка: ' + (j && j.error ? j.error : 'невідома')); return; }
    showOk('Надіслано ✅');
  }catch(e){
    showErr('Помилка мережі: ' + (e.message || 'невідома'));
  }
};
</script>
</body>
</html>
