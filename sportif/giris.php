<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(can('panel.access') ? 'admin/' : 'hesabim.php');
}
if (is_post()) {
    verify_csrf();
    if ($u = attempt_login(input('email'), (string) ($_POST['password'] ?? ''))) {
        $to = $_SESSION['intended'] ?? '';
        unset($_SESSION['intended']);
        if ($to && str_starts_with($to, '/') && !str_starts_with($to, '//')) {
            header('Location: ' . $to, true, 303);
            exit;
        }
        redirect(can('panel.access', $u) ? 'admin/' : 'hesabim.php');
    }
}
$pageTitle = 'Giriş Yap';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container narrow-sm">
    <div class="card">
        <h1 class="h2">Giriş Yap</h1>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <label>E-posta<input type="email" name="email" value="<?= e(input('email')) ?>" required autofocus></label>
            <label>Şifre<input type="password" name="password" required></label>
            <button class="btn btn-primary btn-block">Giriş Yap</button>
        </form>
        <p class="center small mt-1"><a href="<?= url('sifremi-unuttum.php') ?>">Şifremi unuttum</a> · Hesabınız yok mu? <a href="<?= url('kayit.php') ?>">Üye olun</a></p>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
