<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

if (is_post()) {
    verify_csrf();
    if (input('action') === 'profile') {
        $name = mb_substr(input('name'), 0, 120);
        if (mb_strlen($name) >= 3) {
            q('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, mb_substr(input('phone'), 0, 30), $user['id']]);
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

$orders = rows("SELECT * FROM orders WHERE user_id = ? AND NOT (status = 'pending' AND contract_accepted_at IS NULL) ORDER BY id DESC", [$user['id']]);
$pageTitle = 'Hesabım';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <h1>Merhaba, <?= e($user['name']) ?></h1>
    <p>Siparişlerini, kargo durumunu ve hesap bilgilerini buradan yönetebilirsin. <a class="link-light" href="<?= url('cikis.php') ?>">Çıkış yap</a></p>
</div></section>
<section class="section-sm"><div class="container account-grid">
    <div>
        <h2 class="h3">Siparişlerim</h2>
        <?php if (!$orders): ?>
            <div class="card"><p class="muted">Henüz siparişin yok. <a href="<?= url('urunler.php') ?>">Ürünleri incele</a>.</p></div>
        <?php endif; ?>
        <?php foreach ($orders as $o): $items = rows('SELECT * FROM order_items WHERE order_id = ?', [$o['id']]); $track = tracking_url($o['cargo_company'], $o['tracking_no']); ?>
            <div class="card order-card">
                <div class="oc-head">
                    <div><strong><?= e($o['order_no']) ?></strong><span class="muted small"><?= tr_date($o['created_at']) ?></span></div>
                    <?= status_badge($o['status']) ?>
                    <strong><?= money($o['amount']) ?></strong>
                </div>
                <?php if (in_array($o['status'], SALE_STATUSES, true)): ?>
                    <ol class="track-steps">
                        <?php $steps = ['paid' => 'Sipariş alındı', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim edildi']; $reached = true;
                        foreach ($steps as $k => $l): ?><li class="<?= $reached ? 'done' : '' ?>"><?= $l ?></li><?php if ($k === $o['status']) $reached = false; endforeach; ?>
                    </ol>
                <?php endif; ?>
                <?php if ($o['tracking_no']): ?>
                    <div class="info-box">📦 <strong><?= e($o['cargo_company']) ?></strong> · Takip No: <strong><?= e($o['tracking_no']) ?></strong> <?= $track ? '· <a href="' . e($track) . '" target="_blank" rel="noopener">Kargom nerede?</a>' : '' ?></div>
                <?php endif; ?>
                <ul class="mini-items"><?php foreach ($items as $it): ?><li><span><?= e($it['product_name']) ?><small><?= e($it['size']) ?> · <?= e($it['color']) ?><?= ($it['print_name'] || $it['print_number']) ? ' · Baskı: ' . e(trim($it['print_name'] . ' ' . $it['print_number'])) : '' ?> · <?= (int) $it['qty'] ?> adet</small></span><strong><?= money($it['line_total']) ?></strong></li><?php endforeach; ?></ul>
                <?php if (in_array($o['status'], ['pending', 'failed'], true)): ?><a class="btn btn-primary btn-sm" href="<?= url('odeme.php?siparis=' . urlencode($o['order_no'])) ?>">Ödemeyi Tamamla</a><?php endif; ?>
                <?php if ($o['status'] === 'delivered'): ?><p class="small muted">İade veya değişim için sipariş numaranla <a href="mailto:<?= e(setting('company_email')) ?>?subject=<?= rawurlencode('İade/Değişim - ' . $o['order_no']) ?>"><?= e(setting('company_email')) ?></a> adresine yazabilirsin.</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
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
