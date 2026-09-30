<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$featured = rows('SELECT p.*, s.title AS service_title FROM packages p JOIN services s ON s.id = p.service_id WHERE p.is_active = 1 AND s.is_active = 1 AND p.is_featured = 1 ORDER BY s.sort_order LIMIT 6');
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <span class="eyebrow">Profesyonel Spor Danışmanlığı</span>
            <h1>Sporda başarıyı <span class="hl-yellow">tesadüfe</span> değil, <span class="hl-red">plana</span> bırakın.</h1>
            <p><?= e(setting('site_description')) ?></p>
            <div class="hero-cta">
                <a href="<?= url('hizmetler.php') ?>" class="btn btn-primary btn-lg">Hizmetleri Keşfet</a>
                <a href="<?= url('paketler.php') ?>" class="btn btn-ghost btn-lg">Paketler &amp; Fiyatlar</a>
            </div>
            <ul class="hero-stats">
                <li><strong><?= count($services) ?></strong><span>Danışmanlık alanı</span></li>
                <li><strong>3 iş günü</strong><span>İçinde hizmete başlangıç</span></li>
                <li><strong>3D Secure</strong><span>Güvenli ödeme</span></li>
            </ul>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="hv-card hv-1"><?= service_icon('performance') ?><span>Performans</span></div>
            <div class="hv-card hv-2"><?= service_icon('club') ?><span>Kulüp Yönetimi</span></div>
            <div class="hv-card hv-3"><?= service_icon('facility') ?><span>Tesis Projeleri</span></div>
            <div class="hv-ring"></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Hizmetlerimiz</span>
            <h2>Spora dair her ihtiyacınız için uzman danışmanlık</h2>
            <p>Bir hizmete tıklayarak kapsamını, çalışma şeklini ve paket fiyatlarını inceleyebilirsiniz.</p>
        </div>
        <div class="service-grid">
            <?php foreach ($services as $s): ?>
                <a class="service-card" href="<?= url('hizmet.php?slug=' . urlencode($s['slug'])) ?>">
                    <span class="service-icon"><?= service_icon($s['icon']) ?></span>
                    <h3><?= e($s['title']) ?></h3>
                    <p><?= e($s['short_desc']) ?></p>
                    <?php $min = val('SELECT MIN(price) FROM packages WHERE service_id = ? AND is_active = 1', [$s['id']]); ?>
                    <span class="service-foot"><?php if ($min): ?><span>Başlangıç <strong><?= money($min) ?></strong></span><?php endif; ?><span class="arrow">İncele →</span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="section-head light">
            <span class="eyebrow">Nasıl Çalışıyoruz?</span>
            <h2>4 adımda net ve şeffaf süreç</h2>
        </div>
        <ol class="steps-grid">
            <li><span>01</span><h4>Paketinizi seçin</h4><p>Hizmet ve paket içeriklerini inceleyin; fiyatlar nettir, KDV dahildir.</p></li>
            <li><span>02</span><h4>Güvenle ödeyin</h4><p>Garanti BBVA 3D Secure altyapısıyla kredi/banka kartınızla ödeme yapın.</p></li>
            <li><span>03</span><h4>Danışmanınız arasın</h4><p>En geç 3 iş günü içinde danışmanınız sizinle iletişime geçer.</p></li>
            <li><span>04</span><h4>Gelişimi takip edin</h4><p>Program, rapor ve görüşmelerle hedefinize adım adım ilerleyin.</p></li>
        </ol>
    </div>
</section>

<?php if ($featured): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Öne Çıkan Paketler</span>
            <h2>En çok tercih edilen danışmanlık paketleri</h2>
        </div>
        <div class="pkg-grid"><?php foreach ($featured as $p) echo package_card($p, true); ?></div>
        <div class="center mt-2"><a class="btn btn-dark" href="<?= url('paketler.php') ?>">Tüm Paketleri Gör</a></div>
    </div>
</section>
<?php endif; ?>

<section class="section section-muted">
    <div class="container why-grid">
        <div>
            <span class="eyebrow">Neden GS Projeler?</span>
            <h2>Veriyle çalışan, sonuç odaklı danışmanlık</h2>
            <p>Her çalışma ölçümle başlar, hedefle planlanır ve raporla takip edilir. Hazır şablonlar yerine size özel programlar üretiriz.</p>
            <a class="btn btn-primary" href="<?= url('iletisim.php') ?>">Ücretsiz Ön Görüşme Talep Et</a>
        </div>
        <ul class="why-list">
            <li><strong>Uzman kadro</strong><span>Alanında deneyimli antrenör, diyetisyen, psikolog ve proje uzmanları.</span></li>
            <li><strong>Net fiyat</strong><span>Tüm paket fiyatları sitede açıkça yazılıdır, sürpriz ücret yoktur.</span></li>
            <li><strong>Güvenli ödeme</strong><span>Garanti BBVA sanal POS, 3D Secure ve SSL koruması.</span></li>
            <li><strong>Yasal güvence</strong><span>Mesafeli satış sözleşmesi, açık iptal ve iade koşulları.</span></li>
        </ul>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
