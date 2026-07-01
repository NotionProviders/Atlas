/*
 * Background service worker — the extension's network hub. The content-script
 * runner and the popup both message it to reach the Atlas API, so the token
 * lives in exactly one place and every authenticated fetch happens here.
 *
 * Handlers return { ok: true, data } or { ok: false, error } via sendResponse.
 */

import * as api from './core/api.js';
import { getConnection, setConnection, clearConnection } from './core/storage.js';

const HANDLERS = {
  async getConfig() {
    const c = await getConnection();
    return { apiBase: c.apiBase, connected: !!(c.apiBase && c.token), name: c.name, app: c.app };
  },

  async pair({ apiBase, code, name }) {
    const result = await api.pair(apiBase, code, name);
    await setConnection({
      apiBase,
      token: result.token,
      name: name || 'This browser',
      app: result.app || 'Atlas',
    });
    return { app: result.app, sources: result.sources };
  },

  async disconnect() {
    await clearConnection();
    return { disconnected: true };
  },

  async listPending() {
    const c = await getConnection();
    return api.listPending(c.apiBase, c.token);
  },

  async createRun({ source, name, sourceRef }) {
    const c = await getConnection();
    return api.createRun(c.apiBase, c.token, { source, name, sourceRef });
  },

  async claimRun({ slug }) {
    const c = await getConnection();
    return api.claimRun(c.apiBase, c.token, slug);
  },

  async setTree({ slug, total }) {
    const c = await getConnection();
    return api.setTree(c.apiBase, c.token, slug, total);
  },

  async sendNodes({ slug, nodes }) {
    const c = await getConnection();
    return api.sendNodes(c.apiBase, c.token, slug, nodes);
  },

  async completeRun({ slug, status }) {
    const c = await getConnection();
    return api.completeRun(c.apiBase, c.token, slug, status);
  },
};

chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (!msg || !msg.cmd || !(msg.cmd in HANDLERS)) return false;

  HANDLERS[msg.cmd](msg)
    .then((data) => sendResponse({ ok: true, data }))
    .catch((err) => sendResponse({ ok: false, error: err.message || String(err) }));

  return true; // keep the message channel open for the async response
});
