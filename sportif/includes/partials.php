<?php
/** Tekrar kullanılan görünüm parçaları */

function product_card(array $p): string
{
    $stock = isset($p['total_stock']) ? (int) $p['total_stock'] : product_stock((int) $p['id']);
    $off = discount_percent($p);
    $colors = rows('SELECT DISTINCT color, color_hex FROM product_variants WHERE product_id = ? ORDER BY sort_order', [$p['id']]);
    ob_start(); ?>
    <a class="product-card<?= $stock <= 0 ? ' soldout' : '' ?>" href="<?= url('urun.php?u=' . urlencode($p['slug'])) ?>">
        <div class="pc-media">
            <?= product_thumb($p, 'pc-img') ?>
            <div class="pc-badges">
                <?php if ($off): ?><span class="tag tag-red">%<?= $off ?> İndirim</span><?php endif; ?>
                <?php if ($p['personalizable']): ?><span class="tag tag-yellow">İsim-Numara</span><?php endif; ?>
                <?php if ($stock <= 0): ?><span class="tag tag-dark">Tükendi</span><?php elseif ($stock <= (int) setting('low_stock_limit', '3')): ?><span class="tag tag-dark">Son <?= $stock ?> ürün</span><?php endif; ?>
            </div>
        </div>
        <div class="pc-body">
            <h3><?= e($p['name']) ?></h3>
            <div class="pc-swatches"><?php foreach ($colors as $c): ?><span style="background:<?= e($c['color_hex']) ?>" title="<?= e($c['color']) ?>"></span><?php endforeach; ?></div>
            <div class="pc-price">
                <strong><?= money($p['price']) ?></strong>
                <?php if ($off): ?><del><?= money($p['old_price']) ?></del><?php endif; ?>
            </div>
        </div>
    </a>
    <?php return ob_get_clean();
}
