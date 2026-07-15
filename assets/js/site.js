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
