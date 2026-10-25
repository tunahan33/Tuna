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

Bir oyun günü (09:00–19:00) normal hızda yaklaşık 1 dakika sürer; 2× ve 4× hız ile duraklatma var.

## Dosyalar

```
galeri-patronu/
  www/                 Uygulamanın kendisi (Capacitor bu klasörü paketler)
    index.html         Sayfa yapısı ve bütün stiller
    js/data.js         Modeller, segmentler, müşteri tipleri, konumlar, olaylar, görevler
    js/engine.js       Oyun motoru: değerleme, pazarlık, müşteriler, atölye, ihale, gün sonu
    js/ui.js           Ekranlar, araç çizimleri, pencereler
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
- [ ] Gelir modeli kararı: ücretli, reklamlı (ör. AdMob ödüllü reklam) ya da uygulama içi satın alma. Reklam ve satın alma eklenecekse ilgili Capacitor eklentileri ve mağaza ayarları gerekir.

### Marka adları

Oyundaki bütün marka ve model adları (Anka, Ferro, Kaizen, Volta, Stahl...) hayalidir. Gerçek marka adı ve logo kullanmak mağaza reddine ve hukuki soruna yol açabilir; yeni araç eklerken de hayali isim kullan.
