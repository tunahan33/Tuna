# GS Sportif Ürünler — E-ticaret Sitesi

Sarı · kırmızı · antrasit temalı spor ürünleri mağazası ve rol bazlı yönetim paneli.
**PHP + SQLite** ile yazıldı: kurulum sihirbazı, veritabanı sunucusu, Composer veya Node.js gerekmez.
Site ilk açıldığında veritabanı (`data/gs.sqlite`) kendiliğinden oluşur ve demo verilerle dolar.

---

## 1. Bilgisayarınızda açıp görmek (yayınlamadan)

### Bir kereye mahsus kurulum
1. **Visual Studio Code**'u kurun: https://code.visualstudio.com
2. **PHP**'yi kurun (8.1 veya üzeri):
   - **Windows:** Başlat menüsünde *PowerShell* açın ve şunu yazın:
     `winget install PHP.PHP.8.3`
     (Sonra bilgisayarı yeniden başlatın ya da VS Code'u kapatıp açın.)
   - **Mac:** Terminal'de `brew install php` (Homebrew yoksa: https://brew.sh)
   - Kontrol: terminalde `php -v` yazınca sürüm görünmeli.

### Her seferinde siteyi açmak
1. VS Code → **File → Open Folder…** → bu proje klasörünü seçin.
2. Aşağıdakilerden **biri**:
   - **En kolayı:** `Ctrl + Shift + B` (Mac: `Cmd + Shift + B`) → *Siteyi Başlat* görevi çalışır.
   - veya VS Code menüsünden **Terminal → New Terminal** açıp yazın: `php -S localhost:8000 router.php`
   - veya (Windows) klasördeki **`baslat.bat`** dosyasına çift tıklayın — tarayıcı da otomatik açılır.
3. Tarayıcıda açın:
   - Mağaza: **http://localhost:8000**
   - Yönetim paneli: **http://localhost:8000/admin**
4. Durdurmak için terminalde `Ctrl + C`.

### Düzenleme yaparken
Siteyi çalışır halde bırakın, VS Code'da bir dosyayı değiştirip kaydedin (`Ctrl + S`), tarayıcıda sayfayı yenileyin (`F5`).
Değişiklik anında görünür. Hiçbir şey internete gitmez; site yalnızca sizin bilgisayarınızda çalışır.

| Ne değiştirmek istiyorsunuz? | Nereden? |
|---|---|
| Ürünler, fiyatlar, stok, açıklamalar | Panel → **Ürünler** (kod gerekmez) |
| Ürün fotoğrafları | Panel → **Ürünler** → ürünü açın → **Fotoğraflar**: sürükleyip bırakın veya seçin (sınırsız sayıda), kapak seçin, sıralayın, silin |
| Ürün açıklaması, sayfa ve sözleşme metinleri | Word benzeri editörle: Başlık, Kalın, Liste, Bağlantı düğmeleri. Kod bilmeye gerek yok |
| Sözleşmeler, SSS, Hakkımızda vb. metinler | Panel → **Sayfalar & Sözleşmeler** |
| Firma unvanı, adres, telefon, vergi no, kargo ücreti | Panel → **Mağaza Ayarları** (Süper Admin) |
| Renkler | `assets/css/site.css` en üstteki `:root` bölümü |
| Altbilgi (footer) bağlantıları | `app/footer.php` |
| Logo | `assets/img/logo.svg` (koyu zemin için `logo-light.svg`) |
| Rollerin yetkileri | `app/auth.php` → `PERMISSIONS` tablosu |

Yeni sürüm indirdiğinizde veritabanını silmenize gerek yoktur: eksik tablolar ve yeni ürünler site açılırken otomatik eklenir
(`app/migrate.php`), siparişleriniz ve değişiklikleriniz korunur.

**Her şeyi baştan başlatmak** (demo verilere dönmek): `Terminal → Run Task… → Veritabanını Sıfırla`
ya da `php app/sifirla.php`. *Dikkat: tüm siparişler ve değişiklikler silinir.*

---

## 2. Demo hesaplar

| Rol | E-posta | Şifre |
|---|---|---|
| **Süper Admin** | admin@gssportif.local | Admin123! |
| Admin | yonetici@gssportif.local | Test1234! |
| Editör | editor@gssportif.local | Test1234! |
| Satış Temsilcisi | satis@gssportif.local | Test1234! |
| Üye (müşteri) | uye@gssportif.local | Test1234! |

Deneme ödemesi için kart: **4242 4242 4242 4242**, ileri bir tarih (örn. 12/30), herhangi bir CVV.
Yayına almadan önce bu şifreleri **mutlaka** değiştirin (Panel → Kullanıcılar).

---

## 3. Roller ve yetkiler

| Yetki | Süper Admin | Admin | Editör | Satış Temsilcisi |
|---|:-:|:-:|:-:|:-:|
| Siparişleri görme, üstlenme, hazırlama, kargoya verme | ✓ | ✓ | – | ✓ |
| İptal / iade (stok otomatik geri eklenir), temsilci atama | ✓ | ✓ | – | – |
| Sipariş silme | ✓ | – | – | – |
| Müşteriler, talepler (iletişim, arıza, KVKK, İK) | ✓ | ✓ | – | ✓ |
| Ürün metinleri, sayfa ve sözleşme metinleri | ✓ | ✓ | ✓ | – |
| Ürün ekleme/silme, fiyat, stok, kategoriler | ✓ | ✓ | – | – |
| Kullanıcı yönetimi | ✓ (herkes) | ✓ (editör, satış, üye) | – | – |
| **Günlük satış raporu** (gün gün, sipariş sipariş, Excel) | ✓ | – | – | – |
| **Aktivite akışı** (kim, ne zaman, ne yaptı) | ✓ | – | – | – |
| **Bugünün siparişleri ve ciro** (gösterge paneli) | ✓ | – | – | – |
| Mağaza ayarları | ✓ | – | – | – |

Yalnızca Süper Admin'e açık menüler panelde **SA** etiketiyle işaretlidir.

---

## 4. Sitede neler var?

- **Mağaza:** 11 kategori (Tişört & Atlet, Kadın, Motorsport dâhil), 41 ürün (en düşük fiyat 2.549,90 ₺; panel 2.500 ₺ altını kabul etmez), net KDV dâhil fiyatlar,
  ürüne tıklayınca fotoğraf galerisi ve açıklama / özellikler / kargo-iade sekmeleri, beden seçimi, stok uyarısı, arama, sıralama, indirimler.
- **Ürün fotoğrafları:** GS Jogger Eşofman Altı, GS Hakiki Deri Kemer, GS Heritage FG Krampon ve GS Antrenman Şortu gerçek
  fotoğraflarıyla gelir (`assets/urunler/`). Panelden yüklenen fotoğraflar kareye tamamlanıp küçültülerek `uploads/` klasörüne
  kaydedilir. Fotoğrafı olmayan ürünlerde marka renklerinde bir çizim gösterilir.
- **Sepet ve ödeme:** ücretsiz kargo çubuğu → teslimat bilgileri → müşterinin bilgileriyle doldurulmuş
  **Ön Bilgilendirme Formu** ve **Mesafeli Satış Sözleşmesi** onayı → kart bilgileri → sipariş. Ödemede stoktan düşülür.
- **Altbilgi (görseldeki tasarımın birebir aynısı), hepsi tıklanabilir ve dolu:**
  - KURUMSAL: Hakkımızda, İnsan Kaynakları (başvuru formlu)
  - MÜŞTERİ HİZMETLERİ: Sıkça sorulan sorular, Sipariş takibi (sipariş no + e-posta ile sorgu), Arıza takibi
    (kayıt açma ve kayıt numarasıyla sorgulama), İade ve iade çeki koşulları, Teslimat koşulları, Güvenli alışveriş, İletişim
  - SÖZLEŞMELER VE YASAL: Üyelik sözleşmesi, Genel Aydınlatma metni, Çerez politikası, Çerez tercihleri (açma/kapama
    anahtarlı), İlgili kişi başvuru formu (KVKK m.11)
  - Alt satırda: Mesafeli Satış Sözleşmesi, Ön Bilgilendirme Formu, Gizlilik Politikası
- **Üyelik:** kayıt, giriş, Hesabım (siparişler ve durumları, bilgi ve şifre değiştirme).

> Not: Yasal metinler genel şablondur. Yayına almadan önce bir hukukçuya kontrol ettirmeniz önerilir.
> Firma bilgileri (Panel → Mağaza Ayarları) girildiğinde tüm sözleşmelere otomatik yansır.

---

## 5. Yayına alırken (alan adı ve hosting gelince)

Ödeme şu an **DEMO** modundadır: kart doğrulanır ama para çekilmez, kart bilgisi hiçbir yerde saklanmaz.
Gerçek satış için bankanızın sanal POS'u veya bir ödeme kuruluşu (iyzico, PayTR vb.) entegrasyonu `odeme.php` içine eklenir.

Hosting gereksinimi: PHP 8.1+, `pdo_sqlite` eklentisi (hemen hepsinde açıktır), SSL. Dosyaları yükleyip
`data/` ve `uploads/` klasörlerinin yazılabilir olduğundan emin olmanız yeterlidir; `.htaccess` dosyaları `app/` ve `data/` klasörlerini dışarıya kapatır.
Alan adı ve hosting bilgileri geldiğinde Panel → Mağaza Ayarları'ndaki **Yayına Hazırlık Kontrolü** listesini tamamlayın.

## Klasör yapısı

```
index.php, urunler.php, urun.php        Ana sayfa, ürün listesi, ürün detayı
sepet.php, odeme.php, siparis-tamam.php Sepet ve ödeme
giris.php, kayit.php, hesabim.php       Üyelik
sayfa.php                               Kurumsal/yasal sayfalar (panelden düzenlenir)
siparis-takibi.php, ariza-takibi.php    Sipariş ve arıza takibi
iletisim.php, insan-kaynaklari.php      Formlar
cerez-tercihleri.php, basvuru-formu.php Çerez tercihleri, KVKK başvurusu
admin/                                  Yönetim paneli
app/                                    Çekirdek: veritabanı, yetkiler, demo veriler, üst/alt bölüm
assets/                                 CSS, JavaScript, logo, hazır ürün fotoğrafları (assets/urunler)
uploads/                                Panelden yüklenen ürün fotoğrafları (git'e eklenmez)
data/                                   Veritabanı dosyası (otomatik oluşur, git'e eklenmez)
```
