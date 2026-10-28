const aboutVisual = document.querySelector('[data-about-visual]');

if (aboutVisual) {
    const image = aboutVisual.querySelector('img');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let visible = false;
    let frame = 0;

    const updateParallax = () => {
        if (!visible || !image || reducedMotion.matches) {
            frame = 0;
            return;
        }

        const rect = aboutVisual.getBoundingClientRect();
        const distance = (rect.top + (rect.height / 2) - (window.innerHeight / 2)) / window.innerHeight;
        const offset = Math.max(-24, Math.min(24, distance * -36));
        image.style.setProperty('--about-parallax', `${offset}px`);
        frame = 0;
    };

    const scheduleUpdate = () => {
        if (!frame) {
            frame = requestAnimationFrame(updateParallax);
        }
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            aboutVisual.classList.toggle('is-ambient-active', visible);

            if (visible) {
                aboutVisual.classList.add('is-visible');
                aboutVisual.closest('.home-about__inner')?.classList.add('is-visible');
                window.addEventListener('scroll', scheduleUpdate, { passive: true });
                scheduleUpdate();
            } else {
                window.removeEventListener('scroll', scheduleUpdate);
                window.cancelAnimationFrame(frame);
                frame = 0;
            }
        }, { threshold: 0.15 })
        : null;

    if (observer) {
        observer.observe(aboutVisual);
    } else {
        aboutVisual.classList.add('is-visible');
        aboutVisual.closest('.home-about__inner')?.classList.add('is-visible');
    }

    reducedMotion.addEventListener?.('change', () => {
        if (reducedMotion.matches && image) {
            image.style.setProperty('--about-parallax', '0px');
        } else {
            scheduleUpdate();
        }
    });

    window.setTimeout(() => aboutVisual.classList.add('is-visible'), 5000);
}

const aboutPage = document.querySelector('[data-about-page]');

if (aboutPage) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const organization = aboutPage.querySelector('[data-organization]');
    const members = [...organization.querySelectorAll('[data-person-id]')];
    const filterBar = organization.querySelector('.about-filters');
    const filterButtons = [...filterBar.querySelectorAll('button')];
    const groups = [...organization.querySelectorAll('[data-people-group]')];
    const applyFilter = (filter) => {
        filterButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.organizationFilter === filter)));
        let count = 0;
        members.forEach((member) => {
            const show = filter === 'all' || member.dataset.personFilter === filter;
            member.hidden = !show;
            if (show) {
                count += 1;
                member.style.setProperty('--reveal-delay', `${(count - 1) % 4 * 100}ms`);
                member.classList.remove('is-in', 'is-visible');
                member.style.setProperty('--d', String((count - 1) % 4 * 100));
                requestAnimationFrame(() => requestAnimationFrame(() => member.classList.add('is-in', 'is-visible')));
            }
        });
        groups.forEach((group) => {
            group.hidden = ![...group.querySelectorAll('[data-person-id]')].some((member) => !member.hidden);
            group.querySelector('ul').scrollLeft = 0;
        });
        organization.querySelector('[data-organization-status]').textContent = `${count} pengurus ditampilkan.`;
    };
    filterBar.hidden = false;
    filterButtons.forEach((button) => button.addEventListener('click', () => applyFilter(button.dataset.organizationFilter)));

    const dialog = organization.querySelector('[data-person-dialog]');
    const dialogPhoto = dialog.querySelector('[data-dialog-photo]');
    const dialogInitials = dialog.querySelector('[data-dialog-initials]');
    const report = dialog.querySelector('[data-dialog-report]');
    let activeTrigger = null;
    let previousOverflow = '';
    const openPerson = (member) => {
        const card = member.querySelector('.org-person');
        const photo = member.querySelector('img');
        activeTrigger = member.querySelector('[data-person-open]');
        dialog.querySelector('#person-dialog-name').textContent = card.querySelector('h4').textContent;
        dialog.querySelector('[data-dialog-position]').textContent = card.querySelector('.org-person__identity p').textContent;
        dialogPhoto.hidden = !photo;
        dialogInitials.hidden = Boolean(photo);
        if (photo) {
            dialogPhoto.src = photo.src;
            dialogPhoto.alt = photo.alt;
        } else {
            dialogPhoto.removeAttribute('src');
            dialogInitials.textContent = member.querySelector('.org-person__initials')?.textContent ?? '';
        }
        report.replaceChildren();
        const parent = members.find((candidate) => candidate.dataset.personId === member.dataset.parentId);
        if (parent) {
            const label = document.createElement('span');
            label.textContent = 'Melapor kepada';
            const link = document.createElement('a');
            link.href = `#org-person-${parent.dataset.personId}`;
            link.textContent = parent.querySelector('h4').textContent;
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const parentCard = parent.querySelector('.org-person');
                activeTrigger = parentCard;
                dialog.close();
                applyFilter('all');
                parentCard.focus({ preventScroll: true });
                parentCard.scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth', block: 'center', inline: 'center' });
                parentCard.classList.add('is-highlighted');
                window.setTimeout(() => parentCard.classList.remove('is-highlighted'), 2500);
            });
            report.append(label, link);
        } else {
            report.textContent = 'Pembina Utama';
        }
        previousOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = 'hidden';
        dialog.showModal();
    };
    members.forEach((member) => member.querySelector('.org-person').addEventListener('click', () => openPerson(member)));
    dialog.querySelector('[data-dialog-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) {
                dialog.close();
            }
        }
    });
    dialog.addEventListener('close', () => {
        document.documentElement.style.overflow = previousOverflow;
        activeTrigger?.focus({ preventScroll: true });
    });

    // Network failures use the same initials fallback as missing server-side photos.
    members.forEach((member) => {
        const image = member.querySelector('img');
        const useInitials = () => {
            const initials = document.createElement('span');
            initials.className = 'org-person__initials';
            initials.setAttribute('aria-hidden', 'true');
            initials.textContent = member.querySelector('h4').textContent.split(',')[0].trim().split(/\s+/).slice(0, 2).map((word) => word[0]).join('');
            image.replaceWith(initials);
        };
        image?.addEventListener('error', useInitials, { once: true });
        if (image?.complete && !image.naturalWidth) {
            useInitials();
        }
    });

    const copyButton = aboutPage.querySelector('[data-copy-phone]');
    const copyStatus = aboutPage.querySelector('[data-copy-status]');
    let copyTimer = 0;
    copyButton.hidden = false;
    copyButton.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText('3215');
            clearTimeout(copyTimer);
            copyButton.textContent = 'Tersalin';
            copyStatus.textContent = 'Nomor 3215 berhasil disalin.';
            copyTimer = window.setTimeout(() => { copyButton.textContent = 'Salin nomor'; }, 2000);
        } catch {
            copyStatus.textContent = 'Nomor telepon: 3215. Pilih nomor pada bagian informasi untuk menyalinnya.';
            copyButton.textContent = 'Nomor: 3215';
        }
    });
}
