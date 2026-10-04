<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

if (is_post()) {
    verify_csrf();
    $id = (int) input('id');
    $p = row('SELECT * FROM packages WHERE id = ?', [$id]);
    if ($p && input('action') === 'price' && can('prices.edit')) {
        $price = (float) str_replace(',', '.', input('price'));
        if ($price > 0 && $price != $p['price']) {
            q('UPDATE packages SET price = ?, updated_at = ? WHERE id = ?', [$price, now(), $id]);
            log_activity('Fiyat değiştirdi', $p['name'] . ': ' . money($p['price']) . ' → ' . money($price), 'package', $id);
            flash('success', 'Fiyat güncellendi.');
        }
    } elseif ($p && input('action') === 'toggle' && can('content.create')) {
        q('UPDATE packages SET is_active = ?, updated_at = ? WHERE id = ?', [$p['is_active'] ? 0 : 1, now(), $id]);
        log_activity($p['is_active'] ? 'Paketi yayından kaldırdı' : 'Paketi yayına aldı', $p['name'], 'package', $id);
    } elseif ($p && input('action') === 'delete' && can('content.delete')) {
        q('DELETE FROM packages WHERE id = ?', [$id]);
        log_activity('Paketi sildi', $p['name'] . ' (' . money($p['price']) . ')', 'package', $id);
        flash('success', 'Paket silindi. (Geçmiş siparişler etkilenmez.)');
    }
    redirect('admin/packages.php' . (input('service') ? '?service=' . (int) input('service') : ''));
}

$filter = (int) input('service');
$services = rows('SELECT id, title FROM services ORDER BY sort_order, id');
admin_header('Paketler & Fiyatlar', can('prices.edit') ? 'Fiyatları doğrudan tablodan değiştirebilirsiniz (KDV dahil).' : 'Editör olarak açıklamaları düzenleyebilirsiniz; fiyat değişikliği admin yetkisindedir.');
?>
<div class="toolbar">
    <form method="get" class="inline-form">
        <select name="service" onchange="this.form.submit()"><option value="">Tüm hizmetler</option><?php foreach ($services as $s): ?><option value="<?= (int) $s['id'] ?>" <?= $filter === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?></select>
    </form>
    <?php if (can('content.create')): ?><a class="btn btn-primary btn-sm" href="package_edit.php<?= $filter ? '?service=' . $filter : '' ?>">+ Yeni Paket</a><?php endif; ?>
</div>
<?php foreach ($services as $s):
    if ($filter && $filter !== (int) $s['id']) continue;
    $packs = rows('SELECT * FROM packages WHERE service_id = ? ORDER BY sort_order, price', [$s['id']]); ?>
<section class="panel">
    <div class="panel-head"><h2><?= e($s['title']) ?></h2><a class="btn btn-xs btn-outline" href="service_edit.php?id=<?= (int) $s['id'] ?>">Hizmeti düzenle</a></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Paket</th><th>Süre</th><th>Fiyat (KDV dahil)</th><th>Durum</th><th>Satış</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($packs as $p):
            $sold = (int) val("SELECT COUNT(*) FROM orders WHERE package_id = ? AND status IN ('" . implode("','", SALE_STATUSES) . "')", [$p['id']]); ?>
            <tr>
                <td><strong><?= e($p['name']) ?></strong> <?= $p['is_featured'] ? '<span class="badge badge-yellow">Öne çıkan</span>' : '' ?><br><small class="muted"><?= e(mb_strimwidth($p['short_desc'], 0, 80, '…')) ?></small></td>
                <td><?= e($p['duration']) ?></td>
                <td>
                    <?php if (can('prices.edit')): ?>
                        <form method="post" class="price-form"><?= csrf_field() ?><input type="hidden" name="action" value="price"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="service" value="<?= $filter ?: '' ?>">
                            <input name="price" value="<?= e(number_format((float) $p['price'], 2, ',', '')) ?>" inputmode="decimal"><button class="btn btn-xs btn-dark">₺ Kaydet</button></form>
                    <?php else: ?>
                        <strong><?= money($p['price']) ?></strong>
                    <?php endif; ?>
                </td>
                <td><?= $p['is_active'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
                <td><?= $sold ?></td>
                <td class="actions">
                    <a class="btn btn-xs btn-outline" href="package_edit.php?id=<?= (int) $p['id'] ?>">Düzenle</a>
                    <?php if (can('content.create')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button name="action" value="toggle" class="btn btn-xs btn-outline"><?= $p['is_active'] ? 'Pasifleştir' : 'Yayına Al' ?></button></form><?php endif; ?>
                    <?php if (can('content.delete')): ?><form method="post" data-confirm="Paket silinecek. Emin misiniz?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button name="action" value="delete" class="btn btn-xs btn-danger">Sil</button></form><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$packs): ?><tr><td colspan="6" class="muted">Bu hizmette paket yok.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
<?php endforeach; ?>
<?php admin_footer();
