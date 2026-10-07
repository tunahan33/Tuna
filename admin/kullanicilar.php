<?php
require __DIR__ . '/_layout.php';
$u = require_perm('users');

$where = ['1=1'];
$params = [];
if (isset(ROLES[input('rol')])) { $where[] = 'role = ?'; $params[] = input('rol'); }
if (($term = input('q')) !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; array_push($params, "%$term%", "%$term%"); }
$total = (int) val('SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $where), $params);
[$page, $pages, $offset, $per] = paginate($total, 30);
$list = rows('SELECT * FROM users WHERE ' . implode(' AND ', $where) . " ORDER BY CASE role WHEN 'super_admin' THEN 1 WHEN 'admin' THEN 2 WHEN 'editor' THEN 3 WHEN 'satis' THEN 4 ELSE 5 END, name LIMIT $per OFFSET $offset", $params);
$counts = [];
foreach (rows('SELECT role, COUNT(*) n FROM users GROUP BY role') as $r) $counts[$r['role']] = $r['n'];

admin_header('Kullanıcılar & Roller', $total . ' kullanıcı', '<a class="btn btn-primary" href="' . url('admin/kullanici-duzenle.php') . '">+ Yeni Kullanıcı</a>');
?>
<div class="chips">
    <a href="?" class="<?= !input('rol') ? 'active' : '' ?>">Tümü <em><?= array_sum($counts) ?></em></a>
    <?php foreach (ROLES as $k => $l): ?><a href="?rol=<?= $k ?>" class="<?= input('rol') === $k ? 'active' : '' ?>"><?= $l ?> <em><?= $counts[$k] ?? 0 ?></em></a><?php endforeach; ?>
</div>
<div class="grid-2-1">
    <section class="panel">
        <form class="filter-bar flat"><input name="q" value="<?= e($term) ?>" placeholder="Ad veya e-posta"><button class="btn btn-dark">Ara</button></form>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Kullanıcı</th><th>Rol</th><th>Son giriş</th><th>Durum</th></tr></thead>
            <tbody>
            <?php foreach ($list as $x): $editable = can_manage_role($x['role']); ?>
                <tr <?= $editable ? 'data-href="' . url('admin/kullanici-duzenle.php?id=' . $x['id']) . '"' : 'class="row-muted" title="Bu kullanıcıyı düzenleme yetkiniz yok"' ?>>
                    <td><strong><?= e($x['name']) ?></strong><?= $x['id'] == $u['id'] ? ' <span class="badge badge-yellow">Siz</span>' : '' ?><br><small class="muted"><?= e($x['email']) ?></small></td>
                    <td><span class="role-tag role-<?= e($x['role']) ?>"><?= e(role_label($x['role'])) ?></span></td>
                    <td class="small"><?= tr_date($x['last_login']) ?></td>
                    <td><?= $x['active'] ? badge('Aktif', 'green') : badge('Pasif', 'gray') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?= pager($page, $pages) ?>
    </section>
    <section class="panel">
        <h2>Rol Yetkileri</h2>
        <table class="table perm-table">
            <thead><tr><th></th><th title="Süper Admin">SA</th><th title="Admin">A</th><th title="Editör">E</th><th title="Satış Temsilcisi">ST</th></tr></thead>
            <tbody>
            <?php
            $perm = ['orders.view' => 'Siparişleri görme', 'orders.update' => 'Hazırlama / kargo', 'orders.cancel' => 'İptal & iade', 'orders.assign' => 'Temsilci atama', 'orders.delete' => 'Sipariş silme',
                'customers.view' => 'Müşteriler', 'requests.view' => 'Talepler', 'products.text' => 'Ürün metinleri', 'products.manage' => 'Ürün / fiyat / stok', 'categories' => 'Kategoriler',
                'pages' => 'Sayfa & sözleşmeler', 'users' => 'Kullanıcı yönetimi', 'reports' => 'Günlük satış raporu', 'activity' => 'Aktivite akışı', 'settings' => 'Mağaza ayarları'];
            foreach ($perm as $k => $l): ?>
                <tr><td><?= $l ?></td><?php foreach (['super_admin', 'admin', 'editor', 'satis'] as $r): ?><td class="center"><?= in_array($r, PERMISSIONS[$k], true) ? '<span class="yes">✓</span>' : '<span class="no">–</span>' ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="small muted">Admin yalnızca Editör, Satış Temsilcisi ve Üye hesaplarını yönetebilir. Süper Admin herkesi yönetir.</p>
    </section>
</div>
<?php admin_footer();
