# 12 — Ürün Görselleri (Gemini görsel üretimi)

Her ürünün JSON'unda `gorsel_prompt` alanı İngilizce hazır gelir. Gemini'de görsel üretim açık bir sohbette (veya Google AI Studio'da) şu şablonla kullan:

```
Generate a square 1:1 image.
<BURAYA ÜRÜNÜN gorsel_prompt ALANINI YAPIŞTIR>
Style rules: consistent e-commerce catalog style, pure white (#FFFFFF) background,
soft shadow under the product, 45-degree front angle, sharp focus, no text, no watermark,
no logo, no brand name, no hands, no extra objects.
```

## Tutarlı bir katalog için ipuçları
- Tüm ürünlerde **aynı stil kurallarını** kullan; site daha profesyonel görünür.
- Kategori kapak görselleri için:
```
Generate a wide 16:9 banner image: a flat-lay of colorful school and office stationery
(<KATEGORİ ÜRÜNLERİ>), on a light pastel background, top-down view, lots of empty space
on the left side for text, soft natural light, no text, no logos.
```
- ⚠️ Yapay zekâ görselleri ürünü **temsilîdir**. Gerçek stok ürününü satıyorsan, mümkün olduğunda kendi çektiğin fotoğrafı kullan; müşteri yanıltıcı görsel nedeniyle iade talep edebilir.
