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
    UI.clubDraft = { id, isNew: !!isNew, ...c };
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
    S().clubs[d.id] = { name: d.name, code: d.code, shape: d.shape, icon: d.icon, c1: d.c1, c2: d.c2 };
    G.feed(`${d.name} kulübün hazır! Maç sonuçları gün sonu raporunda.`, "good");
    UI.clubDraft = null;
    G.save && G.save();
    UI.close(true);
    UI.sig = "";
    UI.render();
  };
  UI.ACT["club-edit"] = el => UI.openClub(el.dataset.v, false);
  // ad ve kısa ad yazılırken önizleme anında değişir
  document.addEventListener("input", e => {
    if (e.target.id === "clubName") { const t = document.getElementById("clubPrevName"); if (t) t.textContent = e.target.value; }
    if (e.target.id === "clubCode") { const t = document.querySelector(".club-prev [data-club-code]"); if (t) t.textContent = e.target.value.toLocaleUpperCase("tr-TR").slice(0, 4); }
  });
})(window.G);
