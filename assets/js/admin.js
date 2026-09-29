(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Mobil kenar çubuğu
  var burger = $('#burger'), overlay = $('#sbOverlay');
  if (burger) burger.addEventListener('click', function () { document.body.classList.toggle('sb-open'); });
  if (overlay) overlay.addEventListener('click', function () { document.body.classList.remove('sb-open'); });

  // Kullanıcı menüsü
  var menu = $('.tb-menu');
  if (menu) {
    $('.avatar', menu).addEventListener('click', function (e) { e.stopPropagation(); menu.classList.toggle('open'); });
    document.addEventListener('click', function () { menu.classList.remove('open'); });
  }

  // Tıklanabilir satırlar
  document.addEventListener('click', function (e) {
    var tr = e.target.closest('tr[data-href]');
    if (tr && !e.target.closest('a,button,input,select')) location.href = tr.getAttribute('data-href');
  });

  // Canlı aktivite akışı (Süper Admin) — 10 sn'de bir yeni kayıtları getirir
  var feed = $('#liveFeed');
  if (feed) {
    var poll = function () {
      if (document.hidden) return;
      fetch(feed.getAttribute('data-feed') + '?after=' + feed.getAttribute('data-last'), { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (!d || !d.html) return;
          feed.setAttribute('data-last', d.last);
          var tmp = document.createElement('ul');
          tmp.innerHTML = d.html;
          $$('li', tmp).reverse().forEach(function (li) {
            li.classList.add('new');
            feed.insertBefore(li, feed.firstChild);
          });
          var max = feed.classList.contains('compact') ? 12 : 50;
          while (feed.children.length > max) feed.removeChild(feed.lastChild);
        })
        .catch(function () {});
    };
    setInterval(poll, 10000);
  }

  // Basit metin editörü: kalın, başlık, liste, bağlantı + HTML görünümü
  $$('textarea[data-editor]').forEach(function (ta) {
    var wrap = document.createElement('div');
    wrap.className = 'editor';
    var bar = document.createElement('div');
    bar.className = 'ed-bar';
    var area = document.createElement('div');
    area.className = 'ed-area';
    area.contentEditable = 'true';
    area.innerHTML = ta.value;
    var tools = [
      ['B', 'bold'], ['İ', 'italic'], ['|'], ['Başlık', 'formatBlock', 'h2'], ['Alt başlık', 'formatBlock', 'h3'], ['Paragraf', 'formatBlock', 'p'], ['|'],
      ['• Liste', 'insertUnorderedList'], ['1. Liste', 'insertOrderedList'], ['|'], ['Bağlantı', 'link'], ['Temizle', 'removeFormat'], ['|'], ['HTML', 'html']
    ];
    tools.forEach(function (t) {
      if (t[0] === '|') { var s = document.createElement('span'); s.className = 'sep'; bar.appendChild(s); return; }
      var b = document.createElement('button');
      b.type = 'button'; b.textContent = t[0];
      b.addEventListener('click', function () {
        if (t[1] === 'html') {
          var showHtml = ta.hidden === false;
          if (showHtml) { area.innerHTML = ta.value; ta.hidden = true; area.hidden = false; b.classList.remove('on'); }
          else { ta.value = area.innerHTML; ta.hidden = false; area.hidden = true; b.classList.add('on'); }
          return;
        }
        area.focus();
        if (t[1] === 'link') {
          var u = prompt('Bağlantı adresi (ör. https://... veya /iletisim):');
          if (u) document.execCommand('createLink', false, u);
          return;
        }
        document.execCommand(t[1], false, t[2] ? '<' + t[2] + '>' : null);
      });
      bar.appendChild(b);
    });
    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(bar);
    wrap.appendChild(area);
    wrap.appendChild(ta);
    ta.hidden = true;
    var form = ta.closest('form');
    if (form) form.addEventListener('submit', function () { if (ta.hidden) ta.value = area.innerHTML; });
  });

  // Kaydedilmemiş değişiklik uyarısı
  $$('form[data-editor-form]').forEach(function (f) {
    var dirty = false;
    f.addEventListener('input', function () { dirty = true; });
    f.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });
})();
