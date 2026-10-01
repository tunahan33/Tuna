<?php
require __DIR__ . '/_init.php';
$u = require_perm('users.view');

if (is_post()) {
    verify_csrf();
    $t = row('SELECT * FROM users WHERE id = ?', [(int) input('id')]);
    if ($t && (int) $t['id'] !== (int) $u['id'] && can_manage_role($t['role']) && input('action') === 'toggle') {
        $new = $t['status'] === 'active' ? 'passive' : 'active';
        q('UPDATE users SET status = ? WHERE id = ?', [$new, $t['id']]);
        log_activity($new === 'active' ? 'Kullanıcıyı aktifleştirdi' : 'Kullanıcıyı pasifleştirdi', $t['name'] . ' (' . role_label($t['role']) . ')', 'user', (int) $t['id']);
        flash('success', 'Kullanıcı durumu güncellendi.');
    } else {
        flash('error', 'Bu işlem için yetkiniz yok.');
    }
    redirect('admin/users.php?' . http_build_query(['role' => input('role')]));
}

$where = ['1=1'];
$params = [];
$role = input('role');
if (isset(ROLES[$role])) { $where[] = 'role = ?'; $params[] = $role; }
if (($qq = input('q')) !== '') { $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)'; array_push($params, ...array_fill(0, 3, "%$qq%")); }
$w = implode(' AND ', $where);
$total = (int) val("SELECT COUNT(*) FROM users WHERE $w", $params);
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows("SELECT * FROM users WHERE $w ORDER BY CASE role WHEN 'super_admin' THEN 1 WHEN 'admin' THEN 2 WHEN 'editor' THEN 3 WHEN 'sales' THEN 4 ELSE 5 END, id DESC LIMIT $per OFFSET $offset", $params);
$counts = [];
foreach (rows('SELECT role, COUNT(*) AS c FROM users GROUP BY role') as $r) $counts[$r['role']] = (int) $r['c'];

admin_header('Kullanıcılar & Yetkiler', 'Üyelere rol atayarak yetkilendirin');
?>
<div class="role-tabs">
    <a href="users.php" class="<?= !$role ? 'active' : '' ?>">Tümü <em><?= array_sum($counts) ?></em></a>
    <?php foreach (ROLES as $k => $l): ?><a href="?role=<?= $k ?>" class="<?= $role === $k ? 'active' : '' ?>"><?= e($l) ?> <em><?= $counts[$k] ?? 0 ?></em></a><?php endforeach; ?>
</div>
<div class="toolbar">
    <form method="get" class="inline-form"><input type="hidden" name="role" value="<?= e($role) ?>"><input name="q" value="<?= e(input('q')) ?>" placeholder="İsim, e-posta, telefon…"><button class="btn btn-dark btn-sm">Ara</button></form>
    <?php if (can('users.manage')): ?><a class="btn btn-primary btn-sm push-right" href="user_edit.php">+ Yeni Kullanıcı</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kullanıcı</th><th>Rol</th><th>Telefon</th><th>Kayıt</th><th>Son Giriş</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $x): $manage = can_manage_role($x['role']) && (int) $x['id'] !== (int) $u['id']; ?>
            <tr>
                <td><strong><?= e($x['name']) ?></strong><br><small class="muted"><?= e($x['email']) ?></small></td>
                <td><span class="role role-<?= e($x['role']) ?>"><?= e(role_label($x['role'])) ?></span></td>
                <td><?= e($x['phone'] ?: '-') ?></td>
                <td><?= tr_date($x['created_at'], false) ?></td>
                <td><?= tr_date($x['last_login_at']) ?></td>
                <td><?= $x['status'] === 'active' ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
                <td class="actions">
                    <?php if ($manage): ?>
                        <a class="btn btn-xs btn-outline" href="user_edit.php?id=<?= (int) $x['id'] ?>">Düzenle</a>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="role" value="<?= e($role) ?>"><button name="action" value="toggle" class="btn btn-xs btn-outline"><?= $x['status'] === 'active' ? 'Pasifleştir' : 'Aktifleştir' ?></button></form>
                    <?php elseif ((int) $x['id'] === (int) $u['id']): ?>
                        <a class="btn btn-xs btn-outline" href="profile.php">Profilim</a>
                    <?php else: ?>
                        <span class="small muted">🔒</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?= pager($page, $pages) ?>
</section>
<section class="panel">
    <div class="panel-head"><h2>Rol & Yetki Tablosu</h2></div>
    <div class="table-wrap"><table class="table perm-table">
        <thead><tr><th>Yetki</th><?php foreach (ROLES as $k => $l): ?><th class="center"><span class="role role-<?= $k ?>"><?= e($l) ?></span></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php
        $labels = ['panel.access' => 'Yönetim paneline giriş', 'orders.view' => 'Siparişleri görüntüleme', 'orders.status' => 'Sipariş durumu güncelleme', 'orders.cancel' => 'İptal / iade / atama', 'orders.delete' => 'Sipariş silme', 'customers.view' => 'Müşteri listesi', 'messages.view' => 'İletişim mesajları', 'orders.ship' => 'Kargoya verme / takip no girme', 'team.view' => 'Takım sipariş talepleri', 'content.edit' => 'Ürün metinleri ve fotoğrafları', 'content.create' => 'Ürün & kategori ekleme / yayın', 'content.delete' => 'Ürün & kategori silme', 'prices.edit' => 'Fiyat belirleme', 'stock.edit' => 'Stok ve beden/renk yönetimi', 'coupons.manage' => 'İndirim kuponları', 'pages.edit' => 'Sayfa & sözleşme düzenleme', 'users.manage' => 'Kullanıcı & rol yönetimi', 'reports.view' => 'Günlük sipariş / satış raporları', 'activity.view' => 'Aktivite akışı (kim ne yaptı)', 'settings.edit' => 'Site & sanal POS ayarları'];
        foreach ($labels as $perm => $l): ?>
            <tr><td><?= e($l) ?></td><?php foreach (ROLES as $k => $_): ?><td class="center"><?= in_array($k, PERMISSIONS[$perm], true) ? '<span class="yes">✓</span>' : '<span class="no">—</span>' ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        <tr><td>Satın alma (müşteri olarak)</td><?php foreach (ROLES as $k => $_): ?><td class="center"><span class="yes">✓</span></td><?php endforeach; ?></tr>
        </tbody>
    </table></div>
    <p class="small muted">Admin; editör, satış temsilcisi ve üyeleri yönetebilir. Admin ve süper admin hesaplarını yalnızca süper admin yönetebilir.</p>
</section>
<?php admin_footer();
