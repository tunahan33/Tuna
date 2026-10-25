# Galeri Patronu

İkinci el araç galerisi işletme oyunu. Sanayideki toprak arsada tek araçla başlarsın; araç alır, hazırlar, pazarlıkla satar ve galerini otomotiv merkezine kadar büyütürsün.

Oyun saf HTML, CSS ve JavaScript ile yazıldı (kütüphane yok). Aynı kod tarayıcıda çalışır ve [Capacitor](https://capacitorjs.com) ile iOS ve Android uygulamasına dönüştürülür.

## Oyun sistemleri

| Sistem | Ne yapar |
|---|---|
| **Araçlar** | 9 segment (ekonomi, aile, ticari, SUV, elektrikli, premium, spor, klasik, lüks), 39 hayali model, 7 kasa tipi. Her aracın yılı, kilometresi, yakıtı, vitesi, rengi, motor/kaporta/iç durumu, boyalı ve değişen parça sayısı, tramer kaydı ve temizliği var. |
| **Değerleme** | Piyasa değeri yaşa, kilometreye, duruma, boya/değişene, tramere, renge, yakıta, vitese ve segment trendine göre hesaplanır. Klasik araçlar yaşla değer kaybetmez, restorasyonla çok kazanır. |
| **Gizli kusurlar** | Şanzıman, conta, turbo, batarya, pas, elektrik arızası, beyan edilmemiş boya, düşürülmüş kilometre, sel hasarı. Ekspertiz yaptırmadan alınan araçta çıkabilir; kusuru bilinmeden satılan araç müşteri şikâyeti, tazminat ve itibar kaybı getirir. |
| **Araç alımı** | Günlük ilanlar (sahibinden, galeriden, filo çıkışı, acil satılık, ağır hasarlı) ve satıcıyla mesajlaşarak pazarlık. Her gün canlı açık artırma: rakip galericilerle saniye saniye teklif yarışı. |
| **Atölye** | Detaylı temizlik, periyodik bakım, motor revizyonu, kaporta-boya, boyasız göçük düzeltme, döşeme yenileme, arıza giderme, klasiklerde tam restorasyon. Kendi atölyen yoksa işler pahalı ve yavaş dış servise gider. Boya kaportayı düzeltir ama boyalı parça sayısını artırır. |
| **Müşteriler** | 12 müşteri tipi (öğrenci, esnaf, aile, doktor, koleksiyoncu, iş insanı...). Her birinin bütçesi, istediği segment, pazarlık payı ve boya/değişen hassasiyeti farklı. Kimi kendi ekspertizini yaptırır, kimi takasa araç verir. Teklifi kabul et, karşı teklif ver ya da reddet. |
| **Büyüme** | 5 konum (sanayi arsası → mahalle galerisi → cadde showroom → oto plaza → otomotiv merkezi), 8 geliştirme (yıkama, atölye bölmeleri, boya kabini, göçük seti, ekspertiz cihazı, kapalı vitrin, müşteri köşesi, kamera-alarm), 4 personel rolü, 4 reklam paketi, banka kredisi. |
| **Piyasa** | Segment fiyatları her gün oynar; akaryakıt zammı, bayram, kur artışı, ÖTV, faiz indirimi, kar, klasik otomobil fuarı gibi olaylar talebi değiştirir. Dolu, hırsızlık ve vergi denetimi gibi riskler var. |
| **İlerleme** | Seviye ve XP, segment kilitleri, 17 görev ve ödülleri, günlük kâr-zarar raporu ve 14 günlük grafik, otomatik kayıt. |
| **Galeri görünümü** | Galeri ekranının üstünde galerinin önden çizimi: bina konuma göre değişir (konteyner ofis, mahalle dükkânı, cam showroom, plaza, otomotiv merkezi). Alınan atölye, yıkama, vitrin, kamera ve müşteri köşesi sahnede belirir. Etiketli araçlar parkta, pazarlık eden müşteriler aracın yanında durur; gökyüzü gün içinde akşama döner. Sahnedeki araca, müşteriye ya da binaya dokununca ilgili ekran açılır. |
| **Sade kullanım** | "Şimdi ne yapmalı?" kartı sıradaki adımı söyler. Fiyat üç hazır seçenekle (hızlı satış, dengeli, kârlı) tek dokunuşta asılır; araç sayfası en kârlı atölye işini önerir; müşteriye "kabul et / biraz daha iste / etikette diren" düğmeleriyle cevap verilir. Ayrıntılar ve kaydırıcılar katlanır bölümlerde durur. |

Bir oyun günü (09:00–19:00) normal hızda yaklaşık 1 dakika sürer; 2× ve 4× hız ile duraklatma var.

## Reklam modeli

Oyun ücretsiz; gelir Google AdMob reklamlarından gelir. Oyuncuyu reklama boğmamak için kurallar kodda sabit:

- **Banner reklam yok.** Oyun sırasında, pazarlıkta ya da ihalede kendiliğinden reklam açılmaz.
- **Ödüllü reklam** yalnızca oyuncu "Reklam izle" düğmesine basınca açılır. Günde en fazla 10 tane izlenebilir:

| Ödül | Nerede | Günlük hak |
|---|---|---|
| Atölye işini hemen bitir | Atölye ve araç sayfası | 3 |
| Ücretsiz ekspertiz | Araç ve ilan sayfası | 3 |
| Müşteriye çay ikram et: sabrı dolar, +1 pazarlık hakkı | Müşteri sayfası | 3 |
| Müşteri kampanyası: 2 saat iki kat müşteri | Müşteri sekmesi | 2 |
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

`www/index.html` dosyasını tarayıcıda açman yeterli. Site sunucuya yüklendiğinde `/galeri-patronu/www/` adresinden de açılır.

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
