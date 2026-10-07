<?php
require __DIR__ . '/app/bootstrap.php';

$ticket = null;
$tab = input('islem') === 'yeni' ? 'yeni' : 'sorgula';
if (is_post()) {
    verify_csrf();
    $name = (string) input('name');
    $email = (string) input('email');
    $desc = (string) input('message');
    $orderNo = strtoupper((string) input('order_no'));
    $product = (string) input('product');
    if ($err = validate_contact($name, $email, $desc, 20)) {
        flash('error', $err);
        $tab = 'yeni';
    } elseif ($product === '') {
        flash('error', 'Arızalı ürünü belirtin.');
        $tab = 'yeni';
    } else {
        $no = create_request('ariza', $name, $email, (string) input('phone'), $product . ' - arıza bildirimi', $desc,
            ['siparis_no' => $orderNo, 'urun' => $product, 'satin_alma' => (string) input('purchase_date')]);
        flash('success', "Arıza kaydınız oluşturuldu. Kayıt numaranız: $no — bu numarayla sürecinizi takip edebilirsiniz.");
        redirect('ariza-takibi.php?no=' . urlencode($no) . '&email=' . urlencode($email));
    }
}
$no = strtoupper(trim((string) input('no')));
if ($no !== '' && !is_post()) {
    $ticket = row("SELECT * FROM requests WHERE type = 'ariza' AND ticket_no = ? AND lower(email) = ?", [$no, mb_strtolower((string) input('email'))]);
    if (!$ticket) {
        flash('error', 'Bu kayıt numarası ve e-posta ile eşleşen bir arıza kaydı bulunamadı.');
    }
}
$title = 'Arıza Takibi';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Arıza takibi</nav><h1>Arıza Takibi</h1><p>Kusurlu veya arızalı ürünleriniz için kayıt açın, süreci adım adım takip edin.</p></div></section>
<section class="section-sm">
    <div class="container narrow">
        <?php if ($ticket): $steps = ['yeni' => 'Kayıt Alındı', 'inceleniyor' => 'İnceleniyor', 'serviste' => 'Teknik Serviste', 'cozuldu' => 'Çözüldü']; $keys = array_keys($steps); $pos = array_search($ticket['status'], $keys, true); $x = json_decode((string) $ticket['extra'], true) ?: []; ?>
            <div class="card" style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><h3 style="margin:0">Kayıt <?= e($ticket['ticket_no']) ?></h3><?= request_badge($ticket['status']) ?></div>
                <p class="small muted"><?= tr_date($ticket['created_at']) ?> · <?= e($x['urun'] ?? $ticket['subject']) ?><?= !empty($x['siparis_no']) ? ' · Sipariş ' . e($x['siparis_no']) : '' ?></p>
                <div class="track"><?php foreach ($keys as $i => $k): ?><div class="<?= $pos === false ? ($ticket['status'] === 'kapandi' ? 'done' : '') : ($i < $pos ? 'done' : ($i === $pos ? 'current' : '')) ?>"><?= $steps[$k] ?></div><?php endforeach; ?></div>
                <p><strong>Bildiriminiz:</strong> <?= nl2br(e($ticket['message'])) ?></p>
                <?php if ($ticket['reply']): ?><div class="alert alert-info"><strong>Yetkili yanıtı:</strong> <?= nl2br(e($ticket['reply'])) ?></div><?php else: ?><p class="muted small">Kaydınız ekibimize ulaştı. En geç 2 iş günü içinde değerlendirme sonucu burada görünecektir.</p><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tabs" data-tabs style="margin-top:0">
            <div class="tab-btns">
                <button class="<?= $tab === 'sorgula' ? 'active' : '' ?>" data-tab="t-sorgula">Kaydımı Sorgula</button>
                <button class="<?= $tab === 'yeni' ? 'active' : '' ?>" data-tab="t-yeni">Yeni Arıza Kaydı Aç</button>
            </div>
            <form class="tab-panel form <?= $tab === 'sorgula' ? 'active' : '' ?>" id="t-sorgula">
                <div class="grid-2">
                    <label>Kayıt Numarası<input name="no" value="<?= e($no) ?>" placeholder="Örn. ARZ-261007-123" required></label>
                    <label>E-posta<input type="email" name="email" value="<?= e(input('email')) ?>" required></label>
                </div>
                <button class="btn btn-primary">Sorgula</button>
            </form>
            <form method="post" class="tab-panel form <?= $tab === 'yeni' ? 'active' : '' ?>" id="t-yeni">
                <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off"><input type="hidden" name="islem" value="yeni">
                <div class="grid-2">
                    <label>Ad Soyad *<input name="name" value="<?= e(input('name', current_user()['name'] ?? '')) ?>" required></label>
                    <label>E-posta *<input type="email" name="email" value="<?= e(input('email', current_user()['email'] ?? '')) ?>" required></label>
                    <label>Telefon<input name="phone" value="<?= e(input('phone')) ?>"></label>
                    <label>Sipariş Numarası<input name="order_no" value="<?= e(input('order_no')) ?>" placeholder="Biliyorsanız"></label>
                    <label>Arızalı Ürün *<input name="product" value="<?= e(input('product')) ?>" required placeholder="Örn. GS Strike FG Krampon"></label>
                    <label>Satın Alma Tarihi<input type="date" name="purchase_date" value="<?= e(input('purchase_date')) ?>"></label>
                </div>
                <label>Arızanın Açıklaması *<textarea name="message" rows="5" required minlength="20" placeholder="Sorunu ve ne zaman ortaya çıktığını kısaca anlatın"><?= e(input('message')) ?></textarea></label>
                <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
                <button class="btn btn-primary">Arıza Kaydı Oluştur</button>
            </form>
        </div>
        <div class="prose" style="margin-top:20px">
            <h3 style="margin-top:0">Arıza süreci nasıl işler?</h3>
            <ol><li>Formu doldurun; size bir <strong>kayıt numarası</strong> verilir.</li><li>Ekibimiz kaydı inceler, gerekirse ürünü ücretsiz kargo koduyla teknik servise alır.</li><li>Üretim hatası tespit edilirse ürün <strong>onarılır, değiştirilir veya bedeli iade edilir</strong>.</li><li>Her adımı bu sayfadan kayıt numaranız ve e-postanızla takip edebilirsiniz.</li></ol>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
