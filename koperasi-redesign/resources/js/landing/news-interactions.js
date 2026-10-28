const progress = document.querySelector('[data-news-progress]');

if (progress) {
    let frame = 0;
    const update = () => {
        const distance = document.documentElement.scrollHeight - innerHeight;
        progress.style.setProperty('--news-progress', String(distance > 0 ? Math.max(0, Math.min(1, scrollY / distance)) : 0));
        frame = 0;
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule, { passive: true });
    update();
}
