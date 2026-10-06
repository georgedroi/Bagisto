# Phase 1: Bagisto on this Windows computer

This phase installs and verifies the original Bagisto storefront and administrator dashboard. Design, custom product management, Media Bank, payment integrations and production deployment belong to later phases.

## Requirements checked on 6 October 2026

Bagisto **v2.4.12**, commit `805b67013134ebc53f1da1285f0aa6632f5dde62`, is the latest stable release found. The newer 2.5 releases are beta releases. The exact 2.4.12 `composer.json` accepts PHP `>=8.3 <8.5` and requires Laravel 12.

| Component | Required / chosen |
| --- | --- |
| Bagisto | Stable v2.4.12 |
| PHP | 8.3 or 8.4; this setup uses PHP 8.3 |
| Composer | Composer 2; use 2.5+ |
| Node.js | Node 22 for future frontend builds; included as an optional tools container |
| Database | MySQL 8.0 or MariaDB; this setup uses MariaDB 10.11 |
| Web server | Nginx with PHP-FPM |
| Git | Installed Git for Windows, local-development branch |
| PHP extensions | bcmath, calendar, ctype, curl, dom, fileinfo, filter, gd, hash, iconv, intl, json, libxml, mbstring, openssl, pcre, PDO, pdo_mysql, session, SimpleXML, tokenizer, xml, xmlreader, xmlwriter, zip, zlib |

Your computer already has Docker Desktop using its Linux engine and WSL2, Git, approximately 64 GB RAM and 376 GB free on D:. Windows Node is v25.9.0; frontend commands below deliberately use Node 22 inside Docker. PHP and Composer were not on the Windows PATH; the app container supplies both.

Official references: [stable release](https://github.com/bagisto/bagisto/releases/tag/v2.4.12), [release-specific requirements](https://github.com/bagisto/bagisto/blob/v2.4.12/composer.json), [Bagisto requirements](https://devdocs.bagisto.com/getting-started/before-you-start), [installation](https://devdocs.bagisto.com/getting-started/installation), [Docker for Windows](https://docs.docker.com/desktop/setup/install/windows-install/).

The online development documentation also describes the newer 2.5 branch. Use the pinned release's own requirements when checking this installation.

## Where to run commands

Open **PowerShell**. Keep Docker Desktop running. All commands after the source download run here:

```powershell
Set-Location -LiteralPath 'D:\CHATGPT PROJECTS\Bagisto'
```

Docker Compose runs the services as Linux containers. The source code stays in this Windows folder. The MariaDB database and Composer vendor dependencies use Docker named volumes for persistence and speed. Application uploads remain under this project's `storage/app/public` folder.

Port 8000 is occupied by another application, so Bagisto uses port **8088**, bound only to this computer. The database has no published host port.

## Files added for local development

- `compose.local.yaml`: separate local application, web, database and optional Node services.
- `docker/local/Dockerfile`: PHP 8.3, required extensions and Composer 2.
- `docker/local/php.ini`: local PHP memory/upload/time limits and timezone.
- `docker/local/nginx.conf`: serve only `public/`, route Laravel requests and prevent other PHP files from being executed through Nginx.
- `.env.local.example`: a nonsecret template documenting local values.
- `scripts/local/Initialize-Environment.ps1`: create random credentials for a fresh setup.
- `scripts/local/configure-local-admin.php`: replace the upstream installer's default administrator credentials immediately after installation.
- `LOCAL-DEVELOPMENT.md`: this guide.

`.gitignore` is appended with exclusions for `.env` variants, local credentials, keys, backups and browser test artifacts. Bagisto's core PHP, Blade, themes and dependency manifests are preserved. Complete configuration and helper code is in the files listed above.

## Initial installation, in order

The source download performed for this installation was:

```powershell
git clone --depth 1 --branch v2.4.12 https://github.com/bagisto/bagisto.git 'D:\CHATGPT PROJECTS\Bagisto'
Set-Location -LiteralPath 'D:\CHATGPT PROJECTS\Bagisto'
git switch -c local-development
```

The local configuration files listed above were then added. On a fresh checkout containing those files, generate credentials once:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\local\Initialize-Environment.ps1
git check-ignore .env .local/admin-credentials.json
```

The execution-policy option applies to that invocation only. The initializer refuses to overwrite an existing `.env`. **On this installed copy, retain the existing `.env` and skip initialization.** The application URL is `http://localhost:8088`, currency is MYR, timezone is Asia/Kuala_Lumpur, and development email is written to the local Laravel log instead of sent to customers.

Check the configuration without printing passwords, then build PHP and start the dedicated database:

```powershell
docker compose -f compose.local.yaml config --quiet
docker compose -f compose.local.yaml up -d --build database app
docker compose -f compose.local.yaml ps
docker compose -f compose.local.yaml exec -T app php -v
docker compose -f compose.local.yaml exec -T app php -m
docker compose -f compose.local.yaml exec -T app composer --version
docker compose -f compose.local.yaml run --rm --no-deps node node --version
docker compose -f compose.local.yaml exec -T database mariadb --version
```

The first build downloads images and compiles PHP extensions. Later starts reuse the built image. `database` should report healthy, and PHP must be 8.3.x with the extensions in the table.

Install the dependencies recorded by the release, then verify them:

```powershell
docker compose -f compose.local.yaml exec -T app composer install --no-interaction --prefer-dist --no-progress
docker compose -f compose.local.yaml exec -T app composer check-platform-reqs
```

Do not use `--ignore-platform-reqs` or `composer update` to get around a missing requirement. The release includes built storefront, admin and installer assets, so rebuilding frontend assets is unnecessary for this initial installation.

**The next installer command wipes its configured database. Run it once only against this new, empty `bagisto_local` database. Do not rerun it on an installed store.**

```powershell
docker compose -f compose.local.yaml exec -T app php artisan bagisto:install --no-interaction
docker compose -f compose.local.yaml exec -T app php scripts/local/configure-local-admin.php
docker compose -f compose.local.yaml exec -T app php artisan optimize:clear
docker compose -f compose.local.yaml exec -T app php artisan migrate:status
docker compose -f compose.local.yaml up -d web
```

The Bagisto installer creates tables, baseline store data, an application key and the storage link. It briefly creates its upstream default administrator. The helper replaces that account with the random local credentials before the web service is started. No demo products are requested in Phase 1.

## Check the storefront and dashboard

1. Open `http://localhost:8088`. The original Bagisto storefront must render with its header, banners and footer, without a Laravel exception. An empty catalog is expected in Phase 1.
2. Open `http://localhost:8088/admin/login`.
3. Read `.local\admin-credentials.json` privately on your computer. Use its email and generated password; keep this file out of Git and messages.
4. Sign in. The dashboard at `/admin/dashboard` must load with Catalog, Sales, Customers, Settings and Configuration navigation.
5. Refresh the dashboard to confirm the session persists. Sign out and confirm protected pages return to login.
6. Check the storefront on a mobile viewport and check for failed local CSS, JS and image requests.

Quick HTTP checks from PowerShell:

```powershell
(Invoke-WebRequest -UseBasicParsing 'http://localhost:8088').StatusCode
(Invoke-WebRequest -UseBasicParsing 'http://localhost:8088/admin/login').StatusCode
docker compose -f compose.local.yaml ps
docker compose -f compose.local.yaml logs --tail 50 web app
```

Both public requests should return 200. An admin login page alone does not prove the dashboard works; sign in and check the dashboard too.

## Start, stop and inspect later

Run all of these in `D:\CHATGPT PROJECTS\Bagisto`:

```powershell
# Start the installed store after restarting Windows / Docker Desktop.
docker compose -f compose.local.yaml up -d

# Stop this store and retain its database.
docker compose -f compose.local.yaml stop

# Restart just the application and web server.
docker compose -f compose.local.yaml restart app web

# Inspect service status and recent logs.
docker compose -f compose.local.yaml ps
docker compose -f compose.local.yaml logs --tail 50 app web database

# Clear Laravel caches after changing local configuration.
docker compose -f compose.local.yaml exec -T app php artisan optimize:clear

# Run Composer or Artisan without installing PHP on Windows.
docker compose -f compose.local.yaml exec -T app composer check-platform-reqs
docker compose -f compose.local.yaml exec -T app php artisan about
```

Do not run `docker compose down -v` or delete the database volume: that removes stored data. Start/stop commands do not reinstall Bagisto.

## Git

The original v2.4.12 commit remains in history. Local setup is recorded on `local-development` in a separate initial-installation commit. The upstream remote points to Bagisto; no changes are pushed there. Set a remote for your own repository when you are ready.

```powershell
git status --short
git log -2 --oneline
git check-ignore .env .local/admin-credentials.json
git ls-files .env .local/admin-credentials.json
```

The ignored-file check must identify both secret files; the tracked-file check must produce no output.

## Troubleshooting

- If Docker reports that its Linux engine is missing, open Docker Desktop, wait for the engine to be running and repeat the start command. Do not reset Docker or delete volumes.
- If an image is broken, check that `.env` has `APP_URL=http://localhost:8088` and `php artisan storage:link` has succeeded.
- If the page shows a database error, check `docker compose -f compose.local.yaml ps` and the database log. Use `DB_HOST=database` in the container, rather than localhost.
- If PHP reports missing extensions, rebuild the local app image and run `composer check-platform-reqs` again.
- If port 8088 later becomes occupied, change both `APP_PORT` and `APP_URL` in `.env`, clear Laravel caches and recreate the web service with `docker compose -f compose.local.yaml up -d web`.

This is a localhost development setup. Production TLS, queues, backups, hardening and VPS migration are handled in their later phases after the store's features are stable.

## Verified installation results

Installed versions: Bagisto 2.4.12, Laravel 12.69.2, PHP 8.3.35, Composer 2.10.3, MariaDB 10.11.19 and Node 22.23.3.

- Composer's platform checks passed for all locked dependencies.
- All 199 database migrations completed.
- The local administrator helper passed PHP syntax validation and Pint.
- The environment initializer generated distinct random credentials and refused to overwrite an existing `.env` in an isolated test folder.
- All 14 upstream media filename tests passed with 25 assertions using an isolated in-memory SQLite connection.
- The upstream translation checker passed all 315 locale checks.
- Nginx configuration validation passed.
- Nine browser workflow checks passed: desktop storefront; tablet/mobile storefront; dashboard authentication; successful admin login; session persistence; product administration; mobile admin rendering; logout protection; no JavaScript errors or failed local assets.
- The Bagisto services stopped and restarted successfully. The database retained the administrator, and the storefront and admin login returned HTTP 200 afterward.

Browser verification used installed Playwright with headless Chrome because the Browser plugin was unavailable. Screenshots and the browser result report are saved under `local-artifacts/phase1/`, which is excluded from Git. Core source files and the release's Composer lock file retain their original content.
