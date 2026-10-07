<?php
require __DIR__ . '/_layout.php';
$u = require_perm('products.text');

$where = ['1=1'];
$params = [];
if (($term = input('q')) !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$term%"; }
if ((int) input('kategori')) { $where[] = 'p.category_id = ?'; $params[] = (int) input('kategori'); }
if (input('stok') === 'az') { $where[] = 'p.stock <= 5'; }
$list = rows('SELECT p.*, c.name cat, (SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = p.id AND o.status IN (\'yeni\',\'hazirlaniyor\',\'kargoda\',\'teslim\')) sold
    FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.sort, p.id', $params);
$cats = rows('SELECT * FROM categories ORDER BY sort');

admin_header('Ürünler', count($list) . ' ürün' . (can('products.manage') ? '' : ' · Editör olarak ürün metinlerini düzenleyebilirsiniz'),
    can('products.manage') ? '<a class="btn btn-primary" href="' . url('admin/urun-duzenle.php') . '">+ Yeni Ürün</a>' : '');
?>
<form class="filter-bar">
    <input name="q" value="<?= e($term) ?>" placeholder="Ürün ara">
    <select name="kategori"><option value="">Tüm kategoriler</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int) input('kategori') === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <select name="stok"><option value="">Tüm stoklar</option><option value="az" <?= input('stok') === 'az' ? 'selected' : '' ?>>Kritik stok (≤5)</option></select>
    <button class="btn btn-dark">Filtrele</button>
</form>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th></th><th>Ürün</th><th>Kategori</th><th>Fiyat</th><th>Stok</th><th>Satılan</th><th>Durum</th></tr></thead>
        <tbody>
        <?php foreach ($list as $p): ?>
            <tr data-href="<?= url('admin/urun-duzenle.php?id=' . $p['id']) ?>">
                <td style="width:56px"><div class="thumb"><?= product_media($p) ?></div></td>
                <td><strong><?= e($p['name']) ?></strong><?= $p['featured'] ? ' <span class="badge badge-yellow">Öne çıkan</span>' : '' ?><br><small class="muted"><?= e($p['short_desc']) ?></small></td>
                <td><?= e($p['cat']) ?></td>
                <td><strong><?= money($p['price']) ?></strong><?= $p['old_price'] > $p['price'] ? '<br><del class="muted small">' . money($p['old_price']) . '</del>' : '' ?></td>
                <td><?= $p['stock'] <= 0 ? badge('Tükendi', 'red') : ($p['stock'] <= 5 ? badge($p['stock'] . ' adet', 'yellow') : (int) $p['stock']) ?></td>
                <td><?= (int) $p['sold'] ?></td>
                <td><?= $p['active'] ? badge('Yayında', 'green') : badge('Gizli', 'gray') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php admin_footer();
