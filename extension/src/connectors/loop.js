/*
 * Loop connector — the source adapter for Microsoft Loop.
 *
 * This is the JS port of the Python Playwright scraper's DOM logic, but it runs
 * *inside your already-authenticated Loop tab* as a content script, so there's
 * no separate browser and no login to automate. It walks the sidebar page tree,
 * clicks each page, and reads the "scriptor" canvas.
 *
 * A connector implements: key, label, matches(url), ready(), workspaceName(),
 * discover() and extractPage(node). Register it on window.__ATLAS_CONNECTORS so
 * the shared content-script runner can find it. Add a new tool = add a file like
 * this + a manifest match; nothing else changes.
 */
(function () {
  const SEL = {
    workspace: "[data-testid='WorkspaceNameTestId']",
    treeItem: "[data-testid='page-tree'] [role='treeitem'][title]",
    collapsed: "[data-testid='page-tree'] [role='treeitem'][aria-expanded='false']",
    chevron: "[data-testid='CollapsiblePageChevron']",
    content: ".scriptor-canvas, [class*='scriptor-canvas'], [data-testid='page-content'], [class*='PageContent'], [class*='pageContent']",
    reload: "button, [data-testid]",
  };

  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

  async function waitFor(selector, timeout = 30000) {
    const start = Date.now();
    while (Date.now() - start < timeout) {
      const el = document.querySelector(selector);
      if (el) return el;
      await sleep(200);
    }
    return null;
  }

  async function expandAll() {
    for (let pass = 0; pass < 12; pass++) {
      const collapsed = document.querySelectorAll(SEL.collapsed);
      if (!collapsed.length) break;
      for (const el of collapsed) {
        const chevron = el.querySelector(SEL.chevron);
        if (chevron) {
          try { chevron.click(); await sleep(120); } catch (e) { /* ignore */ }
        }
      }
      await sleep(250);
    }
  }

  // Loop attaches Office files as "LoopButton" tree items whose id is a base64
  // SharePoint URL. Decode it to tell a real page from an attachment.
  function attachmentInfo(itemEl, title) {
    const btn = itemEl.querySelector("[data-testid='LoopButton']");
    if (!btn) return { isAttachment: false };
    const id = btn.getAttribute('id') || '';
    try {
      const decoded = atob(id.replace('SPO_', '') + '==');
      if (['/:x:/', '/:w:/', '/:p:/'].some((p) => decoded.includes(p))) {
        let url = decoded.startsWith('http') ? decoded : '';
        if (url && !url.includes('?')) url += '?download=1';
        return { isAttachment: true, attachmentUrl: url, attachmentFilename: title };
      }
    } catch (e) { /* not decodable — treat as page */ }
    return { isAttachment: false };
  }

  function detectErrorScreen() {
    const btns = Array.from(document.querySelectorAll('button'));
    return btns.find((b) => /^(reload|retry)$/i.test((b.textContent || '').trim()));
  }

  // Clean a cloned canvas node before conversion: drop UI-chrome images and
  // dead "[Link]" anchors, and unwrap <p> inside <li> (Loop double-spaces lists).
  function cleanCanvas(node) {
    node.querySelectorAll('img').forEach((img) => {
      const src = img.getAttribute('src') || '';
      if (src.startsWith('blob:') || src.includes('cdn.office.net') || src.includes('res.cdn')) img.remove();
    });
    node.querySelectorAll('a').forEach((a) => {
      if ((a.textContent || '').trim() === 'Link') a.remove();
    });
    node.querySelectorAll('li > p').forEach((p) => {
      const parent = p.parentNode;
      while (p.firstChild) parent.insertBefore(p.firstChild, p);
      p.remove();
    });
    return node;
  }

  function extractComments() {
    const out = [];
    const els = document.querySelectorAll(
      "[data-testid*='comment'], [class*='Comment'], [class*='comment'], [class*='Annotation']"
    );
    els.forEach((el) => {
      try {
        const authorEl = el.querySelector("[class*='author'], [class*='Author'], [class*='name']");
        const timeEl = el.querySelector("time, [class*='time'], [class*='Time'], [class*='date']");
        const textEl = el.querySelector("[class*='text'], [class*='body'], [class*='content'], p");
        const text = ((textEl || el).textContent || '').trim();
        if (!text) return;
        out.push({
          author: authorEl ? (authorEl.textContent || '').trim() : '',
          timestamp: timeEl ? (timeEl.getAttribute('datetime') || (timeEl.textContent || '').trim()) : '',
          text,
        });
      } catch (e) { /* skip */ }
    });
    return out;
  }

  const LoopConnector = {
    key: 'loop',
    label: 'Microsoft Loop',

    matches(url) {
      return /^https:\/\/loop\.cloud\.microsoft/.test(url);
    },

    async ready() {
      return (await waitFor(SEL.treeItem, 30000)) !== null;
    },

    async workspaceName() {
      const el = document.querySelector(SEL.workspace);
      const raw = el ? (el.textContent || '').trim() : (document.title || 'Loop workspace');
      return raw.split('\n')[0].trim() || 'Loop workspace';
    },

    /**
     * Walk the sidebar into a flat, ordered node list. Each node carries its
     * ancestor path and DOM index so extractPage can click the right item.
     */
    async discover() {
      await expandAll();
      const items = Array.from(document.querySelectorAll(SEL.treeItem));
      const flat = items.map((el, index) => {
        const title = (el.getAttribute('title') || '').trim();
        const level = parseInt(el.getAttribute('aria-level') || '1', 10) || 1;
        return { title, level, domIndex: index, ...attachmentInfo(el, title) };
      }).filter((n) => n.title);

      // Derive ancestor path + hasChildren from the level sequence.
      const stack = [];
      flat.forEach((node, i) => {
        while (stack.length && stack[stack.length - 1].level >= node.level) stack.pop();
        node.path = stack.map((s) => s.title);
        node.hasChildren = i + 1 < flat.length && flat[i + 1].level > node.level;
        stack.push(node);
      });

      return flat;
    },

    /**
     * Navigate to a page and return its markdown + comments. Attachments are
     * not clicked — their URL was captured during discover().
     */
    async extractPage(node) {
      if (node.isAttachment) {
        return { isAttachment: true, attachmentUrl: node.attachmentUrl, attachmentFilename: node.attachmentFilename };
      }

      const items = document.querySelectorAll(SEL.treeItem);
      if (node.domIndex >= items.length) {
        return { error: `Tree item ${node.domIndex} out of range (${items.length} items)` };
      }

      const priorUrl = location.href;
      try { items[node.domIndex].click(); } catch (e) { return { error: 'Could not click page: ' + e.message }; }

      // Wait for navigation or content to settle.
      const start = Date.now();
      while (Date.now() - start < 10000 && location.href === priorUrl) await sleep(150);
      await waitFor(SEL.content, 6000);
      await sleep(300);

      // Loop's "We don't know what happened" screen — retry once.
      let err = detectErrorScreen();
      if (err) {
        try { err.click(); } catch (e) { /* ignore */ }
        await waitFor(SEL.content, 6000);
        if (detectErrorScreen()) return { error: 'Loop error screen after retry — manual review needed' };
      }

      const contentEl = document.querySelector(SEL.content) || document.body;
      const clone = contentEl.cloneNode(true);
      cleanCanvas(clone);
      const markdown = window.AtlasHtmlToMarkdown(clone);

      return { markdown, comments: extractComments() };
    },
  };

  window.__ATLAS_CONNECTORS = window.__ATLAS_CONNECTORS || [];
  window.__ATLAS_CONNECTORS.push(LoopConnector);
})();
