/* GS Projeler - site etkileşimleri */
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

    // Çerez bildirimi
    var bar = document.querySelector('[data-cookie-bar]');
    if (bar && document.cookie.indexOf('gsp_cookie_ok=1') === -1) {
        bar.hidden = false;
        bar.querySelector('[data-cookie-ok]').addEventListener('click', function () {
            document.cookie = 'gsp_cookie_ok=1; path=/; max-age=31536000; SameSite=Lax';
            bar.hidden = true;
        });
    }
})();

// Kopyala butonları (IBAN, sipariş no)
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b || !navigator.clipboard) return;
    navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () {
        var t = b.textContent; b.textContent = 'Kopyalandı ✓'; setTimeout(function () { b.textContent = t; }, 1500);
    });
});
