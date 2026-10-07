<?php
require __DIR__ . '/_layout.php';
$u = require_perm('pages');
$p = row('SELECT * FROM pages WHERE id = ?', [(int) input('id')]);
if (!$p) {
    redirect('admin/sayfalar.php');
}
if (is_post()) {
    verify_csrf();
    $title = mb_substr((string) input('title'), 0, 150);
    $content = clean_html((string) ($_POST['content'] ?? ''));
    if (mb_strlen($title) < 2 || mb_strlen(strip_tags($content)) < 10) {
        flash('error', 'Başlık ve içerik boş olamaz.');
    } else {
        q('UPDATE pages SET title = ?, content = ?, updated_by = ?, updated_at = ? WHERE id = ?', [$title, $content, $u['name'], now(), $p['id']]);
        log_activity('Sayfayı güncelledi', $title, 'admin/sayfa-duzenle.php?id=' . $p['id']);
        flash('success', 'Sayfa kaydedildi.');
        redirect('admin/sayfa-duzenle.php?id=' . $p['id']);
    }
}
admin_header($p['title'], 'Sayfa düzenle', '<a class="btn btn-ghost" href="' . url('admin/sayfalar.php') . '">← Sayfalar</a><a class="btn btn-ghost" target="_blank" href="' . url('sayfa.php?s=' . $p['slug']) . '">Sitede gör ↗</a>');
?>
<form method="post" class="panel form">
    <?= csrf_field() ?>
    <label>Başlık<input name="title" value="<?= e($p['title']) ?>" required></label>
    <div class="editor-bar" data-editor-bar>
        <button type="button" data-wrap="h3">Başlık</button><button type="button" data-wrap="p">Paragraf</button><button type="button" data-wrap="strong">Kalın</button><button type="button" data-wrap="li">Madde</button><button type="button" data-wrap="ul">Liste</button>
    </div>
    <label>İçerik (HTML)<textarea name="content" rows="24" class="code" data-editor><?= e($p['content']) ?></textarea></label>
    <button class="btn btn-primary btn-lg">Kaydet</button>
</form>
<?php admin_footer();
