const statement = document.querySelector('[data-scroll-statement]');

if (statement) {
    const words = [...statement.querySelectorAll('span')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reducedMotion) {
        words.forEach((word) => word.style.setProperty('--word-opacity', '1'));
    } else {
        let frame = 0;
        const updateWords = () => {
            const rect = statement.getBoundingClientRect();
            const range = window.innerHeight * .7;
            const progress = Math.min(1, Math.max(0, (window.innerHeight * .8 - rect.top) / range));

            words.forEach((word, index) => {
                const wordProgress = Math.min(1, Math.max(0, (progress * words.length) - index + 1));
                word.style.setProperty('--word-opacity', String(.2 + (wordProgress * .8)));
            });
        };

        const scheduleUpdate = () => {
            window.cancelAnimationFrame(frame);
            frame = window.requestAnimationFrame(updateWords);
        };

        window.addEventListener('scroll', scheduleUpdate, { passive: true });
        window.addEventListener('resize', scheduleUpdate);
        scheduleUpdate();
    }
}
