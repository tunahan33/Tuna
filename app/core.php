<?php
require __DIR__ . '/config.php';

/* ------------------------------------------------------------------ */
/*  Oturum                                                             */
/* ------------------------------------------------------------------ */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('IS_HTTPS', $isHttps);

session_name('gsp_sid');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => IS_HTTPS,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

/* ------------------------------------------------------------------ */
/*  Veritabanı                                                         */
/* ------------------------------------------------------------------ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0775, true);
    $fresh = !file_exists(DB_FILE);
    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
    if ($fresh || !$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'")->fetch()) {
        require_once __DIR__ . '/schema.php';
        install_schema($pdo);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/* ------------------------------------------------------------------ */
/*  Ayarlar                                                            */
/* ------------------------------------------------------------------ */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (rows('SELECT key, value FROM settings') as $r) $cache[$r['key']] = $r['value'];
    }
    return $cache[$key] ?? (DEFAULT_SETTINGS[$key] ?? $default);
}

function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value', [$key, $value]);
}

/* ------------------------------------------------------------------ */
/*  Yardımcılar                                                        */
/* ------------------------------------------------------------------ */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    static $b = null;
    if ($b === null) {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return $b;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (IS_HTTPS ? 'https://' : 'http://') . $host . url($path);
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $v = file_exists($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function money(int $kurus): string
{
    return number_format($kurus / 100, 2, ',', '.') . ' ₺';
}

function money_short(int $kurus): string
{
    return number_format($kurus / 100, 0, ',', '.') . ' ₺';
}

function tr_date(?string $dt, bool $time = true): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $time ? date('d.m.Y H:i', $ts) : date('d.m.Y', $ts);
}

function tr_day_name(string $date): string
{
    $days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    return $days[(int)date('w', strtotime($date))];
}

function tr_month_name(int $m): string
{
    return ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$m];
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function slugify(string $s): string
{
    $s = mb_strtolower(strtr($s, ['İ' => 'i', 'I' => 'ı']), 'UTF-8');
    $s = strtr($s, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function valid_email(string $e): bool
{
    return (bool)filter_var($e, FILTER_VALIDATE_EMAIL);
}

function random_code(string $prefix, int $digits = 6): string
{
    $n = '';
    for ($i = 0; $i < $digits; $i++) $n .= random_int(0, 9);
    return $prefix . $n;
}

/** Fiyat girişi: "1.250,50" / "1250.50" / "1250" → kuruş */
function parse_price(string $s): int
{
    $s = str_replace([' ', '₺', 'TL'], '', $s);
    if (str_contains($s, ',')) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    }
    return (int)round(((float)$s) * 100);
}

/* ------------------------------------------------------------------ */
/*  CSRF                                                               */
/* ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Oturum süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}

/* ------------------------------------------------------------------ */
/*  Kimlik doğrulama & roller                                          */
/* ------------------------------------------------------------------ */
const ROLES = [
    'super_admin' => 'Süper Admin',
    'admin'       => 'Admin',
    'editor'      => 'Editör',
    'sales'       => 'Satış Temsilcisi',
    'member'      => 'Üye',
];

const ROLE_DESCRIPTIONS = [
    'super_admin' => 'En yetkili hesap. Her şeyi yapar; günlük satış raporları, aktivite akışı (kim ne yaptı), site ve Sanal POS ayarları ile Admin atama yalnızca ona özeldir.',
    'admin'       => 'Siparişleri yönetir, iptal/iade işaretler, fiyatları değiştirir, danışmanlık/paket ekler; Editör, Satış Temsilcisi ve Üye hesaplarını yönetir.',
    'editor'      => 'Danışmanlık, paket ve sayfa içeriklerini düzenler. Fiyat değiştiremez; siparişleri ve müşteri verilerini göremez.',
    'sales'       => 'Siparişleri görür ve hizmet durumunu günceller, not ekler; müşteri listesi, mesajlar ve destek kayıtlarıyla ilgilenir. İçerik ve fiyat değiştiremez.',
    'member'      => 'Siteye üye olan müşteri. Yalnızca kendi siparişlerini görür; yönetim paneline erişemez.',
];

const STAFF_ROLES =['super_admin', 'admin', 'editor', 'sales'];

/**
 * Yetki matrisi — her rolün yapabildikleri birbirinden farklıdır.
 * Süper Admin her şeyi yapabilir; raporlar, aktivite akışı ve ayarlar yalnızca ona özeldir.
 */
const PERMISSIONS = [
    'dashboard'       => ['super_admin', 'admin', 'editor', 'sales'],
    'orders.view'     => ['super_admin', 'admin', 'sales'],
    'orders.edit'     => ['super_admin', 'admin', 'sales'],
    'orders.refund'   => ['super_admin', 'admin'],
    'customers.view'  => ['super_admin', 'admin', 'sales'],
    'messages.view'   => ['super_admin', 'admin', 'sales'],
    'tickets.manage'  => ['super_admin', 'admin', 'sales'],
    'catalog.edit'    => ['super_admin', 'admin', 'editor'],
    'catalog.price'   => ['super_admin', 'admin'],
    'catalog.create'  => ['super_admin', 'admin'],
    'pages.edit'      => ['super_admin', 'admin', 'editor'],
    'users.manage'    => ['super_admin', 'admin'],
    'reports.sales'   => ['super_admin'],
    'activity.view'   => ['super_admin'],
    'settings.manage' => ['super_admin'],
];

const PERMISSION_LABELS = [
    'dashboard'       => 'Kontrol panelini görüntüleme',
    'orders.view'     => 'Siparişleri görüntüleme',
    'orders.edit'     => 'Sipariş durumu güncelleme & not ekleme',
    'orders.refund'   => 'Sipariş iptal / iade işaretleme',
    'customers.view'  => 'Müşteri listesini görüntüleme',
    'messages.view'   => 'Mesaj ve başvuruları yönetme',
    'tickets.manage'  => 'Arıza / destek kayıtlarını yanıtlama',
    'catalog.edit'    => 'Danışmanlık ve paket içeriklerini düzenleme',
    'catalog.price'   => 'Paket fiyatlarını değiştirme',
    'catalog.create'  => 'Yeni danışmanlık / paket ekleme, yayından kaldırma',
    'pages.edit'      => 'Kurumsal ve yasal sayfaları düzenleme',
    'users.manage'    => 'Kullanıcı ekleme ve düzenleme',
    'reports.sales'   => 'Günlük satış & sipariş raporları, CSV dışa aktarma',
    'activity.view'   => 'Kim ne yaptı — aktivite akışı',
    'settings.manage' => 'Şirket ve Sanal POS ayarları, Admin rolü atama',
];

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $u = row('SELECT * FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']]);
        if (!$u) unset($_SESSION['uid']);
    }
    return $u;
}

function is_staff(?array $u = null): bool
{
    $u = $u ?? current_user();
    return $u && in_array($u['role'], STAFF_ROLES, true);
}

function can(string $perm, ?array $u = null): bool
{
    $u = $u ?? current_user();
    return $u && in_array($u['role'], PERMISSIONS[$perm] ?? [], true);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? url();
        flash('info', 'Devam etmek için lütfen giriş yapın.');
        redirect('giris');
    }
    return $u;
}

function require_perm(string $perm): array
{
    $u = current_user();
    if (!$u || !is_staff($u)) redirect('admin/giris');
    if (!can($perm, $u)) {
        http_response_code(403);
        admin_render('Yetkisiz Erişim', function () {
            echo '<div class="card empty"><div class="empty-icon">🔒</div><h2>Bu alana erişim yetkiniz yok</h2><p>Bu işlem rolünüz için tanımlı değil. Gerekirse Süper Admin ile iletişime geçin.</p><a class="btn" href="' . url('admin') . '">Panele dön</a></div>';
        });
        exit;
    }
    return $u;
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    q('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $u['id']]);
}

function too_many_attempts(): bool
{
    $n = (int)val("SELECT COUNT(*) FROM activity_log WHERE action = 'login_failed' AND ip = ? AND created_at >= ?",
        [client_ip(), date('Y-m-d H:i:s', time() - 900)]);
    return $n >= 6;
}

/* ------------------------------------------------------------------ */
/*  Aktivite kaydı (Süper Admin akışı)                                */
/* ------------------------------------------------------------------ */
const ACTIONS = [
    'login'            => ['Giriş yaptı', 'auth'],
    'logout'           => ['Çıkış yaptı', 'auth'],
    'login_failed'     => ['Hatalı giriş denemesi', 'warn'],
    'register'         => ['Üye oldu', 'user'],
    'order_created'    => ['Sipariş oluşturdu', 'order'],
    'payment_success'  => ['Ödeme başarılı', 'money'],
    'payment_failed'   => ['Ödeme başarısız', 'warn'],
    'order_status'     => ['Sipariş durumunu değiştirdi', 'order'],
    'order_note'       => ['Siparişe not ekledi', 'order'],
    'service_saved'    => ['Hizmeti düzenledi', 'content'],
    'service_created'  => ['Hizmet ekledi', 'content'],
    'package_saved'    => ['Paketi düzenledi', 'content'],
    'package_created'  => ['Paket ekledi', 'content'],
    'price_changed'    => ['Fiyat değiştirdi', 'money'],
    'page_saved'       => ['Sayfa içeriğini düzenledi', 'content'],
    'user_created'     => ['Kullanıcı ekledi', 'user'],
    'user_updated'     => ['Kullanıcıyı düzenledi', 'user'],
    'role_changed'     => ['Rol değiştirdi', 'warn'],
    'settings_saved'   => ['Ayarları güncelledi', 'warn'],
    'message_received' => ['Form gönderdi', 'message'],
    'message_status'   => ['Mesaj durumunu güncelledi', 'message'],
    'ticket_created'   => ['Destek kaydı açtı', 'message'],
    'ticket_reply'     => ['Destek kaydını yanıtladı', 'message'],
    'password_changed' => ['Şifresini değiştirdi', 'auth'],
    'export'           => ['Rapor dışa aktardı', 'content'],
    'install'          => ['Sistemi kurdu', 'warn'],
];

function log_activity(string $action, string $details = '', ?array $user = null, ?string $name = null): void
{
    $u = $user ?? current_user();
    q('INSERT INTO activity_log(user_id, user_name, role, action, details, ip, created_at) VALUES(?,?,?,?,?,?,?)', [
        $u['id'] ?? null,
        $name ?? ($u['name'] ?? 'Ziyaretçi'),
        $u['role'] ?? 'guest',
        $action,
        mb_substr($details, 0, 500),
        client_ip(),
        now(),
    ]);
}

/* ------------------------------------------------------------------ */
/*  Siparişler                                                         */
/* ------------------------------------------------------------------ */
const ORDER_STATUSES = [
    'pending'    => ['Ödeme Bekleniyor', 'gray'],
    'paid'       => ['Ödendi', 'green'],
    'processing' => ['Hizmet Başladı', 'blue'],
    'completed'  => ['Tamamlandı', 'dark'],
    'failed'     => ['Ödeme Başarısız', 'red'],
    'cancelled'  => ['İptal Edildi', 'red'],
    'refunded'   => ['İade Edildi', 'yellow'],
];

/** Satış sayılan (ciroya dahil) durumlar */
const REVENUE_STATUSES = ['paid', 'processing', 'completed'];

function status_badge(string $status): string
{
    [$label, $color] = ORDER_STATUSES[$status] ?? [$status, 'gray'];
    return '<span class="badge badge-' . $color . '">' . e($label) . '</span>';
}

function revenue_in(): string
{
    return "'" . implode("','", REVENUE_STATUSES) . "'";
}

/* ------------------------------------------------------------------ */
/*  Sayfa içerikleri: {{yer_tutucu}} → şirket bilgileri                 */
/* ------------------------------------------------------------------ */
function fill_placeholders(string $html, array $extra = []): string
{
    $map = [
        '{{site}}'      => e(setting('site_name')),
        '{{unvan}}'     => e(setting('company_title')),
        '{{adres}}'     => e(setting('company_address')),
        '{{telefon}}'   => e(setting('company_phone')),
        '{{eposta}}'    => e(setting('company_email')),
        '{{kep}}'       => e(setting('company_kep')),
        '{{vergi_dairesi}}' => e(setting('tax_office')),
        '{{vergi_no}}'  => e(setting('tax_number')),
        '{{mersis}}'    => e(setting('mersis_number')),
        '{{sicil}}'     => e(setting('trade_registry')),
        '{{calisma}}'   => e(setting('working_hours')),
        '{{alan_adi}}'  => e($_SERVER['HTTP_HOST'] ?? 'gsprojeler.com.tr'),
    ];
    $html = strtr($html, $map + $extra);
    if (base_path() !== '') $html = str_replace('href="/', 'href="' . base_path() . '/', $html);
    // Doldurulmamış alıcı alanları (genel görünüm)
    return preg_replace('/\{\{[a-z_]+\}\}/', '<span class="blank">………………</span>', $html);
}

/** Editörden gelen HTML'i güvenli etiketlere indirger (script, stil, olay nitelikleri temizlenir). */
function clean_html(string $html): string
{
    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'details', 'summary', 'blockquote', 'hr', 'span', 'div'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) return '';
    $walk = function (DOMNode $node) use (&$walk, $allowed) {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'], true)) { $node->removeChild($child); continue; }
                $walk($child);
                if (!in_array($tag, $allowed, true)) {
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child);
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    $keep = ($name === 'href' && $tag === 'a' && preg_match('#^(https?:|mailto:|tel:|/|\#)#i', trim($attr->value)))
                        || ($name === 'class') || ($name === 'target' && $tag === 'a') || ($name === 'open' && $tag === 'details');
                    if (!$keep) $child->removeAttribute($attr->name);
                }
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}

function page(string $slug): ?array
{
    return row('SELECT * FROM pages WHERE slug = ?', [$slug]);
}

/* ------------------------------------------------------------------ */
/*  Görünüm                                                            */
/* ------------------------------------------------------------------ */
function view(string $file, array $vars = []): void
{
    extract($vars);
    require APP_ROOT . '/app/' . $file;
}

function not_found(): never
{
    http_response_code(404);
    render('Sayfa bulunamadı', function () {
        echo '<section class="section"><div class="container narrow center"><div class="big-404">404</div><h1>Aradığınız sayfa bulunamadı</h1><p class="muted">Sayfa taşınmış ya da kaldırılmış olabilir.</p><a class="btn btn-primary" href="' . url() . '">Ana sayfaya dön</a></div></section>';
    });
    exit;
}

function render(string $title, callable $body, array $opts = []): void
{
    require_once APP_ROOT . '/app/views/layout.php';
    layout_header($title, $opts);
    $body();
    layout_footer();
}

function admin_render(string $title, callable $body, array $opts = []): void
{
    require_once APP_ROOT . '/app/views/admin_layout.php';
    admin_header($title, $opts);
    $body();
    admin_footer();
}

function logo_svg(string $class = 'logo', bool $withText = true): string
{
    $mark = '<rect width="48" height="48" rx="11" fill="#2B2D31"/>'
        . '<path d="M33 48 L45 6 h3 v7 L38 48z" fill="#FDB913"/>'
        . '<path d="M40 48 L48 20 v20 a8 8 0 0 1 -8 8z" fill="#C8102E"/>'
        . '<text x="21" y="31.5" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-weight="800" font-size="19" letter-spacing="-0.5" fill="#FFFFFF">GS</text>';
    if (!$withText) {
        return '<svg class="' . e($class) . '" viewBox="0 0 48 48" role="img" aria-label="GS Projeler"><title>GS Projeler</title>' . $mark . '</svg>';
    }
    return '<svg class="' . e($class) . '" viewBox="0 0 196 48" role="img" aria-label="GS Projeler"><title>GS Projeler</title>' . $mark
        . '<text x="60" y="26" textLength="132" lengthAdjust="spacingAndGlyphs" font-family="Inter,Arial,sans-serif" font-weight="800" font-size="19" letter-spacing="0.5" fill="currentColor">GS PROJELER</text>'
        . '<rect x="60" y="33" width="14" height="3" rx="1.5" fill="#FDB913"/><rect x="77" y="33" width="14" height="3" rx="1.5" fill="#C8102E"/>'
        . '<text x="96" y="37" textLength="96" lengthAdjust="spacingAndGlyphs" font-family="Inter,Arial,sans-serif" font-weight="600" font-size="7.2" letter-spacing="1.6" fill="currentColor" opacity=".6">SPOR DANIŞMANLIK</text>'
        . '</svg>';
}
