/* Galeri Patronu — sıfır araç bayiliği
 * Galeri belli bir seviyeye gelince markalardan bayilik lisansı istenebilir. Şartları karşılayan galeri başvurur,
 * ücretini öder; evrak incelemesi ve showroom denetiminden geçerse lisans çıkar. Lisanslı markanın sıfır araçları
 * üreticiden bayi fiyatına sipariş edilir, ertesi sabah galeriye gelir ve liste fiyatından satılır. */
(function (G) {
  "use strict";
  const UI = G.UI, R = G.R;
  const S = () => G.S;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  // loc: en az hangi adres (0 sanayi … 4 merkez), sold: toplam satış, fee: başvuru ücreti, daily: lisans başına günlük aidat
  G.DEALER = {
    unlock: 6,          // galeri binası bu seviyeye gelince bayilik ekranı açılır
    cost: 0.86,         // bayi fiyatı = liste fiyatı × bu oran
    steps: [{ id: "evrak", name: "Evrak incelemesi", days: 2 }, { id: "denetim", name: "Showroom denetimi", days: 1 }],
    inspectClean: 60,   // denetim günü parktaki araçların ortalama temizliği en az bu kadar olmalı
    retryDays: 3,
    tiers: [
      { id: 1, name: "Bayi", brands: ["Tufan", "Ravex", "Hwarang", "Wolfram"], req: { galeri: 8, rep: 55, sold: 40, loc: 1 }, fee: 1e6, daily: 4000 },
      { id: 2, name: "Yetkili bayi", brands: ["Fiamma", "Raijin"], req: { galeri: 12, rep: 65, sold: 120, loc: 2 }, fee: 4e6, daily: 12000 },
      { id: 3, name: "Premium bayi", brands: ["Valkyr", "Vantor", "Sturm"], req: { galeri: 16, rep: 80, sold: 300, loc: 3 }, fee: 15e6, daily: 40000 }
    ]
  };
  const D = () => (S().dealer = S().dealer || { lic: {}, orders: [] });
  G.dealerTier = brand => G.DEALER.tiers.find(t => t.brands.includes(brand));
  G.dealerModels = brand => G.MODELS.filter(m => m.brand === brand && m.seg !== "klasik" && m.seg !== "ultra");
  G.licensed = brand => { const l = D().lic[brand]; return !!l && l.state === "ok"; };
  G.anyLicense = () => Object.values(D().lic).some(l => l.state === "ok");
  G.dealerCost = md => R.r1k(md.price * G.DEALER.cost);

  G.dealerChecks = function (brand) {
    const t = G.dealerTier(brand), q = t.req, s = S();
    return [
      { ok: G.facLevel("galeri") >= q.galeri, text: `Galeri binası ${q.galeri}. seviye`, now: `${G.facLevel("galeri")}` },
      { ok: s.rep >= q.rep, text: `İtibar en az ${q.rep}`, now: `${Math.round(s.rep)}` },
      { ok: s.stats.sold >= q.sold, text: `Toplam ${q.sold} satış`, now: `${s.stats.sold}` },
      { ok: s.loc >= q.loc, text: `Adres: en az ${G.LOCS[q.loc].name}`, now: G.loc().name },
      { ok: s.money >= t.fee, text: `Başvuru ücreti ${G.fmtS(t.fee)}`, now: G.fmtS(s.money) }
    ];
  };

  G.dealerApply = function (brand) {
    const t = G.dealerTier(brand), s = S(), l = D().lic[brand];
    if (!t) return { ok: false };
    if (l && (l.state === "ok" || G.DEALER.steps.some(x => x.id === l.state))) return { ok: false, msg: "Başvurun zaten var." };
    if (l && l.state === "red" && s.day < l.retry) return { ok: false, msg: `Yeniden başvuru ${l.retry - s.day} gün sonra.` };
    if (!G.dealerChecks(brand).every(c => c.ok)) return { ok: false, msg: "Bütün şartları karşılamıyorsun." };
    s.money -= t.fee;
    s.today.expenses += t.fee;
    D().lic[brand] = { state: G.DEALER.steps[0].id, until: s.day + G.DEALER.steps[0].days, paid: t.fee, applied: s.day };
    G.feed(`${brand} bayilik başvurusu yapıldı. ${G.DEALER.steps[0].name} ${G.DEALER.steps[0].days} gün sürecek.`, "info");
    return { ok: true };
  };

  // Her yeni günün başında: başvuru adımları ilerler, siparişler gelir. Gün sonu raporuna haber ekler.
  G.dealerDay = function (summary) {
    const s = S(), d = D();
    for (const [brand, l] of Object.entries(d.lic)) {
      const i = G.DEALER.steps.findIndex(x => x.id === l.state);
      if (i < 0 || s.day < l.until) continue;
      const nx = G.DEALER.steps[i + 1];
      if (nx) {
        l.state = nx.id; l.until = s.day + nx.days;
        summary.news.push({ title: `${brand} bayiliği`, text: `Evraklar onaylandı. Yarın üretici showroom denetimine gelecek: araçların temiz, itibarın yüksek olsun.` });
        continue;
      }
      // karar: denetim günü itibar ve park alanının temizliği
      const t = G.dealerTier(brand);
      const lot = s.cars.filter(c => c.status === "lot");
      const clean = lot.length ? lot.reduce((a, c) => a + c.clean, 0) / lot.length : 100;
      const why = s.rep < t.req.rep - 5 ? `itibarın ${Math.round(s.rep)}'e düşmüş` : clean < G.DEALER.inspectClean ? `araçlar kirliydi (temizlik ortalaması ${Math.round(clean)})` : "";
      if (!why) {
        l.state = "ok"; l.since = s.day;
        summary.news.push({ title: `${brand} bayiliği onaylandı!`, text: `Artık ${brand} sıfır araçlarını üreticiden bayi fiyatına sipariş edip satabilirsin (Araç al → Sıfır).` });
        G.feed(`${brand} bayilik lisansı çıktı! Sıfır araç siparişi açıldı.`, "level");
      } else {
        const back = Math.round(l.paid * 0.5);
        s.money += back;
        d.lic[brand] = { state: "red", retry: s.day + G.DEALER.retryDays, why, back };
        summary.news.push({ title: `${brand} bayiliği reddedildi`, text: `Denetimde ${why}. Ücretin yarısı (${G.fmt(back)}) iade edildi; ${G.DEALER.retryDays} gün sonra yeniden başvurabilirsin.` });
        G.feed(`${brand} bayilik başvurusu reddedildi: ${why}.`, "bad");
      }
    }
    // dünkü siparişler sabah gelir (park yeri yoksa bekler)
    const due = d.orders.filter(o => o.day < s.day);
    for (const o of due) {
      if (G.spaceLeft() <= 0) break;
      const car = G.newCarFor(G.M[o.m]);
      car.buy = o.cost; car.spent = 0;
      s.cars.push(car);
      d.orders = d.orders.filter(x => x !== o);
      G.feed(`Sıfır ${G.carName(car)} galeriye teslim edildi. Fiyat etiketini as.`, "buy");
    }
  };
  G.dealerFee = () => G.DEALER.tiers.reduce((a, t) => a + t.brands.filter(G.licensed).length * t.daily, 0);

  // Fabrikadan çıkmış araç: bu yılın modeli, birkaç km, kusursuz, ekspertizi temiz
  G.newCarFor = function (md) {
    const car = G.makeCar(md, G.SELLERS.galeriden, { year: G.YEAR, noIssues: true });
    car.year = G.YEAR; car.km = R.randi(5, 60);
    car.motor = 100; car.body = 100; car.inter = 100; car.clean = 100;
    car.painted = 0; car.replaced = 0; car.tramer = 0; car.issues = []; car.inspected = true;
    car.zero = true;
    return car;
  };
  G.dealerOrder = function (modelId) {
    const md = G.M[modelId], s = S();
    if (!md || !G.licensed(md.brand)) return { ok: false };
    if (!G.segUnlocked(md.seg)) return { ok: false, msg: `${G.SEGMENTS[md.seg].name} sınıfı seviye ${G.SEGMENTS[md.seg].lvl}'de açılır.` };
    const cost = G.dealerCost(md);
    if (G.spaceLeft() - D().orders.length <= 0) return { ok: false, msg: "Sipariş için boş park yeri yok." };
    if (s.money < cost) return { ok: false, msg: "Kasada yeterli para yok." };
    s.money -= cost;
    s.today.spentBuy = (s.today.spentBuy || 0) + cost;
    D().orders.push({ m: modelId, cost, day: s.day });
    G.feed(`${G.carName({ m: modelId })} siparişi verildi (${G.fmt(cost)}). Yarın sabah gelecek.`, "info");
    return { ok: true };
  };

  // ---- Ekranlar ----
  const tick = ok => `<span class="${ok ? "pos" : "neg"}" aria-hidden="true">${ok ? "✓" : "✗"}</span>`;
  UI.dealerView = function () {
    const s = S(), d = D(), gl = G.facLevel("galeri");
    let html = `<div class="card"><h3>Sıfır araç bayiliği</h3><p class="muted small">Sıfır aracı %${Math.round((1 - G.DEALER.cost) * 100)} ucuza al, liste fiyatından sat. Denetim günü araçlar temiz olsun.</p></div>`;
    if (gl < G.DEALER.unlock) return html + `<div class="card"><p class="small">Bayilik başvuruları galeri binası <b>${G.DEALER.unlock}. seviyeye</b> gelince açılır. Şu an ${gl}. seviye.</p></div>`;
    for (const t of G.DEALER.tiers) {
      html += `<h3 class="dl-tier">${esc(t.name)} <span class="muted small">Başvuru ${G.fmtS(t.fee)} · lisans başına günlük aidat ${G.fmtS(t.daily)}</span></h3>`;
      for (const brand of t.brands) {
        const l = d.lic[brand], models = G.dealerModels(brand);
        const pic = models.find(m => G.PHOTO_COLOR[m.id]);
        let body;
        if (l && l.state === "ok") body = `<span class="chip good">Lisanslı</span> <button class="btn sm pri" type="button" data-a="dealer-shop">Sıfır araç sipariş et</button>`;
        else if (l && G.DEALER.steps.some(x => x.id === l.state)) {
          const i = G.DEALER.steps.findIndex(x => x.id === l.state);
          body = `<div class="dl-steps">${G.DEALER.steps.map((x, k) => `<span class="${k < i ? "done" : k === i ? "now" : ""}">${esc(x.name)}</span>`).join("")}<span>Karar</span></div><p class="small muted">${esc(G.DEALER.steps[i].name)}: ${Math.max(0, l.until - s.day)} gün kaldı.</p>`;
        } else {
          const checks = G.dealerChecks(brand), ok = checks.every(c => c.ok);
          const wait = l && l.state === "red" && s.day < l.retry;
          body = `${l && l.state === "red" ? `<p class="small neg">Son başvuru reddedildi: ${esc(l.why)}.${wait ? ` ${l.retry - s.day} gün sonra yeniden başvurabilirsin.` : ""}</p>` : ""}
            <ul class="dl-req">${checks.map(c => `<li>${tick(c.ok)} ${esc(c.text)} <span class="muted">(${esc(c.now)})</span></li>`).join("")}</ul>
            <button class="btn sm ${ok && !wait ? "pri" : ""}" type="button" data-a="dealer-apply" data-v="${esc(brand)}" ${ok && !wait ? "" : "disabled"}>Başvur · ${G.fmtS(t.fee)}</button>`;
        }
        html += `<div class="card dl-brand"><div class="row"><div class="who">${pic ? `<span class="dl-pic">${UI.carSVG({ m: pic.id, color: G.PHOTO_COLOR[pic.id], clean: 100 })}</span>` : ""}<div><b>${esc(brand)}</b><p class="small muted">${models.length} model: ${esc(models.map(m => m.name).join(", "))}</p></div></div></div>${body}</div>`;
      }
    }
    return html;
  };

  UI.dealerShop = function () {
    const s = S(), d = D();
    const brands = G.DEALER.tiers.flatMap(t => t.brands).filter(G.licensed);
    let html = `<div class="card"><h3>Sıfır araç siparişi</h3><p class="muted small">Bayi fiyatına öde, araç yarın sabah galeriye gelsin. Boş park yeri: ${Math.max(0, G.spaceLeft() - d.orders.length)}.${d.orders.length ? ` Yoldaki sipariş: ${d.orders.length}.` : ""}</p></div>`;
    if (!brands.length) return html + `<div class="card"><p class="small">Henüz bayilik lisansın yok. Patron → Bayilik'ten başvurabilirsin.</p><button class="btn sm" type="button" data-a="dealer-go">Bayiliğe git</button></div>`;
    if (d.orders.length) html += `<div class="card"><h3>Yolda</h3>${d.orders.map(o => `<div class="row small"><div>${esc(G.carName({ m: o.m }))}</div><div class="muted">yarın sabah · ${G.fmtS(o.cost)}</div></div>`).join("")}</div>`;
    html += `<div class="list">${brands.flatMap(b => G.dealerModels(b)).map(md => {
      const cost = G.dealerCost(md), open = G.segUnlocked(md.seg);
      const sample = { m: md.id, color: G.PHOTO_COLOR[md.id] || "Beyaz", clean: 100 };
      return `<article class="crow"><div class="crow-img">${UI.carSVG(sample)}</div>
        <div class="crow-main"><b>${esc(md.brand)} ${esc(md.name)}</b><span>${G.YEAR} · sıfır · ${esc(G.SEGMENTS[md.seg].name)}</span><span class="small muted">Liste ${G.fmtS(md.price)} · kâr ≈ ${G.fmtS(md.price - cost)}</span></div>
        <div class="crow-side"><span class="sticker sm">${G.fmtS(cost)}</span>${open ? `<button class="btn sm pri" type="button" data-a="dealer-order" data-v="${md.id}" ${s.money >= cost ? "" : "disabled"}>Sipariş</button>` : `<span class="chip">Seviye ${G.SEGMENTS[md.seg].lvl}</span>`}</div></article>`;
    }).join("")}</div>`;
    return html;
  };
})(window.G);
