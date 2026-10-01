<?php $__footerPages = rows('SELECT slug, title FROM pages WHERE show_in_footer = 1 ORDER BY sort_order'); ?>
</main>
<section class="trust-bar">
    <div class="container trust-grid">
        <div><strong>🚚 Hızlı Kargo</strong><span><?= e(setting('shipping_days', '1-3 iş günü')) ?> içinde kargoda</span></div>
        <div><strong>↺ 14 Gün İade</strong><span>Kolay iade ve beden değişimi</span></div>
        <div><strong>🔒 Güvenli Ödeme</strong><span>Garanti BBVA 3D Secure</span></div>
        <div><strong>👕 Takım Siparişi</strong><span>İsim-numara baskılı toplu sipariş</span></div>
    </div>
</section>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img src="<?= asset('img/logo-light.svg') ?>" alt="<?= e(setting('site_name', 'GS Sportif')) ?>" width="210" height="48">
            <p class="muted-light"><?= e(setting('site_description')) ?></p>
        </div>
        <div>
            <h4>Kategoriler</h4>
            <ul>
                <?php foreach ($__cats ?? [] as $__c): ?><li><a href="<?= url('urunler.php?kategori=' . urlencode($__c['slug'])) ?>"><?= e($__c['name']) ?></a></li><?php endforeach; ?>
                <li><a href="<?= url('takim-siparisi.php') ?>">Takım Siparişi</a></li>
            </ul>
        </div>
        <div>
            <h4>Kurumsal &amp; Yasal</h4>
            <ul>
                <li><a href="<?= url('sayfa.php?s=hakkimizda') ?>">Hakkımızda</a></li>
                <?php foreach ($__footerPages as $__p): ?><li><a href="<?= url('sayfa.php?s=' . urlencode($__p['slug'])) ?>"><?= e($__p['title']) ?></a></li><?php endforeach; ?>
                <li><a href="<?= url('iletisim.php') ?>">İletişim</a></li>
            </ul>
        </div>
        <div>
            <h4>İletişim</h4>
            <ul class="contact-list">
                <li><strong><?= e(setting('company_title')) ?></strong></li>
                <li><?= e(setting('company_address')) ?></li>
                <li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a></li>
                <li><a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a></li>
                <li>Vergi Dairesi / No: <?= e(setting('tax_office')) ?> / <?= e(setting('tax_number')) ?></li>
                <li>MERSİS: <?= e(setting('mersis_number')) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer-pay">
        <div class="secure-note">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            Ödemeleriniz <strong>Garanti BBVA</strong> güvencesiyle <strong>3D Secure</strong> ve 256-bit SSL ile korunur. Kart bilgileriniz sitemizde saklanmaz.
        </div>
        <img src="<?= asset('img/payment-logos.svg') ?>" alt="Visa, Mastercard, Troy, 3D Secure" height="32" class="pay-logos">
    </div>
    <div class="footer-bottom">
        <div class="container">© <?= date('Y') ?> <?= e(setting('site_name', 'GS Sportif')) ?>. Tüm hakları saklıdır. Sitedeki tüm fiyatlara KDV dahildir.</div>
    </div>
</footer>
<div class="cookie-bar" data-cookie-bar hidden>
    <span>Sitemizde yalnızca zorunlu çerezler kullanılmaktadır. <a href="<?= url('sayfa.php?s=cerez-politikasi') ?>">Çerez Politikası</a></span>
    <button class="btn btn-primary btn-sm" data-cookie-ok>Tamam</button>
</div>
<?php if ($wa = preg_replace('/[^0-9]/', '', setting('company_whatsapp'))): ?>
<a class="wa-float" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg></a>
<?php endif; ?>
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
