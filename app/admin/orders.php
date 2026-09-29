<?php
require_once __DIR__ . '/dashboard.php';

function admin_orders(): void
{
    require_perm('orders.view');
    $status = $_GET['durum'] ?? '';
    $search = trim($_GET['q'] ?? '');
    $where = ['1=1']; $params = [];
    if (isset(ORDER_STATUSES[$status])) { $where[] = 'status = ?'; $params[] = $status; }
    if ($search !== '') {
        $where[] = '(order_no LIKE ? OR customer_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
        array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
    }
    $w = implode(' AND ', $where);
    $total = (int)val("SELECT COUNT(*) FROM orders WHERE $w", $params);
    [$page, $pages, $offset, $per] = paginate($total);
    $orders = rows("SELECT * FROM orders WHERE $w ORDER BY id DESC LIMIT $per OFFSET $offset", $params);
    $counts = [];
    foreach (rows('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];

    admin_render('Siparişler', function () use ($orders, $status, $search, $counts, $page, $pages, $total) { ?>
<div class="chips">
  <a href="?" class="chip <?= $status === '' ? 'on' : '' ?>">Tümü <em><?= array_sum($counts) ?></em></a>
  <?php foreach (ORDER_STATUSES as $k => [$label]): ?>
    <a href="?durum=<?= $k ?>" class="chip <?= $status === $k ? 'on' : '' ?>"><?= e($label) ?> <em><?= $counts[$k] ?? 0 ?></em></a>
  <?php endforeach; ?>
</div>
<section class="card">
  <form class="toolbar" method="get">
    <?php if ($status): ?><input type="hidden" name="durum" value="<?= e($status) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Sipariş no, müşteri adı, e-posta veya telefon ara…">
    <button class="btn btn-dark">Ara</button>
    <?php if (can('reports.sales')): ?><a class="btn btn-ghost" href="<?= url('admin/raporlar') ?>">Günlük görünüm</a><?php endif; ?>
  </form>
  <p class="small muted"><?= $total ?> sipariş bulundu.</p>
  <?php orders_table($orders); ?>
  <?= pager($page, $pages) ?>
</section>
<?php
    });
}

function admin_order(string $id): void
{
    $u = require_perm('orders.view');
    $o = row('SELECT * FROM orders WHERE id = ?', [(int)$id]);
    if (!$o) { flash('error', 'Sipariş bulunamadı.'); redirect('admin/siparisler'); }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (!can('orders.edit')) { flash('error', 'Yetkiniz yok.'); redirect('admin/siparis/' . $o['id']); }
        if (post('action') === 'status') {
            $new = post('status');
            $restricted = ['cancelled', 'refunded'];
            if (!isset(ORDER_STATUSES[$new])) flash('error', 'Geçersiz durum.');
            elseif (in_array($new, $restricted, true) && !can('orders.refund')) flash('error', 'İptal / iade işaretlemek için Admin veya Süper Admin yetkisi gerekir.');
            elseif (in_array($new, ['paid', 'processing', 'completed'], true) && in_array($o['status'], ['pending', 'failed'], true) && $u['role'] !== 'super_admin')
                flash('error', 'Ödemesi alınmamış bir siparişi yalnızca Süper Admin "ödendi" durumuna alabilir.');
            elseif ($new !== $o['status']) {
                q('UPDATE orders SET status = ?, updated_at = ?' . ($new === 'paid' && !$o['paid_at'] ? ', paid_at = ?' : '') . ' WHERE id = ?',
                    $new === 'paid' && !$o['paid_at'] ? [$new, now(), now(), $o['id']] : [$new, now(), $o['id']]);
                $label = ORDER_STATUSES[$o['status']][0] . ' → ' . ORDER_STATUSES[$new][0];
                q('INSERT INTO order_notes(order_id, user_id, user_name, note, created_at) VALUES(?,?,?,?,?)', [$o['id'], $u['id'], $u['name'], 'Durum değişti: ' . $label, now()]);
                log_activity('order_status', $o['order_no'] . ' — ' . $label);
                flash('success', 'Sipariş durumu güncellendi.');
            }
        } elseif (post('action') === 'note' && post('note') !== '') {
            q('INSERT INTO order_notes(order_id, user_id, user_name, note, created_at) VALUES(?,?,?,?,?)', [$o['id'], $u['id'], $u['name'], mb_substr(post('note'), 0, 2000), now()]);
            log_activity('order_note', $o['order_no'] . ' — ' . mb_substr(post('note'), 0, 120));
            flash('success', 'Not eklendi.');
        }
        redirect('admin/siparis/' . $o['id']);
    }

    $notes = rows('SELECT * FROM order_notes WHERE order_id = ? ORDER BY id DESC', [$o['id']]);
    $history = can('activity.view') ? rows('SELECT * FROM activity_log WHERE details LIKE ? ORDER BY id DESC LIMIT 30', [$o['order_no'] . '%']) : [];

    admin_render('Sipariş ' . $o['order_no'], function () use ($o, $notes, $history) { ?>
<div class="page-actions"><a class="btn btn-ghost" href="<?= url('admin/siparisler') ?>">← Siparişler</a></div>
<div class="grid-main">
  <div>
    <section class="card">
      <div class="card-head"><h3><?= e($o['service_title']) ?> — <?= e($o['package_name']) ?></h3><?= status_badge($o['status']) ?></div>
      <div class="detail-grid">
        <div><span>Sipariş No</span><strong><?= e($o['order_no']) ?></strong></div>
        <div><span>Tutar (KDV dahil)</span><strong class="big"><?= money((int)$o['amount']) ?></strong></div>
        <div><span>Sipariş tarihi</span><strong><?= tr_date($o['created_at']) ?></strong></div>
        <div><span>Ödeme tarihi</span><strong><?= tr_date($o['paid_at']) ?></strong></div>
        <div><span>Banka referansı</span><strong><?= e($o['payment_ref'] ?: '—') ?></strong></div>
        <div><span>Banka mesajı</span><strong><?= e($o['payment_message'] ?: '—') ?></strong></div>
      </div>
    </section>
    <section class="card">
      <div class="card-head"><h3>Müşteri & fatura bilgileri</h3></div>
      <div class="detail-grid">
        <div><span>Ad Soyad</span><strong><?= e($o['customer_name']) ?></strong></div>
        <div><span>E-posta</span><strong><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></strong></div>
        <div><span>Telefon</span><strong><a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a></strong></div>
        <div><span>Fatura tipi</span><strong><?= $o['invoice_type'] === 'kurumsal' ? 'Kurumsal' : 'Bireysel' ?></strong></div>
        <?php if ($o['invoice_type'] === 'kurumsal'): ?>
          <div><span>Firma</span><strong><?= e($o['company_name']) ?></strong></div>
          <div><span>Vergi D. / No</span><strong><?= e($o['tax_office']) ?> / <?= e($o['tax_no']) ?></strong></div>
        <?php else: ?>
          <div><span>T.C. Kimlik No</span><strong><?= e($o['identity_no'] ?: '—') ?></strong></div>
        <?php endif; ?>
        <div class="span-2"><span>Adres</span><strong><?= e($o['address']) ?> / <?= e($o['city']) ?></strong></div>
        <?php if ($o['note']): ?><div class="span-2"><span>Müşteri notu</span><strong><?= e($o['note']) ?></strong></div><?php endif; ?>
        <div><span>IP adresi</span><strong><?= e($o['ip']) ?></strong></div>
      </div>
    </section>
    <section class="card">
      <details><summary><strong>Onaylanan sözleşmeler</strong> (Ön Bilgilendirme Formu & Mesafeli Satış Sözleşmesi)</summary>
        <div class="contract"><?= $o['contract_html'] ?></div></details>
    </section>
  </div>
  <div>
    <?php if (can('orders.edit')): ?>
    <section class="card">
      <div class="card-head"><h3>Durumu güncelle</h3></div>
      <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="action" value="status">
        <select name="status">
          <?php foreach (ORDER_STATUSES as $k => [$label]): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-block">Kaydet</button>
        <?php if (!can('orders.refund')): ?><p class="small muted">İptal / iade işaretleme Admin yetkisindedir.</p><?php endif; ?>
      </form>
    </section>
    <?php endif; ?>
    <section class="card">
      <div class="card-head"><h3>Notlar & geçmiş</h3></div>
      <?php if (can('orders.edit')): ?>
      <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="action" value="note">
        <textarea name="note" rows="3" placeholder="Örn: Müşteri arandı, ilk görüşme 12 Ekim 14:00" required></textarea>
        <button class="btn btn-dark btn-sm">Not ekle</button>
      </form>
      <?php endif; ?>
      <ul class="notes">
        <?php foreach ($notes as $n): ?><li><div><strong><?= e($n['user_name']) ?></strong> <small><?= tr_date($n['created_at']) ?></small></div><?= nl2br(e($n['note'])) ?></li><?php endforeach; ?>
        <?php if (!$notes): ?><li class="muted">Henüz not yok.</li><?php endif; ?>
      </ul>
    </section>
    <?php if ($history): ?>
    <section class="card">
      <div class="card-head"><h3>Kayıt izi</h3></div>
      <ul class="activity compact"><?php foreach ($history as $a) echo activity_item($a); ?></ul>
    </section>
    <?php endif; ?>
  </div>
</div>
<?php
    }, ['subtitle' => $o['customer_name'] . ' · ' . tr_date($o['created_at'])]);
}
