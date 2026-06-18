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

## Mapping a real Notion workspace

The crawl walks **teamspaces → pages (recursively) → databases → each database
page → its subpages and nested databases**, all the way down — pages first,
then databases, at every level.

Because the Notion REST API can't list teamspaces, seed the teamspace roots
once (e.g. from a Notion MCP `get-teams` call), then let the API do the deep
recursive crawl with a full-access integration token.

**From the browser:** log in at `/console`, then in **Add a workspace** paste
your token and teamspace roots (or pick auto-discover) and submit. The new map
opens automatically and is selectable from the switcher in the map's top-left.

**From the CLI** (sturdier for large, fully-recursive crawls):

```bash
# .env: NOTION_API_KEY=ntn_...
php artisan atlas:ingest --name="Formosa EV HQ" \
  --teamspaces='[{"id":"fbbe1391-befa-44f2-aa7e-cc72c2d2d3c8","name":"Formosa EV HQ","type":"page"}]' -v

# or auto-discover every top-level page/database the token can see
php artisan atlas:ingest --name="My Workspace" --discover -v
```

Crawled maps are stored privately; `CONSOLE_DEFAULT_MAP` chooses which one the
console opens first. `ATLAS_PUBLIC_MAP` chooses the public map shown at `/`.

## Project structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/AtlasController.php` | Public atlas at `/` (public maps only) |
| `app/Http/Controllers/ConsoleController.php` | Private console: login, dashboard, map view |
| `app/Http/Controllers/WorkspacesController.php` | Crawl intake actions (console-only) |
| `app/Http/Middleware/ConsoleAuth.php` | Session login gate for `/console` |
| `app/Console/Commands/AtlasIngest.php` | `atlas:ingest` recursive crawl command |
| `app/Services/Notion/` | Notion REST client, recursive crawler, map storage |
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
