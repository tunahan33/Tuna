<?php
require __DIR__ . '/app/bootstrap.php';

$order = null;
$no = strtoupper(trim((string) input('no')));
$email = mb_strtolower(trim((string) input('email')));
if ($no !== '' || $email !== '') {
    $order = row('SELECT * FROM orders WHERE order_no = ? AND lower(email) = ?', [$no, $email]);
    if (!$order) {
        flash('error', 'Bu sipariş numarası ve e-posta ile eşleşen bir sipariş bulunamadı.');
    }
}
$title = 'Sipariş Takibi';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Sipariş takibi</nav><h1>Sipariş Takibi</h1><p>Siparişinizin hangi aşamada olduğunu anında öğrenin.</p></div></section>
<section class="section-sm">
    <div class="container narrow">
        <form class="card form" style="margin-bottom:20px">
            <div class="grid-2">
                <label>Sipariş Numarası<input name="no" value="<?= e($no) ?>" placeholder="Örn. GS2610071234" required></label>
                <label>E-posta Adresi<input type="email" name="email" value="<?= e($email) ?>" placeholder="Siparişte kullandığınız e-posta" required></label>
            </div>
            <button class="btn btn-primary">Siparişimi Sorgula</button>
            <p class="small muted" style="margin:0">Üyeyseniz tüm siparişlerinizi <a href="<?= url('hesabim.php') ?>">Hesabım</a> sayfasından görebilirsiniz.</p>
        </form>
        <?php if ($order): ?><div class="card"><?= order_track_html($order) ?></div><?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
