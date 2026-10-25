/* Galeri Patronu — galeri sahnesi
 * Galerinin önden görünüşü: bina (konuma göre değişir), atölye kapıları, yıkama, vitrin, flamalı park alanı,
 * etiketli araçlar ve müşteriler. Araca, müşteriye, binaya ya da atölyeye dokununca ilgili ekran açılır. */
(function (G) {
  "use strict";
  const UI = G.UI;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  const W = 400;
  const BASE = 122;          // binaların oturduğu çizgi
  const ROW_H = 30, PER_ROW = 6, SLOT_W = 62, LOT_X = 14;
  const CAR_S = 0.38;
  const SHIRTS = ["#d9534f", "#3b7dd8", "#f0ad4e", "#5cb85c", "#8e5bd8", "#2bb3a8", "#e46aa6", "#555f6b"];
  const SKINS = ["#f1c7a5", "#d9a47e", "#b67a55", "#8d5a3b"];

  const tiny = x => (x >= 1e6 ? (x / 1e6).toFixed(1).replace(".", ",") + "M" : Math.round(x / 1000) + "B");

  function mix(a, b, t) {
    const pa = parseInt(a.slice(1), 16), pb = parseInt(b.slice(1), 16);
    const c = s => Math.round(((pa >> s) & 255) * (1 - t) + ((pb >> s) & 255) * t);
    return `rgb(${c(16)},${c(8)},${c(0)})`;
  }
  function sky(min) {
    const t = (min - G.OPEN) / (G.CLOSE - G.OPEN);
    if (t < 0.5) return [mix("#cfe8ff", "#8fcaf6", t * 2), mix("#eaf5ff", "#cfe9fb", t * 2)];
    return [mix("#8fcaf6", "#f0a274", (t - 0.5) * 2), mix("#cfe9fb", "#fbd9b0", (t - 0.5) * 2)];
  }

  function person(x, y, id, cls, act) {
    const shirt = SHIRTS[id % SHIRTS.length], skin = SKINS[(id >> 2) % SKINS.length];
    return `<g class="${cls || ""}" transform="translate(${x} ${y})" ${act || ""}>
      <rect x="-2.6" y="-3" width="2" height="5" rx="1" fill="#2c3540"/><rect x=".6" y="-3" width="2" height="5" rx="1" fill="#2c3540"/>
      <rect x="-3.6" y="-11" width="7.2" height="9" rx="3" fill="${shirt}"/><circle cy="-14" r="3.1" fill="${skin}"/></g>`;
  }

  function carAt(car, x, y, s, extra) {
    return `<g transform="translate(${x} ${y}) scale(${s})">${UI.carShape(car)}</g>${extra || ""}`;
  }

  // ---- Binalar ----
  function building(tier, x, w, name, show) {
    const sign = (sx, sy, sw, bg, fg) => `<rect x="${sx}" y="${sy}" width="${sw}" height="13" rx="2" fill="${bg}"/>
      <text x="${sx + sw / 2}" y="${sy + 9.6}" text-anchor="middle" font-size="8.5" font-weight="700" fill="${fg}" font-family="Saira Condensed, Arial Narrow, sans-serif" letter-spacing=".6">${esc(name.toLocaleUpperCase("tr-TR").slice(0, Math.floor(sw / 5.4)))}</text>`;
    let out = "", glass = null;
    if (tier === 0) {
      const h = 50, y = BASE - h;
      out += `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#b4553c"/>`;
      for (let i = x + 5; i < x + w; i += 6) out += `<line x1="${i}" y1="${y + 2}" x2="${i}" y2="${BASE - 2}" stroke="#8f3f2b" stroke-width="1.4"/>`;
      out += `<rect x="${x + 10}" y="${y + 16}" width="16" height="${h - 16}" fill="#5a3a2c"/><rect x="${x + 34}" y="${y + 12}" width="${Math.min(40, w - 44)}" height="16" fill="#cfe4f2" stroke="#6b3527"/>`;
      out += sign(x + 4, y - 15, w - 8, "#f7f3e8", "#7a2c1d");
    } else if (tier === 1) {
      const h = 66, y = BASE - h;
      out += `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#ece3d2"/><rect x="${x}" y="${y}" width="${w}" height="15" fill="#1f5c4f"/>`;
      out += `<text x="${x + w / 2}" y="${y + 11}" text-anchor="middle" font-size="9" font-weight="800" fill="#ffd23f" font-family="Saira Condensed, Arial Narrow, sans-serif" letter-spacing=".8">${esc(name.toLocaleUpperCase("tr-TR").slice(0, Math.floor(w / 5.6)))}</text>`;
      for (let i = 0; i < w; i += 12) out += `<path d="M${x + i} ${y + 18} h12 l-1.5 8 h-9 z" fill="${(i / 12) % 2 ? "#ffffff" : "#c8323a"}"/>`;
      out += `<rect x="${x + 8}" y="${y + 30}" width="${w - 40}" height="${h - 32}" fill="#bcdcef" stroke="#7b8a94"/><rect x="${x + w - 26}" y="${y + 30}" width="16" height="${h - 30}" fill="#6a5442"/>`;
      glass = { x: x + 8, y: y + 30, w: w - 40, h: h - 32, inside: false };
    } else {
      const h = tier === 2 ? 80 : tier === 3 ? 98 : 108, y = BASE - h;
      const gh = tier === 2 ? 58 : 50;
      out += `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${tier === 4 ? "#3a4652" : "#46515c"}"/>`;
      if (tier >= 3) {
        for (let r = y + 18; r < BASE - gh - 8; r += 14) out += `<rect x="${x + 6}" y="${r}" width="${w - 12}" height="8" fill="#9fc6e0" opacity=".8"/>`;
      }
      out += `<rect x="${x + 4}" y="${BASE - gh}" width="${w - 8}" height="${gh}" fill="#a9d3ec" opacity=".9"/>`;
      for (let i = x + 4; i <= x + w - 4; i += 40) out += `<line x1="${i}" y1="${BASE - gh}" x2="${i}" y2="${BASE}" stroke="#46515c" stroke-width="2"/>`;
      out += sign(x + 6, y + 3, w - 12, tier === 4 ? "#ffd23f" : "#151b21", tier === 4 ? "#151b21" : "#ffd23f");
      if (tier === 4) out += `<rect x="${x + w - 26}" y="${y - 26}" width="20" height="30" fill="#2c3540"/><circle cx="${x + w - 16}" cy="${y - 14}" r="7" fill="#ffd23f"/><text x="${x + w - 16}" y="${y - 11}" text-anchor="middle" font-size="8" font-weight="800" fill="#151b21" font-family="Saira Condensed, sans-serif">${esc(name.trim()[0] || "G")}</text>`;
      glass = { x: x + 6, y: BASE - gh, w: w - 12, h: gh, inside: true };
    }
    // vitrindeki araçlar camın arkasında
    if (glass && show.length) {
      const per = Math.max(1, Math.floor(glass.w / 44));
      show.slice(0, per).forEach((c, i) => {
        out += `<g data-a="car" data-id="${c.id}" class="hit">${carAt(c, glass.x + 3 + i * 44, BASE - 20, 0.34)}</g>`;
      });
      if (show.length > per) out += `<text x="${glass.x + glass.w - 4}" y="${glass.y + 10}" text-anchor="end" font-size="8" fill="#17202b" font-weight="700">+${show.length - per}</text>`;
    }
    return { svg: out, usedGlass: !!(glass && glass.inside) || (glass && show.length > 0) };
  }

  function garage(x, bays, jobs) {
    const w = bays * 30 + 6, h = 50, y = BASE - h;
    let out = `<g data-a="tab" data-v="atolye" class="hit"><rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#8a949e"/><rect x="${x}" y="${y}" width="${w}" height="10" fill="#5d6873"/>
      <text x="${x + w / 2}" y="${y + 7.6}" text-anchor="middle" font-size="6.5" font-weight="700" fill="#fff" letter-spacing=".8" font-family="Saira Condensed, sans-serif">ATÖLYE</text>`;
    const inBay = jobs.filter(j => j.inhouse && j.where === "bay");
    for (let i = 0; i < bays; i++) {
      const dx = x + 4 + i * 30;
      const job = inBay[i];
      if (job) {
        const car = G.S.cars.find(c => c.id === job.carId);
        out += `<rect x="${dx}" y="${y + 14}" width="26" height="${h - 14}" fill="#2b3138"/><rect x="${dx + 2}" y="${BASE - 4}" width="22" height="2" fill="#f0a73a"/>`;
        if (car) out += carAt(car, dx - 1, BASE - 20, 0.235);
      } else {
        out += `<rect x="${dx}" y="${y + 14}" width="26" height="${h - 14}" fill="#c3cad1"/>`;
        for (let k = y + 17; k < BASE; k += 4) out += `<line x1="${dx}" y1="${k}" x2="${dx + 26}" y2="${k}" stroke="#a6afb8" stroke-width="1"/>`;
      }
    }
    return out + "</g>";
  }

  function wash(x, active, lvl) {
    const y = BASE - 40, tunnel = lvl >= 10, w = tunnel ? 52 : 38;
    const arch = ax => `<path d="M${ax} ${BASE} V${y + 8} Q${ax} ${y} ${ax + 8} ${y} H${ax + 30} Q${ax + 38} ${y} ${ax + 38} ${y + 8} V${BASE}" fill="none" stroke="#2f7fc1" stroke-width="5"/>`;
    let out = `<g data-a="fac" data-v="yikama" class="hit">`;
    if (tunnel) out += `<rect x="${x}" y="${y}" width="${w}" height="${BASE - y}" rx="8" fill="#cfe8f7" opacity=".7"/>` + arch(x + 14);
    out += arch(x);
    if (lvl >= 5) out += `<path d="M${x + 4} ${y + 6} l3 -4 l3 4 z M${x + 30} ${y + 6} l3 -4 l3 4 z" fill="#ffd23f"/>`;
    out += `<text x="${x + w / 2}" y="${y - 3}" text-anchor="middle" font-size="6" font-weight="700" fill="#2f7fc1" font-family="Saira Condensed, sans-serif">${tunnel ? "KÖPÜK TÜNELİ" : "YIKAMA"}</text>`;
    if (active) for (let i = 0; i < 5; i++) out += `<circle class="drop" cx="${x + 8 + i * 5.5}" cy="${y + 12 + (i % 2) * 6}" r="1.6" fill="#7cc4f2" style="animation-delay:${i * 0.18}s"/>`;
    return out + "</g>";
  }

  // Usta çardağı: asmalı pergola, masa, dinlenen ustalar
  function cardak(x, lvl, resting) {
    const w = 54, top = BASE - 34;
    let out = `<g data-a="fac" data-v="cardak" class="hit">`;
    out += `<rect x="${x + 2}" y="${top}" width="3" height="34" fill="#8a5a33"/><rect x="${x + w - 5}" y="${top}" width="3" height="34" fill="#8a5a33"/>`;
    out += `<rect x="${x - 2}" y="${top - 3}" width="${w + 4}" height="4" fill="#9b6a3e"/>`;
    for (let i = x; i < x + w; i += 6) out += `<rect x="${i}" y="${top - 6}" width="2" height="9" fill="#7a4f2c"/>`;
    const leaves = 6 + Math.min(14, lvl);
    for (let i = 0; i < leaves; i++) out += `<circle cx="${(x + 2 + (i * 37) % (w - 2)).toFixed(1)}" cy="${(top - 5 + (i % 3) * 3).toFixed(1)}" r="${(2.6 + (i % 2)).toFixed(1)}" fill="${i % 3 ? "#3f8f46" : "#57a85a"}"/>`;
    if (lvl >= 15) for (let i = 0; i < 4; i++) out += `<circle cx="${x + 10 + i * 11}" cy="${top + 2}" r="1.6" fill="#6b2d6b"/>`; // üzüm salkımı
    out += `<rect x="${x + 18}" y="${BASE - 11}" width="18" height="2" fill="#7a4f2c"/><rect x="${x + 26}" y="${BASE - 9}" width="2" height="9" fill="#7a4f2c"/>`;
    out += `<rect x="${x + 21}" y="${BASE - 14}" width="2" height="3" fill="#c0392b" opacity=".85"/><rect x="${x + 30}" y="${BASE - 14}" width="2" height="3" fill="#c0392b" opacity=".85"/>`;
    if (lvl >= 20) out += `<path d="M${x + 6} ${top + 10} Q${x + 14} ${top + 18} ${x + 22} ${top + 10}" stroke="#e2742c" stroke-width="2" fill="none"/>`; // hamak
    resting.slice(0, 5).forEach((u, i) => { out += person(x + 9 + i * 9 + (i > 1 ? 18 : 0), BASE, u.id || i, "sit"); });
    out += `<text x="${x + w / 2}" y="${top - 10}" text-anchor="middle" font-size="6" font-weight="700" fill="#3f6b2f" font-family="Saira Condensed, sans-serif">ÇARDAK</text>`;
    return out + "</g>";
  }

  // Galeri binasının seviye süsleri
  function decor(x, w, tier, lvl) {
    const h = [50, 66, 80, 98, 108][tier], y = BASE - h;
    let out = "";
    if (lvl >= 16) out += `<rect x="${x}" y="${y}" width="${w}" height="2.5" fill="#d4a72c"/><rect x="${x}" y="${BASE - 3}" width="${w}" height="3" fill="#d4a72c"/>`;
    if (lvl >= 8) out += `<rect x="${x + 1}" y="${y + 1}" width="${w - 2}" height="${h - 2}" fill="none" stroke="#ff4fd8" stroke-width="1.2" opacity=".75"/>`;
    if (lvl >= 12) out += `<rect x="${x + w - 40}" y="${y + 18}" width="32" height="14" rx="1.5" fill="#101418"/><text x="${x + w - 24}" y="${y + 28}" text-anchor="middle" font-size="7" font-weight="800" fill="#ffd23f" font-family="Saira Condensed, sans-serif" class="led">FIRSAT</text>`;
    if (lvl >= 4) {
      [x + 6, x + w - 6].forEach(lx => { out += `<path d="M${lx} ${y - 2} L${lx - 9} ${y - 26} L${lx + 9} ${y - 26} Z" fill="#fff6c8" opacity=".35"/><rect x="${lx - 2}" y="${y - 4}" width="4" height="4" rx="1" fill="#3c4650"/>`; });
    }
    return out;
  }

  function canopy(x, w, show) {
    const y = BASE - 44;
    let out = `<path d="M${x} ${y + 10} L${x + w / 2} ${y} L${x + w} ${y + 10} Z" fill="#f7f3e8" stroke="#c8323a" stroke-width="1.5"/>
      <rect x="${x + 2}" y="${y + 10}" width="2" height="34" fill="#7b8a94"/><rect x="${x + w - 4}" y="${y + 10}" width="2" height="34" fill="#7b8a94"/>
      <text x="${x + w / 2}" y="${y + 8}" text-anchor="middle" font-size="5.5" font-weight="700" fill="#c8323a" font-family="Saira Condensed, sans-serif">VİTRİN</text>`;
    const per = Math.max(1, Math.floor((w - 6) / 44));
    show.slice(0, per).forEach((c, i) => { out += `<g data-a="car" data-id="${c.id}" class="hit">${carAt(c, x + 3 + i * 44, BASE - 19, 0.34)}</g>`; });
    if (show.length > per) out += `<text x="${x + w - 6}" y="${y + 20}" text-anchor="end" font-size="8" font-weight="700" fill="#17202b">+${show.length - per}</text>`;
    return out;
  }

  UI.scene = function () {
    const S = G.S, L = G.loc(), tier = S.loc;
    const show = S.cars.filter(c => c.show);
    const lot = S.cars.filter(c => !c.show && c.status === "lot");
    const shop = S.cars.filter(c => c.status === "shop");
    const slots = Math.max(lot.length, L.cap - Math.min(G.showSlots(), L.cap) - shop.length, 1);
    const rows = Math.ceil(slots / PER_ROW);
    const lotTop = BASE + 12;
    const roadY = lotTop + rows * ROW_H + 6;
    const H = roadY + 28;
    const [s1, s2] = sky(S.min);
    const t = (S.min - G.OPEN) / (G.CLOSE - G.OPEN);
    const sunX = 30 + t * 340, sunY = 52 - Math.sin(t * Math.PI) * 38;

    let svg = `<svg class="scene" viewBox="0 0 ${W} ${H}" role="img" aria-label="${esc(S.name)}: ${esc(L.name)}, parkta ${lot.length} araç">
      <defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s1}"/><stop offset="1" stop-color="${s2}"/></linearGradient></defs>
      <rect width="${W}" height="${lotTop}" fill="url(#sky)"/>
      <circle cx="${sunX.toFixed(1)}" cy="${sunY.toFixed(1)}" r="11" fill="${t > 0.8 ? "#ffb070" : "#ffe08a"}" opacity=".9"/>
      <g fill="#ffffff" opacity=".75"><ellipse cx="${(90 + t * 40).toFixed(0)}" cy="28" rx="22" ry="6"/><ellipse cx="${(290 - t * 30).toFixed(0)}" cy="18" rx="16" ry="5"/></g>`;
    // arka plandaki şehir
    if (tier >= 1) {
      svg += `<g fill="#b9c7d3" opacity="${tier >= 2 ? 0.9 : 0.5}">`;
      [[8, 40], [40, 62], [70, 30], [300, 54], [330, 70], [362, 44]].forEach(([bx, bh], i) => { svg += `<rect x="${bx}" y="${BASE - bh - 10}" width="${26 + (i % 2) * 6}" height="${bh + 10}"/>`; });
      svg += `</g>`;
    }

    // yerleşim: atölye | bina | (vitrin çadırı) | yıkama
    const gw = S.upg.bays ? S.upg.bays * 30 + 6 : 0;
    const wl = G.facLevel("yikama"), cl = G.facLevel("cardak"), gl = G.facLevel("galeri"), ol = G.facLevel("otopark");
    const ww = wl ? (wl >= 10 ? 52 : 38) : 0;
    const cw = cl ? 54 : 0;
    const canopyW = tier < 2 && G.showSlots() ? 94 : 0;
    const want = [110, 170, 230, 260, 280][tier];
    const gaps = [gw, ww, cw, canopyW].filter(Boolean).length * 6;
    const bw = Math.max(90, Math.min(want, W - 8 - gw - ww - cw - canopyW - gaps));
    const total = gw + bw + canopyW + ww + cw + gaps;
    let x = Math.round((W - total) / 2);
    if (cw) { svg += cardak(x, cl, G.ustas().filter(u => u.resting)); x += cw + 6; }
    if (gw) { svg += garage(x, S.upg.bays, S.jobs); x += gw + 6; }
    const b = building(tier, x, bw, S.name, tier >= 2 ? show : (tier === 1 && !canopyW ? show : []));
    svg += `<g data-a="fac" data-v="galeri" class="hit">${b.svg}${decor(x, bw, tier, gl)}</g>`;
    if (S.upg.security) svg += `<rect x="${x + bw - 3}" y="${BASE - 58}" width="2" height="16" fill="#5d6873"/><rect x="${x + bw - 9}" y="${BASE - 60}" width="9" height="5" rx="1" fill="#2c3540"/><circle cx="${x + bw - 8}" cy="${BASE - 57.5}" r="1" fill="#e04a3f"/>`;
    x += bw + 6;
    if (canopyW) { svg += canopy(x, canopyW, show); x += canopyW + 6; }
    if (ww) svg += wash(x, S.jobs.some(j => j.where === "wash"), wl);

    // park alanı (otopark tesisi: zemin, ışık, tente)
    const oe = G.facEff("otopark");
    const dirt = tier === 0 && !oe.asphalt;
    const ground = dirt ? "#bfa27c" : oe.asphalt ? "#828b94" : "#9aa3ac";
    svg += `<rect x="0" y="${BASE}" width="${W}" height="${roadY - BASE}" fill="${ground}" data-a="fac" data-v="otopark" class="hit"/>`;
    svg += `<rect x="0" y="${BASE}" width="${W}" height="4" fill="${dirt ? "#a88c68" : "#c3cad1"}"/>`;
    if (S.upg.cafe) svg += `<g transform="translate(${W - 26} ${BASE + 2})"><path d="M-12 0 Q0 -10 12 0 Z" fill="#e85d4a"/><rect x="-.6" y="0" width="1.2" height="9" fill="#5d6873"/><rect x="-6" y="8" width="12" height="1.5" fill="#5d6873"/></g>`;
    // flamalar
    const fy = BASE + 6;
    svg += `<path d="M2 ${fy - 4} Q${W / 2} ${fy + 8} ${W - 2} ${fy - 4}" fill="none" stroke="#3c4650" stroke-width=".8"/>`;
    const FL = ["#d9343b", "#ffd23f", "#2f6fd0", "#2e9d5b"];
    for (let i = 0, fx = 8; fx < W - 6; fx += 13, i++) {
      const u = fx / W, py = (1 - u) * (1 - u) * (fy - 4) + 2 * (1 - u) * u * (fy + 8) + u * u * (fy - 4);
      svg += `<path d="M${fx - 3.5} ${py.toFixed(1)} L${fx + 3.5} ${py.toFixed(1)} L${fx} ${(py + 8).toFixed(1)} Z" fill="${FL[i % 4]}"/>`;
    }
    // park yerleri ve araçlar
    const offerBy = {};
    S.custs.forEach(c => { if (c.state === "offer") offerBy[c.carId] = c; });
    for (let i = 0; i < slots; i++) {
      const r = Math.floor(i / PER_ROW), col = i % PER_ROW;
      const sx = LOT_X + col * SLOT_W, sy = lotTop + r * ROW_H;
      svg += oe.asphalt
        ? `<path d="M${sx} ${sy + 24} v-20 M${sx + SLOT_W - 6} ${sy + 24} v-20 M${sx} ${sy + 24} h${SLOT_W - 6}" stroke="#f4f6f8" stroke-width="1" fill="none" opacity=".85"/>`
        : `<path d="M${sx} ${sy + 22} v-6 M${sx + SLOT_W - 6} ${sy + 22} v-6" stroke="${dirt ? "#e9dcc6" : "#eef2f5"}" stroke-width="1.2" opacity=".8"/>`;
      const car = lot[i];
      if (!car) continue;
      const cx = sx + 4, cy = sy + 3;
      let tag = car.price
        ? `<rect x="${cx + 26}" y="${cy - 6}" width="20" height="9" rx="1.5" fill="#ffd23f" transform="rotate(4 ${cx + 36} ${cy - 2})"/><text x="${cx + 36}" y="${cy + 0.8}" text-anchor="middle" font-size="6.2" font-weight="700" fill="#1d1a10" font-family="Saira, sans-serif" transform="rotate(4 ${cx + 36} ${cy - 2})">${tiny(car.price)}</text>`
        : `<rect x="${cx + 28}" y="${cy - 6}" width="13" height="9" rx="1.5" fill="#ffffff" stroke="#e04a3f" stroke-width=".8"/><text x="${cx + 34.5}" y="${cy + 0.8}" text-anchor="middle" font-size="7" font-weight="800" fill="#e04a3f">?</text>`;
      svg += `<g data-a="car" data-id="${car.id}" class="hit">${carAt(car, cx, cy, CAR_S)}${tag}</g>`;
      const c = offerBy[car.id];
      if (c) svg += `<g data-a="cust" data-id="${c.id}" class="hit">${person(sx + SLOT_W - 8, sy + 22, c.id)}<g class="bubble"><rect x="${sx + SLOT_W - 12}" y="${sy - 7}" width="11" height="8" rx="3" fill="#ffffff" stroke="#1d8549"/><text x="${sx + SLOT_W - 6.5}" y="${sy - 0.8}" text-anchor="middle" font-size="6" font-weight="800" fill="#1d8549">₺</text></g></g>`;
    }
    // tente ilk sıranın üstünde, lambalar park alanının iki ucunda
    if (oe.covered) {
      const tw = Math.min(PER_ROW, slots) * SLOT_W - 4;
      svg += `<g data-a="fac" data-v="otopark" class="hit" opacity=".9"><rect x="${LOT_X - 2}" y="${lotTop - 3}" width="${tw}" height="5" fill="#f7f3e8"/>`;
      for (let i = LOT_X - 2; i < LOT_X - 2 + tw; i += 12) svg += `<rect x="${i}" y="${lotTop - 3}" width="6" height="5" fill="#2f6fd0"/>`;
      svg += `<rect x="${LOT_X - 2}" y="${lotTop + 2}" width="1.5" height="22" fill="#7b8a94"/><rect x="${LOT_X - 3.5 + tw}" y="${lotTop + 2}" width="1.5" height="22" fill="#7b8a94"/></g>`;
    }
    if (oe.light) {
      [4, W - 6].forEach(lx => { svg += `<rect x="${lx}" y="${lotTop - 8}" width="2" height="${roadY - lotTop + 6}" fill="#5d6873"/><rect x="${lx - 3}" y="${lotTop - 10}" width="8" height="3" rx="1.5" fill="#3c4650"/><circle cx="${lx + 1}" cy="${lotTop - 6}" r="2" fill="#fff3b0"/>`; });
    }
    // gezinen müşteriler
    S.custs.filter(c => c.state === "browse").slice(0, 6).forEach((c, i) => {
      svg += person(30 + ((c.id * 53) % 330), roadY - 2 - (i % 2) * 3, c.id, "walk", `style="animation-delay:${(i * 0.7).toFixed(1)}s"`);
    });
    // yol
    svg += `<rect x="0" y="${roadY}" width="${W}" height="5" fill="#c8ced4"/><rect x="0" y="${roadY + 5}" width="${W}" height="23" fill="#4a535c"/>
      <path d="M0 ${roadY + 16.5} H${W}" stroke="#e8e2c4" stroke-width="1.2" stroke-dasharray="10 9"/>`;
    const out = S.jobs.filter(j => !j.inhouse).length;
    if (out) svg += `<g data-a="tab" data-v="atolye" class="hit"><rect x="${W - 92}" y="${roadY + 8}" width="86" height="13" rx="6.5" fill="#ffffff"/><text x="${W - 49}" y="${roadY + 17}" text-anchor="middle" font-size="7.5" font-weight="700" fill="#17202b">Dış serviste: ${out} araç</text></g>`;
    if (G.boostActive()) svg += `<g><rect x="6" y="${roadY + 8}" width="104" height="13" rx="6.5" fill="#ffd23f"/><text x="58" y="${roadY + 17}" text-anchor="middle" font-size="7.5" font-weight="700" fill="#1d1a10">Kampanya: 2× müşteri</text></g>`;
    return svg + `</svg>`;
  };
})(window.G = window.G || {});
