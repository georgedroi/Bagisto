const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { createRequire } = require('node:module');

const project = path.resolve(__dirname, '../..');
const packageRequire = createRequire(path.join(project, 'packages/Webkul/Admin/package.json'));
const baseURL = 'http://localhost:8088';
const target = `${baseURL}/admin/settings/system-updates`;
const evidence = path.join(project, 'local-artifacts/system-updates', new Date().toISOString().replaceAll(':', '-'));
const results = [];

/**
 * Reuse existing browser tooling without installing project dependencies.
 */
function browserTools() {
    for (const name of ['playwright', '@playwright/test']) {
        try {
            return packageRequire(name);
        } catch (error) {
            if (error.code !== 'MODULE_NOT_FOUND') {
                throw error;
            }
        }
    }

    throw new Error('Existing Admin Playwright tooling is unavailable. No dependency was installed.');
}

/**
 * Exercise local update controls without submitting an install or rollback action.
 */
async function main() {
    const { chromium } = browserTools();
    let browser;

    try {
        browser = await chromium.launch({ channel: 'chrome', headless: false });
    } catch {
        browser = await chromium.launch({ headless: false });
    }

    fs.mkdirSync(evidence, { recursive: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const errors = [];
    let authenticationLost = false;

    /**
     * Reject false positives after a session expires or the owner gate denies access.
     */
    async function authenticated() {
        assert(new URL(page.url()).origin === baseURL);
        assert(!new URL(page.url()).pathname.includes('/login'));
        assert(await page.locator('[data-system-updates]').isVisible(), 'The update panel is not accessible to this session.');
    }

    /**
     * Record actual application checks separately from the explicitly mocked UI response.
     */
    async function check(name, action, needsAuthentication = true) {
        if (authenticationLost && needsAuthentication) {
            results.push({ name, status: 'blocked', message: 'Owner authentication was lost.' });
            console.log(`BLOCKED: ${name}`);

            return;
        }

        try {
            if (needsAuthentication) {
                await authenticated();
            }

            await action();

            if (needsAuthentication) {
                await authenticated();
            }

            results.push({ name, status: 'passed' });
            console.log(`PASS: ${name}`);
        } catch (error) {
            results.push({ name, status: 'failed', message: error.message });
            console.log(`FAIL: ${name}: ${error.message}`);

            if (needsAuthentication) {
                try {
                    await authenticated();
                } catch {
                    authenticationLost = true;
                }
            }
        }
    }

    try {
        await check('Guest access redirects to the admin login', async () => {
            await page.goto(target, { waitUntil: 'domcontentloaded' });
            assert(new URL(page.url()).pathname.endsWith('/admin/login'));
        }, false);
        console.log('Log in yourself in the opened LOCAL browser within three minutes.');
        console.log('This verifier never submits Install or Rollback and does not read or save passwords or cookies.');
        await page.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('.mp-admin-shell [data-mp-sidebar-toggle]', { timeout: 180000 });
        const response = await page.goto(target, { waitUntil: 'networkidle' });
        assert.equal(response.status(), 200, 'The configured owner must have full admin access. Default permitted owner ID is 1.');
        await authenticated();
        page.on('pageerror', error => errors.push(error.message));

        await check('Native Settings menu links to the protected update panel', async () => {
            assert(await page.locator(`a[href="${target}"]`).count() > 0);
        });
        await check('Local install and rollback are disabled; CSRF fields are present', async () => {
            assert(await page.locator('[data-update-form="install"] button').isDisabled());
            assert(await page.locator('[data-update-form="rollback"] button').isDisabled());
            assert.equal(await page.locator('[data-update-form] input[name="_token"]').count(), 3);
        });
        await check('Real release check returns a valid application response', async () => {
            const resultPromise = page.waitForResponse(response => response.url() === `${target}/check` && response.request().method() === 'POST', { timeout: 70000 });
            await page.locator('[data-update-form="check"] button').click();
            const response = await resultPromise;
            assert.equal(response.status(), 200, 'Release access failed. Check repository visibility or configure a read-only token locally; no live update was attempted.');
            const body = await response.json();
            assert.equal(typeof body.message, 'string');
            assert(Object.hasOwn(body, 'candidate'));
            await page.waitForFunction(() => !document.querySelector('[data-update-form="check"] button').disabled);
        });
        await check('Operation polling cannot inspect an operation outside this session', async () => {
            const response = await context.request.get(`${target}/operations/9270bfcf-eb71-4ae1-ac57-b1020f0a9156`, { headers: { Accept: 'application/json' } });
            assert.equal(response.status(), 404);
        });
        await check('Mocked release notes remain plain text and cannot enable local installation', async () => {
            await page.route(`${target}/check`, route => route.fulfill({ contentType: 'application/json', body: JSON.stringify({
                message: 'UI fixture only', capabilities: { ready: false, busy: false, rollback: false },
                candidate: { version: '2026.10.17.1', token: 'x'.repeat(40), available: true, notes: '<img src=x onerror="window.mpUpdateNotesExecuted=true">' },
            }) }));

            try {
                await page.locator('[data-update-form="check"] button').click();
                await page.locator('[data-notes-card]').waitFor({ state: 'visible' });
                assert((await page.locator('[data-release-notes]').textContent()).includes('<img'));
                assert.equal(await page.evaluate(() => window.mpUpdateNotesExecuted), undefined);
                assert(await page.locator('[data-update-form="install"] button').isDisabled());
                assert(await page.locator('[data-update-form="rollback"] button').isDisabled());
            } finally {
                await page.unroute(`${target}/check`);
            }
        });
        for (const width of [1440, 1024, 768, 375, 320]) {
            await check(`Update panel fits ${width}px without page overflow`, async () => {
                await page.setViewportSize({ width, height: 1000 });
                await page.waitForTimeout(400);
                assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
                await page.screenshot({ path: path.join(evidence, `panel-${width}.png`) });
            });
        }
        await check('No application page errors occurred', async () => assert.deepEqual(errors, []));
    } catch (error) {
        results.push({ name: 'Owner login and update-panel acceptance', status: 'blocked', message: error.message });
        console.log(`BLOCKED: ${error.message}`);
    } finally {
        fs.writeFileSync(path.join(evidence, 'browser-results.json'), JSON.stringify({ scope: 'Local UI and read-only release check; no server deployment, install or rollback', results }, null, 2));
        await browser.close();
    }

    const counts = ['passed', 'failed', 'blocked'].map(status => results.filter(result => result.status === status).length);
    console.log(`Evidence: ${evidence}`);
    console.log(`Browser result: ${counts[0]} passed, ${counts[1]} failed, ${counts[2]} blocked.`);
    process.exitCode = counts[1] || counts[2] ? 1 : 0;
}

main().catch(error => { console.error(error.message); process.exitCode = 1; });
