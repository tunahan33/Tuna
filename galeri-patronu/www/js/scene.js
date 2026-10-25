/* Galeri Patronu — galeri sahnesi
 * Galerinin önden görünüşü: bina (konuma göre değişir), atölye, çardak, yıkama, vitrin, flamalı park alanı,
 * etiketli araçlar ve müşteriler. Her tesis seviyesi sahneye yeni bir parça ekler (20 seviye = 20 parça).
 * Araca, müşteriye, binaya ya da bir tesise dokununca ilgili ekran açılır. */
(function (G) {
  "use strict";
  const UI = G.UI;
  const esc = s => String(s).replace(/[&<>"']/g, ch => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

  const W = 400;
  const BASE = 122;          // binaların oturduğu çizgi
  const ROW_H = 30, PER_ROW = 6, SLOT_W = 62, LOT_X = 14;
  const CAR_S = 0.38;
  const H_T = [50, 66, 80, 98, 108]; // konuma göre bina yüksekliği
  const SHIRTS = ["#d9534f", "#3b7dd8", "#f0ad4e", "#5cb85c", "#8e5bd8", "#2bb3a8", "#e46aa6", "#555f6b"];
  const SKINS = ["#f1c7a5", "#d9a47e", "#b67a55", "#8d5a3b"];
  const FONT = 'font-family="Saira Condensed, Arial Narrow, sans-serif"';

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
  function sitter(x, y, id) {
    const shirt = SHIRTS[id % SHIRTS.length], skin = SKINS[(id >> 2) % SKINS.length];
    return `<g transform="translate(${x} ${y})"><rect x="-3" y="-4" width="6" height="2.5" rx="1" fill="#2c3540"/><rect x="-3.4" y="-11" width="6.8" height="8" rx="3" fill="${shirt}"/><circle cy="-13.6" r="2.9" fill="${skin}"/></g>`;
  }
  const carAt = (car, x, y, s) => `<g transform="translate(${x} ${y}) scale(${s})">${UI.carShape(car)}</g>`;
  function tree(cx, base, s) {
    const h = 40 * s;
    return `<rect x="${cx - 1.5}" y="${base - h * 0.45}" width="3" height="${h * 0.45}" fill="#7a4f2c"/>
      <circle cx="${cx}" cy="${base - h * 0.62}" r="${9 * s}" fill="#3f8f46"/><circle cx="${cx - 6 * s}" cy="${base - h * 0.5}" r="${7 * s}" fill="#4a9e50"/><circle cx="${cx + 6 * s}" cy="${base - h * 0.52}" r="${7 * s}" fill="#378540"/>`;
  }
  const pot = (x, y) => `<path d="M${x} ${y} h7 l-1 5 h-5 z" fill="#b5623a"/><circle cx="${x + 2}" cy="${y - 1}" r="2" fill="#3f8f46"/><circle cx="${x + 5}" cy="${y - 1.5}" r="2.2" fill="#4a9e50"/><circle cx="${x + 3.5}" cy="${y - 3}" r="1.2" fill="#e04a6a"/>`;
  const star = (x, y, r, fill, cls) => `<path class="${cls || ""}" d="M${x} ${y - r} L${x + r * 0.3} ${y - r * 0.3} L${x + r} ${y} L${x + r * 0.3} ${y + r * 0.3} L${x} ${y + r} L${x - r * 0.3} ${y + r * 0.3} L${x - r} ${y} L${x - r * 0.3} ${y - r * 0.3} Z" fill="${fill}"/>`;

  // ---- Bina (konum) ----
  function building(tier, x, w, name, show) {
    const sign = (sx, sy, sw, bg, fg) => `<rect x="${sx}" y="${sy}" width="${sw}" height="13" rx="2" fill="${bg}"/>
      <text x="${sx + sw / 2}" y="${sy + 9.6}" text-anchor="middle" font-size="8.5" font-weight="700" fill="${fg}" ${FONT} letter-spacing=".6">${esc(name.toLocaleUpperCase("tr-TR").slice(0, Math.floor(sw / 5.4)))}</text>`;
    let out = "", glass = null;
    const h = H_T[tier], y = BASE - h;
    if (tier === 0) {
      out += `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#b4553c"/>`;
      for (let i = x + 5; i < x + w; i += 6) out += `<line x1="${i}" y1="${y + 2}" x2="${i}" y2="${BASE - 2}" stroke="#8f3f2b" stroke-width="1.4"/>`;
      out += `<rect x="${x + 10}" y="${y + 16}" width="16" height="${h - 16}" fill="#5a3a2c"/><rect x="${x + 34}" y="${y + 12}" width="${Math.min(40, w - 44)}" height="16" fill="#cfe4f2" stroke="#6b3527"/>`;
      out += sign(x + 4, y - 15, w - 8, "#f7f3e8", "#7a2c1d");
    } else if (tier === 1) {
      out += `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#ece3d2"/><rect x="${x}" y="${y}" width="${w}" height="15" fill="#1f5c4f"/>`;
      out += `<text x="${x + w / 2}" y="${y + 11}" text-anchor="middle" font-size="9" font-weight="800" fill="#ffd23f" ${FONT} letter-spacing=".8">${esc(name.toLocaleUpperCase("tr-TR").slice(0, Math.floor(w / 5.6)))}</text>`;
      for (let i = 0; i < w; i += 12) out += `<path d="M${x + i} ${y + 18} h12 l-1.5 8 h-9 z" fill="${(i / 12) % 2 ? "#ffffff" : "#c8323a"}"/>`;
      out += `<rect x="${x + 8}" y="${y + 30}" width="${w - 40}" height="${h - 32}" fill="#bcdcef" stroke="#7b8a94"/><rect x="${x + w - 26}" y="${y + 30}" width="16" height="${h - 30}" fill="#6a5442"/>`;
      glass = { x: x + 8, y: y + 30, w: w - 40, h: h - 32 };
    } else {
      const gh = tier === 2 ? 58 : 50;
      out += `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${tier === 4 ? "#3a4652" : "#46515c"}"/>`;
      if (tier >= 3) for (let r = y + 18; r < BASE - gh - 8; r += 14) out += `<rect x="${x + 6}" y="${r}" width="${w - 12}" height="8" fill="#9fc6e0" opacity=".8"/>`;
      out += `<rect x="${x + 4}" y="${BASE - gh}" width="${w - 8}" height="${gh}" fill="#a9d3ec" opacity=".9"/>`;
      for (let i = x + 4; i <= x + w - 4; i += 40) out += `<line x1="${i}" y1="${BASE - gh}" x2="${i}" y2="${BASE}" stroke="#46515c" stroke-width="2"/>`;
      out += sign(x + 6, y + 3, w - 12, tier === 4 ? "#ffd23f" : "#151b21", tier === 4 ? "#151b21" : "#ffd23f");
      if (tier === 4) out += `<rect x="${x + w - 26}" y="${y - 26}" width="20" height="30" fill="#2c3540"/><circle cx="${x + w - 16}" cy="${y - 14}" r="7" fill="#ffd23f"/><text x="${x + w - 16}" y="${y - 11}" text-anchor="middle" font-size="8" font-weight="800" fill="#151b21" ${FONT}>${esc(name.trim()[0] || "G")}</text>`;
      glass = { x: x + 6, y: BASE - gh, w: w - 12, h: gh };
    }
    if (glass && show.length) {
      const per = Math.max(1, Math.floor(glass.w / 44));
      show.slice(0, per).forEach((c, i) => { out += `<g data-a="car" data-id="${c.id}" class="hit">${carAt(c, glass.x + 3 + i * 44, BASE - 20, 0.34)}</g>`; });
      if (show.length > per) out += `<text x="${glass.x + glass.w - 4}" y="${glass.y + 10}" text-anchor="end" font-size="8" fill="#17202b" font-weight="700">+${show.length - per}</text>`;
    }
    return out;
  }

  // ---- Galeri binası: seviye süsleri ----
  // Arkada kalanlar (ağaç, ışık hüzmesi) binadan önce çizilir
  function galeriBehind(x, w, tier, n) {
    const top = BASE - H_T[tier];
    let o = "";
    if (n >= 18) [x + w * 0.3, x + w * 0.7].forEach((bx, i) => { o += `<path class="beam" style="animation-delay:${i * 1.5}s;transform-origin:${bx}px ${top}px" d="M${bx - 2} ${top} L${bx - 26} -4 L${bx - 12} -4 L${bx + 2} ${top} Z" fill="#fff6c8" opacity=".35"/>`; });
    if (n >= 11) o += tree(x + w + 2, BASE, 1.05);
    if (n >= 17) o += tree(x - 2, BASE, 0.95);
    return o;
  }
  function galeriFront(x, w, tier, n) {
    const top = BASE - H_T[tier], mid = x + w / 2;
    const L = [];
    L[1] = () => [x + w * 0.25, x + w * 0.75].map(lx => `<path d="M${lx} ${top} v-4 h3" stroke="#3c4650" stroke-width="1" fill="none"/><circle cx="${lx + 3}" cy="${top - 3.4}" r="1.4" fill="#ffe9a8"/>`).join("");
    L[2] = () => `<rect x="${x + w - 28}" y="${BASE - 13}" width="17" height="8" rx="2" fill="#101418"/><text class="led" x="${x + w - 19.5}" y="${BASE - 7}" text-anchor="middle" font-size="5.5" font-weight="800" fill="#ff5a5a" ${FONT}>AÇIK</text>`;
    L[3] = () => pot(x + 2, BASE - 5);
    L[4] = () => [x + 6, x + w - 6].map(lx => `<path d="M${lx} ${top - 2} L${lx - 9} ${top - 26} L${lx + 9} ${top - 26} Z" fill="#fff6c8" opacity=".35"/><rect x="${lx - 2}" y="${top - 4}" width="4" height="4" rx="1" fill="#3c4650"/>`).join("");
    L[5] = () => `<rect x="${x - 3}" y="${top - 20}" width="1.4" height="${BASE - top + 20}" fill="#5d6873"/><path class="wave" d="M${x - 1.6} ${top - 20} h13 l-2.5 4 l2.5 4 h-13 z" fill="#ffd23f"/><text x="${x + 4.5}" y="${top - 13.2}" text-anchor="middle" font-size="5.5" font-weight="800" fill="#151b21" ${FONT}>P</text>`;
    L[6] = () => pot(x + w - 9, BASE - 5);
    L[7] = () => `<rect x="${mid - 8}" y="${BASE - 1}" width="16" height="6" fill="#c0262d"/>`;
    L[8] = () => `<rect x="${x + 1}" y="${top + 1}" width="${w - 2}" height="${H_T[tier] - 2}" fill="none" stroke="#ff4fd8" stroke-width="1.2" opacity=".75" class="glow"/>`;
    L[9] = () => { let s = ""; for (let i = 0; i < 4; i++) s += `<path d="M${mid - 12 + i * 6} ${BASE - 30} h6 l-0.8 5 h-4.4 z" fill="${i % 2 ? "#ffffff" : "#1f5c4f"}"/>`; return s; };
    L[10] = () => [0.2, 0.5, 0.8].map((f, i) => `<rect x="${x + w * f}" y="${top - 12}" width="1" height="12" fill="#5d6873"/><path class="wave" d="M${x + w * f + 1} ${top - 12} l6 2.5 l-6 2.5 z" fill="${["#d9343b", "#2f6fd0", "#2e9d5b"][i]}"/>`).join("");
    L[12] = () => `<rect x="${x + w - 40}" y="${top + 18}" width="32" height="14" rx="1.5" fill="#101418"/><text x="${x + w - 24}" y="${top + 28}" text-anchor="middle" font-size="7" font-weight="800" fill="#ffd23f" ${FONT} class="led">FIRSAT</text>`;
    L[13] = () => `<rect x="${mid - 1}" y="${top - 10}" width="2" height="10" fill="#5d6873"/><rect x="${mid - 24}" y="${top - 24}" width="48" height="14" rx="1.5" fill="#1f4fa8" stroke="#ffffff" stroke-width=".8"/><text x="${mid}" y="${top - 14.2}" text-anchor="middle" font-size="7" font-weight="800" fill="#ffffff" ${FONT}>SIFIR GİBİ</text>`;
    L[14] = () => `<ellipse class="pulse" cx="${mid}" cy="${BASE + 2}" rx="20" ry="2.6" fill="#fff6c8" opacity=".75"/>`;
    L[15] = () => [x + 3, x + w - 3].map((bx, j) => ["#e04a6a", "#2f6fd0", "#ffd23f"].map((c, i) => `<path d="M${bx} ${BASE - 4} Q${bx + (i - 1) * 2} ${BASE - 16} ${bx + (i - 1) * 4} ${BASE - 24 - i * 2}" stroke="#7b8a94" stroke-width=".5" fill="none"/><ellipse class="bob" style="animation-delay:${(i + j) * 0.3}s" cx="${bx + (i - 1) * 4}" cy="${BASE - 27 - i * 2}" rx="2.6" ry="3.2" fill="${c}"/>`).join("")).join("");
    L[16] = () => `<rect x="${x}" y="${top}" width="${w}" height="2.5" fill="#d4a72c"/><rect x="${x}" y="${BASE - 3}" width="${w}" height="3" fill="#d4a72c"/>`;
    L[19] = () => [mid - 12, mid + 10].map(px => `<rect x="${px}" y="${BASE - 6}" width="1.6" height="7" fill="#d4a72c"/><circle cx="${px + 0.8}" cy="${BASE - 6}" r="1.2" fill="#d4a72c"/>`).join("") + `<path d="M${mid - 11} ${BASE - 4} Q${mid - 6} ${BASE} ${mid - 1} ${BASE - 4} M${mid + 1} ${BASE - 4} Q${mid + 6} ${BASE} ${mid + 11} ${BASE - 4}" stroke="#b01e2a" stroke-width="1.2" fill="none"/>`;
    L[20] = () => star(mid, top - (n >= 13 ? 32 : 10), 6, "#ffd23f") + [[-30, 6], [28, 10], [-16, 20], [20, 26]].map(([dx, dy], i) => star(mid + dx, top + dy, 2.2, "#ffffff", "twinkle")).join("");
    let o = "";
    for (let i = 1; i <= n; i++) if (L[i]) o += L[i]();
    return o;
  }

  // ---- Atölye ve ekipman ----
  function garage(x, bays, jobs, doorW) {
    const w = bays * (doorW + 4) + 6, h = 50, y = BASE - h;
    let out = `<g data-a="tab" data-v="atolye" class="hit"><rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#8a949e"/><rect x="${x}" y="${y}" width="${w}" height="10" fill="#5d6873"/>
      <text x="${x + w / 2}" y="${y + 7.6}" text-anchor="middle" font-size="6.5" font-weight="700" fill="#fff" letter-spacing=".8" ${FONT}>ATÖLYE</text>`;
    const inBay = jobs.filter(j => j.inhouse && j.where === "bay");
    for (let i = 0; i < bays; i++) {
      const dx = x + 4 + i * (doorW + 4);
      const job = inBay[i];
      if (job) {
        const car = G.S.cars.find(c => c.id === job.carId);
        out += `<rect x="${dx}" y="${y + 14}" width="${doorW}" height="${h - 14}" fill="#2b3138"/><rect x="${dx + 2}" y="${BASE - 4}" width="${doorW - 4}" height="2" fill="#f0a73a"/>`;
        if (car) out += carAt(car, dx - 1, BASE - 20, doorW / 110);
      } else {
        out += `<rect x="${dx}" y="${y + 14}" width="${doorW}" height="${h - 14}" fill="#c3cad1"/>`;
        for (let k = y + 17; k < BASE; k += 4) out += `<line x1="${dx}" y1="${k}" x2="${dx + doorW}" y2="${k}" stroke="#a6afb8" stroke-width="1"/>`;
      }
    }
    return out + "</g>";
  }
  // Boya kabini (atölyenin üstünde), göçük seti arabası, ekspertiz tabelası
  function equipment(gx, gw, bx, bw, tier) {
    const S = G.S;
    let o = "";
    const ax = gw ? gx : bx; // atölye yoksa binanın yanına
    if (S.upg.paint) {
      const px = gw ? gx + gw - 24 : bx + 2, py = gw ? BASE - 64 : BASE - H_T[tier] - 14;
      o += `<g data-a="tab" data-v="atolye" class="hit"><rect x="${px}" y="${py}" width="22" height="13" rx="1.5" fill="#e2742c"/><rect x="${px + 16}" y="${py - 6}" width="3" height="6" fill="#5d6873"/>
        <text x="${px + 11}" y="${py + 9}" text-anchor="middle" font-size="5.5" font-weight="800" fill="#ffffff" ${FONT}>BOYA</text></g>`;
    }
    if (S.upg.pdr) {
      const cx = ax + (gw ? 4 : -14);
      o += `<g data-a="tab" data-v="atolye" class="hit"><rect x="${cx}" y="${BASE - 9}" width="11" height="6" rx="1" fill="#c8323a"/><rect x="${cx + 1}" y="${BASE - 12}" width="2" height="3" fill="#a3acb6"/><rect x="${cx + 5}" y="${BASE - 13}" width="1.4" height="4" fill="#a3acb6"/><circle cx="${cx + 2}" cy="${BASE - 2}" r="1.3" fill="#1b1f24"/><circle cx="${cx + 9}" cy="${BASE - 2}" r="1.3" fill="#1b1f24"/></g>`;
    }
    if (S.upg.expert) {
      o += `<g><rect x="${bx + 3}" y="${BASE - H_T[tier] + (tier === 0 ? 2 : 18)}" width="26" height="9" rx="1.5" fill="#1f4fa8"/><text x="${bx + 16}" y="${BASE - H_T[tier] + (tier === 0 ? 8.5 : 24.5)}" text-anchor="middle" font-size="5" font-weight="800" fill="#ffffff" ${FONT}>EKSPERTİZ ✓</text></g>`;
    }
    if (S.upg.security) o += `<rect x="${bx + bw - 3}" y="${BASE - 58}" width="2" height="16" fill="#5d6873"/><rect x="${bx + bw - 9}" y="${BASE - 60}" width="9" height="5" rx="1" fill="#2c3540"/><circle class="led" cx="${bx + bw - 8}" cy="${BASE - 57.5}" r="1" fill="#e04a3f"/>`;
    return o;
  }

  // ---- Yıkama ünitesi: seviye parçaları ----
  function wash(x, active, n) {
    const y = BASE - 40, tunnel = n >= 10, w = tunnel ? 52 : 38;
    const arch = ax => `<path d="M${ax} ${BASE} V${y + 8} Q${ax} ${y} ${ax + 8} ${y} H${ax + 30} Q${ax + 38} ${y} ${ax + 38} ${y + 8} V${BASE}" fill="none" stroke="#2f7fc1" stroke-width="5"/>`;
    const L = [];
    L[2] = () => `<circle cx="${x + 5}" cy="${BASE - 16}" r="3" fill="none" stroke="#2e9d5b" stroke-width="1.6"/><path d="M${x + 8} ${BASE - 16} Q${x + 14} ${BASE - 8} ${x + 10} ${BASE - 2}" stroke="#2e9d5b" stroke-width="1" fill="none"/>`;
    L[3] = () => `<path d="M${x + 12} ${BASE - 6} h6 l-1 6 h-4 z" fill="#2f6fd0"/><ellipse cx="${x + 15}" cy="${BASE - 6.5}" rx="2.5" ry="1" fill="#ffd23f"/>`;
    L[4] = () => `<rect x="${x + w / 2 - 6}" y="${y - 12}" width="12" height="8" rx="3" fill="#7cc4f2" stroke="#2f7fc1" stroke-width=".8"/><rect x="${x + w / 2 - 0.6}" y="${y - 4}" width="1.2" height="4" fill="#2f7fc1"/>`;
    L[5] = () => [0, 1, 2].map(i => `<rect x="${x + w - 12 + i * 3}" y="${BASE - 12}" width="2.2" height="5" rx=".6" fill="${["#ffd23f", "#e04a6a", "#2e9d5b"][i]}"/>`).join("") + `<rect x="${x + w - 13}" y="${BASE - 7}" width="10" height="1" fill="#7b8a94"/>`;
    L[6] = () => `<g class="brush" style="transform-origin:${x + 10}px ${BASE - 15}px"><rect x="${x + 8}" y="${y + 10}" width="4" height="${BASE - y - 12}" rx="2" fill="#4f8fe0"/><path d="M${x + 8} ${y + 14} h4 M${x + 8} ${y + 20} h4 M${x + 8} ${y + 26} h4 M${x + 8} ${y + 32} h4" stroke="#c8323a" stroke-width="1.2"/></g>`;
    L[7] = () => [0, 1, 2, 3].map(i => `<circle cx="${x + 14 + i * 4}" cy="${BASE - 2 - (i % 2) * 2}" r="${1.6 + (i % 2)}" fill="#ffffff" opacity=".9"/>`).join("");
    L[8] = () => `<path d="M${x + 2} ${y - 2} a3 3 0 0 1 6 0 z" fill="#f0a73a" class="led"/>`;
    L[9] = () => `<g transform="translate(${x + w - 6} ${y + 10})"><circle r="3.6" fill="#dfe3e8" stroke="#7b8a94" stroke-width=".6"/><g class="spin"><path d="M0 0 L0 -3 L1.2 -1 Z M0 0 L2.6 1.5 L0.4 1.6 Z M0 0 L-2.6 1.5 L-1.6 -0.4 Z" fill="#5d6873"/></g></g>`;
    L[11] = () => `<g class="brush" style="transform-origin:${x + w - 14}px ${BASE - 15}px"><rect x="${x + w - 16}" y="${y + 10}" width="4" height="${BASE - y - 12}" rx="2" fill="#e04a6a"/><path d="M${x + w - 16} ${y + 16} h4 M${x + w - 16} ${y + 24} h4 M${x + w - 16} ${y + 32} h4" stroke="#ffffff" stroke-width="1.2"/></g>`;
    L[12] = () => `<path d="M${x} ${BASE - 2} V${y + 8} Q${x} ${y} ${x + 8} ${y} H${x + w - 8} Q${x + w} ${y} ${x + w} ${y + 8} V${BASE - 2}" fill="none" stroke="#5ef2ff" stroke-width=".9" class="glow"/>`;
    L[13] = () => `<rect x="${x + w + 1}" y="${BASE - 22}" width="2" height="22" fill="#5d6873"/><rect x="${x + w - 1}" y="${BASE - 25}" width="6" height="4" rx="1" fill="#ffd23f"/><path d="M${x + w + 2} ${BASE - 21} Q${x + w + 7} ${BASE - 12} ${x + w + 3} ${BASE - 4}" stroke="#2c3540" stroke-width=".8" fill="none"/>`;
    L[14] = () => [0, 1, 2, 3, 4].map(i => `<circle class="bub" style="animation-delay:${i * 0.35}s" cx="${x + 10 + i * 6}" cy="${y + 20 + (i % 3) * 5}" r="${1.8 + (i % 2)}" fill="#ffffff" opacity=".85"/>`).join("");
    L[15] = () => [0, 1].map(i => `<circle cx="${x + 4 + i * 6}" cy="${BASE - 4}" r="2.4" fill="#1b1f24" stroke="#5d6873" stroke-width=".7"/>`).join("");
    L[16] = () => `<path d="M${x + 4} ${y - 2} l10 -6 l10 6 z" fill="#1f3a6b" stroke="#a9d3ec" stroke-width=".5"/>`;
    L[17] = () => `<rect x="${x + w - 4}" y="${y - 16}" width="1" height="14" fill="#5d6873"/><path class="wave" d="M${x + w - 3} ${y - 16} l7 2.5 l-7 2.5 z" fill="#2f7fc1"/>`;
    L[19] = () => `<rect class="hue" x="${x + 3}" y="${y + 3}" width="${w - 6}" height="${BASE - y - 3}" rx="6" fill="#ff7ad9" opacity=".18"/>`;
    L[20] = () => `<rect x="${x + w / 2 - 15}" y="${y - 22}" width="30" height="8" rx="1.5" fill="#d4a72c"/><text x="${x + w / 2}" y="${y - 16}" text-anchor="middle" font-size="5.5" font-weight="800" fill="#151b21" ${FONT}>DETAY</text>`;
    let out = `<g data-a="fac" data-v="yikama" class="hit">`;
    if (n >= 19) out += L[19]();
    if (tunnel) out += `<rect x="${x}" y="${y}" width="${w}" height="${BASE - y}" rx="8" fill="#cfe8f7" opacity=".7"/>` + arch(x + 14);
    out += arch(x);
    for (let i = 2; i <= n; i++) if (L[i] && i !== 19) out += L[i]();
    out += `<text x="${x + w / 2}" y="${y - 3}" text-anchor="middle" font-size="6" font-weight="700" fill="#2f7fc1" ${FONT}>${tunnel ? "KÖPÜK TÜNELİ" : "YIKAMA"}</text>`;
    if (active || n >= 18) for (let i = 0; i < 5; i++) out += `<circle class="drop" cx="${x + 8 + i * 5.5}" cy="${y + 12 + (i % 2) * 6}" r="1.6" fill="#7cc4f2" style="animation-delay:${i * 0.18}s"/>`;
    return out + "</g>";
  }

  // ---- Usta çardağı: seviye parçaları ----
  function cardak(x, n, resting) {
    const w = 54, top = BASE - 34, tx = x + 18, ty = BASE - 11; // masa
    const L = [];
    L[2] = () => `<rect x="${tx}" y="${ty}" width="18" height="2" fill="#7a4f2c"/><rect x="${tx + 8}" y="${ty + 2}" width="2" height="9" fill="#7a4f2c"/>`;
    L[3] = () => `<rect x="${x + 9}" y="${BASE - 6}" width="6" height="1.6" fill="#9b6a3e"/><rect x="${x + 11}" y="${BASE - 5}" width="1.4" height="5" fill="#9b6a3e"/><rect x="${x + 39}" y="${BASE - 6}" width="6" height="1.6" fill="#9b6a3e"/><rect x="${x + 41}" y="${BASE - 5}" width="1.4" height="5" fill="#9b6a3e"/>`;
    L[4] = () => { let s = ""; for (let i = 0; i < 6; i++) s += `<rect x="${tx + i * 3}" y="${ty - 0.8}" width="3" height="1.6" fill="${i % 2 ? "#ffffff" : "#d9343b"}"/>`; return s; };
    L[5] = () => `<path d="M${tx + 2} ${ty - 1} h4 l-.6 -5 h-2.8 z" fill="#d4a72c"/><circle cx="${tx + 4}" cy="${ty - 7}" r="1.3" fill="#d4a72c"/>`;
    L[6] = () => pot(x, BASE - 5) + pot(x + w - 7, BASE - 5);
    L[7] = () => `<rect x="${tx + 9}" y="${ty - 2.4}" width="7" height="2" fill="#6b3a1f"/><path d="M${tx + 12.5} ${ty - 2.4} v2" stroke="#e8d6b0" stroke-width=".4"/>`;
    L[8] = () => `<rect x="${x + w - 12}" y="${top + 4}" width="7" height="4.5" rx="1" fill="#2c3540"/><circle cx="${x + w - 9.5}" cy="${top + 6.2}" r="1.2" fill="#a3acb6"/>`;
    L[9] = () => `<rect x="${x + 6}" y="${BASE - 1.5}" width="${w - 12}" height="2" fill="#a83a3a"/><path d="M${x + 8} ${BASE - 0.5} h${w - 16}" stroke="#ffd23f" stroke-width=".5" stroke-dasharray="2 2"/>`;
    L[10] = () => { let s = `<path d="M${x} ${top + 1} Q${x + w / 2} ${top + 6} ${x + w} ${top + 1}" stroke="#3c4650" stroke-width=".4" fill="none"/>`; for (let i = 0; i < 7; i++) { const u = i / 6; s += `<circle class="twinkle" style="animation-delay:${i * 0.2}s" cx="${x + u * w}" cy="${top + 1 + 5 * 4 * u * (1 - u) * 0.5 + 0.8}" r="1" fill="${["#ffd23f", "#ff7ad9", "#7cc4f2"][i % 3]}"/>`; } return s; };
    L[11] = () => `<g transform="translate(${x + 3} ${BASE - 2})"><ellipse cx="4" cy="0" rx="4" ry="1.8" fill="#7b8a94"/><circle cx="8" cy="-1.4" r="1.6" fill="#7b8a94"/><path d="M7.2 -2.8 l.4 -1 l.6 1 M8.6 -2.8 l.4 -1 l.6 1" fill="#7b8a94"/><path d="M0 0 q-2 -2 -1 -3" stroke="#7b8a94" stroke-width=".7" fill="none"/></g>`;
    L[12] = () => `<path d="M${x + w - 9} ${BASE} q-2 -3 0 -5 q-1 -1 1 -2 h2 q2 1 1 2 q2 2 0 5 z" fill="#b5623a"/>`;
    L[13] = () => `<rect x="${x + 2}" y="${top + 2}" width="7" height="22" fill="#e8d6b0" opacity=".55"/><path d="M${x + 3.5} ${top + 2} v22 M${x + 6} ${top + 2} v22" stroke="#c9b48a" stroke-width=".5"/>`;
    L[14] = () => `<path d="M${x + 30} ${top + 1} v4" stroke="#5d6873" stroke-width=".5"/><path d="M${x + 27} ${top + 9} q3 -5 6 0 z" fill="none" stroke="#d4a72c" stroke-width=".7"/><rect x="${x + 27}" y="${top + 9}" width="6" height=".8" fill="#d4a72c"/><circle cx="${x + 30}" cy="${top + 7.5}" r="1" fill="#ffd23f"/>`;
    L[15] = () => { let s = ""; for (let i = 0; i < 4; i++) s += `<circle cx="${x + 10 + i * 11}" cy="${top + 2}" r="1.6" fill="#6b2d6b"/><circle cx="${x + 11 + i * 11}" cy="${top + 3.6}" r="1.2" fill="#7d3a7d"/>`; return s; };
    L[16] = () => `<g transform="translate(${x + 5} ${top + 10})"><circle r="2.8" fill="#dfe3e8" stroke="#7b8a94" stroke-width=".5"/><g class="spin"><path d="M0 0 L0 -2.4 L1 -0.8 Z M0 0 L2.1 1.2 L0.3 1.3 Z M0 0 L-2.1 1.2 L-1.3 -0.3 Z" fill="#5d6873"/></g></g>`;
    L[17] = () => `<rect x="${x + w - 16}" y="${BASE - 7}" width="9" height="3" fill="#2c3540"/><path d="M${x + w - 15} ${BASE - 4} l-1 4 M${x + w - 8} ${BASE - 4} l1 4" stroke="#2c3540" stroke-width=".8"/><path d="M${x + w - 14} ${BASE - 7} h7" stroke="#e2742c" stroke-width="1"/><circle class="smoke" cx="${x + w - 11}" cy="${BASE - 10}" r="2" fill="#c3cad1" opacity=".6"/>`;
    L[18] = () => `<path d="M${x + 44} ${top + 1} v3" stroke="#5d6873" stroke-width=".5"/><rect x="${x + 42}" y="${top + 4}" width="4" height="5" rx="1" fill="#2c3540"/><rect class="led" x="${x + 42.8}" y="${top + 5}" width="2.4" height="3" fill="#ffd23f"/>`;
    L[19] = () => { let s = ""; for (let i = 0; i < 6; i++) s += `<circle cx="${x + 4 + i * 9}" cy="${top - 7 + (i % 2) * 2}" r="1.3" fill="#ff8ab5"/>`; return s; };
    L[20] = () => `<path d="M${x + 6} ${top + 10} Q${x + 14} ${top + 18} ${x + 22} ${top + 10}" stroke="#e2742c" stroke-width="2" fill="none"/>`;
    let o = `<g data-a="fac" data-v="cardak" class="hit">`;
    o += `<rect x="${x + 2}" y="${top}" width="3" height="34" fill="#8a5a33"/><rect x="${x + w - 5}" y="${top}" width="3" height="34" fill="#8a5a33"/>`;
    o += `<rect x="${x - 2}" y="${top - 3}" width="${w + 4}" height="4" fill="#9b6a3e"/>`;
    for (let i = x; i < x + w; i += 6) o += `<rect x="${i}" y="${top - 6}" width="2" height="9" fill="#7a4f2c"/>`;
    const leaves = 6 + n; // her seviye asmayı biraz daha sarar
    for (let i = 0; i < leaves; i++) o += `<circle cx="${(x + 2 + (i * 37) % (w - 2)).toFixed(1)}" cy="${(top - 5 + (i % 3) * 3).toFixed(1)}" r="${(2.6 + (i % 2)).toFixed(1)}" fill="${i % 3 ? "#3f8f46" : "#57a85a"}"/>`;
    for (let i = 2; i <= n; i++) if (L[i]) o += L[i]();
    resting.slice(0, 5).forEach((u, i) => { o += sitter(x + 12 + i * 8 + (i > 1 ? 14 : 0), BASE - 1, u.id || i); });
    if (n >= 5) o += `<rect x="${tx + 13}" y="${ty - 3}" width="1.4" height="3" fill="#c0392b" opacity=".85"/><rect x="${tx + 16}" y="${ty - 3}" width="1.4" height="3" fill="#c0392b" opacity=".85"/>`;
    o += `<text x="${x + w / 2}" y="${top - 10}" text-anchor="middle" font-size="6" font-weight="700" fill="#3f6b2f" ${FONT}>ÇARDAK</text>`;
    return o + "</g>";
  }

  // ---- Vitrin çadırı (küçük adreslerde) ----
  function canopy(x, w, show) {
    const y = BASE - 44;
    let out = `<path d="M${x} ${y + 10} L${x + w / 2} ${y} L${x + w} ${y + 10} Z" fill="#f7f3e8" stroke="#c8323a" stroke-width="1.5"/>
      <rect x="${x + 2}" y="${y + 10}" width="2" height="34" fill="#7b8a94"/><rect x="${x + w - 4}" y="${y + 10}" width="2" height="34" fill="#7b8a94"/>
      <text x="${x + w / 2}" y="${y + 8}" text-anchor="middle" font-size="5.5" font-weight="700" fill="#c8323a" ${FONT}>VİTRİN</text>`;
    const per = Math.max(1, Math.floor((w - 6) / 44));
    show.slice(0, per).forEach((c, i) => { out += `<g data-a="car" data-id="${c.id}" class="hit">${carAt(c, x + 3 + i * 44, BASE - 19, 0.34)}</g>`; });
    if (show.length > per) out += `<text x="${x + w - 6}" y="${y + 20}" text-anchor="end" font-size="8" font-weight="700" fill="#17202b">+${show.length - per}</text>`;
    return out;
  }

  // ---- Ön otopark: seviye parçaları ----
  function parkingDecor(n, lotTop, roadY, slots, rows, oe) {
    const L = [];
    const slotXY = i => [LOT_X + (i % PER_ROW) * SLOT_W, lotTop + Math.floor(i / PER_ROW) * ROW_H];
    L[1] = () => `<rect x="6" y="${lotTop + 6}" width="1.4" height="14" fill="#5d6873"/><rect x="2" y="${lotTop}" width="9.5" height="9.5" rx="1.5" fill="#2f6fd0"/><text x="6.75" y="${lotTop + 7.5}" text-anchor="middle" font-size="7.5" font-weight="800" fill="#ffffff" ${FONT}>P</text>`;
    L[2] = () => { let s = ""; for (let i = 0; i < slots; i++) { const [sx, sy] = slotXY(i); s += `<text x="${sx + 3}" y="${sy + 28}" font-size="4.2" font-weight="700" fill="#ffffff" opacity=".8" ${FONT}>${i + 1}</text>`; } return s; };
    L[3] = () => { let s = ""; for (let i = 0; i < slots; i++) { const [sx, sy] = slotXY(i); s += `<rect x="${sx + 22}" y="${sy + 25}" width="10" height="1.6" rx=".8" fill="#ffd23f"/>`; } return s; };
    L[5] = () => { let s = ""; for (let bx = 18; bx < W - 14; bx += 22) s += `<circle cx="${bx}" cy="${roadY - 1}" r="2.2" fill="#3f8f46"/><circle cx="${bx + 2.5}" cy="${roadY - 1.6}" r=".9" fill="${bx % 3 ? "#ff8ab5" : "#ffd23f"}"/>`; return s; };
    // 8 (bariyer), 11 (hız tümseği) ve 19 (babalar) kaldırımda: parkingDecorRoad
    L[7] = () => `<rect x="1" y="${roadY - 15}" width="12" height="13" fill="#e8ecef" stroke="#7b8a94" stroke-width=".6"/><rect x="2.5" y="${roadY - 12}" width="9" height="4.5" fill="#a9d3ec"/><rect x="0" y="${roadY - 16.5}" width="14" height="2" fill="#c8323a"/>`;
    L[9] = () => tree(W - 7, roadY - 1, 0.55) + tree(W - 7, lotTop + 22, 0.45);
    L[13] = () => `<rect x="${W - 12}" y="${lotTop + 3}" width="7" height="11" rx="1.5" fill="#2e9d5b"/><rect x="${W - 10.5}" y="${lotTop + 5}" width="4" height="3" fill="#c8f7d8"/><path d="M${W - 8.5} ${lotTop + 9} l-1 2 h2 l-1 2" stroke="#ffffff" stroke-width=".6" fill="none"/>`;
    L[14] = () => { let s = ""; for (let ax = 40; ax < W - 30; ax += 70) s += `<path d="M${ax} ${roadY - 3} h8 l-2 -1.6 M${ax + 8} ${roadY - 3} l-2 1.6" stroke="#ffffff" stroke-width=".9" fill="none" opacity=".85"/>`; return s; };
    L[15] = () => oe.light ? [5, W - 5].map((lx, i) => `<path class="wave" d="M${lx + 1} ${lotTop - 2} h5 v9 l-2.5 -2 l-2.5 2 z" fill="${i ? "#2f6fd0" : "#ffd23f"}"/>`).join("") : "";
    L[16] = () => `<rect x="${W - 12}" y="${lotTop - 12}" width="6" height="3.5" rx="1" fill="#2c3540"/><circle class="led" cx="${W - 11}" cy="${lotTop - 10.2}" r=".8" fill="#e04a3f"/>`;
    L[17] = () => `<rect class="glow" x="${LOT_X - 2}" y="${lotTop - 1}" width="${PER_ROW * SLOT_W - 4}" height="1.2" fill="#5ef2ff" opacity=".8"/>`;
    L[20] = () => { const [sx, sy] = slotXY(0); return `<rect x="${sx - 1}" y="${sy + 1}" width="${SLOT_W - 4}" height="25" rx="2" fill="none" stroke="#d4a72c" stroke-width="1.4"/><text x="${sx + SLOT_W - 8}" y="${sy + 27.5}" text-anchor="end" font-size="4.5" font-weight="800" fill="#d4a72c" ${FONT}>VIP</text>`; };
    let o = "";
    for (let i = 1; i <= n; i++) if (L[i]) o += L[i]();
    return o;
  }
  // Tente: 6. seviyede ilk sıra, 12'de ikinci sıra, 18'de bütün sıralar
  function tentes(n, lotTop, slots, rows) {
    if (n < 6) return "";
    const cover = n >= 18 ? rows : n >= 12 ? Math.min(2, rows) : 1;
    let o = `<g data-a="fac" data-v="otopark" class="hit" opacity=".9">`;
    for (let r = 0; r < cover; r++) {
      const inRow = Math.min(PER_ROW, slots - r * PER_ROW);
      if (inRow <= 0) break;
      const tw = inRow * SLOT_W - 4, ty = lotTop + r * ROW_H;
      o += `<rect x="${LOT_X - 2}" y="${ty - 3}" width="${tw}" height="5" fill="#f7f3e8"/>`;
      for (let i = LOT_X - 2; i < LOT_X - 2 + tw; i += 12) o += `<rect x="${i}" y="${ty - 3}" width="6" height="5" fill="${r % 2 ? "#2e9d5b" : "#2f6fd0"}"/>`;
      o += `<rect x="${LOT_X - 2}" y="${ty + 2}" width="1.5" height="22" fill="#7b8a94"/><rect x="${LOT_X - 3.5 + tw}" y="${ty + 2}" width="1.5" height="22" fill="#7b8a94"/>`;
    }
    return o + `</g>`;
  }

  UI.scene = function () {
    const S = G.S, L = G.loc(), tier = S.loc;
    const show = S.cars.filter(c => c.show);
    const lot = S.cars.filter(c => !c.show && c.status === "lot");
    const shop = S.cars.filter(c => c.status === "shop");
    const slots = Math.max(lot.length, G.capacity() - Math.min(G.showSlots(), G.capacity()) - shop.length, 1);
    const rows = Math.ceil(slots / PER_ROW);
    const lotTop = BASE + 12;
    const roadY = lotTop + rows * ROW_H + 6;
    const H = roadY + 28;
    const [s1, s2] = sky(S.min);
    const t = (S.min - G.OPEN) / (G.CLOSE - G.OPEN);
    const sunX = 30 + t * 340, sunY = 52 - Math.sin(t * Math.PI) * 38;
    const wl = G.facLevel("yikama"), cl = G.facLevel("cardak"), gl = G.facLevel("galeri"), ol = G.facLevel("otopark");
    // çatıdaki süsler sığsın diye gerekirse üstte gökyüzü payı bırakılır
    const btop = BASE - H_T[tier];
    let minY = tier === 0 ? btop - 15 : btop;
    if (tier === 4) minY = Math.min(minY, btop - 26);
    if (gl >= 4) minY = Math.min(minY, btop - 26);
    if (gl >= 13) minY = Math.min(minY, btop - 24);
    if (gl >= 20) minY = Math.min(minY, btop - (gl >= 13 ? 38 : 16));
    if (wl >= 20) minY = Math.min(minY, BASE - 62);
    if (S.upg.paint) minY = Math.min(minY, BASE - 70);
    const pad = Math.max(0, Math.ceil(6 - minY));
    let svg = `<svg class="scene" viewBox="0 ${-pad} ${W} ${H + pad}" role="img" aria-label="${esc(S.name)}: ${esc(L.name)}, parkta ${lot.length} araç">
      <defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s1}"/><stop offset="1" stop-color="${s2}"/></linearGradient></defs>
      <rect y="${-pad}" width="${W}" height="${lotTop + pad}" fill="url(#sky)"/>
      <circle cx="${sunX.toFixed(1)}" cy="${sunY.toFixed(1)}" r="11" fill="${t > 0.8 ? "#ffb070" : "#ffe08a"}" opacity=".9"/>
      <g fill="#ffffff" opacity=".75"><ellipse cx="${(90 + t * 40).toFixed(0)}" cy="28" rx="22" ry="6"/><ellipse cx="${(290 - t * 30).toFixed(0)}" cy="18" rx="16" ry="5"/></g>`;
    // patronun uçağı gökyüzünde
    const pl = G.bestOf("ucak");
    if (pl) {
      const py = 14 - pad * 0.3;
      svg += pl.id === "uc2"
        ? `<g class="fly" data-a="lux"><g transform="translate(0 ${py})"><rect x="-14" y="0" width="28" height="1.2" fill="#3c4650" class="rotor"/><path d="M-8 3 q2 -1 9 -1 q6 0 7 5 q-1 4 -7 4 h-5 q-4 0 -4 -4 z" fill="#1f4fa8"/><rect x="-16" y="5" width="9" height="2" fill="#1f4fa8"/></g></g>`
        : `<g class="fly" data-a="lux"><g transform="translate(0 ${py})"><path d="M-14 4 q1 -3 8 -3 h18 q5 0 7 3 q-2 3 -7 3 h-24 z" fill="#ffffff" stroke="#9aa3ac" stroke-width=".5"/><path d="M-12 2 l-3 -6 h3 l5 5 z" fill="${pl.id === "uc1" ? "#c8323a" : "#1f4fa8"}"/><path d="M-2 5 l6 6 h3 l-4 -6 z" fill="#c3cad1"/></g></g>`;
    }
    // futbol takımı varsa uzakta stadyum ve ışıkları
    if (G.owns("i5")) svg += `<g data-a="lux" opacity=".85"><path d="M300 ${BASE - 30} Q345 ${BASE - 52} 392 ${BASE - 30} V${BASE} H300 Z" fill="#c8323a"/><path d="M304 ${BASE - 31} Q345 ${BASE - 49} 388 ${BASE - 31}" stroke="#ffd23f" stroke-width="2" fill="none"/>${[302, 390].map(x => `<rect x="${x}" y="${BASE - 62}" width="1.6" height="32" fill="#5d6873"/><rect x="${x - 4}" y="${BASE - 66}" width="10" height="5" fill="#fff6c8"/>`).join("")}</g>`;
    if (tier >= 1) {
      svg += `<g fill="#b9c7d3" opacity="${tier >= 2 ? 0.9 : 0.5}">`;
      [[8, 40], [40, 62], [70, 30], [300, 54], [330, 70], [362, 44]].forEach(([bx, bh], i) => { svg += `<rect x="${bx}" y="${BASE - bh - 10}" width="${26 + (i % 2) * 6}" height="${bh + 10}"/>`; });
      svg += `</g>`;
    }

    // yerleşim: çardak | atölye | bina | (vitrin çadırı) | yıkama — sığmazsa parçalar daralır
    let doorW = 26, canopyW = tier < 2 && G.showSlots() ? 94 : 0, cw = cl ? 54 : 0;
    const ww = wl ? (wl >= 10 ? 52 : 38) : 0;
    const want = [110, 170, 230, 260, 280][tier];
    let gw = 0, gaps = 0, bw = 0;
    const fit = () => {
      gw = S.upg.bays ? S.upg.bays * (doorW + 4) + 6 : 0;
      gaps = [gw, ww, cw, canopyW].filter(Boolean).length * 6;
      bw = Math.min(want, W - 8 - gw - ww - cw - canopyW - gaps);
    };
    fit();
    if (bw < 96 && canopyW) { canopyW = 50; fit(); }
    if (bw < 96) { doorW = 20; fit(); }
    bw = Math.max(80, bw);
    const total = gw + bw + canopyW + ww + cw + gaps;
    let x = Math.round((W - total) / 2);
    const cx0 = x, gx0 = x + (cw ? cw + 6 : 0), bx0 = gx0 + (gw ? gw + 6 : 0);
    svg += galeriBehind(bx0, bw, tier, gl);
    if (cw) { svg += cardak(x, cl, G.ustas().filter(u => u.resting)); x += cw + 6; }
    if (gw) { svg += garage(x, S.upg.bays, S.jobs, doorW); x += gw + 6; }
    const showIn = tier >= 2 ? show : (tier === 1 && !canopyW ? show : []);
    svg += `<g data-a="fac" data-v="galeri" class="hit">${building(tier, x, bw, S.name, showIn)}${galeriFront(x, bw, tier, gl)}</g>`;
    svg += equipment(gx0, gw, bx0, bw, tier);
    x += bw + 6;
    if (canopyW) { svg += canopy(x, canopyW, show); x += canopyW + 6; }
    if (ww) svg += wash(x, S.jobs.some(j => j.where === "wash"), wl);
    void cx0;

    // park alanı
    const oe = G.facEff("otopark");
    const dirt = tier === 0 && !oe.asphalt;
    const ground = dirt ? "#bfa27c" : oe.asphalt ? "#828b94" : "#9aa3ac";
    svg += `<rect x="0" y="${BASE}" width="${W}" height="${roadY - BASE}" fill="${ground}" data-a="fac" data-v="otopark" class="hit"/>`;
    svg += `<rect x="0" y="${BASE}" width="${W}" height="4" fill="${dirt ? "#a88c68" : "#c3cad1"}"/>`;
    if (gl >= 7) svg += `<rect x="${bx0 + bw / 2 - 8}" y="${BASE}" width="16" height="4" fill="#c0262d"/>`;
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
    }
    svg += parkingDecor(ol, lotTop, roadY, slots, rows, oe);
    for (let i = 0; i < slots; i++) {
      const car = lot[i];
      if (!car) continue;
      const r = Math.floor(i / PER_ROW), col = i % PER_ROW;
      const sx = LOT_X + col * SLOT_W, sy = lotTop + r * ROW_H;
      const cx = sx + 4, cy = sy + 3;
      const tag = car.price
        ? `<rect x="${cx + 26}" y="${cy - 6}" width="20" height="9" rx="1.5" fill="#ffd23f" transform="rotate(4 ${cx + 36} ${cy - 2})"/><text x="${cx + 36}" y="${cy + 0.8}" text-anchor="middle" font-size="6.2" font-weight="700" fill="#1d1a10" font-family="Saira, sans-serif" transform="rotate(4 ${cx + 36} ${cy - 2})">${tiny(car.price)}</text>`
        : `<rect x="${cx + 28}" y="${cy - 6}" width="13" height="9" rx="1.5" fill="#ffffff" stroke="#e04a3f" stroke-width=".8"/><text x="${cx + 34.5}" y="${cy + 0.8}" text-anchor="middle" font-size="7" font-weight="800" fill="#e04a3f">?</text>`;
      svg += `<g data-a="car" data-id="${car.id}" class="hit">${carAt(car, cx, cy, CAR_S)}${tag}</g>`;
      const c = offerBy[car.id];
      if (c) svg += `<g data-a="cust" data-id="${c.id}" class="hit">${person(sx + SLOT_W - 8, sy + 22, c.id)}<g class="bubble"><rect x="${sx + SLOT_W - 12}" y="${sy - 7}" width="11" height="8" rx="3" fill="#ffffff" stroke="#1d8549"/><text x="${sx + SLOT_W - 6.5}" y="${sy - 0.8}" text-anchor="middle" font-size="6" font-weight="800" fill="#1d8549">₺</text></g></g>`;
    }
    svg += tentes(ol, lotTop, slots, rows);
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
    if (ol >= 8) svg += parkingDecorRoad(ol, roadY);
    // patronun kendi arabası kapının önünde
    const pc = G.bestOf("araba");
    if (pc) {
      const px = 150;
      svg += `<g data-a="lux" class="hit">${carAt({ m: pc.model, color: pc.color, clean: 100, body: 100, motor: 100, painted: 0, fuel: G.M[pc.model].fuels[0] }, px, roadY + 4, CAR_S)}<rect x="${px + 14}" y="${roadY + 22}" width="18" height="5" rx="1" fill="#ffd23f"/><text x="${px + 23}" y="${roadY + 25.8}" text-anchor="middle" font-size="3.8" font-weight="800" fill="#151b21" ${FONT}>PATRON</text></g>`;
    }
    const out = S.jobs.filter(j => !j.inhouse).length;
    if (out) svg += `<g data-a="tab" data-v="atolye" class="hit"><rect x="${W - 92}" y="${roadY + 8}" width="86" height="13" rx="6.5" fill="#ffffff"/><text x="${W - 49}" y="${roadY + 17}" text-anchor="middle" font-size="7.5" font-weight="700" fill="#17202b">Dış serviste: ${out} araç</text></g>`;
    if (G.boostActive()) svg += `<g><rect x="6" y="${roadY + 8}" width="104" height="13" rx="6.5" fill="#ffd23f"/><text x="58" y="${roadY + 17}" text-anchor="middle" font-size="7.5" font-weight="700" fill="#1d1a10">Kampanya: 2× müşteri</text></g>`;
    return svg + `</svg>`;
  };
  // Kaldırımdaki parçalar (bariyer, hız tümseği, babalar) yolun üstüne çizilir
  function parkingDecorRoad(n, roadY) {
    let s = "";
    if (n >= 8) { s += `<rect x="11" y="${roadY - 2}" width="2.4" height="5" fill="#2c3540"/>`; for (let i = 0; i < 6; i++) s += `<rect x="${13 + i * 4.5}" y="${roadY - 0.6}" width="4.5" height="1.6" fill="${i % 2 ? "#ffffff" : "#d9343b"}"/>`; }
    if (n >= 11) for (let i = 0; i < 7; i++) s += `<rect x="${44 + i * 3.6}" y="${roadY + 1.2}" width="3.6" height="1.8" fill="${i % 2 ? "#151b21" : "#ffd23f"}"/>`;
    if (n >= 19) for (let bx = 80; bx < W - 20; bx += 40) s += `<rect x="${bx}" y="${roadY}" width="2" height="5" rx="1" fill="#d4a72c"/>`;
    return s;
  }
})(window.G = window.G || {});
