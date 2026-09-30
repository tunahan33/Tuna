<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$pageTitle = 'Paketler ve Fiyatlar';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Paketler &amp; Fiyatlar</nav>
    <h1>Paketler ve Fiyatlar</h1>
    <p>Tüm danışmanlık paketlerimiz net fiyatlıdır. Fiyatlara KDV dahildir, ek ücret alınmaz.</p>
    <div class="chip-row mt-1">
        <?php foreach ($services as $s): ?><a class="chip light" href="#<?= e($s['slug']) ?>"><?= service_icon($s['icon']) ?><?= e($s['title']) ?></a><?php endforeach; ?>
    </div>
</div></section>
<?php foreach ($services as $i => $s):
    $packages = rows('SELECT p.*, ? AS service_title FROM packages p WHERE service_id = ? AND is_active = 1 ORDER BY sort_order, price', [$s['title'], $s['id']]);
    if (!$packages) continue; ?>
<section class="section <?= $i % 2 ? 'section-muted' : '' ?>" id="<?= e($s['slug']) ?>"><div class="container">
    <div class="section-head left">
        <h2><a href="<?= url('hizmet.php?slug=' . urlencode($s['slug'])) ?>"><?= e($s['title']) ?></a></h2>
        <p><?= e($s['short_desc']) ?></p>
    </div>
    <div class="pkg-grid"><?php foreach ($packages as $p) echo package_card($p); ?></div>
</div></section>
<?php endforeach; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
