/* ExploreX admin: sidebar toggle, confirms, repeatable rows, Chart.js dashboard charts. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  var side = $('#admin-side'), tg = $('.side-toggle');
  if (side && tg) tg.addEventListener('click', function () {
    var o = side.classList.toggle('open'); tg.setAttribute('aria-expanded', o ? 'true' : 'false');
  });

  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });

  // repeatable rows (itinerary days, hotels)
  $$('[data-repeat]').forEach(function (wrap) {
    var list = $('.repeat-list', wrap), tpl = $('template', wrap), add = $('[data-add]', wrap);
    var renumber = function () {
      $$('.repeat-row', list).forEach(function (row, i) {
        $$('[name]', row).forEach(function (el) { el.name = el.name.replace(/\[\d+\]/, '[' + i + ']'); });
        var d = $('[data-day]', row); if (d) d.value = i + 1;
      });
    };
    add.addEventListener('click', function () {
      list.appendChild(tpl.content.cloneNode(true)); renumber();
      var last = list.lastElementChild; var first = last && $('input[type=text]', last); if (first) first.focus();
    });
    wrap.addEventListener('click', function (e) {
      if (e.target.matches('[data-remove]')) { e.target.closest('.repeat-row').remove(); renumber(); }
    });
    renumber();
  });

  // dashboard charts
  if (window.Chart) {
    var green = '#2c5139', sage = '#a9b9a0', gold = '#b08d57', clay = '#a3412b', blue = '#35566e';
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#667168';
    var read = function (id) { var el = document.getElementById(id); return el ? { el: el, d: JSON.parse(el.getAttribute('data-chart')) } : null; };
    var m = read('chart-bookings');
    if (m) new Chart(m.el, { type: 'bar', data: { labels: m.d.labels, datasets: [{ label: 'Bookings', data: m.d.values, backgroundColor: green, borderRadius: 6 }] },
      options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } } });
    var r = read('chart-revenue');
    if (r) new Chart(r.el, { type: 'line', data: { labels: r.d.labels, datasets: [{ label: 'Revenue (₹)', data: r.d.values, borderColor: gold, backgroundColor: 'rgba(176,141,87,.15)', fill: true, tension: .3, pointRadius: 4 }] },
      options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return '₹' + Number(v).toLocaleString('en-IN'); } } }, x: { grid: { display: false } } } } });
    var s = read('chart-status');
    if (s) new Chart(s.el, { type: 'doughnut', data: { labels: s.d.labels, datasets: [{ data: s.d.values, backgroundColor: [gold, green, clay, blue], borderWidth: 0 }] },
      options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } } });
  }
})();
