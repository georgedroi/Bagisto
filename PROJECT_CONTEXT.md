# PROJECT_CONTEXT.md — MAN SECRET PLEASURE (Bagisto)

The hand-over document for anyone picking up this project. It says what the project is, what has been built, what is available to use, what is broken or missing, and what to do next. `AGENTS.md` holds the working rules; this file holds the facts.

**Snapshot date: 2026-10-25.** Every statement is tagged:
- **[Verified]** — checked in code, git, or by a command run on the snapshot date.
- **[Owner]** — decided or stated by the owner; not re-checked in code.
- **[Unverified]** — reported by older documents, or not checked.

"Present in code" never means "tested" or "working". The evidence is stated each time. No secrets belong in this file; credentials live in `.env` (git-ignored) and on the production server.

---

## 1. Start here (first 30 minutes)

1. Read this file, then `AGENTS.md` (rules: what never to run, code standards, Git and release safety).
2. `git status` and `git log -5`. Another person's uncommitted work may be in the tree; do not discard it. Leave the untracked `*.bak`, `deploy.bat`, `route-list.json` and the stray `C:` folder alone.
3. Start the local store: `docker compose -f compose.local.yaml up -d`, then open `http://localhost:8088` and `http://localhost:8088/admin/login`. Ask the owner for the admin login; do not reset passwords.
4. Run artisan in the `app` container as `-u www-data -e HOME=/tmp`. Never run `bagisto:install`, `migrate:fresh` or `db:wipe`: they wipe the database.
5. Read section 7 (work done) and section 8 (what is available) to see what already exists before writing anything new.
6. Check section 13 (known issues) and section 12 (planned work) before choosing a task.
7. When you finish, add a row to the progress log (section 17) and fix any statement your change made untrue.

### Which documents to trust

| Document | Use it for | Trust |
|---|---|---|
| `PROJECT_CONTEXT.md` (this file) | Current facts, status, history | Current |
| `AGENTS.md`, `CLAUDE.md` | Working rules | Current |
| `docs/MANPLEASURE_CATALOG_ARCHITECTURE.md` | Catalog design: categories, families, attributes, SKU and URL conventions | Design intent; the category tree there is the original 6-category plan, see section 12 for the proposed change |
| `LOCAL-DEVELOPMENT.md` | Original Windows/Docker install walkthrough (6 Oct 2026) | Install history only |
| `BAGISTO_PROJECT_AGENT_TRACKER.md`, `CODEX_HANDOVER.md` | Earlier phase-by-phase notes (to 8 Oct 2026) | **Stale and contradicting each other**; history only |
| `README.md`, `CHANGELOG.md`, `UPGRADE.md`, `CONTRIBUTING.md`, `SECURITY.md` | Upstream Bagisto | Upstream, not this project |

---

## 2. Purpose and goals

- **Store:** MAN SECRET PLEASURE at `mansecretpleasure.com`, an adult wellness and adult-product store aimed at men, operated by Four Gems Marketing [Owner]. Design: professional, premium, discreet, wellness-oriented, not pornographic [Verified in `CODEX_HANDOVER.md`].
- **Market:** Malaysia first, Singapore later. No EU sales (the EU Withdrawal package stays disabled). No payment gateway ever; manual payments only [Owner].
- **Language:** the owner writes in Chinese; reply in their language, keep storefront content in English [Owner].
- **Launch intent:** test freely until go-live. At launch, empty the **product catalog data only** and keep system configuration. Orders, customers and carts are undecided; ask first [Owner, 2026-10-25].

## 3. Technology stack [Verified]

| Item | Value | Source |
|---|---|---|
| Platform | Bagisto 2.4.12 | `packages/Webkul/Core/src/Core.php` |
| Framework | Laravel 12.69.2 | `composer.lock` |
| PHP | `>=8.3 <8.5`; the container runs 8.3.x | `composer.json`, container |
| Model layer | Konekt Concord 1.18.1, `prettus/l5-repository` 2.10.1 | `composer.lock` |
| Frontend | Vue 3, Tailwind 3, Vite 6, built per package | `package.json`, package folders |
| Tests | Pest 3.8.7, Playwright, Pint 1.29.3 | `composer.lock`, `package.json` |
| Local DB | MariaDB 10.11 in Docker | `compose.local.yaml` |
| Production DB | MySQL-compatible; not inspected here | [Unverified] |

## 4. Environment and workflow

**Docker (`compose.local.yaml`) is the authoritative local environment.** The root `docker-compose.yml` is upstream's and is not used.

| Service | What it is | Notes |
|---|---|---|
| `app` | PHP/Laravel, built from `docker/local/Dockerfile` | project folder mounted at `/var/www/html`; `vendor` is a named volume |
| `web` | `nginx:stable-alpine` | bound to `127.0.0.1:8088` |
| `database` | `mariadb:10.11` | host `database` inside the Docker network only; **not published to the host** |
| `node` | `node:22-bookworm-slim` | tooling only |

- Status: `docker compose -f compose.local.yaml ps`. Stop without losing data: `docker compose -f compose.local.yaml stop`. **Never** `down -v`: it deletes the database volume.
- A Windows MySQL may also listen on port 3306. It is **not** the project database; the project login is rejected there [Verified 2026-10-25].
- Windows host PHP comes from Laravel Herd (`php.bat`). It is good for `php -l` and Pint but cannot reach the Docker database.
- `.env` holds the real values. `.env.backup-*` copies exist locally and are git-ignored [Verified]. Never print or commit them.

**Running tests safely.** The suite uses `DatabaseTransactions` against whatever database `.env` names and expects a freshly installed database. SQLite fails (the migrations use MySQL functions such as `CONCAT`). What worked on 2026-10-25: start a throwaway MariaDB container on the compose network, run `php artisan migrate --force` plus the installer's `DatabaseSeeder` (and `DatabaseManager::createAdminUser`) with environment-variable overrides for the DB settings, run Pest inside the `app` container, then delete the container. Do not use `bagisto:install` for this: it runs `key:generate` and wipes the database.

## 5. Repository structure [Verified]

```
app/                      thin shell + the project's own code (below)
bootstrap/providers.php   every service provider (App\Providers\ManPleasure* are registered here)
config/                   concord.php, themes.php + project files: manpleasure-*.php, mansecretpleasure.php
database/                 app migrations (media_assets, login page settings) + seeders (ManPleasure*)
docs/                     MANPLEASURE_CATALOG_ARCHITECTURE.md
docker/local, production  Dockerfile / nginx / php.ini; production README
packages/Webkul/          the 41 Bagisto packages (Admin, Shop, Payment, Shipping, Category, ...)
resources/themes/manpleasure/views   storefront theme overrides (the folder's .gitignore is `*`; add files with git add -f)
routes/                   web.php (empty of routes), manpleasure-updates.php, manpleasure-login-page-builder.php
scripts/deploy, local     release packaging/staging/activation/rollback, production check; local helpers
tests/                    Pest bootstrap only; real tests live inside each package
release.json             current release id and commit (msp-2026.10.24.8 at snapshot)
```

**Project code in `app/`:**
- `Http/Controllers/Admin/`: `MediaAssetController`, `LoginPageBuilderController`, `SystemUpdateController`. `Http/Controllers/BlogController` (the `/blog` page). `Http/Middleware/RequireUpdateOwner`.
- `Models/MediaAsset`, `Models/ManPleasureLoginPageSetting`. `Repositories/StorefrontPageRepository`. `Support/WhatsAppMessage`, `Support/ManPleasureLoginPageSanitizer`.
- `Services/Updates/` (release catalog, manifest, deployment operations).
- `Providers/ManPleasureAdminServiceProvider` and `ManPleasureStorefrontServiceProvider`.
- `Resources/views/`: admin sidebar and "executive" layout, dashboard/order/product grids, product editor blueprint and actions, Media Bank page, login page builder page, system updates page, WhatsApp order panel, age gate, privacy links.

**Modified upstream packages.** Against tag `v2.4.12` there are 37 project commits and 292 changed files under `packages/`: Admin 167, Payment 47, Shop 35, Shipping 28, Category 4, Stripe 6, Attribute 1, others 3. Upstream code in these packages has been changed. Compare against the local copy, not upstream docs, and expect merge work on any Bagisto upgrade.

## 6. Configuration files (project-owned)

| File | Purpose |
|---|---|
| `config/mansecretpleasure.php` | Trusted-proxy settings for production |
| `config/manpleasure-contact.php` | Store WhatsApp number and contact email used by the WhatsApp links (values are committed; not repeated here) |
| `config/manpleasure-storefront-system.php` | Storefront system-config defaults merged into `core` |
| `config/manpleasure-updates.php`, `-update-menu.php`, `-update-acl.php` | In-admin updater: repository, deploy switch, owner ids, request/status directories, menu entry, permissions |
| `config/manpleasure-login-page-builder-menu.php`, `-acl.php` | Menu entry and permissions for the login page builder |
| `release.json` | Release id, version and commit; bumped on every release |

---

## 7. Work done (verified from git, newest last)

The project was forked from Bagisto 2.4.12. All 37 project commits fall between **2026-10-06 and 2026-10-25**. Release ids `msp-2026.10.24.1` to `.8` are the production releases cut on those days.

| Date | Work | Evidence |
|---|---|---|
| 06 Oct | Bagisto 2.4.12 installed locally with Docker; `MANPLEASURE` storefront theme added | commits `7b19a08264`, `b8491a47f2` |
| 07 Oct | Storefront theme and product gallery verified; mobile gallery regression fixed; localization finished | `c24a38ffd3`, `f27ece47d9` |
| 08 Oct | Catalog and privacy scaffolding: category, attribute and QA-product seeders, `media_assets` migration, Media Bank, age gate, product editor blueprint, catalog architecture document | `e7c173d35c` |
| 09 Oct | `payment_receipts` table (customer payment proof) added to the Payment package | migration `2026_10_09_000001` |
| 18 Oct | Code synced down from the live web server (release `msp-2026.10.17.4`): production existed by 17 Oct | `2bb7c2c729` |
| 23 Oct | Vulnerable Composer dependencies fixed; Shop lockfile tracked; release packaging fixed | `79cb11722d`, `a53f654339`, `a815e2291f`, `6c53e80efb` |
| 24 Oct | Admin "executive" layout, product editor and **login page builder** synced into the repo | `99df15212e` |
| 24 Oct | Full storefront theme tracked in git | `7a5fa45205` |
| 24 Oct | **Manual payments**: Bitcoin/USDT transfer, bank transfer, DuitNow QR (manual) enabled, payment instructions shown at checkout and in the order email | `218f879ee6`, `da0358aba4` |
| 24 Oct | **WhatsApp** one-click messages on admin orders, plus a proof-of-payment button | `9717c47daa` |
| 24 Oct | **Discreet order emails**: product names, SKUs and options hidden | `c0a4ec335f` |
| 24 Oct | Home page renders when the proxy controller passes no categories; unused `public/admin` removed (it caused nginx 403s); admin sidebar cleanup (Help & Resources and sidebar search removed) | `f4beb00003`, `bb625c242b`, `b173a12544` |
| 24 Oct | **Zone-rate shipping** for Malaysia with configurable zones and subtotal tiers | `b446219920` |
| 24 Oct | Production check updated so the manual DuitNow QR method may be enabled | `67b2566ce7` |
| 25 Oct | Currency switcher restyled (circular symbol badge); themed desktop top bar with the currency badge | `a2c8e53ebf`, `19ee7c537f` |
| 25 Oct | **Attribute family group deletion**: specific warning pop-up, server-side guard, transactional family update, new strings in 22 locales, 5 new tests (branch `family-group-delete-warning`, not merged) | `779b43d850` |

**Work done outside the repository (2026-10-25):**
- A private **Product Entry Form** page that builds a product import CSV (section 10).
- Category and competitor research (Horny.sg, SexyWawa, Fleshlight menus) leading to the proposed category tree (section 12).
- This file, `AGENTS.md` and `CLAUDE.md`.

---

## 8. What is available (and how well it is known to work)

"Evidence" says whether the feature was run. Unless stated, features below are **present in code but were not re-tested** on the snapshot date.

### Storefront
- Theme `manpleasure`: home, header (desktop top bar and mobile, with the currency switcher), category pages, product page with gallery (desktop and mobile), cart, one-page checkout, checkout success, search, blog index, footer, store-benefits strip. 29 view overrides are tracked in git [Verified].
- Age gate template registered in `ManPleasureStorefrontServiceProvider`; privacy links partial. Browser behaviour unverified.
- `/blog` served by `BlogController`.

### Checkout and payments (package `Payment`)
- **Live-intent methods (manual):** bank transfer, DuitNow QR (manual), Bitcoin/USDT crypto transfer. Payment instructions appear at checkout and in the order email [Verified in commits; production activation is [Owner]].
- **Proof of payment:** a logged-in customer can upload a receipt for their own order (JPG, PNG or PDF, up to 5 MB) when DuitNow QR manual is available; admins can approve or reject receipts (`admin.sales.receipts.approve|reject`) [Verified in code].
- **Present but intentionally disabled:** cash on delivery, money transfer, FPX, TNG eWallet, credit/debit cards, DuitNow QR (auto/plain), Stripe. `scripts/deploy/check-production.php` blocks activation if FPX, TNG eWallet, cards, DuitNow QR auto/plain or Stripe is enabled [Verified].
- Stripe, PayPal, Razorpay, PayU, PhonePe and PayGlocal packages exist from upstream.

### Shipping (package `Shipping`)
- Carriers: flat rate, free, and **zone rate**. Zone rate works from two configuration texts: *zones* (a country, optionally narrowed by postcode ranges or state names; a zone with matchers wins over a country-wide zone) and *rates* (a fee per zone for each order-subtotal band). A destination with no zone or no matching band is not served. [Verified in `ZoneRate.php` and `ZoneRateTable.php`]
- Unit test: `Shipping/tests/Unit/ZoneRateTableTest.php` (not re-run on the snapshot date).

### Orders and customer messages
- **WhatsApp panel** on the admin order page: one-click `wa.me` links for *received*, *paid* and *shipped* messages, built from the customer's phone number; disabled when no usable number exists. No automated sending [Verified in code].
- **Discreet emails:** customer order emails show neutral item labels instead of product names, SKUs and options.

### Admin tools
- **Executive layout and sidebar** restyle; Help & Resources and sidebar search removed.
- **Product editor blueprint and actions:** a 12-section guide panel (Basic Information, Images, Video, Category, Description, Specifications, Variations, Pricing, Inventory, Shipping, SEO, Status) and action buttons shown on top of Bagisto's native product editor. It does not replace the editor or change how products are saved. The fuller "Lazada-style" editor described in older documents (fixed save/preview/publish bar, Media Bank picker) was not found in the code.
- **Media Bank** at `/admin/media` (routes in the Admin package): upload JPG, JPEG, PNG, WEBP or MP4 (up to 50 MB), duplicate detection by SHA-256, search by name/type/checksum, 24 per page, delete. **Gaps:** no folders (a free-text `collection` only), no URL import, no protection against deleting an asset that is in use, English strings not translated, direct model queries instead of a repository. [Verified in `MediaAssetController`]
- **Login page builder** (admin → Appearance): save, publish, unpublish and status routes with a sanitizer. Unverified in a browser.
- **System updates** (admin → Settings, owner-only): check, install, rollback and operation-status routes, rate-limited. Needs deploy switch and directories in `config/manpleasure-updates.php`. Unverified.
- **Attribute family group deletion** now warns, shows attribute and product counts, and the server refuses a save that would leave a system attribute without a group. Run evidence: section 15.

### Catalog
- Category tree: 6 main + 21 sub-categories under Root. Families: `default`, `manpleasure_core`, `manpleasure_consumable` [Verified in the local database, 2026-10-25].
- Seeders: `ManPleasureCategorySeeder`, `ManPleasureAttributeSeeder` (creates the `mp_*` attributes and the two families), `ManPleasureQaProductSeeder` (QA products with SKUs `MP-QA-SIMPLE-001` and `MP-QA-CONFIG-001`). The production check requires that no `MP-QA-%` products exist.
- Product import: the standard Bagisto Data Transfer importer (admin → Settings → Data Transfer → Imports). CSV format: see the Product Entry Form.

### Release and operations tooling (`scripts/`)
- `deploy/package-release.sh` builds a release tarball from a clean worktree; `stage-release.sh` stages it on the server; `activate-release.sh <id> --data-reviewed` activates it; `rollback-release.sh` reverts; `migrate-reviewed.sh`, `provision-database.sh`, `prepare-origin.sh`, `install-node.sh`, `install-admin-updater.sh`, `package-admin-update.sh` support these. `check-production.php` runs the go-live gate (production env, debug off, HTTPS URL, secure cookies, queue and session drivers, no QA products, built assets present, unverified payment methods disabled). **Read a script before running it; the owner activates releases.**
- `local/Initialize-Environment.ps1` and `local/configure-local-admin.php` are local setup helpers. Unverified.

### Tests
- Upstream Pest tests per package, plus Playwright under `packages/Webkul/Admin/tests/e2e-pw`. Project-specific automated tests: `ZoneRateTableTest` and the five new attribute-family tests. **No automated tests found** for payments, receipts, WhatsApp, Media Bank, age gate, login page builder or the updater.

---

## 9. Catalog data snapshot [Verified, local database, 2026-10-25, read-only]

- 28 categories including Root; none contains a product.
- 3 products, all in `manpleasure_core` (test items).
- Families: `default` (8 groups), `manpleasure_core` (8), `manpleasure_consumable` (7), plus a local test family `zz_test_family`.
- **Gap:** `manpleasure_core` and `manpleasure_consumable` omit 11 system attributes: `tax_category_id`, `new`, `featured`, `special_price`, `special_price_from`, `special_price_to`, `meta_title`, `meta_keywords`, `product_number`, `allow_rma`, `rma_rule_id`. So these families have no New/Featured/Sale flags, no special price, no per-product SEO fields and no tax category. The older document figures (49 attributes, 199 migrations) were not re-checked.

## 10. Product Entry Form (external)

A private claude.ai artifact, "Product Entry Form" (`https://claude.ai/artifact/7CsenwhmdD6yLUxxB1rzAG`), builds a product CSV for the Data Transfer importer. It is **not part of this repository**.

- "Device or accessory" and "Lubricant, cleaner or care" are hard-coded in its `FAMILY` constant and map to `manpleasure_core` and `manpleasure_consumable`. Detail fields are in `DETAILS`; category list, option IDs, existing SKUs and URL keys are in `STORE`. All of these go stale when categories or attributes change.
- It creates simple products only. It states an exchange rate of 1 USD = RM 4.50, a free-delivery threshold of RM 300 and fee tiers. These were **not checked against the configured zone-rate table**, and the owner's notes say there is no free shipping [Unverified, possible conflict].

---

## 11. Deployment [Owner + scripts]

Package the release from a clean worktree, upload and stage it, then the **owner** activates it as root. Every release bumps `release.json`. Hard-won checks (full list in `AGENTS.md`):
- new theme files need `git add -f`, run as its own command, then check with `git ls-files`;
- never ship files under `public/admin` (nginx returns 403 for `/admin/`); test both `/admin` and `/admin/` after a release;
- `git add` explicit paths only; never stage `*.bak`, `route-list.json`, `deploy.bat`;
- config is cached at activation; after editing production `.env` run `config:cache` and restart the queue service.

## 12. Planned work (nothing below is built; nothing has been changed in code or database)

1. **Category restructure — awaiting owner approval.** Proposed: 8 main categories, at most 3 sub-categories each: Strokers & Masturbators (Manual Strokers, Automatic Strokers, Sleeves & Compact); Rings & Delay (Cock Rings, Extenders, Delay Sprays); Pumps & Trainers (Manual Pumps, Electric Pumps, Trainers & Accessories); Prostate & Anal (Prostate Massagers, Anal Toys); **Condoms** (Regular, Thin, Lasting); Lubricants & Care (Water-Based, Silicone-Based, Cleaners & Care); Accessories & Storage (Storage & Travel, Replacement Parts, Chargers & Cables); **Underwear & Costumes** (Men's Underwear, Women's Lingerie, Costumes & Cosplay). Fixes: plainer wording for claim-style names, "Beginner-Friendly" becomes a filter, overlapping travel categories merged. Categories hold no products yet, so the change is low-risk now.
2. **Apparel family** for Underwear & Costumes: new family, product types (Underwear, Lingerie, Costume), attributes Size (required), Colour (optional), Fabric, Audience; configurable products with one SKU and stock count per variant; a non-returnable-hygiene line on the policy page. The owner confirmed sizes and possibly colours as variants.
3. **Product Entry Form v2:** the category decides the family and fills in the product type (editable); add a variants section for apparel.
4. **Add `new`, `featured`, `special_price*`** to the families if New Arrivals / Best Sellers / Sale collections are wanted.
5. **Launch cleanup** of product data (section 2): backup with a tested restore, rehearsal on a copy, run by the owner. Keep configuration. Do not use the installer or seeders to reset.
6. Replace placeholder text on policy pages where it remains (the privacy page held placeholders on 2026-10-08) [Unverified now].

## 13. Known issues and limitations

- **Documentation drift:** the tracker and handover disagree with each other and with git (see section 1). They are history.
- **Two failing tests** in `Catalog/CategoryTest` (single and mass delete expect 200, get 409). The controller deliberately returns 409 on the old delete routes ("use the tree"), so the tests are out of date. Pre-existing [Verified].
- **Repository rule violations** (all database access must go through a repository): `MediaAssetController` uses the model directly; the three `ManPleasure*` seeders use the `DB` facade [Verified]. The Media Bank page and controller also have untranslated English strings.
- **Media Bank gaps** listed in section 8.
- **`CLAUDE.md` skills:** the upstream `bagisto-*` skills it once named are not installed (`.agents/skills`, `.claude/skills` do not exist). `AGENTS.md` does not depend on them.
- **Working-tree clutter:** untracked `*.bak` files, `deploy.bat`, `route-list.json` (a stale route dump), and a garbled `C:` folder created at the root.
- **Not run on the snapshot date:** the full Pest suite, Playwright, a Vite build, and any browser check other than the family-group pop-up.
- **Single point of knowledge:** the owner-only release activation and server details are not documented in the repository beyond the scripts.

## 14. Git summary [Verified, 2026-10-25]

- Remotes: `origin` (the owner's GitHub fork), `upstream` (bagisto/bagisto).
- Branch `family-group-delete-warning`: one commit ahead of `main` (`779b43d850`), pushed to `origin`, not merged. `main` is at the release bump `f2f0272f7a`.
- Branch `docs/project-handover` is stacked on `family-group-delete-warning` and adds one commit with the three documentation files (`AGENTS.md`, `CLAUDE.md`, `PROJECT_CONTEXT.md`). Merge the family branch first, then this one. Pushed to `origin`, not merged.
- Earlier local branches: zone-rate shipping, manual payment instructions, crypto transfer, WhatsApp, discreet emails, currency switcher, admin UI tweaks, and two small fixes.

## 15. Checks run on 2026-10-25 (the only ones)

| Check | Result |
|---|---|
| Pint on the three changed PHP files | pass |
| `php -l` on all changed PHP files and 22 locale files | pass |
| `php artisan bagisto:translations:check` | 357 locale files pass |
| `AttributeFamilyTest` (disposable database) | 17 of 17 pass |
| `packages/Webkul/Admin/tests/Feature/Catalog` | 182 pass, 2 fail (the known-stale Category tests) |
| Browser check of the group-delete pop-up | passed, by the owner |
| Playwright, full Pest suite, Vite build | **not run** |

## 16. Recommended next steps

1. Review and merge `family-group-delete-warning`, then release the usual way (owner activates).
2. Merge `docs/project-handover` after step 1.
3. Decide the category tree (section 12.1) and whether to add Condoms and Underwear & Costumes; apply to the database only after approval.
4. Decide on the 11 missing system attributes (section 9) before building collections or per-product SEO.
5. Retire the stale tracker/handover so there is one source of progress (this file plus git).
6. Add tests for payments and receipts, WhatsApp, Media Bank, age gate, login page builder and the updater; fix the two stale Category tests.
7. Fix the Media Bank gaps: in-use delete protection, translations, repository, URL import.
8. Verify the Product Entry Form's delivery-fee and free-shipping text against the real zone-rate table and the policy pages.
9. Plan the launch cleanup with a backup, a restore test and a rehearsal.

## 17. Progress log

Add one row after every significant change: date, what changed, files, checks run with results, open items. Newest first. No secrets. Do not claim a check passed unless it was run.

| Date | Change | Files | Checks run | Open items |
|---|---|---|---|---|
| 2026-10-25 | Rewrote this file as a full hand-over (start-here guide, work log from the 37 project commits, availability map); rewrote `AGENTS.md` for this project only; trimmed `CLAUDE.md` to point at both | `PROJECT_CONTEXT.md`, `AGENTS.md`, `CLAUDE.md` | Documentation only; referenced paths, routes and classes were checked to exist | Items marked [Unverified]; committed on branch `docs/project-handover`, not merged |
| 2026-10-25 | Warn before deleting an attribute family group; server guard against orphaned system attributes; transactional family update; 4 strings x 22 locales | `AttributeFamilyController`, `AttributeFamilyRepository`, `families/edit.blade.php`, `AttributeFamilyTest`, 22 admin `app.php` | Section 15 | Not merged; Playwright not run |
