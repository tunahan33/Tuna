<?php
/** İade çekleri: görüntüleme (satış temsilcisi dahil), tanımlama ve iptal (admin ve süper admin) */
require __DIR__ . '/_init.php';
$u = require_perm('vouchers.view');

if (is_post()) {
    verify_csrf();
    if (!can('vouchers.manage')) {
        flash('error', 'İade çeki tanımlama ve iptal yetkiniz yok.');
        redirect('admin/vouchers.php');
    }
    if (input('action') === 'create') {
        $email = mb_strtolower(input('customer_email'));
        $orderNo = strtoupper(input('source_order_no'));
        $refund = (float) str_replace(',', '.', input('refund_amount'));
        $bonus = input('bonus') === '1';
        $months = max(1, min(36, (int) input('months', (string) VOUCHER_VALID_MONTHS)));
        $order = $orderNo !== '' ? row('SELECT * FROM orders WHERE order_no = ?', [$orderNo]) : null;
        if ($order && $email === '') {
            $email = mb_strtolower($order['customer_email']);
        }
        $amount = round($refund * ($bonus ? 1 + VOUCHER_BONUS_RATE : 1), 2);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Geçerli bir müşteri e-postası girin (veya sipariş numarası girerek otomatik doldurun).');
        } elseif ($orderNo !== '' && !$order) {
            flash('error', 'Sipariş bulunamadı: ' . $orderNo);
        } elseif ($refund <= 0 || ($order && $refund > (float) $order['amount'] + (float) $order['voucher_amount'] + 0.009)) {
            flash('error', 'İade tutarı 0\'dan büyük olmalı' . ($order ? ' ve sipariş tutarını (' . money((float) $order['amount'] + (float) $order['voucher_amount']) . ') aşmamalı' : '') . '.');
        } else {
            $code = voucher_generate_code();
            $expires = date('Y-m-d 23:59:59', strtotime('+' . $months . ' months'));
            $id = insert('vouchers', [
                'code' => $code, 'customer_email' => $email, 'customer_name' => $order['customer_name'] ?? (mb_substr(input('customer_name'), 0, 120) ?: null),
                'amount' => $amount, 'balance' => $amount, 'source_order_no' => $order['order_no'] ?? null,
                'note' => mb_substr(input('note'), 0, 500) ?: null, 'status' => 'active', 'expires_at' => $expires,
                'created_by' => $u['id'], 'created_at' => now(),
            ]);
            if ($order) {
                insert('order_notes', ['order_id' => $order['id'], 'user_id' => $u['id'], 'user_name' => $u['name'], 'note' => 'İade çeki tanımlandı: ' . $code . ' · ' . money($amount) . ($bonus ? ' (' . money($refund) . ' iade + %' . (VOUCHER_BONUS_RATE * 100) . ')' : ''), 'created_at' => now()]);
            }
            log_activity('İade çeki tanımladı', $code . ' · ' . money($amount) . ' · ' . $email . ($order ? ' · Sipariş ' . $order['order_no'] : ''), 'voucher', $id);
            $sent = input('send_mail') && send_mail($email, 'İade çekiniz tanımlandı: ' . money($amount),
                '<p>Merhaba' . (!empty($order['customer_name']) ? ' ' . e($order['customer_name']) : '') . ',</p><p>Hesabınıza <b>' . money($amount) . '</b> tutarında iade çeki tanımlanmıştır.</p>'
                . '<p style="font-size:20px;letter-spacing:2px"><b>' . e($code) . '</b></p><p>Son kullanma tarihi: <b>' . tr_date($expires, false) . '</b><br>Kodu, ödeme sayfasında “İade çeki kodum var” alanına girerek bu e-posta adresiyle vereceğiniz siparişlerde kullanabilirsiniz. Kalan bakiye sonraki siparişlerinizde kullanılabilir.</p>'
                . '<p><a href="' . e(url('sayfa.php?s=iade-ve-iade-ceki-kosullari')) . '">İade ve İade Çeki Koşulları</a></p>');
            flash('success', 'İade çeki oluşturuldu: ' . $code . ' (' . money($amount) . ')' . ($sent ? ' — müşteriye e-posta gönderildi.' : ''));
        }
        redirect('admin/vouchers.php');
    }
    if (input('action') === 'cancel' && ($v = row('SELECT * FROM vouchers WHERE id = ?', [(int) input('id')])) && $v['status'] === 'active') {
        q("UPDATE vouchers SET status = 'cancelled' WHERE id = ?", [$v['id']]);
        log_activity('İade çekini iptal etti', $v['code'] . ' · Kalan bakiye ' . money($v['balance']), 'voucher', (int) $v['id']);
        flash('success', $v['code'] . ' iptal edildi.');
        redirect('admin/vouchers.php');
    }
}

// Süresi geçenleri işaretle
q("UPDATE vouchers SET status = 'expired' WHERE status = 'active' AND expires_at < ?", [now()]);

$search = trim((string) input('q'));
$where = $search !== '' ? 'WHERE v.code LIKE ? OR v.customer_email LIKE ? OR v.source_order_no LIKE ?' : '';
$params = $search !== '' ? array_fill(0, 3, '%' . $search . '%') : [];
$total = (int) val("SELECT COUNT(*) FROM vouchers v $where", $params);
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows("SELECT v.*, u.name AS creator FROM vouchers v LEFT JOIN users u ON u.id = v.created_by $where ORDER BY v.id DESC LIMIT $per OFFSET $offset", $params);
$sum = row("SELECT COALESCE(SUM(balance),0) AS open_balance, COUNT(*) AS c FROM vouchers WHERE status = 'active'");
$prefill = ['order' => strtoupper((string) input('siparis')), 'amount' => ''];
if ($prefill['order'] && ($po = row('SELECT * FROM orders WHERE order_no = ?', [$prefill['order']]))) {
    $prefill['amount'] = number_format((float) $po['amount'] + (float) $po['voucher_amount'], 2, '.', '');
}

admin_header('İade Çekleri', 'Kart iadesi yerine tanımlanan bakiye kodları · Açık bakiye: <strong>' . money($sum['open_balance']) . '</strong> (' . (int) $sum['c'] . ' aktif çek)');
?>
<?php if (can('vouchers.manage')): ?>
<section class="panel">
    <div class="panel-head"><h2>Yeni İade Çeki Tanımla</h2></div>
    <form method="post" class="form" style="max-width:900px">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <div class="grid-3">
            <label>Kaynak Sipariş No <small>(önerilir, e-postayı otomatik doldurur)</small><input name="source_order_no" value="<?= e($prefill['order']) ?>" placeholder="GSE..."></label>
            <label>Müşteri E-postası <small>(sipariş no girilmediyse)</small><input type="email" name="customer_email"></label>
            <label>Müşteri Adı <small>(isteğe bağlı)</small><input name="customer_name"></label>
            <label>İade Edilecek Tutar (₺) *<input name="refund_amount" value="<?= e($prefill['amount']) ?>" inputmode="decimal" required></label>
            <label>Geçerlilik (ay)<input type="number" name="months" value="<?= VOUCHER_VALID_MONTHS ?>" min="1" max="36"></label>
            <label>Not <small>(iç kullanım)</small><input name="note" placeholder="Örn. paketin 4 dersi kullanılmadı"></label>
        </div>
        <label class="check"><input type="checkbox" name="bonus" value="1" checked> <span>Kart iadesi yerine iade çeki tercih edildi: tutara <b>%<?= VOUCHER_BONUS_RATE * 100 ?></b> ekle (İade ve İade Çeki Koşulları m.3)</span></label>
        <label class="check"><input type="checkbox" name="send_mail" value="1" checked> <span>Kodu müşteriye e-posta ile gönder</span></label>
        <div><button class="btn btn-primary">İade Çeki Oluştur</button></div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <form class="inline-form mb-1" method="get"><input name="q" value="<?= e($search) ?>" placeholder="Kod, e-posta veya sipariş no ara"><button class="btn btn-sm btn-dark">Ara</button><?php if ($search !== ''): ?> <a href="vouchers.php" class="btn btn-sm btn-outline">Temizle</a><?php endif; ?></form>
    <?php if (!$list): ?><p class="muted">Henüz iade çeki tanımlanmadı.</p><?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kod</th><th>Müşteri</th><th>Tutar</th><th>Kalan</th><th>Durum</th><th>Son Kullanma</th><th>Kaynak / Kullanım</th><th>Tanımlayan</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $v): $uses = rows('SELECT * FROM voucher_uses WHERE voucher_id = ? ORDER BY id', [$v['id']]); ?>
            <tr>
                <td><strong><?= e($v['code']) ?></strong><?= $v['note'] ? '<br><small class="muted">' . e($v['note']) . '</small>' : '' ?></td>
                <td><?= e($v['customer_name'] ?? '') ?><br><small class="muted"><?= e($v['customer_email']) ?></small></td>
                <td><?= money($v['amount']) ?></td>
                <td><strong><?= money($v['balance']) ?></strong></td>
                <td><?= voucher_badge($v['status']) ?></td>
                <td><?= tr_date($v['expires_at'], false) ?></td>
                <td class="small">
                    <?php if ($v['source_order_no']): ?>İade: <?= e($v['source_order_no']) ?><br><?php endif; ?>
                    <?php foreach ($uses as $us): ?>Kullanım: <?= e($us['order_no']) ?> · <?= money($us['amount']) ?><br><?php endforeach; ?>
                </td>
                <td class="small"><?= e($v['creator'] ?? '-') ?><br><span class="muted"><?= tr_date($v['created_at']) ?></span></td>
                <td><?php if ($v['status'] === 'active' && can('vouchers.manage')): ?>
                    <form method="post" onsubmit="return confirm('<?= e($v['code']) ?> iptal edilsin mi? Kalan bakiye kullanılamaz hale gelir.')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int) $v['id'] ?>"><button class="btn btn-xs btn-danger">İptal</button></form>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?= pager($page, $pages) ?>
    <?php endif; ?>
</section>
<?php admin_footer();
