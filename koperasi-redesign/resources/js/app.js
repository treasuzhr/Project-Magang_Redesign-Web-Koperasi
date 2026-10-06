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
const categoryDotsContainer = document.querySelector('.carousel-dots');
let categoryDots = [...(categoryDotsContainer?.children || [])];

if (categoryTrack) {
    const originalCards = [...categoryTrack.children];
    const copiesPerSide = 2;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let cards = [];
    let startPosition = 0;
    let cardStep = 0;
    let cardsPerPage = 1;
    let pointerStart = null;
    let isHovered = false;
    let hasFocus = false;
    let pauseUntil = 0;
    let programmaticMove = false;
    let settleTimeout;
    let manualScrollTimeout;

    const createClones = () => {
        const clones = document.createDocumentFragment();

        originalCards.forEach((card) => {
            const clone = card.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            clone.dataset.carouselClone = 'true';
            clones.append(clone);
        });

        return clones;
    };

    const beforeClones = document.createDocumentFragment();
    for (let copy = 0; copy < copiesPerSide; copy += 1) {
        beforeClones.append(createClones());
    }
    categoryTrack.prepend(beforeClones);

    for (let copy = 0; copy < copiesPerSide; copy += 1) {
        categoryTrack.append(createClones());
    }

    const cardPosition = (card) => {
        const trackRect = categoryTrack.getBoundingClientRect();
        const paddingLeft = Number.parseFloat(getComputedStyle(categoryTrack).paddingLeft) || 0;

        return card.getBoundingClientRect().left - trackRect.left + categoryTrack.scrollLeft - paddingLeft;
    };

    const measureLoop = () => {
        cards = [...categoryTrack.children];
        const firstOriginal = cards[copiesPerSide * originalCards.length];
        const secondOriginal = cards[copiesPerSide * originalCards.length + 1];

        if (!firstOriginal || !secondOriginal) {
            return;
        }

        cardStep = cardPosition(secondOriginal) - cardPosition(firstOriginal);
        startPosition = cardPosition(firstOriginal);

        const style = getComputedStyle(categoryTrack);
        const paddingLeft = Number.parseFloat(style.paddingLeft) || 0;
        const paddingRight = Number.parseFloat(style.paddingRight) || 0;
        const contentEnd = categoryTrack.clientWidth - paddingLeft - paddingRight;
        cardsPerPage = 0;

        for (let index = 0; index < originalCards.length; index += 1) {
            const card = cards[copiesPerSide * originalCards.length + index];
            if (cardPosition(card) - startPosition + card.getBoundingClientRect().width > contentEnd + 0.5) {
                break;
            }
            cardsPerPage += 1;
        }
        cardsPerPage = Math.max(1, cardsPerPage);

        if (categoryDotsContainer) {
            categoryDotsContainer.replaceChildren(...Array.from(
                { length: Math.ceil(originalCards.length / cardsPerPage) },
                () => {
                    const dot = document.createElement('span');
                    dot.className = 'carousel-dot';
                    return dot;
                },
            ));
            categoryDots = [...categoryDotsContainer.children];
        }
    };

    const nearestCardIndex = () => {
        let nearestIndex = 0;
        let nearestDistance = Number.POSITIVE_INFINITY;

        cards.forEach((card, index) => {
            const distance = Math.abs(cardPosition(card) - categoryTrack.scrollLeft);
            if (distance < nearestDistance) {
                nearestDistance = distance;
                nearestIndex = index;
            }
        });

        return nearestIndex;
    };

    const logicalCardIndex = (index) => (
        (index - copiesPerSide * originalCards.length) % originalCards.length + originalCards.length
    ) % originalCards.length;

    const updateDots = () => {
        if (!categoryDots.length) {
            return;
        }

        const activeIndex = Math.floor(logicalCardIndex(nearestCardIndex()) / cardsPerPage) % categoryDots.length;
        categoryDots.forEach((dot, index) => {
            dot.classList.toggle('carousel-dot--active', index === activeIndex);
        });
    };

    const normalizeToOriginalCards = () => {
        if (!cards.length || !cardStep) {
            return;
        }

        const originalIndex = copiesPerSide * originalCards.length + logicalCardIndex(nearestCardIndex());
        categoryTrack.scrollLeft = cardPosition(cards[originalIndex]);
        updateDots();
    };

    const pauseTemporarily = (duration = 1800) => {
        pauseUntil = performance.now() + duration;
    };

    const finishProgrammaticMove = () => {
        window.clearTimeout(settleTimeout);
        programmaticMove = false;
        normalizeToOriginalCards();
    };

    const scrollToCard = (card) => {
        window.clearTimeout(settleTimeout);
        window.clearTimeout(manualScrollTimeout);
        programmaticMove = true;
        categoryTrack.scrollTo({
            left: cardPosition(card),
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });

        if (reducedMotion.matches) {
            finishProgrammaticMove();
        } else {
            settleTimeout = window.setTimeout(finishProgrammaticMove, 900);
        }
    };

    const moveByCard = (direction) => {
        if (!cardStep) {
            return;
        }

        pauseTemporarily(5000);
        const currentIndex = nearestCardIndex();
        const targetIndex = Math.max(0, Math.min(cards.length - 1, currentIndex + direction));
        scrollToCard(cards[targetIndex]);
    };

    const settleManualScroll = () => {
        window.clearTimeout(manualScrollTimeout);
        if (!programmaticMove && !pointerStart) {
            normalizeToOriginalCards();
        }
    };

    categoryArrows.forEach((arrow) => {
        arrow.addEventListener('click', () => {
            moveByCard(arrow.dataset.carousel === 'next' ? 1 : -1);
        });
    });

    const categoryCarousel = categoryTrack.closest('.category-carousel');
    categoryCarousel?.addEventListener('pointerenter', () => {
        isHovered = true;
    });
    categoryCarousel?.addEventListener('pointerleave', () => {
        isHovered = false;
        pauseTemporarily(5000);
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
            moveByCard(event.key === 'ArrowRight' ? 1 : -1);
        }
    });

    categoryTrack.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'touch') {
            pauseTemporarily();
            return;
        }

        window.clearTimeout(manualScrollTimeout);
        window.clearTimeout(settleTimeout);
        programmaticMove = false;
        pointerStart = {
            id: event.pointerId,
            x: event.clientX,
            scrollLeft: categoryTrack.scrollLeft,
        };

        event.preventDefault();
        categoryTrack.setPointerCapture(event.pointerId);
        categoryTrack.classList.add('is-dragging');
        pauseTemporarily();
    });
    categoryTrack.addEventListener('pointermove', (event) => {
        if (pointerStart?.id === event.pointerId) {
            const rawPosition = pointerStart.scrollLeft - (event.clientX - pointerStart.x);
            categoryTrack.scrollLeft = Math.max(0, Math.min(categoryTrack.scrollWidth - categoryTrack.clientWidth, rawPosition));
        }
    });

    const endDrag = (event) => {
        if (pointerStart?.id === event.pointerId) {
            pointerStart = null;
            categoryTrack.classList.remove('is-dragging');
            pauseTemporarily();
            window.clearTimeout(manualScrollTimeout);
            manualScrollTimeout = window.setTimeout(settleManualScroll, 220);
        }
    };

    categoryTrack.addEventListener('pointerup', endDrag);
    categoryTrack.addEventListener('pointercancel', endDrag);
    categoryTrack.addEventListener('lostpointercapture', endDrag);
    categoryTrack.addEventListener('scrollend', () => {
        if (programmaticMove) {
            finishProgrammaticMove();
        } else {
            settleManualScroll();
        }
    });
    categoryTrack.addEventListener('scroll', () => {
        if (!programmaticMove) {
            pauseTemporarily();
            window.clearTimeout(manualScrollTimeout);
            manualScrollTimeout = window.setTimeout(settleManualScroll, 220);
        }
        updateDots();
    }, { passive: true });

    window.setInterval(() => {
        if (!reducedMotion.matches && !document.hidden && !isHovered && !hasFocus && performance.now() >= pauseUntil) {
            moveByCard(1);
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
