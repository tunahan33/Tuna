<?php
require __DIR__ . '/_layout.php';
$u = require_perm('categories');

if (is_post()) {
    verify_csrf();
    $name = mb_substr((string) input('name'), 0, 80);
    $desc = mb_substr((string) input('description'), 0, 200);
    $id = (int) input('id');
    if (input('action') === 'delete') {
        $c = row('SELECT * FROM categories WHERE id = ?', [$id]);
        if ($c && !val('SELECT 1 FROM products WHERE category_id = ?', [$id])) {
            q('DELETE FROM categories WHERE id = ?', [$id]);
            log_activity('Kategoriyi sildi', $c['name']);
            flash('success', 'Kategori silindi.');
        } else {
            flash('error', 'İçinde ürün bulunan kategori silinemez. Önce ürünleri başka kategoriye taşıyın.');
        }
    } elseif (mb_strlen($name) < 2) {
        flash('error', 'Kategori adı girin.');
    } elseif ($id) {
        q('UPDATE categories SET name = ?, description = ?, sort = ? WHERE id = ?', [$name, $desc, (int) input('sort'), $id]);
        log_activity('Kategoriyi güncelledi', $name);
        flash('success', 'Kategori güncellendi.');
    } else {
        $slug = slugify($name);
        while (val('SELECT 1 FROM categories WHERE slug = ?', [$slug])) $slug .= '-2';
        insert('categories', ['slug' => $slug, 'name' => $name, 'description' => $desc, 'sort' => (int) val('SELECT COALESCE(MAX(sort),0)+1 FROM categories')]);
        log_activity('Kategori ekledi', $name);
        flash('success', 'Kategori eklendi.');
    }
    redirect('admin/kategoriler.php');
}
$list = rows('SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) n FROM categories c ORDER BY sort');
admin_header('Kategoriler', count($list) . ' kategori');
?>
<div class="grid-2-1">
    <section class="panel">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Sıra</th><th>Ad</th><th>Açıklama</th><th>Ürün</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($list as $c): $f = 'cat' . $c['id']; ?>
                <tr>
                    <td style="width:70px"><input form="<?= $f ?>" name="sort" type="number" value="<?= (int) $c['sort'] ?>" class="mini"></td>
                    <td><input form="<?= $f ?>" name="name" value="<?= e($c['name']) ?>" class="mini wide"></td>
                    <td><input form="<?= $f ?>" name="description" value="<?= e($c['description']) ?>" class="mini wide"></td>
                    <td><?= (int) $c['n'] ?></td>
                    <td class="nowrap"><button form="<?= $f ?>" class="btn btn-sm btn-dark">Kaydet</button> <button form="<?= $f ?>" class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Kategori silinsin mi?">Sil</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php foreach ($list as $c): ?><form method="post" id="cat<?= $c['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"></form><?php endforeach; ?>
    </section>
    <form method="post" class="panel form">
        <?= csrf_field() ?>
        <h2>Yeni Kategori</h2>
        <label>Ad<input name="name" required></label>
        <label>Açıklama<input name="description"></label>
        <button class="btn btn-primary">Ekle</button>
    </form>
</div>
<?php admin_footer();
