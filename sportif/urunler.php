<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$cat = input('kategori') !== '' ? row('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [input('kategori')]) : null;
$where = ['p.is_active = 1', 'c.is_active = 1'];
$params = [];
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat['id']; }
if (($q = mb_substr(input('q'), 0, 60)) !== '') { $where[] = '(p.name LIKE ? OR p.short_desc LIKE ? OR c.name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$genders = array_values(array_intersect((array) ($_GET['cinsiyet'] ?? []), array_keys(GENDERS)));
if ($genders) { $where[] = 'p.gender IN (' . implode(',', array_fill(0, count($genders), '?')) . ')'; array_push($params, ...$genders); }
$size = mb_substr(input('beden'), 0, 20);
if ($size !== '') { $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.size = ? AND v.stock > 0)'; $params[] = $size; }
$color = mb_substr(input('renk'), 0, 60);
if ($color !== '') { $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.color = ?)'; $params[] = $color; }
if (is_numeric(input('min'))) { $where[] = 'p.price >= ?'; $params[] = (float) input('min'); }
if (is_numeric(input('max'))) { $where[] = 'p.price <= ?'; $params[] = (float) input('max'); }
if (input('indirim')) { $where[] = 'p.old_price > p.price'; }
if (input('stokta')) { $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.stock > 0)'; }
$sorts = ['onerilen' => ['Önerilen', 'p.is_featured DESC, p.sort_order'], 'yeni' => ['En Yeniler', 'p.id DESC'], 'artan' => ['Fiyat: Artan', 'p.price ASC'], 'azalan' => ['Fiyat: Azalan', 'p.price DESC'], 'indirim' => ['İndirim Oranı', '(COALESCE(p.old_price, p.price) - p.price) / COALESCE(p.old_price, p.price) DESC']];
$sort = isset($sorts[input('sirala')]) ? input('sirala') : 'onerilen';
$w = implode(' AND ', $where);

$total = (int) val("SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE $w", $params);
$per = 24;
$page = max(1, min((int) input('p', 1), (int) ceil(max(1, $total) / $per)));
$list = rows("SELECT p.*, (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.id) AS total_stock FROM products p JOIN categories c ON c.id = p.category_id WHERE $w ORDER BY {$sorts[$sort][1]} LIMIT $per OFFSET " . (($page - 1) * $per), $params);

// Filtre seçenekleri (seçili kategori kapsamında)
$scope = $cat ? 'AND p.category_id = ' . (int) $cat['id'] : '';
$allSizes = array_column(rows("SELECT DISTINCT v.size FROM product_variants v JOIN products p ON p.id = v.product_id WHERE p.is_active = 1 $scope"), 'size');
$order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
usort($allSizes, fn($a, $b) => ($order[$a] ?? 50) <=> ($order[$b] ?? 50) ?: strnatcmp($a, $b));
$allColors = rows("SELECT v.color, MIN(v.color_hex) AS hex FROM product_variants v JOIN products p ON p.id = v.product_id WHERE p.is_active = 1 $scope GROUP BY v.color ORDER BY v.color");

$qs = function (array $change) {
    $p = array_merge($_GET, $change);
    unset($p['p']);
    return '?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null && $v !== []));
};
$pageTitle = $cat['name'] ?? ($q !== '' ? '"' . $q . '" arama sonuçları' : (input('indirim') ? 'İndirimdeki Ürünler' : 'Tüm Ürünler'));
$pageDesc = $cat['description'] ?? null;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <?= $cat ? '<a href="' . url('urunler.php') . '">Ürünler</a> / ' . e($cat['name']) : e($pageTitle) ?></nav>
    <h1><?= e($pageTitle) ?></h1>
    <?php if (!empty($cat['description'])): ?><p><?= e($cat['description']) ?></p><?php endif; ?>
</div></section>
<section class="section-sm"><div class="container shop-layout">
    <aside class="filters-side" data-filters>
        <form method="get">
            <?php if ($cat): ?><input type="hidden" name="kategori" value="<?= e($cat['slug']) ?>"><?php endif; ?>
            <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
            <input type="hidden" name="sirala" value="<?= e($sort) ?>">
            <div class="f-group"><h4>Cinsiyet</h4>
                <?php foreach (GENDERS as $k => $l): ?><label class="f-check"><input type="checkbox" name="cinsiyet[]" value="<?= $k ?>" <?= in_array($k, $genders, true) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?>
            </div>
            <div class="f-group"><h4>Beden</h4><div class="f-sizes">
                <label><input type="radio" name="beden" value="" <?= $size === '' ? 'checked' : '' ?>><span>Tümü</span></label>
                <?php foreach ($allSizes as $s): ?><label><input type="radio" name="beden" value="<?= e($s) ?>" <?= $size === $s ? 'checked' : '' ?>><span><?= e($s) ?></span></label><?php endforeach; ?>
            </div></div>
            <div class="f-group"><h4>Renk</h4><div class="f-colors">
                <label title="Tümü"><input type="radio" name="renk" value="" <?= $color === '' ? 'checked' : '' ?>><span class="all">Tümü</span></label>
                <?php foreach ($allColors as $c): ?><label title="<?= e($c['color']) ?>"><input type="radio" name="renk" value="<?= e($c['color']) ?>" <?= $color === $c['color'] ? 'checked' : '' ?>><span style="background:<?= e($c['hex']) ?>"></span></label><?php endforeach; ?>
            </div></div>
            <div class="f-group"><h4>Fiyat (₺)</h4><div class="f-price"><input type="number" name="min" min="0" placeholder="En az" value="<?= e(input('min')) ?>"><input type="number" name="max" min="0" placeholder="En çok" value="<?= e(input('max')) ?>"></div></div>
            <div class="f-group">
                <label class="f-check"><input type="checkbox" name="indirim" value="1" <?= input('indirim') ? 'checked' : '' ?>> Sadece indirimdekiler</label>
                <label class="f-check"><input type="checkbox" name="stokta" value="1" <?= input('stokta') ? 'checked' : '' ?>> Sadece stoktakiler</label>
            </div>
            <button class="btn btn-dark btn-block">Filtrele</button>
            <a class="btn btn-outline btn-block mt-05" href="<?= url('urunler.php' . ($cat ? '?kategori=' . urlencode($cat['slug']) : '')) ?>">Temizle</a>
        </form>
    </aside>
    <div>
        <div class="list-bar">
            <span><strong><?= $total ?></strong> ürün</span>
            <button type="button" class="btn btn-outline btn-sm filter-toggle" data-filter-toggle>Filtrele</button>
            <form method="get" class="sort-form">
                <?php foreach ($_GET as $k => $v): if (in_array($k, ['sirala', 'p'], true)) continue; foreach ((array) $v as $vv): ?><input type="hidden" name="<?= e($k) . (is_array($v) ? '[]' : '') ?>" value="<?= e($vv) ?>"><?php endforeach; endforeach; ?>
                <select name="sirala" onchange="this.form.submit()" aria-label="Sırala"><?php foreach ($sorts as $k => [$l]): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
            </form>
        </div>
        <?php if (!$list): ?>
            <div class="empty"><h3>Aradığın kriterlere uygun ürün bulunamadı</h3><p>Filtreleri azaltmayı deneyebilirsin.</p><a class="btn btn-primary" href="<?= url('urunler.php') ?>">Tüm Ürünler</a></div>
        <?php else: ?>
            <div class="product-grid"><?php foreach ($list as $p) echo product_card($p); ?></div>
            <?php if ($total > $per): ?><nav class="pager center-pager"><?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e($qs([]) . '&p=' . $i) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
        <?php endif; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
