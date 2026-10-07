<?php
require __DIR__ . '/app/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $key = (string) input('key');
    if (isset($_SESSION['cart'][$key])) {
        if (input('action') === 'remove') {
            unset($_SESSION['cart'][$key]);
            flash('success', 'Ürün sepetten çıkarıldı.');
        } else {
            $p = row('SELECT stock FROM products WHERE id = ?', [$_SESSION['cart'][$key]['product_id']]);
            $qty = max(1, min(20, (int) input('qty')));
            if ($p && $qty > $p['stock']) {
                $qty = (int) $p['stock'];
                flash('error', 'Stokta yalnızca ' . $qty . ' adet var.');
            }
            $_SESSION['cart'][$key]['qty'] = $qty;
        }
    }
    redirect('sepet.php');
}

$s = cart_summary();
$title = 'Sepetim';
require __DIR__ . '/app/header.php';
?>
<section class="section-sm">
    <div class="container">
        <h1>Sepetim</h1>
        <?php if (!$s['lines']): ?>
            <div class="card empty"><h3>Sepetiniz boş</h3><p class="muted">Hemen alışverişe başlayın, sarı-kırmızı ruhu üzerinizde taşıyın.</p><a class="btn btn-primary" href="<?= url('urunler.php') ?>">Ürünleri Keşfet</a></div>
        <?php else: ?>
        <div class="two-col">
            <div class="card">
                <table class="cart-table">
                    <?php foreach ($s['lines'] as $l): $p = $l['product']; ?>
                    <tr>
                        <td><div class="cart-item"><?= product_art($p) ?><div><a href="<?= url('urun.php?u=' . $p['slug']) ?>"><?= e($p['name']) ?></a><div class="small muted"><?= $l['size'] ? 'Beden: ' . e($l['size']) . ' · ' : '' ?><?= money($p['price']) ?></div></div></div></td>
                        <td>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="key" value="<?= e($l['key']) ?>">
                                <select name="qty" data-autosubmit aria-label="Adet" style="padding:8px;border-radius:8px;border:1px solid var(--line)">
                                    <?php for ($i = 1; $i <= min(20, max($l['qty'], (int) $p['stock'])); $i++): ?><option <?= $i === $l['qty'] ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                                </select>
                            </form>
                        </td>
                        <td style="text-align:right"><strong><?= money($l['total']) ?></strong></td>
                        <td style="text-align:right"><form method="post"><?= csrf_field() ?><input type="hidden" name="key" value="<?= e($l['key']) ?>"><button class="btn btn-sm" name="action" value="remove" aria-label="Sil" style="background:var(--bg)">✕</button></form></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <a href="<?= url('urunler.php') ?>" class="btn btn-sm mt" style="background:var(--bg)">← Alışverişe devam et</a>
            </div>
            <aside class="card summary">
                <h3>Sipariş Özeti</h3>
                <?php if ($s['free_left'] > 0): $limit = (float) setting('free_shipping_limit'); ?>
                    <div class="free-bar">Kargonun bedava olmasına <strong><?= money($s['free_left']) ?></strong> kaldı<i><b style="width:<?= (int) min(100, $s['subtotal'] / $limit * 100) ?>%"></b></i></div>
                <?php else: ?>
                    <div class="free-bar">🎉 Kargonuz <strong>ücretsiz</strong>!</div>
                <?php endif; ?>
                <dl>
                    <dt>Ara toplam</dt><dd><?= money($s['subtotal']) ?></dd>
                    <dt>Kargo</dt><dd><?= $s['shipping'] > 0 ? money($s['shipping']) : 'Ücretsiz' ?></dd>
                    <dt class="total">Toplam</dt><dd class="total"><?= money($s['total']) ?></dd>
                </dl>
                <a class="btn btn-primary btn-lg btn-block" href="<?= url('odeme.php') ?>">Ödemeye Geç</a>
                <p class="small muted center">Tüm fiyatlara KDV dahildir.</p>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
