const revealTargets = document.querySelectorAll('.home-reveal, .home-facts, .home-join, [data-footer-reveal]');
const headings = document.querySelectorAll('.home-page h2, .home-page h3');

if (revealTargets.length || headings.length) {
    document.documentElement.classList.add('js');

    const revealAll = () => {
        revealTargets.forEach((target) => target.classList.add('is-visible'));
        headings.forEach((heading) => heading.classList.add('is-heading-visible'));
    };

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const wrapWords = (heading) => {
        if (heading.dataset.wordsWrapped) {
            return;
        }

        const walk = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walk.nextNode()) {
            if (walk.currentNode.textContent.trim()) {
                nodes.push(walk.currentNode);
            }
        }

        let wordIndex = 0;
        nodes.forEach((node) => {
            const fragment = document.createDocumentFragment();
            const pieces = node.textContent.split(/(\s+)/);

            pieces.forEach((piece) => {
                if (!piece) {
                    return;
                }

                if (/^\s+$/.test(piece)) {
                    const spacing = document.createElement('span');
                    spacing.className = 'home-heading-space';
                    spacing.textContent = piece;
                    fragment.append(spacing);
                    return;
                }

                const mask = document.createElement('span');
                const word = document.createElement('span');
                mask.className = 'home-heading-mask';
                word.className = 'home-heading-word';
                word.style.setProperty('--heading-delay', `${wordIndex * 60}ms`);
                word.textContent = piece;
                mask.append(word);
                fragment.append(mask);
                wordIndex += 1;
            });

            node.replaceWith(fragment);
        });

        heading.dataset.wordsWrapped = 'true';
    };

    headings.forEach(wrapWords);

    if (reducedMotion || !('IntersectionObserver' in window)) {
        revealAll();
    } else {
        revealTargets.forEach((target, index) => {
            target.style.setProperty('--reveal-delay', `${(index % 8) * 70}ms`);
        });

        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });

        revealTargets.forEach((target) => observer.observe(target));

        const headingObserver = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-heading-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.25 });

        headings.forEach((heading) => headingObserver.observe(heading));
        window.setTimeout(revealAll, 5000);
    }
}
