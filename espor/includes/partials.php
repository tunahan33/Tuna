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
            <a class="btn btn-primary btn-block" href="<?= url('odeme.php?paket=' . (int) $p['id']) ?>">Satın Al</a>
        </div>
    </article>
    <?php return ob_get_clean();
}
