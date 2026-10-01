<?php
/** Veritabanı yedeği indirme - YALNIZCA SÜPER ADMIN */
require __DIR__ . '/_init.php';
$u = require_perm('settings.edit');

if (!is_post()) {
    redirect('admin/settings.php?tab=server');
}
verify_csrf();
@set_time_limit(300);
$stamp = date('Y-m-d_H-i');
log_activity('Veritabanı yedeği indirdi', 'Yedek dosyası: gsprojeler-yedek-' . $stamp, 'settings');

if (config('db.driver') === 'sqlite') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="gsprojeler-yedek-' . $stamp . '.sqlite"');
    readfile(config('db.sqlite'));
    exit;
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="gsprojeler-yedek-' . $stamp . '.sql"');
$pdo = db();
echo "-- GS Projeler veritabanı yedeği\n-- Tarih: " . date('d.m.Y H:i:s') . "\n-- Geri yükleme: phpMyAdmin > İçe Aktar\n\n";
echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    echo "DROP TABLE IF EXISTS `$table`;\n$create;\n\n";
    $st = $pdo->query("SELECT * FROM `$table`");
    $batch = [];
    while ($r = $st->fetch(PDO::FETCH_NUM)) {
        $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $r)) . ')';
        if (count($batch) === 200) {
            echo "INSERT INTO `$table` VALUES\n" . implode(",\n", $batch) . ";\n";
            $batch = [];
        }
    }
    if ($batch) {
        echo "INSERT INTO `$table` VALUES\n" . implode(",\n", $batch) . ";\n";
    }
    echo "\n";
}
echo "SET FOREIGN_KEY_CHECKS = 1;\n";
