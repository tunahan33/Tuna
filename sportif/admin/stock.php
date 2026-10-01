<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

$limit = (int) setting('low_stock_limit', '3');
if (is_post() && can('stock.edit')) {
    verify_csrf();
    $changes = [];
    foreach ((array) ($_POST['stock'] ?? []) as $vid => $val) {
        $v = row('SELECT v.*, p.name FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?', [(int) $vid]);
        if (!$v || $val === '' || (int) $val === (int) $v['stock']) continue;
        q('UPDATE product_variants SET stock = ? WHERE id = ?', [max(0, (int) $val), $v['id']]);
        $changes[] = $v['name'] . ' ' . $v['size'] . '/' . $v['color'] . ': ' . $v['stock'] . ' → ' . max(0, (int) $val);
    }
    if ($changes) log_activity('Stok güncelledi', mb_strimwidth(implode(' · ', $changes), 0, 900, '…'), 'product');
    flash('success', count($changes) . ' seçeneğin stoğu güncellendi.');
    redirect('admin/stock.php?' . http_build_query(['f' => input('f'), 'q' => input('q')]));
}

$f = input('f', 'az');
$where = ['1=1'];
$params = [];
if ($f === 'az') { $where[] = 'v.stock <= ?'; $params[] = $limit; }
if ($f === 'yok') { $where[] = 'v.stock <= 0'; }
if (($qq = input('q')) !== '') { $where[] = '(p.name LIKE ? OR v.sku LIKE ?)'; array_push($params, "%$qq%", "%$qq%"); }
$list = rows('SELECT v.*, p.name, p.id AS pid, c.name AS cat FROM product_variants v JOIN products p ON p.id = v.product_id JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY v.stock ASC, p.name, v.sort_order LIMIT 500', $params);
$counts = ['out' => (int) val('SELECT COUNT(*) FROM product_variants WHERE stock <= 0'), 'low' => (int) val('SELECT COUNT(*) FROM product_variants WHERE stock > 0 AND stock <= ?', [$limit]), 'total' => (int) val('SELECT COALESCE(SUM(stock),0) FROM product_variants')];
admin_header('Stok Takibi', 'Toplam <strong>' . $counts['total'] . '</strong> adet ürün stokta · Azalan stok sınırı: ' . $limit . ' adet');
?>
<div class="stat-grid">
    <a class="stat accent-red" href="?f=yok"><span>Tükenen Seçenek</span><strong><?= $counts['out'] ?></strong><small>Beden/renk bazında</small></a>
    <a class="stat accent-yellow" href="?f=az"><span>Azalan Stok</span><strong><?= $counts['low'] ?></strong><small><?= $limit ?> adet ve altı</small></a>
    <a class="stat" href="?f=hepsi"><span>Toplam Stok</span><strong><?= $counts['total'] ?></strong><small>Tüm seçenekleri gör</small></a>
</div>
<div class="toolbar">
    <div class="role-tabs"><a href="?f=az" class="<?= $f === 'az' ? 'active' : '' ?>">Azalanlar</a><a href="?f=yok" class="<?= $f === 'yok' ? 'active' : '' ?>">Tükenenler</a><a href="?f=hepsi" class="<?= $f === 'hepsi' ? 'active' : '' ?>">Tümü</a></div>
    <form method="get" class="inline-form"><input type="hidden" name="f" value="<?= e($f) ?>"><input name="q" value="<?= e($qq) ?>" placeholder="Ürün veya stok kodu"><button class="btn btn-dark btn-sm">Ara</button></form>
</div>
<form method="post" class="panel">
    <?= csrf_field() ?><input type="hidden" name="f" value="<?= e($f) ?>"><input type="hidden" name="q" value="<?= e($qq) ?>">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Ürün</th><th>Kategori</th><th>Beden</th><th>Renk</th><th>SKU</th><th class="num">Stok</th></tr></thead>
        <tbody>
        <?php foreach ($list as $v): ?>
            <tr class="<?= $v['stock'] <= 0 ? 'row-out' : ($v['stock'] <= $limit ? 'row-low' : '') ?>">
                <td><a href="product_edit.php?id=<?= (int) $v['pid'] ?>#stok"><strong><?= e($v['name']) ?></strong></a></td>
                <td><?= e($v['cat']) ?></td>
                <td><?= e($v['size']) ?></td>
                <td><i class="dot-color" style="background:<?= e($v['color_hex']) ?>"></i><?= e($v['color']) ?></td>
                <td><small><?= e($v['sku'] ?: '-') ?></small></td>
                <td class="num"><?php if (can('stock.edit')): ?><input type="number" min="0" name="stock[<?= (int) $v['id'] ?>]" value="<?= (int) $v['stock'] ?>" class="in-num"><?php else: ?><strong><?= (int) $v['stock'] ?></strong><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$list): ?><tr><td colspan="6" class="muted">Bu filtrede kayıt yok. 👍</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php if (can('stock.edit') && $list): ?><div class="form-actions"><button class="btn btn-primary">Stokları Kaydet</button></div><?php endif; ?>
</form>
<?php admin_footer();
