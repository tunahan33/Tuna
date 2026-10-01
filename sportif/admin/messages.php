<?php
require __DIR__ . '/_init.php';
$u = require_perm('messages.view');

$statuses = ['new' => ['Yeni', 'red'], 'read' => ['Okundu', 'gray'], 'replied' => ['Yanıtlandı', 'green']];
if (is_post()) {
    verify_csrf();
    $m = row('SELECT * FROM messages WHERE id = ?', [(int) input('id')]);
    if ($m && isset($statuses[input('status')])) {
        q('UPDATE messages SET status = ?, assigned_to = COALESCE(assigned_to, ?) WHERE id = ?', [input('status'), $u['id'], $m['id']]);
        log_activity('Mesaj durumunu güncelledi', $m['name'] . ' · ' . $statuses[input('status')][0], 'message', (int) $m['id']);
    }
    redirect('admin/messages.php' . (input('open') ? '?open=' . (int) input('open') : ''));
}
$open = (int) input('open');
if ($open && ($m = row("SELECT * FROM messages WHERE id = ? AND status = 'new'", [$open]))) {
    q("UPDATE messages SET status = 'read', assigned_to = COALESCE(assigned_to, ?) WHERE id = ?", [$u['id'], $open]);
    log_activity('Mesajı okudu', $m['name'] . ' · ' . $m['subject'], 'message', $open);
}
$total = (int) val('SELECT COUNT(*) FROM messages');
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows("SELECT m.*, u.name AS rep FROM messages m LEFT JOIN users u ON u.id = m.assigned_to ORDER BY m.id DESC LIMIT $per OFFSET $offset");
admin_header('Mesajlar / Talepler', 'İletişim formundan gelen talepler');
?>
<section class="panel">
    <?php if (!$list): ?><p class="muted">Henüz mesaj yok.</p><?php endif; ?>
    <div class="msg-list">
    <?php foreach ($list as $m): ?>
        <details class="msg <?= $m['status'] === 'new' ? 'is-new' : '' ?>" <?= $open === (int) $m['id'] ? 'open' : '' ?> data-open-url="messages.php?open=<?= (int) $m['id'] ?>">
            <summary>
                <span class="badge badge-<?= $statuses[$m['status']][1] ?>"><?= $statuses[$m['status']][0] ?></span>
                <strong><?= e($m['name']) ?></strong> <span class="muted">· <?= e($m['subject']) ?></span>
                <span class="push-right small muted"><?= tr_date($m['created_at']) ?></span>
            </summary>
            <div class="msg-body">
                <p><a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' · <a href="tel:' . e($m['phone']) . '">' . e($m['phone']) . '</a>' : '' ?><?= $m['rep'] ? ' · İlgilenen: <strong>' . e($m['rep']) . '</strong>' : '' ?></p>
                <p><?= nl2br(e($m['message'])) ?></p>
                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="open" value="<?= (int) $m['id'] ?>">
                    <?php foreach ($statuses as $k => [$l]): if ($k === $m['status']) continue; ?><button name="status" value="<?= $k ?>" class="btn btn-xs btn-outline"><?= $l ?> olarak işaretle</button><?php endforeach; ?>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
    </div>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
