#!/usr/bin/env python3
"""www/ klasöründeki oyunu tek bir HTML dosyasında birleştirir: dist/galeri-patronu.html
Araç fotoğrafları (www/img/araclar/*.webp) base64 olarak gömülür (G.CAR_PHOTO_DATA).
Kullanım: python3 build-single.py [--artifact ÇIKTI.html]
  --artifact: yalnızca ARTIFACT-START/END arasını (head/body iskeleti olmadan) ayrıca yazar."""
import base64
import os
import re
import sys

root = os.path.dirname(os.path.abspath(__file__))
www = os.path.join(root, "www")
html = open(os.path.join(www, "index.html"), encoding="utf-8").read()


def inline(m):
    code = open(os.path.join(www, m.group(1)), encoding="utf-8").read()
    return "<script>\n" + code.replace("</script", "<\\/script") + "\n</script>"


def photo_data():
    d = os.path.join(www, "img", "araclar")
    items = []
    for f in sorted(os.listdir(d)):
        if f.endswith(".webp"):
            b64 = base64.b64encode(open(os.path.join(d, f), "rb").read()).decode("ascii")
            items.append(f'"{f[:-5]}":"{b64}"')
    return "<script>\nwindow.G.CAR_PHOTO_DATA = {" + ",\n".join(items) + "};\n</script>"


html = re.sub(r'<script src="(js/[a-z]+\.js)"></script>', inline, html)
# fotoğraf verisi photos.js'ten hemen sonra (ui.js'ten önce) yüklenir
html = html.replace("<!-- PHOTO-DATA -->", photo_data(), 1)
assert 'src="js/' not in html
assert "G.CAR_PHOTO_DATA = {" in html

if "--artifact" in sys.argv:
    out = sys.argv[sys.argv.index("--artifact") + 1]
    part = html.split("<!-- ARTIFACT-START -->\n", 1)[1].split("<!-- ARTIFACT-END -->", 1)[0]
    open(out, "w", encoding="utf-8").write(part)
    print(out, f"{len(part.encode('utf-8')) / 1024:.0f} KB")

html = html.replace("<!-- ARTIFACT-START -->\n", "").replace("<!-- ARTIFACT-END -->\n", "")
os.makedirs(os.path.join(root, "dist"), exist_ok=True)
out = os.path.join(root, "dist", "galeri-patronu.html")
open(out, "w", encoding="utf-8").write(html)
print(out, f"{len(html.encode('utf-8')) / 1024:.0f} KB")
