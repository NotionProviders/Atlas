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

## Project structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/AtlasController.php` | Serves the atlas view |
| `config/atlas.php` | Page metadata + loads tree data |
| `resources/data/atlas.json` | Workspace tree, palette, legend |
| `resources/views/atlas/` | Blade templates |
| `public/css/atlas.css` | Styles |
| `public/js/atlas.js` | Interactive map logic |
| `docker/` | Nginx, PHP, and entrypoint for production |
| `routes/web-console.php` | Authenticated Atlas Console routes (`/console/*`) |
| `legacy/notion-workspace-atlas.html` | Original standalone file |

## Atlas Console (client workspace mapping)

Authenticated backend at **`/console`** for managing per-client workspace mapping projects (before / canonical / after snapshots).

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Sign in at [http://127.0.0.1:8000/console/login](http://127.0.0.1:8000/console/login) with the seeded admin user (`admin@notionproviders.com` / `password`).

Import snapshot JSON:

```bash
php artisan atlas:import-snapshot formosa-ev before resources/data/demo-formosa-before.json
```

Import canonical template (Notion HTML export directory or committed JSON):

```bash
php artisan atlas:import-canonical-template path/to/ExportBlock --replace
php artisan atlas:import-canonical-template resources/data/canonical-databases.json --replace
```

Production requires PostgreSQL — see [COOLIFY.md](COOLIFY.md).

## Regenerating data from the legacy HTML

```bash
php scripts/extract-atlas-data.php
php scripts/extract-atlas-assets.php
```

## License

MIT
