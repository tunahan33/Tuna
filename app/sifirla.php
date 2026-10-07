<?php
/**
 * Veritabanını siler ve demo verileriyle yeniden oluşturur.
 * Kullanım (VS Code terminali):  php app/sifirla.php
 * DİKKAT: Tüm siparişler, üyeler ve yaptığınız değişiklikler silinir!
 */
if (PHP_SAPI !== 'cli') {
    exit('Yalnızca terminalden çalıştırılabilir.');
}
$file = dirname(__DIR__) . '/data/gs.sqlite';
foreach ([$file, "$file-wal", "$file-shm"] as $f) {
    if (is_file($f)) unlink($f);
}
require __DIR__ . '/bootstrap.php';
db();
echo "Veritabanı sıfırlandı ve demo veriler yüklendi.\n";
echo "Süper admin: admin@gssportif.local / Admin123!\n";
