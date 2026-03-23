/* ============================================================
   PayMonitor – Dashboard JavaScript
   ============================================================
   Handles: Bootstrap-compatible dropdown toggling, modal open/close,
   tooltip stubs, and keyboard shortcuts for the admin dashboard.
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // ─── Bootstrap stub (provides API expected by legacy views) ───
    window.bootstrap = window.bootstrap || {
        Tooltip: class {
            constructor(element) { this.element = element; }
        },
        Alert: class {
            constructor(element) { this.element = element; }
            close() { this.element?.remove(); }
            static getOrCreateInstance(element) { return new this(element); }
        },
    };

    // ─── Dropdown toggle ───
    const closeDropdowns = (exceptMenu = null) => {
        document.querySelectorAll('.legacy-content .dropdown-menu.show').forEach((menu) => {
            if (exceptMenu && menu === exceptMenu) return;
            menu.classList.remove('show');
            menu.parentElement
                ?.querySelector('[data-bs-toggle="dropdown"]')
                ?.setAttribute('aria-expanded', 'false');
        });
    };

    document.querySelectorAll('.legacy-content [data-bs-toggle="dropdown"]').forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const menu = toggle.parentElement?.querySelector('.dropdown-menu');
            if (!menu) return;
            const willOpen = !menu.classList.contains('show');
            closeDropdowns(willOpen ? menu : null);
            menu.classList.toggle('show', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.legacy-content .dropdown')) {
            closeDropdowns();
        }
    });

    // ─── Modal open/close ───
    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.add('show');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('show');
        if (!document.querySelector('.legacy-content .modal.show')) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    document.querySelectorAll('.legacy-content [data-bs-toggle="modal"]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(document.querySelector(trigger.getAttribute('data-bs-target')));
        });
    });

    document.querySelectorAll('.legacy-content [data-bs-dismiss="modal"]').forEach((trigger) => {
        trigger.addEventListener('click', () => closeModal(trigger.closest('.modal')));
    });

    document.querySelectorAll('.legacy-content .modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal(modal);
        });
    });

    // ─── Keyboard shortcuts ───
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.legacy-content .modal.show').forEach(closeModal);
            closeDropdowns();
        }
    });
});
