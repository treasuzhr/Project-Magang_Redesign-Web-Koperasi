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

const contactMap = document.querySelector('[data-contact-map]');

if (contactMap) {
    const overlay = contactMap.querySelector('.contact-location__overlay');
    const mapFrame = contactMap.querySelector('iframe');
    const activateButton = contactMap.querySelector('[data-map-activate]');
    const closeButton = contactMap.querySelector('[data-map-close]');
    const hoverPointer = window.matchMedia('(hover: hover) and (pointer: fine)');

    const setMapActive = (active) => {
        contactMap.classList.toggle('is-map-active', active);
        overlay.inert = active;
        mapFrame.tabIndex = active ? 0 : -1;
        activateButton.setAttribute('aria-expanded', String(active));
        closeButton.hidden = !active;
    };

    contactMap.addEventListener('pointerenter', () => {
        if (hoverPointer.matches) {
            setMapActive(true);
        }
    });

    contactMap.addEventListener('pointerleave', () => {
        if (hoverPointer.matches) {
            setMapActive(false);
        }
    });

    activateButton.addEventListener('click', () => {
        setMapActive(true);
        closeButton.focus({ preventScroll: true });
    });

    closeButton.addEventListener('click', () => {
        setMapActive(false);
        activateButton.focus({ preventScroll: true });
    });

    hoverPointer.addEventListener('change', () => setMapActive(false));

    document.addEventListener('pointerdown', (event) => {
        if (!contactMap.contains(event.target)) {
            setMapActive(false);
        }
    });

    contactMap.addEventListener('focusin', () => {
        if (hoverPointer.matches) {
            setMapActive(true);
        }
    });
    contactMap.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMapActive(false);
            if (!hoverPointer.matches) {
                activateButton.focus({ preventScroll: true });
            }
        }
    });
    contactMap.addEventListener('focusout', (event) => {
        if (!contactMap.contains(event.relatedTarget) && !contactMap.matches(':hover')) {
            setMapActive(false);
        }
    });
}

if (siteHeader) {
    const updateHeader = () => {
        siteHeader.classList.toggle('is-sticky', window.scrollY > 12);
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}
