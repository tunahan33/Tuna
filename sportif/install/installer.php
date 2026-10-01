<?php
/** Kurulum işlemleri: tabloları oluşturur, başlangıç verilerini ekler, config.php yazar */

require_once __DIR__ . '/schema.php';

function install_run(array $config, array $admin): void
{
    $GLOBALS['__config'] = $config;
    require_once dirname(__DIR__) . '/includes/db.php';
    if (!function_exists('config')) {
        require_once dirname(__DIR__) . '/includes/functions.php';
    }
    date_default_timezone_set($config['timezone']);

    $pdo = db();
    foreach (schema_sql($config['db']['driver']) as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // İndeks zaten varsa yoksay
            if (!str_starts_with($sql, 'CREATE INDEX')) {
                throw $e;
            }
        }
    }

    $t = date('Y-m-d H:i:s');

    // Süper admin
    $email = mb_strtolower(trim($admin['email']));
    if (!row('SELECT id FROM users WHERE email = ?', [$email])) {
        insert('users', [
            'name' => $admin['name'], 'email' => $email, 'phone' => $admin['phone'] ?? null,
            'password_hash' => password_hash($admin['password'], PASSWORD_DEFAULT),
            'role' => 'super_admin', 'status' => 'active', 'created_at' => $t,
        ]);
    }

    // Kategoriler ve ürünler
    if ((int) val('SELECT COUNT(*) FROM categories') === 0) {
        $seed = require __DIR__ . '/seed_products.php';
        $catIds = [];
        foreach ($seed['categories'] as $i => [$slug, $name, $desc, $color]) {
            $catIds[$slug] = insert('categories', ['slug' => $slug, 'name' => $name, 'description' => $desc, 'color' => $color, 'sort_order' => $i + 1, 'is_active' => 1, 'created_at' => $t, 'updated_at' => $t]);
        }
        foreach ($seed['products'] as $i => [$cat, $name, $short, $desc, $features, $gender, $price, $old, $art, $artColor, $pers, $persPrice, $featured, $sizes, $colors, $stock]) {
            $pid = insert('products', [
                'category_id' => $catIds[$cat], 'slug' => slugify($name), 'name' => $name, 'short_desc' => $short, 'description' => $desc,
                'features' => $features, 'gender' => $gender, 'price' => $price, 'old_price' => $old, 'images' => '[]', 'art' => $art,
                'art_color' => $artColor, 'personalizable' => $pers, 'personalization_price' => $persPrice, 'is_featured' => $featured,
                'is_active' => 1, 'sort_order' => $i + 1, 'created_at' => $t, 'updated_at' => $t,
            ]);
            $n = 0;
            foreach ($colors as [$cName, $cHex]) {
                foreach ($sizes as $size) {
                    insert('product_variants', ['product_id' => $pid, 'size' => $size, 'color' => $cName, 'color_hex' => $cHex,
                        'sku' => 'GS' . $pid . '-' . strtoupper(substr(slugify($cName), 0, 3)) . '-' . preg_replace('/[^A-Z0-9]/', '', strtoupper(slugify($size))),
                        'stock' => $stock, 'sort_order' => $n++]);
                }
            }
        }
    }

    // Yasal sayfalar
    if ((int) val('SELECT COUNT(*) FROM pages') === 0) {
        $pages = require __DIR__ . '/seed_pages.php';
        foreach ($pages as $i => $p) {
            insert('pages', [
                'slug' => $p['slug'], 'title' => $p['title'], 'content' => $p['content'],
                'show_in_footer' => $p['footer'], 'sort_order' => $i + 1, 'updated_at' => $t,
            ]);
        }
    }

    // Varsayılan ayarlar
    $defaults = [
        'site_name' => 'GS Sportif',
        'site_slogan' => 'Sahada fark yaratanların giyimi.',
        'site_description' => 'GS Sportif; forma, antrenman giyim, eşofman, şort, sweatshirt ve spor aksesuarlarında kaliteli ve uygun fiyatlı ürünler sunar. Takımlar için isim-numara baskılı toplu sipariş.',
        'company_title' => 'GS Sportif (Ünvanınızı girin)',
        'company_address' => 'Adresinizi yönetim panelinden girin',
        'company_phone' => '+90 (___) ___ __ __',
        'company_email' => 'info@gssportifurunler.com',
        'company_whatsapp' => '',
        'tax_office' => '-',
        'tax_number' => '-',
        'mersis_number' => '-',
        'kep_address' => '-',
        'working_hours' => 'Hafta içi 09:00 - 18:00',
        'instagram' => '', 'linkedin' => '', 'youtube' => '',
        'pos_mode' => 'demo',
        'garanti_merchant_id' => '', 'garanti_terminal_id' => '', 'garanti_prov_user' => 'PROVAUT',
        'garanti_prov_password' => '', 'garanti_store_key' => '', 'garanti_security_level' => '3D_OOS_PAY',
        'notify_email' => $email,
        'mail_driver' => 'mail', 'smtp_host' => 'mail.gssportifurunler.com', 'smtp_port' => '465', 'smtp_secure' => 'ssl', 'smtp_user' => 'info@gssportifurunler.com', 'smtp_pass' => '', 'smtp_from' => 'info@gssportifurunler.com',
        'force_https' => '0',
        'shipping_fee' => '89.90', 'free_shipping_limit' => '1500', 'shipping_days' => '1-3 iş günü',
        'cargo_companies' => "Yurtiçi Kargo|https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code={no}\nAras Kargo|https://www.araskargo.com.tr/trs_gonderi_sorgula.aspx?kargo_takip_no={no}\nMNG Kargo|https://www.mngkargo.com.tr/gonderi-takip/?code={no}\nPTT Kargo|https://gonderitakip.ptt.gov.tr/Track/Verify?q={no}\nSürat Kargo|https://www.suratkargo.com.tr/KargoTakip/?kargotakipno={no}",
        'low_stock_limit' => '3', 'team_min_qty' => '10',
    ];
    foreach ($defaults as $k => $v) {
        if (!row('SELECT skey FROM settings WHERE skey = ?', [$k])) {
            q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$k, $v]);
        }
    }

    q('INSERT INTO activity_log (user_id, user_name, user_role, action, entity, details, ip, created_at) VALUES (?,?,?,?,?,?,?,?)',
        [null, 'Sistem', 'system', 'Kurulum tamamlandı', 'system', 'Mağaza kuruldu, süper admin oluşturuldu: ' . $email, $_SERVER['REMOTE_ADDR'] ?? 'cli', $t]);
}

function install_write_config(array $config): void
{
    $php = "<?php\n// GS Projeler yapılandırması - kurulum sihirbazı tarafından oluşturuldu (" . date('d.m.Y H:i') . ")\nreturn " . var_export($config, true) . ";\n";
    file_put_contents(dirname(__DIR__) . '/config.php', $php);
    @chmod(dirname(__DIR__) . '/config.php', 0640);
}
