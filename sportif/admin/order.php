<?php
require __DIR__ . '/_init.php';
$u = require_perm('orders.view');

$id = (int) input('id');
$o = row('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$o) {
    flash('error', 'Sipariş bulunamadı.');
    redirect('admin/orders.php');
}

/** Rolün geçebileceği durumlar */
function allowed_statuses(array $o): array
{
    if (can('orders.cancel')) {
        return array_keys(ORDER_STATUSES);
    }
    $map = ['paid' => ['preparing'], 'preparing' => ['shipped'], 'shipped' => ['delivered']];
    return $map[$o['status']] ?? [];
}

function add_note(int $orderId, array $u, string $note): void
{
    insert('order_notes', ['order_id' => $orderId, 'user_id' => $u['id'], 'user_name' => $u['name'], 'note' => $note, 'created_at' => now()]);
}

function send_shipped_mail(array $o): void
{
    $track = tracking_url($o['cargo_company'], $o['tracking_no']);
    send_mail($o['customer_email'], 'Siparişin kargoya verildi - ' . $o['order_no'],
        '<p>Merhaba ' . e($o['customer_name']) . ',</p><p><b>' . e($o['order_no']) . '</b> numaralı siparişin kargoya verildi.</p>'
        . '<p>Kargo firması: <b>' . e($o['cargo_company']) . '</b><br>Takip numarası: <b>' . e($o['tracking_no']) . '</b></p>'
        . ($track ? '<p><a href="' . e($track) . '">Kargonu buradan takip edebilirsin</a></p>' : '')
        . order_items_html((int) $o['id']));
}

if (is_post()) {
    verify_csrf();
    $action = input('action');
    if ($action === 'ship' && can('orders.ship') && in_array($o['status'], ['paid', 'preparing', 'shipped'], true)) {
        $company = input('cargo_company');
        $no = mb_substr(preg_replace('/\s+/', '', input('tracking_no')), 0, 80);
        if (!isset(cargo_companies()[$company]) || $no === '') {
            flash('error', 'Kargo firması ve takip numarası girin.');
        } else {
            q('UPDATE orders SET status = ?, cargo_company = ?, tracking_no = ?, shipped_at = COALESCE(shipped_at, ?), updated_at = ? WHERE id = ?', ['shipped', $company, $no, now(), now(), $id]);
            add_note($id, $u, 'Kargoya verildi: ' . $company . ' / ' . $no);
            log_activity('Siparişi kargoya verdi', $o['order_no'] . ' · ' . $company . ' ' . $no, 'order', $id);
            $o = row('SELECT * FROM orders WHERE id = ?', [$id]);
            if (input('notify')) send_shipped_mail($o);
            flash('success', 'Sipariş kargoya verildi olarak işaretlendi' . (input('notify') ? ' ve müşteriye e-posta gönderildi.' : '.'));
        }
    } elseif ($action === 'status' && can('orders.status')) {
        $new = input('status');
        if ($new !== $o['status'] && in_array($new, allowed_statuses($o), true)) {
            if ($new === 'shipped' && !$o['tracking_no']) {
                flash('error', '“Kargoda” durumu için önce kargo firması ve takip numarasını girin.');
                redirect('admin/order.php?id=' . $id);
            }
            $extra = '';
            if (in_array($new, ['cancelled', 'refunded'], true) && $o['stock_reduced'] && input('restock')) {
                order_restore_stock($o);
                $extra = ' · Stok geri eklendi';
            }
            if (in_array($new, SALE_STATUSES, true) && !$o['stock_reduced'] && in_array($o['status'], ['cancelled', 'refunded', 'pending', 'failed'], true)) {
                order_reduce_stock($o);
                $extra = ' · Stoktan düşüldü';
            }
            $set = ['status' => $new, 'updated_at' => now()];
            if ($new === 'delivered') $set['delivered_at'] = now();
            if ($new === 'paid' && !$o['paid_at']) $set['paid_at'] = now();
            update('orders', $set, $id);
            add_note($id, $u, 'Durum: ' . ORDER_STATUSES[$o['status']][0] . ' → ' . ORDER_STATUSES[$new][0] . $extra);
            log_activity('Sipariş durumu değiştirdi', $o['order_no'] . ': ' . ORDER_STATUSES[$o['status']][0] . ' → ' . ORDER_STATUSES[$new][0] . $extra, 'order', $id);
            flash('success', 'Sipariş durumu güncellendi.' . $extra);
        } else {
            flash('error', 'Bu durum değişikliği için yetkiniz yok.');
        }
    } elseif ($action === 'note' && ($note = input('note')) !== '') {
        add_note($id, $u, mb_substr($note, 0, 2000));
        log_activity('Siparişe not ekledi', $o['order_no'] . ': ' . mb_strimwidth($note, 0, 80, '…'), 'order', $id);
        flash('success', 'Not eklendi.');
    } elseif ($action === 'claim' && $u['role'] === 'sales' && !$o['assigned_to']) {
        q('UPDATE orders SET assigned_to = ?, updated_at = ? WHERE id = ?', [$u['id'], now(), $id]);
        log_activity('Siparişi üstlendi', $o['order_no'], 'order', $id);
        flash('success', 'Sipariş size atandı.');
    } elseif ($action === 'assign' && can('orders.cancel')) {
        $to = (int) input('assigned_to') ?: null;
        $rep = $to ? row("SELECT name FROM users WHERE id = ? AND role IN ('sales','admin','super_admin')", [$to]) : null;
        q('UPDATE orders SET assigned_to = ?, updated_at = ? WHERE id = ?', [$rep ? $to : null, now(), $id]);
        log_activity('Sipariş ataması yaptı', $o['order_no'] . ' → ' . ($rep['name'] ?? 'Atanmamış'), 'order', $id);
        flash('success', 'Atama güncellendi.');
    } elseif ($action === 'delete' && can('orders.delete')) {
        if ($o['stock_reduced'] && !in_array($o['status'], ['delivered'], true)) order_restore_stock($o);
        q('DELETE FROM order_notes WHERE order_id = ?', [$id]);
        q('DELETE FROM order_items WHERE order_id = ?', [$id]);
        q('DELETE FROM orders WHERE id = ?', [$id]);
        log_activity('Sipariş sildi', $o['order_no'] . ' · ' . $o['customer_name'] . ' · ' . money($o['amount']), 'order', $id);
        flash('success', 'Sipariş silindi.');
        redirect('admin/orders.php');
    }
    redirect('admin/order.php?id=' . $id);
}

$items = rows('SELECT i.*, v.stock, v.sku FROM order_items i LEFT JOIN product_variants v ON v.id = i.variant_id WHERE i.order_id = ?', [$id]);
$notes = rows('SELECT * FROM order_notes WHERE order_id = ? ORDER BY id DESC', [$id]);
$assignee = $o['assigned_to'] ? row('SELECT name FROM users WHERE id = ?', [$o['assigned_to']]) : null;
$reps = can('orders.cancel') ? rows("SELECT id, name, role FROM users WHERE role IN ('sales','admin','super_admin') AND status = 'active' ORDER BY name") : [];
$history = can('activity.view') ? rows("SELECT * FROM activity_log WHERE entity = 'order' AND entity_id = ? ORDER BY id DESC", [$id]) : [];
$track = tracking_url($o['cargo_company'], $o['tracking_no']);

admin_header('Sipariş ' . $o['order_no'], status_badge($o['status']) . ' · ' . tr_date($o['created_at']));
?>
<div class="toolbar no-print">
    <a href="orders.php" class="btn btn-xs btn-outline">← Siparişler</a>
    <button type="button" class="btn btn-xs btn-dark" onclick="window.print()">🖨 Sipariş Fişi Yazdır</button>
</div>
<div class="grid-main">
    <div>
        <section class="panel print-area">
            <div class="print-only print-head"><img src="<?= asset('img/logo.svg') ?>" alt="" height="40"><div><strong>Sipariş Fişi</strong><br><?= e($o['order_no']) ?> · <?= tr_date($o['created_at']) ?></div></div>
            <div class="panel-head"><h2>Ürünler (<?= (int) $o['item_count'] ?> adet)</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Ürün</th><th>Beden / Renk</th><th>Baskı</th><th class="num">Adet</th><th class="num">Birim</th><th class="num">Tutar</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><strong><?= e($it['product_name']) ?></strong><?= $it['sku'] ? '<br><small class="muted">' . e($it['sku']) . '</small>' : '' ?></td>
                        <td><?= e($it['size']) ?> · <?= e($it['color']) ?></td>
                        <td><?= ($it['print_name'] || $it['print_number']) ? '<span class="badge badge-yellow">' . e(trim($it['print_name'] . ' ' . $it['print_number'])) . '</span>' : '-' ?></td>
                        <td class="num"><strong><?= (int) $it['qty'] ?></strong></td>
                        <td class="num"><?= money($it['unit_price']) ?></td>
                        <td class="num"><?= money($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="5" class="num">Ara Toplam</td><td class="num"><?= money($o['subtotal']) ?></td></tr>
                    <?php if ($o['discount'] > 0): ?><tr><td colspan="5" class="num">İndirim (<?= e($o['coupon_code']) ?>)</td><td class="num">−<?= money($o['discount']) ?></td></tr><?php endif; ?>
                    <tr><td colspan="5" class="num">Kargo</td><td class="num"><?= $o['shipping_fee'] > 0 ? money($o['shipping_fee']) : 'Ücretsiz' ?></td></tr>
                    <tr><th colspan="5" class="num">TOPLAM (KDV dahil)</th><th class="num"><?= money($o['amount']) ?></th></tr>
                </tfoot>
            </table></div>
            <div class="addr-grid">
                <div><h3>Teslimat Adresi</h3><p><strong><?= e($o['ship_name']) ?></strong><br><?= e($o['ship_address']) ?><br><?= e($o['ship_district']) ?> / <?= e($o['ship_city']) ?><br>📞 <?= e($o['ship_phone']) ?></p></div>
                <div><h3>Fatura</h3><p><?php if ($o['invoice_type'] === 'kurumsal'): ?><strong><?= e($o['company_name']) ?></strong><br><?= e($o['tax_office']) ?> / <?= e($o['tax_number']) ?><br><?php endif; ?><?= e($o['customer_name']) ?><?= $o['identity_no'] ? ' · TC: ' . e($o['identity_no']) : '' ?><br><?= e($o['customer_address']) ?> <?= e($o['customer_city']) ?><br>✉ <?= e($o['customer_email']) ?> · 📞 <?= e($o['customer_phone']) ?></p></div>
            </div>
            <?php if ($o['customer_note']): ?><div class="info-box"><strong>Müşteri notu:</strong> <?= nl2br(e($o['customer_note'])) ?></div><?php endif; ?>
        </section>
        <section class="panel no-print">
            <div class="panel-head"><h2>Ödeme</h2></div>
            <dl class="dl-grid">
                <dt>Durum</dt><dd><?= status_badge($o['status']) ?> <?= $o['stock_reduced'] ? '<small class="muted">· stoktan düşüldü</small>' : '' ?></dd>
                <dt>Ödeme Tarihi</dt><dd><?= tr_date($o['paid_at']) ?></dd>
                <dt>Banka Referansı</dt><dd><?= e($o['payment_ref'] ?: '-') ?></dd>
                <dt>Banka Mesajı</dt><dd><?= e($o['payment_message'] ?: '-') ?></dd>
                <dt>Sözleşme Onayı</dt><dd><?= tr_date($o['contract_accepted_at']) ?> <?= $o['ip'] ? '<small class="muted">IP: ' . e($o['ip']) . '</small>' : '' ?></dd>
                <dt>Kargo</dt><dd><?= $o['tracking_no'] ? e($o['cargo_company']) . ' · ' . e($o['tracking_no']) . ($track ? ' · <a href="' . e($track) . '" target="_blank" rel="noopener">Takip</a>' : '') . ' · ' . tr_date($o['shipped_at']) : '-' ?></dd>
                <dt>Teslim</dt><dd><?= tr_date($o['delivered_at']) ?></dd>
                <dt>Sorumlu</dt><dd><?= e($assignee['name'] ?? 'Atanmamış') ?></dd>
            </dl>
        </section>
        <?php if ($history): ?>
        <section class="panel no-print">
            <div class="panel-head"><h2>İşlem Geçmişi <small class="lock-tag">★ Süper Admin</small></h2></div>
            <ul class="feed"><?php foreach ($history as $a): ?><li><span class="dot role-<?= e($a['user_role']) ?>"></span><div><strong><?= e($a['user_name']) ?></strong> — <?= e($a['action']) ?><br><small><?= e($a['details']) ?> · <?= tr_date($a['created_at']) ?> · <?= e($a['ip']) ?></small></div></li><?php endforeach; ?></ul>
        </section>
        <?php endif; ?>
    </div>
    <div class="no-print">
        <?php if (can('orders.ship') && in_array($o['status'], ['paid', 'preparing', 'shipped'], true)): ?>
        <section class="panel">
            <h3>📦 <?= $o['tracking_no'] ? 'Kargo Bilgisini Güncelle' : 'Kargoya Ver' ?></h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="ship">
                <select name="cargo_company" required><option value="">Kargo firması seçin</option><?php foreach (cargo_companies() as $name => $_): ?><option <?= $o['cargo_company'] === $name ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select>
                <input name="tracking_no" value="<?= e($o['tracking_no']) ?>" placeholder="Takip numarası" required>
                <label class="check"><input type="checkbox" name="notify" value="1" checked> <span>Müşteriye takip numarasıyla e-posta gönder</span></label>
                <button class="btn btn-primary btn-sm">Kaydet ve Kargoya Verildi Yap</button>
            </form>
        </section>
        <?php endif; ?>
        <?php if (can('orders.status') && ($allowed = allowed_statuses($o))): ?>
        <section class="panel">
            <h3>Durumu Güncelle</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="status">
                <select name="status"><?php foreach ($allowed as $s): ?><option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= e(ORDER_STATUSES[$s][0]) ?></option><?php endforeach; ?></select>
                <?php if (can('orders.cancel') && $o['stock_reduced']): ?><label class="check"><input type="checkbox" name="restock" value="1" checked> <span>İptal/iade edilirse ürünleri stoğa geri ekle</span></label><?php endif; ?>
                <button class="btn btn-dark btn-sm">Güncelle</button>
            </form>
            <?php if (can('orders.cancel')): ?><p class="small muted">Para iadesi için tutarı Garanti BBVA Sanal POS ekranından iade edip durumu “İade Edildi” yapın.</p><?php endif; ?>
        </section>
        <?php endif; ?>
        <?php if ($u['role'] === 'sales' && !$o['assigned_to']): ?>
            <form method="post" class="panel"><?= csrf_field() ?><input type="hidden" name="action" value="claim"><button class="btn btn-primary btn-block">Bu siparişi üstlen</button></form>
        <?php endif; ?>
        <?php if ($reps): ?>
        <section class="panel">
            <h3>Sorumlu Ata</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="assign">
                <select name="assigned_to"><option value="">Atanmamış</option><?php foreach ($reps as $r): ?><option value="<?= (int) $r['id'] ?>" <?= (int) $o['assigned_to'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?> (<?= e(role_label($r['role'])) ?>)</option><?php endforeach; ?></select>
                <button class="btn btn-dark btn-sm">Ata</button>
            </form>
        </section>
        <?php endif; ?>
        <section class="panel">
            <h3>Notlar</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="note">
                <textarea name="note" rows="3" placeholder="Müşteri görüşmesi, paketleme notu vb." required></textarea>
                <button class="btn btn-primary btn-sm">Not Ekle</button>
            </form>
            <ul class="notes"><?php foreach ($notes as $n): ?><li><strong><?= e($n['user_name']) ?></strong> <small class="muted"><?= tr_date($n['created_at']) ?></small><p><?= nl2br(e($n['note'])) ?></p></li><?php endforeach; ?></ul>
        </section>
        <?php if (can('orders.delete')): ?>
            <form method="post" class="panel" data-confirm="Sipariş kalıcı olarak silinecek. Emin misiniz?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-block btn-sm">Siparişi Sil</button></form>
        <?php endif; ?>
    </div>
</div>
<?php admin_footer();
