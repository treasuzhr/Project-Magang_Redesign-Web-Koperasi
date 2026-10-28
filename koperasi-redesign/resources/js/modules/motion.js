const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');


function splitWords(heading) {
    if (heading.dataset.wordsWrapped) return;
    heading.setAttribute('aria-label', heading.textContent.trim());
    const walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    let index = 0;
    nodes.forEach((node) => {
        const fragment = document.createDocumentFragment();
        node.textContent.split(/(\s+)/).forEach((text) => {
            if (!text) return;
            if (/^\s+$/.test(text)) { fragment.append(text); return; }
            const mask = document.createElement('span');
            const word = document.createElement('span');
            mask.className = 'w';
            mask.setAttribute('aria-hidden', 'true');
            word.textContent = text;
            word.style.setProperty('--i', index++);
            mask.append(word);
            fragment.append(mask);
        });
        node.replaceWith(fragment);
    });
    heading.dataset.wordsWrapped = 'true';
}

export function initMotion() {
    if (!document.querySelector('main') || document.documentElement.dataset.motionReady) return;
    document.documentElement.dataset.motionReady = 'true';
    if (document.body.classList.contains('page-news')) {
        const page = document.querySelector('main');
        page.classList.remove('page-enter');
        page.querySelectorAll('[data-reveal], [data-clip], [data-words]').forEach((element) => {
            ['data-reveal', 'data-clip', 'data-words'].forEach((attribute) => element.removeAttribute(attribute));
        });
        document.querySelectorAll('[data-footer-reveal]').forEach((element) => element.classList.add('is-visible'));
        return;
    }
    document.querySelector('main').classList.add('page-enter');

    // Bridge existing markup to one observer without changing page content or layout.
    document.querySelectorAll('main h1, main h2, [data-about-heading], [data-news-heading]').forEach((heading) => {
        if (!heading.closest('dialog') && heading.textContent.trim()) heading.setAttribute('data-words', '');
    });
    document.querySelectorAll('.home-reveal, .home-facts, .home-join, [data-footer-reveal], .home-news__layout, .home-about__stagger, .home-about__caption, .service-card__copy > *, .service-card__visual, .contact-card, .contact-location, .contact-note, .catalog-page > :not(.catalog-pagination), .about-section-heading > p, .news-article__body > section, .news-article__body > p, .news-article__meta, .home-hero .eyebrow-pill, .hero__description, .hero__actions').forEach((element) => {
        if (!element.hasAttribute('data-words')) element.setAttribute('data-reveal', '');
    });
    document.querySelectorAll('.service-card__copy').forEach((group) => {
        [...group.children].forEach((element, index) => element.style.setProperty('--d', String(index * 100)));
    });
    document.querySelectorAll('.landing-news-card').forEach((card) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'motion-card-reveal';
        wrapper.setAttribute('data-reveal', '');
        wrapper.style.setProperty('--d', card.style.getPropertyValue('--news-card-delay').replace('ms', '') || '0');
        card.before(wrapper);
        wrapper.append(card);
    });
    document.querySelectorAll('[data-about-visual], .about-hero__visual').forEach((element) => element.setAttribute('data-clip', ''));
    document.querySelectorAll('[data-stagger]').forEach((group) => {
        const step = Number(group.dataset.stagger) || 140;
        [...group.children].forEach((element, index) => element.style.setProperty('--d', String(index * step)));
    });
    document.querySelectorAll('[data-words]').forEach(splitWords);
    const targets = [...document.querySelectorAll('[data-reveal], [data-clip], [data-words]')];
    const show = (element) => element.classList.add('is-in', 'is-visible', 'is-heading-visible');
    const showAll = () => targets.forEach(show);
    let observer;
    if (reducedMotion.matches || !('IntersectionObserver' in window) || document.documentElement.classList.contains('motion-fallback')) {
        showAll();
    } else {
        observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) { show(entry.target); observer.unobserve(entry.target); }
        }), { threshold: .12, rootMargin: '0px 0px -4% 0px' });
        targets.forEach((element) => observer.observe(element));
    }
    setTimeout(showAll, 5000);
    reducedMotion.addEventListener('change', () => {
        if (reducedMotion.matches) { observer?.disconnect(); showAll(); }
    });
    initParallax();

}

function initParallax() {
    const items = [...document.querySelectorAll('[data-parallax]')];
    if (!items.length) return;
    let frame = 0;
    const update = () => {
        items.forEach((element) => {
            const rect = element.parentElement.getBoundingClientRect();
            const limit = Math.abs(Number(element.dataset.parallax)) || 24;
            const progress = (rect.top + rect.height / 2 - innerHeight / 2) / innerHeight;
            const offset = reducedMotion.matches ? 0 : Math.max(-limit, Math.min(limit, -progress * limit));
            element.style.translate = `0 ${offset.toFixed(2)}px`;
        });
        frame = 0;
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule, { passive: true });
    reducedMotion.addEventListener('change', schedule);
    update();
}
