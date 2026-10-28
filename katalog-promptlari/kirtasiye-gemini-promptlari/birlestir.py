"""
Gemini çıktılarını tek katalogda birleştirir.

Kullanım:
  1) Her partinin JSON çıktısını ciktilar/ klasörüne .json olarak kaydet.
  2) python birlestir.py
Sonuç: katalog.json, katalog.csv (Excel'de açılır, ; ayraçlı) ve ekrana hata raporu.
"""
import csv, glob, json, os

KLASOR = os.path.dirname(os.path.abspath(__file__))
ALANLAR = ["sku", "urun_adi", "slug", "kategori", "alt_kategori", "marka", "kisa_aciklama",
           "uzun_aciklama", "ozellikler", "renk_secenekleri", "olcu", "paket_icerigi", "yas_grubu",
           "fiyat", "indirimli_fiyat", "stok", "desi", "etiketler", "seo_baslik", "seo_aciklama",
           "gorsel_prompt"]


def json_oku(yol):
    # Kod bloklarını, "devam et" ile eklenen parçaları ve araya giren metinleri
    # tolere etmek için dosyadaki tüm üst seviye {...} nesnelerini tek tek okur.
    metin = open(yol, encoding="utf-8").read()
    cozucu, nesneler, i = json.JSONDecoder(), [], 0
    while i < len(metin):
        if metin[i] == "{":
            nesne, i = cozucu.raw_decode(metin, i)
            nesneler.append(nesne)
        else:
            i += 1
    return nesneler


urunler, uyarilar = [], []
for yol in sorted(glob.glob(os.path.join(KLASOR, "ciktilar", "*.json"))):
    try:
        veri = json_oku(yol)
    except json.JSONDecodeError as e:
        uyarilar.append(f"{os.path.basename(yol)}: geçersiz JSON ({e})")
        continue
    print(f"{os.path.basename(yol)}: {len(veri)} ürün")
    urunler.extend(veri)

skular, adlar, sluglar = set(), set(), set()
for u in urunler:
    sku = u.get("sku", "?")
    for alan in ALANLAR:
        if alan not in u:
            uyarilar.append(f"{sku}: '{alan}' alanı eksik")
    for kume, alan in ((skular, "sku"), (adlar, "urun_adi"), (sluglar, "slug")):
        deger = str(u.get(alan, "")).strip().lower()
        if deger in kume:
            uyarilar.append(f"{sku}: tekrar eden {alan} -> {u.get(alan)}")
        kume.add(deger)
    if len(u.get("seo_baslik", "")) > 60:
        uyarilar.append(f"{sku}: seo_baslik 60 karakterden uzun")
    if u.get("indirimli_fiyat") and u["indirimli_fiyat"] >= u.get("fiyat", 0):
        uyarilar.append(f"{sku}: indirimli fiyat normal fiyattan düşük değil")

with open(os.path.join(KLASOR, "katalog.json"), "w", encoding="utf-8") as f:
    json.dump(urunler, f, ensure_ascii=False, indent=2)

with open(os.path.join(KLASOR, "katalog.csv"), "w", encoding="utf-8-sig", newline="") as f:
    yazici = csv.writer(f, delimiter=";")
    yazici.writerow(ALANLAR)
    for u in urunler:
        satir = []
        for alan in ALANLAR:
            deger = u.get(alan, "")
            if isinstance(deger, list):
                deger = " | ".join(map(str, deger))
            elif deger is None:
                deger = ""
            satir.append(deger)
        yazici.writerow(satir)

print(f"\nToplam {len(urunler)} ürün -> katalog.json, katalog.csv")
if uyarilar:
    print(f"\n{len(uyarilar)} uyarı:")
    for x in uyarilar:
        print("  -", x)
else:
    print("Sorun bulunamadı.")
