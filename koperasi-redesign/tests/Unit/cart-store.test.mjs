import test from 'node:test';
import assert from 'node:assert/strict';
import { CART_KEY, decodeCart, readCart, saveCart, subscribeCart } from '../../resources/js/modules/cart-store.js';
globalThis.window = new EventTarget();
const storage = new Map();
globalThis.localStorage = { getItem: key => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value) };
const product = { id: 'amplop-coklat-a4', name: 'Amplop Coklat A4', price: 750, quantity: 2 };

test('invalid storage and invalid quantities are ignored', () => {
    assert.equal(decodeCart('{broken').size, 0);
    assert.equal(decodeCart('{}').size, 0);
    for (const quantity of [-1, 0, 1.5, 1000, '2']) assert.equal(decodeCart(JSON.stringify([{ ...product, quantity }])).size, 0);
    assert.equal(decodeCart(JSON.stringify([{ ...product, price: -1 }])).size, 0);
});

test('cart survives page reads and callers cannot mutate saved state', () => {
    saveCart(new Map([[product.id, { name: product.name, price: product.price, quantity: 2 }]]));
    assert.equal(readCart().get(product.id).quantity, 2);
    const copy = readCart();
    copy.get(product.id).quantity = 99;
    assert.equal(readCart().get(product.id).quantity, 2);
});

test('subscribers receive cart updates and cross-tab changes, and can unsubscribe', () => {
    let changes = 0;
    const stop = subscribeCart(() => changes++);
    saveCart(new Map());
    const event = new Event('storage');
    event.key = CART_KEY;
    window.dispatchEvent(event);
    assert.equal(changes, 2);
    stop();
    window.dispatchEvent(event);
    assert.equal(changes, 2);
});

test('storage failure falls back to in-memory cart without throwing', () => {
    const original = globalThis.localStorage;
    globalThis.localStorage = { getItem() { throw Error('blocked'); }, setItem() { throw Error('full'); } };
    assert.equal(saveCart(new Map([[product.id, product]])), false);
    assert.equal(readCart().get(product.id).quantity, 2);
    globalThis.localStorage = original;
});
