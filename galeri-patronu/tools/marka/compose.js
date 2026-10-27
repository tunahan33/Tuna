// Mağaza ekran görüntüleri, 2. adım: shots/*.png üstüne başlık ekleyip iPhone 6.7" (1290×2796) ve Android (1080×1920) boyutlarında store/ekran-goruntuleri altına yazar
const { chromium } = require('playwright');
const fs = require('fs'), path = require('path');
const OUT = path.join(__dirname, '../../store/ekran-goruntuleri');
const SH = [
  ['1-galeri', 'Kendi oto galerini kur', 'Sanayi arsasından otomotiv merkezine'],
  ['2-ilanlar', 'Her sabah yeni ilanlar', 'Fırsatı yakala, pahalıdan uzak dur'],
  ['3-pazarlik', 'Satıcıyla pazarlık et', 'Ekspertizle gizli kusuru yakala'],
  ['4-musteri', 'Müşteriyle anlaş', '13 farklı müşteri, her biri farklı pazarlık eder'],
  ['5-atolye', 'Hazırla, kârla sat', 'Bakım, boya, göçük, restorasyon'],
  ['6-luks', 'Patron gibi yaşa', 'Süper arabalar, yatlar, uçaklar ve daha fazlası']
];
const SIZES = { 'iphone-6.7': [1290, 2796], 'android': [1080, 1920] };
(async () => {
  const b = await chromium.launch();
  for (const [dev, [W, H]] of Object.entries(SIZES)) {
    fs.mkdirSync(path.join(OUT, dev), { recursive: true });
    const p = await b.newPage({ viewport: { width: W, height: H } });
    const cap = Math.round(H * (dev === 'android' ? 0.2 : 0.185));
    const ph = H - cap - Math.round(H * 0.03), pw = Math.round(ph * 1290 / 2796);
    for (const [i, [f, t, s]] of SH.entries()) {
      const html = `<!doctype html><html lang="tr"><meta charset="utf-8"><link rel="stylesheet" href="file://${path.join(__dirname, '../../www/fonts/fonts.css')}">
      <body style="margin:0;width:${W}px;height:${H}px;overflow:hidden;background:radial-gradient(120% 70% at 30% 0%,#16c79a 0%,#00a676 38%,#00684f 100%);position:relative">
      <div style="position:absolute;left:0;right:0;top:${Math.round(cap*0.2)}px;text-align:center;padding:0 ${Math.round(W*0.06)}px">
        <div style="font:800 ${Math.round(W*0.085)}px/1 'Saira Condensed';color:#fff;text-transform:uppercase;letter-spacing:.01em;text-shadow:0 4px 18px rgba(0,0,0,.2)">${t}</div>
        <div style="font:600 ${Math.round(W*0.04)}px/1.25 Figtree;color:#ffe066;margin-top:${Math.round(W*0.022)}px">${s}</div></div>
      <img src="file://${process.cwd()}/shots/${f}.png" style="position:absolute;left:${(W-pw)/2}px;top:${cap}px;width:${pw}px;height:${ph}px;border-radius:${Math.round(pw*0.06)}px;box-shadow:0 30px 80px rgba(0,30,20,.45);border:${Math.round(W*0.008)}px solid #0b1320">
      </body></html>`;
      fs.writeFileSync('compose.html', html);
      await p.goto('file://' + process.cwd() + '/compose.html'); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(300);
      await p.screenshot({ path: path.join(OUT, dev, `${i + 1}-${f.split('-')[1]}.png`) });
    }
    await p.close();
  }
  await b.close();
})();
