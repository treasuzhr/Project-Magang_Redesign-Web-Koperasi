const newsLayout = document.querySelector('.home-news__layout');

if (newsLayout) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = window.matchMedia('(pointer: fine)');
    const cursorArea = newsLayout.querySelector('[data-news-cursor]');
    let target = null;
    let current = null;
    let frame = 0;

    const revealObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry], observer) => {
            if (entry.isIntersecting) {
                newsLayout.classList.add('is-visible');
                observer.disconnect();
            }
        }, { threshold: 0.1 })
        : null;

    revealObserver?.observe(newsLayout);
    if (!revealObserver || reducedMotion.matches) {
        newsLayout.classList.add('is-visible');
    }
    window.setTimeout(() => newsLayout.classList.add('is-visible'), 5000);

    if (cursorArea && finePointer.matches && !reducedMotion.matches) {
        const cursor = cursorArea.querySelector('.home-news-feature__cursor');

        const moveCursor = () => {
            if (!target || !cursor) {
                frame = 0;
                return;
            }

            current ??= target;
            current.x += (target.x - current.x) * .18;
            current.y += (target.y - current.y) * .18;
            cursor.style.setProperty('--cursor-x', `${current.x}px`);
            cursor.style.setProperty('--cursor-y', `${current.y}px`);

            if (Math.abs(target.x - current.x) > .5 || Math.abs(target.y - current.y) > .5) {
                frame = requestAnimationFrame(moveCursor);
            } else {
                frame = 0;
            }
        };

        cursorArea.addEventListener('pointerenter', (event) => {
            const rect = cursorArea.getBoundingClientRect();
            target = { x: event.clientX - rect.left - 36, y: event.clientY - rect.top - 36 };
            current = { ...target };
            cursorArea.classList.add('is-cursor-active');
            cursorArea.style.setProperty('--cursor-x', `${target.x}px`);
            cursorArea.style.setProperty('--cursor-y', `${target.y}px`);
        });

        cursorArea.addEventListener('pointermove', (event) => {
            const rect = cursorArea.getBoundingClientRect();
            target = { x: event.clientX - rect.left - 36, y: event.clientY - rect.top - 36 };
            if (!frame) {
                frame = requestAnimationFrame(moveCursor);
            }
        }, { passive: true });

        cursorArea.addEventListener('pointerleave', () => {
            target = null;
            cursorArea.classList.remove('is-cursor-active');
            window.cancelAnimationFrame(frame);
            frame = 0;
        });
    }
}
