<?php
/** Aktivite akışı: kim, ne zaman, ne yaptı - YALNIZCA SÜPER ADMIN */
require __DIR__ . '/_init.php';
$u = require_perm('activity.view');

$where = ['1=1'];
$params = [];
$f = ['q' => input('q'), 'user' => input('user'), 'role' => input('role'), 'from' => input('from'), 'to' => input('to'), 'type' => input('type')];
if ($f['q'] !== '') { $where[] = '(action LIKE ? OR details LIKE ? OR user_name LIKE ?)'; array_push($params, ...array_fill(0, 3, '%' . $f['q'] . '%')); }
if ($f['user'] !== '') { $where[] = 'user_id = ?'; $params[] = (int) $f['user']; }
if ($f['role'] !== '') { $where[] = 'user_role = ?'; $params[] = $f['role']; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) { $where[] = 'created_at >= ?'; $params[] = $f['from'] . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) { $where[] = 'created_at <= ?'; $params[] = $f['to'] . ' 23:59:59'; }
$types = ['order' => 'Sipariş & ödeme', 'user' => 'Kullanıcı & giriş', 'service' => 'Hizmet', 'package' => 'Paket', 'page' => 'Sayfa', 'settings' => 'Ayarlar', 'message' => 'Mesaj', 'report' => 'Rapor'];
if (isset($types[$f['type']])) { $where[] = 'entity = ?'; $params[] = $f['type']; }
$w = implode(' AND ', $where);

$total = (int) val("SELECT COUNT(*) FROM activity_log WHERE $w", $params);
[$page, $pages, $offset, $per] = paginate($total, 60);
$list = rows("SELECT * FROM activity_log WHERE $w ORDER BY id DESC LIMIT $per OFFSET $offset", $params);
$staff = rows("SELECT id, name, role FROM users WHERE role <> 'member' ORDER BY name");

$todayCount = (int) val('SELECT COUNT(*) FROM activity_log WHERE created_at >= ?', [date('Y-m-d') . ' 00:00:00']);
admin_header('Aktivite Akışı', 'Sitede kim, ne zaman, ne yaptı · Bugün <strong>' . $todayCount . '</strong> işlem · <span class="lock-tag">★ Yalnızca Süper Admin</span>');
?>
<form class="panel filters" method="get">
    <input name="q" value="<?= e($f['q']) ?>" placeholder="İşlem, detay veya kişi ara…">
    <select name="user"><option value="">Tüm personel</option><?php foreach ($staff as $s): ?><option value="<?= (int) $s['id'] ?>" <?= $f['user'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e(role_label($s['role'])) ?>)</option><?php endforeach; ?></select>
    <select name="role"><option value="">Tüm roller</option><?php foreach (ROLES + ['guest' => 'Ziyaretçi', 'system' => 'Sistem'] as $k => $l): ?><option value="<?= $k ?>" <?= $f['role'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <select name="type"><option value="">Tüm işlem türleri</option><?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $f['type'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <label class="inline">Başlangıç<input type="date" name="from" value="<?= e($f['from']) ?>"></label>
    <label class="inline">Bitiş<input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    <button class="btn btn-dark btn-sm">Filtrele</button>
    <a class="btn btn-outline btn-sm" href="activity.php">Temizle</a>
    <label class="inline chk push-right"><input type="checkbox" data-autorefresh="30"> Canlı (30 sn'de bir yenile)</label>
</form>
<section class="panel">
    <?php if (!$list): ?><p class="muted">Kayıt bulunamadı.</p><?php endif; ?>
    <?php $lastDay = null; foreach ($list as $a):
        $d = substr($a['created_at'], 0, 10);
        if ($d !== $lastDay): if ($lastDay !== null) echo '</ul>'; $lastDay = $d; ?>
            <h3 class="day-head"><?= tr_day_name($d) ?>, <?= date('d.m.Y', strtotime($d)) ?> <a class="small" href="reports.php?day=<?= $d ?>">günün raporu →</a></h3><ul class="feed">
        <?php endif; ?>
        <li>
            <span class="dot role-<?= e($a['user_role']) ?>"></span>
            <div>
                <span class="time"><?= date('H:i:s', strtotime($a['created_at'])) ?></span>
                <strong><?= e($a['user_name']) ?></strong>
                <span class="role-tag"><?= e(ROLES[$a['user_role']] ?? ($a['user_role'] === 'guest' ? 'Ziyaretçi' : 'Sistem')) ?></span>
                — <?= e($a['action']) ?>
                <?php if ($a['entity'] === 'order' && $a['entity_id'] && row('SELECT id FROM orders WHERE id = ?', [$a['entity_id']])): ?><a class="small" href="order.php?id=<?= (int) $a['entity_id'] ?>">siparişe git</a><?php endif; ?>
                <br><small><?= e($a['details']) ?> · IP: <?= e($a['ip']) ?></small>
            </div>
        </li>
    <?php endforeach; if ($lastDay !== null) echo '</ul>'; ?>
    <?= pager($page, $pages) ?>
</section>
<?php admin_footer();
