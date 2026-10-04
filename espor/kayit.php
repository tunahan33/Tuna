<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('hesabim.php');
}
$old = ['name' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    verify_csrf();
    $old = ['name' => input('name'), 'email' => mb_strtolower(input('email')), 'phone' => input('phone')];
    $pw = (string) ($_POST['password'] ?? '');
    if (input('website') !== '') {
        redirect('kayit.php');
    }
    if (mb_strlen($old['name']) < 3) {
        flash('error', 'Lütfen adınızı ve soyadınızı girin.');
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Geçerli bir e-posta adresi girin.');
    } elseif (strlen($pw) < 8) {
        flash('error', 'Şifreniz en az 8 karakter olmalıdır.');
    } elseif ($pw !== ($_POST['password2'] ?? '')) {
        flash('error', 'Şifreler eşleşmiyor.');
    } elseif (!input('terms') || !input('kvkk')) {
        flash('error', 'Üyelik Sözleşmesi ve Genel Aydınlatma Metni onaylanmalıdır.');
    } elseif (row('SELECT id FROM users WHERE email = ?', [$old['email']])) {
        flash('error', 'Bu e-posta adresiyle kayıtlı bir hesap zaten var.');
    } else {
        $id = insert('users', [
            'name' => mb_substr($old['name'], 0, 120), 'email' => $old['email'], 'phone' => mb_substr($old['phone'], 0, 30),
            'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'role' => 'member', 'status' => 'active', 'created_at' => now(),
        ]);
        $u = row('SELECT * FROM users WHERE id = ?', [$id]);
        log_activity('Yeni üye kaydı', $u['name'] . ' (' . $u['email'] . ')', 'user', $id, $u);
        login_user($u);
        flash('success', 'Üyeliğiniz oluşturuldu. Hoş geldiniz!');
        $to = $_SESSION['intended'] ?? '';
        unset($_SESSION['intended']);
        if ($to && str_starts_with($to, '/') && !str_starts_with($to, '//')) {
            header('Location: ' . $to, true, 303);
            exit;
        }
        redirect('hesabim.php');
    }
}
$pageTitle = 'Üye Ol';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container narrow-sm">
    <div class="card">
        <h1 class="h2">Üye Ol</h1>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <label>Ad Soyad<input name="name" value="<?= e($old['name']) ?>" required></label>
            <label>E-posta<input type="email" name="email" value="<?= e($old['email']) ?>" required></label>
            <label>Telefon<input name="phone" value="<?= e($old['phone']) ?>" placeholder="05xx xxx xx xx"></label>
            <div class="grid-2">
                <label>Şifre<input type="password" name="password" minlength="8" required></label>
                <label>Şifre Tekrar<input type="password" name="password2" minlength="8" required></label>
            </div>
            <label class="check"><input type="checkbox" name="terms" value="1" required> <span><a href="<?= url('sayfa.php?s=uyelik-sozlesmesi') ?>" target="_blank">Üyelik Sözleşmesi</a>'ni okudum ve kabul ediyorum.</span></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
            <button class="btn btn-primary btn-block">Üye Ol</button>
        </form>
        <p class="center small mt-1">Zaten üye misiniz? <a href="<?= url('giris.php') ?>">Giriş yapın</a></p>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
