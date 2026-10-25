/* Galeri Patronu — arayüz */
(function (G) {
  "use strict";
  const R = G.R;
  const UI = G.UI = { tab: "galeri", sub: { piyasa: "ilan", patron: "durum" }, sheet: null, draft: {}, sort: "yeni", dragging: false, sig: "", sheetSig: "", armed: null };
  const $ = id => document.getElementById(id);
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));
  const S = () => G.S;

  // ---- Araç çizimi (yandan görünüş, ön taraf sağda) ----
  const SHAPES = {
    sedan: { d: "M6 36 L8 28 Q10 25 18 24 L34 23 L44 14 Q47 12 52 12 L76 12 Q81 12 85 16 L93 23 L106 25 Q114 26 114 31 L114 36 Q114 38 112 38 L100 38 A10 10 0 0 0 80 38 L40 38 A10 10 0 0 0 20 38 L8 38 Q6 38 6 36 Z",
      win: ["M46 15.5 Q48 14 52 14 L62 14 L62 23 L37 23 Z", "M65 14 L76 14 Q80 14 83 17 L89 23 L65 23 Z"], tail: 6 },
    hatch: { d: "M14 36 L13 22 Q14 14 22 13 L66 13 Q72 13 76 17 L86 24 L102 26 Q110 27 110 32 L110 36 Q110 38 108 38 L100 38 A10 10 0 0 0 80 38 L40 38 A10 10 0 0 0 20 38 L16 38 Q14 38 14 36 Z",
      win: ["M18 16 Q18 15.5 22 15.5 L44 15.5 L44 23 L16.5 23 Z", "M47 15.5 L65 15.5 Q70 15.5 73 18.5 L79 23 L47 23 Z"], tail: 13, head: 104 },
    wagon: { d: "M6 36 L6 21 Q6 14 14 13 L72 13 Q78 13 82 17 L91 24 L106 26 Q114 27 114 32 L114 36 Q114 38 112 38 L100 38 A10 10 0 0 0 80 38 L40 38 A10 10 0 0 0 20 38 L8 38 Q6 38 6 36 Z",
      win: ["M10 16 L36 16 L36 23 L9 23 Z", "M39 16 L58 16 L58 23 L39 23 Z", "M61 16 L71 16 Q76 16 79 19 L83 23 L61 23 Z"], tail: 6 },
    suv: { d: "M6 36 L6 18 Q6 9 14 8 L74 8 Q80 8 84 12 L93 21 L106 23 Q114 24 114 30 L114 36 Q114 38 112 38 L101 38 A11 11 0 0 0 79 38 L41 38 A11 11 0 0 0 19 38 L8 38 Q6 38 6 36 Z",
      win: ["M10 11 L38 11 L38 20 L9 20 Z", "M41 11 L60 11 L60 20 L41 20 Z", "M63 11 L73 11 Q78 11 81 14 L87 20 L63 20 Z"], tail: 6, r: 9 },
    coupe: { d: "M8 36 L9 30 Q11 27 22 26 L40 25 L52 16 Q56 14 62 14 L70 14 Q77 14 84 19 L92 25 L108 28 Q114 29 114 33 L114 36 Q114 38 112 38 L100 38 A10 10 0 0 0 80 38 L40 38 A10 10 0 0 0 20 38 L10 38 Q8 38 8 36 Z",
      win: ["M50 18 Q56 16 62 16 L64 16 L64 25 L44 25 Z", "M67 16 L70 16 Q76 16 81 20 L86 25 L67 25 Z"], tail: 8 },
    van: { d: "M6 36 L6 10 Q6 6 10 6 L80 6 Q85 6 88 10 L97 21 L108 23 Q114 24 114 30 L114 36 Q114 38 112 38 L100 38 A10 10 0 0 0 80 38 L40 38 A10 10 0 0 0 20 38 L8 38 Q6 38 6 36 Z",
      win: ["M50 9 L66 9 L66 20 L50 20 Z", "M70 9 L80 9 Q84 9 86 12 L92 20 L70 20 Z"], tail: 6 },
    pickup: { d: "M6 36 L6 22 L50 22 L50 11 Q50 8 54 8 L76 8 Q81 8 84 12 L93 21 L106 23 Q114 24 114 30 L114 36 Q114 38 112 38 L100 38 A10 10 0 0 0 80 38 L40 38 A10 10 0 0 0 20 38 L8 38 Q6 38 6 36 Z",
      win: ["M54 11 L64 11 L64 20 L53 20 Z", "M67 11 L76 11 Q80 11 83 14 L88 20 L67 20 Z"], tail: 6 }
  };
  function shade(hex, f) {
    const n = parseInt(hex.slice(1), 16);
    const ch = s => R.clamp(Math.round(((n >> s) & 255) * f), 0, 255);
    return `rgb(${ch(16)},${ch(8)},${ch(0)})`;
  }
  UI.carSVG = function (car, opt) {
    const md = G.M[car.m];
    const sh = SHAPES[md.body] || SHAPES.sedan;
    const col = (G.COLORS.find(c => c.name === car.color) || G.COLORS[0]).hex;
    const r = sh.r || 8;
    const dirty = car.clean < 45 ? `<path d="${sh.d}" fill="#6b5a3c" opacity="${((45 - car.clean) / 45 * 0.35).toFixed(2)}"/>` : "";
    const wheel = x => `<circle cx="${x}" cy="38" r="${r}" fill="#1b1f24"/><circle cx="${x}" cy="38" r="${(r * 0.45).toFixed(1)}" fill="#a3acb6"/>`;
    return `<svg viewBox="0 0 120 48" role="img" aria-label="${esc(car.color)} ${esc(G.carName(car))}">
      <ellipse cx="60" cy="46" rx="54" ry="2.2" fill="#000" opacity=".18"/>
      <path d="${sh.d}" fill="${col}" stroke="${shade(col, 0.6)}" stroke-width="1"/>${dirty}
      ${sh.win.map(w => `<path d="${w}" fill="#2a3644" opacity=".88"/>`).join("")}
      <rect x="${(sh.head || 108) - 1}" y="27" width="6" height="3" rx="1.5" fill="#ffe7a3"/>
      <rect x="${sh.tail}" y="27" width="3.5" height="3" rx="1" fill="#d23a35"/>
      ${wheel(30)}${wheel(90)}
      ${opt && opt.sold ? "" : ""}
    </svg>`;
  };

  // ---- Küçük parçalar ----
  const plateHTML = p => `<span class="plate"><i>TR</i><span>${esc(p)}</span></span>`;
  const barCls = v => (v >= 75 ? "" : v >= 50 ? "warn" : "bad");
  function bars(car, all) {
    const p = G.parts(car, all);
    const one = (l, v) => `<div><div class="bar-l"><span>${l}</span><b>${Math.round(v)}</b></div><div class="bar"><i class="${barCls(v)}" style="width:${v}%"></i></div></div>`;
    return `<div class="bars">${one("Motor", p.m)}${one("Kaporta", p.b)}${one("İç", p.i)}</div>`;
  }
  function paintChips(car) {
    const out = [];
    if (!car.painted && !car.replaced) out.push(`<span class="chip good">Boyasız, değişensiz</span>`);
    else {
      if (car.painted) out.push(`<span class="chip warn">${car.painted} boyalı</span>`);
      if (car.replaced) out.push(`<span class="chip bad">${car.replaced} değişen</span>`);
    }
    out.push(car.tramer ? `<span class="chip">Tramer ${G.fmtS(car.tramer)}</span>` : `<span class="chip good">Tramersiz</span>`);
    return out.join("");
  }
  function issueChips(car) {
    const known = car.issues.filter(is => is.known && !is.fixed);
    if (!car.inspected) return `<span class="chip warn">Ekspertizsiz</span>`;
    if (!known.length) return `<span class="chip good">Ekspertiz temiz</span>`;
    return known.map(is => `<span class="chip bad">${esc(G.ISSUES[is.id].name)}</span>`).join("");
  }
  const spec = car => `${car.year} · ${G.fmtKm(G.realKm(car, false))} · ${G.FUELS[car.fuel].name} · ${G.GEARS[car.gear].name}`;
  const pct = (a, b) => (b ? Math.round((a / b - 1) * 100) : 0);
  const signed = x => (x > 0 ? "+" : x < 0 ? "−" : "") + G.fmt(Math.abs(x));

  // ---- İkonlar ----
  const IC = {
    galeri: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 15l1.6-4.6A2 2 0 0 1 6.5 9h11a2 2 0 0 1 1.9 1.4L21 15v3a1 1 0 0 1-1 1h-1.5M3 15v3a1 1 0 0 0 1 1h1.5M3 15h18"/><circle cx="7.5" cy="18.5" r="1.8"/><circle cx="16.5" cy="18.5" r="1.8"/><path d="M9.3 18.5h5.4"/></svg>',
    piyasa: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="8" cy="8" r="1.6"/></svg>',
    musteri: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c.6-3.4 3-5.4 6-5.4s5.4 2 6 5.4"/><path d="M16 4.6a3.2 3.2 0 0 1 0 6.4M18 14.8c1.7.7 2.7 2.4 3 5.2"/></svg>',
    atolye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.1L3.6 17.1a1.9 1.9 0 0 0 2.7 2.7l5.7-5.7a4 4 0 0 0 5.1-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/></svg>',
    patron: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-5h6v5M9 11h.01M15 11h.01"/></svg>'
  };
  const TABS = [["galeri", "Galeri"], ["piyasa", "Piyasa"], ["musteri", "Müşteri"], ["atolye", "Atölye"], ["patron", "Patron"]];

  // ---- Bildirimler ----
  UI.toast = function (text, kind, action) {
    const box = $("toasts");
    const el = document.createElement(action ? "button" : "div");
    el.className = "toast " + (kind || "");
    el.textContent = text;
    if (action) { el.type = "button"; Object.keys(action).forEach(k => { el.dataset[k] = action[k]; }); }
    box.appendChild(el);
    while (box.children.length > 3) box.firstChild.remove();
    setTimeout(() => el.remove(), action ? 5200 : 3600);
  };
  G.onFeed = (text, kind) => {
    $("tickerT").textContent = text;
    if (["good", "bad", "level", "news"].includes(kind)) UI.toast(text, kind);
  };
  G.onOffer = (c, car) => {
    if (UI.sheet && UI.sheet.kind === "cust" && UI.sheet.id === c.id) return;
    UI.toast(`Yeni teklif: ${c.name}, ${G.carName(car)} için ${G.fmtS(c.offer)}`, "offer", { a: "cust", id: c.id });
  };

  // ---- Üst panel ----
  function renderTop() {
    const s = S();
    $("gName").textContent = s.name;
    $("dayL").textContent = `Gün ${s.day}`;
    $("timeL").textContent = G.clock(s.min);
    $("dayBar").style.width = ((s.min - G.OPEN) / (G.CLOSE - G.OPEN) * 100).toFixed(1) + "%";
    const km = $("kMoney");
    km.textContent = G.fmt(s.money);
    km.classList.toggle("neg", s.money < 0);
    $("kRep").textContent = Math.round(s.rep) + "/100";
    $("kRepBar").style.width = s.rep + "%";
    $("kLvlL").textContent = `Seviye ${s.level}`;
    $("kXp").textContent = `${Math.floor(s.xp)}/${G.xpNeed(s.level)} XP`;
    $("kXpBar").style.width = (s.xp / G.xpNeed(s.level) * 100) + "%";
    $("kPark").textContent = `${G.carCount()}/${G.capacity()}`;
    const sp = $("speed");
    const want = s.paused ? "0" : String(s.speed);
    if (sp.dataset.v !== want) {
      sp.dataset.v = want;
      sp.innerHTML = [["0", "❚❚", "Durdur"], ["1", "1×", "Normal hız"], ["2", "2×", "İki kat hız"], ["4", "4×", "Dört kat hız"]]
        .map(([v, l, a]) => `<button type="button" data-a="speed" data-v="${v}" aria-label="${a}" aria-pressed="${want === v}">${l}</button>`).join("");
    }
  }

  function renderNav() {
    const offers = S().custs.filter(c => c.state === "offer").length;
    const jobs = S().jobs.length;
    $("nav").innerHTML = TABS.map(([id, name]) => {
      let b = "";
      if (id === "musteri" && offers) b = `<span class="badge">${offers}</span>`;
      if (id === "atolye" && jobs) b = `<span class="badge" style="background:var(--accent)">${jobs}</span>`;
      return `<button type="button" data-a="tab" data-v="${id}" ${UI.tab === id ? 'aria-current="page"' : ""}>${IC[id]}<span>${name}</span>${b}</button>`;
    }).join("");
  }

  // ---- Sekmeler ----
  const VIEWS = {};

  VIEWS.galeri = function () {
    const s = S();
    const cars = s.cars.slice();
    const sorters = {
      yeni: (a, b) => b.id - a.id,
      kar: (a, b) => (G.value(b, false) - b.buy - b.spent) - (G.value(a, false) - a.buy - a.spent),
      bekleyen: (a, b) => b.days - a.days,
      fiyat: (a, b) => G.value(b, false) - G.value(a, false)
    };
    cars.sort(sorters[UI.sort] || sorters.yeni);
    const listed = s.cars.filter(c => c.price > 0).length;
    let html = `<div class="vh"><div><h2>Galeri</h2><p>${esc(G.loc().name)}</p></div>
      <div class="strip"><span class="pill">Park <b>${G.carCount()}/${G.capacity()}</b></span>
      <span class="pill">Vitrin <b>${G.showUsed()}/${G.showSlots()}</b></span>
      <span class="pill">Etiketli <b>${listed}</b></span></div></div>`;
    if (!cars.length) {
      return html + `<div class="empty"><b>Galeride araç yok</b>Piyasa sekmesinden ilanlara bak ya da günün ihalesine katıl.<div style="margin-top:12px"><button class="btn pri" type="button" data-a="tab" data-v="piyasa">Piyasaya git</button></div></div>`;
    }
    html += `<div class="segctl" role="group" aria-label="Sıralama">${[["yeni", "Yeni gelen"], ["kar", "Potansiyel kâr"], ["bekleyen", "En çok bekleyen"], ["fiyat", "Değer"]]
      .map(([k, l]) => `<button type="button" data-a="sort" data-v="${k}" aria-pressed="${UI.sort === k}">${l}</button>`).join("")}</div>`;
    if (!listed) html += `<div class="card"><p><b>Fiyat etiketi olmayan araca müşteri gelmez.</b> <span class="muted">Bir araca dokun, fiyatını belirle ve etiketi as.</span></p></div>`;
    html += `<div class="grid">${cars.map(carCard).join("")}</div>`;
    return html;
  };

  function carCard(car) {
    const s = S();
    const mv = G.value(car, false);
    const cost = car.buy + car.spent;
    const job = car.status === "shop" ? s.jobs.find(j => j.carId === car.id) : null;
    const cust = s.custs.find(c => c.carId === car.id && c.state === "offer");
    const stat = [];
    if (car.show) stat.push(`<span class="chip acc">Vitrinde</span>`);
    if (job) stat.push(`<span class="chip dark">Atölyede · ${G.fmtDur(job.left)}</span>`);
    if (cust) stat.push(`<span class="chip dark">Pazarlıkta</span>`);
    if (car.clean < 40) stat.push(`<span class="chip dark">Kirli</span>`);
    return `<article class="ccard" role="button" tabindex="0" data-a="car" data-id="${car.id}">
      <div class="cimg">${UI.carSVG(car)}
        <span class="sticker ${car.price ? "" : "none"}">${car.price ? G.fmtS(car.price) : "Fiyat yok"}</span>
        <div class="cstat">${stat.join("")}</div></div>
      <div class="cbody">
        <div class="ctitle"><b>${esc(G.carName(car))}</b>${plateHTML(car.plate)}</div>
        <div class="cspec">${spec(car)}</div>
        <div class="chips">${paintChips(car)}${issueChips(car)}</div>
        ${bars(car, false)}
        <div class="cfoot"><span>Piyasa <b>${G.fmtS(mv)}</b></span><span>Maliyet <b>${G.fmtS(cost)}</b></span><span>${car.days} gün</span></div>
      </div></article>`;
  }

  VIEWS.piyasa = function () {
    const s = S(), sub = UI.sub.piyasa;
    let html = `<div class="vh"><div><h2>Piyasa</h2><p>Boş park yeri: ${Math.max(0, G.spaceLeft())} · Kasa: ${G.fmt(s.money)}</p></div></div>
      <div class="segctl" role="group">${[["ilan", "İlanlar"], ["ihale", "Açık artırma"], ["trend", "Trendler"]]
        .map(([k, l]) => `<button type="button" data-a="sub" data-k="piyasa" data-v="${k}" aria-pressed="${sub === k}">${l}</button>`).join("")}</div>`;
    if (sub === "ilan") {
      const ls = s.listings.filter(l => !l.done);
      if (!ls.length) return html + `<div class="empty"><b>Bugünkü ilanların hepsine baktın</b>Yarın sabah yeni ilanlar düşer.</div>`;
      html += `<p class="muted small" style="margin:0">İlandaki durum satıcının beyanıdır. Ekspertiz yaptırmadan aldığın aracın gizli kusuru çıkabilir.</p>`;
      html += `<div class="grid">${ls.map(listingCard).join("")}</div>`;
    } else if (sub === "ihale") {
      const a = s.auction;
      html += `<div class="card"><div class="row"><div><h3>Günün ihalesi</h3><p class="muted small">Banka, sigorta ve filo araçları. İhaleye ekspertiz sokulamaz; ${s.upg.expert ? "ekspertiz cihazınla hızlı kontrol yapabilirsin." : "ekspertiz cihazı alırsan kontrol edebilirsin."}</p></div></div>
        ${a.lots.map((lot, i) => `<div class="opt"><div><b>${esc(G.carTitle(lot.car))}</b><small>${esc(G.aucSrc(lot.src))} · ${G.fmtKm(lot.car.km)} · açılış ${G.fmtS(lot.start)}</small></div>
          <div class="small">${lot.done ? (lot.winner === "Sen" ? `<span class="pos">Senin · ${G.fmtS(lot.price)}</span>` : `<span class="muted">${esc(lot.winner || "Satılmadı")}${lot.winner ? " · " + G.fmtS(lot.price) : ""}</span>`) : (i === a.idx && a.live ? "<b>Şimdi</b>" : "Sırada")}</div></div>`).join("")}
        <button class="btn pri block" type="button" data-a="auction" ${a.finished ? "disabled" : ""}>${a.finished ? "Bugünkü ihale bitti" : a.idx > 0 || a.live ? "İhale salonuna dön" : "İhale salonuna gir"}</button></div>`;
    } else {
      const segs = Object.keys(G.SEGMENTS);
      html += `<div class="card"><h3>Segment fiyatları</h3><div class="list">${segs.map(k => {
        const t = G.trendOf(k), p = Math.round((t - 1) * 100);
        const open = G.segUnlocked(k);
        return `<div class="row"><div><b>${G.SEGMENTS[k].name}</b>${open ? "" : ` <span class="chip">Seviye ${G.SEGMENTS[k].lvl}</span>`}</div><div class="num ${p > 0 ? "pos" : p < 0 ? "neg" : "muted"}">${p > 0 ? "▲ +" : p < 0 ? "▼ " : ""}${p}%</div></div>`;
      }).join("")}</div></div>`;
      html += `<div class="card"><h3>Gündem</h3>${s.events.length ? s.events.map(e => { const d = G.EVENTS.find(x => x.id === e.id); return `<div><b>${esc(d.title)}</b><p class="muted small">${esc(d.text)} · ${e.until - s.day + 1} gün daha</p></div>`; }).join("") : `<p class="muted small">Piyasada olağan bir hafta. Haberler gün sonunda gelir.</p>`}</div>`;
    }
    return html;
  };

  function listingCard(l) {
    const car = l.car;
    const ap = G.value(car, false);
    return `<article class="ccard" role="button" tabindex="0" data-a="listing" data-id="${l.id}">
      <div class="cimg">${UI.carSVG(car)}<span class="sticker">${G.fmtS(l.last)}</span>
        <div class="cstat"><span class="chip dark">${esc(G.SELLERS[l.seller].name)}</span>${car.inspected ? `<span class="chip acc">Ekspertizli</span>` : ""}</div></div>
      <div class="cbody">
        <div class="ctitle"><b>${esc(G.carName(car))}</b><span class="chip">${G.SEGMENTS[G.M[car.m].seg].name}</span></div>
        <div class="cspec">${spec(car)} · ${car.color}</div>
        <div class="chips">${paintChips(car)}${car.inspected ? issueChips(car) : ""}</div>
        ${bars(car, false)}
        <div class="cfoot"><span>Tahmini piyasa <b>${G.fmtS(ap)}</b></span><span>${l.expires === S().day ? "Bugün kalkıyor" : `${l.expires - S().day + 1} gün`}</span></div>
      </div></article>`;
  }

  VIEWS.musteri = function () {
    const s = S();
    const offers = s.custs.filter(c => c.state === "offer");
    const browsing = s.custs.filter(c => c.state === "browse");
    let html = `<div class="vh"><div><h2>Müşteriler</h2><p>Bugün ${s.today.visitors} ziyaretçi · ${s.today.sales} satış</p></div></div>`;
    if (!offers.length && !browsing.length) {
      html += `<div class="empty"><b>Şu an galeride müşteri yok</b>${s.min >= G.CLOSE ? "Galeri kapalı." : "Etiketli araçlar, temizlik, vitrin, reklam ve itibar müşteri getirir."}</div>`;
    }
    if (offers.length) {
      html += `<div class="list">${offers.map(c => {
        const car = G.custCar(c);
        if (!car) return "";
        const profit = c.offer - car.buy - car.spent;
        return `<div class="card"><div class="row"><div><b>${esc(c.name)}</b> <span class="muted small">${esc(G.CT[c.type].name)}</span><p class="small muted">${esc(G.carTitle(car))} · etiket ${G.fmtS(car.price)}</p></div>
          <div style="text-align:right"><div class="num" style="font-weight:700;font-size:18px">${G.fmtS(c.offer)}</div><div class="small ${profit >= 0 ? "pos" : "neg"}">${profit >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(profit))}</div></div></div>
          ${c.trade ? `<p class="small">Takas teklifi: <b>${esc(G.carTitle(c.trade))}</b> (${G.fmtS(c.tradeVal)})</p>` : ""}
          <div class="prog pat"><i data-pat="${c.id}" style="width:${c.patience / c.patMax * 100}%"></i></div>
          <div class="btns"><button class="btn pri sm" type="button" data-a="accept" data-id="${c.id}">Kabul et</button><button class="btn sm" type="button" data-a="cust" data-id="${c.id}">Pazarlık</button><button class="btn sm danger" type="button" data-a="reject" data-id="${c.id}">Reddet</button></div></div>`;
      }).join("")}</div>`;
    }
    if (browsing.length) {
      html += `<div class="card"><h3>Araçlara bakıyor</h3>${browsing.map(c => `<div class="row small"><div><b>${esc(c.name)}</b> · ${esc(G.CT[c.type].name)}</div><div class="muted">${G.SEGMENTS[c.seg].name} arıyor</div></div>`).join("")}</div>`;
    }
    if (s.lost.length) {
      html += `<div class="card"><h3>Bugün kaçanlar</h3><p class="muted small">Ne aradıklarına bakıp stoğunu ona göre kur.</p>${s.lost.map(l => `<div class="row small"><div><b>${esc(l.name)}</b> · ${esc(l.type)}</div><div class="muted" style="text-align:right">${esc(l.text)}</div></div>`).join("")}</div>`;
    }
    if (G.staffOf("satis").length) html += `<p class="muted small">Satış danışmanın, 15 dakika içinde cevap vermediğin teklifleri en fazla %${Math.round(s.maxDisc * 100)} indirimle kendisi bağlar.</p>`;
    return html;
  };

  VIEWS.atolye = function () {
    const s = S();
    const bayUsed = s.jobs.filter(j => j.inhouse && j.where === "bay").length;
    let html = `<div class="vh"><div><h2>Atölye</h2><p>${s.upg.bays ? `Bölmeler ${bayUsed}/${s.upg.bays} dolu` : "Kendi atölyen yok; işler dış servise gider"} · Usta: ${G.staffOf("usta").length}</p></div></div>`;
    if (!s.jobs.length) html += `<div class="empty"><b>Atölyede iş yok</b>Galeride bir araca dokun ve temizlik, bakım ya da boya işi başlat. Bakımlı araç daha pahalı satılır.</div>`;
    else html += `<div class="list">${s.jobs.map(j => {
      const car = s.cars.find(c => c.id === j.carId);
      return `<div class="card job"><div class="row"><div><b>${esc(j.name)}</b><p class="small muted">${car ? esc(G.carTitle(car)) : ""} · ${j.inhouse ? "Kendi atölyen" : "Dış servis"}</p></div><div class="num small" data-jobt="${j.id}">${G.fmtDur(j.left)}</div></div>
        <div class="prog"><i data-job="${j.id}" style="width:${(1 - j.left / j.total) * 100}%"></i></div></div>`;
    }).join("")}</div>`;
    html += `<div class="card"><h3>Atölye düzeni</h3><div class="kv">
      <div><span>Bölme</span><b>${s.upg.bays}</b></div><div><span>Yıkama</span><b>${s.upg.wash ? "Var" : "Yok"}</b></div>
      <div><span>Boya kabini</span><b>${s.upg.paint ? "Var" : "Yok"}</b></div><div><span>Göçük seti</span><b>${s.upg.pdr ? "Var" : "Yok"}</b></div>
      <div><span>İş hızı</span><b>${s.upg.bays ? "×" + (1 / G.jobTimeMult(true)).toFixed(2).replace(".", ",") : "—"}</b></div></div>
      <p class="muted small">Atölye çalışma saatlerinde (09:00–19:00) iş ilerler. Usta olmadan kendi atölyende işler %40 yavaş gider.</p></div>`;
    return html;
  };

  VIEWS.patron = function () {
    const s = S(), sub = UI.sub.patron;
    let html = `<div class="vh"><div><h2>Patron masası</h2><p>${esc(G.loc().name)} · Seviye ${s.level}</p></div></div>
      <div class="segctl" role="group">${[["durum", "Durum"], ["dukkan", "Dükkân"], ["personel", "Personel"], ["finans", "Finans"]]
        .map(([k, l]) => `<button type="button" data-a="sub" data-k="patron" data-v="${k}" aria-pressed="${sub === k}">${l}</button>`).join("")}</div>`;
    if (sub === "durum") html += patronDurum();
    if (sub === "dukkan") html += patronDukkan();
    if (sub === "personel") html += patronPersonel();
    if (sub === "finans") html += patronFinans();
    return html;
  };

  function chart(hist) {
    const h = hist.slice(-14);
    if (!h.length) return `<p class="muted small">İlk gün bitince burada günlük kâr-zarar grafiği görünür.</p>`;
    const W = 320, H = 120, pad = 18, bw = (W - pad) / 14;
    const mx = Math.max(1, ...h.map(d => Math.abs(d.net)));
    const y0 = H / 2;
    const bars = h.map((d, i) => {
      const hh = Math.abs(d.net) / mx * (H / 2 - 12);
      const y = d.net >= 0 ? y0 - hh : y0;
      return `<rect x="${pad + i * bw + 2}" y="${y.toFixed(1)}" width="${(bw - 4).toFixed(1)}" height="${Math.max(1, hh).toFixed(1)}" rx="2" fill="${d.net >= 0 ? "var(--good)" : "var(--bad)"}"/>
        <text x="${(pad + i * bw + bw / 2).toFixed(1)}" y="${H - 1}" text-anchor="middle">${d.day}</text>`;
    }).join("");
    return `<svg class="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Son günlerin net kâr-zarar grafiği">
      <line x1="${pad}" x2="${W}" y1="${y0}" y2="${y0}" stroke="var(--line)"/>
      <text x="0" y="12">+${G.fmtS(mx).replace(" ₺", "")}</text><text x="0" y="${H - 14}">−${G.fmtS(mx).replace(" ₺", "")}</text>${bars}</svg>`;
  }

  function patronDurum() {
    const s = S(), T = s.today;
    const qs = G.activeQuests(3);
    return `<div class="card"><h3>Bugün</h3><div class="kv">
        <div><span>Satış</span><b>${T.sales}</b></div><div><span>Ciro</span><b>${G.fmtS(T.revenue)}</b></div>
        <div><span>Satış kârı</span><b class="${T.profit >= 0 ? "pos" : "neg"}">${G.fmtS(T.profit)}</b></div><div><span>Atölye ve ekspertiz</span><b>${G.fmtS(T.spentWork)}</b></div>
        <div><span>Araç alımı</span><b>${G.fmtS(T.spentBuy)}</b></div><div><span>Ziyaretçi</span><b>${T.visitors}</b></div></div></div>
      <div class="card"><h3>Son 14 gün net</h3>${chart(s.history)}</div>
      <div class="card"><h3>Görevler</h3>${qs.length ? qs.map(q => `<div class="row"><div>${esc(q.text)}</div><div class="num small pos">+${G.fmtS(q.reward)}</div></div>`).join("") : `<p class="muted">Bütün görevleri bitirdin. Gerçek bir patronsun.</p>`}
        <p class="muted small">${s.quests.length}/${G.QUESTS.length} görev tamamlandı</p></div>
      <div class="card"><h3>Galeri karnesi</h3><div class="kv">
        <div><span>Net değer</span><b>${G.fmtS(G.netWorth())}</b></div><div><span>Toplam satış</span><b>${s.stats.sold}</b></div>
        <div><span>Toplam kâr</span><b class="${s.stats.profit >= 0 ? "pos" : "neg"}">${G.fmtS(s.stats.profit)}</b></div><div><span>En iyi satış kârı</span><b>${G.fmtS(s.stats.best)}</b></div>
        <div><span>İhaleden alınan</span><b>${s.stats.auctions}</b></div><div><span>Ekspertiz</span><b>${s.stats.inspected}</b></div></div></div>`;
  }

  function patronDukkan() {
    const s = S(), L = G.loc(), N = G.LOCS[s.loc + 1];
    let html = `<div class="card"><h3>${esc(L.name)}</h3><p class="muted small">${esc(L.desc)}</p><div class="kv">
      <div><span>Kapasite</span><b>${L.cap} araç</b></div><div><span>Personel</span><b>${s.staff.length}/${L.staff}</b></div>
      <div><span>Günlük kira</span><b>${G.fmt(L.rent)}</b></div><div><span>Trafik</span><b>×${String(L.traffic).replace(".", ",")}</b></div></div></div>`;
    if (N) {
      const ok = s.level >= N.lvl && s.money >= N.cost;
      html += `<div class="card"><div class="row"><div><h3>Sıradaki adres: ${esc(N.name)}</h3><p class="muted small">${esc(N.desc)}</p></div></div>
        <div class="kv"><div><span>Kapasite</span><b>${N.cap} araç</b></div><div><span>Personel</span><b>${N.staff}</b></div><div><span>Kira</span><b>${G.fmt(N.rent)}</b></div><div><span>Taşınma</span><b>${G.fmtS(N.cost)}</b></div></div>
        <button class="btn pri block" type="button" data-a="move" ${ok ? "" : "disabled"}>${s.level < N.lvl ? `Seviye ${N.lvl} gerekir` : s.money < N.cost ? `Kasada ${G.fmtS(N.cost)} olmalı` : "Taşın"}</button></div>`;
    }
    html += `<div class="card"><h3>Geliştirmeler</h3>${Object.keys(G.UPGRADES).map(k => {
      const u = G.UPGRADES[k], n = G.upgNext(k), lv = s.upg[k];
      const lvTxt = u.levels.length > 1 ? ` <span class="chip">${lv}/${u.levels.length}</span>` : lv ? ` <span class="chip good">Kurulu</span>` : "";
      let btn;
      if (!n) btn = `<span class="chip good">Tamam</span>`;
      else if (n.lvl > s.level) btn = `<span class="chip">Seviye ${n.lvl}</span>`;
      else btn = `<button class="btn sm ${s.money >= n.cost ? "pri" : ""}" type="button" data-a="upg" data-v="${k}" ${s.money >= n.cost ? "" : "disabled"}>${G.fmtS(n.cost)}</button>`;
      return `<div class="opt"><div><b>${esc(u.name)}</b>${lvTxt}<small>${esc(u.desc)}${n && n.slots ? ` Sonraki: ${n.slots} araçlık vitrin.` : ""}</small></div>${btn}</div>`;
    }).join("")}</div>`;
    return html;
  }

  function patronPersonel() {
    const s = S(), L = G.loc();
    let html = `<div class="card"><div class="row"><h3>Kadro</h3><span class="pill">Kadro <b>${s.staff.length}/${L.staff}</b></span></div>`;
    html += s.staff.length ? s.staff.map(st => `<div class="opt"><div><b>${esc(st.name)}</b> · ${esc(G.ROLES[st.role].name)} <span class="chip">${"★".repeat(st.skill)}${"☆".repeat(5 - st.skill)}</span><small>Günlük ${G.fmt(st.salary)}</small></div>
      <button class="btn sm danger" type="button" data-a="fire" data-id="${st.id}">${UI.armed === "fire" + st.id ? "Emin misin?" : "Çıkar"}</button></div>`).join("")
      : `<p class="muted small">Henüz çalışanın yok. Her şeyi sen yapıyorsun.</p>`;
    html += `</div>`;
    if (G.staffOf("satis").length) {
      html += `<div class="card"><h3>Danışmana verilen indirim yetkisi</h3><div class="range"><div class="range-out"><span class="muted small">Etiket fiyatından en fazla</span><b id="discOut">%${Math.round(s.maxDisc * 100)}</b></div>
        <input type="range" id="discRange" min="0" max="15" step="1" value="${Math.round(s.maxDisc * 100)}" data-in="disc" aria-label="İndirim yetkisi"></div></div>`;
    }
    html += `<div class="card"><h3>İş başvuruları</h3><p class="muted small">Başvurular 5 günde bir yenilenir.</p>${s.cands.length ? s.cands.map(c => `<div class="opt"><div><b>${esc(c.name)}</b> · ${esc(G.ROLES[c.role].name)} <span class="chip">${"★".repeat(c.skill)}${"☆".repeat(5 - c.skill)}</span><small>${esc(G.ROLES[c.role].desc)} Günlük ${G.fmt(c.salary)}.</small></div>
      <button class="btn sm pri" type="button" data-a="hire" data-id="${c.id}" ${s.staff.length >= L.staff ? "disabled" : ""}>İşe al</button></div>`).join("") : `<p class="muted small">Şu an başvuru yok.</p>`}</div>`;
    return html;
  }

  function patronFinans() {
    const s = S();
    const lim = G.loanLimit();
    let html = `<div class="card"><h3>Reklam</h3>${G.ADS.map((a, i) => `<div class="opt"><div><b>${esc(a.name)}</b><small>${a.cost ? `Günlük ${G.fmt(a.cost)} · müşteri ×${String(a.mult).replace(".", ",")}${a.rep ? " · itibar her gün artar" : ""}` : "Müşteriler yalnızca yoldan geçerken gelir."}</small></div>
      <button class="btn sm ${s.ads === i ? "pri" : ""}" type="button" data-a="ads" data-v="${i}" ${s.ads === i ? "disabled" : ""}>${s.ads === i ? "Seçili" : "Seç"}</button></div>`).join("")}</div>`;
    html += `<div class="card"><h3>Banka</h3><div class="kv"><div><span>Kredi borcu</span><b>${G.fmt(s.loan)}</b></div><div><span>Limit</span><b>${G.fmtS(lim)}</b></div><div><span>Günlük faiz</span><b>%0,15</b></div></div>
      <p class="muted small">Kasa eksiye düşerse ek hesaptan günlük %0,4 faiz işler.</p>
      <div class="btns"><button class="btn" type="button" data-a="borrow" data-v="250000" ${s.loan + 250000 > lim ? "disabled" : ""}>250 bin çek</button><button class="btn" type="button" data-a="borrow" data-v="1000000" ${s.loan + 1e6 > lim ? "disabled" : ""}>1 milyon çek</button>
      <button class="btn" type="button" data-a="repay" data-v="250000" ${s.loan <= 0 ? "disabled" : ""}>250 bin öde</button><button class="btn" type="button" data-a="repay" data-v="${s.loan}" ${s.loan <= 0 || s.money < s.loan ? "disabled" : ""}>Hepsini öde</button></div></div>`;
    html += `<div class="card"><h3>Oyun</h3><p class="muted small">Oyun bu cihazda otomatik kaydedilir.</p><div class="btns"><button class="btn danger" type="button" data-a="reset">${UI.armed === "reset" ? "Bütün ilerleme silinecek. Tekrar dokun." : "Sıfırdan başla"}</button></div></div>`;
    return html;
  }

  // ---- Sayfalar (sheet) ----
  const SHEETS = {};

  SHEETS.car = function (id) {
    const s = S(), car = s.cars.find(c => c.id === id);
    if (!car) return null;
    const md = G.M[car.m];
    const mv = G.value(car, false);
    const cost = car.buy + car.spent;
    const job = s.jobs.find(j => j.carId === car.id);
    const d = UI.draft;
    if (d.carId !== id) { d.carId = id; d.price = car.price || R.r1k(mv * 1.06); }
    const lo = R.r1k(mv * 0.6), hi = R.r1k(mv * 1.5);
    d.price = R.clamp(d.price, lo, hi);
    let html = `<div class="grip"></div><div class="sh-head"><div><h2>${esc(G.carName(car))}</h2><p>${esc(G.SEGMENTS[md.seg].name)} · ${esc(G.BODIES[md.body])} · ${esc(car.color)}</p></div><button class="x" type="button" data-a="close" aria-label="Kapat">×</button></div>
      <div class="hero">${UI.carSVG(car)}<span class="sticker" style="${car.price ? "" : "display:none"}">${G.fmtS(car.price)}</span></div>
      <div class="row">${plateHTML(car.plate)}<span class="muted small">${car.days} gündür galeride · temizlik %${Math.round(car.clean)}</span></div>
      <div class="kv"><div><span>Model yılı</span><b>${car.year}</b></div><div><span>Kilometre</span><b>${G.fmtKm(G.realKm(car, false))}</b></div>
        <div><span>Yakıt / vites</span><b>${G.FUELS[car.fuel].name} · ${G.GEARS[car.gear].name}</b></div><div><span>Piyasa değeri</span><b>${G.fmt(mv)}</b></div>
        <div><span>Alış</span><b>${G.fmt(car.buy)}</b></div><div><span>Masraf</span><b>${G.fmt(car.spent)}</b></div></div>
      <div class="chips">${paintChips(car)}${issueChips(car)}</div>
      ${bars(car, false)}`;
    if (job) {
      html += `<div class="card job"><div class="row"><b>${esc(job.name)}</b><span class="num small" data-jobt="${job.id}">${G.fmtDur(job.left)}</span></div><div class="prog"><i data-job="${job.id}" style="width:${(1 - job.left / job.total) * 100}%"></i></div><p class="muted small">Araç atölyedeyken satışa çıkmaz.</p></div>`;
    }
    // fiyat
    const margin = d.price - cost;
    html += `<div class="card"><h3>Fiyat etiketi</h3><div class="range">
      <div class="range-out"><b id="prOut">${G.fmt(d.price)}</b><span class="small" id="prInfo">${priceInfo(d.price, mv, cost)}</span></div>
      <input type="range" id="prRange" data-in="price" min="${lo}" max="${hi}" step="1000" value="${d.price}" aria-label="Etiket fiyatı">
      <div class="quick">${[0.95, 1, 1.05, 1.1, 1.2].map(f => `<button class="btn" type="button" data-a="pq" data-v="${f}">${f === 1 ? "Piyasa" : (f > 1 ? "+" : "−") + Math.round(Math.abs(f - 1) * 100) + "%"}</button>`).join("")}</div>
      <div class="btns"><button class="btn pri" type="button" data-a="price-save" data-id="${car.id}">${car.price ? "Etiketi güncelle" : "Etiketi as"}</button>${car.price ? `<button class="btn" type="button" data-a="price-clear" data-id="${car.id}">Etiketi kaldır</button>` : ""}</div>
      <p class="muted small">Piyasanın %10'undan fazla üstündeki araca müşteri zor bakar; çok ucuz araç ise hemen gider.</p></div></div>`;
    void margin;
    // ekspertiz
    if (!car.inspected) {
      html += `<div class="card"><div class="row"><div><h3>Ekspertiz</h3><p class="muted small">Gizli arızaları ortaya çıkarır. Bilinmeyen kusurla satılan araç şikâyet ve tazminat getirebilir.</p></div>
        <button class="btn warn" type="button" data-a="inspect" data-id="${car.id}">${G.fmt(G.expertCost())}</button></div></div>`;
    } else if (car.issues.length) {
      html += `<div class="card"><h3>Ekspertiz raporu</h3>${car.issues.map(is => `<div class="row small"><div>${esc(G.ISSUES[is.id].name)}${is.id === "km" ? ` · gerçek km ${G.fmtKm(is.realKm)}` : ""}</div><div>${is.fixed ? '<span class="chip good">Giderildi</span>' : G.ISSUES[is.id].fix ? '<span class="chip warn">Onarılabilir</span>' : '<span class="chip bad">Kalıcı</span>'}</div></div>`).join("")}</div>`;
    }
    // atölye
    if (car.status === "lot") {
      const opts = G.jobOptions(car);
      html += `<div class="card"><h3>Atölye işleri</h3>${opts.map((o, i) => `<div class="opt"><div><b>${esc(o.name)}</b><small>${o.inhouse ? "Kendi atölyende" : "Dış serviste"} · ${G.fmtDur(o.mins)}${o.why ? ` · <span class="neg">${esc(o.why)}</span>` : ""}${jobHint(o, car)}</small></div>
        <button class="btn sm" type="button" data-a="job" data-id="${car.id}" data-i="${i}" ${o.why ? "disabled" : ""}>${G.fmtS(o.cost)}</button></div>`).join("")}</div>`;
    }
    // vitrin, toptan
    const ws = G.wholesalePrice(car);
    html += `<div class="card"><div class="opt"><div><b>Vitrin</b><small>${G.showSlots() ? `Vitrindeki araç daha çok ilgi görür ve doludan korunur. Dolu yer: ${G.showUsed()}/${G.showSlots()}` : "Dükkân sekmesinden kapalı vitrin kurabilirsin."}</small></div>
        <button class="btn sm" type="button" data-a="show" data-id="${car.id}" ${car.status !== "lot" || (!car.show && G.showUsed() >= G.showSlots()) ? "disabled" : ""}>${car.show ? "Vitrinden al" : "Vitrine koy"}</button></div>
      <div class="opt"><div><b>Toptancıya sat</b><small>Hemen nakit: ${G.fmt(ws)} (${signed(ws - cost)})</small></div>
        <button class="btn sm danger" type="button" data-a="wholesale" data-id="${car.id}" ${car.status !== "lot" ? "disabled" : ""}>${UI.armed === "ws" + car.id ? "Onayla" : "Sat"}</button></div></div>`;
    return html;
  };

  function jobHint(o, car) {
    const map = { clean: "Temiz araç vitrinde daha iyi durur", service: "Motor +22", engine: "Motor 96'ya çıkar", paint: car && G.M[car.m].seg === "klasik" ? "Kaporta 95; klasikte boya az fark eder" : "Kaporta 95'e çıkar ama boyalı parça sayısı artar", pdr: "Kaporta +15, boya yok", interior: "İç 95'e çıkar", restore: "Her şey 95 civarına çıkar", fix: "Arıza kalıcı olarak giderilir" };
    return map[o.type] ? ` · ${map[o.type]}` : "";
  }
  function priceInfo(p, mv, cost) {
    const pm = pct(p, mv), m = p - cost;
    return `<span class="${pm > 10 ? "neg" : pm < -5 ? "pos" : "muted"}">Piyasanın ${pm >= 0 ? "%" + pm + " üstü" : "%" + -pm + " altı"}</span> · <span class="${m >= 0 ? "pos" : "neg"}">${m >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(m))}</span>`;
  }

  SHEETS.listing = function (id) {
    const s = S(), l = s.listings.find(x => x.id === id);
    if (!l) return null;
    const car = l.car, ap = G.value(car, false);
    const d = UI.draft;
    if (d.lid !== id) { d.lid = id; d.offer = R.r1k(Math.min(l.last, ap * 0.92)); }
    const lo = R.r1k(l.last * 0.6), hi = l.last;
    d.offer = R.clamp(d.offer, lo, hi);
    let html = `<div class="grip"></div><div class="sh-head"><div><h2>${esc(G.carName(car))}</h2><p>${esc(G.SELLERS[l.seller].name)} · ${esc(l.sellerName)}</p></div><button class="x" type="button" data-a="close" aria-label="Kapat">×</button></div>
      <div class="hero">${UI.carSVG(car)}<span class="sticker">${G.fmtS(l.last)}</span></div>
      <div class="kv"><div><span>Model yılı</span><b>${car.year}</b></div><div><span>Kilometre</span><b>${G.fmtKm(G.realKm(car, false))}</b></div>
        <div><span>Yakıt / vites</span><b>${G.FUELS[car.fuel].name} · ${G.GEARS[car.gear].name}</b></div><div><span>Renk</span><b>${esc(car.color)}</b></div>
        <div><span>İstenen</span><b>${G.fmt(l.last)}</b></div><div><span>Tahmini piyasa</span><b>${G.fmt(ap)}</b></div></div>
      <div class="chips">${paintChips(car)}${car.inspected ? issueChips(car) : `<span class="chip warn">Durum: satıcı beyanı</span>`}</div>
      ${bars(car, false)}`;
    if (!car.inspected && !l.done) {
      html += `<div class="card"><div class="row"><div><h3>Ekspertize götür</h3><p class="muted small">Pazarlıktan önce gizli kusurları öğren.</p></div><button class="btn warn" type="button" data-a="l-inspect" data-id="${l.id}">${G.fmt(G.expertCost())}</button></div></div>`;
    }
    if (l.msgs.length) html += `<div class="chat">${l.msgs.map(m => `<div class="msg ${m.who}">${esc(m.text)}</div>`).join("")}</div>`;
    if (!l.done) {
      html += `<div class="card"><h3>Teklifin</h3><div class="range"><div class="range-out"><b id="ofOut">${G.fmt(d.offer)}</b><span class="small muted" id="ofInfo">${offerInfo(d.offer, l, ap)}</span></div>
        <input type="range" id="ofRange" data-in="offer" min="${lo}" max="${hi}" step="1000" value="${d.offer}" aria-label="Teklif">
        <div class="btns"><button class="btn pri" type="button" data-a="l-offer" data-id="${l.id}">Teklif ver</button><button class="btn" type="button" data-a="l-buy" data-id="${l.id}">${G.fmtS(l.last)}'ye al</button></div>
        <p class="muted small">Satıcının sabrı: ${"●".repeat(Math.max(0, l.rounds))}${"○".repeat(Math.max(0, G.SELLERS[l.seller].rounds - l.rounds))} · Çok düşük teklif satıcıyı kızdırır.</p></div></div>`;
      if (G.spaceLeft() <= 0) html += `<p class="neg small">Galeride boş park yeri yok. Önce bir araç sat ya da büyük bir yere taşın.</p>`;
    } else if (l.bought) html += `<div class="card"><p class="pos"><b>Araç artık senin.</b> Galeri sekmesinde fiyat etiketini asmayı unutma.</p><button class="btn pri" type="button" data-a="car" data-id="${car.id}">Araca git</button></div>`;
    return html;
  };
  function offerInfo(o, l, ap) {
    const pm = pct(o, ap);
    return `İstenenin %${Math.round((1 - o / l.last) * 100)} altı · piyasanın ${pm >= 0 ? "%" + pm + " üstü" : "%" + -pm + " altı"}`;
  }

  SHEETS.cust = function (id) {
    const s = S(), c = s.custs.find(x => x.id === id);
    if (!c) return `<div class="grip"></div><div class="sh-head"><div><h2>Müşteri ayrıldı</h2><p>Görüşme bitti.</p></div><button class="x" type="button" data-a="close" aria-label="Kapat">×</button></div>`;
    const car = G.custCar(c);
    if (!car) return null;
    const cost = car.buy + car.spent;
    const d = UI.draft;
    if (d.cid !== id) { d.cid = id; d.counter = car.price; }
    const lo = c.offer, hi = Math.max(car.price, c.offer + 1000);
    d.counter = R.clamp(d.counter, lo, hi);
    const t = G.CT[c.type];
    const bLo = R.r1k(c.budget * 0.8 / 50000) * 50, bHi = R.r1k(c.budget * 1.15 / 50000) * 50;
    let html = `<div class="grip"></div><div class="sh-head"><div><h2>${esc(c.name)}</h2><p>${esc(t.name)} · bütçesi ${G.fmtS(bLo)} – ${G.fmtS(bHi)} arası görünüyor</p></div><button class="x" type="button" data-a="close" aria-label="Kapat">×</button></div>
      <div class="hero">${UI.carSVG(car)}<span class="sticker">${G.fmtS(car.price)}</span></div>
      <div class="kv"><div><span>Araç</span><b>${esc(G.carTitle(car))}</b></div><div><span>Maliyetin</span><b>${G.fmt(cost)}</b></div>
        <div><span>Teklif</span><b>${G.fmt(c.offer)}</b></div><div><span>Teklifte kâr</span><b class="${c.offer - cost >= 0 ? "pos" : "neg"}">${signed(c.offer - cost)}</b></div></div>
      <div class="prog pat"><i data-pat="${c.id}" style="width:${c.patience / c.patMax * 100}%"></i></div>
      <div class="chat">${c.msgs.map(m => `<div class="msg ${m.who}">${esc(m.text)}</div>`).join("")}</div>`;
    if (c.trade) {
      html += `<div class="card"><h3>Takas aracı</h3><div class="row"><div><b>${esc(G.carTitle(c.trade))}</b><p class="small muted">${spec(c.trade)}</p></div><div class="num">${G.fmtS(c.tradeVal)}</div></div><div class="chips">${paintChips(c.trade)}<span class="chip warn">Ekspertizsiz</span></div>${bars(c.trade, false)}
        <p class="muted small">Kabul edersen eline ${G.fmt(c.offer - c.tradeVal)} nakit ve bu araç geçer.</p></div>`;
    }
    html += `<div class="card"><h3>Karşı teklif</h3><div class="range"><div class="range-out"><b id="coOut">${G.fmt(d.counter)}</b><span class="small" id="coInfo">${counterInfo(d.counter, cost)}</span></div>
      <input type="range" id="coRange" data-in="counter" min="${lo}" max="${hi}" step="1000" value="${d.counter}" aria-label="Karşı teklif">
      <div class="btns"><button class="btn pri" type="button" data-a="accept" data-id="${c.id}">${G.fmtS(c.offer)} kabul</button><button class="btn warn" type="button" data-a="counter" data-id="${c.id}">Karşı teklif ver</button><button class="btn danger" type="button" data-a="reject" data-id="${c.id}">Reddet</button></div>
      <p class="muted small">Pazarlık hakkı: ${c.rounds}. Çok yüksek karşı teklif müşteriyi kaçırır.</p></div></div>`;
    return html;
  };
  function counterInfo(v, cost) { const m = v - cost; return `<span class="${m >= 0 ? "pos" : "neg"}">${m >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(m))}</span>`; }

  SHEETS.auction = function () {
    const s = S(), a = s.auction;
    let html = `<div class="grip"></div><div class="sh-head"><div><h2>İhale salonu</h2><p>Gün ${a.day} · ${a.lots.length} araç · Kasa ${G.fmt(s.money)}</p></div><button class="x" type="button" data-a="close" aria-label="Kapat">×</button></div>`;
    if (a.finished) {
      html += `<div class="card"><h3>İhale bitti</h3>${a.lots.map(l => `<div class="row small"><div>${esc(G.carTitle(l.car))}</div><div class="${l.winner === "Sen" ? "pos" : "muted"}">${l.winner ? esc(l.winner) + " · " + G.fmtS(l.price) : "Satılmadı"}</div></div>`).join("")}</div>
        <button class="btn pri block" type="button" data-a="close">Kapat</button>`;
      return html;
    }
    const lot = a.lots[a.idx], car = lot.car, L = a.live;
    html += `<div class="hero">${UI.carSVG(car)}<span class="sticker">Lot ${a.idx + 1}/${a.lots.length}</span></div>
      <div class="row"><div><b>${esc(G.carTitle(car))}</b><p class="small muted">${esc(G.aucSrc(lot.src))} · ${spec(car)}</p></div><div class="num small">≈ ${G.fmtS(G.value(car, false))}</div></div>
      <div class="chips">${paintChips(car)}${car.inspected ? issueChips(car) : `<span class="chip warn">Kontrol edilmedi</span>`}</div>${bars(car, false)}`;
    if (!L) {
      html += `${s.upg.expert && !car.inspected ? `<button class="btn warn block" type="button" data-a="a-inspect">Hızlı kontrol · ${G.fmt(G.expertCost())}</button>` : ""}
        <button class="btn pri block" type="button" data-a="a-start">${a.idx === 0 ? "İhaleyi başlat" : "Sıradaki lotu aç"}</button>
        <p class="muted small">Açılış ${G.fmt(lot.start)} · artış ${G.fmt(lot.inc)}. Son tekliften sonra 4 saniye kimse artırmazsa çekiç iner.</p>`;
    } else {
      const next = L.leader ? L.bid + lot.inc : L.bid;
      html += `<div class="card"><div class="row"><div><div class="muted small">${L.leader ? "En yüksek teklif · " + esc(L.leader) : "Açılış fiyatı"}</div><div class="auc-big ${L.leader === "Sen" ? "pos" : ""}">${G.fmt(L.bid)}</div></div><div class="num" style="font-size:22px;font-weight:700">${Math.max(0, L.timer).toFixed(1).replace(".", ",")} sn</div></div>
        <div class="prog"><i style="width:${Math.min(100, L.timer / 7 * 100)}%"></i></div>
        <div class="btns"><button class="btn pri" type="button" data-a="a-bid" ${L.leader === "Sen" ? "disabled" : ""}>${L.leader === "Sen" ? "Teklif senin" : "Teklif ver · " + G.fmtS(next)}</button><button class="btn" type="button" data-a="a-skip" ${L.leader === "Sen" ? "disabled" : ""}>Pas geç</button></div>
        <div class="auc-log">${L.log.slice(0, 5).map(x => `<div>${esc(x)}</div>`).join("")}</div></div>`;
    }
    return html;
  };

  SHEETS.summary = function () {
    const sm = S().summary;
    if (!sm) return null;
    const T = sm.today;
    return `<div class="grip"></div><div class="sh-head"><div><h2>Gün ${sm.day} kapandı</h2><p>Kepenkler indi. Günün hesabı:</p></div></div>
      <div class="kv"><div><span>Satış</span><b>${T.sales}</b></div><div><span>Ciro</span><b>${G.fmtS(T.revenue)}</b></div>
        <div><span>Satış kârı</span><b class="${T.profit >= 0 ? "pos" : "neg"}">${G.fmtS(T.profit)}</b></div><div><span>Atölye ve ekspertiz</span><b>${G.fmtS(T.spentWork)}</b></div>
        <div><span>Ziyaretçi</span><b>${T.visitors}</b></div><div><span>Günün neti</span><b class="${sm.net >= 0 ? "pos" : "neg"}">${G.fmtS(sm.net)}</b></div></div>
      <div class="card"><h3>Giderler</h3>${sm.lines.map(([l, v]) => `<div class="row small"><div>${esc(l)}</div><div class="num">${v < 0 ? "−" : ""}${G.fmt(Math.abs(v))}</div></div>`).join("")}${T.commission ? `<div class="row small"><div>Danışman primi</div><div class="num">${G.fmt(T.commission)}</div></div>` : ""}${T.invest ? `<div class="row small"><div>Yatırım (geliştirme, taşınma)</div><div class="num">${G.fmt(T.invest)}</div></div>` : ""}</div>
      ${sm.news.length ? `<div class="card"><h3>Haberler</h3>${sm.news.map(n => `<div><b>${esc(n.title)}</b><p class="muted small">${esc(n.text)}</p></div>`).join("")}</div>` : ""}
      ${sm.lost.length ? `<div class="card"><h3>Kaçan müşteriler</h3>${sm.lost.map(l => `<div class="row small"><div>${esc(l.type)}</div><div class="muted" style="text-align:right">${esc(l.text)}</div></div>`).join("")}</div>` : ""}
      <button class="btn pri block" type="button" data-a="nextday">Gün ${S().day} başlasın</button>`;
  };

  SHEETS.feed = function () {
    return `<div class="grip"></div><div class="sh-head"><div><h2>Galeri defteri</h2><p>Son olaylar</p></div><button class="x" type="button" data-a="close" aria-label="Kapat">×</button></div>
      <div class="card">${S().feed.map(f => `<div class="small"><span class="muted">${esc(f.t)}</span><br>${esc(f.text)}</div>`).join("") || '<p class="muted">Henüz kayıt yok.</p>'}</div>`;
  };

  SHEETS.intro = function () {
    const demo = ["ferro-egeo", "kaizen-taiga", "mirage-stallion"].map((m, i) => UI.carSVG({ m, color: ["Beyaz", "Lacivert", "Kırmızı"][i], clean: 100 })).join("");
    return `<div class="grip"></div><div class="sh-head"><div><h2>Galeri Patronu</h2><p>Sanayideki arsadan otomotiv merkezine.</p></div></div>
      <div class="intro-art">${demo}</div>
      <div class="field"><label for="nameIn"><b>Galerinin adı</b></label><input type="text" id="nameIn" maxlength="28" value="${esc(UI.draft.name || "Patron Otomotiv")}" autocomplete="off"></div>
      <div class="card"><h3>Nasıl oynanır</h3><ol class="steps small">
        <li><b>Piyasa</b>'dan ucuz araç bul. Ekspertiz yaptır, satıcıyla pazarlık et ya da ihaleye gir.</li>
        <li><b>Atölye</b>'de temizle, bakımını yap, göçüğünü düzelt. Boya kaportayı düzeltir ama "boyalı parça" sayısını artırır.</li>
        <li>Araca <b>fiyat etiketi</b> as. Müşteriler gelir, teklif verir; kabul et, pazarlık et ya da reddet.</li>
        <li>Kârla <b>dükkânı büyüt</b>: vitrin, atölye, personel, reklam ve daha büyük adresler.</li></ol></div>
      <button class="btn pri block" type="button" data-a="begin">Kepenkleri aç</button>`;
  };

  // ---- Çizim ----
  function viewSig() {
    const s = S();
    switch (UI.tab) {
      case "galeri": return "g" + s.day + s.cars.map(c => `${c.id}.${c.status}.${c.price}.${c.show}.${Math.round(c.clean / 20)}.${c.inspected}.${c.motor}.${c.body}.${c.inter}.${c.painted}.${c.issues.filter(i => i.fixed).length}`).join() + "|" + s.custs.filter(c => c.state === "offer").map(c => c.carId).join() + "|" + s.jobs.map(j => j.id + "." + Math.ceil(j.left / 30)).join() + "|" + s.upg.showroom;
      case "piyasa": return "p" + s.day + UI.sub.piyasa + s.listings.map(l => l.id + "." + l.done + "." + l.last + "." + l.car.inspected).join() + "|" + s.auction.idx + s.auction.finished + "|" + Math.round(s.money / 1000) + "|" + G.carCount();
      case "musteri": return "m" + s.custs.map(c => `${c.id}.${c.state}.${c.offer}`).join() + "|" + s.lost.length + "|" + s.today.sales;
      case "atolye": return "a" + s.jobs.map(j => j.id).join() + "|" + JSON.stringify(s.upg) + s.staff.length;
      case "patron": return "b" + s.day + UI.sub.patron + Math.round(s.money / 1000) + s.staff.length + s.cands.length + JSON.stringify(s.upg) + s.loan + s.ads + s.loc + s.level + s.quests.length + Math.round(s.rep) + s.today.sales + UI.armed;
    }
    return "";
  }

  UI.render = function () {
    renderTop();
    renderNav();
    UI.sig = viewSig();
    $("view").innerHTML = VIEWS[UI.tab]();
    UI.renderSheet(true);
  };

  function sheetSig() {
    const sh = UI.sheet; if (!sh) return "";
    const s = S();
    if (sh.kind === "car") { const c = s.cars.find(x => x.id === sh.id); const j = s.jobs.find(x => x.carId === sh.id); return c ? `${c.status}${c.price}${c.show}${c.inspected}${c.motor}${c.body}${c.inter}${Math.round(c.clean / 10)}${Math.round(s.money / 1000)}${G.baysFree()}${UI.armed}${j ? Math.ceil(j.left / 30) : ""}` : "x"; }
    if (sh.kind === "cust") { const c = s.custs.find(x => x.id === sh.id); return c ? `${c.offer}${c.msgs.length}${c.rounds}` : "gone"; }
    if (sh.kind === "listing") { const l = s.listings.find(x => x.id === sh.id); return l ? `${l.msgs.length}${l.done}${l.car.inspected}${G.carCount()}` : "x"; }
    if (sh.kind === "auction") { const a = s.auction; return a.live ? `${a.idx}${a.live.bid}${a.live.leader}${Math.ceil(a.live.timer * 10)}` : `${a.idx}${a.finished}${a.lots[a.idx] && a.lots[a.idx].car.inspected}`; }
    return sh.kind;
  }

  UI.renderSheet = function (force) {
    const wrap = $("sheetWrap");
    if (!UI.sheet) { wrap.hidden = true; return; }
    const sig = sheetSig();
    if (!force && sig === UI.sheetSig) return;
    if (UI.dragging && !force) return;
    const html = SHEETS[UI.sheet.kind](UI.sheet.id);
    if (html == null) { UI.close(); return; }
    UI.sheetSig = sig;
    const sh = $("sheet");
    const top = sh.scrollTop;
    sh.innerHTML = html;
    sh.scrollTop = top;
    wrap.hidden = false;
  };

  UI.open = function (kind, id) {
    const s = S();
    for (const c of s.custs) c.userBusy = false;
    if (kind === "cust") { const c = s.custs.find(x => x.id === id); if (c) c.userBusy = true; }
    const changed = !UI.sheet || UI.sheet.kind !== kind || UI.sheet.id !== id;
    UI.sheet = { kind, id };
    UI.armed = null;
    if (changed) { UI.draft = { name: UI.draft.name }; $("sheet").scrollTop = 0; }
    UI.renderSheet(true);
  };
  UI.close = function () {
    if (UI.sheet && (UI.sheet.kind === "summary" || UI.sheet.kind === "intro")) return;
    if (UI.sheet && UI.sheet.kind === "auction" && S().auction.live) return;
    for (const c of S().custs) c.userBusy = false;
    UI.sheet = null;
    UI.armed = null;
    $("sheetWrap").hidden = true;
    UI.render();
  };
  UI.blocking = () => !!UI.sheet && (UI.sheet.kind === "summary" || UI.sheet.kind === "intro" || UI.sheet.kind === "auction");

  // Sık güncellenen küçük öğeler (ilerleme çubukları, sabır), gerekirse tam yeniden çizim
  UI.tick = function () {
    const s = S();
    if (!s) return;
    renderTop();
    if (s.summary && (!UI.sheet || UI.sheet.kind !== "summary")) { UI.sheet = { kind: "summary" }; UI.renderSheet(true); }
    const sig = viewSig();
    if (sig !== UI.sig && !UI.dragging) { UI.sig = sig; const y = window.scrollY; $("view").innerHTML = VIEWS[UI.tab](); window.scrollTo(0, y); }
    renderNav();
    for (const el of document.querySelectorAll("[data-job]")) { const j = s.jobs.find(x => x.id === +el.dataset.job); if (j) el.style.width = ((1 - j.left / j.total) * 100) + "%"; }
    for (const el of document.querySelectorAll("[data-jobt]")) { const j = s.jobs.find(x => x.id === +el.dataset.jobt); if (j) el.textContent = G.fmtDur(j.left); }
    for (const el of document.querySelectorAll("[data-pat]")) { const c = s.custs.find(x => x.id === +el.dataset.pat); if (c) el.style.width = (c.patience / c.patMax * 100) + "%"; }
    UI.renderSheet(false);
  };

  // ---- Eylemler ----
  function msg(r) { if (r && r.msg) UI.toast(r.msg, "bad"); }

  const ACT = {
    tab: el => { UI.tab = el.dataset.v; window.scrollTo(0, 0); UI.armed = null; UI.render(); },
    sub: el => { UI.sub[el.dataset.k] = el.dataset.v; UI.render(); },
    sort: el => { UI.sort = el.dataset.v; UI.render(); },
    speed: el => { const v = +el.dataset.v; const s = S(); if (v === 0) s.paused = true; else { s.speed = v; if (!s.summary) s.paused = false; } renderTop(); },
    close: () => UI.close(),
    feed: () => UI.open("feed"),
    car: el => UI.open("car", +el.dataset.id),
    listing: el => UI.open("listing", +el.dataset.id),
    cust: el => UI.open("cust", +el.dataset.id),
    pq: el => {
      const car = S().cars.find(c => c.id === UI.sheet.id); if (!car) return;
      UI.draft.price = R.r1k(G.value(car, false) * +el.dataset.v);
      UI.renderSheet(true);
    },
    "price-save": el => {
      const car = S().cars.find(c => c.id === +el.dataset.id); if (!car) return;
      G.setPrice(car, UI.draft.price);
      UI.toast(`Etiket asıldı: ${G.fmt(car.price)}`);
      UI.render();
    },
    "price-clear": el => { const car = S().cars.find(c => c.id === +el.dataset.id); if (car) { car.price = 0; for (const c of S().custs) if (c.carId === car.id && c.state === "offer") G.leave(c, null); UI.render(); } },
    inspect: el => {
      const car = S().cars.find(c => c.id === +el.dataset.id); if (!car) return;
      const r = G.inspect(car);
      if (!r.ok) return msg(r);
      UI.toast(r.found.length ? `Ekspertiz: ${r.found.join(", ")}` : "Ekspertiz temiz çıktı.", r.found.length ? "bad" : "good");
      UI.render();
    },
    job: el => {
      const car = S().cars.find(c => c.id === +el.dataset.id); if (!car) return;
      const o = G.jobOptions(car)[+el.dataset.i]; if (!o) return;
      const r = G.startJob(car, o);
      if (!r.ok) return msg(r);
      UI.toast(`${o.name} başladı · ${G.fmtDur(o.mins)}`);
      UI.render();
    },
    show: el => { const car = S().cars.find(c => c.id === +el.dataset.id); if (car) { msg(G.toggleShow(car)); UI.render(); } },
    wholesale: el => {
      const id = +el.dataset.id;
      if (UI.armed !== "ws" + id) { UI.armed = "ws" + id; UI.renderSheet(true); return; }
      const car = S().cars.find(c => c.id === id); if (!car) return;
      const r = G.wholesale(car);
      if (!r.ok) return msg(r);
      UI.armed = null;
      UI.sheet = null; UI.render();
    },
    "l-inspect": el => {
      const l = S().listings.find(x => x.id === +el.dataset.id); if (!l) return;
      const r = G.inspect(l.car);
      if (!r.ok) return msg(r);
      l.msgs.push({ who: "sales", text: r.found.length ? `Ekspertiz raporu: ${r.found.join(", ")}.` : "Ekspertiz raporu temiz." });
      UI.renderSheet(true);
    },
    "l-offer": el => listingOffer(+el.dataset.id, UI.draft.offer),
    "l-buy": el => { const l = S().listings.find(x => x.id === +el.dataset.id); if (l) listingOffer(l.id, l.last); },
    accept: el => {
      const c = S().custs.find(x => x.id === +el.dataset.id); if (!c) return;
      const r = G.acceptOffer(c);
      if (!r.ok) return msg(r);
      if (UI.sheet && UI.sheet.kind === "cust") UI.sheet = null;
      UI.render();
    },
    counter: el => {
      const c = S().custs.find(x => x.id === +el.dataset.id); if (!c) return;
      const r = G.counterOffer(c, UI.draft.counter);
      if (r.res === "counter") UI.draft.counter = R.clamp(UI.draft.counter, c.offer, G.custCar(c).price);
      UI.renderSheet(true);
      if (r.res === "walked") UI.toast(`${c.name} anlaşamadan ayrıldı.`, "bad");
      UI.render();
    },
    reject: el => { const c = S().custs.find(x => x.id === +el.dataset.id); if (c) { G.rejectOffer(c); if (UI.sheet && UI.sheet.kind === "cust") UI.sheet = null; UI.render(); } },
    auction: () => UI.open("auction"),
    "a-start": () => { G.auctionBegin(); UI.renderSheet(true); },
    "a-bid": () => { msg(G.auctionBid()); UI.renderSheet(true); },
    "a-skip": () => { G.auctionSkip(); },
    "a-inspect": () => { const a = S().auction; const r = G.inspect(a.lots[a.idx].car); if (!r.ok) return msg(r); UI.toast(r.found.length ? `Kontrol: ${r.found.join(", ")}` : "Kontrol temiz.", r.found.length ? "bad" : "good"); UI.renderSheet(true); },
    upg: el => { msg(G.buyUpgrade(el.dataset.v)); UI.render(); },
    move: () => { msg(G.moveTo(S().loc + 1)); UI.render(); },
    hire: el => { const c = S().cands.find(x => x.id === +el.dataset.id); if (c) { msg(G.hire(c)); UI.render(); } },
    fire: el => {
      const id = +el.dataset.id;
      if (UI.armed !== "fire" + id) { UI.armed = "fire" + id; UI.render(); return; }
      const st = S().staff.find(x => x.id === id); if (st) G.fire(st);
      UI.armed = null; UI.render();
    },
    ads: el => { S().ads = +el.dataset.v; UI.render(); },
    borrow: el => { msg(G.borrow(+el.dataset.v)); UI.render(); },
    repay: el => { msg(G.repay(+el.dataset.v)); UI.render(); },
    reset: () => {
      if (UI.armed !== "reset") { UI.armed = "reset"; UI.render(); return; }
      UI.armed = null;
      G.wipe();
    },
    nextday: () => {
      const s = S();
      s.summary = null;
      s.paused = false;
      UI.sheet = null;
      UI.render();
    },
    begin: () => {
      const name = ($("nameIn").value || "").trim() || "Patron Otomotiv";
      G.start(name);
      G.S.paused = false;
      UI.sheet = null;
      G.save();
      UI.render();
    }
  };

  function listingOffer(id, amount) {
    const l = S().listings.find(x => x.id === id); if (!l) return;
    const r = G.offerListing(l, amount);
    if (r.res === "nospace") return UI.toast("Galeride boş park yeri yok.", "bad");
    if (r.res === "nomoney") return UI.toast("Kasada bu kadar para yok.", "bad");
    if (r.res === "counter") UI.draft.offer = Math.min(UI.draft.offer, l.last);
    UI.renderSheet(true);
    UI.sig = "";
  }

  UI.bind = function () {
    document.addEventListener("click", e => {
      const el = e.target.closest("[data-a]");
      if (!el || el.disabled) return;
      const f = ACT[el.dataset.a];
      if (f) { e.preventDefault(); f(el); }
    });
    document.addEventListener("keydown", e => {
      if ((e.key === "Enter" || e.key === " ") && e.target.matches("article[data-a]")) { e.preventDefault(); ACT[e.target.dataset.a](e.target); }
      if (e.key === "Escape") UI.close();
    });
    document.addEventListener("input", e => {
      const el = e.target;
      const k = el.dataset && el.dataset.in;
      if (!k) return;
      const v = +el.value;
      const s = S();
      if (k === "price") { const car = s.cars.find(c => c.id === UI.sheet.id); UI.draft.price = v; $("prOut").textContent = G.fmt(v); if (car) $("prInfo").innerHTML = priceInfo(v, G.value(car, false), car.buy + car.spent); }
      if (k === "offer") { const l = s.listings.find(x => x.id === UI.sheet.id); UI.draft.offer = v; $("ofOut").textContent = G.fmt(v); if (l) $("ofInfo").textContent = offerInfo(v, l, G.value(l.car, false)); }
      if (k === "counter") { const c = s.custs.find(x => x.id === UI.sheet.id); UI.draft.counter = v; $("coOut").textContent = G.fmt(v); const car = c && G.custCar(c); if (car) $("coInfo").innerHTML = counterInfo(v, car.buy + car.spent); }
      if (k === "disc") { s.maxDisc = v / 100; $("discOut").textContent = "%" + v; }
    });
    const down = e => { if (e.target.matches && e.target.matches('input[type="range"]')) UI.dragging = true; };
    const up = () => { if (UI.dragging) { UI.dragging = false; } };
    document.addEventListener("pointerdown", down);
    document.addEventListener("pointerup", up);
    document.addEventListener("pointercancel", up);
    document.addEventListener("touchstart", down, { passive: true });
    document.addEventListener("touchend", up);
  };
})(window.G = window.G || {});
