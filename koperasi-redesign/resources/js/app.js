import './landing/about-effects.js';
import './landing/ambient-effects.js';
import './landing/category-showcase.js';
import './landing/count-up.js';
import './landing/news-interactions.js';
import { initMotion } from './modules/motion.js';
import './landing/scroll-statement.js';
import './landing/service-stack.js';

initMotion();

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

if (navigation) {
    const indicator = document.createElement('span');
    indicator.className = 'nav-highlight';
    indicator.ariaHidden = 'true';
    navigation.prepend(indicator);
    const activeLink = navigation.querySelector('[aria-current="page"]');
    let highlightedLink = activeLink;
    let highlightFrame = 0;
    const positionHighlight = () => {
        cancelAnimationFrame(highlightFrame);
        highlightFrame = requestAnimationFrame(() => {
            const visible = highlightedLink && getComputedStyle(navigation).display !== 'none';
            indicator.style.opacity = visible ? '1' : '0';
            if (visible) {
                indicator.style.transform = `translate(${highlightedLink.offsetLeft}px, ${highlightedLink.offsetTop}px) scale(${highlightedLink.offsetWidth / 100}, ${highlightedLink.offsetHeight / 40})`;
            }
        });
    };
    navigation.querySelectorAll('a').forEach((link) => {
        ['pointerenter', 'focus'].forEach((event) => link.addEventListener(event, () => {
            highlightedLink = link;
            positionHighlight();
        }, { passive: true }));
    });
    const resetHighlight = () => { highlightedLink = activeLink; positionHighlight(); };
    navigation.addEventListener('pointerleave', resetHighlight, { passive: true });
    navigation.addEventListener('focusout', (event) => {
        if (!navigation.contains(event.relatedTarget)) resetHighlight();
    });
    new ResizeObserver(positionHighlight).observe(navigation);
    document.fonts.ready.then(positionHighlight);
    positionHighlight();
}

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
    let headerFrame = 0;
    let isSticky = window.scrollY > 32;
    const navbar = siteHeader.querySelector('.navbar');
    let navbarAnimation;
    const updateHeader = () => {
        // Separate thresholds keep tiny scroll movements from reversing the animation.
        const nextSticky = isSticky ? window.scrollY > 8 : window.scrollY > 32;
        const changed = nextSticky !== siteHeader.classList.contains('is-sticky');
        if (changed) navbarAnimation?.cancel();
        const before = changed ? navbar.getBoundingClientRect() : null;
        isSticky = nextSticky;
        siteHeader.classList.toggle('is-sticky', isSticky);
        if (before && matchMedia('(prefers-reduced-motion: no-preference)').matches) {
            const after = navbar.getBoundingClientRect();
            navbarAnimation = navbar.animate([
                { transformOrigin: 'top left', transform: `translate(${before.left - after.left}px, ${before.top - after.top}px) scale(${before.width / after.width}, ${before.height / after.height})` },
                { transformOrigin: 'top left', transform: 'none' },
            ], { duration: 650, easing: 'cubic-bezier(.2,.8,.2,1)' });
        }
        headerFrame = 0;
    };

    updateHeader();
    window.addEventListener('scroll', () => {
        if (!headerFrame) {
            headerFrame = requestAnimationFrame(updateHeader);
        }
    }, { passive: true });
}
