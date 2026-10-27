// Ortak marka parçaları (SVG)
const CAR = (fill, win) => `
<path d="M8 62 C8 53 12 49 22 47 L54 42 C64 31 80 23 102 23 C122 23 136 30 150 41 L178 46 C190 48 195 54 195 62 L195 66 C195 69 193 71 190 71 L171 71 A17 17 0 0 0 137 71 L65 71 A17 17 0 0 0 31 71 L13 71 C10 71 8 69 8 66 Z" fill="${fill}"/>
<path d="M62 42.5 C70 33 82 28 99 27.5 L100.5 42.5 Z M106 27.5 C121 28 132 33 142 42.5 L106 42.5 Z" fill="${win}"/>
<circle cx="48" cy="71" r="13" fill="${fill}"/><circle cx="48" cy="71" r="6" fill="${win}"/>
<circle cx="154" cy="71" r="13" fill="${fill}"/><circle cx="154" cy="71" r="6" fill="${win}"/>`;
const CROWN = fill => `<path d="M0 46 L-6 6 L20 26 L40 -4 L60 26 L86 6 L80 46 Z" fill="${fill}" stroke="${fill}" stroke-width="6" stroke-linejoin="round"/>
<circle cx="-6" cy="4" r="7" fill="${fill}"/><circle cx="40" cy="-8" r="8" fill="${fill}"/><circle cx="86" cy="4" r="7" fill="${fill}"/><rect x="0" y="52" width="80" height="10" rx="5" fill="${fill}"/>`;
const TAG = (fill, hole) => `<path d="M150 0 H560 Q600 0 600 40 V340 Q600 380 560 380 H150 Q132 380 118 366 L12 214 Q0 190 12 166 L118 14 Q132 0 150 0 Z" fill="${fill}"/><circle cx="104" cy="190" r="30" fill="${hole}"/>`;
const BG = `<defs><radialGradient id="bg" cx="40%" cy="30%" r="85%"><stop offset="0" stop-color="#16927b"/><stop offset=".55" stop-color="#0d6b5a"/><stop offset="1" stop-color="#063b31"/></radialGradient>
<radialGradient id="nv" cx="40%" cy="30%" r="85%"><stop offset="0" stop-color="#26364a"/><stop offset="1" stop-color="#0b1118"/></radialGradient>
<linearGradient id="gold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffe27a"/><stop offset="1" stop-color="#f2b81f"/></linearGradient></defs>`;
const ICONS = {
  // A: sarı fiyat etiketi + araba + ip (oyundaki fiyat etiketiyle aynı dil)
  a: `${BG}<rect width="1024" height="1024" fill="url(#bg)"/>
    <g transform="translate(520 540) rotate(-14) scale(1.18) translate(-310 -190)">
    <g transform="translate(16 24)" opacity=".28">${TAG("#021a15", "none")}</g>${TAG("url(#gold)", "#0d6b5a")}
    <circle cx="104" cy="190" r="30" fill="none" stroke="#b8860b" stroke-width="5" opacity=".5"/>
    <path d="M104 190 C70 170 50 120 20 80" stroke="#e7f5f1" stroke-width="12" fill="none" stroke-linecap="round"/>
    <g transform="translate(154 76) scale(2.15)">${CAR("#17202b", "url(#gold)")}</g></g>`,
  // B: taç + araba (patron)
  b: `${BG}<rect width="1024" height="1024" fill="url(#bg)"/>
    <g transform="translate(376 250) scale(3.4)">${CROWN("url(#gold)")}</g>
    <g transform="translate(130 520) scale(3.8)">${CAR("#ffffff", "#0d6b5a")}</g>
    <rect x="150" y="806" width="724" height="14" rx="7" fill="#063b31" opacity=".6"/>`,
  // C: lacivert zemin, sarı etiket üstünde taç
  c: `${BG}<rect width="1024" height="1024" fill="url(#nv)"/>
    <g transform="translate(512 512) rotate(-14) translate(-300 -190)">${TAG("url(#gold)", "#152130")}
    <g transform="translate(250 112) scale(2.4)">${CROWN("#17202b")}</g></g>`
};
// Şeffaf, döndürülmüş etiket sembolü (açılış ve bant için)
const MARK = `<g transform="rotate(-14) translate(-310 -190)"><path d="M104 190 C70 170 50 120 20 80" stroke="#e7f5f1" stroke-width="12" fill="none" stroke-linecap="round" opacity="0"/><g transform="translate(16 24)" opacity=".3">${TAG("#000", "none")}</g>${TAG("url(#gold)", "#0f1419")}<g transform="translate(154 76) scale(2.15)">${CAR("#17202b", "url(#gold)")}</g></g>`;
module.exports = { MARK, CAR, CROWN, TAG, BG, ICONS };
