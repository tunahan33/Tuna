// GS Sportif Ürünler – panel etkileşimleri
(function () {
    // Mobil menü
    var side = document.querySelector('[data-side]');
    var tog = document.querySelector('[data-side-toggle]');
    if (side && tog) tog.addEventListener('click', function () { side.classList.toggle('open'); });

    // Tıklanabilir tablo satırları
    document.querySelectorAll('tr[data-href]').forEach(function (tr) {
        tr.addEventListener('click', function (ev) {
            if (ev.target.closest('a, button, input, select, textarea, form')) return;
            window.location = tr.dataset.href;
        });
    });

    // Onay isteyen butonlar
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (ev) { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
    });

    // Sipariş durumu "Kargoya Verildi" seçilince kargo alanlarını göster
    var sel = document.querySelector('[data-status-select]');
    var cargo = document.querySelector('[data-cargo-fields]');
    if (sel && cargo) {
        var sync = function () { cargo.style.display = sel.value === 'kargoda' ? '' : 'none'; };
        sel.addEventListener('change', sync);
        sync();
    }

    // Sayfa düzenleyici: seçili metni etiketle sar
    var ed = document.querySelector('[data-editor]');
    document.querySelectorAll('[data-wrap]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!ed) return;
            var t = b.dataset.wrap, s = ed.selectionStart, e = ed.selectionEnd, v = ed.value;
            var inner = v.slice(s, e) || 'Metin';
            if (t === 'ul') inner = '\n<li>' + inner + '</li>\n';
            var out = '<' + t + '>' + inner + '</' + t + '>';
            ed.value = v.slice(0, s) + out + v.slice(e);
            ed.focus();
            ed.selectionStart = s; ed.selectionEnd = s + out.length;
        });
    });
})();
