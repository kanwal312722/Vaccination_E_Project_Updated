(function () {
  var $ = function (s) { return document.querySelector(s); };

  // Dark mode toggle, remembered in this browser.
  var theme = $('#theme');
  if (theme) theme.addEventListener('click', function () {
    var r = document.documentElement;
    var dark = r.dataset.theme ? r.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme:dark)').matches;
    r.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('vms-theme', r.dataset.theme); } catch (e) {}
  });

  // Cover page vaccination card: tap a dose to mark it given or due.
  document.querySelectorAll('.vb').forEach(function (b) {
    b.addEventListener('click', function () {
      var d = b.querySelector('.dot'), w = b.querySelector('.w'), done = d.classList.contains('d');
      d.className = 'dot ' + (done ? 'o' : 'd');
      d.textContent = done ? '!' : '✓';
      if (b.dataset.dose === 'ipv') w.textContent = done ? 'Due 26 Aug' : 'Given today';
      var n = document.querySelectorAll('.vb .dot.d').length;
      var msg = $('#vmsg'), st = $('.stage .sticker');
      if (msg) msg.textContent = n === 3 ? 'All doses up to date!' : 'Tap IPV to mark it given';
      if (st) st.textContent = n === 3 ? 'All caught up. Great job!' : 'Brave baby. Dose 3 done!';
    });
  });
})();
