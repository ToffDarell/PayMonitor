<style>
    .pm-dialog-root[hidden] {
        display: none !important;
    }

    body.pm-dialog-open {
        overflow: hidden;
    }

    .pm-dialog-root {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .pm-dialog-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(2, 6, 23, 0.76);
        backdrop-filter: blur(6px);
    }

    .pm-dialog-panel {
        position: relative;
        width: min(100%, 32rem);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 1.5rem;
        background: var(--pm-card-bg, #111827);
        color: var(--pm-text-primary, #f8fafc);
        box-shadow: 0 30px 80px rgba(2, 6, 23, 0.45);
        padding: 1.5rem;
    }

    .pm-dialog-title {
        margin-top: 0;
        margin-bottom: 0;
        font-size: 1.375rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--pm-text-primary, #f8fafc);
    }

    .pm-dialog-message {
        margin-top: 0.85rem;
        color: var(--pm-text-secondary, #cbd5e1);
        font-size: 0.95rem;
        line-height: 1.65;
        white-space: pre-line;
    }

    .pm-dialog-actions {
        margin-top: 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .pm-dialog-button {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 9999px;
        background: rgba(15, 23, 42, 0.55);
        color: var(--pm-text-primary, #f8fafc);
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 600;
        min-width: 7.5rem;
        padding: 0.75rem 1.15rem;
        transition: transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease;
    }

    .pm-dialog-button:hover,
    .pm-dialog-button:focus-visible {
        border-color: rgba(148, 163, 184, 0.35);
        transform: translateY(-1px);
        outline: none;
    }

    .pm-dialog-button-secondary {
        background: rgba(148, 163, 184, 0.12);
        color: var(--pm-text-secondary, #cbd5e1);
    }

    .pm-dialog-button-secondary:hover,
    .pm-dialog-button-secondary:focus-visible {
        background: rgba(148, 163, 184, 0.18);
        color: var(--pm-text-primary, #f8fafc);
    }

    .pm-dialog-button-primary {
        border-color: rgba(34, 197, 94, 0.26);
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.95), rgba(22, 163, 74, 0.95));
        color: #ffffff;
    }

    .pm-dialog-button-primary:hover,
    .pm-dialog-button-primary:focus-visible {
        border-color: rgba(134, 239, 172, 0.4);
    }

    .pm-dialog-button-danger {
        border-color: rgba(248, 113, 113, 0.26);
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.95), rgba(220, 38, 38, 0.95));
        color: #ffffff;
    }

    .pm-dialog-button-danger:hover,
    .pm-dialog-button-danger:focus-visible {
        border-color: rgba(254, 202, 202, 0.42);
    }

    @media (max-width: 640px) {
        .pm-dialog-root {
            padding: 1rem;
            align-items: flex-end;
        }

        .pm-dialog-panel {
            width: 100%;
            border-radius: 1.25rem;
            padding: 1.25rem;
        }

        .pm-dialog-actions {
            flex-direction: column-reverse;
        }

        .pm-dialog-button {
            width: 100%;
        }
    }
</style>

<div id="pm-dialog-root" class="pm-dialog-root" hidden aria-hidden="true" data-tone="primary">
    <div class="pm-dialog-backdrop" data-pm-dialog-dismiss></div>
    <div class="pm-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="pm-dialog-title" aria-describedby="pm-dialog-message">
        <h2 id="pm-dialog-title" class="pm-dialog-title">Please confirm</h2>
        <p id="pm-dialog-message" class="pm-dialog-message"></p>
        <div class="pm-dialog-actions">
            <button type="button" id="pm-dialog-cancel" class="pm-dialog-button pm-dialog-button-secondary">Cancel</button>
            <button type="button" id="pm-dialog-confirm" class="pm-dialog-button pm-dialog-button-primary">Confirm</button>
        </div>
    </div>
</div>

<script>
    (() => {
        if (window.PayMonitorDialog) {
            return;
        }

        const root = document.getElementById('pm-dialog-root');
        const panel = root?.querySelector('.pm-dialog-panel');
        const title = document.getElementById('pm-dialog-title');
        const message = document.getElementById('pm-dialog-message');
        const confirmButton = document.getElementById('pm-dialog-confirm');
        const cancelButton = document.getElementById('pm-dialog-cancel');
        const dismissTarget = root?.querySelector('[data-pm-dialog-dismiss]');

        if (!root || !panel || !title || !message || !confirmButton || !cancelButton) {
            return;
        }

        let activeRequest = null;
        let previouslyFocusedElement = null;

        const getFocusableElements = () => Array.from(panel.querySelectorAll('button:not([disabled]):not([hidden]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
            .filter((element) => !element.hasAttribute('disabled') && !element.getAttribute('aria-hidden'));

        const resolveRequest = (result) => {
            if (!activeRequest) {
                return;
            }

            const request = activeRequest;
            activeRequest = null;
            root.hidden = true;
            root.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('pm-dialog-open');

            if (previouslyFocusedElement instanceof HTMLElement) {
                previouslyFocusedElement.focus({ preventScroll: true });
            }

            const resolvedValue = request.mode === 'alert' ? true : result === true;
            request.resolve(resolvedValue);
        };

        const renderDialog = (options) => {
            const tone = options.tone === 'danger' ? 'danger' : 'primary';
            const mode = options.mode === 'alert' ? 'alert' : 'confirm';
            const confirmText = options.confirmText || (mode === 'alert' ? 'OK' : 'Confirm');
            const cancelText = options.cancelText || 'Cancel';
            const dialogTitle = options.title || (mode === 'alert' ? 'Notice' : 'Please confirm');

            root.dataset.tone = tone;
            title.textContent = dialogTitle;
            message.textContent = options.message || '';
            confirmButton.textContent = confirmText;
            cancelButton.textContent = cancelText;
            cancelButton.hidden = mode === 'alert';

            confirmButton.classList.remove('pm-dialog-button-primary', 'pm-dialog-button-danger');
            confirmButton.classList.add(tone === 'danger' ? 'pm-dialog-button-danger' : 'pm-dialog-button-primary');
        };

        const openDialog = (options = {}) => new Promise((resolve) => {
            previouslyFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            activeRequest = {
                dismissible: options.dismissible !== false,
                mode: options.mode === 'alert' ? 'alert' : 'confirm',
                resolve,
            };

            renderDialog(options);
            root.hidden = false;
            root.setAttribute('aria-hidden', 'false');
            document.body.classList.add('pm-dialog-open');

            window.requestAnimationFrame(() => {
                confirmButton.focus({ preventScroll: true });
            });
        });

        const readOption = (name, primary, fallback) => primary?.getAttribute(name) ?? fallback?.getAttribute(name) ?? '';

        const extractConfirmOptions = (primary, fallback) => ({
            title: readOption('data-confirm-title', primary, fallback) || undefined,
            message: readOption('data-confirm', primary, fallback) || undefined,
            confirmText: readOption('data-confirm-confirm-text', primary, fallback) || undefined,
            cancelText: readOption('data-confirm-cancel-text', primary, fallback) || undefined,
            tone: readOption('data-confirm-tone', primary, fallback) || undefined,
            dismissible: readOption('data-confirm-dismissible', primary, fallback) !== 'false',
        });

        const triggerFormSubmit = (form, submitter) => {
            form.dataset.pmConfirmApproved = '1';

            if (typeof form.requestSubmit === 'function') {
                if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
                    form.requestSubmit(submitter);
                    return;
                }

                form.requestSubmit();
                return;
            }

            form.submit();
        };

        window.PayMonitorDialog = {
            confirm(options = {}) {
                return openDialog({ ...options, mode: 'confirm' });
            },
            alert(options = {}) {
                return openDialog({ ...options, mode: 'alert' }).then(() => undefined);
            },
        };

        confirmButton.addEventListener('click', () => resolveRequest(true));
        cancelButton.addEventListener('click', () => resolveRequest(false));
        dismissTarget?.addEventListener('click', () => {
            if (activeRequest?.dismissible !== false) {
                resolveRequest(false);
            }
        });

        root.addEventListener('keydown', (event) => {
            if (!activeRequest) {
                return;
            }

            if (event.key === 'Escape' && activeRequest.dismissible !== false) {
                event.preventDefault();
                resolveRequest(false);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusable = getFocusableElements();

            if (focusable.length === 0) {
                event.preventDefault();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
            const confirmMessage = readOption('data-confirm', submitter, form);

            if (!confirmMessage) {
                return;
            }

            if (form.dataset.pmConfirmApproved === '1') {
                delete form.dataset.pmConfirmApproved;
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            window.PayMonitorDialog.confirm(extractConfirmOptions(submitter, form)).then((confirmed) => {
                if (!confirmed) {
                    return;
                }

                triggerFormSubmit(form, submitter);
            });
        }, true);

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-confirm]');

            if (!(link instanceof HTMLAnchorElement)) {
                return;
            }

            const href = link.getAttribute('href');

            if (!href) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            window.PayMonitorDialog.confirm(extractConfirmOptions(link, null)).then((confirmed) => {
                if (confirmed) {
                    window.location.assign(href);
                }
            });
        });
    })();
</script>
