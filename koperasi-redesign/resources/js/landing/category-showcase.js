const showcase = document.querySelector('[data-category-showcase]');

if (showcase) {
    const items = [...showcase.querySelectorAll('[data-category-index]')];
    const panels = [...showcase.querySelectorAll('[data-category-panel]')];
    const segments = [...showcase.querySelectorAll('[data-category-segment]')];
    const list = showcase.querySelector('[role="tablist"]');
    const highlight = showcase.querySelector('.category-showcase__highlight');
    const counter = showcase.querySelector('[data-category-current]');
    const preview = showcase.querySelector('.category-showcase__preview');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const desktop = matchMedia('(min-width: 860px)');
    const fine = matchMedia('(hover: hover) and (pointer: fine)');
    const hideTimers = new Map();
    let activeIndex = 0;
    let visible = false;
    let hovered = false;
    let frame = 0;
    let pointerFrame = 0;
    let started = performance.now();
    let counterAnimation;
    const duration = 4000;
    const easing = 'cubic-bezier(.2,.8,.2,1)';

    const positionHighlight = () => {
        const item = items[activeIndex];
        if (!item || !desktop.matches) return;
        highlight.style.height = `${item.offsetHeight}px`;
        highlight.style.transform = `translateY(${item.offsetTop}px)`;
    };
    const canPlay = () => desktop.matches && !reduced.matches && visible && !hovered
        && !showcase.contains(document.activeElement) && !document.hidden && items.length > 1;
    const resetProgress = () => {
        highlight.style.setProperty('--category-progress', '0');
        segments.forEach(segment => segment.style.setProperty('--segment-progress', '0'));
    };
    const stop = () => {
        cancelAnimationFrame(frame);
        frame = 0;
        showcase.classList.remove('is-playing');
    };
    const tick = now => {
        if (!canPlay()) { stop(); return; }
        const progress = Math.min(1, (now - started) / duration);
        highlight.style.setProperty('--category-progress', progress);
        segments[activeIndex]?.style.setProperty('--segment-progress', progress);
        if (progress >= 1) activate((activeIndex + 1) % items.length, false);
        frame = requestAnimationFrame(tick);
    };
    const play = () => {
        stop();
        resetProgress();
        started = performance.now();
        if (canPlay()) {
            showcase.classList.add('is-playing');
            frame = requestAnimationFrame(tick);
        }
    };
    const activate = (index, restart = true) => {
        if (!items[index]) return;
        const changed = index !== activeIndex;
        const children = items.flatMap(item => [...item.children]);
        const before = children.map(child => child.getBoundingClientRect());
        activeIndex = index;
        items.forEach((item, position) => {
            item.classList.toggle('is-active', position === index);
            item.classList.toggle('is-before-active', position === index - 1);
            item.setAttribute('aria-selected', String(position === index));
            item.tabIndex = position === index ? 0 : -1;
        });
        if (changed && desktop.matches && !reduced.matches) {
            children.forEach((child, position) => {
                const after = child.getBoundingClientRect();
                child.getAnimations().forEach(animation => animation.cancel());
                child.animate([{ transform: `translateY(${before[position].top - after.top}px)` }, { transform: 'translateY(0)' }], { duration: 450, easing });
            });
        }
        positionHighlight();
        segments.forEach((segment, position) => segment.classList.toggle('is-active', position === index));
        panels.forEach((panel, position) => {
            clearTimeout(hideTimers.get(panel));
            panel.inert = position !== index;
            if (position === index) {
                panel.hidden = false;
                if (changed) {
                    panel.classList.remove('is-active');
                    void panel.offsetWidth;
                }
                panel.classList.add('is-active');
            } else {
                panel.classList.remove('is-active');
                hideTimers.set(panel, setTimeout(() => {
                    if (position !== activeIndex) panel.hidden = true;
                }, reduced.matches ? 0 : 550));
            }
        });
        if (changed) {
            const old = counter.textContent;
            const next = String(index + 1).padStart(2, '0');
            counterAnimation?.cancel();
            counter.replaceChildren();
            const outgoing = document.createElement('span');
            const incoming = document.createElement('span');
            outgoing.textContent = old.slice(-2);
            incoming.textContent = next;
            counter.append(outgoing, incoming);
            if (!reduced.matches) {
                counterAnimation = counter.animate([{ transform: 'translateY(0)' }, { transform: 'translateY(-50%)' }], { duration: 350, easing });
                counterAnimation.onfinish = () => counter.replaceChildren(incoming);
            } else counter.replaceChildren(incoming);
        }
        showcase.querySelector('.category-showcase__mobile-description').textContent = items[index].dataset.categoryDescription;
        showcase.querySelector('[data-category-mobile-link]').href = items[index].dataset.categoryUrl;
        resetProgress();
        started = performance.now();
        if (restart) play();
    };
    items.forEach((item, index) => {
        item.addEventListener('pointerenter', event => {
            if (event.pointerType !== 'touch' && fine.matches && desktop.matches) activate(index);
        }, { passive: true });
        item.addEventListener('click', () => activate(index));
        item.addEventListener('focus', () => { stop(); activate(index); });
        item.addEventListener('keydown', event => {
            let next;
            if (['ArrowDown', 'ArrowRight'].includes(event.key)) next = (index + 1) % items.length;
            else if (['ArrowUp', 'ArrowLeft'].includes(event.key)) next = (index - 1 + items.length) % items.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = items.length - 1;
            else return;
            event.preventDefault();
            items[next].focus({ preventScroll: true });
            if (!desktop.matches) items[next].scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
    });
    showcase.addEventListener('pointerenter', event => {
        if (event.pointerType !== 'touch') { hovered = true; stop(); }
    }, { passive: true });
    showcase.addEventListener('pointerleave', () => { hovered = false; play(); }, { passive: true });
    showcase.addEventListener('focusout', event => {
        if (!showcase.contains(event.relatedTarget)) queueMicrotask(play);
    });
    preview.addEventListener('pointermove', event => {
        if (reduced.matches || !fine.matches || !desktop.matches) return;
        const bounds = preview.getBoundingClientRect();
        const x = Math.max(-10, Math.min(10, ((event.clientX - bounds.left) / bounds.width - .5) * 20));
        const y = Math.max(-10, Math.min(10, ((event.clientY - bounds.top) / bounds.height - .5) * 20));
        cancelAnimationFrame(pointerFrame);
        pointerFrame = requestAnimationFrame(() => {
            preview.style.setProperty('--photo-x', `${x}px`);
            preview.style.setProperty('--photo-y', `${y}px`);
        });
    }, { passive: true });
    const resetPointer = () => {
        cancelAnimationFrame(pointerFrame);
        preview.style.setProperty('--photo-x', '0px');
        preview.style.setProperty('--photo-y', '0px');
    };
    preview.addEventListener('pointerleave', resetPointer, { passive: true });
    const resize = () => {
        list.setAttribute('aria-orientation', desktop.matches ? 'vertical' : 'horizontal');
        positionHighlight();
        resetPointer();
        play();
    };
    let resizeFrame = 0;
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeFrame);
        resizeFrame = requestAnimationFrame(resize);
    }, { passive: true });
    reduced.addEventListener('change', resize);
    document.addEventListener('visibilitychange', play);
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            if (visible) {
                showcase.classList.add('is-visible');
                panels.forEach(panel => { const image = panel.querySelector('img'); if (image) image.loading = 'eager'; });
            }
            play();
        }, { threshold: .15 }).observe(showcase);
    } else { visible = true; showcase.classList.add('is-visible'); }
    setTimeout(() => showcase.classList.add('is-visible'), 5000);
    activate(0);
    resize();
}
