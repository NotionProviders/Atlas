/*
 * Atlas API client. All calls are bearer-token authenticated (except pairing,
 * which trades a console-minted code for a token). Runs in the background
 * service worker; the Atlas /api/* routes send permissive CORS headers, so the
 * cross-origin fetch from the extension origin is allowed.
 */

function normalizeBase(apiBase) {
  return String(apiBase || '').replace(/\/+$/, '');
}

async function request(method, url, { token, body } = {}) {
  const headers = { 'Accept': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  let res;
  try {
    res = await fetch(url, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined });
  } catch (e) {
    throw new Error(`Network error reaching Atlas (${url}). Is the API URL correct and reachable?`);
  }

  let data = null;
  const text = await res.text();
  if (text) { try { data = JSON.parse(text); } catch (e) { data = { raw: text }; } }

  if (!res.ok) {
    const msg = (data && (data.message || data.error)) || `HTTP ${res.status}`;
    throw new Error(msg);
  }
  return data;
}

export async function pair(apiBase, code, name) {
  const base = normalizeBase(apiBase);
  return request('POST', `${base}/extension/pair`, { body: { code, name } });
}

export async function me(apiBase, token) {
  return request('GET', `${normalizeBase(apiBase)}/extension/me`, { token });
}

export async function listPending(apiBase, token) {
  return request('GET', `${normalizeBase(apiBase)}/extension/pending`, { token });
}

export async function createRun(apiBase, token, payload) {
  return request('POST', `${normalizeBase(apiBase)}/migrations`, { token, body: payload });
}

export async function claimRun(apiBase, token, slug) {
  return request('POST', `${normalizeBase(apiBase)}/migrations/${slug}/claim`, { token, body: {} });
}

export async function setTree(apiBase, token, slug, total) {
  return request('POST', `${normalizeBase(apiBase)}/migrations/${slug}/tree`, { token, body: { total } });
}

export async function sendNodes(apiBase, token, slug, nodes) {
  return request('POST', `${normalizeBase(apiBase)}/migrations/${slug}/nodes`, { token, body: { nodes } });
}

export async function completeRun(apiBase, token, slug, status) {
  return request('POST', `${normalizeBase(apiBase)}/migrations/${slug}/complete`, { token, body: { status } });
}
