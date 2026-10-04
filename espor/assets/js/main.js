/* GS Sportif Faaliyetler - site etkileşimleri */
(function () {
    'use strict';
    var base = (document.querySelector('link[rel=stylesheet][href*="assets/css/style.css"]') || {}).href || '';
    var root = base.split('assets/css/style.css')[0];

    // Mobil menü
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            document.body.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Paket detay penceresi
    var modal = document.getElementById('package-modal');
    var body = modal && modal.querySelector('[data-modal-body]');
    var lastFocus = null;

    function openPackage(id) {
        if (!modal) { location.href = root + 'paket.php?id=' + id; return; }
        lastFocus = document.activeElement;
        body.innerHTML = '<div class="loading">Yükleniyor…</div>';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        fetch(root + 'paket.php?ajax=1&id=' + encodeURIComponent(id), { credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) { body.innerHTML = html; modal.querySelector('.modal-close').focus(); })
            .catch(function () { location.href = root + 'paket.php?id=' + id; });
        if (history.replaceState) history.replaceState(null, '', '#paket-' + id);
    }
    function closeModal() {
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        if (history.replaceState) history.replaceState(null, '', location.pathname + location.search);
        if (lastFocus) lastFocus.focus();
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-open-package]');
        if (btn) { e.preventDefault(); openPackage(btn.getAttribute('data-open-package')); return; }
        var card = e.target.closest('[data-package]');
        if (card && !e.target.closest('a, button')) { openPackage(card.getAttribute('data-package')); return; }
        if (e.target.closest('[data-close]')) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
        var card = e.target.closest && e.target.closest('[data-package]');
        if (card && (e.key === 'Enter' || e.key === ' ') && e.target === card) { e.preventDefault(); openPackage(card.getAttribute('data-package')); }
    });
    var m = location.hash.match(/^#paket-(\d+)$/);
    if (m) openPackage(m[1]);

    // Kurumsal fatura alanları
    var inv = document.querySelector('[data-invoice-form]');
    if (inv) {
        var sync = function () {
            var c = inv.querySelector('input[name=invoice_type]:checked');
            inv.classList.toggle('is-corporate', c && c.value === 'kurumsal');
        };
        inv.addEventListener('change', sync);
        sync();
    }

    // Kart numarası biçimlendirme
    var cn = document.querySelector('[data-card-number]');
    if (cn) {
        cn.addEventListener('input', function () {
            var v = cn.value.replace(/\D/g, '').slice(0, 16);
            cn.value = v.replace(/(.{4})/g, '$1 ').trim();
        });
        cn.form.addEventListener('submit', function () { cn.value = cn.value.replace(/\D/g, ''); });
    }

    // Çerez bildirimi ve tercihleri (gse_cookie_prefs: {"analytics":0|1,"marketing":0|1})
    function setCookie(name, value) {
        document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; SameSite=Lax';
    }
    function readPrefs() {
        var mt = document.cookie.match(/(?:^|; )gse_cookie_prefs=([^;]*)/);
        try { return mt ? JSON.parse(decodeURIComponent(mt[1])) : null; } catch (err) { return null; }
    }
    window.gseConsent = readPrefs() || { analytics: 0, marketing: 0 };

    var bar = document.querySelector('[data-cookie-bar]');
    if (bar && document.cookie.indexOf('gse_cookie_ok=1') === -1 && !document.querySelector('[data-cookie-prefs]')) {
        bar.hidden = false;
        bar.querySelector('[data-cookie-ok]').addEventListener('click', function () {
            setCookie('gse_cookie_ok', '1');
            setCookie('gse_cookie_prefs', JSON.stringify({ analytics: 1, marketing: 1 }));
            bar.hidden = true;
        });
    }
    var prefs = document.querySelector('[data-cookie-prefs]');
    if (prefs) {
        var cur = readPrefs() || { analytics: 0, marketing: 0 };
        prefs.querySelectorAll('input[data-pref]').forEach(function (i) { i.checked = !!cur[i.getAttribute('data-pref')]; });
        var save = function (all) {
            var v = { analytics: 0, marketing: 0 };
            prefs.querySelectorAll('input[data-pref]').forEach(function (i) {
                if (all !== undefined) i.checked = all;
                v[i.getAttribute('data-pref')] = i.checked ? 1 : 0;
            });
            setCookie('gse_cookie_prefs', JSON.stringify(v));
            setCookie('gse_cookie_ok', '1');
            window.gseConsent = v;
            var msg = prefs.querySelector('[data-pref-msg]');
            msg.hidden = false;
            msg.textContent = 'Tercihleriniz kaydedildi (' + new Date().toLocaleString('tr-TR') + ').';
        };
        prefs.querySelector('[data-pref-save]').addEventListener('click', function () { save(); });
        prefs.querySelector('[data-pref-all]').addEventListener('click', function () { save(true); });
        prefs.querySelector('[data-pref-none]').addEventListener('click', function () { save(false); });
    }

    // SSS: h3 başlıklarını açılır-kapanır listeye çevirir
    var faq = document.querySelector('[data-faq]');
    if (faq) {
        var out = document.createElement('div');
        out.className = 'faq-list';
        var nodes = Array.prototype.slice.call(faq.childNodes);
        var cur = null;
        nodes.forEach(function (n) {
            if (n.nodeType === 1 && n.tagName === 'H3') {
                cur = document.createElement('details');
                var sm = document.createElement('summary');
                sm.textContent = n.textContent;
                cur.appendChild(sm);
                var wrap = document.createElement('div');
                wrap.className = 'faq-body';
                cur.appendChild(wrap);
                out.appendChild(cur);
            } else if (cur && (n.nodeType === 1 || n.textContent.trim())) {
                cur.querySelector('.faq-body').appendChild(n);
            } else if (!cur && n.nodeType === 1) {
                out.appendChild(n);
            }
        });
        faq.innerHTML = '';
        faq.appendChild(out);
        var first = out.querySelector('details');
        if (first) first.open = true;
    }

    // Sekmeler (arıza takibi: yeni kayıt / kayıt sorgula)
    document.querySelectorAll('[data-tabs]').forEach(function (t) {
        var btns = t.querySelectorAll('[data-tab]');
        btns.forEach(function (b) {
            b.addEventListener('click', function () {
                btns.forEach(function (x) { x.classList.toggle('active', x === b); });
                t.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== b.getAttribute('data-tab'); });
            });
        });
    });
})();
