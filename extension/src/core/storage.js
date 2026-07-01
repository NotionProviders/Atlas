/*
 * Thin wrapper over chrome.storage.local for the extension's connection state:
 * the Atlas API base URL and the paired bearer token. This is the only place
 * the token is persisted.
 */

const KEY = 'atlas_connection';

export async function getConnection() {
  const data = await chrome.storage.local.get(KEY);
  return data[KEY] || { apiBase: '', token: '', name: '', app: '' };
}

export async function setConnection(conn) {
  await chrome.storage.local.set({ [KEY]: conn });
  return conn;
}

export async function clearConnection() {
  await chrome.storage.local.remove(KEY);
}

export async function isConnected() {
  const c = await getConnection();
  return !!(c.apiBase && c.token);
}
