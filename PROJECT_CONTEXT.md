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
- `Http/Controllers/Admin/`: `MediaAssetController`, `LoginPageBuilderController`, `SystemUpdateController`, `ProductEntryController`. `Http/Controllers/BlogController` (the `/blog` page). `Http/Middleware/RequireUpdateOwner`.
- `Models/MediaAsset`, `Models/ManPleasureLoginPageSetting`. `Repositories/StorefrontPageRepository`, `Http/Requests/ProductEntryRequest`. `Support/WhatsAppMessage`, `Support/ProductEntryCatalog`, `Support/ManPleasureLoginPageSanitizer`.
- `Services/Updates/` (release catalog, manifest, deployment operations).
- `Providers/ManPleasureAdminServiceProvider` and `ManPleasureStorefrontServiceProvider`.
- `Resources/views/`: admin sidebar and "executive" layout, dashboard/order/product grids, product editor action bar, Add product page (`product-entry/index.blade.php`), Media Bank page, login page builder page, system updates page, WhatsApp order panel, age gate, privacy links.

**Modified upstream packages.** Against tag `v2.4.12` there are 37 project commits and 292 changed files under `packages/`: Admin 167, Payment 47, Shop 35, Shipping 28, Category 4, Stripe 6, Attribute 1, others 3. Upstream code in these packages has been changed. Compare against the local copy, not upstream docs, and expect merge work on any Bagisto upgrade.

## 6. Configuration files (project-owned)

| File | Purpose |
|---|---|
| `config/mansecretpleasure.php` | Trusted-proxy settings for production |
| `config/manpleasure-contact.php` | Store WhatsApp number and contact email used by the WhatsApp links (values are committed; not repeated here) |
| `config/manpleasure-storefront-system.php` | Storefront system-config defaults merged into `core` |
| `config/manpleasure-updates.php`, `-update-menu.php`, `-update-acl.php` | In-admin updater: repository, deploy switch, owner ids, request/status directories, menu entry, permissions |
| `config/manpleasure-login-page-builder-menu.php`, `-acl.php` | Menu entry and permissions for the login page builder |
| `config/manpleasure-product-entry.php`, `-menu.php`, `-acl.php` | Add product page: main category to family and SKU prefix, product type per sub-category, fields the page draws itself, numeric fields, the US dollar display rate; the Catalog menu entry and its permission |
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
| 25 Oct | **Category tree and product-family fields:** 8 main + 23 sub-categories (at most 3 each, unique names) built by the category seeder, with earlier categories renamed in place or merged; New, Featured and Special Price (with from/to dates) added to both families; every family group now has a `code` (the family edit form needs one to save) | `ManPleasureCategorySeeder`, `ManPleasureAttributeSeeder`, `ManPleasureCatalogSeederTest`; branch `category-setup`, uncommitted; applied to the **local** database only |
| 26 Oct | **Eight product families, one per main category** (strokers, rings, pumps, prostate, condoms, lubricants, accessories, apparel), with eight new fields (Width, Pack size, Texture, Lubricated, Best before, Insertable length, Fabric, Audience), new options (Condom, Underwear, Lingerie, Costume; Latex, Polyisoprene, Polyurethane) and required flags; the field table was approved by the owner; no KEY flag | `ManPleasureFamilySeeder`, `ManPleasureAttributeSeeder`, `ManPleasureCatalogSeederTest`; branch `category-setup`, uncommitted; applied to the **local** database only |
| 26 Oct | **In-admin Add product page, phase 1 (simple products)** at Catalog › Add product: two-column category picker with search and recent chips, fields that follow the category's family, change-category warning, image upload, price, special price with dates, stock, weight, New/Featured, descriptions, live phone preview, checklist and fill rate; saves as draft or published through the same repository calls as the product editor, inside one transaction; strings in all 22 locales | `ProductEntryController`, `ProductEntryRequest`, `ProductEntryCatalog`, `product-entry/index.blade.php`, config and routes, `ProductEntryTest`; branch `category-setup`, uncommitted |
| 26 Oct | **Product editor cleanup:** the "Product Editor Blueprint" panel removed, the action bar made sticky inside the page and translated (22 locales), the Media Bank added to the Catalog menu with its own permission | `product-editor-actions.blade.php`, `ManPleasureAdminServiceProvider`, `config/manpleasure-product-entry-menu.php` and `-acl.php`, 22 `product-editor.php`, `ProductEditorChromeTest`; branch `category-setup`, uncommitted |

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
- **Product editor action bar:** a Save Draft / Preview / Publish bar at the bottom of the product editor, translated in all 22 locales. It sticks to the bottom of the page content (it used to be pinned to the browser window and covered the sidebar and content). The earlier "Product Editor Blueprint" panel (12 section buttons, "Phase 4") was removed on 2026-10-26 because the Add product page replaces it. The sticky behaviour has not been checked in a browser; if a parent element clips it, the bar simply sits at the end of the form.
- **Add product page** (Catalog › Add product, route `admin.catalog.product_entry.index`): see section 12.3 for the design. Server side (verified by 9 passing tests): creates a simple product in the family of the chosen sub-category with its fields, stock, categories (main and sub) and pictures; refuses a missing required field, a duplicate SKU, a special price not below the price and a category without a family; ignores fields of another family; suggests the next SKU; leaves no product behind if saving fails. Browser side: the Vue template compiles with no errors and the script is syntactically valid, but **the page has not been opened in a browser yet**. Not built: variations, Media Bank picker and "from a link" images, per-field translated labels (field names come from the attribute names).
- **Pricing & Daily Deals modal** (product list, "Edit pricing" row button, simple and virtual products only): edits price, cost, special price and its dates; "Forever" clears the dates, turning the deal off clears the special price. Shows a storefront preview (active, scheduled, expired, off), "Save %" and margin. Saves only those fields through the product repository and fires the product update events. Row button "View on storefront" appears for active products with a URL key. Verified by 6 tests; not opened in a browser. The storefront theme's own "Save %" display is unchanged.
- **Media Bank** at `/admin/media` (routes in the Admin package; listed in the Catalog menu and given its own permission `catalog.media_bank` on 2026-10-26, because the removed blueprint panel had been its only link): upload JPG, JPEG, PNG, WEBP or MP4 (up to 50 MB), duplicate detection by SHA-256, search by name/type/checksum, 24 per page, delete. **Gaps:** no folders (a free-text `collection` only), no URL import, no protection against deleting an asset that is in use, English strings not translated, direct model queries instead of a repository. [Verified in `MediaAssetController`]
- **Login page builder** (admin → Appearance): save, publish, unpublish and status routes with a sanitizer. Unverified in a browser.
- **System updates** (admin → Settings, owner-only): check, install, rollback and operation-status routes, rate-limited. Needs deploy switch and directories in `config/manpleasure-updates.php`. Unverified.
- **Attribute family group deletion** now warns, shows attribute and product counts, and the server refuses a save that would leave a system attribute without a group. Run evidence: section 15.

### Catalog
- Category tree: 8 main + 23 sub-categories under Root (max 3 sub-categories each, unique names). Families: `default`, `manpleasure_core`, `manpleasure_consumable` [Verified in the local database after the 2026-10-25 category setup; **not yet applied to production**].
- Seeders (all repeatable): `ManPleasureCategorySeeder` builds the tree, renames earlier categories in place so ids and product links survive, merges retired categories into their replacement, and rolls back with an error if a retired category still holds products and has no replacement; `ManPleasureAttributeSeeder` creates the `mp_*` attributes, their options and required flags, and the two legacy families, adding missing fields and group codes without removing anything; `ManPleasureFamilySeeder` creates the eight per-category families and only ever adds to a family that already exists; `ManPleasureQaProductSeeder` creates QA products (`MP-QA-SIMPLE-001`, `MP-QA-CONFIG-001`). The production check requires that no `MP-QA-%` products exist.
- Product import: the standard Bagisto Data Transfer importer (admin → Settings → Data Transfer → Imports). CSV format: see the Product Entry Form.

### Release and operations tooling (`scripts/`)
- `deploy/package-release.sh` builds a release tarball from a clean worktree; `stage-release.sh` stages it on the server; `activate-release.sh <id> --data-reviewed` activates it; `rollback-release.sh` reverts; `migrate-reviewed.sh`, `provision-database.sh`, `prepare-origin.sh`, `install-node.sh`, `install-admin-updater.sh`, `package-admin-update.sh` support these. `check-production.php` runs the go-live gate (production env, debug off, HTTPS URL, secure cookies, queue and session drivers, no QA products, built assets present, unverified payment methods disabled). **Read a script before running it; the owner activates releases.**
- `local/Initialize-Environment.ps1` and `local/configure-local-admin.php` are local setup helpers. Unverified.

### Tests
- Upstream Pest tests per package, plus Playwright under `packages/Webkul/Admin/tests/e2e-pw`. Project-specific automated tests: `ZoneRateTableTest` and the five new attribute-family tests. **No automated tests found** for payments, receipts, WhatsApp, Media Bank, age gate, login page builder or the updater.

---

## 9. Catalog data snapshot [Verified, local database, 2026-10-25, after the category setup]

- 32 categories including Root (8 main, 23 sub); none contains a product.
- 3 products, all in `manpleasure_core` (test items).
- Families: `default` (8 groups), the legacy `manpleasure_core` (8) and `manpleasure_consumable` (7), and the eight per-category families `manpleasure_strokers`, `_rings`, `_pumps`, `_prostate`, `_condoms`, `_lubricants`, `_accessories`, `_apparel` (6 groups each). The consumable family has an extra group "Ingredients & Care" that the seeder does not define (it drifted in the database); the seeder leaves it alone.
- New, Featured, Special Price and its from/to dates are assigned to both families (General and Price groups). Every group in these families now has a `code`.
- **Remaining gap:** no ManPleasure family includes 6 system attributes: `tax_category_id`, `meta_title`, `meta_keywords`, `product_number`, `allow_rma`, `rma_rule_id`. So there is no tax category, no per-product SEO fields and no RMA setting. The older document figures (49 attributes, 199 migrations) were not re-checked.
- A full backup of the local database taken before the change was written outside the repository (scratchpad, 299 KB). It is not kept in git.

## 10. Product Entry Form (external)

A private claude.ai artifact, "Product Entry Form" (`https://claude.ai/artifact/7CsenwhmdD6yLUxxB1rzAG`), builds a product CSV for the Data Transfer importer. It is **not part of this repository**.

- "Device or accessory" and "Lubricant, cleaner or care" are hard-coded in its `FAMILY` constant and map to `manpleasure_core` and `manpleasure_consumable`. Detail fields are in `DETAILS`; category list, option IDs, existing SKUs and URL keys are in `STORE`. All of these go stale when categories or attributes change. **They are stale now:** the form still shows the old 6-category list, so refresh `STORE` and the category rules before using it again.
- It creates simple products only. It states an exchange rate of 1 USD = RM 4.50, a free-delivery threshold of RM 300 and fee tiers. These were **not checked against the configured zone-rate table**, and the owner's notes say there is no free shipping [Unverified, possible conflict].

---

## 11. Deployment [Owner + scripts]

Package the release from a clean worktree, upload and stage it, then the **owner** activates it as root. Every release bumps `release.json`. Hard-won checks (full list in `AGENTS.md`):
- new theme files need `git add -f`, run as its own command, then check with `git ls-files`;
- never ship files under `public/admin` (nginx returns 403 for `/admin/`); test both `/admin` and `/admin/` after a release;
- `git add` explicit paths only; never stage `*.bak`, `route-list.json`, `deploy.bat`;
- config is cached at activation; after editing production `.env` run `config:cache` and restart the queue service.

## 12. Planned work (nothing below is built; nothing has been changed in code or database)

1. **Category restructure: done locally on 2026-10-25, not yet in production.** The approved tree is 8 main categories with at most 3 sub-categories each: Strokers & Masturbators; Rings & Delay; Pumps & Trainers; Prostate & Anal; Condoms; Lubricants & Care; Accessories & Storage; Underwear & Costumes (sub-category names are in `ManPleasureCategorySeeder`). To apply it to production, take a backup, run `php artisan db:seed --class="Database\Seeders\ManPleasureAttributeSeeder" --force` then the same for `ManPleasureCategorySeeder` as part of the owner-run activation, and check that no retired category holds products (the seeder stops and rolls back if one does). Rehearsed on a restored copy of the local database first.
2. **Eight families, one per main category: created locally on 2026-10-26, not yet in production.** The owner approved the field table and decided there is **no KEY flag** (so no KEY badges and no "Show more": every field stays visible, required ones starred). Family codes: `manpleasure_strokers`, `_rings`, `_pumps`, `_prostate`, `_condoms`, `_lubricants`, `_accessories`, `_apparel`. Each has General, Product Details, Description, Shipping, Price and Inventory groups. The two older families (`manpleasure_core`, `manpleasure_consumable`) still exist as legacy because the 3 test products use `manpleasure_core`; retire them at the launch cleanup. Production: run `ManPleasureAttributeSeeder` then `ManPleasureFamilySeeder` after a backup (the family seeder only adds to a family that exists). **Required fields:** the owner decided on 2026-10-26 that Material and Compatibility notes need not be required, so only four fields are required: Volume, Width, Pack size and Fabric (plus Size when variations are on, which Bagisto enforces itself). Everything else, Material included, is optional. Bagisto stores "required" on the field, not the family, so the approved table's other required marks (Material for four families, Compatibility notes for Accessories, Power source for Pumps) were dropped. A non-returnable-hygiene line is still needed on the policy page.
3. **In-admin Add product page: phase 1 built locally on 2026-10-26 (simple products); phase 2 (variations) not built.** The owner picked this over extending the CSV form; the CSV form stays for reference and bulk loading. Design canvas (private claude.ai page, "Add product: two drafts": https://claude.ai/artifact/WCp5Pzf5Jz5DNUW6iMNTxY). Decisions:
   - **Category first**, chosen in a two-column picker with search and per-admin recently-used chips; the category sets the family and product type (editable). Behaviour copied from a marketplace seller screen the owner recorded; the look and data are ours.
   - **Changing category asks first:** the name, price, stock, images and description are kept; fields that belong only to the old family are hidden, not deleted (Bagisto keeps the stored values), and come back if the owner switches back. The dialog names the fields that hide and the new ones to fill in.
   - **Specification section** shows required fields starred and a Fill Rate bar. There is no KEY badge and no "Show more" (owner decision).
   - **Variations** (apparel and any product that needs them): off by default behind an "Enable variations" button; up to two variations; names chosen from the store's configurable fields (Size, Colour), not free-form, so filters keep working; options typed inline in two columns with a spare empty box that adds the next row; an optional explanation per option, stored as a size-guide note; a picture slot on each option of Variation 1, copied to that option's variants; a variation list with a "Set for all" row (price, stock) and one row per combination with price, stock and SKU. **Not included:** GTIN/barcode and days-to-ship columns.
   - The owner ticked both "explanation" and "none of these" for extras; it was read as: keep the explanation, drop GTIN and days-to-ship. Confirm if wrong.
   - Left open: whether to use MagicAI or a keyword rule for suggested categories; the Media Bank picker and "from a link" images for the page; how the page treats products added through the CSV import (the import checks required fields itself).
4. **New Arrivals / On Sale.** The fields now exist on both families (done locally). Still to do: tick `new` on products, add a Product Carousel section filtered on `new` to the home page, and decide whether an "On Sale" collection page is wanted (the product card already shows a Sale badge when a special price is active, but carousels have no sale filter).
5. **Launch cleanup** of product data (section 2): backup with a tested restore, rehearsal on a copy, run by the owner. Keep configuration. Do not use the installer or seeders to reset.
6. Replace placeholder text on policy pages where it remains (the privacy page held placeholders on 2026-10-08) [Unverified now].

## 13. Known issues and limitations

- **Documentation drift:** the tracker and handover disagree with each other and with git (see section 1). They are history.
- **Two failing tests** in `Catalog/CategoryTest` (single and mass delete expect 200, get 409). The controller deliberately returns 409 on the old delete routes ("use the tree"), so the tests are out of date. Pre-existing [Verified].
- **The create-order page can crash.** The WhatsApp panel (`order-whatsapp.blade.php`) is injected through `bagisto.admin.sales.order.right_component.before`, which also fires on the create-order page, where there is no `$order`, so that page errors with "Undefined variable $order". Found by `OrdersTest` on 2026-10-26; a real bug, not yet fixed.
- **Other failing Admin tests (2026-10-26, full suite 818 pass / 6 fail):** the two Category delete tests above; one `OrdersTest` case that expects Stripe first in the payment method list while the store lists Razorpay first (the test is out of date); the create-order case above; and two `DownloadableTest` upload cases that return 500 only when Pest runs as `www-data`, because `storage/app/private/product_downloadable_links` and `storage/app/public/product_downloadable_links` cannot be written by that user (they passed when run as root). None involve the Add product page.
- **Repository rule violations** (all database access must go through a repository): `MediaAssetController` uses the model directly; `ManPleasureAttributeSeeder` and `ManPleasureQaProductSeeder` use the `DB` facade (the category seeder now uses the repository) [Verified]. The Media Bank page and controller also have untranslated English strings.
- **Family groups had no `code`.** The ManPleasure family groups were created without one, and the family edit form requires it, so saving either family in the admin would have failed validation. Fixed by the attribute seeder on 2026-10-25 (local only; production still has the problem until the seeder runs there).
- **Legacy families.** `manpleasure_core` and `manpleasure_consumable` remain only because the 3 test products use `manpleasure_core`; the per-category families replace them. Required is per field in Bagisto, not per family (see section 12.2).
- **Production not updated.** The new category tree, family fields and group codes exist only in the local database. The Product Entry Form is stale (section 10).
- **Media Bank gaps** listed in section 8.
- **`CLAUDE.md` skills:** the upstream `bagisto-*` skills it once named are not installed (`.agents/skills`, `.claude/skills` do not exist). `AGENTS.md` does not depend on them.
- **Working-tree clutter:** untracked `*.bak` files, `deploy.bat`, `route-list.json` (a stale route dump), and a garbled `C:` folder created at the root.
- **Not run on the snapshot date:** Playwright, a Vite build, and any browser check other than the family-group pop-up. The Add product page has never been opened in a browser; only its server side is tested.
- **Single point of knowledge:** the owner-only release activation and server details are not documented in the repository beyond the scripts.

## 14. Git summary [Verified, 2026-10-25]

- Remotes: `origin` (the owner's GitHub fork), `upstream` (bagisto/bagisto).
- Branch `family-group-delete-warning`: one commit ahead of `main` (`779b43d850`), pushed to `origin`, not merged. `main` is at the release bump `f2f0272f7a`.
- Branch `category-setup` (from `docs/project-handover`): the category, attribute and family seeder changes plus `ManPleasureCatalogSeederTest`. **Uncommitted** at the snapshot.
- Branch `docs/project-handover` is stacked on `family-group-delete-warning` and adds one commit with the three documentation files (`AGENTS.md`, `CLAUDE.md`, `PROJECT_CONTEXT.md`). Merge the family branch first, then this one. Pushed to `origin`, not merged.
- Earlier local branches: zone-rate shipping, manual payment instructions, crypto transfer, WhatsApp, discreet emails, currency switcher, admin UI tweaks, and two small fixes.

## 15. Checks run on 2026-10-25 (the only ones)

| Check | Result |
|---|---|
| Pint on the three changed PHP files | pass |
| `php -l` on all changed PHP files and 22 locale files | pass |
| `php artisan bagisto:translations:check` | 357 locale files pass |
| `AttributeFamilyTest` (disposable database) | 17 of 17 pass |
| `ManPleasureCatalogSeederTest` (10 tests, disposable database) | 10 of 10 pass; together with `AttributeFamilyTest` 27 of 27 |
| `ProductEntryTest` (9 tests, disposable database) | 9 of 9 pass |
| `ProductEditorChromeTest` (2 tests) and the simple-product editor tests | 19 of 19 pass together with `ProductEntryTest`; the rendered editor has no blueprint markup and no fixed-position bar; the bar script passes `node --check` |
| Add product page: Vue template compiled with `@vue/compiler-dom`, script checked with `node --check` | 0 errors; not run in a browser |
| Whole `packages/Webkul/Admin/tests/Feature` suite (disposable database, as `www-data`) | 818 pass, 6 fail (see section 13); none related to the Add product page |
| Seeders run on a restored copy of the local database, then on the real local database | tree, names, slugs, group codes and family fields verified by query; storefront `/`, `/condoms`, `/underwear-costumes` and `/admin/login` return 200 |
| `packages/Webkul/Admin/tests/Feature/Catalog` | 182 pass, 2 fail (the known-stale Category tests) |
| Browser check of the group-delete pop-up | passed, by the owner |
| Playwright, full Pest suite, Vite build | **not run** |

## 16. Recommended next steps

1. Review and merge `family-group-delete-warning`, then release the usual way (owner activates).
2. Merge `docs/project-handover` after step 1.
3. Refresh the Product Entry Form from the new tree and make the category decide the family and product type (section 12.3).
3a. Apply the category tree and family changes to production through the owner-run release (section 12.1), after a backup.
3b. Build the in-admin Add product page (section 12.3), then refresh the Product Entry Form from the new families and category tree.
4. Decide whether to add the 6 remaining system attributes (tax category, meta title/keywords, product number, RMA) to the eight families.
5. Retire the stale tracker/handover so there is one source of progress (this file plus git).
6. Add tests for payments and receipts, WhatsApp, Media Bank, age gate, login page builder and the updater; fix the two stale Category tests.
7. Fix the Media Bank gaps: in-use delete protection, translations, repository, URL import.
8. Verify the Product Entry Form's delivery-fee and free-shipping text against the real zone-rate table and the policy pages.
9. Plan the launch cleanup with a backup, a restore test and a rehearsal.

## 17. Progress log

Add one row after every significant change: date, what changed, files, checks run with results, open items. Newest first. No secrets. Do not claim a check passed unless it was run.

| Date | Change | Files | Checks run | Open items |
|---|---|---|---|---|
| 2026-10-26 | Fixed the create-order page crash ("Undefined variable $order"): the WhatsApp panel is now added only when the event carries an order | `ManPleasureAdminServiceProvider` | Pint, php -l, `OrdersTest` 8 of 8 pass on a disposable database (including create-order) | Uncommitted on `category-setup`; the other known failures in section 13 may need updating |
| 2026-10-26 | Integrated the "Executive Precision" design export: Add product page redrawn (Cost with margin, daily deal with Forever/Valid dates, storefront preview, audit panel); new Pricing & Daily Deals modal on the product list (simple and virtual products); row buttons "Edit pricing" and "View on storefront"; `cost` added to the Price group of all 8 families and seeded into the local database | `product-entry/index.blade.php`, `product-pricing-modal.blade.php`, `stitch-product-grid.blade.php`, `ProductPricingController`, `ProductPricingRequest`, `ProductEntryRequest`, `ProductEntryCatalog`, `ManPleasureFamilySeeder`, `routes/manpleasure-product-entry.php`, ACL key `catalog.product_pricing`, 22 `product-entry.php` (82 keys) and `product-pricing.php` (37 keys), `ProductEntryTest` (10), `ProductPricingTest` (6) | Pint, php -l, 18 tests pass, translation check, Vue templates compile with 0 errors, script syntax check, Cost verified by query in all 8 local families (backup taken first) | Not opened in a browser; deal start and end dates depend on the price indexer schedule; machine-written translations (Sinhala, Bengali, Persian first) need native review; uncommitted on `category-setup` |
| 2026-10-26 | Product editor cleanup after the owner found it cluttered: blueprint panel removed, action bar sticky and translated, Media Bank added to the Catalog menu | `product-editor-actions.blade.php` (rewritten), `product-editor-blueprint.blade.php` (deleted), `ManPleasureAdminServiceProvider`, menu and ACL configs, 22 `product-editor.php`, `ProductEditorChromeTest` | Pint, php -l, 19 tests pass, translation check, script syntax check | Not yet seen in a browser; uncommitted on `category-setup` |
| 2026-10-26 | Ran the whole Admin feature suite after the Add product page and recorded the 6 failures and their causes | `PROJECT_CONTEXT.md` | Full Admin suite: 818 pass, 6 fail | Create-order page crash (WhatsApp panel) not fixed; two out-of-date tests; storage permissions for the downloadable tests |
| 2026-10-26 | In-admin Add product page, phase 1 (simple products): controller, request, catalog helper, routes, menu and permission, Vue page, 79 strings x 22 locales | `ProductEntryController`, `ProductEntryRequest`, `ProductEntryCatalog`, `product-entry/index.blade.php`, `routes/manpleasure-product-entry.php`, `config/manpleasure-product-entry*.php`, 22 `product-entry.php`, `ProductEntryTest` | Pint, php -l, 9 new tests pass, translation check, Vue template compile and script syntax check | Never opened in a browser; variations and Media Bank picker not built; uncommitted on `category-setup` |
| 2026-10-26 | Material and Compatibility notes made optional (only Volume, Width, Pack size, Fabric required); applied locally; design board updated | `ManPleasureAttributeSeeder`, `ManPleasureCatalogSeederTest` | 10 seeder tests pass on a disposable database; flags verified by query on the real local database | Uncommitted on `category-setup`; not in production |
| 2026-10-26 | Eight per-category product families, eight new fields and options, required flags, applied to the local database through repeatable seeders; no KEY flag; design board updated | `ManPleasureFamilySeeder`, `ManPleasureAttributeSeeder`, `ManPleasureCatalogSeederTest` | Pint, php -l, 27 tests pass on a disposable database, rehearsal on a restored copy, query and HTTP checks on the real local database | Uncommitted on `category-setup`; not in production; in-admin page not built; CSV form stale |
| 2026-10-25 | Category tree (8 main, 23 sub) and New/Featured/Special Price fields applied to the local database through repeatable seeders; family group codes fixed; 7 new tests | `ManPleasureCategorySeeder`, `ManPleasureAttributeSeeder`, `ManPleasureCatalogSeederTest` | Pint, php -l, 24 tests pass on a disposable database, rehearsal on a restored copy, query and HTTP checks on the real local database | Uncommitted on branch `category-setup`; not applied to production; Product Entry Form stale; apparel family not built |
| 2026-10-25 | Rewrote this file as a full hand-over (start-here guide, work log from the 37 project commits, availability map); rewrote `AGENTS.md` for this project only; trimmed `CLAUDE.md` to point at both | `PROJECT_CONTEXT.md`, `AGENTS.md`, `CLAUDE.md` | Documentation only; referenced paths, routes and classes were checked to exist | Items marked [Unverified]; committed on branch `docs/project-handover`, not merged |
| 2026-10-25 | Warn before deleting an attribute family group; server guard against orphaned system attributes; transactional family update; 4 strings x 22 locales | `AttributeFamilyController`, `AttributeFamilyRepository`, `families/edit.blade.php`, `AttributeFamilyTest`, 22 admin `app.php` | Section 15 | Not merged; Playwright not run |
