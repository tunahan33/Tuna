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
    $map = ['paid' => ['processing'], 'processing' => ['completed']];
    return $map[$o['status']] ?? [];
}

if (is_post()) {
    verify_csrf();
    $action = input('action');
    if ($action === 'status' && can('orders.status')) {
        $new = input('status');
        if ($new === 'paid' && in_array($o['status'], ['pending', 'failed'], true) && can('orders.cancel')) {
            // Site dışında alınan ödemenin onayı: ödeme tarihi, iade çeki ve müşteri e-postası tek noktadan işlenir
            require_once dirname(__DIR__) . '/includes/payment.php';
            finalize_order($o, true, 'Ödeme panelden onaylandı (' . $u['name'] . ')', 'MANUEL-' . date('ymd'), ['mode' => 'manual', 'by' => $u['name']]);
            insert('order_notes', ['order_id' => $id, 'user_id' => $u['id'], 'user_name' => $u['name'], 'note' => 'Ödeme panelden onaylandı: ' . ORDER_STATUSES[$o['status']][0] . ' → Ödendi', 'created_at' => now()]);
            log_activity('Ödemeyi onayladı', $o['order_no'] . ' · Manuel · ' . money($o['amount']), 'order', $id);
            flash('success', 'Ödeme onaylandı, müşteriye bilgilendirme e-postası gönderildi.');
        } elseif ($new !== $o['status'] && in_array($new, allowed_statuses($o), true)) {
            q('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?', [$new, now(), $id]);
            insert('order_notes', ['order_id' => $id, 'user_id' => $u['id'], 'user_name' => $u['name'], 'note' => 'Durum değiştirildi: ' . ORDER_STATUSES[$o['status']][0] . ' → ' . ORDER_STATUSES[$new][0], 'created_at' => now()]);
            log_activity('Sipariş durumu değiştirdi', $o['order_no'] . ': ' . ORDER_STATUSES[$o['status']][0] . ' → ' . ORDER_STATUSES[$new][0], 'order', $id);
            flash('success', 'Sipariş durumu güncellendi.');
        } else {
            flash('error', 'Bu durum değişikliği için yetkiniz yok.');
        }
    } elseif ($action === 'note' && ($note = input('note')) !== '') {
        insert('order_notes', ['order_id' => $id, 'user_id' => $u['id'], 'user_name' => $u['name'], 'note' => mb_substr($note, 0, 2000), 'created_at' => now()]);
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
        q('DELETE FROM order_notes WHERE order_id = ?', [$id]);
        q('DELETE FROM orders WHERE id = ?', [$id]);
        log_activity('Sipariş sildi', $o['order_no'] . ' · ' . $o['customer_name'] . ' · ' . money($o['amount']), 'order', $id);
        flash('success', 'Sipariş silindi.');
        redirect('admin/orders.php');
    }
    redirect('admin/order.php?id=' . $id);
}

$notes = rows('SELECT * FROM order_notes WHERE order_id = ? ORDER BY id DESC', [$id]);
$assignee = $o['assigned_to'] ? row('SELECT name FROM users WHERE id = ?', [$o['assigned_to']]) : null;
$reps = can('orders.cancel') ? rows("SELECT id, name, role FROM users WHERE role IN ('sales','admin','super_admin') AND status = 'active' ORDER BY name") : [];
$history = can('activity.view') ? rows("SELECT * FROM activity_log WHERE entity = 'order' AND entity_id = ? ORDER BY id DESC", [$id]) : [];

admin_header('Sipariş ' . $o['order_no'], status_badge($o['status']) . ' · ' . tr_date($o['created_at']));
?>
<div class="grid-main">
    <div>
        <section class="panel">
            <div class="panel-head"><h2>Sipariş Bilgileri</h2><a href="orders.php" class="btn btn-xs btn-outline">← Liste</a></div>
            <dl class="dl-grid">
                <dt>Hizmet</dt><dd><?= e($o['service_title']) ?></dd>
                <dt>Paket</dt><dd><?= e($o['package_name']) ?></dd>
                <dt>Ödeme Yöntemi</dt><dd><?= e(['kart' => 'Kredi / Banka Kartı', 'iade_ceki' => 'İade Çeki'][$o['payment_method'] ?? ''] ?? ($o['status'] === 'paid' && str_starts_with((string) $o['payment_ref'], 'MANUEL') ? 'Panelden onaylandı' : '-')) ?></dd>
                <dt>Tutar</dt><dd><strong class="big"><?= money($o['amount']) ?></strong> <small class="muted">KDV dahil</small></dd>
                <?php if ($o['voucher_code']): ?><dt>İade Çeki</dt><dd><?= money($o['voucher_amount']) ?> <small class="muted">(<?= e($o['voucher_code']) ?>) · kartla ödenen: <?= money($o['amount']) ?></small></dd><?php endif; ?>
                <?php if (can('vouchers.manage') && in_array($o['status'], ['paid', 'processing', 'cancelled', 'refunded'], true)): ?><dt></dt><dd><a class="btn btn-xs btn-outline" href="<?= url('admin/vouchers.php?siparis=' . urlencode($o['order_no'])) ?>">Bu sipariş için iade çeki tanımla</a></dd><?php endif; ?>
                <dt>Durum</dt><dd><?= status_badge($o['status']) ?></dd>
                <dt>Ödeme Tarihi</dt><dd><?= tr_date($o['paid_at']) ?></dd>
                <dt>Banka Referansı</dt><dd><?= e($o['payment_ref'] ?: '-') ?></dd>
                <dt>Banka Mesajı</dt><dd><?= e($o['payment_message'] ?: '-') ?></dd>
                <dt>Sözleşme Onayı</dt><dd><?= tr_date($o['contract_accepted_at']) ?> <?= $o['ip'] ? '<small class="muted">IP: ' . e($o['ip']) . '</small>' : '' ?></dd>
                <dt>Sorumlu</dt><dd><?= e($assignee['name'] ?? 'Atanmamış') ?></dd>
            </dl>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Müşteri &amp; Fatura</h2></div>
            <dl class="dl-grid">
                <dt>Ad Soyad</dt><dd><?= e($o['customer_name']) ?></dd>
                <dt>E-posta</dt><dd><a href="mailto:<?= e($o['customer_email']) ?>"><?= e($o['customer_email']) ?></a></dd>
                <dt>Telefon</dt><dd><a href="tel:<?= e($o['customer_phone']) ?>"><?= e($o['customer_phone']) ?></a></dd>
                <dt>Fatura Tipi</dt><dd><?= e(ucfirst($o['invoice_type'])) ?></dd>
                <?php if ($o['invoice_type'] === 'kurumsal'): ?>
                    <dt>Firma</dt><dd><?= e($o['company_name']) ?></dd>
                    <dt>Vergi D. / No</dt><dd><?= e($o['tax_office']) ?> / <?= e($o['tax_number']) ?></dd>
                <?php endif; ?>
                <dt>T.C. Kimlik</dt><dd><?= e($o['identity_no'] ?: '-') ?></dd>
                <dt>Adres</dt><dd><?= e($o['customer_address']) ?> <?= e($o['customer_city']) ?></dd>
                <dt>Müşteri Notu</dt><dd><?= nl2br(e($o['customer_note'] ?: '-')) ?></dd>
            </dl>
        </section>
        <?php if ($history): ?>
        <section class="panel">
            <div class="panel-head"><h2>İşlem Geçmişi <small class="lock-tag">★ Süper Admin</small></h2></div>
            <ul class="feed"><?php foreach ($history as $a): ?><li><span class="dot role-<?= e($a['user_role']) ?>"></span><div><strong><?= e($a['user_name']) ?></strong> — <?= e($a['action']) ?><br><small><?= e($a['details']) ?> · <?= tr_date($a['created_at']) ?> · <?= e($a['ip']) ?></small></div></li><?php endforeach; ?></ul>
        </section>
        <?php endif; ?>
    </div>
    <div>
        <?php if (can('orders.status') && ($allowed = allowed_statuses($o))): ?>
        <section class="panel">
            <h3>Durumu Güncelle</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="status">
                <select name="status"><?php foreach ($allowed as $s): ?><option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= e(ORDER_STATUSES[$s][0]) ?></option><?php endforeach; ?></select>
                <button class="btn btn-dark btn-sm">Güncelle</button>
            </form>
            <?php if (can('orders.cancel')): ?><p class="small muted">İade için tutarı ödemenin alındığı yerden iade edip durumu “İade Edildi” yapın; dilerseniz İade Çekleri'nden iade çeki tanımlayın.</p><?php endif; ?>
        </section>
        <?php endif; ?>
        <?php if ($u['role'] === 'sales' && !$o['assigned_to']): ?>
            <form method="post" class="panel"><?= csrf_field() ?><input type="hidden" name="action" value="claim"><button class="btn btn-primary btn-block">Bu siparişi üstlen</button></form>
        <?php endif; ?>
        <?php if ($reps): ?>
        <section class="panel">
            <h3>Satış Temsilcisi Ata</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="assign">
                <select name="assigned_to"><option value="">Atanmamış</option><?php foreach ($reps as $r): ?><option value="<?= (int) $r['id'] ?>" <?= (int) $o['assigned_to'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?> (<?= e(role_label($r['role'])) ?>)</option><?php endforeach; ?></select>
                <button class="btn btn-dark btn-sm">Ata</button>
            </form>
        </section>
        <?php endif; ?>
        <section class="panel">
            <h3>Notlar</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="note">
                <textarea name="note" rows="3" placeholder="Görüşme notu, planlanan tarih vb." required></textarea>
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
