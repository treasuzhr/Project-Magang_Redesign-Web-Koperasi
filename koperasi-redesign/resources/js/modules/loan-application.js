const form = document.querySelector('[data-loan-form]');

if (form) {
    const totalInput = form.querySelector('[data-loan-total]');
    const totalHelp = form.querySelector('[data-loan-total-help]');
    const termInput = form.querySelector('[data-loan-term]');
    const typeInput = form.querySelector('[name="loan_type"]');
    const values = [...form.querySelectorAll('.loan-simulation-row strong')];
    const kinds = JSON.parse(form.dataset.loanKinds || '{}');
    const currency = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
    let timer;
    let pending;
    let revision = 0;

    const updateSimulation = () => {
        clearTimeout(timer);
        pending?.abort();
        const currentRevision = ++revision;
        const kind = kinds[typeInput.value];
        const amount = totalInput.value.replace(/^Rp\s*/i, '').replace(/[., ]/g, '');
        const term = termInput.value.trim();
        values.forEach((value) => { value.textContent = '-'; });
        if (totalHelp) totalHelp.textContent = /^[0-9]+$/.test(amount) ? `Rp ${currency.format(Number(amount))}` : '';
        termInput.setAttribute('max', kind?.tenor_maks ?? '');
        totalInput.setAttribute('max', kind?.plafon_maks ?? '');
        if (!kind || !/^[0-9]+$/.test(amount) || !/^[0-9]+$/.test(term)
            || Number(amount) < 1 || Number(amount) > kind.plafon_maks
            || Number(term) < 1 || Number(term) > kind.tenor_maks) return;

        timer = setTimeout(async () => {
            pending = new AbortController();
            const url = new URL(form.dataset.simulationUrl, window.location.origin);
            url.search = new URLSearchParams({ loan_type: typeInput.value, loan_total: totalInput.value, loan_term: term });
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' }, signal: pending.signal, credentials: 'same-origin',
                });
                if (!response.ok) return;
                const estimate = await response.json();
                if (revision !== currentRevision) return;
                ['principal', 'interest', 'total'].forEach((key, index) => {
                    values[index].textContent = `Rp ${currency.format(estimate[key])}`;
                });
            } catch (error) {
                // Keep the empty estimate on network failure; submission is validated by the server.
            }
        }, 300);
    };

    totalInput.addEventListener('input', updateSimulation);
    termInput.addEventListener('input', updateSimulation);
    typeInput.addEventListener('change', updateSimulation);
    updateSimulation();
    form.querySelector('[aria-invalid="true"]')?.focus({ preventScroll: true });
    form.addEventListener('submit', () => {
        form.querySelector('[data-loan-submit]').disabled = true;
        form.querySelector('[data-loan-submit-label]').textContent = 'Mengirim...';
    });
    window.addEventListener('pageshow', () => {
        form.querySelector('[data-loan-submit]').disabled = false;
        form.querySelector('[data-loan-submit-label]').textContent = 'Ajukan pinjaman';
    });
}
