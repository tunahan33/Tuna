<?php
require __DIR__ . '/includes/bootstrap.php';

$min = (int) setting('team_min_qty', '10');
$old = ['club_name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'city' => '', 'sport' => 'Futbol', 'quantity' => $min, 'products' => [], 'needed_by' => '', 'message' => ''];
$u = current_user();
if ($u) { $old['contact_name'] = $u['name']; $old['email'] = $u['email']; $old['phone'] = (string) $u['phone']; }
$productOptions = ['Maç forması (isim-numara baskılı)', 'Kaleci forması', 'Maç şortu', 'Eşofman takımı', 'Antrenman tişörtü', 'Sweatshirt / Hoodie', 'Çorap', 'Spor çantası'];

if (is_post()) {
    verify_csrf();
    if (input('website') !== '') redirect('takim-siparisi.php');
    foreach (['club_name', 'contact_name', 'email', 'phone', 'city', 'sport', 'needed_by', 'message'] as $k) $old[$k] = mb_substr(trim((string) input($k)), 0, $k === 'message' ? 3000 : 160);
    $old['quantity'] = (int) input('quantity');
    $old['products'] = array_values(array_intersect((array) ($_POST['products'] ?? []), $productOptions));
    $err = null;
    if (mb_strlen($old['club_name']) < 2) $err = 'Kulüp / okul / kurum adını girin.';
    elseif (mb_strlen($old['contact_name']) < 3) $err = 'Yetkili adını girin.';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $err = 'Geçerli bir e-posta girin.';
    elseif (strlen(preg_replace('/\D/', '', $old['phone'])) < 10) $err = 'Geçerli bir telefon girin.';
    elseif ($old['quantity'] < $min) $err = 'Takım siparişleri en az ' . $min . ' adettir. Daha az adet için ürünleri doğrudan sepete ekleyebilirsiniz.';
    elseif (!$old['products']) $err = 'İhtiyacınız olan en az bir ürün seçin.';
    elseif (!input('kvkk')) $err = 'KVKK Aydınlatma Metni onaylanmalıdır.';
    if ($err) {
        flash('error', $err);
    } else {
        $id = insert('team_requests', [
            'club_name' => $old['club_name'], 'contact_name' => $old['contact_name'], 'email' => $old['email'], 'phone' => $old['phone'], 'city' => $old['city'],
            'sport' => $old['sport'], 'quantity' => $old['quantity'], 'products' => implode(', ', $old['products']),
            'needed_by' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['needed_by']) ? $old['needed_by'] : null, 'message' => $old['message'],
            'status' => 'new', 'ip' => client_ip(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        log_activity('Takım siparişi talebi gönderildi', $old['club_name'] . ' · ' . $old['quantity'] . ' adet', 'team', $id);
        send_mail(setting('notify_email'), 'Yeni takım siparişi talebi: ' . $old['club_name'], '<p><b>' . e($old['club_name']) . '</b> · ' . (int) $old['quantity'] . ' adet</p><p>' . e($old['contact_name']) . ' · ' . e($old['phone']) . ' · ' . e($old['email']) . '</p><p>' . e(implode(', ', $old['products'])) . '</p><p>' . nl2br(e($old['message'])) . '</p>');
        send_mail($old['email'], 'Takım siparişi talebiniz alındı', '<p>Merhaba ' . e($old['contact_name']) . ',</p><p><b>' . e($old['club_name']) . '</b> için talebinizi aldık. Ekibimiz en geç 1 iş günü içinde size özel fiyat teklifiyle dönüş yapacak.</p>');
        flash('success', 'Talebiniz alındı! En geç 1 iş günü içinde size özel teklifle dönüş yapacağız.');
        redirect('takim-siparisi.php');
    }
}
$pageTitle = 'Takım ve Toplu Sipariş';
$pageDesc = 'Kulüp, okul ve kurumlar için isim-numara baskılı forma, eşofman ve spor giyim toplu siparişi.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Takım Siparişi</nav>
    <h1>Takımına Özel Forma ve Toplu Sipariş</h1>
    <p>Kulüpler, okullar ve kurumlar için logo, isim ve numara baskılı formalar; eşofman takımları ve aksesuarlar. En az <?= $min ?> adetlik siparişlerde özel fiyat teklifi.</p>
</div></section>
<section class="section-sm"><div class="container contact-grid">
    <div class="card">
        <h2 class="h3">Ücretsiz Teklif Al</h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <div class="grid-2">
                <label>Kulüp / Okul / Kurum Adı *<input name="club_name" value="<?= e($old['club_name']) ?>" required></label>
                <label>Branş<select name="sport"><?php foreach (['Futbol', 'Basketbol', 'Voleybol', 'Hentbol', 'Atletizm', 'Okul / Beden Eğitimi', 'Kurumsal / Etkinlik', 'Diğer'] as $s): ?><option <?= $old['sport'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
                <label>Yetkili Adı Soyadı *<input name="contact_name" value="<?= e($old['contact_name']) ?>" required></label>
                <label>Telefon *<input name="phone" value="<?= e($old['phone']) ?>" required></label>
                <label>E-posta *<input type="email" name="email" value="<?= e($old['email']) ?>" required></label>
                <label>İl<input name="city" value="<?= e($old['city']) ?>"></label>
                <label>Toplam Adet * <small>(en az <?= $min ?>)</small><input type="number" name="quantity" min="<?= $min ?>" value="<?= (int) $old['quantity'] ?>" required></label>
                <label>İhtiyaç Tarihi<input type="date" name="needed_by" value="<?= e($old['needed_by']) ?>" min="<?= date('Y-m-d') ?>"></label>
            </div>
            <label>İhtiyacınız Olan Ürünler *</label>
            <div class="chip-checks"><?php foreach ($productOptions as $po): ?><label><input type="checkbox" name="products[]" value="<?= e($po) ?>" <?= in_array($po, $old['products'], true) ? 'checked' : '' ?>><span><?= e($po) ?></span></label><?php endforeach; ?></div>
            <label>Detaylar <small>(renk tercihi, logo, beden dağılımı, isim-numara listesi vb.)</small><textarea name="message" rows="5"><?= e($old['message']) ?></textarea></label>
            <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('sayfa.php?s=kvkk-aydinlatma-metni') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum.</span></label>
            <button class="btn btn-primary btn-lg">Teklif İste</button>
        </form>
    </div>
    <div>
        <div class="card dark">
            <h3>Nasıl çalışıyoruz?</h3>
            <ol class="team-steps">
                <li><strong>Talebini gönder</strong><span>Adet, ürün ve tarih bilgisini paylaş.</span></li>
                <li><strong>Teklif ve tasarım</strong><span>1 iş günü içinde fiyat teklifi ve forma tasarım önizlemesi.</span></li>
                <li><strong>Beden ve isim listesi</strong><span>Oyuncu bedenleri, isim ve numaraları alınır.</span></li>
                <li><strong>Üretim ve teslimat</strong><span>Onaydan sonra baskı yapılır, takım halinde paketlenip gönderilir.</span></li>
            </ol>
        </div>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
