<?php
/** robots.txt (.htaccess ile /robots.txt adresinden sunulur) */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
foreach (['/admin/', '/install/', '/odeme.php', '/odeme-sonuc.php', '/paytr-bildirim.php', '/hesabim.php', '/giris.php', '/kayit.php', '/sifremi-unuttum.php'] as $p) {
    echo 'Disallow: ' . rtrim((string) parse_url(config('base_url'), PHP_URL_PATH), '/') . $p . "\n";
}
echo "\nSitemap: " . url('sitemap.xml') . "\n";
