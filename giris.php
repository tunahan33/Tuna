<?php
require __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('hesabim.php');
}
if (is_post()) {
    verify_csrf();
    if ($u = attempt_login((string) input('email'), (string) input('password'))) {
        $to = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login']);
        flash('success', 'Hoş geldiniz, ' . explode(' ', $u['name'])[0] . '!');
        if ($to && str_starts_with($to, base_path() . '/')) {
            header('Location: ' . $to);
            exit;
        }
        redirect(can('panel', $u) ? 'admin/' : 'hesabim.php');
    }
}
$title = 'Giriş Yap';
require __DIR__ . '/app/header.php';
?>
<section class="section-sm">
    <div class="auth-wrap">
        <form method="post" class="card form">
            <?= csrf_field() ?>
            <h1>Giriş Yap</h1>
            <label>E-posta<input type="email" name="email" value="<?= e(input('email')) ?>" required autocomplete="email"></label>
            <label>Şifre<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="btn btn-primary btn-lg">Giriş Yap</button>
            <p class="center muted small">Hesabınız yok mu? <a href="<?= url('kayit.php') ?>">Hemen üye olun</a></p>
            <p class="center muted small">Şifrenizi mi unuttunuz? <a href="<?= url('iletisim.php') ?>">Bize yazın</a>, hesabınızı doğrulayıp yeni şifre iletelim.</p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
