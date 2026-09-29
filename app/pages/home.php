<?php
require_once __DIR__ . '/services.php';

function page_home(): void
{
    $services = rows('SELECT s.*, (SELECT MIN(price) FROM packages p WHERE p.service_id = s.id AND p.is_active = 1) AS min_price
                      FROM services s WHERE s.is_active = 1 ORDER BY s.sort');
    $featured = rows('SELECT p.*, s.title AS service_title, s.slug AS service_slug, s.icon FROM packages p JOIN services s ON s.id = p.service_id
                      WHERE p.is_active = 1 AND s.is_active = 1 AND p.is_featured = 1 ORDER BY s.sort LIMIT 3');

    render(setting('site_name'), function () use ($services, $featured) { ?>
<section class="hero">
  <div class="hero-stripes" aria-hidden="true"><span></span><span></span></div>
  <div class="container hero-inner">
    <div class="hero-text">
      <span class="eyebrow"><span class="dot"></span> Spor danışmanlığında yeni standart</span>
      <h1>Sahadaki başarı,<br><span class="hl">doğru stratejiyle</span> başlar.</h1>
      <p class="hero-lead">Sporcudan kulübe, tesis yatırımcısından markaya; <?= count($services) ?> farklı alanda bilimsel, ölçülebilir ve şeffaf fiyatlı danışmanlık paketleri.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= url('hizmetler') ?>">Danışmanlıkları Keşfet <?= icon('arrow') ?></a>
        <a class="btn btn-outline-light btn-lg" href="<?= url('iletisim') ?>">Ücretsiz ön görüşme</a>
      </div>
      <div class="hero-trust">
        <div><strong>250+</strong><span>Danışan sporcu</span></div>
        <div><strong>40+</strong><span>Kulüp & kurum</span></div>
        <div><strong>%100</strong><span>Güvenli ödeme</span></div>
      </div>
    </div>
    <div class="hero-card" aria-hidden="true">
      <div class="hc-head"><?= logo_svg('logo-mark', false) ?><div><strong>Performans Raporu</strong><span>Haftalık gelişim</span></div></div>
      <div class="hc-bars">
        <?php foreach ([42, 55, 48, 67, 72, 80, 91] as $i => $h): ?><span style="--h:<?= $h ?>%" class="<?= $i === 6 ? 'on' : '' ?>"></span><?php endforeach; ?>
      </div>
      <div class="hc-stats">
        <div><span>Sprint</span><strong>+12%</strong></div>
        <div><span>Dayanıklılık</span><strong>+18%</strong></div>
        <div><span>Odak</span><strong>+24%</strong></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="kicker">Danışmanlık Alanlarımız</span>
      <h2>İhtiyacınıza uygun danışmanlığı seçin</h2>
      <p class="muted">Her danışmanlık alanında net kapsamlı ve net fiyatlı paketler. Detaylar için karta tıklayın.</p>
    </div>
    <div class="service-grid">
      <?php foreach ($services as $s): ?>
        <a class="service-card" href="<?= url('hizmet/' . $s['slug']) ?>">
          <span class="sc-icon"><?= icon($s['icon']) ?></span>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e($s['short']) ?></p>
          <span class="sc-foot"><span><?= $s['min_price'] ? money_short((int)$s['min_price']) . "'den başlayan" : '' ?></span><?= icon('arrow') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <span class="kicker">En çok tercih edilenler</span>
      <h2>Öne çıkan paketler</h2>
    </div>
    <div class="package-grid">
      <?php foreach ($featured as $p) package_card($p, true); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="kicker">Nasıl çalışıyoruz?</span>
      <h2>4 adımda profesyonel destek</h2>
    </div>
    <div class="steps">
      <div class="step"><span>01</span><h3>Paketi seçin</h3><p>İhtiyacınıza uygun danışmanlık paketini net fiyatıyla inceleyin.</p></div>
      <div class="step"><span>02</span><h3>Güvenle ödeyin</h3><p>Garanti BBVA 3D Secure altyapısıyla kartınızla güvenle ödeme yapın.</p></div>
      <div class="step"><span>03</span><h3>Tanışalım</h3><p>1 iş günü içinde danışmanınız sizi arar, ilk görüşmeyi planlar.</p></div>
      <div class="step"><span>04</span><h3>Gelişimi izleyin</h3><p>Düzenli raporlarla ilerlemenizi ölçülebilir şekilde takip edin.</p></div>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container why">
    <div>
      <span class="kicker kicker-light">Neden GS Projeler?</span>
      <h2>Spor bilimi, yönetim ve pazarlama<br>tek çatı altında.</h2>
      <p>Her danışmanlık alanında konusunda uzman bir ekiple çalışıyoruz. Paketlerimiz net kapsamlıdır; ne alacağınızı ve ne ödeyeceğinizi baştan bilirsiniz.</p>
      <a class="btn btn-primary" href="<?= url('hakkimizda') ?>">Bizi tanıyın <?= icon('arrow') ?></a>
    </div>
    <ul class="why-list">
      <li><?= icon('check') ?><div><strong>Bilimsel yöntem</strong><span>Tüm programlar ölçüm ve veriye dayanır.</span></div></li>
      <li><?= icon('check') ?><div><strong>Şeffaf fiyat</strong><span>KDV dahil net fiyatlar, sürpriz maliyet yok.</span></div></li>
      <li><?= icon('check') ?><div><strong>14 gün cayma hakkı</strong><span>Hizmet başlamadan koşulsuz iade.</span></div></li>
      <li><?= icon('check') ?><div><strong>Güvenli ödeme</strong><span>3D Secure, kart bilgisi saklanmaz.</span></div></li>
    </ul>
  </div>
</section>

<section class="section">
  <div class="container cta-box">
    <div>
      <h2>Hangi paketin size uygun olduğundan emin değil misiniz?</h2>
      <p>Ücretsiz 15 dakikalık ön görüşmede ihtiyacınızı birlikte belirleyelim.</p>
    </div>
    <a class="btn btn-dark btn-lg" href="<?= url('iletisim') ?>">Ön görüşme talep et</a>
  </div>
</section>
<?php
    });
}
