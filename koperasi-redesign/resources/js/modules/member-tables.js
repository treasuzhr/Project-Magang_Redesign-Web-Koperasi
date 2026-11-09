const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-member-table]').forEach((table) => {
    const rows = [...table.querySelectorAll('[data-table-row]')];
    const lengthSelect = table.querySelector('[data-table-length]');
    const info = table.querySelector('[data-table-info]');
    const pagination = table.querySelector('[data-table-pagination]');
    const previousButton = table.querySelector('[data-page-previous]');
    const nextButton = table.querySelector('[data-page-next]');
    const pageNumbers = table.querySelector('[data-page-numbers]');
    let page = 1;
    let pageSize = Number(lengthSelect.value);

    lengthSelect.disabled = false;
    pagination.hidden = false;

    const render = () => {
        const pageCount = Math.max(1, Math.ceil(rows.length / pageSize));
        page = Math.min(page, pageCount);
        const start = rows.length === 0 ? 0 : (page - 1) * pageSize;
        const end = Math.min(start + pageSize, rows.length);

        rows.forEach((row, index) => {
            row.hidden = index < start || index >= end;
        });

        info.textContent = `Menampilkan ${rows.length === 0 ? 0 : start + 1}-${end} dari ${rows.length} data`;
        previousButton.disabled = page <= 1;
        nextButton.disabled = page >= pageCount || rows.length === 0;
        pageNumbers.replaceChildren();

        if (rows.length === 0) {
            return;
        }

        const firstPage = Math.max(1, Math.min(page - 2, pageCount - 4));
        const lastPage = Math.min(pageCount, firstPage + 4);

        for (let pageNumber = firstPage; pageNumber <= lastPage; pageNumber += 1) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `member-pagination__page${pageNumber === page ? ' is-current' : ''}`;
            button.textContent = String(pageNumber);
            button.setAttribute('aria-label', `Halaman ${pageNumber}`);

            if (pageNumber === page) {
                button.setAttribute('aria-current', 'page');
            }

            button.addEventListener('click', () => {
                page = pageNumber;
                render();
            });
            pageNumbers.append(button);
        }
    };

    lengthSelect.addEventListener('change', () => {
        pageSize = Number(lengthSelect.value);
        page = 1;
        render();
    });

    previousButton.addEventListener('click', () => {
        page -= 1;
        render();
    });

    nextButton.addEventListener('click', () => {
        page += 1;
        render();
    });

    render();
});

document.querySelectorAll('[data-member-tabs]').forEach((scrollContainer) => {
    const track = scrollContainer.querySelector('.member-tabs__track');
    const indicator = scrollContainer.querySelector('.member-tabs__indicator');
    const tabs = [...scrollContainer.querySelectorAll('[data-member-tab]')];
    const activeIndex = tabs.findIndex((tab) => tab.hasAttribute('aria-current'));

    if (!indicator || activeIndex < 0) {
        return;
    }

    const activeTab = tabs[activeIndex];
    const currentBounds = {
        left: activeTab.offsetLeft,
        width: activeTab.offsetWidth,
    };
    const storedPreviousIndex = sessionStorage.getItem('member-tabs-next-index');
    const previousIndex = storedPreviousIndex === null ? -1 : Number(storedPreviousIndex);
    sessionStorage.removeItem('member-tabs-next-index');
    const previousTab = Number.isInteger(previousIndex) ? tabs[previousIndex] : null;
    const animateFromPrevious = previousTab && previousIndex !== activeIndex && !reducedMotion.matches;

    indicator.style.width = `${currentBounds.width}px`;

    if (animateFromPrevious) {
        indicator.style.transition = 'none';
        indicator.style.transform = `translateX(${previousTab.offsetLeft}px) scaleX(${previousTab.offsetWidth / currentBounds.width})`;
        scrollContainer.classList.add('is-enhanced');
        indicator.getBoundingClientRect();
        requestAnimationFrame(() => {
            indicator.style.removeProperty('transition');
            indicator.style.transform = `translateX(${currentBounds.left}px) scaleX(1)`;
        });
    } else {
        indicator.style.transform = `translateX(${currentBounds.left}px) scaleX(1)`;
        scrollContainer.classList.add('is-enhanced');
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            sessionStorage.setItem('member-tabs-next-index', String(index));
        });
    });

    const revealActiveTab = () => {
        const target = activeTab.offsetLeft + activeTab.offsetWidth / 2 - scrollContainer.clientWidth / 2;
        const left = Math.max(0, Math.min(target, scrollContainer.scrollWidth - scrollContainer.clientWidth));
        scrollContainer.scrollTo({ left, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    };

    revealActiveTab();
    window.addEventListener('resize', () => {
        indicator.style.width = `${activeTab.offsetWidth}px`;
        indicator.style.transform = `translateX(${activeTab.offsetLeft}px) scaleX(1)`;
        revealActiveTab();
    }, { passive: true });
});
