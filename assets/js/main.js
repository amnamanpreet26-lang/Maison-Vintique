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

  /* Mobile sub-menus.
     There's no hover on touch, so each parent item gets a caret button that
     expands its children. The parent's own link still navigates — only the
     caret toggles — so a parent page stays reachable. */
  if (mobileNav) {
    mobileNav.querySelectorAll('.menu-item-has-children').forEach(function (item) {
      var submenu = item.querySelector('.sub-menu');
      if (!submenu) { return; }

      var toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'mv-subnav-toggle';
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Show submenu');

      toggle.addEventListener('click', function () {
        var open = item.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });

      // Sits beside the parent link, not inside it.
      var link = item.querySelector(':scope > a');
      if (link) {
        link.insertAdjacentElement('afterend', toggle);
      } else {
        item.appendChild(toggle);
      }
    });
  }

  /* ------------------------------------------------------------------
     Single product — tabs, deep-linking, and the gallery.
     Lives here rather than inline in single-product.php so it always runs.
     Every block no-ops when its markup isn't on the page.
     ------------------------------------------------------------------ */

  var tabButtons = document.querySelectorAll('.tabnav button');
  var tabPanes   = document.querySelectorAll('.tabpane');

  function activateTab(name) {
    var pane = document.getElementById('tab-' + name);
    if (!pane) { return false; }
    tabButtons.forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-tab') === name);
    });
    tabPanes.forEach(function (p) { p.classList.toggle('on', p === pane); });
    return true;
  }

  if (tabButtons.length) {
    tabButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        activateTab(btn.getAttribute('data-tab'));
      });
    });
  }

  /* Open a tab straight from the URL. Accepts BOTH forms, because a plain
     #hash is easy for a plugin or a redirect to drop:
       /wine/xyz/#tab-tech      <- what the wine cards link to
       /wine/xyz/?tab=tech      <- survives anything that strips fragments */
  function openTabFromUrl(scroll) {
    var name = '';
    var hash = /^#tab-([\w-]+)$/.exec(window.location.hash || '');
    if (hash) {
      name = hash[1];
    } else {
      var q = /[?&]tab=([\w-]+)/.exec(window.location.search || '');
      if (q) { name = q[1]; }
    }
    if (!name || !activateTab(name)) { return; }

    if (scroll) {
      var tabs = document.querySelector('.tabs');
      if (tabs) { tabs.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }
  }

  if (tabPanes.length) {
    openTabFromUrl(true);
    window.addEventListener('hashchange', function () { openTabFromUrl(true); });
  }

  /* --- Product gallery ---------------------------------------------- */
  var thumbs  = document.querySelectorAll('#pdpThumbs span');
  var mainImg = document.getElementById('pdpMainImg');

  if (thumbs.length && mainImg) {
    var showThumb = function (index) {
      if (index < 0) { index = thumbs.length - 1; }
      if (index >= thumbs.length) { index = 0; }

      var t = thumbs[index];
      var full = t.getAttribute('data-full');
      if (!full) { return; }

      /* WordPress renders the main image WITH srcset/sizes. Setting src alone
         leaves the browser free to keep using the srcset candidate it already
         picked — which is why the image appeared not to change at all. Both
         have to go before the new src will be honoured. */
      mainImg.removeAttribute('srcset');
      mainImg.removeAttribute('sizes');
      mainImg.setAttribute('src', full);

      var alt = t.querySelector('img');
      if (alt) { mainImg.setAttribute('alt', alt.getAttribute('alt') || ''); }

      thumbs.forEach(function (s) { s.classList.remove('on'); });
      t.classList.add('on');
      current = index;
    };

    var current = 0;
    thumbs.forEach(function (t, i) {
      t.addEventListener('click', function () { showThumb(i); });
      t.setAttribute('tabindex', '0');
      t.setAttribute('role', 'button');
      t.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showThumb(i); }
      });
    });

    // Arrow keys once a thumbnail has focus
    document.getElementById('pdpThumbs').addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); showThumb(current + 1); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); showThumb(current - 1); }
    });

    // Swipe the main image on touch
    var media = document.getElementById('pdpMedia');
    if (media) {
      var startX = null;
      media.addEventListener('touchstart', function (e) {
        startX = e.changedTouches[0].clientX;
      }, { passive: true });
      media.addEventListener('touchend', function (e) {
        if (startX === null) { return; }
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) { showThumb(dx < 0 ? current + 1 : current - 1); }
        startX = null;
      }, { passive: true });
    }
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
