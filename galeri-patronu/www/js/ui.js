/* Galeri Patronu — arayüz */
(function (G) {
  "use strict";
  const R = G.R;
  const UI = G.UI = { tab: "galeri", sub: { piyasa: "ilan", patron: "tesis" }, sheet: null, draft: {}, sort: "yeni", dragging: false, sig: "", sheetSig: "", armed: null };
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
  // Kasa tipine göre kapı çizgileri, kollar, ayna ve tampon noktaları
  const META = {
    sedan:  { seams: [36, 63.5, 92], handles: [54, 80], mirror: [87, 21], belt: 23, rear: 6, front: 114, deck: 24 },
    hatch:  { seams: [45.5, 82], handles: [36, 66], mirror: [77, 21], belt: 23, rear: 13, front: 110, deck: 14 },
    wagon:  { seams: [37.5, 59.5, 86], handles: [50, 74], mirror: [81, 21], belt: 23, rear: 6, front: 114, deck: 14 },
    suv:    { seams: [39.5, 61.5, 90], handles: [52, 78], mirror: [85, 18], belt: 20, rear: 6, front: 114, deck: 9 },
    coupe:  { seams: [43, 90], handles: [72], mirror: [84, 23], belt: 25, rear: 8, front: 114, deck: 26 },
    van:    { seams: [48, 68, 94], handles: [58, 82], mirror: [90, 18], belt: 20, rear: 6, front: 114, deck: 7 },
    pickup: { seams: [52, 65.5, 91], handles: [58, 80], mirror: [86, 18], belt: 20, rear: 6, front: 114, deck: 22 }
  };
  const WHEEL = { ultra: "gold", ekonomi: "steel", ticari: "steel", aile: "alloy", suv: "offroad", elektrik: "aero", premium: "multi", luks: "multi", spor: "sport", klasik: "white" };
  const BRAND = { Anka: "#c8102e", Ferro: "#1f4fa8", Rivo: "#d4a72c", Kaizen: "#5d6873", Hanbit: "#2e6e4a", Volta: "#2f6fd0", Nordik: "#1d2024", Stahl: "#9aa3ac", Mirage: "#7b2d8e" };
  let cid = 0;

  function spokes(n, r0, r1, color, w) {
    let o = "";
    for (let k = 0; k < n; k++) {
      const a = (k / n) * Math.PI * 2 - Math.PI / 2, c = Math.cos(a), sn = Math.sin(a);
      o += `<line x1="${(c * r0).toFixed(2)}" y1="${(sn * r0).toFixed(2)}" x2="${(c * r1).toFixed(2)}" y2="${(sn * r1).toFixed(2)}" stroke="${color}" stroke-width="${w}" stroke-linecap="round"/>`;
    }
    return o;
  }
  function wheelSVG(cx, r, style) {
    const rr = r * 0.62;
    let o = `<g transform="translate(${cx} 38)"><circle r="${r + 1.3}" fill="#0e1013"/><circle r="${r}" fill="#17191d"/><circle r="${r - 1.1}" fill="none" stroke="#2c3137" stroke-width=".8"/>`;
    switch (style) {
      case "steel": o += `<circle r="${rr}" fill="#8d959e"/><circle r="${rr * 0.82}" fill="#b9c0c8"/><circle r="${rr * 0.55}" fill="none" stroke="#8d959e" stroke-width=".6"/><circle r="${rr * 0.25}" fill="#6b737c"/>`; break;
      case "alloy": o += `<circle r="${rr}" fill="#c9cfd6"/><circle r="${rr * 0.86}" fill="#363c44"/>${spokes(5, 1.2, rr * 0.9, "#d7dce2", 1.9)}<circle r="1.3" fill="#9aa3ac"/>`; break;
      case "multi": o += `<circle r="${rr + 0.4}" fill="#e3e7eb"/><circle r="${rr * 0.88}" fill="#2b3036"/>${spokes(10, 1, rr * 0.92, "#eef1f4", 0.8)}<circle r="1.3" fill="#c9cfd6"/>`; break;
      case "sport": o += `<circle r="${rr + 0.3}" fill="#8d959e"/><circle r="${rr}" fill="#1d2126"/><path d="M${(-rr * 0.75).toFixed(2)} ${(-rr * 0.45).toFixed(2)} A${(rr * 0.85).toFixed(2)} ${(rr * 0.85).toFixed(2)} 0 0 0 ${(-rr * 0.75).toFixed(2)} ${(rr * 0.45).toFixed(2)}" stroke="#d9343b" stroke-width="2.4" fill="none"/>${spokes(5, 1.2, rr * 0.95, "#5d6873", 1.7)}<circle r="1.2" fill="#d9343b"/>`; break;
      case "offroad": o += `<circle r="${r}" fill="none" stroke="#24282d" stroke-width="1.6" stroke-dasharray="1.6 1.4"/><circle r="${rr}" fill="#4a5058"/>${[0, 1, 2, 3, 4, 5].map(k => { const a = k * Math.PI / 3; return `<circle cx="${(Math.cos(a) * rr * 0.6).toFixed(2)}" cy="${(Math.sin(a) * rr * 0.6).toFixed(2)}" r=".9" fill="#2b3036"/>`; }).join("")}<circle r="1.4" fill="#7b8a94"/>`; break;
      case "white": o += `<circle r="${r * 0.8}" fill="#f2f2ee"/><circle r="${rr * 0.85}" fill="#d7dce2"/><circle r="${rr * 0.55}" fill="#eef1f4" stroke="#9aa3ac" stroke-width=".4"/><circle r="1.1" fill="#9aa3ac"/>`; break;
      case "gold": o += `<circle r="${rr + 0.4}" fill="#d4a72c"/><circle r="${rr * 0.86}" fill="#1d2126"/>${spokes(7, 1, rr * 0.92, "#e8c25a", 1.2)}<circle r="1.3" fill="#d4a72c"/>`; break;
      case "aero": o += `<circle r="${rr}" fill="#dfe3e8"/><path d="M-${rr * 0.2} -${rr * 0.8} q${rr * 0.5} ${rr * 0.4} ${rr * 0.3} ${rr * 0.9} M${rr * 0.75} ${rr * 0.25} q-${rr * 0.6} 0 -${rr * 0.75} ${rr * 0.55} M-${rr * 0.65} ${rr * 0.45} q${rr * 0.1} -${rr * 0.55} -${rr * 0.15} -${rr * 0.85}" stroke="#5d6873" stroke-width=".9" fill="none"/><circle r="1.1" fill="#2f9fff"/>`; break;
    }
    return o + "</g>";
  }

  UI.carShape = function (car) {
    const md = G.M[car.m];
    const sh = SHAPES[md.body] || SHAPES.sedan;
    const mt = META[md.body] || META.sedan;
    const seg = md.seg;
    const col = (G.COLORS.find(c => c.name === car.color) || G.COLORS[0]).hex;
    const dark = shade(col, 0.55), r = sh.r || 8;
    const id = "c" + (++cid);
    const chrome = seg === "klasik" || seg === "luks" || seg === "ultra";
    const ev = car.fuel === "elektrik";
    const body = car.body == null ? 90 : car.body;
    const clean = car.clean == null ? 100 : car.clean;
    const contrast = ["Beyaz", "Gümüş", "Sarı"].includes(car.color) ? "#1d2024" : "#ffffff";
    const head = sh.head || 108, rear = mt.rear, front = mt.front;
    let o = `<defs><clipPath id="${id}b"><path d="${sh.d}"/></clipPath><clipPath id="${id}w">${sh.win.map(w => `<path d="${w}"/>`).join("")}</clipPath></defs>`;
    o += `<ellipse cx="60" cy="46.2" rx="55" ry="2.4" fill="#000" opacity=".22"/>`;
    if (md.body === "suv") o += `<path d="M14 7 H74" stroke="#2c3540" stroke-width="1.5" stroke-linecap="round"/><path d="M20 7 v1.6 M66 7 v1.6" stroke="#2c3540" stroke-width="1.2"/>`;
    o += `<path d="${sh.d}" fill="${col}" stroke="${dark}" stroke-width=".8"/>`;
    // gövde gölgesi, ışığı ve çizgileri
    o += `<g clip-path="url(#${id}b)">
      <rect x="0" y="31" width="120" height="12" fill="#000" opacity=".2"/>
      <rect x="0" y="0" width="120" height="${mt.belt - 1.5}" fill="#fff" opacity=".13"/>
      <path d="M0 ${mt.belt + 4.4} H120" stroke="#fff" stroke-width=".9" opacity=".45"/>
      <path d="M0 ${mt.belt + 6.1} H120" stroke="#000" stroke-width=".6" opacity=".14"/>
      <path d="M0 34.6 H120" stroke="#000" stroke-width=".7" opacity=".22"/>
      ${mt.seams.map(x => `<path d="M${x} ${mt.belt} V35" stroke="${dark}" stroke-width=".5" opacity=".8"/>`).join("")}`;
    if (car.painted > 0) o += `<rect x="${front - 24}" y="${mt.belt}" width="24" height="16" fill="${shade(col, car.painted > 2 ? 1.14 : 1.07)}" opacity=".55"/>`;
    if (seg === "spor") o += `<rect x="0" y="${mt.belt + 7.4}" width="120" height="1.4" fill="${contrast}" opacity=".85"/>`;
    if (chrome) o += `<path d="M0 31.2 H120" stroke="#eef1f4" stroke-width=".9"/>`;
    if (ev) o += `<path d="M0 34.2 H120" stroke="#2f9fff" stroke-width=".8"/>`;
    o += `<rect x="${rear - 1}" y="30.6" width="12" height="4.6" fill="${chrome ? "#dfe3e8" : "#2a2f35"}" opacity="${chrome ? 1 : 0.5}"/><rect x="${front - 11}" y="30.6" width="12" height="4.6" fill="${chrome ? "#dfe3e8" : "#2a2f35"}" opacity="${chrome ? 1 : 0.5}"/>`;
    if (clean < 45) o += `<rect x="0" y="0" width="120" height="48" fill="#6b5a3c" opacity="${((45 - clean) / 45 * 0.35).toFixed(2)}"/>`;
    if (clean < 30) o += `<path d="M14 36 q4 -4 10 -2 q-4 1 -6 3 z M74 36 q4 -4 10 -2 q-4 1 -6 3 z M36 35 q4 -3 9 -1 q-3 1 -5 2 z" fill="#5a4a30" opacity=".55"/>`;
    if (body < 55) o += `<path d="M58 31 l9 -2.2 M61 32.6 l6 -1.4 M46 29 l5 1.2" stroke="#ffffff" stroke-width=".55" opacity=".65"/><ellipse cx="70" cy="30.5" rx="4" ry="1.8" fill="#000" opacity=".2"/>`;
    if (body < 35) o += `<path d="M24 28 l6 -1 M98 30 l-5 2" stroke="#ffffff" stroke-width=".55" opacity=".65"/><ellipse cx="102" cy="31" rx="3" ry="1.5" fill="#000" opacity=".22"/>${[30, 44, 88].map((x, i) => `<circle cx="${x}" cy="${33 + (i % 2)}" r="${1 + (i % 2) * 0.5}" fill="#8a4b22" opacity=".75"/>`).join("")}`;
    o += `</g>`;
    // camlar ve yansıma
    o += sh.win.map(w => `<path d="${w}" fill="#24303c"/>`).join("");
    o += `<g clip-path="url(#${id}w)"><path d="M30 6 L40 6 L28 30 L18 30 Z M56 6 L61 6 L49 30 L44 30 Z M78 6 L86 6 L74 30 L66 30 Z" fill="#fff" opacity=".2"/></g>`;
    if (chrome || seg === "premium") o += sh.win.map(w => `<path d="${w}" fill="none" stroke="#e3e7eb" stroke-width=".55"/>`).join("");
    // kollar, ayna, ışıklar, tampon ayrıntıları
    o += mt.handles.map(hx => `<rect x="${hx - 2}" y="${mt.belt + 5.2}" width="4" height="1.1" rx=".5" fill="${shade(col, 0.45)}"/>`).join("");
    o += `<path d="M${mt.mirror[0]} ${mt.mirror[1]} h3.2 l1.2 2.4 h-4.2 z" fill="${col}" stroke="${dark}" stroke-width=".5"/>`;
    o += `<path d="M${head - 6} 26.4 L${head + 4.5} 27.4 L${head + 4} 29.8 L${head - 6.4} 29.8 Z" fill="#fff3c4" stroke="#c9b58a" stroke-width=".4"/>`;
    if (seg === "premium" || seg === "luks" || ev) o += `<rect x="${head - 7}" y="30.4" width="9" height=".9" rx=".4" fill="#dff6ff"/>`;
    o += `<rect x="${md.body === "hatch" ? 13 : sh.tail}" y="26.2" width="4" height="3.4" rx=".8" fill="#c62d2a"/><rect x="${(md.body === "hatch" ? 13 : sh.tail) + 0.8}" y="26.8" width="1.6" height="1.2" fill="#ff8a7a"/>`;
    o += ev ? `<rect x="${front - 5}" y="31" width="4" height="1" fill="${dark}"/>` : `<rect x="${front - 5}" y="30.8" width="4.6" height="2.6" rx=".6" fill="#1b1f24" opacity=".75"/>`;
    o += `<circle cx="${front - 7.5}" cy="29" r="1" fill="${BRAND[md.brand] || "#9aa3ac"}" stroke="#eef1f4" stroke-width=".3"/>`;
    if (md.body === "suv") o += `<circle cx="${front - 3}" cy="33.4" r=".9" fill="#fff3c4"/>`;
    if (!ev) o += `<rect x="${rear - 1.6}" y="35.2" width="4.6" height="1.6" rx=".8" fill="#6b737c"/>`;
    if (ev) o += `<rect x="${rear + 12}" y="${mt.belt + 3}" width="3" height="2" rx=".5" fill="${dark}"/><text x="${mt.seams[0] + 4}" y="${mt.belt + 10.5}" font-size="3.2" font-weight="800" fill="#2f9fff" font-family="Saira, sans-serif">e</text>`;
    if (seg === "spor" && (md.body === "coupe" || md.body === "sedan")) o += `<path d="M${rear + 1} ${mt.deck - 2} h12 v-1.6 h-12 z" fill="${dark}"/><path d="M${rear + 4} ${mt.deck - 0.4} v1.6 M${rear + 10} ${mt.deck - 0.4} v1.6" stroke="${dark}" stroke-width=".8"/>`;
    if (md.body === "van") o += `<path d="M48 21.2 H68" stroke="#000" stroke-width=".6" opacity=".35"/>`;
    if (md.body === "pickup") o += `<path d="M6 22.4 H50" stroke="${dark}" stroke-width="1.2"/><path d="M9 23 V34" stroke="${dark}" stroke-width=".5"/>`;
    if ((car.motor != null ? car.motor : 90) < 35 && !ev) o += `<circle cx="${rear - 3}" cy="34" r="1.8" fill="#9aa3ac" opacity=".55"/><circle cx="${rear - 6}" cy="32" r="2.4" fill="#9aa3ac" opacity=".35"/>`;
    o += wheelSVG(30, r, WHEEL[seg] || "alloy") + wheelSVG(90, r, WHEEL[seg] || "alloy");
    if (clean >= 95) o += `<g class="spark"><path d="M50 9 l.7 2 l2 .7 l-2 .7 l-.7 2 l-.7 -2 l-2 -.7 l2 -.7 z M100 24 l.6 1.6 l1.6 .6 l-1.6 .6 l-.6 1.6 l-.6 -1.6 l-1.6 -.6 l1.6 -.6 z" fill="#ffffff"/></g>`;
    return o;
  };
  UI.carSVG = car => `<svg viewBox="0 0 120 48" role="img" aria-label="${esc(car.color)} ${esc(G.carName(car))}">${UI.carShape(car)}</svg>`;

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
  const signed = x => (x > 0 ? "+" : x < 0 ? "−" : "") + G.fmtS(Math.abs(x));
  const condChip = car => { const v = G.condScore(car); return `<span class="chip ${v >= 70 ? "good" : v >= 50 ? "warn" : "bad"}">Durum ${v} · ${G.condLabel(v)}</span>`; };
  const AD_ICON = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M5 3.5v9l7-4.5z" fill="currentColor"/></svg>';
  function adBtn(kind, label, attrs, cls) {
    const left = G.adLeft(kind);
    if (!left) return "";
    return `<button class="btn ad ${cls || ""}" type="button" data-a="ad-${kind}" ${attrs || ""}>${AD_ICON}<span>${label}</span><small>${left} hak</small></button>`;
  }

  // ---- İkonlar ----
  const IC = {
    galeri: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 15l1.6-4.6A2 2 0 0 1 6.5 9h11a2 2 0 0 1 1.9 1.4L21 15v3a1 1 0 0 1-1 1h-1.5M3 15v3a1 1 0 0 0 1 1h1.5M3 15h18"/><circle cx="7.5" cy="18.5" r="1.8"/><circle cx="16.5" cy="18.5" r="1.8"/><path d="M9.3 18.5h5.4"/></svg>',
    piyasa: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="8" cy="8" r="1.6"/></svg>',
    musteri: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c.6-3.4 3-5.4 6-5.4s5.4 2 6 5.4"/><path d="M16 4.6a3.2 3.2 0 0 1 0 6.4M18 14.8c1.7.7 2.7 2.4 3 5.2"/></svg>',
    atolye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.1L3.6 17.1a1.9 1.9 0 0 0 2.7 2.7l5.7-5.7a4 4 0 0 0 5.1-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/></svg>',
    patron: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-5h6v5M9 11h.01M15 11h.01"/></svg>'
  };
  const TABS = [["galeri", "Galeri"], ["piyasa", "Araç al"], ["musteri", "Müşteri"], ["atolye", "Atölye"], ["patron", "Patron"]];

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
    if (UI.skipping) return;
    if (["good", "bad", "level", "news"].includes(kind)) UI.toast(text, kind);
  };
  G.onOffer = (c, car) => {
    if (UI.skipping) return;
    if (UI.sheet && UI.sheet.kind === "cust" && UI.sheet.id === c.id) return;
    UI.toast(`Yeni teklif: ${c.name}, ${G.carName(car)} için ${G.fmtS(c.offer)}. Dokun ve cevap ver.`, "offer", { a: "cust", id: c.id });
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
    $("kXp").textContent = `%${Math.floor(s.xp / G.xpNeed(s.level) * 100)}`;
    $("kXpBar").style.width = (s.xp / G.xpNeed(s.level) * 100) + "%";
    const sp = $("speed");
    const want = s.paused ? "0" : String(s.speed);
    if (sp.dataset.v !== want) {
      sp.dataset.v = want;
      sp.innerHTML = [["0", "❚❚", "Durdur"], ["1", "1×", "Normal hız"], ["2", "2×", "İki kat hız"], ["4", "4×", "Dört kat hız"]]
        .map(([v, l, a]) => `<button type="button" data-a="speed" data-v="${v}" aria-label="${a}" aria-pressed="${want === v}">${l}</button>`).join("")
        + `<button type="button" data-a="skip" aria-label="Günü bitir" title="Günü bitir">⏭</button>`;
    }
  }

  function renderNav() {
    const offers = S().custs.filter(c => c.state === "offer").length;
    const jobs = S().jobs.length;
    const html = TABS.map(([id, name]) => {
      let b = "";
      if (id === "musteri" && offers) b = `<span class="badge">${offers}</span>`;
      if (id === "atolye" && jobs) b = `<span class="badge alt">${jobs}</span>`;
      return `<button type="button" data-a="tab" data-v="${id}" ${UI.tab === id ? 'aria-current="page"' : ""}>${IC[id]}<span>${name}</span>${b}</button>`;
    }).join("");
    const nav = $("nav");
    if (nav.dataset.h !== html) { nav.dataset.h = html; nav.innerHTML = html; }
  }

  // ---- Sekmeler ----
  const VIEWS = {};

  function carStatus(car) {
    const s = S();
    const job = car.status === "shop" ? s.jobs.find(j => j.carId === car.id) : null;
    if (job) return `<span class="chip dark">Atölyede · ${G.fmtDur(G.jobEta(job))}</span>`;
    if (s.custs.some(c => c.carId === car.id && c.state === "offer")) return `<span class="chip warn">Teklif var</span>`;
    if (!car.price) return `<span class="chip bad">Fiyat yok</span>`;
    if (car.clean < 40) return `<span class="chip warn">Kirli</span>`;
    if (car.show) return `<span class="chip acc">Vitrinde</span>`;
    return `<span class="chip good">Satışta</span>`;
  }

  function carRow(car) {
    const mv = G.value(car, false);
    const gain = (car.price || mv) - car.buy - car.spent;
    return `<article class="crow ${G.M[car.m].seg === "ultra" ? "ultra" : ""}" role="button" tabindex="0" data-a="car" data-id="${car.id}">
      <div class="crow-img">${UI.carSVG(car)}</div>
      <div class="crow-main"><b>${esc(G.carName(car))}</b><span>${car.year} · ${G.fmtKm(G.realKm(car, false))}</span>${carStatus(car)}</div>
      <div class="crow-side">${car.price ? `<span class="sticker sm">${G.fmtS(car.price)}</span>` : `<span class="muted small">≈ ${G.fmtS(mv)}</span>`}<small class="${gain >= 0 ? "pos" : "neg"}">${gain >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(gain))}</small></div>
    </article>`;
  }

  function todoCard() {
    const t = G.todos();
    if (!t.length) return "";
    return `<div class="card todo"><h3>Şimdi ne yapmalı?</h3>${t.map(x => `<div class="todo-row ${x.hot ? "hot" : ""}"><span>${esc(x.text)}</span>
      <button class="btn sm ${x.hot ? "warn" : "pri"}" type="button" data-a="${x.a}" ${x.id != null ? `data-id="${x.id}"` : ""} ${x.v ? `data-v="${x.v}"` : ""}>${esc(x.btn)}</button></div>`).join("")}</div>`;
  }

  VIEWS.galeri = function () {
    const s = S();
    const cars = s.cars.slice();
    const sorters = {
      yeni: (a, b) => b.id - a.id,
      kar: (a, b) => (G.value(b, false) - b.buy - b.spent) - (G.value(a, false) - a.buy - a.spent),
      bekleyen: (a, b) => b.days - a.days
    };
    cars.sort(sorters[UI.sort] || sorters.yeni);
    let html = `<div class="scene-card">${UI.scene()}<div class="scene-cap"><span>${esc(G.loc().name)}</span><span>Park <b>${G.carCount()}/${G.capacity()}</b>${G.showSlots() ? ` · Vitrin <b>${G.showUsed()}/${G.showSlots()}</b>` : ""}</span></div></div>`;
    html += todoCard();
    if (!cars.length) {
      return html + `<div class="empty"><b>Galeride araç yok</b>"Araç al" sekmesinden ilanlara bak ya da günün ihalesine katıl.<div style="margin-top:12px"><button class="btn pri" type="button" data-a="tab" data-v="piyasa">Araç al</button></div></div>`;
    }
    html += `<div class="vh"><h2>Araçların</h2><div class="segctl small-ctl" role="group" aria-label="Sıralama">${[["yeni", "Yeni"], ["kar", "Kâr"], ["bekleyen", "Bekleyen"]]
      .map(([k, l]) => `<button type="button" data-a="sort" data-v="${k}" aria-pressed="${UI.sort === k}">${l}</button>`).join("")}</div></div>`;
    html += `<div class="rows">${cars.map(carRow).join("")}</div>`;
    return html;
  };

  VIEWS.piyasa = function () {
    const s = S(), sub = UI.sub.piyasa;
    let html = `<div class="vh"><div><h2>Araç al</h2><p>Boş park yeri: ${Math.max(0, G.spaceLeft())} · Kasa: ${G.fmtS(s.money)}</p></div></div>
      <div class="segctl" role="group">${[["ilan", "İlanlar"], ["ihale", "İhale"], ["trend", "Piyasa"]]
        .map(([k, l]) => `<button type="button" data-a="sub" data-k="piyasa" data-v="${k}" aria-pressed="${sub === k}">${l}</button>`).join("")}</div>`;
    if (sub === "ilan") {
      const all = s.listings.filter(l => !l.done);
      const ls = filterListings(all);
      const nd = G.nextDrop();
      html += `<div class="card ilan-info"><div class="row"><div><b>Bugün ${all.length} ilan</b><p class="small muted">${nd ? `Sıradaki yeni ilanlar saat ${G.clock(nd)}'de düşer.` : "Bugün yeni ilan düşmeyecek."} Her sabah bütün ilanlar yenilenir.</p></div>${adBtn("ilan", "+4 ilan")}</div></div>`;
      html += listingFilters(all, ls.length);
      if (all.length && !ls.length) return html + `<div class="empty"><b>Bu filtreye uyan ilan yok</b>Filtreleri gevşet ya da sıfırla.<div style="margin-top:10px"><button class="btn" type="button" data-a="lf-reset">Filtreleri sıfırla</button></div></div>`;
      if (!ls.length) return html + `<div class="empty"><b>Şu an bakılacak ilan kalmadı</b>${nd ? `Saat ${G.clock(nd)}'de yeni ilanlar gelecek.` : "Yarın sabah yeni ilanlar gelecek."}</div>`;
      html += `<div class="rows">${ls.map(listingRow).join("")}</div>`;
    } else if (sub === "ihale") {
      const a = s.auction;
      html += `<div class="card"><h3>Günün ihalesi</h3><p class="muted small">Banka, sigorta ve filo araçları piyasanın altında açılır ama ekspertize sokulamaz${s.upg.expert ? "; ekspertiz cihazınla hızlı kontrol yapabilirsin" : ""}.</p>
        ${a.lots.map((lot, i) => `<div class="opt"><div><b>${esc(G.carTitle(lot.car))}</b><small>${esc(G.aucSrc(lot.src))} · açılış ${G.fmtS(lot.start)}</small></div>
          <div class="small">${lot.done ? (lot.winner === "Sen" ? `<span class="pos">Senin · ${G.fmtS(lot.price)}</span>` : `<span class="muted">${esc(lot.winner || "Satılmadı")}</span>`) : (i === a.idx && a.live ? "<b>Şimdi</b>" : "Sırada")}</div></div>`).join("")}
        <button class="btn pri block" type="button" data-a="auction" ${a.finished ? "disabled" : ""}>${a.finished ? "Bugünkü ihale bitti" : a.idx > 0 || a.live ? "İhale salonuna dön" : "İhale salonuna gir"}</button></div>`;
    } else {
      const segs = Object.keys(G.SEGMENTS);
      html += `<div class="card"><h3>Segment fiyatları</h3><p class="muted small">Yükselen segmentte alınan araç daha kârlı satılır.</p><div class="list">${segs.map(k => {
        const t = G.trendOf(k), p = Math.round((t - 1) * 100);
        const open = G.segUnlocked(k);
        return `<div class="row"><div><b>${G.SEGMENTS[k].name}</b>${open ? "" : ` <span class="chip">Seviye ${G.SEGMENTS[k].lvl}</span>`}</div><div class="num ${p > 0 ? "pos" : p < 0 ? "neg" : "muted"}">${p > 0 ? "▲ +" : p < 0 ? "▼ " : ""}${p}%</div></div>`;
      }).join("")}</div></div>`;
      html += `<div class="card"><h3>Gündem</h3>${s.events.length ? s.events.map(e => { const d = G.EVENTS.find(x => x.id === e.id); return `<div><b>${esc(d.title)}</b><p class="muted small">${esc(d.text)} · ${e.until - s.day + 1} gün daha</p></div>`; }).join("") : `<p class="muted small">Piyasada olağan bir hafta. Haberler gün sonunda gelir.</p>`}</div>`;
    }
    return html;
  };

  // ---- İlan filtresi ----
  UI.lf = UI.lf || { seg: "all", sort: "yeni", deal: false, afford: false };
  const LF_SORT = [["yeni", "Önerilen"], ["ucuz", "En ucuz"], ["pahali", "En pahalı"], ["kar", "En kârlı"], ["km", "En az km"], ["yil", "En yeni model"]];
  function filterListings(all) {
    const f = UI.lf, s = S();
    let ls = all.filter(l => {
      if (f.seg !== "all" && G.M[l.car.m].seg !== f.seg) return false;
      if (f.deal && G.dealLabel(l.last, G.value(l.car, false)).c !== "good") return false;
      if (f.afford && l.last > s.money) return false;
      return true;
    });
    const gain = l => G.value(l.car, false) - l.last;
    const sorts = {
      yeni: (a, b) => (b.ultra ? 2 : 0) + isNew(b) - (a.ultra ? 2 : 0) - isNew(a),
      ucuz: (a, b) => a.last - b.last, pahali: (a, b) => b.last - a.last,
      kar: (a, b) => gain(b) - gain(a), km: (a, b) => G.realKm(a.car, false) - G.realKm(b.car, false), yil: (a, b) => b.car.year - a.car.year
    };
    return ls.sort(sorts[f.sort] || sorts.yeni);
  }
  function listingFilters(all, shown) {
    const f = UI.lf;
    const segs = Object.keys(G.SEGMENTS).filter(k => all.some(l => G.M[l.car.m].seg === k));
    const on = f.seg !== "all" || f.deal || f.afford || f.sort !== "yeni";
    return `<div class="filters">
      <label class="fsel"><span>Segment</span><select id="lfSeg" data-ch="seg"><option value="all">Tümü (${all.length})</option>${segs.map(k => `<option value="${k}" ${f.seg === k ? "selected" : ""}>${G.SEGMENTS[k].name} (${all.filter(l => G.M[l.car.m].seg === k).length})</option>`).join("")}</select></label>
      <label class="fsel"><span>Sırala</span><select id="lfSort" data-ch="sort">${LF_SORT.map(([k, l]) => `<option value="${k}" ${f.sort === k ? "selected" : ""}>${l}</option>`).join("")}</select></label>
      <div class="ftog"><button type="button" class="btn sm ${f.deal ? "pri" : ""}" data-a="lf-tog" data-v="deal" aria-pressed="${f.deal}">Sadece fırsatlar</button><button type="button" class="btn sm ${f.afford ? "pri" : ""}" data-a="lf-tog" data-v="afford" aria-pressed="${f.afford}">Kasama uygun</button>${on ? `<button type="button" class="btn sm" data-a="lf-reset">Sıfırla</button>` : ""}<span class="muted small">${shown}/${all.length} ilan</span></div></div>`;
  }

  const isNew = l => (l.day === S().day && l.born > G.OPEN && S().min - l.born < 120 ? 1 : 0);
  function listingRow(l) {
    const car = l.car;
    const deal = G.dealLabel(l.last, G.value(car, false));
    return `<article class="crow ${l.ultra ? "ultra" : ""}" role="button" tabindex="0" data-a="listing" data-id="${l.id}">
      <div class="crow-img">${UI.carSVG(car)}</div>
      <div class="crow-main"><b>${esc(G.carName(car))}</b><span>${car.year} · ${G.fmtKm(G.realKm(car, false))} · ${esc(G.SELLERS[l.seller].name)}</span><span class="chips">${l.ultra ? `<span class="chip gold">Ultra lüks</span>` : ""}<span class="chip ${deal.c}">${deal.t}</span>${isNew(l) ? `<span class="chip acc">Yeni</span>` : ""}</span></div>
      <div class="crow-side"><span class="sticker sm">${G.fmtS(l.last)}</span><small class="muted">${G.SEGMENTS[G.M[car.m].seg].name}</small></div>
    </article>`;
  }

  VIEWS.musteri = function () {
    const s = S();
    const offers = s.custs.filter(c => c.state === "offer");
    const browsing = s.custs.filter(c => c.state === "browse");
    let html = `<div class="vh"><div><h2>Müşteriler</h2><p>Bugün ${s.today.visitors} ziyaretçi · ${s.today.sales} satış</p></div></div>`;
    if (offers.length) html += `<div class="list">${offers.map(offerCard).join("")}</div>`;
    if (G.boostActive()) html += `<div class="card boost"><b>Kampanya sürüyor</b><p class="small">Saat ${G.clock(s.boost.until)}'e kadar iki kat müşteri geliyor.</p></div>`;
    else if (s.min < G.CLOSE - 60 && G.adLeft("boost")) html += `<div class="card boost"><div class="row"><div><b>Müşteri kampanyası</b><p class="small muted">Kısa bir reklam izle, 2 saat boyunca galerine iki kat müşteri gelsin.</p></div>${adBtn("boost", "İzle")}</div></div>`;
    if (!offers.length && !browsing.length) {
      html += `<div class="empty"><b>Şu an galeride müşteri yok</b>${s.min >= G.CLOSE ? "Galeri kapalı." : "Fiyat etiketli, temiz araçlar ve iyi itibar müşteri getirir."}</div>`;
    }
    if (browsing.length) {
      html += `<div class="card"><h3>Araçlara bakıyor</h3>${browsing.map(c => `<div class="row small"><div><b>${esc(c.name)}</b> · ${esc(G.CT[c.type].name)}</div><div class="muted">${G.SEGMENTS[c.seg].name} arıyor</div></div>`).join("")}</div>`;
    }
    if (s.lost.length) {
      html += `<div class="card"><h3>Bugün kaçanlar</h3><p class="muted small">Ne aradıklarına bakıp stoğunu ona göre kur.</p>${s.lost.map(l => `<div class="row small"><div><b>${esc(l.type)}</b></div><div class="muted" style="text-align:right">${esc(l.text)}</div></div>`).join("")}</div>`;
    }
    return html;
  };

  function raiseAmount(c, car) { return R.r1k(c.offer + (car.price - c.offer) / 2); }

  function offerCard(c) {
    const car = G.custCar(c);
    if (!car) return "";
    const profit = c.offer - car.buy - car.spent;
    const raise = raiseAmount(c, car);
    return `<div class="card offer"><div class="row"><div><b>${esc(c.name)}</b> <span class="muted small">${esc(G.CT[c.type].name)}</span><p class="small muted">${esc(G.carTitle(car))} · etiket ${G.fmtS(car.price)}</p></div>
      <div style="text-align:right"><div class="num big">${G.fmtS(c.offer)}</div><div class="small ${profit >= 0 ? "pos" : "neg"}">${profit >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(profit))}</div></div></div>
      ${c.trade ? `<p class="small">Takasa <b>${esc(G.carTitle(c.trade))}</b> veriyor (${G.fmtS(c.tradeVal)})</p>` : ""}
      <div class="prog pat" title="Müşterinin sabrı"><i data-pat="${c.id}" style="width:${c.patience / c.patMax * 100}%"></i></div>
      <div class="btns"><button class="btn pri" type="button" data-a="accept" data-id="${c.id}">Kabul et</button>${raise > c.offer ? `<button class="btn warn" type="button" data-a="raise" data-id="${c.id}">${G.fmtS(raise)} iste</button>` : ""}<button class="btn" type="button" data-a="cust" data-id="${c.id}">Görüş</button></div></div>`;
  }

  VIEWS.atolye = function () {
    const s = S();
    const bayUsed = s.jobs.filter(j => j.inhouse && j.where === "bay").length;
    let html = `<div class="vh"><div><h2>Atölye</h2><p>${s.upg.bays ? `Bölmeler ${bayUsed}/${s.upg.bays} dolu` : "Kendi atölyen yok; işler dış servise gider"} · Usta: ${G.staffOf("usta").length}</p></div></div>`;
    if (!s.jobs.length) html += `<div class="empty"><b>Atölyede iş yok</b>Galeride bir araca dokun; "Satışa hazırla" bölümü en kârlı işi önerir.</div>`;
    else html += `<div class="list">${s.jobs.map(j => {
      const car = s.cars.find(c => c.id === j.carId);
      return `<div class="card job"><div class="row"><div><b>${esc(j.name)}</b><p class="small muted">${car ? esc(G.carTitle(car)) : ""} · ${j.inhouse ? "Kendi atölyen" : "Dış servis"}</p></div><div class="num small" data-jobt="${j.id}">${G.fmtDur(G.jobEta(j))}</div></div>
        <div class="prog"><i data-job="${j.id}" style="width:${(1 - j.left / j.total) * 100}%"></i></div>
        ${adBtn("rush", "Reklam izle, hemen bitir", `data-id="${j.id}"`, "sm")}</div>`;
    }).join("")}</div>`;
    html += ustaCard();
    html += `<div class="card"><h3>Atölye düzeni</h3><div class="kv">
      <div><span>Bölme</span><b>${s.upg.bays}</b></div><div><span>Yıkama</span><b>${G.facLevel("yikama") ? "Seviye " + G.facLevel("yikama") : "Yok"}</b></div>
      <div><span>Boya kabini</span><b>${s.upg.paint ? "Var" : "Yok"}</b></div><div><span>Göçük seti</span><b>${s.upg.pdr ? "Var" : "Yok"}</b></div></div>
      <p class="muted small">İşler galeri açıkken (09:00–19:00) ilerler. Usta olmadan kendi atölyende işler yavaş gider. Yeni ekipman Patron sekmesinde.</p></div>`;
    return html;
  };

  function energyCls(e) { return e >= 60 ? "" : e >= 30 ? "warn" : "bad"; }
  function ustaCard() {
    const s = S(), us = G.ustas(), cl = G.facLevel("cardak"), ce = G.facEff("cardak");
    let html = `<div class="card"><div class="row"><h3>Ustalar ve çardak</h3><span class="pill">Atölye hızı <b>×${G.workRate().toFixed(2).replace(".", ",")}</b></span></div>`;
    if (!us.length) return html + `<p class="muted small">Ustan yok; içerideki işler yavaş ilerler. Patron → Personel'den usta al.</p></div>`;
    html += `<p class="muted small">${cl ? `Çardak ${cl}. seviye · ${G.restingCount()}/${ce.cap} yer dolu · saatte +${Math.round(ce.rest)} enerji. Dinlenen usta en az 30 dakika kalırsa ${G.fmtDur(ce.buffMins)} boyunca ×${ce.buffMult.toFixed(2).replace(".", ",")} hızlı çalışır.${ce.auto ? " Otomatik mola açık." : ""}` : "Çalışan usta yorulur ve yavaşlar. Usta çardağı kurarsan gün içinde dinlendirebilirsin."}</p>`;
    html += us.map(u => {
      const e = Math.round(u.energy == null ? 100 : u.energy);
      const st = u.resting ? `<span class="chip acc">Çardakta</span>` : G.buffActive(u) ? `<span class="chip good">Dinç ×${u.buff.mult.toFixed(2).replace(".", ",")} · ${G.fmtDur(u.buff.until - s.min)}</span>` : e < 35 ? `<span class="chip bad">Yorgun</span>` : `<span class="chip">Çalışıyor</span>`;
      const btn = !cl ? "" : u.resting ? `<button class="btn sm" type="button" data-a="recall" data-id="${u.id}">Geri çağır</button>` : `<button class="btn sm ${e < 50 ? "pri" : ""}" type="button" data-a="rest" data-id="${u.id}" ${G.restFree() <= 0 ? "disabled" : ""}>Çardağa gönder</button>`;
      return `<div class="opt"><div><b>${esc(u.name)}</b> <span class="chip">${"★".repeat(u.skill)}</span> ${st}
        <div class="energy"><span>Enerji</span><div class="bar"><i class="${energyCls(e)}" data-energy="${u.id}" style="width:${e}%"></i></div><b data-energyt="${u.id}">${e}</b></div></div>${btn}</div>`;
    }).join("");
    return html + `</div>`;
  }

  // Tesis kartı (Patron → Tesisler)
  function pips(n) { let h = ""; for (let i = 1; i <= G.FAC_MAX; i++) h += `<i class="${i <= n ? "on" : ""}"></i>`; return `<div class="pips" aria-label="${n}/${G.FAC_MAX}">${h}</div>`; }
  function facCard(key) {
    const s = S(), F = G.FACILITIES[key], n = G.facLevel(key), nx = G.facNext(key);
    const lines = G.facLines(key, n);
    let btn;
    if (!nx) btn = `<span class="chip good">En üst seviye</span>`;
    else if (nx.req > s.level) btn = `<span class="chip">Oyuncu seviyesi ${nx.req} gerekir</span>`;
    else btn = `<button class="btn ${s.money >= nx.cost ? "pri" : ""}" type="button" data-a="facup" data-v="${key}" ${s.money >= nx.cost ? "" : "disabled"}>${n ? "Yükselt" : "Kur"} · ${G.fmtS(nx.cost)}</button>`;
    return `<div class="card fac"><div class="row"><div><h3>${esc(F.name)}</h3><p class="muted small">Seviye ${n}/${G.FAC_MAX}</p></div><button class="btn sm" type="button" data-a="fac" data-v="${key}">Ayrıntı</button></div>
      ${pips(n)}<ul class="facl">${lines.slice(0, 3).map(l => `<li>${esc(l)}</li>`).join("")}</ul><div class="row">${nx ? `<span class="small muted">Sonraki: ${G.facLines(key, nx.n).find((l, i) => l !== lines[i]) || ""}</span>` : "<span></span>"}${btn}</div></div>`;
  }
  function patronTesis() {
    return `<p class="muted small" style="margin:0">Her tesis 20 seviyedir. Yeni seviyeler sen seviye atladıkça açılır; aldığın her seviye galeri görünümünde belirir.</p>` + Object.keys(G.FACILITIES).map(facCard).join("");
  }

  VIEWS.patron = function () {
    const s = S(), sub = UI.sub.patron;
    let html = `<div class="vh"><div><h2>Patron masası</h2><p>${esc(G.loc().name)} · Seviye ${s.level}</p></div></div>
      <div class="segctl" role="group">${[["tesis", "Tesisler"], ["lux", "Lüks"], ["durum", "Durum"], ["dukkan", "Adres"], ["personel", "Personel"], ["finans", "Banka"]]
        .map(([k, l]) => `<button type="button" data-a="sub" data-k="patron" data-v="${k}" aria-pressed="${sub === k}">${l}</button>`).join("")}</div>`;
    if (sub === "tesis") html += patronTesis();
    if (sub === "lux") html += UI.luxView();
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
    const bs = h.map((d, i) => {
      const hh = Math.abs(d.net) / mx * (H / 2 - 12);
      const y = d.net >= 0 ? y0 - hh : y0;
      return `<rect x="${pad + i * bw + 2}" y="${y.toFixed(1)}" width="${(bw - 4).toFixed(1)}" height="${Math.max(1, hh).toFixed(1)}" rx="2" fill="${d.net >= 0 ? "var(--good)" : "var(--bad)"}"/>
        <text x="${(pad + i * bw + bw / 2).toFixed(1)}" y="${H - 1}" text-anchor="middle">${d.day}</text>`;
    }).join("");
    return `<svg class="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Son günlerin net kâr-zarar grafiği">
      <line x1="${pad}" x2="${W}" y1="${y0}" y2="${y0}" stroke="var(--line)"/>
      <text x="0" y="12">+${G.fmtS(mx).replace(" ₺", "")}</text><text x="0" y="${H - 14}">−${G.fmtS(mx).replace(" ₺", "")}</text>${bs}</svg>`;
  }

  function passiveCard() {
    const pr = G.passiveRate();
    return `<div class="card"><div class="row"><h3>Pasif gelir</h3><span class="pill">Saatte <b>${G.fmtS(pr.total)}</b></span></div>
      <p class="muted small">Oyundan çıkınca galerin çalışmaya devam eder; döndüğünde en fazla ${G.OFFLINE_MAX_H} saatlik kazancı alırsın. Saatlik gelir son 7 günün ortalama satış kârına göre hesaplanır; satış danışmanları ve şöhret artırır.</p>
      <div class="kv"><div><span>Temel</span><b>${G.fmtS(pr.base)}/sa</b></div><div><span>Danışman bonusu</span><b>+%${Math.round(pr.sales * 100)}</b></div><div><span>Şöhret bonusu</span><b>+%${Math.round(pr.fame * 100)}</b></div><div><span>Şimdiye kadar</span><b>${G.fmtS(S().stats.passive || 0)}</b></div></div></div>`;
  }
  function patronDurum() {
    const s = S(), T = s.today;
    const qs = G.activeQuests(3);
    return passiveCard() + `<div class="card"><h3>Görevler</h3>${qs.length ? qs.map(q => `<div class="row"><div>${esc(q.text)}</div><div class="num small pos">+${G.fmtS(q.reward)}</div></div>`).join("") : `<p class="muted">Bütün görevleri bitirdin. Gerçek bir patronsun.</p>`}
        <p class="muted small">${s.quests.length}/${G.QUESTS.length} görev tamamlandı</p></div>
      <div class="card"><h3>Bugün</h3><div class="kv">
        <div><span>Satış</span><b>${T.sales}</b></div><div><span>Satış kârı</span><b class="${T.profit >= 0 ? "pos" : "neg"}">${G.fmtS(T.profit)}</b></div>
        <div><span>Atölye ve ekspertiz</span><b>${G.fmtS(T.spentWork)}</b></div><div><span>Ziyaretçi</span><b>${T.visitors}</b></div></div></div>
      <div class="card"><h3>Son 14 gün net</h3>${chart(s.history)}</div>
      <div class="card"><h3>Galeri karnesi</h3><div class="kv">
        <div><span>Net değer</span><b>${G.fmtS(G.netWorth())}</b></div><div><span>Toplam satış</span><b>${s.stats.sold}</b></div>
        <div><span>Toplam kâr</span><b class="${s.stats.profit >= 0 ? "pos" : "neg"}">${G.fmtS(s.stats.profit)}</b></div><div><span>En iyi satış kârı</span><b>${G.fmtS(s.stats.best)}</b></div></div></div>`;
  }

  function patronDukkan() {
    const s = S(), L = G.loc(), N = G.LOCS[s.loc + 1];
    let html = `<div class="card"><h3>${esc(L.name)}</h3><p class="muted small">${esc(L.desc)}</p><div class="kv">
      <div><span>Kapasite</span><b>${L.cap} araç</b></div><div><span>Personel</span><b>${s.staff.length}/${L.staff}</b></div>
      <div><span>Günlük kira</span><b>${G.fmt(L.rent)}</b></div><div><span>Trafik</span><b>×${String(L.traffic).replace(".", ",")}</b></div></div></div>`;
    if (N) {
      const ok = s.level >= N.lvl && s.money >= N.cost;
      html += `<div class="card"><h3>Sıradaki adres: ${esc(N.name)}</h3><p class="muted small">${esc(N.desc)}</p>
        <div class="kv"><div><span>Kapasite</span><b>${N.cap} araç</b></div><div><span>Personel</span><b>${N.staff}</b></div><div><span>Kira</span><b>${G.fmt(N.rent)}</b></div><div><span>Taşınma</span><b>${G.fmtS(N.cost)}</b></div></div>
        <button class="btn pri block" type="button" data-a="move" ${ok ? "" : "disabled"}>${s.level < N.lvl ? `Seviye ${N.lvl} gerekir` : s.money < N.cost ? `Kasada ${G.fmtS(N.cost)} olmalı` : "Taşın"}</button></div>`;
    }
    html += `<div class="card"><h3>Ekipman</h3><p class="muted small">Galeri, yıkama, çardak ve otopark Tesisler bölümünde.</p>${Object.keys(G.UPGRADES).map(k => {
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
      html += `<div class="card"><h3>Danışmanın indirim yetkisi</h3><p class="muted small">15 dakika içinde cevap vermediğin teklifleri danışmanın bu indirime kadar kendisi bağlar.</p><div class="quick">${[0, 3, 5, 8, 12].map(v => `<button class="btn ${Math.round(s.maxDisc * 100) === v ? "pri" : ""}" type="button" data-a="disc" data-v="${v}">%${v}</button>`).join("")}</div></div>`;
    }
    html += `<div class="card"><h3>İş başvuruları</h3><p class="muted small">Başvurular 5 günde bir yenilenir.</p>${s.cands.length ? s.cands.map(c => `<div class="opt"><div><b>${esc(c.name)}</b> · ${esc(G.ROLES[c.role].name)} <span class="chip">${"★".repeat(c.skill)}${"☆".repeat(5 - c.skill)}</span><small>${esc(G.ROLES[c.role].desc)} Günlük ${G.fmt(c.salary)}.</small></div>
      <button class="btn sm pri" type="button" data-a="hire" data-id="${c.id}" ${s.staff.length >= L.staff ? "disabled" : ""}>İşe al</button></div>`).join("") : `<p class="muted small">Şu an başvuru yok.</p>`}</div>`;
    return html;
  }

  function patronFinans() {
    const s = S();
    const lim = G.loanLimit();
    let html = `<div class="card"><h3>Galeri tanıtımı</h3><p class="muted small">Oyun içindeki tanıtım bütçen; her gün kasadan düşer.</p>${G.ADS.map((a, i) => `<div class="opt"><div><b>${esc(a.name)}</b><small>${a.cost ? `Günlük ${G.fmt(a.cost)} · müşteri ×${String(a.mult).replace(".", ",")}${a.rep ? " · itibar her gün artar" : ""}` : "Müşteriler yalnızca yoldan geçerken gelir."}</small></div>
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
  const head = (title, sub, closable) => `<div class="grip"></div><div class="sh-head"><div><h2>${esc(title)}</h2>${sub ? `<p>${sub}</p>` : ""}</div>${closable === false ? "" : `<button class="x" type="button" data-a="close" aria-label="Kapat">×</button>`}</div>`;

  SHEETS.car = function (id) {
    const s = S(), car = s.cars.find(c => c.id === id);
    if (!car) return null;
    const md = G.M[car.m];
    const mv = G.value(car, false);
    const cost = car.buy + car.spent;
    const job = s.jobs.find(j => j.carId === car.id);
    const cs = G.condScore(car);
    const d = UI.draft;
    if (d.carId !== id) { d.carId = id; d.price = car.price || R.r1k(mv * 1.05); }
    const lo = R.r1k(mv * 0.6), hi = R.r1k(mv * 1.5);
    d.price = R.clamp(d.price, lo, hi);
    let html = head(G.carName(car), `${car.year} · ${G.fmtKm(G.realKm(car, false))} · ${esc(car.color)} · ${esc(G.SEGMENTS[md.seg].name)}`);
    html += `<div class="hero">${UI.carSVG(car)}${car.price ? `<span class="sticker">${G.fmtS(car.price)}</span>` : ""}</div>
      <div class="tiles"><div><span>Piyasa değeri</span><b>${G.fmtS(mv)}</b></div><div><span>Maliyetin</span><b>${G.fmtS(cost)}</b></div><div><span>Durum</span><b class="${cs >= 70 ? "pos" : cs < 50 ? "neg" : ""}">${cs} · ${G.condLabel(cs)}</b></div></div>`;
    if (job) {
      html += `<div class="card job"><div class="row"><b>Atölyede: ${esc(job.name)}</b><span class="num small" data-jobt="${job.id}">${G.fmtDur(G.jobEta(job))}</span></div><div class="prog"><i data-job="${job.id}" style="width:${(1 - job.left / job.total) * 100}%"></i></div>
        ${adBtn("rush", "Reklam izle, hemen bitir", `data-id="${job.id}"`, "sm")}<p class="muted small">Araç atölyedeyken müşteri göremez.</p></div>`;
    }
    // 1. fiyat
    html += `<section class="step"><h3><span class="n">1</span>Fiyat etiketi</h3><div class="presets">${G.PRICE_PRESETS.map(p => {
      const v = R.r1k(mv * p.f), m = v - cost, on = car.price === v;
      return `<button class="preset ${on ? "on" : ""}" type="button" data-a="preset" data-id="${car.id}" data-v="${v}"><span>${p.name}</span><b>${G.fmtS(v)}</b><small class="${m >= 0 ? "pos" : "neg"}">${m >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(m))}</small><em>${p.note}</em></button>`;
    }).join("")}</div>
      <details class="more"><summary>Kendi fiyatımı yazayım</summary><div class="range">
        <div class="range-out"><b id="prOut">${G.fmt(d.price)}</b><span class="small" id="prInfo">${priceInfo(d.price, mv, cost)}</span></div>
        <input type="range" id="prRange" data-in="price" min="${lo}" max="${hi}" step="1000" value="${d.price}" aria-label="Etiket fiyatı">
        <button class="btn pri" type="button" data-a="price-save" data-id="${car.id}">Bu fiyatı as</button></div></details>
      ${car.price ? `<button class="link" type="button" data-a="price-clear" data-id="${car.id}">Etiketi kaldır</button>` : ""}</section>`;
    // 2. hazırlık
    html += `<section class="step"><h3><span class="n">2</span>Satışa hazırla</h3>`;
    if (!car.inspected) {
      html += `<div class="card rec"><div><b>Ekspertiz yaptır</b><p class="small muted">Gizli kusurları ortaya çıkarır. Bilinmeyen kusurla satılan araç şikâyet ve tazminat getirebilir.</p></div>
        <div class="btns"><button class="btn warn" type="button" data-a="inspect" data-id="${car.id}">${G.fmt(G.expertCost())} öde</button>${adBtn("inspect", "Reklamla ücretsiz", `data-id="${car.id}"`)}</div></div>`;
    }
    if (car.status === "lot") {
      const rec = G.recommendJob(car);
      if (rec) {
        const gain = G.jobGain(car, rec);
        html += `<div class="card rec"><div><b>Önerilen: ${esc(rec.name)}</b><p class="small muted">${G.fmtS(rec.cost)} · ${G.fmtDur(rec.mins)} · ${rec.inhouse ? "kendi atölyende" : "dış serviste"}${gain > 0 ? ` · değeri <span class="pos">+${G.fmtS(gain)}</span>` : " · müşteriler temiz aracı daha çok sever"}</p></div>
          <button class="btn pri" type="button" data-a="job" data-id="${car.id}" data-t="${rec.type}" data-is="${rec.issue || ""}" ${rec.why ? "disabled" : ""}>${rec.why ? esc(rec.why) : "Başlat"}</button></div>`;
      } else if (car.inspected) {
        html += `<p class="muted small">Bu araç satışa hazır. Ekstra iş değerini masrafından fazla artırmaz.</p>`;
      }
      const opts = G.jobOptions(car);
      html += `<details class="more"><summary>Bütün atölye işleri (${opts.length})</summary>${opts.map(o => {
        const g = o.type === "clean" ? 0 : G.jobGain(car, o);
        return `<div class="opt"><div><b>${esc(o.name)}</b><small>${o.inhouse ? "Kendi atölyen" : "Dış servis"} · ${G.fmtDur(o.mins)}${g > 0 ? ` · değer +${G.fmtS(g)}` : ""}${o.why ? ` · <span class="neg">${esc(o.why)}</span>` : ""}${o.type === "paint" && G.M[car.m].seg !== "klasik" ? " · boyalı parça sayısı artar" : ""}</small></div>
        <button class="btn sm" type="button" data-a="job" data-id="${car.id}" data-t="${o.type}" data-is="${o.issue || ""}" ${o.why ? "disabled" : ""}>${G.fmtS(o.cost)}</button></div>`;
      }).join("")}</details>`;
    }
    html += `</section>`;
    // 3. diğer
    const ws = G.wholesalePrice(car);
    html += `<section class="step"><h3><span class="n">3</span>Diğer</h3><div class="card"><div class="opt"><div><b>Vitrin</b><small>${G.showSlots() ? `Vitrindeki araç daha çok ilgi görür. Dolu: ${G.showUsed()}/${G.showSlots()}` : "Patron sekmesinden kapalı vitrin kurabilirsin."}</small></div>
        <button class="btn sm" type="button" data-a="show" data-id="${car.id}" ${car.status !== "lot" || (!car.show && G.showUsed() >= G.showSlots()) ? "disabled" : ""}>${car.show ? "Vitrinden al" : "Vitrine koy"}</button></div>
      <div class="opt"><div><b>Toptancıya sat</b><small>Hemen nakit: ${G.fmt(ws)} (${signed(ws - cost)})</small></div>
        <button class="btn sm danger" type="button" data-a="wholesale" data-id="${car.id}" ${car.status !== "lot" ? "disabled" : ""}>${UI.armed === "ws" + car.id ? "Onayla" : "Sat"}</button></div></div></section>`;
    // ayrıntılar
    html += `<details class="more"><summary>Araç bilgileri ve ekspertiz raporu</summary>
      <div class="row">${plateHTML(car.plate)}<span class="muted small">${car.days} gündür galeride · temizlik %${Math.round(car.clean)}</span></div>
      <div class="kv"><div><span>Yakıt / vites</span><b>${G.FUELS[car.fuel].name} · ${G.GEARS[car.gear].name}</b></div><div><span>Kasa</span><b>${esc(G.BODIES[md.body])}</b></div><div><span>Alış</span><b>${G.fmt(car.buy)}</b></div><div><span>Masraf</span><b>${G.fmt(car.spent)}</b></div></div>
      <div class="chips">${paintChips(car)}${issueChips(car)}</div>${bars(car, false)}
      ${car.inspected && car.issues.length ? car.issues.map(is => `<div class="row small"><div>${esc(G.ISSUES[is.id].name)}${is.id === "km" ? ` · gerçek km ${G.fmtKm(is.realKm)}` : ""}</div><div>${is.fixed ? '<span class="chip good">Giderildi</span>' : G.ISSUES[is.id].fix ? '<span class="chip warn">Onarılabilir</span>' : '<span class="chip bad">Kalıcı</span>'}</div></div>`).join("") : ""}</details>`;
    return html;
  };

  function priceInfo(p, mv, cost) {
    const pm = pct(p, mv), m = p - cost;
    return `<span class="${pm > 10 ? "neg" : pm < -5 ? "pos" : "muted"}">Piyasanın ${pm >= 0 ? "%" + pm + " üstü" : "%" + -pm + " altı"}</span> · <span class="${m >= 0 ? "pos" : "neg"}">${m >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(m))}</span>`;
  }

  SHEETS.listing = function (id) {
    const s = S(), l = s.listings.find(x => x.id === id);
    if (!l) return null;
    const car = l.car, ap = G.value(car, false);
    const deal = G.dealLabel(l.last, ap);
    const suggest = R.r1k(Math.min(l.last, ap * 0.9));
    const d = UI.draft;
    if (d.lid !== id) { d.lid = id; d.offer = suggest; }
    const lo = R.r1k(l.last * 0.6), hi = l.last;
    d.offer = R.clamp(d.offer, lo, hi);
    const cs = G.condScore(car);
    let html = head(G.carName(car), `${esc(G.SELLERS[l.seller].name)} · ${esc(l.sellerName)}`);
    html += `<div class="hero">${UI.carSVG(car)}<span class="sticker">${G.fmtS(l.last)}</span></div>
      <div class="tiles"><div><span>İstenen</span><b>${G.fmtS(l.last)}</b></div><div><span>Tahmini değer</span><b>${G.fmtS(ap)}</b></div><div><span>Değerlendirme</span><b class="${deal.c === "good" ? "pos" : deal.c === "bad" ? "neg" : ""}">${deal.t}</b></div></div>
      <p class="small">${spec(car)} · ${esc(car.color)}</p>
      <div class="chips">${paintChips(car)}${car.inspected ? issueChips(car) : ""}<span class="chip ${cs >= 70 ? "good" : cs >= 50 ? "warn" : "bad"}">Durum ${cs}${car.inspected ? "" : " (satıcı beyanı)"}</span></div>`;
    if (!car.inspected && !l.done) {
      html += `<div class="card rec"><div><b>Almadan önce ekspertiz</b><p class="small muted">Satıcının söylediği ile gerçek durum farklı olabilir.</p></div>
        <div class="btns"><button class="btn warn" type="button" data-a="l-inspect" data-id="${l.id}">${G.fmt(G.expertCost())} öde</button>${adBtn("inspect", "Reklamla ücretsiz", `data-l="${l.id}"`)}</div></div>`;
    }
    if (l.msgs.length) html += `<div class="chat">${l.msgs.map(m => `<div class="msg ${m.who}">${esc(m.text)}</div>`).join("")}</div>`;
    if (!l.done) {
      if (G.spaceLeft() <= 0) html += `<p class="neg small">Galeride boş park yeri yok. Önce bir araç sat ya da büyük bir yere taşın.</p>`;
      html += `<div class="btns big-btns"><button class="btn pri" type="button" data-a="l-offer" data-id="${l.id}" data-v="${suggest}">${suggest < l.last ? `${G.fmtS(suggest)} teklif et` : `${G.fmtS(l.last)} ver, al`}</button>${suggest < l.last ? `<button class="btn" type="button" data-a="l-buy" data-id="${l.id}">${G.fmtS(l.last)} ver, hemen al</button>` : ""}</div>
        <details class="more"><summary>Kendi teklifimi vereyim</summary><div class="range"><div class="range-out"><b id="ofOut">${G.fmt(d.offer)}</b><span class="small muted" id="ofInfo">${offerInfo(d.offer, l, ap)}</span></div>
        <input type="range" id="ofRange" data-in="offer" min="${lo}" max="${hi}" step="1000" value="${d.offer}" aria-label="Teklif">
        <button class="btn pri" type="button" data-a="l-offer" data-id="${l.id}">Bu teklifi ver</button></div></details>
        <p class="muted small">Satıcının sabrı: ${"●".repeat(Math.max(0, l.rounds))}${"○".repeat(Math.max(0, G.SELLERS[l.seller].rounds - l.rounds))} · Çok düşük teklif satıcıyı kızdırır.</p>`;
    } else if (l.bought) html += `<div class="card"><p class="pos"><b>Araç artık senin.</b> Şimdi fiyat etiketini as.</p><button class="btn pri" type="button" data-a="car" data-id="${car.id}">Araca git</button></div>`;
    return html;
  };
  function offerInfo(o, l, ap) {
    const pm = pct(o, ap);
    return `İstenenin %${Math.round((1 - o / l.last) * 100)} altı · değerin ${pm >= 0 ? "%" + pm + " üstü" : "%" + -pm + " altı"}`;
  }

  SHEETS.cust = function (id) {
    const s = S(), c = s.custs.find(x => x.id === id);
    if (!c) return head("Görüşme bitti", "Müşteri galeriden ayrıldı.");
    const car = G.custCar(c);
    if (!car) return null;
    const cost = car.buy + car.spent;
    const d = UI.draft;
    if (d.cid !== id) { d.cid = id; d.counter = car.price; }
    const lo = c.offer, hi = Math.max(car.price, c.offer + 1000);
    d.counter = R.clamp(d.counter, lo, hi);
    const t = G.CT[c.type];
    const raise = raiseAmount(c, car);
    let html = head(c.name, `${esc(t.name)} · ${esc(G.carTitle(car))}`);
    html += `<div class="tiles"><div><span>Teklif</span><b>${G.fmtS(c.offer)}</b></div><div><span>Etiket</span><b>${G.fmtS(car.price)}</b></div><div><span>Teklifte kâr</span><b class="${c.offer - cost >= 0 ? "pos" : "neg"}">${signed(c.offer - cost)}</b></div></div>
      <div class="pat-row"><span class="small muted">Sabrı</span><div class="prog pat"><i data-pat="${c.id}" style="width:${c.patience / c.patMax * 100}%"></i></div><span class="small muted">${c.rounds} hak</span></div>
      <div class="chat">${c.msgs.map(m => `<div class="msg ${m.who}">${esc(m.text)}</div>`).join("")}</div>`;
    if (c.trade) {
      html += `<div class="card"><div class="row"><div><b>Takas: ${esc(G.carTitle(c.trade))}</b><p class="small muted">${spec(c.trade)}</p></div><div class="num">${G.fmtS(c.tradeVal)}</div></div>
        <p class="muted small">Kabul edersen eline ${G.fmt(c.offer - c.tradeVal)} nakit ve bu araç geçer.</p></div>`;
    }
    html += `<div class="btns big-btns"><button class="btn pri" type="button" data-a="accept" data-id="${c.id}">${G.fmtS(c.offer)}'ye sat</button>
      ${raise > c.offer ? `<button class="btn warn" type="button" data-a="raise" data-id="${c.id}">${G.fmtS(raise)} iste</button>` : ""}
      ${car.price > raise ? `<button class="btn" type="button" data-a="hold" data-id="${c.id}">Etikette diren (${G.fmtS(car.price)})</button>` : ""}
      <button class="btn danger" type="button" data-a="reject" data-id="${c.id}">Reddet</button></div>
      ${adBtn("tea", "Çay ikram et: sabrı dolar, +1 pazarlık hakkı", `data-id="${c.id}"`, "block")}
      <details class="more"><summary>Kendi karşı teklifimi vereyim</summary><div class="range"><div class="range-out"><b id="coOut">${G.fmt(d.counter)}</b><span class="small" id="coInfo">${counterInfo(d.counter, cost)}</span></div>
      <input type="range" id="coRange" data-in="counter" min="${lo}" max="${hi}" step="1000" value="${d.counter}" aria-label="Karşı teklif">
      <button class="btn warn" type="button" data-a="counter" data-id="${c.id}">Bu fiyatı söyle</button></div></details>
      <p class="muted small">Çok yüksek karşı teklif müşteriyi kaçırır. Pazarlık hakkı bitince müşteri gider.</p>`;
    return html;
  };
  function counterInfo(v, cost) { const m = v - cost; return `<span class="${m >= 0 ? "pos" : "neg"}">${m >= 0 ? "kâr" : "zarar"} ${G.fmtS(Math.abs(m))}</span>`; }

  SHEETS.auction = function () {
    const s = S(), a = s.auction;
    let html = head("İhale salonu", `Gün ${a.day} · ${a.lots.length} araç · Kasa ${G.fmtS(s.money)}`, !a.live);
    if (a.finished) {
      html += `<div class="card"><h3>İhale bitti</h3>${a.lots.map(l => `<div class="row small"><div>${esc(G.carTitle(l.car))}</div><div class="${l.winner === "Sen" ? "pos" : "muted"}">${l.winner ? esc(l.winner) + " · " + G.fmtS(l.price) : "Satılmadı"}</div></div>`).join("")}</div>
        <button class="btn pri block" type="button" data-a="close">Kapat</button>`;
      return html;
    }
    const lot = a.lots[a.idx], car = lot.car, L = a.live;
    html += `<div class="hero">${UI.carSVG(car)}<span class="sticker">Lot ${a.idx + 1}/${a.lots.length}</span></div>
      <div class="row"><div><b>${esc(G.carTitle(car))}</b><p class="small muted">${esc(G.aucSrc(lot.src))} · ${spec(car)}</p></div><div class="num small">≈ ${G.fmtS(G.value(car, false))}</div></div>
      <div class="chips">${paintChips(car)}${car.inspected ? issueChips(car) : `<span class="chip warn">Kontrol edilmedi</span>`}${condChip(car)}</div>`;
    if (!L) {
      html += `${s.upg.expert && !car.inspected ? `<button class="btn warn block" type="button" data-a="a-inspect">Hızlı kontrol · ${G.fmt(G.expertCost())}</button>` : ""}
        <button class="btn pri block" type="button" data-a="a-start">${a.idx === 0 ? "İhaleyi başlat" : "Sıradaki lotu aç"}</button>
        <p class="muted small">Açılış ${G.fmt(lot.start)} · artış ${G.fmt(lot.inc)}. Son tekliften sonra 4 saniye kimse artırmazsa çekiç iner.</p>`;
    } else {
      const next = L.leader ? L.bid + lot.inc : L.bid;
      html += `<div class="card"><div class="row"><div><div class="muted small">${L.leader ? "En yüksek teklif · " + esc(L.leader) : "Açılış fiyatı"}</div><div class="auc-big ${L.leader === "Sen" ? "pos" : ""}">${G.fmt(L.bid)}</div></div><div class="num" style="font-size:22px;font-weight:700">${Math.max(0, L.timer).toFixed(1).replace(".", ",")} sn</div></div>
        <div class="prog"><i style="width:${Math.min(100, L.timer / 7 * 100)}%"></i></div>
        <div class="btns big-btns"><button class="btn pri" type="button" data-a="a-bid" ${L.leader === "Sen" ? "disabled" : ""}>${L.leader === "Sen" ? "Teklif senin" : "Teklif ver · " + G.fmtS(next)}</button><button class="btn" type="button" data-a="a-skip" ${L.leader === "Sen" ? "disabled" : ""}>Pas geç</button></div>
        <div class="auc-log">${L.log.slice(0, 5).map(x => `<div>${esc(x)}</div>`).join("")}</div></div>`;
    }
    return html;
  };

  SHEETS.summary = function () {
    const sm = S().summary;
    if (!sm) return null;
    const T = sm.today;
    let html = head(`Gün ${sm.day} kapandı`, "Kepenkler indi. Günün hesabı:", false);
    html += `<div class="tiles"><div><span>Satış</span><b>${T.sales}</b></div><div><span>Satış kârı</span><b class="${T.profit >= 0 ? "pos" : "neg"}">${G.fmtS(T.profit)}</b></div><div><span>Günün neti</span><b class="${sm.net >= 0 ? "pos" : "neg"}">${G.fmtS(sm.net)}</b></div></div>`;
    if (!sm.bonusTaken && G.adLeft("bonus")) {
      html += `<div class="card bonus"><div><b>Sponsor bonusu: ${G.fmt(sm.bonus)}</b><p class="small muted">Kısa bir reklam izle, bu para kasana girsin. İstemezsen geç.</p></div>${adBtn("bonus", "İzle ve al", "", "block")}</div>`;
    } else if (sm.bonusTaken) html += `<p class="pos small"><b>Sponsor bonusu kasaya girdi: ${G.fmt(sm.bonus)}</b></p>`;
    html += `<details class="more"><summary>Giderler ve ayrıntılar</summary><div class="card">${sm.lines.map(([l, v]) => `<div class="row small"><div>${esc(l)}</div><div class="num">${v < 0 ? "−" : ""}${G.fmt(Math.abs(v))}</div></div>`).join("")}
      ${T.commission ? `<div class="row small"><div>Danışman primi</div><div class="num">${G.fmt(T.commission)}</div></div>` : ""}${T.invest ? `<div class="row small"><div>Yatırım (ekipman, taşınma)</div><div class="num">${G.fmt(T.invest)}</div></div>` : ""}
      <div class="row small"><div>Atölye ve ekspertiz</div><div class="num">${G.fmt(T.spentWork)}</div></div><div class="row small"><div>Ziyaretçi</div><div class="num">${T.visitors}</div></div></div></details>
      ${sm.news.length ? `<div class="card"><h3>Haberler</h3>${sm.news.map(n => `<div><b>${esc(n.title)}</b><p class="muted small">${esc(n.text)}</p></div>`).join("")}</div>` : ""}
      ${sm.lost.length ? `<div class="card"><h3>Kaçan müşteriler</h3>${sm.lost.map(l => `<div class="row small"><div>${esc(l.type)}</div><div class="muted" style="text-align:right">${esc(l.text)}</div></div>`).join("")}</div>` : ""}
      <button class="btn pri block" type="button" data-a="nextday">Gün ${S().day} başlasın</button>`;
    return html;
  };

  SHEETS.fac = function (key) {
    const s = S(), F = G.FACILITIES[key];
    if (!F) return null;
    const n = G.facLevel(key), nx = G.facNext(key);
    let html = head(F.name, `Seviye ${n}/${G.FAC_MAX}`);
    html += `<div class="card"><p class="small">${esc(F.desc)}</p>${pips(n)}</div>`;
    const cur = G.facLines(key, n);
    html += `<div class="card"><h3>Şu an</h3><ul class="facl">${cur.map(l => `<li>${esc(l)}</li>`).join("")}</ul></div>`;
    if (nx) {
      const nl = G.facLines(key, nx.n);
      html += `<div class="card"><h3>${nx.n}. seviyede</h3><ul class="facl">${nl.map((l, i) => `<li class="${l !== cur[i] ? "up" : ""}">${esc(l)}</li>`).join("")}</ul>
        ${F.marks[nx.n] ? `<p class="pos small"><b>Yeni: ${esc(F.marks[nx.n])}</b></p>` : ""}
        <div class="kv"><div><span>Fiyat</span><b>${G.fmtS(nx.cost)}</b></div><div><span>Gereken seviye</span><b class="${s.level >= nx.req ? "pos" : "neg"}">${nx.req} (sen ${s.level})</b></div></div>
        <button class="btn pri block" type="button" data-a="facup" data-v="${key}" ${s.level >= nx.req && s.money >= nx.cost ? "" : "disabled"}>${s.level < nx.req ? `Oyuncu seviyesi ${nx.req} olunca açılır` : s.money < nx.cost ? `Kasada ${G.fmtS(nx.cost)} olmalı` : `${n ? "Yükselt" : "Kur"} · ${G.fmt(nx.cost)}`}</button></div>`;
    }
    // yol haritası: önemli seviyeler
    const marks = Object.keys(F.marks).map(Number).sort((a, b) => a - b);
    html += `<div class="card"><h3>Yol haritası</h3>${marks.map(m => `<div class="row small"><div><b class="${m <= n ? "pos" : ""}">${m}. seviye</b> · ${esc(F.marks[m])}</div><div class="muted">${m <= n ? "Açık" : `Oyuncu sv. ${G.facReq(key, m)} · ${G.fmtS(G.facCost(key, m))}`}</div></div>`).join("")}</div>`;
    return html;
  };

  SHEETS.offline = function () {
    const o = UI.offline;
    if (!o) return null;
    const pr = G.passiveRate();
    const h = o.away >= 60 * 24 ? `${Math.floor(o.away / 1440)} gün ${Math.floor((o.away % 1440) / 60)} saat` : o.away >= 60 ? `${Math.floor(o.away / 60)} saat ${Math.round(o.away % 60)} dakika` : `${Math.round(o.away)} dakika`;
    return head("Hoş geldin patron!", `Sen ${h} yoktun. Galerin boş durmadı.`, false) + `
      <div class="card welcome"><span class="muted small">Pasif gelir</span><div class="big-money">${G.fmt(o.amount)}</div>
        <p class="small muted">Saatte ${G.fmtS(pr.total)} × ${o.hours.toFixed(1).replace(".", ",")} saat${o.capped ? ` (en fazla ${G.OFFLINE_MAX_H} saat birikir)` : ""}</p></div>
      ${adBtn("offline", `Reklam izle, 2 katını al: ${G.fmtS(o.amount * 2)}`, "", "block")}
      <button class="btn pri block" type="button" data-a="offline-claim">${G.fmtS(o.amount)} al</button>`;
  };

  SHEETS.freecar = function () {
    const car = UI.freeCar;
    if (!car) return null;
    const v = G.value(car, false), cs = G.condScore(car);
    return head("Bedava araç fırsatı!", "Kısa bir reklam izle, bu araç hiç para ödemeden senin olsun.") + `
      <div class="hero">${UI.carSVG(car)}<span class="sticker">Bedava</span></div>
      <div class="tiles"><div><span>Araç</span><b>${esc(G.carTitle(car))}</b></div><div><span>Piyasa değeri</span><b class="pos">${G.fmtS(v)}</b></div><div><span>Durum</span><b>${cs} · ${G.condLabel(cs)}</b></div></div>
      <p class="small">${spec(car)} · ${esc(car.color)}</p>
      <div class="chips">${paintChips(car)}<span class="chip good">Ekspertiz temiz</span></div>
      ${adBtn("freecar", "Reklam izle, bedava al", "", "block big")}
      <button class="btn block" type="button" data-a="freecar-no">Hayır, teşekkürler</button>`;
  };

  SHEETS.skip = function () {
    const s = S();
    const offers = s.custs.filter(c => c.state === "offer").length;
    const sales = G.staffOf("satis").length;
    return head("Günü bitir", `Saat ${G.clock(s.min)} · kapanışa ${G.fmtDur(G.CLOSE - s.min)} var`) + `
      <div class="card"><p class="small">Zaman 19:00'a kadar hızla akar: atölye işleri ilerler, müşteriler gelir, ilan saatlerinde yeni ilanlar düşer.</p>
        ${offers ? `<p class="small ${sales ? "" : "neg"}">${offers} müşteri teklif bekliyor. ${sales ? "Satış danışmanın onlarla ilgilenecek." : "Danışmanın olmadığı için cevapsız kalırlarsa beklemekten sıkılıp gidebilirler."}</p>` : ""}
        <p class="small muted">Danışmanın yoksa gün içinde gelen yeni müşterilerin teklifleri de cevapsız kalır.</p></div>
      <div class="btns big-btns"><button class="btn pri" type="button" data-a="skip-go">Günü bitir</button><button class="btn" type="button" data-a="close">Vazgeç</button></div>`;
  };

  SHEETS.feed = function () {
    return head("Galeri defteri", "Son olaylar") + `<div class="card">${S().feed.map(f => `<div class="small"><span class="muted">${esc(f.t)}</span><br>${esc(f.text)}</div>`).join("") || '<p class="muted">Henüz kayıt yok.</p>'}</div>`;
  };

  SHEETS.intro = function () {
    const demo = ["ferro-egeo", "kaizen-taiga", "mirage-stallion"].map((m, i) => UI.carSVG({ m, color: ["Beyaz", "Lacivert", "Kırmızı"][i], clean: 100 })).join("");
    return head("Galeri Patronu", "Sanayideki arsadan otomotiv merkezine.", false) + `
      <div class="intro-art">${demo}</div>
      <div class="field"><label for="nameIn"><b>Galerinin adı</b></label><input type="text" id="nameIn" maxlength="28" value="${esc(UI.draft.name || "Patron Otomotiv")}" autocomplete="off"></div>
      <div class="card"><h3>Nasıl oynanır</h3><ol class="steps small">
        <li><b>Araç al</b>: ilanlarda "Fırsat" yazan araçlara bak, pazarlık et ya da ihaleye gir.</li>
        <li><b>Hazırla</b>: araca dokun, önerilen atölye işini başlat.</li>
        <li><b>Fiyat koy</b>: tek dokunuşla etiketi as. Müşteri gelince teklifini kabul et ya da biraz daha iste.</li>
        <li><b>Büyü</b>: kârla vitrin, atölye, personel ve yeni adresler al.</li></ol>
        <p class="muted small">Ne yapacağını bilmezsen Galeri ekranındaki "Şimdi ne yapmalı?" kartına bak. Reklam izlemek tamamen isteğe bağlıdır; izlersen bonus kazanırsın.</p></div>
      <button class="btn pri block" type="button" data-a="begin">Kepenkleri aç</button>`;
  };

  // ---- Çizim ----
  function viewSig() {
    const s = S();
    switch (UI.tab) {
      case "galeri": return "g" + s.day + Math.floor(s.min / 20) + s.cars.map(c => `${c.id}.${c.status}.${c.price}.${c.show}.${Math.round(c.clean / 20)}.${c.inspected}.${c.motor}.${c.body}.${c.inter}.${c.painted}.${c.issues.filter(i => i.fixed).length}`).join() + "|" + s.custs.map(c => c.id + c.state + c.carId).join() + "|" + s.jobs.map(j => j.id + "." + Math.ceil(j.left / 30)).join() + "|" + JSON.stringify(s.upg) + JSON.stringify(s.fac) + s.loc + G.boostActive() + Math.round(s.money / 50000) + G.ustas().map(u => u.resting + "" + Math.round(u.energy / 35)).join();
      case "piyasa": return "p" + s.day + UI.sub.piyasa + s.listings.map(l => l.id + "." + l.done + "." + l.last).join() + "|" + Math.floor(s.min / 60) + G.adLeft("ilan") + JSON.stringify(UI.lf) + "|" + s.auction.idx + s.auction.finished + "|" + Math.round(s.money / 10000) + "|" + G.carCount();
      case "musteri": return "m" + s.custs.map(c => `${c.id}.${c.state}.${c.offer}`).join() + "|" + s.lost.length + "|" + s.today.sales + G.boostActive() + G.adLeft("boost") + (s.min < G.CLOSE - 60);
      case "atolye": return "a" + s.jobs.map(j => j.id).join() + "|" + JSON.stringify(s.upg) + JSON.stringify(s.fac) + s.staff.length + G.adLeft("rush") + G.restFree() + G.ustas().map(u => u.resting + "" + G.buffActive(u) + Math.round(u.energy / 35)).join();
      case "patron": return "b" + s.day + UI.sub.patron + Math.round(s.money / 1000) + s.staff.length + s.cands.length + JSON.stringify(s.upg) + JSON.stringify(s.fac) + (s.lux || []).join() + UI.luxCat + G.partyReady() + G.partyActive() + s.loan + s.ads + s.loc + s.level + s.quests.length + Math.round(s.rep) + s.today.sales + UI.armed + s.maxDisc;
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
    if (sh.kind === "car") { const c = s.cars.find(x => x.id === sh.id); const j = s.jobs.find(x => x.carId === sh.id); return c ? `${c.status}${c.price}${c.show}${c.inspected}${c.motor}${c.body}${c.inter}${c.painted}${Math.round(c.clean / 10)}${Math.round(s.money / 10000)}${G.baysFree()}${UI.armed}${j ? Math.ceil(j.left / 30) : ""}${G.adLeft("rush")}${G.adLeft("inspect")}` : "x"; }
    if (sh.kind === "cust") { const c = s.custs.find(x => x.id === sh.id); return c ? `${c.offer}${c.msgs.length}${c.rounds}${G.adLeft("tea")}` : "gone"; }
    if (sh.kind === "listing") { const l = s.listings.find(x => x.id === sh.id); return l ? `${l.msgs.length}${l.done}${l.car.inspected}${G.carCount()}${l.rounds}` : "x"; }
    if (sh.kind === "auction") { const a = s.auction; return a.live ? `${a.idx}${a.live.bid}${a.live.leader}${Math.ceil(a.live.timer * 10)}` : `${a.idx}${a.finished}${a.lots[a.idx] && a.lots[a.idx].car.inspected}`; }
    if (sh.kind === "fac") return `${G.facLevel(sh.id)}${Math.round(s.money / 10000)}${s.level}`;
    if (sh.kind === "summary") { const sm = s.summary; return sm ? `${sm.day}${sm.bonusTaken}` : "x"; }
    return sh.kind;
  }

  UI.renderSheet = function (force) {
    const wrap = $("sheetWrap");
    if (!UI.sheet) { wrap.hidden = true; return; }
    const sig = sheetSig();
    if (!force && sig === UI.sheetSig) return;
    if (UI.dragging && !force) return;
    const html = SHEETS[UI.sheet.kind](UI.sheet.id);
    if (html == null) { UI.close(true); return; }
    UI.sheetSig = sig;
    const sh = $("sheet");
    const top = sh.scrollTop;
    const open = [...sh.querySelectorAll("details")].map(x => x.open);
    sh.innerHTML = html;
    [...sh.querySelectorAll("details")].forEach((x, i) => { if (open[i]) x.open = true; });
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
    if (changed) { UI.draft = { name: UI.draft.name }; $("sheet").innerHTML = ""; $("sheet").scrollTop = 0; }
    UI.renderSheet(true);
  };
  UI.close = function (force) {
    if (!force && UI.sheet && (UI.sheet.kind === "summary" || UI.sheet.kind === "intro" || UI.sheet.kind === "offline")) return;
    if (!force && UI.sheet && UI.sheet.kind === "auction" && S().auction.live) return;
    for (const c of S().custs) c.userBusy = false;
    UI.sheet = null;
    UI.armed = null;
    $("sheetWrap").hidden = true;
    UI.render();
  };
  UI.blocking = () => (G.Ads && G.Ads.showing) || (!!UI.sheet && (UI.sheet.kind === "summary" || UI.sheet.kind === "intro" || UI.sheet.kind === "auction" || UI.sheet.kind === "offline"));

  // Sık güncellenen küçük öğeler (ilerleme çubukları, sabır), gerekirse tam yeniden çizim
  UI.tick = function () {
    const s = S();
    if (!s) return;
    renderTop();
    if (s.summary && (!UI.sheet || (UI.sheet.kind !== "summary" && UI.sheet.kind !== "offline"))) { UI.sheet = { kind: "summary" }; UI.renderSheet(true); }
    const sig = viewSig();
    if (sig !== UI.sig && !UI.dragging) { UI.sig = sig; const y = window.scrollY; $("view").innerHTML = VIEWS[UI.tab](); window.scrollTo(0, y); }
    renderNav();
    maybeFreeCar(s);
    for (const el of document.querySelectorAll("[data-job]")) { const j = s.jobs.find(x => x.id === +el.dataset.job); if (j) el.style.width = ((1 - j.left / j.total) * 100) + "%"; }
    for (const el of document.querySelectorAll("[data-jobt]")) { const j = s.jobs.find(x => x.id === +el.dataset.jobt); if (j) el.textContent = G.fmtDur(G.jobEta(j)); }
    for (const el of document.querySelectorAll("[data-energy]")) { const u = s.staff.find(x => x.id === +el.dataset.energy); if (u) { el.style.width = u.energy + "%"; el.className = energyCls(u.energy); } }
    for (const el of document.querySelectorAll("[data-energyt]")) { const u = s.staff.find(x => x.id === +el.dataset.energyt); if (u) el.textContent = Math.round(u.energy); }
    for (const el of document.querySelectorAll("[data-pat]")) { const c = s.custs.find(x => x.id === +el.dataset.pat); if (c) el.style.width = (c.patience / c.patMax * 100) + "%"; }
    UI.renderSheet(false);
  };

  // İlanlara bakarken ara sıra "reklam izle, bu araç bedava senin" teklifi; kasa azsa daha sık
  function maybeFreeCar(s) {
    const onListings = UI.tab === "piyasa" && UI.sub.piyasa === "ilan";
    if (!onListings) { UI.listingsSince = 0; return; }
    const now = Date.now();
    if (!UI.listingsSince) UI.listingsSince = now;
    if (UI.sheet || s.paused || s.summary || UI.dragging || !G.Ads || G.Ads.showing) return;
    if (!G.adLeft("freecar") || G.spaceLeft() <= 0) return;
    if (!s.freeShown || s.freeShown.day !== s.day) s.freeShown = { day: s.day, n: 0 };
    if (s.freeShown.n >= 3) return;
    if (now - Math.max(UI.listingsSince, UI.lastFree || 0) < 45000) return;
    const low = s.money < 300000;
    if (!R.chance(low ? 0.008 : 0.0033)) return;
    const car = G.makeFreeCar();
    if (!car) return;
    UI.freeCar = car;
    UI.lastFree = now;
    s.freeShown.n++;
    UI.open("freecar");
  }

  // Oyuna dönünce pasif gelir ekranı
  UI.checkOffline = function (ms) {
    const s = S();
    if (!s || !s.started || UI.offline || (UI.sheet && UI.sheet.kind === "intro")) return;
    const o = G.offlineReward(ms);
    if (!o || o.amount <= 0) return;
    UI.offline = o;
    UI.sheet = { kind: "offline" };
    UI.renderSheet(true);
  };

  // ---- Reklam izleme ----
  UI.watch = async function (kind, apply) {
    if (!G.adLeft(kind)) { UI.toast("Bu ödül için bugünkü hakkın bitti; yarın yenilenir.", "bad"); return; }
    if (!G.Ads) return;
    const ok = await G.Ads.rewarded();
    if (ok === null) { UI.toast("Şu an reklam yüklenemedi. Biraz sonra tekrar dene.", "bad"); return; }
    if (!ok) { UI.toast("Reklam yarıda kaldı; ödül verilmedi."); return; }
    G.adUsed(kind);
    apply();
    G.save();
    UI.render();
  };

  // ---- Eylemler ----
  function msg(r) { if (r && r.msg) UI.toast(r.msg, "bad"); }
  const carById = id => S().cars.find(c => c.id === +id);
  const custById = id => S().custs.find(c => c.id === +id);
  function sayInspect(r) { UI.toast(r.found.length ? `Ekspertiz: ${r.found.join(", ")}` : "Ekspertiz temiz çıktı.", r.found.length ? "bad" : "good"); }
  function afterSale() { if (UI.sheet && UI.sheet.kind === "cust") UI.sheet = null; UI.render(); }

  const ACT = {
    tab: el => { UI.tab = el.dataset.v; if (UI.sheet && !UI.blocking()) UI.sheet = null; window.scrollTo(0, 0); UI.armed = null; UI.render(); },
    sub: el => { UI.sub[el.dataset.k] = el.dataset.v; UI.render(); },
    sort: el => { UI.sort = el.dataset.v; UI.render(); },
    speed: el => { const v = +el.dataset.v; const s = S(); if (v === 0) s.paused = true; else { s.speed = v; if (!s.summary) s.paused = false; } renderTop(); },
    close: () => UI.close(),
    feed: () => UI.open("feed"),
    car: el => UI.open("car", +el.dataset.id),
    listing: el => UI.open("listing", +el.dataset.id),
    cust: el => UI.open("cust", +el.dataset.id),
    preset: el => {
      const car = carById(el.dataset.id); if (!car) return;
      G.setPrice(car, +el.dataset.v);
      UI.toast(`Etiket asıldı: ${G.fmt(car.price)}`);
      UI.render();
    },
    "price-save": el => {
      const car = carById(el.dataset.id); if (!car) return;
      G.setPrice(car, UI.draft.price);
      UI.toast(`Etiket asıldı: ${G.fmt(car.price)}`);
      UI.render();
    },
    "price-clear": el => { const car = carById(el.dataset.id); if (car) { car.price = 0; for (const c of S().custs.slice()) if (c.carId === car.id && c.state === "offer") G.leave(c, null); UI.render(); } },
    inspect: el => { const car = carById(el.dataset.id); if (!car) return; const r = G.inspect(car); if (!r.ok) return msg(r); sayInspect(r); UI.render(); },
    job: el => {
      const car = carById(el.dataset.id); if (!car) return;
      const o = G.jobOptions(car).find(x => x.type === el.dataset.t && (x.issue || "") === (el.dataset.is || ""));
      if (!o) return;
      const r = G.startJob(car, o);
      if (!r.ok) return msg(r);
      UI.toast(`${o.name} başladı · ${G.fmtDur(o.mins)}`);
      UI.render();
    },
    show: el => { const car = carById(el.dataset.id); if (car) { msg(G.toggleShow(car)); UI.render(); } },
    wholesale: el => {
      const id = +el.dataset.id;
      if (UI.armed !== "ws" + id) { UI.armed = "ws" + id; UI.renderSheet(true); return; }
      const car = carById(id); if (!car) return;
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
    "l-offer": el => listingOffer(+el.dataset.id, el.dataset.v ? +el.dataset.v : UI.draft.offer),
    "l-buy": el => { const l = S().listings.find(x => x.id === +el.dataset.id); if (l) listingOffer(l.id, l.last); },
    accept: el => { const c = custById(el.dataset.id); if (!c) return; const r = G.acceptOffer(c); if (!r.ok) return msg(r); afterSale(); },
    raise: el => counter(el.dataset.id, c => raiseAmount(c, G.custCar(c))),
    hold: el => counter(el.dataset.id, c => G.custCar(c).price),
    counter: el => counter(el.dataset.id, () => UI.draft.counter),
    reject: el => { const c = custById(el.dataset.id); if (c) { G.rejectOffer(c); afterSale(); } },
    auction: () => UI.open("auction"),
    "a-start": () => { G.auctionBegin(); UI.renderSheet(true); },
    "a-bid": () => { msg(G.auctionBid()); UI.renderSheet(true); },
    "a-skip": () => { G.auctionSkip(); },
    "a-inspect": () => { const a = S().auction; const r = G.inspect(a.lots[a.idx].car); if (!r.ok) return msg(r); sayInspect(r); UI.renderSheet(true); },
    upg: el => { msg(G.buyUpgrade(el.dataset.v)); UI.render(); },
    move: () => { msg(G.moveTo(S().loc + 1)); UI.render(); },
    hire: el => { const c = S().cands.find(x => x.id === +el.dataset.id); if (c) { msg(G.hire(c)); UI.render(); } },
    fire: el => {
      const id = +el.dataset.id;
      if (UI.armed !== "fire" + id) { UI.armed = "fire" + id; UI.render(); return; }
      const st = S().staff.find(x => x.id === id); if (st) G.fire(st);
      UI.armed = null; UI.render();
    },
    disc: el => { S().maxDisc = +el.dataset.v / 100; UI.render(); },
    fac: el => UI.open("fac", el.dataset.v),
    skip: () => { const s = S(); if (!s.started || s.summary || UI.blocking()) return; UI.open("skip"); },
    "skip-go": () => { UI.sheet = null; UI.skipping = true; try { G.skipDay(); } finally { UI.skipping = false; } G.save(); UI.render(); },
    "ad-freecar": () => { const car = UI.freeCar; if (!car) return; if (G.spaceLeft() <= 0) { UI.toast("Galeride boş park yeri yok.", "bad"); return; } UI.watch("freecar", () => { if (G.claimFreeCar(car)) { UI.freeCar = null; UI.sheet = null; UI.toast(`${G.carTitle(car)} artık senin!`, "good"); } }); },
    "freecar-no": () => { UI.freeCar = null; UI.close(); },
    "lf-tog": el => { UI.lf[el.dataset.v] = !UI.lf[el.dataset.v]; UI.render(); },
    "lf-reset": () => { UI.lf = { seg: "all", sort: "yeni", deal: false, afford: false }; UI.render(); },
    "offline-claim": () => { G.claimOffline(UI.offline, 1); UI.offline = null; UI.sheet = S().summary ? { kind: "summary" } : null; G.save(); UI.render(); },
    "ad-offline": () => { const o = UI.offline; if (!o) return; UI.watch("offline", () => { G.claimOffline(o, 2); UI.offline = null; UI.sheet = S().summary ? { kind: "summary" } : null; }); },
    luxcat: el => { UI.luxCat = el.dataset.v; UI.armed = null; UI.render(); },
    "lux-buy": el => { const r = G.buyLux(el.dataset.v); msg(r); if (r.ok) UI.toast(`Hayırlı olsun: ${G.LX[el.dataset.v].name}`, "good"); UI.render(); },
    "lux-sell": el => {
      const id = el.dataset.v;
      if (UI.armed !== "lux" + id) { UI.armed = "lux" + id; UI.render(); return; }
      UI.armed = null; G.sellLux(id); UI.render();
    },
    lux: () => { UI.tab = "patron"; UI.sub.patron = "lux"; UI.sheet = null; window.scrollTo(0, 0); UI.render(); },
    party: () => { const r = G.throwParty(); msg(r); if (r.ok) UI.toast("Parti verildi! Yarın galeriye zengin müşteriler gelecek.", "good"); UI.render(); },
    facup: el => { const r = G.buyFac(el.dataset.v); msg(r); if (r.ok) UI.toast(`${G.FACILITIES[el.dataset.v].name}: seviye ${G.facLevel(el.dataset.v)}`, "good"); UI.render(); },
    rest: el => { const u = S().staff.find(x => x.id === +el.dataset.id); if (u) { msg(G.sendRest(u)); UI.render(); } },
    recall: el => { const u = S().staff.find(x => x.id === +el.dataset.id); if (u) { G.recall(u); UI.render(); } },
    ads: el => { S().ads = +el.dataset.v; UI.render(); },
    borrow: el => { msg(G.borrow(+el.dataset.v)); UI.render(); },
    repay: el => { msg(G.repay(+el.dataset.v)); UI.render(); },
    reset: () => {
      if (UI.armed !== "reset") { UI.armed = "reset"; UI.render(); return; }
      UI.armed = null;
      G.wipe();
    },
    // reklam ödülleri
    "ad-rush": el => { const j = S().jobs.find(x => x.id === +el.dataset.id); if (j) UI.watch("rush", () => G.reward.rush(j)); },
    "ad-boost": () => UI.watch("boost", () => G.reward.boost()),
    "ad-bonus": () => UI.watch("bonus", () => G.reward.bonus(S().summary)),
    "ad-ilan": () => UI.watch("ilan", () => G.reward.ilan()),
    "ad-tea": el => { const c = custById(el.dataset.id); if (c) UI.watch("tea", () => G.reward.tea(c)); },
    "ad-inspect": el => {
      const car = el.dataset.l ? (S().listings.find(x => x.id === +el.dataset.l) || {}).car : carById(el.dataset.id);
      if (!car || car.inspected) return;
      UI.watch("inspect", () => {
        const r = G.reward.inspect(car);
        if (!r.ok) return;
        sayInspect(r);
        if (el.dataset.l) { const l = S().listings.find(x => x.id === +el.dataset.l); if (l) l.msgs.push({ who: "sales", text: r.found.length ? `Ekspertiz raporu: ${r.found.join(", ")}.` : "Ekspertiz raporu temiz." }); }
      });
    },
    nextday: async () => {
      const s = S();
      const ended = s.summary ? s.summary.day : s.day - 1;
      s.summary = null;
      UI.sheet = null;
      UI.render();
      if (G.Ads && G.interstitialDue(ended)) { G.interstitialShown(ended); await G.Ads.interstitial(); }
      s.paused = false;
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

  function counter(id, amountOf) {
    const c = custById(id); if (!c) return;
    const r = G.counterOffer(c, amountOf(c));
    if (r.res === "walked") UI.toast(`${c.name} anlaşamadan ayrıldı.`, "bad");
    if (r.res === "sold") return afterSale();
    UI.renderSheet(true);
    UI.render();
  }

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
    document.addEventListener("change", e => {
      const k = e.target.dataset && e.target.dataset.ch;
      if (!k) return;
      UI.lf[k] = e.target.value;
      UI.render();
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
    });
    const down = e => { if (e.target.matches && e.target.matches('input[type="range"]')) UI.dragging = true; };
    const up = () => { UI.dragging = false; };
    document.addEventListener("pointerdown", down);
    document.addEventListener("pointerup", up);
    document.addEventListener("pointercancel", up);
    document.addEventListener("touchstart", down, { passive: true });
    document.addEventListener("touchend", up);
  };
})(window.G = window.G || {});
