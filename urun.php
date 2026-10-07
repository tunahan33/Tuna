<?php
require __DIR__ . '/app/bootstrap.php';

$p = row('SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.active = 1', [input('u')]);
if (!$p) {
    http_response_code(404);
    $title = 'Ürün bulunamadı';
    require __DIR__ . '/app/header.php';
    echo '<section class="section"><div class="container card empty"><h1>Ürün bulunamadı</h1><a class="btn btn-primary" href="' . url('urunler.php') . '">Tüm Ürünler</a></div></section>';
    require __DIR__ . '/app/footer.php';
    exit;
}

if (is_post()) {
    verify_csrf();
    $err = cart_add((int) $p['id'], (string) input('size'), (int) input('qty', 1));
    if ($err) {
        flash('error', $err);
        redirect('urun.php?u=' . $p['slug']);
    }
    flash('success', $p['name'] . ' sepetinize eklendi.');
    redirect(input('hemen') ? 'odeme.php' : 'sepet.php');
}

$sizes = product_sizes($p);
$images = product_images($p);
$off = discount_percent($p);
$features = features_list($p['features']);
$related = rows('SELECT * FROM products WHERE category_id = ? AND id <> ? AND active = 1 ORDER BY featured DESC LIMIT 5', [$p['category_id'], $p['id']]);
$title = $p['name'];
$description = $p['short_desc'];
require __DIR__ . '/app/header.php';
?>
<section class="section-sm">
    <div class="container">
        <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <a href="<?= url('urunler.php?kategori=' . $p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a> / <?= e($p['name']) ?></nav>
        <div class="pd">
            <div class="pd-gallery" data-gallery>
                <div class="pd-media"><?= product_media($p, 'pd-main') ?><?php if ($off): ?><span class="tag tag-red pd-off">%<?= $off ?> indirim</span><?php endif; ?></div>
                <?php if (count($images) > 1): ?>
                    <div class="pd-thumbs">
                        <?php foreach ($images as $i => $img): ?><button type="button" class="<?= $i ? '' : 'active' ?>" data-src="<?= e(url($img)) ?>" aria-label="Fotoğraf <?= $i + 1 ?>"><img src="<?= e(url($img)) ?>" alt=""></button><?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="pd-info">
                <a class="pd-cat" href="<?= url('urunler.php?kategori=' . $p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a>
                <h1><?= e($p['name']) ?></h1>
                <p class="pd-lead"><?= e($p['short_desc']) ?></p>
                <div class="pd-price">
                    <strong><?= money($p['price']) ?></strong>
                    <?php if ($off): ?><del><?= money($p['old_price']) ?></del><span class="tag tag-red">%<?= $off ?> indirim</span><?php endif; ?>
                    <small>Net fiyat · KDV dahil · <?= $p['price'] >= (float) setting('free_shipping_limit') ? 'Kargo bedava' : 'Kargo ' . money(setting('shipping_fee')) ?></small>
                </div>
                <form method="post">
                    <?= csrf_field() ?>
                    <?php if ($sizes): ?>
                        <div class="size-label"><span>Beden / Numara seçin</span></div>
                        <div class="sizes">
                            <?php foreach ($sizes as $s): ?><label><input type="radio" name="size" value="<?= e($s) ?>" required><span><?= e($s) ?></span></label><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="buy-row">
                        <div class="qty"><button type="button" data-qty="-1" aria-label="Azalt">−</button><input name="qty" type="number" value="1" min="1" max="<?= max(1, min(20, (int) $p['stock'])) ?>" aria-label="Adet"><button type="button" data-qty="1" aria-label="Arttır">+</button></div>
                        <button class="btn btn-primary btn-lg" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>><?= $p['stock'] <= 0 ? 'Tükendi' : 'Sepete Ekle' ?></button>
                    </div>
                    <?php if ($p['stock'] > 0): ?>
                        <button class="btn btn-dark btn-block mt" name="hemen" value="1" style="margin-top:10px">Hemen Satın Al</button>
                        <p class="stock-line <?= $p['stock'] <= 5 ? 'low' : 'ok' ?>"><?= $p['stock'] <= 5 ? '⚡ Acele edin, son ' . (int) $p['stock'] . ' ürün!' : '✓ Stokta, ' . e(setting('shipping_days')) . ' içinde kargoda' ?></p>
                    <?php endif; ?>
                </form>
                <ul class="pd-perks">
                    <li><b>🚚</b> <?= e(money(setting('free_shipping_limit'))) ?> ve üzeri siparişlerde ücretsiz kargo</li>
                    <li><b>↺</b> 14 gün içinde ücretsiz iade veya iade çeki</li>
                    <li><b>🔒</b> 256-bit SSL ve 3D Secure ile güvenli ödeme</li>
                </ul>
            </div>
        </div>

        <div class="tabs" data-tabs>
            <div class="tab-btns">
                <button class="active" data-tab="t-aciklama">Ürün Açıklaması</button>
                <?php if ($features): ?><button data-tab="t-ozellik">Özellikler</button><?php endif; ?>
                <button data-tab="t-kargo">Kargo ve İade</button>
            </div>
            <div class="tab-panel prose active" id="t-aciklama" style="box-shadow:none"><?= $p['description'] ?></div>
            <?php if ($features): ?>
                <div class="tab-panel" id="t-ozellik"><ul class="feature-list"><?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="tab-panel prose" id="t-kargo" style="box-shadow:none">
                <p><strong>Kargo:</strong> Siparişiniz <?= e(setting('shipping_days')) ?> içinde kargoya verilir. <?= e(money(setting('free_shipping_limit'))) ?> ve üzeri siparişlerde kargo ücretsizdir, altındaki siparişlerde kargo ücreti <?= e(money(setting('shipping_fee'))) ?>'dir.</p>
                <p><strong>İade:</strong> Teslimattan itibaren 14 gün içinde ücretsiz iade edebilir, dilerseniz bedeli iade çeki olarak alabilirsiniz. <a href="<?= url('sayfa.php?s=iade-ve-iade-ceki-kosullari') ?>">İade ve iade çeki koşulları</a></p>
                <p><strong>Arıza:</strong> Ürününüzde üretim kaynaklı bir sorun olursa <a href="<?= url('ariza-takibi.php') ?>">Arıza takibi</a> sayfasından kayıt açabilirsiniz.</p>
            </div>
        </div>

        <?php if ($related): ?>
            <div class="section-head mt" style="margin-top:48px"><h2>Bunlar da ilginizi çekebilir</h2></div>
            <div class="product-grid"><?php foreach ($related as $r) echo product_card($r); ?></div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
