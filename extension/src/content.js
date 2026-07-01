/*
 * Content-script runner. Lives in the source tab (e.g. Loop) for as long as it
 * stays open — which is why the *crawl* is driven here, not in the background
 * service worker (MV3 kills that mid-job). It picks the connector matching the
 * current URL, walks the workspace, and streams pages to the Atlas backend via
 * the background worker (which holds the token and does the authenticated
 * fetches, sidestepping CORS).
 */
(function () {
  const BATCH_SIZE = 12;

  function pickConnector() {
    const list = window.__ATLAS_CONNECTORS || [];
    return list.find((c) => c.matches(location.href)) || null;
  }

  // Relay an API call to the background worker (which has the token + host).
  function api(cmd, payload) {
    return new Promise((resolve) => {
      chrome.runtime.sendMessage({ cmd, ...payload }, (res) => {
        if (chrome.runtime.lastError) return resolve({ ok: false, error: chrome.runtime.lastError.message });
        resolve(res || { ok: false, error: 'No response from background.' });
      });
    });
  }

  function emit(evt, payload) {
    try { chrome.runtime.sendMessage({ evt, ...payload }); } catch (e) { /* popup may be closed */ }
  }

  async function run(options) {
    const connector = pickConnector();
    if (!connector) {
      emit('done', { ok: false, error: 'This tab is not a supported source. Open a Loop workspace and try again.' });
      return;
    }

    emit('status', { phase: 'connecting', message: `Reading ${connector.label}…` });

    if (!(await connector.ready())) {
      emit('done', { ok: false, error: `Could not find the ${connector.label} page tree. Make sure a workspace is open.` });
      return;
    }

    const workspaceName = await connector.workspaceName();
    const name = (options && options.name) || workspaceName;

    // Either claim a console-queued run, or create a fresh one.
    let slug = options && options.slug;
    if (slug) {
      const claim = await api('claimRun', { slug });
      if (!claim.ok) { emit('done', { ok: false, error: 'Could not claim run: ' + claim.error }); return; }
    } else {
      const created = await api('createRun', { source: connector.key, name, sourceRef: location.href });
      if (!created.ok) { emit('done', { ok: false, error: 'Could not start run: ' + created.error }); return; }
      slug = created.data.run.slug;
    }

    emit('started', { slug, name });
    emit('status', { phase: 'discovering', message: 'Mapping the page tree…' });

    const nodes = await connector.discover();
    await api('setTree', { slug, total: nodes.length });
    emit('status', { phase: 'scraping', message: `Found ${nodes.length} pages. Scraping…`, total: nodes.length });

    let batch = [];
    let processed = 0;
    let ok = 0;
    let failed = 0;

    const flush = async () => {
      if (!batch.length) return;
      const res = await api('sendNodes', { slug, nodes: batch });
      if (!res.ok) emit('warn', { message: 'A batch failed to upload: ' + res.error });
      batch = [];
    };

    for (const node of nodes) {
      emit('page', { title: node.title, state: 'scraping', processed, total: nodes.length });
      let extracted;
      try {
        extracted = await connector.extractPage(node);
      } catch (e) {
        extracted = { error: e.message || 'extract failed' };
      }

      const payloadNode = {
        externalId: node.externalId || null,
        title: node.title,
        path: node.path || [],
        level: node.level || 0,
        hasChildren: !!node.hasChildren,
        ...extracted,
      };
      batch.push(payloadNode);

      processed++;
      if (extracted && extracted.error) { failed++; emit('page', { title: node.title, state: 'failed', processed, total: nodes.length }); }
      else if (extracted && extracted.isAttachment) { emit('page', { title: node.title, state: 'skipped', processed, total: nodes.length }); }
      else { ok++; emit('page', { title: node.title, state: 'ok', processed, total: nodes.length }); }

      if (batch.length >= BATCH_SIZE) await flush();
    }

    await flush();

    const done = await api('completeRun', { slug, status: 'completed' });
    emit('done', {
      ok: done.ok,
      slug,
      summary: { total: nodes.length, ok, failed },
      error: done.ok ? null : done.error,
    });
  }

  chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
    if (msg && msg.cmd === 'ping') {
      const connector = pickConnector();
      sendResponse({ ok: true, supported: !!connector, source: connector ? connector.key : null, label: connector ? connector.label : null });
      return true;
    }
    if (msg && msg.cmd === 'startMigration') {
      run(msg.options || {});
      sendResponse({ ok: true });
      return true;
    }
    return false;
  });
})();
