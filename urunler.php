<?php
require __DIR__ . '/app/bootstrap.php';

$cats = rows('SELECT c.* FROM categories c WHERE EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id AND p.active = 1) ORDER BY c.sort');
$cat = null;
$where = ['p.active = 1'];
$params = [];
if (input('kategori') !== '') {
    $cat = row('SELECT * FROM categories WHERE slug = ?', [input('kategori')]);
    if ($cat) {
        $where[] = 'p.category_id = ?';
        $params[] = $cat['id'];
    }
}
if (($term = input('q')) !== '') {
    $where[] = '(p.name LIKE ? OR p.short_desc LIKE ?)';
    $params[] = "%$term%";
    $params[] = "%$term%";
}
if (input('indirim')) {
    $where[] = 'p.old_price > p.price';
}
$sorts = ['onerilen' => 'p.featured DESC, p.id', 'artan' => 'p.price ASC', 'azalan' => 'p.price DESC', 'yeni' => 'p.id DESC'];
$sort = $sorts[input('sirala')] ?? $sorts['onerilen'];
$products = rows('SELECT p.* FROM products p WHERE ' . implode(' AND ', $where) . " ORDER BY p.stock > 0 DESC, $sort", $params);

$title = $cat['name'] ?? ($term ? "“{$term}” araması" : (input('indirim') ? 'İndirimdeki Ürünler' : 'Tüm Ürünler'));
require __DIR__ . '/app/header.php';
?>
<section class="page-head">
    <div class="container">
        <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <?= e($title) ?></nav>
        <h1><?= e($title) ?></h1>
        <p><?= e($cat['description'] ?? 'Sarı-kırmızı ruhu taşıyan tüm sportif ürünler') ?></p>
    </div>
</section>
<section class="section-sm">
    <div class="container list-layout">
        <aside class="filters">
            <h4>KATEGORİ</h4>
            <a href="<?= url('urunler.php') ?>" class="<?= !$cat && !input('indirim') ? 'active' : '' ?>">Tüm Ürünler</a>
            <?php foreach ($cats as $c): ?><a href="<?= url('urunler.php?kategori=' . $c['slug']) ?>" class="<?= ($cat['id'] ?? 0) == $c['id'] ? 'active' : '' ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
            <h4>FIRSATLAR</h4>
            <a href="<?= url('urunler.php?indirim=1') ?>" class="<?= input('indirim') ? 'active' : '' ?>">İndirimdekiler</a>
        </aside>
        <div>
            <div class="list-bar">
                <span class="muted"><?= count($products) ?> ürün listeleniyor</span>
                <form>
                    <?php foreach (['kategori', 'q', 'indirim'] as $k): if (input($k) !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= e(input($k)) ?>"><?php endif; endforeach; ?>
                    <select name="sirala" data-autosubmit aria-label="Sırala">
                        <?php foreach (['onerilen' => 'Önerilen', 'artan' => 'Fiyat: Düşükten yükseğe', 'azalan' => 'Fiyat: Yüksekten düşüğe', 'yeni' => 'En yeniler'] as $k => $l): ?>
                            <option value="<?= $k ?>" <?= input('sirala') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php if (!$products): ?>
                <div class="card empty"><h3>Ürün bulunamadı</h3><p class="muted">Farklı bir arama yapmayı deneyin.</p><a class="btn btn-primary" href="<?= url('urunler.php') ?>">Tüm Ürünler</a></div>
            <?php else: ?>
                <div class="product-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))"><?php foreach ($products as $p) echo product_card($p); ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
