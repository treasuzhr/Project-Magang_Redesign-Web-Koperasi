//
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

const categoryTrack = document.querySelector('#category-track');
const categoryArrows = document.querySelectorAll('[data-carousel]');
const categoryDots = document.querySelectorAll('.carousel-dot');

if (categoryTrack) {
    const originalCards = [...categoryTrack.children];

    for (let copy = 0; copy < 2; copy += 1) {
        const cloneFragment = document.createDocumentFragment();

        originalCards.forEach((card) => {
            const clone = card.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            clone.dataset.carouselClone = 'true';
            cloneFragment.append(clone);
        });

        categoryTrack.append(cloneFragment);
    }

    const cloneStart = categoryTrack.querySelector('[data-carousel-clone]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let loopWidth = 0;
    let startPosition = 0;
    let pageWidth = 0;
    let pointerStart = null;
    let hasFocus = false;
    let pauseUntil = 0;
    let programmaticMove = false;
    let settleTimeout;
    let manualScrollTimeout;

    const measureLoop = () => {
        if (!cloneStart) {
            return;
        }

        loopWidth = cloneStart.getBoundingClientRect().left
            - categoryTrack.getBoundingClientRect().left
            + categoryTrack.scrollLeft;
        startPosition = loopWidth - (Number.parseFloat(getComputedStyle(categoryTrack).paddingLeft) || 0);

        const card = categoryTrack.querySelector('.category-card');
        const gap = Number.parseFloat(getComputedStyle(categoryTrack).columnGap) || 0;
        pageWidth = ((card?.getBoundingClientRect().width || 0) + gap) * 2;
    };

    const updateDots = () => {
        if (!loopWidth || !categoryDots.length) {
            return;
        }

        const position = ((categoryTrack.scrollLeft - startPosition) % loopWidth + loopWidth) % loopWidth;
        const activeIndex = Math.min(categoryDots.length - 1, Math.floor(position / pageWidth));

        categoryDots.forEach((dot, index) => {
            dot.classList.toggle('carousel-dot--active', index === activeIndex);
        });
    };

    const normalizePosition = () => {
        if (!loopWidth) {
            return;
        }

        const position = ((categoryTrack.scrollLeft - startPosition) % loopWidth + loopWidth) % loopWidth;
        const normalizedPosition = startPosition + position;

        if (Math.abs(categoryTrack.scrollLeft - normalizedPosition) > 0.5) {
            categoryTrack.scrollLeft = normalizedPosition;
        }
    };

    const pauseTemporarily = (duration = 1800) => {
        pauseUntil = performance.now() + duration;
    };

    const finishProgrammaticMove = () => {
        window.clearTimeout(settleTimeout);
        programmaticMove = false;
        normalizePosition();
        updateDots();
    };

    categoryTrack.addEventListener('scrollend', () => {
        if (programmaticMove) {
            finishProgrammaticMove();
        } else if (!pointerStart) {
            snapToPage();
        }
    });

    const moveByPage = (direction) => {
        if (!loopWidth || !pageWidth) {
            return;
        }

        pauseTemporarily();
        programmaticMove = true;
        categoryTrack.scrollTo({
            left: categoryTrack.scrollLeft + direction * pageWidth,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });

        if (reducedMotion.matches) {
            finishProgrammaticMove();
        } else {
            settleTimeout = window.setTimeout(finishProgrammaticMove, 900);
        }
    };

    const snapToPage = () => {
        if (!loopWidth || !pageWidth) {
            return;
        }

        const position = ((categoryTrack.scrollLeft - startPosition) % loopWidth + loopWidth) % loopWidth;
        const page = Math.round(position / pageWidth);
        const target = page * pageWidth >= loopWidth ? startPosition : startPosition + page * pageWidth;

        programmaticMove = true;
        categoryTrack.scrollTo({
            left: target,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });

        if (reducedMotion.matches) {
            finishProgrammaticMove();
        } else {
            settleTimeout = window.setTimeout(finishProgrammaticMove, 900);
        }
    };

    const settleManualScroll = () => {
        window.clearTimeout(manualScrollTimeout);

        if (!programmaticMove && !pointerStart) {
            snapToPage();
        }
    };

    categoryArrows.forEach((arrow) => {
        arrow.addEventListener('click', () => {
            const direction = arrow.dataset.carousel === 'next' ? 1 : -1;

            moveByPage(direction);
        });
    });

    categoryTrack.addEventListener('focusin', () => {
        hasFocus = true;
    });
    categoryTrack.addEventListener('focusout', (event) => {
        if (!categoryTrack.contains(event.relatedTarget)) {
            hasFocus = false;
        }
    });
    categoryTrack.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            moveByPage(event.key === 'ArrowRight' ? 1 : -1);
        }
    });

    categoryTrack.addEventListener('pointerdown', (event) => {
        pointerStart = {
            id: event.pointerId,
            x: event.clientX,
            scrollLeft: categoryTrack.scrollLeft,
        };

        if (event.pointerType !== 'touch') {
            event.preventDefault();
            categoryTrack.setPointerCapture(event.pointerId);
            categoryTrack.classList.add('is-dragging');
        }

        pauseTemporarily();
    });
    categoryTrack.addEventListener('pointermove', (event) => {
        if (pointerStart?.id === event.pointerId) {
            const rawPosition = pointerStart.scrollLeft - (event.clientX - pointerStart.x);
            const position = loopWidth
                ? startPosition + ((rawPosition - startPosition) % loopWidth + loopWidth) % loopWidth
                : Math.max(0, rawPosition);

            pointerStart.scrollLeft += position - rawPosition;
            categoryTrack.scrollLeft = position;
        }
    });

    const endDrag = (event) => {
        if (pointerStart?.id === event.pointerId) {
            pointerStart = null;
            categoryTrack.classList.remove('is-dragging');
            pauseTemporarily();
            snapToPage();
        }
    };

    categoryTrack.addEventListener('pointerup', endDrag);
    categoryTrack.addEventListener('pointercancel', endDrag);
    categoryTrack.addEventListener('lostpointercapture', endDrag);

    categoryTrack.addEventListener('scroll', () => {
        if (!programmaticMove && !pointerStart) {
            normalizePosition();
            window.clearTimeout(manualScrollTimeout);
            manualScrollTimeout = window.setTimeout(settleManualScroll, 180);
        }
        updateDots();
    }, { passive: true });

    window.setInterval(() => {
        if (!reducedMotion.matches && !document.hidden && !hasFocus && performance.now() >= pauseUntil) {
            moveByPage(1);
        }
    }, 4200);

    measureLoop();
    categoryTrack.scrollLeft = startPosition;
    updateDots();
    window.addEventListener('resize', () => {
        measureLoop();
        categoryTrack.scrollLeft = startPosition;
        updateDots();
    });
}
