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
})();

/* ------------------------------------------------------------------
   Yazı editörü: <textarea data-rich> alanlarını Word benzeri bir
   düzenleyiciye çevirir. Kaydederken HTML otomatik olarak textarea'ya yazılır.
------------------------------------------------------------------- */
(function () {
    var tools = [
        ['h3', 'Başlık', 'formatBlock', 'h3'],
        ['p', 'Paragraf', 'formatBlock', 'p'],
        ['sep'],
        ['b', '<b>K</b>', 'bold', null, 'Kalın'],
        ['i', '<i>İ</i>', 'italic', null, 'İtalik'],
        ['u', '<u>A</u>', 'underline', null, 'Altı çizili'],
        ['sep'],
        ['ul', '• Liste', 'insertUnorderedList'],
        ['ol', '1. Liste', 'insertOrderedList'],
        ['sep'],
        ['link', '🔗 Bağlantı', 'createLink'],
        ['clear', 'Biçimi temizle', 'removeFormat'],
        ['sep'],
        ['undo', '↶', 'undo', null, 'Geri al'],
        ['redo', '↷', 'redo', null, 'Yinele'],
        ['code', '&lt;/&gt; Kod', 'code', null, 'HTML kodunu göster (ileri düzey)']
    ];
    document.querySelectorAll('textarea[data-rich]').forEach(function (ta) {
        var wrap = document.createElement('div');
        wrap.className = 'rte';
        var bar = document.createElement('div');
        bar.className = 'rte-bar';
        var area = document.createElement('div');
        area.className = 'rte-area prose';
        area.contentEditable = 'true';
        area.innerHTML = ta.value.trim() || '<p><br></p>';
        tools.forEach(function (t) {
            if (t[0] === 'sep') { var s = document.createElement('span'); s.className = 'rte-sep'; bar.appendChild(s); return; }
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = t[1];
            b.title = t[4] || t[1].replace(/<[^>]+>/g, '');
            b.dataset.cmd = t[0];
            b.addEventListener('mousedown', function (e) { e.preventDefault(); });
            b.addEventListener('click', function () {
                if (t[2] === 'code') {
                    var showCode = !ta.classList.contains('rte-code-on');
                    if (showCode) { ta.value = area.innerHTML; } else { area.innerHTML = ta.value; }
                    ta.classList.toggle('rte-code-on', showCode);
                    area.style.display = showCode ? 'none' : '';
                    b.classList.toggle('on', showCode);
                    return;
                }
                area.focus();
                if (t[2] === 'createLink') {
                    var url = prompt('Bağlantı adresi (örn. https://... veya sayfa.php?s=sss):', 'https://');
                    if (url) document.execCommand('createLink', false, url);
                    return;
                }
                document.execCommand(t[2], false, t[3] ? '<' + t[3] + '>' : null);
                sync();
            });
            bar.appendChild(b);
        });
        function sync() { if (!ta.classList.contains('rte-code-on')) ta.value = area.innerHTML; }
        area.addEventListener('input', sync);
        area.addEventListener('blur', sync);
        // Word / web sayfasından yapıştırırken gereksiz biçimleri at, düz metin olarak ekle
        area.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        });
        ta.parentNode.insertBefore(wrap, ta);
        wrap.appendChild(bar);
        wrap.appendChild(area);
        wrap.appendChild(ta);
        ta.classList.add('rte-source');
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}
        if (ta.form) ta.form.addEventListener('submit', sync);
    });
})();

/* ------------------------------------------------------------------
   Fotoğraf yükleme: sürükle-bırak, önizleme ve büyük fotoğrafları
   tarayıcıda küçültme (sunucu sınırlarına takılmadan sınırsız yükleme)
------------------------------------------------------------------- */
(function () {
    var input = document.querySelector('[data-photo-input]');
    if (!input) return;
    var drop = input.closest('[data-drop]');
    var list = document.querySelector('[data-photo-preview]');
    var status = document.querySelector('[data-photo-status]');
    var MAX = 1600;
    var picked = [];

    function shrink(file) {
        return new Promise(function (resolve) {
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return resolve(null);
            var img = new Image();
            var url = URL.createObjectURL(file);
            img.onload = function () {
                URL.revokeObjectURL(url);
                var scale = Math.min(1, MAX / Math.max(img.width, img.height));
                if (scale === 1 && file.size < 1.5 * 1024 * 1024) return resolve(file);
                var c = document.createElement('canvas');
                c.width = Math.round(img.width * scale);
                c.height = Math.round(img.height * scale);
                var ctx = c.getContext('2d');
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, c.width, c.height);
                ctx.drawImage(img, 0, 0, c.width, c.height);
                c.toBlob(function (blob) {
                    resolve(blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
                }, 'image/jpeg', 0.9);
            };
            img.onerror = function () { URL.revokeObjectURL(url); resolve(null); };
            img.src = url;
        });
    }
    function render() {
        list.innerHTML = '';
        picked.forEach(function (f, i) {
            var fig = document.createElement('figure');
            var im = document.createElement('img');
            im.src = URL.createObjectURL(f);
            var cap = document.createElement('figcaption');
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.textContent = 'Kaldır';
            rm.addEventListener('click', function () { picked.splice(i, 1); apply(); });
            cap.appendChild(rm);
            fig.appendChild(im);
            fig.appendChild(cap);
            list.appendChild(fig);
        });
        status.textContent = picked.length ? picked.length + ' yeni fotoğraf seçildi. Yüklemek için “Kaydet”e basın.' : '';
        status.hidden = !picked.length;
    }
    function apply() {
        var dt = new DataTransfer();
        picked.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        render();
    }
    function add(files) {
        status.hidden = false;
        status.textContent = 'Fotoğraflar hazırlanıyor…';
        Promise.all(Array.prototype.map.call(files, shrink)).then(function (out) {
            var bad = out.filter(function (f) { return !f; }).length;
            picked = picked.concat(out.filter(Boolean));
            apply();
            if (bad) status.textContent += ' (' + bad + ' dosya fotoğraf olmadığı için atlandı)';
        });
    }
    input.addEventListener('change', function () {
        var files = Array.prototype.slice.call(input.files);
        // change olayı input.files'ı değiştirir; önceki seçimleri korumak için yeniden ekle
        add(files.filter(function (f) { return picked.indexOf(f) === -1; }));
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); });
    });
    drop.addEventListener('drop', function (e) { add(e.dataTransfer.files); });
})();
