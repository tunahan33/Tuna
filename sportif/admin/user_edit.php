<?php
require __DIR__ . '/_init.php';
$u = require_perm('users.manage');

$id = (int) input('id');
$x = $id ? row('SELECT * FROM users WHERE id = ?', [$id]) : null;
if ($id && (!$x || !can_manage_role($x['role']) || (int) $x['id'] === (int) $u['id'])) {
    flash('error', 'Bu kullanıcıyı düzenleme yetkiniz yok.');
    redirect('admin/users.php');
}
$x = $x ?? ['name' => '', 'email' => '', 'phone' => '', 'role' => 'sales', 'status' => 'active'];
$roles = array_intersect_key(ROLES, array_flip(MANAGEABLE_ROLES[$u['role']]));

if (is_post()) {
    verify_csrf();
    $data = [
        'name' => mb_substr(input('name'), 0, 120),
        'email' => mb_strtolower(input('email')),
        'phone' => mb_substr(input('phone'), 0, 30),
        'role' => input('role'),
        'status' => input('status') === 'passive' ? 'passive' : 'active',
    ];
    $pw = (string) ($_POST['password'] ?? '');
    $err = null;
    if (mb_strlen($data['name']) < 3) $err = 'Ad soyad girin.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $err = 'Geçerli bir e-posta girin.';
    elseif (!isset($roles[$data['role']])) $err = 'Bu rolü atama yetkiniz yok.';
    elseif (row('SELECT id FROM users WHERE email = ? AND id <> ?', [$data['email'], $id])) $err = 'Bu e-posta başka bir kullanıcıda kayıtlı.';
    elseif (!$id && strlen($pw) < 8) $err = 'Yeni kullanıcı için en az 8 karakterlik şifre belirleyin.';
    elseif ($pw !== '' && strlen($pw) < 8) $err = 'Şifre en az 8 karakter olmalıdır.';

    if ($err) {
        flash('error', $err);
        $x = array_merge($x, $data);
    } else {
        if ($pw !== '') $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        if ($id) {
            update('users', $data, $id);
            $msg = $data['name'] . ' (' . $data['email'] . ')';
            if ($x['role'] !== $data['role']) $msg .= ' · Rol: ' . role_label($x['role']) . ' → ' . role_label($data['role']);
            if ($pw !== '') $msg .= ' · Şifre sıfırlandı';
            log_activity('Kullanıcıyı düzenledi', $msg, 'user', $id);
        } else {
            $id = insert('users', $data + ['created_by' => $u['id'], 'created_at' => now()]);
            log_activity('Yeni kullanıcı oluşturdu', $data['name'] . ' · ' . role_label($data['role']), 'user', $id);
        }
        flash('success', 'Kullanıcı kaydedildi.');
        redirect('admin/users.php');
    }
}

$roleDesc = [
    'super_admin' => 'Tam yetki. Günlük sipariş/satış raporları, aktivite akışı ve site/POS ayarları yalnızca bu roldedir.',
    'admin' => 'Siparişler, iptal/iade, fiyat, stok, kupon, ürünler ve editör/satış/üye hesaplarını yönetir. Raporlar ve aktivite akışını göremez.',
    'editor' => 'Ürün açıklamaları, fotoğrafları ve yasal sayfaları düzenler. Fiyat, stok, sipariş ve kullanıcılara erişemez.',
    'sales' => 'Siparişleri hazırlar, kargo takip numarası girer, durumu “Hazırlanıyor / Kargoda / Teslim Edildi” yapar; takım talepleri ve mesajlarla ilgilenir.',
    'member' => 'Müşteri hesabı. Paket satın alır, kendi siparişlerini görür. Panele erişemez.',
];
admin_header($id ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı');
?>
<form method="post" class="panel form narrow-form">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Ad Soyad<input name="name" value="<?= e($x['name']) ?>" required></label>
        <label>E-posta<input type="email" name="email" value="<?= e($x['email']) ?>" required></label>
        <label>Telefon<input name="phone" value="<?= e($x['phone']) ?>"></label>
        <label>Durum<select name="status"><option value="active">Aktif</option><option value="passive" <?= $x['status'] === 'passive' ? 'selected' : '' ?>>Pasif</option></select></label>
    </div>
    <label>Rol / Yetki</label>
    <div class="role-pick">
        <?php foreach ($roles as $k => $l): ?>
            <label><input type="radio" name="role" value="<?= $k ?>" <?= $x['role'] === $k ? 'checked' : '' ?>><span><strong class="role role-<?= $k ?>"><?= e($l) ?></strong><small><?= e($roleDesc[$k]) ?></small></span></label>
        <?php endforeach; ?>
    </div>
    <label><?= $id ? 'Yeni Şifre <small>(değiştirmek istemiyorsanız boş bırakın)</small>' : 'Şifre' ?><input type="password" name="password" minlength="8" <?= $id ? '' : 'required' ?> autocomplete="new-password"></label>
    <div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-outline" href="users.php">Vazgeç</a></div>
</form>
<?php admin_footer();
