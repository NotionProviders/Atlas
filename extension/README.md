# Atlas Migrator — browser extension

A general-purpose migration extension for Atlas. It runs **inside your own
logged-in browser tab** and scrapes a workspace out of a tool that has no usable
API — starting with **Microsoft Loop** — then streams it to the Atlas backend,
which turns it into a Notion-ready markdown export.

Because the scraping happens in your authenticated tab, there's no second
browser and no login to automate. The Atlas server never touches your Microsoft
session.

## Install (unpacked)

1. Open `chrome://extensions` (Chrome or Edge) and enable **Developer mode**.
2. **Load unpacked** → select this `extension/` folder.
3. In the Atlas console, open **Migrations → Generate pairing code**.
4. Click the extension icon, paste the **API URL** and **pairing code**, hit **Connect**.

## Use

1. Open a Microsoft Loop workspace in a tab and make sure it's loaded.
2. Click the extension icon → **Scrape this workspace** (or pick a run queued
   from the console).
3. Watch progress in the popup or in the console's live view. When it finishes,
   download the Notion export (`.zip`) from the console and import it into Notion
   via ••• → Import → Markdown & CSV.

## How it's built

```
manifest.json            MV3 manifest (content script on loop.cloud.microsoft)
src/
  content.js             the runner — drives discover → stream → complete in the tab
  connectors/
    loop.js              Loop adapter: sidebar walk + canvas read (DOM port of the scraper)
  lib/
    html-to-markdown.js  dependency-free HTML → Markdown converter
  core/
    api.js               Atlas API client (bearer token)
    storage.js           token + API URL persistence
  background.js          service worker — the single place the token lives + all fetches happen
popup/                   pairing + start + live progress UI
```

### Adding another source tool

The architecture is deliberately pluggable:

1. Add a connector file under `src/connectors/` implementing
   `{ key, label, matches(url), ready(), workspaceName(), discover(), extractPage(node) }`
   and push it onto `window.__ATLAS_CONNECTORS`.
2. Add its host to `manifest.json` (`content_scripts.matches` + `host_permissions`).
3. Add a matching entry to `config/migration.php` on the server (`sources`).

Nothing else changes — the runner, the streaming API, the markdown cleanup, the
report, and the console module are all source-agnostic.

## Security notes

- The token is stored only in `chrome.storage.local` and sent only to the API
  URL you paired with. Disconnect from the popup or revoke it from the console.
- `host_permissions` is scoped to the source tools you actually migrate — the
  extension can't read arbitrary sites.
- All Atlas API calls are bearer-token authenticated; no cookies are used.
