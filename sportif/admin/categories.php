<?php
require __DIR__ . '/_init.php';
$u = require_perm('content.edit');

if (is_post()) {
    verify_csrf();
    $id = (int) input('id');
    $c = $id ? row('SELECT * FROM categories WHERE id = ?', [$id]) : null;
    $action = input('action');
    if ($action === 'save') {
        $name = mb_substr(input('name'), 0, 160);
        $data = ['name' => $name, 'description' => mb_substr(input('description'), 0, 500), 'color' => preg_match('/^#[0-9A-Fa-f]{6}$/', input('color')) ? input('color') : '#C8102E', 'updated_at' => now()];
        if (mb_strlen($name) < 2) {
            flash('error', 'Kategori adı girin.');
        } elseif ($c) {
            update('categories', $data, $id);
            log_activity('Kategoriyi düzenledi', $name, 'category', $id);
            flash('success', 'Kategori kaydedildi.');
        } elseif (can('content.create')) {
            $slug = slugify($name);
            if (row('SELECT id FROM categories WHERE slug = ?', [$slug])) $slug .= '-' . random_int(10, 99);
            $nid = insert('categories', $data + ['slug' => $slug, 'sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM categories'), 'is_active' => 1, 'created_at' => now()]);
            log_activity('Yeni kategori ekledi', $name, 'category', $nid);
            flash('success', 'Kategori eklendi.');
        }
    } elseif ($c && $action === 'toggle' && can('content.create')) {
        q('UPDATE categories SET is_active = ?, updated_at = ? WHERE id = ?', [$c['is_active'] ? 0 : 1, now(), $id]);
        log_activity($c['is_active'] ? 'Kategoriyi yayından kaldırdı' : 'Kategoriyi yayına aldı', $c['name'], 'category', $id);
    } elseif ($c && in_array($action, ['up', 'down'], true) && can('content.create')) {
        $ids = array_column(rows('SELECT id FROM categories ORDER BY sort_order, id'), 'id');
        $pos = array_search($id, $ids);
        $swap = $action === 'up' ? $pos - 1 : $pos + 1;
        if (isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            foreach ($ids as $i => $cid) q('UPDATE categories SET sort_order = ? WHERE id = ?', [$i + 1, $cid]);
        }
    } elseif ($c && $action === 'delete' && can('content.delete')) {
        if ((int) val('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id])) {
            flash('error', 'Bu kategoride ürün var. Önce ürünleri başka kategoriye taşıyın veya kategoriyi pasifleştirin.');
        } else {
            q('DELETE FROM categories WHERE id = ?', [$id]);
            log_activity('Kategoriyi sildi', $c['name'], 'category', $id);
            flash('success', 'Kategori silindi.');
        }
    }
    redirect('admin/categories.php');
}

$cats = rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS pc FROM categories c ORDER BY sort_order, id');
admin_header('Kategoriler', count($cats) . ' kategori');
?>
<?php foreach ($cats as $c): ?><form method="post" id="cat-<?= (int) $c['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"></form><?php endforeach; ?>
<section class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Kategori</th><th>Açıklama</th><th>Renk</th><th>Ürün</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cats as $c): ?>
            <tr>
                <td><input form="cat-<?= (int) $c['id'] ?>" name="name" value="<?= e($c['name']) ?>" class="in-sm"></td>
                <td><input form="cat-<?= (int) $c['id'] ?>" name="description" value="<?= e($c['description']) ?>" class="in-sm"></td>
                <td><input form="cat-<?= (int) $c['id'] ?>" type="color" name="color" value="<?= e($c['color']) ?>"></td>
                <td><a href="products.php?category=<?= (int) $c['id'] ?>"><?= (int) $c['pc'] ?></a></td>
                <td><?= $c['is_active'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
                <td class="actions">
                    <button form="cat-<?= (int) $c['id'] ?>" name="action" value="save" class="btn btn-xs btn-dark">Kaydet</button>
                    <?php if (can('content.create')): ?><button form="cat-<?= (int) $c['id'] ?>" name="action" value="up" class="btn btn-xs btn-outline">↑</button><button form="cat-<?= (int) $c['id'] ?>" name="action" value="down" class="btn btn-xs btn-outline">↓</button><button form="cat-<?= (int) $c['id'] ?>" name="action" value="toggle" class="btn btn-xs btn-outline"><?= $c['is_active'] ? 'Pasifleştir' : 'Yayına Al' ?></button><?php endif; ?>
                    <?php if (can('content.delete')): ?><button form="cat-<?= (int) $c['id'] ?>" name="action" value="delete" class="btn btn-xs btn-danger" onclick="return confirm('Kategori silinsin mi?')">Sil</button><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php if (can('content.create')): ?>
<form method="post" class="panel form narrow-form"><?= csrf_field() ?><input type="hidden" name="action" value="save">
    <h3>Yeni Kategori</h3>
    <div class="grid-3"><label>Ad<input name="name" required></label><label>Açıklama<input name="description"></label><label>Renk<input type="color" name="color" value="#C8102E"></label></div>
    <div class="form-actions"><button class="btn btn-primary btn-sm">Ekle</button></div>
</form>
<?php endif; ?>
<?php admin_footer();
