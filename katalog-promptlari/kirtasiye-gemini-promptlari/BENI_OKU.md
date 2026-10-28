# Kırtasiye Kataloğu — Gemini Prompt Paketi

Bu paketle Gemini'ye **250 ürünlük** (10 kategori × 25 ürün) bir kırtasiye kataloğu ürettirebilirsin. Çıktı JSON formatında gelir; ekteki script ile tek bir **CSV/Excel** ve **JSON** dosyasına çevrilir, siteye toplu ürün yüklemesinde kullanılabilir.

## Dosyalar
| Dosya | Ne işe yarar |
|---|---|
| `00_ANA_PROMPT.md` | Kurallar + ürün alan yapısı. Her sohbetin başında **bir kez** yapıştırılır |
| `01_…` – `10_…` | Her biri 25 ürünlük kategori partisi |
| `11_KONTROL_VE_DUZELTME.md` | Yarıda kesilme, hata kontrolü, fiyat güncelleme promptları |
| `12_URUN_GORSELLERI.md` | Ürün ve banner görseli üretme şablonu |
| `13_KATEGORI_SAYFA_METINLERI.md` | Kategori sayfası, SSS ve ana sayfa metinleri |
| `birlestir.py` | Tüm partileri birleştirip `katalog.csv` + `katalog.json` üretir, hataları raporlar |
| `ciktilar/` | Gemini'den gelen JSON'ları buraya kaydet |

## Partiler
| # | Kategori | SKU |
|---|---|---|
| 01 | Yazı Gereçleri | `KRT-YZG-001…025` |
| 02 | Defter ve Kağıt Ürünleri | `KRT-DFK-001…025` |
| 03 | Sanat ve Boyama | `KRT-SNT-001…025` |
| 04 | Okul Çantası ve Kalem Kutusu | `KRT-CNT-001…025` |
| 05 | Ofis ve Dosyalama | `KRT-OFS-001…025` |
| 06 | Kesme, Yapıştırma ve Düzeltme | `KRT-KYP-001…025` |
| 07 | Ölçme, Geometri ve Hesap | `KRT-OLC-001…025` |
| 08 | Hobi ve El İşi | `KRT-HOB-001…025` |
| 09 | Ajanda, Planlayıcı ve Masa Düzeni | `KRT-ORG-001…025` |
| 10 | Okul Öncesi ve Eğitici Ürünler | `KRT-OKO-001…025` |

## Adım adım
1. `00_ANA_PROMPT.md` dosyasının en altındaki **MAĞAZA BİLGİLERİ** kısmını doldur (mağaza adı, markalar).
2. Gemini'de (mümkünse en güçlü model, ör. *Pro*) yeni sohbet aç, ana prompt'u yapıştır. "Hazırım" cevabını bekle.
   - Daha pratik: Gemini → **Gem oluştur** → ana prompt'u "Talimatlar"a yapıştır.
3. `01_…` dosyasının içeriğini gönder. Gelen JSON'u kopyala, `ciktilar/01_YZG.json` gibi kaydet.
4. Yanıt yarıda kesilirse **"devam et"** yaz ve devamını aynı dosyanın sonuna yapıştır (script birleştirir).
5. Diğer partilerle devam et (02 → 10). Sohbet çok uzarsa yeni sohbet açıp ana prompt'u tekrar yapıştırabilirsin; SKU'lar kategoriye göre sabit olduğu için çakışma olmaz.
6. Bilgisayarında Python varsa paket klasöründe: `python birlestir.py`
   → `katalog.csv` (Excel'de doğrudan açılır) ve `katalog.json` oluşur; eksik alan / tekrar eden ürün uyarıları ekrana yazılır.
7. İstersen `13_…` ile kategori sayfası metinlerini, `12_…` ile görselleri üret.

## Önemli notlar
- **Fiyatlar tahminîdir.** Gemini gerçekçi aralıkta üretir ama kendi alış fiyatlarına göre mutlaka gözden geçir (`11_…` içinde toplu % artırma promptu var).
- **Barkod üretilmez**, gerçek ürün barkodlarını sonradan ekle.
- Ürün adlarında gerçek marka kullanılmaz; gerçekten sattığın markaları `marka` alanına sonradan yazabilirsin.
- Yapay zekâ metinleri yayına almadan önce hızlıca göz at: ölçü / adet bilgileri gerçek stoğunla tutmalı.
- Daha fazla ürün istersen herhangi bir partiyi kopyalayıp SKU'ları `026`'dan başlatarak 25 ürün daha üretebilirsin.
