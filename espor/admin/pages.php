<?php
require __DIR__ . '/_init.php';
$u = require_perm('pages.edit');
$pages = rows('SELECT p.*, u.name AS editor FROM pages p LEFT JOIN users u ON u.id = p.updated_by ORDER BY sort_order');
admin_header('Sayfalar & Sözleşmeler', 'Satış ve sanal POS başvurusu için gerekli yasal metinler');
?>
<div class="info-box">Metinlerdeki <code>{{firma_unvan}}</code>, <code>{{firma_adres}}</code>, <code>{{vergi_no}}</code> gibi alanlar <?= can('settings.edit') ? '<a href="settings.php">Site Ayarları</a>' : 'Site Ayarları' ?>'ndaki firma bilgileriyle otomatik doldurulur. Sözleşmelerdeki <code>{{alici_ad}}</code>, <code>{{paket_adi}}</code>, <code>{{toplam_tutar}}</code> alanları ödeme sırasında siparişe göre doldurulur.</div>
<section class="panel">
    <table class="table">
        <thead><tr><th>Sayfa</th><th>Adres</th><th>Alt menüde</th><th>Son güncelleme</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><strong><?= e($p['title']) ?></strong></td>
                <td><code>sayfa.php?s=<?= e($p['slug']) ?></code></td>
                <td><?= $p['show_in_footer'] ? 'Evet' : 'Hayır' ?></td>
                <td><?= tr_date($p['updated_at']) ?><?= $p['editor'] ? '<br><small class="muted">' . e($p['editor']) . '</small>' : '' ?></td>
                <td class="actions"><a class="btn btn-xs btn-outline" href="page_edit.php?id=<?= (int) $p['id'] ?>">Düzenle</a> <a class="btn btn-xs btn-outline" target="_blank" href="<?= url('sayfa.php?s=' . urlencode($p['slug'])) ?>">Gör</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php admin_footer();
