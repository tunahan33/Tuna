<?php
/** Kimlik doğrulama ve yetki (rol) yönetimi */

const ROLES = [
    'super_admin' => 'Süper Admin',
    'admin'       => 'Admin',
    'editor'      => 'Editör',
    'sales'       => 'Satış Temsilcisi',
    'member'      => 'Üye',
];

/**
 * Yetki matrisi: her yetki için izinli roller.
 * En yetkili rol süper admindir; günlük sipariş raporları, satış raporları,
 * aktivite akışı ve sistem ayarları yalnızca süper admine açıktır.
 */
const PERMISSIONS = [
    'panel.access'     => ['super_admin', 'admin', 'editor', 'sales'],
    'dashboard.stats'  => ['super_admin', 'admin', 'sales'],
    'orders.view'      => ['super_admin', 'admin', 'sales'],
    'orders.status'    => ['super_admin', 'admin', 'sales'],
    'orders.cancel'    => ['super_admin', 'admin'],
    'orders.delete'    => ['super_admin'],
    'customers.view'   => ['super_admin', 'admin', 'sales'],
    'messages.view'    => ['super_admin', 'admin', 'sales'],
    'content.edit'     => ['super_admin', 'admin', 'editor'],
    'content.create'   => ['super_admin', 'admin'],
    'content.delete'   => ['super_admin', 'admin'],
    'prices.edit'      => ['super_admin', 'admin'],
    'pages.edit'       => ['super_admin', 'admin', 'editor'],
    'users.view'       => ['super_admin', 'admin'],
    'users.manage'     => ['super_admin', 'admin'],
    'reports.view'     => ['super_admin'],
    'activity.view'    => ['super_admin'],
    'settings.edit'    => ['super_admin'],
];

/** Hangi rol hangi rolleri yönetebilir */
const MANAGEABLE_ROLES = [
    'super_admin' => ['super_admin', 'admin', 'editor', 'sales', 'member'],
    'admin'       => ['editor', 'sales', 'member'],
];

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $u = row('SELECT * FROM users WHERE id = ? AND status = ?', [$_SESSION['uid'], 'active']);
        if ($u && hash_equals($_SESSION['upw'] ?? '', substr($u['password_hash'], -12))) {
            $user = $u;
        } else {
            unset($_SESSION['uid'], $_SESSION['upw']);
        }
    }
    return $user;
}

function role_label(string $role): string
{
    return ROLES[$role] ?? $role;
}

function can(string $perm, ?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && in_array($user['role'], PERMISSIONS[$perm] ?? [], true);
}

function can_manage_role(string $targetRole, ?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && in_array($targetRole, MANAGEABLE_ROLES[$user['role']] ?? [], true);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('info', 'Devam etmek için lütfen giriş yapın.');
        redirect('giris.php');
    }
    return $u;
}

function require_perm(string $perm): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('admin/login.php');
    }
    if (!can($perm, $u)) {
        http_response_code(403);
        log_activity('Yetkisiz erişim denemesi', ($_SERVER['REQUEST_URI'] ?? '') . ' (' . $perm . ')');
        require ROOT . '/admin/_forbidden.php';
        exit;
    }
    return $u;
}

function attempt_login(string $email, string $password): ?array
{
    // Basit kaba kuvvet koruması: 15 dakikada aynı IP'den 8 hatalı deneme
    $since = date('Y-m-d H:i:s', time() - 900);
    $fails = (int) val("SELECT COUNT(*) FROM activity_log WHERE action = 'Hatalı giriş denemesi' AND ip = ? AND created_at >= ?", [client_ip(), $since]);
    if ($fails >= 8) {
        flash('error', 'Çok fazla hatalı deneme. Lütfen 15 dakika sonra tekrar deneyin.');
        return null;
    }
    $u = row('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        log_activity('Hatalı giriş denemesi', 'E-posta: ' . $email);
        flash('error', 'E-posta veya şifre hatalı.');
        return null;
    }
    if ($u['status'] !== 'active') {
        flash('error', 'Hesabınız pasif durumdadır. Lütfen bizimle iletişime geçin.');
        return null;
    }
    login_user($u);
    return $u;
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    $_SESSION['upw'] = substr($u['password_hash'], -12);
    q('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $u['id']]);
    log_activity('Giriş yaptı', role_label($u['role']) . ' girişi', 'user', (int) $u['id'], $u);
}

function logout_user(): void
{
    if ($u = current_user()) {
        log_activity('Çıkış yaptı', '', 'user', (int) $u['id']);
    }
    $_SESSION = [];
    session_regenerate_id(true);
}
