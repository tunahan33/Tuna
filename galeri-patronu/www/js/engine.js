/* Galeri Patronu — oyun motoru: durum, değerleme, simülasyon */
(function (G) {
  "use strict";

  // ---- Yardımcılar ----
  const R = G.R = {
    rand: (a, b) => a + Math.random() * (b - a),
    randi: (a, b) => Math.floor(a + Math.random() * (b - a + 1)),
    pick: arr => arr[Math.floor(Math.random() * arr.length)],
    chance: p => Math.random() < p,
    clamp: (x, a, b) => Math.max(a, Math.min(b, x)),
    r1k: x => Math.round(x / 1000) * 1000,
    r100: x => Math.round(x / 100) * 100,
    wpick(items, w) {
      let t = 0;
      const ws = items.map(it => { const v = Math.max(0, w(it)); t += v; return v; });
      if (t <= 0) return null;
      let r = Math.random() * t;
      for (let i = 0; i < items.length; i++) { r -= ws[i]; if (r <= 0) return items[i]; }
      return items[items.length - 1];
    }
  };

  const nfTL = new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 0 });
  const nf1 = new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 1 });
  const nf2 = new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 2 });
  G.fmt = x => nfTL.format(Math.round(x)) + " ₺";
  G.fmtS = x => {
    const a = Math.abs(x), s = x < 0 ? "−" : "";
    if (a >= 1e9) return s + nf2.format(a / 1e9) + " milyar ₺";
    if (a >= 1e6) return s + nf2.format(a / 1e6) + " milyon ₺";
    if (a >= 1e4) return s + nf1.format(a / 1e3) + " bin ₺";
    return s + nfTL.format(a) + " ₺";
  };
  G.fmtKm = km => nfTL.format(km) + " km";
  G.clock = min => String(Math.floor(min / 60)).padStart(2, "0") + ":" + String(Math.floor(min % 60)).padStart(2, "0");
  G.fmtDur = mins => {
    mins = Math.max(0, Math.ceil(mins));
    const d = Math.floor(mins / 600), h = Math.floor((mins % 600) / 60), m = mins % 60;
    if (d) return `${d} gün ${h} sa`;
    if (h) return `${h} sa ${m} dk`;
    return `${m} dk`;
  };

  const OPEN = 9 * 60, CLOSE = 19 * 60;
  G.OPEN = OPEN; G.CLOSE = CLOSE;

  let S = null;
  Object.defineProperty(G, "S", { get: () => S, set: v => { S = v; } });

  // ---- Durum ----
  G.blankDay = () => ({ sales: 0, revenue: 0, profit: 0, bought: 0, spentBuy: 0, spentWork: 0, expenses: 0, visitors: 0, lostN: 0, commission: 0, invest: 0 });

  G.newState = function (name) {
    const st = {
      v: 1, name: name || "Patron Otomotiv",
      money: 1200000, day: 1, min: OPEN, speed: 1, paused: true,
      rep: 30, xp: 0, level: 1, loc: 0,
      upg: { wash: 0, bays: 0, paint: 0, pdr: 0, expert: 0, showroom: 0, cafe: 0, security: 0 },
      ads: 0, maxDisc: 0.05,
      staff: [], cands: [], candDay: 0,
      cars: [], listings: [], auction: null,
      custs: [], lost: [], jobs: [],
      trend: {}, events: [], news: [],
      loan: 0, feed: [], complaints: [],
      today: G.blankDay(), history: [],
      stats: { sold: 0, bought: 0, revenue: 0, profit: 0, best: 0, inspected: 0, auctions: 0, luxSold: 0 },
      quests: [], nextId: 1, summary: null, started: false
    };
    Object.keys(G.SEGMENTS).forEach(k => { st.trend[k] = 1; });
    return st;
  };

  G.start = function (name) {
    S = G.newState(name);
    const starter = G.makeCar(G.M["anka-serce"], G.SELLERS.sahibinden, { year: 2014 });
    starter.issues = [];
    starter.inspected = true;
    starter.buy = R.r1k(G.value(starter, true) * 0.86);
    starter.price = R.r1k(G.value(starter, false) * 1.06);
    S.cars.push(starter);
    G.refreshListings();
    G.newAuction();
    G.newCandidates();
    S.started = true;
    G.feed(`${S.name} kapılarını açtı. Kasada ${G.fmt(S.money)} var, arsada bir Anka Serçe duruyor.`, "info");
  };

  G.nid = () => S.nextId++;

  G.feed = function (text, kind) {
    S.feed.unshift({ t: `Gün ${S.day} · ${G.clock(S.min)}`, text, kind: kind || "info" });
    if (S.feed.length > 60) S.feed.length = 60;
    if (G.onFeed) G.onFeed(text, kind);
  };

  // ---- Seviye ve sınırlar ----
  G.loc = () => G.LOCS[S.loc];
  G.capacity = () => G.loc().cap;
  G.carCount = () => S.cars.length;
  G.spaceLeft = () => G.capacity() - G.carCount();
  G.showSlots = () => (S.upg.showroom ? G.UPGRADES.showroom.levels[S.upg.showroom - 1].slots : 0);
  G.showUsed = () => S.cars.filter(c => c.show).length;
  G.xpNeed = l => Math.round(120 * Math.pow(l, 1.55));
  G.segUnlocked = seg => G.SEGMENTS[seg].lvl <= S.level;
  G.staffOf = role => S.staff.filter(s => s.role === role);
  G.skillSum = role => G.staffOf(role).reduce((a, s) => a + s.skill, 0);

  G.addXp = function (x) {
    S.xp += x;
    while (S.xp >= G.xpNeed(S.level)) {
      S.xp -= G.xpNeed(S.level);
      S.level++;
      const segs = Object.keys(G.SEGMENTS).filter(k => G.SEGMENTS[k].lvl === S.level).map(k => G.SEGMENTS[k].name);
      const locs = G.LOCS.filter(l => l.lvl === S.level).map(l => l.name);
      let extra = [];
      if (segs.length) extra.push(`${segs.join(", ")} segmenti piyasada`);
      if (locs.length) extra.push(`${locs.join(", ")} kiralanabilir`);
      G.feed(`Seviye ${S.level}! ${extra.length ? extra.join("; ") + "." : "Yeni geliştirmeler açıldı."}`, "level");
    }
  };

  G.addRep = function (d) { S.rep = R.clamp(S.rep + d, 0, 100); };

  // ---- Piyasa çarpanları ----
  G.trendOf = function (seg) {
    let t = S.trend[seg] || 1;
    for (const e of S.events) {
      const d = G.EVENTS.find(x => x.id === e.id);
      if (!d) continue;
      if (d.all) t *= d.all;
      if (d.seg && d.seg[seg]) t *= d.seg[seg];
    }
    return t;
  };
  G.trafficMult = function () {
    let t = 1;
    for (const e of S.events) { const d = G.EVENTS.find(x => x.id === e.id); if (d && d.traffic) t *= d.traffic; }
    return t;
  };

  // ---- Araç üretimi ----
  G.plate = function () {
    const city = R.pick(G.PLATE_CITIES);
    const nl = R.randi(1, 3);
    let l = "";
    for (let i = 0; i < nl; i++) l += G.PLATE_LETTERS[R.randi(0, G.PLATE_LETTERS.length - 1)];
    const nd = nl === 1 ? 4 : nl === 2 ? R.randi(3, 4) : R.randi(2, 3);
    const d = String(R.randi(Math.pow(10, nd - 1), Math.pow(10, nd) - 1));
    return `${city} ${l} ${d}`;
  };

  G.makeCar = function (model, src, opt) {
    opt = opt || {};
    src = src || G.SELLERS.sahibinden;
    const [y0, y1] = model.years;
    let year;
    if (opt.year) year = opt.year;
    else if (model.seg === "klasik") year = R.randi(y0, y1);
    else {
      const maxAge = G.YEAR - y0;
      const age = Math.min(maxAge, Math.floor(Math.pow(Math.random(), 1.25) * Math.min(maxAge + 1, 16)));
      year = Math.min(y1, Math.max(y0, G.YEAR - age));
    }
    const age = G.YEAR - year;
    const klasik = model.seg === "klasik";
    let km;
    if (klasik) km = R.randi(60, 320) * 1000;
    else if (age === 0) km = R.randi(0, 15) * 1000 + R.randi(0, 999);
    else km = Math.round(age * R.rand(8000, 21000) * (src.kmMult || 1) / 100) * 100 + R.randi(0, 99);

    const wear = klasik ? R.rand(25, 55) : age * 2.2;
    const part = extra => Math.round(R.clamp(100 - wear * R.rand(0.6, 1.3) - R.rand(0, 16) - (extra || 0), 8, 100));
    const motor = part(0), body = part(src.bodyHit ? R.rand(src.bodyHit * 0.6, src.bodyHit) : 0), inter = part(0);

    let painted = 0, replaced = 0, tramer = 0;
    for (let i = 0; i < Math.min(6, age); i++) if (R.chance(0.14)) painted++;
    if (R.chance(src.pert ? 0.9 : 0.1)) replaced = R.randi(1, src.pert ? 4 : 2);
    if (src.pert) painted += R.randi(1, 3);
    if (painted + replaced > 0 && R.chance(src.pert ? 1 : 0.6)) {
      tramer = R.r1k(R.rand(6e3, 35e3) * (painted + replaced * 2.5) * (src.pert ? 3 : 1) * (model.price / 1.5e6));
    }

    const color = opt.color || R.wpick(G.COLORS, c => c.w * (model.seg === "spor" && c.sport ? 3 : 1));
    const fuel = R.pick(model.fuels);
    const gear = model.gears.length > 1 ? (R.chance(age < 6 ? 0.7 : 0.35) ? "otomatik" : "manuel") : model.gears[0];

    const issues = [];
    if (!opt.noIssues && R.chance(src.issue)) {
      const n = R.chance(0.25) ? 2 : 1;
      const pool = Object.keys(G.ISSUES).filter(id => {
        const d = G.ISSUES[id];
        if (d.noEv && fuel === "elektrik") return false;
        if (d.evOnly && fuel !== "elektrik") return false;
        if (id === "km" && (age < 3 || klasik)) return false;
        return true;
      });
      for (let i = 0; i < n; i++) {
        const id = R.pick(pool);
        if (issues.some(x => x.id === id)) continue;
        const is = { id, known: false, fixed: false };
        if (id === "km") is.realKm = Math.round(km * R.rand(1.5, 2.2) / 100) * 100;
        issues.push(is);
      }
    }

    return {
      id: G.nid(), m: model.id, year, km, color: color.name, fuel, gear, plate: G.plate(),
      motor, body, inter, clean: src.clean ? R.randi(85, 100) : R.randi(30, 75),
      painted, replaced, tramer, issues, inspected: false,
      buy: 0, spent: 0, price: 0, show: false, days: 0, status: "lot"
    };
  };

  // ---- Değerleme ----
  G.issueVisible = (is, all) => !is.fixed && (all || is.known);

  G.parts = function (car, all) {
    let m = car.motor, b = car.body, i = car.inter;
    for (const is of car.issues) {
      if (!G.issueVisible(is, all)) continue;
      const d = G.ISSUES[is.id];
      if (d.part === "motor") m -= d.hit;
      if (d.part === "body") b -= d.hit;
      if (d.part === "inter") i -= d.hit;
    }
    return { m: R.clamp(m, 0, 100), b: R.clamp(b, 0, 100), i: R.clamp(i, 0, 100) };
  };
  G.cond = function (car, all) { const p = G.parts(car, all); return 0.45 * p.m + 0.35 * p.b + 0.2 * p.i; };
  G.realKm = function (car, all) {
    const is = car.issues.find(x => x.id === "km" && (all || x.known));
    return is ? is.realKm : car.km;
  };
  G.paintedCount = function (car, all) {
    let p = car.painted;
    if (all) for (const is of car.issues) if (is.id === "gizliboya" && !is.known) p += G.ISSUES.gizliboya.painted;
    return p;
  };
  G.ageF = function (md, year) {
    if (md.seg === "klasik") return 1;
    const age = G.YEAR - year;
    return age === 0 ? 1 : Math.max(0.16, 0.9 * Math.pow(0.9, age));
  };
  // Kusursuz hâldeki değer: onarım maliyetleri bunun üzerinden hesaplanır
  G.nominal = car => { const md = G.M[car.m]; return md.price * G.ageF(md, car.year); };

  G.value = function (car, all) {
    const md = G.M[car.m];
    const klasik = md.seg === "klasik";
    const age = G.YEAR - car.year;
    let v = md.price * G.ageF(md, car.year);
    const km = G.realKm(car, all);
    if (klasik) v *= R.clamp(1 - km / 700000, 0.65, 1);
    else { const exp = Math.max(1, age) * 15000; v *= R.clamp(1 + (exp - km) / 320000, 0.62, 1.12); }
    const c = G.cond(car, all) / 100;
    v *= klasik ? 0.12 + 0.88 * Math.pow(c, 2.2) : 0.3 + 0.7 * Math.pow(c, 1.3);
    const pp = klasik ? 0.4 : 1;
    v *= Math.max(0.6, 1 - (0.015 * G.paintedCount(car, all) + 0.045 * car.replaced) * pp);
    if (car.tramer) v *= 1 - Math.min(0.14, car.tramer / Math.max(1, v) * 0.45);
    const col = G.COLORS.find(x => x.name === car.color);
    if (col) v *= (md.seg === "spor" && col.sport) ? col.sport : col.f;
    v *= G.FUELS[car.fuel].f * (md.seg === "spor" && car.gear === "manuel" ? 1.02 : G.GEARS[car.gear].f);
    v *= G.trendOf(md.seg);
    for (const is of car.issues) if (G.issueVisible(is, all) && G.ISSUES[is.id].valueHit) v *= G.ISSUES[is.id].valueHit;
    return Math.max(15000, R.r1k(v));
  };
  G.carName = car => { const md = G.M[car.m]; return `${md.brand} ${md.name}`; };
  G.carTitle = car => `${car.year} ${G.carName(car)}`;
  G.hasUnknown = car => car.issues.some(is => !is.known && !is.fixed);

  G.netWorth = () => S.money + S.cars.reduce((a, c) => a + G.value(c, false), 0) - S.loan;

  // ---- İlanlar (piyasadan alım) ----
  G.pickModel = function (filter) {
    const pool = G.MODELS.filter(m => G.segUnlocked(m.seg) && (!filter || filter(m)));
    return R.wpick(pool, m => m.pop * (0.7 + 0.6 * G.trendOf(m.seg)) * (m.seg === "luks" || m.seg === "spor" ? 0.6 : 1));
  };

  G.makeListing = function () {
    const sellers = Object.keys(G.SELLERS).filter(k => G.SELLERS[k].w > 0 && (!G.SELLERS[k].lvl || G.SELLERS[k].lvl <= S.level));
    const sk = R.wpick(sellers, k => G.SELLERS[k].w);
    const src = G.SELLERS[sk];
    const car = G.makeCar(G.pickModel(), src);
    const apparent = G.value(car, false);
    const ask = R.r1k(apparent * R.rand(src.ask[0], src.ask[1]));
    return {
      id: G.nid(), car, seller: sk, sellerName: R.pick(G.FIRST) + " " + R.pick(G.LAST)[0] + ".",
      ask, min: R.r1k(ask * R.rand(src.min[0], src.min[1])), rounds: src.rounds,
      last: ask, expires: S.day + R.randi(1, 3), msgs: [], done: false
    };
  };

  G.refreshListings = function () {
    S.listings = S.listings.filter(l => l.expires >= S.day && !l.done);
    const target = Math.min(14, 6 + Math.floor(S.level / 2) + S.loc);
    while (S.listings.length < target) S.listings.push(G.makeListing());
  };

  G.expertCost = () => (S.upg.expert ? 500 : 3500);

  G.inspect = function (car) {
    const cost = G.expertCost();
    if (car.inspected) return { ok: false, msg: "Bu araç zaten ekspertizden geçti." };
    if (S.money < cost) return { ok: false, msg: "Kasada ekspertiz ücreti kadar para yok." };
    S.money -= cost;
    S.today.spentWork += cost;
    car.inspected = true;
    S.stats.inspected++;
    const found = [];
    for (const is of car.issues) {
      if (is.known || is.fixed) continue;
      is.known = true;
      found.push(G.ISSUES[is.id].name);
      if (is.id === "gizliboya") car.painted += G.ISSUES.gizliboya.painted;
    }
    G.checkQuests();
    return { ok: true, found, cost };
  };

  // Satıcıyla pazarlık: oyuncu teklif verir, satıcı kabul eder, karşı teklif verir ya da görüşmeyi keser
  G.offerListing = function (l, amount) {
    amount = R.r1k(amount);
    if (l.done) return { res: "closed" };
    if (G.spaceLeft() <= 0) return { res: "nospace" };
    if (S.money < amount) return { res: "nomoney" };
    l.msgs.push({ who: "me", text: `${G.fmt(amount)} veriyorum.` });
    if (amount >= l.last || amount >= l.min) {
      const price = Math.min(amount, l.last);
      l.msgs.push({ who: "them", text: R.pick(["Tamam, anlaştık. Hayırlı olsun!", "Olsun bakalım, el sıkışalım.", "Peki, senin olsun. Güle güle kullan."]) });
      G.buyListing(l, price);
      return { res: "accepted", price };
    }
    l.rounds -= amount < l.min * 0.8 ? 2 : 1;
    if (l.rounds <= 0) {
      l.msgs.push({ who: "them", text: R.pick(["Kusura bakma, bu fiyatlarla anlaşamayız. Başka alıcım var.", "Olmaz kardeşim, ben vazgeçtim."]) });
      l.done = true; l.walked = true;
      return { res: "walked" };
    }
    const counter = R.r1k(Math.max(l.min, l.last - (l.last - Math.max(amount, l.min)) * R.rand(0.35, 0.6)));
    l.last = Math.max(l.min, Math.min(l.last, counter));
    l.msgs.push({ who: "them", text: amount < l.min * 0.8
      ? `Dalga mı geçiyorsun? En az ${G.fmt(l.last)} olur.`
      : R.pick([`Son fiyat ${G.fmt(l.last)}. Daha aşağı inemem.`, `${G.fmt(l.last)} olursa veririm abi.`, `Araç temiz, ${G.fmt(l.last)} ver al.`]) });
    return { res: "counter", price: l.last };
  };

  G.buyListing = function (l, price) {
    const car = l.car;
    S.money -= price;
    car.buy = price;
    car.status = "lot";
    car.price = 0;
    S.cars.push(car);
    l.done = true; l.bought = true;
    S.today.bought++;
    S.today.spentBuy += price;
    S.stats.bought++;
    G.feed(`Satın alındı: ${G.carTitle(car)} · ${G.fmt(price)}`, "buy");
    G.checkQuests();
  };

  // ---- Açık artırma ----
  G.newAuction = function () {
    const n = R.randi(3, 4 + Math.min(2, S.loc));
    const lots = [];
    for (let i = 0; i < n; i++) {
      const src = R.pick(["banka", "sigorta", "filo"]);
      const car = G.makeCar(G.pickModel(), Object.assign({}, G.SELLERS.ihale, src === "sigorta" ? { bodyHit: 25 } : {}, src === "filo" ? { kmMult: 1.8 } : {}));
      car.clean = R.randi(20, 60);
      const ap = G.value(car, false);
      lots.push({ car, src, start: R.r1k(ap * R.rand(0.42, 0.55)), inc: Math.max(5000, R.r1k(ap * 0.02)), done: false, winner: null, price: 0 });
    }
    S.auction = { day: S.day, lots, idx: 0, live: null, finished: false };
  };
  const AUC_SRC = { banka: "Bankadan icralık", sigorta: "Sigorta şirketinden", filo: "Kiralama filosundan" };
  G.aucSrc = s => AUC_SRC[s];

  G.auctionBegin = function () {
    const a = S.auction;
    if (!a || a.finished) return;
    G.auctionLot();
  };
  G.auctionLot = function () {
    const a = S.auction;
    const lot = a.lots[a.idx];
    if (!lot) { a.finished = true; a.live = null; return; }
    const ap = G.value(lot.car, false), tv = G.value(lot.car, true);
    const npcs = [];
    const names = G.RIVALS.slice().sort(() => Math.random() - 0.5).slice(0, R.randi(2, 4));
    for (const name of names) npcs.push({ name, max: R.r1k(((ap + tv) / 2) * R.rand(0.6, 0.97)), wait: R.rand(0.6, 2.8) });
    a.live = { bid: lot.start, leader: null, timer: 7, npcs, log: [`Açılış: ${G.fmt(lot.start)}`] };
  };
  G.auctionTick = function (dt) {
    const a = S.auction;
    if (!a || !a.live) return;
    const L = a.live, lot = a.lots[a.idx];
    L.timer -= dt;
    for (const n of L.npcs) {
      if (L.leader === n.name) continue;
      const next = L.leader ? L.bid + lot.inc : L.bid;
      if (n.max < next) continue;
      n.wait -= dt;
      if (n.wait <= 0) {
        L.bid = next; L.leader = n.name;
        L.timer = Math.max(L.timer, 4);
        L.log.unshift(`${n.name}: ${G.fmt(L.bid)}`);
        L.npcs.forEach(o => { o.wait = R.rand(0.7, 3.2); });
        break;
      }
    }
    if (L.timer <= 0) {
      lot.done = true;
      lot.winner = L.leader;
      lot.price = L.bid;
      if (L.leader === "Sen") {
        const car = lot.car;
        S.money -= L.bid;
        car.buy = L.bid;
        S.cars.push(car);
        S.today.bought++;
        S.today.spentBuy += L.bid;
        S.stats.bought++;
        S.stats.auctions++;
        G.feed(`İhaleden alındı: ${G.carTitle(car)} · ${G.fmt(L.bid)}`, "buy");
        G.checkQuests();
      }
      a.idx++;
      a.live = null;
      if (a.idx >= a.lots.length) a.finished = true;
    }
  };
  G.auctionBid = function () {
    const a = S.auction;
    if (!a || !a.live) return { ok: false };
    const L = a.live, lot = a.lots[a.idx];
    if (L.leader === "Sen") return { ok: false, msg: "En yüksek teklif zaten senin." };
    const next = L.leader ? L.bid + lot.inc : L.bid;
    if (S.money < next) return { ok: false, msg: "Kasadaki para bu teklife yetmiyor." };
    if (G.spaceLeft() <= 0) return { ok: false, msg: "Galeride yer yok; önce bir araç sat." };
    L.bid = next; L.leader = "Sen";
    L.timer = Math.max(L.timer, 4);
    L.log.unshift(`Sen: ${G.fmt(L.bid)}`);
    L.npcs.forEach(o => { o.wait = R.rand(0.9, 3.4); });
    return { ok: true };
  };
  G.auctionSkip = function () {
    const a = S.auction;
    if (!a || !a.live) return;
    // oyuncu ilgilenmiyorsa lot rakipler arasında hızla kapanır
    const L = a.live;
    if (L.leader === "Sen") return;
    L.timer = Math.min(L.timer, 1.2);
  };

  // ---- Atölye ----
  G.baysFree = () => S.upg.bays - S.jobs.filter(j => j.inhouse && j.where === "bay").length;
  G.jobTimeMult = function (inhouse) {
    if (!inhouse) return 1.6;
    const sk = G.skillSum("usta");
    return sk ? 1 / (1 + 0.1 * sk) : 1.4;
  };

  G.jobOptions = function (car) {
    const md = G.M[car.m];
    const v = G.nominal(car);
    const out = [];
    const add = (type, extra) => {
      const d = G.JOBS[type];
      let inhouse = false, why = "";
      if (d.where === "wash") inhouse = S.upg.wash > 0;
      else if (d.where === "paint") inhouse = S.upg.paint > 0 && G.baysFree() > 0;
      else if (d.where === "pdr") { inhouse = true; if (!S.upg.pdr) why = "Boyasız göçük seti gerekir"; }
      else inhouse = G.baysFree() > 0;
      let cost = extra && extra.cost != null ? extra.cost : d.cost(v);
      let hours = extra && extra.hours ? extra.hours : d.hours;
      if (type === "clean" && inhouse) { cost = 1000; hours = 1; }
      else if (inhouse && type !== "pdr") cost *= 0.65;
      cost = R.r100(cost);
      const mins = Math.round(hours * 60 * (type === "clean" ? 1 : G.jobTimeMult(inhouse)));
      out.push(Object.assign({ type, name: d.name, cost, mins, inhouse, why }, extra || {}));
    };
    add("clean");
    const p = G.parts(car, false);
    if (p.m < 90) add("service");
    if (p.m < 60) add("engine");
    if (p.b < 92) add("paint");
    if (p.b < 92 && p.b >= 55) add("pdr");
    if (p.i < 90) add("interior");
    if (md.seg === "klasik" && G.cond(car, false) < 85) add("restore");
    for (const is of car.issues) {
      const d = G.ISSUES[is.id];
      if (is.known && !is.fixed && d.fix) add("fix", { issue: is.id, name: `${d.name} onarımı`, cost: 5000 + d.fix * v, hours: d.hours });
    }
    for (const o of out) {
      if (!o.why && S.money < o.cost) o.why = "Kasada yeterli para yok";
    }
    return out;
  };

  G.startJob = function (car, opt) {
    if (car.status !== "lot") return { ok: false, msg: "Araç şu an müsait değil." };
    if (opt.why) return { ok: false, msg: opt.why };
    if (S.money < opt.cost) return { ok: false, msg: "Kasada yeterli para yok." };
    // araç bir müşteriyle pazarlıktaysa önce müşteri gider
    for (const c of S.custs) if (c.carId === car.id && c.state === "offer") G.leave(c, null, "Aracı atölyeye aldın; müşteri ayrıldı.");
    S.money -= opt.cost;
    car.spent += opt.cost;
    S.today.spentWork += opt.cost;
    car.status = "shop";
    car.show = false;
    const where = G.JOBS[opt.type].where === "wash" ? "wash" : G.JOBS[opt.type].where === "pdr" ? "pdr" : "bay";
    S.jobs.push({ id: G.nid(), carId: car.id, type: opt.type, issue: opt.issue || null, name: opt.name, left: opt.mins, total: opt.mins, inhouse: opt.inhouse, where, cost: opt.cost });
    return { ok: true };
  };

  function finishJob(j) {
    const car = S.cars.find(c => c.id === j.carId);
    S.jobs = S.jobs.filter(x => x !== j);
    if (!car) return;
    car.status = "lot";
    switch (j.type) {
      case "clean": car.clean = 100; car.inter = Math.min(100, car.inter + 3); break;
      case "service": car.motor = Math.min(95, car.motor + 22); break;
      case "engine": car.motor = Math.max(car.motor, 96); break;
      case "paint":
        car.body = Math.max(car.body, 95);
        car.painted += j.inhouse ? 1 : R.randi(1, 3);
        break;
      case "pdr": car.body = Math.min(100, car.body + 15); break;
      case "interior": car.inter = Math.max(car.inter, 95); break;
      case "restore":
        car.motor = Math.max(car.motor, 95); car.body = Math.max(car.body, 96); car.inter = Math.max(car.inter, 95);
        car.painted += 3; car.clean = 100;
        car.issues.forEach(is => { if (is.known && G.ISSUES[is.id].fix) is.fixed = true; });
        break;
      case "fix": { const is = car.issues.find(x => x.id === j.issue); if (is) is.fixed = true; break; }
    }
    G.feed(`Atölye: ${G.carTitle(car)} için ${j.name.toLocaleLowerCase("tr-TR")} bitti.`, "shop");
  }

  // ---- Fiyat, vitrin, toptan satış ----
  G.setPrice = function (car, price) { car.price = Math.max(0, R.r1k(price)); };
  G.toggleShow = function (car) {
    if (car.show) { car.show = false; return { ok: true }; }
    if (car.status !== "lot") return { ok: false, msg: "Atölyedeki araç vitrine konamaz." };
    if (G.showUsed() >= G.showSlots()) return { ok: false, msg: G.showSlots() ? "Vitrin dolu." : "Önce kapalı vitrin kurmalısın." };
    car.show = true;
    return { ok: true };
  };
  G.wholesalePrice = car => R.r1k(G.value(car, false) * 0.8 * (G.hasUnknown(car) ? 0.95 : 1));
  G.wholesale = function (car) {
    if (car.status !== "lot") return { ok: false, msg: "Atölyedeki araç satılamaz." };
    const price = G.wholesalePrice(car);
    for (const c of S.custs) if (c.carId === car.id && c.state === "offer") G.leave(c, null, "Aracı toptancıya verdin; müşteri ayrıldı.");
    finalizeSale(car, price, null, true);
    return { ok: true, price };
  };

  // ---- Müşteriler ----
  function customerTypes() {
    const L = G.loc();
    return G.CTYPES.filter(t => t.prestige <= L.prestige + (S.rep >= 75 ? 1 : 0) && Object.keys(t.segs).some(G.segUnlocked));
  }

  G.spawnCustomer = function () {
    const types = customerTypes();
    let boost = {};
    for (const e of S.events) { const d = G.EVENTS.find(x => x.id === e.id); if (d && d.cust) Object.assign(boost, d.cust); }
    const t = R.wpick(types, x => x.w * (boost[x.id] || 1));
    if (!t) return;
    const segs = Object.keys(t.segs).filter(G.segUnlocked);
    const seg = R.wpick(segs, s => t.segs[s] * G.trendOf(s));
    const c = {
      id: G.nid(), name: R.pick(G.FIRST) + " " + R.pick(G.LAST), type: t.id, budget: R.r1k(R.rand(t.budget[0], t.budget[1])),
      seg, state: "browse", t: R.randi(12, 35), patience: 0, patMax: 0, rounds: 0, carId: null, offer: 0, max: 0, msgs: [], trade: null, tradeVal: 0
    };
    if (R.chance(t.trade)) {
      const tm = R.pick(G.MODELS.filter(m => (m.seg === "ekonomi" || m.seg === "aile") && m.price < c.budget * 1.6));
      if (tm) c.trade = G.makeCar(tm, G.SELLERS.sahibinden);
    }
    S.custs.push(c);
    S.today.visitors++;
  };

  G.leave = function (c, lostReason, note) {
    c.state = "gone";
    S.custs = S.custs.filter(x => x !== c);
    if (lostReason) {
      S.today.lostN++;
      S.lost.unshift({ name: c.name, type: G.CT[c.type].name, text: lostReason });
      if (S.lost.length > 12) S.lost.length = 12;
    }
    if (note) G.feed(note, "info");
  };

  function decide(c) {
    const t = G.CT[c.type];
    const taken = new Set(S.custs.filter(o => o !== c && o.state === "offer").map(o => o.carId));
    let best = null, bs = 0;
    for (const car of S.cars) {
      if (car.status !== "lot" || !car.price || taken.has(car.id)) continue;
      const md = G.M[car.m];
      if (car.price > c.budget * 1.08) continue;
      const segW = md.seg === c.seg ? 1 : (t.segs[md.seg] ? 0.35 : 0.04);
      const mv = G.value(car, false);
      const ratio = car.price / mv;
      if (ratio > 1.25) continue;
      const priceS = R.clamp(1.25 - ratio, 0.04, 0.6) / 0.35;
      const cond = G.cond(car, false) / 100;
      const looks = 0.62 + 0.25 * car.clean / 100 + (car.show ? 0.35 : 0);
      const picky = Math.max(0.2, 1 - t.picky * (0.04 * car.painted + 0.09 * car.replaced + (car.tramer > 0 ? 0.05 : 0)));
      const fit = 0.55 + 0.45 * Math.min(1, car.price / (c.budget * 0.6));
      const lux = (md.seg === "luks" || md.seg === "spor") && !car.show ? 0.6 : 1;
      const s = segW * priceS * (0.5 + cond) * looks * picky * fit * lux * R.rand(0.8, 1.2);
      if (s > bs) { bs = s; best = car; }
    }
    if (!best || bs < 0.32) {
      G.leave(c, `${G.SEGMENTS[c.seg].name} aradı, bütçesi ${G.fmtS(c.budget)}`);
      return;
    }
    makeOffer(c, best);
  }

  function makeOffer(c, car) {
    const t = G.CT[c.type], md = G.M[car.m];
    const mv = G.value(car, false), tv = G.value(car, true);
    c.max = R.r1k(Math.min(c.budget * 1.08, mv * R.rand(1.0, 1.08) * (car.show ? 1.03 : 1) * (1 + S.rep / 1000)));
    // titiz müşteri kendi ekspertizini yaptırır
    if (R.chance(t.picky * 0.5) && tv < mv) { c.max = R.r1k(Math.min(c.max, tv * 1.02)); c.checked = true; }
    let offer;
    if (car.price <= c.max * (1 - t.haggle * 0.3) && R.chance(0.3)) offer = car.price;
    else offer = R.r1k(Math.min(car.price, c.max) * (1 - t.haggle * R.rand(0.7, 1.4)));
    c.state = "offer";
    c.carId = car.id;
    c.offer = Math.min(offer, car.price);
    c.patMax = c.patience = Math.round(t.patience * (S.upg.cafe ? 1.35 : 1));
    c.rounds = R.randi(2, 3);
    if (c.trade) {
      c.tradeVal = R.r1k(G.value(c.trade, false) * 0.85);
      if (c.tradeVal >= c.offer * 0.8) { c.trade = null; c.tradeVal = 0; }
    }
    const first = c.offer >= car.price
      ? `${md.brand} ${md.name} tam aradığım araç. Etiket fiyatına alıyorum.`
      : R.pick([`${md.brand} ${md.name} ilgimi çekti. ${G.fmt(c.offer)} veririm.`, `Aracı beğendim ama fiyat biraz yüksek. ${G.fmt(c.offer)} olur mu?`, `Nakit ${G.fmt(c.offer)} hazır, hemen alırım.`]);
    c.msgs = [{ who: "them", text: first }];
    if (c.checked) c.msgs.push({ who: "them", text: "Bu arada aracı bildiğim bir ekspertize gösterdim, bazı kusurlar çıktı." });
    if (c.trade) c.msgs.push({ who: "them", text: `Kendi ${G.carTitle(c.trade)} aracımı takasa veririm, ona ${G.fmt(c.tradeVal)} düşersin.` });
    if (G.onOffer) G.onOffer(c, car);
  }

  G.custCar = c => S.cars.find(x => x.id === c.carId);

  G.acceptOffer = function (c) {
    const car = G.custCar(c);
    if (!car || c.state !== "offer") return { ok: false };
    c.msgs.push({ who: "me", text: "Tamam, anlaştık." });
    finalizeSale(car, c.offer, c);
    return { ok: true, price: c.offer };
  };

  G.counterOffer = function (c, amount, bySales) {
    const car = G.custCar(c);
    if (!car || c.state !== "offer") return { res: "gone" };
    amount = R.r1k(amount);
    c.msgs.push({ who: "me", text: `${G.fmt(amount)} olursa veririm.` });
    if (amount <= c.offer) { finalizeSale(car, amount, c, false, bySales); return { res: "sold", price: amount }; }
    if (amount <= c.max) {
      c.msgs.push({ who: "them", text: R.pick(["Tamam, el sıkışalım. Hayırlı olsun!", "Peki, anlaştık.", "Olur, alıyorum."]) });
      finalizeSale(car, amount, c, false, bySales);
      return { res: "sold", price: amount };
    }
    c.rounds -= amount > c.max * 1.12 ? 2 : 1;
    if (c.rounds <= 0) {
      c.msgs.push({ who: "them", text: R.pick(["Bu fiyata olmaz, kusura bakma. Ben bir dolaşayım.", "Çok pahalı, başka galerilere bakacağım."]) });
      G.addRep(-0.3);
      G.leave(c, `${G.carName(car)} için pazarlıkta anlaşılamadı`);
      return { res: "walked" };
    }
    const nv = R.r1k(c.offer + (Math.min(amount, c.max) - c.offer) * R.rand(0.35, 0.65));
    c.offer = Math.max(c.offer, Math.min(nv, car.price));
    c.patience = Math.max(c.patience, 20);
    c.msgs.push({ who: "them", text: R.pick([`En fazla ${G.fmt(c.offer)} verebilirim.`, `Biraz daha inin. ${G.fmt(c.offer)} diyorum.`, `${G.fmt(c.offer)}, son sözüm.`]) });
    return { res: "counter", price: c.offer };
  };

  G.rejectOffer = function (c) {
    c.msgs.push({ who: "me", text: "Bu fiyata veremem." });
    G.leave(c, null);
  };

  function finalizeSale(car, price, c, wholesale, bySales) {
    let cash = price;
    let tradeCar = null;
    if (c && c.trade && c.tradeVal) { tradeCar = c.trade; cash = price - c.tradeVal; }
    S.money += cash;
    const profit = price - car.buy - car.spent;
    S.cars = S.cars.filter(x => x !== car);
    S.today.sales++;
    S.today.revenue += price;
    S.today.profit += profit;
    S.stats.sold++;
    S.stats.revenue += price;
    S.stats.profit += profit;
    if (profit > S.stats.best) S.stats.best = profit;
    if (G.M[car.m].seg === "luks") S.stats.luxSold++;
    if (bySales) {
      const com = R.r100(price * 0.015);
      S.money -= com;
      S.today.commission += com;
      S.today.expenses += com;
    }
    G.addXp(Math.round(20 + Math.max(0, profit) / 4000) * (wholesale ? 0.5 : 1));
    if (!wholesale) {
      const hidden = car.issues.filter(is => !is.known && !is.fixed && G.ISSUES[is.id].part !== undefined);
      if (hidden.length && !c.checked && R.chance(0.65)) {
        S.complaints.push({ day: S.day + R.randi(1, 4), name: c.name, car: G.carTitle(car), issue: G.ISSUES[hidden[0].id].name, amount: R.r1k(price * 0.12) });
      } else {
        G.addRep(0.6 + (G.cond(car, true) > 80 ? 0.6 : 0));
      }
    }
    if (c) { c.state = "sold"; S.custs = S.custs.filter(x => x !== c); }
    if (tradeCar) {
      tradeCar.buy = c.tradeVal;
      tradeCar.status = "lot";
      S.cars.push(tradeCar);
    }
    const who = wholesale ? "Toptancıya satıldı" : bySales ? "Danışmanın sattı" : "Satıldı";
    G.feed(`${who}: ${G.carTitle(car)} · ${G.fmt(price)} (${profit >= 0 ? "kâr" : "zarar"} ${G.fmt(Math.abs(profit))})${tradeCar ? ` · takasta ${G.carTitle(tradeCar)} alındı` : ""}`, profit >= 0 ? "good" : "bad");
    G.checkQuests();
  }

  // Satış danışmanı: oyuncu bir süre cevap vermezse teklifi o yönetir
  function salesAuto(c) {
    const sk = G.staffOf("satis").reduce((a, s) => Math.max(a, s.skill), 0);
    if (!sk) return;
    const car = G.custCar(c);
    if (!car) return;
    c.auto = true;
    c.max = R.r1k(c.max * (1 + 0.012 * sk));
    const target = R.r1k(car.price * (1 - S.maxDisc));
    if (c.offer >= target) { c.msgs.push({ who: "sales", text: "Danışmanın teklifi kabul etti." }); finalizeSale(car, c.offer, c, false, true); return; }
    c.msgs.push({ who: "sales", text: `Danışmanın ${G.fmt(target)} istedi.` });
    if (target <= c.max) { finalizeSale(car, target, c, false, true); return; }
    G.leave(c, `${G.carName(car)} için danışmanın indirim sınırında anlaşamadı`);
  }

  // ---- Personel ----
  G.newCandidates = function () {
    S.cands = [];
    const roles = Object.keys(G.ROLES);
    for (let i = 0; i < 4; i++) {
      const role = i < roles.length ? roles[i] : R.pick(roles);
      const skill = R.wpick([1, 2, 3, 4, 5], s => [5, 5, 3, 1.5, 0.6][s - 1] * (s >= 4 && S.level < 5 ? 0.4 : 1));
      S.cands.push({ id: G.nid(), role, skill, name: R.pick(G.FIRST) + " " + R.pick(G.LAST), salary: R.r100(G.ROLES[role].base * (0.7 + 0.3 * skill) * R.rand(0.92, 1.1)) });
    }
    S.candDay = S.day;
  };
  G.hire = function (cand) {
    if (S.staff.length >= G.loc().staff) return { ok: false, msg: `Bu konumda en fazla ${G.loc().staff} çalışan olabilir.` };
    S.staff.push(cand);
    S.cands = S.cands.filter(x => x !== cand);
    G.feed(`${cand.name} ${G.ROLES[cand.role].name.toLocaleLowerCase("tr-TR")} olarak işe başladı.`, "info");
    G.checkQuests();
    return { ok: true };
  };
  G.fire = function (st) {
    const sev = st.salary * 3;
    S.money -= sev;
    S.today.expenses += sev;
    S.staff = S.staff.filter(x => x !== st);
    G.feed(`${st.name} ile yollar ayrıldı. Tazminat: ${G.fmt(sev)}`, "info");
  };

  // ---- Dükkân ----
  G.upgNext = key => G.UPGRADES[key].levels[S.upg[key]] || null;
  G.buyUpgrade = function (key) {
    const n = G.upgNext(key);
    if (!n) return { ok: false, msg: "En üst seviyede." };
    if (n.lvl > S.level) return { ok: false, msg: `Seviye ${n.lvl} gerekir.` };
    if (S.money < n.cost) return { ok: false, msg: "Kasada yeterli para yok." };
    S.money -= n.cost;
    S.upg[key]++;
    S.today.invest += n.cost;
    G.feed(`${G.UPGRADES[key].name} kuruldu.`, "info");
    G.checkQuests();
    return { ok: true };
  };
  G.moveTo = function (i) {
    const L = G.LOCS[i];
    if (i !== S.loc + 1) return { ok: false, msg: "Sırayla büyüyebilirsin." };
    if (L.lvl > S.level) return { ok: false, msg: `Seviye ${L.lvl} gerekir.` };
    if (S.money < L.cost) return { ok: false, msg: "Kasada yeterli para yok." };
    S.money -= L.cost;
    S.today.invest += L.cost;
    S.loc = i;
    G.addRep(3);
    G.feed(`Yeni adres: ${L.name}! Kapasite ${L.cap} araç.`, "level");
    G.checkQuests();
    return { ok: true };
  };

  // ---- Banka ----
  G.loanLimit = () => R.r1k(500e3 + S.level * 400e3 + 0.3 * S.cars.reduce((a, c) => a + G.value(c, false), 0));
  G.LOAN_RATE = 0.0015;
  G.borrow = function (amt) {
    if (S.loan + amt > G.loanLimit()) return { ok: false, msg: "Kredi limitini aşıyor." };
    S.loan += amt; S.money += amt;
    G.feed(`Bankadan ${G.fmt(amt)} kredi çekildi.`, "info");
    return { ok: true };
  };
  G.repay = function (amt) {
    amt = Math.min(amt, S.loan);
    if (amt <= 0) return { ok: false, msg: "Ödenecek kredi yok." };
    if (S.money < amt) return { ok: false, msg: "Kasada yeterli para yok." };
    S.loan -= amt; S.money -= amt;
    G.feed(`${G.fmt(amt)} kredi borcu ödendi.`, "info");
    return { ok: true };
  };

  // ---- Görevler ----
  G.checkQuests = function () {
    for (const q of G.QUESTS) {
      if (S.quests.includes(q.id)) continue;
      if (q.check(S)) {
        S.quests.push(q.id);
        S.money += q.reward;
        G.feed(`Görev tamamlandı: ${q.text}. Ödül ${G.fmt(q.reward)}`, "level");
      }
    }
  };
  G.activeQuests = n => G.QUESTS.filter(q => !S.quests.includes(q.id)).slice(0, n || 3);

  // ---- Zaman ----
  G.minute = function () {
    S.min++;
    // atölye
    for (const j of S.jobs.slice()) { j.left--; if (j.left <= 0) finishJob(j); }
    // müşteri gelişi
    const L = G.loc();
    const listed = S.cars.filter(c => c.status === "lot" && c.price > 0).length;
    const stock = listed ? Math.min(1.5, 0.6 + listed * 0.12) : 0.25;
    const perHour = 1.15 * L.traffic * G.ADS[S.ads].mult * (0.45 + S.rep / 90) * G.trafficMult() * stock;
    if (S.custs.length < 3 + L.prestige * 2 && R.chance(perHour / 60)) G.spawnCustomer();
    // müşteriler
    for (const c of S.custs.slice()) {
      if (c.state === "browse") { if (--c.t <= 0) decide(c); }
      else if (c.state === "offer") {
        c.patience--;
        if (!c.auto && G.staffOf("satis").length && c.patMax - c.patience >= 15 && !c.userBusy) { salesAuto(c); continue; }
        if (c.patience <= 0) {
          G.addRep(-0.3);
          const car = G.custCar(c);
          G.leave(c, `Beklemekten sıkıldı${car ? ` (${G.carName(car)})` : ""}`);
        }
      }
    }
    if (S.min >= CLOSE) G.endDay();
  };

  G.endDay = function () {
    const L = G.loc();
    const salaries = S.staff.reduce((a, s) => a + s.salary, 0);
    const ads = G.ADS[S.ads].cost;
    const insurance = S.cars.length * 150;
    const interest = Math.round(S.loan * G.LOAN_RATE);
    const overdraft = S.money < 0 ? Math.round(-S.money * 0.004) : 0;
    let exp = L.rent + salaries + ads + insurance + interest + overdraft;
    const accDisc = Math.min(0.15, 0.03 * G.skillSum("muhasebe"));
    const saved = Math.round(exp * accDisc);
    exp -= saved;
    S.money -= exp;
    S.today.expenses += exp;
    const T = S.today;
    const net = T.profit - T.expenses - T.spentWork;
    S.history.push({ day: S.day, revenue: T.revenue, profit: T.profit, expenses: T.expenses + T.spentWork, net, sales: T.sales });
    if (S.history.length > 30) S.history.shift();
    const summary = {
      day: S.day, today: Object.assign({}, T), net,
      lines: [["Kira", L.rent], ["Maaşlar", salaries], ["Reklam", ads], ["Sigorta ve otopark", insurance], ["Kredi faizi", interest], ["Ek hesap faizi", overdraft], ["Muhasebe tasarrufu", -saved]].filter(x => x[1]),
      lost: S.lost.slice(0, 6), news: []
    };

    // yeni gün
    S.day++;
    S.min = OPEN;
    S.today = G.blankDay();
    S.lost = [];
    for (const c of S.custs) if (c.state === "offer") G.addRep(-0.2);
    S.custs = [];
    const wash = G.skillSum("yikama") * 4;
    let washed = 0;
    for (const car of S.cars) {
      car.days++;
      car.clean = Math.max(0, car.clean - (car.show ? 4 : R.randi(8, 13)));
      if (washed < wash && car.status === "lot") { car.clean = 100; washed++; }
    }
    for (const k of Object.keys(S.trend)) S.trend[k] = R.clamp(S.trend[k] * R.rand(0.985, 1.016) + (1 - S.trend[k]) * 0.05, 0.85, 1.18);
    S.events = S.events.filter(e => e.until >= S.day);
    if (G.ADS[S.ads].rep) G.addRep(G.ADS[S.ads].rep);
    if (S.rep > 50) G.addRep(-0.15);
    // olay
    if (S.day > 2 && R.chance(0.33)) summary.news.push(triggerEvent());
    // şikâyetler
    for (const cp of S.complaints.slice()) {
      if (cp.day > S.day) continue;
      S.complaints = S.complaints.filter(x => x !== cp);
      S.money -= cp.amount;
      S.today.expenses += cp.amount;
      G.addRep(-5);
      const t = `${cp.name} şikâyet etti: ${cp.car} aracında "${cp.issue}" çıktı. Tazminat ${G.fmt(cp.amount)}, itibar −5.`;
      G.feed(t, "bad");
      summary.news.push({ title: "Müşteri şikâyeti", text: t });
    }
    G.refreshListings();
    G.newAuction();
    if (S.day - S.candDay >= 5) G.newCandidates();
    G.checkQuests();
    S.summary = summary;
    S.paused = true;
  };

  function triggerEvent() {
    const pool = G.EVENTS.filter(e => !S.events.some(x => x.id === e.id));
    const e = R.pick(pool);
    let text = e.text;
    if (e.instant === "dolu") {
      const hit = S.cars.filter(c => !c.show && c.status === "lot");
      hit.forEach(c => { c.body = Math.max(10, c.body - R.randi(6, 14)); });
      text = hit.length ? `Açıkta duran ${hit.length} aracın kaportası göçük doldu. Boyasız göçük düzeltme işe yarar.` : "Neyse ki açıkta araç yoktu.";
    } else if (e.instant === "hirsiz") {
      if (S.upg.security) text = "Kameralar ve alarm hırsızları kaçırdı. Hasar yok.";
      else {
        const car = R.pick(S.cars);
        if (car) { car.inter = Math.max(5, car.inter - 20); text = `${G.carTitle(car)} aracının teybi ve koltukları çalındı. Kamera ve alarm kurmayı düşün.`; }
        else text = "Çalınacak bir şey bulamadılar.";
      }
    } else if (e.instant === "denetim") {
      if (G.staffOf("muhasebe").length) text = "Muhasebecin bütün evrakı hazır tuttu. Ceza yok.";
      else { const fine = R.r1k(8000 + S.level * 6000); S.money -= fine; S.today.expenses += fine; text = `Defterlerde eksik çıktı, ${G.fmt(fine)} ceza ödendi. Muhasebeci bu riski ortadan kaldırır.`; }
    } else {
      S.events.push({ id: e.id, until: S.day + e.days - 1 });
      if (e.rep) G.addRep(e.rep);
      text += ` (${e.days} gün)`;
    }
    const n = { title: e.title, text };
    S.news.unshift({ day: S.day, title: e.title, text });
    if (S.news.length > 20) S.news.length = 20;
    G.feed(`${e.title}: ${text}`, "news");
    return n;
  }
})(window.G = window.G || {});
