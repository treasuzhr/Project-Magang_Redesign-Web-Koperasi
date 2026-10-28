const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(pointer: fine)');

const addPointerGlow = (element, className, variablePrefix = 'cursor') => {
    if (!element || !finePointer.matches || motion.matches) {
        return;
    }

    let frame = 0;
    let latest = null;
    const update = () => {
        const rect = element.getBoundingClientRect();
        element.style.setProperty(`--${variablePrefix}-x`, `${latest.x - rect.left}px`);
        element.style.setProperty(`--${variablePrefix}-y`, `${latest.y - rect.top}px`);
        element.classList.add(className);
        frame = 0;
    };

    element.addEventListener('pointermove', (event) => {
        latest = { x: event.clientX, y: event.clientY };
        if (!frame) {
            frame = requestAnimationFrame(update);
        }
    }, { passive: true });
    element.addEventListener('pointerleave', () => {
        element.classList.remove(className);
        window.cancelAnimationFrame(frame);
        frame = 0;
    });
};

document.querySelectorAll('[data-cursor-glow]').forEach((element) => addPointerGlow(element, 'is-pointer-active'));

const joinBlock = document.querySelector('.home-join');
if (joinBlock && 'IntersectionObserver' in window) {
    const joinObserver = new IntersectionObserver(([entry]) => {
        joinBlock.classList.toggle('is-ambient-active', entry.isIntersecting);
    }, { threshold: 0 });
    joinObserver.observe(joinBlock);
}

const footer = document.querySelector('.site-footer');

if (footer) {
    const mark = footer.querySelector('.footer-wordmark');
    let visible = false;
    let frame = 0;
    const updateFooter = () => {
        if (!visible || !mark || motion.matches) {
            frame = 0;
            return;
        }

        const rect = footer.getBoundingClientRect();
        const progress = Math.max(0, Math.min(1, (window.innerHeight - rect.top) / (window.innerHeight + rect.height)));
        mark.style.setProperty('--footer-parallax', `${20 - progress * 20}px`);
        frame = 0;
    };

    const scheduleFooter = () => {
        if (!frame) {
            frame = requestAnimationFrame(updateFooter);
        }
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            footer.querySelectorAll('[data-footer-reveal]').forEach((column) => {
                column.classList.toggle('is-visible', entry.isIntersecting);
            });

            if (visible) {
                window.addEventListener('scroll', scheduleFooter, { passive: true });
                scheduleFooter();
            } else {
                window.removeEventListener('scroll', scheduleFooter);
                window.cancelAnimationFrame(frame);
                frame = 0;
            }
        }, { threshold: 0.03 })
        : null;

    observer?.observe(footer);
    if (!observer) {
        footer.querySelectorAll('[data-footer-reveal]').forEach((column) => column.classList.add('is-visible'));
    }
}

if (finePointer.matches && !motion.matches) {
    const magneticTargets = document.querySelectorAll(
        '.home-page .button--primary:not(.hero__actions .button--primary), .home-page .service-card__button',
    );

    magneticTargets.forEach((button) => {
        let frame = 0;
        let latest = null;
        const update = () => {
            const rect = button.getBoundingClientRect();
            const x = (latest.x - rect.left - rect.width / 2) / rect.width;
            const y = (latest.y - rect.top - rect.height / 2) / rect.height;
            button.style.setProperty('--magnet-x', `${Math.max(-6, Math.min(6, x * 12))}px`);
            button.style.setProperty('--magnet-y', `${Math.max(-6, Math.min(6, y * 12))}px`);
            frame = 0;
        };

        button.addEventListener('pointermove', (event) => {
            latest = { x: event.clientX, y: event.clientY };
            if (!frame) {
                frame = requestAnimationFrame(update);
            }
        }, { passive: true });

        button.addEventListener('pointerleave', () => {
            button.style.setProperty('--magnet-x', '0px');
            button.style.setProperty('--magnet-y', '0px');
            window.cancelAnimationFrame(frame);
            frame = 0;
        });
    });
}
