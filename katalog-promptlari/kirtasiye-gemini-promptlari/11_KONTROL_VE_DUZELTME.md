# 11 — Kontrol ve Düzeltme Promptları

Bir partide sorun görürsen aynı sohbette aşağıdakilerden uygun olanı gönder.

## A) Yanıt yarıda kesildiyse
```
devam et — kaldığın üründen itibaren sadece kalan JSON nesnelerini yaz, diziyi baştan başlatma.
```

## B) Partiyi kontrol ettir
```
Az önce ürettiğin partiyi şu açılardan kontrol et ve SADECE düzeltilmiş tam JSON dizisini tekrar ver:
1) Tam 25 ürün var mı, alt kategori adetleri istenene uyuyor mu?
2) SKU'lar sıralı ve benzersiz mi?
3) Tüm alanlar var mı, sırası doğru mu, boş zorunlu alan var mı?
4) seo_baslik ≤ 60, seo_aciklama 140–155, kisa_aciklama ≤ 160 karakter mi?
5) Fiyatlar aralıkta ve ,90 ile mi bitiyor? İndirimli fiyat normal fiyattan düşük mü?
6) Gerçek bir marka adı geçiyor mu? Geçiyorsa kaldır.
7) Tekrar eden veya birbirine çok benzeyen ürün var mı? Varsa farklı bir ürünle değiştir.
```

## C) Tek bir ürünü değiştir
```
KRT-XXX-000 SKU'lu ürünü kaldır, yerine aynı alt kategoride farklı bir ürün üret. Sadece o ürünün JSON nesnesini ver.
```

## D) Fiyatları topluca güncelle
```
Bu partideki tüm fiyatları %X artır, yine ,90 ile bitecek şekilde yuvarla. İndirimli fiyatları da orantılı güncelle. Sadece güncellenmiş tam JSON dizisini ver.
```
