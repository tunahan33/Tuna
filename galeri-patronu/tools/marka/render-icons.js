// Uygulama ikonu seçeneklerini üretir: node render-icons.js (bu klasörde çalıştır) → icon-a/b/c.png, icon-preview.png
const { chromium } = require('playwright'); const { ICONS } = require('./art.js');
(async()=>{const b=await chromium.launch();const p=await b.newPage({viewport:{width:1024,height:1024}});
for (const k in ICONS){ await p.setContent(`<body style="margin:0"><svg width="1024" height="1024" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">${ICONS[k]}</svg>`); await p.screenshot({path:`icon-${k}.png`}); }
// önizleme: telefon ekranında boyutlar ve maskeler
const row = (k,bg)=>`<div style="display:flex;gap:28px;align-items:flex-end;padding:20px;background:${bg}">${[180,96,48].map(s=>`<img src="icon-${k}.png" style="width:${s}px;height:${s}px;border-radius:${s*.225}px">`).join('')}<img src="icon-${k}.png" style="width:96px;border-radius:50%"><span style="font:600 22px sans-serif;color:#888">${k.toUpperCase()}</span></div>`;
const html = `<body style="margin:0;display:grid;grid-template-columns:1fr 1fr">${Object.keys(ICONS).map(k=>row(k,'#f1f3f5')+row(k,'#101418')).join('')}</body>`;
require('fs').writeFileSync('preview.html',html);
await p.setViewportSize({width:1200,height:700}); await p.goto('file://'+process.cwd()+'/preview.html'); await p.screenshot({path:'icon-preview.png',fullPage:true});
await b.close();})();
