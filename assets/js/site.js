(function () {
  'use strict';

  var targets = document.querySelectorAll(
    '.section__header, .door, .tour-card, .tile, .experience-tile, .why-item, .hero-stat, .list-row, .faq-item, .activity-item, .side-card'
  );

  if (!('IntersectionObserver' in window) || !targets.length) {
    return;
  }

  // Siblings inside the same grid/rail cascade in with a short stagger
  // instead of popping in as one flat block. Capped so a long grid doesn't
  // leave its last rows waiting seconds to appear.
  var staggerParents = document.querySelectorAll('.card-grid, .tile-rail, .experience-grid, .why-grid, .hero__stats, .activity-list, .detail-side');
  var staggerIndex = new Map();
  staggerParents.forEach(function (parent) {
    Array.prototype.forEach.call(parent.children, function (child, i) {
      staggerIndex.set(child, Math.min(i, 5));
    });
  });

  targets.forEach(function (el) {
    el.classList.add('reveal');
    var i = staggerIndex.get(el);
    if (i) {
      el.style.transitionDelay = (i * 0.08) + 's';
    }
  });

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal--visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  targets.forEach(function (el) {
    observer.observe(el);
  });
})();

// Hero background slideshow: crossfades through however many photos the
// admin uploaded (.hero__slide, .is-active toggled every 5s). Does nothing
// if there's only one slide, and skips the auto-advance entirely under
// prefers-reduced-motion (the slides just stay on the first photo).
(function () {
  'use strict';

  var slides = document.querySelectorAll('.hero__slide');
  if (slides.length < 2) {
    return;
  }
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  var current = 0;
  setInterval(function () {
    slides[current].classList.remove('is-active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('is-active');
  }, 5000);
})();
