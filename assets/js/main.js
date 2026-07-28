/* Maison Vintique — front-end interactions.
   Cart badge + Add-to-Cart demo behaviour. In production the cart total
   comes from the Laravel trade portal / WooCommerce session. */
(function () {
  'use strict';

  var count = 0;
  var badges = document.querySelectorAll('[data-cart-count]');

  function bump() {
    count += 1;
    badges.forEach(function (b) { b.textContent = String(count); });
  }

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add]');
    if (add) {
      e.preventDefault();
      bump();
    }
  });

  // Header mobile menu toggle
  var burger = document.querySelector('.mv-header__burger');
  var mobileNav = document.getElementById('site-header-mobile-nav');
  if (burger && mobileNav) {
    burger.addEventListener('click', function () {
      var open = mobileNav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Reveal-on-scroll for elements marked .mv-reveal
  var reveal = document.querySelectorAll('.mv-reveal');
  if ('IntersectionObserver' in window && reveal.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -6% 0px' });
    reveal.forEach(function (el) { io.observe(el); });
  }
})();





document.querySelectorAll('.producer-carousel').forEach(function (carousel) {
  var count = parseInt(carousel.dataset.count, 10);
  if (count <= 3) return; // static grid, no carousel behavior needed

  var track = carousel.querySelector('.producer-carousel__track');
  var prev  = carousel.querySelector('.producer-carousel__arrow--prev');
  var next  = carousel.querySelector('.producer-carousel__arrow--next');

  var scrollAmount = function () {
    var slide = track.querySelector('.producer-carousel__slide');
    return slide ? slide.getBoundingClientRect().width + 24 : 300;
  };

  prev.addEventListener('click', function () {
    track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
  });
  next.addEventListener('click', function () {
    track.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
  });
});
