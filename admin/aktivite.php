<?php
/** SÜPER ADMİN'E ÖZEL: Kim, ne zaman, ne yaptı — tüm işlemlerin tek tek akışı */
require __DIR__ . '/_layout.php';
$u = require_perm('activity');

$where = ['1=1'];
$params = [];
if (input('kisi') !== '') { $where[] = 'user_name = ?'; $params[] = input('kisi'); }
if (input('rol') !== '') { $where[] = 'role = ?'; $params[] = input('rol'); }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', input('gun'))) { $where[] = 'date(created_at) = ?'; $params[] = input('gun'); }
if (($term = input('q')) !== '') { $where[] = '(action LIKE ? OR details LIKE ?)'; array_push($params, "%$term%", "%$term%"); }
$sqlWhere = implode(' AND ', $where);
$total = (int) val("SELECT COUNT(*) FROM activity WHERE $sqlWhere", $params);
[$page, $pages, $offset, $per] = paginate($total, 50);
$list = rows("SELECT * FROM activity WHERE $sqlWhere ORDER BY created_at DESC, id DESC LIMIT $per OFFSET $offset", $params);
$people = rows("SELECT user_name, role, COUNT(*) n FROM activity WHERE role NOT IN ('ziyaretci','sistem') GROUP BY user_name, role ORDER BY n DESC");
$roles = ['super_admin', 'admin', 'editor', 'satis', 'uye', 'ziyaretci', 'sistem'];

admin_header('Aktivite Akışı', 'Yalnızca Süper Admin görebilir · Personelin ve müşterilerin tüm işlemleri, tek tek');
?>
<form class="filter-bar">
    <select name="kisi"><option value="">Tüm kişiler</option><?php foreach ($people as $p): ?><option <?= input('kisi') === $p['user_name'] ? 'selected' : '' ?> value="<?= e($p['user_name']) ?>"><?= e($p['user_name']) ?> (<?= e(role_label($p['role'])) ?>)</option><?php endforeach; ?></select>
    <select name="rol"><option value="">Tüm roller</option><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= input('rol') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?></select>
    <input type="date" name="gun" value="<?= e(input('gun')) ?>" aria-label="Gün">
    <input name="q" value="<?= e($term) ?>" placeholder="İşlem veya detay ara">
    <button class="btn btn-dark">Filtrele</button>
    <?php if ($where !== ['1=1']): ?><a href="?" class="small">Temizle</a><?php endif; ?>
</form>
<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2><?= $total ?> kayıt</h2></div>
        <ul class="feed timeline-feed">
            <?php $lastDay = null; foreach ($list as $a): $d = substr($a['created_at'], 0, 10); ?>
                <?php if ($d !== $lastDay): $lastDay = $d; ?><li class="feed-day"><?= tr_date($d, false) ?> · <?= tr_day($d) ?></li><?php endif; ?>
                <li>
                    <span class="dot role-<?= e($a['role']) ?>"></span>
                    <div>
                        <span class="time"><?= date('H:i', strtotime($a['created_at'])) ?></span>
                        <strong><?= e($a['user_name']) ?></strong> <span class="role-tag role-<?= e($a['role']) ?>"><?= e(role_label($a['role'])) ?></span>
                        <span class="act"><?= e($a['action']) ?></span>
                        <?php if ($a['details']): ?><span class="muted">— <?= $a['link'] ? '<a href="' . e(url($a['link'])) . '">' . e($a['details']) . '</a>' : e($a['details']) ?></span><?php endif; ?>
                        <small>IP: <?= e($a['ip']) ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (!$list): ?><li class="muted">Kayıt bulunamadı.</li><?php endif; ?>
        </ul>
        <?= pager($page, $pages) ?>
    </section>
    <section class="panel">
        <h2>Personel Özeti</h2>
        <table class="table"><tbody>
            <?php foreach ($people as $p): ?><tr data-href="?kisi=<?= urlencode($p['user_name']) ?>"><td><strong><?= e($p['user_name']) ?></strong><br><span class="role-tag role-<?= e($p['role']) ?>"><?= e(role_label($p['role'])) ?></span></td><td style="text-align:right"><?= (int) $p['n'] ?> işlem</td></tr><?php endforeach; ?>
        </tbody></table>
    </section>
</div>
<?php admin_footer();
