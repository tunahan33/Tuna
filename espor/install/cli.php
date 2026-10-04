<?php
/**
 * Yerel geliştirme için komut satırından kurulum (SQLite ile).
 * Kullanım: php install/cli.php
 * Ardından: php -S localhost:8000   ->  http://localhost:8000
 */
if (PHP_SAPI !== 'cli') {
    exit('Yalnızca komut satırından çalıştırılabilir.');
}
$missing = array_filter(['pdo_sqlite', 'mbstring', 'openssl'], fn($x) => !extension_loaded($x));
if ($missing) {
    echo "Eksik PHP eklentisi: " . implode(', ', $missing) . "\n";
    echo "php.ini dosyanizi acip su satirlarin basindaki ; isaretini kaldirin:\n";
    foreach ($missing as $x) echo "  extension=$x\n";
    echo "php.ini konumu: " . (php_ini_loaded_file() ?: '(yok - PHP klasorundeki php.ini-development dosyasini php.ini adiyla kopyalayin)') . "\n";
    exit(1);
}
if (file_exists(dirname(__DIR__) . '/config.php') && !in_array('--yeniden', $argv, true)) {
    exit("Site zaten kurulu. Sifirdan kurmak icin: php install/cli.php --yeniden\n");
}
if (in_array('--yeniden', $argv, true)) {
    @unlink(dirname(__DIR__) . '/config.php');
    @unlink(dirname(__DIR__) . '/storage/database.sqlite');
    @unlink(__DIR__ . '/install.lock');
}
require __DIR__ . '/installer.php';

$root = dirname(__DIR__);
$config = [
    'db' => ['driver' => 'sqlite', 'host' => '', 'port' => 0, 'name' => '', 'user' => '', 'pass' => '', 'sqlite' => $root . '/storage/database.sqlite'],
    'base_url' => 'http://localhost:8000',
    'app_key' => bin2hex(random_bytes(32)),
    'timezone' => 'Europe/Istanbul',
    'debug' => true,
];
$admin = ['name' => 'Süper Admin', 'email' => 'admin@gssportif.local', 'password' => 'Admin123!'];

install_run($config, $admin);
install_write_config($config);
touch(__DIR__ . '/install.lock');
echo "Kurulum tamam.\nGiriş: {$admin['email']} / {$admin['password']}\nÇalıştırmak için: php -S localhost:8000\n";
