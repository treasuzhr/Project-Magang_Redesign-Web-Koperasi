const stack = document.querySelector('.service-stack');

if (stack) {
    const cards = [...stack.querySelectorAll('.service-card')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const counter = stack.querySelector('[data-service-counter]');
    let currentCard = -1;
    let counterStarted = false;

    const animateLoanCounter = () => {
        if (!counter || counterStarted) {
            return;
        }

        counterStarted = true;
        const end = Number(counter.dataset.countEnd);

        if (reducedMotion) {
            counter.textContent = String(end);
            return;
        }

        const startedAt = performance.now();
        const duration = 1200;
        const update = (now) => {
            const progress = Math.min(1, (now - startedAt) / duration);
            const eased = 1 - ((1 - progress) ** 3);
            counter.textContent = String(Math.round(end * eased));

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        };

        requestAnimationFrame(update);
    };

    if (reducedMotion || !('IntersectionObserver' in window)) {
        cards.forEach((card) => card.classList.add('inview'));
        animateLoanCounter();
    } else {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('inview');
                    if (entry.target === cards[1]) {
                        animateLoanCounter();
                    }
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.35 });

        cards.forEach((card) => observer.observe(card));
        window.setTimeout(() => cards.forEach((card) => card.classList.add('inview')), 6000);
    }

    if (!reducedMotion) {
        let frame = 0;
        const updateStack = () => {
            let nextCurrentCard = 0;

            cards.forEach((card, index) => {
                const nextCard = cards[index + 1];
                const rect = card.getBoundingClientRect();
                const stickyTop = Number.parseFloat(getComputedStyle(card).top) || rect.top;

                if (!nextCard) {
                    card.style.removeProperty('--stack-progress');
                } else {
                    const nextRect = nextCard.getBoundingClientRect();
                    const isStacking = rect.top <= stickyTop + 1;
                    const progress = isStacking
                        ? Math.min(1, Math.max(0, (rect.top + rect.height - nextRect.top) / rect.height))
                        : 0;

                    card.style.setProperty('--stack-progress', String(progress));
                    card.style.setProperty('--card-scale', String(1 - (progress * 0.06)));
                    card.style.setProperty('--card-brightness', String(1 - (progress * 0.1)));
                }

                if (rect.top <= stickyTop + 2) {
                    nextCurrentCard = index;
                }
            });

            if (nextCurrentCard !== currentCard) {
                cards[currentCard]?.classList.remove('is-current');
                cards[nextCurrentCard]?.classList.add('is-current');
                currentCard = nextCurrentCard;
            }

            frame = 0;
        };

        const scheduleUpdate = () => {
            if (!frame) {
                frame = window.requestAnimationFrame(updateStack);
            }
        };

        const stackObserver = 'IntersectionObserver' in window
            ? new IntersectionObserver(([entry]) => {
                if (entry.isIntersecting) {
                    window.addEventListener('scroll', scheduleUpdate, { passive: true });
                    window.addEventListener('resize', scheduleUpdate);
                    scheduleUpdate();
                } else {
                    window.removeEventListener('scroll', scheduleUpdate);
                    window.removeEventListener('resize', scheduleUpdate);
                    window.cancelAnimationFrame(frame);
                    frame = 0;
                }
            }, { rootMargin: '200px' })
            : null;

        if (stackObserver) {
            stackObserver.observe(stack);
        } else {
            window.addEventListener('scroll', scheduleUpdate, { passive: true });
            window.addEventListener('resize', scheduleUpdate);
            scheduleUpdate();
        }
    }
}
