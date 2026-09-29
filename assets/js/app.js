(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Mobil menü
  var toggle = $('#menuToggle'), nav = $('#nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Modal pencereler (paket detayları, sözleşmeler)
  function openModal(id) {
    var d = document.getElementById(id);
    if (!d) return false;
    if (typeof d.showModal === 'function') { d.showModal(); return true; }
    return false;
  }
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-modal]');
    if (trigger) {
      if (openModal(trigger.getAttribute('data-modal'))) e.preventDefault();
      return;
    }
    if (e.target.closest('[data-close]')) {
      var dlg = e.target.closest('dialog');
      if (dlg) { e.preventDefault(); dlg.close(); }
      return;
    }
    // Paket kartının boş alanına tıklanınca detay penceresini aç
    var card = e.target.closest('.package-card');
    if (card && !e.target.closest('a,button')) {
      var btn = $('[data-modal]', card);
      if (btn) openModal(btn.getAttribute('data-modal'));
    }
  });
  $$('dialog.modal').forEach(function (d) {
    d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
  });

  // Ödeme formu: kurumsal fatura alanları ve sözleşmeye canlı bilgi aktarımı
  var form = $('#checkoutForm');
  if (form) {
    var corp = $('.corporate', form);
    $$('input[name="invoice_type"]', form).forEach(function (r) {
      r.addEventListener('change', function () { corp.hidden = r.value !== 'kurumsal' || !r.checked; });
    });
    var sync = function () {
      var vals = {};
      $$('[data-bind-src]', form).forEach(function (el) { vals[el.getAttribute('data-bind-src')] = el.value.trim(); });
      if (vals.city) vals.address = (vals.address ? vals.address + ' ' : '') + vals.city;
      $$('[data-bind]').forEach(function (el) {
        var v = vals[el.getAttribute('data-bind')];
        el.textContent = v ? v : '…';
      });
    };
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    sync();
  }

  // Kart numarası biçimlendirme (3D_PAY modeli)
  $$('[data-card]').forEach(function (el) {
    el.addEventListener('input', function () {
      el.value = el.value.replace(/\D/g, '').slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 ');
    });
    el.form && el.form.addEventListener('submit', function () { el.value = el.value.replace(/\s/g, ''); });
  });

  // Ortak ödeme sayfasına otomatik yönlendirme
  var bank = $('#bankForm');
  if (bank) setTimeout(function () { bank.submit(); }, 1500);

  // Çerez tercihleri
  var COOKIE = 'gsp_cookie_consent';
  function readConsent() {
    var m = document.cookie.match(new RegExp('(?:^|; )' + COOKIE + '=([^;]*)'));
    if (!m) return null;
    var o = {};
    decodeURIComponent(m[1]).split('|').forEach(function (p) { var kv = p.split(':'); o[kv[0]] = kv[1] === '1'; });
    return o;
  }
  function writeConsent(o) {
    var v = ['p', 'a', 'm'].map(function (k) { return k + ':' + (o[k] ? '1' : '0'); }).join('|');
    document.cookie = COOKIE + '=' + encodeURIComponent(v) + '; max-age=' + (365 * 86400) + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
  }
  var bar = $('#cookieBar'), prefs = $('#cookiePrefs'), consent = readConsent();
  if (bar && !consent && !prefs) bar.hidden = false;
  if (prefs && consent) $$('[data-cat]', prefs).forEach(function (c) { c.checked = !!consent[c.getAttribute('data-cat')]; });
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-cookie]');
    if (!b) return;
    var act = b.getAttribute('data-cookie'), o = { p: false, a: false, m: false };
    if (act === 'accept') o = { p: true, a: true, m: true };
    if (act === 'save' && prefs) $$('[data-cat]', prefs).forEach(function (c) { o[c.getAttribute('data-cat')] = c.checked; });
    writeConsent(o);
    if (bar) bar.hidden = true;
    if (prefs) {
      $$('[data-cat]', prefs).forEach(function (c) { c.checked = !!o[c.getAttribute('data-cat')]; });
      var s = $('#cookieSaved'); if (s) s.hidden = false;
    }
  });

  // Tıklanabilir tablo satırları
  $$('tr[data-href]').forEach(function (tr) {
    tr.addEventListener('click', function (e) { if (!e.target.closest('a,button')) location.href = tr.getAttribute('data-href'); });
  });
})();
