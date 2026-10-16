const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { createRequire } = require('node:module');

const project = path.resolve(__dirname, '../..');
const packageRequire = createRequire(path.join(project, 'packages/Webkul/Admin/package.json'));
const baseURL = 'http://localhost:8088';
const results = [];
let verificationPage;
let authenticationLost = false;

/**
 * Measure the sidebar against a fixed viewport reference, including scrollbar space.
 */
function measureRtlSidebar(report = false) {
    const sidebar = document.querySelector('.mp-admin-sidebar');
    const reference = document.createElement('span');

    reference.setAttribute('aria-hidden', 'true');
    reference.style.cssText = 'all:initial;position:fixed;top:0;right:0;width:1px;height:1px;visibility:hidden;pointer-events:none';
    document.documentElement.append(reference);

    try {
        const bounds = sidebar?.getBoundingClientRect();
        const expectedRight = reference.getBoundingClientRect().right;
        const direction = sidebar ? getComputedStyle(sidebar).direction : null;
        const aligned = Boolean(bounds && bounds.width > 0 && bounds.height > 0
            && direction === 'rtl' && Math.abs(bounds.right - expectedRight) <= 2);

        return report ? {
            aligned,
            viewportWidth: window.innerWidth,
            documentWidth: document.documentElement.clientWidth,
            expectedRight,
            sidebar: bounds ? { left: bounds.left, right: bounds.right, width: bounds.width, height: bounds.height } : null,
            direction,
        } : aligned;
    } finally {
        reference.remove();
    }
}

/**
 * Require the circular control to straddle the sidebar's outer logical edge.
 */
async function verifyEdgePlacement(page) {
    const metrics = await page.locator('.mp-sidebar-edge-toggle').evaluate(button => {
        const sidebar = button.closest('.mp-admin-sidebar');
        const bounds = sidebar.getBoundingClientRect();
        const control = button.getBoundingClientRect();
        const isRtl = getComputedStyle(sidebar).direction === 'rtl';

        return {
            edge: isRtl ? bounds.left : bounds.right,
            center: control.left + control.width / 2,
            topOffset: control.top - bounds.top,
            width: control.width,
            height: control.height,
        };
    });

    assert(Math.abs(metrics.edge - metrics.center) <= 1, `Edge control is misaligned: ${JSON.stringify(metrics)}`);
    assert(metrics.topOffset >= 0 && metrics.topOffset <= 64, `Edge control is outside the upper sidebar: ${JSON.stringify(metrics)}`);
    assert(metrics.width >= 28 && Math.abs(metrics.width - metrics.height) <= 1);
}

/**
 * Check pointer discovery and visible keyboard focus without changing collapse state.
 */
async function verifyEdgeDiscovery(page) {
    const control = page.locator('.mp-sidebar-edge-toggle');

    await verifyEdgePlacement(page);
    await control.hover();
    await page.waitForFunction(() => Number(getComputedStyle(document.querySelector('.mp-sidebar-edge-toggle')).opacity) >= .99);
    await page.mouse.move(720, 200);

    if (await page.evaluate(() => matchMedia('(hover: hover) and (pointer: fine)').matches)) {
        await page.waitForFunction(() => Number(getComputedStyle(document.querySelector('.mp-sidebar-edge-toggle')).opacity) <= .01);
    }

    await page.keyboard.press('Tab');
    await control.focus();
    await page.waitForFunction(() => Number(getComputedStyle(document.querySelector('.mp-sidebar-edge-toggle')).opacity) >= .99);
    assert(await control.evaluate(button => button === document.activeElement));
}

/**
 * Check both native activation keys and return the rail to its expanded state.
 */
async function verifyEdgeKeyboard(page) {
    const control = page.locator('.mp-sidebar-edge-toggle');

    for (const key of ['Enter', 'Space']) {
        await control.press(key);
        await page.waitForFunction(() => Math.abs(document.querySelector('.mp-admin-sidebar').getBoundingClientRect().width - 70) <= 1);
        assert.equal(await control.getAttribute('aria-expanded'), 'false');
        await verifyEdgePlacement(page);
        await control.press(key);
        await page.waitForFunction(() => Math.abs(document.querySelector('.mp-admin-sidebar').getBoundingClientRect().width - 270) <= 1);
        assert.equal(await control.getAttribute('aria-expanded'), 'true');
    }
}

/**
 * Wait for the RTL layout and restore the original document direction after every result.
 */
async function verifyRtlSidebar(page) {
    await page.setViewportSize({ width: 1440, height: 1000 });
    const originalDirection = await page.locator('html').getAttribute('dir');

    try {
        await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));

        try {
            await page.waitForFunction(measureRtlSidebar, false, { timeout: 5000 });
        } catch {
            const measurements = await page.evaluate(measureRtlSidebar, true);

            assert.fail(`RTL sidebar alignment did not settle: ${JSON.stringify(measurements)}`);
        }

        const measurements = await page.evaluate(measureRtlSidebar, true);

        assert(measurements.aligned, `RTL sidebar alignment changed: ${JSON.stringify(measurements)}`);
        await verifyEdgePlacement(page);
        console.log(`RTL measurements: ${JSON.stringify(measurements)}`);
    } finally {
        await page.evaluate(direction => {
            if (direction === null) {
                document.documentElement.removeAttribute('dir');
            } else {
                document.documentElement.setAttribute('dir', direction);
            }
        }, originalDirection);
    }
}

/**
 * Require the authenticated admin shell before accepting an application result.
 */
async function requireAuthentication() {
    await verificationPage.locator('.mp-admin-shell [data-mp-sidebar-toggle]').waitFor({ state: 'attached', timeout: 5000 });
    const url = new URL(verificationPage.url());

    assert(url.origin === baseURL && url.pathname.startsWith('/admin/')
        && ! /^\/admin\/(login|logout)\/?$/.test(url.pathname), 'The browser is no longer on an authenticated admin page.');
}

/**
 * Resolve the project's existing browser test dependency without installing one.
 */
function loadBrowserTools() {
    for (const name of ['playwright', '@playwright/test']) {
        try {
            return packageRequire(name);
        } catch (error) {
            if (error.code !== 'MODULE_NOT_FOUND') {
                throw error;
            }
        }
    }

    throw new Error('Existing Admin Playwright dependency is unavailable. No package was installed.');
}

/**
 * Record each check independently without writing account data or credentials.
 */
async function check(name, action) {
    if (authenticationLost) {
        results.push({ name, status: 'blocked', message: 'Authentication was lost earlier in this run.' });
        console.log(`BLOCKED: ${name}: Authentication was lost earlier in this run.`);

        return;
    }

    try {
        await requireAuthentication();
        await action();
        await requireAuthentication();
        results.push({ name, status: 'passed' });
        console.log(`PASS: ${name}`);
    } catch (error) {
        results.push({ name, status: 'failed', message: error.message });
        console.log(`FAIL: ${name}: ${error.message}`);

        try {
            await requireAuthentication();
        } catch {
            authenticationLost = true;
            console.log('Authentication was lost. Remaining checks are blocked; rerun and log in again.');
        }
    }
}

/**
 * Verify responsive navigation through a manually authenticated local session.
 */
async function run() {
    const { chromium } = loadBrowserTools();
    let browser;

    try {
        browser = await chromium.launch({ channel: 'chrome', headless: false });
    } catch {
        browser = await chromium.launch({ headless: false });
    }

    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    verificationPage = page;
    page.setDefaultTimeout(10000);
    const pageErrors = [];
    const failedLocalResponses = [];

    console.log('Log in yourself in the opened LOCAL browser window within three minutes.');
    console.log('The runner does not read, print or save your password or browser cookies.');
    try {
        await page.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('.mp-admin-shell [data-mp-sidebar-toggle]', { timeout: 180000 });
    } catch (error) {
        await browser.close();

        throw error;
    }

    page.on('pageerror', error => pageErrors.push(error.message));
    page.on('response', response => {
        const url = new URL(response.url());

        if (url.origin === baseURL && response.status() >= 400) {
            failedLocalResponses.push(`${response.status()} ${url.pathname}`);
        }
    });

    try {
        await check('Dark theme is the default in a fresh browser context', async () => {
            assert(await page.locator('html').evaluate(element => element.classList.contains('dark')));
        });

        await check('Expanded desktop sidebar and footer fit the viewport', async () => {
            const box = await page.locator('.mp-admin-sidebar').boundingBox();

            assert(box && Math.abs(box.width - 270) <= 1);
            assert(box.y + box.height <= 1001);
            const footer = await page.locator('.mp-admin-sidebar__footer').boundingBox();

            assert(footer && footer.y + footer.height <= box.y + box.height + 1);
            assert(await page.locator('[data-mp-sidebar-toggle]').isVisible());
            await page.locator('[data-mp-sidebar-toggle]').click({ trial: true });
        });

        await check('Edge chevron appears on hover and keyboard focus', () => verifyEdgeDiscovery(page));
        await check('Edge chevron supports Enter and Space in both sidebar states', () => verifyEdgeKeyboard(page));

        await check('Sidebar collapses and persists after reload', async () => {
            await page.locator('[data-mp-sidebar-toggle]').click();
            await page.waitForFunction(() => Math.abs(document.querySelector('.mp-admin-sidebar').getBoundingClientRect().width - 70) <= 1);
            await page.reload({ waitUntil: 'domcontentloaded' });
            await page.waitForSelector('.mp-admin-shell.sidebar-collapsed');
            assert.equal(await page.locator('[data-mp-sidebar-toggle]').getAttribute('aria-expanded'), 'false');
        });

        await check('Collapsed disclosure expands the rail and exposes its submenu', async () => {
            const disclosure = page.locator('[data-mp-navigation="desktop"] [data-mp-disclosure]').first();

            assert(await disclosure.count(), 'No permitted expandable menu exists for this account.');
            await disclosure.click();
            await page.waitForSelector('.mp-admin-shell.sidebar-not-collapsed');
            assert.equal(await disclosure.getAttribute('aria-expanded'), 'true');
            const panel = await disclosure.getAttribute('aria-controls');

            assert(await page.locator(`#${panel}`).isVisible());
        });

        await check('Enter and Escape operate submenus and restore focus', async () => {
            const disclosure = page.locator('[data-mp-navigation="desktop"] [data-mp-disclosure]').first();

            await disclosure.focus();

            if (await disclosure.getAttribute('aria-expanded') === 'true') {
                await disclosure.press('Enter');
            }

            await disclosure.press('Enter');
            assert.equal(await disclosure.getAttribute('aria-expanded'), 'true');
            await disclosure.press('Escape');
            assert.equal(await disclosure.getAttribute('aria-expanded'), 'false');
            assert(await disclosure.evaluate(element => element === document.activeElement));
        });

        await check('Native product navigation still reaches the product listing', async () => {
            const products = page.locator('[data-mp-navigation="desktop"] a[href*="/catalog/products"]').first();

            assert(await products.count(), 'Products menu is unavailable to this account.');
            const group = products.locator('xpath=ancestor::*[@data-mp-group][1]');
            const disclosure = group.locator('[data-mp-disclosure]');

            if (! await products.isVisible() && await disclosure.count()) {
                await disclosure.click();
            }

            await products.click();
            await page.waitForURL(url => url.origin === baseURL && url.pathname.includes('/catalog/products'));
            await page.waitForSelector('.mp-admin-shell');
        });

        for (const width of [1024, 768, 375, 320]) {
            await check(`Navigation fits ${width}px without horizontal page overflow`, async () => {
                await page.setViewportSize({ width, height: 900 });

                assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1));

                if (width >= 1024) {
                    assert(await page.locator('.mp-admin-sidebar').isVisible());
                } else {
                    assert(! await page.locator('.mp-admin-sidebar').isVisible());
                    await page.locator('.mp-admin-drawer-toggle').click();
                    const mobile = page.locator('[data-mp-navigation="mobile"]');

                    await mobile.waitFor({ state: 'visible' });
                    assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1));
                    const disclosure = mobile.locator('[data-mp-disclosure]').first();
                    const before = await disclosure.getAttribute('aria-expanded');

                    await disclosure.click();
                    assert.notEqual(await disclosure.getAttribute('aria-expanded'), before);
                    await page.goto(`${baseURL}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
                    await page.waitForSelector('.mp-admin-shell');
                }
            });
        }

        await check('Sidebar uses logical alignment for RTL layouts', () => verifyRtlSidebar(page));

        await check('Theme switch works and a saved light preference survives reload', async () => {
            await page.locator('[data-mp-theme-toggle]').click();
            assert(! await page.locator('html').evaluate(element => element.classList.contains('dark')));
            await page.reload({ waitUntil: 'domcontentloaded' });
            await page.waitForSelector('[data-mp-theme-toggle]');
            assert(! await page.locator('html').evaluate(element => element.classList.contains('dark')));
            await page.locator('[data-mp-theme-toggle]').click();
        });

        await check('No page errors or failed local HTTP responses', async () => {
            assert.deepEqual(pageErrors, []);
            assert.deepEqual(failedLocalResponses, []);
        });
    } finally {
        const folder = path.join(project, 'local-artifacts/admin-navigation', new Date().toISOString().replace(/[:.]/g, '-'));

        fs.mkdirSync(folder, { recursive: true });
        fs.writeFileSync(path.join(folder, 'browser-results.json'), JSON.stringify({ scope: 'Local authenticated UI; no catalog or configuration changes', results }, null, 2));
        console.log(`Evidence: ${folder}`);
        console.log(`Browser result: ${results.filter(result => result.status === 'passed').length} passed, ${results.filter(result => result.status === 'failed').length} failed, ${results.filter(result => result.status === 'blocked').length} blocked.`);
        await browser.close();
    }

    if (results.some(result => result.status !== 'passed')) {
        process.exitCode = 1;
    }
}

run().catch(error => {
    console.error(`VERIFICATION STOPPED: ${error.message}`);
    process.exitCode = 1;
});
