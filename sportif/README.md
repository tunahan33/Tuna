# GS Sportif Ürünler – Spor Giyim Mağazası

GS Projeler ile aynı sarı · kırmızı · antrasit kimlikte, Garanti BBVA Sanal POS uyumlu spor giyim e-ticaret sitesi.
Saf PHP 8 + MySQL; framework, Composer veya Node.js gerekmez. VS Code ile `sportif` klasörünü açarak çalışabilirsiniz.

## Özellikler

**Mağaza**
- 6 kategori, 17 örnek ürün (forma, antrenman giyim, eşofman, şort/tayt, sweatshirt, aksesuar), KDV dahil net fiyatlar
- Beden ve renk seçenekleri, seçenek bazında stok; tükenen bedenler seçilemez, “Son 3 ürün” uyarısı
- Formalarda **isim-numara baskısı** (ek ücretli), sepette ve siparişte baskı bilgisi
- Filtreleme (kategori, cinsiyet, beden, renk, fiyat, indirim, stok) ve sıralama, ürün arama
- Sepet, **indirim kuponları**, ücretsiz kargo limiti ve “kargonun bedava olmasına X ₺ kaldı” çubuğu
- 4 adımlı ödeme: Sepet → Teslimat/Fatura → Sözleşme onayı (sipariş kalemleriyle doldurulmuş) → Garanti 3D Secure
- Ödeme onayında stoktan düşme; iptal/iadede stoğa geri ekleme
- Hesabım: sipariş durumu adımları (alındı → hazırlanıyor → kargoda → teslim) ve **kargo takip bağlantısı**
- **Takım / toplu sipariş** teklif formu (kulüp, okul, kurum)
- Fiziksel ürün satışına uygun yasal sayfalar: mesafeli satış (baskılı ürünlerde cayma istisnası dahil), ön bilgilendirme,
  iade ve değişim, teslimat ve kargo, beden tablosu, gizlilik, KVKK, çerez, üyelik sözleşmesi

**Yönetim paneli (`/admin`)**

| Yetki | Süper Admin | Admin | Editör | Satış Temsilcisi |
|---|:-:|:-:|:-:|:-:|
| Siparişleri görme, hazırlama, kargoya verme (takip no) | ✓ | ✓ | – | ✓ |
| İptal / iade (stok geri ekleme), sorumlu atama | ✓ | ✓ | – | – |
| Takım talepleri, müşteriler, mesajlar | ✓ | ✓ | – | ✓ |
| Ürün metinleri ve fotoğrafları, sayfalar | ✓ | ✓ | ✓ | – |
| Ürün/kategori ekleme-silme, fiyat, stok, kupon | ✓ | ✓ | – | – |
| Kullanıcı ve rol yönetimi | ✓ (herkes) | ✓ (editör, satış, üye) | – | – |
| **Günlük satış raporu, aktivite akışı, site/POS ayarları** | ✓ | – | – | – |

Fotoğraflar panelden yüklenir; sunucuda yeniden işlenip WEBP olarak kaydedilir. Fotoğraf yüklenene kadar her ürün için markaya
uygun bir çizim gösterilir. Kare ve açık zeminli ürün fotoğrafları en iyi sonucu verir.

## Bilgisayarda çalıştırma

```bash
cd sportif
php install/cli.php http://localhost:8100   # SQLite ile yerel kurulum
php install/demo.php                         # (isteğe bağlı) örnek personel, sipariş, kupon, takım talebi
php -S localhost:8100
```
Panel: http://localhost:8100/admin · `admin@gssportif.local` / `Admin123!`
(demo: `yonetici@`, `editor@`, `satis@`, `uye@gssportif.local` – şifre `Test1234!`; deneme kuponu `HOSGELDIN10`)

## Sunucuya kurulum (yeni VPS)

1. Sunucu paneli: işletim sistemi olarak **Ubuntu 24.04** kurun.
2. Alan adının DNS'inde `@` ve `www` için **A kaydı** = sunucu IP'si (Cloudflare kullanıyorsanız “DNS only” / gri bulut).
3. Sunucuya bağlanın: `ssh root@SUNUCU_IP`
4. Çalıştırın:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/tunahan33/Tuna/claude/dreamy-carson-mpbcx7/sportif/deploy/vps-kurulum.sh -o kurulum.sh
   DOMAIN=gssportifurunler.com bash kurulum.sh
   ```
5. DNS kurulum sırasında hazır değilse, yayıldıktan sonra: `DOMAIN=gssportifurunler.com bash kurulum.sh ssl`
6. Güncelleme: `DOMAIN=gssportifurunler.com bash kurulum.sh guncelle` (yedek alır; ayarlar, veritabanı ve yüklenen fotoğraflar korunur)

Kurulumdan sonra panelde **Site & Ödeme Ayarları**: Firma Bilgileri, **Mağaza & Kargo** (kargo ücreti, ücretsiz kargo limiti,
kargo firmaları ve takip bağlantıları), E-posta (SMTP) ve Garanti Sanal POS bilgilerini girin.
