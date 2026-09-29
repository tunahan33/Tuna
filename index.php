<?php
/**
 * GS Projeler — ön denetleyici (front controller).
 * Tüm istekler .htaccess ile bu dosyaya yönlendirilir.
 * Yerel geliştirme: php -S localhost:8000 index.php
 */

// PHP yerleşik sunucusu: statik dosyaları doğrudan sun, gizli klasörleri engelle
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#^/(app|data)(/|$)|/\.#', $p)) { http_response_code(403); exit('Forbidden'); }
    if ($p !== '/' && is_file(__DIR__ . $p)) return false;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require __DIR__ . '/app/core.php';

set_exception_handler(function (Throwable $e) {
    error_log($e);
    http_response_code(500);
    echo APP_DEBUG ? '<pre>' . e($e) . '</pre>' : 'Beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
});

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rawurldecode(substr($path, strlen(base_path())));
$path = trim($path, '/');
if ($path === 'index.php') $path = '';

// İlk kurulum: Süper Admin hesabı yoksa kurulum sihirbazına yönlendir
db();
$hasSuper = (bool)val("SELECT 1 FROM users WHERE role = 'super_admin' LIMIT 1");
if (!$hasSuper && $path !== 'kurulum' && !str_starts_with($path, 'assets/')) redirect('kurulum');

$routes = [
    ''                               => ['pages/home.php', 'page_home'],
    'hizmetler'                      => ['pages/services.php', 'page_services'],
    'hizmet/([a-z0-9-]+)'            => ['pages/services.php', 'page_service'],
    'paket/(\d+)'                    => ['pages/services.php', 'page_package'],
    'odeme/(\d+)'                    => ['pages/checkout.php', 'page_checkout'],
    'odeme/banka/([A-Z0-9]+)'        => ['pages/checkout.php', 'page_pay'],
    'odeme/sonuc'                    => ['pages/checkout.php', 'page_callback'],
    'odeme/demo'                     => ['pages/checkout.php', 'page_demo_pay'],
    'odeme/tamamlandi/([A-Z0-9]+)'   => ['pages/checkout.php', 'page_result'],
    'giris'                          => ['pages/account.php', 'page_login'],
    'kayit'                          => ['pages/account.php', 'page_register'],
    'cikis'                          => ['pages/account.php', 'page_logout'],
    'hesabim'                        => ['pages/account.php', 'page_account'],
    'kurulum'                        => ['pages/account.php', 'page_install'],
    'iletisim'                       => ['pages/forms.php', 'page_contact'],
    'insan-kaynaklari'               => ['pages/forms.php', 'page_hr'],
    'basvuru-formu'                  => ['pages/forms.php', 'page_kvkk'],
    'siparis-takibi'                 => ['pages/forms.php', 'page_order_track'],
    'ariza-takibi'                   => ['pages/forms.php', 'page_tickets'],
    'cerez-tercihleri'               => ['pages/forms.php', 'page_cookie_prefs'],

    // Yönetim paneli
    'admin'                          => ['admin/dashboard.php', 'admin_dashboard'],
    'admin/giris'                    => ['admin/dashboard.php', 'admin_login'],
    'admin/siparisler'               => ['admin/orders.php', 'admin_orders'],
    'admin/siparis/(\d+)'            => ['admin/orders.php', 'admin_order'],
    'admin/raporlar'                 => ['admin/reports.php', 'admin_reports'],
    'admin/raporlar/gun/(\d{4}-\d{2}-\d{2})' => ['admin/reports.php', 'admin_report_day'],
    'admin/raporlar/csv'             => ['admin/reports.php', 'admin_report_csv'],
    'admin/aktivite'                 => ['admin/reports.php', 'admin_activity'],
    'admin/aktivite/canli'           => ['admin/reports.php', 'admin_activity_feed'],
    'admin/hizmetler'                => ['admin/catalog.php', 'admin_services'],
    'admin/hizmet/(\d+|yeni)'        => ['admin/catalog.php', 'admin_service_edit'],
    'admin/paketler'                 => ['admin/catalog.php', 'admin_packages'],
    'admin/paket/(\d+|yeni)'         => ['admin/catalog.php', 'admin_package_edit'],
    'admin/sayfalar'                 => ['admin/catalog.php', 'admin_pages'],
    'admin/sayfa/([a-z0-9-]+)'       => ['admin/catalog.php', 'admin_page_edit'],
    'admin/mesajlar'                 => ['admin/support.php', 'admin_messages'],
    'admin/mesaj/(\d+)'              => ['admin/support.php', 'admin_message'],
    'admin/destek'                   => ['admin/support.php', 'admin_tickets'],
    'admin/destek/(\d+)'             => ['admin/support.php', 'admin_ticket'],
    'admin/musteriler'               => ['admin/users.php', 'admin_customers'],
    'admin/kullanicilar'             => ['admin/users.php', 'admin_users'],
    'admin/kullanici/(\d+|yeni)'     => ['admin/users.php', 'admin_user_edit'],
    'admin/profil'                   => ['admin/users.php', 'admin_profile'],
    'admin/ayarlar'                  => ['admin/users.php', 'admin_settings'],
    'admin/yetkiler'                 => ['admin/users.php', 'admin_roles'],
];

foreach ($routes as $pattern => [$file, $fn]) {
    if (preg_match('#^' . $pattern . '$#', $path, $m)) {
        require_once __DIR__ . '/app/' . $file;
        array_shift($m);
        $fn(...$m);
        exit;
    }
}

// Veritabanındaki içerik sayfaları (Hakkımızda, SSS, sözleşmeler...)
if (preg_match('#^[a-z0-9-]+$#', $path) && page($path)) {
    require_once __DIR__ . '/app/pages/forms.php';
    page_static($path);
    exit;
}

not_found();
