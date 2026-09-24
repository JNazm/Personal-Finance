# Self-Hosting on Your Own Hardware (ZBook Fury)

This document describes how to run this app on your own machine, exposed at
**`https://nazmifinance.online/personal-finance`** (a path prefix, not the
domain root), with Docker Compose as the single on/off switch. The domain
root (`https://nazmifinance.online/`) serves a small landing page that lets
you pick between this app and [WhisperDesk](../../whisperdesk) (a separate
stack, at `/whisperdesk`) — see [section 6](#6-whisperdesk-at-domain-root--whisperdesk).

Files involved:
- [docker-compose.yml](../docker-compose.yml) — app (Laravel/Nginx/PHP-FPM), Postgres, Caddy reverse proxy.
- [docker/Caddyfile](../docker/Caddyfile) — reverse proxy; strips the `/personal-finance` prefix before forwarding to the app container, serves the landing page at `/`, and proxies `/whisperdesk` to the WhisperDesk stack. Serves plain HTTP by default (`http://{$DOMAIN}`) because Cloudflare Tunnel (section 1b) terminates HTTPS at Cloudflare's edge — if you use router port-forwarding instead (section 1), change the Caddyfile's site address back to a bare `{$DOMAIN}` so Caddy manages its own Let's Encrypt certificate.
- [docker/landing/index.html](../docker/landing/index.html) — the domain-root landing page (choice of Personal Finance / WhisperDesk).
- [app/Providers/AppServiceProvider.php](../app/Providers/AppServiceProvider.php) — forces Laravel's generated URLs (routes, redirects, assets) to include the `/personal-finance` prefix via `URL::forceRootUrl()`, since the app itself sees stripped, root-relative requests.
- `.env.docker` — your real secrets/config (copy from [.env.docker.example](../.env.docker.example), never commit it).

## 1. One-time setup

1. **Buy/confirm the domain** on Namecheap (`nazmifinance.online`).
2. **Point DNS at your home network:**
   - If your ISP gives you a static IP: add an `A` record for `nazmifinance.online` (and `www`) to that IP in the Namecheap DNS panel.
   - If your IP changes (most home connections): enable **Namecheap Dynamic DNS** for the domain, then run a DDNS client on the ZBook (or your router, if it supports Namecheap DDNS) that updates the record whenever your IP changes.
3. **Forward ports 80 and 443** on your router to the ZBook's local IP. Some ISPs block inbound 80/443 on residential plans, or place your connection behind **Carrier-Grade NAT (CGNAT)** — common on Malaysian home fibre — where port forwarding cannot work at all because your router never receives a real public IP. Verify with `https://canyouseeme.org` (check ports 80 and 443) before relying on this. **If you're behind CGNAT, skip to section 1b below instead.**
4. **Install Docker Desktop** (Windows/WSL2 backend) on the ZBook.
5. **Create the shared "edge" network** used to connect this stack's Caddy
   to the separate WhisperDesk stack (see [section 6](#6-whisperdesk-at-domain-root--whisperdesk)):
   ```powershell
   docker network create edge
   ```
6. Copy the env template and fill in real values:
   ```powershell
   Copy-Item .env.docker.example .env.docker
   ```
   The template already targets `nazmifinance.online/personal-finance`; at minimum change:
   - `DB_PASSWORD` → a strong password
7. Generate an `APP_KEY` and paste it into `.env.docker`:
   ```powershell
   docker compose --env-file .env.docker run --rm app php artisan key:generate --show
   ```

> All `docker compose` commands below need `--env-file .env.docker` because the
> filename isn't Compose's default (`.env`). Alternatively, `Copy-Item .env.docker .env`
> after editing it, then omit the flag everywhere — just make sure `.env` (already
> git-ignored) isn't confused with the app's local-dev `.env`.

## 1b. Alternative: Cloudflare Tunnel (use this if behind CGNAT, or to skip router config entirely)

If your WAN IP shown in the router's admin panel doesn't match your public IP
from a site like whatismyipaddress.com, you're behind CGNAT and port forwarding
is not possible — the ISP is sharing that public IP across many customers and
never routes it directly to your router. A Cloudflare Tunnel avoids this
entirely: `cloudflared` makes an *outbound* connection from the ZBook to
Cloudflare, so no inbound ports need to be open or forwarded at all.

1. Sign up for a free Cloudflare account, add `nazmifinance.online` as a site,
   and update your **nameservers at Namecheap** to the two Cloudflare
   nameservers it gives you (Namecheap → Domain List → Manage → Nameservers).
   This can take a few hours to propagate.
2. In the Cloudflare dashboard, go to **Zero Trust → Networks → Tunnels**,
   create a tunnel, and add a **Public Hostname** that forwards everything to
   Caddy, which handles the path-based routing internally (`/` → landing page,
   `/personal-finance` → this app, `/whisperdesk` → WhisperDesk — see
   [docker/Caddyfile](../docker/Caddyfile)):
   - Subdomain: (leave blank for root)
   - Domain: `nazmifinance.online`
   - Path: (leave blank)
   - Service: `HTTP` → `caddy:80`
3. Copy the **tunnel token** shown during setup and add it to `.env.docker`:
   ```
   CLOUDFLARE_TUNNEL_TOKEN=<paste token here>
   ```
4. Start the stack with the `tunnel` profile instead of exposing Caddy's ports:
   ```powershell
   docker compose --env-file .env.docker --profile tunnel up -d
   ```
   The `cloudflared` service (see [docker-compose.yml](../docker-compose.yml))
   connects outbound to Cloudflare and forwards traffic to the `caddy`
   service over the internal Docker network, so Caddy's routing/TLS
   termination is used the same way as the port-forwarding path below.
5. Cloudflare issues and manages the HTTPS certificate for you automatically;
   no Let's Encrypt/Caddy TLS setup is needed on this path.

Skip section 3 (router port forwarding) entirely if you use this approach.

## 2. Activate hosting

```powershell
docker compose --env-file .env.docker up -d --build
```

This builds the app image, starts Postgres, runs migrations (via the existing
[entrypoint.sh](../docker/entrypoint.sh)), and starts Caddy.

If you're on **Cloudflare Tunnel** (section 1b, the default Caddyfile scheme),
Cloudflare handles HTTPS for you — nothing else to do here. If you're using
**router port-forwarding** (section 1) instead, first edit
[docker/Caddyfile](../docker/Caddyfile) and change `http://{$DOMAIN}` back to
a bare `{$DOMAIN}`, so Caddy automatically requests/renews its own Let's
Encrypt certificate for `DOMAIN` on first request.

Visit **`https://nazmifinance.online/personal-finance`** once it's up.

Check status:
```powershell
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs -f app
```

## 3. Deactivate hosting

```powershell
docker compose --env-file .env.docker down
```

Data (database, uploaded files, TLS certs) persists in named Docker volumes
(`db_data`, `app_storage`, `caddy_data`) and survives `down`/`up` cycles. Use
`docker compose --env-file .env.docker down -v` only if you intentionally want
to wipe all data.

## 4. Updating after a `git pull`

```powershell
docker compose --env-file .env.docker up -d --build
```

Rebuilds the `app` image with the latest code and restarts it in place; the
database and volumes are untouched.

## 5. Backups

Periodically back up the Postgres volume, e.g.:
```powershell
docker compose --env-file .env.docker exec db pg_dump -U personal_finance personal_finance > backup.sql
```
Store `backup.sql` off-site (cloud storage, external drive) since this is the
only copy of your financial data if the ZBook fails.

## 6. WhisperDesk at domain root / `/whisperdesk`

The domain root (`https://nazmifinance.online/`) and `/whisperdesk` are
handled by [docker/Caddyfile](../docker/Caddyfile), which:
- serves [docker/landing/index.html](../docker/landing/index.html) (a static
  page with buttons to Personal Finance and WhisperDesk) at `/`;
- reverse-proxies `/whisperdesk` to a **separate** stack defined in
  [whisperdesk/docker-compose.yml](../../whisperdesk/docker-compose.yml)
  (its own repo/folder — GPU-accelerated transcription + an Ollama container
  for AI meeting notes).

Both stacks talk to each other over a shared external Docker network. Create
it once (already covered in step 1 above if you followed it in order):
```powershell
docker network create edge
```

Then, from the `whisperdesk` folder:
```powershell
Copy-Item .env.example .env
# edit .env: set WHISPERDESK_USERNAME / WHISPERDESK_PASSWORD (required — this
# is reachable from the public internet at nazmifinance.online/whisperdesk)
docker compose up -d --build
docker compose exec ollama ollama pull qwen2.5:7b-instruct
```

Restart (or reload) Caddy afterwards so it can resolve the `whisperdesk`
service name on the `edge` network:
```powershell
docker compose --env-file .env.docker restart caddy
```

Visit `https://nazmifinance.online/whisperdesk` and log in with the
credentials you set above.

