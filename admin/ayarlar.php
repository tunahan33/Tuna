<?php
/** SÜPER ADMİN'E ÖZEL: Mağaza, firma ve kargo ayarları */
require __DIR__ . '/_layout.php';
$u = require_perm('settings');

$groups = [
    'Mağaza' => [
        'site_name' => ['Mağaza adı', 'text'], 'site_slogan' => ['Slogan', 'text'], 'site_url' => ['Alan adı (site adresi)', 'url'],
        'announcement' => ['Üst duyuru şeridi (boş bırakılırsa gizlenir)', 'text'],
    ],
    'Firma Bilgileri (sözleşmelerde kullanılır)' => [
        'company_title' => ['Ticari unvan', 'text'], 'company_address' => ['Adres', 'text'], 'company_phone' => ['Telefon', 'text'],
        'company_email' => ['E-posta', 'email'], 'working_hours' => ['Çalışma saatleri', 'text'], 'tax_office' => ['Vergi dairesi', 'text'],
        'tax_number' => ['Vergi numarası', 'text'], 'mersis_number' => ['MERSİS no', 'text'], 'kep_address' => ['KEP adresi', 'text'],
    ],
    'Kargo' => [
        'shipping_fee' => ['Kargo ücreti (₺)', 'number'], 'free_shipping_limit' => ['Ücretsiz kargo alt limiti (₺) — 0 ise kapalı', 'number'], 'shipping_days' => ['Kargoya verilme süresi', 'text'],
    ],
];
if (is_post()) {
    verify_csrf();
    $changed = [];
    foreach ($groups as $fields) {
        foreach ($fields as $k => [$label, $type]) {
            $v = mb_substr(trim((string) input($k)), 0, 300);
            if ($type === 'number') $v = (string) max(0, (float) str_replace(',', '.', $v));
            if ($v !== setting($k)) $changed[] = $label;
            save_setting($k, $v);
        }
    }
    log_activity('Mağaza ayarlarını güncelledi', $changed ? implode(', ', $changed) : 'değişiklik yok');
    flash('success', 'Ayarlar kaydedildi.');
    redirect('admin/ayarlar.php');
}
admin_header('Mağaza Ayarları', 'Yalnızca Süper Admin görebilir');
?>
<form method="post" class="grid-2-1">
    <?= csrf_field() ?>
    <div>
        <?php foreach ($groups as $title => $fields): ?>
            <section class="panel form">
                <h2><?= e($title) ?></h2>
                <div class="grid-2">
                    <?php foreach ($fields as $k => [$label, $type]): ?>
                        <label><?= e($label) ?><input type="<?= $type === 'number' ? 'number' : ($type === 'email' ? 'email' : 'text') ?>" <?= $type === 'number' ? 'step="0.01" min="0"' : '' ?> name="<?= $k ?>" value="<?= e(setting($k)) ?>"></label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <button class="btn btn-primary btn-lg">Kaydet</button>
    </div>
    <section class="panel">
        <h2>Yayına Hazırlık Kontrolü</h2>
        <ul class="checklist">
            <?php
            $checks = [
                'Ticari unvan girildi' => !str_contains(setting('company_title'), 'girin'),
                'Adres girildi' => !str_contains(setting('company_address'), 'girin'),
                'Vergi numarası girildi' => setting('tax_number') !== '-' && setting('tax_number') !== '',
                'MERSİS numarası girildi' => setting('mersis_number') !== '-' && setting('mersis_number') !== '',
                'Alan adı girildi' => !str_contains(setting('site_url'), 'alanadiniz'),
                'Kurumsal e-posta girildi' => !str_contains(setting('company_email'), 'alanadiniz'),
                'Demo şifreler değiştirildi' => !password_verify('Admin123!', (string) val("SELECT password_hash FROM users WHERE email = 'admin@gssportif.local'")),
            ];
            foreach ($checks as $l => $ok): ?><li class="<?= $ok ? 'ok' : 'todo' ?>"><?= $ok ? '✓' : '!' ?> <?= e($l) ?></li><?php endforeach; ?>
        </ul>
        <p class="small muted">Firma bilgileri altbilgi sayfalarına, Mesafeli Satış Sözleşmesi'ne ve Ön Bilgilendirme Formu'na otomatik yansır.</p>
    </section>
</form>
<?php admin_footer();
