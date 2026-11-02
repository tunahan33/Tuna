import sys, os
from PIL import Image
src, dst = sys.argv[1], sys.argv[2]
mp = dict(a.split('=') for a in sys.argv[3].split(','))
for f, gid in mp.items():
    im = Image.open(os.path.join(src, f + '.jpg')).convert('RGB')
    w, h = im.size
    # 15:8 oranında ortadan kırp, 768×410 (kartta 2× keskin, dosya küçük)
    tw = w; th = round(w * 512 / 960)
    if th > h: th = h; tw = round(h * 960 / 512)
    l = (w - tw) // 2; t = (h - th) // 2
    im = im.crop((l, t, l + tw, t + th)).resize((768, 410), Image.LANCZOS)
    im.save(os.path.join(dst, gid + '.webp'), quality=74, method=6)
    print(gid, os.path.getsize(os.path.join(dst, gid + '.webp')) // 1024, 'KB')
