/* Galeri Patronu — oyuncunun spor kulüpleri (futbol, basketbol)
 * Kulüp satın alınınca adı, kısa adı ve logosu (şekil, iki renk, amblem) seçilir; sonradan değiştirilebilir.
 * Logo lüks kartında ve maç sonuçlarında kulüp adıyla görünür. */
(function (G) {
  "use strict";
  const UI = G.UI;
  const S = () => G.S;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  G.CLUB_COLORS = [["Kırmızı", "#c8282d"], ["Sarı", "#f2c218"], ["Lacivert", "#1f2f5a"], ["Mavi", "#2f6fd0"], ["Yeşil", "#1d8549"], ["Siyah", "#17191d"], ["Beyaz", "#ffffff"], ["Bordo", "#6e1f2b"], ["Turuncu", "#e2742c"], ["Mor", "#6b3fa0"]];
  const SHAPES = {
    kalkan: ["Kalkan", c => `<path d="M50 3 L91 15 V47 C91 73 72 89 50 97 C28 89 9 73 9 47 V15 Z" fill="${c.c1}" stroke="${c.c2}" stroke-width="5"/>`],
    daire: ["Daire", c => `<circle cx="50" cy="50" r="45" fill="${c.c1}" stroke="${c.c2}" stroke-width="5"/><circle cx="50" cy="50" r="37" fill="none" stroke="${c.c2}" stroke-width="1.6" opacity=".7"/>`],
    altigen: ["Altıgen", c => `<path d="M50 4 L90 27 V73 L50 96 L10 73 V27 Z" fill="${c.c1}" stroke="${c.c2}" stroke-width="5"/>`],
    elmas: ["Elmas", c => `<path d="M50 3 L95 50 L50 97 L5 50 Z" fill="${c.c1}" stroke="${c.c2}" stroke-width="5"/>`]
  };
  const ICONS = {
    top: ["Top", (c, kind) => kind === "basket"
      ? `<circle cx="50" cy="42" r="15" fill="${c.c2}"/><path d="M35 42 H65 M50 27 V57 M39 31 Q47 42 39 53 M61 31 Q53 42 61 53" stroke="${c.c1}" stroke-width="2" fill="none"/>`
      : `<circle cx="50" cy="42" r="15" fill="${c.c2}"/><path d="M50 34 L57 39 L54 47 H46 L43 39 Z" fill="${c.c1}"/><path d="M50 34 V27 M57 39 L64 36 M54 47 L58 54 M46 47 L42 54 M43 39 L36 36" stroke="${c.c1}" stroke-width="1.6"/>`],
    yildiz: ["Yıldız", c => `<path d="M50 24 L55.3 36.7 L69 37.8 L58.6 46.8 L61.8 60.2 L50 53 L38.2 60.2 L41.4 46.8 L31 37.8 L44.7 36.7 Z" fill="${c.c2}"/>`],
    tac: ["Taç", c => `<path d="M32 55 L30 30 L41 41 L50 25 L59 41 L70 30 L68 55 Z" fill="${c.c2}"/><rect x="32" y="56" width="36" height="5" rx="1" fill="${c.c2}"/>`],
    simsek: ["Şimşek", c => `<path d="M55 22 L37 46 H49 L44 62 L64 37 H52 Z" fill="${c.c2}"/>`],
    kartal: ["Kartal", c => `<path d="M50 30 C44 30 41 34 41 38 L22 30 L28 40 L19 40 L32 47 L26 50 L41 50 L46 58 H54 L59 50 L74 50 L68 47 L81 40 L72 40 L78 30 L59 38 C59 34 56 30 50 30 Z" fill="${c.c2}"/><circle cx="52" cy="35" r="1.6" fill="${c.c1}"/>`],
    aslan: ["Aslan", c => `<circle cx="50" cy="42" r="15" fill="${c.c2}"/><circle cx="50" cy="43" r="9" fill="${c.c1}"/><circle cx="46.5" cy="41" r="1.3" fill="${c.c2}"/><circle cx="53.5" cy="41" r="1.3" fill="${c.c2}"/><path d="M47 47 Q50 49.5 53 47" stroke="${c.c2}" stroke-width="1.4" fill="none"/>`]
  };

  // Logonun iç çizimi (viewBox 0 0 100 100): şekil, amblem ve kısa ad
  UI.clubLogoInner = function (c, kind) {
    const sh = (SHAPES[c.shape] || SHAPES.kalkan)[1](c);
    const ic = (ICONS[c.icon] || ICONS.yildiz)[1](c, kind);
    return sh + ic + `<text data-club-code x="50" y="80" text-anchor="middle" font-family="Saira Condensed, Arial Narrow, sans-serif" font-weight="800" font-size="17" letter-spacing="1" fill="${c.c2}">${esc(c.code || "")}</text>`;
  };
  UI.clubLogo = (c, kind, size) => `<svg class="club-logo" viewBox="0 0 100 100" width="${size || 64}" height="${size || 64}" role="img" aria-label="${esc(c.name)} logosu">${UI.clubLogoInner(c, kind)}</svg>`;

  // ---- Kulüp düzenleme penceresi ----
  UI.openClub = function (id, isNew) {
    const c = G.clubOf(id);
    UI.clubDraft = { id, isNew: !!isNew, name: c.name, code: c.code, shape: c.shape, icon: c.icon, c1: c.c1, c2: c.c2 };
    UI.open("club", id);
  };
  const opt = (k, v, label, on, extra) => `<button type="button" class="club-opt ${on ? "on" : ""}" data-a="club-set" data-k="${k}" data-v="${v}" aria-pressed="${on}" ${extra || ""}>${label}</button>`;
  UI.SHEETS.club = function (id) {
    const d = UI.clubDraft;
    if (!d || d.id !== id) return null;
    const x = G.LX[id], kind = x.club;
    const sw = (k) => G.CLUB_COLORS.map(([n, hex]) => opt(k, hex, `<span class="sw" style="background:${hex}"></span>`, d[k] === hex, `aria-label="${n}" title="${n}"`)).join("");
    return UI.head(d.isNew ? "Kulübünü kur" : "Kulübü düzenle", d.isNew ? `Hayırlı olsun! ${esc(x.name)} artık senin. Adını ve logosunu belirle.` : esc(x.name)) + `
      <div class="club-prev">${UI.clubLogo(d, kind, 120)}<div><b id="clubPrevName">${esc(d.name)}</b><p class="small muted">${kind === "basket" ? "Basketbol" : "Futbol"} · Süper Lig</p></div></div>
      <label class="fsel"><span>Kulüp adı</span><input id="clubName" type="text" maxlength="24" value="${esc(d.name)}" autocomplete="off"></label>
      <label class="fsel"><span>Kısa ad (logoda yazar)</span><input id="clubCode" type="text" maxlength="4" value="${esc(d.code)}" autocomplete="off"></label>
      <div class="club-row"><span>Şekil</span><div class="club-opts">${Object.entries(SHAPES).map(([k, [n]]) => opt("shape", k, n, d.shape === k)).join("")}</div></div>
      <div class="club-row"><span>Amblem</span><div class="club-opts">${Object.entries(ICONS).map(([k, [n]]) => opt("icon", k, n, d.icon === k)).join("")}</div></div>
      <div class="club-row"><span>Ana renk</span><div class="club-opts">${sw("c1")}</div></div>
      <div class="club-row"><span>İkinci renk</span><div class="club-opts">${sw("c2")}</div></div>
      <button class="btn pri block" type="button" data-a="club-save">Kaydet</button>`;
  };
  function readInputs() {
    const d = UI.clubDraft, n = document.getElementById("clubName"), c = document.getElementById("clubCode");
    if (n) d.name = n.value.trim().slice(0, 24) || d.name;
    if (c) d.code = c.value.trim().toLocaleUpperCase("tr-TR").slice(0, 4);
  }
  UI.ACT["club-set"] = el => { readInputs(); UI.clubDraft[el.dataset.k] = el.dataset.v; UI.renderSheet(true); };
  UI.ACT["club-save"] = () => {
    readInputs();
    const d = UI.clubDraft;
    S().clubs = S().clubs || {};
    // yalnızca ad ve logo değişir; kadro, teknik kadro ve maç kaydı korunur
    S().clubs[d.id] = Object.assign(S().clubs[d.id] || {}, { name: d.name, code: d.code, shape: d.shape, icon: d.icon, c1: d.c1, c2: d.c2 });
    G.feed(`${d.name} kulübün hazır! Maç sonuçları gün sonu raporunda.`, "good");
    UI.clubDraft = null;
    G.save && G.save();
    UI.sig = "";
    UI.render();
    UI.open("clubm", d.id);
  };
  UI.ACT["club-edit"] = el => UI.openClub(el.dataset.v, false);
  // ad ve kısa ad yazılırken önizleme anında değişir
  document.addEventListener("input", e => {
    if (e.target.id === "clubName") { const t = document.getElementById("clubPrevName"); if (t) t.textContent = e.target.value; }
    if (e.target.id === "clubCode") { const t = document.querySelector(".club-prev [data-club-code]"); if (t) t.textContent = e.target.value.toLocaleUpperCase("tr-TR").slice(0, 4); }
  });
})(window.G);

/* ---- Kulüp yönetimi: kadro, transfer, teknik kadro, maç ---- */
(function (G) {
  "use strict";
  const UI = G.UI, R = G.R;
  const S = () => G.S;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  // squad: maçta oynayan sayı (güç en iyi bu kadar oyuncunun ortalaması), max: kadro sınırı,
  // base: 70 güçlü oyuncunun bonservisi, prize: galibiyet / beraberlik primi, staffBase: teknik kadro ilk seviye ücreti
  G.CLUB = {
    futbol: { squad: 11, max: 22, start: 16, base: 3e6, prize: { w: 6e6, d: 1.5e6 }, staffBase: 6e6, sport: "Futbol", pos: ["Kaleci", "Defans", "Defans", "Orta saha", "Orta saha", "Forvet"] },
    basket: { squad: 5, max: 13, start: 10, base: 1.2e6, prize: { w: 2.5e6, d: 0 }, staffBase: 2.5e6, sport: "Basketbol", pos: ["Oyun kurucu", "Şutör", "Forvet", "Uzun forvet", "Pivot"] }
  };
  G.CLUB_STAFF = {
    td: { name: "Teknik direktör", max: 5, power: 1.6, desc: "Taktik ve oyun planı" },
    kond: { name: "Kondisyoner", max: 5, power: 0.8, desc: "Oyuncular maç sonuna kadar dayanır" },
    scout: { name: "Scout ekibi", max: 3, power: 0, desc: "Transfer listesine daha iyi oyuncular gelir" }
  };
  G.playerPrice = (r, kind) => R.r1k(G.CLUB[kind].base * Math.pow(1.13, r - 70));
  // Dünya yıldızları: tamamen hayali isimler (gerçek kişilerin adı kullanılmaz); transfer listesine ara sıra düşer
  G.STARS = {
    futbol: [
      ["Leandro Vesti", "Arjantin", "Forvet", 96, 34], ["Duarte Velaso", "Portekiz", "Forvet", 93, 36], ["Kenzo Darbois", "Fransa", "Forvet", 95, 26],
      ["Sverre Halvorsen", "Norveç", "Forvet", 94, 25], ["Caio Brandão", "Brezilya", "Forvet", 90, 31], ["Karim Nassef", "Mısır", "Forvet", 92, 30],
      ["Joris Vandermeer", "Belçika", "Orta saha", 91, 32], ["Thiago Arcanjo", "Brezilya", "Forvet", 93, 25], ["Oliver Ashworth", "İngiltere", "Orta saha", 93, 23],
      ["Harvey Lindell", "İngiltere", "Forvet", 92, 30], ["Tomasz Wierzbicki", "Polonya", "Forvet", 90, 33], ["Ivo Matkovac", "Hırvatistan", "Orta saha", 89, 34],
      ["Daan Verhoeven", "Hollanda", "Defans", 91, 31], ["Lucien Marchetti", "Fransa", "Kaleci", 91, 32], ["Erik Söderlund", "İsveç", "Forvet", 88, 35]
    ],
    basket: [
      ["Marcus Wellford", "ABD", "Forvet", 95, 34], ["Devon Kittredge", "ABD", "Oyun kurucu", 95, 32], ["Jalen Ashby", "ABD", "Forvet", 93, 31],
      ["Nikos Papadakis", "Yunanistan", "Uzun forvet", 96, 29], ["Matej Kovač", "Slovenya", "Oyun kurucu", 96, 25], ["Dušan Ristović", "Sırbistan", "Pivot", 97, 29],
      ["Ibrahim Ndoye", "Senegal", "Pivot", 93, 28], ["Théo Lavigne", "Fransa", "Pivot", 94, 22], ["Darnell Okafor", "Kanada", "Şutör", 95, 26],
      ["Terrence Holloway", "ABD", "Forvet", 93, 27], ["Quentin Mayes", "ABD", "Şutör", 92, 24], ["Corey Banfield", "ABD", "Forvet", 91, 31]
    ]
  };
  // önceki sürümdeki yıldız isimleri kayıtlı oyunlarda yeni hayali isimlere çevrilir (aynı sıra)
  const OLD_STARS = ["Lyonel Mezzi", "Kristiano Ronaldu", "Kilian Mbapa", "Erlin Holand", "Neimar Junyor", "Muhammed Salahi", "Kevin Dö Broyne", "Vinisius Junyor", "Jud Bellinghem", "Hari Keyn", "Robert Levandovsi", "Lukas Modrik", "Virjil Van Dayk", "Tibo Kurtoa", "Slatan Ibrahimo",
    "LöBron Ceyms", "Stefan Köri", "Kevin Durent", "Yannis Antetokumpo", "Luka Donçik", "Nikola Yokiç", "Coel Embid", "Viktor Vembanyama", "Şey Gilgus Aleksandr", "Ceysın Teytım", "Entoni Edvırds", "Cimmi Batlır"];
  const renameOld = list => { const all = G.STARS.futbol.concat(G.STARS.basket); for (const p of list || []) { const k = OLD_STARS.indexOf(p.name); if (k >= 0) p.name = all[k][0]; } };
  G.STAR_PREMIUM = 2.5; // yıldızın bonservisi ve maaşı aynı güçteki oyuncunun bu katı
  G.pPrice = (p, kind) => R.r1k(G.playerPrice(p.r, kind) * (p.star ? G.STAR_PREMIUM : 1));
  G.playerWage = (p, kind) => R.r100(G.pPrice(p, kind) * 0.0015);
  const newPlayer = (kind, lo, hi) => ({ id: G.nid(), name: R.pick(G.FIRST_E) + " " + R.pick(G.LAST), pos: R.pick(G.CLUB[kind].pos), r: R.randi(lo, hi), age: R.randi(19, 33) });

  // kulübün eksik alanlarını tamamlar (eski kayıtlar ve yeni kulüp)
  G.clubData = function (id) {
    const x = G.LX[id], c = G.clubOf(id), k = G.CLUB[x.club];
    if (!c.players) c.players = Array.from({ length: k.start }, () => newPlayer(x.club, 56, 70));
    if (!c.staff) c.staff = { td: 0, kond: 0, scout: 0 };
    if (!c.rec) c.rec = { w: 0, d: 0, l: 0 };
    if (!c.starNames) { renameOld(c.players); if (c.market) renameOld(c.market.list); c.starNames = 2; }
    return c;
  };
  G.clubPower = function (id) {
    const x = G.LX[id], c = G.clubData(id), k = G.CLUB[x.club];
    const top = c.players.map(p => p.r).sort((a, b) => b - a).slice(0, k.squad);
    const avg = top.length ? top.reduce((a, b) => a + b, 0) / k.squad : 0; // eksik oyuncu gücü düşürür
    return Math.round((avg + c.staff.td * G.CLUB_STAFF.td.power + c.staff.kond * G.CLUB_STAFF.kond.power) * 10) / 10;
  };
  G.clubWages = id => { const x = G.LX[id]; return G.clubData(id).players.reduce((a, p) => a + G.playerWage(p, x.club), 0); };
  G.winChance = (id, opp) => 1 / (1 + Math.exp(-(G.clubPower(id) - opp) / 4));

  // her gün bir maç: rakip gücü 62–84; kazanınca gelir artar ve galibiyet primi gelir; maaşlar düşülür
  G.playMatch = function (x) {
    const c = G.clubData(x.id), k = G.CLUB[x.club];
    const opp = R.randi(62, 84);
    const p = G.winChance(x.id, opp);
    const r = Math.random();
    let res;
    if (x.club === "basket") res = r < p ? "w" : "l";
    else res = r < p * 0.8 ? "w" : r < p * 0.8 + 0.22 ? "d" : "l";
    c.rec[res]++;
    const m = res === "w" ? 1.3 : res === "d" ? 1 : 0.7;
    const prize = res === "w" ? k.prize.w : res === "d" ? k.prize.d : 0;
    const wages = G.clubWages(x.id);
    const inc = x.income * m * R.rand(0.92, 1.08) + prize - wages;
    let score;
    if (x.club === "basket") { const a = R.randi(72, 104), d = R.randi(1, 16); score = res === "w" ? `${a}-${a - d}` : `${a - d}-${a}`; }
    else { const a = R.randi(1, 4), d = R.randi(1, 2); score = res === "w" ? `${a}-${Math.max(0, a - d)}` : res === "d" ? `${a - 1}-${a - 1}` : `${Math.max(0, a - d)}-${a}`; }
    S().stats.wins = (S().stats.wins || 0) + (res === "w" ? 1 : 0);
    if (res === "w") G.addRep(0.4);
    const word = res === "w" ? "kazandı" : res === "d" ? "berabere kaldı" : "kaybetti";
    return { inc, news: { title: `${c.name} ${word}: ${score}`, text: `Rakip gücü ${opp}, bizim ${G.clubPower(x.id)}. ${prize ? `Galibiyet primi ${G.fmt(prize)}. ` : ""}Gelir ${G.fmt(R.r1k(x.income * m))}, maaşlar ${G.fmt(wages)}.` } };
  };

  // transfer listesi: her gün yenilenir; scout ekibi listeyi büyütür ve iyileştirir
  G.clubMarket = function (id) {
    const x = G.LX[id], c = G.clubData(id), s = S();
    if (!c.market || c.market.day !== s.day) {
      const sc = c.staff.scout;
      const list = Array.from({ length: 6 + sc * 2 }, () => newPlayer(x.club, 60 + sc * 3, 84 + sc * 4)).map(p => ({ ...p, r: Math.min(97, p.r) }));
      // ara sıra bir dünya yıldızı (scout ekibi ihtimali artırır); kadroda olan tekrar çıkmaz
      const have = new Set(c.players.filter(p => p.star).map(p => p.name));
      const pool = G.STARS[x.club].filter(st => !have.has(st[0]));
      if (pool.length && R.chance(0.3 + 0.15 * sc)) { const [name, nat, pos, r, age] = R.pick(pool); list.push({ id: G.nid(), name, nat, pos, r, age, star: true }); }
      c.market = { day: s.day, list };
    }
    return c.market.list;
  };
  G.clubBuy = function (id, pid) {
    const x = G.LX[id], c = G.clubData(id), k = G.CLUB[x.club];
    const p = G.clubMarket(id).find(q => q.id === pid);
    if (!p) return { ok: false };
    if (c.players.length >= k.max) return { ok: false, msg: `Kadro dolu (en fazla ${k.max} oyuncu). Önce birini sat.` };
    const price = G.pPrice(p, x.club);
    if (S().money < price) return { ok: false, msg: "Kasada yeterli para yok." };
    S().money -= price;
    c.players.push({ id: p.id, name: p.name, pos: p.pos, r: p.r, age: p.age, star: p.star, nat: p.nat });
    if (p.star) G.addRep(1);
    c.market.list = c.market.list.filter(q => q !== p);
    G.feed(`${c.name} transferi: ${p.name} (${p.pos}, güç ${p.r}) · ${G.fmt(price)}`, "good");
    return { ok: true };
  };
  G.clubSell = function (id, pid) {
    const x = G.LX[id], c = G.clubData(id);
    const p = c.players.find(q => q.id === pid);
    if (!p) return { ok: false };
    const back = R.r1k(G.pPrice(p, x.club) * 0.7);
    S().money += back;
    c.players = c.players.filter(q => q !== p);
    G.feed(`${p.name} satıldı: ${G.fmt(back)}`, "info");
    return { ok: true };
  };
  G.staffCost = (id, key) => { const x = G.LX[id], lv = G.clubData(id).staff[key]; return R.r1k(G.CLUB[x.club].staffBase * Math.pow(2.2, lv)); };
  G.clubStaffUp = function (id, key) {
    const c = G.clubData(id), st = G.CLUB_STAFF[key];
    if (c.staff[key] >= st.max) return { ok: false };
    const cost = G.staffCost(id, key);
    if (S().money < cost) return { ok: false, msg: "Kasada yeterli para yok." };
    S().money -= cost;
    c.staff[key]++;
    if (key === "scout" && c.market) c.market.day = -1; // liste hemen yenilensin
    return { ok: true };
  };

  // ---- Kulüp yönetim penceresi ----
  UI.clubTab = UI.clubTab || "kadro";
  UI.SHEETS.clubm = function (id) {
    const x = G.LX[id];
    if (!x || !G.owns(id)) return null;
    const c = G.clubData(id), k = G.CLUB[x.club], pw = G.clubPower(id);
    const tabs = [["kadro", "Kadro"], ["transfer", "Transfer"], ["teknik", "Teknik kadro"]];
    let html = UI.head(c.name, `${k.sport} · Kadro gücü <b>${pw}</b> · ${c.rec.w}G ${x.club === "basket" ? "" : c.rec.d + "B "}${c.rec.l}M`) + `
      <div class="club-top">${UI.clubLogo(c, x.club, 64)}<div class="small"><div>Maç başına galibiyet primi <b>${G.fmtS(k.prize.w)}</b>${k.prize.d ? `, beraberlik ${G.fmtS(k.prize.d)}` : ""}</div><div>Ortalama rakibe (73) karşı kazanma şansı <b>%${Math.round(G.winChance(id, 73) * 100)}</b></div><div class="muted">Günlük maaşlar ${G.fmtS(G.clubWages(id))} · ${c.players.length}/${k.max} oyuncu</div></div>
      <button class="btn sm" type="button" data-a="club-edit" data-v="${id}">Ad ve logo</button></div>
      <div class="segctl" role="group">${tabs.map(([t, l]) => `<button type="button" data-a="club-tab" data-v="${t}" aria-pressed="${UI.clubTab === t}">${l}</button>`).join("")}</div>`;
    if (UI.clubTab === "kadro") {
      const sorted = c.players.slice().sort((a, b) => b.r - a.r);
      html += `<div class="list">${sorted.map((p, i) => `<div class="opt ${p.star ? "star" : ""}"><div><b>${esc(p.name)}</b> ${p.star ? `<span class="chip gold">Yıldız</span> ` : ""}<span class="chip ${i < k.squad ? "good" : ""}">${i < k.squad ? "İlk " + k.squad : "Yedek"}</span><small>${p.nat ? esc(p.nat) + " · " : ""}${esc(p.pos)} · ${p.age} yaş · maaş ${G.fmtS(G.playerWage(p, x.club))}/gün</small></div><div class="club-r"><b class="num">${p.r}</b><button class="btn sm danger" type="button" data-a="club-sell" data-v="${p.id}">Sat · ${G.fmtS(R.r1k(G.pPrice(p, x.club) * 0.7))}</button></div></div>`).join("")}</div>`;
    } else if (UI.clubTab === "transfer") {
      const list = G.clubMarket(id).slice().sort((a, b) => (b.star ? 1 : 0) - (a.star ? 1 : 0) || b.r - a.r);
      html += `${UI.adBtn ? UI.adBtn("scout", "Reklam izle, listeyi hemen yenile", `data-v="${id}"`, "block") : ""}<div class="list">${list.map(p => { const pr = G.pPrice(p, x.club); return `<div class="opt ${p.star ? "star" : ""}"><div><b>${esc(p.name)}</b>${p.star ? ` <span class="chip gold">Yıldız</span>` : ""}<small>${p.nat ? esc(p.nat) + " · " : ""}${esc(p.pos)} · ${p.age} yaş · maaş ${G.fmtS(G.playerWage(p, x.club))}/gün</small></div><div class="club-r"><b class="num">${p.r}</b><button class="btn sm ${S().money >= pr ? "pri" : ""}" type="button" data-a="club-buy" data-v="${p.id}" ${S().money >= pr ? "" : "disabled"}>${G.fmtS(pr)}</button></div></div>`; }).join("") || `<p class="small">Bugünkü listede oyuncu kalmadı.</p>`}</div>`;
    } else {
      html += `<div class="list">${Object.entries(G.CLUB_STAFF).map(([key, st]) => { const lv = c.staff[key], full = lv >= st.max, cost = full ? 0 : G.staffCost(id, key); return `<div class="opt"><div><b>${st.name}</b> <span class="chip">${lv}/${st.max}</span><small>${st.desc}${st.power ? ` · seviye başına güç +${String(st.power).replace(".", ",")}` : ""}</small></div>${full ? `<span class="chip good">Tam</span>` : `<button class="btn sm ${S().money >= cost ? "pri" : ""}" type="button" data-a="club-staff" data-v="${key}" ${S().money >= cost ? "" : "disabled"}>${G.fmtS(cost)}</button>`}</div>`; }).join("")}</div>`;
    }
    return html;
  };
  UI.openClubManage = id => UI.open("clubm", id);
  const re = () => { UI.renderSheet(true); };
  UI.ACT["club-manage"] = el => UI.openClubManage(el.dataset.v);
  UI.ACT["club-tab"] = el => { UI.clubTab = el.dataset.v; re(); };
  UI.ACT["club-buy"] = el => { const r = G.clubBuy(UI.sheet.id, +el.dataset.v); if (r.msg) UI.toast(r.msg, "bad"); re(); };
  UI.ACT["club-sell"] = el => { G.clubSell(UI.sheet.id, +el.dataset.v); re(); };
  UI.ACT["club-staff"] = el => { const r = G.clubStaffUp(UI.sheet.id, el.dataset.v); if (r.msg) UI.toast(r.msg, "bad"); re(); };
  UI.ACT["ad-scout"] = el => { const id = el.dataset.v; UI.watch("scout", () => { const c = G.clubData(id); if (c.market) c.market.day = -1; UI.open("clubm", id); }); };
})(window.G);
