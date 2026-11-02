#!/usr/bin/env python3
"""GEMINI_*_PROMPTLARI.md dosyalarındaki promptlarla Gemini API'den görsel üretir ve kaydeder.

Anahtar ortam değişkeninden okunur: GEMINI_API_KEY (Google AI Studio → API anahtarı).
Kullanım:
  python3 gemini-uret.py luks      # 67 lüks eşya   → ham/luks/<ad>.png   (16:9)
  python3 gemini-uret.py kisi      # 36 portre      → ham/kisiler/<ad>.png (1:1)
  python3 gemini-uret.py luks --sadece e01,s04   # yalnızca bunlar
Var olan dosyalar atlanır; yarıda kalırsa aynı komutla kaldığı yerden devam eder.
Ham görseller sonra kırpılıp WebP'ye çevrilerek oyuna eklenir.
"""
import argparse
import base64
import json
import os
import re
import sys
import time
import urllib.error
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
SETS = {
    "luks": ("GEMINI_LUKS_PROMPTLARI.md", "luks", "16:9"),
    "kisi": ("GEMINI_KISI_PROMPTLARI.md", "kisiler", "1:1"),
}


def prompts(md_path):
    text = open(md_path, encoding="utf-8").read()
    # "### 01. Ad — `dosya.png`" başlığından sonraki ilk ``` bloğu
    for m in re.finditer(r"^### \d+\. .*?`([a-z0-9-]+)\.png`.*?\n```\n(.*?)\n```", text, re.S | re.M):
        yield m.group(1), m.group(2).strip()


def generate(key, model, prompt, aspect):
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent"
    body = {
        "contents": [{"parts": [{"text": prompt}]}],
        "generationConfig": {"responseModalities": ["IMAGE"], "imageConfig": {"aspectRatio": aspect}},
    }
    req = urllib.request.Request(url, data=json.dumps(body).encode(), headers={"Content-Type": "application/json", "x-goog-api-key": key})
    with urllib.request.urlopen(req, timeout=180) as r:
        data = json.load(r)
    for cand in data.get("candidates", []):
        for part in cand.get("content", {}).get("parts", []):
            inline = part.get("inlineData") or part.get("inline_data")
            if inline and inline.get("data"):
                return base64.b64decode(inline["data"])
    raise RuntimeError("yanıtta görsel yok: " + json.dumps(data)[:300])


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("set", choices=SETS)
    ap.add_argument("--model", default=os.environ.get("GEMINI_IMAGE_MODEL", "gemini-2.5-flash-image"))
    ap.add_argument("--sadece", default="", help="virgülle ayrılmış dosya adları (uzantısız)")
    ap.add_argument("--bekle", type=float, default=4.0, help="istekler arası saniye")
    a = ap.parse_args()
    key = os.environ.get("GEMINI_API_KEY")
    if not key:
        sys.exit("GEMINI_API_KEY ortam değişkeni yok.")
    md, sub, aspect = SETS[a.set]
    out = os.path.join(ROOT, "tools", "gorsel", "ham", sub)
    os.makedirs(out, exist_ok=True)
    only = {x.strip() for x in a.sadece.split(",") if x.strip()}
    items = [(n, p) for n, p in prompts(os.path.join(ROOT, md)) if not only or n in only]
    done = fail = 0
    for i, (name, prompt) in enumerate(items, 1):
        path = os.path.join(out, name + ".png")
        if os.path.exists(path):
            continue
        for attempt in range(3):
            try:
                img = generate(key, a.model, prompt, aspect)
                open(path, "wb").write(img)
                done += 1
                print(f"[{i}/{len(items)}] {name} kaydedildi", flush=True)
                break
            except urllib.error.HTTPError as e:
                msg = e.read().decode(errors="replace")[:300]
                if e.code in (429, 500, 503) and attempt < 2:
                    time.sleep(20 * (attempt + 1))
                    continue
                print(f"[{i}/{len(items)}] {name} HATA {e.code}: {msg}", flush=True)
                fail += 1
                if e.code in (400, 401, 403):
                    sys.exit("Anahtar ya da model kabul edilmedi; durduruldu.")
                break
            except Exception as e:  # ağ hatası, boş yanıt
                if attempt < 2:
                    time.sleep(10)
                    continue
                print(f"[{i}/{len(items)}] {name} HATA: {e}", flush=True)
                fail += 1
        time.sleep(a.bekle)
    print(f"Bitti: {done} yeni, {fail} hatalı. Klasör: {out}")


if __name__ == "__main__":
    main()
