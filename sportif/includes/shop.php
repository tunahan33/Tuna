<?php
/** Mağaza çekirdeği: ürün görselleri, sepet, stok, kupon ve kargo hesapları */

const GENDERS = ['unisex' => 'Unisex', 'erkek' => 'Erkek', 'kadin' => 'Kadın', 'cocuk' => 'Çocuk'];
const PRODUCT_ARTS = ['jersey' => 'Forma', 'tshirt' => 'Tişört', 'tank' => 'Atlet / Üst', 'jacket' => 'Ceket / Eşofman', 'hoodie' => 'Sweatshirt', 'shorts' => 'Şort', 'leggings' => 'Tayt', 'socks' => 'Çorap', 'bag' => 'Çanta', 'cap' => 'Şapka'];

/* ---------- Ürün görselleri ---------- */

function product_images(array $p): array
{
    $imgs = json_decode((string) ($p['images'] ?? '[]'), true);
    return is_array($imgs) ? array_values(array_filter($imgs, 'is_string')) : [];
}

/** Ürünün ana görseli: yüklenmiş fotoğraf varsa o, yoksa marka çizimi */
function product_thumb(array $p, string $class = ''): string
{
    $imgs = product_images($p);
    if ($imgs) {
        return '<img class="' . e($class) . '" src="' . e(url('uploads/' . $imgs[0])) . '" alt="' . e($p['name']) . '" loading="lazy">';
    }
    return product_art($p['art'] ?? 'tshirt', $p['art_color'] ?? '#C8102E', $class, $p['name'] ?? '');
}

/** Sade, markaya uygun ürün çizimleri (fotoğraf yüklenene kadar) */
function product_art(string $art, string $color, string $class = '', string $label = ''): string
{
    $c = preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '#C8102E';
    $dark = in_array(strtolower($c), ['#2b2f33', '#111315', '#1f2226'], true);
    $stripeA = $dark ? '#FDB913' : '#2B2F33';
    $stripeB = $c === '#FDB913' ? '#C8102E' : '#FDB913';
    $trim = $dark ? '#FDB913' : '#2B2F33';
    $shapes = [
        'jersey' => '<path d="M70 40 L100 28 Q120 44 140 28 L170 40 L205 78 L180 102 L168 92 L168 205 L72 205 L72 92 L60 102 L35 78 Z" fill="C"/><path d="M72 150 L168 96 L168 112 L72 166 Z" fill="A"/><path d="M72 170 L168 116 L168 126 L72 180 Z" fill="B"/><path d="M100 28 Q120 50 140 28" fill="none" stroke="T" stroke-width="6"/><text x="120" y="80" text-anchor="middle" font-family="Montserrat,Arial" font-weight="800" font-size="22" fill="T" opacity=".9">10</text>',
        'tshirt' => '<path d="M72 42 L100 30 Q120 44 140 30 L168 42 L202 74 L180 98 L166 88 L166 205 L74 205 L74 88 L60 98 L38 74 Z" fill="C"/><path d="M100 30 Q120 46 140 30" fill="none" stroke="T" stroke-width="5"/><rect x="104" y="70" width="32" height="6" rx="3" fill="A"/><rect x="104" y="80" width="32" height="6" rx="3" fill="B"/>',
        'tank' => '<path d="M86 30 Q92 60 80 76 L76 205 L164 205 L160 76 Q148 60 154 30 L140 30 Q132 58 120 58 Q108 58 100 30 Z" fill="C"/><path d="M76 160 L164 120 L164 132 L76 172 Z" fill="A"/><path d="M76 176 L164 136 L164 144 L76 184 Z" fill="B"/>',
        'jacket' => '<path d="M70 38 L102 26 L120 40 L138 26 L170 38 L198 70 L190 205 L156 205 L156 100 L120 100 L84 100 L84 205 L50 205 L42 70 Z" fill="C"/><path d="M84 100 L84 205 L156 205 L156 100" fill="C"/><line x1="120" y1="40" x2="120" y2="205" stroke="T" stroke-width="4"/><path d="M42 70 L50 205 L60 205 L54 72Z" fill="A"/><path d="M198 70 L190 205 L180 205 L186 72Z" fill="A"/><path d="M102 26 L120 40 L138 26" fill="none" stroke="B" stroke-width="5"/>',
        'hoodie' => '<path d="M84 46 Q120 0 156 46 L176 52 L204 84 L186 104 L172 96 L172 205 L68 205 L68 96 L54 104 L36 84 L64 52 Z" fill="C"/><path d="M92 50 Q120 18 148 50 Q120 70 92 50Z" fill="T" opacity=".35"/><path d="M90 150 L150 150 L156 185 L84 185 Z" fill="T" opacity=".18"/><rect x="68" y="196" width="104" height="9" fill="A"/><line x1="112" y1="56" x2="110" y2="86" stroke="B" stroke-width="3"/><line x1="128" y1="56" x2="130" y2="86" stroke="B" stroke-width="3"/>',
        'shorts' => '<path d="M62 60 L178 60 L196 180 L132 190 L120 120 L108 190 L44 180 Z" fill="C"/><rect x="62" y="60" width="116" height="14" fill="T" opacity=".35"/><path d="M44 180 L58 72 L66 72 L54 181Z" fill="A"/><path d="M196 180 L182 72 L174 72 L186 181Z" fill="A"/><text x="160" y="110" text-anchor="middle" font-family="Montserrat,Arial" font-weight="800" font-size="16" fill="B">10</text>',
        'leggings' => '<path d="M84 26 L156 26 L162 60 L150 214 L128 214 L120 90 L112 214 L90 214 L78 60 Z" fill="C"/><rect x="84" y="26" width="72" height="16" fill="T" opacity=".35"/><path d="M80 70 L90 214 L95 214 L86 70Z" fill="A"/><path d="M160 70 L150 214 L145 214 L154 70Z" fill="B"/>',
        'socks' => '<path d="M92 24 L136 24 L136 150 Q136 170 156 178 L186 190 Q204 200 194 214 L140 214 Q92 210 92 160 Z" fill="C"/><rect x="92" y="24" width="44" height="20" fill="T" opacity=".3"/><rect x="92" y="56" width="44" height="7" fill="A"/><rect x="92" y="68" width="44" height="7" fill="B"/>',
        'bag' => '<path d="M70 70 Q120 20 170 70" fill="none" stroke="T" stroke-width="8"/><rect x="34" y="70" width="172" height="120" rx="22" fill="C"/><rect x="34" y="160" width="172" height="30" rx="0" fill="T" opacity=".25"/><path d="M34 140 L206 100 L206 112 L34 152Z" fill="A"/><path d="M34 156 L206 116 L206 124 L34 164Z" fill="B"/><rect x="104" y="84" width="32" height="8" rx="4" fill="T" opacity=".6"/>',
        'cap' => '<path d="M50 140 Q50 56 120 56 Q190 56 190 140 Z" fill="C"/><path d="M120 140 Q200 140 222 160 Q170 172 110 158 Z" fill="T"/><circle cx="120" cy="56" r="6" fill="T"/><path d="M76 128 L164 92 L166 104 L78 140Z" fill="A"/><path d="M80 143 L168 107 L168 115 L82 150Z" fill="B"/>',
    ];
    $svg = strtr($shapes[$art] ?? $shapes['tshirt'], ['"C"' => '"' . $c . '"', '"A"' => '"' . $stripeA . '"', '"B"' => '"' . $stripeB . '"', '"T"' => '"' . $trim . '"']);
    return '<svg class="product-art ' . e($class) . '" viewBox="0 0 240 240" role="img" aria-label="' . e($label) . '"><rect width="240" height="240" fill="#F2F3F5"/><g transform="translate(0,6)">' . $svg . '</g></svg>';
}

/* ---------- Ürün sorguları ---------- */

function product_stock(int $productId): int
{
    return (int) val('SELECT COALESCE(SUM(stock),0) FROM product_variants WHERE product_id = ?', [$productId]);
}

function product_variants(int $productId): array
{
    return rows('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
}

function discount_percent(array $p): int
{
    return ($p['old_price'] && $p['old_price'] > $p['price']) ? (int) round((1 - $p['price'] / $p['old_price']) * 100) : 0;
}

/* ---------- Kargo ---------- */

function cargo_companies(): array
{
    $out = [];
    foreach (preg_split('/\R/', (string) setting('cargo_companies')) as $line) {
        [$name, $link] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        if ($name !== '') $out[$name] = $link;
    }
    return $out;
}

function tracking_url(?string $company, ?string $no): string
{
    if (!$company || !$no) return '';
    $tpl = cargo_companies()[$company] ?? '';
    return $tpl ? str_replace('{no}', rawurlencode($no), $tpl) : '';
}

/* ---------- Sepet (oturumda tutulur) ---------- */

function cart_items(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_key(int $variantId, string $name, string $number): string
{
    return $variantId . '|' . mb_strtoupper($name) . '|' . $number;
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'qty'));
}

function cart_add(int $variantId, int $qty, string $printName = '', string $printNumber = ''): string
{
    $v = row('SELECT v.*, p.is_active, p.personalizable, c.is_active AS cat_active FROM product_variants v JOIN products p ON p.id = v.product_id JOIN categories c ON c.id = p.category_id WHERE v.id = ?', [$variantId]);
    if (!$v || !$v['is_active'] || !$v['cat_active']) {
        return 'Ürün bulunamadı.';
    }
    if (!$v['personalizable']) {
        $printName = $printNumber = '';
    }
    $printName = mb_strtoupper(mb_substr(trim(preg_replace('/[^\p{L} .\-]/u', '', $printName)), 0, 14));
    $printNumber = substr(preg_replace('/\D/', '', $printNumber), 0, 2);
    $key = cart_key($variantId, $printName, $printNumber);
    $inCart = 0;
    foreach (cart_items() as $it) {
        if ((int) $it['variant_id'] === $variantId) $inCart += $it['qty'];
    }
    if ($v['stock'] <= 0) {
        return 'Bu beden/renk için stok tükendi.';
    }
    $qty = max(1, min($qty, 99));
    if ($inCart + $qty > $v['stock']) {
        return 'Bu beden/renkten en fazla ' . $v['stock'] . ' adet alabilirsiniz' . ($inCart ? ' (sepetinizde ' . $inCart . ' adet var).' : '.');
    }
    $_SESSION['cart'][$key] = [
        'variant_id' => $variantId,
        'qty' => ($_SESSION['cart'][$key]['qty'] ?? 0) + $qty,
        'print_name' => $printName,
        'print_number' => $printNumber,
    ];
    return '';
}

/**
 * Sepetin güncel özetini veritabanından hesaplar (fiyat, stok, kupon, kargo).
 * Fiyatlar her zaman sunucuda hesaplanır; oturumdaki veriler yalnızca seçimdir.
 */
function cart_summary(?string $couponCode = null): array
{
    $lines = [];
    $warnings = [];
    $subtotal = 0.0;
    $used = [];
    foreach (cart_items() as $key => $it) {
        $v = row('SELECT v.*, p.name, p.slug, p.price, p.personalizable, p.personalization_price, p.images, p.art, p.art_color, p.is_active, c.is_active AS cat_active
                  FROM product_variants v JOIN products p ON p.id = v.product_id JOIN categories c ON c.id = p.category_id WHERE v.id = ?', [$it['variant_id']]);
        if (!$v || !$v['is_active'] || !$v['cat_active']) {
            unset($_SESSION['cart'][$key]);
            $warnings[] = 'Satıştan kaldırılan bir ürün sepetinizden çıkarıldı.';
            continue;
        }
        $available = $v['stock'] - ($used[$v['id']] ?? 0);
        $qty = min((int) $it['qty'], max(0, $available));
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
            $warnings[] = $v['name'] . ' (' . $v['size'] . ', ' . $v['color'] . ') stokta kalmadığı için sepetten çıkarıldı.';
            continue;
        }
        if ($qty < $it['qty']) {
            $_SESSION['cart'][$key]['qty'] = $qty;
            $warnings[] = $v['name'] . ' (' . $v['size'] . ') için stok ' . $qty . ' adet olduğundan miktar güncellendi.';
        }
        $used[$v['id']] = ($used[$v['id']] ?? 0) + $qty;
        $personal = $v['personalizable'] && ($it['print_name'] !== '' || $it['print_number'] !== '');
        $unit = (float) $v['price'] + ($personal ? (float) $v['personalization_price'] : 0);
        $lines[] = [
            'key' => $key, 'variant_id' => (int) $v['id'], 'product_id' => (int) $v['product_id'], 'name' => $v['name'], 'slug' => $v['slug'],
            'size' => $v['size'], 'color' => $v['color'], 'color_hex' => $v['color_hex'], 'qty' => $qty, 'stock' => (int) $v['stock'],
            'print_name' => $it['print_name'], 'print_number' => $it['print_number'], 'personal' => $personal,
            'unit_price' => $unit, 'line_total' => round($unit * $qty, 2), 'product' => $v,
        ];
        $subtotal += $unit * $qty;
    }
    $subtotal = round($subtotal, 2);

    $discount = 0.0;
    $coupon = null;
    $couponError = '';
    $couponCode = $couponCode ?? ($_SESSION['coupon'] ?? null);
    if ($couponCode && $lines) {
        [$coupon, $couponError] = coupon_check($couponCode, $subtotal);
        if ($coupon) {
            $discount = $coupon['type'] === 'percent' ? round($subtotal * min(100, (float) $coupon['value']) / 100, 2) : min($subtotal, (float) $coupon['value']);
        }
    }
    $afterDiscount = $subtotal - $discount;
    $limit = (float) setting('free_shipping_limit', '0');
    $fee = (float) setting('shipping_fee', '0');
    $shipping = (!$lines || ($limit > 0 && $afterDiscount >= $limit)) ? 0.0 : $fee;

    return [
        'lines' => $lines, 'warnings' => $warnings, 'count' => array_sum(array_column($lines, 'qty')),
        'subtotal' => $subtotal, 'discount' => $discount, 'coupon' => $coupon, 'coupon_error' => $couponError,
        'shipping' => $shipping, 'total' => round($afterDiscount + $shipping, 2),
        'free_shipping_left' => ($limit > 0 && $afterDiscount < $limit) ? round($limit - $afterDiscount, 2) : 0,
    ];
}

/** Kupon geçerli mi? [kupon, hata mesajı] */
function coupon_check(string $code, float $subtotal): array
{
    $c = row('SELECT * FROM coupons WHERE code = ?', [mb_strtoupper(trim($code))]);
    $now = now();
    if (!$c || !$c['is_active']) return [null, 'Kupon kodu geçersiz.'];
    if ($c['starts_at'] && $c['starts_at'] > $now) return [null, 'Bu kupon henüz kullanıma açılmadı.'];
    if ($c['expires_at'] && $c['expires_at'] < $now) return [null, 'Bu kuponun süresi dolmuş.'];
    if ($c['max_uses'] > 0 && $c['used_count'] >= $c['max_uses']) return [null, 'Bu kuponun kullanım limiti dolmuş.'];
    if ($subtotal < (float) $c['min_total']) return [null, 'Bu kupon en az ' . money($c['min_total']) . ' tutarındaki alışverişlerde geçerlidir.'];
    return [$c, ''];
}

function coupon_label(array $c): string
{
    return $c['type'] === 'percent' ? '%' . rtrim(rtrim(number_format((float) $c['value'], 2, ',', ''), '0'), ',') . ' indirim' : money($c['value']) . ' indirim';
}

/* ---------- Stok hareketleri ---------- */

/** Ödeme onaylanınca stoktan düşer (bir kez) */
function order_reduce_stock(array $order): void
{
    if ($order['stock_reduced']) return;
    foreach (rows('SELECT variant_id, qty FROM order_items WHERE order_id = ? AND variant_id IS NOT NULL', [$order['id']]) as $it) {
        q('UPDATE product_variants SET stock = CASE WHEN stock >= ? THEN stock - ? ELSE 0 END WHERE id = ?', [$it['qty'], $it['qty'], $it['variant_id']]);
    }
    q('UPDATE orders SET stock_reduced = 1 WHERE id = ?', [$order['id']]);
}

/** İptal/iadede stoğu geri ekler (bir kez) */
function order_restore_stock(array $order): void
{
    if (!$order['stock_reduced']) return;
    foreach (rows('SELECT variant_id, qty FROM order_items WHERE order_id = ? AND variant_id IS NOT NULL', [$order['id']]) as $it) {
        q('UPDATE product_variants SET stock = stock + ? WHERE id = ?', [$it['qty'], $it['variant_id']]);
    }
    q('UPDATE orders SET stock_reduced = 0 WHERE id = ?', [$order['id']]);
}

function order_items_html(int $orderId): string
{
    $html = '<table style="width:100%;border-collapse:collapse;font-size:14px">';
    foreach (rows('SELECT * FROM order_items WHERE order_id = ?', [$orderId]) as $it) {
        $print = trim(($it['print_name'] ?? '') . ' ' . ($it['print_number'] ?? ''));
        $html .= '<tr><td style="padding:6px 0;border-bottom:1px solid #eee">' . e($it['product_name']) . ' <small>(' . e($it['size']) . ', ' . e($it['color']) . ($print ? ', Baskı: ' . e($print) : '') . ')</small></td>'
            . '<td style="padding:6px 0;border-bottom:1px solid #eee;text-align:center">' . (int) $it['qty'] . ' adet</td>'
            . '<td style="padding:6px 0;border-bottom:1px solid #eee;text-align:right">' . money($it['line_total']) . '</td></tr>';
    }
    return $html . '</table>';
}

/**
 * Yüklenen ürün görselini doğrular, en fazla 1400px olacak şekilde yeniden işler ve uploads/ klasörüne kaydeder.
 * Görsel yeniden kodlandığı için içine gizlenmiş zararlı içerik temizlenir. Başarılıysa dosya adını döner.
 */
function save_product_image(array $file, ?string &$error = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Dosya yüklenemedi (kod ' . ($file['error'] ?? '?') . ').';
        return null;
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        $error = 'Görsel en fazla 8 MB olabilir.';
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        $error = 'Yalnızca JPG, PNG veya WEBP görsel yükleyebilirsiniz.';
        return null;
    }
    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        $error = 'Görsel okunamadı.';
        return null;
    }
    [$w, $h] = [imagesx($src), imagesy($src)];
    $scale = min(1, 1400 / max($w, $h));
    $nw = (int) round($w * $scale);
    $nh = (int) round($h * $scale);
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $dir = ROOT . '/uploads/' . date('Y/m');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $error = 'uploads klasörü yazılabilir değil.';
        return null;
    }
    $name = date('Y/m') . '/' . bin2hex(random_bytes(8)) . (function_exists('imagewebp') ? '.webp' : '.jpg');
    $ok = function_exists('imagewebp') ? imagewebp($dst, ROOT . '/uploads/' . $name, 84) : imagejpeg($dst, ROOT . '/uploads/' . $name, 86);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) {
        $error = 'Görsel kaydedilemedi.';
        return null;
    }
    return $name;
}
