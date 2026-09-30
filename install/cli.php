<?php
/**
 * Yerel geliştirme için komut satırından kurulum (SQLite ile).
 * Kullanım: php install/cli.php
 * Ardından: php -S localhost:8000   ->  http://localhost:8000
 */
if (PHP_SAPI !== 'cli') {
    exit('Yalnızca komut satırından çalıştırılabilir.');
}
require __DIR__ . '/installer.php';

$root = dirname(__DIR__);
$config = [
    'db' => ['driver' => 'sqlite', 'host' => '', 'port' => 0, 'name' => '', 'user' => '', 'pass' => '', 'sqlite' => $root . '/storage/database.sqlite'],
    'base_url' => $argv[1] ?? 'http://localhost:8000',
    'app_key' => bin2hex(random_bytes(32)),
    'timezone' => 'Europe/Istanbul',
    'debug' => true,
];
$admin = ['name' => 'Süper Admin', 'email' => 'admin@gsprojeler.local', 'password' => 'Admin123!'];

install_run($config, $admin);
install_write_config($config);
touch(__DIR__ . '/install.lock');
echo "Kurulum tamam.\nGiriş: {$admin['email']} / {$admin['password']}\nÇalıştırmak için: php -S localhost:8000\n";
