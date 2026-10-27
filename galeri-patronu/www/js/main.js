/* Galeri Patronu — başlatma, oyun döngüsü, kayıt */
(function (G) {
  "use strict";
  const UI = G.UI;
  const SAVE_KEY = "galeri-patronu-v1";
  const MIN_PER_SEC = 4; // 1× hızda bir oyun günü (09:00–19:00) yaklaşık 2,5 dakika sürer
  // Uygulamada kayıt ayrıca cihazın kalıcı deposuna (@capacitor/preferences) yazılır:
  // iOS yer daraldığında web depolamasını silebilir; açılışta localStorage boşsa kayıt oradan geri gelir.
  const Cap = window.Capacitor;
  const Prefs = Cap && Cap.Plugins && Cap.Plugins.Preferences;
  const nativePrefs = !!Prefs && Cap.getPlatform && Cap.getPlatform() !== "web";

  G.save = function () {
    if (!G.S || !G.S.started) return;
    G.S.lastSeen = Date.now();
    const data = JSON.stringify(G.S);
    try { localStorage.setItem(SAVE_KEY, data); } catch (e) { /* kayıt alanı yoksa oyun yine çalışır */ }
    if (nativePrefs) Prefs.set({ key: SAVE_KEY, value: data }).catch(() => {});
  };
  function loadSaved() {
    try {
      const d = JSON.parse(localStorage.getItem(SAVE_KEY) || "null");
      if (d && d.v === 1 && d.started) return d;
    } catch (e) { /* bozuk kayıt: yeni oyun */ }
    return null;
  }
  G.wipe = function () {
    try { localStorage.removeItem(SAVE_KEY); } catch (e) { /* yok say */ }
    if (nativePrefs) Prefs.remove({ key: SAVE_KEY }).catch(() => {});
    G.S = G.newState();
    UI.tab = "galeri";
    UI.sheet = { kind: "intro" };
    UI.render();
  };

  let last = 0, acc = 0, uiAcc = 0, saveAcc = 0;
  function frame(now) {
    const dt = last ? Math.min(0.25, (now - last) / 1000) : 0;
    last = now;
    const S = G.S;
    if (S && S.started) {
      if (UI.sheet && UI.sheet.kind === "auction" && S.auction && S.auction.live) G.auctionTick(dt);
      if (!S.paused && !UI.blocking()) {
        acc += dt * MIN_PER_SEC * S.speed;
        let n = Math.floor(acc);
        acc -= n;
        while (n-- > 0 && !S.paused) G.minute();
      }
    }
    uiAcc += dt;
    if (uiAcc >= 0.2) { uiAcc = 0; UI.tick(); }
    saveAcc += dt;
    if (saveAcc >= 5) { saveAcc = 0; G.save(); }
    requestAnimationFrame(frame);
  }

  function start(hotData) {
    UI.bind();
    if (G.Ads) G.Ads.init();
    const saved = (hotData && hotData.state) || loadSaved();
    if (saved) {
      G.S = G.migrate(saved);
      G.S.paused = true; // oyuncu dönünce kendisi başlatsın
      if (G.S.summary) UI.sheet = { kind: "summary" };
    } else {
      G.S = G.newState();
      UI.sheet = { kind: "intro" };
    }
    UI.render();
    if (saved && saved.lastSeen) UI.checkOffline(Date.now() - saved.lastSeen);
    // tur yarıda kaldıysa kaldığı yerden değil baştan sürer
    if (saved && G.S.started && G.S.tut && !G.S.tut.done && !G.S.tut.off && !UI.sheet) UI.tourStart();
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) { G.save(); if (G.S) G.S.paused = true; }
      else if (G.S && G.S.lastSeen) UI.checkOffline(Date.now() - G.S.lastSeen);
    });
    window.addEventListener("pagehide", G.save);
    requestAnimationFrame(frame);
  }

  const hot = window.claude && window.claude.hot;
  if (hot && hot.snapshot) hot.snapshot(() => ({ state: G.S }));
  // uygulamada önce kalıcı depodaki kayıt localStorage'a geri yüklenir
  async function boot(hotData) {
    if (nativePrefs) {
      try {
        let has = false;
        try { has = !!localStorage.getItem(SAVE_KEY); } catch (e) { /* yok say */ }
        if (!has) { const r = await Prefs.get({ key: SAVE_KEY }); if (r && r.value) localStorage.setItem(SAVE_KEY, r.value); }
      } catch (e) { /* kalıcı depo yoksa web kaydıyla devam */ }
    }
    start(hotData);
  }
  if (hot && hot.ready) hot.ready(boot); else boot((hot && hot.data) || {});
})(window.G = window.G || {});
