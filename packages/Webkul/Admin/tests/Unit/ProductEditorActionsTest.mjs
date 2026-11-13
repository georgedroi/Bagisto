import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { chromium } from '@playwright/test';

const source = await readFile(new URL('../../../../../app/Resources/views/product-editor-actions.blade.php', import.meta.url), 'utf8');
const script = source.match(/<script type="module">([\s\S]*?)<\/script>/)[1]
    .replace(/@js\(trans\('([^']+)'\)\)/g, (_, key) => JSON.stringify(key));

for (const action of ['draft', 'publish', 'preview']) {
    test(`${action} still works after Vue replaces the original product form`, async () => {
        const browser = await chromium.launch({ headless: true });

        try {
            const page = await browser.newPage();
            await page.setContent(`<form>
                <input type="checkbox" name="status" value="1" ${action === 'draft' ? 'checked' : ''}>
                <div data-editor-actions>
                    <p data-editor-message>Ready</p>
                    <button type="button" data-editor-action="${action}">${action}</button>
                </div>
            </form>`);
            await page.addScriptTag({ content: script, type: 'module' });
            await page.evaluate(() => {
                const original = document.querySelector('form');
                original.outerHTML = original.outerHTML;
                window.submissions = [];
                window.statusChanges = 0;
                document.querySelector('[name="status"]').addEventListener('change', () => window.statusChanges++);
                document.addEventListener('submit', event => {
                    event.preventDefault();
                    window.submissions.push(Object.fromEntries(new FormData(event.target)));
                });
            });
            await page.locator(`[data-editor-action="${action}"]`).click();

            const state = await page.evaluate(() => ({
                submissions: window.submissions,
                changes: window.statusChanges,
                message: document.querySelector('[data-editor-message]').textContent,
            }));

            if (action === 'preview') {
                assert.deepEqual(state.submissions, []);
                assert.equal(state.message, 'admin::product-editor.save-first');
            } else {
                assert.equal(state.submissions.length, 1);
                assert.equal(state.submissions[0].status ?? '0', action === 'draft' ? '0' : '1');
                assert.equal(state.changes, 1);
            }
        } finally {
            await browser.close();
        }
    });
}
