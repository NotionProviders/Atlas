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

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). You'll land on the Formosa
workspace map; visit [/workspaces](http://127.0.0.1:8000/workspaces) for the
intake console.

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

## Importing a real Notion workspace

A complete footprint never comes from one export. Atlas treats intake as a
**checklist of many sources** that reconcile against each other:

| Source | Role |
|--------|------|
| **AdminContentSearch (Active)** CSV | Canonical page seed |
| **AdminContentSearch (Retained)** CSV | Admin-deleted / legal-hold pages |
| **Audit Log** CSV | Behavioral truth (who/what/when/IP) |
| **Content Analytics** CSV | Recency corroborator |
| **Members** CSV | People + permission groups |
| **Workspace export** ZIP | Page tree + archive state |
| **Live API scan** (paste token) | Current structure via REST |
| **Connect Notion** (OAuth) | One-click scan — setup required |

The admin CSVs are the canonical, complete sources; the live API scan can't see
admin-deleted pages or un-shared teamspaces. So intake is primarily **file
upload** with the API scan as a supplement.

**In the console:**

1. Log in at `/console` (`CONSOLE_PASSWORD`).
2. Create a workspace, then work down its **intake checklist** — upload each
   export or run the live API scan. A progress bar tracks coverage. Uploaded
   files are stored privately under `storage/app` and never web-served.
3. The **Guide** (`/console/guide`) explains each source and how to obtain it.

**Live API scan token handling:** pasted for the one request, never written to
disk. `Connect Notion` (OAuth) is cleaner but needs `NOTION_OAUTH_*` set.

**CLI** (sturdy for large recursive crawls — feeds the "Live API scan" source):

```bash
# .env: NOTION_API_KEY=ntn_...
php artisan atlas:ingest --name="Formosa EV HQ" \
  --teamspaces='[{"id":"fbbe1391-befa-44f2-aa7e-cc72c2d2d3c8","name":"Formosa EV HQ","type":"page"}]' -v

# or auto-discover every top-level page/database the token can see
php artisan atlas:ingest --name="My Workspace" --discover -v
```

The recursive crawl walks **teamspaces → pages → databases → each database page
→ nested subpages/databases**, pages first then databases at every level.
`ATLAS_PUBLIC_MAP` chooses the public map at `/`; `CONSOLE_DEFAULT_MAP` the one
the console opens first.

## Project structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/AtlasController.php` | Public atlas at `/` (public maps only) |
| `app/Http/Controllers/ConsoleController.php` | Private console: login, dashboard, map view |
| `app/Http/Controllers/IntakeController.php` | Intake checklist, uploads, API scan, guide |
| `app/Http/Middleware/ConsoleAuth.php` | Session login gate for `/console` |
| `app/Console/Commands/AtlasIngest.php` | `atlas:ingest` recursive crawl command |
| `app/Services/Intake/` | Intake manifest store + CSV inspector |
| `app/Services/Notion/` | Notion REST client, recursive crawler, map storage |
| `config/intake.php` | Source catalog driving the checklist + guide |
| `config/atlas.php` · `config/notion.php` | Page metadata · Notion token + crawl limits |
| `resources/data/atlas.json` | Built-in conceptual map (`?w=concept`) |
| `resources/data/workspaces/*.json` | Seeded real-workspace maps |
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
