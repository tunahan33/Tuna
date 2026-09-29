<?php
/**
 * GS Projeler — temel yapılandırma.
 * Şirket bilgileri ve Garanti BBVA Sanal POS bilgileri Süper Admin panelinden
 * (Ayarlar) değiştirilebilir; buradaki değerler yalnızca ilk kurulum varsayılanlarıdır.
 */

date_default_timezone_set('Europe/Istanbul');

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('DB_FILE', DATA_DIR . '/gsprojeler.sqlite');
define('APP_DEBUG', false);

// İlk kurulumda settings tablosuna yazılan varsayılanlar
const DEFAULT_SETTINGS = [
    'site_name'        => 'GS Projeler',
    'site_slogan'      => 'Sporda profesyonel danışmanlık',
    'company_title'    => 'GS Projeler Spor Danışmanlık Ltd. Şti.',
    'company_address'  => 'Örnek Mah. Spor Cad. No: 1 Kat: 3, 34000 İstanbul',
    'company_phone'    => '0850 000 00 00',
    'company_email'    => 'info@gsprojeler.com.tr',
    'company_kep'      => 'gsprojeler@hs01.kep.tr',
    'tax_office'       => 'Mecidiyeköy',
    'tax_number'       => '0000000000',
    'mersis_number'    => '0000000000000000',
    'trade_registry'   => 'İstanbul Ticaret Sicili — 000000',
    'working_hours'    => 'Hafta içi 09:00 – 18:00',
    'iban'             => '',

    // Garanti BBVA Sanal POS
    'pos_mode'         => 'demo',          // demo | test | prod
    'pos_security'     => '3D_OOS_PAY',    // 3D_OOS_PAY (bankanın ortak ödeme sayfası) | 3D_PAY (kart formu sitede, bilgiler doğrudan bankaya gider)
    'pos_terminal_id'  => '',
    'pos_merchant_id'  => '',
    'pos_user_id'      => 'PROVAUT',
    'pos_prov_user'    => 'PROVAUT',
    'pos_prov_password'=> '',
    'pos_store_key'    => '',
];
