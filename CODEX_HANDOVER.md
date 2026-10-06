# CODEX HANDOVER — Bagisto E-commerce Project

**Project:** Bagisto E-commerce Store  
**Local path:** `D:\CHATGPT PROJECTS\Bagisto`  
**Platform:** Bagisto / Laravel  
**Base version:** Bagisto v2.4.12  
**Current repository state:** `v2.4.12-2-gb8491a4`  
**Branch/package state:** `dev-local-development`  
**Environment:** Windows + Docker Desktop + WSL2  
**Local URL:** `http://localhost:8088`  
**Admin URL:** `http://localhost:8088/admin/login`  
**Timezone:** `Asia/Kuala_Lumpur`  
**Project start:** `2026-10-06T11:35:00+08:00`

---

## 1. Read These Files First

Before changing anything, read:

1. `BAGISTO_PROJECT_AGENT_TRACKER.md`
2. `LOCAL-DEVELOPMENT.md`
3. `AGENTS.md`
4. `CLAUDE.md`
5. `.env` — do not print secrets
6. `compose.local.yaml`

Treat `BAGISTO_PROJECT_AGENT_TRACKER.md` as the project progress source of truth.

Update the tracker after every meaningful change, test, blocker, or decision.

---

## 2. Current Project Status

### Phase 0 — Planning & Architecture
**Status:** COMPLETE

Completed decisions include:

- Bagisto chosen over Medusa for this project.
- Local-first development selected.
- VPS deployment will happen later.
- Custom storefront required.
- Lazada-style admin/product editor required.
- Media Bank required.
- Strong SEO required.
- Privacy/discreet-shopping features required.
- Payment integration must remain modular.
- Security hardening required.
- Preferred VPS candidate researched separately; not needed for local development yet.

### Phase 1 — Local Environment & Installation
**Status:** ~96% COMPLETE

The Docker environment has already been installed and verified.

Remaining Phase 1 acceptance check:

- Open storefront in browser.
- Open admin login in browser.
- Confirm both render correctly.
- If both pass, mark Phase 1 100% complete.

Do NOT reinstall Bagisto.

---

## 3. Important Discovery

There are two possible local database paths, but only one is correct for this project.

### Correct project path: Docker

The repository already includes a complete Docker local-development stack.

Services:

- `app` — PHP/Laravel
- `database` — MariaDB 10.11
- `web` — Nginx
- optional Node tooling container

The persistent Docker database already contains the installed Bagisto schema and all migrations.

### Incorrect path for this project: WAMP MySQL

WAMP is installed locally and runs:

`C:\wamp64\bin\mysql\mysql8.4.7\bin\mysqld.exe`

on port 3306.

A temporary empty database named `bagisto_local` was created/tested in WAMP during troubleshooting, but it is NOT the project database and should not be used for Bagisto.

Do not initialize Bagisto against WAMP.

---

## 4. Correct `.env` Local Database Configuration

The working Docker-local `.env` uses:

```env
APP_URL=http://localhost:8088

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=bagisto_local
DB_USERNAME=bagisto_local
```

Do not expose or print `DB_PASSWORD`.

A backup exists:

`.env.backup-20261006`

The backup was verified to contain the correct Docker-local values.

---

## 5. Current Runtime Verification

The following were verified successfully on Windows:

- PHP 8.4.26 via Laravel Herd
- Composer 2.10.2
- Node.js v25.9.0
- npm 11.14.1
- Required PHP extensions present
- Composer dependencies restored
- `vendor/autoload.php` exists
- Laravel boots successfully

`php artisan about` on Windows reported:

- Application: Bagisto
- Laravel: 12.69.2
- PHP: 8.4.26
- Composer: 2.10.2
- Environment: local
- Debug: enabled
- URL: localhost:8088
- Timezone: Asia/Kuala_Lumpur
- Database driver: mysql
- Session driver: database
- Storage link: present

However, the authoritative local environment for this project is Docker.

---

## 6. Docker Status Already Verified

These commands were run successfully:

```powershell
docker compose -f compose.local.yaml ps
```

Verified services:

- `bagisto-local-app-1` — Up
- `bagisto-local-database-1` — Up / healthy
- `bagisto-local-web-1` — Up
- Nginx bound to `127.0.0.1:8088`

The database service is MariaDB 10.11.

---

## 7. Migration Status Already Verified

This command was run:

```powershell
docker compose -f compose.local.yaml exec -T app php artisan migrate:status
```

The installed migration history was present and migrations were marked:

`Ran`

The repository documentation records:

- all 199 migrations completed
- storefront browser checks passed previously
- admin browser checks passed previously
- admin session persistence passed
- product administration passed
- mobile admin rendering passed
- no JavaScript errors / failed local assets in previous verification

Do not run destructive migration commands.

---

## 8. VERY IMPORTANT — Commands NOT to Run

Do NOT run any of these unless explicitly instructed and the consequences are understood:

```powershell
composer create-project
composer update
php artisan migrate:fresh
php artisan db:wipe
php artisan bagisto:install
docker compose down -v
```

Reasons:

- `composer create-project` would overwrite/reinstall the existing repository.
- `composer update` would change the locked dependency set.
- `migrate:fresh` / `db:wipe` would destroy data.
- `bagisto:install` wipes the configured database and is intended for first installation only.
- `docker compose down -v` deletes persistent Docker volumes, including database data.

---

## 9. Git State

Repository working tree was checked with:

```powershell
git status --short
```

Result:

No output — working tree clean.

Exact repository identity:

```text
Bagisto base: v2.4.12
Current: v2.4.12-2-gb8491a4
```

Existing work is committed.

Do not reset the branch or overwrite local commits.

---

## 10. Next Immediate Task

Complete Phase 1 browser acceptance test.

### Step 1 — Ensure Docker is running

```powershell
cd "D:\CHATGPT PROJECTS\Bagisto"
docker compose -f compose.local.yaml up -d
docker compose -f compose.local.yaml ps
```

### Step 2 — Verify Laravel inside the app container

```powershell
docker compose -f compose.local.yaml exec -T app php artisan about
```

### Step 3 — Open storefront

`http://localhost:8088`

Confirm:

- page loads
- no Laravel exception
- header renders
- banners render
- footer renders
- CSS/JS assets load
- no obvious broken layout

### Step 4 — Open admin login

`http://localhost:8088/admin/login`

Local admin credentials are stored locally in:

`.local\admin-credentials.json`

Do not print or expose the password in chat/logs.

Confirm:

- login page loads
- admin login succeeds
- dashboard renders
- session persists
- logout works

### Step 5 — Update tracker

If both storefront and admin are working:

- mark Phase 1 as 100% complete
- add completion timestamp
- update change log
- update current handoff
- set Current Phase to Phase 2

---

## 11. Phase 2 Direction

After Phase 1 is formally complete, begin:

**Phase 2 — Storefront Theme & UI**

Primary goals:

- premium/discreet lifestyle visual direction
- responsive desktop/tablet/mobile design
- custom header/navigation
- hero banner
- categories
- best sellers
- new arrivals
- promotional sections
- product listing page
- product detail page
- wishlist/cart/account UI
- mobile responsiveness

Do not begin Phase 4 admin redesign before Phase 2 and Phase 3 structure are stable unless the project owner explicitly changes priority.

---

## 12. Future Admin Requirements

The planned admin product editor should eventually become Lazada-style.

Required areas:

1. Basic Information
2. Product Images
3. Product Video
4. Category
5. Product Description
6. Specifications
7. Variations
8. Pricing
9. Inventory
10. Shipping
11. SEO
12. Status

Fixed bottom action bar:

- Save Draft
- Preview
- Publish / Submit

Image area:

`[IMAGE] [IMAGE] [IMAGE] [+]`

Add Image menu:

- Upload
- Media Bank
- URL

URL-imported images must be downloaded to local/media storage rather than hotlinked.

---

## 13. Future Media Bank Requirements

Planned Media Bank features:

- image upload
- video upload
- search
- filters
- folders/categories
- thumbnail view
- file metadata
- dimensions
- upload date
- reusable media
- safe delete
- prevent deletion if media is in use
- import image from URL
- duplicate detection where practical
- validation for JPG/JPEG/PNG/WEBP/MP4
- MIME validation
- executable upload prevention

---

## 14. Privacy / Store Context

The store is intended as a legal adult wellness / adult-product e-commerce store.

Design should remain:

- professional
- premium
- discreet
- lifestyle/wellness-oriented
- not pornographic

Planned privacy features:

- age confirmation
- guest checkout
- discreet packaging option
- neutral shipping-label wording
- neutral order email wording
- minimal customer data collection
- privacy policy
- no storage of raw payment-card details

Do not implement deceptive or payment-provider-bypassing behavior.

---

## 15. SEO Requirements

Planned SEO implementation:

- meta title
- meta description
- SEO-friendly URLs
- canonical URLs
- XML sitemap
- robots.txt
- Open Graph
- product structured data
- breadcrumb structured data
- category metadata
- image alt text
- fast page load
- mobile optimization

Bagisto's current built-in SEO features should be reused where practical instead of duplicating functionality.

---

## 16. Security Principles

Follow Laravel and Bagisto extension conventions.

Prefer:

- packages
- themes
- service providers
- events/listeners
- admin extensions
- supported overrides

Avoid unnecessary core edits.

Never commit:

- `.env`
- passwords
- API keys
- private keys
- production credentials
- database secrets

Validate all uploads.

Use Git before major refactors.

---

## 17. Agent Update Rules

Every Codex session should:

1. Read `BAGISTO_PROJECT_AGENT_TRACKER.md`.
2. Read this handover.
3. Check `git status --short`.
4. Do not overwrite clean committed work.
5. Update the tracker after changes.
6. Record:
   - timestamp
   - phase
   - tasks completed
   - files changed
   - tests run
   - blockers
   - next action
7. Do not mark tasks complete until tested.
8. Do not move to the next phase until the current phase completion gate passes.

---

## 18. Quick Resume Commands

From PowerShell:

```powershell
cd "D:\CHATGPT PROJECTS\Bagisto"

git status --short

docker compose -f compose.local.yaml up -d

docker compose -f compose.local.yaml ps

docker compose -f compose.local.yaml exec -T app php artisan about

docker compose -f compose.local.yaml exec -T app php artisan migrate:status
```

Then open:

```text
http://localhost:8088
http://localhost:8088/admin/login
```

If both work, finalize Phase 1.

---

## 19. Current Handoff Summary

**Current Phase:** Phase 1 — Local Environment & Installation  
**Progress:** ~96%  
**Current state:** Docker runtime and persistent DB restored and verified  
**Working tree:** clean  
**Current blocker:** none  
**Immediate next task:** browser verification of storefront + admin  
**Expected next phase:** Phase 2 — Storefront Theme & UI

Do not reinstall or reinitialize the project.
