<?php
require __DIR__ . '/app/bootstrap.php';

$prefs = json_decode((string) ($_COOKIE['gs_cerez'] ?? ''), true) ?: ['zorunlu' => true, 'analitik' => false, 'pazarlama' => false];
if (is_post()) {
    verify_csrf();
    $prefs = ['zorunlu' => true, 'analitik' => input('analitik') === '1', 'pazarlama' => input('pazarlama') === '1'];
    if (input('hepsi') === '1') $prefs = ['zorunlu' => true, 'analitik' => true, 'pazarlama' => true];
    setcookie('gs_cerez', json_encode($prefs), ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
    flash('success', 'Çerez tercihleriniz kaydedildi.');
    redirect('cerez-tercihleri.php');
}
$title = 'Çerez Tercihleri';
require __DIR__ . '/app/header.php';
?>
<section class="page-head"><div class="container"><nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / Çerez tercihleri</nav><h1>Çerez Tercihleri</h1><p>Hangi çerezlere izin verdiğinizi dilediğiniz zaman buradan değiştirebilirsiniz.</p></div></section>
<section class="section-sm">
    <div class="container narrow">
        <form method="post" class="card">
            <?= csrf_field() ?>
            <div class="pref"><div><strong>Zorunlu çerezler</strong><p>Oturum, sepet ve güvenli giriş için gereklidir. Kapatılamaz.</p></div><label class="switch"><input type="checkbox" checked disabled><span></span></label></div>
            <div class="pref"><div><strong>Analitik çerezler</strong><p>Siteyi nasıl kullandığınızı anonim olarak ölçmemize ve geliştirmemize yardımcı olur.</p></div><label class="switch"><input type="checkbox" name="analitik" value="1" <?= !empty($prefs['analitik']) ? 'checked' : '' ?>><span></span></label></div>
            <div class="pref"><div><strong>Pazarlama çerezleri</strong><p>İlgi alanlarınıza uygun kampanya ve ürünleri göstermek için kullanılır.</p></div><label class="switch"><input type="checkbox" name="pazarlama" value="1" <?= !empty($prefs['pazarlama']) ? 'checked' : '' ?>><span></span></label></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
                <button class="btn btn-dark">Tercihlerimi Kaydet</button>
                <button class="btn btn-primary" name="hepsi" value="1">Tümünü Kabul Et</button>
            </div>
            <p class="small muted" style="margin-top:16px">Ayrıntılı bilgi için <a href="<?= url('sayfa.php?s=cerez-politikasi') ?>">Çerez Politikası</a>'nı inceleyebilirsiniz.</p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
