/* Popup UI: pairing, tab detection, starting a run, and live progress. */

const $ = (id) => document.getElementById(id);

function bg(cmd, payload) {
  return new Promise((resolve) => {
    chrome.runtime.sendMessage({ cmd, ...(payload || {}) }, (res) => {
      if (chrome.runtime.lastError) return resolve({ ok: false, error: chrome.runtime.lastError.message });
      resolve(res || { ok: false, error: 'No response.' });
    });
  });
}

async function activeTab() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  return tab;
}

function consoleUrlFor(apiBase, slug) {
  const origin = String(apiBase).replace(/\/api\/?$/, '');
  return `${origin}/console/migrations/${slug}`;
}

let config = { apiBase: '', connected: false };
let supportedTab = false;

async function init() {
  const res = await bg('getConfig');
  config = res.ok ? res.data : { apiBase: '', connected: false };

  if (config.connected) {
    showMain();
  } else {
    $('viewPair').hidden = false;
    $('viewMain').hidden = true;
    if (config.apiBase) $('apiBase').value = config.apiBase;
  }
}

// ── Pairing ──────────────────────────────────────────────────────────
$('btnConnect').addEventListener('click', async () => {
  const apiBase = $('apiBase').value.trim();
  const code = $('code').value.trim().toUpperCase();
  const name = $('name').value.trim();
  const msg = $('pairMsg');
  msg.textContent = ''; msg.className = 'msg';

  if (!apiBase || !code) { msg.textContent = 'Enter the API URL and a pairing code.'; msg.className = 'msg err'; return; }

  $('btnConnect').disabled = true;
  $('btnConnect').textContent = 'Connecting…';
  const res = await bg('pair', { apiBase, code, name });
  $('btnConnect').disabled = false;
  $('btnConnect').textContent = 'Connect';

  if (!res.ok) { msg.textContent = res.error; msg.className = 'msg err'; return; }
  await init();
});

$('btnDisconnect').addEventListener('click', async () => {
  await bg('disconnect');
  location.reload();
});

// ── Connected view ───────────────────────────────────────────────────
async function showMain() {
  $('viewPair').hidden = true;
  $('viewMain').hidden = false;
  $('connDot').classList.add('on');
  $('connDot').title = 'Connected';
  $('identName').textContent = config.name ? `Connected · ${config.name}` : 'Connected';

  await detectTab();
  await loadPending();
}

async function detectTab() {
  const state = $('tabState');
  const tab = await activeTab();
  if (!tab || !tab.id) { state.textContent = 'No active tab.'; state.className = 'tab-state bad'; return; }

  let ping;
  try {
    ping = await chrome.tabs.sendMessage(tab.id, { cmd: 'ping' });
  } catch (e) {
    ping = null;
  }

  if (ping && ping.supported) {
    supportedTab = true;
    state.textContent = `Ready: ${ping.label} tab detected.`;
    state.className = 'tab-state ok';
    $('btnStart').disabled = false;
  } else {
    supportedTab = false;
    state.textContent = 'Open a Microsoft Loop workspace tab, then reopen this popup.';
    state.className = 'tab-state bad';
    $('btnStart').disabled = true;
  }
}

async function loadPending() {
  const res = await bg('listPending');
  const wrap = $('pendingWrap');
  const list = $('pendingList');
  if (!res.ok || !res.data || !res.data.runs || !res.data.runs.length) { wrap.hidden = true; return; }

  list.innerHTML = '';
  res.data.runs.forEach((run) => {
    const li = document.createElement('li');
    const label = document.createElement('span');
    label.textContent = `${run.name} · ${run.sourceLabel}`;
    const go = document.createElement('button');
    go.className = 'link go';
    go.textContent = 'Run →';
    go.disabled = !supportedTab;
    go.addEventListener('click', () => startMigration({ slug: run.slug, name: run.name }));
    li.append(label, go);
    list.appendChild(li);
  });
  wrap.hidden = false;
}

// ── Start + progress ─────────────────────────────────────────────────
$('btnStart').addEventListener('click', () => {
  startMigration({ name: $('runName').value.trim() || undefined });
});

async function startMigration(options) {
  const tab = await activeTab();
  if (!tab || !tab.id) return;

  $('startWrap').hidden = true;
  $('pendingWrap').hidden = true;
  $('progressWrap').hidden = false;
  setProg('Starting…', 0);

  try {
    await chrome.tabs.sendMessage(tab.id, { cmd: 'startMigration', options });
  } catch (e) {
    setProg('Could not reach the tab. Reload the Loop page and try again.', 0, 'err');
  }
}

function setProg(line, pct, cls) {
  const l = $('progLine');
  l.textContent = line;
  l.className = 'prog-line' + (cls ? ' ' + cls : '');
  if (typeof pct === 'number') $('barFill').style.width = pct + '%';
}

// Progress events emitted by the content-script runner.
chrome.runtime.onMessage.addListener((msg) => {
  if (!msg || !msg.evt) return;

  switch (msg.evt) {
    case 'status':
      setProg(msg.message || '…', undefined);
      break;
    case 'started': {
      const link = $('openConsole');
      link.href = consoleUrlFor(config.apiBase, msg.slug);
      link.hidden = false;
      break;
    }
    case 'page': {
      const pct = msg.total ? Math.round((msg.processed / msg.total) * 100) : undefined;
      if (typeof pct === 'number') $('barFill').style.width = pct + '%';
      $('progCurrent').textContent = `${msg.processed}/${msg.total} · ${msg.title}`;
      break;
    }
    case 'warn':
      $('progCurrent').textContent = msg.message || '';
      break;
    case 'done':
      if (msg.ok) {
        setProg(`Done — ${msg.summary.ok} pages, ${msg.summary.failed} failed.`, 100, 'done');
      } else {
        setProg(msg.error || 'Migration failed.', undefined, 'err');
      }
      $('progCurrent').textContent = '';
      break;
  }
});

init();
