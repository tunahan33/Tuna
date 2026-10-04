<?php
if (!function_exists('admin_header')) require __DIR__ . '/_layout.php';
http_response_code(403);
admin_header('Yetkisiz Erişim');
?>
<div class="panel empty-state">
    <div class="big-icon">🔒</div>
    <h2>Bu sayfaya erişim yetkiniz yok</h2>
    <p>Rolünüz: <strong><?= e(role_label(current_user()['role'])) ?></strong>. Bu alan daha yüksek yetki gerektiriyor. Deneme kaydı süper admin akışına işlendi.</p>
    <a class="btn btn-dark" href="<?= url('admin/') ?>">Kontrol Paneline Dön</a>
</div>
<?php admin_footer();
