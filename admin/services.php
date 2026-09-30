<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

if (is_post()) {
    verify_csrf();
    $id = (int) input('id');
    $s = row('SELECT * FROM services WHERE id = ?', [$id]);
    if ($s && input('action') === 'toggle' && can('content.create')) {
        q('UPDATE services SET is_active = ?, updated_at = ? WHERE id = ?', [$s['is_active'] ? 0 : 1, now(), $id]);
        log_activity($s['is_active'] ? 'Hizmeti yayından kaldırdı' : 'Hizmeti yayına aldı', $s['title'], 'service', $id);
    } elseif ($s && input('action') === 'delete' && can('content.delete')) {
        if ((int) val('SELECT COUNT(*) FROM orders o JOIN packages p ON p.id = o.package_id WHERE p.service_id = ?', [$id])) {
            flash('error', 'Bu hizmete ait siparişler olduğu için silinemez. Yayından kaldırabilirsiniz.');
        } else {
            q('DELETE FROM packages WHERE service_id = ?', [$id]);
            q('DELETE FROM services WHERE id = ?', [$id]);
            log_activity('Hizmeti sildi', $s['title'], 'service', $id);
            flash('success', 'Hizmet silindi.');
        }
    } elseif ($s && in_array(input('action'), ['up', 'down'], true) && can('content.create')) {
        $list = rows('SELECT id FROM services ORDER BY sort_order, id');
        $ids = array_column($list, 'id');
        $pos = array_search($id, $ids);
        $swap = input('action') === 'up' ? $pos - 1 : $pos + 1;
        if (isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            foreach ($ids as $i => $sid) q('UPDATE services SET sort_order = ? WHERE id = ?', [$i + 1, $sid]);
            log_activity('Hizmet sırasını değiştirdi', $s['title'], 'service', $id);
        }
    }
    redirect('admin/services.php');
}

$services = rows('SELECT s.*, (SELECT COUNT(*) FROM packages p WHERE p.service_id = s.id) AS pc FROM services s ORDER BY sort_order, id');
admin_header('Hizmetler', count($services) . ' danışmanlık hizmeti');
?>
<div class="toolbar">
    <?php if (can('content.create')): ?><a class="btn btn-primary btn-sm" href="service_edit.php">+ Yeni Hizmet</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th></th><th>Hizmet</th><th>Paket</th><th>Durum</th><th>Güncelleme</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
            <tr>
                <td><span class="mini-icon"><?= service_icon($s['icon']) ?></span></td>
                <td><strong><?= e($s['title']) ?></strong><br><small class="muted"><?= e(mb_strimwidth($s['short_desc'], 0, 90, '…')) ?></small></td>
                <td><a href="packages.php?service=<?= (int) $s['id'] ?>"><?= (int) $s['pc'] ?> paket</a></td>
                <td><?= $s['is_active'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
                <td><?= tr_date($s['updated_at']) ?></td>
                <td class="actions">
                    <a class="btn btn-xs btn-outline" href="service_edit.php?id=<?= (int) $s['id'] ?>">Düzenle</a>
                    <a class="btn btn-xs btn-outline" target="_blank" href="<?= url('hizmet.php?slug=' . urlencode($s['slug'])) ?>">Gör</a>
                    <?php if (can('content.create')): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button name="action" value="up" class="btn btn-xs btn-outline" title="Yukarı">↑</button><button name="action" value="down" class="btn btn-xs btn-outline" title="Aşağı">↓</button><button name="action" value="toggle" class="btn btn-xs btn-outline"><?= $s['is_active'] ? 'Pasifleştir' : 'Yayına Al' ?></button></form>
                    <?php endif; ?>
                    <?php if (can('content.delete')): ?>
                        <form method="post" data-confirm="Hizmet ve tüm paketleri silinecek. Emin misiniz?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button name="action" value="delete" class="btn btn-xs btn-danger">Sil</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php admin_footer();
