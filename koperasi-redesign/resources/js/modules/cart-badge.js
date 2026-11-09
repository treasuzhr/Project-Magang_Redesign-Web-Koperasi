import '../../css/cart-badge.css';
import { readCart, subscribeCart } from './cart-store.js';

export function initCartBadge() {
    const target = document.querySelector('.navbar .cart-link');
    if (!target) return;
    const update = () => {
        const count = readCart().size;
        let badge = target.querySelector('[data-product-cart-badge]');
        if (!badge && count) {
            badge = document.createElement('span');
            badge.className = 'pc-navbar-badge';
            badge.dataset.productCartBadge = '';
            badge.setAttribute('aria-hidden', 'true');
            target.append(badge);
        }
        if (badge) { badge.textContent = count; badge.hidden = !count; }
        target.setAttribute('aria-label', count ? `Keranjang, ${count} produk` : 'Keranjang');
    };
    update();
    subscribeCart(update);
}
