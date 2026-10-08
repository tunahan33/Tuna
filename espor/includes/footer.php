<?php
$__salesPages = rows('SELECT slug, title FROM pages WHERE show_in_footer = 1 ORDER BY sort_order');
$__p = fn(string $slug) => url('sayfa.php?s=' . $slug);
$__socials = array_filter(['Instagram' => setting('instagram'), 'YouTube' => setting('youtube'), 'Twitch' => setting('twitch'), 'Discord' => setting('discord'), 'LinkedIn' => setting('linkedin')]);
?>
</main>
<footer class="site-footer">
    <div class="container footer-brand">
        <a href="<?= url() ?>"><img src="<?= asset('img/logo-light.svg') ?>" alt="GS Sportif Faaliyetler" width="330" height="60"></a>
        <p><?= e(setting('site_description')) ?></p>
        <?php if ($__socials): ?>
            <div class="footer-social"><?php foreach ($__socials as $__n => $__u): ?><a href="<?= e($__u) ?>" target="_blank" rel="noopener"><?= e($__n) ?></a><?php endforeach; ?></div>
        <?php endif; ?>
    </div>
    <div class="container footer-cols">
        <div>
            <h4>KURUMSAL</h4>
            <ul>
                <li><a href="<?= $__p('hakkimizda') ?>">Hakkımızda</a></li>
                <li><a href="<?= $__p('insan-kaynaklari') ?>">İnsan Kaynakları</a></li>
            </ul>
        </div>
        <div>
            <h4>MÜŞTERİ HİZMETLERİ</h4>
            <ul>
                <li><a href="<?= $__p('sikca-sorulan-sorular') ?>">Sıkça sorulan sorular</a></li>
                <li><a href="<?= $__p('siparis-takibi') ?>">Sipariş takibi</a></li>
                <li><a href="<?= $__p('ariza-takibi') ?>">Arıza takibi</a></li>
                <li><a href="<?= $__p('iade-ve-iade-ceki-kosullari') ?>">İade ve iade çeki koşulları</a></li>
                <li><a href="<?= $__p('teslimat-kosullari') ?>">Teslimat koşulları</a></li>
                <li><a href="<?= $__p('guvenli-alisveris') ?>">Güvenli alışveriş</a></li>
                <li><a href="<?= url('iletisim.php') ?>">İletişim</a></li>
            </ul>
        </div>
        <div>
            <h4>SÖZLEŞMELER VE YASAL</h4>
            <ul>
                <li><a href="<?= $__p('uyelik-sozlesmesi') ?>">Üyelik sözleşmesi</a></li>
                <li><a href="<?= $__p('genel-aydinlatma-metni') ?>">Genel Aydınlatma metni</a></li>
                <li><a href="<?= $__p('cerez-politikasi') ?>">Çerez politikası</a></li>
                <li><a href="<?= $__p('cerez-tercihleri') ?>">Çerez tercihleri</a></li>
                <li><a href="<?= $__p('ilgili-kisi-basvuru-formu') ?>">İlgili kişi başvuru formu</a></li>
            </ul>
        </div>
    </div>
    <?php if ($__salesPages): ?>
    <div class="container footer-legal">
        <?php foreach ($__salesPages as $__sp): ?><a href="<?= $__p($__sp['slug']) ?>"><?= e($__sp['title']) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="container footer-company">
        <strong><?= e(setting('company_title')) ?></strong>
        <span><?= e(setting('company_address')) ?></span>
        <span><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a> · <a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a></span>
        <span>Vergi Dairesi / No: <?= e(setting('tax_office')) ?> / <?= e(setting('tax_number')) ?> · MERSİS: <?= e(setting('mersis_number')) ?><?= setting('kep_address') !== '' && setting('kep_address') !== '-' ? ' · KEP: ' . e(setting('kep_address')) : '' ?></span>
    </div>
    <div class="container footer-pay">
        <div class="secure-note">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            Ödemeleriniz <strong>3D Secure</strong> doğrulaması ve 256-bit SSL ile korunur. Kart bilgileriniz sitemizde saklanmaz.
        </div>
        <img src="<?= asset('img/payment-logos.svg') ?>" alt="Visa, Mastercard, Troy, 3D Secure" height="32" class="pay-logos">
    </div>
    <div class="footer-bottom">
        <div class="container">© <?= date('Y') ?> <?= e(setting('site_name', 'GS Sportif Faaliyetler')) ?>. Tüm hakları saklıdır. <?= e(setting('site_name', 'GS Sportif Faaliyetler')) ?>, <?= e(setting('company_title')) ?> markasıdır. Sitede yer alan tüm fiyatlara KDV dahildir. Adı geçen oyun adları ilgili şirketlerin tescilli markalarıdır.</div>
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
    <span>Sitemizde zorunlu çerezler kullanılır; isteğe bağlı çerezler yalnızca izninizle etkinleşir. <a href="<?= url('sayfa.php?s=cerez-politikasi') ?>">Çerez Politikası</a></span>
    <span class="cookie-actions"><a class="btn btn-ghost btn-sm" href="<?= url('sayfa.php?s=cerez-tercihleri') ?>">Tercihler</a><button class="btn btn-primary btn-sm" data-cookie-ok>Kabul Et</button></span>
</div>
<?php if ($wa = preg_replace('/[^0-9]/', '', setting('company_whatsapp'))): ?>
<a class="wa-float" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.4-.7-2.8-1.1-4.6-4-4.8-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l2 .9c.3.1.5.2.5.3.1.2.1.8-.1 1.4z"/></svg>
</a>
<?php endif; ?>
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
