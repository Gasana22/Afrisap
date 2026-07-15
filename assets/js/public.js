/**
 * Public marketing site behaviour: contact/demo form submission feedback,
 * pricing toggle (monthly/yearly), fade-in on scroll.
 */
document.addEventListener('DOMContentLoaded', () => {
    const billingToggle = document.getElementById('billingToggle');
    if (billingToggle) {
        billingToggle.addEventListener('change', (e) => {
            document.querySelectorAll('[data-price-monthly]').forEach((el) => {
                const monthly = el.dataset.priceMonthly;
                const yearly = el.dataset.priceYearly;
                el.textContent = e.target.checked ? yearly : monthly;
            });
        });
    }

    document.querySelectorAll('form[data-confirm-submit]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = 'Please wait...';
            }
        });
    });
});
