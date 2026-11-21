/* ExploreX Tours - small progressive-enhancement helpers (no framework). */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  // mobile navigation
  var toggle = $('.nav-toggle'), nav = $('#site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // close the account dropdown when clicking elsewhere
  document.addEventListener('click', function (e) {
    $$('.user-menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });

  // filters panel on small screens
  var ft = $('.filters-toggle'), fp = $('.filters');
  if (ft && fp) {
    fp.classList.add('collapsed');
    ft.addEventListener('click', function () { fp.classList.toggle('collapsed'); });
  }

  // wishlist heart: AJAX when possible, normal form post as fallback
  $$('form.wish-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.fetch) return;
      e.preventDefault();
      var btn = $('[data-wish]', form);
      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json', 'X-CSRF-Token': csrf },
        body: new FormData(form),
        credentials: 'same-origin'
      }).then(function (r) {
        if (r.status === 401) { window.location = (document.querySelector('meta[name="base-path"]').content || '') + '/user/login.php?next=' + encodeURIComponent(window.location.pathname.split('/').pop() + window.location.search); return null; }
        return r.json();
      }).then(function (data) {
        if (!data) return;
        btn.classList.toggle('is-saved', !!data.saved);
        btn.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
        if (btn.classList.contains('btn')) btn.textContent = data.saved ? '♥ Saved. Click to remove' : '♡ Save for later';
        var c = $('[data-wishlist-count]'); if (c) c.textContent = data.count;
        if (data.saved === false && form.hasAttribute('data-remove-card')) {
          var card = form.closest('.pkg-card'); if (card) card.remove();
        }
      }).catch(function () { form.submit(); });
    });
  });

  // scroll reveal
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: .12 });
    $$('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    $$('.reveal').forEach(function (el) { el.classList.add('in'); });
  }

  // booking form: dynamic traveller rows + live total
  var bf = $('#booking-form');
  if (bf) {
    var price = parseFloat(bf.getAttribute('data-price')) || 0;
    var guestsEl = $('#guests'), box = $('#travellers'), totalEl = $('[data-total]'), countEl = $('[data-guest-count]');
    var inr = new Intl.NumberFormat('en-IN');
    var render = function () {
      var n = Math.max(1, Math.min(parseInt(guestsEl.value, 10) || 1, parseInt(guestsEl.max, 10) || 12));
      if (totalEl) totalEl.textContent = '₹' + inr.format(price * n);
      if (countEl) countEl.textContent = n;
      var rows = $$('.traveller-row', box);
      while (rows.length > n - 1) { rows.pop().remove(); }
      for (var i = rows.length + 1; i < n; i++) {
        var row = document.createElement('div'); row.className = 'traveller-row';
        row.innerHTML = '<div><label class="lbl" for="tn' + i + '">Traveller ' + (i + 1) + ' - full name</label>' +
          '<input type="text" id="tn' + i + '" name="traveller_name[' + i + ']" maxlength="100" required></div>' +
          '<div><label class="lbl" for="ta' + i + '">Age</label>' +
          '<input type="number" id="ta' + i + '" name="traveller_age[' + i + ']" min="0" max="110"></div>';
        box.appendChild(row);
      }
    };
    guestsEl.addEventListener('input', render);
    render();

    // prevent double submits
    bf.addEventListener('submit', function () {
      var b = $('button[type=submit]', bf); if (b) { b.disabled = true; b.textContent = 'Please wait…'; }
    });
  }
  // generic double-submit guard for forms flagged data-once
  $$('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = $('button[type=submit]:not([name])', f); if (b) { setTimeout(function () { b.disabled = true; }, 0); }
    });
  });

  // confirm dialogs for destructive actions (forms with data-confirm)
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // star rating input: highlight on hover
  $$('.star-input').forEach(function (g) {
    var labels = $$('label', g);
    var paint = function (upTo) { labels.forEach(function (l, i) { l.classList.toggle('on', i < upTo); }); };
    $$('input', g).forEach(function (inp, idx) {
      inp.addEventListener('change', function () { paint(idx + 1); });
      if (inp.checked) paint(idx + 1);
    });
    labels.forEach(function (l, i) {
      l.addEventListener('mouseenter', function () { paint(i + 1); });
    });
    g.addEventListener('mouseleave', function () {
      var c = $$('input', g).findIndex(function (x) { return x.checked; });
      paint(c + 1);
    });
  });
})();
