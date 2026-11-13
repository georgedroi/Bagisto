import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../../../../resources/themes/manpleasure/views/checkout/onepage/index.blade.php', import.meta.url), 'utf8');
const script = source.match(/<script type="module">([\s\S]*?)<\/script>/)[1].replace(/{{[\s\S]*?}}/g, 'qa-route');
let definition;
vm.runInNewContext(script, {
    app: { component(name, component) { definition = component; } },
    window: { location: { href: '' } },
});

function checkout() {
    const state = Object.assign(definition.data(), definition.methods);
    state.getCart = () => {};
    state.cart = { payment_method: 'duitnowqr_manual' };

    return state;
}

test('withholds order submission until payment selection has finished saving', () => {
    const state = checkout();
    state.stepForward('review');
    assert.equal(state.canPlaceOrder, false);
    state.stepProcessed({});
    assert.equal(state.canPlaceOrder, true);
});

test('withholds order submission while an existing selection is being replaced', () => {
    const state = checkout();
    state.canPlaceOrder = true;
    state.stepForward('review');
    assert.equal(state.canPlaceOrder, false);
});

test('refuses submission while payment selection is pending or another order request is in flight', () => {
    const state = checkout();
    let calls = 0;
    state.$axios = { post() { calls++; return Promise.resolve({ data: { data: { redirect: true, redirect_url: '/' } } }); } };
    state.placeOrder();
    assert.equal(calls, 0);
    state.canPlaceOrder = true;
    state.isPlacingOrder = true;
    state.placeOrder();
    assert.equal(calls, 0);
});
