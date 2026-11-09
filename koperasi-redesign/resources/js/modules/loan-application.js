const form = document.querySelector('[data-loan-form]');

if (form) {
    const totalInput = form.querySelector('[data-loan-total]');
    const totalHelp = form.querySelector('[data-loan-total-help]');
    const termInput = form.querySelector('[data-loan-term]');
    const simulationValues = [...form.querySelectorAll('.loan-simulation-row strong')];
    const submitButton = form.querySelector('[data-loan-submit]');
    const submitLabel = form.querySelector('[data-loan-submit-label]');
    const firstInvalidField = form.querySelector('[aria-invalid="true"]');

    const formatRupiah = (digits) => digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const updateSimulation = () => {
        const totalDigits = totalInput.value.replace(/\D/g, '');
        const termDigits = termInput.value.trim();
        let principal = '-';

        if (totalDigits && /^[0-9]+$/.test(termDigits)) {
            const total = BigInt(totalDigits);
            const term = BigInt(termDigits);

            if (total > 0n && term > 0n) {
                let rounded = total / term;
                const remainder = total % term;
                if (remainder * 2n >= term) rounded += 1n;
                principal = `Rp ${formatRupiah(rounded.toString())}`;
            }
        }

        simulationValues[0].textContent = principal;
        simulationValues[1].textContent = '-';
        simulationValues[2].textContent = '-';

        if (totalHelp) {
            totalHelp.textContent = totalDigits ? `Rp ${formatRupiah(totalDigits)}` : '';
        }
    };

    totalInput.addEventListener('input', updateSimulation);
    termInput.addEventListener('input', updateSimulation);
    updateSimulation();

    if (firstInvalidField) {
        firstInvalidField.focus({ preventScroll: true });
    }

    form.addEventListener('submit', () => {
        submitButton.disabled = true;
        submitLabel.textContent = 'Mengirim...';
    });
}
