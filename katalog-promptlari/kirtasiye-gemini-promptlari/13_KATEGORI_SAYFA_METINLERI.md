# 13 — Kategori Sayfası ve SEO Metinleri

Tüm partiler bittikten sonra aynı sohbette gönder:

```
Şimdi katalogdaki 10 kategori için web sitesi kategori sayfası içeriği hazırla.
Her kategori için tek bir JSON nesnesi üret; çıktı yalnızca JSON dizisi olsun. Alanlar:
- "kategori_kodu"
- "kategori_adi"
- "slug"
- "kisa_tanitim": 1 cümle, ≤ 120 karakter (kategori kartında görünecek)
- "sayfa_metni": 150–200 kelime, SEO uyumlu, 2–3 paragraf (\n\n ile ayrılır)
- "seo_baslik": ≤ 60 karakter, sonunda " | <MAĞAZA_ADI>"
- "seo_aciklama": 140–155 karakter
- "sss": 3 adet {"soru": "...", "cevap": "..."} — müşterilerin o kategoride sık sorduğu sorular
- "banner_gorsel_prompt": İngilizce, 16:9 banner görsel promptu, metin ve logo içermesin
```

## Ana sayfa metinleri (opsiyonel)
```
<MAĞAZA_ADI> adlı online kırtasiye mağazası için şunları yaz (JSON olarak):
- 3 farklı ana sayfa slider başlığı + alt başlığı
- "Neden biz?" bölümü için 4 madde (başlık + 1 cümle)
- Hakkımızda sayfası metni (120–180 kelime)
- Footer için 1 cümlelik mağaza tanıtımı
```
