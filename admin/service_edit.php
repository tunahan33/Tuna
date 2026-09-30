<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

$id = (int) input('id');
$s = $id ? row('SELECT * FROM services WHERE id = ?', [$id]) : null;
if ($id && !$s) redirect('admin/services.php');
if (!$s && !can('content.create')) {
    require __DIR__ . '/_forbidden.php';
    exit;
}
$s = $s ?? ['title' => '', 'slug' => '', 'short_desc' => '', 'description' => '', 'highlights' => '', 'icon' => 'default', 'is_active' => 1];

if (is_post()) {
    verify_csrf();
    $data = [
        'title' => mb_substr(input('title'), 0, 160),
        'short_desc' => mb_substr(input('short_desc'), 0, 400),
        'description' => sanitize_html((string) ($_POST['description'] ?? '')),
        'highlights' => mb_substr(input('highlights'), 0, 2000),
        'icon' => array_key_exists(input('icon'), SERVICE_ICONS) ? input('icon') : 'default',
        'updated_at' => now(),
    ];
    $slug = slugify(input('slug') ?: $data['title']);
    if (mb_strlen($data['title']) < 3 || mb_strlen($data['short_desc']) < 10) {
        flash('error', 'Başlık ve kısa açıklama zorunludur.');
        $s = array_merge($s, $data);
    } elseif (row('SELECT id FROM services WHERE slug = ? AND id <> ?', [$slug, $id])) {
        flash('error', 'Bu adres (slug) başka bir hizmette kullanılıyor.');
        $s = array_merge($s, $data);
    } else {
        if (can('content.create')) {
            $data['slug'] = $slug;
        }
        if ($id) {
            update('services', $data, $id);
            log_activity('Hizmeti düzenledi', $data['title'], 'service', $id);
        } else {
            $data += ['slug' => $slug, 'sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0) + 1 FROM services'), 'is_active' => 1, 'created_at' => now()];
            $id = insert('services', $data);
            log_activity('Yeni hizmet ekledi', $data['title'], 'service', $id);
        }
        flash('success', 'Hizmet kaydedildi.');
        redirect('admin/service_edit.php?id=' . $id);
    }
}

admin_header($id ? 'Hizmet Düzenle' : 'Yeni Hizmet', $id ? e($s['title']) : '');
?>
<form method="post" class="panel form">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Hizmet Adı<input name="title" value="<?= e($s['title']) ?>" required></label>
        <label>Adres (slug) <small><?= can('content.create') ? 'boş bırakılırsa otomatik' : 'yalnızca admin değiştirebilir' ?></small><input name="slug" value="<?= e($s['slug']) ?>" <?= can('content.create') ? '' : 'disabled' ?>></label>
    </div>
    <label>Kısa Açıklama <small>(kartlarda görünür)</small><textarea name="short_desc" rows="2" required><?= e($s['short_desc']) ?></textarea></label>
    <label>Detaylı Açıklama</label>
    <div data-rte><textarea name="description" rows="12"><?= e($s['description']) ?></textarea></div>
    <div class="grid-2">
        <label>Öne Çıkanlar <small>(her satıra bir madde)</small><textarea name="highlights" rows="5"><?= e($s['highlights']) ?></textarea></label>
        <div>
            <label>İkon</label>
            <div class="icon-pick">
                <?php foreach (SERVICE_ICONS as $k => $l): ?>
                    <label title="<?= e($l) ?>"><input type="radio" name="icon" value="<?= $k ?>" <?= $s['icon'] === $k ? 'checked' : '' ?>><span><?= service_icon($k) ?></span></label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary">Kaydet</button>
        <a class="btn btn-outline" href="services.php">Vazgeç</a>
        <?php if ($id): ?><a class="btn btn-outline" href="packages.php?service=<?= $id ?>">Bu hizmetin paketleri →</a><?php endif; ?>
    </div>
</form>
<?php admin_footer();
