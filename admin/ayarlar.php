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
        'tax_number' => ['Vergi / TC kimlik numarası', 'text'], 'mersis_number' => ['MERSİS no (şirketler için, yoksa boş)', 'text'], 'kep_address' => ['KEP adresi (varsa)', 'text'],
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
    // Ödeme (PayTR) ayarları
    $mode = input('payment_mode') === 'paytr' ? 'paytr' : 'demo';
    if ($mode !== setting('payment_mode', 'demo')) $changed[] = 'Ödeme yöntemi: ' . ($mode === 'paytr' ? 'PayTR' : 'Demo');
    save_setting('payment_mode', $mode);
    save_setting('paytr_merchant_id', preg_replace('/\D/', '', (string) input('paytr_merchant_id')));
    foreach (['paytr_merchant_key' => 'PayTR Merchant Key', 'paytr_merchant_salt' => 'PayTR Merchant Salt'] as $k => $label) {
        $v = trim((string) input($k));
        if ($v !== '') { save_setting($k, $v); $changed[] = $label; } // boş bırakılırsa mevcut değer korunur
    }
    $test = input('paytr_test_mode') ? '1' : '0';
    if ($test !== setting('paytr_test_mode', '1')) $changed[] = 'PayTR ' . ($test === '1' ? 'TEST' : 'CANLI') . ' mod';
    save_setting('paytr_test_mode', $test);
    save_setting('paytr_max_installment', (string) max(0, min(12, (int) input('paytr_max_installment'))));
    save_setting('paytr_no_installment', input('paytr_no_installment') ? '1' : '0');
    if ($mode === 'paytr' && !paytr_enabled()) {
        flash('error', 'PayTR seçildi ancak Mağaza No, Merchant Key veya Merchant Salt eksik. Bilgiler tamamlanana kadar ödeme DEMO modunda çalışır.');
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
        <section class="panel form" id="odeme">
            <h2>Ödeme (PayTR)</h2>
            <p class="small muted" style="margin:0">Bilgileri PayTR Mağaza Paneli'nden alın. <strong>Bildirim URL</strong> olarak PayTR paneline şunu girin:
                <code><?= e(rtrim(setting('site_url'), '/')) ?>/paytr-bildirim.php</code></p>
            <div class="grid-2">
                <label>Ödeme yöntemi
                    <select name="payment_mode">
                        <option value="demo" <?= setting('payment_mode', 'demo') !== 'paytr' ? 'selected' : '' ?>>Demo (gerçek çekim yok)</option>
                        <option value="paytr" <?= setting('payment_mode') === 'paytr' ? 'selected' : '' ?>>PayTR</option>
                    </select>
                </label>
                <label>Mağaza No (merchant_id)<input name="paytr_merchant_id" value="<?= e(setting('paytr_merchant_id')) ?>" inputmode="numeric" autocomplete="off"></label>
                <label>Merchant Key <small class="muted"><?= setting('paytr_merchant_key') !== '' ? '(kayıtlı · değiştirmek için yeni değer girin)' : '' ?></small><input type="password" name="paytr_merchant_key" autocomplete="new-password" placeholder="<?= setting('paytr_merchant_key') !== '' ? '••••••••' : '' ?>"></label>
                <label>Merchant Salt <small class="muted"><?= setting('paytr_merchant_salt') !== '' ? '(kayıtlı · değiştirmek için yeni değer girin)' : '' ?></small><input type="password" name="paytr_merchant_salt" autocomplete="new-password" placeholder="<?= setting('paytr_merchant_salt') !== '' ? '••••••••' : '' ?>"></label>
                <label>En fazla taksit <small class="muted">(0 = PayTR'nin izin verdiği tümü)</small><input type="number" min="0" max="12" name="paytr_max_installment" value="<?= e(setting('paytr_max_installment', '0')) ?>"></label>
                <div class="form">
                    <label class="check"><input type="checkbox" name="paytr_no_installment" value="1" <?= setting('paytr_no_installment') === '1' ? 'checked' : '' ?>> Taksit kapalı (tek çekim)</label>
                    <label class="check"><input type="checkbox" name="paytr_test_mode" value="1" <?= setting('paytr_test_mode', '1') === '1' ? 'checked' : '' ?>> TEST modu (gerçek para çekilmez)</label>
                </div>
            </div>
            <p class="small" style="margin:0">Durum: <?= paytr_enabled() ? (paytr_test_mode() ? badge('PayTR TEST modunda', 'yellow') : badge('PayTR CANLI', 'green')) : badge('Demo ödeme', 'gray') ?></p>
        </section>
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
                'Alan adı girildi' => !str_contains(setting('site_url'), 'alanadiniz'),
                'Kurumsal e-posta girildi' => !str_contains(setting('company_email'), 'alanadiniz'),
                'Online ödeme (PayTR) bağlı' => paytr_enabled(),
                'PayTR canlı modda (test kapalı)' => paytr_enabled() && !paytr_test_mode(),
                'Demo şifreler değiştirildi' => !password_verify('Admin123!', (string) val("SELECT password_hash FROM users WHERE email = 'admin@gssportif.local'")),
            ];
            foreach ($checks as $l => $ok): ?><li class="<?= $ok ? 'ok' : 'todo' ?>"><?= $ok ? '✓' : '!' ?> <?= e($l) ?></li><?php endforeach; ?>
        </ul>
        <p class="small muted">Firma bilgileri altbilgi sayfalarına, Mesafeli Satış Sözleşmesi'ne ve Ön Bilgilendirme Formu'na otomatik yansır.</p>
    </section>
</form>
<?php admin_footer();
