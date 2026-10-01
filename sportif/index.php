<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$cats = rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS pc FROM categories c WHERE c.is_active = 1 ORDER BY sort_order, id');
$featured = rows('SELECT p.* FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND c.is_active = 1 AND p.is_featured = 1 ORDER BY p.sort_order LIMIT 8');
$deals = rows('SELECT p.* FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND c.is_active = 1 AND p.old_price > p.price ORDER BY (p.old_price - p.price) / p.old_price DESC LIMIT 4');
$catArt = ['formalar' => 'jersey', 'antrenman' => 'tshirt', 'esofman' => 'jacket', 'sort-tayt' => 'shorts', 'sweatshirt' => 'hoodie', 'aksesuar' => 'bag'];
require __DIR__ . '/includes/header.php';
?>
<section class="shop-hero">
    <div class="container shop-hero-inner">
        <div class="hero-text">
            <span class="eyebrow">Yeni Sezon Koleksiyonu</span>
            <h1>Sahada <span class="hl-yellow">fark yaratanların</span> <span class="hl-red">giyimi</span>.</h1>
            <p><?= e(setting('site_description')) ?></p>
            <div class="hero-cta">
                <a href="<?= url('urunler.php') ?>" class="btn btn-primary btn-lg">Alışverişe Başla</a>
                <a href="<?= url('takim-siparisi.php') ?>" class="btn btn-ghost btn-lg">Takım Siparişi Ver</a>
            </div>
        </div>
        <div class="hero-products" aria-hidden="true">
            <div class="hp hp-1"><?= product_art('jersey', '#C8102E') ?></div>
            <div class="hp hp-2"><?= product_art('hoodie', '#2B2F33') ?></div>
            <div class="hp hp-3"><?= product_art('shorts', '#FDB913') ?></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head"><span class="eyebrow">Kategoriler</span><h2>Ne arıyorsun?</h2></div>
        <div class="cat-grid">
            <?php foreach ($cats as $c): ?>
                <a class="cat-card" href="<?= url('urunler.php?kategori=' . urlencode($c['slug'])) ?>" style="--cat: <?= e($c['color']) ?>">
                    <?= product_art($catArt[$c['slug']] ?? 'tshirt', $c['color']) ?>
                    <div><h3><?= e($c['name']) ?></h3><span><?= (int) $c['pc'] ?> ürün →</span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<section class="section section-muted">
    <div class="container">
        <div class="section-head left flex-head"><div><span class="eyebrow">Öne Çıkanlar</span><h2>En çok tercih edilenler</h2></div><a class="btn btn-outline btn-sm" href="<?= url('urunler.php') ?>">Tümünü Gör</a></div>
        <div class="product-grid"><?php foreach ($featured as $p) echo product_card($p); ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container team-banner">
        <div>
            <span class="eyebrow">Kulüpler &amp; Okullar İçin</span>
            <h2>Takımına özel forma, isim ve numara baskısıyla</h2>
            <p>En az <?= (int) setting('team_min_qty', '10') ?> adetlik siparişlerde takımına özel fiyat teklifi alırsın. Logo, isim ve numara baskılı formalar, eşofman takımları ve çantalar.</p>
            <a class="btn btn-yellow btn-lg" href="<?= url('takim-siparisi.php') ?>">Ücretsiz Teklif Al</a>
        </div>
        <div class="team-art"><?= product_art('jersey', '#FDB913') ?><?= product_art('jersey', '#C8102E') ?></div>
    </div>
</section>

<?php if ($deals): ?>
<section class="section section-muted">
    <div class="container">
        <div class="section-head left flex-head"><div><span class="eyebrow">Fırsatlar</span><h2>İndirimdeki ürünler</h2></div><a class="btn btn-outline btn-sm" href="<?= url('urunler.php?indirim=1') ?>">Tüm İndirimler</a></div>
        <div class="product-grid"><?php foreach ($deals as $p) echo product_card($p); ?></div>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
