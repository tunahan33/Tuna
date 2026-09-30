<?php
require __DIR__ . '/_init.php';
$u = require_perm('pages.edit');
$id = (int) input('id');
$p = row('SELECT * FROM pages WHERE id = ?', [$id]);
if (!$p) redirect('admin/pages.php');

if (is_post()) {
    verify_csrf();
    $title = mb_substr(input('title'), 0, 200);
    $content = sanitize_html((string) ($_POST['content'] ?? ''));
    if (mb_strlen($title) < 2 || mb_strlen(strip_tags($content)) < 10) {
        flash('error', 'Başlık ve içerik boş olamaz.');
    } else {
        q('UPDATE pages SET title = ?, content = ?, show_in_footer = ?, updated_by = ?, updated_at = ? WHERE id = ?', [$title, $content, input('show_in_footer') ? 1 : 0, $u['id'], now(), $id]);
        log_activity('Sayfayı düzenledi', $title, 'page', $id);
        flash('success', 'Sayfa kaydedildi.');
        redirect('admin/page_edit.php?id=' . $id);
    }
}
admin_header('Sayfa Düzenle', e($p['title']));
?>
<form method="post" class="panel form">
    <?= csrf_field() ?>
    <label>Başlık<input name="title" value="<?= e($p['title']) ?>" required></label>
    <label>İçerik</label>
    <div data-rte><textarea name="content" rows="20"><?= e($p['content']) ?></textarea></div>
    <label class="check"><input type="checkbox" name="show_in_footer" value="1" <?= $p['show_in_footer'] ? 'checked' : '' ?>> <span>Sitenin alt kısmında (footer) bağlantısını göster</span></label>
    <div class="form-actions">
        <button class="btn btn-primary">Kaydet</button>
        <a class="btn btn-outline" href="pages.php">Vazgeç</a>
        <a class="btn btn-outline" target="_blank" href="<?= url('sayfa.php?s=' . urlencode($p['slug'])) ?>">Sitede Gör</a>
    </div>
</form>
<?php admin_footer();
