# Galeri Patronu

İkinci el araç galerisi işletme oyunu. Sanayideki toprak arsada tek araçla başlarsın; araç alır, hazırlar, pazarlıkla satar ve galerini otomotiv merkezine kadar büyütürsün.

Oyun saf HTML, CSS ve JavaScript ile yazıldı (kütüphane yok). Aynı kod tarayıcıda çalışır ve [Capacitor](https://capacitorjs.com) ile iOS ve Android uygulamasına dönüştürülür.

## Oyun sistemleri

| Sistem | Ne yapar |
|---|---|
| **Araçlar** | 9 segment (ekonomi, aile, ticari, SUV, elektrikli, premium, spor, klasik, lüks), 39 hayali model, 7 kasa tipi. Her aracın yılı, kilometresi, yakıtı, vitesi, rengi, motor/kaporta/iç durumu, boyalı ve değişen parça sayısı, tramer kaydı ve temizliği var. |
| **Değerleme** | Piyasa değeri yaşa, kilometreye, duruma, boya/değişene, tramere, renge, yakıta, vitese ve segment trendine göre hesaplanır. Klasik araçlar yaşla değer kaybetmez, restorasyonla çok kazanır. |
| **Gizli kusurlar** | Şanzıman, conta, turbo, batarya, pas, elektrik arızası, beyan edilmemiş boya, düşürülmüş kilometre, sel hasarı. Ekspertiz yaptırmadan alınan araçta çıkabilir; kusuru bilinmeden satılan araç müşteri şikâyeti, tazminat ve itibar kaybı getirir. |
| **Araç alımı** | Her sabah tamamen yenilenen ilanlar (oyuncu seviyesi ve adresle 10'dan 24'e çıkar; gün içinde 11:00, 13:00, 15:00 ve 17:00'de 2–3 yeni ilan daha düşer) (sahibinden, galeriden, filo çıkışı, acil satılık, ağır hasarlı) ve satıcıyla mesajlaşarak pazarlık. Her gün canlı açık artırma: rakip galericilerle saniye saniye teklif yarışı. |
| **Atölye** | Detaylı temizlik, periyodik bakım, motor revizyonu, kaporta-boya, boyasız göçük düzeltme, döşeme yenileme, arıza giderme, klasiklerde tam restorasyon. Kendi atölyen yoksa işler pahalı ve yavaş dış servise gider. Boya kaportayı düzeltir ama boyalı parça sayısını artırır. |
| **Müşteriler** | 12 müşteri tipi (öğrenci, esnaf, aile, doktor, koleksiyoncu, iş insanı...). Her birinin bütçesi, istediği segment, pazarlık payı ve boya/değişen hassasiyeti farklı. Kimi kendi ekspertizini yaptırır, kimi takasa araç verir. Teklifi kabul et, karşı teklif ver ya da reddet. |
| **Büyüme** | 5 konum (sanayi arsası → mahalle galerisi → cadde showroom → oto plaza → otomotiv merkezi), 8 geliştirme (yıkama, atölye bölmeleri, boya kabini, göçük seti, ekspertiz cihazı, kapalı vitrin, müşteri köşesi, kamera-alarm), 4 personel rolü, 4 reklam paketi, banka kredisi. |
| **Piyasa** | Segment fiyatları her gün oynar; akaryakıt zammı, bayram, kur artışı, ÖTV, faiz indirimi, kar, klasik otomobil fuarı gibi olaylar talebi değiştirir. Dolu, hırsızlık ve vergi denetimi gibi riskler var. |
| **İlerleme** | Seviye ve XP, segment kilitleri, 17 görev ve ödülleri, günlük kâr-zarar raporu ve 14 günlük grafik, otomatik kayıt. |
| **Galeri görünümü** | Galeri ekranının üstünde galerinin önden çizimi: bina konuma göre değişir (konteyner ofis, mahalle dükkânı, cam showroom, plaza, otomotiv merkezi). Alınan atölye, yıkama, vitrin, kamera ve müşteri köşesi sahnede belirir. Etiketli araçlar parkta, pazarlık eden müşteriler aracın yanında durur; gökyüzü gün içinde akşama döner. Sahnedeki araca, müşteriye ya da binaya dokununca ilgili ekran açılır. |
| **Sade kullanım** | "Şimdi ne yapmalı?" kartı sıradaki adımı söyler. Fiyat üç hazır seçenekle (hızlı satış, dengeli, kârlı) tek dokunuşta asılır; araç sayfası en kârlı atölye işini önerir; müşteriye "kabul et / biraz daha iste / etikette diren" düğmeleriyle cevap verilir. Ayrıntılar ve kaydırıcılar katlanır bölümlerde durur. |

Bir oyun günü (09:00–19:00) normal hızda yaklaşık 1 dakika sürer; 2× ve 4× hız ile duraklatma var.

## Tesisler (20 seviye)

| Tesis | Ne kazandırır |
|---|---|
| **Galeri binası** | Müşteri trafiği (+%3/seviye), müşterinin ödemeye razı olduğu fiyat (+%0,4/seviye), vitrin yerleri (2. seviyede açılır, her 3 seviyede +1). Spot ışık, neon tabela, LED ekran ve altın cephe sahnede görünür. |
| **Yıkama ünitesi** | İçeride ucuz ve hızlı yıkama, her sabah otomatik yıkanan araçlar (her 2 seviyede +1), daha geç kirlenme, 5. seviyeden sonra cila ile ek müşteri ilgisi. 10. seviyede köpük tüneli. |
| **Usta çardağı** | Ustalar çalıştıkça yorulur ve yavaşlar. Çardağa gönderilen usta enerjisini toplar; en az 30 dakika dinlenen usta çardaktan çıkınca bir süre çok daha hızlı çalışır (×1,33'ten ×2'ye, 1,5 saatten 4,5 saate). Seviyeyle çardağın kapasitesi ve dinlenme hızı artar; 10. seviyede yorulan usta kendisi molaya gider. |
| **Ön otopark** | Her 2 seviyede +1 park yeri, 4. seviyede aydınlatma (hırsızlık riski yarıya iner), 6. seviyede tente (araçların bir kısmı doludan korunur), 10. seviyede asfalt ve çizgili park yerleri. |

### Her seviye görünür

Her tesis seviyesi galeri sahnesine yeni bir parça ekler (20 seviye = 20 parça, birikerek):

- **Galeri binası:** çatı lambaları, "AÇIK" tabelası, saksılar, spot ışıklar, bayrak direği, kırmızı halı, neon çerçeve, giriş tentesi, çatı bayrakları, ağaç, LED ekran, çatı reklam panosu, ışıklı kaide, balonlar, altın cephe, ikinci ağaç, ışık hüzmeleri, kadife ip, taç ve parıltılar.
- **Yıkama:** hortum, kova, su deposu, cila şişeleri, dönen fırça, köpük, döner lamba, kurutma fanı, köpük tüneli, ikinci fırça, LED şerit, süpürge direği, köpük kabarcıkları, lastik parlatıcı, güneş paneli, bayrak, sürekli su, renk değiştiren ışık, altın "DETAY" tabelası.
- **Çardak:** masa, tabureler, kareli örtü, semaver ve çay bardakları, saksılar, tavla, radyo, kilim, ışık zinciri, kedi, testi, hasır perde, kuş kafesi, üzüm salkımları, vantilatör, mangal, fener, çiçekler, hamak; asma her seviyede biraz daha sarar.
- **Otopark:** P tabelası, park numaraları, tekerlek takozları, aydınlatma, çiçek şeridi, tente, güvenlik kulübesi, bariyer, ağaçlar, asfalt, hız tümseği, ikinci sıra tente, elektrikli şarj istasyonu, yön okları, lamba bayrakları, kamera, LED zemin şeridi, bütün sıralara tente, altın babalar, VIP park yeri.
- **Ekipmanlar:** atölye bölmeleri, boya kabini, göçük seti arabası, ekspertiz tabelası, müşteri köşesi şemsiyesi ve kamera da sahnede görünür.

### Araç çizimleri

Araçlar kasa tipine göre çizilir. Gövdede ışık, gölge, kapı çizgileri, kollar, ayna, cam yansıması ve marka rozeti vardır. Jantlar sınıfa göre değişir: ekonomi ve ticaride çelik jant, ailede alüminyum, SUV'da arazi, elektrikliyi aerodinamik, premium ve lükste çok kollu, sporda siyah jant ile kırmızı kaliper, klasikte beyaz yanaklı lastik. Spor araçta spoiler ve şerit, SUV'da tavan rayı, lüks ve klasikte krom, elektrikliyi mavi şerit bulunur. Aracın durumu görünür: kaportası kötü araç çizik ve göçüklü (çok kötüyse paslı), boyalı parçası farklı tonda, motoru kötü araç dumanlı, kirli araç çamurlu, yeni yıkanmış araç parıltılıdır.

### Denge ayarı

Her tesisin n. seviyesi için gereken oyuncu seviyesi = başlangıç + yuvarla((n − 1) × 1,15); fiyat = taban × 1,3^(n − 1). Böylece tesisler oyuncunun seviyesiyle birlikte büyür ve 20. seviye oyuncunun 23–25. seviyesine denk gelir. Değerler `www/js/data.js` içindeki `G.FAC_STEP`, `G.FAC_GROWTH` ve her tesisin `start` ile `base` alanlarından değiştirilir.

| Tesis seviyesi | Galeri | Yıkama | Çardak | Otopark |
|---|---|---|---|---|
| 1 | oyuncu sv. 2 · 60 bin ₺ | sv. 1 · 40 bin ₺ | sv. 3 · 50 bin ₺ | sv. 1 · 30 bin ₺ |
| 5 | sv. 7 · 171 bin ₺ | sv. 6 · 114 bin ₺ | sv. 8 · 143 bin ₺ | sv. 6 · 86 bin ₺ |
| 10 | sv. 12 · 636 bin ₺ | sv. 11 · 424 bin ₺ | sv. 13 · 530 bin ₺ | sv. 11 · 318 bin ₺ |
| 15 | sv. 18 · 2,36 milyon ₺ | sv. 17 · 1,57 milyon ₺ | sv. 19 · 1,97 milyon ₺ | sv. 17 · 1,18 milyon ₺ |
| 20 | sv. 24 · 8,77 milyon ₺ | sv. 23 · 5,85 milyon ₺ | sv. 25 · 7,31 milyon ₺ | sv. 23 · 4,39 milyon ₺ |

Basit bir bot oyuncuyla 240 günlük simülasyonda oyuncu 21. seviyeye, tesisler 17–18. seviyeye ulaştı. Tesisler hep seviye kilidinin 0–2 altında ilerledi; yani tempoyu para değil oyuncu seviyesi belirliyor.

## Kolaylıklar

- **İlan filtresi:** segment, sıralama (önerilen, en ucuz, en pahalı, en kârlı, en az km, en yeni model), "sadece fırsatlar" ve "kasama uygun".
- **Günü bitir (⏭):** hız düğmelerinin yanında; onaydan sonra zamanı 19:00'a kadar hızla ilerletir. Atölye, müşteriler ve olaylar normal işler; danışman yoksa cevapsız teklifler kaybolabilir.
- **Oyun hızı:** 1× hızda bir oyun günü yaklaşık 2,5 dakika sürer (`www/js/main.js` → `MIN_PER_SEC`).
- **Bedava araç teklifi:** ilanlara bakarken ekranda 45 saniyeden sonra ara sıra "Reklam izle, bu araç bedava senin" penceresi çıkar (kasa 300 bin ₺'nin altındaysa daha sık; günde en fazla 3 kez gösterilir, 2 kez alınabilir). Araç oyuncunun seviyesine göre seçilir ve pahalı değildir: değeri en fazla 250 bin + seviye × 120 bin ₺ (1. seviyede 370 bin, 10. seviyede 1,45 milyon); lüks, spor ve klasik çıkmaz, ekspertizi temizdir.
- **Gizli kusur oranı:** ilanların yaklaşık %12'sinde (fırsat ilanlarında %21) ekspertizde kusur çıkar.

## Pasif gelir

Oyundan çıkınca galeri çalışmaya devam eder. Oyuncu en az 5 dakika sonra döndüğünde "Hoş geldin patron!" ekranı çıkar ve yokluk süresine göre gelir verilir (en fazla 8 saat birikir). İsteyen reklam izleyip bu gelirin 2 katını alabilir (günde 2 kez).

- Saatlik gelir = son 7 günün ortalama günlük satış kârının %12'si (en az oyuncu seviyesi × 2.000 ₺)
- Her satış danışmanı +%15, şöhret +%50'ye kadar artırır
- Patron → Durum ekranında saatlik pasif gelir ve şimdiye kadar toplanan tutar görünür
- Ayarlar `www/js/engine.js` içinde: `G.OFFLINE_MAX_H` (8 saat), `G.OFFLINE_MIN_MIN` (5 dakika), `G.passiveRate`

## Ultra lüks araçlar

Oyuncu 6. seviyeye gelince ilanlara arada bir ultra lüks koleksiyon aracı düşer: her sabah %12 ihtimalle, gün içinde her ilan saatinde %3 ihtimalle (ortalama 4 günde bir). 6 model var: Ferro Leggenda, Mirage Apex Hiper, Nordik Aurora EV Hiper, Stahl Phantom V12, Volta Speedster '57 ve Anka Kartal Prototip '64. Değerleri 25–70 milyon ₺ arasındadır ve değer kaybetmezler.

- "Koleksiyon satışı" ilanları değerin %72–84'üne çıkar; kâr çoğu zaman 5–15 milyon ₺ olur
- Yalnızca **milyarder koleksiyoncu** müşteriler (bütçe 30–140 milyon ₺) alır; galeride etiketli ultra araç varken gelirler
- İlanda altın çerçeve ve "Ultra lüks" etiketiyle en üstte durur, araçların altın jantları vardır
- "Ultra lüks bir araç sat" görevi 3 milyon ₺ ödül verir

## Lüks alışveriş (Patron → Lüks)

Patron kazandığı parayla kendine ev, araba, uçak, yat ve mücevher alabilir, gelir getiren yatırımlar yapabilir. 83 eşyanın her birinin kendi çizimi, oyuncu seviyesi şartı, şöhret puanı, günlük bakım masrafı ve geri satış değeri vardır.

| Kategori | Sayı | Fiyat aralığı (ucuzdan pahalıya) | Özel fayda | Günlük bakım | Geri satış |
|---|---|---|---|---|---|
| Ev | 13 | Stüdyo daire 4 milyon → mahalle dairesi, bahçeli ev, site, dağ evi, çiftlik evi, rezidans, penthouse, villa, tarihi köşk, yalı, özel ada → Avrupa'da şato 1,4 milyar | En çok şöhreti verir | %0,016 | %95 |
| Araba | 26 | Anka Serçe 2,5 milyon → aile, SUV, elektrikli, premium, spor ve hiper otomobiller → Ferro Leggenda 250 milyon | En iyi araba galerinin önüne "PATRON" plakasıyla park eder | %0,032 | %70 |
| Mücevher | 20 | Gümüş künye 150 bin → bilezik, saatler, küpeler, inci, yakut, pırlanta, antika sorguç, gerdanlık, mücevherli yumurta, pembe elmas, kraliyet tacı → Doğu Yıldızı elması 600 milyon | Değerini korur, sigortası ucuz | %0,01 | %85 |
| Uçak | 9 | Ultra hafif uçak 8 milyon → tek motorlu, amfibi deniz uçağı, helikopter, turboprop, hafif jet, orta boy jet, uzun menzil jet → VIP geniş gövde uçak 1,1 milyar | Her sabah 2–10 ithal ilan; uçak sahnenin gökyüzünde uçar | %0,048 | %75 |
| Yat | 10 | Jet ski 1,5 milyon → balıkçı teknesi, yelkenli, sürat teknesi, katamaran, gulet, flybridge, motoryat, süper yat → mega yat 875 milyon | Haftada bir yat partisi | %0,048 | %70 |
| Yatırım | 5 | Kiralık daire 12 milyon → restoran, butik otel, AVM → futbol takımı 1,5 milyar | **Her gün gelir getirir**, gideri de vardır; oyundan çıkınca pasif gelire eklenir | %0,15 (takım %0,25) | %85 |

Her eşyanın kendi çizimi vardır; seviye şartları da ucuzdan pahalıya doğru artar. Liste `www/js/data.js` → `G.LUXURY`.

**Yatırım gelirleri:** kiralık daire günde 72 bin, restoran 270 bin, butik otel 660 bin, AVM 2,4 milyon ₺ getirir. Futbol takımı (galerinin adını taşır, ör. "Yıldızspor") her gün maça çıkar; galibiyette gelir %40 fazla, yenilgide %40 az olur, sonuç gün sonu haberlerinde görünür. Yatırımların net günlük kazancının dörtte biri, oyundan çıkılan her saat için pasif gelire eklenir. Futbol takımı sahnenin arka planında stadyum olarak görünür.

**Şöhret** (sahip olunan eşyaların puanlarının toplamı) şunları verir: müşterinin ödemeye razı olduğu fiyat +%6'ya kadar, günde +0,5'e kadar itibar, 50 şöhrette bir ve 150 şöhrette iki kademe daha zengin müşteri tipi. Bakım masrafları kişisel gider olarak gün sonu raporunda ayrı satırda görünür ve işletmenin günlük netine sayılmaz. Lüks eşyaların geri satış değeri net değere eklenir. Bütün eşyaların toplam fiyatı yaklaşık 2,85 milyar ₺; geri satış ödenen fiyat üzerinden hesaplanır.

## Reklam modeli

Oyun ücretsiz; gelir Google AdMob reklamlarından gelir. Oyuncuyu reklama boğmamak için kurallar kodda sabit:

- **Banner reklam yok.** Oyun sırasında, pazarlıkta ya da ihalede kendiliğinden reklam açılmaz.
- **Ödüllü reklam** yalnızca oyuncu "Reklam izle" düğmesine basınca açılır. Günde en fazla 12 tane izlenebilir:

| Ödül | Nerede | Günlük hak |
|---|---|---|
| Atölye işini hemen bitir | Atölye ve araç sayfası | 3 |
| Ücretsiz ekspertiz | Araç ve ilan sayfası | 3 |
| Müşteriye çay ikram et: sabrı dolar, +1 pazarlık hakkı | Müşteri sayfası | 3 |
| Müşteri kampanyası: 2 saat iki kat müşteri | Müşteri sekmesi | 2 |
| Piyasaya 4 yeni ilan getir | Araç al → İlanlar | 2 |
| Bedava araç (seviyeye uygun, ekspertizi temiz) | İlanlara bakarken çıkan teklif penceresi | 2 |
| Sponsor bonusu: günün kârına göre nakit | Gün sonu raporu | 1 |

- **Ara reklam** (geçilebilir, tam ekran) yalnızca gün geçişinde çıkar: 4. günden sonra, en fazla 3 günde bir ve o gün oyuncu hiç ödüllü reklam izlemediyse. Yani reklam izleyen oyuncu ara reklam görmez.
- Sınırlar `www/js/engine.js` içindeki `G.AD_CAPS`, `G.AD_DAILY` ve `G.interstitialDue` ile ayarlanır.

Tarayıcıda gerçek reklam gösterilmez; akışı denemek için 5 saniyelik örnek reklam ekranı açılır. Uygulama içinde `www/js/ads.js` otomatik olarak AdMob'u kullanır.

### AdMob kurulumu (yayından önce)

1. [admob.google.com](https://admob.google.com) üzerinden hesap aç, Android ve iOS için birer uygulama ekle.
2. Her platform için bir **ödüllü** ve bir **geçiş (interstitial)** reklam birimi oluştur. Kimlikleri `www/js/ads.js` içindeki `AD_UNITS` alanına yaz ve `TESTING` değerini `false` yap. Geliştirme sırasında test kimlikleriyle kal; kendi reklamına tıklamak hesabın kapatılmasına yol açabilir.
3. AdMob **uygulama kimliğini** (`ca-app-pub-…~…`) ekle:
   - Android: `android/app/src/main/AndroidManifest.xml` içinde `<application>` altına `<meta-data android:name="com.google.android.gms.ads.APPLICATION_ID" android:value="UYGULAMA_KİMLİĞİ"/>`
   - iOS: `ios/App/App/Info.plist` içine `GADApplicationIdentifier`, `SKAdNetworkItems` listesi ve `NSUserTrackingUsageDescription` (ör. "Reklamları sana uygun göstermek için kullanılır.")
4. AdMob panelinde **Gizlilik ve mesajlar** bölümünden GDPR/KVKK onay mesajını ve iOS izin açıklamasını oluştur; oyun açılışta bu formu kendisi gösterir.
5. Sitende `app-ads.txt` dosyasını yayınla ve mağaza sayfasındaki geliştirici web sitesi olarak bu siteyi göster.
6. Mağaza formları: Google Play'de "Uygulama reklam içeriyor" seçeneğini işaretle ve Veri güvenliği formunda reklam kimliğinin toplandığını belirt; App Store'da gizlilik etiketlerinde "Tanımlayıcılar → Cihaz kimliği (reklam)" seç. Gizlilik politikanda AdMob'u adıyla an.

İleride ücretli "Reklamları kaldır" paketi eklenebilir; o zaman `G.interstitialDue` her zaman false döndürmeli, ödüllü reklamlar ise isteğe bağlı kalmalı.

## Dosyalar

```
galeri-patronu/
  www/                 Uygulamanın kendisi (Capacitor bu klasörü paketler)
    index.html         Sayfa yapısı ve bütün stiller
    js/data.js         Modeller, segmentler, müşteri tipleri, konumlar, olaylar, görevler
    js/engine.js       Oyun motoru: değerleme, pazarlık, müşteriler, atölye, ihale, gün sonu
    js/ads.js          Reklam katmanı (AdMob ve tarayıcıdaki örnek reklam)
    js/ui.js           Ekranlar, araç çizimleri, pencereler
    js/scene.js        Galeri sahnesi (bina, park alanı, müşteriler)
    js/main.js         Oyun döngüsü ve kayıt
  capacitor.config.json
  package.json
```

Oyun dengesini değiştirmek için çoğu zaman yalnızca `js/data.js` dosyasına dokunmak yeterli.

## Bilgisayarda çalıştırma

`www/index.html` dosyasını tarayıcıda açman yeterli.

**Tek dosya:** `dist/galeri-patronu.html` bütün oyunu tek bir HTML dosyasında içerir; çift tıklayınca açılır, başka dosyaya ihtiyaç duymaz. `www/` içinde değişiklik yaptıktan sonra `python3 build-single.py` ile yeniden üretilir. Site sunucuya yüklendiğinde `/galeri-patronu/www/` adresinden de açılır.

## App Store ve Google Play'e çıkış

### 1. Hazırlık (bir kez)

- **Google Play geliştirici hesabı**: tek seferlik 25 $. Yeni kişisel hesaplarda, uygulamayı yayına almadan önce en az 12 test kullanıcısıyla 14 gün kapalı test yapma şartı var.
- **Apple Developer Program**: yıllık 99 $. iOS sürümünü derlemek için Xcode yüklü bir Mac gerekir. Mac yoksa Codemagic ya da Ionic Appflow gibi bulut derleme servisleri kullanılabilir.
- Bilgisayara [Node.js](https://nodejs.org) (LTS), Android için [Android Studio](https://developer.android.com/studio) kur.

### 2. Uygulama projelerini oluştur

```bash
cd galeri-patronu
npm install
npm run add:android
npm run add:ios        # yalnızca Mac'te
npm run sync           # www klasöründeki her değişiklikten sonra
npm run open:android   # Android Studio açılır, oradan çalıştır / imzala
npm run open:ios       # Xcode açılır
```

`capacitor.config.json` içindeki `appId` (`com.tunahan.galeripatronu`) mağazadaki kalıcı kimliktir; yayından önce sana ait bir alan adıyla değiştir, sonra değiştirilemez.

### 3. Yayından önce yapılacaklar

- [ ] **Uygulama ikonu ve açılış ekranı**: 1024×1024 ikon ve 2732×2732 açılış görseli hazırlayıp `npx @capacitor/assets generate` ile bütün boyutları üret.
- [ ] **Yazı tiplerini pakete ekle**: şu an Google Fonts'tan yükleniyor; internet yokken yedek yazı tipine düşer. `.woff2` dosyalarını `www/fonts/` altına koyup `@font-face` ile bağla.
- [ ] **Kayıt**: oyun `localStorage` kullanıyor. Uygulamada daha güvenli olması için `@capacitor/preferences` eklentisine geçir (iOS, alan daraldığında web depolamasını silebilir).
- [ ] **Gizlilik politikası**: iki mağaza da bir gizlilik politikası adresi ister. Oyun şu an hiçbir kişisel veri toplamıyor; bunu belirten kısa bir sayfa yeterli.
- [ ] **Yaş derecelendirmesi** formlarını doldur (şiddet, kumar vb. yok).
- [ ] **Mağaza görselleri**: telefon ve tablet ekran görüntüleri, kısa ve uzun açıklama.
- [ ] Yukarıdaki AdMob kurulum adımları.

### Marka adları

Oyundaki bütün marka ve model adları (Anka, Ferro, Kaizen, Volta, Stahl...) hayalidir. Gerçek marka adı ve logo kullanmak mağaza reddine ve hukuki soruna yol açabilir; yeni araç eklerken de hayali isim kullan.
