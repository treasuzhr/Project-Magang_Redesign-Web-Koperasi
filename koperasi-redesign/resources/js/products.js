import { readCart, saveCart, subscribeCart } from './modules/cart-store.js';

function initProductCatalog() {
    const page = document.querySelector('[data-product-catalog]');
    if (!page || !window.fetch || !window.AbortController) return;

    const shell = page.querySelector('[data-catalog-shell]');
    const sheet = page.querySelector('[data-filter-sheet]');
    const sheetContent = page.querySelector('[data-sheet-content]');
    const toast = page.querySelector('[data-cart-toast]');
    const mobileCart = page.querySelector('[data-mobile-cart]');
    const cartTarget = document.querySelector('.navbar .cart-link');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const mobile = matchMedia('(max-width: 859px)');
    let cart = readCart();
    const money = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
    let requestController;
    let requestId = 0;
    let searchTimer;
    let toastTimer;
    let revealObserver;
    let draft = null;
    let previousOverflow = '';
    let resizeFrame = 0;
    let sidebarFrame = 0;

    const form = () => page.querySelector('[data-catalog-form]');
    const categoryInput = () => form().querySelector('[data-selected-category]');
    const viewInput = () => form().querySelector('[data-selected-view]');
    const field = name => [...form().elements].find(input => input.name === name && input.tagName === 'INPUT');
    const formatPrice = input => {
        if (!input || input.value === '') return;
        const cursor = input.selectionStart;
        const digitsBefore = (input.value.slice(0, cursor ?? input.value.length).match(/[0-9]/g) || []).length;
        const digits = input.value.replace(/[^0-9]/g, '');
        input.value = digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
        if (document.activeElement === input && cursor !== null) {
            let count = 0;
            let position = 0;
            while (position < input.value.length && count < digitsBefore) {
                if (/[0-9]/.test(input.value[position])) count++;
                position++;
            }
            input.setSelectionRange(position, position);
        }
    };
    const selectCategory = value => {
        categoryInput().value = value;
        page.querySelectorAll('[data-category-filter]').forEach(button => {
            const active = button.value === value;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
    };
    const selectView = value => {
        viewInput().value = value;
        page.querySelectorAll('[data-view-switch] button').forEach(button => button.setAttribute('aria-pressed', String(button.value === value)));
    };
    const sizeSidebar = () => {
        const sidebar = page.querySelector('.pc-sidebar');
        if (!sidebar || mobile.matches) return;
        const top = Math.max(96, sidebar.getBoundingClientRect().top);
        const height = Math.max(0, window.innerHeight - top - 16);
        sidebar.style.setProperty('--pc-sidebar-height', `${height}px`);
    };
    const positionFilters = () => {
        const body = page.querySelector('[data-filter-body]');
        if (!body) return;
        if (mobile.matches && typeof sheet.showModal === 'function') {
            sheetContent.append(body);
        } else {
            if (sheet.open) sheet.close();
            page.querySelector('.pc-sidebar').append(body);
        }
        sizeSidebar();
    };
    const showCards = () => {
        revealObserver?.disconnect();
        const cards = [...page.querySelectorAll('[data-card-reveal]')];
        if (reduced.matches || !('IntersectionObserver' in window)) {
            cards.forEach(card => card.classList.add('is-visible'));
        } else {
            revealObserver = new IntersectionObserver(entries => entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            }), { threshold: .08 });
            cards.forEach(card => revealObserver.observe(card));
            setTimeout(() => cards.forEach(card => card.classList.add('is-visible')), 5000);
        }
    };
    const renderCart = () => {
        let count = 0;
        let total = 0;
        cart.forEach(product => { count += product.quantity; total += product.price * product.quantity; });
        page.querySelectorAll('[data-product-card]').forEach(card => {
            const quantity = cart.get(card.dataset.productId)?.quantity || 0;
            const add = card.querySelector('[data-add-product]');
            add.disabled = false;
            add.hidden = quantity > 0;
            card.querySelector('[data-product-quantity]').hidden = quantity === 0;
            card.querySelector('[data-quantity-value]').textContent = quantity;
        });
        if (cartTarget) {
            const productCount = cart.size;
            let badge = cartTarget.querySelector('[data-product-cart-badge]');
            if (!badge && productCount) {
                badge = document.createElement('span');
                badge.className = 'pc-navbar-badge';
                badge.dataset.productCartBadge = '';
                badge.setAttribute('aria-hidden', 'true');
                cartTarget.style.position = 'relative';
                cartTarget.append(badge);
            }
            if (badge) { badge.textContent = productCount; badge.hidden = productCount === 0; }
            if (productCount) cartTarget.setAttribute('aria-label', `Keranjang, ${productCount} produk`);
            else cartTarget.setAttribute('aria-label', 'Keranjang');
        }
        mobileCart.hidden = count === 0;
        page.querySelector('[data-mobile-cart-count]').textContent = `${count} barang`;
        page.querySelector('[data-mobile-cart-total]').textContent = money(total);
    };
    const pulseBadge = () => {
        const badge = cartTarget?.querySelector('[data-product-cart-badge]');
        if (!badge) return;
        badge.getAnimations().forEach(animation => animation.cancel());
        badge.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.2)' }, { transform: 'scale(1)' }], { duration: reduced.matches ? 160 : 300 });
    };
    const addedFeedback = origin => {
        clearTimeout(toastTimer);
        toast.hidden = false;
        if (!reduced.matches) toast.animate([{ opacity: 0, transform: 'translate(-50%, 8px)' }, { opacity: 1, transform: 'translate(-50%, 0)' }], { duration: 180 });
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3000);
        if (!cartTarget || reduced.matches) { pulseBadge(); return; }
        const start = origin;
        const target = cartTarget.getBoundingClientRect();
        const dot = document.createElement('span');
        dot.className = 'pc-cart-dot';
        dot.setAttribute('aria-hidden', 'true');
        dot.style.left = `${start.left + start.width / 2 - 8}px`;
        dot.style.top = `${start.top + start.height / 2 - 8}px`;
        document.body.append(dot);
        const x = target.left + target.width / 2 - (start.left + start.width / 2);
        const y = target.top + target.height / 2 - (start.top + start.height / 2);
        const animation = dot.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: `translate(${x * .5}px, ${y * .5 - 70}px) scale(.8)`, opacity: 1, offset: .5 },
            { transform: `translate(${x}px, ${y}px) scale(.25)`, opacity: 0 },
        ], { duration: 700, easing: 'cubic-bezier(.3,.1,.6,1)' });
        animation.onfinish = () => { dot.remove(); pulseBadge(); };
        animation.oncancel = () => dot.remove();
    };
    const setLoading = loading => {
        const results = page.querySelector('[data-catalog-results]');
        results.classList.toggle('is-loading', loading);
        results.setAttribute('aria-busy', String(loading));
        page.querySelector('[data-catalog-skeleton]').hidden = !loading;
    };
    const load = async (url, { push = true, scroll = false } = {}) => {
        clearTimeout(searchTimer);
        requestController?.abort();
        requestController = new AbortController();
        const currentRequest = ++requestId;
        setLoading(true);
        try {
            const [response] = await Promise.all([
                fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: requestController.signal }),
                new Promise(resolve => setTimeout(resolve, reduced.matches ? 0 : 200)),
            ]);
            if (!response.ok && response.status !== 422) throw new Error('Catalog response unavailable');
            const result = await response.json();
            if (currentRequest !== requestId) return;
            if (typeof result.html !== 'string' || typeof result.url !== 'string') throw new Error('Invalid catalog response');
            const focused = document.activeElement;
            const focusId = page.contains(focused) ? focused?.id : null;
            const selection = focused instanceof HTMLInputElement ? [focused.selectionStart, focused.selectionEnd] : null;
            const sidebarScroll = page.querySelector('.pc-sidebar')?.scrollTop || 0;
            const documentScroll = { left: window.scrollX, top: window.scrollY };
            sheetContent.replaceChildren();
            shell.innerHTML = result.html;
            setup();
            const sidebar = page.querySelector('.pc-sidebar');
            if (sidebar && !mobile.matches) sidebar.scrollTop = sidebarScroll;
            if (push && result.url !== location.href) history.pushState({}, '', result.url);
            if (sheet.open && !result.invalid) { draft = null; sheet.close(); }
            if (sheet.open && result.invalid) {
                const errors = page.querySelector('.pc-errors')?.cloneNode(true);
                if (errors) { errors.removeAttribute('id'); sheetContent.prepend(errors); }
            }
            if (focusId && !sheet.open) {
                const replacement = document.getElementById(focusId);
                replacement?.focus({ preventScroll: true });
                if (selection && replacement instanceof HTMLInputElement && selection[0] !== null) replacement.setSelectionRange(...selection);
            }
            if (scroll) page.querySelector('[data-catalog-results]').scrollIntoView({ block: 'start', behavior: 'instant' });
            else window.scrollTo({ ...documentScroll, behavior: 'instant' });
            sizeSidebar();
        } catch (error) {
            if (error.name !== 'AbortError' && currentRequest === requestId) location.assign(url);
        } finally {
            if (currentRequest === requestId) setLoading(false);
        }
    };
    const submitFilters = submitter => {
        if (submitter?.matches('[data-category-filter]')) selectCategory(submitter.value);
        if (submitter?.name === 'tampilan') selectView(submitter.value);
        const parameters = new URLSearchParams();
        const values = new FormData(form());
        for (const [name, value] of values) {
            if (typeof value !== 'string' || value === '') continue;
            parameters.set(name, ['min', 'max'].includes(name) ? value.replace(/[.\s]/g, '') : value.trim());
        }
        if (submitter?.name === 'tampilan') {
            const currentPage = new URL(location.href).searchParams.get('page');
            if (currentPage) parameters.set('page', currentPage);
        }
        const url = new URL(form().action);
        url.search = parameters.toString();
        load(url.href);
    };
    const setup = () => {
        page.querySelectorAll('[data-price-input]').forEach(formatPrice);
        positionFilters();
        renderCart();
        showCards();
    };

    page.addEventListener('submit', event => {
        if (event.target !== form()) return;
        event.preventDefault();
        const submitter = event.submitter;
        if (sheet.open && submitter?.matches('[data-category-filter]')) { selectCategory(submitter.value); return; }
        submitFilters(submitter);
    });
    page.addEventListener('input', event => {
        if (event.target.name === 'q' || event.target.matches('[data-price-input]')) {
            requestController?.abort();
            requestId++;
            setLoading(false);
        }
        if (event.target.matches('[data-price-input]')) formatPrice(event.target);
        if (event.target.name === 'q') {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => submitFilters(), 300);
        }
    });
    page.addEventListener('change', event => {
        if (event.target.name === 'urut' || (event.target.matches('[data-price-input]') && !sheet.open)) submitFilters();
    });
    page.addEventListener('click', event => {
        const open = event.target.closest('[data-open-filters]');
        if (open) {
            draft = { category: categoryInput().value, min: field('min').value, max: field('max').value };
            previousOverflow = document.documentElement.style.overflow;
            document.documentElement.style.overflow = 'hidden';
            sheet.showModal();
            return;
        }
        if (event.target.closest('[data-close-filters]')) { sheet.close(); return; }
        const preset = event.target.closest('[data-price-preset]');
        if (preset) {
            event.preventDefault();
            if (sheet.open) {
                field('min').value = preset.dataset.min;
                field('max').value = preset.dataset.max;
                [field('min'), field('max')].forEach(formatPrice);
            } else load(preset.href);
            return;
        }
        const link = event.target.closest('[data-catalog-link]');
        if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
            event.preventDefault();
            load(link.href, { scroll: !!link.closest('.pc-pagination') });
            return;
        }
        const control = event.target.closest('[data-add-product], [data-change-quantity]');
        if (!control) return;
        const card = control.closest('[data-product-card]');
        const id = card.dataset.productId;
        const origin = control.getBoundingClientRect();
        const product = cart.get(id) || { name: card.dataset.productName, price: Number(card.dataset.productPrice), quantity: 0 };
        const change = control.hasAttribute('data-add-product') ? 1 : Number(control.dataset.changeQuantity);
        product.quantity = Math.max(0, Math.min(999, product.quantity + change));
        if (product.quantity <= 0) cart.delete(id);
        else cart.set(id, product);
        saveCart(cart);
        renderCart();
        if (change > 0) addedFeedback(origin);
        if (control.hidden || control.closest('[hidden]')) {
            const next = card.querySelector(product.quantity > 0 ? '[data-change-quantity="1"]' : '[data-add-product]');
            next?.focus({ preventScroll: true });
        }
    });
    sheet.addEventListener('click', event => { if (event.target === sheet) { const rect = sheet.getBoundingClientRect(); if (event.clientY < rect.top) sheet.close(); } });
    sheet.addEventListener('close', () => {
        document.documentElement.style.overflow = previousOverflow;
        if (draft) { selectCategory(draft.category); field('min').value = draft.min; field('max').value = draft.max; draft = null; }
        page.querySelector('[data-open-filters]')?.focus({ preventScroll: true });
    });
    window.addEventListener('scroll', () => {
        if (sidebarFrame) return;
        sidebarFrame = requestAnimationFrame(() => { sidebarFrame = 0; sizeSidebar(); });
    }, { passive: true });
    window.addEventListener('popstate', () => load(location.href, { push: false }));
    window.addEventListener('resize', () => { cancelAnimationFrame(resizeFrame); resizeFrame = requestAnimationFrame(positionFilters); }, { passive: true });
    reduced.addEventListener('change', () => {
        showCards();
        if (reduced.matches) document.querySelectorAll('.pc-cart-dot').forEach(dot => { dot.getAnimations().forEach(animation => animation.cancel()); dot.remove(); });
    });
    subscribeCart(() => { cart = readCart(); renderCart(); });
    page.classList.add('pc-enhanced');
    setup();
}

initProductCatalog();
