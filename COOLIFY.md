# Coolify Deployment Guide

Deploy **Notion Workspace · Orbital Atlas** to Coolify using the included Dockerfile. The container listens on **port 3000** (Coolify's default reverse-proxy target).

## Quick checklist

1. Create a GitHub repo and push this project.
2. In Coolify: **+ New → Public/Private Repository** → select the repo.
3. Build pack: **Dockerfile** (not Nixpacks).
4. Port: **3000**
5. Set environment variables (see below).
6. Deploy.

## Required environment variables

Set these in Coolify's **Environment Variables** UI (runtime — not build-time unless noted):

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_NAME` | `Notion Workspace Atlas` | |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `APP_KEY` | `base64:…` | Generate locally: `php artisan key:generate --show` |
| `APP_URL` | `https://your-domain.com` | Must match your Coolify domain |
| `LOG_CHANNEL` | `stderr` | Logs appear in Coolify UI |
| `LOG_LEVEL` | `warning` | |
| `DB_CONNECTION` | `sqlite` | Default; no separate DB needed |
| `DB_DATABASE` | `/var/www/html/database/database.sqlite` | Auto-created on startup |
| `SESSION_DRIVER` | `cookie` | No DB sessions required |
| `CACHE_STORE` | `file` | Ephemeral; fine for this app |
| `QUEUE_CONNECTION` | `sync` | No queue worker needed |

## Optional: PostgreSQL

This app does not require PostgreSQL (it serves static JSON tree data). If your org standard requires a Postgres resource:

| Variable | Value |
|----------|-------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Internal hostname from Coolify Postgres resource |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Your database name |
| `DB_USERNAME` | `postgres` |
| `DB_PASSWORD` | From Coolify Postgres resource |

Migrations run automatically on container start (`php artisan migrate --force`).

## Port configuration

- Nginx inside the container binds to **3000** (`docker/nginx.conf`).
- Coolify's Traefik reverse-proxies to port **3000** by default.
- Do **not** change the port without updating Coolify's port setting.

## Build notes

- **No npm build step** — CSS/JS live in `public/css/` and `public/js/` as pre-built static assets.
- **No interactive prompts** — entrypoint runs migrations and caches non-interactively.
- **Config cached at runtime** — env vars from Coolify are picked up on each deploy start.
- **Health check** — `GET /up` (Laravel built-in).

## Static files

All atlas assets are served by Nginx from `public/`:

- `/css/atlas.css`
- `/js/atlas.js`

No Vite build or Cloudflare R2 required for this app.

## Persistence

- **SQLite** (default): stored in the container filesystem; recreated on deploy. Acceptable because this app has no user data.
- **Logs**: written to stderr only (`LOG_CHANNEL=stderr`).
- **Uploads**: not used by this app.

If you later add user uploads, mount a Coolify persistent volume or use S3/R2.

## SSL

Coolify handles SSL via Let's Encrypt when you assign a domain. Set `APP_URL` to the same `https://` URL.

## Local Docker test

```bash
docker build -t atlas .
docker run --rm -p 3000:3000 \
  -e APP_KEY="base64:YOUR_KEY_HERE" \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_URL=http://localhost:3000 \
  atlas
```

Open [http://localhost:3000](http://localhost:3000).

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| 500 on first load | Set `APP_KEY` in Coolify env vars |
| Blank page | Check container logs in Coolify; verify port is **3000** |
| Build fails | Ensure Dockerfile build pack is selected (not Nixpacks) |
| Config changes ignored | Redeploy — entrypoint runs `config:cache` on each start |
