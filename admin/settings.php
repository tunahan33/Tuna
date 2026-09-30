<?php
/** Site ve sanal POS ayarları - YALNIZCA SÜPER ADMIN */
require __DIR__ . '/_init.php';
require dirname(__DIR__) . '/includes/garanti.php';
$u = require_perm('settings.edit');

$groups = [
    'general' => ['Genel', [
        'site_name' => ['Site Adı', 'text'], 'site_slogan' => ['Slogan', 'text'],
        'site_description' => ['Site Açıklaması (SEO ve ana sayfa)', 'textarea'], 'working_hours' => ['Çalışma Saatleri', 'text'],
        'company_whatsapp' => ['WhatsApp Numarası (örn. 905xxxxxxxxx, boşsa buton gizlenir)', 'text'],
        'instagram' => ['Instagram Adresi', 'text'], 'linkedin' => ['LinkedIn Adresi', 'text'], 'youtube' => ['YouTube Adresi', 'text'],
        'notify_email' => ['Sipariş / mesaj bildirimlerinin gideceği e-posta', 'email'],
    ]],
    'company' => ['Firma Bilgileri', [
        'company_title' => ['Firma Ticari Ünvanı (vergi levhasındaki gibi)', 'text'], 'company_address' => ['Açık Adres', 'textarea'],
        'company_phone' => ['Telefon', 'text'], 'company_email' => ['E-posta', 'email'],
        'tax_office' => ['Vergi Dairesi', 'text'], 'tax_number' => ['Vergi No / T.C. Kimlik No (şahıs şirketi)', 'text'],
        'mersis_number' => ['MERSİS No (şahıs şirketinde "-" yazılabilir)', 'text'], 'kep_address' => ['KEP Adresi', 'text'],
    ]],
    'pos' => ['Ödeme (Garanti Sanal POS)', [
        'pos_mode' => ['Çalışma Modu', 'select', ['demo' => 'DEMO (banka bağlantısı yok, test simülasyonu)', 'test' => 'TEST (Garanti test ortamı)', 'prod' => 'CANLI (gerçek tahsilat)']],
        'garanti_security_level' => ['3D Modeli', 'select', ['3D_OOS_PAY' => '3D OOS Pay - Bankanın ortak ödeme sayfası (önerilen)', '3D_PAY' => '3D Pay - Kart formu sitede, veriler doğrudan bankaya']],
        'garanti_merchant_id' => ['Üye İşyeri No (Merchant ID)', 'text'], 'garanti_terminal_id' => ['Terminal No (Terminal ID)', 'text'],
        'garanti_prov_user' => ['Provizyon Kullanıcısı', 'text'], 'garanti_prov_password' => ['Provizyon Şifresi', 'secret'],
        'garanti_store_key' => ['3D Secure Anahtarı (Store Key)', 'secret'],
    ]],
];
$tab = isset($groups[input('tab')]) ? input('tab') : 'general';

if (is_post()) {
    verify_csrf();
    $changed = [];
    foreach ($groups[$tab][1] as $key => $def) {
        $val = trim((string) ($_POST[$key] ?? ''));
        if ($def[1] === 'secret' && $val === '') continue; // boş bırakılırsa mevcut korunur
        if ($def[1] === 'select' && !isset($def[2][$val])) continue;
        if ($val !== setting($key)) {
            save_setting($key, mb_substr($val, 0, 2000));
            $changed[] = $def[1] === 'secret' ? $def[0] . ' (gizli)' : $def[0];
        }
    }
    if ($changed) {
        log_activity('Site ayarlarını değiştirdi', $groups[$tab][0] . ': ' . implode(', ', $changed), 'settings');
        if (in_array('Çalışma Modu', $changed, true)) {
            log_activity('Ödeme modunu değiştirdi', 'Yeni mod: ' . strtoupper(trim($_POST['pos_mode'])), 'settings');
        }
    }
    flash('success', 'Ayarlar kaydedildi.');
    redirect('admin/settings.php?tab=' . $tab);
}

// Garanti başvuru kontrol listesi
$https = str_starts_with(config('base_url'), 'https://');
$filled = fn($k) => ($v = trim(setting($k))) !== '' && $v !== '-' && !str_contains($v, 'girin') && !str_contains($v, '___');
$checks = [
    ['SSL sertifikası (site https:// ile açılıyor)', $https],
    ['Firma ticari ünvanı', $filled('company_title')],
    ['Açık adres', $filled('company_address')],
    ['Telefon', $filled('company_phone')],
    ['Vergi dairesi ve vergi no', $filled('tax_office') && $filled('tax_number')],
    ['Hakkımızda sayfası', (bool) row("SELECT id FROM pages WHERE slug = 'hakkimizda'")],
    ['Mesafeli Satış Sözleşmesi', (bool) row("SELECT id FROM pages WHERE slug = 'mesafeli-satis-sozlesmesi'")],
    ['Ön Bilgilendirme Formu', (bool) row("SELECT id FROM pages WHERE slug = 'on-bilgilendirme-formu'")],
    ['İptal ve İade Koşulları', (bool) row("SELECT id FROM pages WHERE slug = 'iptal-ve-iade-kosullari'")],
    ['Gizlilik Politikası & KVKK Aydınlatma Metni', (bool) row("SELECT id FROM pages WHERE slug = 'gizlilik-politikasi'") && (bool) row("SELECT id FROM pages WHERE slug = 'kvkk-aydinlatma-metni'")],
    ['Teslimat / Hizmet Koşulları', (bool) row("SELECT id FROM pages WHERE slug = 'teslimat-ve-hizmet-kosullari'")],
    ['Ürün/hizmet açıklamaları ve net KDV dahil fiyatlar', (int) val('SELECT COUNT(*) FROM packages WHERE is_active = 1 AND price > 0') > 0],
    ['Sepet/ödeme sayfasında sözleşme onay kutuları', true],
    ['Alt bilgide kart logoları ve güvenli ödeme ibaresi', true],
    ['İletişim sayfası ve iletişim formu', true],
    ['Garanti terminal bilgileri girildi', $filled('garanti_merchant_id') && $filled('garanti_terminal_id') && $filled('garanti_prov_password') && $filled('garanti_store_key')],
];
$ok = count(array_filter($checks, fn($c) => $c[1]));

admin_header('Site & Ödeme Ayarları', '<span class="lock-tag">★ Yalnızca Süper Admin</span>');
?>
<div class="role-tabs">
    <?php foreach ($groups as $k => [$l]): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($l) ?></a><?php endforeach; ?>
</div>
<div class="grid-main">
    <form method="post" class="panel form">
        <?= csrf_field() ?>
        <?php if ($tab === 'pos'): ?>
            <div class="info-box">
                Mevcut mod: <strong><?= strtoupper(pos_mode()) ?></strong>. Garanti BBVA başvurunuz onaylandığında size iletilen bilgileri girin, önce <b>TEST</b> modunda deneyin, ardından <b>CANLI</b>'ya alın.<br>
                Bankaya bildirmeniz gereken dönüş adresi (Success/Error URL): <code><?= e(url('odeme-sonuc.php')) ?></code>
            </div>
        <?php endif; ?>
        <?php foreach ($groups[$tab][1] as $key => $def): ?>
            <label><?= e($def[0]) ?>
                <?php if ($def[1] === 'textarea'): ?>
                    <textarea name="<?= $key ?>" rows="3"><?= e(setting($key)) ?></textarea>
                <?php elseif ($def[1] === 'select'): ?>
                    <select name="<?= $key ?>"><?php foreach ($def[2] as $v => $l): ?><option value="<?= e($v) ?>" <?= setting($key) === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
                <?php elseif ($def[1] === 'secret'): ?>
                    <input type="password" name="<?= $key ?>" autocomplete="new-password" placeholder="<?= setting($key) !== '' ? '•••••••• (kayıtlı - değiştirmek için yazın)' : 'Girilmedi' ?>">
                <?php else: ?>
                    <input type="<?= $def[1] ?>" name="<?= $key ?>" value="<?= e(setting($key)) ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        <div class="form-actions"><button class="btn btn-primary">Kaydet</button></div>
    </form>
    <section class="panel">
        <div class="panel-head"><h2>Garanti Sanal POS Başvuru Kontrolü</h2><span class="badge <?= $ok === count($checks) ? 'badge-green' : 'badge-yellow' ?>"><?= $ok ?>/<?= count($checks) ?></span></div>
        <ul class="checks">
            <?php foreach ($checks as [$l, $v]): ?><li class="<?= $v ? 'ok' : 'no' ?>"><span><?= $v ? '✓' : '!' ?></span><?= e($l) ?></li><?php endforeach; ?>
        </ul>
        <p class="small muted">Eksik firma bilgileri “Firma Bilgileri” sekmesinden tamamlanır; tüm yasal sayfalar bu bilgilerle otomatik güncellenir.</p>
    </section>
</div>
<?php admin_footer();
