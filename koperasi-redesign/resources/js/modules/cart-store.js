export const CART_KEY = 'primkop.cart.v1';
const MAX_QUANTITY = 999;
let memory = new Map();

export function decodeCart(raw) {
    try {
        const rows = JSON.parse(raw);
        if (!Array.isArray(rows)) return new Map();
        return new Map(rows.slice(0, 500).filter(row =>
            row && typeof row.id === 'string' && row.id.length <= 200 &&
            typeof row.name === 'string' && row.name.length <= 300 &&
            Number.isSafeInteger(row.price) && row.price >= 0 && row.price <= 2147483647 &&
            Number.isInteger(row.quantity) && row.quantity > 0 && row.quantity <= MAX_QUANTITY
        ).map(({ id, name, price, quantity }) => [id, { name, price, quantity }]));
    } catch { return new Map(); }
}

export function readCart() {
    try { memory = decodeCart(localStorage.getItem(CART_KEY)); } catch { /* In-memory fallback when storage is blocked. */ }
    return new Map([...memory].map(([id, product]) => [id, { ...product }]));
}

export function saveCart(cart) {
    const rows = [...cart].map(([id, product]) => ({ id, ...product }));
    const raw = JSON.stringify(rows);
    memory = decodeCart(raw);
    let persisted = true;
    try { localStorage.setItem(CART_KEY, raw); } catch { persisted = false; }
    window.dispatchEvent(new CustomEvent('primkop:cart'));
    return persisted;
}

export function subscribeCart(callback) {
    const onStorage = event => { if (event.key === CART_KEY || event.key === null) callback(); };
    window.addEventListener('storage', onStorage);
    window.addEventListener('primkop:cart', callback);
    window.addEventListener('pageshow', callback);
    return () => {
        window.removeEventListener('storage', onStorage);
        window.removeEventListener('primkop:cart', callback);
        window.removeEventListener('pageshow', callback);
    };
}
