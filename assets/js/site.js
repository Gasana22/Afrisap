(function () {
  'use strict';

  var targets = document.querySelectorAll(
    '.section__header, .door, .tour-card, .tile, .experience-tile, .why-item, .hero-stat, .list-row, .faq-item'
  );

  if (!('IntersectionObserver' in window) || !targets.length) {
    return;
  }

  targets.forEach(function (el) {
    el.classList.add('reveal');
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
