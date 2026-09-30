<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

if (is_post()) {
    verify_csrf();
    if (input('action') === 'profile') {
        $name = mb_substr(input('name'), 0, 120);
        $phone = mb_substr(input('phone'), 0, 30);
        if (mb_strlen($name) >= 3) {
            q('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, $phone, $user['id']]);
            log_activity('Profilini güncelledi', '', 'user', (int) $user['id']);
            flash('success', 'Bilgileriniz güncellendi.');
        }
    } elseif (input('action') === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password_hash'])) {
            flash('error', 'Mevcut şifreniz hatalı.');
        } elseif (strlen($new) < 8 || $new !== ($_POST['new_password2'] ?? '')) {
            flash('error', 'Yeni şifre en az 8 karakter olmalı ve tekrarı ile eşleşmelidir.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            q('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $user['id']]);
            $_SESSION['upw'] = substr($hash, -12);
            log_activity('Şifresini değiştirdi', '', 'user', (int) $user['id']);
            flash('success', 'Şifreniz değiştirildi.');
        }
    }
    redirect('hesabim.php');
}

$orders = rows('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC', [$user['id']]);
$pageTitle = 'Hesabım';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <h1>Merhaba, <?= e($user['name']) ?></h1>
    <p>Siparişlerinizi ve hesap bilgilerinizi buradan yönetebilirsiniz. <a class="link-light" href="<?= url('cikis.php') ?>">Çıkış yap</a></p>
</div></section>
<section class="section"><div class="container account-grid">
    <div class="card">
        <h2 class="h3">Siparişlerim</h2>
        <?php if (!$orders): ?>
            <p class="muted">Henüz siparişiniz yok. <a href="<?= url('paketler.php') ?>">Paketleri inceleyin</a>.</p>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Paket</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= e($o['order_no']) ?></td>
                        <td><?= tr_date($o['created_at']) ?></td>
                        <td><?= e($o['service_title']) ?><br><small class="muted"><?= e($o['package_name']) ?></small></td>
                        <td><?= money($o['amount']) ?></td>
                        <td><?= status_badge($o['status']) ?></td>
                        <td><?php if (in_array($o['status'], ['pending', 'failed'], true)): ?><a class="btn btn-primary btn-xs" href="<?= url('odeme.php?siparis=' . urlencode($o['order_no'])) ?>">Öde</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
    <div>
        <div class="card">
            <h3>Profil Bilgileri</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <label>Ad Soyad<input name="name" value="<?= e($user['name']) ?>" required></label>
                <label>E-posta<input value="<?= e($user['email']) ?>" disabled></label>
                <label>Telefon<input name="phone" value="<?= e($user['phone']) ?>"></label>
                <button class="btn btn-dark">Kaydet</button>
            </form>
        </div>
        <div class="card mt-1">
            <h3>Şifre Değiştir</h3>
            <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="password">
                <label>Mevcut Şifre<input type="password" name="current_password" required></label>
                <label>Yeni Şifre<input type="password" name="new_password" minlength="8" required></label>
                <label>Yeni Şifre Tekrar<input type="password" name="new_password2" minlength="8" required></label>
                <button class="btn btn-dark">Şifreyi Değiştir</button>
            </form>
        </div>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
