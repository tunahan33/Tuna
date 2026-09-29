<?php
require_once __DIR__ . '/dashboard.php';
require_once APP_ROOT . '/app/garanti.php';

/** Oturumdaki kullanıcının atayabileceği roller */
function assignable_roles(array $me): array
{
    return $me['role'] === 'super_admin' ? ROLES : array_intersect_key(ROLES, array_flip(['editor', 'sales', 'member']));
}

function can_edit_user(array $me, array $target): bool
{
    if ($me['role'] === 'super_admin') return true;
    return $me['role'] === 'admin' && in_array($target['role'], ['editor', 'sales', 'member'], true);
}

function admin_customers(): void
{
    require_perm('customers.view');
    $q = trim($_GET['q'] ?? '');
    $params = [];
    $having = '';
    if ($q !== '') { $having = 'WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?'; $params = ["%$q%", "%$q%", "%$q%"]; }
    $rev = revenue_in();
    $sql = "SELECT * FROM (
              SELECT lower(o.email) email, MAX(o.customer_name) name, MAX(o.phone) phone, COUNT(*) orders,
                     COALESCE(SUM(CASE WHEN o.status IN ($rev) THEN o.amount END),0) spent, MAX(o.created_at) last_order,
                     (SELECT COUNT(*) FROM users u WHERE lower(u.email) = lower(o.email)) is_member
              FROM orders o GROUP BY lower(o.email)
              UNION ALL
              SELECT lower(u.email), u.name, u.phone, 0, 0, NULL, 1 FROM users u
              WHERE u.role = 'member' AND NOT EXISTS (SELECT 1 FROM orders o WHERE lower(o.email) = lower(u.email))
            ) $having ORDER BY last_order DESC NULLS LAST LIMIT 500";
    $list = rows($sql, $params);
    admin_render('Müşteriler', function () use ($list, $q) { ?>
<section class="card">
  <form class="toolbar" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="İsim, e-posta, telefon ara…"><button class="btn btn-dark">Ara</button></form>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Müşteri</th><th>Telefon</th><th class="r">Sipariş</th><th class="r">Toplam harcama</th><th>Son sipariş</th><th>Üyelik</th></tr></thead>
    <tbody><?php foreach ($list as $c): ?>
      <tr class="clickable" data-href="<?= url('admin/siparisler?q=' . urlencode($c['email'])) ?>">
        <td><strong><?= e($c['name']) ?></strong><br><small class="muted"><?= e($c['email']) ?></small></td>
        <td><?= e($c['phone'] ?: '—') ?></td>
        <td class="r"><?= (int)$c['orders'] ?></td>
        <td class="r"><strong><?= money((int)$c['spent']) ?></strong></td>
        <td><?= tr_date($c['last_order']) ?></td>
        <td><?= $c['is_member'] ? '<span class="badge badge-blue">Üye</span>' : '<span class="badge badge-gray">Misafir</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="6" class="empty-line">Kayıt yok.</td></tr><?php endif; ?></tbody>
  </table></div>
</section>
<?php
    });
}

function admin_users(): void
{
    $me = require_perm('users.manage');
    $role = $_GET['rol'] ?? '';
    $where = '1=1'; $params = [];
    if (isset(ROLES[$role])) { $where = 'role = ?'; $params[] = $role; }
    $list = rows("SELECT * FROM users WHERE $where ORDER BY CASE role WHEN 'super_admin' THEN 0 WHEN 'admin' THEN 1 WHEN 'editor' THEN 2 WHEN 'sales' THEN 3 ELSE 4 END, name LIMIT 500", $params);
    $counts = [];
    foreach (rows('SELECT role, COUNT(*) c FROM users GROUP BY role') as $r) $counts[$r['role']] = (int)$r['c'];

    admin_render('Kullanıcılar & Roller', function () use ($list, $role, $counts, $me) { ?>
<div class="page-actions"><a class="btn btn-primary" href="<?= url('admin/kullanici/yeni') ?>">+ Yeni kullanıcı</a></div>
<div class="chips">
  <a href="?" class="chip <?= $role === '' ? 'on' : '' ?>">Tümü <em><?= array_sum($counts) ?></em></a>
  <?php foreach (ROLES as $k => $l): ?><a href="?rol=<?= $k ?>" class="chip <?= $role === $k ? 'on' : '' ?>"><?= e($l) ?> <em><?= $counts[$k] ?? 0 ?></em></a><?php endforeach; ?>
</div>
<section class="card"><div class="table-wrap"><table class="table">
  <thead><tr><th>Kullanıcı</th><th>Rol</th><th>Kayıt</th><th>Son giriş</th><th>Durum</th><th></th></tr></thead>
  <tbody><?php foreach ($list as $x): ?>
    <tr>
      <td><strong><?= e($x['name']) ?></strong><br><small class="muted"><?= e($x['email']) ?></small></td>
      <td><?= role_badge($x['role']) ?></td>
      <td><?= tr_date($x['created_at'], false) ?></td>
      <td><?= tr_date($x['last_login_at']) ?></td>
      <td><?= $x['is_active'] ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
      <td class="r"><?php if (can_edit_user($me, $x)): ?><a class="btn btn-sm btn-ghost" href="<?= url('admin/kullanici/' . $x['id']) ?>">Düzenle</a><?php else: ?><span class="small muted">🔒</span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?></tbody>
</table></div></section>
<?php
    }, ['subtitle' => $me['role'] === 'admin' ? 'Admin olarak Editör, Satış Temsilcisi ve Üye hesaplarını yönetebilirsiniz' : 'Tüm rolleri atayabilirsiniz']);
}

function admin_user_edit(string $id): void
{
    $me = require_perm('users.manage');
    $isNew = $id === 'yeni';
    $x = $isNew ? ['id' => 0, 'name' => '', 'email' => '', 'phone' => '', 'role' => 'sales', 'is_active' => 1] : row('SELECT * FROM users WHERE id = ?', [(int)$id]);
    if (!$x || (!$isNew && !can_edit_user($me, $x))) { flash('error', 'Bu kullanıcıyı düzenleme yetkiniz yok.'); redirect('admin/kullanicilar'); }
    $roles = assignable_roles($me);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $name = post('name'); $email = post('email'); $role = post('role'); $pw = (string)($_POST['password'] ?? '');
        $active = (int)!empty($_POST['is_active']);
        $err = null;
        if (mb_strlen($name) < 3) $err = 'Ad soyad gerekli.';
        elseif (!valid_email($email)) $err = 'Geçerli e-posta girin.';
        elseif (val('SELECT 1 FROM users WHERE email = ? AND id != ?', [$email, $x['id']])) $err = 'Bu e-posta başka bir hesapta kayıtlı.';
        elseif (!isset($roles[$role])) $err = 'Bu rolü atama yetkiniz yok.';
        elseif ($isNew && strlen($pw) < 8) $err = 'Şifre en az 8 karakter olmalı.';
        elseif ($pw !== '' && strlen($pw) < 8) $err = 'Şifre en az 8 karakter olmalı.';
        elseif (!$isNew && (int)$x['id'] === (int)$me['id'] && ($role !== $me['role'] || !$active)) $err = 'Kendi rolünüzü değiştiremez veya hesabınızı pasifleştiremezsiniz.';
        elseif (!$isNew && $x['role'] === 'super_admin' && ($role !== 'super_admin' || !$active) && (int)val("SELECT COUNT(*) FROM users WHERE role='super_admin' AND is_active=1") <= 1) $err = 'Sistemde en az bir aktif Süper Admin kalmalıdır.';
        if ($err) { flash('error', $err); redirect('admin/kullanici/' . $id); }

        if ($isNew) {
            q('INSERT INTO users(name, email, phone, password_hash, role, is_active, created_at) VALUES(?,?,?,?,?,?,?)',
                [$name, $email, post('phone'), password_hash($pw, PASSWORD_DEFAULT), $role, $active, now()]);
            $newId = db()->lastInsertId();
            log_activity('user_created', $name . ' <' . $email . '> — ' . ROLES[$role]);
            flash('success', 'Kullanıcı oluşturuldu.');
            redirect('admin/kullanici/' . $newId);
        }
        q('UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ? WHERE id = ?', [$name, $email, post('phone'), $role, $active, $x['id']]);
        if ($pw !== '') q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $x['id']]);
        if ($role !== $x['role']) log_activity('role_changed', $name . ': ' . ROLES[$x['role']] . ' → ' . ROLES[$role]);
        log_activity('user_updated', $name . ($pw !== '' ? ' (şifre sıfırlandı)' : '') . ($active != $x['is_active'] ? ($active ? ' (aktifleştirildi)' : ' (pasifleştirildi)') : ''));
        flash('success', 'Kullanıcı güncellendi.');
        redirect('admin/kullanici/' . $x['id']);
    }
    $acts = !$isNew && can('activity.view') ? rows('SELECT * FROM activity_log WHERE user_id = ? ORDER BY id DESC LIMIT 20', [$x['id']]) : [];

    admin_render($isNew ? 'Yeni kullanıcı' : $x['name'], function () use ($x, $isNew, $roles, $acts) { ?>
<div class="page-actions"><a class="btn btn-ghost" href="<?= url('admin/kullanicilar') ?>">← Kullanıcılar</a></div>
<div class="grid-main">
<form method="post" class="card form" autocomplete="off">
  <?= csrf_field() ?>
  <div class="grid-2">
    <label>Ad Soyad<input name="name" required value="<?= e($x['name']) ?>"></label>
    <label>E-posta<input type="email" name="email" required value="<?= e($x['email']) ?>"></label>
    <label>Telefon<input name="phone" value="<?= e($x['phone']) ?>"></label>
    <label><?= $isNew ? 'Şifre' : 'Yeni şifre <small>(değiştirmek için doldurun)</small>' ?><input type="password" name="password" <?= $isNew ? 'required' : '' ?> minlength="8" autocomplete="new-password"></label>
  </div>
  <fieldset><legend>Rol</legend>
    <div class="role-picker">
      <?php foreach ($roles as $k => $l): ?>
        <label class="role-option"><input type="radio" name="role" value="<?= $k ?>" <?= $x['role'] === $k ? 'checked' : '' ?>>
          <span><?= role_badge($k) ?><small><?= e(ROLE_DESCRIPTIONS[$k]) ?></small></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <label class="check"><input type="checkbox" name="is_active" value="1" <?= $x['is_active'] ? 'checked' : '' ?>> <span>Hesap aktif</span></label>
  <button class="btn btn-primary">Kaydet</button>
</form>
<?php if ($acts): ?>
<section class="card"><div class="card-head"><h3>Bu kullanıcının son işlemleri</h3><a class="link" href="<?= url('admin/aktivite?kullanici=' . $x['id']) ?>">Tümü →</a></div>
  <ul class="activity compact"><?php foreach ($acts as $a) echo activity_item($a); ?></ul></section>
<?php endif; ?>
</div>
<?php
    });
}

function admin_roles(): void
{
    require_perm('dashboard');
    admin_render('Yetki Tablosu', function () { ?>
<div class="role-cards">
  <?php foreach (STAFF_ROLES as $r): ?><div class="card role-card"><?= role_badge($r) ?><p><?= e(ROLE_DESCRIPTIONS[$r]) ?></p></div><?php endforeach; ?>
</div>
<section class="card"><div class="table-wrap"><table class="table matrix">
  <thead><tr><th>Yetki</th><?php foreach (STAFF_ROLES as $r): ?><th class="c"><?= role_badge($r) ?></th><?php endforeach; ?></tr></thead>
  <tbody><?php foreach (PERMISSION_LABELS as $perm => $label): ?>
    <tr><td><?= e($label) ?></td><?php foreach (STAFF_ROLES as $r): ?><td class="c"><?= in_array($r, PERMISSIONS[$perm], true) ? '<span class="yes">✓</span>' : '<span class="no">—</span>' ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?></tbody>
</table></div></section>
<?php
    }, ['subtitle' => 'Her rolün yetkisi birbirinden farklıdır']);
}

function admin_profile(): void
{
    $u = require_perm('dashboard');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $pw = (string)($_POST['password'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) flash('error', 'Mevcut şifre hatalı.');
        elseif (strlen($pw) < ($u['role'] === 'super_admin' ? 10 : 8)) flash('error', 'Yeni şifre çok kısa.');
        elseif ($pw !== ($_POST['password2'] ?? '')) flash('error', 'Şifreler eşleşmiyor.');
        else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
            log_activity('password_changed', '', $u);
            flash('success', 'Şifreniz değiştirildi.');
        }
        redirect('admin/profil');
    }
    admin_render('Profilim', function () use ($u) { ?>
<div class="grid-main">
  <section class="card"><div class="card-head"><h3><?= e($u['name']) ?></h3><?= role_badge($u['role']) ?></div>
    <div class="detail-grid"><div><span>E-posta</span><strong><?= e($u['email']) ?></strong></div><div><span>Son giriş</span><strong><?= tr_date($u['last_login_at']) ?></strong></div></div>
    <p class="small muted"><?= e(ROLE_DESCRIPTIONS[$u['role']]) ?></p></section>
  <form method="post" class="card form"><div class="card-head"><h3>Şifre değiştir</h3></div>
    <?= csrf_field() ?>
    <label>Mevcut şifre<input type="password" name="current" required autocomplete="current-password"></label>
    <label>Yeni şifre<input type="password" name="password" required autocomplete="new-password"></label>
    <label>Yeni şifre (tekrar)<input type="password" name="password2" required autocomplete="new-password"></label>
    <button class="btn btn-primary">Değiştir</button>
  </form>
</div>
<?php
    });
}

const SETTING_GROUPS = [
    'Şirket bilgileri (footer, sözleşmeler ve faturalarda kullanılır)' => [
        'site_name' => 'Site adı', 'site_slogan' => 'Slogan', 'company_title' => 'Ticari unvan', 'company_address' => 'Adres',
        'company_phone' => 'Telefon', 'company_email' => 'E-posta', 'company_kep' => 'KEP adresi', 'tax_office' => 'Vergi dairesi',
        'tax_number' => 'Vergi numarası', 'mersis_number' => 'MERSİS numarası', 'trade_registry' => 'Ticaret sicil', 'working_hours' => 'Çalışma saatleri',
    ],
];

function admin_settings(): void
{
    require_perm('settings.manage');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $changed = [];
        foreach (SETTING_GROUPS as $fields) foreach ($fields as $k => $label) {
            if (isset($_POST[$k]) && post($k) !== setting($k)) { save_setting($k, post($k)); $changed[] = $label; }
        }
        $posFields = ['pos_mode' => 'POS modu', 'pos_security' => '3D modeli', 'pos_terminal_id' => 'Terminal ID', 'pos_merchant_id' => 'Üye işyeri no',
                      'pos_user_id' => 'Terminal kullanıcı', 'pos_prov_user' => 'Provizyon kullanıcı'];
        foreach ($posFields as $k => $label) {
            if (isset($_POST[$k]) && post($k) !== setting($k)) { save_setting($k, post($k)); $changed[] = $label; }
        }
        // Gizli alanlar: boş bırakılırsa mevcut değer korunur
        foreach (['pos_prov_password' => 'Provizyon şifresi', 'pos_store_key' => '3D Store Key'] as $k => $label) {
            if (post($k) !== '') { save_setting($k, post($k)); $changed[] = $label; }
        }
        if (!in_array(setting('pos_mode'), ['demo', 'test', 'prod'], true)) save_setting('pos_mode', 'demo');
        if ($changed) log_activity('settings_saved', implode(', ', $changed));
        flash('success', $changed ? 'Ayarlar kaydedildi.' : 'Değişiklik yapılmadı.');
        redirect('admin/ayarlar');
    }
    admin_render('Site & Sanal POS Ayarları', function () { ?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <?php foreach (SETTING_GROUPS as $group => $fields): ?>
  <section class="card"><div class="card-head"><h3><?= e($group) ?></h3></div>
    <div class="grid-2"><?php foreach ($fields as $k => $label): ?><label><?= e($label) ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"></label><?php endforeach; ?></div>
  </section>
  <?php endforeach; ?>
  <section class="card">
    <div class="card-head"><h3>Garanti BBVA Sanal POS</h3>
      <?= pos_mode() === 'prod' ? '<span class="badge badge-green">CANLI</span>' : (pos_mode() === 'test' ? '<span class="badge badge-blue">TEST</span>' : '<span class="badge badge-yellow">DEMO</span>') ?></div>
    <div class="alert alert-info">Bilgiler, Garanti BBVA sanal POS başvurunuz onaylandığında banka tarafından e-posta ile iletilir (Terminal ID, Üye İşyeri No, PROVAUT şifresi, 3D Secure Store Key). Önce <strong>Test</strong> modunda deneyin, sonra <strong>Canlı</strong>'ya alın.<br>Bankaya bildirmeniz gereken dönüş adresi (başarılı / hatalı): <code><?= e(abs_url('odeme/sonuc')) ?></code></div>
    <div class="grid-2">
      <label>Çalışma modu<select name="pos_mode">
        <option value="demo" <?= pos_mode() === 'demo' ? 'selected' : '' ?>>Demo — banka bağlantısı yok (test amaçlı onay ekranı)</option>
        <option value="test" <?= pos_mode() === 'test' ? 'selected' : '' ?>>Test — Garanti test ortamı</option>
        <option value="prod" <?= pos_mode() === 'prod' ? 'selected' : '' ?>>Canlı — gerçek tahsilat</option>
      </select></label>
      <label>3D modeli<select name="pos_security">
        <option value="3D_OOS_PAY" <?= setting('pos_security') !== '3D_PAY' ? 'selected' : '' ?>>3D_OOS_PAY — Bankanın ortak ödeme sayfası (önerilen)</option>
        <option value="3D_PAY" <?= setting('pos_security') === '3D_PAY' ? 'selected' : '' ?>>3D_PAY — Kart formu sitede</option>
      </select></label>
      <label>Terminal ID<input name="pos_terminal_id" value="<?= e(setting('pos_terminal_id')) ?>" placeholder="3XXXXXXX"></label>
      <label>Üye işyeri (Merchant) ID<input name="pos_merchant_id" value="<?= e(setting('pos_merchant_id')) ?>"></label>
      <label>Terminal kullanıcı ID<input name="pos_user_id" value="<?= e(setting('pos_user_id')) ?>"></label>
      <label>Provizyon kullanıcı<input name="pos_prov_user" value="<?= e(setting('pos_prov_user')) ?>"></label>
      <label>Provizyon (PROVAUT) şifresi<input type="password" name="pos_prov_password" placeholder="<?= setting('pos_prov_password') ? '•••••••• (kayıtlı — değiştirmek için yazın)' : 'Girilmedi' ?>" autocomplete="new-password"></label>
      <label>3D Secure Store Key<input type="password" name="pos_store_key" placeholder="<?= setting('pos_store_key') ? '•••••••• (kayıtlı — değiştirmek için yazın)' : 'Girilmedi' ?>" autocomplete="new-password"></label>
    </div>
    <p class="small muted">Durum: <?= garanti_configured() ? '✓ Tüm banka bilgileri girilmiş.' : '⚠ Banka bilgileri eksik; Test/Canlı modda ödeme sayfası çalışmaz.' ?></p>
  </section>
  <div class="sticky-save"><button class="btn btn-primary btn-lg">Tüm ayarları kaydet</button></div>
</form>
<?php
    }, ['subtitle' => 'Yalnızca Süper Admin']);
}
