const hero = document.querySelector('.home-hero');

if (hero) {
    const ambient = hero.querySelector('.home-hero__ambient');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = window.matchMedia('(pointer: fine)');
    let frame = 0;
    let pointer = null;

    const onPointerMove = (event) => {
        pointer = { x: event.clientX, y: event.clientY };

        if (!frame) {
            frame = requestAnimationFrame(() => {
                const rect = hero.getBoundingClientRect();
                ambient?.style.setProperty('--hero-x', `${pointer.x - rect.left}px`);
                ambient?.style.setProperty('--hero-y', `${pointer.y - rect.top}px`);
                frame = 0;
            });
        }
    };

    const enablePointer = () => {
        if (finePointer.matches && !reducedMotion.matches && ambient) {
            hero.addEventListener('pointermove', onPointerMove, { passive: true });
            hero.classList.add('is-pointer-active');
        }
    };

    const disablePointer = () => {
        hero.removeEventListener('pointermove', onPointerMove);
        hero.classList.remove('is-pointer-active');
        window.cancelAnimationFrame(frame);
        frame = 0;
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry]) => {
            hero.classList.toggle('is-ambient-active', entry.isIntersecting);
            if (entry.isIntersecting) {
                enablePointer();
            } else {
                disablePointer();
            }
        }, { threshold: 0 })
        : null;

    observer?.observe(hero);

    if (!observer && !reducedMotion.matches) {
        hero.classList.add('is-ambient-active');
        enablePointer();
    }

    finePointer.addEventListener?.('change', () => finePointer.matches ? enablePointer() : disablePointer());
    reducedMotion.addEventListener?.('change', () => reducedMotion.matches ? disablePointer() : enablePointer());
}
