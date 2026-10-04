<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$featured = rows('SELECT p.*, s.title AS service_title FROM packages p JOIN services s ON s.id = p.service_id WHERE p.is_active = 1 AND s.is_active = 1 AND p.is_featured = 1 ORDER BY s.sort_order LIMIT 6');
$minPrice = val('SELECT MIN(price) FROM packages WHERE is_active = 1');
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <span class="eyebrow">Profesyonel E-Spor Koçluğu</span>
            <h1>Rankını <span class="hl-yellow">şansa</span> değil, <span class="hl-red">plana</span> bırak.</h1>
            <p><?= e(setting('site_description')) ?></p>
            <div class="hero-cta">
                <a href="<?= url('hizmetler.php') ?>" class="btn btn-primary btn-lg">Koçluk Türlerini Keşfet</a>
                <a href="<?= url('paketler.php') ?>" class="btn btn-ghost btn-lg">Paketler &amp; Fiyatlar</a>
            </div>
            <ul class="hero-stats">
                <li><strong><?= count($services) ?></strong><span>Koçluk türü</span></li>
                <li><strong>24 saat</strong><span>İçinde koçunuz sizi arar</span></li>
                <?php if ($minPrice): ?><li><strong><?= money($minPrice) ?></strong><span>'den başlayan paketler</span></li><?php endif; ?>
                <li><strong>3D Secure</strong><span>Güvenli ödeme</span></li>
            </ul>
            <div class="game-strip"><span>Valorant</span><span>League of Legends</span><span>CS2</span><span>PUBG Mobile</span><span>EA SPORTS FC</span></div>
        </div>
        <div class="hero-emblem" aria-hidden="true">
            <img src="<?= asset('img/emblem-3d.jpg') ?>" alt="" width="600" height="500">
            <div class="hv-card hv-1"><?= service_icon('crosshair') ?><span>Aim &amp; Mekanik</span></div>
            <div class="hv-card hv-2"><?= service_icon('trophy') ?><span>Takım &amp; Turnuva</span></div>
            <div class="hv-card hv-3"><?= service_icon('brain') ?><span>Mental Performans</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Koçluk Türleri</span>
            <h2>Oyununa ve hedefine özel e-spor koçluğu</h2>
            <p>Bir koçluk türüne tıklayarak kapsamını, çalışma şeklini ve net paket fiyatlarını inceleyebilirsin.</p>
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
            <li><span>01</span><h4>Paketini seç</h4><p>Koçluk türünü ve paketini incele; fiyatlar nettir, KDV dahildir.</p></li>
            <li><span>02</span><h4>Güvenle öde</h4><p>Garanti BBVA 3D Secure altyapısıyla kredi veya banka kartınla öde.</p></li>
            <li><span>03</span><h4>Koçun seni arasın</h4><p>24 saat içinde koçun ulaşır, VOD analiziyle ilk dersi planlar.</p></li>
            <li><span>04</span><h4>Gelişimini ölç</h4><p>Canlı dersler, analizler ve paket sonu istatistik raporuyla ilerle.</p></li>
        </ol>
    </div>
</section>

<?php if ($featured): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Öne Çıkan Paketler</span>
            <h2>En çok tercih edilen koçluk paketleri</h2>
            <p>Paket kartına tıklayarak tüm detayları görebilirsin.</p>
        </div>
        <div class="pkg-grid"><?php foreach ($featured as $p) echo package_card($p, true); ?></div>
        <div class="center mt-2"><a class="btn btn-outline" href="<?= url('paketler.php') ?>">Tüm Paketleri Gör</a></div>
    </div>
</section>
<?php endif; ?>

<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Koç Kadromuz</span>
            <h2>Her oyun için alanında uzman koçlar</h2>
            <p>Koçlarımız üst rank ve lig deneyimi, eğitmenlik becerisi ve iletişim yetkinliği değerlendirilerek seçilir.</p>
        </div>
        <div class="coach-grid">
            <div class="coach"><span class="coach-avatar"><?= service_icon('crosshair') ?></span><h4>FPS Koçları</h4><small>Valorant · CS2</small><p>Aim mekaniği, utility kullanımı, pozisyon ve IGL eğitimi.</p></div>
            <div class="coach"><span class="coach-avatar"><?= service_icon('sword') ?></span><h4>MOBA Koçları</h4><small>League of Legends</small><p>Koridor evresi, dalga yönetimi, makro ve draft stratejisi.</p></div>
            <div class="coach"><span class="coach-avatar"><?= service_icon('mobile') ?></span><h4>Mobil &amp; Spor Oyunları</h4><small>PUBG Mobile · EA SPORTS FC</small><p>Cihaz ayarı, rotasyon, taktik diziliş ve rekabetçi oyun planı.</p></div>
            <div class="coach"><span class="coach-avatar"><?= service_icon('brain') ?></span><h4>Mental Performans</h4><small>Tüm oyunlar</small><p>Tilt yönetimi, odak, maç günü rutini ve sağlıklı oyun düzeni.</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container why-grid">
        <div>
            <span class="eyebrow">Neden GS Sportif Faaliyetler?</span>
            <h2>Ölçülebilir, şeffaf ve güvenli koçluk</h2>
            <p>Her koçluk maç kaydı analiziyle başlar, kişisel planla ilerler ve istatistiklerle raporlanır. Hesap şifreni asla istemeyiz; hesabında oynamayız, sana oynamayı öğretiriz.</p>
            <a class="btn btn-primary" href="<?= url('iletisim.php') ?>">Ücretsiz Ön Görüşme Talep Et</a>
        </div>
        <ul class="why-list">
            <li><strong>VOD analizi</strong><span>Maç kayıtların zaman damgalı olarak incelenir, hataların tek tek işaretlenir.</span></li>
            <li><strong>Net fiyat</strong><span>Tüm paket fiyatları sitede açıkça yazılıdır, KDV dahildir, sürpriz ücret yoktur.</span></li>
            <li><strong>Güvenli ödeme</strong><span>Garanti BBVA sanal POS, 3D Secure ve SSL koruması.</span></li>
            <li><strong>Yasal güvence</strong><span>Mesafeli satış sözleşmesi, açık iade ve iade çeki koşulları.</span></li>
        </ul>
    </div>
</section>

<section class="section section-muted">
    <div class="container">
        <div class="cta-band">
            <div><h2>Hangi paketin sana uygun olduğundan emin değil misin?</h2><p>Oyununu ve hedefini yaz, sana en uygun koçluğu birlikte seçelim.</p></div>
            <a class="btn btn-yellow btn-lg" href="<?= url('iletisim.php?konu=' . urlencode('Paket önerisi')) ?>">Bize Yaz</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
