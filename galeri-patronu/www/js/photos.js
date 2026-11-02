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
  // Müşteri ve personel portreleri: img/kisiler/{anahtar}.webp (256×256). Anahtar: musteri-{tip}-{k|e}, personel-{rol}-{k|e}.
  // Fotoğrafı gelen portrenin anahtarı bu listeye eklenir; olmayanlar için baş harfli renkli avatar çizilir.
  G.PEOPLE_PHOTOS = [];
  G.portraitKey = (who, kind, g) => `${who}-${kind}-${g || "e"}`;
  // Gerçekçi galeri sahnesi: img/sahne/galeri-{adres}-{1..4}.webp (16:9) ve {atolye|yikama|cardak|otopark}-{1..4}.webp (4:3).
  // Aşama seviyeye göre: 1 (0–4), 2 (5–9), 3 (10–14), 4 (15–20). Fotoğrafı gelen anahtar listeye eklenir;
  // o adresin galeri fotoğrafı yoksa sahne çizimle gösterilir.
  G.SCENE_PHOTOS = [
    "galeri-sanayi-1", "galeri-sanayi-2", "galeri-sanayi-3", "galeri-sanayi-4",
    "galeri-cadde-1", "galeri-cadde-2", "galeri-cadde-3", "galeri-cadde-4",
    "galeri-plaza-1", "galeri-plaza-2", "galeri-plaza-3", "galeri-plaza-4",
    "galeri-merkez-1", "galeri-merkez-2", "galeri-merkez-3", "galeri-merkez-4",
    "atolye-1", "atolye-2", "atolye-3", "atolye-4", "yikama-1", "yikama-2", "yikama-3", "yikama-4",
    "cardak-1", "cardak-2", "cardak-3", "cardak-4", "otopark-1", "otopark-2", "otopark-3", "otopark-4"
  ];
  // Lüks eşya fotoğrafları: img/luks/{eşya id}.webp (768×410, 15:8). Listede olmayan eşya çizimle gösterilir.
  G.LUX_PHOTOS = ["e01", "ev1", "e03", "ev2", "e05", "e06", "ev3", "e08", "ev4", "e10", "ev5", "e12", "e13"];
  G.stageOf = n => (n >= 15 ? 4 : n >= 10 ? 3 : n >= 5 ? 2 : 1);
  G.photoKey = m => G.PHOTO_COLOR[m] ? `${m}-${G.COLOR_SLUG[G.PHOTO_COLOR[m]]}` : null;
  // fotoğrafı olan modelin rengini fotoğrafa sabitler
  G.photoColor = (m, fallback) => G.PHOTO_COLOR[m] || fallback;
  for (const x of G.LUXURY) if (x.model && G.PHOTO_COLOR[x.model]) x.color = G.PHOTO_COLOR[x.model];
})();
