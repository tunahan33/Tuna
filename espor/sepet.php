<?php
/** Sepet: paket ekleme / çıkarma ve sepet görünümü */
require __DIR__ . '/includes/bootstrap.php';

/** Yalnızca site içi göreli adreslere geri dönülür */
function cart_back(string $fallback = 'sepet.php'): string
{
    $b = (string) input('geri');
    return ($b !== '' && preg_match('#^[a-z0-9\-]+\.php(\?[^\s]*)?(\#[\w\-]+)?$#i', $b)) ? $b : $fallback;
}

if (is_post()) {
    verify_csrf();
    $action = input('action');
    $id = (int) input('paket');
    if ($action === 'add') {
        $p = row('SELECT p.id, p.name, s.title AS service_title FROM packages p JOIN services s ON s.id = p.service_id WHERE p.id = ? AND p.is_active = 1 AND s.is_active = 1', [$id]);
        if (!$p) {
            flash('error', 'Paket bulunamadı veya satışta değil.');
            redirect(cart_back());
        }
        $added = cart_add($id);
        if ($added) {
            log_activity('Sepete ekledi', $p['service_title'] . ' / ' . $p['name'], 'package', $id);
        }
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch' && input('go') !== 'odeme') {
            header('Content-Type: application/json; charset=utf-8');
            exit(json_encode(['ok' => true, 'added' => $added, 'count' => cart_count(), 'message' => ($added ? '“' . $p['name'] . '” sepete eklendi.' : '“' . $p['name'] . '” zaten sepetinizde.'), 'cart' => url('sepet.php')], JSON_UNESCAPED_UNICODE));
        }
        if (input('go') === 'odeme') {
            redirect('odeme.php');
        }
        flash($added ? 'success' : 'info', $added ? '“' . $p['name'] . '” sepete eklendi.' : '“' . $p['name'] . '” zaten sepetinizde.');
        redirect(cart_back());
    }
    if ($action === 'remove') {
        cart_remove($id);
        flash('info', 'Paket sepetten çıkarıldı.');
    }
    if ($action === 'clear') {
        cart_clear();
        flash('info', 'Sepetiniz boşaltıldı.');
    }
    redirect('sepet.php');
}

$items = cart_items();
$total = cart_total($items);
$oldTotal = round(array_sum(array_map(fn($p) => (float) ($p['old_price'] && $p['old_price'] > $p['price'] ? $p['old_price'] : $p['price']), $items)), 2);
$suggest = $items ? rows('SELECT p.*, s.title AS service_title FROM packages p JOIN services s ON s.id = p.service_id WHERE p.is_active = 1 AND s.is_active = 1 AND p.is_featured = 1
    AND p.id NOT IN (' . implode(',', array_fill(0, count($items), '?')) . ') ORDER BY s.sort_order LIMIT 3', array_column($items, 'id')) : [];
$pageTitle = 'Sepetim';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero slim"><div class="container">
    <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Sepetim</nav>
    <h1>Sepetim</h1>
</div></section>
<section class="section"><div class="container">
    <?php if (!$items): ?>
        <div class="card center cart-empty">
            <span class="pay-lock" style="margin:0 auto 1rem"><?= service_icon('gamepad') ?></span>
            <h2 class="h3">Sepetiniz boş</h2>
            <p class="muted">Koçluk türlerini inceleyip istediğiniz paketleri sepete ekleyebilirsiniz; birden fazla koçluğu tek ödemeyle alabilirsiniz.</p>
            <a class="btn btn-primary" href="<?= url('paketler.php') ?>">Paketleri İncele</a>
        </div>
    <?php else: ?>
    <div class="checkout-grid">
        <div class="card">
            <div class="cart-head"><h2 class="h3">Sepetteki Paketler <small class="muted">(<?= count($items) ?>)</small></h2>
                <form method="post" onsubmit="return confirm('Sepetteki tüm paketler çıkarılsın mı?')"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-xs btn-outline">Sepeti Boşalt</button></form>
            </div>
            <ul class="cart-list">
                <?php foreach ($items as $p): ?>
                <li class="cart-item">
                    <span class="service-icon"><?= service_icon($p['service_icon']) ?></span>
                    <div class="cart-item-main">
                        <small class="pkg-service"><?= e($p['service_title']) ?></small>
                        <strong><?= e($p['name']) ?></strong>
                        <div class="pkg-meta"><span><?= e($p['duration']) ?></span><?php if ($p['sessions']): ?><span><?= e($p['sessions']) ?></span><?php endif; ?></div>
                        <button type="button" class="cart-detail" data-open-package="<?= (int) $p['id'] ?>">Detayları gör</button>
                    </div>
                    <div class="cart-item-side">
                        <?php if ($p['old_price'] && $p['old_price'] > $p['price']): ?><del><?= money($p['old_price']) ?></del><?php endif; ?>
                        <strong><?= money($p['price']) ?></strong>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="paket" value="<?= (int) $p['id'] ?>"><button class="cart-remove" aria-label="<?= e($p['name']) ?> paketini sepetten çıkar">Kaldır ✕</button></form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <a class="small" href="<?= url('paketler.php') ?>">← Alışverişe devam et</a>
        </div>
        <aside class="card summary">
            <h3>Sepet Özeti</h3>
            <dl class="voucher-lines">
                <dt><?= count($items) ?> paket</dt><dd><?= money($oldTotal) ?></dd>
                <?php if ($oldTotal > $total): ?><dt>İndirim</dt><dd>− <?= money($oldTotal - $total) ?></dd><?php endif; ?>
            </dl>
            <div class="summary-total"><span>Toplam (KDV dahil)</span><strong><?= money($total) ?></strong></div>
            <p class="small muted">İade çeki kodunuz varsa ödeme adımında tutardan düşülür.</p>
            <a class="btn btn-primary btn-lg btn-block" href="<?= url('odeme.php') ?>">Ödemeye Geç</a>
            <img src="<?= asset('img/payment-logos.svg') ?>" alt="Visa, Mastercard, Troy, 3D Secure" class="w100 mt-1">
        </aside>
    </div>
    <?php if ($suggest): require __DIR__ . '/includes/partials.php'; ?>
        <div class="section-head left cart-suggest"><h2 class="h3">Bunlar da ilginizi çekebilir</h2></div>
        <div class="pkg-grid"><?php foreach ($suggest as $sp) echo package_card($sp, true); ?></div>
    <?php endif; ?>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
