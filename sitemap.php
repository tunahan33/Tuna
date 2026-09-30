<?php
/** Google site haritası (.htaccess ile /sitemap.xml adresinden sunulur) */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [
    [url(), null, '1.0'],
    [url('hizmetler.php'), null, '0.9'],
    [url('paketler.php'), null, '0.9'],
    [url('iletisim.php'), null, '0.6'],
];
foreach (rows('SELECT slug, updated_at FROM services WHERE is_active = 1 ORDER BY sort_order') as $s) {
    $urls[] = [url('hizmet.php?slug=' . rawurlencode($s['slug'])), $s['updated_at'], '0.8'];
}
foreach (rows('SELECT p.id, p.updated_at FROM packages p JOIN services s ON s.id = p.service_id WHERE p.is_active = 1 AND s.is_active = 1') as $p) {
    $urls[] = [url('paket.php?id=' . (int) $p['id']), $p['updated_at'], '0.7'];
}
foreach (rows('SELECT slug, updated_at FROM pages ORDER BY sort_order') as $p) {
    $urls[] = [url('sayfa.php?s=' . rawurlencode($p['slug'])), $p['updated_at'], '0.3'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $mod, $prio]) {
    echo '  <url><loc>' . e($loc) . '</loc>' . ($mod ? '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>' : '') . '<priority>' . $prio . "</priority></url>\n";
}
echo '</urlset>';
