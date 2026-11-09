const form = document.querySelector('[data-avatar-upload-form]');

if (form) {
    const input = form.querySelector('[data-avatar-input]');
    const dropzone = form.querySelector('[data-avatar-dropzone]');
    const fileCard = form.querySelector('[data-avatar-file]');
    const thumbnail = form.querySelector('[data-avatar-thumbnail]');
    const fileName = form.querySelector('[data-avatar-filename]');
    const fileSize = form.querySelector('[data-avatar-filesize]');
    const error = form.querySelector('[data-avatar-error]');
    const preview = form.querySelector('[data-avatar-preview]');
    const previewDesktop = form.querySelector('[data-avatar-preview-desktop]');
    const previewMobile = form.querySelector('[data-avatar-preview-mobile]');
    const submit = form.querySelector('[data-avatar-submit]');
    const submitLabel = form.querySelector('[data-avatar-submit-label]');
    const maxBytes = Number(form.dataset.maxBytes);
    const maxLabel = form.dataset.maxLabel;
    const acceptedTypes = new Set(form.dataset.accept.split(','));
    let selectedFile = null;
    let previewUrl = null;
    let pendingUrl = null;
    let selectionRequest = 0;
    let checkingImage = false;

    const showError = (message) => {
        error.textContent = message;
        error.hidden = !message;
        input.setAttribute('aria-invalid', String(Boolean(message)));
        input.setAttribute('aria-describedby', message ? 'avatar-error' : '');
    };

    const setSubmitEnabled = (enabled) => {
        submit.disabled = !enabled;
        submit.setAttribute('aria-disabled', String(!enabled));
    };

    const formatSize = (bytes) => {
        if (bytes >= 1024 * 1024) {
            return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(bytes / (1024 * 1024))} MB`;
        }

        return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.max(1, bytes / 1024))} KB`;
    };

    const makeImage = (source, alt = '') => {
        const image = document.createElement('img');
        image.src = source;
        image.alt = alt;
        return image;
    };

    const revokePreview = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    };

    const clearSelection = () => {
        selectionRequest++;
        checkingImage = false;
        if (pendingUrl) URL.revokeObjectURL(pendingUrl);
        pendingUrl = null;
        revokePreview();
        selectedFile = null;
        input.value = '';
        thumbnail.replaceChildren();
        previewDesktop.replaceChildren();
        previewMobile.replaceChildren();
        fileCard.hidden = true;
        dropzone.hidden = false;
        preview.classList.remove('is-visible');
        preview.hidden = true;
        setSubmitEnabled(false);
        showError('');
    };

    const restoreSelectedFile = () => {
        if (!selectedFile) return;

        const transfer = new DataTransfer();
        transfer.items.add(selectedFile);
        input.files = transfer.files;
    };

    const chooseFile = async (file) => {
        if (!file) return;
        const requestId = ++selectionRequest;
        if (pendingUrl) URL.revokeObjectURL(pendingUrl);
        pendingUrl = null;
        checkingImage = false;
        showError('');

        if (!acceptedTypes.has(file.type)) {
            showError('Format file tidak didukung. Pilih foto JPG, PNG, atau WEBP.');
            return;
        }

        if (file.size > maxBytes) {
            showError(`Ukuran foto terlalu besar. Pilih foto di bawah ${maxLabel}.`);
            return;
        }

        const candidateUrl = URL.createObjectURL(file);
        pendingUrl = candidateUrl;
        checkingImage = true;
        const image = new Image();

        image.onload = () => {
            if (requestId !== selectionRequest) {
                URL.revokeObjectURL(candidateUrl);
                return;
            }

            if (image.naturalWidth === 0 || image.naturalHeight === 0) {
                URL.revokeObjectURL(candidateUrl);
                pendingUrl = null;
                checkingImage = false;
                showError('File tidak bisa dibaca sebagai foto. Coba pilih file lain.');
                return;
            }

            revokePreview();
            previewUrl = candidateUrl;
            pendingUrl = null;
            checkingImage = false;
            selectedFile = file;
            thumbnail.replaceChildren(makeImage(previewUrl));
            previewDesktop.replaceChildren(makeImage(previewUrl));
            previewMobile.replaceChildren(makeImage(previewUrl));
            fileName.textContent = file.name;
            fileSize.textContent = formatSize(file.size);
            dropzone.hidden = true;
            fileCard.hidden = false;
            preview.hidden = false;
            preview.classList.remove('is-visible');
            requestAnimationFrame(() => preview.classList.add('is-visible'));
            setSubmitEnabled(true);
            showError('');
        };

        image.onerror = () => {
            if (requestId !== selectionRequest) {
                URL.revokeObjectURL(candidateUrl);
                return;
            }

            URL.revokeObjectURL(candidateUrl);
            pendingUrl = null;
            checkingImage = false;
            showError('File tidak bisa dibaca sebagai foto. Coba pilih file lain.');
        };

        image.src = candidateUrl;
    };

    input.addEventListener('change', () => {
        chooseFile(input.files?.[0]);
    });

    form.querySelector('[data-avatar-change]').addEventListener('click', () => input.click());
    form.querySelector('[data-avatar-clear]').addEventListener('click', clearSelection);

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        chooseFile(event.dataTransfer?.files?.[0]);
    });

    form.addEventListener('submit', (event) => {
        if (checkingImage) {
            event.preventDefault();
            return;
        }

        if (!selectedFile) {
            event.preventDefault();
            showError('Pilih foto dulu sebelum mengunggah.');
            input.focus();
            return;
        }

        restoreSelectedFile();
        submit.disabled = true;
        submitLabel.textContent = 'Mengunggah...';
    });

    const deleteForm = document.querySelector('[data-avatar-delete-form]');
    if (deleteForm) {
        const deleteTrigger = deleteForm.querySelector('[data-avatar-delete-trigger]');
        const confirmation = deleteForm.querySelector('[data-avatar-delete-confirm]');
        deleteTrigger.addEventListener('click', () => {
            deleteForm.classList.add('is-confirming');
            confirmation.hidden = false;
            confirmation.querySelector('button[type="submit"]').focus();
        });

        deleteForm.querySelector('[data-avatar-delete-cancel]').addEventListener('click', () => {
            deleteForm.classList.remove('is-confirming');
            confirmation.hidden = true;
            deleteTrigger.focus();
        });
    }

    window.addEventListener('pagehide', () => {
        revokePreview();
        if (pendingUrl) URL.revokeObjectURL(pendingUrl);
    }, { once: true });
}
