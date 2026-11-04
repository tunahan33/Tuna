/* Galeri Patronu — ilk oynayanlar için rehber: adım adım tanıtım turu ve ekranlara ilk girişte çıkan ipuçları.
   "Eğitimi atla" turu da ipuçlarını da tek dokunuşla kapatır. Patron → Durum'dan tur baştan izlenebilir. */
(function (G) {
  "use strict";
  const UI = G.UI;
  const S = () => G.S;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));
  const nav = n => () => document.querySelectorAll("#nav button")[n];
  const q = sel => () => document.querySelector(sel);

  // tab: adımda açılacak sekme, el: vurgulanacak öğe (yoksa ekranın ortasında çıkar)
  G.TOUR = [
    { title: "Hoş geldin patron!", text: "Ucuza al, hazırla, kârla sat. Kısa bir tur?" },
    { tab: "galeri", el: q(".top .kpis"), title: "Kasa, itibar, seviye", text: "Paran, itibarın ve seviyen. Sattıkça büyürsün." },
    { tab: "galeri", el: q(".top .speed"), title: "Zaman", text: "Durdur, hızlandır ya da günü bitir." },
    { tab: "galeri", el: q(".scene-card"), title: "Galerin", text: "Galerin. Yükselttikçe görünümü değişir." },
    { tab: "galeri", el: q(".card.todo"), title: "Şimdi ne yapmalı?", text: "Takılınca buraya bak." },
    { tab: "piyasa", el: q("#view .crow"), title: "Araç al", text: "İlana dokun, pazarlık et. Ekspertiz gizli kusuru bulur." },
    { tab: "piyasa", el: nav(4), title: "Ucuz mu, pahalı mı?", text: "Değeri görmek için ekspertiz ustası al." },
    { tab: "atolye", el: nav(3), title: "Atölye", text: "Aracı hazırla, daha pahalıya sat." },
    { tab: "musteri", el: nav(2), title: "Müşteriler", text: "Teklifler alttaki kartta açılır. Danışman alırsan o halleder." },
    { tab: "patron", el: nav(4), title: "Patron masası", text: "Tesis, personel, adres ve lüks burada." },
    { tab: "galeri", title: "Reklamlar isteğe bağlı", text: "▶ işaretli düğmeler reklamla bonus verir. Hadi başla!", last: true }
  ];

  // Bir ekran ilk kez açılınca çıkan kısa ipuçları (pencere türüne göre)
  G.TIPS = {
    listing: ["Pazarlık", "Önerilen teklifle başla; çok düşük teklif satıcıyı kızdırır."],
    car: ["Kendi aracın", "Önce hazırla, sonra fiyat etiketini as."],
    cust: ["Müşteri teklifi", "Teklif iyiyse kabul et, değilse biraz daha iste."],
    auction: ["İhale", "Değerinin üstüne çıkma!"],
    summary: ["Gün sonu", "Günün kârı ve giderlerin."],
    fac: ["Tesisler", "Her yükseltme görünümü de değiştirir."]
  };

  let step = -1, wasPaused = false;
  const tut = () => (S().tut = S().tut || { done: false, off: false, seen: {} });
  UI.touring = () => step >= 0;

  function layer() {
    let el = document.getElementById("tour");
    if (!el) {
      el = document.createElement("div");
      el.id = "tour";
      el.className = "tour";
      el.addEventListener("click", e => {
        const b = e.target.closest("[data-t]");
        if (!b) return;
        if (b.dataset.t === "skip") UI.tourSkip();
        else if (b.dataset.t === "next") UI.tourGo(step + 1);
        else if (b.dataset.t === "back") UI.tourGo(step - 1);
      });
      document.body.appendChild(el);
    }
    return el;
  }

  UI.tourStart = function () {
    if (step >= 0 || tut().done || tut().off) return;
    wasPaused = S().paused;
    S().paused = true;
    UI.hideTip();
    UI.tourGo(0);
  };

  UI.tourGo = function (n) {
    if (n >= G.TOUR.length) return UI.tourEnd();
    n = Math.max(0, n);
    const st = G.TOUR[n];
    if (st.tab && UI.tab !== st.tab) { UI.tab = st.tab; UI.sheet = null; UI.render(); }
    let target = st.el ? st.el() : null;
    if (st.el && !target) { // hedef bu oyunda yoksa (ör. yapılacak iş yok) adımı geç
      return UI.tourGo(n > step ? n + 1 : n - 1);
    }
    step = n;
    if (target && target.closest(".view")) target.scrollIntoView({ block: "center" });
    else window.scrollTo(0, 0);
    const el = layer();
    el.innerHTML = `<div class="tour-hole" ${target ? "" : "hidden"}></div><div class="tour-card" role="dialog" aria-modal="true" aria-labelledby="tourT">
      <div class="tour-top"><span class="tour-n">${n + 1} / ${G.TOUR.length}</span><button type="button" class="tour-skip" data-t="skip">Eğitimi atla</button></div>
      <h3 id="tourT">${esc(st.title)}</h3><p>${esc(st.text)}</p>
      <div class="tour-dots">${G.TOUR.map((_, i) => `<i class="${i === n ? "on" : i < n ? "past" : ""}"></i>`).join("")}</div>
      <div class="tour-btns">${n > 0 ? `<button type="button" class="btn" data-t="back">Geri</button>` : ""}<button type="button" class="btn pri" data-t="next">${st.last ? "Başla" : n === 0 ? "Turu başlat" : "İleri"}</button></div></div>`;
    el.classList.toggle("dim", !target);
    place(target);
    el.querySelector('[data-t="next"]').focus({ preventScroll: true });
  };

  function place(target) {
    const el = document.getElementById("tour");
    if (!el) return;
    const card = el.querySelector(".tour-card"), hole = el.querySelector(".tour-hole");
    const vh = window.innerHeight, pad = 6;
    if (!target) { card.style.top = Math.max(16, (vh - card.offsetHeight) / 2) + "px"; return; }
    const r = target.getBoundingClientRect();
    Object.assign(hole.style, { left: r.left - pad + "px", top: r.top - pad + "px", width: r.width + pad * 2 + "px", height: r.height + pad * 2 + "px" });
    const ch = card.offsetHeight, gap = 14;
    const below = r.bottom + pad + gap, above = r.top - pad - gap - ch;
    let top = r.top + r.height / 2 < vh / 2 ? below : above;
    if (top + ch > vh - 12) top = above;
    if (top < 12) top = Math.min(vh - ch - 12, below);
    card.style.top = Math.max(12, top) + "px";
  }
  window.addEventListener("resize", () => { if (step >= 0) { const st = G.TOUR[step]; place(st.el ? st.el() : null); } });

  UI.tourEnd = function () {
    step = -1;
    const el = document.getElementById("tour");
    if (el) el.remove();
    tut().done = true;
    S().paused = wasPaused;
    UI.tab = "galeri";
    UI.render();
    G.save && G.save();
  };
  UI.tourSkip = function () {
    tut().off = true;
    UI.tourEnd();
  };

  // ---- Ekran ipuçları ----
  UI.hideTip = function () { const t = document.getElementById("tip"); if (t) t.remove(); };
  UI.maybeTip = function (kind) {
    const t = tut();
    if (step >= 0 || t.off || !t.done || t.seen[kind] || !G.TIPS[kind] || document.getElementById("tip")) return;
    t.seen[kind] = 1;
    const [title, text] = G.TIPS[kind];
    const el = document.createElement("div");
    el.id = "tip";
    el.className = "tip";
    el.setAttribute("role", "status");
    el.innerHTML = `<div class="tip-ic" aria-hidden="true">i</div><div class="tip-body"><b>${esc(title)}</b><p>${esc(text)}</p>
      <div class="tip-btns"><button type="button" class="btn sm pri" data-tip="ok">Anladım</button><button type="button" class="tip-off" data-tip="off">İpuçlarını kapat</button></div></div>`;
    el.addEventListener("click", e => {
      const b = e.target.closest("[data-tip]");
      if (!b) return;
      if (b.dataset.tip === "off") tut().off = true;
      UI.hideTip();
      G.save && G.save();
    });
    document.body.appendChild(el);
  };
  UI.tourRestart = function () {
    const t = tut();
    t.off = false; t.done = false; t.seen = {};
    UI.tourStart();
  };
})(window.G);
