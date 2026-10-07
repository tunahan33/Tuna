// GS Sportif Ürünler – mağaza etkileşimleri
(function () {
    // Mobil menü
    var toggle = document.querySelector('[data-menu]');
    var nav = document.querySelector('[data-catnav]');
    if (toggle && nav) toggle.addEventListener('click', function () { nav.classList.toggle('open'); });

    // Ürün sayfası adet butonları
    document.querySelectorAll('[data-qty]').forEach(function (b) {
        b.addEventListener('click', function () {
            var input = b.parentElement.querySelector('input');
            var max = parseInt(input.max || '20', 10);
            input.value = Math.min(max, Math.max(1, (parseInt(input.value, 10) || 1) + parseInt(b.dataset.qty, 10)));
        });
    });

    // Ürün detay sekmeleri
    document.querySelectorAll('[data-tabs]').forEach(function (wrap) {
        var btns = wrap.querySelectorAll('[data-tab]');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                btns.forEach(function (x) { x.classList.toggle('active', x === btn); });
                wrap.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.toggle('active', p.id === btn.dataset.tab); });
            });
        });
    });

    // Sepet sayfasında adet değişince formu gönder
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    // Ödeme: kart görselini canlı güncelle
    var cardNum = document.querySelector('[name=card_number]');
    if (cardNum) {
        var out = document.querySelector('[data-card-num]');
        var nameIn = document.querySelector('[name=card_name]');
        var nameOut = document.querySelector('[data-card-name]');
        cardNum.addEventListener('input', function () {
            var v = cardNum.value.replace(/\D/g, '').slice(0, 16);
            cardNum.value = v.replace(/(.{4})/g, '$1 ').trim();
            out.textContent = (v + '•'.repeat(16 - v.length)).replace(/(.{4})/g, '$1 ').trim();
        });
        if (nameIn) nameIn.addEventListener('input', function () { nameOut.textContent = nameIn.value.toUpperCase() || 'AD SOYAD'; });
        var exp = document.querySelector('[name=card_exp]');
        if (exp) exp.addEventListener('input', function () {
            var v = exp.value.replace(/\D/g, '').slice(0, 4);
            exp.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
        });
    }

    // Çerez tercihi (1 yıl saklanır)
    function saveCookie(prefs) {
        document.cookie = 'gs_cerez=' + encodeURIComponent(JSON.stringify(prefs)) + ';path=/;max-age=31536000;SameSite=Lax';
    }
    document.querySelectorAll('[data-cookie]').forEach(function (b) {
        b.addEventListener('click', function () {
            var all = b.dataset.cookie === 'all';
            saveCookie({ zorunlu: true, analitik: all, pazarlama: all });
            var bar = document.querySelector('[data-cookie-bar]');
            if (bar) bar.remove();
        });
    });

    // Silme vb. işlemlerde onay sor
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (ev) { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
    });
})();

// Ürün galerisi: küçük fotoğrafa tıklayınca büyük fotoğraf değişir
document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var main = g.querySelector('.pd-main');
    g.querySelectorAll('.pd-thumbs button').forEach(function (b) {
        b.addEventListener('click', function () {
            if (main && main.tagName === 'IMG') main.src = b.dataset.src;
            g.querySelectorAll('.pd-thumbs button').forEach(function (x) { x.classList.toggle('active', x === b); });
        });
    });
});
