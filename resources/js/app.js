import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-datepicker]').forEach(el => {
        if (el._flatpickr) return;
        flatpickr(el, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'm/d/Y',
            allowInput: true,
            altInputClass: 'form-control',
            onChange: (selectedDates, dateStr, fp) => {
                el.dispatchEvent(new Event('input', { bubbles: true }));
            },
        });
    });
});
