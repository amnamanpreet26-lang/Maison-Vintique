/* Maison Vintique — front-end interactions.

   ONE BROKEN BLOCK MUST NOT TAKE THE REST WITH IT.
   ------------------------------------------------
   Everything below is a separate block, but they all live in one file, and an
   uncaught error anywhere stops the browser reading the rest of it. That is
   how a missing carousel arrow on one page silently killed the trade popup,
   the smooth scrolling and the FAQ accordion on every page.

   mvBlock() runs each block on its own. If one throws, it is reported to the
   console and the next one still runs. */
function mvBlock(fn) {
  try {
    fn();
  } catch (err) {
    if (window.console && console.warn) {
      console.warn('[Maison Vintique] a script block failed and was skipped:', err);
    }
  }
}

/* Cart badge + Add-to-Cart demo behaviour. In production the cart total
   comes from the Laravel trade portal / WooCommerce session. */
mvBlock(function () {
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
});





/* ==========================================================================
   PRODUCER CAROUSEL ARROWS
   --------------------------------------------------------------------------
   Three producers or fewer render as a plain grid with no arrows at all, so
   every element here is checked before it is used. Without those checks a page
   with a short producer list threw, and everything after this point in the
   file never ran.
   ========================================================================== */
mvBlock(function () {
  document.querySelectorAll('.producer-carousel').forEach(function (carousel) {
    var count = parseInt(carousel.dataset.count, 10);
    if (!isNaN(count) && count <= 3) return;   // static grid, nothing to scroll

    var track = carousel.querySelector('.producer-carousel__track');
    var prev  = carousel.querySelector('.producer-carousel__arrow--prev');
    var next  = carousel.querySelector('.producer-carousel__arrow--next');
    if (!track) return;

    var scrollAmount = function () {
      var slide = track.querySelector('.producer-carousel__slide');
      return slide ? slide.getBoundingClientRect().width + 24 : 300;
    };

    if (prev) {
      prev.addEventListener('click', function () {
        track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
      });
    }
    if (next) {
      next.addEventListener('click', function () {
        track.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
      });
    }
  });
});

/* ==========================================================================
   SMOOTH SCROLL FOR IN-PAGE LINKS
   --------------------------------------------------------------------------
   Done in JavaScript rather than with `scroll-behavior: smooth` in CSS so it
   can offset for the sticky header. With the CSS property alone the target
   section lands underneath the header and the visitor sees the wrong thing.
   ========================================================================== */
mvBlock(function () {
	var header = document.querySelector('.mv-header');

	function headerOffset() {
		if (!header) return 0;
		// Only a sticky/fixed header covers the content once you have scrolled.
		var pos = getComputedStyle(header).position;
		return (pos === 'sticky' || pos === 'fixed') ? header.getBoundingClientRect().height : 0;
	}

	function scrollToTarget(target, push, href) {
		var top = target.getBoundingClientRect().top + window.pageYOffset - headerOffset() - 12;
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		window.scrollTo({ top: top, behavior: reduce ? 'auto' : 'smooth' });

		// Keep the URL and the back button working, without the instant jump
		// that setting location.hash would cause.
		if (push && href && window.history && window.history.pushState) {
			window.history.pushState(null, '', href);
		}

		// Move keyboard focus too, or the next Tab starts from the top again.
		if (!target.hasAttribute('tabindex')) {
			target.setAttribute('tabindex', '-1');
		}
		target.focus({ preventScroll: true });
	}

	document.addEventListener('click', function (e) {
		var link = e.target.closest && e.target.closest('a[href*="#"]');
		if (!link || link.hasAttribute('data-no-smooth')) return;

		// Same page only. A link to another page's anchor must navigate.
		if (link.pathname !== window.location.pathname || link.host !== window.location.host) return;

		var id = link.hash.slice(1);
		if (!id) return;

		var target = document.getElementById(id) || document.getElementsByName(id)[0];
		if (!target) return;

		e.preventDefault();
		scrollToTarget(target, true, link.href);
	});

	// Arriving with a #hash in the URL: let the browser do its instant jump,
	// then correct for the header once images have settled the layout.
	window.addEventListener('load', function () {
		if (!window.location.hash) return;
		var target = document.getElementById(window.location.hash.slice(1));
		if (target) {
			setTimeout(function () { scrollToTarget(target, false); }, 60);
		}
	});
});

/* ==========================================================================
   FAQ ACCORDION — OPEN AND CLOSE SMOOTHLY
   --------------------------------------------------------------------------
   <details> cannot be animated: the browser flips it open in one frame, and
   height:auto is not animatable anyway. So the panel is measured and its
   height animated by hand, and on close the `open` attribute is held until the
   animation has finished.

   With JavaScript off, <details> still opens and closes — just instantly.
   ========================================================================== */
mvBlock(function () {
	var panels = document.querySelectorAll('.mvp-faq details, details.mv-accordion');
	if (!panels.length) return;

	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (reduce) return;   // no animation wanted; native behaviour is correct

	Array.prototype.forEach.call(panels, function (details) {
		var summary = details.querySelector('summary');
		if (!summary) return;

		// Everything after the <summary> is the panel.
		var body = document.createElement('div');
		body.className = 'mv-accordion__body';
		while (summary.nextSibling) {
			body.appendChild(summary.nextSibling);
		}
		details.appendChild(body);

		var animation = null;
		var closing = false;

		function animate(from, to, onDone) {
			if (animation) animation.cancel();
			animation = body.animate(
				{ height: [from + 'px', to + 'px'], opacity: [from ? 1 : 0, to ? 1 : 0] },
				{ duration: 260, easing: 'cubic-bezier(.22,.61,.36,1)' }
			);
			animation.onfinish = function () {
				animation = null;
				body.style.height = '';
				if (onDone) onDone();
			};
			animation.oncancel = function () { animation = null; };
		}

		summary.addEventListener('click', function (e) {
			e.preventDefault();

			if (details.open && !closing) {
				// Closing: keep it open until the animation has played out.
				closing = true;
				animate(body.offsetHeight, 0, function () {
					details.open = false;
					closing = false;
				});
			} else {
				closing = false;
				details.open = true;
				animate(0, body.scrollHeight);
			}
		});
	});
});

/* ==========================================================================
   TRADE PRICING POPUP
   --------------------------------------------------------------------------
   Shown once per visitor, not once per page view. The flag is in localStorage
   with a timestamp, so it survives navigation and a return visit, and the
   "days to stay dismissed" setting can be honoured.

   A native dialog element, so focus trapping, Escape-to-close and the backdrop
   come from the browser rather than from code we would have to maintain.
   ========================================================================== */
mvBlock(function () {
	var popup = document.getElementById('mv-trade-popup');
	if (!popup) return;

	var KEY   = 'mvTradePopupDismissed';
	var days  = parseInt(popup.dataset.days, 10);
	var delay = parseInt(popup.dataset.delay, 10);
	if (isNaN(days)) days = 30;
	if (isNaN(delay)) delay = 1200;

	function store() {
		// Private browsing can throw on localStorage access, and a popup is not
		// worth breaking the page over.
		try { return window.localStorage; } catch (e) { return null; }
	}

	function alreadyDismissed() {
		var ls = store();
		if (!ls) return false;
		var stamp = parseInt(ls.getItem(KEY), 10);
		if (!stamp) return false;
		if (days === 0) return true;                 // 0 = never show again
		var age = (Date.now() - stamp) / 86400000;   // ms -> days
		return age < days;
	}

	function remember() {
		if (popup.dataset.preview) return;   // previewing should not dismiss it
		var ls = store();
		if (ls) { try { ls.setItem(KEY, String(Date.now())); } catch (e) {} }
	}

	function close() {
		remember();
		if (typeof popup.close === 'function' && popup.open) popup.close();
		else popup.removeAttribute('open');
	}

	// ?mv_popup=preview forces it every time, so it can be checked while
	// signed in and after it has already been dismissed.
	if (!popup.dataset.preview && alreadyDismissed()) return;

	setTimeout(function () {
		if (typeof popup.showModal === 'function') popup.showModal();
		else popup.setAttribute('open', '');          // very old browsers
	}, delay);

	popup.querySelectorAll('[data-mv-popup-close]').forEach(function (btn) {
		btn.addEventListener('click', close);
	});

	// Escape fires dialog's own close event; record the dismissal for that too.
	popup.addEventListener('close', remember);

	// Clicking the backdrop closes it. The dialog's own box is the only child,
	// so a click landing on the dialog itself was outside that box.
	popup.addEventListener('click', function (e) {
		if (e.target === popup) close();
	});

	// Following the CTA counts as dealt with — do not nag them again.
	var cta = popup.querySelector('.mv-popup__cta');
	if (cta) cta.addEventListener('click', remember);
});

/* ==========================================================================
   TRADE ACCOUNT APPLICATION
   --------------------------------------------------------------------------
   Two small jobs on a long form:

   1. Conditional questions. "Premises licence number" only makes sense once
      somebody has said they do sell to the public, so those fields stay out of
      the way until the answer above them calls for them. With scripts blocked
      every field is simply visible — nothing is hidden behind JavaScript.

   2. "Add another person". The authorised-users table starts with two rows
      and this clones one more on demand, renumbering the field names so the
      server receives them as a list.
   ========================================================================== */
mvBlock(function () {
	var form = document.querySelector('.mvta-form');
	if (!form) return;

	/* ---------- 1. Conditional questions ---------- */
	var triggers = form.querySelectorAll('[data-mvta-controls]');

	function sync(trigger) {
		var group  = trigger.getAttribute('data-mvta-controls');
		var chosen = trigger.querySelector('input:checked');
		var answer = chosen ? chosen.value : '';
		var fields = form.querySelectorAll('[data-mvta-group="' + group + '"]');

		Array.prototype.forEach.call(fields, function (field) {
			// Most follow-ups appear on anything but "no". A field carrying
			// data-mvta-when is the other way round — "explain why you have no
			// licence" only belongs under a "no".
			var when   = field.getAttribute('data-mvta-when');
			var wanted = when ? (answer === when) : (!!answer && answer !== 'no');

			field.hidden = !wanted;

			// A hidden required field can never be filled in, and the browser
			// then refuses to submit with no visible explanation.
			field.querySelectorAll('input, select, textarea').forEach(function (input) {
				if (!wanted && input.required) {
					input.dataset.mvtaRequired = '1';
					input.required = false;
				} else if (wanted && input.dataset.mvtaRequired) {
					input.required = true;
					delete input.dataset.mvtaRequired;
				}
			});
		});
	}

	Array.prototype.forEach.call(triggers, function (trigger) {
		sync(trigger);
		trigger.addEventListener('change', function () { sync(trigger); });
	});

	/* ---------- 2. Add another authorised user ---------- */
	var table = form.querySelector('[data-mvta-users]');
	var add   = form.querySelector('[data-mvta-add-user]');
	if (!table || !add) return;

	add.addEventListener('click', function () {
		var rows = table.querySelectorAll('[data-mvta-user-row]');
		if (!rows.length) return;

		var last  = rows[rows.length - 1];
		var clone = last.cloneNode(true);
		var index = rows.length;

		clone.querySelectorAll('input').forEach(function (input) {
			// name="authorised_users[2][email]" — only the number changes.
			input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
			if (input.type === 'checkbox') {
				input.checked = false;
			} else {
				input.value = '';
			}
		});

		add.parentNode.insertBefore(clone, add);

		var first = clone.querySelector('input');
		if (first) first.focus();
	});
});
