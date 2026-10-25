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
      upg: { bays: 0, paint: 0, pdr: 0, expert: 0, cafe: 0, security: 0 },
      fac: { galeri: 0, yikama: 0, cardak: 0, otopark: 0 },
      ads: 0, maxDisc: 0.05,
      staff: [], cands: [], candDay: 0,
      cars: [], listings: [], auction: null,
      custs: [], lost: [], jobs: [],
      trend: {}, events: [], news: [],
      loan: 0, feed: [], complaints: [],
      today: G.blankDay(), history: [],
      stats: { sold: 0, bought: 0, revenue: 0, profit: 0, best: 0, inspected: 0, auctions: 0, luxSold: 0 },
      quests: [], nextId: 1, summary: null, started: false,
      adv: { day: 0, n: {}, total: 0, lastInter: 0, lastWatch: 0 }, boost: null,
      lux: [], party: null, lastParty: -99
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
    G.importListings();
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
  G.capacity = () => G.loc().cap + G.facEff("otopark").cap;
  G.carCount = () => S.cars.length;
  G.spaceLeft = () => G.capacity() - G.carCount();
  G.showSlots = () => G.facEff("galeri").slots;
  G.showUsed = () => S.cars.filter(c => c.show).length;
  G.xpNeed = l => Math.round(100 * Math.pow(l, 1.45));
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

  // ---- Tesisler ----
  G.facLevel = key => (S.fac && S.fac[key]) || 0;
  G.facReq = (key, n) => G.FACILITIES[key].start + Math.round((n - 1) * G.FAC_STEP);
  G.facCost = (key, n) => { const c = G.FACILITIES[key].base * Math.pow(G.FAC_GROWTH, n - 1); return c < 1e6 ? R.r1k(c) : Math.round(c / 1e4) * 1e4; };
  // Bir tesisin n. seviyedeki etkileri
  G.facEff = function (key, n) {
    if (n == null) n = G.facLevel(key);
    switch (key) {
      case "galeri": return { traffic: 1 + 0.03 * n, pay: 1 + 0.004 * n, slots: n >= 2 ? Math.floor((n + 1) / 3) + G.loc().prestige : 0 };
      case "yikama": return { on: n > 0, cost: Math.max(300, 1200 - 45 * n), mins: Math.max(15, 60 - Math.round(2.25 * n)), auto: Math.floor(n / 2), decay: 1 - 0.025 * n, shine: n >= 5 ? 0.012 * n : 0 };
      case "cardak": return { cap: n ? 1 + Math.floor(n / 5) : 0, rest: 30 + 5 * n, buffMult: 1.3 + 0.035 * n, buffMins: Math.round(90 + 9 * n), auto: n >= 10 };
      case "otopark": return { cap: Math.floor(n / 2), light: n >= 4, covered: n >= 6 ? Math.min(0.8, 0.3 + 0.025 * (n - 6)) : 0, asphalt: n >= 10, shine: 0.006 * n };
    }
    return {};
  };
  // Kullanıcıya gösterilen etki satırları
  G.facLines = function (key, n) {
    const e = G.facEff(key, n);
    const pc = x => "%" + Math.round(x * 100);
    switch (key) {
      case "galeri": return [`Müşteri trafiği +${pc(e.traffic - 1)}`, `Müşterinin ödemeye razı olduğu fiyat +${pc(e.pay - 1).replace("%", "%")}`, e.slots ? `${e.slots} araçlık vitrin` : "Vitrin yok (2. seviyede açılır)"];
      case "yikama": return e.on ? [`Yıkama ${G.fmt(e.cost)}, ${e.mins} dakika`, e.auto ? `Her sabah ${e.auto} araç kendiliğinden yıkanır` : "Otomatik yıkama yok (2. seviyede açılır)", `Araçlar ${pc(1 - e.decay)} daha geç kirlenir`, e.shine ? `Cila: temiz araca ilgi +${pc(e.shine)}` : "Cila yok (5. seviyede açılır)"] : ["Yıkama dış serviste: 3.000 ₺, 3 saat"];
      case "cardak": return e.cap ? [`Aynı anda ${e.cap} usta dinlenebilir`, `Dinlenirken saatte +${Math.round(e.rest)} enerji`, `Çardaktan dinç çıkan usta ${G.fmtDur(e.buffMins)} boyunca ×${e.buffMult.toFixed(2).replace(".", ",")} hızlı`, e.auto ? "Otomatik mola açık" : "Otomatik mola 10. seviyede"] : ["Çardak yok: ustalar gün içinde dinlenemez"];
      case "otopark": return [`+${e.cap} park yeri`, e.light ? "Aydınlatma: hırsızlık riski yarıya iner" : "Aydınlatma 4. seviyede", e.covered ? `Tente: araçların ${pc(e.covered)} kadarı doludan korunur` : "Tente 6. seviyede", e.asphalt ? `Asfalt zemin: araçlara ilgi +${pc(e.shine)}` : `Zemin: araçlara ilgi +${pc(e.shine)}`];
    }
    return [];
  };
  G.facNext = function (key) {
    const n = G.facLevel(key) + 1;
    if (n > G.FAC_MAX) return null;
    return { n, cost: G.facCost(key, n), req: G.facReq(key, n) };
  };
  G.buyFac = function (key) {
    const nx = G.facNext(key);
    if (!nx) return { ok: false, msg: "Bu tesis en üst seviyede." };
    if (S.level < nx.req) return { ok: false, msg: `Bunun için oyuncu seviyen ${nx.req} olmalı.` };
    if (S.money < nx.cost) return { ok: false, msg: "Kasada yeterli para yok." };
    S.money -= nx.cost;
    S.today.invest += nx.cost;
    S.fac[key] = nx.n;
    G.addXp(10 * nx.n);
    const mark = G.FACILITIES[key].marks[nx.n];
    G.feed(`${G.FACILITIES[key].name} ${nx.n}. seviyeye çıktı${mark ? `: ${mark}` : ""}.`, mark ? "level" : "info");
    G.checkQuests();
    return { ok: true };
  };

  // ---- Ustalar: yorgunluk ve çardak ----
  G.ustas = () => G.staffOf("usta");
  G.buffActive = u => !!(u.buff && u.buff.day === S.day && S.min < u.buff.until);
  G.restingCount = () => G.ustas().filter(u => u.resting).length;
  G.restFree = () => G.facEff("cardak").cap - G.restingCount();
  G.workRate = function () {
    let power = 0;
    for (const u of G.ustas()) {
      if (u.resting) continue;
      const e = u.energy == null ? 100 : u.energy;
      power += u.skill * (0.25 + 0.75 * e / 100) * (G.buffActive(u) ? u.buff.mult : 1);
    }
    return power ? 1 + 0.2 * power : 0.7;
  };
  const ustaJob = j => j.inhouse && (j.where === "bay" || j.where === "pdr");
  G.jobRate = j => (ustaJob(j) ? G.workRate() : 1);
  G.jobEta = j => j.left / G.jobRate(j);
  G.sendRest = function (u, auto) {
    if (!G.facLevel("cardak")) return { ok: false, msg: "Önce usta çardağı kurmalısın." };
    if (u.resting) return { ok: false };
    if (G.restFree() <= 0) return { ok: false, msg: "Çardak dolu." };
    u.resting = true;
    u.restMins = 0;
    u.buff = null;
    if (auto) G.feed(`${u.name} yoruldu, çardağa çay içmeye gitti.`, "info");
    return { ok: true };
  };
  G.recall = function (u, auto) {
    if (!u.resting) return { ok: false };
    u.resting = false;
    const e = G.facEff("cardak");
    if ((u.restMins || 0) >= 30) {
      u.buff = { day: S.day, until: Math.min(G.CLOSE, S.min + e.buffMins), mult: e.buffMult };
      G.feed(`${u.name} çardaktan dinç çıktı: ${G.fmtDur(e.buffMins)} boyunca ×${e.buffMult.toFixed(2).replace(".", ",")} hızlı çalışacak.`, auto ? "good" : "info");
    }
    return { ok: true };
  };
  function ustaMinute() {
    const ustas = G.ustas();
    if (!ustas.length) return;
    const working = S.jobs.filter(ustaJob).length;
    const active = ustas.filter(u => !u.resting);
    const load = active.length ? Math.min(1, working / active.length) : 0;
    const ce = G.facEff("cardak");
    for (const u of ustas) {
      if (u.energy == null) u.energy = 100;
      if (u.resting) {
        u.energy = Math.min(100, u.energy + ce.rest / 60);
        u.restMins = (u.restMins || 0) + 1;
        if (u.energy >= 100) G.recall(u, true);
      } else if (load) u.energy = Math.max(0, u.energy - 0.25 * load);
    }
    if (ce.auto) for (const u of active) if (u.energy < 30 && G.restFree() > 0) G.sendRest(u, true);
  }

  // Eski kayıtları yeni tesis sistemine taşır
  G.migrate = function (st) {
    if (!st.fac) {
      st.fac = { galeri: [0, 5, 11, 20, 20][st.upg.showroom || 0] || 0, yikama: st.upg.wash ? 3 : 0, cardak: 0, otopark: 0 };
    }
    delete st.upg.showroom;
    delete st.upg.wash;
    if (!st.lux) { st.lux = []; st.party = null; st.lastParty = -99; }
    // lüks fiyatları 2,5 katına çıkmadan önce alınan eşyalar eski fiyatlarıyla kayda geçer
    if (!st.luxPaid) { st.luxPaid = {}; for (const id of st.lux) if (G.LX[id]) st.luxPaid[id] = Math.round(G.LX[id].price / 2.5); }
    for (const x of st.staff) if (x.role === "usta" && x.energy == null) x.energy = 100;
    return st;
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
    if (G.partyActive()) t *= G.partyEff().traffic;
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
      const n = R.chance(0.1) ? 2 : 1;
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
    if (md.seg === "klasik" || md.seg === "ultra") return 1;
    const age = G.YEAR - year;
    return age === 0 ? 1 : Math.max(0.16, 0.9 * Math.pow(0.9, age));
  };
  // Kusursuz hâldeki değer: onarım maliyetleri bunun üzerinden hesaplanır
  G.nominal = car => { const md = G.M[car.m]; return md.price * G.ageF(md, car.year); };

  G.value = function (car, all) {
    const md = G.M[car.m];
    const klasik = md.seg === "klasik" || md.seg === "ultra";
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

  G.netWorth = () => S.money + S.cars.reduce((a, c) => a + G.value(c, false), 0) - S.loan + G.luxValue();

  // ---- İlanlar (piyasadan alım) ----
  G.pickModel = function (filter) {
    const pool = G.MODELS.filter(m => m.seg !== "ultra" && G.segUnlocked(m.seg) && (!filter || filter(m)));
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
      last: ask, day: S.day, born: S.min, msgs: [], done: false
    };
  };

  // İlanlar her sabah tamamen yenilenir; gün içinde belli saatlerde yeni ilanlar düşer
  G.LISTING_DROPS = [11 * 60, 13 * 60, 15 * 60, 17 * 60];
  G.listingTarget = () => Math.min(24, 10 + Math.floor(S.level / 2) + 2 * S.loc);
  G.refreshListings = function () {
    S.listings = [];
    const n = G.listingTarget();
    for (let i = 0; i < n; i++) S.listings.push(G.makeListing());
  };
  G.addListings = function (n, note) {
    // biten ilanları at, liste çok uzamasın
    S.listings = S.listings.filter(l => !l.done);
    for (let i = 0; i < n; i++) S.listings.push(G.makeListing());
    const cap = G.listingTarget() + 10;
    if (S.listings.length > cap) S.listings.splice(0, S.listings.length - cap);
    if (note) G.feed(note, "info");
  };
  G.nextDrop = () => G.LISTING_DROPS.find(m => m > S.min) || null;

  G.expertCost = () => (S.upg.expert ? 500 : 3500);

  G.inspect = function (car, free) {
    const cost = free ? 0 : G.expertCost();
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

  G.jobOptions = function (car) {
    const md = G.M[car.m];
    const v = G.nominal(car);
    const out = [];
    const add = (type, extra) => {
      const d = G.JOBS[type];
      let inhouse = false, why = "";
      if (d.where === "wash") inhouse = G.facLevel("yikama") > 0;
      else if (d.where === "paint") inhouse = S.upg.paint > 0 && G.baysFree() > 0;
      else if (d.where === "pdr") { inhouse = true; if (!S.upg.pdr) why = "Boyasız göçük seti gerekir"; }
      else inhouse = G.baysFree() > 0;
      let cost = extra && extra.cost != null ? extra.cost : d.cost(v);
      let hours = extra && extra.hours ? extra.hours : d.hours;
      if (type === "clean" && inhouse) { const we = G.facEff("yikama"); cost = we.cost; hours = we.mins / 60; }
      else if (inhouse && type !== "pdr") cost *= 0.65;
      cost = R.r100(cost);
      // work: iş miktarı (dakika); dış serviste 1,6 kat sürer, içeride ustaların hızına bağlıdır
      const work = Math.round(hours * 60 * (inhouse || type === "clean" ? 1 : 1.6));
      const mins = Math.round(inhouse && type !== "clean" ? work / G.workRate() : work);
      out.push(Object.assign({ type, name: d.name, cost, mins, work, inhouse, why }, extra || {}));
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
    S.jobs.push({ id: G.nid(), carId: car.id, type: opt.type, issue: opt.issue || null, name: opt.name, left: opt.work, total: opt.work, inhouse: opt.inhouse, where, cost: opt.cost });
    return { ok: true };
  };

  function finishJob(j) {
    const car = S.cars.find(c => c.id === j.carId);
    S.jobs = S.jobs.filter(x => x !== j);
    if (!car) return;
    car.status = "lot";
    applyJob(car, j.type, j.inhouse, j.issue, false);
    G.feed(`Atölye: ${G.carTitle(car)} için ${j.name.toLocaleLowerCase("tr-TR")} bitti.`, "shop");
  }
  G.finishJob = finishJob;

  function applyJob(car, type, inhouse, issue, expected) {
    switch (type) {
      case "clean": car.clean = 100; car.inter = Math.min(100, car.inter + 3); break;
      case "service": car.motor = Math.min(95, car.motor + 22); break;
      case "engine": car.motor = Math.max(car.motor, 96); break;
      case "paint":
        car.body = Math.max(car.body, 95);
        car.painted += inhouse ? 1 : expected ? 2 : R.randi(1, 3);
        break;
      case "pdr": car.body = Math.min(100, car.body + 15); break;
      case "interior": car.inter = Math.max(car.inter, 95); break;
      case "restore":
        car.motor = Math.max(car.motor, 95); car.body = Math.max(car.body, 96); car.inter = Math.max(car.inter, 95);
        car.painted += 3; car.clean = 100;
        car.issues.forEach(is => { if (is.known && G.ISSUES[is.id].fix) is.fixed = true; });
        break;
      case "fix": { const is = car.issues.find(x => x.id === issue); if (is) is.fixed = true; break; }
    }
  }

  // Bir işin aracın piyasa değerini ne kadar artıracağı (temizlik değere değil müşteri ilgisine etki eder)
  G.jobGain = function (car, o) {
    const copy = JSON.parse(JSON.stringify(car));
    applyJob(copy, o.type, o.inhouse, o.issue, true);
    return G.value(copy, false) - G.value(car, false);
  };
  // Araç için en mantıklı sıradaki iş: önce bilinen arızalar, sonra değeri maliyetinden çok artıran iş, sonra temizlik
  G.recommendJob = function (car) {
    if (car.status !== "lot") return null;
    const opts = G.jobOptions(car).filter(o => !o.why || o.why === "Kasada yeterli para yok");
    let best = null, bestNet = 0;
    for (const o of opts) {
      if (o.type === "clean") continue;
      const net = G.jobGain(car, o) - o.cost;
      const score = o.type === "fix" ? net + 1e9 : net;
      if (net > o.cost * 0.15 && score > bestNet) { bestNet = score; best = o; }
    }
    if (best) return best;
    if (car.clean < 70) return opts.find(o => o.type === "clean") || null;
    return null;
  };

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
    return G.CTYPES.filter(t => t.prestige <= L.prestige + (S.rep >= 75 ? 1 : 0) + G.presEff().gate && Object.keys(t.segs).some(G.segUnlocked));
  }

  G.spawnCustomer = function (forceType) {
    const types = forceType ? [G.CT[forceType]] : customerTypes();
    let boost = {};
    for (const e of S.events) { const d = G.EVENTS.find(x => x.id === e.id); if (d && d.cust) Object.assign(boost, d.cust); }
    if (G.partyActive()) Object.assign(boost, { patron: 4, doktor: 2.5, hizli: 2.5 });
    const ultraListed = G.ultraListed();
    const t = R.wpick(types, x => (x.ultra ? (ultraListed ? 2.5 : 0) : x.w) * (boost[x.id] || 1));
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
      const looks = 0.62 + 0.25 * car.clean / 100 + (car.show ? 0.35 : 0) + (car.clean >= 80 ? G.facEff("yikama").shine : 0) + G.facEff("otopark").shine;
      const picky = Math.max(0.2, 1 - t.picky * (0.04 * car.painted + 0.09 * car.replaced + (car.tramer > 0 ? 0.05 : 0)));
      const fit = 0.55 + 0.45 * Math.min(1, car.price / (c.budget * 0.6));
      const lux = (md.seg === "luks" || md.seg === "spor") && !car.show ? 0.6 : 1;
      if (md.seg === "ultra" && c.type !== "milyarder") continue;
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
    c.max = R.r1k(Math.min(c.budget * 1.08, mv * R.rand(1.0, 1.08) * (car.show ? 1.03 : 1) * (1 + S.rep / 1000) * G.facEff("galeri").pay * G.presEff().pay));
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
    if (G.M[car.m].seg === "ultra") S.stats.ultraSold = (S.stats.ultraSold || 0) + 1;
    if (bySales) {
      const com = R.r100(price * 0.015);
      S.money -= com;
      S.today.commission += com;
      S.today.expenses += com;
    }
    G.addXp(Math.round(20 + Math.max(0, profit) / 2500) * (wholesale ? 0.5 : 1));
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
    if (cand.role === "usta") cand.energy = 100;
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
  // ---- Lüks alışveriş ----
  G.owns = id => (S.lux || []).includes(id);
  G.luxOwned = () => (S.lux || []).map(id => G.LX[id]).filter(Boolean);
  G.prestige = () => G.luxOwned().reduce((a, x) => a + x.pres, 0);
  // Şöhretin etkileri: müşterinin ödemeye razı olduğu fiyat, zengin müşteri kapısı, günlük itibar
  G.presEff = function (p) {
    if (p == null) p = G.prestige();
    return { pay: 1 + Math.min(0.06, p * 0.0002), gate: p >= 150 ? 2 : p >= 50 ? 1 : 0, rep: Math.min(0.5, p / 400) };
  };
  // geri satış ödenen fiyat üzerinden hesaplanır (fiyatlar sonradan değişse de)
  G.luxPaid = x => (S.luxPaid && S.luxPaid[x.id]) || x.price;
  G.luxResale = x => R.r1k(G.luxPaid(x) * G.LUX_CATS[x.cat].resale);
  G.luxValue = () => G.luxOwned().reduce((a, x) => a + G.luxResale(x), 0);
  G.itemUpkeep = x => Math.round(x.price * (x.upkeep || G.LUX_CATS[x.cat].upkeep));
  G.luxUpkeep = () => G.luxOwned().reduce((a, x) => a + G.itemUpkeep(x), 0);
  // Yatırımların beklenen günlük geliri ve net kazancı
  G.investIncome = () => G.luxOwned().reduce((a, x) => a + (x.income || 0), 0);
  G.investNet = () => G.luxOwned().filter(x => x.income).reduce((a, x) => a + x.income - G.itemUpkeep(x), 0);
  G.teamName = () => (S.name.trim().split(/\s+/)[0] || "Patron") + "spor";
  // Gün sonunda yatırım gelirleri; futbol takımının geliri maç sonucuna göre değişir
  G.collectInvestments = function () {
    let total = 0;
    const news = [];
    for (const x of G.luxOwned()) {
      if (!x.income) continue;
      let inc = x.income * R.rand(0.9, 1.1);
      if (x.football) {
        const r = Math.random();
        const g1 = R.randi(0, 4), g2 = R.randi(0, 3);
        let res, m;
        if (r < 0.45) { res = "kazandı"; m = 1.4; } else if (r < 0.75) { res = "berabere kaldı"; m = 1; } else { res = "kaybetti"; m = 0.6; }
        const a = res === "kazandı" ? Math.max(g1, g2 + 1) : res === "kaybetti" ? Math.min(g1, Math.max(0, g2 - 1)) : g2;
        const b = res === "kazandı" ? Math.min(g2, a - 1) : res === "kaybetti" ? a + 1 + R.randi(0, 1) : g2;
        inc = x.income * m * R.rand(0.9, 1.1);
        S.stats.wins = (S.stats.wins || 0) + (res === "kazandı" ? 1 : 0);
        news.push({ title: `${G.teamName()} ${res}: ${a}-${b}`, text: `Bilet, yayın ve sponsor geliri ${G.fmt(R.r1k(inc))}.` });
        if (res === "kazandı") G.addRep(0.5);
      }
      total += inc;
    }
    total = R.r1k(total);
    S.stats.investIncome = (S.stats.investIncome || 0) + total;
    return { total, news };
  };
  G.bestOf = cat => G.luxOwned().filter(x => x.cat === cat).sort((a, b) => b.price - a.price)[0] || null;
  G.buyLux = function (id) {
    const x = G.LX[id];
    if (!x || G.owns(id)) return { ok: false };
    if (S.level < x.lvl) return { ok: false, msg: `Bunun için oyuncu seviyen ${x.lvl} olmalı.` };
    if (S.money < x.price) return { ok: false, msg: "Kasada yeterli para yok." };
    S.money -= x.price;
    S.lux.push(id);
    if (!S.luxPaid) S.luxPaid = {};
    S.luxPaid[id] = x.price;
    G.addRep(Math.min(5, x.pres / 10));
    G.feed(`Patron lüks bir alışveriş yaptı: ${x.name} (${G.fmtS(x.price)}). Şöhret +${x.pres}.`, "level");
    G.checkQuests();
    return { ok: true };
  };
  G.sellLux = function (id) {
    const x = G.LX[id];
    if (!x || !G.owns(id)) return { ok: false };
    const v = G.luxResale(x);
    S.money += v;
    S.lux = S.lux.filter(k => k !== id);
    if (S.luxPaid) delete S.luxPaid[id];
    G.feed(`${x.name} satıldı: ${G.fmt(v)} kasaya girdi.`, "info");
    return { ok: true, price: v };
  };
  // Uçak: her sabah ithal ilanlar
  G.importCount = () => { const p = G.bestOf("ucak"); return p ? p.imports : 0; };
  G.importListings = function () {
    const n = G.importCount();
    if (!n) return 0;
    const segs = ["premium", "spor", "luks", "elektrik"].filter(G.segUnlocked);
    if (!segs.length) return 0;
    for (let i = 0; i < n * 2; i++) {
      const src = G.SELLERS.ithal;
      const car = G.makeCar(G.pickModel(m => segs.includes(m.seg)), src);
      const ap = G.value(car, false);
      const ask = R.r1k(ap * R.rand(src.ask[0], src.ask[1]));
      S.listings.unshift({ id: G.nid(), car, seller: "ithal", sellerName: R.pick(["Münih", "Milano", "Lyon", "Göteborg", "Stuttgart", "Seul"]) + " bayisi", ask, min: R.r1k(ask * R.rand(src.min[0], src.min[1])), rounds: src.rounds, last: ask, day: S.day, born: S.min, msgs: [], done: false });
    }
    return n * 2;
  };
  // Yat: haftada bir parti
  G.PARTY_COOLDOWN = 7;
  G.partyEff = () => { const y = G.bestOf("yat"); const t = y ? y.party : 0; return { traffic: 1.3 + 0.15 * t, rep: 2 * t }; };
  G.partyActive = () => !!(S.party && S.party.day === S.day);
  G.partyReady = () => !!G.bestOf("yat") && S.day - (S.lastParty == null ? -99 : S.lastParty) >= G.PARTY_COOLDOWN;
  G.throwParty = function () {
    if (!G.partyReady()) return { ok: false, msg: G.bestOf("yat") ? `Bir sonraki parti ${S.lastParty + G.PARTY_COOLDOWN}. günde verilebilir.` : "Önce bir yat almalısın." };
    const e = G.partyEff();
    const cost = R.r1k(150000 + G.bestOf("yat").party * 150000);
    if (S.money < cost) return { ok: false, msg: `Parti masrafı için kasada ${G.fmt(cost)} olmalı.` };
    S.money -= cost;
    S.lastParty = S.day;
    S.party = { day: S.day + 1 };
    S.stats.parties = (S.stats.parties || 0) + 1;
    G.addRep(e.rep);
    G.feed(`${G.bestOf("yat").name} üzerinde yat partisi verildi (${G.fmt(cost)}). İtibar +${e.rep}; yarın galeriye zengin müşteriler gelecek.`, "level");
    G.checkQuests();
    return { ok: true };
  };
  G.partyCost = () => { const y = G.bestOf("yat"); return y ? R.r1k(150000 + y.party * 150000) : 0; };

  // Günü bitir: saat 19:00'a kadar zamanı hızla ilerletir (atölye, müşteriler ve olaylar normal işler)
  G.skipDay = function () {
    let guard = 2000;
    while (!S.summary && guard-- > 0) G.minute();
  };

  // ---- Reklamla bedava araç ----
  // İlanlara bakarken ara sıra çıkar: oyuncunun seviyesine uygun, pahalı olmayan, ekspertizi temiz bir araç
  G.freeCarCap = () => R.r1k(250000 + S.level * 120000);
  G.makeFreeCar = function () {
    const cap = G.freeCarCap();
    let best = null;
    for (let i = 0; i < 40; i++) {
      const car = G.makeCar(G.pickModel(m => m.seg !== "luks" && m.seg !== "spor" && m.seg !== "klasik"), G.SELLERS.galeriden, { noIssues: true });
      const v = G.value(car, false);
      if (v <= cap && v >= cap * 0.35) { best = car; break; }
      if (v <= cap && (!best || v > G.value(best, false))) best = car;
    }
    if (!best) return null;
    best.issues = [];
    best.inspected = true;
    best.clean = 100;
    return best;
  };
  G.claimFreeCar = function (car) {
    if (!car || G.spaceLeft() <= 0) return false;
    car.buy = 0; car.spent = 0; car.status = "lot"; car.price = 0;
    S.cars.push(car);
    S.stats.freeCars = (S.stats.freeCars || 0) + 1;
    G.feed(`Reklam hediyesi: ${G.carTitle(car)} bedavaya galerine geldi (değeri ${G.fmtS(G.value(car, false))}).`, "good");
    return true;
  };

  // ---- Ultra lüks ilanlar ----
  G.ULTRA_CHANCE = 0.12; // her sabah ultra lüks ilan çıkma ihtimali (gün içinde her ilan saatinde %3 daha): ortalama 4 günde bir
  G.ultraListed = () => S.cars.some(c => c.price > 0 && c.status === "lot" && G.M[c.m].seg === "ultra");
  G.ultraListing = function (quiet) {
    if (S.level < G.SEGMENTS.ultra.lvl) return null;
    const md = R.pick(G.MODELS.filter(m => m.seg === "ultra"));
    const src = G.SELLERS.miras;
    const car = G.makeCar(md, src);
    const old = G.YEAR - car.year > 30;
    car.km = old ? R.randi(15, 90) * 1000 : R.randi(1, 12) * 1000 + R.randi(0, 999);
    car.motor = R.randi(80, 99); car.body = R.randi(82, 99); car.inter = R.randi(80, 99);
    car.painted = 0; car.replaced = 0; car.tramer = 0;
    car.issues = car.issues.filter(is => is.id !== "km" && is.id !== "sel");
    car.color = R.pick(md.id === "volta-speedster" || md.id === "anka-prototip" ? ["Kırmızı", "Bordo", "Gümüş", "Beyaz"] : ["Siyah", "Kırmızı", "Sarı", "Turuncu", "Beyaz", "Gri"]);
    const ap = G.value(car, false);
    const ask = R.r1k(ap * R.rand(src.ask[0], src.ask[1]));
    const l = { id: G.nid(), car, seller: "miras", sellerName: R.pick(["Miras satışı", "Özel koleksiyon", "Müzayede evi", "Yurt dışı koleksiyoner"]), ask, min: R.r1k(ask * R.rand(src.min[0], src.min[1])), rounds: src.rounds, last: ask, day: S.day, born: S.min, msgs: [], done: false, ultra: true };
    S.listings.unshift(l);
    if (!quiet) G.feed(`Nadir fırsat! Ultra lüks ${G.carTitle(car)} ilana düştü: ${G.fmtS(ask)}.`, "level");
    return l;
  };

  // ---- Pasif gelir ----
  // Oyundan çıkınca galeri çalışmaya devam eder: dönüşte en fazla G.OFFLINE_MAX_H saatlik gelir verilir
  G.OFFLINE_MAX_H = 8;
  G.OFFLINE_MIN_MIN = 5;
  G.passiveRate = function () {
    const h = S.history.slice(-7);
    const avg = h.length ? h.reduce((a, d) => a + Math.max(0, d.profit), 0) / h.length : 0;
    const base = Math.max(2000 * S.level, avg * 0.12);
    const sales = G.staffOf("satis").length * 0.15;
    const fame = Math.min(0.5, G.prestige() * 0.001);
    const invest = R.r100(Math.max(0, G.investNet()) * 0.25);
    return { base: R.r100(base), sales, fame, invest, total: R.r100(base * (1 + sales + fame) + invest), avg: R.r1k(avg) };
  };
  G.offlineReward = function (ms) {
    const mins = ms / 60000;
    if (!(mins >= G.OFFLINE_MIN_MIN)) return null;
    const hours = Math.min(G.OFFLINE_MAX_H, mins / 60);
    return { away: mins, hours, amount: R.r100(G.passiveRate().total * hours), capped: mins / 60 > G.OFFLINE_MAX_H };
  };
  G.claimOffline = function (o, mult) {
    if (!o || o.claimed) return;
    o.claimed = true;
    const v = o.amount * (mult || 1);
    S.money += v;
    S.stats.passive = (S.stats.passive || 0) + v;
    G.feed(`Pasif gelir: sen yokken galeri ${G.fmt(v)} kazandırdı.`, "good");
  };

  // ---- Reklam ödülleri ----
  // Ödüllü reklamlar yalnızca oyuncu isteyince açılır; her ödülün ve günün bir sınırı vardır.
  G.AD_CAPS = { rush: 3, boost: 2, bonus: 1, inspect: 3, tea: 3, ilan: 2, offline: 2, freecar: 2 };
  G.AD_DAILY = 12;
  function adDay() {
    if (!S.adv) S.adv = { day: 0, n: {}, total: 0, lastInter: 0, lastWatch: 0 };
    if (S.adv.day !== S.day) { S.adv.day = S.day; S.adv.n = {}; S.adv.total = 0; }
    return S.adv;
  }
  G.adLeft = function (kind) {
    const a = adDay();
    return Math.max(0, Math.min(G.AD_CAPS[kind] - (a.n[kind] || 0), G.AD_DAILY - a.total));
  };
  G.adUsed = function (kind) {
    const a = adDay();
    a.n[kind] = (a.n[kind] || 0) + 1;
    a.total++;
    a.lastWatch = S.day;
  };
  G.boostActive = () => !!(S.boost && S.boost.day === S.day && S.min < S.boost.until);
  G.reward = {
    rush(job) { if (!S.jobs.includes(job)) return false; finishJob(job); return true; },
    boost() { S.boost = { day: S.day, until: Math.min(G.CLOSE, S.min + 120) }; G.feed("Sosyal medya kampanyası başladı: 2 saat boyunca iki kat müşteri.", "good"); return true; },
    bonus(sm) { if (!sm || sm.bonusTaken) return false; sm.bonusTaken = true; S.money += sm.bonus; G.feed(`Sponsor bonusu: ${G.fmt(sm.bonus)} kasaya girdi.`, "good"); return true; },
    inspect(car) { return G.inspect(car, true); },
    ilan() { G.addListings(4, "Reklam ödülü: piyasaya 4 yeni ilan geldi."); return true; },
    tea(c) { if (!S.custs.includes(c)) return false; c.patience = c.patMax; c.rounds++; c.msgs.push({ who: "them", text: "Çay için teşekkürler, acelem yok. Konuşalım." }); return true; }
  };
  // Ara reklam: yalnızca gün geçişinde, 4. günden sonra, en az 3 günde bir ve o gün ödüllü reklam izlenmediyse
  G.interstitialDue = function (endedDay) {
    const a = adDay();
    return endedDay >= 4 && endedDay - (a.lastInter || 0) >= 3 && a.lastWatch < endedDay;
  };
  G.interstitialShown = function (endedDay) { adDay().lastInter = endedDay; };

  // ---- Sade ekran yardımcıları ----
  G.condScore = car => Math.round(G.cond(car, false));
  G.condLabel = v => (v >= 85 ? "Çok iyi" : v >= 70 ? "İyi" : v >= 55 ? "Orta" : v >= 40 ? "Bakım ister" : "Kötü");
  G.PRICE_PRESETS = [
    { k: "hizli", name: "Hızlı satış", f: 0.97, note: "Müşteri çabuk gelir" },
    { k: "dengeli", name: "Dengeli", f: 1.05, note: "Önerilen" },
    { k: "karli", name: "Kârlı", f: 1.12, note: "Bekleyebilir" }
  ];
  G.dealLabel = function (ask, value) {
    const r = ask / value;
    return r <= 0.93 ? { t: "Fırsat", c: "good" } : r <= 1.04 ? { t: "Normal fiyat", c: "" } : { t: "Pahalı", c: "bad" };
  };
  // "Şimdi ne yapmalı?" listesi: oyuncuya sıradaki adımları gösterir
  G.todos = function () {
    const out = [];
    const offers = S.custs.filter(c => c.state === "offer");
    for (const c of offers.slice(0, 2)) {
      const car = G.custCar(c);
      if (car) out.push({ text: `${c.name}, ${G.carName(car)} için ${G.fmtS(c.offer)} teklif ediyor`, a: "cust", id: c.id, btn: "Cevap ver", hot: true });
    }
    const noPrice = S.cars.filter(c => c.status === "lot" && !c.price);
    if (noPrice.length) out.push({ text: noPrice.length === 1 ? `${G.carName(noPrice[0])} aracının fiyat etiketi yok; müşteri bakmaz` : `${noPrice.length} aracın fiyat etiketi yok; müşteri bakmaz`, a: "car", id: noPrice[0].id, btn: "Fiyat koy" });
    const unins = S.cars.find(c => c.status === "lot" && !c.inspected);
    if (unins) out.push({ text: `${G.carName(unins)} ekspertizden geçmedi; gizli kusuru olabilir`, a: "car", id: unins.id, btn: "Ekspertiz" });
    const prep = S.cars.find(c => c.status === "lot" && G.recommendJob(c));
    if (prep) { const o = G.recommendJob(prep); out.push({ text: `${G.carName(prep)}: ${o.name.toLocaleLowerCase("tr-TR")} değerini artırır`, a: "car", id: prep.id, btn: "Hazırla" }); }
    if (G.spaceLeft() > 0 && S.listings.some(l => !l.done) && S.money > 150000) out.push({ text: `${G.spaceLeft()} boş park yerin var; piyasada ${S.listings.filter(l => !l.done).length} ilan seni bekliyor`, a: "tab", v: "piyasa", btn: "İlanlara bak" });
    if (S.auction && !S.auction.finished && S.auction.idx === 0 && !S.auction.live && G.spaceLeft() > 0) out.push({ text: `Günün ihalesinde ${S.auction.lots.length} araç var`, a: "auction", btn: "İhaleye gir" });
    const tired = G.ustas().find(u => !u.resting && u.energy < 35);
    if (tired && G.restFree() > 0) out.push({ text: `${tired.name} yorgun (enerji ${Math.round(tired.energy)}); çardakta dinlenirse dinç döner`, a: "rest", id: tired.id, btn: "Çardağa gönder" });
    const fac = Object.keys(G.FACILITIES).map(k => ({ k, nx: G.facNext(k) })).filter(x => x.nx && x.nx.req <= S.level && S.money >= x.nx.cost * 2.5).sort((a, b) => a.nx.cost - b.nx.cost)[0];
    if (fac) out.push({ text: `${G.FACILITIES[fac.k].name} ${fac.nx.n}. seviyeye yükseltilebilir (${G.fmtS(fac.nx.cost)})`, a: "fac", v: fac.k, btn: "Bak" });
    const lux = G.LUXURY.filter(x => !G.owns(x.id) && x.lvl <= S.level && S.money >= x.price * 3).sort((a, b) => a.price - b.price)[0];
    if (lux && !G.luxOwned().length) out.push({ text: `Kasan dolu: kendine bir ${lux.name.toLocaleLowerCase("tr-TR")} alabilirsin (${G.fmtS(lux.price)})`, a: "tab", v: "patron", btn: "Lüks" });
    if (G.partyReady()) out.push({ text: `Yatında parti verebilirsin; yarın zengin müşteriler gelir`, a: "party", btn: "Parti ver" });
    const next = G.LOCS[S.loc + 1];
    if (next && S.level >= next.lvl && S.money >= next.cost * 1.3) out.push({ text: `${next.name} adresine taşınabilirsin`, a: "tab", v: "patron", btn: "Bak" });
    return out.slice(0, 2); // en önemli iki adım (sıra: teklifler, fiyat, ekspertiz, hazırlık...)
  };

  G.activeQuests = n => G.QUESTS.filter(q => !S.quests.includes(q.id)).slice(0, n || 3);

  // ---- Zaman ----
  G.minute = function () {
    S.min++;
    if (G.LISTING_DROPS.includes(S.min)) { const n = R.randi(2, 3); G.addListings(n, `Piyasaya ${n} yeni ilan düştü.`); if (R.chance(0.03)) G.ultraListing(); }
    // galeride etiketli ultra lüks araç varsa milyarder koleksiyoncular ara sıra uğrar
    if (G.ultraListed() && !S.custs.some(c => c.type === "milyarder") && R.chance(1 / 240)) G.spawnCustomer("milyarder");
    // atölye
    ustaMinute();
    for (const j of S.jobs.slice()) { j.left -= G.jobRate(j); if (j.left <= 0) finishJob(j); }
    // müşteri gelişi
    const L = G.loc();
    const listed = S.cars.filter(c => c.status === "lot" && c.price > 0).length;
    const stock = listed ? Math.min(1.5, 0.6 + listed * 0.12) : 0.25;
    const perHour = 1.15 * L.traffic * G.ADS[S.ads].mult * (0.45 + S.rep / 90) * G.trafficMult() * stock * (G.boostActive() ? 2 : 1) * G.facEff("galeri").traffic;
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
    // kişisel lüks eşyaların bakımı işletme netine sayılmaz ama kasadan düşer
    const luxUp = G.luxUpkeep();
    S.money -= luxUp;
    const inv = G.collectInvestments();
    S.money += inv.total;
    const T = S.today;
    const net = T.profit - T.expenses - T.spentWork;
    S.history.push({ day: S.day, revenue: T.revenue, profit: T.profit, expenses: T.expenses + T.spentWork, net, sales: T.sales });
    if (S.history.length > 30) S.history.shift();
    const summary = {
      day: S.day, today: Object.assign({}, T), net,
      lines: [["Kira", L.rent], ["Maaşlar", salaries], ["Reklam", ads], ["Sigorta ve otopark", insurance], ["Kredi faizi", interest], ["Ek hesap faizi", overdraft], ["Muhasebe tasarrufu", -saved], ["Lüks bakım masrafı (kişisel)", luxUp], ["Yatırım gelirleri (kişisel)", -inv.total]].filter(x => x[1]),
      lost: S.lost.slice(0, 6), news: [], invest: inv.total
    };
    summary.news.push(...inv.news);

    // yeni gün
    S.day++;
    S.min = OPEN;
    S.today = G.blankDay();
    S.lost = [];
    for (const c of S.custs) if (c.state === "offer") G.addRep(-0.2);
    S.custs = [];
    for (const u of G.ustas()) { u.resting = false; u.buff = null; u.energy = 100; } // gece herkes dinlenir
    const we = G.facEff("yikama");
    const wash = G.skillSum("yikama") * 4 + we.auto;
    let washed = 0;
    for (const car of S.cars) {
      car.days++;
      car.clean = Math.max(0, car.clean - (car.show ? 4 : R.randi(8, 13)) * we.decay);
      if (washed < wash && car.status === "lot") { car.clean = 100; washed++; }
    }
    for (const k of Object.keys(S.trend)) S.trend[k] = R.clamp(S.trend[k] * R.rand(0.985, 1.016) + (1 - S.trend[k]) * 0.05, 0.85, 1.18);
    S.events = S.events.filter(e => e.until >= S.day);
    if (G.ADS[S.ads].rep) G.addRep(G.ADS[S.ads].rep);
    G.addRep(G.presEff().rep);
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
    const imp = G.importListings();
    if (S.level >= G.SEGMENTS.ultra.lvl && R.chance(G.ULTRA_CHANCE)) { const u = G.ultraListing(true); if (u) summary.news.push({ title: "Nadir fırsat", text: `Piyasaya ultra lüks bir araç düştü: ${G.carTitle(u.car)}. Kârı yüksek ama pahalı.` }); }
    if (imp) summary.news.push({ title: "Yurt dışından ilanlar", text: `Uçağın sayesinde bugün piyasada ${imp} ithal ilan var.` });
    if (G.partyActive()) summary.news.push({ title: "Yat partisinin etkisi", text: "Bugün galeriye zengin müşteri akını bekleniyor." });
    G.newAuction();
    if (S.day - S.candDay >= 5) G.newCandidates();
    G.checkQuests();
    summary.bonus = R.r1k(R.clamp(Math.max(0, T.profit) * 0.3, 25000 + S.level * 5000, 150000 + S.level * 60000));
    S.summary = summary;
    S.paused = true;
  };

  function triggerEvent() {
    const pool = G.EVENTS.filter(e => !S.events.some(x => x.id === e.id));
    const e = R.pick(pool);
    let text = e.text;
    if (e.instant === "dolu") {
      const cov = G.facEff("otopark").covered;
      const hit = S.cars.filter(c => !c.show && c.status === "lot" && !R.chance(cov));
      hit.forEach(c => { c.body = Math.max(10, c.body - R.randi(6, 14)); });
      text = hit.length ? `Açıkta duran ${hit.length} aracın kaportası göçük doldu. Boyasız göçük düzeltme işe yarar.` : "Neyse ki açıkta araç yoktu.";
    } else if (e.instant === "hirsiz") {
      if (S.upg.security) text = "Kameralar ve alarm hırsızları kaçırdı. Hasar yok.";
      else if (G.facEff("otopark").light && R.chance(0.5)) text = "Otoparkın ışıkları hırsızları caydırdı. Hasar yok.";
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
