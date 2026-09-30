<?php
require __DIR__ . '/includes/bootstrap.php';

$page = row('SELECT * FROM pages WHERE slug = ?', [input('s')]);
if (!$page) {
    http_response_code(404);
    $page = ['title' => 'Sayfa bulunamadı', 'content' => '<p>Aradığınız sayfa bulunamadı.</p>', 'updated_at' => null];
}
$contractVars = [
    'alici_ad' => '(Sipariş sırasında doldurulur)', 'alici_adres' => '(Sipariş sırasında doldurulur)',
    'alici_telefon' => '(Sipariş sırasında doldurulur)', 'alici_eposta' => '(Sipariş sırasında doldurulur)',
    'hizmet_adi' => '(Seçilen hizmet)', 'paket_adi' => '(Seçilen paket)', 'paket_sure' => '(Paket süresi)',
    'toplam_tutar' => '(Paket fiyatı, KDV dahil)', 'siparis_tarihi' => '(Sipariş tarihi)',
];
$pageTitle = $page['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <?= e($page['title']) ?></nav>
    <h1><?= e($page['title']) ?></h1>
</div></section>
<section class="section"><div class="container narrow">
    <article class="prose card legal"><?= fill_placeholders($page['content'], $contractVars) ?>
        <?php if (!empty($page['updated_at'])): ?><p class="small muted mt-2">Son güncelleme: <?= tr_date($page['updated_at'], false) ?></p><?php endif; ?>
    </article>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
