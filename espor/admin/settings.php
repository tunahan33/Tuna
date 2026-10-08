<?php
/** Site ve sanal POS ayarları - YALNIZCA SÜPER ADMIN */
require __DIR__ . '/_init.php';
$u = require_perm('settings.edit');
require dirname(__DIR__) . '/includes/payment.php';

$groups = [
    'general' => ['Genel', [
        'site_name' => ['Site Adı', 'text'], 'site_slogan' => ['Slogan', 'text'],
        'site_description' => ['Site Açıklaması (SEO ve ana sayfa)', 'textarea'], 'working_hours' => ['Çalışma Saatleri', 'text'],
        'company_whatsapp' => ['WhatsApp Numarası (örn. 905xxxxxxxxx, boşsa buton gizlenir)', 'text'],
        'instagram' => ['Instagram Adresi', 'text'], 'linkedin' => ['LinkedIn Adresi', 'text'], 'youtube' => ['YouTube Adresi', 'text'], 'discord' => ['Discord Davet Bağlantısı', 'text'], 'twitch' => ['Twitch Adresi', 'text'],
        'notify_email' => ['Sipariş / mesaj bildirimlerinin gideceği e-posta', 'email'],
    ]],
    'company' => ['Firma Bilgileri', [
        'company_title' => ['Firma Ticari Ünvanı (vergi levhasındaki gibi)', 'text'], 'company_address' => ['Açık Adres', 'textarea'],
        'company_phone' => ['Telefon', 'text'], 'company_email' => ['E-posta', 'email'],
        'tax_office' => ['Vergi Dairesi', 'text'], 'tax_number' => ['Vergi No / T.C. Kimlik No (şahıs şirketi)', 'text'],
        'mersis_number' => ['MERSİS No (şahıs şirketinde "-" yazılabilir)', 'text'], 'kep_address' => ['KEP Adresi', 'text'],
    ]],
    'pos' => ['Ödeme (PayTR)', [
        'paytr_mode' => ['Çalışma Modu', 'select', ['off' => 'KAPALI (kartla ödeme gösterilmez)', 'test' => 'TEST (PayTR test modu, yalnızca panel personeli görür)', 'live' => 'CANLI (gerçek tahsilat)']],
        'paytr_merchant_id' => ['Mağaza No (merchant_id)', 'text'],
        'paytr_merchant_key' => ['Mağaza Parola (merchant_key)', 'secret'],
        'paytr_merchant_salt' => ['Mağaza Gizli Anahtar (merchant_salt)', 'secret'],
        'paytr_installment' => ['Taksit', 'select', ['1' => 'Yalnızca tek çekim', '0' => 'Taksit açık (PayTR panelindeki tüm seçenekler)', '3' => 'En fazla 3 taksit', '6' => 'En fazla 6 taksit', '9' => 'En fazla 9 taksit', '12' => 'En fazla 12 taksit']],
    ]],
    'mail' => ['E-posta (SMTP)', [
        'mail_driver' => ['Gönderim Yöntemi', 'select', ['mail' => 'PHP mail() - hostingin varsayılan gönderimi', 'smtp' => 'SMTP - e-posta hesabı ile doğrulamalı gönderim (önerilen)']],
        'smtp_host' => ['SMTP Sunucusu (örn. mail.gssportif.com)', 'text'],
        'smtp_port' => ['Port (SSL: 465, TLS: 587)', 'text'],
        'smtp_secure' => ['Güvenlik', 'select', ['ssl' => 'SSL (465)', 'tls' => 'TLS / STARTTLS (587)', 'none' => 'Yok (önerilmez)']],
        'smtp_user' => ['Kullanıcı Adı (genelde e-posta adresinin tamamı)', 'text'],
        'smtp_pass' => ['E-posta Şifresi', 'secret'],
        'smtp_from' => ['Gönderen Adresi (boşsa firma e-postası)', 'email'],
    ]],
    'server' => ['Sunucu & Yedek', []],
];
$tab = isset($groups[input('tab')]) ? input('tab') : 'general';

if (is_post() && input('action') === 'test_mail') {
    verify_csrf();
    $err = null;
    $to = input('test_to') ?: $u['email'];
    if (send_mail($to, 'Test e-postası - ' . setting('site_name', 'GS Sportif Faaliyetler'), '<p>Bu bir test e-postasıdır. E-posta ayarlarınız doğru çalışıyor.</p><p>Gönderim yöntemi: <b>' . e(strtoupper(setting('mail_driver', 'mail'))) . '</b></p>', $err)) {
        flash('success', 'Test e-postası gönderildi: ' . $to . ' (Gelen kutusu ve spam klasörünü kontrol edin.)');
        log_activity('Test e-postası gönderdi', $to, 'settings');
    } else {
        flash('error', 'E-posta gönderilemedi: ' . $err);
    }
    redirect('admin/settings.php?tab=mail');
}

if (is_post() && input('action') === 'maintenance') {
    verify_csrf();
    $on = input('maintenance_mode') === '1';
    $until = input('maintenance_until');
    $until = $on && $until !== '' && strtotime($until) ? date('Y-m-d H:i:s', strtotime($until)) : '';
    if ($on && $until !== '' && strtotime($until) <= time()) {
        flash('error', 'Bitiş saati ileri bir zaman olmalı (ya da boş bırakın).');
        redirect('admin/settings.php?tab=server');
    }
    save_setting('maintenance_mode', $on ? '1' : '0');
    save_setting('maintenance_until', $until);
    save_setting('maintenance_message', mb_substr(input('maintenance_message'), 0, 500));
    log_activity($on ? 'Bakım modunu açtı' : 'Bakım modunu kapattı', $on ? ($until ? 'Bitiş: ' . tr_date($until) : 'Elle kapatılana kadar') : '', 'settings');
    flash('success', $on ? 'Bakım modu açıldı. Ziyaretçiler bakım sayfasını görüyor; siz siteyi normal görmeye devam edersiniz.' : 'Bakım modu kapatıldı, site herkese açık.');
    redirect('admin/settings.php?tab=server');
}

if (is_post() && $tab === 'server') {
    verify_csrf();
    $newUrl = rtrim(input('base_url'), '/');
    if ($newUrl !== config('base_url')) {
        if (!filter_var($newUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#', $newUrl)) {
            flash('error', 'Geçerli bir site adresi girin (örn. https://www.gssportif.com).');
            redirect('admin/settings.php?tab=server');
        }
        if (!write_config(['base_url' => $newUrl])) {
            flash('error', 'config.php dosyası yazılamadı. Dosya Yöneticisi ile config.php içindeki base_url değerini elle değiştirin.');
            redirect('admin/settings.php?tab=server');
        }
        log_activity('Site adresini değiştirdi', $newUrl, 'settings');
    }
    $force = input('force_https') === '1' && str_starts_with($newUrl, 'https://') ? '1' : '0';
    if ($force !== setting('force_https', '0')) {
        save_setting('force_https', $force);
        log_activity($force === '1' ? 'HTTPS zorunluluğunu açtı' : 'HTTPS zorunluluğunu kapattı', '', 'settings');
    }
    flash('success', 'Sunucu ayarları kaydedildi.');
    header('Location: ' . $newUrl . '/admin/settings.php?tab=server', true, 303);
    exit;
}

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
            log_activity('Ödeme modunu değiştirdi', 'PayTR: ' . strtoupper(paytr_mode()), 'settings');
        }
    }
    flash('success', 'Ayarlar kaydedildi.');
    redirect('admin/settings.php?tab=' . $tab);
}

// Sanal POS / ödeme kuruluşu başvurusu kontrol listesi
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
    ['İade ve İade Çeki Koşulları', (bool) row("SELECT id FROM pages WHERE slug = 'iade-ve-iade-ceki-kosullari'")],
    ['Gizlilik Sözleşmesi & Genel Aydınlatma Metni', (bool) row("SELECT id FROM pages WHERE slug = 'gizlilik-sozlesmesi'") && (bool) row("SELECT id FROM pages WHERE slug = 'genel-aydinlatma-metni'")],
    ['Teslimat Koşulları', (bool) row("SELECT id FROM pages WHERE slug = 'teslimat-kosullari'")],
    ['Ürün/hizmet açıklamaları ve net KDV dahil fiyatlar', (int) val('SELECT COUNT(*) FROM packages WHERE is_active = 1 AND price > 0') > 0],
    ['Sepet/ödeme sayfasında sözleşme onay kutuları', true],
    ['Alt bilgide kart logoları ve güvenli ödeme ibaresi', true],
    ['İletişim sayfası ve iletişim formu', true],
    ['PayTR mağaza bilgileri girildi', paytr_configured()],
    ['PayTR canlı modda', paytr_mode() === 'live'],
];
$ok = count(array_filter($checks, fn($c) => $c[1]));

admin_header('Site & Ödeme Ayarları', '<span class="lock-tag">★ Yalnızca Süper Admin</span>');
?>
<div class="role-tabs">
    <?php foreach ($groups as $k => [$l]): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($l) ?></a><?php endforeach; ?>
</div>
<div class="grid-main">
<?php if ($tab === 'server'):
    $dbVersion = (string) db()->getAttribute(PDO::ATTR_SERVER_VERSION);
    $health = [
        ['PHP sürümü', PHP_VERSION, version_compare(PHP_VERSION, '8.1', '>=')],
        ['Veritabanı', strtoupper(config('db.driver')) . ' ' . $dbVersion, true],
        ['SSL (bu istek https ile mi?)', request_is_https() ? 'Evet' : 'Hayır', request_is_https()],
        ['Site adresi https ile mi tanımlı?', config('base_url'), str_starts_with(config('base_url'), 'https://')],
        ['install klasörü silindi mi?', is_dir(ROOT . '/install') ? 'Hayır - silin!' : 'Evet', !is_dir(ROOT . '/install')],
        ['config.php yazılabilir (adres değişikliği için)', is_writable(ROOT . '/config.php') ? 'Evet' : 'Hayır', is_writable(ROOT . '/config.php')],
        ['Hata gösterimi kapalı (debug)', config('debug') ? 'Açık - canlıda kapatın' : 'Kapalı', !config('debug')],
        ['OpenSSL / mbstring / cURL', (extension_loaded('openssl') ? '✓' : '✕') . ' / ' . (extension_loaded('mbstring') ? '✓' : '✕') . ' / ' . (extension_loaded('curl') ? '✓' : '✕'), extension_loaded('openssl') && extension_loaded('mbstring')],
        ['Yükleme limiti / bellek', ini_get('upload_max_filesize') . ' / ' . ini_get('memory_limit'), true],
        ['Sunucu saati', date('d.m.Y H:i') . ' (' . date_default_timezone_get() . ')', true],
        ['E-posta yöntemi', strtoupper(setting('mail_driver', 'mail')), setting('mail_driver') === 'smtp'],
    ];
    ?>
    <div>
        <form method="post" class="panel form maint-<?= maintenance_active() ? 'on' : 'off' ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="maintenance">
            <div class="panel-head"><h3>Bakım Modu</h3><?= maintenance_active() ? '<span class="badge badge-red">AÇIK - site ziyaretçilere kapalı</span>' : '<span class="badge badge-green">Kapalı - site yayında</span>' ?></div>
            <p class="small muted">Açıkken ziyaretçiler “Kısa bir bakımdayız” sayfasını görür. Siz ve ekibiniz (giriş yapmış personel) siteyi normal görürsünüz. Banka ödeme dönüşleri etkilenmez.</p>
            <div class="segmented">
                <label><input type="radio" name="maintenance_mode" value="0" <?= maintenance_active() ? '' : 'checked' ?>> Site açık</label>
                <label><input type="radio" name="maintenance_mode" value="1" <?= maintenance_active() ? 'checked' : '' ?>> Bakım modu</label>
            </div>
            <label>Otomatik açılış saati <small>(isteğe bağlı; boşsa siz kapatana kadar sürer)</small><input type="datetime-local" name="maintenance_until" value="<?= setting('maintenance_until') ? e(date('Y-m-d\TH:i', strtotime(setting('maintenance_until')))) : '' ?>"></label>
            <label>Ziyaretçiye gösterilecek mesaj <small>(boşsa varsayılan mesaj)</small><textarea name="maintenance_message" rows="2"><?= e(setting('maintenance_message')) ?></textarea></label>
            <div class="form-actions"><button class="btn btn-dark">Uygula</button> <a class="btn btn-outline" href="<?= url() ?>" target="_blank">Siteyi gör</a></div>
        </form>
        <form method="post" class="panel form">
            <?= csrf_field() ?>
            <h3>Site Adresi</h3>
            <div class="info-box">Alan adınızı aldığınızda veya geçici adresten gerçek adrese geçtiğinizde buradan güncelleyin. E-posta bağlantıları, banka dönüş adresi ve sözleşmelerdeki site adresi bu değeri kullanır.</div>
            <label>Sitenin tam adresi<input name="base_url" value="<?= e(config('base_url')) ?>" required placeholder="https://www.gssportif.com"></label>
            <label class="check"><input type="checkbox" name="force_https" value="1" <?= setting('force_https') === '1' ? 'checked' : '' ?>> <span>Tüm ziyaretçileri <b>https://</b> ve yukarıdaki tek adrese yönlendir (örn. gssportif.com → www.gssportif.com). SSL sertifikası kurulduktan sonra açın; adres https ile başlamıyorsa uygulanmaz.</span></label>
            <div class="form-actions"><button class="btn btn-primary">Kaydet</button></div>
        </form>
        <form method="post" action="backup.php" class="panel form">
            <?= csrf_field() ?>
            <h3>Veritabanı Yedeği</h3>
            <p class="small muted">Siparişler, üyeler, içerikler ve ayarların tamamını tek dosya olarak indirir. Haftada bir indirip saklamanız önerilir. Geri yüklemek için cPanel &gt; phpMyAdmin &gt; İçe Aktar kullanılır.</p>
            <div class="form-actions"><button class="btn btn-yellow">Yedeği İndir</button></div>
        </form>
    </div>
    <section class="panel">
        <div class="panel-head"><h2>Sunucu Durumu</h2></div>
        <ul class="checks">
            <?php foreach ($health as [$l, $v, $okk]): ?><li class="<?= $okk ? 'ok' : 'no' ?>"><span><?= $okk ? '✓' : '!' ?></span><div><?= e($l) ?><br><small class="muted"><?= e($v) ?></small></div></li><?php endforeach; ?>
        </ul>
    </section>
<?php else: ?>
    <form method="post" class="panel form">
        <?= csrf_field() ?>
        <?php if ($tab === 'pos'): ?>
            <div class="info-box">
                Mevcut mod: <strong><?= strtoupper(paytr_mode()) ?></strong><?= paytr_mode() !== 'off' && !paytr_configured() ? ' · <b>Mağaza bilgileri eksik, kartla ödeme kapalı.</b>' : '' ?><br>
                Bilgiler: PayTR Mağaza Paneli → <b>Destek &amp; Kurulum → Entegrasyon Bilgileri</b>.<br>
                PayTR panelinde <b>Destek &amp; Kurulum → Ayarlar → Bildirim URL</b> alanına şu adresi girin: <code><?= e(url('paytr-bildirim.php')) ?></code><br>
                Önce <b>TEST</b> modunda (yalnızca personel görür) PayTR test kartıyla deneyin, ardından <b>CANLI</b>'ya alın.
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
    <?php if ($tab === 'mail'): ?>
    <section class="panel">
        <div class="panel-head"><h2>Test E-postası Gönder</h2></div>
        <p class="small muted">Ayarları kaydettikten sonra buradan deneyin. Hosting firmanızın panelinde (cPanel &gt; E-posta Hesapları) oluşturduğunuz hesabın bilgilerini kullanın.</p>
        <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="action" value="test_mail">
            <label>Alıcı<input type="email" name="test_to" value="<?= e($u['email']) ?>"></label>
            <button class="btn btn-dark btn-sm">Test Gönder</button>
        </form>
    </section>
    <?php else: ?>
    <section class="panel">
        <div class="panel-head"><h2>Sanal POS / Ödeme Kuruluşu Başvuru Kontrolü</h2><span class="badge <?= $ok === count($checks) ? 'badge-green' : 'badge-yellow' ?>"><?= $ok ?>/<?= count($checks) ?></span></div>
        <ul class="checks">
            <?php foreach ($checks as [$l, $v]): ?><li class="<?= $v ? 'ok' : 'no' ?>"><span><?= $v ? '✓' : '!' ?></span><?= e($l) ?></li><?php endforeach; ?>
        </ul>
        <p class="small muted">Eksik firma bilgileri “Firma Bilgileri” sekmesinden tamamlanır; tüm yasal sayfalar bu bilgilerle otomatik güncellenir.</p>
    </section>
    <?php endif; ?>
<?php endif; ?>
</div>
<?php admin_footer();
