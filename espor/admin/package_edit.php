<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

$id = (int) input('id');
$p = $id ? row('SELECT * FROM packages WHERE id = ?', [$id]) : null;
if ($id && !$p) redirect('admin/packages.php');
if (!$p && !can('content.create')) {
    require __DIR__ . '/_forbidden.php';
    exit;
}
$p = $p ?? ['service_id' => (int) input('service'), 'name' => '', 'price' => '', 'old_price' => '', 'duration' => '', 'sessions' => '', 'short_desc' => '', 'description' => '', 'features' => '', 'is_featured' => 0];
$services = rows('SELECT id, title FROM services ORDER BY sort_order, id');
$canPrice = can('prices.edit');

if (is_post()) {
    verify_csrf();
    $data = [
        'name' => mb_substr(input('name'), 0, 160),
        'duration' => mb_substr(input('duration'), 0, 80),
        'sessions' => mb_substr(input('sessions'), 0, 80),
        'short_desc' => mb_substr(input('short_desc'), 0, 400),
        'description' => sanitize_html((string) ($_POST['description'] ?? '')),
        'features' => mb_substr(input('features'), 0, 4000),
        'updated_at' => now(),
    ];
    if (can('content.create')) {
        $data['service_id'] = (int) input('service_id');
        $data['is_featured'] = input('is_featured') ? 1 : 0;
    }
    $changes = [];
    if ($canPrice) {
        $price = (float) str_replace(',', '.', input('price'));
        $old = input('old_price') !== '' ? (float) str_replace(',', '.', input('old_price')) : null;
        $data['price'] = $price;
        $data['old_price'] = $old;
        if ($id && (float) $p['price'] !== $price) $changes[] = 'Fiyat: ' . money($p['price']) . ' → ' . money($price);
    }
    $sid = $data['service_id'] ?? $p['service_id'];
    if (mb_strlen($data['name']) < 3 || !$data['duration'] || mb_strlen($data['short_desc']) < 5 || !row('SELECT id FROM services WHERE id = ?', [$sid])) {
        flash('error', 'Hizmet, paket adı, süre ve kısa açıklama zorunludur.');
        $p = array_merge($p, $data);
    } elseif ($canPrice && $data['price'] <= 0) {
        flash('error', 'Geçerli bir fiyat girin.');
        $p = array_merge($p, $data);
    } else {
        if ($id) {
            update('packages', $data, $id);
            log_activity('Paketi düzenledi', $data['name'] . ($changes ? ' · ' . implode(', ', $changes) : ''), 'package', $id);
        } else {
            $data += ['sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0) + 1 FROM packages WHERE service_id = ?', [$sid]), 'is_active' => 1, 'created_at' => now()];
            $id = insert('packages', $data);
            log_activity('Yeni paket ekledi', $data['name'] . ' · ' . money($data['price']), 'package', $id);
        }
        flash('success', 'Paket kaydedildi.');
        redirect('admin/package_edit.php?id=' . $id);
    }
}

admin_header($id ? 'Paket Düzenle' : 'Yeni Paket', $id ? e($p['name']) : '');
?>
<form method="post" class="panel form">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Hizmet
            <select name="service_id" <?= can('content.create') ? '' : 'disabled' ?> required>
                <?php foreach ($services as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (int) $p['service_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Paket Adı<input name="name" value="<?= e($p['name']) ?>" required></label>
    </div>
    <div class="grid-4">
        <label>Fiyat (₺, KDV dahil)<input name="price" value="<?= e($p['price'] !== '' ? number_format((float) $p['price'], 2, ',', '') : '') ?>" <?= $canPrice ? 'required' : 'disabled' ?> inputmode="decimal"></label>
        <label>Eski Fiyat <small>(üstü çizili, isteğe bağlı)</small><input name="old_price" value="<?= e($p['old_price'] ? number_format((float) $p['old_price'], 2, ',', '') : '') ?>" <?= $canPrice ? '' : 'disabled' ?> inputmode="decimal"></label>
        <label>Süre<input name="duration" value="<?= e($p['duration']) ?>" placeholder="Örn: 3 Ay" required></label>
        <label>Oturum / Görüşme<input name="sessions" value="<?= e($p['sessions']) ?>" placeholder="Örn: 12 online görüşme"></label>
    </div>
    <?php if (!$canPrice): ?><p class="small muted">🔒 Fiyat alanları yalnızca Admin ve Süper Admin tarafından değiştirilebilir.</p><?php endif; ?>
    <label>Kısa Açıklama<textarea name="short_desc" rows="2" required><?= e($p['short_desc']) ?></textarea></label>
    <label>Detaylı Açıklama <small>(pakete tıklanınca açılan pencerede görünür)</small></label>
    <div data-rte><textarea name="description" rows="10"><?= e($p['description']) ?></textarea></div>
    <label>Paket İçeriği <small>(her satıra bir madde; kartta ilk 4 madde görünür)</small><textarea name="features" rows="7"><?= e($p['features']) ?></textarea></label>
    <?php if (can('content.create')): ?><label class="check"><input type="checkbox" name="is_featured" value="1" <?= $p['is_featured'] ? 'checked' : '' ?>> <span>“En Çok Tercih Edilen” olarak öne çıkar (ana sayfada gösterilir)</span></label><?php endif; ?>
    <div class="form-actions">
        <button class="btn btn-primary">Kaydet</button>
        <a class="btn btn-outline" href="packages.php?service=<?= (int) $p['service_id'] ?>">Vazgeç</a>
        <?php if ($id): ?><a class="btn btn-outline" target="_blank" href="<?= url('paket.php?id=' . $id) ?>">Sitede Gör</a><?php endif; ?>
    </div>
</form>
<?php admin_footer();
