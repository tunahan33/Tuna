<?php
/** Tekrar kullanılan görünüm parçaları */

function package_card(array $p, bool $showService = false): string
{
    ob_start(); ?>
    <article class="pkg-card<?= $p['is_featured'] ? ' featured' : '' ?>" data-package="<?= (int) $p['id'] ?>" tabindex="0" role="button" aria-label="<?= e($p['name']) ?> detayları">
        <?php if ($p['is_featured']): ?><span class="pkg-ribbon">En Çok Tercih Edilen</span><?php endif; ?>
        <?php if ($showService): ?><div class="pkg-service"><?= e($p['service_title']) ?></div><?php endif; ?>
        <h3><?= e($p['name']) ?></h3>
        <div class="pkg-meta"><span><?= e($p['duration']) ?></span><?php if ($p['sessions']): ?><span><?= e($p['sessions']) ?></span><?php endif; ?></div>
        <div class="pkg-price">
            <?php if ($p['old_price'] && $p['old_price'] > $p['price']): ?><del><?= money($p['old_price']) ?></del><?php endif; ?>
            <strong><?= money($p['price']) ?></strong>
            <small>KDV dahil</small>
        </div>
        <p><?= e($p['short_desc']) ?></p>
        <ul class="check-list">
            <?php foreach (array_slice(features_list($p['features']), 0, 4) as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
        </ul>
        <div class="pkg-actions">
            <button type="button" class="btn btn-outline btn-block" data-open-package="<?= (int) $p['id'] ?>">Detayları İncele</button>
            <?= cart_button((int) $p['id']) ?>
        </div>
    </article>
    <?php return ob_get_clean();
}

/** Sepete ekle düğmesi (paket sepetteyse sepete gider) */
function cart_button(int $id, bool $large = false, bool $buyNow = false): string
{
    $cls = 'btn btn-primary btn-block' . ($large ? ' btn-lg' : '');
    if (cart_has($id)) {
        return '<a class="btn btn-dark btn-block' . ($large ? ' btn-lg' : '') . ' in-cart" href="' . url('sepet.php') . '">✓ Sepette · Sepete Git</a>';
    }
    $back = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    if ($back === 'paket.php') {
        $back = 'paketler.php';
    }
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $geri = $back . ($qs !== '' && $back !== 'paketler.php' ? '?' . $qs : '');
    $html = '<form method="post" action="' . url('sepet.php') . '" class="cart-form">' . csrf_field()
        . '<input type="hidden" name="action" value="add"><input type="hidden" name="paket" value="' . $id . '"><input type="hidden" name="geri" value="' . e($geri) . '">'
        . '<button class="' . $cls . '">Sepete Ekle</button>';
    if ($buyNow) {
        $html .= '<button class="btn btn-outline btn-block' . ($large ? ' btn-lg' : '') . '" name="go" value="odeme">Hemen Satın Al</button>';
    }
    return $html . '</form>';
}
