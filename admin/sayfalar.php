<?php
require __DIR__ . '/_layout.php';
$u = require_perm('pages');
$list = rows('SELECT * FROM pages ORDER BY title');
admin_header('Sayfalar & Sözleşmeler', 'Altbilgideki kurumsal ve yasal sayfaların metinleri');
?>
<section class="panel">
    <p class="alert alert-info small">Metinlerde <code>{{firma_unvan}}</code>, <code>{{firma_adres}}</code>, <code>{{firma_eposta}}</code>, <code>{{firma_telefon}}</code>, <code>{{kargo_suresi}}</code> gibi alanlar Mağaza Ayarları'ndaki bilgilerle otomatik doldurulur. Mesafeli satış sözleşmesindeki <code>{{alici_ad}}</code>, <code>{{urun_listesi}}</code> vb. alanlar ödeme sırasında müşterinin bilgileriyle dolar.</p>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Sayfa</th><th>Adres</th><th>Son güncelleme</th></tr></thead>
        <tbody>
        <?php foreach ($list as $p): ?>
            <tr data-href="<?= url('admin/sayfa-duzenle.php?id=' . $p['id']) ?>"><td><strong><?= e($p['title']) ?></strong></td><td class="small muted">sayfa.php?s=<?= e($p['slug']) ?></td><td class="small"><?= tr_date($p['updated_at']) ?> · <?= e($p['updated_by']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php admin_footer();
