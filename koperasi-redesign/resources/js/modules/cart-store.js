export const CART_KEY = 'primkop.cart.v1';
export const MAX_QUANTITY = 99;
let memory = new Map();

export function decodeCart(raw) {
    try {
        const rows = JSON.parse(raw);
        if (!Array.isArray(rows)) return new Map();
        return new Map(rows.slice(0, 100).filter(row => row && Number.isSafeInteger(row.id) && row.id > 0
            && Number.isInteger(row.qty) && row.qty > 0 && row.qty <= MAX_QUANTITY)
            .map(({ id, qty }) => [id, { qty }]));
    } catch { return new Map(); }
}

export function readCart() {
    try {
        const raw = localStorage.getItem(CART_KEY);
        memory = decodeCart(raw);
        const clean = JSON.stringify([...memory].map(([id, { qty }]) => ({ id, qty })));
        if (raw !== null && raw !== clean) localStorage.setItem(CART_KEY, clean);
    } catch { /* In-memory fallback when storage is blocked. */ }
    return new Map([...memory].map(([id, item]) => [id, { ...item }]));
}

export function saveCart(cart) {
    const raw = JSON.stringify([...cart].map(([id, item]) => ({ id, qty: item.qty })));
    memory = decodeCart(raw);
    let persisted = true;
    try { localStorage.setItem(CART_KEY, JSON.stringify([...memory].map(([id, { qty }]) => ({ id, qty })))); } catch { persisted = false; }
    window.dispatchEvent(new CustomEvent('primkop:cart'));
    return persisted;
}

export function subscribeCart(callback) {
    const onStorage = event => { if (event.key === CART_KEY || event.key === null) callback(); };
    window.addEventListener('storage', onStorage);
    window.addEventListener('primkop:cart', callback);
    window.addEventListener('pageshow', callback);
    return () => { window.removeEventListener('storage', onStorage); window.removeEventListener('primkop:cart', callback); window.removeEventListener('pageshow', callback); };
}
