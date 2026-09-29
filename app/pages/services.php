<?php

function package_features(array $p): array
{
    return array_values(array_filter(array_map('trim', explode("\n", $p['features']))));
}

/** Paket kartı + tıklanınca açılan açıklama penceresi */
function package_card(array $p, bool $showService = false): void
{
    $features = package_features($p);
    $id = 'pkg-' . (int)$p['id'];
    ?>
<article class="package-card<?= $p['is_featured'] ? ' featured' : '' ?>">
  <?php if ($p['is_featured']): ?><span class="ribbon">En çok tercih edilen</span><?php endif; ?>
  <?php if ($showService): ?><span class="pc-service"><?= icon($p['icon'] ?? 'target', 'icon icon-sm') ?> <?= e($p['service_title']) ?></span><?php endif; ?>
  <h3><?= e($p['name']) ?></h3>
  <p class="pc-short"><?= e($p['short']) ?></p>
  <div class="price"><strong><?= money((int)$p['price']) ?></strong><span>KDV dahil · <?= e($p['duration']) ?></span></div>
  <ul class="checklist">
    <?php foreach (array_slice($features, 0, 5) as $f): ?><li><?= icon('check') ?><?= e($f) ?></li><?php endforeach; ?>
    <?php if (count($features) > 5): ?><li class="more">+<?= count($features) - 5 ?> özellik daha</li><?php endif; ?>
  </ul>
  <div class="pc-actions">
    <a class="btn btn-ghost" href="<?= url('paket/' . (int)$p['id']) ?>" data-modal="<?= $id ?>">Detayları gör</a>
    <a class="btn btn-primary" href="<?= url('odeme/' . (int)$p['id']) ?>">Satın al</a>
  </div>
</article>
<dialog class="modal" id="<?= $id ?>" aria-labelledby="<?= $id ?>-t">
  <div class="modal-head">
    <div>
      <span class="pc-service"><?= e($p['service_title'] ?? '') ?></span>
      <h3 id="<?= $id ?>-t"><?= e($p['name']) ?> Paketi</h3>
    </div>
    <button class="modal-close" data-close aria-label="Kapat"><?= icon('x') ?></button>
  </div>
  <div class="modal-body">
    <div class="modal-price"><strong><?= money((int)$p['price']) ?></strong><span>KDV dahil · Süre: <?= e($p['duration']) ?></span></div>
    <div class="prose"><?= $p['description'] ?></div>
    <h4>Paket içeriği</h4>
    <ul class="checklist">
      <?php foreach ($features as $f): ?><li><?= icon('check') ?><?= e($f) ?></li><?php endforeach; ?>
    </ul>
    <p class="small muted"><?= icon('lock', 'icon icon-sm') ?> Garanti BBVA 3D Secure ile güvenli ödeme · 14 gün cayma hakkı · Hizmet 1 iş günü içinde başlar</p>
  </div>
  <div class="modal-foot">
    <button class="btn btn-ghost" data-close>Kapat</button>
    <a class="btn btn-primary" href="<?= url('odeme/' . (int)$p['id']) ?>">Satın al — <?= money((int)$p['price']) ?></a>
  </div>
</dialog>
<?php
}

function page_services(): void
{
    $services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort');
    render('Danışmanlıklar', function () use ($services) { ?>
<section class="page-hero"><div class="container">
  <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Danışmanlıklar</nav>
  <h1>Danışmanlık Hizmetlerimiz</h1>
  <p><?= count($services) ?> farklı alanda, her biri net fiyatlı 3 paket seçeneği.</p>
</div></section>
<section class="section"><div class="container">
  <?php foreach ($services as $s):
      $packages = rows('SELECT p.*, ? AS service_title FROM packages p WHERE service_id = ? AND is_active = 1 ORDER BY sort', [$s['title'], $s['id']]); ?>
    <div class="service-block" id="<?= e($s['slug']) ?>">
      <div class="sb-head">
        <span class="sc-icon"><?= icon($s['icon']) ?></span>
        <div>
          <h2><a href="<?= url('hizmet/' . $s['slug']) ?>"><?= e($s['title']) ?></a></h2>
          <p class="muted"><?= e($s['short']) ?></p>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= url('hizmet/' . $s['slug']) ?>">Hizmet detayı <?= icon('arrow') ?></a>
      </div>
      <div class="package-grid"><?php foreach ($packages as $p) package_card($p); ?></div>
    </div>
  <?php endforeach; ?>
</div></section>
<?php
    });
}

function page_service(string $slug): void
{
    $s = row('SELECT * FROM services WHERE slug = ? AND is_active = 1', [$slug]);
    if (!$s) not_found();
    $packages = rows('SELECT p.*, ? AS service_title FROM packages p WHERE service_id = ? AND is_active = 1 ORDER BY sort', [$s['title'], $s['id']]);
    $others = rows('SELECT slug, title, icon, short FROM services WHERE is_active = 1 AND id != ? ORDER BY sort', [$s['id']]);

    render($s['title'], function () use ($s, $packages, $others) { ?>
<section class="page-hero"><div class="container">
  <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <a href="<?= url('hizmetler') ?>">Danışmanlıklar</a> / <?= e($s['title']) ?></nav>
  <div class="ph-icon"><?= icon($s['icon']) ?></div>
  <h1><?= e($s['title']) ?></h1>
  <p><?= e($s['short']) ?></p>
</div></section>
<section class="section"><div class="container">
  <div class="service-detail">
    <div class="prose"><?= $s['description'] ?></div>
    <aside class="side-box">
      <h4>Neden bu danışmanlık?</h4>
      <ul class="checklist">
        <li><?= icon('check') ?>Alanında uzman danışman</li>
        <li><?= icon('check') ?>Kişiye / kuruma özel plan</li>
        <li><?= icon('check') ?>Ölçülebilir raporlama</li>
        <li><?= icon('check') ?>14 gün cayma hakkı</li>
      </ul>
      <a class="btn btn-dark btn-block" href="<?= url('iletisim') ?>">Soru sor</a>
    </aside>
  </div>
  <div class="section-head left"><h2>Paketler ve fiyatlar</h2><p class="muted">Paket kartına ya da "Detayları gör" butonuna tıklayarak tüm içeriği inceleyebilirsiniz.</p></div>
  <div class="package-grid"><?php foreach ($packages as $p) package_card($p); ?></div>

  <?php if ($packages): ?>
  <div class="compare">
    <h3>Paket karşılaştırma</h3>
    <div class="table-wrap"><table class="table">
      <thead><tr><th></th><?php foreach ($packages as $p): ?><th><?= e($p['name']) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <tr><td>Fiyat (KDV dahil)</td><?php foreach ($packages as $p): ?><td><strong><?= money((int)$p['price']) ?></strong></td><?php endforeach; ?></tr>
        <tr><td>Süre</td><?php foreach ($packages as $p): ?><td><?= e($p['duration']) ?></td><?php endforeach; ?></tr>
        <tr><td>Özellik sayısı</td><?php foreach ($packages as $p): ?><td><?= count(package_features($p)) ?></td><?php endforeach; ?></tr>
        <tr><td></td><?php foreach ($packages as $p): ?><td><a class="btn btn-primary btn-sm" href="<?= url('odeme/' . $p['id']) ?>">Satın al</a></td><?php endforeach; ?></tr>
      </tbody>
    </table></div>
  </div>
  <?php endif; ?>
</div></section>
<section class="section section-alt"><div class="container">
  <div class="section-head left"><h2>Diğer danışmanlıklar</h2></div>
  <div class="service-grid small">
    <?php foreach ($others as $o): ?>
      <a class="service-card" href="<?= url('hizmet/' . $o['slug']) ?>"><span class="sc-icon"><?= icon($o['icon']) ?></span><h3><?= e($o['title']) ?></h3></a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php
    }, ['description' => $s['short']]);
}

function page_package(string $id): void
{
    $p = row('SELECT p.*, s.title AS service_title, s.slug AS service_slug, s.icon FROM packages p JOIN services s ON s.id = p.service_id
              WHERE p.id = ? AND p.is_active = 1 AND s.is_active = 1', [(int)$id]);
    if (!$p) not_found();
    $features = package_features($p);
    render($p['service_title'] . ' — ' . $p['name'], function () use ($p, $features) { ?>
<section class="page-hero"><div class="container">
  <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <a href="<?= url('hizmet/' . $p['service_slug']) ?>"><?= e($p['service_title']) ?></a> / <?= e($p['name']) ?></nav>
  <h1><?= e($p['name']) ?> Paketi</h1>
  <p><?= e($p['service_title']) ?> · <?= e($p['short']) ?></p>
</div></section>
<section class="section"><div class="container">
  <div class="service-detail">
    <div>
      <div class="prose"><?= $p['description'] ?></div>
      <h3>Paket içeriği</h3>
      <ul class="checklist big"><?php foreach ($features as $f): ?><li><?= icon('check') ?><?= e($f) ?></li><?php endforeach; ?></ul>
      <h3>Hizmet nasıl başlar?</h3>
      <p>Ödemeniz onaylandıktan sonra sipariş onayınız ve faturanız e-postanıza gönderilir. En geç 1 iş günü içinde danışmanınız sizinle iletişime geçerek ilk görüşmeyi planlar. Detaylar için <a href="<?= url('teslimat-kosullari') ?>">Teslimat koşulları</a> ve <a href="<?= url('iade-kosullari') ?>">İade koşulları</a> sayfalarını inceleyebilirsiniz.</p>
    </div>
    <aside class="side-box sticky">
      <span class="pc-service"><?= icon($p['icon'], 'icon icon-sm') ?> <?= e($p['service_title']) ?></span>
      <h3><?= e($p['name']) ?></h3>
      <div class="price"><strong><?= money((int)$p['price']) ?></strong><span>KDV dahil · <?= e($p['duration']) ?></span></div>
      <a class="btn btn-primary btn-block btn-lg" href="<?= url('odeme/' . $p['id']) ?>">Satın al</a>
      <ul class="mini-trust">
        <li><?= icon('lock', 'icon icon-sm') ?> 3D Secure güvenli ödeme</li>
        <li><?= icon('calendar', 'icon icon-sm') ?> 14 gün cayma hakkı</li>
        <li><?= icon('clock', 'icon icon-sm') ?> 1 iş gününde başlangıç</li>
      </ul>
      <?= payment_logos() ?>
    </aside>
  </div>
</div></section>
<?php
    }, ['description' => $p['short']]);
}
