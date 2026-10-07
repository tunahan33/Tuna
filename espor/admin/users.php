<?php
require __DIR__ . '/_init.php';
$u = require_perm('users.view');

if (is_post()) {
    verify_csrf();
    $t = row('SELECT * FROM users WHERE id = ?', [(int) input('id')]);
    $newRole = (string) input('new_role');
    if ($t && can_edit_user($t) && input('action') === 'role') {
        if (!can_manage_role($newRole)) {
            flash('error', 'Bu rolü atama yetkiniz yok.');
        } elseif ($newRole !== $t['role']) {
            q('UPDATE users SET role = ? WHERE id = ?', [$newRole, $t['id']]);
            log_activity('Kullanıcının yetkisini değiştirdi', $t['name'] . ' (' . $t['email'] . ') · ' . role_label($t['role']) . ' → ' . role_label($newRole), 'user', (int) $t['id']);
            flash('success', $t['name'] . ' artık ' . role_label($newRole) . '.' . ($newRole === 'super_admin' ? ' Süper admin hesapları korumalıdır; bu yetki sonradan panelden geri alınamaz.' : ''));
        }
    } elseif ($t && can_edit_user($t) && input('action') === 'toggle') {
        $new = $t['status'] === 'active' ? 'passive' : 'active';
        q('UPDATE users SET status = ? WHERE id = ?', [$new, $t['id']]);
        log_activity($new === 'active' ? 'Kullanıcıyı aktifleştirdi' : 'Kullanıcıyı pasifleştirdi', $t['name'] . ' (' . role_label($t['role']) . ')', 'user', (int) $t['id']);
        flash('success', 'Kullanıcı durumu güncellendi.');
    } else {
        if ($t && $t['role'] === 'super_admin' && (int) $t['id'] !== (int) $u['id']) {
            log_activity('Yetkisiz erişim denemesi', 'Başka bir süper adminin ' . (input('action') === 'role' ? 'yetkisini değiştirmeye' : 'hesabını pasifleştirmeye') . ' çalıştı: ' . $t['name'], 'user', (int) $t['id']);
        }
        flash('error', 'Bu işlem için yetkiniz yok.' . ($t && $t['role'] === 'super_admin' ? ' Süper admin hesapları korumalıdır.' : ''));
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

admin_header('Kullanıcılar & Yetkiler', 'Kayıtlı üyelere yetki verin: satırdaki listeden Admin, Editör, Satış Temsilcisi veya Üye seçip “Kaydet”e basın');
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
    <div class="table-wrap"><table class="table users-table">
        <thead><tr><th>Kullanıcı</th><th>Rol / Yetki</th><th>Kayıt</th><th>Son Giriş</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $x): $manage = can_edit_user($x); ?>
            <tr>
                <td><strong><?= e($x['name']) ?></strong><br><small class="muted"><?= e($x['email']) ?><?= $x['phone'] ? ' · ' . e($x['phone']) : '' ?></small></td>
                <td>
                    <?php if ($manage): ?>
                    <form method="post" class="role-quick" data-role-form><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="role" value="<?= e($role) ?>"><input type="hidden" name="action" value="role">
                        <select name="new_role" aria-label="Yetki" data-name="<?= e($x['name']) ?>">
                            <?php foreach (MANAGEABLE_ROLES[$u['role']] as $rk): ?><option value="<?= $rk ?>" <?= $rk === $x['role'] ? 'selected' : '' ?>><?= e(role_label($rk)) ?></option><?php endforeach; ?>
                        </select><button class="btn btn-xs btn-primary">Kaydet</button>
                    </form>
                    <?php else: ?><span class="role role-<?= e($x['role']) ?>"><?= e(role_label($x['role'])) ?></span><?php endif; ?>
                </td>
                <td><?= tr_date($x['created_at'], false) ?></td>
                <td><?= tr_date($x['last_login_at']) ?></td>
                <td><?= $x['status'] === 'active' ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
                <td class="actions">
                    <?php if ($manage): ?>
                        <a class="btn btn-xs btn-outline" href="user_edit.php?id=<?= (int) $x['id'] ?>">Düzenle</a>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="role" value="<?= e($role) ?>"><button name="action" value="toggle" class="btn btn-xs btn-outline"><?= $x['status'] === 'active' ? 'Pasifleştir' : 'Aktifleştir' ?></button></form>
                    <?php elseif ((int) $x['id'] === (int) $u['id']): ?>
                        <a class="btn btn-xs btn-outline" href="profile.php">Profilim</a>
                    <?php elseif ($x['role'] === 'super_admin'): ?>
                        <span class="small muted" title="Süper admin hesapları korumalıdır; yetkisi düşürülemez, pasifleştirilemez.">🔒 Korumalı</span>
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
        $labels = ['panel.access' => 'Yönetim paneline giriş', 'orders.view' => 'Siparişleri görüntüleme', 'orders.status' => 'Sipariş durumu güncelleme', 'orders.cancel' => 'İptal / iade / atama', 'orders.delete' => 'Sipariş silme', 'customers.view' => 'Müşteri listesi', 'messages.view' => 'İletişim mesajları', 'content.edit' => 'Hizmet & paket metinleri', 'content.create' => 'Hizmet & paket ekleme / yayın', 'content.delete' => 'Hizmet & paket silme', 'prices.edit' => 'Fiyat belirleme', 'pages.edit' => 'Sayfa & sözleşme düzenleme', 'users.manage' => 'Kullanıcı & rol yönetimi', 'reports.view' => 'Günlük sipariş / satış raporları', 'activity.view' => 'Aktivite akışı (kim ne yaptı)', 'settings.edit' => 'Site & sanal POS ayarları'];
        foreach ($labels as $perm => $l): ?>
            <tr><td><?= e($l) ?></td><?php foreach (ROLES as $k => $_): ?><td class="center"><?= in_array($k, PERMISSIONS[$perm], true) ? '<span class="yes">✓</span>' : '<span class="no">—</span>' ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        <tr><td>Satın alma (müşteri olarak)</td><?php foreach (ROLES as $k => $_): ?><td class="center"><span class="yes">✓</span></td><?php endforeach; ?></tr>
        </tbody>
    </table></div>
    <p class="small muted">Admin; editör, satış temsilcisi ve üyeleri yönetebilir. Admin hesaplarını yalnızca süper admin yönetebilir. Süper admin yeni süper admin atayabilir; ancak süper admin hesapları korumalıdır: hiçbir süper admin bir başkasının yetkisini düşüremez, hesabını pasifleştiremez veya bilgilerini/şifresini değiştiremez.</p>
</section>
<?php admin_footer();
