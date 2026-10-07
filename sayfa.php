<?php
require __DIR__ . '/app/bootstrap.php';

$page = row('SELECT * FROM pages WHERE slug = ?', [(string) input('s')]);
if (!$page) {
    http_response_code(404);
    $page = ['title' => 'Sayfa bulunamadı', 'content' => '<p>Aradığınız sayfa bulunamadı. <a href="' . e(url()) . '">Ana sayfaya dön</a></p>', 'updated_at' => null];
}
$empty = '(Sipariş sırasında doldurulur)';
$html = str_replace('{{urun_listesi}}', '<p><em>(Sipariş edilen ürünler, beden, adet ve fiyatları sipariş sırasında burada listelenir.)</em></p>', $page['content']);
$html = fill_placeholders($html, ['alici_ad' => $empty, 'alici_adres' => $empty, 'alici_telefon' => $empty, 'alici_eposta' => $empty,
    'ara_toplam' => $empty, 'kargo_bedeli' => $empty, 'toplam_tutar' => $empty, 'siparis_tarihi' => $empty]);
// Sayfa içindeki göreli bağlantıları site köküne göre düzelt
$html = preg_replace('#href="(?!https?:|mailto:|tel:|/|\#)([^"]+)"#', 'href="' . e(base_path()) . '/$1"', $html);
$title = $page['title'];
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <?= e($page['title']) ?></nav><h1><?= e($page['title']) ?></h1></div></section>
<section class="section-sm">
    <div class="container narrow">
        <article class="prose"><?= $html ?>
            <?php if ($page['updated_at']): ?><p class="small muted" style="margin-top:30px">Son güncelleme: <?= tr_date($page['updated_at'], false) ?></p><?php endif; ?>
        </article>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
