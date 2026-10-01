<?php
require __DIR__ . '/_init.php';
$u = require_perm('coupons.manage');

if (is_post()) {
    verify_csrf();
    $action = input('action');
    $c = ($cid = (int) input('id')) ? row('SELECT * FROM coupons WHERE id = ?', [$cid]) : null;
    if ($action === 'create') {
        $code = mb_strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', input('code')));
        $value = (float) str_replace(',', '.', input('value'));
        $type = input('type') === 'amount' ? 'amount' : 'percent';
        $dt = fn($v) => $v !== '' && strtotime($v) ? date('Y-m-d H:i:s', strtotime($v)) : null;
        if (strlen($code) < 3 || $value <= 0 || ($type === 'percent' && $value > 100)) {
            flash('error', 'Geçerli bir kod (en az 3 karakter) ve indirim değeri girin (yüzde en fazla 100).');
        } elseif (row('SELECT id FROM coupons WHERE code = ?', [$code])) {
            flash('error', 'Bu kupon kodu zaten var.');
        } else {
            $nid = insert('coupons', ['code' => $code, 'type' => $type, 'value' => $value, 'min_total' => max(0, (float) str_replace(',', '.', input('min_total'))),
                'max_uses' => max(0, (int) input('max_uses')), 'used_count' => 0, 'starts_at' => $dt(input('starts_at')), 'expires_at' => $dt(input('expires_at')), 'is_active' => 1, 'created_at' => now()]);
            log_activity('Kupon oluşturdu', $code . ' · ' . ($type === 'percent' ? '%' . $value : money($value)), 'coupon', $nid);
            flash('success', 'Kupon oluşturuldu: ' . $code);
        }
    } elseif ($c && $action === 'toggle') {
        q('UPDATE coupons SET is_active = ? WHERE id = ?', [$c['is_active'] ? 0 : 1, $c['id']]);
        log_activity($c['is_active'] ? 'Kuponu durdurdu' : 'Kuponu aktifleştirdi', $c['code'], 'coupon', (int) $c['id']);
    } elseif ($c && $action === 'delete') {
        q('DELETE FROM coupons WHERE id = ?', [$c['id']]);
        log_activity('Kuponu sildi', $c['code'], 'coupon', (int) $c['id']);
    }
    redirect('admin/coupons.php');
}

$list = rows('SELECT c.*, (SELECT COALESCE(SUM(discount),0) FROM orders o WHERE o.coupon_code = c.code AND o.status IN (\'' . implode("','", SALE_STATUSES) . '\')) AS total_discount FROM coupons c ORDER BY c.id DESC');
admin_header('İndirim Kuponları', count($list) . ' kupon');
?>
<form method="post" class="panel form">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <h3>Yeni Kupon</h3>
    <div class="grid-4">
        <label>Kupon Kodu<input name="code" placeholder="ORN: YAZ20" required style="text-transform:uppercase"></label>
        <label>Tür<select name="type"><option value="percent">Yüzde (%)</option><option value="amount">Tutar (₺)</option></select></label>
        <label>İndirim Değeri<input name="value" inputmode="decimal" placeholder="20" required></label>
        <label>Alt Limit (₺) <small>(sepet en az)</small><input name="min_total" inputmode="decimal" value="0"></label>
        <label>Kullanım Limiti <small>(0 = sınırsız)</small><input type="number" name="max_uses" min="0" value="0"></label>
        <label>Başlangıç <small>(boş: hemen)</small><input type="datetime-local" name="starts_at"></label>
        <label>Bitiş <small>(boş: süresiz)</small><input type="datetime-local" name="expires_at"></label>
        <div class="form-actions" style="align-self:end"><button class="btn btn-primary">Kupon Oluştur</button></div>
    </div>
</form>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kod</th><th>İndirim</th><th>Alt Limit</th><th>Kullanım</th><th>Geçerlilik</th><th class="num">Toplam İndirim</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $c):
            $expired = $c['expires_at'] && $c['expires_at'] < now();
            $full = $c['max_uses'] > 0 && $c['used_count'] >= $c['max_uses']; ?>
            <tr>
                <td><code class="coupon-code"><?= e($c['code']) ?></code></td>
                <td><strong><?= e(coupon_label($c)) ?></strong></td>
                <td><?= $c['min_total'] > 0 ? money($c['min_total']) : '-' ?></td>
                <td><?= (int) $c['used_count'] ?><?= $c['max_uses'] > 0 ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                <td class="small"><?= $c['starts_at'] ? tr_date($c['starts_at']) : 'Hemen' ?> → <?= $c['expires_at'] ? tr_date($c['expires_at']) : 'Süresiz' ?></td>
                <td class="num"><?= money($c['total_discount']) ?></td>
                <td><?= !$c['is_active'] ? '<span class="badge badge-gray">Durduruldu</span>' : ($expired ? '<span class="badge badge-red">Süresi doldu</span>' : ($full ? '<span class="badge badge-yellow">Limit doldu</span>' : '<span class="badge badge-green">Aktif</span>')) ?></td>
                <td class="actions">
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button name="action" value="toggle" class="btn btn-xs btn-outline"><?= $c['is_active'] ? 'Durdur' : 'Aktifleştir' ?></button></form>
                    <form method="post" data-confirm="Kupon silinsin mi?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button name="action" value="delete" class="btn btn-xs btn-danger">Sil</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$list): ?><tr><td colspan="8" class="muted">Henüz kupon yok.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
<?php admin_footer();
