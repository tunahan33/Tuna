# GS Projeler — Spor Danışmanlık Sitesi

Sarı · kırmızı · antrasit temalı, 7 spor danışmanlığı alanı ve 21 net fiyatlı paket içeren; Garanti BBVA Sanal POS (3D Secure) entegrasyonlu satış sitesi ve rol bazlı yönetim paneli.

**Teknoloji:** PHP 8.1+ ve SQLite. Ayrı bir veritabanı kurulumu gerekmez; standart bir cPanel/Plesk hostingde çalışır.

## Kurulum (5 dakika)

1. Tüm dosyaları hostinginizin `public_html` klasörüne yükleyin. Apache'de `mod_rewrite` açık olmalı; `.htaccess` dosyası hazır olarak gelir.
2. `data/` klasörünün yazılabilir olduğundan emin olun (izin 755 veya 775).
3. Alan adınıza **SSL sertifikası** kurun. Sanal POS için zorunludur; site HTTP isteklerini otomatik olarak HTTPS'e yönlendirir.
4. Siteyi açın. İlk açılışta **Kurulum** ekranı gelir; buradan **Süper Admin** hesabını oluşturun.
5. **Yönetim > Site & Sanal POS Ayarları** ekranından şirket unvanı, adres, vergi no, MERSİS gibi bilgileri girin. Bu bilgiler footer'a, sözleşmelere ve yasal sayfalara otomatik olarak işlenir.

Yerelde denemek için: `php -S localhost:8000 index.php`

## Roller ve yetkiler

| Yetki | Süper Admin | Admin | Editör | Satış Temsilcisi |
|---|:-:|:-:|:-:|:-:|
| Siparişleri görme, durum güncelleme ve not ekleme | ✓ | ✓ | — | ✓ |
| İptal / iade işaretleme | ✓ | ✓ | — | — |
| Müşteri listesi, mesajlar, destek kayıtları | ✓ | ✓ | — | ✓ |
| Danışmanlık, paket ve sayfa içeriklerini düzenleme | ✓ | ✓ | ✓ | — |
| Fiyat değiştirme, yeni paket/hizmet ekleme | ✓ | ✓ | — | — |
| Kullanıcı yönetimi | ✓ (tüm roller) | ✓ (Editör, Satış, Üye) | — | — |
| **Günlük satış ve siparişler (gün gün), CSV** | ✓ | — | — | — |
| **Aktivite akışı: kim, ne zaman, ne yaptı (canlı)** | ✓ | — | — | — |
| **Site ve Sanal POS ayarları** | ✓ | — | — | — |

Güncel tablo panelde **Yetki Tablosu** sayfasında da görünür. Girişler, hatalı giriş denemeleri, siparişler, ödemeler, fiyat değişiklikleri, içerik düzenlemeleri, rol değişiklikleri ve form gönderimleri IP adresiyle birlikte kaydedilir.

## Garanti BBVA Sanal POS başvurusu — kontrol listesi

Bankanın site incelemesinde aranan maddeler sitede hazır:

- [x] Hakkımızda sayfasında ticari unvan, adres, vergi dairesi ve no, MERSİS, ticaret sicil, KEP
- [x] Footer'da şirket bilgileri, iletişim bilgileri ve Visa / Mastercard / Troy / 3D Secure / SSL logoları
- [x] **Mesafeli Satış Sözleşmesi** ve **Ön Bilgilendirme Formu**: ödemede zorunlu onay kutusu var; müşteri bilgileriyle doldurulmuş hali siparişe kaydedilir
- [x] İade ve iade çeki koşulları (14 gün cayma hakkı), teslimat koşulları (hizmetin nasıl ifa edildiği)
- [x] KVKK Genel Aydınlatma Metni, İlgili Kişi Başvuru Formu, Çerez Politikası ve Çerez Tercihleri, Gizlilik Politikası, Üyelik Sözleşmesi
- [x] Güvenli alışveriş sayfası; kart bilgileri sitede saklanmaz
- [x] Tüm fiyatlar TL ve KDV dahil; her paketin açıklaması ve kapsamı net
- [x] Çalışan sepet ve ödeme akışı, sipariş takibi, destek (arıza) kaydı
- [ ] **Sizin yapmanız gerekenler:** SSL sertifikası kurmak, Ayarlar'daki şirket bilgilerini gerçek bilgilerle doldurmak, sözleşme metinlerini hukuk danışmanınıza kontrol ettirmek

### Banka bilgileri geldikten sonra

Süper Admin > **Site & Sanal POS Ayarları**:

1. Terminal ID, Üye İşyeri No, PROVAUT şifresi ve 3D Store Key bilgilerini girin.
2. Modu **Test** yapın ve Garanti test kartıyla bir sipariş deneyin.
3. Ekranda yazan dönüş adresini (`https://alanadiniz/odeme/sonuc`) bankaya bildirin.
4. Test başarılı olursa modu **Canlı** yapın.

Varsayılan model **3D_OOS_PAY**'dir: kart bilgisi bankanın ödeme sayfasında girilir, bu yüzden PCI yükü en düşük seçenektir. İsterseniz **3D_PAY** modelini seçip kart formunu sitede gösterebilirsiniz; bu durumda da kart bilgileri doğrudan bankaya gönderilir.

Banka bilgileri girilene kadar site **Demo** modunda çalışır. Bu modda ödeme ekranı yalnızca akışı test etmek içindir ve karttan para çekilmez.

## Klasör yapısı

```
index.php            Yönlendirici (tüm istekler buradan geçer)
app/core.php         Veritabanı, oturum, roller/yetkiler, aktivite kaydı
app/seed.php         Başlangıç içerikleri (hizmetler, paketler, yasal metinler)
app/garanti.php      Garanti BBVA 3D Secure entegrasyonu
app/pages/           Site sayfaları
app/admin/           Yönetim paneli
assets/              CSS, JS, logo
data/                SQLite veritabanı (web'den erişime kapalı; yedeğini alın)
```

**Yedekleme:** `data/gsprojeler.sqlite` dosyasını düzenli olarak yedekleyin. Tüm veriler bu dosyadadır.
