<?php
require __DIR__ . '/_init.php';
$u = require_perm('team.view');

$statuses = ['new' => ['Yeni', 'red'], 'contacted' => ['Görüşüldü', 'blue'], 'quoted' => ['Teklif Verildi', 'yellow'], 'won' => ['Kazanıldı', 'green'], 'lost' => ['Kaybedildi', 'gray']];
if (is_post()) {
    verify_csrf();
    $t = row('SELECT * FROM team_requests WHERE id = ?', [(int) input('id')]);
    if ($t) {
        $st = isset($statuses[input('status')]) ? input('status') : $t['status'];
        $quote = input('quote_amount') !== '' ? (float) str_replace(',', '.', input('quote_amount')) : null;
        q('UPDATE team_requests SET status = ?, quote_amount = ?, assigned_to = COALESCE(assigned_to, ?), updated_at = ? WHERE id = ?', [$st, $quote, $u['id'], now(), $t['id']]);
        log_activity('Takım talebini güncelledi', $t['club_name'] . ' · ' . $statuses[$st][0] . ($quote ? ' · Teklif: ' . money($quote) : ''), 'team', (int) $t['id']);
        flash('success', 'Talep güncellendi.');
    }
    redirect('admin/team.php?open=' . (int) input('id'));
}
$open = (int) input('open');
$list = rows('SELECT t.*, u.name AS rep FROM team_requests t LEFT JOIN users u ON u.id = t.assigned_to ORDER BY t.id DESC LIMIT 200');
$pipeline = (float) val("SELECT COALESCE(SUM(quote_amount),0) FROM team_requests WHERE status IN ('quoted')");
admin_header('Takım / Toplu Sipariş Talepleri', 'Bekleyen teklif tutarı: <strong>' . money($pipeline) . '</strong>');
?>
<section class="panel">
    <?php if (!$list): ?><p class="muted">Henüz takım siparişi talebi yok.</p><?php endif; ?>
    <div class="msg-list">
    <?php foreach ($list as $t): ?>
        <details class="msg <?= $t['status'] === 'new' ? 'is-new' : '' ?>" <?= $open === (int) $t['id'] ? 'open' : '' ?>>
            <summary>
                <span class="badge badge-<?= $statuses[$t['status']][1] ?>"><?= $statuses[$t['status']][0] ?></span>
                <strong><?= e($t['club_name']) ?></strong> <span class="muted">· <?= (int) $t['quantity'] ?> adet · <?= e($t['sport'] ?: '-') ?> · <?= e($t['city'] ?: '-') ?></span>
                <span class="push-right small muted"><?= tr_date($t['created_at']) ?></span>
            </summary>
            <div class="msg-body">
                <dl class="dl-grid">
                    <dt>Yetkili</dt><dd><?= e($t['contact_name']) ?> · <a href="tel:<?= e($t['phone']) ?>"><?= e($t['phone']) ?></a> · <a href="mailto:<?= e($t['email']) ?>"><?= e($t['email']) ?></a></dd>
                    <dt>Ürünler</dt><dd><?= e($t['products'] ?: '-') ?></dd>
                    <dt>İhtiyaç Tarihi</dt><dd><?= $t['needed_by'] ? tr_date($t['needed_by'], false) : '-' ?></dd>
                    <dt>Mesaj</dt><dd><?= nl2br(e($t['message'] ?: '-')) ?></dd>
                    <dt>İlgilenen</dt><dd><?= e($t['rep'] ?? '-') ?></dd>
                </dl>
                <form method="post" class="inline-form mt-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                    <select name="status"><?php foreach ($statuses as $k => [$l]): ?><option value="<?= $k ?>" <?= $t['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                    <input name="quote_amount" value="<?= $t['quote_amount'] !== null ? e(number_format((float) $t['quote_amount'], 2, ',', '')) : '' ?>" placeholder="Teklif tutarı (₺)" inputmode="decimal">
                    <button class="btn btn-dark btn-sm">Kaydet</button>
                    <a class="btn btn-outline btn-sm" href="mailto:<?= e($t['email']) ?>?subject=<?= rawurlencode('Takım sipariş teklifiniz - ' . setting('site_name')) ?>">E-posta yaz</a>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
    </div>
</section>
<?php admin_footer();
