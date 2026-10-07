<?php
require __DIR__ . '/_layout.php';
$u = require_perm('users');

$id = (int) input('id');
$x = $id ? row('SELECT * FROM users WHERE id = ?', [$id]) : null;
if ($id && (!$x || !can_manage_role($x['role']))) {
    flash('error', 'Bu kullanıcıyı düzenleme yetkiniz yok.');
    redirect('admin/kullanicilar.php');
}
$x ??= ['id' => 0, 'name' => '', 'email' => '', 'phone' => '', 'role' => 'satis', 'active' => 1];
$roles = array_intersect_key(ROLES, array_flip(MANAGEABLE_ROLES[$u['role']] ?? []));

if (is_post()) {
    verify_csrf();
    $data = ['name' => mb_substr((string) input('name'), 0, 120), 'email' => mb_strtolower((string) input('email')), 'phone' => mb_substr((string) input('phone'), 0, 30),
        'role' => (string) input('role'), 'active' => input('active') ? 1 : 0];
    $pass = (string) input('password');
    $errors = [];
    if (mb_strlen($data['name']) < 3) $errors[] = 'Ad soyad girin.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta girin.';
    if (val('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$data['email'], $x['id']])) $errors[] = 'Bu e-posta başka bir hesapta kayıtlı.';
    if (!isset($roles[$data['role']])) $errors[] = 'Bu rolü atama yetkiniz yok.';
    if (!$x['id'] && strlen($pass) < 8) $errors[] = 'Yeni kullanıcı için en az 8 karakterlik şifre belirleyin.';
    if ($pass !== '' && strlen($pass) < 8) $errors[] = 'Şifre en az 8 karakter olmalı.';
    if ($x['id'] == $u['id'] && ($data['role'] !== $u['role'] || !$data['active'])) $errors[] = 'Kendi rolünüzü değiştiremez veya hesabınızı pasifleştiremezsiniz.';
    if ($x['role'] === 'super_admin' && ($data['role'] !== 'super_admin' || !$data['active']) && (int) val("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND active = 1") <= 1) $errors[] = 'Sistemde en az bir aktif Süper Admin kalmalıdır.';

    if ($errors) {
        flash('error', implode(' ', $errors));
        $x = array_merge($x, $data);
    } else {
        if ($pass !== '') $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        if ($x['id']) {
            update('users', $data, (int) $x['id']);
            $detail = $data['name'] . ($x['role'] !== $data['role'] ? ' · rol: ' . role_label($x['role']) . ' → ' . role_label($data['role']) : '') . ($pass !== '' ? ' · şifre değişti' : '') . ($x['active'] != $data['active'] ? ($data['active'] ? ' · aktifleştirildi' : ' · pasifleştirildi') : '');
            log_activity('Kullanıcıyı güncelledi', $detail, 'admin/kullanici-duzenle.php?id=' . $x['id']);
        } else {
            $newId = insert('users', $data + ['created_at' => now()]);
            log_activity('Yeni kullanıcı oluşturdu', $data['name'] . ' · ' . role_label($data['role']), 'admin/kullanici-duzenle.php?id=' . $newId);
        }
        flash('success', 'Kullanıcı kaydedildi.');
        redirect('admin/kullanicilar.php');
    }
}
$stats = $x['id'] ? row("SELECT COUNT(*) n, MAX(created_at) last FROM activity WHERE user_id = ?", [$x['id']]) : null;
admin_header($x['id'] ? $x['name'] : 'Yeni Kullanıcı', $x['id'] ? role_label($x['role']) : 'Personel veya üye hesabı oluşturun', '<a class="btn btn-ghost" href="' . url('admin/kullanicilar.php') . '">← Kullanıcılar</a>');
?>
<div class="grid-2-1">
    <form method="post" class="panel form">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Ad Soyad<input name="name" value="<?= e($x['name']) ?>" required></label>
            <label>E-posta<input type="email" name="email" value="<?= e($x['email']) ?>" required></label>
            <label>Telefon<input name="phone" value="<?= e($x['phone']) ?>"></label>
            <label>Rol<select name="role"><?php foreach ($roles as $k => $l): ?><option value="<?= $k ?>" <?= $x['role'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
        </div>
        <label><?= $x['id'] ? 'Yeni şifre <small class="muted">(değiştirmek istemiyorsanız boş bırakın)</small>' : 'Şifre' ?><input type="password" name="password" minlength="8" autocomplete="new-password" <?= $x['id'] ? '' : 'required' ?>></label>
        <label class="check"><input type="checkbox" name="active" value="1" <?= $x['active'] ? 'checked' : '' ?>> Hesap aktif (pasif hesaplar giriş yapamaz)</label>
        <button class="btn btn-primary">Kaydet</button>
    </form>
    <section class="panel">
        <h2>Roller</h2>
        <ul class="role-help">
            <li><span class="role-tag role-super_admin">Süper Admin</span> Her şey: günlük satış raporu, aktivite akışı, ayarlar, sipariş silme, tüm kullanıcılar.</li>
            <li><span class="role-tag role-admin">Admin</span> Siparişler (iptal/iade dâhil), ürün-fiyat-stok, kategoriler, sayfalar, talepler; editör/satış/üye yönetimi.</li>
            <li><span class="role-tag role-editor">Editör</span> Yalnızca ürün metinleri ve kurumsal/yasal sayfalar.</li>
            <li><span class="role-tag role-satis">Satış Temsilcisi</span> Siparişleri hazırlama, kargoya verme, teslim; müşteriler ve talepler.</li>
            <li><span class="role-tag role-uye">Üye</span> Panele giremez; sitede alışveriş ve sipariş takibi.</li>
        </ul>
        <?php if ($stats && can('activity')): ?><p class="small"><a href="<?= url('admin/aktivite.php?kisi=' . urlencode($x['name'])) ?>"><?= (int) $stats['n'] ?> işlem kaydı →</a></p><?php endif; ?>
    </section>
</div>
<?php admin_footer();
