<?php
require __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('hesabim.php');
}
$old = ['name' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    verify_csrf();
    $old = ['name' => (string) input('name'), 'email' => mb_strtolower((string) input('email')), 'phone' => (string) input('phone')];
    $pass = (string) input('password');
    $error = null;
    if (mb_strlen($old['name']) < 5 || !str_contains($old['name'], ' ')) $error = 'Adınızı ve soyadınızı girin.';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $error = 'Geçerli bir e-posta girin.';
    elseif (strlen($pass) < 8 || !preg_match('/\d/', $pass) || !preg_match('/[a-zA-ZğüşıöçĞÜŞİÖÇ]/u', $pass)) $error = 'Şifre en az 8 karakter olmalı ve harf ile rakam içermelidir.';
    elseif ($pass !== input('password2')) $error = 'Şifreler eşleşmiyor.';
    elseif (!input('uyelik') || !input('kvkk')) $error = 'Üyelik Sözleşmesi ve Aydınlatma Metni onayı gereklidir.';
    elseif (val('SELECT 1 FROM users WHERE email = ?', [$old['email']])) $error = 'Bu e-posta ile kayıtlı bir hesap zaten var.';

    if ($error) {
        flash('error', $error);
    } else {
        $id = insert('users', ['name' => mb_substr($old['name'], 0, 120), 'email' => $old['email'], 'phone' => mb_substr($old['phone'], 0, 30),
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'uye', 'active' => 1, 'created_at' => now()]);
        $u = row('SELECT * FROM users WHERE id = ?', [$id]);
        log_activity('Üye oldu', $u['email'], 'admin/kullanicilar.php?q=' . urlencode($u['email']), $u);
        session_regenerate_id(true);
        $_SESSION['uid'] = $id;
        flash('success', 'Üyeliğiniz oluşturuldu. Hoş geldiniz!');
        redirect('hesabim.php');
    }
}
$title = 'Üye Ol';
require __DIR__ . '/app/header.php';
?>
<section class="section-sm">
    <div class="auth-wrap">
        <form method="post" class="card form">
            <?= csrf_field() ?>
            <h1>Üye Ol</h1>
            <label>Ad Soyad<input name="name" value="<?= e($old['name']) ?>" required autocomplete="name"></label>
            <label>E-posta<input type="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="email"></label>
            <label>Telefon<input name="phone" value="<?= e($old['phone']) ?>" autocomplete="tel"></label>
            <div class="grid-2">
                <label>Şifre<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label>Şifre (tekrar)<input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
            </div>
            <label class="check"><input type="checkbox" name="uyelik" value="1" required> <span><a href="<?= url('sayfa.php?s=uyelik-sozlesmesi') ?>" target="_blank">Üyelik Sözleşmesi</a>'ni okudum, kabul ediyorum.</span></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
            <button class="btn btn-primary btn-lg">Üye Ol</button>
            <p class="center muted small">Zaten üye misiniz? <a href="<?= url('giris.php') ?>">Giriş yapın</a></p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
