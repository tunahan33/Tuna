/* Galeri Patronu — telefon masası (tıkla-kazan)
 * Patron telefonla müşteri arayıp kapora ve komisyon toplar: her dokunuş para getirir, günlük arama hakkı vardır.
 * "Pazarlık ustalığı" dokunuş başına kazancı, "Çağrı merkezi" oyun saati ilerledikçe kendiliğinden gelen geliri artırır.
 * Ödüllü reklam 3 dakika boyunca kazancı ikiye katlar. */
(function (G) {
  "use strict";
  const UI = G.UI, R = G.R;
  const S = () => G.S;

  G.TAP = {
    max: 20, autoMax: 10,
    value: lvl => R.r100(300 * Math.pow(1.5, lvl - 1)),
    cost: lvl => R.r1k(40000 * Math.pow(1.85, lvl - 1)),     // bir sonraki seviyeye geçiş
    taps: lvl => 150 + 10 * lvl,                              // günlük arama hakkı
    autoCost: k => R.r1k(250000 * Math.pow(2.2, k)),
    autoPerMin: 0.04                                          // her çağrı merkezi seviyesi: oyun dakikası başına dokunuş değerinin %4'ü
  };
  const T = () => {
    const s = S();
    s.tap = s.tap || { lvl: 1, auto: 0, day: 0, used: 0, total: 0 };
    if (s.tap.day !== s.day) { s.tap.day = s.day; s.tap.used = 0; }
    return s.tap;
  };
  G.tapBoostOn = () => (S().tapBoost || 0) > Date.now();
  G.tapValue = () => G.TAP.value(T().lvl) * (G.tapBoostOn() ? 2 : 1);
  G.tapLeft = () => Math.max(0, G.TAP.taps(T().lvl) - T().used);
  G.tapOnce = function () {
    const t = T();
    if (!G.tapLeft()) return 0;
    const v = G.tapValue();
    t.used++; t.total += v;
    S().money += v;
    return v;
  };
  G.tapUp = function () {
    const t = T();
    if (t.lvl >= G.TAP.max) return { ok: false };
    const c = G.TAP.cost(t.lvl);
    if (S().money < c) return { ok: false, msg: "Kasada yeterli para yok." };
    S().money -= c; t.lvl++;
    return { ok: true };
  };
  G.tapAutoUp = function () {
    const t = T();
    if (t.auto >= G.TAP.autoMax) return { ok: false };
    const c = G.TAP.autoCost(t.auto);
    if (S().money < c) return { ok: false, msg: "Kasada yeterli para yok." };
    S().money -= c; t.auto++;
    return { ok: true };
  };
  // oyun dakikası başına çağrı merkezi geliri (engine.js G.minute çağırır)
  G.tapMinute = function () {
    const t = T();
    if (!t.auto) return;
    const v = Math.round(G.TAP.value(t.lvl) * G.TAP.autoPerMin * t.auto);
    S().money += v; t.total += v;
  };

  const PHONE = `<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.36 2.3.56 3.6.56a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.3.2 2.5.56 3.6a1 1 0 0 1-.25 1z" fill="currentColor"/></svg>`;
  UI.tapCard = function () {
    const t = T(), left = G.tapLeft(), maxT = G.TAP.taps(t.lvl);
    const up = t.lvl < G.TAP.max ? `<button class="btn sm" type="button" data-a="tap-up">Pazarlık ustalığı ${t.lvl + 1}. sv · ${G.fmtS(G.TAP.cost(t.lvl))}</button>` : `<span class="chip good">Ustalık son seviyede</span>`;
    const auto = t.auto < G.TAP.autoMax ? `<button class="btn sm" type="button" data-a="tap-auto">Çağrı merkezi ${t.auto + 1}. sv · ${G.fmtS(G.TAP.autoCost(t.auto))}</button>` : `<span class="chip good">Çağrı merkezi tam</span>`;
    return `<div class="card tap-card"><div class="row"><div><h3>Telefon masası</h3><p class="small muted">Her dokunuş para.</p></div><span class="chip ${G.tapBoostOn() ? "gold" : ""}" id="tapBoost">${G.tapBoostOn() ? "2× kazanç" : `Sv ${t.lvl}`}</span></div>
      <button class="tap-btn" type="button" data-a="tap" ${left ? "" : "disabled"}>${PHONE}<span><b id="tapVal">+${G.fmtS(G.tapValue())}</b><small id="tapLeft">${left ? `${left}/${maxT} arama hakkı` : "Bugünlük arama hakkın bitti"}</small></span></button>
      <div class="small muted">${t.auto ? `Çağrı merkezi oyun saati ilerledikçe saatte ≈ ${G.fmtS(Math.round(G.TAP.value(t.lvl) * G.TAP.autoPerMin * t.auto) * 60)} getiriyor. ` : ""}Bugüne kadar masadan: <span id="tapTotal">${G.fmtS(t.total)}</span></div>
      <div class="btns">${up}${auto}</div>
      ${G.tapBoostOn() ? "" : (UI.adBtn ? UI.adBtn("tap", "Reklam izle, 3 dakika 2 kat kazan", "", "block") : "")}</div>`;
  };
  function pop(el, v) {
    const r = el.getBoundingClientRect();
    const f = document.createElement("div");
    f.className = "tap-float";
    f.textContent = "+" + G.fmtS(v);
    f.style.left = (r.left + r.width / 2 + (Math.random() * 60 - 30)) + "px";
    f.style.top = (r.top + 6) + "px";
    document.body.appendChild(f);
    setTimeout(() => f.remove(), 900);
  }
  UI.ACT.tap = el => {
    const v = G.tapOnce();
    if (!v) return;
    pop(el, v);
    const l = document.getElementById("tapLeft"), left = G.tapLeft();
    if (l) l.textContent = left ? `${left}/${G.TAP.taps(T().lvl)} arama hakkı` : "Bugünlük arama hakkın bitti";
    if (!left) { el.disabled = true; }
    const tt = document.getElementById("tapTotal");
    if (tt) tt.textContent = G.fmtS(T().total);
    if (UI.renderTopNow) UI.renderTopNow();
  };
  UI.ACT["tap-up"] = () => { const r = G.tapUp(); if (r.msg) UI.toast(r.msg, "bad"); UI.sig = ""; UI.render(); };
  UI.ACT["tap-auto"] = () => { const r = G.tapAutoUp(); if (r.msg) UI.toast(r.msg, "bad"); UI.sig = ""; UI.render(); };
})(window.G);
