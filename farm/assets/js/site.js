(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---------- Mobile nav toggle ----------
    var navToggle = document.querySelector('[data-nav-toggle]');
    if (navToggle) {
        navToggle.addEventListener('click', function () {
            var open = document.body.classList.toggle('nav-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.querySelectorAll('.site-nav-links a').forEach(function (link) {
            link.addEventListener('click', function () {
                document.body.classList.remove('nav-open');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // ---------- Count-up numbers (ledger strip) ----------
    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduceMotion || target === 0) {
            el.textContent = target;
            return;
        }
        var start = null;
        var duration = 900;
        function step(ts) {
            if (start === null) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // ---------- Trail fill (provenance trail widget) ----------
    function fillTrail(container) {
        var trail = container.querySelector('[data-trail]');
        var fill = container.querySelector('[data-trail-fill]');
        if (!trail || !fill) return;
        var pct = trail.getAttribute('data-fill') || '0';
        if (reduceMotion) {
            fill.style.transition = 'none';
        }
        // Next frame so the transition actually runs.
        requestAnimationFrame(function () {
            fill.style.width = pct + '%';
        });
    }

    // ---------- Scroll reveal ----------
    var revealables = document.querySelectorAll('.reveal, .ledger-strip, [data-reveal-trail]');
    if ('IntersectionObserver' in window && revealables.length) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');

                if (entry.target.classList.contains('ledger-strip')) {
                    entry.target.querySelectorAll('[data-count]').forEach(animateCount);
                }
                if (entry.target.hasAttribute('data-reveal-trail')) {
                    fillTrail(entry.target);
                }
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.35 });

        revealables.forEach(function (el) { observer.observe(el); });
    } else {
        // No IntersectionObserver support: just show everything immediately.
        document.querySelectorAll('[data-count]').forEach(animateCount);
        document.querySelectorAll('[data-reveal-trail]').forEach(fillTrail);
    }

    // ---------- Copy tracking code to clipboard (trace result) ----------
    document.querySelectorAll('.trace-code').forEach(function (codeEl) {
        codeEl.style.cursor = 'pointer';
        codeEl.title = 'Click to copy';
        codeEl.addEventListener('click', function () {
            var text = codeEl.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () {
                    var original = codeEl.textContent;
                    codeEl.textContent = 'Copied';
                    setTimeout(function () { codeEl.textContent = original; }, 1200);
                });
            }
        });
    });
})();
