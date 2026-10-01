<?php
/** Google site haritası (.htaccess ile /sitemap.xml adresinden sunulur) */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [
    [url(), null, '1.0'],
    [url('urunler.php'), null, '0.9'],
    [url('takim-siparisi.php'), null, '0.7'],
    [url('iletisim.php'), null, '0.5'],
];
foreach (rows('SELECT slug, updated_at FROM categories WHERE is_active = 1 ORDER BY sort_order') as $c) {
    $urls[] = [url('urunler.php?kategori=' . rawurlencode($c['slug'])), $c['updated_at'], '0.8'];
}
foreach (rows('SELECT p.slug, p.updated_at FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND c.is_active = 1') as $p) {
    $urls[] = [url('urun.php?u=' . rawurlencode($p['slug'])), $p['updated_at'], '0.8'];
}
foreach (rows('SELECT slug, updated_at FROM pages ORDER BY sort_order') as $p) {
    $urls[] = [url('sayfa.php?s=' . rawurlencode($p['slug'])), $p['updated_at'], '0.3'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $mod, $prio]) {
    echo '  <url><loc>' . e($loc) . '</loc>' . ($mod ? '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>' : '') . '<priority>' . $prio . "</priority></url>\n";
}
echo '</urlset>';
