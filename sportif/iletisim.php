<?php
require __DIR__ . '/includes/bootstrap.php';

$old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => input('konu'), 'message' => ''];
if (is_post()) {
    verify_csrf();
    $old = ['name' => input('name'), 'email' => input('email'), 'phone' => input('phone'), 'subject' => input('subject'), 'message' => input('message')];
    if (input('website') !== '') { // bot tuzağı
        redirect('iletisim.php');
    }
    if (mb_strlen($old['name']) < 3 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['message']) < 10) {
        flash('error', 'Lütfen ad soyad, geçerli e-posta ve en az 10 karakterlik mesaj girin.');
    } elseif (!input('kvkk')) {
        flash('error', 'Devam etmek için KVKK Aydınlatma Metni\'ni onaylamanız gerekir.');
    } else {
        $id = insert('messages', [
            'name' => mb_substr($old['name'], 0, 120), 'email' => mb_substr($old['email'], 0, 190),
            'phone' => mb_substr($old['phone'], 0, 30), 'subject' => mb_substr($old['subject'] ?: 'Genel Bilgi', 0, 200),
            'message' => mb_substr($old['message'], 0, 5000), 'status' => 'new', 'ip' => client_ip(), 'created_at' => now(),
        ]);
        log_activity('İletişim formu gönderildi', $old['name'] . ' - ' . $old['subject'], 'message', $id);
        send_mail(setting('notify_email'), 'Yeni iletişim mesajı: ' . $old['subject'], '<p><b>' . e($old['name']) . '</b> (' . e($old['email']) . ', ' . e($old['phone']) . ')</p><p>' . nl2br(e($old['message'])) . '</p>');
        flash('success', 'Mesajınız alındı. En kısa sürede size dönüş yapacağız.');
        redirect('iletisim.php');
    }
}
$services = [['title' => 'Sipariş Durumu'], ['title' => 'İade / Değişim'], ['title' => 'Ürün Bilgisi'], ['title' => 'Takım / Toplu Sipariş']];
$pageTitle = 'İletişim';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / İletişim</nav>
    <h1>İletişim</h1>
    <p>Sorularınız veya ücretsiz ön görüşme talebiniz için bize yazın.</p>
</div></section>
<section class="section"><div class="container contact-grid">
    <div class="card">
        <h3>Mesaj Gönderin</h3>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <div class="grid-2">
                <label>Ad Soyad *<input name="name" value="<?= e($old['name']) ?>" required></label>
                <label>E-posta *<input type="email" name="email" value="<?= e($old['email']) ?>" required></label>
                <label>Telefon<input name="phone" value="<?= e($old['phone']) ?>"></label>
                <label>Konu
                    <select name="subject">
                        <option>Genel Bilgi</option>
                        <?php foreach ($services as $s): ?><option <?= $old['subject'] === $s['title'] ? 'selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?>
                        <option>Diğer</option>
                    </select>
                </label>
            </div>
            <label>Mesajınız *<textarea name="message" rows="6" required><?= e($old['message']) ?></textarea></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=kvkk-aydinlatma-metni') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin işlenmesini kabul ediyorum.</span></label>
            <button class="btn btn-primary">Gönder</button>
        </form>
    </div>
    <div class="contact-info">
        <div class="card dark">
            <h3>İletişim Bilgileri</h3>
            <ul class="contact-list">
                <li><small>Firma</small><?= e(setting('company_title')) ?></li>
                <li><small>Adres</small><?= e(setting('company_address')) ?></li>
                <li><small>Telefon</small><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a></li>
                <li><small>E-posta</small><a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a></li>
                <li><small>Çalışma Saatleri</small><?= e(setting('working_hours')) ?></li>
                <li><small>Vergi Dairesi / No</small><?= e(setting('tax_office')) ?> / <?= e(setting('tax_number')) ?></li>
                <li><small>MERSİS No</small><?= e(setting('mersis_number')) ?></li>
            </ul>
        </div>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
