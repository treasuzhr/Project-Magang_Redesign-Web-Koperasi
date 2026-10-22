const showcase = document.querySelector('[data-category-showcase]');

if (showcase) {
    const items = [...showcase.querySelectorAll('[data-category-index]')];
    const panels = [...showcase.querySelectorAll('[data-category-panel]')];
    const mobileDescription = showcase.querySelector('.category-showcase__mobile-description');
    const mobileLink = showcase.querySelector('[data-category-mobile-link]');
    const currentCount = showcase.querySelector('[data-category-current]');
    const segments = [...showcase.querySelectorAll('[data-category-segment]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let activeIndex = Math.max(0, items.findIndex((item) => item.classList.contains('is-active')));
    let progressFrame = 0;
    let userInteracted = false;
    let pointerWithinList = false;
    let focusedInList = false;
    let showcaseVisible = false;
    let imagesPreloaded = false;
    let activeSince = performance.now();
    const interval = 4000;

    const preloadImages = () => {
        panels.forEach((panel) => {
            const image = panel.querySelector('img');

            if (image && !image.complete) {
                image.loading = 'eager';
            }
        });
    };

    const setActive = (index, { interacted = false, resetTimer = true } = {}) => {
        if (!items[index]) {
            return;
        }

        if (interacted) {
            userInteracted = true;
        }

        items.forEach((item, itemIndex) => {
            const active = itemIndex === index;

            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
            item.tabIndex = active ? 0 : -1;
            item.style.setProperty('--category-progress', '0');
        });

        segments.forEach((segment, segmentIndex) => {
            segment.classList.toggle('is-active', segmentIndex === index);
            segment.style.setProperty('--segment-progress', '0');
        });

        panels.forEach((panel, panelIndex) => {
            const active = panelIndex === index;

            if (active) {
                panel.hidden = false;
                panel.classList.remove('is-leaving');
                requestAnimationFrame(() => panel.classList.add('is-active'));
            } else if (!panel.hidden) {
                panel.classList.remove('is-active');
                panel.classList.add('is-leaving');
                window.setTimeout(() => {
                    if (panelIndex !== activeIndex) {
                        panel.hidden = true;
                        panel.classList.remove('is-leaving');
                    }
                }, reducedMotion.matches ? 0 : 700);
            }
        });

        activeIndex = index;
        if (mobileDescription) {
            mobileDescription.textContent = items[index].dataset.categoryDescription || '';
        }
        if (mobileLink) {
            mobileLink.href = items[index].dataset.categoryUrl || '#';
        }
        if (currentCount) {
            currentCount.textContent = String(index + 1).padStart(2, '0');
            currentCount.classList.remove('is-changing');
            requestAnimationFrame(() => currentCount.classList.add('is-changing'));
        }
        activeSince = performance.now();
        if (resetTimer) {
            startProgress();
        }
    };

    const canAutoplay = () => window.innerWidth >= 860
        && !reducedMotion.matches
        && showcaseVisible
        && !pointerWithinList
        && !focusedInList
        && !userInteracted;

    const updateProgress = (now) => {
        const activeItem = items[activeIndex];
        if (!activeItem) {
            return;
        }

        const progress = Math.min(1, (now - activeSince) / interval);
        activeItem.style.setProperty('--category-progress', String(progress));
        segments[activeIndex]?.style.setProperty('--segment-progress', String(progress));

        if (progress >= 1 && canAutoplay()) {
            setActive((activeIndex + 1) % items.length, { resetTimer: false });
        }

        if (canAutoplay()) {
            progressFrame = requestAnimationFrame(updateProgress);
        } else {
            progressFrame = 0;
        }
    };

    const startProgress = () => {
        window.cancelAnimationFrame(progressFrame);
        items.forEach((item) => item.style.setProperty('--category-progress', '0'));

        if (canAutoplay()) {
            activeSince = performance.now();
            progressFrame = requestAnimationFrame(updateProgress);
        }
    };

    const stopProgress = () => {
        window.cancelAnimationFrame(progressFrame);
        progressFrame = 0;
        items.forEach((item) => item.style.setProperty('--category-progress', '0'));
    };

    items.forEach((item, index) => {
        item.addEventListener('mouseenter', () => {
            pointerWithinList = true;
            stopProgress();
            setActive(index, { resetTimer: false });
        });

        item.addEventListener('click', () => setActive(index, { interacted: true }));

        item.addEventListener('focus', () => {
            focusedInList = true;
            stopProgress();
            setActive(index);
        });

        item.addEventListener('keydown', (event) => {
            let nextIndex = index;

            if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
                nextIndex = (index + 1) % items.length;
            } else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
                nextIndex = (index - 1 + items.length) % items.length;
            } else if (event.key === 'Enter' || event.key === ' ') {
                setActive(index, { interacted: true });
                event.preventDefault();
                return;
            } else {
                return;
            }

            event.preventDefault();
            items[nextIndex].focus();
            setActive(nextIndex, { interacted: true });
        });
    });

    const list = showcase.querySelector('.category-showcase__list');
    list?.addEventListener('mouseleave', () => {
        pointerWithinList = false;
        userInteracted = false;
        startProgress();
    });
    list?.addEventListener('focusout', (event) => {
        if (!list.contains(event.relatedTarget)) {
            focusedInList = false;
            userInteracted = false;
            startProgress();
        }
    });

    const sectionObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry]) => {
            showcaseVisible = entry.isIntersecting;
            if (entry.isIntersecting && !imagesPreloaded) {
                preloadImages();
                imagesPreloaded = true;
            }
            canAutoplay() ? startProgress() : stopProgress();
        }, { rootMargin: '300px' })
        : null;

    if (sectionObserver) {
        sectionObserver.observe(showcase);
    } else {
        showcaseVisible = true;
        preloadImages();
    }

    reducedMotion.addEventListener?.('change', () => canAutoplay() ? startProgress() : stopProgress());
    window.addEventListener('resize', () => canAutoplay() ? startProgress() : stopProgress(), { passive: true });
    setActive(activeIndex);
}
