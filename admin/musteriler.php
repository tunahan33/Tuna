<?php
require __DIR__ . '/_layout.php';
$u = require_perm('customers.view');

$term = (string) input('q');
$params = [];
$where = '';
if ($term !== '') { $where = 'HAVING name LIKE ? OR email LIKE ? OR phone LIKE ?'; $params = ["%$term%", "%$term%", "%$term%"]; }
$sale = "'" . implode("','", SALE_STATUSES) . "'";
$all = rows("SELECT lower(email) email, MAX(customer_name) name, MAX(phone) phone, MAX(city) city, COUNT(*) orders,
        SUM(CASE WHEN status IN ($sale) THEN total ELSE 0 END) spent, MAX(created_at) last,
        (SELECT 1 FROM users WHERE lower(users.email) = lower(orders.email)) member
    FROM orders GROUP BY lower(email) $where ORDER BY last DESC", $params);
[$page, $pages, $offset, $per] = paginate(count($all), 30);
$list = array_slice($all, $offset, $per);
$members = (int) val("SELECT COUNT(*) FROM users WHERE role = 'uye'");
admin_header('Müşteriler', count($all) . ' sipariş veren müşteri · ' . $members . ' kayıtlı üye');
?>
<form class="filter-bar"><input name="q" value="<?= e($term) ?>" placeholder="Ad, e-posta veya telefon"><button class="btn btn-dark">Ara</button></form>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Müşteri</th><th>İletişim</th><th>İl</th><th>Sipariş</th><th>Toplam Harcama</th><th>Son Sipariş</th></tr></thead>
        <tbody>
        <?php foreach ($list as $c): ?>
            <tr data-href="<?= url('admin/siparisler.php?q=' . urlencode($c['email'])) ?>">
                <td><strong><?= e($c['name']) ?></strong> <?= $c['member'] ? badge('Üye', 'green') : badge('Misafir', 'gray') ?></td>
                <td class="small"><?= e($c['email']) ?><br><?= e($c['phone']) ?></td>
                <td><?= e($c['city']) ?></td>
                <td><?= (int) $c['orders'] ?></td>
                <td><strong><?= money($c['spent']) ?></strong></td>
                <td><?= tr_date($c['last']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
