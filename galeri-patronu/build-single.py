#!/usr/bin/env python3
"""www/ klasöründeki oyunu tek bir HTML dosyasında birleştirir: dist/galeri-patronu.html
Kullanım: python3 build-single.py"""
import os
import re

root = os.path.dirname(os.path.abspath(__file__))
www = os.path.join(root, "www")
html = open(os.path.join(www, "index.html"), encoding="utf-8").read()


def inline(m):
    code = open(os.path.join(www, m.group(1)), encoding="utf-8").read()
    return "<script>\n" + code.replace("</script", "<\\/script") + "\n</script>"


html = re.sub(r'<script src="(js/[a-z]+\.js)"></script>', inline, html)
html = html.replace("<!-- ARTIFACT-START -->\n", "").replace("<!-- ARTIFACT-END -->\n", "")
assert 'src="js/' not in html
os.makedirs(os.path.join(root, "dist"), exist_ok=True)
out = os.path.join(root, "dist", "galeri-patronu.html")
open(out, "w", encoding="utf-8").write(html)
print(out, f"{len(html.encode('utf-8')) / 1024:.0f} KB")
