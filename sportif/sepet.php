<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $action = isset($_POST['remove']) ? 'remove' : input('action');
    if ($action === 'add') {
        $v = row('SELECT id FROM product_variants WHERE product_id = ? AND color = ? AND size = ?', [(int) input('product_id'), input('color'), input('size')]);
        $back = $_SERVER['HTTP_REFERER'] ?? url('urunler.php');
        if (!$v) {
            flash('error', 'Lütfen renk ve beden seçin.');
            header('Location: ' . $back, true, 303);
            exit;
        }
        $err = cart_add((int) $v['id'], (int) input('qty', 1), (string) input('print_name'), (string) input('print_number'));
        if ($err) {
            flash('error', $err);
            header('Location: ' . $back, true, 303);
            exit;
        }
        flash('success', 'Ürün sepete eklendi.');
    } elseif ($action === 'update') {
        foreach ((array) ($_POST['qty'] ?? []) as $key => $qty) {
            if (!isset($_SESSION['cart'][$key])) continue;
            $qty = (int) $qty;
            if ($qty <= 0) unset($_SESSION['cart'][$key]);
            else $_SESSION['cart'][$key]['qty'] = min($qty, 99);
        }
        flash('success', 'Sepet güncellendi.');
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][(string) $_POST['remove']]);
        flash('success', 'Ürün sepetten çıkarıldı.');
    } elseif ($action === 'coupon') {
        $code = mb_strtoupper(trim((string) input('coupon')));
        $sum = cart_summary($code);
        if ($sum['coupon']) {
            $_SESSION['coupon'] = $code;
            flash('success', 'Kupon uygulandı: ' . coupon_label($sum['coupon']));
        } else {
            unset($_SESSION['coupon']);
            flash('error', $sum['coupon_error'] ?: 'Kupon kodu geçersiz.');
        }
    } elseif ($action === 'coupon_remove') {
        unset($_SESSION['coupon']);
    }
    redirect('sepet.php');
}

$sum = cart_summary();
if (!empty($_SESSION['coupon']) && !$sum['coupon']) {
    $sum['warnings'][] = 'Kupon artık geçerli değil: ' . $sum['coupon_error'];
    unset($_SESSION['coupon']);
}
$pageTitle = 'Sepetim';
require __DIR__ . '/includes/header.php';
?>
<section class="section-sm"><div class="container">
    <h1 class="h2">Sepetim <?= $sum['count'] ? '<small class="muted">(' . $sum['count'] . ' ürün)</small>' : '' ?></h1>
    <?php foreach ($sum['warnings'] as $w): ?><div class="alert alert-info"><?= e($w) ?></div><?php endforeach; ?>
    <?php if (!$sum['lines']): ?>
        <div class="empty card"><h3>Sepetin boş</h3><p>Hemen alışverişe başlayabilirsin.</p><a class="btn btn-primary" href="<?= url('urunler.php') ?>">Ürünleri Keşfet</a></div>
    <?php else: ?>
    <div class="cart-grid">
        <form method="post" class="card cart-lines">
            <?= csrf_field() ?><input type="hidden" name="action" value="update">
            <?php foreach ($sum['lines'] as $l): ?>
                <div class="cart-line">
                    <a href="<?= url('urun.php?u=' . urlencode($l['slug'])) ?>" class="cl-img"><?= product_thumb($l['product']) ?></a>
                    <div class="cl-info">
                        <a href="<?= url('urun.php?u=' . urlencode($l['slug'])) ?>"><strong><?= e($l['name']) ?></strong></a>
                        <span class="muted small"><i class="dot-color" style="background:<?= e($l['color_hex']) ?>"></i><?= e($l['color']) ?> · Beden: <?= e($l['size']) ?></span>
                        <?php if ($l['personal']): ?><span class="tag tag-yellow">Baskı: <?= e(trim($l['print_name'] . ' ' . $l['print_number'])) ?></span><?php endif; ?>
                        <span class="small"><?= money($l['unit_price']) ?> / adet</span>
                    </div>
                    <div class="qty sm"><button type="button" data-qty="-1">−</button><input name="qty[<?= e($l['key']) ?>]" type="number" value="<?= $l['qty'] ?>" min="0" max="<?= $l['stock'] ?>" aria-label="Adet"><button type="button" data-qty="1">+</button></div>
                    <strong class="cl-total"><?= money($l['line_total']) ?></strong>
                    <button class="cl-remove" name="remove" value="<?= e($l['key']) ?>" aria-label="Kaldır" title="Sepetten çıkar">×</button>
                </div>
            <?php endforeach; ?>
            <div class="cart-actions"><a href="<?= url('urunler.php') ?>" class="btn btn-outline btn-sm">← Alışverişe devam</a><button class="btn btn-dark btn-sm">Sepeti Güncelle</button></div>
        </form>
        <aside class="card summary">
            <h3>Sipariş Özeti</h3>
            <?php if ($sum['free_shipping_left'] > 0): $lim = (float) setting('free_shipping_limit'); ?>
                <div class="ship-progress"><span>Kargonun bedava olmasına <strong><?= money($sum['free_shipping_left']) ?></strong> kaldı</span><i style="width:<?= round(100 - $sum['free_shipping_left'] / $lim * 100) ?>%"></i></div>
            <?php elseif ($sum['shipping'] == 0): ?>
                <div class="ship-progress done"><span>🎉 Kargo bedava!</span><i style="width:100%"></i></div>
            <?php endif; ?>
            <div class="sum-rows">
                <div><span>Ara Toplam</span><span><?= money($sum['subtotal']) ?></span></div>
                <?php if ($sum['coupon']): ?><div class="disc"><span>İndirim (<?= e($sum['coupon']['code']) ?>)</span><span>−<?= money($sum['discount']) ?></span></div><?php endif; ?>
                <div><span>Kargo</span><span><?= $sum['shipping'] > 0 ? money($sum['shipping']) : 'Ücretsiz' ?></span></div>
            </div>
            <div class="summary-total"><span>Toplam (KDV dahil)</span><strong><?= money($sum['total']) ?></strong></div>
            <?php if ($sum['coupon']): ?>
                <form method="post" class="coupon-applied"><?= csrf_field() ?><input type="hidden" name="action" value="coupon_remove"><span>🏷️ <strong><?= e($sum['coupon']['code']) ?></strong> · <?= e(coupon_label($sum['coupon'])) ?></span><button class="link-btn">Kaldır</button></form>
            <?php else: ?>
                <form method="post" class="coupon-form"><?= csrf_field() ?><input type="hidden" name="action" value="coupon"><input name="coupon" placeholder="İndirim kodu" aria-label="İndirim kodu" required><button class="btn btn-dark btn-sm">Uygula</button></form>
            <?php endif; ?>
            <a href="<?= url('odeme.php') ?>" class="btn btn-primary btn-lg btn-block mt-1">Siparişi Tamamla</a>
            <img src="<?= asset('img/payment-logos.svg') ?>" alt="Visa, Mastercard, Troy, 3D Secure" class="w100 mt-1">
        </aside>
    </div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
