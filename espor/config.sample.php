<?php
/**
 * GS Sportif Faaliyetler - Yapılandırma dosyası
 * Kurulum sihirbazı (install/) bu dosyayı otomatik olarak config.php adıyla oluşturur.
 * Elle kurulum için bu dosyayı config.php olarak kopyalayıp değerleri doldurun.
 */
return [
    'db' => [
        'driver'   => 'mysql',          // mysql | sqlite (sqlite yalnızca yerel test için)
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'veritabani_adi',
        'user'     => 'veritabani_kullanici',
        'pass'     => 'veritabani_sifre',
        'sqlite'   => __DIR__ . '/storage/database.sqlite',
    ],
    // Sitenin tam adresi, sonunda / olmadan
    'base_url' => 'https://www.gssportif.com',
    // Rastgele uzun bir anahtar (kurulumda otomatik üretilir)
    'app_key'  => 'BURAYA-RASTGELE-UZUN-BIR-ANAHTAR',
    'timezone' => 'Europe/Istanbul',
    'debug'    => false,
];
