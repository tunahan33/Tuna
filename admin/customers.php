<?php
require __DIR__ . '/_init.php';
$u = require_perm('customers.view');

$in = "'" . implode("','", SALE_STATUSES) . "'";
$params = [];
$w = "u.role = 'member'";
if (($qq = input('q')) !== '') { $w .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; array_push($params, ...array_fill(0, 3, "%$qq%")); }
$total = (int) val("SELECT COUNT(*) FROM users u WHERE $w", $params);
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status IN ($in)) AS oc, (SELECT COALESCE(SUM(amount),0) FROM orders o WHERE o.user_id = u.id AND o.status IN ($in)) AS spent, (SELECT MAX(created_at) FROM orders o WHERE o.user_id = u.id) AS last_order FROM users u WHERE $w ORDER BY u.id DESC LIMIT $per OFFSET $offset", $params);
admin_header('Müşteriler', $total . ' kayıtlı üye');
?>
<div class="toolbar"><form method="get" class="inline-form"><input name="q" value="<?= e(input('q')) ?>" placeholder="İsim, e-posta, telefon…"><button class="btn btn-dark btn-sm">Ara</button></form></div>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Müşteri</th><th>Telefon</th><th>Kayıt</th><th class="num">Satın Alma</th><th class="num">Toplam Harcama</th><th>Son Sipariş</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $c): ?>
            <tr>
                <td><strong><?= e($c['name']) ?></strong><br><small class="muted"><?= e($c['email']) ?></small></td>
                <td><?= e($c['phone'] ?: '-') ?></td>
                <td><?= tr_date($c['created_at'], false) ?></td>
                <td class="num"><?= (int) $c['oc'] ?></td>
                <td class="num"><strong><?= money($c['spent']) ?></strong></td>
                <td><?= tr_date($c['last_order']) ?></td>
                <td><a class="btn btn-xs btn-outline" href="orders.php?q=<?= urlencode($c['email']) ?>">Siparişleri</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
