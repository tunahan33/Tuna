<?php
require __DIR__ . '/app/bootstrap.php';

$featured = rows("SELECT * FROM products WHERE active = 1 ORDER BY featured DESC, images <> '[]' DESC, id LIMIT 10");
$deals = rows('SELECT * FROM products WHERE active = 1 AND old_price > price ORDER BY (old_price - price) DESC LIMIT 5');
$cats = rows('SELECT c.* FROM categories c WHERE EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id AND p.active = 1) ORDER BY c.sort');
foreach ($cats as &$c) {
    // Kategori kartında, fotoğrafı olan ürün öncelikli gösterilir
    $c['cover'] = row("SELECT * FROM products WHERE category_id = ? AND active = 1 ORDER BY images <> '[]' DESC, featured DESC, id LIMIT 1", [$c['id']]);
}
unset($c);
// Vitrin: fotoğrafı olan en değerli öne çıkan ürün
$hero = row("SELECT * FROM products WHERE active = 1 AND featured = 1 ORDER BY images <> '[]' DESC, price DESC LIMIT 1");
require __DIR__ . '/app/header.php';
?>
<section class="hero">
    <div class="container hero-row">
        <div>
            <span class="eyebrow">YENİ SEZON</span>
            <h1>Sahada da tribünde de <span>sarı-kırmızı</span> gurur.</h1>
            <p>Tişörtler, eşofmanlar, kramponlar ve motorsport ürünleri. <?= e(money(setting('free_shipping_limit'))) ?> üzeri kargo bedava, 14 gün ücretsiz iade.</p>
            <div class="hero-actions">
                <a class="btn btn-yellow btn-lg" href="<?= url('urunler.php') ?>">Alışverişe Başla</a>
                <a class="btn btn-ghost btn-lg" href="<?= url('urunler.php?indirim=1') ?>">İndirimleri Gör</a>
            </div>
        </div>
        <?php if ($hero): ?>
            <a class="hero-art" href="<?= url('urun.php?u=' . $hero['slug']) ?>">
                <?= product_media($hero) ?>
                <span class="stamp"><?= e($hero['name']) ?><small><?= money($hero['price']) ?></small></span>
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head"><h2>Kategoriler</h2><a href="<?= url('urunler.php') ?>">Tümünü gör →</a></div>
        <div class="cat-grid">
            <?php foreach ($cats as $c): ?>
                <a class="cat-card" href="<?= url('urunler.php?kategori=' . $c['slug']) ?>">
                    <?= $c['cover'] ? product_media($c['cover']) : product_art(['art' => 'forma', 'color' => '#C8102E', 'name' => $c['name']]) ?>
                    <strong><?= e($c['name']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head"><h2>Öne Çıkanlar</h2><a href="<?= url('urunler.php') ?>">Tüm ürünler →</a></div>
        <div class="product-grid"><?php foreach ($featured as $p) echo product_card($p); ?></div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container promo">
        <div class="promo-box red">
            <h3>Kargo bedava!</h3>
            <p><?= e(money(setting('free_shipping_limit'))) ?> ve üzeri tüm siparişlerde kargo bizden.</p>
            <div><a class="btn btn-yellow" href="<?= url('urunler.php') ?>">Hemen Keşfet</a></div>
        </div>
        <div class="promo-box dark">
            <h3>İade çeki ile <span>anında</span> alışveriş</h3>
            <p>İade bedelini iade çeki olarak alın, bir sonraki alışverişinizde hemen kullanın.</p>
            <div><a class="btn btn-ghost" href="<?= url('sayfa.php?s=iade-ve-iade-ceki-kosullari') ?>">Koşulları Oku</a></div>
        </div>
    </div>
</section>

<?php if ($deals): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head"><h2>Fırsat Ürünleri</h2><a href="<?= url('urunler.php?indirim=1') ?>">Tüm indirimler →</a></div>
        <div class="product-grid"><?php foreach ($deals as $p) echo product_card($p); ?></div>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/app/footer.php'; ?>
