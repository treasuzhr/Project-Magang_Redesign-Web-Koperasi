const counters = [...document.querySelectorAll('[data-count-up]')];

if (counters.length) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const complete = (counter) => {
        counter.textContent = counter.dataset.countEnd;
        counter.dataset.counted = 'true';
    };

    const animate = (counter) => {
        if (counter.dataset.counted === 'true') {
            return;
        }

        const start = Number(counter.dataset.countStart);
        const end = Number(counter.dataset.countEnd);

        if (reducedMotion || !('requestAnimationFrame' in window)) {
            complete(counter);
            return;
        }

        const duration = 1400;
        const startedAt = performance.now();
        counter.textContent = String(start);

        const tick = (now) => {
            const progress = Math.min(1, (now - startedAt) / duration);
            const eased = 1 - ((1 - progress) ** 3);
            const current = Math.round(start + ((end - start) * eased));

            counter.textContent = String(current);

            if (progress < 1) {
                window.requestAnimationFrame(tick);
            } else {
                complete(counter);
            }
        };

        window.requestAnimationFrame(tick);
    };

    if (reducedMotion || !('IntersectionObserver' in window)) {
        counters.forEach(animate);
    } else {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach((counter) => observer.observe(counter));
        counters.forEach((counter) => observer.observe(counter));
    }
}
