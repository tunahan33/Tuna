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

- **Mağaza:** 23 fotoğraflı ürün (boş kategoriler menüde otomatik gizlenir) (en düşük fiyat 2.549,90 ₺; panel 2.500 ₺ altını kabul etmez), net KDV dâhil fiyatlar,
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

## 5. Sunucuya kurulum (VPS, Ubuntu 24.04)

1. Sunucuya bağlanın. Windows'ta **PowerShell** açıp: `ssh root@SUNUCU_IP` (ilk bağlantıda `yes` yazın, sonra şifreyi girin;
   şifre yazarken ekranda görünmez). Alternatif: hosting panelindeki **Konsol / VNC** düğmesi.
2. Şu iki komutu çalıştırın:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/tunahan33/Tuna/claude/confident-ride-wpz20g/deploy/kurulum.sh -o kurulum.sh
   bash kurulum.sh
   ```
   Apache, PHP, güvenlik duvarı, fail2ban, otomatik güvenlik güncellemeleri ve gece yedeği kurulur. Sonunda süper admin
   e-postası ve şifresi sorulur. Demo siparişler ve test hesapları (`@gssportif.local`) silinir; ürünler, sayfalar ve
   firma bilgileri kalır.
3. Site `http://SUNUCU_IP` adresinde açılır, panel `http://SUNUCU_IP/admin`.
4. **root şifresini değiştirin:** `passwd`

| İş | Komut (sunucuda) |
|---|---|
| Yeni sürümü yükle (veriler ve fotoğraflar korunur, önce yedek alınır) | `bash kurulum.sh guncelle` |
| Alan adı yönlendikten sonra ücretsiz SSL (https) | `bash kurulum.sh ssl` |
| Elle yedek al | `bash kurulum.sh yedek` |

Yedekler `/var/backups/gssportif/` klasöründedir (her gece 03:30, son 14 gün).
Alan adı: **gssportifurunler.net** (Natro). Natro > Alan Adı Yönetimi > DNS Yönetimi’nde `@` ve `www` için **A kaydı** = sunucu IP’si olmalı; DNS hazırsa kurulum SSL’i kendisi kurar, değilse yayıldıktan sonra `bash kurulum.sh ssl` çalıştırın.

## 6. Ödeme (PayTR)

Ödeme yöntemi panelden seçilir: **Mağaza Ayarları → Ödeme (PayTR)**. Varsayılan **Demo**'dur (gerçek çekim yapılmaz).

1. PayTR Mağaza Paneli'nden **Mağaza No (merchant_id)**, **Merchant Key** ve **Merchant Salt** bilgilerini alın, panele girin.
2. PayTR Mağaza Paneli'nde **Bildirim URL** olarak `https://www.gssportifurunler.net/paytr-bildirim.php` adresini tanımlayın.
3. Ödeme yöntemini **PayTR** yapın, **TEST modu** açıkken PayTR'nin test kartlarıyla bir sipariş deneyin.
4. Sorunsuzsa TEST modunu kapatın; canlı ödeme başlar.

Akış: müşteri sözleşmeleri onaylar → sipariş **Ödeme Bekleniyor** olarak açılır → PayTR'nin güvenli ödeme formu (iframe) açılır,
kart bilgileri doğrudan PayTR'ye girilir → PayTR sonucu `paytr-bildirim.php`'ye imzalı olarak bildirir → sipariş **Yeni Sipariş** olur
ve stok düşer. Başarısız ödemede müşteri **Tekrar Dene** ile yeniden ödeyebilir. Kart bilgisi sitede hiçbir zaman işlenmez veya saklanmaz.

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
