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

    // Hizmetler ve paketler
    if ((int) val('SELECT COUNT(*) FROM services') === 0) {
        $services = require __DIR__ . '/seed_services.php';
        foreach ($services as $i => $s) {
            $sid = insert('services', [
                'slug' => $s['slug'], 'title' => $s['title'], 'short_desc' => $s['short_desc'],
                'description' => $s['description'], 'highlights' => $s['highlights'], 'icon' => $s['icon'],
                'sort_order' => $i + 1, 'is_active' => 1, 'created_at' => $t, 'updated_at' => $t,
            ]);
            foreach ($s['packages'] as $j => [$name, $price, $old, $duration, $sessions, $short, $desc, $features, $featured]) {
                insert('packages', [
                    'service_id' => $sid, 'name' => $name, 'price' => $price, 'old_price' => $old,
                    'duration' => $duration, 'sessions' => $sessions, 'short_desc' => $short,
                    'description' => $desc, 'features' => $features, 'is_featured' => $featured,
                    'sort_order' => $j + 1, 'is_active' => 1, 'created_at' => $t, 'updated_at' => $t,
                ]);
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
        'site_name' => 'GS Projeler',
        'site_slogan' => 'Sporda başarıyı planlıyoruz.',
        'site_description' => 'GS Projeler; sporcu performans, beslenme, kulüp yönetimi, kariyer, tesis projelendirme ve mental koçluk alanlarında profesyonel spor danışmanlığı hizmeti sunar.',
        'company_title' => 'GS Projeler Spor Danışmanlık (Ünvanınızı girin)',
        'company_address' => 'Adresinizi yönetim panelinden girin',
        'company_phone' => '+90 (___) ___ __ __',
        'company_email' => 'info@gsprojeler.com',
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
        'mail_driver' => 'mail', 'smtp_host' => 'mail.gsprojeler.com', 'smtp_port' => '465', 'smtp_secure' => 'ssl', 'smtp_user' => 'info@gsprojeler.com', 'smtp_pass' => '', 'smtp_from' => 'info@gsprojeler.com',
        'force_https' => '0',
        'transfer_enabled' => '0', 'bank_name' => '', 'bank_account_holder' => '', 'bank_iban' => '', 'transfer_days' => '3',
        'bank_note' => 'Ödemeniz hesabımıza ulaştığında siparişiniz onaylanır ve e-posta ile bilgilendirilirsiniz. Ödeme 3 gün içinde yapılmazsa sipariş iptal edilebilir.',
    ];
    foreach ($defaults as $k => $v) {
        if (!row('SELECT skey FROM settings WHERE skey = ?', [$k])) {
            q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$k, $v]);
        }
    }

    q('INSERT INTO activity_log (user_id, user_name, user_role, action, entity, details, ip, created_at) VALUES (?,?,?,?,?,?,?,?)',
        [null, 'Sistem', 'system', 'Kurulum tamamlandı', 'system', 'Site kuruldu, süper admin oluşturuldu: ' . $email, $_SERVER['REMOTE_ADDR'] ?? 'cli', $t]);
}

function install_write_config(array $config): void
{
    $php = "<?php\n// GS Projeler yapılandırması - kurulum sihirbazı tarafından oluşturuldu (" . date('d.m.Y H:i') . ")\nreturn " . var_export($config, true) . ";\n";
    file_put_contents(dirname(__DIR__) . '/config.php', $php);
    @chmod(dirname(__DIR__) . '/config.php', 0640);
}
