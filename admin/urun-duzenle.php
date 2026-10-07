<?php
require __DIR__ . '/_layout.php';
$u = require_perm('products.text');
$manage = can('products.manage');

$id = (int) input('id');
$p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$p) {
    flash('error', 'Ürün bulunamadı.');
    redirect('admin/urunler.php');
}
if (!$p && !$manage) {
    redirect('admin/urunler.php');
}
$minPrice = 2500;
$cats = rows('SELECT * FROM categories ORDER BY sort');
$p ??= ['id' => 0, 'category_id' => $cats[0]['id'] ?? 0, 'name' => '', 'short_desc' => '', 'description' => '', 'features' => '', 'price' => $minPrice,
    'old_price' => null, 'stock' => 0, 'sizes' => 'S,M,L,XL', 'art' => 'forma', 'color' => '#C8102E', 'featured' => 0, 'active' => 1, 'slug' => '', 'images' => '[]'];

if (is_post()) {
    verify_csrf();
    if (input('action') === 'delete' && $manage && $p['id']) {
        q('UPDATE products SET active = 0, slug = slug || ? WHERE id = ?', ['-silindi-' . time(), $p['id']]);
        $deleted = q('DELETE FROM products WHERE id = ? AND NOT EXISTS (SELECT 1 FROM order_items WHERE product_id = ?)', [$p['id'], $p['id']])->rowCount();
        if ($deleted) {
            foreach (product_images($p) as $f) if (str_starts_with($f, 'uploads/')) @unlink(ROOT . '/' . $f);
        }
        log_activity('Ürünü sildi', $p['name']);
        flash('success', 'Ürün silindi.');
        redirect('admin/urunler.php');
    }
    // Fotoğraf işlemleri: kapak yap / sil (editör dâhil)
    if ($p['id'] && preg_match('/^(cover|remove|left):(\d+)$/', (string) input('photo_action'), $m)) {
        $imgs = product_images($p);
        $i = (int) $m[2];
        if (isset($imgs[$i])) {
            $file = $imgs[$i];
            if ($m[1] === 'remove') {
                array_splice($imgs, $i, 1);
                if (str_starts_with($file, 'uploads/') && is_file(ROOT . '/' . $file)) unlink(ROOT . '/' . $file);
                log_activity('Ürün fotoğrafını sildi', $p['name'], 'admin/urun-duzenle.php?id=' . $p['id']);
            } elseif ($m[1] === 'cover') {
                array_splice($imgs, $i, 1);
                array_unshift($imgs, $file);
                log_activity('Ürün kapak fotoğrafını değiştirdi', $p['name'], 'admin/urun-duzenle.php?id=' . $p['id']);
            } elseif ($i > 0) {
                [$imgs[$i - 1], $imgs[$i]] = [$imgs[$i], $imgs[$i - 1]];
            }
            q('UPDATE products SET images = ?, updated_at = ? WHERE id = ?', [json_encode($imgs), now(), $p['id']]);
            flash('success', 'Fotoğraflar güncellendi.');
        }
        redirect('admin/urun-duzenle.php?id=' . $p['id']);
    }
    // Yeni yüklenen fotoğraflar
    $uploaded = [];
    $files = $_FILES['photos'] ?? null;
    if ($files && is_array($files['name'])) {
        foreach ($files['name'] as $k => $n) {
            if (($files['error'][$k] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $err = null;
            $saved = save_product_photo(['error' => $files['error'][$k], 'size' => $files['size'][$k], 'tmp_name' => $files['tmp_name'][$k]], $err);
            if ($saved) $uploaded[] = $saved;
            else flash('error', $n . ': ' . $err);
        }
    }
    $allImages = array_merge(product_images($p), $uploaded);

    // Herkesin (editör dâhil) düzenleyebildiği metin alanları
    $data = [
        'name' => mb_substr((string) input('name'), 0, 120),
        'short_desc' => mb_substr((string) input('short_desc'), 0, 200),
        'description' => clean_html((string) ($_POST['description'] ?? '')),
        'features' => mb_substr((string) input('features'), 0, 2000),
        'images' => json_encode($allImages),
        'updated_at' => now(),
    ];
    $errors = [];
    if (mb_strlen($data['name']) < 3) $errors[] = 'Ürün adı en az 3 karakter olmalı.';
    if (mb_strlen($data['short_desc']) < 5) $errors[] = 'Kısa açıklama girin.';
    // Fiyat, stok, kategori vb. yalnızca Admin ve Süper Admin
    if ($manage) {
        $price = (float) str_replace(',', '.', (string) input('price'));
        $old = input('old_price') !== '' ? (float) str_replace(',', '.', (string) input('old_price')) : null;
        if ($price < $minPrice) $errors[] = 'Ürün fiyatı en az ' . money($minPrice) . ' olmalıdır.';
        if ($old !== null && $old <= $price) $old = null;
        $data += [
            'category_id' => (int) input('category_id'), 'price' => round($price, 2), 'old_price' => $old, 'stock' => max(0, (int) input('stock')),
            'sizes' => implode(',', array_filter(array_map('trim', explode(',', (string) input('sizes'))))),
            'art' => isset(PRODUCT_ARTS[input('art')]) ? input('art') : 'forma',
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) input('color')) ? input('color') : '#C8102E',
            'featured' => input('featured') ? 1 : 0, 'active' => input('active') ? 1 : 0,
        ];
    }
    if ($errors) {
        foreach ($uploaded as $f) @unlink(ROOT . '/' . $f);
        $data['images'] = $p['images'];
        flash('error', implode(' ', $errors));
        $p = array_merge($p, $data);
    } else {
        if ($p['id']) {
            $changes = [];
            foreach ($data as $k => $v) if ($k !== 'updated_at' && (string) $p[$k] !== (string) $v) $changes[] = $k;
            update('products', $data, (int) $p['id']);
            $labels = ['images' => 'fotoğraflar', 'price' => 'fiyat', 'old_price' => 'eski fiyat', 'stock' => 'stok', 'name' => 'ad', 'description' => 'açıklama', 'short_desc' => 'kısa açıklama', 'features' => 'özellikler', 'active' => 'yayın durumu', 'featured' => 'öne çıkarma', 'category_id' => 'kategori', 'sizes' => 'bedenler', 'art' => 'görsel', 'color' => 'renk'];
            $detail = $data['name'] . ($changes ? ' · ' . implode(', ', array_map(fn($c) => $labels[$c] ?? $c, $changes)) : '');
            if (in_array('price', $changes, true)) $detail .= ' (' . money($p['price']) . ' → ' . money($data['price']) . ')';
            log_activity('Ürünü güncelledi', $detail, 'admin/urun-duzenle.php?id=' . $p['id']);
            flash('success', 'Ürün kaydedildi.');
            redirect('admin/urun-duzenle.php?id=' . $p['id']);
        }
        $slug = slugify($data['name']);
        while (val('SELECT 1 FROM products WHERE slug = ?', [$slug])) $slug .= '-' . random_int(2, 99);
        $newId = insert('products', $data + ['slug' => $slug, 'created_at' => now()]);
        log_activity('Yeni ürün ekledi', $data['name'] . ' · ' . money($data['price']), 'admin/urun-duzenle.php?id=' . $newId);
        flash('success', 'Ürün eklendi.');
        redirect('admin/urun-duzenle.php?id=' . $newId);
    }
}

admin_header($p['id'] ? $p['name'] : 'Yeni Ürün', $p['id'] ? 'Ürün düzenle' : 'Mağazaya yeni ürün ekleyin',
    '<a class="btn btn-ghost" href="' . url('admin/urunler.php') . '">← Ürünler</a>' . ($p['id'] && $p['slug'] ? '<a class="btn btn-ghost" target="_blank" href="' . url('urun.php?u=' . $p['slug']) . '">Sitede gör ↗</a>' : ''));
$ro = $manage ? '' : 'disabled';
?>
<form method="post" class="grid-2-1" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <button type="submit" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px">Kaydet</button>
    <div>
        <section class="panel form">
            <h2>Ürün Metinleri</h2>
            <label>Ürün adı<input name="name" value="<?= e($p['name']) ?>" required maxlength="120"></label>
            <label>Kısa açıklama <small class="muted">(ürün kartında görünür)</small><input name="short_desc" value="<?= e($p['short_desc']) ?>" required maxlength="200"></label>
            <div class="field">
                <span class="field-label">Detaylı açıklama <small class="muted">(ürüne tıklayınca görünür)</small></span>
                <textarea name="description" rows="12" data-rich><?= e($p['description']) ?></textarea>
            </div>
            <label>Öne çıkan özellikler <small class="muted">(her satıra bir özellik)</small><textarea name="features" rows="5"><?= e($p['features']) ?></textarea></label>
        </section>
        <section class="panel">
            <h2>Fotoğraflar</h2>
            <?php $imgs = product_images($p); if ($imgs): ?>
                <div class="photo-grid">
                    <?php foreach ($imgs as $i => $img): ?>
                        <figure class="<?= $i ? '' : 'cover' ?>">
                            <?php if (!$i): ?><span class="cover-tag">Kapak</span><?php endif; ?>
                            <img src="<?= e(url($img)) ?>" alt="">
                            <figcaption>
                                <?php if ($i): ?><button name="photo_action" value="cover:<?= $i ?>" formnovalidate>Kapak yap</button><button name="photo_action" value="left:<?= $i ?>" formnovalidate aria-label="Sola taşı">←</button><?php endif; ?>
                                <button name="photo_action" value="remove:<?= $i ?>" formnovalidate data-confirm="Fotoğraf silinsin mi?">Sil</button>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><p class="muted small">Henüz fotoğraf yok; sitede marka renklerinde bir çizim gösteriliyor.</p><?php endif; ?>
            <?php if ($imgs): ?><p class="small muted"><?= count($imgs) ?> fotoğraf</p><?php endif; ?>
            <label class="drop" data-drop>📷 Fotoğrafları buraya sürükleyin veya tıklayıp seçin
                <small>JPG, PNG, WEBP · istediğiniz kadar fotoğraf, tek seferde birden fazla seçebilirsiniz</small>
                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input>
            </label>
            <p class="photo-status" data-photo-status hidden></p>
            <div class="photo-grid" data-photo-preview style="margin-top:10px"></div>
            <p class="small muted">Fotoğraflar “Kaydet”e basınca yüklenir. Büyük fotoğraflar otomatik küçültülür, kare olmayanların kenarları doldurulur. İlk fotoğraf kapaktır; sırayı “Kapak yap” ve “←” düğmeleriyle değiştirebilirsiniz.</p>
        </section>
    </div>
    <div>
        <section class="panel form">
            <h2>Fiyat & Stok</h2>
            <?php if (!$manage): ?><p class="alert alert-info small">Fiyat, stok ve yayın ayarlarını yalnızca Admin ve Süper Admin değiştirebilir.</p><?php endif; ?>
            <label>Satış fiyatı (₺, KDV dâhil) <small class="muted">en az <?= money($minPrice) ?></small><input name="price" type="number" step="0.01" min="<?= $minPrice ?>" value="<?= e($p['price']) ?>" <?= $ro ?> required></label>
            <label>Eski fiyat (₺) <small class="muted">indirim göstermek için</small><input name="old_price" type="number" step="0.01" value="<?= e($p['old_price']) ?>" <?= $ro ?>></label>
            <label>Stok adedi<input name="stock" type="number" min="0" value="<?= (int) $p['stock'] ?>" <?= $ro ?>></label>
            <label>Bedenler / numaralar <small class="muted">virgülle ayırın, boş bırakılabilir</small><input name="sizes" value="<?= e($p['sizes']) ?>" <?= $ro ?>></label>
            <label>Kategori<select name="category_id" <?= $ro ?>><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $p['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
        </section>
        <section class="panel form">
            <h2>Görünüm</h2>
            <div class="thumb big"><?= product_art($p) ?></div>
            <p class="small muted" style="margin:0">Bu çizim yalnızca ürünün fotoğrafı yoksa gösterilir.</p>
            <label>Görsel tipi<select name="art" <?= $ro ?>><?php foreach (PRODUCT_ARTS as $k => $l): ?><option value="<?= $k ?>" <?= $p['art'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
            <label>Ana renk<input type="color" name="color" value="<?= e($p['color']) ?>" <?= $ro ?>></label>
            <label class="check"><input type="checkbox" name="featured" value="1" <?= $p['featured'] ? 'checked' : '' ?> <?= $ro ?>> Ana sayfada öne çıkar</label>
            <label class="check"><input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?> <?= $ro ?>> Sitede yayında</label>
        </section>
        <button class="btn btn-primary btn-block btn-lg">Kaydet</button>
        <?php if ($manage && $p['id']): ?>
            <button class="btn btn-danger btn-block" style="margin-top:10px" name="action" value="delete" formnovalidate data-confirm="Bu ürün silinsin mi?">Ürünü Sil</button>
        <?php endif; ?>
    </div>
</form>
<?php admin_footer();
