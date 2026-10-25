/* Galeri Patronu — araç fotoğrafları
   Her modelin tek bir fotoğrafı var (img/araclar/{model}-{renk}.webp, 1200×480, şeffaf arka plan,
   taban çizgisi y=460). Fotoğrafı olan model her zaman o renkte üretilir; fotoğrafı olmayan
   model SVG çizimle gösterilir. Tek dosya derlemesinde G.CAR_PHOTO_DATA resimleri gömülü taşır. */
(function () {
  "use strict";
  const G = window.G;
  G.COLOR_SLUG = { "Beyaz": "beyaz", "Siyah": "siyah", "Gri": "gri", "Gümüş": "gumus", "Lacivert": "lacivert", "Mavi": "mavi", "Kırmızı": "kirmizi", "Bordo": "bordo", "Yeşil": "yesil", "Turuncu": "turuncu", "Sarı": "sari", "Kahverengi": "kahverengi" };
  // model → fotoğraftaki renk
  G.PHOTO_COLOR = {
    "anka-ekartal": "Gri",
    "anka-kartal": "Kırmızı",
    "anka-prototip": "Bordo",
    "anka-serce": "Beyaz",
    "anka-yukcu": "Lacivert",
    "ferro-dobo": "Siyah",
    "ferro-egeo": "Beyaz",
    "ferro-leggenda": "Beyaz",
    "ferro-punta": "Beyaz",
    "ferro-spider": "Siyah",
    "ferro-veloce": "Kırmızı",
    "hanbit-ari": "Kahverengi",
    "hanbit-elan": "Gri",
    "hanbit-ion": "Kırmızı",
    "hanbit-toros": "Lacivert",
    "kaizen-atlas": "Bordo",
    "kaizen-luxa": "Gri",
    "kaizen-mio": "Beyaz",
    "kaizen-ryu": "Kırmızı",
    "kaizen-sora": "Gümüş",
    "kaizen-taiga": "Kahverengi",
    "mirage-apex": "Siyah",
    "mirage-comet": "Kırmızı",
    "mirage-escala": "Gümüş",
    "mirage-stallion": "Siyah",
    "mirage-voltis": "Kahverengi",
    "nordik-aurora": "Sarı",
    "nordik-s70": "Beyaz",
    "nordik-xr40": "Gri",
    "nordik-xr90": "Bordo",
    "rivo-dunar": "Siyah",
    "rivo-kanto": "Beyaz",
    "rivo-megara": "Kırmızı",
    "stahl-phantom": "Turuncu",
    "stahl-rcoupe": "Gümüş",
    "stahl-s3": "Beyaz",
    "stahl-s5": "Bordo",
    "stahl-s8": "Lacivert",
    "volta-arcus": "Gümüş",
    "volta-bocek": "Gri",
    "volta-epira": "Bordo",
    "volta-kargo": "Gri",
    "volta-pira": "Gümüş",
    "volta-speedster": "Lacivert",
    "volta-terra": "Kırmızı",
  };
  G.photoKey = m => G.PHOTO_COLOR[m] ? `${m}-${G.COLOR_SLUG[G.PHOTO_COLOR[m]]}` : null;
  // fotoğrafı olan modelin rengini fotoğrafa sabitler
  G.photoColor = (m, fallback) => G.PHOTO_COLOR[m] || fallback;
  for (const x of G.LUXURY) if (x.model && G.PHOTO_COLOR[x.model]) x.color = G.PHOTO_COLOR[x.model];
})();
