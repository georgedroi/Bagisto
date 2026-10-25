# AGENTS.md — MAN SECRET PLEASURE (Bagisto)

Rules for any AI coding session in `D:\CHATGPT PROJECTS\Bagisto`.

- **Read `PROJECT_CONTEXT.md` first** for the facts: stack, environment, feature status, known issues, next steps.
- **Update its progress log last**, and fix any section your change made untrue. It is the only progress file; `BAGISTO_PROJECT_AGENT_TRACKER.md` and `CODEX_HANDOVER.md` are stale history.
- Never claim a check passed unless you ran it.

## The project

A male-focused adult wellness store on Bagisto 2.4.12 (Laravel 12, PHP 8.3, Vue 3, Tailwind 3, Vite 6), live at `mansecretpleasure.com`. Tone: premium, discreet, wellness-oriented, never pornographic. The Laravel app is a thin shell; nearly all behaviour is in the 41 packages under `packages/Webkul/`. Project-specific code is in `app/`, `config/manpleasure-*.php`, `resources/themes/manpleasure/` and the modified packages (mainly Admin, Shop, Payment, Shipping, Category).

## Environment

- Docker is the real environment: `docker compose -f compose.local.yaml up -d`. Storefront `http://localhost:8088`, admin `/admin/login`. The root `docker-compose.yml` is upstream's and is not used.
- Run artisan and Composer in the `app` container as `-u www-data -e HOME=/tmp`. Root creates root-owned caches and the site then returns 500.
- The database host `database` exists only inside Docker. A Windows MySQL on port 3306 is not the project database.
- After any config or code change run `php artisan optimize:clear` in the container.
- Frontend assets build per package (`cd packages/Webkul/Admin|Shop|Installer && npm run build`), not from the root. Run the build after any frontend change, then re-check in a browser; a stale bundle makes a good change look broken.

## Never do these unasked

- `bagisto:install`, `migrate:fresh`, `db:wipe`, `db:seed` on real data, `composer update`, `composer create-project`, `docker compose down -v`. Each wipes data or changes the locked dependencies.
- Point tests at the real database. The suite runs in transactions against whatever `.env` names and assumes a fresh install. Use a disposable database (recipe in `PROJECT_CONTEXT.md` section 3). SQLite does not work.
- Edit `.env`, print secrets, or commit `.env*` files, keys, wallet addresses, bank details or credentials.
- Clean, reset or bulk-delete product data. At launch the owner clears **product data only** and keeps system configuration; scope and timing are theirs.
- Deploy, push or merge. Releases are built by script and **activated by the owner**.
- Edit `vendor/`, `node_modules/`, `composer.lock`, `package-lock.json`, `storage/`, `public/themes/*/*/build/` (Vite output), or `*.hot`.

## Before you edit

- Read the files you will change, their callers and their tests. Search for an existing component, repository, helper or Blade partial before writing a new one.
- Check `git status` and `git log -5`. The tree may hold the owner's uncommitted work: never reset, stash, discard or reformat it. Leave untracked files (`*.bak`, `deploy.bat`, `route-list.json`, the stray `C:` folder) alone.
- Make the smallest change that works. Extend the existing Media Bank, theme sections, login page builder and product editor instead of building parallel versions. Prefer providers, events and theme overrides over core edits. Packages here diverge from upstream, so read the local copy, not upstream docs.
- Where a convention and the surrounding code genuinely disagree, match the code and say so in your summary.

## Code rules (non-negotiable)

- **All database access goes through a repository.** The one exception is a DataGrid's `prepareQueryBuilder()`.
- **No comments inside method bodies**, in PHP, Blade, JS or Vue. Only a docblock above the class, method or property.
- **Every user-facing string goes through `trans()`**, with the key added to all **22** locales; verify with `php artisan bagisto:translations:check`.
- **No `env()` outside `config/`.** It returns null once config is cached.
- **No Composer or npm dependency added or removed** without approval.
- **No documentation files** except `PROJECT_CONTEXT.md`, unless asked.
- **A pre-existing violation in a file you touch is yours:** scan the whole class (member order, docblocks, repository use) and fix what is wrong. Known ones: `MediaAssetController` queries the model directly; the `ManPleasure*` seeders use the `DB` facade.
- **Provider chain:** a package registers its main provider in `bootstrap/providers.php`, and a package with models registers its `ModuleServiceProvider` in `config/concord.php` too. Never edit either file without understanding the chain; removing a provider breaks the module. Models use the Contract/Model/Proxy trio.

## Database, validation, authorization, security

- Every schema change is a new, reversible migration with a working `down()`. Never edit a migration that has run. Check foreign keys and cascades first (deleting an attribute group cascades only its mapping rows). Wrap multi-step writes in a transaction.
- Validate every request with a FormRequest or `validate()` rules. Authorize through the admin ACL and the project ACL configs (`config/manpleasure-*-acl.php`). Owner-only actions, such as the in-admin updater, use `RequireUpdateOwner`.
- Treat uploads as hostile: allow-list JPG, PNG, WEBP, MP4 by extension and MIME type, never keep executable files, sanitize SVG, store under `storage`, and block deleting media that is in use. Download URL imports to local storage; never hotlink.
- Escape output with `{{ }}`. Use `{!! !!}` only for sanitized HTML.
- Payments are manual only: bank transfer, DuitNow QR, crypto. Do not add or enable payment gateways, EU selling or unofficial WhatsApp automation. Crypto addresses go in through the config repository after checksum validation; never ask for or accept a seed phrase.

## Storefront and catalog

- The storefront is the `manpleasure` theme (`resources/themes/manpleasure`). Design mobile-first: it must work at phone width with no horizontal scroll, readable text and thumb-sized tap targets. No explicit imagery; product names stay neutral in emails and shipping labels.
- Do not assume a fashion store. Apparel (Underwear & Costumes) is planned, not built; when built, use configurable products with one SKU and stock count per size/colour variant.
- Catalog design is in `docs/MANPLEASURE_CATALOG_ARCHITECTURE.md`. The two families are `manpleasure_core` and `manpleasure_consumable`; the category decides the family. Changing a category, family or attribute option means updating the external Product Entry Form's lists too (`PROJECT_CONTEXT.md` section 6).

## Git, release and deployment

- Work on a branch, never directly on `main`. Commit only when the owner asks, in the existing style (a short imperative sentence, no prefixes), with the attribution line the session requires.
- `git add` explicit paths, never a directory. Never stage `*.bak`, `route-list.json` or `deploy.bat`.
- `resources/themes/.gitignore` is `*`: new theme files need `git add -f`, run as its own command, then confirm with `git ls-files <path>`. Do not combine it with a normal `git add` in one command.
- Never ship anything under `public/admin`: a real directory there makes nginx return 403 for `/admin/`. After a release test both `/admin` and `/admin/`, not just `/admin/login`.
- Config is cached at activation; after editing production `.env` run `config:cache` and restart the queue service. `scripts/deploy/check-production.php` blocks activation while a listed payment method is enabled.

## Testing and debugging

- Run the gates that apply and name them: Pint (`vendor/bin/pint --test <files>`), the relevant Pest files, `php artisan bagisto:translations:check`, and Playwright for storefront/admin flows. A gate you did not run counts as failed.
- Start a bug fix with a failing test or a reproduction; show the fix makes it pass. Prove a guard works by showing the unsafe case is refused.
- Debug from evidence: `storage/logs`, container logs and the failing request. Never say a customer sees something until you have read or rendered the view.
- Two `Catalog/CategoryTest` delete tests are known-stale (they expect 200; the controller returns 409 by design). Report them as pre-existing, not as new failures.

## Reporting back

End every task with: the files you changed, the checks you ran with their real results, anything you could not run and why, remaining issues, and what you changed in `PROJECT_CONTEXT.md`. If code and a document disagree, say which you followed.
