const progress = document.querySelector('[data-news-progress]');

if (progress) {
    let frame = 0;
    const update = () => {
        const article = document.querySelector('[data-reading-article]');
        const body = article?.querySelector('.news-article__body');
        const start = article ? article.getBoundingClientRect().top + scrollY : 0;
        const end = body ? body.getBoundingClientRect().bottom + scrollY : document.documentElement.scrollHeight;
        const distance = end - innerHeight - start;
        progress.style.setProperty('--news-progress', String(distance > 0 ? Math.max(0, Math.min(1, (scrollY - start) / distance)) : 0));
        frame = 0;
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule, { passive: true });
    update();
}

const copyButton = document.querySelector('.article-redesign [data-copy-news]');

if (copyButton) {
    const status = document.querySelector('[data-copy-status]');
    let resetTimer;
    copyButton.hidden = false;
    copyButton.addEventListener('click', async () => {
        clearTimeout(resetTimer);
        try {
            if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
            await navigator.clipboard.writeText(copyButton.dataset.url);
            copyButton.textContent = 'Tersalin';
            status.textContent = 'Tautan berita tersalin.';
            resetTimer = setTimeout(() => {
                copyButton.textContent = 'Salin tautan';
                status.textContent = '';
            }, 2000);
        } catch {
            copyButton.textContent = 'Salin tautan';
            status.textContent = 'Tautan belum tersalin. Salin alamat dari bilah peramban.';
        }
    });
}

const inlineImages = document.querySelectorAll('.article-redesign [data-news-inline-image]');

if (inlineImages.length) {
    const motion = matchMedia('(prefers-reduced-motion: reduce)');
    let observer;
    const revealImages = () => {
        observer?.disconnect();
        inlineImages.forEach(image => image.classList.remove('is-inline-revealing'));
        if (motion.matches || !('IntersectionObserver' in window)) return;
        observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-inline-revealing');
            observer.unobserve(entry.target);
        }), { threshold: .12 });
        inlineImages.forEach(image => observer.observe(image));
    };
    inlineImages.forEach(figure => {
        figure.querySelector('img').addEventListener('error', () => {
            observer?.unobserve(figure);
            figure.remove();
        });
    });
    motion.addEventListener('change', revealImages);
    revealImages();
}
