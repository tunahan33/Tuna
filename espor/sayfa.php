<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/widgets.php';

$slug = (string) input('s');
$page = row('SELECT * FROM pages WHERE slug = ?', [$slug]);
if (!$page) {
    http_response_code(404);
    $page = ['slug' => '', 'title' => 'Sayfa bulunamadı', 'content' => '<p>Aradığınız sayfa bulunamadı.</p>', 'updated_at' => null];
}
$state = $page['slug'] !== '' ? widget_handle($page['slug']) : ['old' => [], 'result' => null, 'error' => null];

$contractVars = [
    'alici_ad' => '(Sipariş sırasında doldurulur)', 'alici_adres' => '(Sipariş sırasında doldurulur)',
    'alici_telefon' => '(Sipariş sırasında doldurulur)', 'alici_eposta' => '(Sipariş sırasında doldurulur)',
    'hizmet_adi' => '(Seçilen koçluk)', 'paket_adi' => '(Seçilen paket)', 'paket_sure' => '(Paket süresi)',
    'toplam_tutar' => '(Paket fiyatı, KDV dahil)', 'siparis_tarihi' => '(Sipariş tarihi)',
];
$groups = [
    'Kurumsal' => ['hakkimizda', 'insan-kaynaklari'],
    'Müşteri Hizmetleri' => ['sikca-sorulan-sorular', 'siparis-takibi', 'ariza-takibi', 'iade-ve-iade-ceki-kosullari', 'teslimat-kosullari', 'guvenli-alisveris'],
    'Sözleşmeler ve Yasal' => ['uyelik-sozlesmesi', 'genel-aydinlatma-metni', 'cerez-politikasi', 'cerez-tercihleri', 'ilgili-kisi-basvuru-formu', 'mesafeli-satis-sozlesmesi', 'on-bilgilendirme-formu', 'gizlilik-sozlesmesi'],
];
$section = '';
foreach ($groups as $g => $slugs) {
    if (in_array($page['slug'], $slugs, true)) {
        $section = $g;
        $titles = [];
        foreach (rows('SELECT slug, title FROM pages WHERE slug IN (' . implode(',', array_fill(0, count($slugs), '?')) . ')', $slugs) as $r) {
            $titles[$r['slug']] = $r['title'];
        }
        $siblings = array_filter(array_map(fn($s) => isset($titles[$s]) ? [$s, $titles[$s]] : null, $slugs));
    }
}
$isFaq = $page['slug'] === 'sikca-sorulan-sorular';
$pageTitle = $page['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a><?= $section ? ' / ' . e($section) : '' ?> / <?= e($page['title']) ?></nav>
    <h1><?= e($page['title']) ?></h1>
</div></section>
<section class="section"><div class="container <?= $section ? 'page-layout' : 'narrow' ?>">
    <?php if ($section): ?>
    <aside class="page-side">
        <h4><?= e($section) ?></h4>
        <nav>
            <?php foreach ($siblings as [$s, $t]): ?><a href="<?= url('sayfa.php?s=' . $s) ?>" class="<?= $s === $page['slug'] ? 'active' : '' ?>"><?= e($t) ?></a><?php endforeach; ?>
            <?php if ($section === 'Müşteri Hizmetleri'): ?><a href="<?= url('iletisim.php') ?>">İletişim</a><?php endif; ?>
        </nav>
    </aside>
    <?php endif; ?>
    <div>
        <article class="prose card legal" <?= $isFaq ? 'data-faq' : '' ?>><?= fill_placeholders($page['content'], $contractVars) ?></article>
        <?php widget_render($page['slug'], $state); ?>
        <?php if (!empty($page['updated_at'])): ?><p class="small muted mt-1">Son güncelleme: <?= tr_date($page['updated_at'], false) ?></p><?php endif; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
