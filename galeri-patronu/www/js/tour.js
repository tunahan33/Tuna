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
    { title: "Hoş geldin patron!", text: "Galeri Patronu'nda ikinci el araç alır, hazırlar ve kârla satarsın. Sana kısa bir tur yaptırayım; istemezsen \"Eğitimi atla\"ya bas." },
    { tab: "galeri", el: q(".top .kpis"), title: "Kasa, itibar, seviye", text: "Kasa senin paran. İtibar yükseldikçe daha çok müşteri gelir ve daha iyi fiyat verir. Sattıkça seviye atlarsın; yeni araç sınıfları ve tesisler açılır." },
    { tab: "galeri", el: q(".top .speed"), title: "Zaman", text: "Galeri 09:00'da açılır, 19:00'da kapanır. Oyunu durdurabilir, 2× ya da 4× hızlandırabilir, en sağdaki düğmeyle günü bitirebilirsin." },
    { tab: "galeri", el: q(".scene-card"), title: "Galerin", text: "Araçların parkta durur, müşteriler gelip bakar. Sahnedeki araca, müşteriye ya da binaya dokununca ilgili ekran açılır. Tesisleri yükselttikçe galerin büyür." },
    { tab: "galeri", el: q(".card.todo"), title: "Şimdi ne yapmalı?", text: "Ne yapacağını bilmezsen buraya bak: sıradaki en önemli iki adım ve düğmesi burada." },
    { tab: "piyasa", el: q("#view .crow"), title: "Araç al", text: "Her sabah ilanlar yenilenir, gün içinde yenileri düşer. İlana dokun ve satıcıyla pazarlık et. Almadan önce ekspertiz yaptırırsan gizli kusurları görürsün." },
    { tab: "piyasa", el: nav(4), title: "Ucuz mu, pahalı mı?", text: "Aracın gerçek değerini ilk başta bilemezsin. Patron → Personel'den ekspertiz ustası işe alırsan fırsatları ve pahalıları söyler; yıldızı yüksek usta daha isabetli tahmin eder." },
    { tab: "atolye", el: nav(3), title: "Atölye", text: "Aldığın aracı temizle, bakımını yap, boyasını düzelt: hazırlanan araç daha pahalıya satılır. Ustalar çalıştıkça yorulur, çardakta dinlenince hızlanır." },
    { tab: "musteri", el: nav(2), title: "Müşteriler", text: "Fiyat etiketi astığın araçlara müşteriler teklif verir. Kabul et, biraz daha iste ya da reddet. Çok bekletirsen müşteri gider." },
    { tab: "patron", el: nav(4), title: "Patron masası", text: "Tesisleri yükselt, personel al, daha büyük bir adrese taşın. Para biriktikçe Lüks'ten ev, araba, yat, takım elbise ve daha fazlasını al." },
    { tab: "galeri", title: "Reklamlar isteğe bağlı", text: "Yanında ▶ işareti olan düğmeler kısa bir reklam izletir ve bonus verir. İzlemek zorunda değilsin. Hadi ilk aracını sat!", last: true }
  ];

  // Bir ekran ilk kez açılınca çıkan kısa ipuçları (pencere türüne göre)
  G.TIPS = {
    listing: ["Pazarlık", "Önerilen teklifle başla. Çok düşük teklif satıcıyı kızdırır ve sabrı biter. Ekspertiz yaptırmadan alırsan gizli kusur çıkabilir."],
    car: ["Kendi aracın", "Önce önerilen atölye işini yap, sonra fiyat etiketini as. Etiketsiz araca müşteri bakmaz; \"Dengeli\" fiyat çoğu zaman en iyisidir."],
    cust: ["Müşteri teklifi", "Teklif etikete yakınsa kabul et, değilse \"biraz daha iste\". Çok zorlarsan müşteri kapıdan çıkar gider."],
    auction: ["İhale", "Rakip galericiler saniye saniye teklif verir. Son tekliften sonra 4 saniye kimse artırmazsa çekiç iner. Aracın değerinin üstüne çıkma!"],
    summary: ["Gün sonu", "Günün satışı, kârı ve giderleri burada. Sponsor bonusu için reklam izlemek isteğe bağlı; istemezsen geç."],
    fac: ["Tesisler", "Her yükseltme galerinin görünümünü de değiştirir. Yeni seviyeler sen seviye atladıkça açılır."]
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
