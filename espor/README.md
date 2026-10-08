# GS Sportif Faaliyetler – E-Spor Koçluk Sitesi

Sarı · kırmızı · antrasit temalı e-spor koçluk sitesi ve rol bazlı yönetim paneli.
Saf PHP 8 + SQLite/MySQL; framework, Composer veya Node.js gerekmez. Her paylaşımlı hostinge kurulabilir.

## Bilgisayarında görmek ve yayınlamadan düzenlemek (VS Code)

Site yalnızca senin bilgisayarında çalışır, internete açılmaz. İstediğin kadar deneyip düzenleyebilirsin.

### 1. Bir kere yapılacaklar

1. **VS Code** kur: https://code.visualstudio.com
2. **PHP** kur (Windows): Başlat menüsünde *PowerShell* aç ve şunu yaz:
   ```
   winget install PHP.PHP.8.3
   ```
   Kurulum bitince PowerShell'i kapatıp yeniden aç, `php -v` yaz; sürüm numarası görünüyorsa tamamdır.
   (Mac: `brew install php`)
3. Projeyi bilgisayarına indir: GitHub'da yeşil **Code → Download ZIP** ile indirip klasöre çıkar
   (veya VS Code'da *Source Control → Clone Repository*).

### 2. Siteyi açma

1. VS Code → **File → Open Folder** → `espor` klasörünü seç.
2. Üst menüden **Terminal → Run Task…** → **1) Siteyi kur (ilk sefer)**. (Sadece ilk sefer.)
3. **Terminal → Run Task…** → **2) Siteyi başlat**.
4. Tarayıcıda aç: **http://localhost:8000**
5. Yönetim paneli: **http://localhost:8000/admin**

Siteyi kapatmak için terminalde `Ctrl + C`. Sonraki açılışlarda sadece 3. ve 4. adımlar yeterli.

> "Eksik PHP eklentisi" yazarsa: ekrandaki php.ini dosyasını Not Defteri ile açıp yazılan `extension=...` satırlarının
> başındaki `;` işaretini sil, kaydet ve tekrar dene.

### 3. Test hesapları (yalnızca bilgisayarındaki kopya için)

| Rol | E-posta | Şifre |
|---|---|---|
| Süper Admin | `admin@gssportif.local` | `Admin123!` |
| Admin | `yonetici@gssportif.local` | `Test1234!` |
| Editör | `editor@gssportif.local` | `Test1234!` |
| Satış Temsilcisi | `satis@gssportif.local` | `Test1234!` |
| Üye (müşteri) | `uye@gssportif.local` | `Test1234!` |

Demo modunda ödeme sayfasında gerçek kart istenmez, “Başarılı / Başarısız” düğmesiyle akış denenir.
Her şeyi sıfırlamak için: **Run Task → Siteyi sıfırla**.

### 4. Nasıl düzenlerim?

- **Yazılar, fiyatlar, paketler, sözleşmeler:** Kod gerekmez. Panelden (Hizmetler, Paketler & Fiyatlar, Sayfalar & Sözleşmeler) değiştir, kaydet, sayfayı yenile.
- **Firma bilgileri:** Panel → Site & Ödeme Ayarları → Firma Bilgileri. Tüm sözleşmeler ve sayfa altı otomatik dolar.
- **Renkler / görünüm:** `assets/css/style.css` (en alttaki “E-SPOR KARANLIK TEMA” bölümü). Kaydet → tarayıcıda `F5`.
- **Logo:** `assets/img/logo-light.svg` (koyu zemin), `logo.svg` (açık zemin), `emblem-3d.jpg` (ana sayfadaki büyük amblem).
- **Ana sayfa:** `index.php` · **Üst menü:** `includes/header.php` · **Alt bilgi:** `includes/footer.php`

## İçerik

**Site**
- 8 e-spor koçluk türü, her birinde 3 paket (24 paket), KDV dahil net fiyatlar:
  Valorant, League of Legends, CS2, PUBG Mobile, EA SPORTS FC, Takım & Turnuva, Mental Performans, Yayıncı & İçerik Üretici
- Paket kartına tıklayınca detaylı açıklama penceresi açılır
- Alt bilgideki tüm bağlantılar dolu sayfalardır; bazılarında çalışan formlar vardır:

| Bölüm | Sayfa | Özellik |
|---|---|---|
| Kurumsal | Hakkımızda | Firma bilgileri otomatik |
| | İnsan Kaynakları | Açık pozisyonlar + **iş başvuru formu** |
| Müşteri Hizmetleri | Sıkça sorulan sorular | Açılır-kapanır liste |
| | Sipariş takibi | **Sipariş no + e-posta ile durum sorgulama** |
| | Arıza takibi | **Arıza kaydı açma + takip no ile sorgulama**, ekibin yanıtı burada görünür |
| | İade ve iade çeki koşulları | |
| | Teslimat koşulları | |
| | Güvenli alışveriş | |
| | İletişim | İletişim formu |
| Sözleşmeler ve Yasal | Üyelik sözleşmesi | |
| | Genel Aydınlatma metni | KVKK |
| | Çerez politikası | |
| | Çerez tercihleri | **Analitik/pazarlama çerezleri açma-kapama** |
| | İlgili kişi başvuru formu | **KVKK m.11 başvuru formu** (T.C. kimlik doğrulamalı) |
| Satış sözleşmeleri | Mesafeli Satış Sözleşmesi, Ön Bilgilendirme Formu, Gizlilik Sözleşmesi | Ödeme sırasında müşteri bilgileriyle dolar |

**Yönetim paneli (`/admin`)**

| Yetki | Süper Admin | Admin | Editör | Satış Temsilcisi | Üye |
|---|:-:|:-:|:-:|:-:|:-:|
| Panele giriş | ✓ | ✓ | ✓ | ✓ | – |
| Siparişleri görme, durum güncelleme, not | ✓ | ✓ | – | ✓ | – |
| İptal / iade / temsilci atama | ✓ | ✓ | – | – | – |
| Sipariş silme | ✓ | – | – | – | – |
| Müşteriler, mesajlar, arıza / KVKK / iş başvuruları ve yanıtlama | ✓ | ✓ | – | ✓ | – |
| İade çeklerini görme | ✓ | ✓ | – | ✓ | – |
| İade çeki tanımlama / iptal | ✓ | ✓ | – | – | – |
| Koçluk ve paket metinleri, sayfalar | ✓ | ✓ | ✓ | – | – |
| Koçluk/paket ekleme, silme, fiyat | ✓ | ✓ | – | – | – |
| Kullanıcı ve rol yönetimi | ✓ (süper admin hariç herkes) | ✓ (editör, satış, üye) | – | – | – |
| Yeni süper admin atama | ✓ | – | – | – | – |
| **Günlük sipariş ve satış raporu (gün gün, tek tek, Excel)** | ✓ | – | – | – | – |
| **Aktivite akışı (kim, ne zaman, ne yaptı)** | ✓ | – | – | – | – |
| **Site ayarları** | ✓ | – | – | – | – |

**Yetki verme:** Panel → **Kullanıcılar & Yetkiler**. Siteye kaydolan her üye listede görünür; “Rol / Yetki” sütunundan
Admin, Editör, Satış Temsilcisi veya Üye seçip **Kaydet**'e basmak yeterlidir (süper admin, Süper Admin de seçebilir; onay istenir).
Admin yalnızca Editör, Satış Temsilcisi ve Üye atayabilir. Her yetki değişikliği aktivite akışına yazılır.

Süper admin hesapları korumalıdır: bir süper admin başka bir süper adminin yetkisini düşüremez, hesabını pasifleştiremez,
e-postasını veya şifresini değiştiremez (denemeler aktivite akışına “Yetkisiz erişim denemesi” olarak düşer).
Her süper admin kendi bilgilerini yalnızca **Profilim** sayfasından değiştirir.

Yetkiler `includes/auth.php` dosyasındaki `PERMISSIONS` tablosundan değiştirilebilir.

## İade çekleri

Müşteri kart iadesi yerine iade çeki isterse: Panel → **İade Çekleri** (veya sipariş detayındaki **Bu sipariş için iade çeki tanımla**).
Sipariş no + iade tutarı girilir; “%10 ekle” işaretliyse çek tutarı iade tutarının %10 fazlası olur. `IC-XXXX-XXXX` biçiminde kod
üretilir ve müşteriye e-postayla gönderilir. Müşteri kodu ödeme sayfasındaki **İade çeki kodum var** alanına girer:

- Kod yalnızca tanımlandığı e-posta adresiyle kullanılabilir, 12 ay geçerlidir (ay sayısı değiştirilebilir).
- Paket fiyatından düşülür, fark kartla ödenir. Çek paketi tamamen karşılıyorsa bankaya gidilmeden sipariş tamamlanır.
- Kalan bakiye sonraki siparişlerde kullanılabilir; bakiye yalnızca ödeme onaylanınca düşer.
- Müşteri çeklerini **Hesabım** sayfasında görür. Tüm işlemler aktivite akışına yazılır.

## Müşteri nasıl öder? (PayTR)

Paket → **Satın Al** → fatura bilgileri → sözleşme onayı → **Güvenli Ödeme**: PayTR'ın kart formu sayfada açılır
(iframe). Kart bilgileri yalnızca PayTR'a girilir, sitenin sunucusuna gelmez.

Ödeme sonucu PayTR sunucusundan `paytr-bildirim.php` adresine imzalı olarak gelir; imza ve tutar doğrulanınca sipariş
**Ödendi** olur, müşteriye ve size e-posta gider. Müşterinin dönüş sayfası ödemeyi onaylamaz, yalnızca bilgi verir.

**Kurulum (bir kez):**
1. PayTR Mağaza Paneli → **Destek & Kurulum → Entegrasyon Bilgileri**: Mağaza No, Mağaza Parola, Mağaza Gizli Anahtar.
2. Site paneli → Site & Ödeme Ayarları → **Ödeme (PayTR)** sekmesine bu üç bilgiyi girin, modu **TEST** yapın.
3. PayTR Mağaza Paneli → **Destek & Kurulum → Ayarlar → Bildirim URL**:
   `https://www.gssportiffaaliyetler.com/paytr-bildirim.php`
4. TEST modunda (yalnızca panel personeli görür) PayTR test kartıyla bir sipariş verin; sipariş “Ödendi” olmalı.
5. Modu **CANLI** yapın. Taksit seçeneği aynı sekmeden açılabilir (varsayılan: tek çekim).

Mod **KAPALI** iken veya bilgiler eksikken ödeme panelinde “Online ödeme yakında aktif” görünür; sipariş
“Ödeme Bekliyor” olarak kaydedilir. Site dışında alınan ödemeyi onaylamak için siparişin durumunu **Ödendi** yapın
(admin / süper admin). İadeler PayTR Mağaza Paneli'nden yapılır, ardından sipariş durumu “İade Edildi” yapılır.

## Sunucuya kurulum (gssportiffaaliyetler.com)

Sunucu: Ubuntu 24.04 VPS. Tek komutla Apache, PHP, MariaDB, ücretsiz SSL, güvenlik duvarı, saldırı koruması ve her gece
veritabanı yedeği kurulur.

**1. Alan adını sunucuya yönlendir:** Natro → Alan Adlarım → gssportiffaaliyetler.com → **DNS Yönetimi**:
mevcut `@` ve `www` A kayıtlarını silip şunları ekle (park/yönlendirme varsa kapat):

| Tür | Ad | Değer |
|---|---|---|
| A | @ | 213.142.148.32 |
| A | www | 213.142.148.32 |

**2. Sunucuya bağlan:** Windows'ta *PowerShell* aç → `ssh root@213.142.148.32` → `yes` → şifreyi yaz (yazarken görünmez).

**3. Kurulumu başlat:**
```bash
curl -fsSL https://raw.githubusercontent.com/tunahan33/Tuna/claude/dreamy-newton-rci15t/espor/deploy/vps-kurulum.sh -o kurulum.sh
bash kurulum.sh
```
Senden süper admin adı, e-postası ve şifresi istenir. DNS hazırsa SSL de otomatik kurulur ve site
https://www.gssportiffaaliyetler.com adresinde açılır. DNS henüz yayılmadıysa site önce `http://213.142.148.32` adresinde açılır;
yayılınca (15 dk – birkaç saat) `bash kurulum.sh ssl` komutunu çalıştır.

**4. Sonraki güncellemeler:** `bash kurulum.sh guncelle` (önce yedek alır; ayarlar, siparişler ve üyeler korunur).

Veritabanı şifresi sunucuda `/root/gssportif-bilgiler.txt`, gece yedekleri `/var/backups/gssportif/` klasöründedir.

> Güvenlik: kurulumdan sonra sunucuda `passwd` yazarak root şifresini en az 16 karakterli güçlü bir şifreyle değiştir.

## Paylaşımlı hostinge kurulum (cPanel, alternatif)

1. cPanel → MySQL veritabanı ve kullanıcı oluştur.
2. `espor` klasörünün **içindekileri** `public_html` klasörüne yükle (`config.php` ve `storage/*.sqlite` yerel dosyalardır, yükleme).
3. `https://alanadiniz.com/install/` adresinden kurulumu tamamla, ardından `install` klasörünü sil.
4. SSL aktifse panelden **Sunucu & Yedek → https yönlendirmesi**ni aç.
