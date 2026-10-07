<?php
require __DIR__ . '/app/bootstrap.php';

$types = ['Kişisel verilerimin işlenip işlenmediğini öğrenmek', 'İşlenen verilerim hakkında bilgi talep etmek', 'İşlenme amacını öğrenmek',
    'Aktarıldığı üçüncü kişileri öğrenmek', 'Eksik / yanlış verilerin düzeltilmesi', 'Silme / yok etme', 'İşlemeye itiraz', 'Zararın giderilmesi'];
$relations = ['Müşteri', 'Üye', 'Ziyaretçi', 'Çalışan adayı', 'İş ortağı', 'Diğer'];
if (is_post()) {
    verify_csrf();
    $name = (string) input('name');
    $email = (string) input('email');
    $msg = (string) input('message');
    $chosen = array_values(array_intersect((array) ($_POST['types'] ?? []), $types));
    if ($err = validate_contact($name, $email, $msg, 20)) {
        flash('error', $err);
    } elseif (!$chosen) {
        flash('error', 'En az bir talep türü seçin.');
    } elseif (!input('dogruluk')) {
        flash('error', 'Bilgilerin doğruluğunu onaylayın.');
    } else {
        $no = create_request('kvkk', $name, $email, (string) input('phone'), 'KVKK başvurusu: ' . $chosen[0], $msg, [
            'tc_kimlik_son4' => substr(preg_replace('/\D/', '', (string) input('tc')), -4),
            'iliski' => in_array(input('relation'), $relations, true) ? input('relation') : 'Diğer',
            'talep_turu' => implode(', ', $chosen), 'adres' => mb_substr((string) input('address'), 0, 300),
            'yanit_yontemi' => input('reply') === 'posta' ? 'Posta' : 'E-posta',
        ]);
        flash('success', "Başvurunuz alındı (başvuru no: $no). KVKK m.13 uyarınca en geç 30 gün içinde yanıtlanacaktır.");
        redirect('basvuru-formu.php');
    }
}
$title = 'İlgili Kişi Başvuru Formu';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / İlgili kişi başvuru formu</nav><h1>İlgili Kişi Başvuru Formu</h1><p>6698 sayılı KVKK'nın 11. maddesi kapsamındaki haklarınızı kullanmak için bu formu doldurun.</p></div></section>
<section class="section-sm">
    <div class="container narrow">
        <form method="post" class="card form">
            <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <h3>1. Başvuru Sahibi Bilgileri</h3>
            <div class="grid-2">
                <label>Ad Soyad *<input name="name" value="<?= e(input('name')) ?>" required></label>
                <label>T.C. Kimlik No *<input name="tc" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?= e(input('tc')) ?>" required></label>
                <label>E-posta *<input type="email" name="email" value="<?= e(input('email')) ?>" required></label>
                <label>Telefon<input name="phone" value="<?= e(input('phone')) ?>"></label>
            </div>
            <label>Adres<textarea name="address" rows="2"><?= e(input('address')) ?></textarea></label>
            <label>Şirketimizle ilişkiniz<select name="relation"><?php foreach ($relations as $r): ?><option><?= $r ?></option><?php endforeach; ?></select></label>
            <h3>2. Talebiniz</h3>
            <?php foreach ($types as $t): ?><label class="check"><input type="checkbox" name="types[]" value="<?= e($t) ?>"> <span><?= e($t) ?></span></label><?php endforeach; ?>
            <label>Talebinizin açıklaması *<textarea name="message" rows="5" required minlength="20"><?= e(input('message')) ?></textarea></label>
            <label>Yanıtın iletilme şekli<select name="reply"><option value="eposta">E-posta ile</option><option value="posta">Posta ile adresime</option></select></label>
            <label class="check"><input type="checkbox" name="dogruluk" value="1" required> <span>Bu formda verdiğim bilgilerin doğru ve güncel olduğunu beyan ederim.</span></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
            <button class="btn btn-primary">Başvuruyu Gönder</button>
            <p class="small muted">Güvenliğiniz için T.C. kimlik numaranızın yalnızca son 4 hanesi saklanır. Başvurunuz, kimlik doğrulaması yapıldıktan sonra değerlendirilir. Dilerseniz başvurunuzu ıslak imzalı olarak <?= e(setting('company_address')) ?> adresine<?= setting('kep_address') ? ' veya KEP adresimize (' . e(setting('kep_address')) . ')' : '' ?> de iletebilirsiniz.</p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
