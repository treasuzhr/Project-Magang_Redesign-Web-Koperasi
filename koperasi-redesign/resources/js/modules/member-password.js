const form = document.querySelector('[data-member-password-form]');

if (form) {
    form.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = document.getElementById(toggle.dataset.passwordToggle);
        if (!input) return;

        toggle.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
            toggle.setAttribute('aria-pressed', String(visible));
            input.focus({ preventScroll: true });
        });
    });

    const firstInvalid = form.querySelector('[aria-invalid="true"]');
    if (firstInvalid) {
        firstInvalid.focus({ preventScroll: true });
    }

    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-password-submit]');
        const label = submit?.querySelector('[data-password-submit-label]');
        if (!submit || !label) return;

        submit.disabled = true;
        label.textContent = 'Menyimpan...';
    });
}
