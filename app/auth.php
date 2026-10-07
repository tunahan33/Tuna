<?php
/**
 * Üyelik, giriş ve yetkiler.
 * Bir rolün yetkisini değiştirmek için aşağıdaki PERMISSIONS tablosunu düzenlemeniz yeterli.
 */

const ROLES = [
    'super_admin' => 'Süper Admin',
    'admin'       => 'Admin',
    'editor'      => 'Editör',
    'satis'       => 'Satış Temsilcisi',
    'uye'         => 'Üye',
];

const PERMISSIONS = [
    'panel'            => ['super_admin', 'admin', 'editor', 'satis'],  // Yönetim paneline giriş
    'orders.view'      => ['super_admin', 'admin', 'satis'],            // Siparişleri görme
    'orders.update'    => ['super_admin', 'admin', 'satis'],            // Hazırlanıyor / kargoya verildi / teslim
    'orders.cancel'    => ['super_admin', 'admin'],                     // İptal ve iade
    'orders.assign'    => ['super_admin', 'admin'],                     // Siparişe temsilci atama
    'orders.delete'    => ['super_admin'],                              // Sipariş silme
    'customers.view'   => ['super_admin', 'admin', 'satis'],            // Müşteri listesi
    'requests.view'    => ['super_admin', 'admin', 'satis'],            // Talepler (iletişim, arıza, KVKK, İK)
    'products.text'    => ['super_admin', 'admin', 'editor'],           // Ürün adı, açıklama, özellik metinleri
    'products.manage'  => ['super_admin', 'admin'],                     // Ürün ekleme/silme, fiyat, stok
    'categories'       => ['super_admin', 'admin'],                     // Kategoriler
    'pages'            => ['super_admin', 'admin', 'editor'],           // Kurumsal ve yasal sayfalar
    'users'            => ['super_admin', 'admin'],                     // Kullanıcı ve rol yönetimi
    'reports'          => ['super_admin'],                              // Günlük sipariş & satış raporları
    'activity'         => ['super_admin'],                              // Aktivite akışı (kim ne yaptı)
    'settings'         => ['super_admin'],                              // Mağaza ayarları
];

/** Admin hangi rolleri atayabilir/düzenleyebilir? Süper admin hepsini. */
const MANAGEABLE_ROLES = [
    'super_admin' => ['super_admin', 'admin', 'editor', 'satis', 'uye'],
    'admin'       => ['editor', 'satis', 'uye'],
];

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['uid'] ?? null;
    $cache = $id ? row('SELECT * FROM users WHERE id = ? AND active = 1', [$id]) : null;
    if ($id && !$cache) {
        unset($_SESSION['uid']);
    }
    return $cache;
}

function can(string $perm, ?array $user = null): bool
{
    $user ??= current_user();
    return $user && in_array($user['role'], PERMISSIONS[$perm] ?? [], true);
}

function can_manage_role(string $role, ?array $user = null): bool
{
    $user ??= current_user();
    return $user && in_array($role, MANAGEABLE_ROLES[$user['role']] ?? [], true);
}

function role_label(string $role): string
{
    return ROLES[$role] ?? ($role === 'ziyaretci' ? 'Ziyaretçi' : ($role === 'sistem' ? 'Sistem' : $role));
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('error', 'Devam etmek için giriş yapın.');
        redirect('giris.php');
    }
    return $u;
}

/** Panel sayfalarında kullanılır: yetki yoksa uyarı sayfası gösterir */
function require_perm(string $perm): array
{
    $u = current_user();
    if (!$u || !can('panel', $u)) {
        redirect('admin/giris.php');
    }
    if (!can($perm, $u)) {
        http_response_code(403);
        require ROOT . '/admin/_yetkisiz.php';
        exit;
    }
    return $u;
}

function attempt_login(string $email, string $password): ?array
{
    $key = 'login_fail_' . md5(client_ip());
    $fails = $_SESSION[$key] ?? ['n' => 0, 't' => 0];
    if ($fails['n'] >= 5 && time() - $fails['t'] < 300) {
        flash('error', 'Çok fazla hatalı deneme. 5 dakika sonra tekrar deneyin.');
        return null;
    }
    $u = row('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $_SESSION[$key] = ['n' => $fails['n'] + 1, 't' => time()];
        flash('error', 'E-posta veya şifre hatalı.');
        return null;
    }
    if (!$u['active']) {
        flash('error', 'Hesabınız pasif durumda. Lütfen bizimle iletişime geçin.');
        return null;
    }
    unset($_SESSION[$key]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $u['id']]);
    log_activity('Giriş yaptı', role_label($u['role']), '', $u);
    return $u;
}

function logout_user(): void
{
    if ($u = current_user()) {
        log_activity('Çıkış yaptı', '', '', $u);
    }
    $cart = $_SESSION['cart'] ?? [];
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['cart'] = $cart;
}
