#!/usr/bin/env python3
"""GEMINI_*_PROMPTLARI.md dosyalarındaki promptlarla Gemini API'den görsel üretir ve kaydeder.

Anahtar ortam değişkeninden okunur: GEMINI_API_KEY (Google AI Studio → API anahtarı).
Kullanım:
  python3 gemini-uret.py luks      # 67 lüks eşya   → ham/luks/<ad>.jpg   (16:9)
  python3 gemini-uret.py kisi      # 36 portre      → ham/kisiler/<ad>.jpg (1:1)
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


def find_image(o):
    """Yanıttaki ilk base64 görseli bulur (alan adları sürüme göre değişebildiği için ağaçta arar)."""
    if isinstance(o, dict):
        mime = str(o.get("mime_type") or o.get("mimeType") or "")
        data = o.get("data")
        if isinstance(data, str) and len(data) > 1000 and (mime.startswith("image/") or o.get("type") == "image"):
            return base64.b64decode(data)
        for v in o.values():
            r = find_image(v)
            if r:
                return r
    elif isinstance(o, list):
        for v in o:
            r = find_image(v)
            if r:
                return r
    return None


def generate(key, model, prompt, aspect):
    # Google AI Studio'nun yeni "AQ." anahtarları interactions uç noktasıyla çalışır
    url = "https://generativelanguage.googleapis.com/v1beta/interactions"
    body = {
        "model": model,
        "input": [{"type": "text", "text": prompt}],
        "response_format": {"type": "image", "mime_type": "image/jpeg", "aspect_ratio": aspect, "image_size": "1K"},
    }
    req = urllib.request.Request(url, data=json.dumps(body).encode(), headers={"Content-Type": "application/json", "x-goog-api-key": key})
    with urllib.request.urlopen(req, timeout=240) as r:
        data = json.load(r)
    img = find_image(data)
    if img:
        return img
    raise RuntimeError("yanıtta görsel yok: " + json.dumps(data)[:300])


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("set", choices=SETS)
    ap.add_argument("--model", default=os.environ.get("GEMINI_IMAGE_MODEL", "gemini-nano-banana-2.1"))
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
        path = os.path.join(out, name + ".jpg")
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
