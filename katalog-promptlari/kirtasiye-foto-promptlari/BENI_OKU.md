# Kırtasiye Ürün Fotoğrafları — Gemini Prompt Paketi

250 ürün için hazır görsel promptu (10 kategori × 25 ürün).

## Dosyalar
- `01_…md` – `10_…md`: Kategori kategori promptlar. Her ürünün başında görsel için önerilen dosya adı var (örn. `01-001`).
- `tum-promptlar.csv`: Hepsi tek tabloda (Excel'de açılır): dosya adı, kategori, ürün adı, prompt.

## Nasıl kullanılır
1. Gemini'de yeni sohbet aç (görsel üretimi destekleyen model).
2. İlk mesaj olarak aşağıdaki **stil talimatını** gönder; tüm fotoğraflar aynı görünümde olsun.
3. Sonra ürün promptlarını **tek tek** gönder (her mesaja bir ürün). Üretilen görseli indir ve başlıktaki dosya adıyla kaydet (`01-001.png` gibi).
4. Sohbet uzayınca yeni sohbet açıp stil talimatını tekrar gönder.

### Stil talimatı (ilk mesaj)
```
Bu sohbette bir kırtasiye e-ticaret sitesi için ürün fotoğrafları üreteceğiz.
Göndereceğim her mesaj için SADECE 1 adet görsel üret, açıklama yazma.
Tüm görseller aynı katalog stilinde olsun: saf beyaz arka plan, yumuşak stüdyo ışığı,
ürünün altında hafif doğal gölge, kare (1:1) format, ürün ortada.
Görsellerde asla yazı, logo, marka adı veya filigran olmasın.
```

## İpuçları
- Görselde istemediğin bir şey çıkarsa aynı sohbette kısa düzeltme yaz: *"Aynısını yazı/logo olmadan tekrar üret"*, *"Daha yakından çek"*, *"Rengi kırmızı yap"*.
- Renk varyantı için promptun içindeki rengi değiştirmen yeterli.
- Promptlar İngilizce; görsel modelleri İngilizcede daha tutarlı sonuç veriyor. Türkçe ürün adı her promptun başlığında yazıyor.
- ⚠️ Yapay zekâ görselleri temsilîdir. Gerçekte sattığın ürün farklı görünüyorsa müşteri yanılabilir; mümkünse sonradan gerçek ürün fotoğraflarıyla değiştirin.
