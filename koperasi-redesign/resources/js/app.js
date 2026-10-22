import './landing/about-effects.js';
import './landing/ambient-effects.js';
import './landing/category-showcase.js';
import './landing/count-up.js';
import './landing/hero-effects.js';
import './landing/news-interactions.js';
import './landing/reveal.js';
import './landing/scroll-statement.js';
import './landing/service-stack.js';

const menuToggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#primary-navigation');

if (menuToggle && navigation) {
    const closeMenu = () => {
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Buka menu navigasi');
        navigation.classList.remove('is-open');
    };

    menuToggle.addEventListener('click', () => {
        const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';

        menuToggle.setAttribute('aria-expanded', String(!isExpanded));
        menuToggle.setAttribute('aria-label', isExpanded ? 'Buka menu navigasi' : 'Tutup menu navigasi');
        navigation.classList.toggle('is-open', !isExpanded);
    });

    navigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
}

const siteHeader = document.querySelector('.site-header');

if (siteHeader) {
    const updateHeader = () => {
        siteHeader.classList.toggle('is-sticky', window.scrollY > 12);
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}
