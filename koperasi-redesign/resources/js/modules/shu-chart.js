const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-shu-count-up]').forEach((counter) => {
    const end = Number(counter.dataset.shuCountUp);
    let counted = false;

    const animateCount = () => {
        if (counted) return;
        counted = true;

        if (reducedMotion.matches || !('requestAnimationFrame' in window)) {
            counter.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(end)}`;
            return;
        }

        const startedAt = performance.now();
        const duration = 1200;
        const tick = (now) => {
            const progress = Math.min(1, (now - startedAt) / duration);
            const eased = 1 - ((1 - progress) ** 3);
            const value = Math.round(end * eased);
            counter.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;

            if (progress < 1) {
                window.requestAnimationFrame(tick);
            } else {
                counter.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(end)}`;
            }
        };

        window.requestAnimationFrame(tick);
    };

    if (reducedMotion.matches || !('IntersectionObserver' in window)) {
        animateCount();
    } else {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCount();
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: .5 });
        observer.observe(counter);
    }
});

document.querySelectorAll('[data-shu-chart]').forEach((chart) => {
    const bars = [...chart.querySelectorAll('[data-chart-bar]')];
    const showChart = () => chart.classList.add('is-visible');
    const closeTooltips = () => bars.forEach((bar) => {
        bar.classList.remove('is-active');
        bar.setAttribute('aria-pressed', 'false');
    });

    if (reducedMotion.matches || !('IntersectionObserver' in window)) {
        showChart();
    } else {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    showChart();
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: .2 });
        observer.observe(chart);
    }

    bars.forEach((bar) => {
        bar.addEventListener('click', () => {
            const wasActive = bar.classList.contains('is-active');
            closeTooltips();
            if (!wasActive) {
                bar.classList.add('is-active');
                bar.setAttribute('aria-pressed', 'true');
            }
        });
    });

    document.addEventListener('pointerdown', (event) => {
        if (!chart.contains(event.target)) closeTooltips();
    });
    chart.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeTooltips();
    });
});
