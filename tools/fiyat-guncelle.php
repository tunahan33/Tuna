<?php
/**
 * Kurulu sitedeki danışmanlık paket fiyatlarını en az 25.000 ₺ olacak şekilde günceller (Ekim 2026).
 * Yalnızca fiyatı 25.000 ₺'nin altında olan paketlere dokunur; panelden elle yükseltilmiş fiyatlar korunur.
 * Kullanım (sunucuda):  php /var/www/gsprojeler/tools/fiyat-guncelle.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ROOT', dirname(__DIR__));
$GLOBALS['__config'] = require ROOT . '/config.php';
date_default_timezone_set('Europe/Istanbul');
require ROOT . '/includes/db.php';
require ROOT . '/includes/functions.php';
require ROOT . '/includes/auth.php';

const MIN_PRICE = 25000;
// hizmet adresi => [paket adı => [yeni fiyat, üstü çizili eski fiyat]]
$map = [
    'sporcu-performans-danismanligi'   => ['Başlangıç Paketi' => [25000, null], 'Profesyonel Paket' => [39900, 44900], 'Elit Paket' => [59900, 64900]],
    'sporcu-beslenmesi-danismanligi'   => ['Başlangıç Paketi' => [25000, null], 'Profesyonel Paket' => [39900, 44900], 'Elit Paket' => [59900, 64900]],
    'kulup-yonetimi-danismanligi'      => ['Analiz Paketi' => [25000, null]],
    'sporcu-kariyer-danismanligi'      => ['Profil Paketi' => [25000, null], 'Kariyer Paketi' => [39900, 44900], 'Elit Kariyer Paketi' => [59900, 64900]],
    'spor-tesisi-proje-danismanligi'   => ['Ön Fizibilite Paketi' => [25000, null]],
    'sporcu-psikolojisi-mental-kocluk' => ['Başlangıç Paketi' => [25000, null], 'Gelişim Paketi' => [39900, 44900], 'Takım Paketi' => [59900, 64900]],
    'sakatlik-onleme-rehabilitasyon'   => ['Risk Analizi Paketi' => [25000, null], 'Sahaya Dönüş Paketi' => [39900, 44900], 'Sezon Koruma Paketi' => [59900, 64900]],
];

$changed = [];
foreach (rows('SELECT p.*, s.slug AS sslug FROM packages p JOIN services s ON s.id = p.service_id ORDER BY s.sort_order, p.sort_order') as $p) {
    if ((float) $p['price'] >= MIN_PRICE) continue;
    [$price, $old] = $map[$p['sslug']][$p['name']] ?? [MIN_PRICE, null];
    q('UPDATE packages SET price = ?, old_price = ?, updated_at = ? WHERE id = ?', [$price, $old, now(), $p['id']]);
    $changed[] = $p['name'] . ' (' . $p['sslug'] . '): ' . money($p['price']) . ' → ' . money($price);
    echo '✓ ' . end($changed) . "\n";
}
if ($changed) {
    log_activity('Fiyat güncelleme aracı çalıştırıldı', count($changed) . ' paket en az ' . money(MIN_PRICE) . ' yapıldı', 'package', null, ['id' => null, 'name' => 'Sistem', 'role' => 'system']);
}
echo count($changed) ? count($changed) . " paket güncellendi.\n" : "Güncellenecek paket yok (tüm fiyatlar zaten en az " . money(MIN_PRICE) . ").\n";
