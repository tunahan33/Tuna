<?php
/** İletişim mesajları, arıza kayıtları, KVKK başvuruları ve iş başvuruları */
require __DIR__ . '/_layout.php';
$u = require_perm('requests.view');

if (is_post()) {
    verify_csrf();
    $r = row('SELECT * FROM requests WHERE id = ?', [(int) input('id')]);
    if ($r && isset(REQUEST_STATUSES[input('status')])) {
        $reply = trim((string) input('reply'));
        q('UPDATE requests SET status = ?, reply = ?, assigned_to = COALESCE(assigned_to, ?), updated_at = ? WHERE id = ?',
            [input('status'), $reply !== '' ? mb_substr($reply, 0, 3000) : $r['reply'], $u['id'], now(), $r['id']]);
        log_activity('Talebi güncelledi', $r['ticket_no'] . ' → ' . REQUEST_STATUSES[input('status')][0], 'admin/talepler.php?no=' . $r['ticket_no']);
        flash('success', 'Talep güncellendi.');
    }
    redirect('admin/talepler.php?no=' . urlencode($r['ticket_no'] ?? '') . '&tur=' . urlencode((string) input('tur')));
}

$where = ['1=1'];
$params = [];
if (isset(REQUEST_TYPES[input('tur')])) { $where[] = 'r.type = ?'; $params[] = input('tur'); }
if (isset(REQUEST_STATUSES[input('durum')])) { $where[] = 'r.status = ?'; $params[] = input('durum'); }
if (input('no') !== '') { $where[] = 'r.ticket_no = ?'; $params[] = input('no'); }
$sqlWhere = implode(' AND ', $where);
$total = (int) val("SELECT COUNT(*) FROM requests r WHERE $sqlWhere", $params);
[$page, $pages, $offset, $per] = paginate($total, 25);
$list = rows("SELECT r.*, u.name rep FROM requests r LEFT JOIN users u ON u.id = r.assigned_to WHERE $sqlWhere ORDER BY r.status = 'yeni' DESC, r.id DESC LIMIT $per OFFSET $offset", $params);
$counts = [];
foreach (rows("SELECT type, COUNT(*) n FROM requests WHERE status IN ('yeni','inceleniyor','serviste') GROUP BY type") as $c) $counts[$c['type']] = $c['n'];
$labels = ['siparis_no' => 'Sipariş No', 'urun' => 'Ürün', 'satin_alma' => 'Satın alma', 'tc_kimlik_son4' => 'TC (son 4)', 'iliski' => 'İlişki', 'talep_turu' => 'Talep türü',
    'adres' => 'Adres', 'yanit_yontemi' => 'Yanıt yöntemi', 'pozisyon' => 'Pozisyon', 'sehir' => 'Şehir', 'deneyim' => 'Deneyim', 'linkedin' => 'Bağlantı'];

admin_header('Talepler', 'İletişim, arıza, KVKK ve iş başvuruları · Yanıtınız arıza takibi sayfasında müşteriye görünür');
?>
<div class="chips">
    <a href="?" class="<?= !input('tur') ? 'active' : '' ?>">Tümü</a>
    <?php foreach (REQUEST_TYPES as $k => [$l]): ?><a href="?tur=<?= $k ?>" class="<?= input('tur') === $k ? 'active' : '' ?>"><?= $l ?> <?php if (!empty($counts[$k])): ?><em><?= $counts[$k] ?></em><?php endif; ?></a><?php endforeach; ?>
</div>
<section class="panel">
    <?php if (!$list): ?><p class="muted">Talep bulunamadı.</p><?php endif; ?>
    <div class="req-list">
    <?php foreach ($list as $r): $x = json_decode((string) $r['extra'], true) ?: []; ?>
        <details class="req" <?= input('no') === $r['ticket_no'] ? 'open' : '' ?>>
            <summary>
                <?= request_badge($r['status']) ?>
                <span class="badge badge-gray"><?= e(REQUEST_TYPES[$r['type']][0]) ?></span>
                <strong><?= e($r['name']) ?></strong> <span class="muted">· <?= e($r['subject']) ?></span>
                <span class="push"><?= e($r['ticket_no']) ?> · <?= tr_date($r['created_at']) ?></span>
            </summary>
            <div class="req-body">
                <p class="small"><a href="mailto:<?= e($r['email']) ?>?subject=<?= rawurlencode($r['ticket_no'] . ' - ' . $r['subject']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?><?= $r['rep'] ? ' · İlgilenen: <strong>' . e($r['rep']) . '</strong>' : '' ?></p>
                <?php if ($x): ?><p class="small"><?php foreach ($x as $k => $v): if ($v === '' || $v === null) continue; ?><span class="kv"><b><?= e($labels[$k] ?? $k) ?>:</b> <?= e($v) ?></span><?php endforeach; ?></p><?php endif; ?>
                <blockquote><?= nl2br(e($r['message'])) ?></blockquote>
                <form method="post" class="form">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="tur" value="<?= e(input('tur')) ?>">
                    <label>Yanıt / açıklama <?= $r['type'] === 'ariza' ? '<small class="muted">(müşteri arıza takibi sayfasında görür)</small>' : '<small class="muted">(iç not)</small>' ?><textarea name="reply" rows="3"><?= e($r['reply']) ?></textarea></label>
                    <div class="inline-form">
                        <select name="status"><?php foreach (REQUEST_STATUSES as $k => [$l]): if ($k === 'serviste' && $r['type'] !== 'ariza') continue; ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
                        <button class="btn btn-dark">Kaydet</button>
                    </div>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
    </div>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
