<?php

function auth_card(string $title, callable $body): void
{
    render($title, function () use ($title, $body) { ?>
<section class="section auth-section"><div class="container">
  <div class="auth-card card">
    <div class="auth-logo"><?= logo_svg('logo-mark', false) ?></div>
    <h1><?= e($title) ?></h1>
    <?php $body(); ?>
  </div>
</div></section>
<?php
    });
}

function handle_login(bool $staffOnly): array
{
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $errors;
    csrf_check();
    if (too_many_attempts()) return ['Çok fazla hatalı deneme yapıldı. Lütfen 15 dakika sonra tekrar deneyin.'];
    $u = row('SELECT * FROM users WHERE email = ?', [post('email')]);
    if (!$u || !password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        log_activity('login_failed', post('email'), ['id' => null, 'name' => 'Ziyaretçi', 'role' => 'guest']);
        return ['E-posta veya şifre hatalı.'];
    }
    if (!$u['is_active']) return ['Hesabınız pasif durumda. Lütfen bizimle iletişime geçin.'];
    if ($staffOnly && !is_staff($u)) return ['Bu hesabın yönetim paneline erişim yetkisi yok.'];
    login_user($u);
    log_activity('login', $staffOnly ? 'Yönetim paneli' : 'Site', $u);
    $to = $_SESSION['after_login'] ?? null;
    unset($_SESSION['after_login']);
    if ($to && str_starts_with($to, '/') && !str_starts_with($to, '//')) { header('Location: ' . $to); exit; }
    redirect(is_staff($u) ? 'admin' : 'hesabim');
}

function page_login(): void
{
    if (current_user()) redirect(is_staff() ? 'admin' : 'hesabim');
    $errors = handle_login(false);
    auth_card('Giriş Yap', function () use ($errors) { ?>
    <p class="muted center">Hesabınıza giriş yapın.</p>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>E-posta<input type="email" name="email" required autofocus value="<?= old('email') ?>" autocomplete="username"></label>
      <label>Şifre<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-primary btn-block btn-lg">Giriş yap</button>
    </form>
    <p class="center small">Hesabınız yok mu? <a href="<?= url('kayit') ?>">Üye olun</a></p>
<?php
    });
}

function page_register(): void
{
    if (current_user()) redirect('hesabim');
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $name = post('name'); $email = post('email'); $phone = post('phone'); $pw = (string)($_POST['password'] ?? '');
        if (mb_strlen($name) < 5 || !str_contains($name, ' ')) $errors[] = 'Adınızı ve soyadınızı girin.';
        if (!valid_email($email)) $errors[] = 'Geçerli bir e-posta girin.';
        elseif (val('SELECT 1 FROM users WHERE email = ?', [$email])) $errors[] = 'Bu e-posta ile kayıtlı bir hesap var.';
        if (strlen($pw) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        if ($pw !== ($_POST['password2'] ?? '')) $errors[] = 'Şifreler eşleşmiyor.';
        if (empty($_POST['agree'])) $errors[] = 'Üyelik sözleşmesini onaylamanız gerekir.';
        if (empty($_POST['kvkk'])) $errors[] = 'Aydınlatma metnini onaylamanız gerekir.';
        if (!$errors) {
            q('INSERT INTO users(name, email, phone, password_hash, role, created_at) VALUES(?,?,?,?,?,?)',
                [$name, $email, $phone, password_hash($pw, PASSWORD_DEFAULT), 'member', now()]);
            $u = row('SELECT * FROM users WHERE id = ?', [db()->lastInsertId()]);
            login_user($u);
            log_activity('register', $email . (!empty($_POST['marketing']) ? ' (ticari ileti onayı verdi)' : ''), $u);
            flash('success', 'Hoş geldiniz! Üyeliğiniz oluşturuldu.');
            redirect('hesabim');
        }
    }
    auth_card('Üye Ol', function () use ($errors) { ?>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Ad Soyad<input name="name" required value="<?= old('name') ?>" autocomplete="name"></label>
      <label>E-posta<input type="email" name="email" required value="<?= old('email') ?>" autocomplete="email"></label>
      <label>Telefon<input type="tel" name="phone" value="<?= old('phone') ?>" autocomplete="tel"></label>
      <div class="grid-2">
        <label>Şifre<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label>Şifre (tekrar)<input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
      </div>
      <label class="check"><input type="checkbox" name="agree" value="1" required> <span><a href="<?= url('uyelik-sozlesmesi') ?>" target="_blank">Üyelik sözleşmesi</a>'ni okudum ve kabul ediyorum.</span></label>
      <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
      <label class="check"><input type="checkbox" name="marketing" value="1"> <span>Kampanya ve bilgilendirme iletileri almak istiyorum. (İsteğe bağlı)</span></label>
      <button class="btn btn-primary btn-block btn-lg">Üye ol</button>
    </form>
    <p class="center small">Zaten üye misiniz? <a href="<?= url('giris') ?>">Giriş yapın</a></p>
<?php
    });
}

function page_logout(): void
{
    if ($u = current_user()) log_activity('logout', '', $u);
    $_SESSION = [];
    session_regenerate_id(true);
    redirect('');
}

function page_account(): void
{
    $u = require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $pw = (string)($_POST['password'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) flash('error', 'Mevcut şifre hatalı.');
        elseif (strlen($pw) < 8) flash('error', 'Yeni şifre en az 8 karakter olmalı.');
        else {
            q('UPDATE users SET password_hash = ?, phone = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), post('phone'), $u['id']]);
            log_activity('password_changed', '', $u);
            flash('success', 'Bilgileriniz güncellendi.');
        }
        redirect('hesabim');
    }
    $orders = rows('SELECT * FROM orders WHERE user_id = ? OR lower(email) = lower(?) ORDER BY id DESC', [$u['id'], $u['email']]);
    render('Hesabım', function () use ($u, $orders) { ?>
<section class="page-hero slim"><div class="container"><h1>Merhaba, <?= e(explode(' ', $u['name'])[0]) ?></h1><p><?= e($u['email']) ?></p></div></section>
<section class="section"><div class="container account-grid">
  <div class="card">
    <h2>Siparişlerim</h2>
    <?php if (!$orders): ?>
      <p class="muted">Henüz siparişiniz yok. <a href="<?= url('hizmetler') ?>">Danışmanlık paketlerini inceleyin.</a></p>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Sipariş No</th><th>Hizmet</th><th>Tarih</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
        <tbody><?php foreach ($orders as $o): ?>
          <tr><td><strong><?= e($o['order_no']) ?></strong></td><td><?= e($o['service_title']) ?><br><small class="muted"><?= e($o['package_name']) ?></small></td>
          <td><?= tr_date($o['created_at']) ?></td><td><?= money((int)$o['amount']) ?></td><td><?= status_badge($o['status']) ?></td>
          <td><?php if (in_array($o['status'], ['pending', 'failed'], true)): ?><a class="btn btn-primary btn-sm" href="<?= url('odeme/banka/' . $o['order_no']) ?>">Öde</a><?php else: ?><a class="btn btn-ghost btn-sm" href="<?= url('ariza-takibi?order=' . $o['order_no']) ?>">Destek</a><?php endif; ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </div>
  <form method="post" class="card form">
    <h3>Hesap bilgileri</h3>
    <?= csrf_field() ?>
    <label>Telefon<input name="phone" value="<?= e($u['phone']) ?>"></label>
    <label>Mevcut şifre<input type="password" name="current" required autocomplete="current-password"></label>
    <label>Yeni şifre<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-dark">Güncelle</button>
    <a class="btn btn-ghost" href="<?= url('cikis') ?>">Çıkış yap</a>
  </form>
</div></section>
<?php
    });
}

/** İlk kurulum: Süper Admin hesabını oluşturur (yalnızca hiç süper admin yokken çalışır). */
function page_install(): void
{
    if (val("SELECT 1 FROM users WHERE role = 'super_admin' LIMIT 1")) redirect('admin');
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $name = post('name'); $email = post('email'); $pw = (string)($_POST['password'] ?? '');
        if (mb_strlen($name) < 3) $errors[] = 'Ad soyad girin.';
        if (!valid_email($email)) $errors[] = 'Geçerli bir e-posta girin.';
        if (strlen($pw) < 10) $errors[] = 'Süper Admin şifresi en az 10 karakter olmalıdır.';
        if ($pw !== ($_POST['password2'] ?? '')) $errors[] = 'Şifreler eşleşmiyor.';
        if (!$errors) {
            q('INSERT INTO users(name, email, password_hash, role, created_at) VALUES(?,?,?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT), 'super_admin', now()]);
            $u = row('SELECT * FROM users WHERE email = ?', [$email]);
            login_user($u);
            log_activity('install', 'Süper Admin hesabı oluşturuldu', $u);
            flash('success', 'Kurulum tamamlandı. Şimdi Ayarlar bölümünden şirket ve Sanal POS bilgilerinizi girin.');
            redirect('admin/ayarlar');
        }
    }
    auth_card('Kurulum — Süper Admin', function () use ($errors) { ?>
    <p class="muted center">Hoş geldiniz! Sitenin en yetkili hesabı olan <strong>Süper Admin</strong> hesabını oluşturun. Bu ekran yalnızca bir kez görünür.</p>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Ad Soyad<input name="name" required value="<?= old('name') ?>"></label>
      <label>E-posta<input type="email" name="email" required value="<?= old('email') ?>"></label>
      <div class="grid-2">
        <label>Şifre (min. 10)<input type="password" name="password" required minlength="10"></label>
        <label>Şifre (tekrar)<input type="password" name="password2" required minlength="10"></label>
      </div>
      <button class="btn btn-primary btn-block btn-lg">Kurulumu tamamla</button>
    </form>
<?php
    });
}
