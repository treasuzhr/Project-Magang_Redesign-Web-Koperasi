import '../css/cart.css';
import { readCart, saveCart, subscribeCart, MAX_QUANTITY } from './modules/cart-store.js';

const page = document.querySelector('[data-shopping-cart]');
if (page) {
    const money = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
    let revision = 0;
    let pending;
    const render = async () => {
        pending?.abort();
        const current = ++revision;
        const cart = readCart();
        const button = page.querySelector('[data-checkout-submit]');
        if (button) button.disabled = true;
        page.querySelector('[data-cart-error]').hidden = true;
        try {
            let data = { products: [], html: '' };
            if (cart.size) {
                const url = new URL(page.dataset.productsUrl, location.origin);
                cart.forEach((item, id) => url.searchParams.append('ids[]', id));
                pending = new AbortController();
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: pending.signal });
                if (!response.ok) throw Error('catalog');
                data = await response.json();
            }
            if (revision !== current) return;
            const available = new Set(data.products.map(product => product.id));
            const cleaned = new Map([...cart].filter(([id]) => available.has(id)));
            if (cleaned.size !== cart.size) {
                page.querySelector('[data-cart-status]').textContent = 'Produk yang tidak tersedia dihapus dari keranjang.';
                saveCart(cleaned);
                return;
            }
            page.querySelector('[data-cart-items]').innerHTML = data.html;
            let total = 0;
            let count = 0;
            page.querySelectorAll('[data-cart-item]').forEach(row => {
                const qty = cart.get(Number(row.dataset.id)).qty;
                row.hidden = false;
                const subtotal = Number(row.dataset.price) * qty;
                count += qty; total += subtotal;
                row.querySelector('[data-cart-quantity]').textContent = qty;
                row.querySelector('[data-cart-subtotal]').textContent = money(subtotal);
                row.querySelector('[data-cart-change="1"]').disabled = qty >= MAX_QUANTITY;
            });
            page.querySelector('[data-cart-empty]').hidden = cart.size > 0;
            page.querySelector('[data-cart-filled]').hidden = cart.size === 0;
            page.querySelector('[data-cart-count]').textContent = `${cart.size} produk`;
            page.querySelector('[data-summary-count]').textContent = `${count} barang`;
            page.querySelector('[data-summary-subtotal]').textContent = money(total);
            page.querySelector('[data-cart-total]').textContent = money(total);
            const hidden = page.querySelector('[data-checkout-items]');
            if (hidden) {
                hidden.replaceChildren();
                [...cart].forEach(([id, { qty }], index) => {
                    for (const [key, value] of Object.entries({ id, qty })) {
                        const input = document.createElement('input'); input.type = 'hidden';
                        input.name = `items[${index}][${key}]`; input.value = value; hidden.append(input);
                    }
                });
            }
            if (button) button.disabled = !cart.size;
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) page.querySelector('[data-cart-error]').hidden = false;
        } finally {
            if (current === revision) page.querySelector('[data-cart-loading]').hidden = true;
        }
    };
    page.addEventListener('click', event => {
        const control = event.target.closest('[data-cart-change], [data-cart-remove]');
        if (!control) return;
        const row = control.closest('[data-cart-item]'); const id = Number(row.dataset.id);
        const cart = readCart(); const item = cart.get(id); if (!item) return;
        const qty = control.hasAttribute('data-cart-remove') ? 0 : Math.max(0, Math.min(MAX_QUANTITY, item.qty + Number(control.dataset.cartChange)));
        if (qty) cart.set(id, { qty }); else cart.delete(id);
        page.querySelector('[data-storage-notice]').hidden = saveCart(cart);
    });
    page.querySelector('[data-checkout-form]')?.addEventListener('submit', () => {
        const button = page.querySelector('[data-checkout-submit]'); button.disabled = true; button.textContent = 'Mengirim...';
    });
    subscribeCart(render);
    render();
}
