<?php
require __DIR__ . '/includes/bootstrap.php';

$token = input('token');
$user = null;
if ($token) {
    $user = row('SELECT * FROM users WHERE reset_token = ? AND reset_expires >= ?', [hash('sha256', $token), now()]);
    if (!$user) {
        flash('error', 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.');
        redirect('sifremi-unuttum.php');
    }
}
if (is_post()) {
    verify_csrf();
    if ($user) {
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 8 || $pw !== ($_POST['password2'] ?? '')) {
            flash('error', 'Şifre en az 8 karakter olmalı ve tekrarı ile eşleşmelidir.');
            redirect('sifremi-unuttum.php?token=' . urlencode($token));
        }
        q('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $user['id']]);
        log_activity('Şifre sıfırlandı', $user['email'], 'user', (int) $user['id'], $user);
        flash('success', 'Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
        redirect('giris.php');
    }
    $u = row('SELECT * FROM users WHERE email = ? AND status = ?', [mb_strtolower(input('email')), 'active']);
    if ($u) {
        $raw = bin2hex(random_bytes(24));
        q('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?', [hash('sha256', $raw), date('Y-m-d H:i:s', time() + 3600), $u['id']]);
        $link = url('sifremi-unuttum.php?token=' . $raw);
        send_mail($u['email'], 'Şifre sıfırlama', '<p>Merhaba ' . e($u['name']) . ',</p><p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın (1 saat geçerlidir):</p><p><a href="' . e($link) . '">' . e($link) . '</a></p>');
        log_activity('Şifre sıfırlama talebi', $u['email'], 'user', (int) $u['id'], $u);
    }
    flash('success', 'E-posta adresiniz kayıtlıysa şifre sıfırlama bağlantısı gönderildi.');
    redirect('sifremi-unuttum.php');
}
$pageTitle = 'Şifremi Unuttum';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container narrow-sm"><div class="card">
    <h1 class="h2"><?= $user ? 'Yeni Şifre Belirle' : 'Şifremi Unuttum' ?></h1>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <?php if ($user): ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>Yeni Şifre<input type="password" name="password" minlength="8" required></label>
            <label>Yeni Şifre Tekrar<input type="password" name="password2" minlength="8" required></label>
            <button class="btn btn-primary btn-block">Şifreyi Güncelle</button>
        <?php else: ?>
            <label>Kayıtlı e-posta adresiniz<input type="email" name="email" required></label>
            <button class="btn btn-primary btn-block">Sıfırlama Bağlantısı Gönder</button>
        <?php endif; ?>
    </form>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
