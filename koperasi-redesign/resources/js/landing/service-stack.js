import { calculateLoanPreview } from '../modules/loan-calculator.js';

const stack = document.querySelector('.service-stack');

if (stack) {
    const cards = [...stack.querySelectorAll('.service-card')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let currentCard = -1;
    const preview = stack.querySelector('[data-loan-preview]');
    if (preview) {
        const amount = preview.querySelector('[data-loan-amount-input]');
        const tenor = preview.querySelector('[data-loan-tenor-input]');
        const money = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
        const updatePreview = () => {
            const principal = Number(amount.value);
            const months = Number(tenor.value);
            const estimate = calculateLoanPreview(principal, months);
            preview.querySelector('[data-loan-amount]').textContent = money(principal);
            preview.querySelector('[data-loan-tenor]').textContent = `${months} bulan`;
            preview.querySelector('[data-loan-payment]').textContent = money(estimate.monthlyPayment);
            preview.querySelector('[data-loan-breakdown]').textContent = `Pokok ${money(estimate.monthlyPrincipal)} + bunga ${money(estimate.monthlyInterest)}`;
            amount.setAttribute('aria-valuetext', money(principal));
            tenor.setAttribute('aria-valuetext', `${months} bulan`);
            [amount, tenor].forEach(input => {
                const progress = (Number(input.value) - Number(input.min)) / (Number(input.max) - Number(input.min)) * 100;
                input.style.setProperty('--range-progress', `${progress}%`);
            });
        };
        [amount, tenor].forEach(input => {
            input.disabled = false;
            input.addEventListener('input', updatePreview);
        });
        updatePreview();
    }

    if (reducedMotion || !('IntersectionObserver' in window)) {
        cards.forEach((card) => card.classList.add('inview'));
    } else {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('inview');
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
                const rect = card.getBoundingClientRect();
                const stickyTop = Number.parseFloat(getComputedStyle(card).top) || rect.top;

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
