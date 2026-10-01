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

/* ---------- Mağaza ---------- */
(function () {
    'use strict';
    var money = function (v) { return v.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺'; };

    // Adet butonları
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-qty]');
        if (!b) return;
        var input = b.parentNode.querySelector('input');
        var max = parseInt(input.max || '99', 10), min = parseInt(input.min || '1', 10);
        input.value = Math.min(max, Math.max(min, (parseInt(input.value, 10) || 0) + parseInt(b.getAttribute('data-qty'), 10)));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Ürün sayfası: renk seçilince bedenlerin stok durumu
    var form = document.querySelector('[data-variant-form]');
    if (form) {
        var stock = JSON.parse(form.getAttribute('data-stock') || '{}');
        var low = parseInt(form.getAttribute('data-low') || '3', 10);
        var note = form.querySelector('[data-stock-note]');
        var addBtn = form.querySelector('.add-btn');
        var qtyInput = form.querySelector('input[name=qty]');
        var refresh = function () {
            var color = (form.querySelector('input[name=color]:checked') || {}).value;
            form.querySelector('[data-color-label]').textContent = color || '';
            var anyStock = false;
            form.querySelectorAll('input[name=size]').forEach(function (s) {
                var n = (stock[color] || {})[s.value] || 0;
                s.disabled = n <= 0;
                s.parentNode.classList.toggle('out', n <= 0);
                if (n <= 0 && s.checked) s.checked = false;
                if (n > 0) anyStock = true;
            });
            var size = form.querySelector('input[name=size]:checked');
            form.querySelector('[data-size-label]').textContent = size ? size.value : 'Seçiniz';
            note.className = 'stock-note';
            if (size) {
                var n = stock[color][size.value];
                qtyInput.max = n;
                if (parseInt(qtyInput.value, 10) > n) qtyInput.value = n;
                note.textContent = n <= low ? 'Son ' + n + ' ürün! Tükenmeden al.' : 'Stokta var';
                note.classList.add(n <= low ? 'low' : 'ok');
            } else {
                note.textContent = anyStock ? '' : 'Bu renk için stok tükendi.';
                if (!anyStock) note.classList.add('low');
            }
            addBtn.disabled = !anyStock;
            addBtn.textContent = anyStock ? 'Sepete Ekle' : 'Tükendi';
        };
        form.addEventListener('change', refresh);
        form.addEventListener('submit', function (e) {
            if (!form.querySelector('input[name=size]:checked')) {
                e.preventDefault();
                note.textContent = 'Lütfen beden seçin.';
                note.className = 'stock-note low';
            }
        });
        refresh();

        // Baskı seçilince fiyat
        var priceEl = document.querySelector('[data-price]');
        var prints = form.querySelectorAll('[data-print]');
        prints.forEach(function (p) {
            p.addEventListener('input', function () {
                if (p.name === 'print_number') p.value = p.value.replace(/\D/g, '');
                var has = Array.prototype.some.call(prints, function (x) { return x.value.trim() !== ''; });
                var base = parseFloat(priceEl.getAttribute('data-base')), extra = parseFloat(priceEl.getAttribute('data-extra'));
                priceEl.textContent = money(base + (has ? extra : 0));
            });
        });
    }

    // Galeri
    document.querySelectorAll('[data-gallery] .pd-thumbs button').forEach(function (b) {
        b.addEventListener('click', function () {
            var main = document.querySelector('.pd-main img');
            if (main) main.src = b.getAttribute('data-src');
            b.parentNode.querySelectorAll('button').forEach(function (x) { x.classList.toggle('active', x === b); });
        });
    });

    // Mobil filtre paneli
    var ft = document.querySelector('[data-filter-toggle]');
    var fs = document.querySelector('[data-filters]');
    if (ft && fs) {
        ft.addEventListener('click', function () { fs.classList.toggle('open'); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fs.classList.remove('open'); });
    }

    // Fatura adresi aynı
    var same = document.querySelector('[data-same-billing]');
    var bill = document.querySelector('[data-billing]');
    if (same && bill) {
        var sync = function () { bill.classList.toggle('show', !same.checked); };
        same.addEventListener('change', sync);
        sync();
    }
})();
