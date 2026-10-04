<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$s = row('SELECT * FROM services WHERE slug = ? AND is_active = 1', [input('slug')]);
if (!$s) {
    http_response_code(404);
    $pageTitle = 'Sayfa bulunamadı';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container center"><h1>Hizmet bulunamadı</h1><a class="btn btn-primary" href="' . url('hizmetler.php') . '">Tüm Hizmetler</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$packages = rows('SELECT p.*, ? AS service_title FROM packages p WHERE service_id = ? AND is_active = 1 ORDER BY sort_order, price', [$s['title'], $s['id']]);
$others = rows('SELECT slug, title, icon FROM services WHERE is_active = 1 AND id <> ? ORDER BY sort_order', [$s['id']]);
$pageTitle = $s['title'];
$pageDesc = $s['short_desc'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <a href="<?= url('hizmetler.php') ?>">Hizmetlerimiz</a> / <?= e($s['title']) ?></nav>
    <div class="page-hero-flex">
        <span class="service-icon lg"><?= service_icon($s['icon']) ?></span>
        <div><h1><?= e($s['title']) ?></h1><p><?= e($s['short_desc']) ?></p></div>
    </div>
</div></section>

<section class="section"><div class="container detail-grid">
    <div class="prose"><?= $s['description'] ?></div>
    <aside class="side-card">
        <h3>Hizmetin öne çıkanları</h3>
        <ul class="check-list"><?php foreach (features_list($s['highlights']) as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ul>
        <a href="#paketler" class="btn btn-primary btn-block">Paketleri Gör</a>
        <a href="<?= url('iletisim.php?konu=' . urlencode($s['title'])) ?>" class="btn btn-outline btn-block">Soru Sor</a>
    </aside>
</div></section>

<section class="section section-muted" id="paketler"><div class="container">
    <div class="section-head">
        <span class="eyebrow">Paketler</span>
        <h2><?= e($s['title']) ?> paketleri</h2>
        <p>Paket kartına tıklayarak tüm detayları görebilirsiniz. Fiyatlara KDV dahildir.</p>
    </div>
    <div class="pkg-grid"><?php foreach ($packages as $p) echo package_card($p); ?></div>
</div></section>

<?php if ($others): ?>
<section class="section"><div class="container">
    <h3 class="mb-1">Diğer hizmetlerimiz</h3>
    <div class="chip-row"><?php foreach ($others as $o): ?><a class="chip" href="<?= url('hizmet.php?slug=' . urlencode($o['slug'])) ?>"><?= service_icon($o['icon']) ?><?= e($o['title']) ?></a><?php endforeach; ?></div>
</div></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
