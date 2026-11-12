import test from 'node:test';
import assert from 'node:assert/strict';
import { CART_KEY, decodeCart, readCart, saveCart, subscribeCart } from '../../resources/js/modules/cart-store.js';
globalThis.window = new EventTarget();
const storage = new Map();
globalThis.localStorage = { getItem: key => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value) };
test('legacy slugs, invalid ids and quantities are ignored', () => {
    assert.equal(decodeCart('{broken').size, 0);
    assert.equal(decodeCart(JSON.stringify([{ id: 'slug-lama', name: 'Lama', price: 5, quantity: 2 }])).size, 0);
    for (const qty of [-1, 0, 1.5, 100, '2']) assert.equal(decodeCart(JSON.stringify([{ id: 1, qty }])).size, 0);
});
test('only numeric id and qty are persisted and legacy metadata is stripped', () => {
    saveCart(new Map([[1, { qty: 2, name: 'Hapus', price: 10 }]]));
    assert.deepEqual(JSON.parse(storage.get(CART_KEY)), [{ id: 1, qty: 2 }]);
    const copy = readCart(); copy.get(1).qty = 9; assert.equal(readCart().get(1).qty, 2);
    storage.set(CART_KEY, JSON.stringify([{ id: 'slug', quantity: 5 }, { id: 2, qty: 3, price: 100 }]));
    assert.equal(readCart().size, 1); assert.deepEqual(JSON.parse(storage.get(CART_KEY)), [{ id: 2, qty: 3 }]);
});
test('subscriptions can be removed and storage failure preserves in-memory state', () => {
    let calls = 0; const stop = subscribeCart(() => calls++); saveCart(new Map()); stop(); saveCart(new Map()); assert.equal(calls, 1);
    const original = globalThis.localStorage;
    globalThis.localStorage = { getItem() { throw Error('blocked'); }, setItem() { throw Error('blocked'); } };
    assert.equal(saveCart(new Map([[1, { qty: 2 }]])), false); assert.equal(readCart().get(1).qty, 2);
    globalThis.localStorage = original;
});
