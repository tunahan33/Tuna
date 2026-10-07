<?php
require __DIR__ . '/app/bootstrap.php';

$topics = ['Sipariş', 'İade / Değişim', 'İade çeki', 'Ürün bilgisi', 'Ödeme', 'Öneri / Şikâyet', 'Diğer'];
if (is_post()) {
    verify_csrf();
    $name = (string) input('name');
    $email = (string) input('email');
    $msg = (string) input('message');
    if ($err = validate_contact($name, $email, $msg)) {
        flash('error', $err);
    } else {
        $topic = in_array(input('subject'), $topics, true) ? input('subject') : 'Diğer';
        $no = create_request('iletisim', $name, $email, (string) input('phone'), $topic . (input('order_no') ? ' · ' . input('order_no') : ''), $msg);
        flash('success', "Mesajınız alındı (kayıt no: $no). En kısa sürede size dönüş yapacağız.");
        redirect('iletisim.php');
    }
}
$u = current_user();
$title = 'İletişim';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / İletişim</nav><h1>İletişim</h1><p>Sorularınız, iade ve değişim talepleriniz için bize yazın.</p></div></section>
<section class="section-sm">
    <div class="container two-col">
        <form method="post" class="card form">
            <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <h2>Mesaj Gönderin</h2>
            <div class="grid-2">
                <label>Ad Soyad *<input name="name" value="<?= e(input('name', $u['name'] ?? '')) ?>" required></label>
                <label>E-posta *<input type="email" name="email" value="<?= e(input('email', $u['email'] ?? '')) ?>" required></label>
                <label>Telefon<input name="phone" value="<?= e(input('phone', $u['phone'] ?? '')) ?>"></label>
                <label>Konu<select name="subject"><?php foreach ($topics as $t): ?><option <?= input('subject') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label>
            </div>
            <label>Sipariş Numarası (varsa)<input name="order_no" value="<?= e(input('order_no')) ?>"></label>
            <label>Mesajınız *<textarea name="message" rows="6" required minlength="10"><?= e(input('message')) ?></textarea></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
            <button class="btn btn-primary">Gönder</button>
        </form>
        <aside class="card" style="background:var(--ant);color:#fff">
            <h3>İletişim Bilgileri</h3>
            <p><small style="opacity:.7">Telefon</small><br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>" style="color:var(--yellow);font-weight:700;font-size:1.2rem"><?= e(setting('company_phone')) ?></a></p>
            <p><small style="opacity:.7">E-posta</small><br><a href="mailto:<?= e(setting('company_email')) ?>" style="color:#fff"><?= e(setting('company_email')) ?></a></p>
            <p><small style="opacity:.7">Çalışma Saatleri</small><br><?= e(setting('working_hours')) ?></p>
            <p><small style="opacity:.7">Adres</small><br><?= e(setting('company_address')) ?></p>
            <p><small style="opacity:.7">Firma</small><br><?= e(setting('company_title')) ?><br>Vergi D./No: <?= e(setting('tax_office')) ?> / <?= e(setting('tax_number')) ?><?= setting('mersis_number') ? '<br>MERSİS: ' . e(setting('mersis_number')) : '' ?></p>
            <hr style="border-color:#444">
            <p class="small">Siparişinizi mi soracaksınız? <a href="<?= url('siparis-takibi.php') ?>" style="color:var(--yellow)">Sipariş takibi</a> · Ürününüz arızalı mı? <a href="<?= url('ariza-takibi.php') ?>" style="color:var(--yellow)">Arıza takibi</a></p>
        </aside>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
