<?php
require __DIR__ . '/_init.php';
require dirname(__DIR__) . '/includes/widgets.php';
$u = require_perm('messages.view');

$statuses = message_statuses();
$type = isset(MESSAGE_TYPES[input('type')]) ? input('type') : '';
$qs = fn(array $extra = []) => 'admin/messages.php?' . http_build_query(array_filter(['type' => $type] + $extra));

if (is_post()) {
    verify_csrf();
    $m = row('SELECT * FROM messages WHERE id = ?', [(int) input('id')]);
    if ($m && input('action') === 'reply') {
        $reply = trim((string) input('reply'));
        if (mb_strlen($reply) < 5) {
            flash('error', 'Yanıt en az 5 karakter olmalı.');
        } else {
            q("UPDATE messages SET reply = ?, replied_at = ?, status = 'replied', assigned_to = COALESCE(assigned_to, ?) WHERE id = ?", [mb_substr($reply, 0, 5000), now(), $u['id'], $m['id']]);
            $label = MESSAGE_TYPES[$m['type']] ?? 'Talep';
            $sent = input('send_mail') ? send_mail($m['email'], 'Re: ' . $m['subject'] . ($m['ticket_no'] ? ' [' . $m['ticket_no'] . ']' : ''),
                '<p>Merhaba ' . e($m['name']) . ',</p><p>' . nl2br(e($reply)) . '</p>' . ($m['type'] === 'ariza' ? '<p>Kaydınızın durumunu <a href="' . e(url('sayfa.php?s=ariza-takibi')) . '">Arıza Takibi</a> sayfasından da görebilirsiniz.</p>' : '')) : false;
            log_activity('Talebi yanıtladı', $label . ' · ' . $m['name'] . ($m['ticket_no'] ? ' · ' . $m['ticket_no'] : '') . ($sent ? ' (e-posta gönderildi)' : ''), 'message', (int) $m['id']);
            flash('success', 'Yanıt kaydedildi' . ($sent ? ' ve müşteriye e-posta gönderildi.' : '.'));
        }
    } elseif ($m && isset($statuses[input('status')])) {
        q('UPDATE messages SET status = ?, assigned_to = COALESCE(assigned_to, ?) WHERE id = ?', [input('status'), $u['id'], $m['id']]);
        log_activity('Talep durumunu güncelledi', $m['name'] . ' · ' . $statuses[input('status')][0], 'message', (int) $m['id']);
    }
    redirect($qs(['open' => (int) input('id')]));
}
$open = (int) input('open');
if ($open && ($m = row("SELECT * FROM messages WHERE id = ? AND status = 'new'", [$open]))) {
    q("UPDATE messages SET status = 'read', assigned_to = COALESCE(assigned_to, ?) WHERE id = ?", [$u['id'], $open]);
    log_activity('Talebi okudu', $m['name'] . ' · ' . $m['subject'], 'message', $open);
}
$where = $type ? 'WHERE m.type = ?' : '';
$params = $type ? [$type] : [];
$total = (int) val("SELECT COUNT(*) FROM messages m $where", $params);
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows("SELECT m.*, u.name AS rep FROM messages m LEFT JOIN users u ON u.id = m.assigned_to $where ORDER BY m.id DESC LIMIT $per OFFSET $offset", $params);
$counts = [];
foreach (rows("SELECT type, COUNT(*) AS c, SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS n FROM messages GROUP BY type") as $r) {
    $counts[$r['type']] = $r;
}
admin_header('Mesajlar / Talepler', 'İletişim, arıza kayıtları, KVKK başvuruları ve iş başvuruları');
?>
<div class="filter-tabs">
    <a href="<?= url('admin/messages.php') ?>" class="<?= $type === '' ? 'active' : '' ?>">Tümü</a>
    <?php foreach (MESSAGE_TYPES as $k => $l): $n = (int) ($counts[$k]['n'] ?? 0); ?>
        <a href="<?= url('admin/messages.php?type=' . $k) ?>" class="<?= $type === $k ? 'active' : '' ?>"><?= e($l) ?> <span class="muted">(<?= (int) ($counts[$k]['c'] ?? 0) ?>)</span><?= $n ? ' <span class="count">' . $n . '</span>' : '' ?></a>
    <?php endforeach; ?>
</div>
<section class="panel">
    <?php if (!$list): ?><p class="muted">Bu kategoride kayıt yok.</p><?php endif; ?>
    <div class="msg-list">
    <?php foreach ($list as $m): $st = $statuses[$m['status']] ?? ['-', 'gray']; ?>
        <details class="msg <?= $m['status'] === 'new' ? 'is-new' : '' ?>" <?= $open === (int) $m['id'] ? 'open' : '' ?> data-open-url="messages.php?<?= e(http_build_query(array_filter(['type' => $type, 'open' => (int) $m['id']]))) ?>">
            <summary>
                <span class="badge badge-<?= $st[1] ?>"><?= e($st[0]) ?></span>
                <span class="badge badge-dark"><?= e(MESSAGE_TYPES[$m['type']] ?? $m['type']) ?></span>
                <strong><?= e($m['name']) ?></strong> <span class="muted">· <?= e($m['subject']) ?><?= $m['ticket_no'] ? ' · ' . e($m['ticket_no']) : '' ?></span>
                <span class="push-right small muted"><?= tr_date($m['created_at']) ?></span>
            </summary>
            <div class="msg-body">
                <p><a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' · <a href="tel:' . e($m['phone']) . '">' . e($m['phone']) . '</a>' : '' ?><?= $m['rep'] ? ' · İlgilenen: <strong>' . e($m['rep']) . '</strong>' : '' ?></p>
                <?php if ($m['type'] === 'kvkk'): ?><div class="alert alert-info">KVKK başvurularına yasal yanıt süresi <b>30 gündür</b>. Son gün: <b><?= tr_date(date('Y-m-d', strtotime($m['created_at'] . ' +30 days')), false) ?></b></div><?php endif; ?>
                <p><?= nl2br(e($m['message'])) ?></p>
                <?php if ($m['reply']): ?><div class="reply-box"><strong>Verilen yanıt</strong> <small class="muted"><?= tr_date($m['replied_at']) ?></small><p><?= nl2br(e($m['reply'])) ?></p></div><?php endif; ?>
                <form method="post" class="form reply-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="reply">
                    <label><?= $m['reply'] ? 'Yanıtı güncelle' : 'Yanıt yaz' ?><?= $m['type'] === 'ariza' ? ' <small>(müşteri Arıza Takibi sayfasında da görür)</small>' : '' ?><textarea name="reply" rows="3"><?= e($m['reply'] ?? '') ?></textarea></label>
                    <label class="check"><input type="checkbox" name="send_mail" value="1" checked> <span>Yanıtı müşteriye e-posta ile de gönder</span></label>
                    <div><button class="btn btn-sm btn-primary">Yanıtı Kaydet</button></div>
                </form>
                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <?php foreach ($statuses as $k => [$l]): if ($k === $m['status']) continue; ?><button name="status" value="<?= $k ?>" class="btn btn-xs btn-outline"><?= e($l) ?> olarak işaretle</button><?php endforeach; ?>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
    </div>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
