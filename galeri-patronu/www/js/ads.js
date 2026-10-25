/* Galeri Patronu — reklam katmanı
 *
 * Uygulama içinde (Capacitor + @capacitor-community/admob) gerçek AdMob reklamı gösterir.
 * Tarayıcıda gerçek reklam yoktur; akışı denemek için 5 saniyelik örnek reklam ekranı açılır.
 *
 * Kurallar (oyuncuyu reklama boğmamak için):
 *  - Ödüllü reklam yalnızca oyuncu "Reklam izle" düğmesine basınca açılır; her ödülün günlük sınırı vardır (engine.js: G.AD_CAPS).
 *  - Ara reklam yalnızca gün geçişinde, 4. günden sonra, en az 3 günde bir ve o gün hiç ödüllü reklam izlenmediyse çıkar.
 *  - Pazarlık, ihale ve oyun sırasında hiçbir zaman kendiliğinden reklam açılmaz. Banner reklam yoktur.
 */
(function (G) {
  "use strict";

  // Yayından önce AdMob panelindeki kendi reklam birimi kimliklerinle değiştir. Şu an Google'ın test kimlikleri kullanılıyor.
  const AD_UNITS = {
    android: { rewarded: "ca-app-pub-3940256099942544/5224354917", interstitial: "ca-app-pub-3940256099942544/1033173712" },
    ios:     { rewarded: "ca-app-pub-3940256099942544/1712485313", interstitial: "ca-app-pub-3940256099942544/4411468910" }
  };
  const TESTING = true;

  const Cap = window.Capacitor;
  const AdMob = Cap && Cap.Plugins && Cap.Plugins.AdMob;
  const platform = Cap && Cap.getPlatform ? Cap.getPlatform() : "web";
  const native = !!AdMob && (platform === "android" || platform === "ios");

  const Ads = G.Ads = { native, showing: false, ready: false };

  Ads.init = async function () {
    if (!native) { Ads.ready = true; return; }
    try {
      if (platform === "ios" && AdMob.trackingAuthorizationStatus) {
        const st = await AdMob.trackingAuthorizationStatus();
        if (st && st.status === "notDetermined") await AdMob.requestTrackingAuthorization();
      }
      await AdMob.initialize({ initializeForTesting: TESTING });
      // Avrupa ve KVKK kapsamındaki kullanıcılar için Google'ın onay formu (UMP)
      if (AdMob.requestConsentInfo) {
        const info = await AdMob.requestConsentInfo();
        if (info && info.isConsentFormAvailable && info.status === "REQUIRED") await AdMob.showConsentForm();
      }
      Ads.ready = true;
    } catch (e) {
      Ads.ready = false;
    }
  };

  // Ödüllü reklam: izlenip bitirildiyse true döner
  Ads.rewarded = async function () {
    if (Ads.showing) return false;
    Ads.showing = true;
    try {
      if (!native) return await simulated("rewarded");
      const id = AD_UNITS[platform].rewarded;
      await AdMob.prepareRewardVideoAd({ adId: id, isTesting: TESTING });
      let got = false;
      const h = await AdMob.addListener("onRewardedVideoAdReward", () => { got = true; });
      try {
        const res = await AdMob.showRewardVideoAd();
        if (res && res.amount !== undefined) got = true;
      } finally { if (h && h.remove) h.remove(); }
      return got;
    } catch (e) {
      return null; // reklam yüklenemedi
    } finally {
      Ads.showing = false;
    }
  };

  Ads.interstitial = async function () {
    if (Ads.showing) return false;
    Ads.showing = true;
    try {
      if (!native) return await simulated("interstitial");
      await AdMob.prepareInterstitial({ adId: AD_UNITS[platform].interstitial, isTesting: TESTING });
      await AdMob.showInterstitial();
      return true;
    } catch (e) {
      return false;
    } finally {
      Ads.showing = false;
    }
  };

  // Tarayıcı için örnek reklam ekranı
  function simulated(kind) {
    return new Promise(resolve => {
      const layer = document.getElementById("adLayer");
      const secs = kind === "rewarded" ? 5 : 3;
      let left = secs;
      layer.innerHTML = `<div class="ad-box" role="dialog" aria-modal="true" aria-label="Reklam">
        <div class="ad-tag">${kind === "rewarded" ? "Ödüllü reklam" : "Reklam"} · örnek</div>
        <div class="ad-art"><b>Burada reklam gösterilir</b><span>Uygulama mağazadan indirildiğinde bu alanda Google AdMob reklamı oynar.</span></div>
        <div class="prog"><i id="adProg" style="width:0%"></i></div>
        <div class="btns"><button class="btn" type="button" id="adClose">${kind === "rewarded" ? "Vazgeç" : "Geç"}</button></div>
        <p class="muted small" id="adNote">${kind === "rewarded" ? `Ödül için ${secs} saniye izle.` : `${secs} saniye sonra geçebilirsin.`}</p></div>`;
      layer.hidden = false;
      const close = layer.querySelector("#adClose");
      if (kind !== "rewarded") close.disabled = true;
      const t = setInterval(() => {
        left -= 0.1;
        const p = Math.min(100, (1 - left / secs) * 100);
        const bar = layer.querySelector("#adProg");
        if (bar) bar.style.width = p + "%";
        if (left <= 0) {
          clearInterval(t);
          if (kind === "rewarded") { done(true); return; }
          close.disabled = false;
          layer.querySelector("#adNote").textContent = "Geçebilirsin.";
        }
      }, 100);
      function done(ok) { clearInterval(t); layer.hidden = true; layer.innerHTML = ""; resolve(ok); }
      close.addEventListener("click", () => done(kind === "rewarded" ? false : true));
    });
  }
})(window.G = window.G || {});
