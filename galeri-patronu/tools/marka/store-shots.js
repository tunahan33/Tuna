// Mağaza ekran görüntüleri, 1. adım: oyunu orta seviyede dolu bir galeriyle kurup 430×932 @3x çeker → shots/*.png (bu klasörde çalıştır: mkdir -p shots && node store-shots.js)
const { chromium } = require('playwright');
(async()=>{const b=await chromium.launch();
const ctx=await b.newContext({viewport:{width:430,height:932},deviceScaleFactor:3,isMobile:true,hasTouch:true});
const p=await ctx.newPage(); const errs=[]; p.on('pageerror',e=>errs.push(e.stack));
await p.goto('file://' + require('path').join(__dirname, '../../www/index.html') + ''); await p.waitForTimeout(900);
await p.fill('#nameIn','Yıldız Otomotiv'); await p.click('[data-a="begin"]'); await p.evaluate(()=>{G.S.tut.off=true;}); await p.waitForTimeout(2600);
await p.evaluate(()=>{
  const S=G.S; S.paused=true; S.level=14; S.xp=Math.round(G.xpNeed(14)*0.62); S.money=48_750_000; S.rep=78; S.day=63;
  G.moveTo ? null : null; S.loc=3; S.fac={galeri:13,yikama:12,cardak:10,otopark:12};
  S.upg={bays:3,paint:1,pdr:1,expert:1,cafe:1,security:1};
  S.staff=[{id:G.nid(),role:'eksper',skill:4,name:'Kemal Usta',salary:2000},{id:G.nid(),role:'usta',skill:4,name:'Ali Usta',salary:2000,energy:90},{id:G.nid(),role:'usta',skill:3,name:'Veli Usta',salary:1800,energy:70},{id:G.nid(),role:'yikama',skill:3,name:'Can',salary:800}];
  S.cars=[]; const ms=['ferro-veloce','stahl-s8','nordik-xr90','mirage-stallion','kaizen-taiga','volta-terra','hanbit-ion','stahl-s5','mirage-escala','kaizen-ryu','rivo-megara','nordik-s70','anka-ekartal','ferro-spider','volta-bocek','kaizen-luxa'];
  for (const [i,m] of ms.entries()) { const c=G.makeCar(G.M[m],G.SELLERS.galeriden,{noIssues:true}); c.inspected=true; c.clean=96; c.buy=Math.round(G.value(c,false)*0.82); c.spent=0; c.price=Math.round(G.value(c,false)*1.05/1000)*1000; c.show=i<4; S.cars.push(c); }
  S.lux=['ar20','y3','uc3','s05'].filter(id=>G.LX[id]); S.luxPaid={}; for (const id of S.lux) S.luxPaid[id]=G.LX[id].price;
  G.refreshListings();
  S.paused=false;
});
// müşteriler gelsin
for (let k=0;k<80;k++){ await p.evaluate(()=>{ for(let i=0;i<5;i++) G.minute(); }); const n=await p.evaluate(()=>G.S.custs.filter(c=>c.state==='offer').length); if(n>=3) break; }
await p.evaluate(()=>{G.S.paused=true; document.getElementById('toasts').innerHTML=''; G.UI.sheet=null; G.UI.tab='galeri'; G.UI.render();}); await p.waitForTimeout(700);
await p.evaluate(()=>{document.getElementById('toasts').innerHTML='';}); await p.screenshot({path:'shots/1-galeri.png'});
await p.evaluate(()=>{G.UI.tab='piyasa';G.UI.sub.piyasa='ilan';G.UI.render();}); await p.waitForTimeout(500); await p.evaluate(()=>{document.getElementById('toasts').innerHTML='';}); await p.screenshot({path:'shots/2-ilanlar.png'});
await p.evaluate(()=>{const l=G.S.listings.filter(l=>!l.done).sort((a,b)=>b.ask-a.ask)[1]; G.UI.open('listing',l.id); }); await p.waitForTimeout(300);
await p.evaluate(()=>{const l=G.S.listings.find(x=>x.id===G.UI.sheet.id); G.offerListing(l, Math.round(l.last*0.72/1000)*1000); G.UI.renderSheet(true);}); await p.waitForTimeout(500); await p.evaluate(()=>{document.getElementById('toasts').innerHTML='';}); await p.screenshot({path:'shots/3-pazarlik.png'});
await p.evaluate(()=>{G.UI.sheet=null;G.UI.tab='musteri';G.UI.render();}); await p.waitForTimeout(500); await p.evaluate(()=>{document.getElementById('toasts').innerHTML='';}); await p.screenshot({path:'shots/4-musteri.png'});
await p.evaluate(()=>{const S=G.S; for(const c of S.cars.slice(4,7)){ const o=G.jobOptions(c).find(o=>o.type==='paint'||o.type==='service'); if(o) G.startJob(c,o);} G.UI.tab='atolye';G.UI.render();}); await p.waitForTimeout(500); await p.evaluate(()=>{document.getElementById('toasts').innerHTML='';}); await p.screenshot({path:'shots/5-atolye.png'});
await p.evaluate(()=>{G.UI.tab='patron';G.UI.sub.patron='lux';G.UI.luxCat='araba';G.UI.render();}); await p.waitForTimeout(800); await p.evaluate(()=>{const cards=[...document.querySelectorAll('.lux-card')]; const t=cards[11]; window.scrollTo(0, t.getBoundingClientRect().top + window.scrollY - 330);}); await p.waitForTimeout(300); await p.evaluate(()=>{document.getElementById('toasts').innerHTML='';}); await p.screenshot({path:'shots/6-luks.png'});
console.log(await p.evaluate(()=>({custs:G.S.custs.map(c=>c.state).join(','), jobs:G.S.jobs.length, lux:G.S.lux})), errs);
await b.close();})();
