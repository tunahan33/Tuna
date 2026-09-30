<?php
/** Paket detayı: tam sayfa veya ?ajax=1 ile açılır pencere (modal) içeriği */
require __DIR__ . '/includes/bootstrap.php';

$p = row('SELECT p.*, s.title AS service_title, s.slug AS service_slug FROM packages p JOIN services s ON s.id = p.service_id WHERE p.id = ? AND p.is_active = 1 AND s.is_active = 1', [(int) input('id')]);
$ajax = input('ajax') === '1';

if (!$p) {
    http_response_code(404);
    exit($ajax ? '<p>Paket bulunamadı.</p>' : 'Paket bulunamadı.');
}

ob_start(); ?>
<div class="pkg-detail">
    <div class="pkg-detail-head">
        <span class="eyebrow"><?= e($p['service_title']) ?></span>
        <h2 id="pm-title"><?= e($p['name']) ?></h2>
        <div class="pkg-meta"><span>Süre: <?= e($p['duration']) ?></span><?php if ($p['sessions']): ?><span><?= e($p['sessions']) ?></span><?php endif; ?></div>
    </div>
    <div class="pkg-detail-body">
        <div class="prose"><?= $p['description'] ?></div>
        <h4>Paket içeriği</h4>
        <ul class="check-list"><?php foreach (features_list($p['features']) as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
        <div class="info-box">
            <strong>Nasıl ilerliyor?</strong> Ödemeniz onaylandıktan sonra en geç 3 iş günü içinde danışmanınız sizinle iletişime geçer ve ilk görüşmeyi planlar. Program ve raporlar e-posta ile teslim edilir.
        </div>
    </div>
    <div class="pkg-detail-foot">
        <div class="pkg-price">
            <?php if ($p['old_price'] && $p['old_price'] > $p['price']): ?><del><?= money($p['old_price']) ?></del><?php endif; ?>
            <strong><?= money($p['price']) ?></strong><small>KDV dahil · Tek çekim</small>
        </div>
        <a class="btn btn-primary btn-lg" href="<?= url('odeme.php?paket=' . (int) $p['id']) ?>">Hemen Satın Al</a>
    </div>
    <p class="small muted">Satın alma öncesi <a href="<?= url('sayfa.php?s=on-bilgilendirme-formu') ?>" target="_blank">Ön Bilgilendirme Formu</a>, <a href="<?= url('sayfa.php?s=mesafeli-satis-sozlesmesi') ?>" target="_blank">Mesafeli Satış Sözleşmesi</a> ve <a href="<?= url('sayfa.php?s=iptal-ve-iade-kosullari') ?>" target="_blank">İptal ve İade Koşulları</a>'nı inceleyebilirsiniz.</p>
</div>
<?php
$html = ob_get_clean();

if ($ajax) {
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
$pageTitle = $p['name'] . ' - ' . $p['service_title'];
$pageDesc = $p['short_desc'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <a href="<?= url('hizmet.php?slug=' . urlencode($p['service_slug'])) ?>"><?= e($p['service_title']) ?></a> / <?= e($p['name']) ?></nav>
</div></section>
<section class="section"><div class="container narrow card"><?= $html ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
