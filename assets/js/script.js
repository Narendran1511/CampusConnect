/* CampusConnect - script.js (vanilla JavaScript)
   Sections: 1 UI helpers | 2 form validation | 3 event feed (cards, search, filters, auto-refresh) */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- 1. UI helpers ---------- */
  var menuBtn = $('#menuBtn');
  if (menuBtn) {
    menuBtn.addEventListener('click', function () {
      var target = document.getElementById(menuBtn.getAttribute('aria-controls'));
      if (target) target.classList.toggle('open');
    });
  }

  $$('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-password'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  $$('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      if (!window.confirm(btn.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  $$('[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });

  $$('[data-toggle-row]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = document.getElementById(btn.getAttribute('data-toggle-row'));
      var open = row.hasAttribute('hidden');
      if (open) row.removeAttribute('hidden'); else row.setAttribute('hidden', '');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.textContent = open ? 'Hide' : 'Details';
    });
  });

  // Live table search (client-side convenience on top of the server-side search)
  $$('[data-table-search]').forEach(function (input) {
    var table = document.getElementById(input.getAttribute('data-table-search'));
    if (!table) return;
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase();
      $$('tbody tr', table).forEach(function (tr) {
        tr.hidden = q && tr.textContent.toLowerCase().indexOf(q) === -1;
      });
    });
  });

  // Poster preview + client-side file checks
  var poster = $('#poster');
  if (poster) {
    poster.addEventListener('change', function () {
      var f = poster.files[0], img = $('#posterPreview');
      if (!f) return;
      var max = parseInt(poster.getAttribute('data-max-bytes'), 10) || 2097152;
      if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1 || f.size > max) {
        setError(poster, 'Choose a JPG, PNG or WebP image up to 2 MB.');
        poster.value = '';
        return;
      }
      clearError(poster);
      if (img) img.src = URL.createObjectURL(f);
    });
  }

  // Event form: the Publish / Save-as-draft buttons decide the status before submit
  $$('[data-set-status]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var s = $('#event_status');
      if (s) s.value = btn.getAttribute('data-set-status');
    });
  });

  /* ---------- 2. Form validation (the server validates again - this is only for convenience) ---------- */
  function fieldOf(el) { return el.closest('.field') || el.parentNode; }
  function setError(el, msg) {
    var f = fieldOf(el); f.classList.add('invalid');
    var e = $('.field-error', f);
    if (!e) { e = document.createElement('div'); e.className = 'field-error'; e.setAttribute('role', 'alert'); f.appendChild(e); }
    e.textContent = msg;
  }
  function clearError(el) {
    var f = fieldOf(el); f.classList.remove('invalid');
    var e = $('.field-error', f); if (e) e.remove();
  }
  function validateField(el) {
    var v = el.value.trim();
    if (el.hasAttribute('required') && !v) { setError(el, 'This field is required.'); return false; }
    if (v && el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { setError(el, 'Enter a valid email address.'); return false; }
    var min = parseInt(el.getAttribute('minlength'), 10);
    if (v && min && el.value.length < min) { setError(el, 'Use at least ' + min + ' characters.'); return false; }
    if (v && el.type === 'number') {
      var n = Number(v), lo = el.getAttribute('min'), hi = el.getAttribute('max');
      if (isNaN(n) || (lo !== null && n < Number(lo)) || (hi !== null && n > Number(hi))) { setError(el, 'Enter a number between ' + lo + ' and ' + hi + '.'); return false; }
    }
    var pat = el.getAttribute('pattern');
    if (v && pat && !new RegExp('^(?:' + pat + ')$').test(v)) { setError(el, 'Please match the requested format.'); return false; }
    var match = el.getAttribute('data-match');
    if (match && el.value !== document.getElementById(match).value) { setError(el, 'Passwords do not match.'); return false; }
    clearError(el); return true;
  }
  function validateEventRules(form) {
    var ok = true, g = function (id) { return document.getElementById(id); };
    var st = g('start_time'), en = g('end_time'), op = g('registration_open'), dl = g('registration_deadline'), dt = g('event_date');
    if (st && en && st.value && en.value && en.value <= st.value) { setError(en, 'End time must be after the start time.'); ok = false; }
    if (op && dl && op.value && dl.value && dl.value <= op.value) { setError(dl, 'Deadline must be after registration opens.'); ok = false; }
    if (dl && dt && en && dl.value && dt.value && en.value && dl.value > dt.value + 'T' + en.value) { setError(dl, 'Deadline cannot be after the event ends.'); ok = false; }
    return ok;
  }
  $$('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var ok = true, first = null;
      $$('input, select, textarea', form).forEach(function (el) {
        if (el.type === 'hidden' || el.type === 'file' || el.type === 'checkbox' || el.readOnly) return;
        if (!validateField(el)) { ok = false; first = first || el; }
      });
      if (form.hasAttribute('data-event-form') && !validateEventRules(form)) ok = false;
      if (!ok) { ev.preventDefault(); (first || $('.invalid input, .invalid select', form) || form).focus(); }
    });
    $$('input, select, textarea', form).forEach(function (el) {
      el.addEventListener('blur', function () { if (el.type !== 'hidden' && el.type !== 'file' && !el.readOnly && el.value !== '') validateField(el); });
    });
  });

  /* ---------- 3. Event feed (data comes from PHP/MySQL via actions/events_feed.php) ---------- */
  var grid = $('#event-grid');
  if (!grid) return;

  var feedUrl = grid.getAttribute('data-feed'), detailsUrl = grid.getAttribute('data-details');
  var mode = grid.getAttribute('data-mode'), limit = parseInt(grid.getAttribute('data-limit'), 10) || 0;
  var all = [], state = { cat: '', q: '', dept: '', date: '', view: 'upcoming', sort: 'soonest' };
  var ui = { q: $('#fq'), dept: $('#fdept'), date: $('#fdate'), view: $('#fview'), sort: $('#fsort'), reset: $('#freset'), chips: $('#categoryChips'), count: $('#eventCount') };

  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }

  var statusText = { published: 'Open', registration_closed: 'Registration closed', completed: 'Completed', cancelled: 'Cancelled' };
  var statusClass = { published: 'success', registration_closed: 'warning', completed: 'info', cancelled: 'danger' };

  function regIndicator(ev) {
    if (ev.my_status === 'confirmed') return ['\u2713 You are registered', 'mystatus'];
    if (ev.my_status === 'waitlisted') return ['On waitlist', 'mystatus'];
    if (ev.status === 'cancelled') return ['Event cancelled', 'seats none'];
    if (ev.status === 'completed') return ['Event finished', 'seats'];
    if (ev.can_register) return ['Open for registration', 'seats'];
    var map = { full: ['Full', 'seats none'], closed: ['Registration closed', 'seats none'], ineligible: ['Not eligible', 'seats none'], not_open: ['Opens soon', 'seats low'] };
    return map[ev.reg_code] || ['Unavailable', 'seats none'];
  }

  function card(ev) {
    var c = el('article', 'ecard');
    var p = el('div', 'poster'), img = el('img'); img.src = ev.poster; img.alt = 'Poster for ' + ev.title; img.loading = 'lazy';
    p.appendChild(img);
    p.appendChild(el('span', 'badge badge-' + (statusClass[ev.status] || 'muted'), statusText[ev.status] || ev.status));
    c.appendChild(p);
    var b = el('div', 'body');
    b.appendChild(el('span', 'chip-static', ev.category));
    b.appendChild(el('h3', '', ev.title));
    var m = el('div', 'meta');
    [['Date', ev.date], ['Time', ev.time], ['Venue', ev.venue], ['Organized by', ev.department], ['Register by', ev.deadline]].forEach(function (r) {
      var line = el('span'); line.appendChild(el('b', '', r[0] + ': ')); line.appendChild(document.createTextNode(r[1])); m.appendChild(line);
    });
    b.appendChild(m);
    var seatCls = ev.available === 0 ? 'seats none' : (ev.available <= Math.max(3, ev.capacity * 0.1) ? 'seats low' : 'seats');
    b.appendChild(el('span', seatCls, ev.available === 0 ? 'No seats left (' + ev.capacity + ' total)' : ev.available + ' of ' + ev.capacity + ' seats available'));
    var ind = regIndicator(ev);
    var foot = el('div', 'foot'); foot.appendChild(el('span', ind[1], ind[0]));
    var a = el('a', 'btn btn-primary btn-sm', 'View Details'); a.href = detailsUrl + '?id=' + ev.id;
    foot.appendChild(a); b.appendChild(foot); c.appendChild(b);
    return c;
  }

  function isUpcoming(ev) { return ev.status === 'published' || ev.status === 'registration_closed'; }

  function visible() {
    var list = all.filter(function (ev) {
      if (mode === 'dashboard') return isUpcoming(ev);
      if (state.cat && ev.category !== state.cat) return false;
      if (state.q && ev.title.toLowerCase().indexOf(state.q) === -1) return false;
      if (state.dept && ev.department !== state.dept) return false;
      if (state.date && ev.date_iso !== state.date) return false;
      if (state.view === 'upcoming' && !isUpcoming(ev)) return false;
      if (state.view === 'completed' && ev.status !== 'completed') return false;
      return true;
    });
    list.sort(function (a, b) {
      if (state.sort === 'newest' && mode !== 'dashboard') return b.created - a.created || b.id - a.id;
      return (state.view === 'completed' && mode !== 'dashboard') ? b.ts - a.ts : a.ts - b.ts;
    });
    return limit ? list.slice(0, limit) : list;
  }

  function render() {
    var list = visible();
    grid.textContent = '';
    if (!list.length) {
      grid.appendChild(el('p', 'empty', all.length ? 'No events match your filters. Try resetting them.' : 'No events have been published yet. Check back soon.'));
    } else {
      list.forEach(function (ev) { grid.appendChild(card(ev)); });
    }
    if (ui.count) ui.count.textContent = list.length + (list.length === 1 ? ' event' : ' events');
  }

  function fillDepartments() {
    if (!ui.dept) return;
    var cur = ui.dept.value, seen = {};
    all.forEach(function (ev) { seen[ev.department] = true; });
    ui.dept.textContent = '';
    var first = el('option', '', 'All departments'); first.value = ''; ui.dept.appendChild(first);
    Object.keys(seen).sort().forEach(function (d) { var o = el('option', '', d); o.value = d; ui.dept.appendChild(o); });
    ui.dept.value = seen[cur] ? cur : '';
    state.dept = ui.dept.value;
  }

  function load() {
    fetch(feedUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        if (r.status === 401) { window.location.reload(); throw new Error('session ended'); }
        return r.json();
      })
      .then(function (data) { all = data.events || []; fillDepartments(); render(); })
      .catch(function (err) {
        if (!all.length) { grid.textContent = ''; grid.appendChild(el('p', 'empty', 'Could not load events. Check your connection and refresh the page.')); }
        if (window.console) console.warn(err);
      });
  }

  if (ui.q) ui.q.addEventListener('input', function () { state.q = ui.q.value.trim().toLowerCase(); render(); });
  if (ui.dept) ui.dept.addEventListener('change', function () { state.dept = ui.dept.value; render(); });
  if (ui.date) ui.date.addEventListener('change', function () { state.date = ui.date.value; render(); });
  if (ui.view) ui.view.addEventListener('change', function () { state.view = ui.view.value; render(); });
  if (ui.sort) ui.sort.addEventListener('change', function () { state.sort = ui.sort.value; render(); });
  if (ui.chips) ui.chips.addEventListener('click', function (e) {
    var b = e.target.closest('.chip'); if (!b) return;
    $$('.chip', ui.chips).forEach(function (c) { c.classList.remove('active'); });
    b.classList.add('active'); state.cat = b.getAttribute('data-cat'); render();
  });
  if (ui.reset) ui.reset.addEventListener('click', function () {
    state = { cat: '', q: '', dept: '', date: '', view: 'upcoming', sort: 'soonest' };
    ui.q.value = ''; ui.dept.value = ''; ui.date.value = ''; ui.view.value = 'upcoming'; ui.sort.value = 'soonest';
    $$('.chip', ui.chips).forEach(function (c) { c.classList.toggle('active', c.getAttribute('data-cat') === ''); });
    render();
  });

  load();
  // Newly published events show up without the student doing anything: refresh every 30 s and when the tab regains focus.
  setInterval(load, 30000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
})();
