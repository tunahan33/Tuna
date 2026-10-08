<?php $__footerPages = rows('SELECT slug, title FROM pages WHERE show_in_footer = 1 ORDER BY sort_order'); ?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img src="<?= asset('img/logo-light.svg') ?>" alt="GS Projeler" width="210" height="48">
            <p class="muted-light"><?= e(setting('site_description')) ?></p>
        </div>
        <div>
            <h4>Hizmetlerimiz</h4>
            <ul>
                <?php foreach ($__services ?? [] as $__s): ?>
                    <li><a href="<?= url('hizmet.php?slug=' . urlencode($__s['slug'])) ?>"><?= e($__s['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Kurumsal &amp; Yasal</h4>
            <ul>
                <li><a href="<?= url('sayfa.php?s=hakkimizda') ?>">Hakkımızda</a></li>
                <?php foreach ($__footerPages as $__p): ?>
                    <li><a href="<?= url('sayfa.php?s=' . urlencode($__p['slug'])) ?>"><?= e($__p['title']) ?></a></li>
                <?php endforeach; ?>
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
            Sitemiz <strong>256-bit SSL</strong> ile korunur; kişisel verileriniz <strong>KVKK</strong> kapsamında güvendedir.
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">© <?= date('Y') ?> <?= e(setting('site_name', 'GS Projeler')) ?>. Tüm hakları saklıdır. Sitede yer alan tüm fiyatlara KDV dahildir.</div>
    </div>
</footer>
<div class="modal" id="package-modal" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pm-title">
        <button class="modal-close" data-close aria-label="Kapat">×</button>
        <div class="modal-body" data-modal-body></div>
    </div>
</div>
<div class="cookie-bar" data-cookie-bar hidden>
    <span>Sitemizde yalnızca zorunlu çerezler kullanılmaktadır. <a href="<?= url('sayfa.php?s=cerez-politikasi') ?>">Çerez Politikası</a></span>
    <button class="btn btn-primary btn-sm" data-cookie-ok>Tamam</button>
</div>
<?php if ($wa = preg_replace('/[^0-9]/', '', setting('company_whatsapp'))): ?>
<a class="wa-float" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.4-.7-2.8-1.1-4.6-4-4.8-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l2 .9c.3.1.5.2.5.3.1.2.1.8-.1 1.4z"/></svg>
</a>
<?php endif; ?>
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
