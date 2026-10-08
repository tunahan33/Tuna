<?php
/**
 * Kurulu sitedeki sözleşme metinlerine "havale / EFT" ödeme seçeneğini ekler (Ekim 2026).
 * Birden fazla çalıştırılabilir; metin zaten güncelse değişiklik yapmaz.
 * Kullanım (sunucuda):  php /var/www/gsprojeler/tools/odeme-sekli-guncelle.php
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

$card = 'Kredi kartı / banka kartı ile tek çekim (Garanti BBVA Sanal POS, 3D Secure)';
$refund = 'Onaylanan iadeler, en geç 14 gün içinde ödemenin yapıldığı kredi kartına / banka kartına Garanti BBVA sanal POS üzerinden iade edilir.';
$refundAdd = " Havale / EFT ile yapılan ödemelerin iadesi, Alıcı adına kayıtlı ve Alıcı'nın bildireceği IBAN'a yapılır.";

$n = 0;
foreach (rows('SELECT id, slug, title, content FROM pages') as $p) {
    $c = $p['content'];
    if (str_contains($c, $card) && !str_contains($c, $card . ' veya havale')) {
        $c = str_replace($card, $card . ' veya havale / EFT', $c);
    }
    if (str_contains($c, $refund) && !str_contains($c, 'Havale / EFT ile yapılan ödemelerin iadesi')) {
        $c = str_replace($refund, $refund . $refundAdd, $c);
    }
    if ($c !== $p['content']) {
        q('UPDATE pages SET content = ?, updated_at = ? WHERE id = ?', [$c, now(), $p['id']]);
        echo '✓ ' . $p['title'] . "\n";
        $n++;
    }
}
if ($n) {
    log_activity('Sözleşmelere havale/EFT eklendi', $n . ' sayfa güncellendi', 'page', null, ['id' => null, 'name' => 'Sistem', 'role' => 'system']);
}
echo $n ? "$n sayfa güncellendi.\n" : "Sayfalar zaten güncel.\n";
