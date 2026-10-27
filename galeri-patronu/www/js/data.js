/* Galeri Patronu — oyun verileri
 * Bütün marka ve model adları hayalidir; mağazada yayınlarken marka hakkı sorunu çıkmaması için gerçek isim kullanılmaz. */
(function (G) {
  "use strict";

  G.YEAR = 2026;

  G.SEGMENTS = {
    ekonomi:  { name: "Ekonomi",    lvl: 1 },
    aile:     { name: "Aile",       lvl: 1 },
    ticari:   { name: "Ticari",     lvl: 2 },
    suv:      { name: "SUV",        lvl: 3 },
    elektrik: { name: "Elektrikli", lvl: 4 },
    premium:  { name: "Premium",    lvl: 5 },
    spor:     { name: "Spor",       lvl: 7 },
    klasik:   { name: "Klasik",     lvl: 8 },
    luks:     { name: "Lüks",       lvl: 10 },
    ultra:    { name: "Ultra lüks", lvl: 6 }  // normal ilanlarda çıkmaz; nadir fırsat olarak gelir
  };

  G.BODIES = { hatch: "Hatchback", sedan: "Sedan", suv: "SUV", coupe: "Coupe", van: "Hafif ticari", pickup: "Pick-up", wagon: "Station wagon" };
  G.FUELS = {
    benzin:   { name: "Benzin",     f: 1 },
    dizel:    { name: "Dizel",      f: 1.02 },
    hibrit:   { name: "Hibrit",     f: 1.07 },
    lpg:      { name: "Benzin/LPG", f: 0.93 },
    elektrik: { name: "Elektrik",   f: 1 }
  };
  G.GEARS = { manuel: { name: "Manuel", f: 0.96 }, otomatik: { name: "Otomatik", f: 1.05 } };

  // w: piyasada görülme sıklığı, f: fiyata etkisi, sport: spor araçta ek etkisi
  G.COLORS = [
    { name: "Beyaz",      hex: "#f2f3f1", w: 9, f: 1.02 },
    { name: "Siyah",      hex: "#1d2024", w: 6, f: 1.01 },
    { name: "Gri",        hex: "#767d86", w: 7, f: 1.01 },
    { name: "Gümüş",      hex: "#b8bdc3", w: 5, f: 1 },
    { name: "Lacivert",   hex: "#1f2f5a", w: 3, f: 0.99 },
    { name: "Mavi",       hex: "#2f6fd0", w: 2, f: 0.98 },
    { name: "Kırmızı",    hex: "#c8282d", w: 3, f: 0.97, sport: 1.06 },
    { name: "Bordo",      hex: "#6e1f2b", w: 2, f: 0.96 },
    { name: "Yeşil",      hex: "#2e6e4a", w: 1, f: 0.93 },
    { name: "Turuncu",    hex: "#e2742c", w: 1, f: 0.92, sport: 1.05 },
    { name: "Sarı",       hex: "#e5bd2e", w: 1, f: 0.9,  sport: 1.05 },
    { name: "Kahverengi", hex: "#5f4430", w: 1, f: 0.92 }
  ];

  // [id, marka, model, segment, kasa, yeni fiyat (klasikte kusursuz örnek), yıl aralığı, yakıtlar, vitesler, yaygınlık]
  G.MODELS = [
    ["anka-serce",     "Anka",   "Serçe",       "ekonomi",  "hatch",  950e3,  [2008, 2026], ["benzin", "lpg"],             ["manuel"],             3],
    ["ferro-punta",    "Ferro",  "Punta",       "ekonomi",  "hatch",  1.1e6,  [2010, 2026], ["benzin", "dizel", "lpg"],    ["manuel", "otomatik"], 4],
    ["rivo-kanto",     "Rivo",   "Kanto",       "ekonomi",  "hatch",  1.25e6, [2012, 2026], ["benzin", "dizel"],           ["manuel", "otomatik"], 4],
    ["kaizen-mio",     "Kaizen", "Mio",         "ekonomi",  "hatch",  1.3e6,  [2010, 2026], ["benzin", "hibrit"],          ["manuel", "otomatik"], 3],
    ["hanbit-ari",     "Hanbit", "Arı",         "ekonomi",  "sedan",  1.2e6,  [2011, 2026], ["benzin", "dizel"],           ["manuel", "otomatik"], 3],
    ["volta-pira",     "Volta",  "Pira",        "ekonomi",  "hatch",  1.45e6, [2010, 2026], ["benzin", "dizel"],           ["manuel", "otomatik"], 4],
    ["ferro-egeo",     "Ferro",  "Egeo",        "aile",     "sedan",  1.35e6, [2015, 2026], ["benzin", "dizel", "lpg"],    ["manuel", "otomatik"], 5],
    ["rivo-megara",    "Rivo",   "Megara",      "aile",     "sedan",  1.7e6,  [2010, 2026], ["benzin", "dizel"],           ["manuel", "otomatik"], 5],
    ["kaizen-sora",    "Kaizen", "Sora",        "aile",     "sedan",  1.9e6,  [2010, 2026], ["benzin", "hibrit"],          ["manuel", "otomatik"], 4],
    ["hanbit-elan",    "Hanbit", "Elan",        "aile",     "sedan",  1.65e6, [2010, 2026], ["benzin", "dizel", "lpg"],    ["manuel", "otomatik"], 3],
    ["volta-arcus",    "Volta",  "Arcus",       "aile",     "sedan",  2.3e6,  [2010, 2026], ["benzin", "dizel"],           ["otomatik"],           4],
    ["nordik-s70",     "Nordik", "S70 Kombi",   "aile",     "wagon",  2.5e6,  [2010, 2026], ["dizel", "hibrit"],           ["otomatik"],           2],
    ["ferro-dobo",     "Ferro",  "Dobo",        "ticari",   "van",    1.15e6, [2008, 2026], ["dizel", "benzin"],           ["manuel"],             5],
    ["anka-yukcu",     "Anka",   "Yükçü",       "ticari",   "van",    1.0e6,  [2006, 2026], ["dizel"],                     ["manuel"],             3],
    ["volta-kargo",    "Volta",  "Kargo",       "ticari",   "van",    1.9e6,  [2010, 2026], ["dizel"],                     ["manuel", "otomatik"], 3],
    ["kaizen-atlas",   "Kaizen", "Atlas",       "ticari",   "pickup", 2.4e6,  [2010, 2026], ["dizel"],                     ["manuel", "otomatik"], 3],
    ["rivo-dunar",     "Rivo",   "Dunar",       "suv",      "suv",    1.85e6, [2012, 2026], ["benzin", "dizel", "lpg"],    ["manuel", "otomatik"], 4],
    ["hanbit-toros",   "Hanbit", "Toros",       "suv",      "suv",    2.2e6,  [2012, 2026], ["benzin", "dizel", "hibrit"], ["otomatik"],           4],
    ["kaizen-taiga",   "Kaizen", "Taiga",       "suv",      "suv",    2.6e6,  [2012, 2026], ["benzin", "hibrit"],          ["otomatik"],           4],
    ["volta-terra",    "Volta",  "Terra",       "suv",      "suv",    2.9e6,  [2012, 2026], ["benzin", "dizel"],           ["otomatik"],           3],
    ["anka-ekartal",   "Anka",   "e-Kartal",    "elektrik", "suv",    2.1e6,  [2023, 2026], ["elektrik"],                  ["otomatik"],           4],
    ["volta-epira",    "Volta",  "e.Pira",      "elektrik", "hatch",  1.9e6,  [2020, 2026], ["elektrik"],                  ["otomatik"],           2],
    ["hanbit-ion",     "Hanbit", "Ion 5",       "elektrik", "suv",    2.8e6,  [2021, 2026], ["elektrik"],                  ["otomatik"],           3],
    ["mirage-voltis",  "Mirage", "Voltis",      "elektrik", "sedan",  3.5e6,  [2018, 2026], ["elektrik"],                  ["otomatik"],           3],
    ["stahl-s3",       "Stahl",  "S3",          "premium",  "sedan",  3.2e6,  [2010, 2026], ["benzin", "dizel"],           ["otomatik"],           4],
    ["stahl-s5",       "Stahl",  "S5",          "premium",  "sedan",  4.5e6,  [2010, 2026], ["benzin", "dizel", "hibrit"], ["otomatik"],           3],
    ["nordik-xr40",    "Nordik", "XR40",        "premium",  "suv",    3.6e6,  [2015, 2026], ["benzin", "hibrit"],          ["otomatik"],           3],
    ["kaizen-luxa",    "Kaizen", "Luxa ES",     "premium",  "sedan",  3.9e6,  [2012, 2026], ["hibrit"],                    ["otomatik"],           2],
    ["kaizen-ryu",     "Kaizen", "Ryu",         "spor",     "coupe",  3.4e6,  [2012, 2026], ["benzin"],                    ["manuel", "otomatik"], 3],
    ["mirage-stallion","Mirage", "Stallion GT", "spor",     "coupe",  4.9e6,  [2010, 2026], ["benzin"],                    ["manuel", "otomatik"], 3],
    ["ferro-veloce",   "Ferro",  "Veloce",      "spor",     "coupe",  7.2e6,  [2010, 2026], ["benzin"],                    ["otomatik"],           2],
    ["stahl-rcoupe",   "Stahl",  "R Coupe",     "spor",     "coupe",  8.5e6,  [2012, 2026], ["benzin"],                    ["otomatik"],           1],
    ["anka-kartal",    "Anka",   "Kartal SLX",  "klasik",   "sedan",  650e3,  [1980, 1994], ["benzin", "lpg"],             ["manuel"],             3],
    ["volta-bocek",    "Volta",  "Böcek",       "klasik",   "hatch",  950e3,  [1965, 1985], ["benzin"],                    ["manuel"],             2],
    ["ferro-spider",   "Ferro",  "Spider",      "klasik",   "coupe",  2.4e6,  [1970, 1990], ["benzin"],                    ["manuel"],             1],
    ["mirage-comet",   "Mirage", "Comet '68",   "klasik",   "coupe",  3.2e6,  [1966, 1972], ["benzin"],                    ["manuel", "otomatik"], 1],
    ["stahl-s8",       "Stahl",  "S8",          "luks",     "sedan",  9.8e6,  [2012, 2026], ["benzin", "hibrit"],          ["otomatik"],           2],
    ["nordik-xr90",    "Nordik", "XR90",        "luks",     "suv",    7.8e6,  [2015, 2026], ["dizel", "hibrit"],           ["otomatik"],           2],
    ["mirage-escala",  "Mirage", "Escala",      "luks",     "suv",    8.5e6,  [2015, 2026], ["benzin"],                    ["otomatik"],           1],
    // Ultra lüks: koleksiyon ve hiper otomobiller. Değer kaybetmez, nadiren ilana düşer.
    ["ferro-leggenda", "Ferro",  "Leggenda",          "ultra", "coupe", 70e6, [2018, 2026], ["benzin"],   ["otomatik"], 1],
    ["mirage-apex",    "Mirage", "Apex Hiper",        "ultra", "coupe", 55e6, [2019, 2026], ["benzin"],   ["otomatik"], 1],
    ["nordik-aurora",  "Nordik", "Aurora EV Hiper",   "ultra", "coupe", 60e6, [2021, 2026], ["elektrik"], ["otomatik"], 1],
    ["stahl-phantom",  "Stahl",  "Phantom V12",       "ultra", "sedan", 48e6, [2016, 2026], ["benzin"],   ["otomatik"], 1],
    ["volta-speedster","Volta",  "Speedster '57",     "ultra", "coupe", 40e6, [1955, 1959], ["benzin"],   ["manuel"],   1],
    ["anka-prototip",  "Anka",   "Kartal Prototip '64","ultra", "sedan", 35e6, [1964, 1964], ["benzin"],   ["manuel"],   1]
  ].map(a => ({ id: a[0], brand: a[1], name: a[2], seg: a[3], body: a[4], price: a[5], years: a[6], fuels: a[7], gears: a[8], pop: a[9] }));
  G.M = {};
  G.MODELS.forEach(m => { G.M[m.id] = m; });

  // Ekspertizde çıkabilecek gizli kusurlar. hit: ilgili parçanın gerçek durumundan düşen puan, fix: onarım maliyeti (araç değerine oranla)
  G.ISSUES = {
    sanziman: { name: "Şanzıman arızası",            part: "motor", hit: 30, fix: 0.07, hours: 14 },
    conta:    { name: "Silindir kapak contası yakmış", part: "motor", hit: 22, fix: 0.04, hours: 10, noEv: true },
    turbo:    { name: "Turbo arızası",               part: "motor", hit: 18, fix: 0.05, hours: 8,  noEv: true },
    batarya:  { name: "Batarya sağlığı düşük",        part: "motor", hit: 26, fix: 0.12, hours: 6,  evOnly: true },
    pas:      { name: "Şasi paslanması",             part: "body",  hit: 22, fix: 0.05, hours: 16 },
    elektrik: { name: "Elektrik tesisatı arızası",   part: "inter", hit: 16, fix: 0.03, hours: 6 },
    gizliboya:{ name: "Beyan edilmemiş boyalı parça", part: null,    hit: 0,  fix: null, painted: 2 },
    km:       { name: "Kilometresi düşürülmüş",       part: null,    hit: 0,  fix: null },
    sel:      { name: "Sel hasarı geçmişi",          part: "inter", hit: 20, fix: null, valueHit: 0.72 }
  };

  // Araç ilanı veren satıcı tipleri. ask: piyasa değerine göre istenen fiyat, min: istenen fiyatın en fazla ne kadarına inerler
  G.SELLERS = {
    sahibinden: { name: "Sahibinden",          ask: [0.97, 1.12], min: [0.86, 0.94], issue: 0.12, w: 5, rounds: 3 },
    galeriden:  { name: "Galeriden",           ask: [1.04, 1.16], min: [0.93, 0.97], issue: 0.05,  w: 3, rounds: 2, clean: true },
    filo:       { name: "Filo çıkışı",         ask: [0.92, 1.02], min: [0.9, 0.95],  issue: 0.06, w: 2, rounds: 2, kmMult: 1.9 },
    acil:       { name: "Acil satılık",        ask: [0.84, 0.93], min: [0.84, 0.91], issue: 0.2, w: 2, rounds: 3 },
    hasarli:    { name: "Ağır hasar kayıtlı",  ask: [0.62, 0.78], min: [0.85, 0.93], issue: 0.3, w: 1, rounds: 3, bodyHit: 35, pert: true, lvl: 2 },
    ihale:      { name: "İhale",               ask: [1, 1],       min: [1, 1],       issue: 0.18,  w: 0, rounds: 0 },
    miras:      { name: "Koleksiyon satışı",   ask: [0.72, 0.84], min: [0.9, 0.95],  issue: 0.05, w: 0, rounds: 3, clean: true },
    ithal:      { name: "Yurt dışı (ithal)",   ask: [0.9, 1.0],   min: [0.92, 0.97], issue: 0.03, w: 0, rounds: 2, clean: true }
  };

  // Galeriye gelen müşteri tipleri. segs: hangi segmentleri ne kadar istedikleri, haggle: pazarlık payı, picky: boya/değişene hassasiyet
  G.CTYPES = [
    { id: "ogrenci",  name: "Üniversite öğrencisi", budget: [250e3, 600e3],  segs: { ekonomi: 4, klasik: 0.4 },               haggle: 0.10, picky: 0.3, patience: 50,  prestige: 0, w: 3,   trade: 0 },
    { id: "genc",     name: "Genç çalışan",         budget: [450e3, 1.2e6],  segs: { ekonomi: 3, aile: 1, elektrik: 0.7, spor: 0.4 }, haggle: 0.07, picky: 0.5, patience: 60, prestige: 0, w: 4, trade: 0.2 },
    { id: "aile",     name: "Kalabalık aile",       budget: [650e3, 1.8e6],  segs: { aile: 3, suv: 2, ticari: 0.4 },          haggle: 0.08, picky: 0.7, patience: 80,  prestige: 0, w: 4,   trade: 0.35 },
    { id: "esnaf",    name: "Esnaf",                budget: [450e3, 1.6e6],  segs: { ticari: 4, aile: 0.6 },                  haggle: 0.12, picky: 0.4, patience: 50,  prestige: 0, w: 3,   trade: 0.25 },
    { id: "memur",    name: "Memur",                budget: [550e3, 1.3e6],  segs: { aile: 3, ekonomi: 2 },                   haggle: 0.06, picky: 0.85, patience: 90, prestige: 0, w: 3,   trade: 0.3 },
    { id: "emekli",   name: "Emekli",               budget: [400e3, 1.1e6],  segs: { aile: 2, ekonomi: 2, klasik: 0.6 },      haggle: 0.09, picky: 0.9, patience: 120, prestige: 0, w: 2,   trade: 0.3 },
    { id: "tekno",    name: "Teknoloji meraklısı",  budget: [1.1e6, 3e6],    segs: { elektrik: 4, premium: 1 },               haggle: 0.05, picky: 0.7, patience: 70,  prestige: 1, w: 2,   trade: 0.2 },
    { id: "filo",     name: "Filo yöneticisi",      budget: [1e6, 2.6e6],    segs: { ticari: 3, aile: 2 },                    haggle: 0.10, picky: 0.5, patience: 60,  prestige: 1, w: 1,   trade: 0 },
    { id: "koleksiyon", name: "Koleksiyoncu",       budget: [700e3, 4e6],    segs: { klasik: 6 },                             haggle: 0.08, picky: 0.4, patience: 100, prestige: 1, w: 1,   trade: 0 },
    { id: "doktor",   name: "Doktor",               budget: [1.8e6, 4.5e6],  segs: { premium: 3, suv: 2, elektrik: 1 },       haggle: 0.05, picky: 0.9, patience: 60,  prestige: 2, w: 2,   trade: 0.3 },
    { id: "hizli",    name: "Hız tutkunu",          budget: [2e6, 8.5e6],    segs: { spor: 5 },                               haggle: 0.05, picky: 0.8, patience: 70,  prestige: 2, w: 1.2, trade: 0.2 },
    { id: "patron",   name: "İş insanı",            budget: [3e6, 1.05e7],   segs: { luks: 3, premium: 2, suv: 1 },           haggle: 0.06, picky: 1,   patience: 50,  prestige: 3, w: 1.5, trade: 0.3 },
    // Yalnızca galeride ultra lüks araç varken gelir
    { id: "milyarder", name: "Milyarder koleksiyoncu", budget: [3e7, 1.4e8], segs: { ultra: 10 },                             haggle: 0.06, picky: 0.6, patience: 150, prestige: 0, w: 0,   trade: 0, ultra: true }
  ];
  G.CT = {};
  G.CTYPES.forEach(t => { G.CT[t.id] = t; });

  G.FIRST = ["Ahmet", "Mehmet", "Ayşe", "Fatma", "Zeynep", "Elif", "Can", "Emre", "Burak", "Deniz", "Ece", "Selin", "Murat", "Hakan",
    "Gizem", "Mert", "Kerem", "Derya", "Ozan", "Merve", "Hülya", "Yusuf", "Kemal", "Sevgi", "Ali", "Esra", "Serkan", "Tuğba", "Onur", "Nur"];
  G.LAST = ["Yılmaz", "Kaya", "Demir", "Şahin", "Çelik", "Aydın", "Öztürk", "Arslan", "Doğan", "Koç", "Kurt", "Aksoy", "Polat", "Erdem", "Yıldız", "Güneş", "Tekin", "Bulut"];
  G.RIVALS = ["Kartal Oto", "Yıldız Motors", "Hakan Usta", "Özgür Galeri", "Bayrak Otomotiv", "Ege Oto", "Marmara Motor", "Çınar Galeri"];

  // Galeri konumları; her taşınma kapasiteyi, müşteri trafiğini ve prestijli müşteri gelme ihtimalini artırır
  G.LOCS = [
    { id: "sanayi",  name: "Sanayi Sitesi Arsası", desc: "Toprak zemin, konteyner ofis. Her şey burada başlar.",       cap: 4,  staff: 1,  rent: 1500,  traffic: 0.8,  prestige: 0, lvl: 1,  cost: 0 },
    { id: "mahalle", name: "Mahalle Galerisi",     desc: "Ana caddenin arkasında küçük bir dükkân ve önünde açık alan.", cap: 8,  staff: 2,  rent: 4500,  traffic: 1.0,  prestige: 1, lvl: 3,  cost: 300e3 },
    { id: "cadde",   name: "Cadde Showroom",       desc: "Cam cepheli showroom; vitrindeki araçlar yoldan görünür.",    cap: 14, staff: 4,  rent: 11000, traffic: 1.35, prestige: 2, lvl: 6,  cost: 1.5e6 },
    { id: "plaza",   name: "Oto Plaza Mağazası",   desc: "Galericiler plazasında iki katlı mağaza.",                    cap: 22, staff: 6,  rent: 24000, traffic: 1.8,  prestige: 3, lvl: 10, cost: 5e6 },
    { id: "merkez",  name: "Otomotiv Merkezi",     desc: "Kendi binan: showroom, servis ve teslimat alanı bir arada.",  cap: 36, staff: 10, rent: 50000, traffic: 2.4,  prestige: 4, lvl: 15, cost: 18e6 }
  ];

  G.UPGRADES = {
    bays:     { name: "Atölye bölmesi",     desc: "Mekanik ve iç onarımları kendi atölyende yaparsın: dış servisten %35 ucuz ve daha hızlı. Her bölme aynı anda bir iş.", levels: [{ cost: 150e3, lvl: 2 }, { cost: 400e3, lvl: 4 }, { cost: 900e3, lvl: 8 }, { cost: 2e6, lvl: 12 }] },
    paint:    { name: "Boya kabini",        desc: "Kaporta-boya işi içeride yapılır; usta işçilikle tek parça boya yeterli olur.",  levels: [{ cost: 350e3, lvl: 4 }] },
    pdr:      { name: "Boyasız göçük seti", desc: "Küçük göçükleri boyasız düzeltir; boyalı parça sayısı artmaz.",                 levels: [{ cost: 90e3, lvl: 2 }] },
    expert:   { name: "Ekspertiz cihazı",   desc: "Ekspertiz 500 ₺'ye düşer; ihaledeki araçları da kontrol edebilirsin.",          levels: [{ cost: 120e3, lvl: 3 }] },
    cafe:     { name: "Müşteri köşesi",     desc: "Çay-kahve ve oturma alanı: müşteriler %35 daha sabırlı.",                       levels: [{ cost: 80e3, lvl: 2 }] },
    security: { name: "Kamera ve alarm",    desc: "Gece hırsızlık ve vandalizm olaylarını önler.",                                  levels: [{ cost: 50e3, lvl: 1 }] }
  };

  // Tesisler: her biri 20 seviye.
  // Denge ayarı: n. seviye için gereken oyuncu seviyesi = start + yuvarla((n - 1) × 1,15); fiyat = base × 1,3^(n - 1).
  // Böylece 20. seviye oyuncunun 23–25. seviyesine denk gelir ve her tesis oyuncuyla birlikte büyür.
  G.FAC_MAX = 20;
  G.FAC_STEP = 1.15;
  G.FAC_GROWTH = 1.3;
  G.FACILITIES = {
    galeri: {
      name: "Galeri binası", short: "Galeri", start: 2, base: 60e3,
      desc: "Showroom'un vitrini, ışıkları ve dekoru. Daha çok müşteri çeker, vitrin yeri açar, müşteriler araca daha çok para verir.",
      marks: { 2: "İlk vitrin yeri", 4: "Spot ışıklar", 8: "Neon tabela", 12: "LED reklam ekranı", 16: "Altın yaldızlı cephe", 20: "Efsane showroom" }
    },
    yikama: {
      name: "Yıkama ünitesi", short: "Yıkama", start: 1, base: 40e3,
      desc: "Araçları içeride yıkarsın: ucuz ve hızlı. Seviye arttıkça her sabah araçlar kendiliğinden yıkanır, cila ile temiz araç daha parlak durur.",
      marks: { 1: "İçeride yıkama", 2: "Her sabah 1 araç otomatik yıkanır", 5: "Cila: temiz araç müşteriyi daha çok çeker", 10: "Köpük tüneli", 20: "Detaylı bakım merkezi" }
    },
    cardak: {
      name: "Usta çardağı", short: "Çardak", start: 3, base: 50e3,
      desc: "Ustaların çay içip dinlendiği asmalı çardak. Çalışan usta yorulur ve yavaşlar; çardağa gönderdiğin usta enerjisini toplar, çıkınca bir süre çok daha hızlı çalışır.",
      marks: { 1: "1 kişilik çardak", 5: "2 kişilik", 10: "Otomatik mola: yorulan usta kendisi dinlenmeye gider", 15: "4 kişilik", 20: "5 kişilik, semaver ve hamak" }
    },
    otopark: {
      name: "Ön otopark", short: "Otopark", start: 1, base: 30e3,
      desc: "Galerinin önündeki park alanı. Her iki seviyede bir araçlık yer açılır; ışık, tente ve asfalt araçları korur ve daha iyi gösterir.",
      marks: { 2: "+1 park yeri", 4: "Aydınlatma: hırsızlık riski yarıya iner", 6: "Tente: araçların bir kısmı doludan korunur", 10: "Asfalt ve çizgili park yerleri", 20: "+10 park yeri" }
    }
  };

  // Lüks alışveriş: patronun kişisel harcamaları. Şöhret puanı verir, günlük bakım masrafı vardır, sonradan satılabilir.
  // upkeep: fiyatın günlük yüzdesi, resale: satışta geri alınan oran
  // Fiyatlar uzun vadeli hedef olsun diye yüksek tutuldu; bakım oranları düşük ki sahip olmak oyunu batırmasın.
  G.LUX_CATS = {
    ev:    { name: "Ev",    upkeep: 0.00016, resale: 0.95, perk: "Evler en çok şöhreti verir ve değerini korur." },
    araba: { name: "Araba", upkeep: 0.00032, resale: 0.7,  perk: "En iyi araban galerinin önüne park eder; müşteriler patronun arabasını görür." },
    mucevher: { name: "Mücevher", upkeep: 0.0001, resale: 0.85, perk: "Mücevherler değerini korur, sigortası ucuzdur; şöhret kazandırır." },
    takim: { name: "Takım elbise", upkeep: 0.0004, resale: 0.4, perk: "Şık patrona müşteri güvenir: en iyi takım elbisen müşterinin ödemeye razı olduğu fiyatı artırır." },
    ucak:  { name: "Uçak",  upkeep: 0.00048, resale: 0.75, perk: "Yurt dışı bağlantısı: her sabah piyasaya ithal premium, spor ve lüks araç ilanları gelir." },
    yat:   { name: "Yat",   upkeep: 0.00048, resale: 0.7,  perk: "Haftada bir yat partisi: ertesi gün galeriye zengin müşteri akını ve itibar." },
    yatirim:  { name: "Yatırım",  upkeep: 0.0015, resale: 0.85, perk: "Yatırımlar her gün gelir getirir ama bakım ve işletme gideri de vardır. Oyundan çıkınca da kazandırır." }
  };
  G.LUXURY = [
    { id: "e01", cat: "ev", name: "Stüdyo daire", desc: "Şehir merkezinde, metroya 3 dakika.", price: 4e6, lvl: 2, pres: 2, art: { t: "studio" } },
    { id: "ev1", cat: "ev", name: "Mahalle dairesi", desc: "2+1, asansörlü, köşede bakkal.", price: 9e6, lvl: 3, pres: 5 },
    { id: "e03", cat: "ev", name: "Bahçeli müstakil ev", desc: "Meyve ağaçlı bahçe, çardak, mangal.", price: 15e6, lvl: 5, pres: 7, art: { t: "mustakil" } },
    { id: "ev2", cat: "ev", name: "Site içinde 3+1", desc: "Havuzlu, güvenlikli, otoparklı site.", price: 22e6, lvl: 6, pres: 10 },
    { id: "e05", cat: "ev", name: "Dağ evi", desc: "Kayak merkezinde şömineli ahşap ev.", price: 32e6, lvl: 8, pres: 13, art: { t: "dag" } },
    { id: "e06", cat: "ev", name: "Çiftlik evi", desc: "Atlar, bağ ve zeytinlik.", price: 45e6, lvl: 9, pres: 16, art: { t: "ciftlik" } },
    { id: "ev3", cat: "ev", name: "Boğaz manzaralı rezidans", desc: "Kat görevlisi, vale, köprü manzarası.", price: 70e6, lvl: 10, pres: 20 },
    { id: "e08", cat: "ev", name: "Gökdelen penthouse", desc: "Son kat, teras havuzu, şehir ayaklarının altında.", price: 110e6, lvl: 12, pres: 26, art: { t: "penthouse" } },
    { id: "ev4", cat: "ev", name: "Müstakil villa", desc: "Bahçeli, havuzlu, 6+2.", price: 160e6, lvl: 14, pres: 35 },
    { id: "e10", cat: "ev", name: "Tarihi köşk", desc: "Restore edilmiş 19. yüzyıl köşkü.", price: 260e6, lvl: 17, pres: 45, art: { t: "kosk" } },
    { id: "ev5", cat: "ev", name: "Sahil yalısı", desc: "Ahşap cephe, kendi iskelesi, deniz kapıda.", price: 450e6, lvl: 20, pres: 60 },
    { id: "e12", cat: "ev", name: "Özel ada", desc: "Ege'de kendine ait bir ada.", price: 900e6, lvl: 24, pres: 85, art: { t: "ada" } },
    { id: "e13", cat: "ev", name: "Avrupa'da şato", desc: "Bağları ve kuleleriyle 400 yıllık şato.", price: 1.4e9, lvl: 27, pres: 110, art: { t: "sato" } },
    { id: "a01", cat: "araba", name: "Anka Serçe Full Paket", desc: "Sıfır kilometre, ilk kendi araban.", price: 2.5e6, lvl: 2, pres: 1, model: "anka-serce", color: "Kırmızı" },
    { id: "a02", cat: "araba", name: "Kaizen Mio Hibrit", desc: "Şehir içinde litresine 25 km.", price: 3.2e6, lvl: 2, pres: 1, model: "kaizen-mio", color: "Mavi" },
    { id: "a03", cat: "araba", name: "Volta Pira GT", desc: "Sportif paket, kırmızı dikişli koltuklar.", price: 4e6, lvl: 3, pres: 2, model: "volta-pira", color: "Beyaz" },
    { id: "a04", cat: "araba", name: "Rivo Dunar 4x4", desc: "Hafta sonu yaylaya çıkar.", price: 4.6e6, lvl: 3, pres: 2, model: "rivo-dunar", color: "Turuncu" },
    { id: "a05", cat: "araba", name: "Anka e-Kartal Uzun Menzil", desc: "Yerli elektrikli, 520 km menzil.", price: 5.2e6, lvl: 4, pres: 3, model: "anka-ekartal", color: "Lacivert" },
    { id: "a06", cat: "araba", name: "Kaizen Taiga Hibrit", desc: "Aile için geniş ve sessiz.", price: 6e6, lvl: 4, pres: 3, model: "kaizen-taiga", color: "Gümüş" },
    { id: "a07", cat: "araba", name: "Volta Arcus Elegance", desc: "Masaj koltuklu iş sedanı.", price: 6.8e6, lvl: 5, pres: 3, model: "volta-arcus", color: "Siyah" },
    { id: "a08", cat: "araba", name: "Hanbit Ion 5", desc: "Ultra hızlı şarj, panoramik cam tavan.", price: 7.5e6, lvl: 5, pres: 4, model: "hanbit-ion", color: "Gri" },
    { id: "a09", cat: "araba", name: "Nordik S70 Kombi", desc: "Bagajı geniş, güvenlikte sınıfının en iyisi.", price: 8.5e6, lvl: 6, pres: 4, model: "nordik-s70", color: "Bordo" },
    { id: "a10", cat: "araba", name: "Kaizen Ryu", desc: "Arkadan itişli, manuel spor coupe.", price: 9.5e6, lvl: 6, pres: 5, model: "kaizen-ryu", color: "Kırmızı" },
    { id: "ar1", cat: "araba", name: "Stahl S5", desc: "Siyah, tam donanım. İlk patron arabası.", price: 11e6, lvl: 7, pres: 5, model: "stahl-s5", color: "Siyah" },
    { id: "ar2", cat: "araba", name: "Mirage Stallion GT", desc: "Kırmızı, V8, manuel.", price: 13e6, lvl: 7, pres: 6, model: "mirage-stallion", color: "Kırmızı" },
    { id: "a13", cat: "araba", name: "Nordik XR40", desc: "İskandinav tasarımı kompakt SUV.", price: 15e6, lvl: 8, pres: 7, model: "nordik-xr40", color: "Beyaz" },
    { id: "a14", cat: "araba", name: "Kaizen Luxa ES", desc: "Sessiz hibrit, makam aracı konforu.", price: 17e6, lvl: 8, pres: 7, model: "kaizen-luxa", color: "Gümüş" },
    { id: "a15", cat: "araba", name: "Mirage Voltis Plaid", desc: "0-100 km/s 2,1 saniye.", price: 19e6, lvl: 9, pres: 8, model: "mirage-voltis", color: "Beyaz" },
    { id: "ar3", cat: "araba", name: "Nordik XR90", desc: "Beyaz, 7 koltuk, her yola çıkar.", price: 20e6, lvl: 9, pres: 9, model: "nordik-xr90", color: "Beyaz" },
    { id: "a17", cat: "araba", name: "Ferro Veloce", desc: "İtalyan V12, el yapımı iç mekân.", price: 24e6, lvl: 10, pres: 10, model: "ferro-veloce", color: "Kırmızı" },
    { id: "a18", cat: "araba", name: "Mirage Escala", desc: "Kırmızı halı SUV'u.", price: 26e6, lvl: 11, pres: 11, model: "mirage-escala", color: "Siyah" },
    { id: "ar4", cat: "araba", name: "Stahl S8 uzun şasi", desc: "Lacivert, arkada masaj koltukları.", price: 28e6, lvl: 11, pres: 12, model: "stahl-s8", color: "Lacivert" },
    { id: "a20", cat: "araba", name: "Stahl R Coupe", desc: "Pist modlu süper otomobil.", price: 34e6, lvl: 13, pres: 14, model: "stahl-rcoupe", color: "Gri" },
    { id: "a21", cat: "araba", name: "Stahl Phantom V12", desc: "Yıldızlı tavan, kapıda şemsiye.", price: 55e6, lvl: 15, pres: 18, model: "stahl-phantom", color: "Siyah" },
    { id: "ar5", cat: "araba", name: "Ferro Inferno", desc: "Sarı hiper otomobil. Dünyada 99 tane.", price: 80e6, lvl: 17, pres: 25, model: "ferro-veloce", color: "Sarı" },
    { id: "a23", cat: "araba", name: "Mirage Apex Hiper", desc: "1.500 beygir, karbon gövde.", price: 110e6, lvl: 19, pres: 30, model: "mirage-apex", color: "Turuncu" },
    { id: "a24", cat: "araba", name: "Nordik Aurora EV Hiper", desc: "Elektrikli hiper otomobil, 2.000 beygir.", price: 140e6, lvl: 21, pres: 35, model: "nordik-aurora", color: "Mavi" },
    { id: "a25", cat: "araba", name: "Volta Speedster '57 (müze parçası)", desc: "Yarış geçmişi olan koleksiyon klasiği.", price: 175e6, lvl: 23, pres: 40, model: "volta-speedster", color: "Gümüş" },
    { id: "a26", cat: "araba", name: "Ferro Leggenda", desc: "Dünyada sadece 12 tane üretildi.", price: 250e6, lvl: 26, pres: 55, model: "ferro-leggenda", color: "Kırmızı" },
    { id: "j01", cat: "mucevher", name: "Gümüş künye", desc: "Adın kazınmış, el işi.", price: 150000, lvl: 1, pres: 1, art: { t: "kunye", metal: "#c9cfd6", stone: "#c9cfd6" } },
    { id: "j02", cat: "mucevher", name: "22 ayar altın bilezik", desc: "Kapalıçarşı işçiliği burgulu bilezik.", price: 400000, lvl: 2, pres: 1, art: { t: "bilezik", metal: "#d4a72c", stone: "#d4a72c" } },
    { id: "j03", cat: "mucevher", name: "Oniks kol düğmeleri", desc: "Gümüş kasa, siyah oniks taş.", price: 600000, lvl: 2, pres: 1, art: { t: "dugme", metal: "#c9cfd6", stone: "#1d2024" } },
    { id: "j04", cat: "mucevher", name: "Altın zincir kolye", desc: "Kalın Kral zincir, 14 ayar.", price: 900000, lvl: 3, pres: 2, art: { t: "zincir", metal: "#d4a72c", stone: "#d4a72c" } },
    { id: "j05", cat: "mucevher", name: "Çelik dalgıç saati", desc: "300 metreye su geçirmez.", price: 1.4e6, lvl: 3, pres: 2, art: { t: "saat", metal: "#c9cfd6", stone: "#1f4fa8" } },
    { id: "m1", cat: "mucevher", name: "Altın kol saati", desc: "18 ayar altın kasa, timsah deri kayış.", price: 2.5e6, lvl: 4, pres: 3 },
    { id: "j07", cat: "mucevher", name: "Safir küpe", desc: "Seylan safiri, beyaz altın.", price: 3.5e6, lvl: 5, pres: 3, art: { t: "kupe", metal: "#e3e7eb", stone: "#2f6fd0" } },
    { id: "j08", cat: "mucevher", name: "İnci kolye", desc: "Güney Denizi incileri.", price: 5e6, lvl: 6, pres: 4, art: { t: "inci", metal: "#e3e7eb", stone: "#f7f3e8" } },
    { id: "j09", cat: "mucevher", name: "Yakut yüzük", desc: "Birmanya yakutu, güvercin kanı rengi.", price: 7e6, lvl: 7, pres: 5, art: { t: "yuzuk", metal: "#d4a72c", stone: "#c8102e" } },
    { id: "m2", cat: "mucevher", name: "İsviçre komplikasyonlu saat", desc: "Ay fazlı, dakika tekrarlı, 300 adet üretildi.", price: 9e6, lvl: 8, pres: 7 },
    { id: "j11", cat: "mucevher", name: "Pırlanta su yolu bileklik", desc: "60 pırlanta, platin.", price: 12e6, lvl: 9, pres: 8, art: { t: "bileklik", metal: "#e3e7eb", stone: "#e8f6ff" } },
    { id: "m3", cat: "mucevher", name: "Pırlanta tektaş yüzük", desc: "4 karat, D renk, kusursuz berraklık.", price: 18e6, lvl: 10, pres: 10 },
    { id: "j13", cat: "mucevher", name: "Platin pırlantalı saat", desc: "Kadranı pırlantalarla kaplı.", price: 25e6, lvl: 11, pres: 12, art: { t: "saat", metal: "#e3e7eb", stone: "#e8f6ff" } },
    { id: "j14", cat: "mucevher", name: "Antika sorguç", desc: "Saray kuyumcusu işi, zümrüt ve elmas.", price: 32e6, lvl: 12, pres: 14, art: { t: "sorguc", metal: "#d4a72c", stone: "#1f9d5b" } },
    { id: "m4", cat: "mucevher", name: "Zümrüt gerdanlık", desc: "Kolombiya zümrütleri, el işi altın zincir.", price: 45e6, lvl: 14, pres: 18 },
    { id: "j16", cat: "mucevher", name: "Mavi elmas küpe", desc: "Nadir mavi elmaslar, 3'er karat.", price: 70e6, lvl: 16, pres: 22, art: { t: "kupe", metal: "#e3e7eb", stone: "#5ec8f0" } },
    { id: "j17", cat: "mucevher", name: "Mücevherli yumurta", desc: "Altın ve mineli, içinden minik bir araba çıkar.", price: 100e6, lvl: 18, pres: 28, art: { t: "yumurta", metal: "#d4a72c", stone: "#c8102e" } },
    { id: "m5", cat: "mucevher", name: "Nadir pembe elmas", desc: "Müzayededen, dünyada benzeri yok.", price: 160e6, lvl: 20, pres: 40 },
    { id: "j19", cat: "mucevher", name: "Kraliyet tacı", desc: "Müzayededen, 1.200 değerli taş.", price: 300e6, lvl: 23, pres: 55, art: { t: "tac", metal: "#d4a72c", stone: "#c8102e" } },
    { id: "j20", cat: "mucevher", name: "Doğu Yıldızı elması", desc: "Dünyanın en büyük berrak elmaslarından.", price: 600e6, lvl: 26, pres: 80, art: { t: "buyukelmas", metal: "#e3e7eb", stone: "#e8f6ff" } },
    { id: "s01", cat: "takim", name: "Hazır takım elbise", desc: "Mağazadan, lacivert, tek düğme.", price: 25000, lvl: 1, pres: 1, charm: 0.005, art: { t: "takim", fabric:"#1f2f5a",lapel:"#18254a",tie:"#c8282d" } },
    { id: "s02", cat: "takim", name: "Ofis takımı", desc: "Gri yün, iki parça, her gün giyilir.", price: 60000, lvl: 2, pres: 1, charm: 0.01, art: { t: "takim", fabric:"#6b737c",lapel:"#575e66",tie:"#1f4fa8" } },
    { id: "s03", cat: "takim", name: "Yelekli üç parça takım", desc: "Yelek, cep mendili ve kravat iğnesi.", price: 150000, lvl: 3, pres: 2, charm: 0.015, art: { t: "takim", fabric:"#5a4636",lapel:"#4a3a2c",tie:"#d4a72c",vest:"#6e5642",sq:"#d4a72c" } },
    { id: "s04", cat: "takim", name: "Smokin", desc: "Saten yaka ve papyon; gala geceleri için.", price: 300000, lvl: 5, pres: 3, charm: 0.02, art: { t: "takim", fabric:"#16181c",lapel:"#3a3f47",tie:"#0b0c0e",bow:1,sq:"#ffffff" } },
    { id: "s05", cat: "takim", name: "İtalyan yünü takım", desc: "İnce yün, el dikişi astar.", price: 650000, lvl: 7, pres: 4, charm: 0.025, art: { t: "takim", fabric:"#2c3e66",lapel:"#24345a",tie:"#7b2d8e",sq:"#f2f3f1" } },
    { id: "s06", cat: "takim", name: "Ismarlama terzi takımı", desc: "Ölçüye göre dikilir, üç prova.", price: 1.2e6, lvl: 9, pres: 5, charm: 0.03, art: { t: "takim", fabric:"#3a3f47",lapel:"#2e3238",tie:"#6e1f2b",vest:"#3a3f47",sq:"#c8282d" } },
    { id: "s07", cat: "takim", name: "Kaşmir palto ve takım", desc: "Kaşmir palto, deri eldiven, ipek atkı.", price: 2.5e6, lvl: 12, pres: 7, charm: 0.035, art: { t: "takim", fabric:"#8a6a4a",lapel:"#735738",tie:"#1f2f5a",coat:1,scarf:"#b8312f" } },
    { id: "s08", cat: "takim", name: "Londra terzisi ısmarlama", desc: "Ünlü terzi sokağından, 80 saat el işçiliği.", price: 5e6, lvl: 15, pres: 9, charm: 0.04, art: { t: "takim", fabric:"#1d2d4f",lapel:"#152341",tie:"#d4a72c",vest:"#1d2d4f",sq:"#f7f3e8",stripe:1 } },
    { id: "s09", cat: "takim", name: "Vikunya yünü takım", desc: "Dünyanın en nadir yünü, altın düğmeler.", price: 12e6, lvl: 19, pres: 12, charm: 0.045, art: { t: "takim", fabric:"#b08d64",lapel:"#9a7a54",tie:"#3a2a1c",btn:"#d4a72c",sq:"#f7f3e8" } },
    { id: "s10", cat: "takim", name: "Pırlanta düğmeli gala takımı", desc: "Altın iplik işlemeli, pırlanta düğmeler; tek parça üretim.", price: 30e6, lvl: 24, pres: 16, charm: 0.05, art: { t: "takim", fabric:"#0f1a33",lapel:"#d4a72c",tie:"#0b0c0e",bow:1,btn:"#e8f6ff",gems:1,sq:"#d4a72c" } },
    { id: "u01", cat: "ucak", name: "Ultra hafif uçak", desc: "İki kişilik, çayırdan kalkar.", price: 8e6, lvl: 6, pres: 8, imports: 1, art: { t: "ultrahafif" } },
    { id: "uc1", cat: "ucak", name: "Tek motorlu uçak", desc: "4 kişilik pervaneli uçak.", price: 30e6, lvl: 9, pres: 15, imports: 1 },
    { id: "u03", cat: "ucak", name: "Amfibi deniz uçağı", desc: "Denize iner, koylara uçar.", price: 45e6, lvl: 11, pres: 18, imports: 1, art: { t: "amfibi" } },
    { id: "uc2", cat: "ucak", name: "Helikopter", desc: "Şehir içi trafiğe takılmazsın.", price: 62e6, lvl: 12, pres: 25, imports: 2 },
    { id: "u05", cat: "ucak", name: "Çift motorlu turboprop", desc: "10 kişilik, kısa pistlere iner.", price: 95e6, lvl: 14, pres: 32, imports: 2, art: { t: "turboprop" } },
    { id: "uc3", cat: "ucak", name: "Hafif jet", desc: "8 kişilik, Avrupa'ya 3 saat.", price: 150e6, lvl: 15, pres: 45, imports: 3 },
    { id: "u07", cat: "ucak", name: "Orta boy iş jeti", desc: "Ayakta durulan kabin, 12 kişi.", price: 300e6, lvl: 19, pres: 60, imports: 3, art: { t: "ortajet" } },
    { id: "uc4", cat: "ucak", name: "Uzun menzil jet", desc: "Okyanus aşar, yatak odalı.", price: 550e6, lvl: 22, pres: 80, imports: 4 },
    { id: "u09", cat: "ucak", name: "VIP geniş gövde uçak", desc: "Uçan saray: toplantı salonu ve yatak odaları.", price: 1.1e9, lvl: 27, pres: 120, imports: 5, art: { t: "genis" } },
    { id: "t01", cat: "yat", name: "Jet ski", desc: "Koyda dalga yarışı.", price: 1.5e6, lvl: 3, pres: 2, party: 1, art: { t: "jetski" } },
    { id: "t02", cat: "yat", name: "Balıkçı teknesi", desc: "Sabah erkenden lüfer avı.", price: 4e6, lvl: 5, pres: 3, party: 1, art: { t: "balikci" } },
    { id: "t03", cat: "yat", name: "Yelkenli", desc: "10 metrelik, iki kamaralı.", price: 9e6, lvl: 6, pres: 5, party: 1, art: { t: "yelkenli" } },
    { id: "y1", cat: "yat", name: "Sürat teknesi", desc: "Hafta sonu koy gezisi için.", price: 15e6, lvl: 7, pres: 6, party: 1 },
    { id: "t05", cat: "yat", name: "Katamaran", desc: "Geniş güverte, sakin seyir.", price: 35e6, lvl: 10, pres: 12, party: 2, art: { t: "katamaran" } },
    { id: "y2", cat: "yat", name: "Ahşap gulet", desc: "Bodrum yapımı, 4 kamaralı.", price: 75e6, lvl: 12, pres: 18, party: 2 },
    { id: "t07", cat: "yat", name: "Flybridge yat", desc: "Üst kat dümen, güneşlenme terası.", price: 130e6, lvl: 15, pres: 28, party: 3, art: { t: "flybridge" } },
    { id: "y3", cat: "yat", name: "Motoryat", desc: "3 katlı, jakuzili.", price: 225e6, lvl: 17, pres: 40, party: 3 },
    { id: "t09", cat: "yat", name: "Süper yat", desc: "5 kamara, denizaltı garajı.", price: 480e6, lvl: 21, pres: 65, party: 4, art: { t: "super" } },
    { id: "y4", cat: "yat", name: "Mega yat", desc: "Helikopter pisti ve 12 kişilik mürettebat.", price: 875e6, lvl: 24, pres: 90, party: 4 },
    // Yatırımlar: income = günlük ortalama brüt gelir; bakım gideri kategori oranından (futbol takımında kendi oranı)
    { id: "i1", cat: "yatirim", name: "Kiralık daire",     desc: "Kiracısı hazır, düzenli kira geliri.",            price: 12e6,   lvl: 5,  pres: 3,   income: 72000 },
    { id: "i2", cat: "yatirim", name: "Restoran",          desc: "Boğaz kenarında balık restoranı.",                price: 45e6,   lvl: 9,  pres: 8,   income: 270000 },
    { id: "i3", cat: "yatirim", name: "Butik otel",        desc: "24 odalı, Kapadokya'da taş otel.",               price: 110e6,  lvl: 13, pres: 15,  income: 660000 },
    { id: "i4", cat: "yatirim", name: "Alışveriş merkezi", desc: "180 mağazalı AVM, sinema ve yemek katı.",        price: 400e6,  lvl: 19, pres: 30,  income: 2400000 },
    { id: "i5", cat: "yatirim", name: "Futbol takımı",     desc: "Süper Lig kulübü: maç, bilet, yayın ve forma gelirleri. Oyuncu maaşları da senden.", price: 1.5e9, lvl: 25, pres: 150, income: 9000000, upkeep: 0.0025, football: true }
  ];
  G.LX = {};
  G.LUXURY.forEach(x => { G.LX[x.id] = x; });

  G.ADS = [
    { name: "Tanıtım yok",         cost: 0,     mult: 1 },
    { name: "Sosyal medya",        cost: 1200,  mult: 1.2 },
    { name: "İlan sitesi vitrini", cost: 3500,  mult: 1.5 },
    { name: "Billboard ve radyo",  cost: 12000, mult: 2.0, rep: 0.25 }
  ];

  G.ROLES = {
    satis:    { name: "Satış danışmanı", base: 1300, desc: "Sen meşgulken teklifleri karşılar, pazarlık eder. Satıştan %1,5 prim alır." },
    usta:     { name: "Usta",            base: 1600, desc: "Atölye işlerini hızlandırır. Çalıştıkça yorulur; çardakta dinlenir." },
    yikama:   { name: "Yıkamacı",        base: 750,  desc: "Her sabah araçları siler, temiz tutar." },
    muhasebe: { name: "Muhasebeci",      base: 1150, desc: "Günlük giderleri düşürür, vergi denetiminde ceza yemezsin." },
    eksper:   { name: "Ekspertiz ustası", base: 1400, desc: "İlandaki ve ihaledeki aracın piyasa değerini tahmin eder; fırsat mı pahalı mı söyler. Yıldızı arttıkça tahmini isabetli olur." }
  };

  // Atölye işleri. cost: araç değerine göre maliyet (v = aracın kusursuz hâldeki piyasa değeri)
  G.JOBS = {
    clean:    { name: "Detaylı temizlik",       hours: 3,  cost: v => 3000,              where: "wash" },
    service:  { name: "Periyodik bakım",        hours: 6,  cost: v => 6000 + 0.01 * v,   where: "bay" },
    engine:   { name: "Motor revizyonu",        hours: 24, cost: v => 20000 + 0.05 * v,  where: "bay" },
    paint:    { name: "Kaporta ve boya",        hours: 16, cost: v => 12000 + 0.03 * v,  where: "paint" },
    pdr:      { name: "Boyasız göçük düzeltme", hours: 4,  cost: v => 6000,              where: "pdr" },
    interior: { name: "Döşeme ve iç yenileme",  hours: 10, cost: v => 7000 + 0.018 * v,  where: "bay" },
    restore:  { name: "Tam restorasyon",        hours: 60, cost: v => 60000 + 0.22 * v,  where: "bay" },
    fix:      { name: "Arıza giderme",          hours: 8,  cost: v => 0,                 where: "bay" }
  };

  // Piyasa olayları. seg: segment fiyat çarpanı, traffic: müşteri trafiği çarpanı
  G.EVENTS = [
    { id: "yakit",  title: "Akaryakıta büyük zam",          text: "Elektrikli ve hibritlere ilgi arttı, büyük SUV'lar yavaşladı.",       days: 5, seg: { elektrik: 1.14, suv: 0.93 } },
    { id: "bayram", title: "Bayram yaklaşıyor",              text: "Aileler memleket yolu için araç arıyor.",                              days: 4, seg: { aile: 1.08, suv: 1.05 }, traffic: 1.25, cust: { aile: 2.5 } },
    { id: "kur",    title: "Döviz kurunda sert yükseliş",    text: "Sıfır araç fiyatları arttı, ikinci el de yukarı gitti.",               days: 6, all: 1.07 },
    { id: "otv",    title: "ÖTV düzenlemesi",                text: "Sıfır lüks araç pahalandı, alıcılar ikinci ele yöneldi.",               days: 6, seg: { luks: 1.1, premium: 1.06 } },
    { id: "faiz",   title: "Taşıt kredisi faizleri düştü",   text: "Galerilere gelen müşteri sayısı arttı.",                                days: 5, traffic: 1.35 },
    { id: "durgun", title: "Piyasada durgunluk",             text: "Alıcılar bekle-gör modunda; fiyatlar biraz geriledi.",                 days: 4, all: 0.96, traffic: 0.75 },
    { id: "yaz",    title: "Yaz sezonu açıldı",              text: "Spor ve klasik araçlar gözde.",                                        days: 6, seg: { spor: 1.12, klasik: 1.06 }, cust: { hizli: 2 } },
    { id: "kis",    title: "Kar kapıda",                     text: "4x4 ve SUV talebi arttı, spor araçlara ilgi azaldı.",                   days: 6, seg: { suv: 1.1, spor: 0.93 } },
    { id: "fuar",   title: "Klasik otomobil fuarı",          text: "Koleksiyoncular şehirde.",                                             days: 3, seg: { klasik: 1.15 }, cust: { koleksiyon: 4 } },
    { id: "esnaf",  title: "Esnafa düşük faizli kredi",      text: "Ticari araç arayan çok.",                                              days: 5, seg: { ticari: 1.08 }, cust: { esnaf: 2.5, filo: 2 } },
    { id: "dolu",   title: "Gece dolu yağdı",                text: "Açıkta duran araçların kaportası zarar gördü.",                        instant: "dolu" },
    { id: "fenomen",title: "Bir fenomen galerini paylaştı",  text: "Takipçileri galerine akın etti.",                                       days: 2, traffic: 1.5, rep: 4 },
    { id: "hirsiz", title: "Gece hırsızlık girişimi",        text: "",                                                                     instant: "hirsiz" },
    { id: "denetim",title: "Vergi denetimi",                 text: "",                                                                     instant: "denetim" }
  ];

  G.QUESTS = [
    { id: "ilksatis",  text: "İlk aracını sat",                        reward: 25000,   check: S => S.stats.sold >= 1 },
    { id: "ekspertiz", text: "Bir aracı ekspertize sok",               reward: 10000,   check: S => S.stats.inspected >= 1 },
    { id: "ihale",     text: "Açık artırmadan araç kazan",             reward: 30000,   check: S => S.stats.auctions >= 1 },
    { id: "bes",       text: "5 araç sat",                             reward: 60000,   check: S => S.stats.sold >= 5 },
    { id: "personel",  text: "İlk çalışanını işe al",                  reward: 40000,   check: S => S.staff.length >= 1 },
    { id: "atolye",    text: "Atölye bölmesi kur",                     reward: 50000,   check: S => S.upg.bays >= 1 },
    { id: "mahalle",   text: "Mahalle galerisine taşın",               reward: 100000,  check: S => S.loc >= 1 },
    { id: "kar100",    text: "Tek satıştan 100.000 ₺ kâr et",          reward: 75000,   check: S => S.stats.best >= 100000 },
    { id: "itibar60",  text: "İtibarını 60'a çıkar",                   reward: 120000,  check: S => S.rep >= 60 },
    { id: "yirmibes",  text: "25 araç sat",                            reward: 250000,  check: S => S.stats.sold >= 25 },
    { id: "cadde",     text: "Cadde showroom'a taşın",                 reward: 300000,  check: S => S.loc >= 2 },
    { id: "kar500",    text: "Tek satıştan 500.000 ₺ kâr et",          reward: 400000,  check: S => S.stats.best >= 500000 },
    { id: "tesis5",    text: "Bir tesisi 5. seviyeye çıkar",           reward: 150000,  check: S => Object.values(S.fac || {}).some(n => n >= 5) },
    { id: "tesis10",   text: "Bir tesisi 10. seviyeye çıkar",          reward: 700000,  check: S => Object.values(S.fac || {}).some(n => n >= 10) },
    { id: "ultra",     text: "Ultra lüks bir araç sat",                reward: 3000000, check: S => (S.stats.ultraSold || 0) >= 1 },
    { id: "yatirim",   text: "İlk yatırımını yap",                     reward: 500000,  check: S => (S.lux || []).some(id => id[0] === "i") },
    { id: "futbol",    text: "Bir futbol takımı satın al",             reward: 50000000, check: S => (S.lux || []).includes("i5") },
    { id: "lux1",      text: "İlk lüks alışverişini yap",              reward: 100000,  check: S => (S.lux || []).length >= 1 },
    { id: "luks",      text: "Bir lüks araç sat",                      reward: 600000,  check: S => S.stats.luxSold >= 1 },
    { id: "plaza",     text: "Oto plazaya taşın",                      reward: 1000000, check: S => S.loc >= 3 },
    { id: "yuz",       text: "100 araç sat",                           reward: 1500000, check: S => S.stats.sold >= 100 },
    { id: "yat",       text: "Bir yat al ve parti ver",                reward: 1500000, check: S => (S.stats.parties || 0) >= 1 },
    { id: "net50",     text: "Net değerini 50 milyon ₺'ye çıkar",      reward: 3000000, check: S => G.netWorth() >= 50e6 },
    { id: "merkez",    text: "Otomotiv merkezini kur",                 reward: 5000000, check: S => S.loc >= 4 },
    { id: "tesis20",   text: "Bütün tesisleri 20. seviyeye çıkar",     reward: 10000000, check: S => Object.values(S.fac || {}).length === 4 && Object.values(S.fac).every(n => n >= 20) }
  ];

  G.PLATE_CITIES = ["34", "06", "35", "16", "07", "41", "01", "42", "33", "27", "55", "61"];
  G.PLATE_LETTERS = "ABCDEFGHJKLMNPRSTUVYZ";
})(window.G = window.G || {});
