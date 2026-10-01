<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

$id = (int) input('id');
$p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$p) redirect('admin/products.php');
if (!$p && !can('content.create')) {
    require __DIR__ . '/_forbidden.php';
    exit;
}
$p = $p ?? ['category_id' => (int) input('category'), 'name' => '', 'slug' => '', 'short_desc' => '', 'description' => '', 'features' => '', 'gender' => 'unisex',
    'price' => '', 'old_price' => '', 'images' => '[]', 'art' => 'tshirt', 'art_color' => '#C8102E', 'personalizable' => 0, 'personalization_price' => 0, 'is_featured' => 0, 'is_active' => 1];
$cats = rows('SELECT id, name FROM categories ORDER BY sort_order');
$canPrice = can('prices.edit');
$canCreate = can('content.create');
$canStock = can('stock.edit');
$dec = fn($v) => (float) str_replace(',', '.', (string) $v);

if (is_post() && input('action') === 'variants' && $id && $canStock) {
    verify_csrf();
    $changes = [];
    foreach ((array) ($_POST['v'] ?? []) as $vid => $vd) {
        $v = row('SELECT * FROM product_variants WHERE id = ? AND product_id = ?', [(int) $vid, $id]);
        if (!$v) continue;
        if (!empty($vd['delete'])) {
            q('DELETE FROM product_variants WHERE id = ?', [$v['id']]);
            $changes[] = $v['size'] . '/' . $v['color'] . ' silindi';
            continue;
        }
        $stock = max(0, (int) ($vd['stock'] ?? 0));
        $hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $vd['color_hex'] ?? '') ? $vd['color_hex'] : $v['color_hex'];
        q('UPDATE product_variants SET stock = ?, sku = ?, color_hex = ? WHERE id = ?', [$stock, mb_substr(trim($vd['sku'] ?? ''), 0, 60), $hex, $v['id']]);
        if ($stock !== (int) $v['stock']) $changes[] = $v['size'] . '/' . $v['color'] . ': ' . $v['stock'] . ' → ' . $stock;
    }
    // Toplu ekleme: bedenler x renkler
    $sizes = array_values(array_filter(array_map('trim', explode(',', (string) input('new_sizes')))));
    $colors = [];
    foreach (preg_split('/\R/', (string) input('new_colors')) as $line) {
        [$cn, $ch] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        if ($cn !== '') $colors[mb_substr($cn, 0, 60)] = preg_match('/^#[0-9A-Fa-f]{6}$/', $ch) ? $ch : '#2B2F33';
    }
    if ($sizes && !$colors) {
        foreach (rows('SELECT DISTINCT color, color_hex FROM product_variants WHERE product_id = ?', [$id]) as $c) $colors[$c['color']] = $c['color_hex'];
    }
    if ($colors && !$sizes) {
        $sizes = array_column(rows('SELECT DISTINCT size FROM product_variants WHERE product_id = ?', [$id]), 'size');
    }
    $added = 0;
    $n = (int) val('SELECT COALESCE(MAX(sort_order),0) FROM product_variants WHERE product_id = ?', [$id]);
    foreach ($colors as $cn => $ch) {
        foreach ($sizes as $sz) {
            $sz = mb_substr($sz, 0, 20);
            if (row('SELECT id FROM product_variants WHERE product_id = ? AND size = ? AND color = ?', [$id, $sz, $cn])) continue;
            insert('product_variants', ['product_id' => $id, 'size' => $sz, 'color' => $cn, 'color_hex' => $ch, 'sku' => null, 'stock' => max(0, (int) input('new_stock')), 'sort_order' => ++$n]);
            $added++;
        }
    }
    if ($added) $changes[] = $added . ' yeni seçenek eklendi';
    if ($changes) {
        q('UPDATE products SET updated_at = ? WHERE id = ?', [now(), $id]);
        log_activity('Stok / seçenek güncelledi', $p['name'] . ' · ' . mb_strimwidth(implode(', ', $changes), 0, 600, '…'), 'product', $id);
    }
    flash('success', $changes ? 'Stok ve seçenekler güncellendi.' : 'Değişiklik yapılmadı.');
    redirect('admin/product_edit.php?id=' . $id . '#stok');
}

if (is_post() && input('action') === 'save') {
    verify_csrf();
    $data = [
        'name' => mb_substr(input('name'), 0, 190),
        'short_desc' => mb_substr(input('short_desc'), 0, 400),
        'description' => sanitize_html((string) ($_POST['description'] ?? '')),
        'features' => mb_substr(input('features'), 0, 3000),
        'gender' => array_key_exists(input('gender'), GENDERS) ? input('gender') : 'unisex',
        'art' => array_key_exists(input('art'), PRODUCT_ARTS) ? input('art') : 'tshirt',
        'art_color' => preg_match('/^#[0-9A-Fa-f]{6}$/', input('art_color')) ? input('art_color') : '#C8102E',
        'updated_at' => now(),
    ];
    if ($canCreate) {
        $data['category_id'] = (int) input('category_id');
        $data['is_featured'] = input('is_featured') ? 1 : 0;
        $data['personalizable'] = input('personalizable') ? 1 : 0;
    }
    $changes = [];
    if ($canPrice) {
        $data['price'] = $dec(input('price'));
        $data['old_price'] = input('old_price') !== '' ? $dec(input('old_price')) : null;
        $data['personalization_price'] = max(0, $dec(input('personalization_price')));
        if ($id && (float) $p['price'] !== $data['price']) $changes[] = 'Fiyat: ' . money($p['price']) . ' → ' . money($data['price']);
    }
    // Görseller: silme, sıralama (ilk görsel ana görsel), yeni yükleme
    $imgs = product_images($p);
    $keep = [];
    foreach ((array) ($_POST['img_keep'] ?? []) as $img) {
        if (in_array($img, $imgs, true)) $keep[] = $img;
    }
    foreach (array_diff($imgs, $keep) as $gone) @unlink(ROOT . '/uploads/' . $gone);
    if (($main = input('img_main')) && in_array($main, $keep, true)) {
        $keep = array_values(array_unique(array_merge([$main], $keep)));
    }
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $_) {
            if (count($keep) >= 8) break;
            $err = null;
            $saved = save_product_image(['name' => $_FILES['images']['name'][$i], 'tmp_name' => $_FILES['images']['tmp_name'][$i], 'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]], $err);
            if ($saved) $keep[] = $saved; else flash('error', $_FILES['images']['name'][$i] . ': ' . $err);
        }
    }
    if ($keep !== $imgs) $changes[] = 'Görseller güncellendi (' . count($keep) . ')';
    $data['images'] = json_encode(array_values($keep));

    $sid = $data['category_id'] ?? $p['category_id'];
    if (mb_strlen($data['name']) < 3 || mb_strlen($data['short_desc']) < 5 || !row('SELECT id FROM categories WHERE id = ?', [$sid])) {
        flash('error', 'Kategori, ürün adı ve kısa açıklama zorunludur.');
        $p = array_merge($p, $data);
    } elseif ($canPrice && $data['price'] <= 0) {
        flash('error', 'Geçerli bir fiyat girin.');
        $p = array_merge($p, $data);
    } else {
        if ($canCreate) {
            $slug = slugify(input('slug') ?: $data['name']);
            if (row('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $id])) $slug .= '-' . random_int(100, 999);
            $data['slug'] = $slug;
        }
        if ($id) {
            update('products', $data, $id);
            log_activity('Ürünü düzenledi', $data['name'] . ($changes ? ' · ' . implode(', ', $changes) : ''), 'product', $id);
        } else {
            $data += ['sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM products'), 'is_active' => 1, 'created_at' => now()];
            $id = insert('products', $data);
            log_activity('Yeni ürün ekledi', $data['name'] . ' · ' . money($data['price'] ?? 0), 'product', $id);
            flash('info', 'Şimdi aşağıdan beden, renk ve stok bilgilerini ekleyin.');
        }
        flash('success', 'Ürün kaydedildi.');
        redirect('admin/product_edit.php?id=' . $id);
    }
}

$variants = $id ? product_variants($id) : [];
$imgs = product_images($p);
admin_header($id ? 'Ürün Düzenle' : 'Yeni Ürün', $id ? e($p['name']) . ' · Toplam stok: <strong>' . product_stock($id) . '</strong>' : '');
?>
<form method="post" enctype="multipart/form-data" class="panel form">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="grid-3">
        <label>Kategori<select name="category_id" <?= $canCreate ? '' : 'disabled' ?> required><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $p['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
        <label>Ürün Adı<input name="name" value="<?= e($p['name']) ?>" required></label>
        <label>Cinsiyet<select name="gender"><?php foreach (GENDERS as $k => $l): ?><option value="<?= $k ?>" <?= $p['gender'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    </div>
    <div class="grid-4">
        <label>Fiyat (₺, KDV dahil)<input name="price" value="<?= e($p['price'] !== '' ? number_format((float) $p['price'], 2, ',', '') : '') ?>" <?= $canPrice ? 'required' : 'disabled' ?> inputmode="decimal"></label>
        <label>Eski Fiyat <small>(üstü çizili)</small><input name="old_price" value="<?= e($p['old_price'] ? number_format((float) $p['old_price'], 2, ',', '') : '') ?>" <?= $canPrice ? '' : 'disabled' ?> inputmode="decimal"></label>
        <label>Baskı Ücreti (₺)<input name="personalization_price" value="<?= e(number_format((float) $p['personalization_price'], 2, ',', '')) ?>" <?= $canPrice ? '' : 'disabled' ?> inputmode="decimal"></label>
        <label>Adres (slug)<input name="slug" value="<?= e($p['slug']) ?>" <?= $canCreate ? '' : 'disabled' ?> placeholder="otomatik"></label>
    </div>
    <?php if (!$canPrice): ?><p class="small muted">🔒 Fiyatlar yalnızca Admin ve Süper Admin tarafından değiştirilebilir.</p><?php endif; ?>
    <?php if ($canCreate): ?>
        <div class="check-row">
            <label class="check"><input type="checkbox" name="personalizable" value="1" <?= $p['personalizable'] ? 'checked' : '' ?>> <span>İsim-numara baskısı yapılabilir</span></label>
            <label class="check"><input type="checkbox" name="is_featured" value="1" <?= $p['is_featured'] ? 'checked' : '' ?>> <span>Ana sayfada öne çıkar</span></label>
        </div>
    <?php endif; ?>
    <label>Kısa Açıklama<textarea name="short_desc" rows="2" required><?= e($p['short_desc']) ?></textarea></label>
    <label>Detaylı Açıklama</label>
    <div data-rte><textarea name="description" rows="10"><?= e($p['description']) ?></textarea></div>
    <label>Öne Çıkan Özellikler <small>(her satıra bir madde)</small><textarea name="features" rows="4"><?= e($p['features']) ?></textarea></label>

    <h3 class="mt-1">Görseller</h3>
    <div class="img-manage">
        <?php foreach ($imgs as $i => $img): ?>
            <div class="img-item">
                <img src="<?= e(url('uploads/' . $img)) ?>" alt="">
                <label class="check"><input type="checkbox" name="img_keep[]" value="<?= e($img) ?>" checked> <span>Kalsın</span></label>
                <label class="check"><input type="radio" name="img_main" value="<?= e($img) ?>" <?= $i === 0 ? 'checked' : '' ?>> <span>Ana görsel</span></label>
            </div>
        <?php endforeach; ?>
        <?php if (!$imgs): ?><div class="img-item placeholder"><?= product_art($p['art'], $p['art_color']) ?><small>Fotoğraf yüklenene kadar bu çizim gösterilir</small></div><?php endif; ?>
    </div>
    <label>Fotoğraf Yükle <small>(JPG/PNG/WEBP, en fazla 8 görsel; kare ve beyaz/açık zeminli fotoğraflar en iyi sonucu verir)</small><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
    <div class="grid-2">
        <label>Yedek Çizim Türü<select name="art"><?php foreach (PRODUCT_ARTS as $k => $l): ?><option value="<?= $k ?>" <?= $p['art'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
        <label>Çizim Rengi<input type="color" name="art_color" value="<?= e($p['art_color']) ?>"></label>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary">Kaydet</button>
        <a class="btn btn-outline" href="products.php">Vazgeç</a>
        <?php if ($id): ?><a class="btn btn-outline" target="_blank" href="<?= url('urun.php?u=' . urlencode($p['slug'])) ?>">Sitede Gör</a><?php endif; ?>
    </div>
</form>

<?php if ($id): ?>
<form method="post" class="panel form" id="stok">
    <?= csrf_field() ?><input type="hidden" name="action" value="variants">
    <div class="panel-head"><h2>Beden, Renk ve Stok</h2><?= $canStock ? '' : '<span class="small muted">🔒 Stok yalnızca Admin ve Süper Admin tarafından değiştirilebilir</span>' ?></div>
    <div class="table-wrap"><table class="table variant-table">
        <thead><tr><th>Beden</th><th>Renk</th><th>Renk Kodu</th><th>Stok Kodu (SKU)</th><th class="num">Stok</th><th>Sil</th></tr></thead>
        <tbody>
        <?php foreach ($variants as $v): $low = $v['stock'] <= (int) setting('low_stock_limit', '3'); ?>
            <tr class="<?= $v['stock'] <= 0 ? 'row-out' : ($low ? 'row-low' : '') ?>">
                <td><strong><?= e($v['size']) ?></strong></td>
                <td><i class="dot-color" style="background:<?= e($v['color_hex']) ?>"></i><?= e($v['color']) ?></td>
                <td><input type="color" name="v[<?= (int) $v['id'] ?>][color_hex]" value="<?= e($v['color_hex']) ?>" <?= $canStock ? '' : 'disabled' ?>></td>
                <td><input name="v[<?= (int) $v['id'] ?>][sku]" value="<?= e($v['sku']) ?>" class="in-sm" <?= $canStock ? '' : 'disabled' ?>></td>
                <td class="num"><input type="number" min="0" name="v[<?= (int) $v['id'] ?>][stock]" value="<?= (int) $v['stock'] ?>" class="in-num" <?= $canStock ? '' : 'disabled' ?>></td>
                <td><?php if ($canStock): ?><input type="checkbox" name="v[<?= (int) $v['id'] ?>][delete]" value="1"><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$variants): ?><tr><td colspan="6" class="muted">Henüz beden/renk seçeneği yok. Aşağıdan ekleyin; seçeneği olmayan ürün satın alınamaz.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php if ($canStock): ?>
        <h3 class="mt-1">Toplu Seçenek Ekle</h3>
        <div class="grid-3">
            <label>Bedenler <small>(virgülle: S, M, L, XL)</small><input name="new_sizes" placeholder="S, M, L, XL"></label>
            <label>Renkler <small>(her satıra Ad|#kod)</small><textarea name="new_colors" rows="3" placeholder="Kırmızı|#C8102E&#10;Antrasit|#2B2F33"></textarea></label>
            <label>Başlangıç Stoğu<input type="number" name="new_stock" min="0" value="10"></label>
        </div>
        <p class="small muted">Yalnızca beden yazarsanız mevcut renklerin hepsine, yalnızca renk yazarsanız mevcut bedenlerin hepsine eklenir. Var olan kombinasyonlar tekrar eklenmez.</p>
        <div class="form-actions"><button class="btn btn-dark">Stok ve Seçenekleri Kaydet</button></div>
    <?php endif; ?>
</form>
<?php endif; ?>
<?php admin_footer();
