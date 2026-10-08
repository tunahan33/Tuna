<?php
/**
 * Veritabanı güncellemeleri. Canlı sitede `bash kurulum.sh guncelle` sonrası ilk sayfa açılışında
 * bir kez çalışır; db_version ayarı hangi adımların uygulandığını tutar.
 */

const DB_VERSION = 6;

/** Firma bilgileri (vergi levhası) — yalnızca panelden henüz doldurulmamış alanlara yazılır */
const COMPANY_DEFAULTS = [
    'company_title'   => 'İSKELET MEDYA LİMİTED ŞİRKETİ',
    'company_address' => 'Mecidiyeköy Mah. Eski Osmanlı Sk. Arıkan İş Merkezi No: 30 İç Kapı No: 10 Şişli / İstanbul',
    'tax_office'      => 'Zincirlikuyu',
    'tax_number'      => '4801170436',
    'company_email'   => 'info@gssportiffaaliyetler.com',
    // Natro kurumsal e-posta (şifre panelden girilir)
    'smtp_host'       => 'mail.kurumsaleposta.com',
    'smtp_user'       => 'info@gssportiffaaliyetler.com',
    'smtp_from'       => 'info@gssportiffaaliyetler.com',
];

/** v3 fiyatları: koçluk türü => paket sırasına göre [fiyat, eski fiyat] (KDV dahil) */
const PRICES_V3 = [
    'valorant-koclugu' => [[25000, 29900], [49900, 57900], [119900, 139900]],
    'league-of-legends-koclugu' => [[25000, 29900], [47900, 55900], [114900, 134900]],
    'cs2-koclugu' => [[25000, 29900], [49900, 57900], [119900, 139900]],
    'pubg-mobile-koclugu' => [[25000, 28900], [44900, 52900], [99900, 119900]],
    'ea-sports-fc-koclugu' => [[25000, 28900], [42900, 49900], [89900, 104900]],
    'takim-ve-turnuva-koclugu' => [[59900, 69900], [149900, 174900], [34900, null]],
    'e-spor-mental-performans-koclugu' => [[25000, 29900], [45900, 52900], [84900, 99900]],
    'yayinci-ve-icerik-uretici-koclugu' => [[27900, 32900], [49900, 57900], [99900, 119900]],
];

/** Eski kurulumlara yeni tablo/sütunları ve verileri ekler */
function db_upgrade(): void
{
    $ver = (int) setting('db_version', '1');
    if ($ver >= DB_VERSION) {
        return;
    }
    if ($ver < 2) {
        $mysql = (config('db.driver') ?? 'mysql') === 'mysql';
        $pk = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $int = $mysql ? 'INT UNSIGNED' : 'INTEGER';
        $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $sql = [
            'ALTER TABLE orders ADD voucher_code VARCHAR(30) NULL',
            'ALTER TABLE orders ADD voucher_amount DECIMAL(12,2) NULL',
            "CREATE TABLE IF NOT EXISTS vouchers (id $pk, code VARCHAR(30) NOT NULL UNIQUE, customer_email VARCHAR(190) NOT NULL, customer_name VARCHAR(120) NULL, amount DECIMAL(12,2) NOT NULL, balance DECIMAL(12,2) NOT NULL, source_order_no VARCHAR(40) NULL, note VARCHAR(500) NULL, status VARCHAR(12) NOT NULL DEFAULT 'active', expires_at DATETIME NOT NULL, created_by $int NULL, created_at DATETIME NOT NULL)$tail",
            "CREATE TABLE IF NOT EXISTS voucher_uses (id $pk, voucher_id $int NOT NULL, order_id $int NOT NULL, order_no VARCHAR(40) NOT NULL, amount DECIMAL(12,2) NOT NULL, created_at DATETIME NOT NULL)$tail",
        ];
        foreach ($sql as $s) {
            try {
                db()->exec($s);
            } catch (Throwable $e) {
                // Sütun zaten varsa yoksay
            }
        }
    }
    if ($ver < 3) {
        db_upgrade_v3();
    }
    if ($ver < 4) {
        db_upgrade_v4();
    }
    if ($ver < 5) {
        db_upgrade_v5();
    }
    if ($ver < 6) {
        db_upgrade_v6();
    }
    save_setting('db_version', (string) DB_VERSION);
}


function setting_is_placeholder(string $v): bool
{
    $v = trim($v);
    return $v === '' || $v === '-' || str_contains($v, 'girin') || str_contains($v, '___') || str_ends_with($v, '@gssportif.com') || $v === 'mail.gssportif.com';
}

function db_upgrade_v3(): void
{
    foreach (COMPANY_DEFAULTS as $k => $val) {
        if (setting_is_placeholder((string) setting($k))) {
            save_setting($k, $val);
        }
    }
    foreach (PRICES_V3 as $slug => $list) {
        $sid = val('SELECT id FROM services WHERE slug = ?', [$slug]);
        if (!$sid) {
            continue;
        }
        $pkgs = rows('SELECT id FROM packages WHERE service_id = ? ORDER BY sort_order, id', [$sid]);
        foreach ($pkgs as $i => $p) {
            if (isset($list[$i])) {
                q('UPDATE packages SET price = ?, old_price = ?, updated_at = ? WHERE id = ?', [$list[$i][0], $list[$i][1], now(), $p['id']]);
            }
        }
    }
    // 25.000 TL altında kalan (sonradan eklenmiş) paketler varsa en az 25.000 TL'ye çekilir
    q('UPDATE packages SET price = 25000 WHERE price < 25000');
    q('UPDATE packages SET old_price = NULL WHERE old_price IS NOT NULL AND old_price <= price');
    log_activity('Sistem güncellemesi', 'Firma bilgileri (vergi levhası) ve yeni paket fiyatları uygulandı', 'system', null, ['id' => null, 'name' => 'Sistem', 'role' => 'system']);
}

/** v4: Havale/EFT ile ödeme — varsayılan ayarlar ve sözleşmelerdeki ödeme/iade maddeleri */
function db_upgrade_v4(): void
{
    if (setting('havale_enabled') === '') {
        save_setting('havale_enabled', '1');
    }
    if (setting_is_placeholder((string) setting('bank_holder'))) {
        save_setting('bank_holder', (string) setting('company_title'));
    }
    $pairs = [
        ['<strong>Ödeme Şekli:</strong> Kredi kartı / banka kartı ile tek çekim (Garanti BBVA Sanal POS, 3D Secure)', '<strong>Ödeme Şekli:</strong> {{odeme_sekli}}'],
        ['Onaylanan iadeler, en geç <strong>14 gün</strong> içinde ödemenin yapıldığı kredi kartına / banka kartına Garanti BBVA sanal POS üzerinden iade edilir.', 'Onaylanan iadeler, en geç <strong>14 gün</strong> içinde kartla yapılan ödemelerde ödemenin yapıldığı kredi kartına / banka kartına Garanti BBVA sanal POS üzerinden, Havale/EFT ile yapılan ödemelerde ise Alıcı adına kayıtlı IBAN\'a iade edilir.'],
        ['Nakit veya havale ile iade yapılmaz.', 'Nakit iade yapılmaz.'],
        ['tahsil edilen bedeli, ödemede kullanılan karta iade eder.', 'tahsil edilen bedeli kartla yapılan ödemelerde ödemede kullanılan karta, Havale/EFT ile yapılan ödemelerde Alıcı\'nın bildireceği kendi adına kayıtlı IBAN\'a iade eder.'],
        ['5.3. Ödeme işlemi Garanti BBVA sanal POS altyapısı ile 3D Secure doğrulamalı olarak gerçekleştirilir. Kart bilgileri Satıcı tarafından görülmez ve saklanmaz.', '5.3. Kartla ödemeler Garanti BBVA sanal POS altyapısı ile 3D Secure doğrulamalı olarak gerçekleştirilir; kart bilgileri Satıcı tarafından görülmez ve saklanmaz. Havale/EFT ile ödemelerde sözleşme, bedelin Satıcı hesabına geçtiği tarihte ifaya başlanır; 3 iş günü içinde ödemesi yapılmayan siparişler iptal edilir.'],
        ['Visa, Mastercard ve Troy logolu tüm kredi kartları ve banka kartları ile Garanti BBVA güvencesinde 3D Secure doğrulamalı ödeme yapabilirsiniz. Kart bilgileriniz sitemizde saklanmaz.', 'Visa, Mastercard ve Troy logolu tüm kredi kartları ve banka kartları ile Garanti BBVA güvencesinde 3D Secure doğrulamalı ödeme yapabilirsiniz. Kart bilgileriniz sitemizde saklanmaz. Dilerseniz Havale/EFT ile de ödeyebilirsiniz; banka hesap bilgilerimiz ödeme adımında ve e-posta ile iletilir.'],
    ];
    foreach (rows('SELECT id, content FROM pages') as $p) {
        $new = $p['content'];
        foreach ($pairs as [$a, $b]) {
            $new = str_replace($a, $b, $new);
        }
        if ($new !== $p['content']) {
            q('UPDATE pages SET content = ?, updated_at = ? WHERE id = ?', [$new, now(), $p['id']]);
        }
    }
}

/** v5: Garanti BBVA ve Havale/EFT kaldırıldı — ayarlar silinir, metinler sağlayıcıdan bağımsız hale getirilir */
function db_upgrade_v5(): void
{
    q("DELETE FROM settings WHERE skey LIKE 'garanti%' OR skey IN ('pos_mode', 'havale_enabled', 'bank_name', 'bank_holder', 'bank_iban')");
    q("UPDATE orders SET status = 'pending' WHERE status = 'awaiting_transfer'");
    $pairs = [
        ['tahsil edilen bedeli kartla yapılan ödemelerde ödemede kullanılan karta, Havale/EFT ile yapılan ödemelerde Alıcı\'nın bildireceği kendi adına kayıtlı IBAN\'a iade eder.', 'tahsil edilen bedeli ödemede kullanılan karta iade eder.'],
        [' Dilerseniz Havale/EFT ile de ödeyebilirsiniz; banka hesap bilgilerimiz ödeme adımında ve e-posta ile iletilir.', ''],
        ['5.3. Kartla ödemeler Garanti BBVA sanal POS altyapısı ile 3D Secure doğrulamalı olarak gerçekleştirilir; kart bilgileri Satıcı tarafından görülmez ve saklanmaz. Havale/EFT ile ödemelerde sözleşme, bedelin Satıcı hesabına geçtiği tarihte ifaya başlanır; 3 iş günü içinde ödemesi yapılmayan siparişler iptal edilir.', '5.3. Kartla ödemeler, lisanslı ödeme hizmet sağlayıcısının altyapısı üzerinden 3D Secure doğrulamalı olarak gerçekleştirilir; kart bilgileri Satıcı tarafından görülmez ve saklanmaz.'],
        ['Onaylanan iadeler, en geç <strong>14 gün</strong> içinde kartla yapılan ödemelerde ödemenin yapıldığı kredi kartına / banka kartına Garanti BBVA sanal POS üzerinden, Havale/EFT ile yapılan ödemelerde ise Alıcı adına kayıtlı IBAN\'a iade edilir.', 'Onaylanan iadeler, en geç <strong>14 gün</strong> içinde ödemenin yapıldığı kredi kartına / banka kartına iade edilir.'],
        ['ödeme işlemi için Garanti BBVA\'ya,', 'ödeme işlemi için ödeme hizmet sağlayıcısına,'],
        ['doğrudan Garanti BBVA\'nın ödeme sayfasında girilir', 'doğrudan ödeme hizmet sağlayıcısının güvenli ödeme sayfasında girilir'],
        ['ödeme işleminin gerçekleştirilmesi için Garanti BBVA ve hizmetin', 'ödeme işleminin gerçekleştirilmesi için ödeme hizmet sağlayıcısı ve hizmetin'],
        ['Visa, Mastercard ve Troy logolu tüm kredi kartları ve banka kartları ile Garanti BBVA güvencesinde 3D Secure doğrulamalı ödeme yapabilirsiniz.', 'Visa, Mastercard ve Troy logolu kredi kartları ve banka kartları ile 3D Secure doğrulamalı ödeme yapabilirsiniz.'],
        ['<h3>Garanti BBVA Sanal POS ve 3D Secure</h3><p>Ödemeleriniz <strong>Garanti BBVA</strong> sanal POS altyapısı üzerinden alınır.', '<h3>3D Secure ile Güvenli Ödeme</h3><p>Ödemeleriniz lisanslı ödeme hizmet sağlayıcısının altyapısı üzerinden alınır.'],
        ['Sitemizdeki tüm ödemeler <strong>Garanti BBVA</strong> sanal POS altyapısı üzerinden, <strong>3D Secure</strong> doğrulaması ile gerçekleştirilir.', 'Sitemizdeki tüm ödemeler lisanslı ödeme hizmet sağlayıcısının altyapısı üzerinden, <strong>3D Secure</strong> doğrulaması ile gerçekleştirilir.'],
    ];
    foreach (rows('SELECT id, content FROM pages') as $p) {
        $new = $p['content'];
        foreach ($pairs as [$a, $b]) {
            $new = str_replace($a, $b, $new);
        }
        if ($new !== $p['content']) {
            q('UPDATE pages SET content = ?, updated_at = ? WHERE id = ?', [$new, now(), $p['id']]);
        }
    }
}

/** v6: PayTR — ödeme denemeleri tablosu ve varsayılan ayarlar */
function db_upgrade_v6(): void
{
    $mysql = (config('db.driver') ?? 'mysql') === 'mysql';
    $pk = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $int = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    db()->exec("CREATE TABLE IF NOT EXISTS payment_attempts (id $pk, order_id $int NOT NULL, oid VARCHAR(64) NOT NULL UNIQUE, amount INT NOT NULL, status VARCHAR(12) NOT NULL DEFAULT 'started', test_mode TINYINT NOT NULL DEFAULT 0, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)$tail");
    try {
        db()->exec('CREATE INDEX idx_attempts_order ON payment_attempts (order_id)');
    } catch (Throwable $e) {
    }
    $pairs = [
        ['ödeme işlemi için ödeme hizmet sağlayıcısına,', 'ödeme işlemi için ödeme kuruluşu PayTR Ödeme ve Elektronik Para Kuruluşu A.Ş.\'ye,'],
        ['Ödemeleriniz lisanslı ödeme hizmet sağlayıcısının altyapısı üzerinden alınır.', 'Ödemeleriniz, Türkiye Cumhuriyet Merkez Bankası lisanslı ödeme kuruluşu <strong>PayTR</strong> altyapısı üzerinden alınır.'],
        ['Sitemizdeki tüm ödemeler lisanslı ödeme hizmet sağlayıcısının altyapısı üzerinden,', 'Sitemizdeki tüm ödemeler lisanslı ödeme kuruluşu <strong>PayTR</strong> altyapısı üzerinden,'],
        ['5.3. Kartla ödemeler, lisanslı ödeme hizmet sağlayıcısının altyapısı üzerinden', '5.3. Kartla ödemeler, lisanslı ödeme kuruluşu PayTR Ödeme ve Elektronik Para Kuruluşu A.Ş. altyapısı üzerinden'],
        ['doğrudan ödeme hizmet sağlayıcısının güvenli ödeme sayfasında girilir', 'doğrudan PayTR\'ın güvenli ödeme formuna girilir'],
        ['ödeme işleminin gerçekleştirilmesi için ödeme hizmet sağlayıcısı ve hizmetin', 'ödeme işleminin gerçekleştirilmesi için ödeme kuruluşu PayTR ve hizmetin'],
    ];
    foreach (rows('SELECT id, content FROM pages') as $p) {
        $new = str_replace(array_column($pairs, 0), array_column($pairs, 1), $p['content']);
        if ($new !== $p['content']) {
            q('UPDATE pages SET content = ?, updated_at = ? WHERE id = ?', [$new, now(), $p['id']]);
        }
    }
    foreach (['paytr_mode' => 'off', 'paytr_installment' => '1'] as $k => $v) {
        if (setting($k) === '') {
            save_setting($k, $v);
        }
    }
}
