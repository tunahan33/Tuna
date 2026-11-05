# İkinci Vitrin — görsel prompt paketi

Oyunda görsel olarak görünen her şey için toplam **97 prompt**. Promptlar İngilizce; görsel üreticiler (Gemini, ChatGPT, Midjourney vb.) İngilizceyi daha iyi anlıyor. Başlıklar ve ürün adları Türkçe.

## Dosyalar

| Dosya | İçerik | Adet |
|---|---|---|
| `01_alim-satim-urunleri.md` | Alım satım ürünleri | 26 |
| `02_luks-ev.md` | Lüks alışveriş — Ev | 10 |
| `03_luks-araba.md` | Lüks alışveriş — Araba | 10 |
| `04_luks-yat.md` | Lüks alışveriş — Tekne & Yat | 10 |
| `05_luks-ucak.md` | Lüks alışveriş — Uçak | 10 |
| `06_luks-stil.md` | Lüks alışveriş — Tarz | 10 |
| `07_dukkan-seviyeleri.md` | Dükkân seviyeleri | 4 |
| `08_calisanlar.md` | Çalışanlar | 5 |
| `09_iliskiler.md` | İlişkiler | 3 |
| `10_satici-tipleri.md` | Satıcı tipleri | 8 |
| `11_uygulama-ikonu.md` | Uygulama ikonu | 1 |
| `tum-promptlar.csv` | Hepsi tek tabloda (kod, grup, ad, dosya adı, oyundaki kimlik, prompt) | 97 |

## Ortak stil

Bütün görseller oyunun yumuşak pastel görünümüne uysun diye aynı stil cümlesiyle bitiyor:
yumuşak 3D mobil oyun çizimi, pastel renkler, açık lavanta zemin (#F5F1F6), kare 1:1, 1024x1024, yazı ve logo yok.

- Daha gerçekçi fotoğraf istersen her promptun sonundaki "Soft 3D render in a cute, polished mobile-game style…" kısmını
  "Professional studio product photo, photorealistic…" ile değiştir.
- Şeffaf arka plan istersen "plain soft lavender background (#F5F1F6)" yerine "transparent background" yaz
  (her üretici desteklemiyor; desteklemezse düz zemini sonradan silebilirsin).

## Kullanım

1. Promptu olduğu gibi kopyalayıp üreticiye yapıştır.
2. Çıkan görseli **Dosya adı** satırındaki adla kaydet (örneğin `urun-saat-01.png`).
3. Hepsini tek klasörde topla ve bana gönder; oyundaki çizimlerin yerine koyarım.

## Notlar

- Markalar uydurmadır (Valmont, Ardeni, Maison Lirac…). Promptlarda marka adı geçmiyor ve "no logos, no brand names" yazıyor; araç, tekne ve uçaklar için de gerçek bir modele benzememesi isteniyor.
- Ürünler oyunda Yıpranmış / İyi / Çok iyi / Yeni gibi durumlarda çıkıyor; her ürün için tek görsel yeterli, durum oyunda noktalarla gösteriliyor.
