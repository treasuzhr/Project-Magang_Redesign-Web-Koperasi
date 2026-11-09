import '../css/cart.css';
import { readCart, saveCart, subscribeCart } from './modules/cart-store.js';

const page = document.querySelector('[data-shopping-cart]');
if (page) {
    const rows = [...page.querySelectorAll('[data-cart-item]')];
    const money = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
    let cart = readCart();
    const canonicalCart = () => new Map(rows.filter(row => cart.has(row.dataset.id)).map(row => [
        row.dataset.id, { name: row.dataset.name, price: Number(row.dataset.price), quantity: cart.get(row.dataset.id).quantity },
    ]));
    if (page.dataset.sourceAvailable === 'true') {
        const canonical = canonicalCart();
        if (JSON.stringify([...canonical]) !== JSON.stringify([...cart])) {
            page.querySelector('[data-storage-notice]').hidden = saveCart(canonical);
            cart = canonical;
        }
    }
    const render = () => {
        cart = readCart();
        let count = 0;
        let total = 0;
        rows.forEach(row => {
            const quantity = cart.get(row.dataset.id)?.quantity || 0;
            row.hidden = !quantity;
            if (!quantity) return;
            const subtotal = Number(row.dataset.price) * quantity;
            count += quantity;
            total += subtotal;
            row.querySelector('[data-cart-quantity]').textContent = quantity;
            row.querySelector('[data-cart-subtotal]').textContent = money(subtotal);
            row.querySelector('[data-cart-change="1"]').disabled = quantity >= 999;
        });
        page.querySelector('[data-cart-loading]').hidden = true;
        if (page.dataset.sourceAvailable !== 'true') return;
        page.querySelector('[data-cart-empty]').hidden = count > 0;
        page.querySelector('[data-cart-filled]').hidden = count === 0;
        page.querySelector('[data-cart-count]').textContent = `${rows.filter(row => !row.hidden).length} produk`;
        page.querySelector('[data-summary-count]').textContent = `${count} barang`;
        page.querySelector('[data-summary-subtotal]').textContent = money(total);
        page.querySelector('[data-cart-total]').textContent = money(total);
    };
    page.addEventListener('click', event => {
        const control = event.target.closest('[data-cart-change], [data-cart-remove]');
        if (!control) return;
        cart = readCart();
        const row = control.closest('[data-cart-item]');
        const id = row.dataset.id;
        const product = cart.get(id);
        if (!product) return;
        const quantity = control.hasAttribute('data-cart-remove') ? 0 : Math.max(0, Math.min(999, product.quantity + Number(control.dataset.cartChange)));
        if (quantity) cart.set(id, { name: row.dataset.name, price: Number(row.dataset.price), quantity });
        else cart.delete(id);
        const nextRow = rows.find(item => item !== row && !item.hidden);
        page.querySelector('[data-storage-notice]').hidden = saveCart(cart);
        render();
        page.querySelector('[data-cart-status]').textContent = quantity ? `Jumlah ${row.dataset.name}: ${quantity}.` : `${row.dataset.name} dihapus dari keranjang.`;
        if (!quantity) (nextRow?.querySelector('[data-cart-change="1"]') || page.querySelector('[data-cart-empty] a')).focus({ preventScroll: true });
    });
    subscribeCart(render);
    render();
}
