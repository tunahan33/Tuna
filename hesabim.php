<?php
require __DIR__ . '/app/bootstrap.php';
$u = require_login();

if (is_post()) {
    verify_csrf();
    if (input('action') === 'profile') {
        $name = (string) input('name');
        if (mb_strlen($name) >= 3) {
            q('UPDATE users SET name = ?, phone = ? WHERE id = ?', [mb_substr($name, 0, 120), mb_substr((string) input('phone'), 0, 30), $u['id']]);
            log_activity('Profilini güncelledi');
            flash('success', 'Bilgileriniz güncellendi.');
        }
    } elseif (input('action') === 'password') {
        $new = (string) input('new');
        if (!password_verify((string) input('current'), $u['password_hash'])) {
            flash('error', 'Mevcut şifreniz hatalı.');
        } elseif (strlen($new) < 8 || !preg_match('/\d/', $new)) {
            flash('error', 'Yeni şifre en az 8 karakter olmalı ve rakam içermelidir.');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            log_activity('Şifresini değiştirdi');
            flash('success', 'Şifreniz değiştirildi.');
        }
    }
    redirect('hesabim.php');
}
$orders = rows('SELECT * FROM orders WHERE user_id = ? OR lower(email) = ? ORDER BY id DESC', [$u['id'], mb_strtolower($u['email'])]);
$title = 'Hesabım';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><h1>Merhaba, <?= e($u['name']) ?></h1><p><?= e(role_label($u['role'])) ?> · <?= e($u['email']) ?></p></div></section>
<section class="section-sm">
    <div class="container two-col">
        <div>
            <h2>Siparişlerim</h2>
            <?php if (!$orders): ?><div class="card empty"><p>Henüz siparişiniz yok.</p><a class="btn btn-primary" href="<?= url('urunler.php') ?>">Alışverişe Başla</a></div><?php endif; ?>
            <?php foreach ($orders as $o): ?><div class="card" style="margin-bottom:16px"><?= order_track_html($o) ?></div><?php endforeach; ?>
        </div>
        <aside>
            <?php if (can('panel', $u)): ?><a class="btn btn-dark btn-block" href="<?= url('admin/') ?>" style="margin-bottom:16px">Yönetim Paneline Git</a><?php endif; ?>
            <form method="post" class="card form" style="margin-bottom:16px">
                <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <h3>Bilgilerim</h3>
                <label>Ad Soyad<input name="name" value="<?= e($u['name']) ?>" required></label>
                <label>Telefon<input name="phone" value="<?= e($u['phone']) ?>"></label>
                <button class="btn btn-dark">Kaydet</button>
            </form>
            <form method="post" class="card form" style="margin-bottom:16px">
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <h3>Şifre Değiştir</h3>
                <label>Mevcut şifre<input type="password" name="current" required autocomplete="current-password"></label>
                <label>Yeni şifre<input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
                <button class="btn btn-dark">Şifreyi Değiştir</button>
            </form>
            <a class="btn btn-ghost btn-block" href="<?= url('cikis.php') ?>">Çıkış Yap</a>
        </aside>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
