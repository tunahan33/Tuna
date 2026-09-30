<?php
require __DIR__ . '/_init.php';
$u = require_perm('panel.access');

if (is_post()) {
    verify_csrf();
    if (input('action') === 'profile') {
        $name = mb_substr(input('name'), 0, 120);
        if (mb_strlen($name) >= 3) {
            q('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, mb_substr(input('phone'), 0, 30), $u['id']]);
            log_activity('Profilini güncelledi', '', 'user', (int) $u['id']);
            flash('success', 'Profil güncellendi.');
        }
    } else {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $u['password_hash'])) {
            flash('error', 'Mevcut şifre hatalı.');
        } elseif (strlen($new) < 8 || $new !== ($_POST['new_password2'] ?? '')) {
            flash('error', 'Yeni şifre en az 8 karakter olmalı ve tekrarıyla eşleşmeli.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            q('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $u['id']]);
            $_SESSION['upw'] = substr($hash, -12);
            log_activity('Şifresini değiştirdi', '', 'user', (int) $u['id']);
            flash('success', 'Şifre değiştirildi.');
        }
    }
    redirect('admin/profile.php');
}
admin_header('Profilim', e(role_label($u['role'])));
?>
<div class="grid-2">
    <form method="post" class="panel form"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <h3>Bilgilerim</h3>
        <label>Ad Soyad<input name="name" value="<?= e($u['name']) ?>" required></label>
        <label>E-posta<input value="<?= e($u['email']) ?>" disabled></label>
        <label>Telefon<input name="phone" value="<?= e($u['phone']) ?>"></label>
        <button class="btn btn-dark">Kaydet</button>
    </form>
    <form method="post" class="panel form"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <h3>Şifre Değiştir</h3>
        <label>Mevcut Şifre<input type="password" name="current_password" required></label>
        <label>Yeni Şifre<input type="password" name="new_password" minlength="8" required></label>
        <label>Yeni Şifre Tekrar<input type="password" name="new_password2" minlength="8" required></label>
        <button class="btn btn-dark">Şifreyi Değiştir</button>
    </form>
</div>
<?php admin_footer();
