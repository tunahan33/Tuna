</main>
<section class="perks">
    <div class="container perks-row">
        <div><strong>Hızlı Kargo</strong><span><?= e(setting('shipping_days')) ?> içinde kargoda</span></div>
        <div><strong>Ücretsiz İade</strong><span>14 gün içinde koşulsuz iade</span></div>
        <div><strong>Güvenli Ödeme</strong><span>256-bit SSL · 3D Secure</span></div>
        <div><strong>Orijinal Ürün</strong><span>Faturalı ve garantili</span></div>
    </div>
</section>
<footer class="footer">
    <div class="container footer-cols">
        <div class="footer-col">
            <h4>KURUMSAL</h4>
            <ul>
                <li><a href="<?= url('sayfa.php?s=hakkimizda') ?>">Hakkımızda</a></li>
                <li><a href="<?= url('insan-kaynaklari.php') ?>">İnsan Kaynakları</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>MÜŞTERİ HİZMETLERİ</h4>
            <ul>
                <li><a href="<?= url('sayfa.php?s=sss') ?>">Sıkça sorulan sorular</a></li>
                <li><a href="<?= url('siparis-takibi.php') ?>">Sipariş takibi</a></li>
                <li><a href="<?= url('ariza-takibi.php') ?>">Arıza takibi</a></li>
                <li><a href="<?= url('sayfa.php?s=iade-ve-iade-ceki-kosullari') ?>">İade ve iade çeki koşulları</a></li>
                <li><a href="<?= url('sayfa.php?s=teslimat-kosullari') ?>">Teslimat koşulları</a></li>
                <li><a href="<?= url('sayfa.php?s=guvenli-alisveris') ?>">Güvenli alışveriş</a></li>
                <li><a href="<?= url('iletisim.php') ?>">İletişim</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>SÖZLEŞMELER VE YASAL</h4>
            <ul>
                <li><a href="<?= url('sayfa.php?s=uyelik-sozlesmesi') ?>">Üyelik sözleşmesi</a></li>
                <li><a href="<?= url('sayfa.php?s=genel-aydinlatma-metni') ?>">Genel Aydınlatma metni</a></li>
                <li><a href="<?= url('sayfa.php?s=cerez-politikasi') ?>">Çerez politikası</a></li>
                <li><a href="<?= url('cerez-tercihleri.php') ?>">Çerez tercihleri</a></li>
                <li><a href="<?= url('basvuru-formu.php') ?>">İlgili kişi başvuru formu</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <nav class="footer-legal">
            <a href="<?= url('sayfa.php?s=mesafeli-satis-sozlesmesi') ?>">Mesafeli Satış Sözleşmesi</a>
            <a href="<?= url('sayfa.php?s=on-bilgilendirme-formu') ?>">Ön Bilgilendirme Formu</a>
            <a href="<?= url('sayfa.php?s=gizlilik-politikasi') ?>">Gizlilik Politikası</a>
        </nav>
        <div class="footer-pay" aria-label="Kabul edilen kartlar">
            <span>VISA</span><span>Mastercard</span><span>troy</span><span>3D Secure</span>
        </div>
        <p>© <?= date('Y') ?> <?= e(setting('site_name')) ?> · Tüm fiyatlara KDV dahildir.</p>
    </div>
</footer>
<?php if (!isset($_COOKIE['gs_cerez'])): ?>
<div class="cookie-bar" data-cookie-bar>
    <p>Sitemizde deneyiminizi iyileştirmek için çerezler kullanıyoruz. Ayrıntılar için <a href="<?= url('sayfa.php?s=cerez-politikasi') ?>">Çerez Politikası</a>.</p>
    <div>
        <a class="btn btn-ghost btn-sm" href="<?= url('cerez-tercihleri.php') ?>">Tercihler</a>
        <button class="btn btn-ghost btn-sm" data-cookie="necessary">Yalnızca zorunlu</button>
        <button class="btn btn-primary btn-sm" data-cookie="all">Tümünü kabul et</button>
    </div>
</div>
<?php endif; ?>
<script src="<?= asset('js/site.js') ?>"></script>
</body>
</html>
