# Coolify Deployment Guide

Deploy **Notion Workspace Atlas** to Coolify using the included Dockerfile. Production: **[atlas.notionproviders.com](https://atlas.notionproviders.com)**. The container listens on **port 3000** (Coolify's default reverse-proxy target).

## Quick checklist

1. Create a GitHub repo and push this project.
2. In Coolify: **+ New → Public/Private Repository** → select the repo.
3. Build pack: **Dockerfile** (not Nixpacks).
4. Port: **3000**
5. Deploy — **no environment variables required** (see below).

## Environment variables

**None required.** The Docker entrypoint ships a production `.env` from `.env.docker`, auto-generates `APP_KEY` on first boot, and the app detects your public URL from the incoming request (via Coolify’s reverse proxy).

Leave Coolify’s environment section empty unless you need to override something.

### Optional overrides

| Variable | When to set |
|----------|-------------|
| `APP_DEBUG` | `true` temporarily to debug a 500 (turn off after) |
| `DB_CONNECTION` | `pgsql` only if you attach a Postgres resource |
| `DB_HOST`, `DB_PASSWORD`, etc. | Only with Postgres |

### Why env looked “required” before

Laravel normally expects `APP_KEY` at minimum. Without it, `/up` can still return 200 (health check only boots Laravel), but `/` hits cookie/session middleware and **500s**. That was likely your error — not missing Postgres, mail, or other vars.

## Atlas Console + PostgreSQL (recommended for production)

The public atlas at `/` still works without a database. **Atlas Console** (`/console`) stores client workspace mapping projects and requires a persistent database.

1. In Coolify, add a **PostgreSQL** resource and link it to the Atlas service.
2. Set these environment variables on the Atlas service:

| Variable | Value |
|----------|-------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Internal hostname from Coolify Postgres resource |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | e.g. `atlas_console` |
| `DB_USERNAME` | `postgres` |
| `DB_PASSWORD` | From Coolify Postgres resource |
| `SESSION_DRIVER` | `database` |

3. Redeploy. Migrations run on container start.
4. Seed an admin user (once): `php artisan db:seed --force` inside the container, or run locally against the same database.

Default seeded credentials (change immediately in production):

- Email: `admin@notionproviders.com`
- Password: `password`

Console routes:

- `/console/login` — sign in
- `/console/projects` — client project dashboard

## Optional: PostgreSQL (legacy note)

If you only need the public read-only atlas and not Atlas Console, SQLite in the container is sufficient. For any Console usage, use PostgreSQL as above.

## Port configuration

- Nginx inside the container binds to **3000** (`docker/nginx.conf`).
- Coolify's Traefik reverse-proxies to port **3000** by default.
- Do **not** change the port without updating Coolify's port setting.

## Build notes

- **No npm build step** — CSS/JS live in `public/css/` and `public/js/` as pre-built static assets.
- **No interactive prompts** — entrypoint runs migrations and caches non-interactively.
- **Config cached at runtime** — env vars from Coolify are picked up on each deploy start.
- **Health check** — `GET /up` (Laravel built-in).

## Faster deploys (same runtime, shorter builds)

A ~15 minute deploy is almost always **Docker image build time**, not the app starting. This stack rebuilds the image on every push. The slow steps are:

1. **PHP extensions** — compiling `intl`, `mbstring`, etc. from source on Alpine (often 5–8 min on a small VPS).
2. **`composer install`** — downloading Laravel and dependencies on a cold cache (often 2–4 min).
3. **No build cache** — if Coolify rebuilds every layer from scratch, nothing is reused even when only `atlas.json` changed.

The Dockerfile is tuned for speed without changing behavior:

- **Pre-built PHP extensions** via `install-php-extensions` (same extensions, no source compile).
- **BuildKit composer cache** so dependency downloads persist between builds on the same builder.
- **Layer order** — `composer.json` / extension install run before app source so asset-only commits skip the heavy steps.

### Coolify settings to check

| Setting | Recommendation |
|---------|------------------|
| Build pack | **Dockerfile** (not Nixpacks — Nixpacks adds npm/composer detection overhead this app does not need) |
| Build server | Use a **dedicated builder** with persistent disk if available |
| BuildKit | Leave enabled (Coolify default on recent versions) |
| Deploy trigger | Push only when you need a redeploy — every push rebuilds the image |

### Expected build times (rough)

| Scenario | Before | After (typical) |
|----------|--------|-----------------|
| Cold build (first deploy, small VPS) | 12–18 min | 3–6 min |
| Code-only change (`atlas.json`, CSS, JS) | 12–18 min | 30–90 sec |
| `composer.lock` change | 12–18 min | 2–4 min |

Container **startup** (migrate + config/route/view cache) is usually under 30 seconds — not minutes.

### Local timing check

```bash
docker build -t atlas .
```

Run twice; the second build should be noticeably faster if BuildKit cache is working.

## Static files

All atlas assets are served by Nginx from `public/`:

- `/css/atlas.css`
- `/js/atlas.js`

No Vite build or Cloudflare R2 required for this app.

## Persistence

- **SQLite** (default): stored in the container filesystem; recreated on deploy. Fine for the public atlas only.
- **PostgreSQL** (recommended): required for Atlas Console client workspace data to survive redeploys.
- **Logs**: written to stderr only (`LOG_CHANNEL=stderr`).
- **Uploads**: JSON imports are processed in-memory; no persistent upload storage required.

## SSL

Assign **atlas.notionproviders.com** in Coolify; Let's Encrypt handles the certificate. No `APP_URL` env var needed — the app reads the public URL from each request.

## Local Docker test

```bash
docker build -t atlas .
docker run --rm -p 3000:3000 atlas
```

Open [http://localhost:3000](http://localhost:3000). No env vars required — same as Coolify.

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| 500 on `/` but **Healthy** in Coolify | Usually missing `APP_KEY` on older deploys. Redeploy latest — entrypoint auto-generates it. Check `/atlas-config.js` loads. |
| 500 on first load (old deploys) | Redeploy latest image; no manual env needed |
| Blank page | Check container logs in Coolify; verify port is **3000** |
| Build fails | Ensure Dockerfile build pack is selected (not Nixpacks) |
| Config changes ignored | Redeploy — entrypoint runs `config:cache` on each start |
