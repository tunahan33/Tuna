<?php
if (!function_exists('admin_header')) { http_response_code(404); exit; }
admin_header('Yetkiniz yok');
?>
<section class="panel empty-state">
    <div style="font-size:3rem">🔒</div>
    <h2>Bu bölüme erişim yetkiniz bulunmuyor</h2>
    <p class="muted">Rolünüz: <strong><?= e(role_label(current_user()['role'])) ?></strong>. Bu sayfaya erişim için bir üst yetkiliye başvurun.</p>
    <a class="btn btn-dark" href="<?= url('admin/') ?>">Gösterge Paneline Dön</a>
</section>
<?php admin_footer();
