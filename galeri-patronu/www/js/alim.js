/* Galeri Patronu — satın alım uzmanı
 * Oyuncunun belirlediği günlük bütçeyle ilanlardan en kârlı aracı seçer, pazarlık edip alır.
 * Ekspertiz ustası varsa onunla birlikte çalışır: değeri ustanın tahminiyle karşılaştırır (hangisi isabetliyse onu kullanır)
 * ve almadan önce araca ekspertiz yaptırır; gizli kusur çıkarsa vazgeçer. Uzmanın hata payı yıldızına göre küçüktür. */
(function (G) {
  "use strict";
  const UI = G.UI, R = G.R;
  const S = () => G.S;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  G.BUYER = {
    spread: [0, 0.10, 0.07, 0.05, 0.035, 0.02],   // 1★ ±%10 … 5★ ±%2
    perDay: sk => 1 + Math.floor(sk / 2),           // günde en fazla alım: 1★ 1, 3★ 2, 5★ 3
    minDeal: 1.06,                                  // tahmini değer istenenin en az %6 üstündeyse alır
    budgets: [[0, "Kapalı"], [1e6, "1 milyon"], [3e6, "3 milyon"], [10e6, "10 milyon"], [30e6, "30 milyon"], [100e6, "100 milyon"], [-1, "Kasanın yarısı"]]
  };
  G.buyerSkill = () => G.staffOf("alim").reduce((a, s) => Math.max(a, s.skill), 0);
  const B = () => {
    const s = S();
    s.buyer = s.buyer || { budget: 3e6, day: 0, spent: 0, n: 0, log: [] };
    if (s.buyer.day !== s.day) { s.buyer.day = s.day; s.buyer.spent = 0; s.buyer.n = 0; }
    return s.buyer;
  };
  G.buyerBudgetLeft = function () {
    const b = B();
    if (!b.budget) return 0;
    const cap = b.budget < 0 ? S().money * 0.5 : b.budget;
    return Math.max(0, Math.min(cap - b.spent, S().money - 100000));
  };
  function noise(key) {
    let h = 2166136261;
    for (const ch of String(key)) { h ^= ch.charCodeAt(0); h = Math.imul(h, 16777619); }
    return ((h >>> 0) % 2001) / 1000 - 1;
  }
  // uzmanın değer tahmini; ekspertiz ustası daha isabetliyse onun tahmini kullanılır
  G.buyerEstimate = function (car) {
    const sk = G.buyerSkill(), es = G.appraiser ? G.appraiser() : 0;
    const own = G.BUYER.spread[sk], eks = es ? G.EST_SPREAD[es] : 1;
    const spread = Math.min(own, eks);
    const key = car.id + (eks < own ? ":e" + es : ":a" + sk);
    return R.r1k(G.value(car, false) * (1 + noise(key) * spread));
  };
  const times = () => [G.OPEN + 30, ...G.LISTING_DROPS.map(m => m + 10)];

  G.buyerMinute = function () {
    const sk = G.buyerSkill(), s = S();
    if (!sk || !times().includes(s.min)) return;
    const b = B();
    const withEks = G.appraiser && G.appraiser() > 0;
    while (b.n < G.BUYER.perDay(sk) && G.spaceLeft() > 0) {
      const left = G.buyerBudgetLeft();
      if (left <= 0) return;
      const pick = s.listings.filter(l => !l.done && l.last <= left)
        .map(l => ({ l, est: G.buyerEstimate(l.car) })).map(o => ({ ...o, ratio: o.est / o.l.last }))
        .filter(o => o.ratio >= G.BUYER.minDeal).sort((a, c) => c.ratio - a.ratio);
      if (!pick.length) return;
      let bought = false;
      for (const o of pick.slice(0, 3)) {
        const l = o.l;
        let est = o.est;
        // ekspertiz ustasıyla birlikte: almadan önce ekspertiz, kusur çıkarsa tahmin yenilenir
        if (withEks && !l.car.inspected) {
          const r = G.inspect(l.car);
          if (r.ok && r.found.length) { est = G.buyerEstimate(l.car); if (est / l.last < G.BUYER.minDeal) { b.log.unshift(`${G.carName(l.car)}: ekspertizde ${r.found.join(", ")} çıktı, vazgeçildi.`); continue; } }
        }
        let r = G.offerListing(l, Math.min(l.last, est * 0.86));
        if (r.res === "counter" && l.last <= est / 1.04 && l.last <= G.buyerBudgetLeft()) r = G.offerListing(l, l.last);
        if (r.res === "accepted") {
          b.spent += r.price; b.n++;
          const st = G.staffOf("alim").sort((x, y) => y.skill - x.skill)[0];
          const msg = `${st.name} ${G.carTitle(l.car)} aracını ${G.fmtS(r.price)}'ye aldı (tahmini değer ${G.fmtS(est)}).`;
          b.log.unshift(msg);
          G.feed(`Satın alım uzmanı: ${msg}`, "buy");
          bought = true;
          break;
        }
      }
      b.log = b.log.slice(0, 6);
      if (!bought) return;
    }
  };

  // ---- Arayüz ----
  UI.buyerCard = function () {
    const sk = G.buyerSkill();
    if (!sk) return "";
    const b = B(), withEks = G.appraiser && G.appraiser() > 0;
    return `<div class="card"><h3>Satın alım bütçesi</h3><p class="muted small">Günde ${G.BUYER.perDay(sk)} araç · hata ±%${Math.round(G.BUYER.spread[sk] * 100)} · ${withEks ? "ekspertizle" : "ekspertizsiz"}</p>
      <div class="presets">${G.BUYER.budgets.map(([v, l]) => `<button type="button" class="btn sm ${b.budget === v ? "pri" : ""}" data-a="buyer-budget" data-v="${v}" aria-pressed="${b.budget === v}">${l}</button>`).join("")}</div>
      <p class="small">Bugün: ${b.n} araç · ${G.fmtS(b.spent)} harcandı${b.budget ? ` · kalan ${G.fmtS(G.buyerBudgetLeft())}` : ""}</p>
      ${b.log.length ? `<div class="small muted">${b.log.slice(0, 4).map(x => `<div>• ${esc(x)}</div>`).join("")}</div>` : ""}</div>`;
  };
  // Araç al ekranında kısa özet
  UI.buyerNote = function () {
    if (!G.buyerSkill()) return "";
    const b = B();
    return `<div class="card buyer-note"><div class="row"><div><b>Satın alım uzmanın</b><p class="small muted">${b.budget ? `Bugün ${b.n} araç aldı · kalan bütçe ${G.fmtS(G.buyerBudgetLeft())}` : "Bütçe kapalı, alım yapmıyor"}</p></div><button class="btn sm" type="button" data-a="buyer-go">Bütçe</button></div></div>`;
  };
  UI.ACT["buyer-go"] = () => { UI.tab = "patron"; UI.sub.patron = "personel"; UI.sheet = null; window.scrollTo(0, 0); UI.sig = ""; UI.render(); };
  UI.ACT["buyer-budget"] = el => { B().budget = +el.dataset.v; UI.sig = ""; UI.render(); };
})(window.G);
