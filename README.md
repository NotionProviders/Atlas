# Notion Workspace Atlas

An interactive map of a Notion workspace, built with Laravel. Zoom from the whole workspace down through domains, pages, and blocks.

**Live:** [atlas.notionproviders.com](https://atlas.notionproviders.com)

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

On Windows if `php` is not on PATH: `C:\php\php.exe artisan serve`

## Production deployment (Coolify)

See **[COOLIFY.md](COOLIFY.md)** for the full deployment guide.

Summary:

- Build with the included **Dockerfile**
- Container port: **3000**
- **No Coolify env vars needed** — see [COOLIFY.md](COOLIFY.md)

## Mapping a real Notion workspace

Atlas can ingest a live workspace and render it as the map. The crawl walks
**teamspaces → pages (recursively) → databases → each database page → its
subpages and nested databases**, all the way down — pages first, then
databases, at every level.

Because the Notion REST API can't list teamspaces, seed the teamspace roots
once (e.g. from a Notion MCP `get-teams` call), then let the API do the deep
recursive crawl with a full-access integration token.

**From the browser:** open `/workspaces`, paste your token and teamspace roots
(or pick auto-discover), and submit. The new map opens automatically and is
selectable from the switcher in the map's top-left.

**From the CLI** (sturdier for large, fully-recursive crawls):

```bash
# .env: NOTION_API_KEY=ntn_...
php artisan atlas:ingest --name="Formosa EV HQ" \
  --teamspaces='[{"id":"fbbe1391-befa-44f2-aa7e-cc72c2d2d3c8","name":"Formosa EV HQ","type":"page"}]' -v

# or auto-discover every top-level page/database the token can see
php artisan atlas:ingest --name="My Workspace" --discover -v
```

Maps are stored per workspace; `ATLAS_DEFAULT_MAP` chooses the one shown at `/`.
The original conceptual atlas remains available at `/?w=concept`.

## Project structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/AtlasController.php` | Serves the atlas view (multi-workspace) |
| `app/Http/Controllers/WorkspacesController.php` | Intake/management console (`/workspaces`) |
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
