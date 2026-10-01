<?php
require __DIR__ . '/_init.php';
$u = require_perm('orders.view');

$where = ['1=1'];
$params = [];
$f = [
    'q' => input('q'), 'status' => input('status'), 'from' => input('from'), 'to' => input('to'),
    'mine' => input('mine'), 'service' => input('service'),
];
if ($f['q'] !== '') {
    $where[] = '(order_no LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ? OR tracking_no LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $f['q'] . '%'));
}
if ($f['status'] === 'sales') {
    $where[] = "status IN ('" . implode("','", SALE_STATUSES) . "')";
} elseif (isset(ORDER_STATUSES[$f['status']])) {
    $where[] = 'status = ?';
    $params[] = $f['status'];
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) {
    $where[] = 'created_at >= ?';
    $params[] = $f['from'] . ' 00:00:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) {
    $where[] = 'created_at <= ?';
    $params[] = $f['to'] . ' 23:59:59';
}
if ($f['mine']) {
    $where[] = 'assigned_to = ?';
    $params[] = $u['id'];
}
if ($f['service'] === 'baski') {
    $where[] = "EXISTS (SELECT 1 FROM order_items i WHERE i.order_id = orders.id AND (COALESCE(i.print_name,'') <> '' OR COALESCE(i.print_number,'') <> ''))";
} elseif ($f['service'] === 'kargo_bekleyen') {
    $where[] = "status IN ('paid','preparing')";
}
$w = implode(' AND ', $where);
$total = (int) val("SELECT COUNT(*) FROM orders WHERE $w", $params);
$sum = (float) val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE $w AND status IN ('" . implode("','", SALE_STATUSES) . "')", $params);
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows("SELECT * FROM orders WHERE $w ORDER BY id DESC LIMIT $per OFFSET $offset", $params);

admin_header('Siparişler', $total . ' kayıt · Filtredeki satış toplamı: <strong>' . money($sum) . '</strong>');
?>
<form class="panel filters" method="get">
    <input name="q" value="<?= e($f['q']) ?>" placeholder="Sipariş no, müşteri, e-posta, telefon…">
    <select name="status">
        <option value="">Tüm durumlar</option>
        <option value="sales" <?= $f['status'] === 'sales' ? 'selected' : '' ?>>Sadece satışlar (ödenmiş)</option>
        <?php foreach (ORDER_STATUSES as $k => [$l]): ?><option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
    <select name="service">
        <option value="">Tüm siparişler</option>
        <option value="kargo_bekleyen" <?= $f['service'] === 'kargo_bekleyen' ? 'selected' : '' ?>>Kargoya verilmeyi bekleyenler</option>
        <option value="baski" <?= $f['service'] === 'baski' ? 'selected' : '' ?>>Baskılı ürün içerenler</option>
    </select>
    <label class="inline">Başlangıç<input type="date" name="from" value="<?= e($f['from']) ?>"></label>
    <label class="inline">Bitiş<input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    <label class="inline chk"><input type="checkbox" name="mine" value="1" <?= $f['mine'] ? 'checked' : '' ?>> Bana atananlar</label>
    <button class="btn btn-dark btn-sm">Filtrele</button>
    <a class="btn btn-outline btn-sm" href="orders.php">Temizle</a>
</form>
<section class="panel">
    <?php include __DIR__ . '/_orders_table.php'; ?>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
