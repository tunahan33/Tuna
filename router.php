<?php
/**
 * Yalnızca bilgisayarda çalıştırırken kullanılır:  php -S localhost:8000 router.php
 * app/ ve data/ klasörlerine tarayıcıdan erişimi engeller (sunucuda bu işi .htaccess yapar).
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(app|data)(/|$)#', $path) || preg_match('#\.(sqlite|md|ini)$#', $path) || preg_match('#^/uploads/.*\.(php|phtml|phar)$#i', $path)) {
    http_response_code(403);
    exit('Erişim engellendi');
}
return false;
