<?php
require __DIR__ . '/includes/bootstrap.php';
$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$pageTitle = 'Koçluk Hizmetleri';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Koçluk Hizmetleri</nav>
    <h1>E-Spor Koçluk Hizmetlerimiz</h1>
    <p>Oyuncular, takımlar ve içerik üreticileri için <?= count($services) ?> farklı alanda profesyonel e-spor koçluğu.</p>
</div></section>
<section class="section"><div class="container">
    <div class="service-list">
        <?php foreach ($services as $s): ?>
            <a class="service-row" href="<?= url('hizmet.php?slug=' . urlencode($s['slug'])) ?>">
                <span class="service-icon"><?= service_icon($s['icon']) ?></span>
                <div>
                    <h2><?= e($s['title']) ?></h2>
                    <p><?= e($s['short_desc']) ?></p>
                    <ul class="tag-list"><?php foreach (features_list($s['highlights']) as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ul>
                </div>
                <div class="service-row-price">
                    <?php $min = val('SELECT MIN(price) FROM packages WHERE service_id = ? AND is_active = 1', [$s['id']]); ?>
                    <?php if ($min): ?><small>Başlangıç</small><strong><?= money($min) ?></strong><?php endif; ?>
                    <span class="btn btn-primary btn-sm">Detay ve Paketler</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
