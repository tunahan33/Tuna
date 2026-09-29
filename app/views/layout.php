<?php
function icon(string $name, string $class = 'icon'): string
{
    $p = [
        'bolt'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        'apple'     => '<path d="M12 7c-2-2-6-1.5-7 2-1 3.5 1 9 3.5 10.5 1.2.7 2.3.2 3.5.2s2.3.5 3.5-.2C18 18 20 12.5 19 9c-1-3.5-5-4-7-2z"/><path d="M12 7c0-2 1-4 3-4.5"/>',
        'brain'     => '<path d="M9.5 2A2.5 2.5 0 0 0 7 4.5v.3A3 3 0 0 0 4.5 8a3 3 0 0 0 .5 5.6V15a3 3 0 0 0 3 3 2.5 2.5 0 0 0 4 1.5V4a2.5 2.5 0 0 0-2.5-2z"/><path d="M14.5 2A2.5 2.5 0 0 1 17 4.5v.3A3 3 0 0 1 19.5 8a3 3 0 0 1-.5 5.6V15a3 3 0 0 1-3 3 2.5 2.5 0 0 1-4 1.5"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
        'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'stadium'   => '<ellipse cx="12" cy="8" rx="9" ry="4"/><path d="M3 8v8c0 2.2 4 4 9 4s9-1.8 9-4V8"/><path d="M12 8v12M7 11.5v7.5M17 11.5v7.5"/>',
        'megaphone' => '<path d="m3 11 15-6v14L3 13z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/><path d="M21 9v6"/>',
        'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'arrow'     => '<path d="M5 12h14M13 5l7 7-7 7"/>',
        'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'lock'      => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-6"/>',
        'star'      => '<path d="m12 2 3 6.5 7 .9-5.1 4.8 1.3 7L12 17.8 5.8 21.2l1.3-7L2 9.4l7-.9z"/>',
        'chevron'   => '<path d="m6 9 6 6 6-6"/>',
        'users'     => '<circle cx="9" cy="8" r="4"/><path d="M1 21a8 8 0 0 1 16 0M17 4a4 4 0 0 1 0 8M23 21a8 8 0 0 0-5-7.4"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    ][$name] ?? '<circle cx="12" cy="12" r="9"/>';
    return '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function payment_logos(): string
{
    return '<div class="pay-logos" aria-label="Kabul edilen kartlar">'
        . '<span class="pay-logo" title="Visa"><svg viewBox="0 0 60 20"><text x="30" y="16" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-style="italic" font-weight="900" font-size="17" fill="#1A1F71">VISA</text></svg></span>'
        . '<span class="pay-logo" title="Mastercard"><svg viewBox="0 0 60 20"><circle cx="24" cy="10" r="8" fill="#EB001B"/><circle cx="36" cy="10" r="8" fill="#F79E1B"/><path d="M30 4.7a8 8 0 0 1 0 10.6 8 8 0 0 1 0-10.6z" fill="#FF5F00"/></svg></span>'
        . '<span class="pay-logo" title="Troy"><svg viewBox="0 0 60 20"><text x="30" y="15.5" text-anchor="middle" font-family="Arial,sans-serif" font-weight="800" font-size="15" fill="#00A3B4">tr<tspan fill="#1E2A4A">oy</tspan></text></svg></span>'
        . '<span class="pay-logo" title="3D Secure"><svg viewBox="0 0 60 20"><rect x="2" y="3" width="18" height="14" rx="3" fill="#2B2D31"/><text x="11" y="13.5" text-anchor="middle" font-family="Arial,sans-serif" font-weight="800" font-size="8" fill="#FDB913">3D</text><text x="40" y="13.5" text-anchor="middle" font-family="Arial,sans-serif" font-weight="700" font-size="8.5" fill="#2B2D31">Secure</text></svg></span>'
        . '<span class="pay-logo" title="256 bit SSL"><svg viewBox="0 0 60 20"><path d="M9 9V7a4 4 0 0 1 8 0v2" stroke="#1A7F37" stroke-width="2" fill="none"/><rect x="7" y="9" width="12" height="9" rx="2" fill="#1A7F37"/><text x="40" y="14" text-anchor="middle" font-family="Arial,sans-serif" font-weight="800" font-size="9" fill="#1A7F37">SSL</text></svg></span>'
        . '<span class="pay-logo pay-logo-wide" title="Garanti BBVA"><svg viewBox="0 0 90 20"><text x="45" y="14.5" text-anchor="middle" font-family="Arial,sans-serif" font-weight="800" font-size="11" fill="#1B8A5A">Garanti <tspan fill="#004481">BBVA</tspan></text></svg></span>'
        . '</div>';
}

function layout_header(string $title, array $opts = []): void
{
    $u = current_user();
    $services = rows('SELECT slug, title, icon FROM services WHERE is_active = 1 ORDER BY sort');
    $site = setting('site_name');
    $desc = $opts['description'] ?? (setting('site_name') . ' — sporcu performansı, beslenme, mental performans, kariyer, kulüp yönetimi, tesis ve sponsorluk danışmanlığı.');
    $path = trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_path())), '/');
    $active = fn(string $p) => ($p === '' ? $path === '' : str_starts_with($path, $p)) ? ' class="active"' : '';
    ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title === $site ? $site . ' — ' . setting('site_slogan') : $title . ' | ' . $site) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#2B2D31">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<div class="topbar">
  <div class="container topbar-inner">
    <div class="topbar-contact">
      <a href="tel:<?= e(preg_replace('/\s+/', '', setting('company_phone'))) ?>"><?= icon('phone') ?> <?= e(setting('company_phone')) ?></a>
      <a href="mailto:<?= e(setting('company_email')) ?>"><?= icon('mail') ?> <?= e(setting('company_email')) ?></a>
    </div>
    <div class="topbar-links">
      <a href="<?= url('siparis-takibi') ?>">Sipariş takibi</a>
      <a href="<?= url('ariza-takibi') ?>">Arıza takibi</a>
      <a href="<?= url('guvenli-alisveris') ?>"><?= icon('lock') ?> Güvenli alışveriş</a>
    </div>
  </div>
</div>
<header class="header" id="header">
  <div class="container header-inner">
    <a class="brand" href="<?= url() ?>" aria-label="<?= e($site) ?> ana sayfa"><?= logo_svg('logo') ?></a>
    <nav class="nav" id="nav" aria-label="Ana menü">
      <a href="<?= url() ?>"<?= $active('') ?>>Ana Sayfa</a>
      <div class="nav-drop">
        <a href="<?= url('hizmetler') ?>"<?= $active('hizmet') ?>>Danışmanlıklar <?= icon('chevron', 'icon icon-sm') ?></a>
        <div class="drop-menu">
          <?php foreach ($services as $s): ?>
            <a href="<?= url('hizmet/' . $s['slug']) ?>"><span class="drop-ico"><?= icon($s['icon']) ?></span><?= e($s['title']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?= url('hakkimizda') ?>"<?= $active('hakkimizda') ?>>Hakkımızda</a>
      <a href="<?= url('sss') ?>"<?= $active('sss') ?>>SSS</a>
      <a href="<?= url('iletisim') ?>"<?= $active('iletisim') ?>>İletişim</a>
      <div class="nav-mobile-extra">
        <?php if ($u): ?>
          <a href="<?= url(is_staff($u) ? 'admin' : 'hesabim') ?>"><?= is_staff($u) ? 'Yönetim Paneli' : 'Hesabım' ?></a>
          <a href="<?= url('cikis') ?>">Çıkış</a>
        <?php else: ?>
          <a href="<?= url('giris') ?>">Giriş Yap</a>
          <a href="<?= url('kayit') ?>">Üye Ol</a>
        <?php endif; ?>
      </div>
    </nav>
    <div class="header-actions">
      <?php if ($u): ?>
        <a class="btn btn-ghost btn-sm hide-sm" href="<?= url(is_staff($u) ? 'admin' : 'hesabim') ?>"><?= icon('user') ?> <?= e(explode(' ', $u['name'])[0]) ?></a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm hide-sm" href="<?= url('giris') ?>"><?= icon('user') ?> Giriş</a>
      <?php endif; ?>
      <a class="btn btn-primary btn-sm hide-sm" href="<?= url('hizmetler') ?>">Paketleri İncele</a>
      <button class="menu-toggle" id="menuToggle" aria-label="Menüyü aç" aria-expanded="false"><?= icon('menu') ?></button>
    </div>
  </div>
</header>
<main id="main">
<?php foreach (flashes() as [$type, $msg]): ?>
  <div class="container"><div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div></div>
<?php endforeach;
}

function layout_footer(): void
{
    $l = fn(string $slug, string $label) => '<li><a href="' . url($slug) . '">' . $label . '</a></li>';
    ?>
</main>
<footer class="footer">
  <div class="container">
    <div class="footer-cols">
      <div class="footer-col">
        <h4>KURUMSAL</h4>
        <ul>
          <?= $l('hakkimizda', 'Hakkımızda') ?>
          <?= $l('insan-kaynaklari', 'İnsan Kaynakları') ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>MÜŞTERİ HİZMETLERİ</h4>
        <ul>
          <?= $l('sss', 'Sıkça sorulan sorular') ?>
          <?= $l('siparis-takibi', 'Sipariş takibi') ?>
          <?= $l('ariza-takibi', 'Arıza takibi') ?>
          <?= $l('iade-kosullari', 'İade ve iade çeki koşulları') ?>
          <?= $l('teslimat-kosullari', 'Teslimat koşulları') ?>
          <?= $l('guvenli-alisveris', 'Güvenli alışveriş') ?>
          <?= $l('iletisim', 'İletişim') ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>SÖZLEŞMELER VE YASAL</h4>
        <ul>
          <?= $l('uyelik-sozlesmesi', 'Üyelik sözleşmesi') ?>
          <?= $l('aydinlatma-metni', 'Genel Aydınlatma metni') ?>
          <?= $l('cerez-politikasi', 'Çerez politikası') ?>
          <?= $l('cerez-tercihleri', 'Çerez tercihleri') ?>
          <?= $l('basvuru-formu', 'İlgili kişi başvuru formu') ?>
        </ul>
      </div>
    </div>
    <div class="footer-mid">
      <a class="footer-brand" href="<?= url() ?>"><?= logo_svg('logo logo-footer') ?></a>
      <?= payment_logos() ?>
    </div>
    <div class="footer-company">
      <span><strong><?= e(setting('company_title')) ?></strong></span>
      <span><?= e(setting('company_address')) ?></span>
      <span>Vergi Dairesi: <?= e(setting('tax_office')) ?> · VKN: <?= e(setting('tax_number')) ?></span>
      <span>MERSİS: <?= e(setting('mersis_number')) ?></span>
      <span><?= e(setting('company_phone')) ?> · <?= e(setting('company_email')) ?></span>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e(setting('site_name')) ?>. Tüm hakları saklıdır. Fiyatlara KDV dahildir.</span>
      <span class="footer-legal">
        <a href="<?= url('mesafeli-satis-sozlesmesi') ?>">Mesafeli satış sözleşmesi</a>
        <a href="<?= url('on-bilgilendirme-formu') ?>">Ön bilgilendirme formu</a>
        <a href="<?= url('gizlilik-politikasi') ?>">Gizlilik politikası</a>
      </span>
    </div>
  </div>
</footer>

<div class="cookie-bar" id="cookieBar" hidden>
  <div class="cookie-inner">
    <p>Sitemizde deneyiminizi iyileştirmek için çerezler kullanıyoruz. Zorunlu çerezler dışındakiler yalnızca onayınızla kullanılır. <a href="<?= url('cerez-politikasi') ?>">Çerez politikası</a></p>
    <div class="cookie-actions">
      <a class="btn btn-ghost btn-sm" href="<?= url('cerez-tercihleri') ?>">Tercihler</a>
      <button class="btn btn-ghost btn-sm" data-cookie="reject">Reddet</button>
      <button class="btn btn-primary btn-sm" data-cookie="accept">Tümünü kabul et</button>
    </div>
  </div>
</div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
<?php
}
