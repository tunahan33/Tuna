/* Galeri Patronu — lüks alışveriş: ev, araba, uçak ve yat çizimleri ile "Lüks" ekranı */
(function (G) {
  "use strict";
  const UI = G.UI;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  // ---- Çizimler (120×64) ----
  const SKY = `<rect width="120" height="64" fill="#cfe8ff"/><ellipse cx="30" cy="12" rx="12" ry="3.5" fill="#fff" opacity=".8"/><ellipse cx="92" cy="8" rx="9" ry="2.6" fill="#fff" opacity=".8"/>`;
  const SEA = y => `<rect y="${y}" width="120" height="${64 - y}" fill="#2f7fc1"/><path d="M0 ${y + 4} q6 -2 12 0 t12 0 t12 0 t12 0 t12 0 t12 0 t12 0 t12 0 t12 0 t12 0" stroke="#7cc4f2" stroke-width=".8" fill="none"/>`;
  const GRASS = y => `<rect y="${y}" width="120" height="${64 - y}" fill="#7fbf6a"/>`;
  const palm = (x, y) => `<rect x="${x - 1}" y="${y - 22}" width="2" height="22" fill="#8a5a33" transform="rotate(4 ${x} ${y})"/><path d="M${x} ${y - 22} q-9 -2 -12 4 M${x} ${y - 22} q9 -2 12 4 M${x} ${y - 22} q-5 -7 -11 -6 M${x} ${y - 22} q5 -7 11 -6" stroke="#3f8f46" stroke-width="2.2" fill="none" stroke-linecap="round"/>`;
  function windows(x, y, cols, rows, w, h, gx, gy, lit) {
    let o = "";
    for (let r = 0; r < rows; r++) for (let c = 0; c < cols; c++) o += `<rect x="${x + c * (w + gx)}" y="${y + r * (h + gy)}" width="${w}" height="${h}" fill="${lit && lit(r, c) ? "#ffd23f" : "#a9d3ec"}"/>`;
    return o;
  }

  const ART = {
    ev1: () => SKY + GRASS(54) + `<rect x="38" y="10" width="44" height="44" fill="#e8dcc6"/><rect x="36" y="8" width="48" height="4" fill="#b5623a"/>` + windows(42, 15, 4, 5, 6, 5, 4, 3, (r, c) => r === 2 && c === 1) + `<rect x="56" y="46" width="8" height="8" fill="#6a5442"/><circle cx="20" cy="46" r="7" fill="#3f8f46"/><rect x="19" y="46" width="2" height="8" fill="#7a4f2c"/>`,
    ev2: () => SKY + GRASS(52) + `<rect x="8" y="16" width="30" height="36" fill="#f2efe8"/>` + windows(11, 20, 3, 4, 6, 5, 3, 3) + `<rect x="80" y="12" width="32" height="40" fill="#f2efe8"/>` + windows(83, 16, 3, 5, 6, 5, 4, 2.5, (r, c) => r === 3 && c === 2) + `<rect x="42" y="50" width="34" height="8" rx="2" fill="#5ec8f0" stroke="#e8ecef" stroke-width="1.5"/><path d="M0 58 H120" stroke="#e8ecef" stroke-width="1.2" stroke-dasharray="2 2"/>`,
    ev3: () => SKY + `<path d="M0 40 L30 22 L60 40 L90 22 L120 40" stroke="#c0392b" stroke-width="1.2" fill="none"/><path d="M30 22 V44 M90 22 V44" stroke="#c0392b" stroke-width="2"/><rect x="0" y="40" width="120" height="2" fill="#7b8a94"/>` + SEA(44) + `<rect x="46" y="4" width="28" height="52" fill="#46515c"/>` + windows(49, 8, 4, 9, 4, 3, 2.4, 2.4, (r, c) => r === 6 && c === 3) + `<rect x="42" y="54" width="36" height="4" fill="#9aa3ac"/>`,
    ev4: () => SKY + GRASS(48) + `<rect x="18" y="26" width="58" height="22" fill="#f7f5ef"/><rect x="50" y="16" width="40" height="14" fill="#f7f5ef"/><rect x="16" y="24" width="62" height="3" fill="#3c4650"/><rect x="48" y="14" width="44" height="3" fill="#3c4650"/>` + `<rect x="22" y="31" width="22" height="13" fill="#a9d3ec"/><rect x="54" y="20" width="32" height="8" fill="#a9d3ec"/><rect x="52" y="33" width="10" height="15" fill="#6a5442"/><rect x="66" y="50" width="34" height="8" rx="2" fill="#5ec8f0" stroke="#fff" stroke-width="1.2"/>` + palm(106, 50) + palm(8, 50),
    ev5: () => SKY + SEA(46) + `<rect x="14" y="14" width="76" height="32" fill="#c98a5a"/><path d="M10 14 L52 2 L94 14 Z" fill="#7a3b22"/>` + windows(18, 18, 7, 2, 7, 9, 3.4, 4) + `<path d="M14 30 H90" stroke="#f2e6d6" stroke-width="1"/><rect x="90" y="40" width="22" height="2" fill="#8a5a33"/><path d="M96 42 v6 M106 42 v6" stroke="#8a5a33" stroke-width="1"/><path d="M98 50 q6 3 12 0 l-1 2 h-10 z" fill="#ffffff"/>`,
    uc1: () => SKY + `<g transform="translate(20 22)"><path d="M6 16 Q8 10 20 10 L64 10 Q74 10 78 14 L80 18 L6 18 Z" fill="#ffffff" stroke="#9aa3ac"/><path d="M28 13 L46 13 L42 0 L34 0 Z" fill="#c8323a"/><path d="M6 12 L2 2 L10 2 L16 10 Z" fill="#c8323a"/><rect x="58" y="12" width="12" height="3" fill="#2a3644"/><circle cx="82" cy="15" r="2" fill="#5d6873"/><rect class="spin" x="81" y="6" width="2" height="18" fill="#3c4650" opacity=".6"/><path d="M30 18 v6 M66 18 v6" stroke="#3c4650"/><circle cx="30" cy="25" r="2.4" fill="#1b1f24"/><circle cx="66" cy="25" r="2.4" fill="#1b1f24"/></g>`,
    uc2: () => SKY + `<g transform="translate(18 18)"><rect x="34" y="0" width="2" height="6" fill="#3c4650"/><rect class="rotor" x="0" y="0" width="70" height="1.6" fill="#3c4650"/><path d="M22 8 Q24 6 40 6 Q56 6 58 16 Q56 26 40 26 L28 26 Q20 26 20 18 Z" fill="#1f4fa8"/><path d="M42 9 Q54 9 55 16 L42 16 Z" fill="#a9d3ec"/><rect x="4" y="13" width="20" height="4" fill="#1f4fa8"/><path d="M2 8 L6 8 L8 15 L2 18 Z" fill="#1f4fa8"/><path d="M26 30 H58 M30 26 v4 M52 26 v4" stroke="#3c4650" stroke-width="1.4"/></g>`,
    uc3: () => SKY + `<g transform="translate(8 22)"><path d="M10 14 Q12 6 30 6 L88 6 Q100 6 104 12 Q100 18 88 18 L14 18 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M14 8 L6 -6 L14 -6 L26 6 Z" fill="#1d2024"/><path d="M44 14 L60 28 L68 28 L58 14 Z" fill="#c3cad1"/>` + windows(40, 9, 7, 1, 3, 3, 4, 0) + `<path d="M92 8 Q98 8 101 12 L92 12 Z" fill="#2a3644"/><path d="M8 13 H100" stroke="#d4a72c" stroke-width=".9"/></g>`,
    uc4: () => SKY + `<g transform="translate(2 18)"><path d="M8 16 Q10 6 30 6 L98 6 Q112 6 116 14 Q112 22 98 22 L12 22 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M12 8 L2 -10 L12 -10 L28 6 Z" fill="#1f4fa8"/><path d="M46 18 L66 36 L76 36 L62 18 Z" fill="#c3cad1"/><ellipse cx="60" cy="27" rx="7" ry="3" fill="#9aa3ac"/>` + windows(30, 10, 14, 1, 3, 3, 2.6, 0) + `<path d="M104 9 Q110 9 113 13 L104 13 Z" fill="#2a3644"/><path d="M8 17 H112" stroke="#1f4fa8" stroke-width="1.2"/></g>`,
    y1: () => SKY + SEA(38) + `<g transform="translate(18 26)"><path d="M0 12 L76 12 Q84 12 88 6 L70 4 L20 4 Z" fill="#ffffff" stroke="#9aa3ac"/><path d="M0 12 L84 12 L80 16 L6 16 Z" fill="#c8323a"/><path d="M40 4 L52 -4 L60 4 Z" fill="#2a3644"/></g><path d="M8 46 q6 -2 10 0 M0 44 q6 -2 12 0" stroke="#ffffff" stroke-width="1.2" fill="none"/>`,
    y2: () => SKY + SEA(40) + `<g transform="translate(12 6)"><path d="M0 30 L94 30 Q90 40 80 42 L12 42 Q4 40 0 30 Z" fill="#8a5a33"/><path d="M2 32 H92" stroke="#f2e6d6" stroke-width="1.2"/><rect x="24" y="22" width="44" height="8" fill="#f2e6d6"/>` + windows(28, 24, 5, 1, 4, 3, 4, 0) + `<path d="M36 22 V0 M64 22 V6" stroke="#5a3a2c" stroke-width="1.6"/><path d="M36 2 L54 20 L36 20 Z M64 8 L78 20 L64 20 Z" fill="#f7f3e8" opacity=".9"/><path d="M94 30 L106 26" stroke="#5a3a2c" stroke-width="1.4"/></g>`,
    y3: () => SKY + SEA(42) + `<g transform="translate(8 14)"><path d="M0 26 L104 26 Q100 36 88 38 L10 38 Q2 34 0 26 Z" fill="#ffffff" stroke="#9aa3ac"/><path d="M14 26 L18 16 L86 16 L94 26 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M28 16 L32 8 L72 8 L78 16 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M20 18 H88 M34 10 H74" stroke="#2a3644" stroke-width="2.6"/><path d="M4 32 H100" stroke="#1f4fa8" stroke-width="1.4"/><rect x="50" y="2" width="2" height="6" fill="#3c4650"/></g>`,
    y4: () => SKY + SEA(44) + `<g transform="translate(2 8)"><path d="M0 30 L114 30 Q110 42 96 44 L8 44 Q2 40 0 30 Z" fill="#ffffff" stroke="#9aa3ac"/><path d="M10 30 L14 22 L100 22 L108 30 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M20 22 L24 14 L90 14 L96 22 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M32 14 L36 6 L78 6 L82 14 Z" fill="#f4f6f8" stroke="#9aa3ac"/><path d="M16 25 H104 M26 17 H92 M38 9 H78" stroke="#2a3644" stroke-width="2.4"/><path d="M4 36 H110" stroke="#d4a72c" stroke-width="1.4"/><ellipse cx="18" cy="21" rx="6" ry="1.4" fill="#c3cad1"/><text x="18" y="22" text-anchor="middle" font-size="2.4" font-weight="800" fill="#2a3644">H</text><rect x="58" y="0" width="1.6" height="6" fill="#3c4650"/></g>`
  };

  UI.luxArt = function (x) {
    let inner;
    if (x.cat === "araba") {
      inner = `<rect width="120" height="64" fill="#e9ecef"/><rect y="46" width="120" height="18" fill="#c3cad1"/><ellipse cx="60" cy="52" rx="50" ry="5" fill="#ffffff" opacity=".6"/>` +
        `<g transform="translate(6 10) scale(0.9)">${UI.carShape({ m: x.model, color: x.color, clean: 100, body: 100, motor: 100, painted: 0, fuel: G.M[x.model].fuels[0] })}</g>`;
    } else inner = ART[x.id] ? ART[x.id]() : "";
    return `<svg viewBox="0 0 120 64" role="img" aria-label="${esc(x.name)}">${inner}</svg>`;
  };

  // ---- Lüks ekranı (Patron → Lüks) ----
  UI.luxCat = UI.luxCat || "ev";
  UI.luxView = function () {
    const S = G.S, p = G.prestige(), e = G.presEff(p);
    let html = `<div class="card lux-head"><div class="row"><div><h3>Patronun hayatı</h3><p class="muted small">Kişisel lüks harcamalar. Her eşya şöhret kazandırır; şöhret müşterilerin sana güvenini ve ödemeye razı olduğu fiyatı artırır.</p></div><div class="fame"><span>Şöhret</span><b>${p}</b></div></div>
      <div class="kv"><div><span>Müşteri ödemesi</span><b>+%${(Math.round((e.pay - 1) * 1000) / 10).toString().replace(".", ",")}</b></div><div><span>Günlük itibar</span><b>+${e.rep.toFixed(2).replace(".", ",")}</b></div>
      <div><span>Zengin müşteri</span><b>${e.gate ? `+${e.gate} kademe` : "50 şöhrette"}</b></div><div><span>Günlük bakım</span><b>${G.fmtS(G.luxUpkeep())}</b></div></div></div>`;
    if (G.bestOf("yat")) {
      const ready = G.partyReady(), pe = G.partyEff();
      html += `<div class="card party"><div class="row"><div><b>Yat partisi · ${esc(G.bestOf("yat").name)}</b><p class="small muted">${G.partyActive() ? "Bugün partinin etkisi sürüyor: zengin müşteri akını!" : ready ? `Parti ver: itibar +${pe.rep}, ertesi gün ${pe.traffic.toFixed(2).replace(".", ",")}× müşteri ve zengin müşteri akını. Masraf ${G.fmtS(G.partyCost())}.` : `Bir sonraki parti ${S.lastParty + G.PARTY_COOLDOWN}. günde.`}</p></div>
        <button class="btn warn" type="button" data-a="party" ${ready ? "" : "disabled"}>Parti ver</button></div></div>`;
    }
    html += `<div class="segctl" role="group">${Object.keys(G.LUX_CATS).map(k => `<button type="button" data-a="luxcat" data-v="${k}" aria-pressed="${UI.luxCat === k}">${G.LUX_CATS[k].name} <small>${G.LUXURY.filter(x => x.cat === k && G.owns(x.id)).length}/${G.LUXURY.filter(x => x.cat === k).length}</small></button>`).join("")}</div>`;
    const C = G.LUX_CATS[UI.luxCat];
    html += `<p class="muted small" style="margin:0">${esc(C.perk)} Bakım: fiyatın günde %${(C.upkeep * 100).toFixed(2).replace(".", ",")}'i · Satarsan fiyatın %${Math.round(C.resale * 100)}'i geri gelir.</p>`;
    html += `<div class="lux-grid">${G.LUXURY.filter(x => x.cat === UI.luxCat).map(x => {
      const own = G.owns(x.id);
      const perk = x.imports ? `Her sabah ${x.imports * 2} ithal ilan` : x.party ? `Parti gücü ${x.party}` : "";
      let btn;
      if (own) btn = `<button class="btn sm danger" type="button" data-a="lux-sell" data-v="${x.id}">${UI.armed === "lux" + x.id ? `${G.fmtS(G.luxResale(x))}'ye sat?` : "Sat"}</button>`;
      else if (S.level < x.lvl) btn = `<span class="chip">Seviye ${x.lvl}</span>`;
      else btn = `<button class="btn sm ${S.money >= x.price ? "pri" : ""}" type="button" data-a="lux-buy" data-v="${x.id}" ${S.money >= x.price ? "" : "disabled"}>${G.fmtS(x.price)}</button>`;
      return `<div class="lux-card ${own ? "own" : ""} ${S.level < x.lvl ? "locked" : ""}"><div class="lux-art">${UI.luxArt(x)}${own ? `<span class="chip good own-tag">Senin</span>` : ""}</div>
        <div class="lux-body"><b>${esc(x.name)}</b><p class="small muted">${esc(x.desc)}</p>
        <div class="chips"><span class="chip acc">Şöhret +${x.pres}</span><span class="chip">Bakım ${G.fmtS(x.price * C.upkeep)}/gün</span>${perk ? `<span class="chip warn">${perk}</span>` : ""}</div>
        <div class="row"><span class="num small">${G.fmtS(x.price)}</span>${btn}</div></div></div>`;
    }).join("")}</div>`;
    return html;
  };
})(window.G = window.G || {});
