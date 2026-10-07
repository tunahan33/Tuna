<?php
require __DIR__ . '/_layout.php';
$u = require_perm('panel');
if (is_post()) {
    verify_csrf();
    $new = (string) input('new');
    if (!password_verify((string) input('current'), $u['password_hash'])) {
        flash('error', 'Mevcut şifre hatalı.');
    } elseif (strlen($new) < 8 || $new !== input('new2')) {
        flash('error', 'Yeni şifre en az 8 karakter olmalı ve iki alan eşleşmeli.');
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        log_activity('Şifresini değiştirdi');
        flash('success', 'Şifreniz değiştirildi.');
    }
    redirect('admin/profil.php');
}
admin_header('Profilim', e($u['email']) . ' · ' . e(role_label($u['role'])));
?>
<form method="post" class="panel form" style="max-width:520px">
    <?= csrf_field() ?>
    <h2>Şifre Değiştir</h2>
    <label>Mevcut şifre<input type="password" name="current" required autocomplete="current-password"></label>
    <label>Yeni şifre<input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
    <label>Yeni şifre (tekrar)<input type="password" name="new2" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-primary">Şifreyi Değiştir</button>
</form>
<?php admin_footer();
