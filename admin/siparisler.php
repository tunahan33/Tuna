<?php
require __DIR__ . '/_layout.php';
$u = require_perm('orders.view');

$where = ['1=1'];
$params = [];
if (isset(ORDER_STATUSES[input('durum')])) { $where[] = 'o.status = ?'; $params[] = input('durum'); }
if (input('benim')) { $where[] = 'o.assigned_to = ?'; $params[] = $u['id']; }
if (($term = input('q')) !== '') { $where[] = '(o.order_no LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ? OR o.phone LIKE ?)'; array_push($params, "%$term%", "%$term%", "%$term%", "%$term%"); }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', input('bas'))) { $where[] = 'date(o.created_at) >= ?'; $params[] = input('bas'); }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', input('bit'))) { $where[] = 'date(o.created_at) <= ?'; $params[] = input('bit'); }
$sqlWhere = implode(' AND ', $where);
$total = (int) val("SELECT COUNT(*) FROM orders o WHERE $sqlWhere", $params);
[$page, $pages, $offset, $per] = paginate($total, 25);
$list = rows("SELECT o.*, u.name rep, (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) items FROM orders o LEFT JOIN users u ON u.id = o.assigned_to WHERE $sqlWhere ORDER BY o.created_at DESC LIMIT $per OFFSET $offset", $params);
$counts = [];
foreach (rows('SELECT status, COUNT(*) n FROM orders GROUP BY status') as $r) $counts[$r['status']] = $r['n'];

admin_header('Siparişler', $total . ' sipariş listeleniyor');
?>
<div class="chips">
    <a href="?" class="<?= !input('durum') && !input('benim') ? 'active' : '' ?>">Tümü <em><?= array_sum($counts) ?></em></a>
    <?php foreach (ORDER_STATUSES as $k => [$l]): ?><a href="?durum=<?= $k ?>" class="<?= input('durum') === $k ? 'active' : '' ?>"><?= $l ?> <em><?= $counts[$k] ?? 0 ?></em></a><?php endforeach; ?>
    <a href="?benim=1" class="<?= input('benim') ? 'active' : '' ?>">Bana atananlar</a>
</div>
<form class="filter-bar">
    <?php if (input('durum')): ?><input type="hidden" name="durum" value="<?= e(input('durum')) ?>"><?php endif; ?>
    <input name="q" value="<?= e($term) ?>" placeholder="Sipariş no, müşteri, e-posta, telefon">
    <label>Başlangıç <input type="date" name="bas" value="<?= e(input('bas')) ?>"></label>
    <label>Bitiş <input type="date" name="bit" value="<?= e(input('bit')) ?>"></label>
    <button class="btn btn-dark">Filtrele</button>
</form>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Müşteri</th><th>Adet</th><th>Tutar</th><th>Durum</th><th>Sorumlu</th></tr></thead>
        <tbody>
        <?php if (!$list): ?><tr><td colspan="7" class="muted">Kayıt bulunamadı.</td></tr><?php endif; ?>
        <?php foreach ($list as $o): ?>
            <tr data-href="<?= url('admin/siparis.php?id=' . $o['id']) ?>">
                <td><strong><?= e($o['order_no']) ?></strong></td>
                <td><?= tr_date($o['created_at']) ?></td>
                <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['city']) ?></small></td>
                <td><?= (int) $o['items'] ?></td>
                <td><strong><?= money($o['total']) ?></strong></td>
                <td><?= order_badge($o['status']) ?></td>
                <td><?= e($o['rep'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
