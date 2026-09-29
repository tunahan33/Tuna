<?php
require_once __DIR__ . '/dashboard.php';
require_once APP_ROOT . '/app/pages/forms.php';

const MESSAGE_TYPES = ['iletisim' => 'İletişim formu', 'ik' => 'İK başvurusu', 'kvkk' => 'KVKK başvurusu'];
const MESSAGE_STATUSES = ['new' => ['Yeni', 'yellow'], 'read' => ['Okundu', 'gray'], 'replied' => ['Yanıtlandı', 'blue'], 'closed' => ['Kapandı', 'green']];

function msg_badge(string $s): string
{
    [$l, $c] = MESSAGE_STATUSES[$s] ?? [$s, 'gray'];
    return '<span class="badge badge-' . $c . '">' . e($l) . '</span>';
}

function admin_messages(): void
{
    require_perm('messages.view');
    $type = $_GET['tur'] ?? '';
    $where = '1=1'; $params = [];
    if (isset(MESSAGE_TYPES[$type])) { $where = 'type = ?'; $params[] = $type; }
    $total = (int)val("SELECT COUNT(*) FROM messages WHERE $where", $params);
    [$page, $pages, $offset, $per] = paginate($total);
    $list = rows("SELECT * FROM messages WHERE $where ORDER BY CASE status WHEN 'new' THEN 0 ELSE 1 END, id DESC LIMIT $per OFFSET $offset", $params);
    $counts = [];
    foreach (rows("SELECT type, COUNT(*) c FROM messages WHERE status = 'new' GROUP BY type") as $r) $counts[$r['type']] = (int)$r['c'];

    admin_render('Mesajlar & Başvurular', function () use ($list, $type, $counts, $page, $pages) { ?>
<div class="chips">
  <a href="?" class="chip <?= $type === '' ? 'on' : '' ?>">Tümü <em><?= array_sum($counts) ?> yeni</em></a>
  <?php foreach (MESSAGE_TYPES as $k => $l): ?><a href="?tur=<?= $k ?>" class="chip <?= $type === $k ? 'on' : '' ?>"><?= e($l) ?> <em><?= $counts[$k] ?? 0 ?></em></a><?php endforeach; ?>
</div>
<section class="card">
  <?php if (!$list): ?><p class="empty-line">Henüz mesaj yok.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Tür</th><th>Gönderen</th><th>Konu</th><th>Tarih</th><th>Durum</th></tr></thead>
    <tbody><?php foreach ($list as $m): ?>
      <tr class="clickable<?= $m['status'] === 'new' ? ' unread' : '' ?>" data-href="<?= url('admin/mesaj/' . $m['id']) ?>">
        <td><?= e(MESSAGE_TYPES[$m['type']] ?? $m['type']) ?></td>
        <td><a href="<?= url('admin/mesaj/' . $m['id']) ?>"><strong><?= e($m['name']) ?></strong></a><br><small class="muted"><?= e($m['email']) ?></small></td>
        <td><?= e($m['subject']) ?><br><small class="muted"><?= e(mb_strimwidth($m['message'], 0, 80, '…')) ?></small></td>
        <td><?= tr_date($m['created_at']) ?></td>
        <td><?= msg_badge($m['status']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?= pager($page, $pages) ?>
  <?php endif; ?>
</section>
<?php
    });
}

function admin_message(string $id): void
{
    require_perm('messages.view');
    $m = row('SELECT * FROM messages WHERE id = ?', [(int)$id]);
    if (!$m) redirect('admin/mesajlar');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $s = post('status');
        if (isset(MESSAGE_STATUSES[$s])) {
            q('UPDATE messages SET status = ? WHERE id = ?', [$s, $m['id']]);
            log_activity('message_status', (MESSAGE_TYPES[$m['type']] ?? '') . ' #' . $m['id'] . ' ' . $m['name'] . ' → ' . MESSAGE_STATUSES[$s][0]);
            flash('success', 'Durum güncellendi.');
        }
        redirect('admin/mesaj/' . $m['id']);
    }
    if ($m['status'] === 'new') { q("UPDATE messages SET status = 'read' WHERE id = ?", [$m['id']]); $m['status'] = 'read'; }

    admin_render(MESSAGE_TYPES[$m['type']] ?? 'Mesaj', function () use ($m) { ?>
<div class="page-actions"><a class="btn btn-ghost" href="<?= url('admin/mesajlar') ?>">← Mesajlar</a></div>
<div class="grid-main">
  <section class="card">
    <div class="card-head"><h3><?= e($m['subject'] ?: 'Mesaj') ?></h3><?= msg_badge($m['status']) ?></div>
    <div class="detail-grid">
      <div><span>Ad Soyad</span><strong><?= e($m['name']) ?></strong></div>
      <div><span>E-posta</span><strong><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></strong></div>
      <div><span>Telefon</span><strong><?= e($m['phone'] ?: '—') ?></strong></div>
      <div><span>Tarih</span><strong><?= tr_date($m['created_at']) ?> · IP <?= e($m['ip']) ?></strong></div>
      <?php if ($m['extra']): ?><div class="span-2"><span>Ek bilgiler</span><strong><?= nl2br(e(str_replace(' | ', "\n", $m['extra']))) ?></strong></div><?php endif; ?>
    </div>
    <div class="message-body"><?= nl2br(e($m['message'] ?: '—')) ?></div>
    <?php if ($m['type'] === 'kvkk'): ?><div class="alert alert-warn">KVKK başvuruları en geç <strong>30 gün</strong> içinde yanıtlanmalıdır. Son tarih: <strong><?= date('d.m.Y', strtotime($m['created_at'] . ' +30 days')) ?></strong></div><?php endif; ?>
  </section>
  <form method="post" class="card form">
    <div class="card-head"><h3>İşlem</h3></div>
    <?= csrf_field() ?>
    <select name="status"><?php foreach (MESSAGE_STATUSES as $k => [$l]): ?><option value="<?= $k ?>" <?= $m['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary btn-block">Durumu kaydet</button>
    <a class="btn btn-ghost btn-block" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . ($m['subject'] ?: setting('site_name'))) ?>">E-posta ile yanıtla</a>
  </form>
</div>
<?php
    });
}

function admin_tickets(): void
{
    require_perm('tickets.manage');
    $status = $_GET['durum'] ?? '';
    $where = '1=1'; $params = [];
    if (isset(TICKET_STATUSES[$status])) { $where = 'status = ?'; $params[] = $status; }
    $list = rows("SELECT t.*, (SELECT COUNT(*) FROM ticket_replies r WHERE r.ticket_id = t.id) rc FROM tickets t WHERE $where ORDER BY CASE status WHEN 'open' THEN 0 ELSE 1 END, updated_at DESC LIMIT 200", $params);
    admin_render('Arıza / Destek Kayıtları', function () use ($list, $status) { ?>
<div class="chips">
  <a href="?" class="chip <?= $status === '' ? 'on' : '' ?>">Tümü</a>
  <?php foreach (TICKET_STATUSES as $k => [$l]): ?><a href="?durum=<?= $k ?>" class="chip <?= $status === $k ? 'on' : '' ?>"><?= e($l) ?></a><?php endforeach; ?>
</div>
<section class="card">
  <?php if (!$list): ?><p class="empty-line">Kayıt yok.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Kayıt</th><th>Müşteri</th><th>Konu</th><th>Sipariş</th><th>Son hareket</th><th>Durum</th></tr></thead>
    <tbody><?php foreach ($list as $t): ?>
      <tr class="clickable<?= $t['status'] === 'open' ? ' unread' : '' ?>" data-href="<?= url('admin/destek/' . $t['id']) ?>">
        <td><a href="<?= url('admin/destek/' . $t['id']) ?>"><strong><?= e($t['ticket_no']) ?></strong></a></td>
        <td><?= e($t['name']) ?><br><small class="muted"><?= e($t['email']) ?></small></td>
        <td><?= e($t['subject']) ?> <small class="muted">(<?= (int)$t['rc'] ?> yanıt)</small></td>
        <td><?= e($t['order_no'] ?: '—') ?></td>
        <td><?= tr_date($t['updated_at']) ?></td>
        <td><?= ticket_badge($t['status']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php
    });
}

function admin_ticket(string $id): void
{
    $u = require_perm('tickets.manage');
    $t = row('SELECT * FROM tickets WHERE id = ?', [(int)$id]);
    if (!$t) redirect('admin/destek');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $msg = post('message');
        $status = isset(TICKET_STATUSES[post('status')]) ? post('status') : $t['status'];
        if ($msg !== '') {
            q('INSERT INTO ticket_replies(ticket_id, author, is_staff, message, created_at) VALUES(?,?,1,?,?)', [$t['id'], $u['name'], $msg, now()]);
            if ($status === 'open') $status = 'answered';
            log_activity('ticket_reply', $t['ticket_no'] . ' — ' . mb_substr($msg, 0, 100));
        }
        q('UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?', [$status, now(), $t['id']]);
        flash('success', 'Kayıt güncellendi.');
        redirect('admin/destek/' . $t['id']);
    }
    $replies = rows('SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY id', [$t['id']]);
    $order = $t['order_no'] ? row('SELECT id FROM orders WHERE order_no = ?', [$t['order_no']]) : null;

    admin_render('Destek ' . $t['ticket_no'], function () use ($t, $replies, $order) { ?>
<div class="page-actions"><a class="btn btn-ghost" href="<?= url('admin/destek') ?>">← Destek kayıtları</a>
  <?php if ($order && can('orders.view')): ?><a class="btn btn-ghost" href="<?= url('admin/siparis/' . $order['id']) ?>">Siparişi aç (<?= e($t['order_no']) ?>)</a><?php endif; ?></div>
<div class="grid-main">
  <section class="card">
    <div class="card-head"><h3><?= e($t['subject']) ?></h3><?= ticket_badge($t['status']) ?></div>
    <div class="thread">
      <div class="msg"><div class="msg-meta"><?= e($t['name']) ?> · <?= e($t['email']) ?> · <?= tr_date($t['created_at']) ?></div><?= nl2br(e($t['message'])) ?></div>
      <?php foreach ($replies as $r): ?><div class="msg<?= $r['is_staff'] ? ' staff' : '' ?>"><div class="msg-meta"><?= e($r['author']) ?><?= $r['is_staff'] ? ' (personel)' : '' ?> · <?= tr_date($r['created_at']) ?></div><?= nl2br(e($r['message'])) ?></div><?php endforeach; ?>
    </div>
  </section>
  <form method="post" class="card form">
    <div class="card-head"><h3>Yanıtla</h3></div>
    <?= csrf_field() ?>
    <textarea name="message" rows="6" placeholder="Müşteri bu yanıtı Arıza takibi sayfasında görür."></textarea>
    <label>Durum<select name="status"><?php foreach (TICKET_STATUSES as $k => [$l]): ?><option value="<?= $k ?>" <?= $t['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <button class="btn btn-primary btn-block">Gönder / Kaydet</button>
  </form>
</div>
<?php
    });
}
