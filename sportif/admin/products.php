<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

if (is_post()) {
    verify_csrf();
    $p = row('SELECT * FROM products WHERE id = ?', [(int) input('id')]);
    if ($p && input('action') === 'toggle' && can('content.create')) {
        q('UPDATE products SET is_active = ?, updated_at = ? WHERE id = ?', [$p['is_active'] ? 0 : 1, now(), $p['id']]);
        log_activity($p['is_active'] ? 'Ürünü yayından kaldırdı' : 'Ürünü yayına aldı', $p['name'], 'product', (int) $p['id']);
    } elseif ($p && input('action') === 'delete' && can('content.delete')) {
        if ((int) val('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$p['id']])) {
            flash('error', 'Bu ürün siparişlerde geçtiği için silinemez; yayından kaldırabilirsiniz.');
        } else {
            foreach (product_images($p) as $img) @unlink(ROOT . '/uploads/' . $img);
            q('DELETE FROM product_variants WHERE product_id = ?', [$p['id']]);
            q('DELETE FROM products WHERE id = ?', [$p['id']]);
            log_activity('Ürünü sildi', $p['name'], 'product', (int) $p['id']);
            flash('success', 'Ürün silindi.');
        }
    }
    redirect('admin/products.php?' . http_build_query(['category' => input('category'), 'q' => input('q')]));
}

$where = ['1=1'];
$params = [];
if ($cid = (int) input('category')) { $where[] = 'p.category_id = ?'; $params[] = $cid; }
if (($qq = input('q')) !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$qq%"; }
if (input('stok') === 'az') { $where[] = '(SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.id) <= ?'; $params[] = (int) setting('low_stock_limit', '3') * 3; }
$list = rows('SELECT p.*, c.name AS cat, (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.id) AS total_stock,
              (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.stock <= 0) AS out_count,
              (SELECT COALESCE(SUM(i.qty),0) FROM order_items i JOIN orders o ON o.id = i.order_id WHERE i.product_id = p.id AND o.status IN (\'' . implode("','", SALE_STATUSES) . '\')) AS sold
              FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.sort_order, p.sort_order, p.id', $params);
$cats = rows('SELECT id, name FROM categories ORDER BY sort_order');
admin_header('Ürünler', count($list) . ' ürün');
?>
<div class="toolbar">
    <form method="get" class="inline-form">
        <select name="category" onchange="this.form.submit()"><option value="">Tüm kategoriler</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cid === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <input name="q" value="<?= e($qq) ?>" placeholder="Ürün ara…">
        <label class="inline chk"><input type="checkbox" name="stok" value="az" <?= input('stok') === 'az' ? 'checked' : '' ?> onchange="this.form.submit()"> Stoğu azalanlar</label>
        <button class="btn btn-dark btn-sm">Ara</button>
    </form>
    <?php if (can('content.create')): ?><a class="btn btn-primary btn-sm push-right" href="product_edit.php">+ Yeni Ürün</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th></th><th>Ürün</th><th>Kategori</th><th class="num">Fiyat</th><th class="num">Stok</th><th class="num">Satılan</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $p): ?>
            <tr class="row-link" data-href="product_edit.php?id=<?= (int) $p['id'] ?>">
                <td class="thumb-cell"><?= product_thumb($p, 'mini-thumb') ?></td>
                <td><strong><?= e($p['name']) ?></strong> <?= $p['is_featured'] ? '<span class="badge badge-yellow">Öne çıkan</span>' : '' ?><?= $p['personalizable'] ? ' <span class="badge badge-blue">Baskı</span>' : '' ?></td>
                <td><?= e($p['cat']) ?></td>
                <td class="num"><strong><?= money($p['price']) ?></strong><?= $p['old_price'] ? '<br><del class="small muted">' . money($p['old_price']) . '</del>' : '' ?></td>
                <td class="num"><?= $p['total_stock'] <= 0 ? '<span class="badge badge-red">Tükendi</span>' : '<strong>' . (int) $p['total_stock'] . '</strong>' ?><?= $p['out_count'] > 0 && $p['total_stock'] > 0 ? '<br><small class="muted">' . (int) $p['out_count'] . ' seçenek tükendi</small>' : '' ?></td>
                <td class="num"><?= (int) $p['sold'] ?></td>
                <td><?= $p['is_active'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
                <td class="actions">
                    <a class="btn btn-xs btn-outline" href="product_edit.php?id=<?= (int) $p['id'] ?>">Düzenle</a>
                    <a class="btn btn-xs btn-outline" target="_blank" href="<?= url('urun.php?u=' . urlencode($p['slug'])) ?>">Gör</a>
                    <?php if (can('content.create')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="category" value="<?= $cid ?: '' ?>"><button name="action" value="toggle" class="btn btn-xs btn-outline"><?= $p['is_active'] ? 'Pasifleştir' : 'Yayına Al' ?></button></form><?php endif; ?>
                    <?php if (can('content.delete')): ?><form method="post" data-confirm="Ürün silinsin mi?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button name="action" value="delete" class="btn btn-xs btn-danger">Sil</button></form><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php admin_footer();
