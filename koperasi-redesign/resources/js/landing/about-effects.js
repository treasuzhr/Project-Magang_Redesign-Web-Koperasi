const aboutVisual = document.querySelector('[data-about-visual]');

if (aboutVisual) {
    const image = aboutVisual.querySelector('img');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let visible = false;
    let frame = 0;

    const updateParallax = () => {
        if (!visible || !image || reducedMotion.matches) {
            frame = 0;
            return;
        }

        const rect = aboutVisual.getBoundingClientRect();
        const distance = (rect.top + (rect.height / 2) - (window.innerHeight / 2)) / window.innerHeight;
        const offset = Math.max(-24, Math.min(24, distance * -36));
        image.style.setProperty('--about-parallax', `${offset}px`);
        frame = 0;
    };

    const scheduleUpdate = () => {
        if (!frame) {
            frame = requestAnimationFrame(updateParallax);
        }
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            aboutVisual.classList.toggle('is-ambient-active', visible);

            if (visible) {
                aboutVisual.closest('.home-about__inner')?.classList.add('is-visible');
                window.addEventListener('scroll', scheduleUpdate, { passive: true });
                scheduleUpdate();
            } else {
                window.removeEventListener('scroll', scheduleUpdate);
                window.cancelAnimationFrame(frame);
                frame = 0;
            }
        }, { threshold: 0.15 })
        : null;

    if (observer) {
        observer.observe(aboutVisual);
    } else {
        aboutVisual.closest('.home-about__inner')?.classList.add('is-visible');
    }

    reducedMotion.addEventListener?.('change', () => {
        if (reducedMotion.matches && image) {
            image.style.setProperty('--about-parallax', '0px');
        } else {
            scheduleUpdate();
        }
    });
}
