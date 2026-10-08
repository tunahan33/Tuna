<?php
/**
 * Kurulu sitede Garanti BBVA sanal POS ve havale/EFT ile ilgili ifadeleri kaldırır (Ekim 2026):
 *  - Sözleşme ve politika metinlerindeki ödeme cümlelerini yeni akışa göre günceller
 *    (sipariş oluşturulur, ekip ödeme için müşteriyi arar)
 *  - Eski Garanti ve havale ayarlarını siler, “havale bekleniyor” durumundaki siparişleri “ödeme bekliyor” yapar
 * Birden fazla çalıştırılabilir; her şey güncelse değişiklik yapmaz.
 * Kullanım (sunucuda):  php /var/www/gsprojeler/tools/odeme-metinleri-guncelle.php
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

/** [eski metin => yeni metin]; uzun/özel olanlar önce gelir */
function payment_text_replacements(): array
{
    $payNew = 'Sipariş sonrasında Satıcı tarafından Alıcı ile iletişime geçilerek belirlenir';
    $refundNew = "Onaylanan iadeler, en geç 14 gün içinde ödemenin yapıldığı yönteme (karta veya Alıcı adına kayıtlı banka hesabına) iade edilir.";
    $refundOld = 'Onaylanan iadeler, en geç 14 gün içinde ödemenin yapıldığı kredi kartına / banka kartına Garanti BBVA sanal POS üzerinden iade edilir.';
    return [
        'Kredi kartı / banka kartı ile tek çekim (Garanti BBVA Sanal POS, 3D Secure) veya havale / EFT' => $payNew,
        'Kredi kartı / banka kartı ile tek çekim (Garanti BBVA Sanal POS, 3D Secure)' => $payNew,
        'Ödeme işlemi Garanti BBVA sanal POS altyapısı ile 3D Secure doğrulamalı olarak gerçekleştirilir. Kart bilgileri Satıcı tarafından görülmez ve saklanmaz.'
            => "Ödeme, sipariş sonrasında Satıcı'nın Alıcı ile iletişime geçerek bildirdiği yöntemle yapılır. Hizmet, ödeme Satıcı'ya ulaştıktan sonra başlatılır.",
        $refundOld . " Havale / EFT ile yapılan ödemelerin iadesi, Alıcı adına kayıtlı ve Alıcı'nın bildireceği IBAN'a yapılır." => $refundNew,
        $refundOld => $refundNew,
        '<h3>Ödeme Güvenliği</h3><p>Sitemizdeki tüm ödemeler <strong>Garanti BBVA</strong> sanal POS altyapısı üzerinden, <strong>3D Secure</strong> doğrulaması ile gerçekleştirilir. Kredi kartı bilgileriniz doğrudan bankanın güvenli ödeme sayfasına iletilir; sitemizin sunucularında <strong>hiçbir şekilde kaydedilmez, saklanmaz ve görüntülenemez.</strong></p>'
            => '<h3>Ödeme Güvenliği</h3><p>Sitemizde kart bilgisi alınmaz ve saklanmaz. Ödemeler, sipariş sonrasında ekibimizin sizinle iletişime geçerek bildirdiği güvenli yöntemle yapılır.</p>',
        "ödeme işlemi için Garanti BBVA'ya, " => 'ödemenin alınması için ilgili banka veya ödeme kuruluşuna, ',
    ];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
    return; // başka bir dosyadan yalnızca liste için dahil edildi
}

$n = 0;
foreach (rows('SELECT id, title, content FROM pages') as $p) {
    $c = strtr($p['content'], payment_text_replacements());
    if ($c !== $p['content']) {
        q('UPDATE pages SET content = ?, updated_at = ? WHERE id = ?', [$c, now(), $p['id']]);
        echo '✓ ' . $p['title'] . "\n";
        $n++;
    }
}
$keys = ['pos_mode', 'garanti_merchant_id', 'garanti_terminal_id', 'garanti_prov_user', 'garanti_prov_password', 'garanti_store_key', 'garanti_security_level',
    'transfer_enabled', 'bank_name', 'bank_account_holder', 'bank_iban', 'bank_note', 'transfer_days'];
$deleted = 0;
foreach ($keys as $k) {
    $deleted += q('DELETE FROM settings WHERE skey = ?', [$k])->rowCount();
}
$moved = q("UPDATE orders SET status = 'pending', updated_at = ? WHERE status = 'transfer'", [now()])->rowCount();

echo "$n sayfa güncellendi · $deleted eski ödeme ayarı silindi · $moved havale siparişi “Ödeme Bekliyor” yapıldı.\n";
if ($n || $deleted || $moved) {
    log_activity('Garanti ve havale kaldırıldı', "$n sayfa, $deleted ayar, $moved sipariş güncellendi", 'settings', null, ['id' => null, 'name' => 'Sistem', 'role' => 'system']);
}
