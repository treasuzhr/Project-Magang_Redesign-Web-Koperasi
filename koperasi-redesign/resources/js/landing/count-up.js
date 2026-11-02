const strip = document.querySelector('.home-facts');
const memberCount = strip?.querySelector('[data-member-count]');

if (strip && memberCount) {
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    let frame = 0;
    let started = false;
    let observer;
    const finish = () => {
        cancelAnimationFrame(frame);
        memberCount.textContent = '160';
        strip.classList.add('stats-visible');
        observer?.disconnect();
    };
    const reveal = () => {
        if (started) return;
        started = true;
        strip.classList.add('stats-visible');
        observer?.disconnect();
        if (reduced.matches) { finish(); return; }
        const start = performance.now();
        memberCount.textContent = '0';
        const tick = now => {
            const progress = Math.min(1, (now - start) / 1200);
            memberCount.textContent = String(Math.round(160 * (1 - (1 - progress) ** 3)));
            if (progress < 1) frame = requestAnimationFrame(tick);
            else finish();
        };
        frame = requestAnimationFrame(tick);
    };
    if (reduced.matches || !('IntersectionObserver' in window)) {
        finish();
    } else {
        strip.classList.add('stats-ready');
        observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) reveal();
        }, { threshold: .25 });
        observer.observe(strip);
        setTimeout(finish, 5000);
    }
    reduced.addEventListener('change', () => { if (reduced.matches) { started = true; finish(); } });
}
