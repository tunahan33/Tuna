<?php
require __DIR__ . '/_layout.php';
$u = require_perm('orders.view');

$o = row('SELECT * FROM orders WHERE id = ?', [(int) input('id')]);
if (!$o) {
    flash('error', 'Sipariş bulunamadı.');
    redirect('admin/siparisler.php');
}
$link = 'admin/siparis.php?id=' . $o['id'];

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'status' && can('orders.update')) {
        $new = (string) input('status');
        $cancelLike = in_array($new, ['iptal', 'iade'], true);
        if (in_array($o['status'], ['iptal', 'iade'], true)) {
            flash('error', 'İptal veya iade edilmiş siparişin durumu değiştirilemez.');
        } elseif (!isset(ORDER_STATUSES[$new]) || $new === $o['status']) {
            flash('error', 'Geçerli bir durum seçin.');
        } elseif ($cancelLike && !can('orders.cancel')) {
            flash('error', 'İptal ve iade işlemleri için yetkiniz yok.');
        } elseif ($new === 'kargoda' && (!in_array(input('cargo_company'), CARGO_COMPANIES, true) || trim((string) input('tracking_no')) === '')) {
            flash('error', 'Kargoya verirken kargo firması ve takip numarası zorunludur.');
        } else {
            $data = ['status' => $new, 'updated_at' => now()];
            if ($new === 'kargoda') {
                $data['cargo_company'] = input('cargo_company');
                $data['tracking_no'] = mb_substr((string) input('tracking_no'), 0, 40);
            }
            if (!$o['assigned_to']) {
                $data['assigned_to'] = $u['id'];
            }
            // İptal / iadede stok geri eklenir
            if ($cancelLike && in_array($o['status'], SALE_STATUSES, true)) {
                foreach (rows('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$o['id']]) as $i) {
                    q('UPDATE products SET stock = stock + ? WHERE id = ?', [$i['qty'], $i['product_id']]);
                }
            }
            update('orders', $data, (int) $o['id']);
            $text = 'Durum: ' . ORDER_STATUSES[$o['status']][0] . ' → ' . ORDER_STATUSES[$new][0] . ($new === 'kargoda' ? ' (' . $data['cargo_company'] . ' ' . $data['tracking_no'] . ')' : '');
            order_history((int) $o['id'], $text);
            log_activity('Sipariş durumunu güncelledi', $o['order_no'] . ' → ' . ORDER_STATUSES[$new][0], $link);
            flash('success', 'Sipariş durumu güncellendi.');
        }
    } elseif ($action === 'claim' && can('orders.update') && !$o['assigned_to']) {
        update('orders', ['assigned_to' => $u['id'], 'updated_at' => now()], (int) $o['id']);
        order_history((int) $o['id'], 'Siparişi üstlendi');
        log_activity('Siparişi üstlendi', $o['order_no'], $link);
        flash('success', 'Sipariş size atandı.');
    } elseif ($action === 'assign' && can('orders.assign')) {
        $rep = row("SELECT id, name FROM users WHERE id = ? AND role IN ('super_admin','admin','satis') AND active = 1", [(int) input('rep')]);
        update('orders', ['assigned_to' => $rep['id'] ?? null, 'updated_at' => now()], (int) $o['id']);
        order_history((int) $o['id'], 'Sorumlu: ' . ($rep['name'] ?? 'kaldırıldı'));
        log_activity('Siparişe sorumlu atadı', $o['order_no'] . ' → ' . ($rep['name'] ?? 'yok'), $link);
        flash('success', 'Sorumlu güncellendi.');
    } elseif ($action === 'note' && can('orders.update') && ($note = trim((string) input('note'))) !== '') {
        order_history((int) $o['id'], 'Not: ' . mb_substr($note, 0, 1000));
        log_activity('Siparişe not ekledi', $o['order_no'], $link);
        flash('success', 'Not eklendi.');
    } elseif ($action === 'delete' && can('orders.delete')) {
        q('DELETE FROM orders WHERE id = ?', [$o['id']]);
        log_activity('Siparişi sildi', $o['order_no'] . ' · ' . $o['customer_name'] . ' · ' . money($o['total']));
        flash('success', 'Sipariş silindi.');
        redirect('admin/siparisler.php');
    } else {
        flash('error', 'Bu işlem için yetkiniz yok.');
    }
    redirect($link);
}

$items = rows('SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE order_id = ?', [$o['id']]);
$history = rows('SELECT * FROM order_history WHERE order_id = ? ORDER BY id DESC', [$o['id']]);
$rep = $o['assigned_to'] ? row('SELECT name FROM users WHERE id = ?', [$o['assigned_to']]) : null;
$staff = rows("SELECT id, name, role FROM users WHERE role IN ('super_admin','admin','satis') AND active = 1 ORDER BY name");
$customer = $o['user_id'] ? row('SELECT * FROM users WHERE id = ?', [$o['user_id']]) : null;
$allowed = array_filter(ORDER_STATUSES, fn($k) => $k !== $o['status'] && (can('orders.cancel') || !in_array($k, ['iptal', 'iade'], true)), ARRAY_FILTER_USE_KEY);

admin_header('Sipariş ' . $o['order_no'], tr_date($o['created_at']) . ' · ' . order_badge($o['status']), '<a class="btn btn-ghost" href="' . url('admin/siparisler.php') . '">← Siparişler</a>');
?>
<div class="grid-2-1">
    <div>
        <section class="panel">
            <h2>Ürünler</h2>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Ürün</th><th>Beden</th><th>Adet</th><th>Birim</th><th>Tutar</th></tr></thead>
                <tbody>
                <?php foreach ($items as $i): ?><tr><td><?= $i['slug'] ? '<a href="' . url('urun.php?u=' . $i['slug']) . '" target="_blank">' . e($i['name']) . '</a>' : e($i['name']) ?></td><td><?= e($i['size'] ?: '—') ?></td><td><?= $i['qty'] ?></td><td><?= money($i['price']) ?></td><td><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="4">Ara toplam</td><td><?= money($o['subtotal']) ?></td></tr>
                    <tr><td colspan="4">Kargo</td><td><?= $o['shipping'] > 0 ? money($o['shipping']) : 'Ücretsiz' ?></td></tr>
                    <tr><th colspan="4">Toplam</th><th><?= money($o['total']) ?></th></tr>
                </tfoot>
            </table></div>
            <p class="muted small">Ödeme: Kart ****<?= e($o['card_last4']) ?> · Tek çekim</p>
        </section>
        <section class="panel">
            <h2>Sipariş Geçmişi</h2>
            <?php if (can('orders.update')): ?>
                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="note"><input name="note" placeholder="İç not ekle (müşteri görmez)" required><button class="btn btn-dark">Ekle</button></form>
            <?php endif; ?>
            <ul class="feed">
                <?php foreach ($history as $h): ?><li><span class="dot"></span><div><strong><?= e($h['user_name']) ?></strong> · <?= e($h['text']) ?><small><?= tr_date($h['created_at']) ?></small></div></li><?php endforeach; ?>
            </ul>
        </section>
    </div>
    <div>
        <section class="panel">
            <h2>Müşteri</h2>
            <p><strong><?= e($o['customer_name']) ?></strong><?= $customer ? ' <span class="badge badge-green">Üye</span>' : ' <span class="badge badge-gray">Misafir</span>' ?><br>
                <a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a><br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $o['phone'])) ?>"><?= e($o['phone']) ?></a></p>
            <p class="small"><strong>Teslimat adresi</strong><br><?= e($o['address']) ?><br><?= e($o['district']) ?> / <?= e($o['city']) ?></p>
            <?php if ($o['note']): ?><p class="alert alert-info small">Müşteri notu: <?= e($o['note']) ?></p><?php endif; ?>
            <?php if ($o['tracking_no']): ?><p class="small"><strong>Kargo:</strong> <?= e($o['cargo_company']) ?> · <?= e($o['tracking_no']) ?></p><?php endif; ?>
        </section>
        <?php if (can('orders.update') && !in_array($o['status'], ['iptal', 'iade'], true)): ?>
        <section class="panel">
            <h2>Durumu Güncelle</h2>
            <form method="post" class="form" data-status-form>
                <?= csrf_field() ?><input type="hidden" name="action" value="status">
                <label>Yeni durum<select name="status" data-status-select><?php foreach ($allowed as $k => [$l]): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></label>
                <div data-cargo-fields class="form">
                    <label>Kargo firması<select name="cargo_company"><?php foreach (CARGO_COMPANIES as $c): ?><option <?= $o['cargo_company'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></label>
                    <label>Takip numarası<input name="tracking_no" value="<?= e($o['tracking_no']) ?>"></label>
                </div>
                <button class="btn btn-primary">Güncelle</button>
                <?php if (!can('orders.cancel')): ?><p class="small muted">İptal ve iade işlemlerini Admin veya Süper Admin yapabilir.</p><?php endif; ?>
            </form>
        </section>
        <?php endif; ?>
        <section class="panel">
            <h2>Sorumlu</h2>
            <p><?= $rep ? '<strong>' . e($rep['name']) . '</strong>' : '<span class="muted">Henüz atanmadı</span>' ?></p>
            <?php if (!$o['assigned_to'] && can('orders.update')): ?>
                <form method="post"><?= csrf_field() ?><button class="btn btn-yellow btn-block" name="action" value="claim">Bu siparişi üstlen</button></form>
            <?php endif; ?>
            <?php if (can('orders.assign')): ?>
                <form method="post" class="inline-form" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="assign">
                    <select name="rep"><option value="">— Sorumlu yok —</option><?php foreach ($staff as $s): ?><option value="<?= $s['id'] ?>" <?= $o['assigned_to'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e(role_label($s['role'])) ?>)</option><?php endforeach; ?></select>
                    <button class="btn btn-dark">Ata</button>
                </form>
            <?php endif; ?>
        </section>
        <?php if (can('orders.delete')): ?>
            <form method="post" class="panel danger-zone"><?= csrf_field() ?><h2>Tehlikeli Bölge</h2><p class="small muted">Sipariş kalıcı olarak silinir. Raporlardan da düşer. Bu işlem aktivite akışına kaydedilir.</p>
                <button class="btn btn-danger" name="action" value="delete" data-confirm="<?= e($o['order_no']) ?> numaralı sipariş kalıcı olarak silinsin mi?">Siparişi Sil</button></form>
        <?php endif; ?>
    </div>
</div>
<?php admin_footer();
