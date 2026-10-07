<?php
require __DIR__ . '/app/bootstrap.php';

$positions = ['Müşteri Temsilcisi', 'Depo ve Sevkiyat Sorumlusu', 'E-ticaret İçerik Editörü', 'Genel Başvuru'];
if (is_post()) {
    verify_csrf();
    $name = (string) input('name');
    $email = (string) input('email');
    $msg = (string) input('message');
    if ($err = validate_contact($name, $email, $msg, 30)) {
        flash('error', $err);
    } else {
        $pos = in_array(input('position'), $positions, true) ? input('position') : 'Genel Başvuru';
        $no = create_request('ik', $name, $email, (string) input('phone'), $pos, $msg, [
            'pozisyon' => $pos, 'sehir' => mb_substr((string) input('city'), 0, 60), 'deneyim' => mb_substr((string) input('experience'), 0, 40),
            'linkedin' => filter_var(input('link'), FILTER_VALIDATE_URL) ? input('link') : '',
        ]);
        flash('success', "Başvurunuz alındı (no: $no). İlginiz için teşekkür ederiz!");
        redirect('insan-kaynaklari.php');
    }
}
$page = row("SELECT * FROM pages WHERE slug = 'insan-kaynaklari'");
$title = 'İnsan Kaynakları';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / İnsan Kaynakları</nav><h1>İnsan Kaynakları</h1><p>Sarı-kırmızı ekibe katılın.</p></div></section>
<section class="section-sm">
    <div class="container two-col">
        <article class="prose"><?= fill_placeholders($page['content'] ?? '') ?></article>
        <form method="post" class="card form">
            <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <h3>Başvuru Formu</h3>
            <label>Pozisyon<select name="position"><?php foreach ($positions as $p): ?><option <?= input('position') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select></label>
            <label>Ad Soyad *<input name="name" value="<?= e(input('name')) ?>" required></label>
            <label>E-posta *<input type="email" name="email" value="<?= e(input('email')) ?>" required></label>
            <label>Telefon<input name="phone" value="<?= e(input('phone')) ?>"></label>
            <div class="grid-2">
                <label>Şehir<input name="city" value="<?= e(input('city')) ?>"></label>
                <label>Deneyim<select name="experience"><option>0-1 yıl</option><option>1-3 yıl</option><option>3-5 yıl</option><option>5+ yıl</option></select></label>
            </div>
            <label>LinkedIn / Özgeçmiş bağlantısı<input type="url" name="link" value="<?= e(input('link')) ?>" placeholder="https://"></label>
            <label>Kendinizden bahsedin *<textarea name="message" rows="5" required minlength="30"><?= e(input('message')) ?></textarea></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
            <button class="btn btn-primary">Başvur</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
