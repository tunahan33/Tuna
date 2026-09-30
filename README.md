# GS Projeler – Spor Danışmanlık Sitesi

Sarı · kırmızı · antrasit temalı, Garanti BBVA Sanal POS uyumlu spor danışmanlık sitesi ve rol bazlı yönetim paneli.
Saf PHP 8 + MySQL ile yazılmıştır; framework, Composer veya Node.js gerekmez. Her paylaşımlı hostinge (cPanel, Plesk, DirectAdmin) kurulabilir.

## İçerik

**Site (ziyaretçi tarafı)**
- 7 danışmanlık hizmeti, her birinde 3 paket (toplam 21 paket), KDV dahil net fiyatlar
- Her hizmet ve paket tıklanabilir; paket kartına tıklayınca detaylı açıklama penceresi açılır
- Üyelik, giriş, şifremi unuttum, hesabım (siparişlerim)
- 3 adımlı ödeme: Fatura bilgileri → Ön bilgilendirme + mesafeli satış sözleşmesi onayı → Garanti 3D Secure ödeme
- Garanti başvurusu için gerekenler: Hakkımızda, Mesafeli Satış Sözleşmesi, Ön Bilgilendirme Formu, İptal ve İade Koşulları,
  Teslimat ve Hizmet Koşulları, Gizlilik Politikası, KVKK Aydınlatma Metni, Çerez Politikası, Üyelik Sözleşmesi, İletişim sayfası,
  altbilgide firma ünvanı/adres/vergi/MERSİS bilgileri, kart logoları ve güvenli ödeme ibaresi

**Yönetim paneli (`/admin`)**

| Yetki | Süper Admin | Admin | Editör | Satış Temsilcisi | Üye |
|---|:-:|:-:|:-:|:-:|:-:|
| Panele giriş | ✓ | ✓ | ✓ | ✓ | – |
| Siparişleri görme, durum güncelleme, not | ✓ | ✓ | – | ✓ | – |
| İptal / iade / temsilci atama | ✓ | ✓ | – | – | – |
| Sipariş silme | ✓ | – | – | – | – |
| Müşteriler, mesajlar | ✓ | ✓ | – | ✓ | – |
| Hizmet ve paket metinleri, sayfalar | ✓ | ✓ | ✓ | – | – |
| Hizmet/paket ekleme, silme, fiyat | ✓ | ✓ | – | – | – |
| Kullanıcı ve rol yönetimi | ✓ (herkes) | ✓ (editör, satış, üye) | – | – | – |
| **Günlük sipariş ve satış raporu (gün gün, tek tek, Excel)** | ✓ | – | – | – | – |
| **Aktivite akışı (kim, ne zaman, ne yaptı)** | ✓ | – | – | – | – |
| **Site ve sanal POS ayarları** | ✓ | – | – | – | – |

Yetkiler `includes/auth.php` dosyasındaki `PERMISSIONS` tablosundan değiştirilebilir.

## VS Code ile açma ve bilgisayarda çalıştırma

1. VS Code'da **File → Open Folder** ile proje klasörünü açın (önerilen eklentiler otomatik önerilir).
2. Bilgisayarınızda PHP 8.1+ kurulu olmalı (Windows için XAMPP ya da `winget install PHP.PHP`).
3. VS Code terminalinde:
   ```bash
   php install/cli.php        # SQLite ile yerel kurulum yapar
   php install/demo.php       # (isteğe bağlı) örnek personel ve siparişler ekler
   php -S localhost:8000      # siteyi başlatır
   ```
4. Tarayıcıda http://localhost:8000 → Panel: http://localhost:8000/admin
   - Süper admin: `admin@gsprojeler.local` / `Admin123!`
   - Demo verisi eklendiyse: `yonetici@`, `editor@`, `satis@`, `uye@gsprojeler.local` – şifre `Test1234!`

## Hostinge kurulum

1. cPanel → **MySQL Veritabanları**: yeni veritabanı ve kullanıcı oluşturun, kullanıcıya tüm yetkileri verin.
2. Tüm dosyaları `public_html` klasörüne yükleyin (Dosya Yöneticisi'nden zip yükleyip açabilir veya FTP kullanabilirsiniz).
   `storage/` klasöründeki `database.sqlite` ve kök dizindeki `config.php` yerel dosyalardır, **yüklemeyin**.
3. Tarayıcıda `https://alanadiniz.com/install/` adresine gidin, veritabanı bilgilerini ve süper admin hesabını girin.
4. Kurulum bitince **`install` klasörünü silin**. (Silinmese bile, `config.php` oluştuğu için sihirbaz tekrar çalışmaz.)
5. SSL aktifse `.htaccess` içindeki https yönlendirme satırlarının başındaki `#` işaretini kaldırın.
6. Panel → **Site & Ödeme Ayarları → Firma Bilgileri**: ünvan, adres, telefon, vergi dairesi/no, MERSİS, KEP bilgilerini girin.
   Tüm sözleşmeler ve sayfa altı bu bilgilerle otomatik dolar. Sağdaki **Garanti Sanal POS Başvuru Kontrolü** listesi
   eksikleri gösterir.

## VPS kurulumu (gsprojeler.com.tr)

Boş bir Ubuntu 22.04 / 24.04 sunucuyu tek komutla canlı siteye çevirir: Apache, PHP, MariaDB, Let's Encrypt SSL, güvenlik duvarı (UFW),
fail2ban, otomatik güvenlik güncellemeleri ve her gece veritabanı yedeği. Site ve süper admin hesabı terminalden kurulur;
kurulum sihirbazı internete hiç açılmaz.

1. Sunucu panelinden işletim sistemi olarak **Ubuntu 24.04 LTS** kurun.
2. Natro > Alan Adı > **DNS Yönetimi**: `@` ve `www` için **A kaydı** = sunucu IP'si (mevcut park/yönlendirme kayıtlarını silin).
3. Sunucuya bağlanın (Windows PowerShell / Mac Terminal): `ssh root@SUNUCU_IP`
4. Çalıştırın:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/tunahan33/Tuna/claude/dreamy-carson-mpbcx7/deploy/vps-kurulum.sh -o kurulum.sh
   bash kurulum.sh
   ```
   Süper admin adı, e-postası ve şifresi sorulur. DNS hazırsa SSL de otomatik kurulur.
5. DNS kurulum sırasında hazır değilse, yayıldıktan sonra: `bash kurulum.sh ssl`
6. Sonraki kod güncellemeleri: `bash kurulum.sh guncelle` (önce yedek alır; ayarlar ve veritabanı korunur).

Veritabanı şifresi sunucuda `/root/gsprojeler-bilgiler.txt`, gece yedekleri `/var/backups/gsprojeler/` klasöründedir.
VPS'te e-posta sunucusu yoktur: `info@gsprojeler.com.tr` için bir e-posta hizmeti (Natro, Yandex, Zoho, Google Workspace vb.)
açıp bilgilerini panelde **E-posta (SMTP)** sekmesine girin.

## Alan adı olmadan hosting hazırlığı

Site, alan adı alınmadan da hostinge kurulup denenebilir. Alan adı gelince tek ayar değiştirilir.

**Önerilen hosting özellikleri:** Linux + cPanel (veya Plesk/DirectAdmin), PHP 8.1 veya üzeri, MySQL/MariaDB, ücretsiz SSL (Let's Encrypt / AutoSSL),
e-posta hesabı, FTP erişimi. Yaklaşık 1 GB alan ve 1 veritabanı yeterlidir.

1. **Geçici adres:** Hosting firmaları alan adı yokken genelde geçici bir adres verir (örn. `http://sunucu-ip/~kullanici/`
   veya `firma-sunucu.hosting.com`). Kurulum sihirbazı bu adresi otomatik algılar.
2. **Kurulum:** Yukarıdaki “Hostinge kurulum” adımlarını geçici adresle uygulayın. Siteyi gerçek MySQL ile uçtan uca test edebilirsiniz
   (ödeme DEMO modunda çalışır).
3. **E-posta:** cPanel > E-posta Hesapları'ndan bir hesap açın. Panel > Site & Ödeme Ayarları > **E-posta (SMTP)** sekmesine girip
   **Test Gönder** ile deneyin.
4. **Alan adı alınınca:**
   - Alan adının DNS/nameserver ayarlarını hosting firmasının verdiği adreslere yönlendirin (24 saate kadar sürebilir).
   - cPanel'den SSL sertifikasını (AutoSSL / Let's Encrypt) etkinleştirin.
   - Panel > Site & Ödeme Ayarları > **Sunucu & Yedek** sekmesinde site adresini `https://www.alanadiniz.com` yapın ve
     “https yönlendirmesi” kutusunu işaretleyin.
   - Aynı sekmedeki **Sunucu Durumu** listesinin tamamen yeşil olduğunu kontrol edin.
   - Firma bilgilerini girip Garanti BBVA başvurusunu yapın (başvuru için alan adı ve SSL şarttır).
5. **Yedek:** Sunucu & Yedek sekmesindeki **Yedeği İndir** butonu tüm veritabanını `.sql` dosyası olarak indirir.

### GitHub'dan otomatik yükleme (isteğe bağlı)

`.github/workflows/deploy.yml` dosyası kodu FTP ile hostinge yükler. GitHub > Settings > Secrets and variables > Actions altına
`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR` (örn. `public_html/`) bilgilerini ekleyin, ardından
Actions > **Hostinge Yükle** > Run workflow deyin. İlk yüklemede “Kurulum sihirbazını da yükle” seçeneğini işaretleyin.
`config.php` ve veritabanı hiçbir zaman ezilmez.

## Garanti BBVA Sanal POS

- Başvuru onaylanınca banka size **Üye İşyeri No, Terminal No, Provizyon kullanıcısı/şifresi ve 3D Store Key** iletir.
- Panel → Site & Ödeme Ayarları → **Ödeme** sekmesine girin.
- Bankaya bildirilecek dönüş adresi: `https://alanadiniz.com/odeme-sonuc.php`
- Modlar: **DEMO** (bankasız simülasyon) → **TEST** (Garanti test ortamı) → **CANLI**.
- 3D modeli: **3D OOS Pay** (kart bankanın sayfasında girilir, önerilir) veya **3D Pay** (kart formu sitede, veriler doğrudan bankaya gider).
- Kart bilgileri hiçbir zaman sunucuya gelmez ve saklanmaz. Banka yanıtı SHA-512 imzası ve tutarla doğrulanır.

## Klasör yapısı

```
index.php, hizmetler.php, hizmet.php, paketler.php, paket.php   Site sayfaları
odeme.php, odeme-sonuc.php, odeme-demo.php                       Ödeme akışı
giris.php, kayit.php, hesabim.php, sifremi-unuttum.php          Üyelik
iletisim.php, sayfa.php                                          İletişim ve yasal sayfalar
admin/                                                          Yönetim paneli
includes/                                                       Çekirdek (veritabanı, yetki, Garanti entegrasyonu)
assets/                                                         CSS, JS, logo ve görseller
install/                                                        Kurulum sihirbazı ve başlangıç içerikleri
```

## Logo

`assets/img/logo.svg` (açık zemin), `logo-light.svg` (koyu zemin), `favicon.svg`. Kart logoları `assets/img/payment-logos.svg`
dosyasındadır; bankanın ilettiği resmi logo setiyle değiştirilebilir.
