<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$p = row('SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.is_active = 1 AND c.is_active = 1', [input('u')]);
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Ürün bulunamadı';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container center"><h1>Ürün bulunamadı</h1><a class="btn btn-primary" href="' . url('urunler.php') . '">Tüm Ürünler</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$variants = product_variants((int) $p['id']);
$colors = [];
$sizes = [];
foreach ($variants as $v) {
    $colors[$v['color']] = $v['color_hex'];
    $sizes[$v['size']] = true;
}
$sizes = array_keys($sizes);
$stockMap = [];
foreach ($variants as $v) $stockMap[$v['color']][$v['size']] = (int) $v['stock'];
$selColor = isset($colors[input('renk')]) ? input('renk') : array_key_first($colors);
$imgs = product_images($p);
$off = discount_percent($p);
$related = rows('SELECT * FROM products WHERE category_id = ? AND id <> ? AND is_active = 1 ORDER BY is_featured DESC, sort_order LIMIT 4', [$p['category_id'], $p['id']]);
$lowLimit = (int) setting('low_stock_limit', '3');
$pageTitle = $p['name'];
$pageDesc = $p['short_desc'];
require __DIR__ . '/includes/header.php';
?>
<section class="section-sm"><div class="container">
    <nav class="crumbs dark"><a href="<?= url() ?>">Ana Sayfa</a> / <a href="<?= url('urunler.php?kategori=' . urlencode($p['cat_slug'])) ?>"><?= e($p['cat_name']) ?></a> / <?= e($p['name']) ?></nav>
    <div class="pd-grid">
        <div class="pd-gallery" data-gallery>
            <div class="pd-main"><?= product_thumb($p, 'pd-main-img') ?><?php if ($off): ?><span class="tag tag-red pd-off">%<?= $off ?> İndirim</span><?php endif; ?></div>
            <?php if (count($imgs) > 1): ?>
                <div class="pd-thumbs"><?php foreach ($imgs as $i => $img): ?><button type="button" class="<?= $i ? '' : 'active' ?>" data-src="<?= e(url('uploads/' . $img)) ?>"><img src="<?= e(url('uploads/' . $img)) ?>" alt=""></button><?php endforeach; ?></div>
            <?php endif; ?>
        </div>
        <div class="pd-info">
            <span class="eyebrow"><?= e($p['cat_name']) ?> · <?= e(GENDERS[$p['gender']] ?? '') ?></span>
            <h1 class="pd-title"><?= e($p['name']) ?></h1>
            <p class="muted"><?= e($p['short_desc']) ?></p>
            <div class="pd-price">
                <strong data-price data-base="<?= e($p['price']) ?>" data-extra="<?= e($p['personalization_price']) ?>"><?= money($p['price']) ?></strong>
                <?php if ($off): ?><del><?= money($p['old_price']) ?></del><?php endif; ?>
                <small>KDV dahil</small>
            </div>
            <form method="post" action="<?= url('sepet.php') ?>" class="pd-form" data-variant-form data-stock='<?= e(json_encode($stockMap, JSON_UNESCAPED_UNICODE)) ?>' data-low="<?= $lowLimit ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                <div class="opt">
                    <div class="opt-label">Renk: <strong data-color-label><?= e($selColor) ?></strong></div>
                    <div class="swatches">
                        <?php foreach ($colors as $name => $hex): ?>
                            <label title="<?= e($name) ?>"><input type="radio" name="color" value="<?= e($name) ?>" <?= $name === $selColor ? 'checked' : '' ?> required><span style="background:<?= e($hex) ?>"></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="opt">
                    <div class="opt-label">Beden: <strong data-size-label>Seçiniz</strong> <a href="<?= url('sayfa.php?s=beden-tablosu') ?>" target="_blank" class="size-guide">Beden tablosu</a></div>
                    <div class="sizes">
                        <?php foreach ($sizes as $s): $st = $stockMap[$selColor][$s] ?? 0; ?>
                            <label class="<?= $st <= 0 ? 'out' : '' ?>"><input type="radio" name="size" value="<?= e($s) ?>" <?= $st <= 0 ? 'disabled' : '' ?> required><span><?= e($s) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="stock-note" data-stock-note></div>
                </div>
                <?php if ($p['personalizable']): ?>
                    <details class="opt personal" data-personal>
                        <summary>👕 İsim ve numara baskısı ekle <span>+<?= money($p['personalization_price']) ?></span></summary>
                        <div class="grid-2">
                            <label>İsim <small>(en fazla 14 harf)</small><input name="print_name" maxlength="14" placeholder="ÖRN. YILMAZ" data-print></label>
                            <label>Numara<input name="print_number" maxlength="2" inputmode="numeric" pattern="[0-9]{0,2}" placeholder="10" data-print></label>
                        </div>
                        <p class="small muted">Baskılı ürünler kişiye özel üretildiği için iade edilemez; hazırlık süresi 3 iş gününe kadar uzayabilir.</p>
                    </details>
                <?php endif; ?>
                <div class="buy-row">
                    <div class="qty"><button type="button" data-qty="-1" aria-label="Azalt">−</button><input name="qty" type="number" value="1" min="1" max="99" aria-label="Adet"><button type="button" data-qty="1" aria-label="Arttır">+</button></div>
                    <button class="btn btn-primary btn-lg add-btn" <?= product_stock((int) $p['id']) <= 0 ? 'disabled' : '' ?>><?= product_stock((int) $p['id']) <= 0 ? 'Tükendi' : 'Sepete Ekle' ?></button>
                </div>
            </form>
            <ul class="pd-perks">
                <li>🚚 <?= (float) setting('free_shipping_limit') > 0 ? money(setting('free_shipping_limit')) . ' üzeri kargo bedava' : 'Hızlı kargo' ?> · <?= e(setting('shipping_days')) ?> içinde kargoda</li>
                <li>↺ 14 gün içinde kolay iade ve beden değişimi</li>
                <li>🔒 Garanti BBVA 3D Secure ile güvenli ödeme</li>
            </ul>
        </div>
    </div>
    <div class="pd-tabs">
        <div class="prose card">
            <h2 class="h3">Ürün Açıklaması</h2>
            <?= $p['description'] ?>
            <?php if ($f = features_list($p['features'])): ?><h3>Öne çıkan özellikler</h3><ul class="check-list"><?php foreach ($f as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul><?php endif; ?>
        </div>
    </div>
    <?php if ($related): ?>
        <h2 class="h3 mt-2">Bunlar da ilgini çekebilir</h2>
        <div class="product-grid"><?php foreach ($related as $r) echo product_card($r); ?></div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
