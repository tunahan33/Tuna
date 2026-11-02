# Galeri sahnesi fotoğraflarını oyuna hazırlar: 16:9 (bina) ya da 4:3 (tesis) oranında ortadan kırpar, WebP'ye çevirir.
# Kullanım: python3 -I sahne-isle.py KAYNAK_KLASÖR HEDEF_KLASÖR dosya=anahtar,dosya=anahtar,...
import sys, os
from PIL import Image
src, dst = sys.argv[1], sys.argv[2]
for pair in sys.argv[3].split(','):
    f, key = pair.split('=')
    im = Image.open(os.path.join(src, f)).convert('RGB')
    ratio, size = ((16 / 9), (1024, 576)) if key.startswith('galeri-') else ((4 / 3), (640, 480))
    w, h = im.size
    tw, th = (w, round(w / ratio)) if w / h < ratio or abs(w / h - ratio) < 1e-9 else (round(h * ratio), h)
    if th > h: th = h; tw = round(h * ratio)
    l, t = (w - tw) // 2, (h - th) // 2
    im.crop((l, t, l + tw, t + th)).resize(size, Image.LANCZOS).save(os.path.join(dst, key + '.webp'), quality=74, method=6)
    print(key, os.path.getsize(os.path.join(dst, key + '.webp')) // 1024, 'KB')
