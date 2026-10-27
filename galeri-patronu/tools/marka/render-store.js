// Mağaza görsellerini üretir: node render-store.js (bu klasörde çalıştır) → splash.png, feature.png
const { chromium } = require('playwright'); const { MARK, BG } = require('./art.js');
const FONT = `<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Saira+Condensed:wght@700;800&family=Figtree:wght@600;700&display=block">`;
const IMG = require('path').join(__dirname, '../../www/img/araclar/');
const mark = (w) => `<svg width="${w}" height="${w*0.7027}" viewBox="-370 -260 740 520">${BG}${MARK}</svg>`;
const word = (px, sub, c) => `<div style="font-family:'Saira Condensed';font-weight:800;line-height:.86;letter-spacing:.01em;">
  <div style="font-size:${px*0.42}px;color:#9fd8cc;letter-spacing:.32em;margin-left:.1em">GALERİ</div>
  <div style="font-size:${px}px;color:#fff">PATRONU</div><div style="height:${px*0.07}px;width:${px*1.2}px;margin:${px*0.1}px ${c ? 'auto' : '0'} 0;background:#ffd23f;border-radius:99px"></div></div>${sub ? `<div style="font:600 ${px*0.2}px Figtree;color:#c9d6de;margin-top:${px*0.18}px">${sub}</div>` : ''}`;
const pages = {
  splash: [2732, 2732, `<body style="margin:0;width:2732px;height:2732px;background:#0f1419;display:grid;place-items:center">
    <div style="display:grid;justify-items:center;gap:40px">${mark(860)}<div style="text-align:center">${word(230, '', 1)}</div></div></body>`],
  feature: [1024, 500, `<body style="margin:0;width:1024px;height:500px;overflow:hidden;position:relative;background:radial-gradient(120% 140% at 75% 30%,#16927b 0%,#0d6b5a 45%,#063b31 100%)">
    <div style="position:absolute;left:0;right:0;bottom:0;height:120px;background:linear-gradient(#0a2f28,#06231e)"></div>
    <div style="position:absolute;left:0;right:0;bottom:118px;height:3px;background:#ffd23f;opacity:.85"></div>
    <div style="position:absolute;left:56px;top:70px;width:400px">${word(118, 'Al, hazırla, kârla sat.<br>Sanayideki arsadan otomotiv imparatorluğuna.')}</div>
    <img src="file://${IMG}nordik-xr90-bordo.webp" style="position:absolute;width:430px;left:415px;bottom:96px;filter:brightness(.85)">
    <img src="file://${IMG}ferro-veloce-kirmizi.webp" style="position:absolute;width:470px;left:540px;bottom:70px">
    <img src="file://${IMG}stahl-s8-lacivert.webp" style="position:absolute;width:440px;left:420px;bottom:20px">
    <div style="position:absolute;left:730px;top:150px;transform:rotate(-8deg);background:#ffd23f;color:#1d1a10;font:800 34px 'Saira Condensed';padding:6px 18px 6px 26px;white-space:nowrap;border-radius:6px;box-shadow:0 6px 14px rgba(0,0,0,.3);clip-path:polygon(14px 0,100% 0,100% 100%,14px 100%,0 50%)">1,25 MİLYON ₺</div>
  </body>`]
};
(async()=>{const b=await chromium.launch();
for (const [k,[w,h,html]] of Object.entries(pages)) { const p=await b.newPage({viewport:{width:w,height:h}}); require('fs').writeFileSync(`${k}.html`,`<!doctype html><html lang="tr"><meta charset="utf-8">`+FONT+html); await p.goto('file://'+process.cwd()+`/${k}.html`); await p.evaluate(()=>document.fonts.ready); await p.waitForTimeout(1500); await p.screenshot({path:`${k}.png`}); await p.close(); }
await b.close();})();
