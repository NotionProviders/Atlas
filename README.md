# Notion Workspace Atlas

An interactive map of a Notion workspace, built with Laravel. Zoom from the whole workspace down through domains, pages, and blocks.

**Live:** [atlas.notionproviders.com](https://atlas.notionproviders.com)

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite     # create the local SQLite file
php artisan migrate                # set up the tables
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000) for the public atlas. The
private mapping tool lives at [/console](http://127.0.0.1:8000/console) — set
`CONSOLE_PASSWORD` in `.env` first, then log in.

The app reads its database from `database/database.sqlite` by default, so the
steps above work as-is on any machine — no path editing required.

On Windows if `php` is not on PATH: `C:\php\php.exe artisan serve` (and
`type nul > database\database.sqlite` instead of `touch`).

## Production deployment (Coolify)

See **[COOLIFY.md](COOLIFY.md)** for the full deployment guide.

Summary:

- Build with the included **Dockerfile**
- Container port: **3000**
- **No Coolify env vars needed** — see [COOLIFY.md](COOLIFY.md)

## Public atlas vs. the private console

Atlas has two faces:

- **`/` — the public Workspace Atlas.** The conceptual map (what visitors see
  at atlas.notionproviders.com). Only public maps render here; real crawled
  workspaces are never reachable from the public side.
- **`/console` — the private backend.** The workspace mapping tool. Log in with
  `CONSOLE_PASSWORD`, then map workspaces and explore the real, crawled maps
  (e.g. Formosa). Crawled maps are private and only viewable here.

Set `CONSOLE_PASSWORD` in `.env` to enable login (blank = console locked).

## Mapping a real Notion workspace (the Intake Hub)

Log in at `/console` to reach the **Intake Hub**. The top "Intake coverage"
checklist shows every way a workspace can enter Atlas and whether it's wired
up; the "Add a workspace" card holds the three working methods as tabs.

However a workspace comes in, the map is the same shape — **teamspaces → pages
(recursively) → databases → each database page → its subpages and nested
databases**, pages first then databases at every level.

### 1. Upload a Notion export — most complete, fully offline ✅

In Notion: `•••` → **Export** → **Markdown & CSV**, include subpages &
databases. Upload the resulting `.zip` on the **Upload export** tab. Atlas reads
the page tree straight off disk, so this covers **every teamspace** — including
ones the API and MCP can't enumerate — and no API keys leave your machine.

### 2. Crawl via the Notion API — live, but partial ⚠️

Paste a full-access integration token (or set `NOTION_API_KEY`) on the **API
crawl** tab. The crawl only sees pages shared with the integration, and the
REST API **can't list teamspaces**, so seed the roots (see method 3). For large
fully-recursive runs the CLI is sturdier:

```bash
# .env: NOTION_API_KEY=ntn_...
php artisan atlas:ingest --name="Formosa" --teamspaces=roots.json -v

# or auto-discover every top-level page/database the token can see
php artisan atlas:ingest --name="My Workspace" --discover -v
```

### 3. Seed teamspaces via the MCP — bridges the API gap

The REST API can't enumerate teamspaces. In a Notion MCP client run
`get-teams`, copy the JSON, and paste it into the **API crawl** tab's seed box
(or click **load discovered** if the workspace's teamspaces are already in
`resources/data/seeds/teamspaces.json`). Note: `get-teams` returns teamspace
IDs only — their child pages still aren't cleanly listable by the API or MCP,
which is why method 1 (export upload) is the most complete.

> **OAuth** is on the checklist as *planned* — a one-click authorize flow that
> would scan everything you can see without pasting keys. It needs a Notion
> OAuth app (client id/secret + redirect) before it can be wired up.

Crawled and uploaded maps are stored privately; `CONSOLE_DEFAULT_MAP` chooses
which one the console opens first. `ATLAS_PUBLIC_MAP` chooses the public map
shown at `/`.

## Migrating off another tool (Loop → Notion)

Some tools have no usable API to export from — Microsoft Loop is the motivating
case. The **Migrations** module (console → **Migrations**) drives a companion
**browser extension** that scrapes a workspace from *your own logged-in tab* and
streams it to Atlas, which turns it into a Notion-ready markdown export.

- Nothing runs server-side against the source — your Microsoft session never
  leaves your browser, so this works fine on the hosted Coolify deployment.
- Pair once: the console mints a code, the extension trades it for a token.
- The pipeline (markdown cleanup, Notion-readiness punch list, re-attach
  manifest, zip) is source-agnostic; a new tool is just a new extension adapter
  plus a `config/migration.php` entry.

See **[extension/README.md](extension/README.md)** to install and use it.

## Project structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/AtlasController.php` | Public atlas at `/` (public maps only) |
| `app/Http/Controllers/ConsoleController.php` | Private console: login, dashboard, map view |
| `app/Http/Controllers/WorkspacesController.php` | Intake actions: API crawl + export upload (console-only) |
| `app/Http/Middleware/ConsoleAuth.php` | Session login gate for `/console` |
| `app/Console/Commands/AtlasIngest.php` | `atlas:ingest` recursive crawl command |
| `app/Services/Notion/WorkspaceCrawler.php` | Recursive REST crawler (teamspaces → pages → databases) |
| `app/Services/Notion/ExportMapBuilder.php` | Builds a map from an uploaded Notion export (zip / md / csv / html) |
| `app/Services/Notion/NotionClient.php` · `WorkspaceMapRepository.php` | Notion REST client · map storage |
| `app/Http/Controllers/MigrationsController.php` | Migrations module: pairing, start, monitor, download (console-only) |
| `app/Http/Controllers/Api/*` · `routes/api.php` | Extension-facing token API (pair, create, stream nodes, complete) |
| `app/Services/Migration/*` | Source-agnostic ingest: cleanup, punch-list report, zip, storage |
| `extension/` | The browser extension — Loop connector + general-purpose runner (MV3) |
| `config/atlas.php` · `config/notion.php` | Page metadata · Notion token + crawl limits |
| `resources/data/atlas.json` | Built-in conceptual map (`?w=concept`) |
| `resources/data/workspaces/*.json` | Seeded real-workspace maps (`formosa.json` = live teamspaces) |
| `resources/data/seeds/teamspaces.json` | MCP-discovered teamspace roots for the "load discovered" button |
| `resources/views/atlas/` · `resources/views/workspaces/` | Map view · console |
| `public/css/atlas.css` · `public/css/console.css` | Map styles · console styles |
| `public/js/atlas.js` | Interactive map logic |
| `docker/` | Nginx, PHP, and entrypoint for production |
| `legacy/notion-workspace-atlas.html` | Original standalone file |

## Regenerating data from the legacy HTML

```bash
php scripts/extract-atlas-data.php
php scripts/extract-atlas-assets.php
```

## License

MIT
