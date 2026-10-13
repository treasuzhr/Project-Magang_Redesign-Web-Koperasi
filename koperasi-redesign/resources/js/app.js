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

const categoryTrack = document.querySelector('#category-track');

if (categoryTrack) {
    const carousel = categoryTrack.closest('.category-carousel');
    const cards = [...categoryTrack.querySelectorAll('.category-card')];
    const dotsContainer = carousel?.querySelector('.category-carousel__dots');
    const previousButton = carousel?.querySelector('[data-category-direction="previous"]');
    const nextButton = carousel?.querySelector('[data-category-direction="next"]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let activePage = 0;
    let updateFrame = 0;

    const getStep = () => {
        const cardWidth = cards[0]?.getBoundingClientRect().width || 0;
        const gap = Number.parseFloat(getComputedStyle(categoryTrack).columnGap) || 0;

        return cardWidth + gap;
    };

    const getLastPage = () => {
        const step = getStep();

        return step ? Math.max(0, Math.round((categoryTrack.scrollWidth - categoryTrack.clientWidth) / step)) : 0;
    };

    const getCurrentPage = () => {
        const step = getStep();

        return step ? Math.min(getLastPage(), Math.max(0, Math.round(categoryTrack.scrollLeft / step))) : 0;
    };

    const updateControls = () => {
        activePage = getCurrentPage();
        dotsContainer?.querySelectorAll('.category-carousel__dot').forEach((dot, index) => {
            const isActive = index === activePage;

            dot.classList.toggle('is-active', isActive);
            dot.setAttribute('aria-current', isActive ? 'true' : 'false');
        });

        if (previousButton) {
            previousButton.disabled = activePage === 0;
        }

        if (nextButton) {
            nextButton.disabled = activePage >= getLastPage();
        }
    };

    const buildDots = () => {
        if (!dotsContainer) {
            return;
        }

        const dotCount = getLastPage() + 1;
        dotsContainer.replaceChildren(...Array.from({ length: dotCount }, (_, index) => {
            const dot = document.createElement('button');

            dot.className = 'category-carousel__dot';
            dot.type = 'button';
            dot.setAttribute('aria-label', `Tampilkan posisi kategori ${index + 1}`);
            dot.addEventListener('click', () => {
                const step = getStep();

                categoryTrack.scrollTo({
                    left: index * step,
                    behavior: reducedMotion.matches ? 'auto' : 'smooth',
                });
            });

            return dot;
        }));

        updateControls();
    };

    const move = (direction) => {
        const step = getStep();
        const targetPage = Math.min(getLastPage(), Math.max(0, getCurrentPage() + direction));

        categoryTrack.scrollTo({
            left: targetPage * step,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });
    };

    previousButton?.addEventListener('click', () => move(-1));
    nextButton?.addEventListener('click', () => move(1));

    categoryTrack.addEventListener('scroll', () => {
        window.cancelAnimationFrame(updateFrame);
        updateFrame = window.requestAnimationFrame(updateControls);
    }, { passive: true });

    categoryTrack.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            move(event.key === 'ArrowRight' ? 1 : -1);
        }
    });

    let resizeTimeout;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimeout);
        resizeTimeout = window.setTimeout(buildDots, 100);
    });

    buildDots();
}

const siteHeader = document.querySelector('.site-header');

if (siteHeader) {
    const updateHeader = () => {
        siteHeader.classList.toggle('is-sticky', window.scrollY > 12);
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}
