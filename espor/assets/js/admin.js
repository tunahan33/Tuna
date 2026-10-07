/* GS Sportif Faaliyetler - yönetim paneli etkileşimleri */
(function () {
    'use strict';
    var sb = document.querySelector('[data-sidebar]');
    var ov = document.querySelector('[data-sidebar-close]');
    var tg = document.querySelector('[data-sidebar-toggle]');
    function toggleSb(open) { sb.classList.toggle('open', open); ov.classList.toggle('open', open); }
    if (tg) tg.addEventListener('click', function () { toggleSb(!sb.classList.contains('open')); });
    if (ov) ov.addEventListener('click', function () { toggleSb(false); });

    // Satıra tıklayınca detaya git
    document.addEventListener('click', function (e) {
        var tr = e.target.closest('tr[data-href]');
        if (tr && !e.target.closest('a, button, input, select, form')) location.href = tr.getAttribute('data-href');
    });

    // Onay isteyen formlar
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
    });

    // Mesaj açılınca "okundu" işaretle
    document.querySelectorAll('details.msg.is-new').forEach(function (d) {
        d.addEventListener('toggle', function () {
            if (d.open) fetch(d.getAttribute('data-open-url'), { credentials: 'same-origin' }).then(function () { d.classList.remove('is-new'); });
        });
    });

    // Canlı yenileme (aktivite akışı)
    var ar = document.querySelector('[data-autorefresh]');
    if (ar) {
        var key = 'gse_autorefresh', timer;
        try { ar.checked = localStorage.getItem(key) === '1'; } catch (err) {}
        var arm = function () {
            clearTimeout(timer);
            if (ar.checked) timer = setTimeout(function () { location.reload(); }, parseInt(ar.getAttribute('data-autorefresh'), 10) * 1000);
        };
        ar.addEventListener('change', function () { try { localStorage.setItem(key, ar.checked ? '1' : '0'); } catch (err) {} arm(); });
        arm();
    }

    // Basit zengin metin düzenleyici
    document.querySelectorAll('[data-rte]').forEach(function (wrap) {
        var ta = wrap.querySelector('textarea');
        wrap.classList.add('rte');
        var bar = document.createElement('div');
        bar.className = 'rte-bar';
        var area = document.createElement('div');
        area.className = 'rte-area prose';
        area.contentEditable = 'true';
        area.innerHTML = ta.value;
        var tools = [
            ['B', 'bold'], ['İ', 'italic'], ['Başlık', 'formatBlock', 'h3'], ['Alt Başlık', 'formatBlock', 'h4'], ['Paragraf', 'formatBlock', 'p'],
            ['• Liste', 'insertUnorderedList'], ['1. Liste', 'insertOrderedList'], ['Bağlantı', 'createLink'], ['Temizle', 'removeFormat'], ['HTML', 'html']
        ];
        tools.forEach(function (t) {
            var b = document.createElement('button');
            b.type = 'button';
            b.textContent = t[0];
            b.addEventListener('click', function () {
                if (t[1] === 'html') {
                    var src = ta.style.display !== 'none';
                    if (src) { area.innerHTML = ta.value; ta.style.display = 'none'; area.style.display = ''; b.classList.remove('on'); }
                    else { ta.value = area.innerHTML; ta.style.display = ''; area.style.display = 'none'; b.classList.add('on'); }
                    return;
                }
                area.focus();
                if (t[1] === 'createLink') {
                    var u = prompt('Bağlantı adresi (https://...)');
                    if (u) document.execCommand('createLink', false, u);
                } else {
                    document.execCommand(t[1], false, t[2] ? '<' + t[2] + '>' : null);
                }
                ta.value = area.innerHTML;
            });
            bar.appendChild(b);
        });
        ta.style.display = 'none';
        wrap.insertBefore(bar, ta);
        wrap.insertBefore(area, ta);
        area.addEventListener('input', function () { ta.value = area.innerHTML; });
        ta.form.addEventListener('submit', function () { if (ta.style.display === 'none') ta.value = area.innerHTML; });
    });
    // Hızlı yetki değiştirme: süper admin atamasında onay iste
    document.querySelectorAll('[data-role-form]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var s = f.querySelector('select');
            if (s.value === 'super_admin' && !confirm(s.getAttribute('data-name') + ' kullanıcısı Süper Admin yapılsın mı?\n\nSüper admin tüm raporları, aktivite akışını ve ödeme ayarlarını görür. Süper admin hesapları korumalı olduğundan bu yetki sonradan panelden geri alınamaz.')) {
                e.preventDefault();
            }
        });
    });
})();
