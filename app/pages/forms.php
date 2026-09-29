<?php
/** İçerik sayfaları ve form içeren sayfalar (iletişim, İK, KVKK başvuru, sipariş/arıza takibi, çerez tercihleri). */

const SIDE_GROUPS = [
    'Kurumsal' => ['hakkimizda' => 'Hakkımızda', 'insan-kaynaklari' => 'İnsan Kaynakları'],
    'Müşteri Hizmetleri' => ['sss' => 'Sıkça sorulan sorular', 'siparis-takibi' => 'Sipariş takibi', 'ariza-takibi' => 'Arıza takibi',
        'iade-kosullari' => 'İade ve iade çeki koşulları', 'teslimat-kosullari' => 'Teslimat koşulları', 'guvenli-alisveris' => 'Güvenli alışveriş', 'iletisim' => 'İletişim'],
    'Sözleşmeler ve Yasal' => ['uyelik-sozlesmesi' => 'Üyelik sözleşmesi', 'aydinlatma-metni' => 'Genel Aydınlatma metni', 'cerez-politikasi' => 'Çerez politikası',
        'cerez-tercihleri' => 'Çerez tercihleri', 'basvuru-formu' => 'İlgili kişi başvuru formu', 'mesafeli-satis-sozlesmesi' => 'Mesafeli satış sözleşmesi',
        'on-bilgilendirme-formu' => 'Ön bilgilendirme formu', 'gizlilik-politikasi' => 'Gizlilik politikası'],
];

function content_page(string $slug, ?callable $extra = null, ?callable $before = null): void
{
    $pg = page($slug);
    if (!$pg) not_found();
    $group = 'Sözleşmeler ve Yasal';
    foreach (SIDE_GROUPS as $g => $items) if (isset($items[$slug])) $group = $g;

    render($pg['title'], function () use ($pg, $slug, $group, $extra, $before) { ?>
<section class="page-hero"><div class="container">
  <nav class="crumbs"><a href="<?= url() ?>">Ana Sayfa</a> / <?= e($group) ?> / <?= e($pg['title']) ?></nav>
  <h1><?= e($pg['title']) ?></h1>
  <?php if ($pg['updated_at']): ?><p class="small">Son güncelleme: <?= tr_date($pg['updated_at'], false) ?></p><?php endif; ?>
</div></section>
<section class="section"><div class="container content-layout">
  <aside class="side-nav">
    <?php foreach (SIDE_GROUPS as $g => $items): ?>
      <h4><?= e($g) ?></h4>
      <ul><?php foreach ($items as $s => $label): ?><li><a href="<?= url($s) ?>"<?= $s === $slug ? ' class="active"' : '' ?>><?= e($label) ?></a></li><?php endforeach; ?></ul>
    <?php endforeach; ?>
  </aside>
  <div class="content-main">
    <?php if ($before) $before(); ?>
    <div class="prose"><?= fill_placeholders($pg['content']) ?></div>
    <?php if ($extra) $extra(); ?>
  </div>
</div></section>
<?php
    });
}

function page_static(string $slug): void
{
    content_page($slug);
}

function is_bot_submission(): bool
{
    return post('website') !== '';
}

function honeypot(): string
{
    return '<div class="hp" aria-hidden="true"><label>Web sitesi<input name="website" tabindex="-1" autocomplete="off"></label></div>';
}

function save_message(string $type, array $d, string $label): bool
{
    if (is_bot_submission()) return true;
    q('INSERT INTO messages(type, name, email, phone, subject, message, extra, ip, created_at) VALUES(?,?,?,?,?,?,?,?,?)', [
        $type, $d['name'], $d['email'], $d['phone'] ?? '', $d['subject'] ?? '', $d['message'], $d['extra'] ?? '', client_ip(), now(),
    ]);
    log_activity('message_received', $label . ' — ' . $d['name'] . ' <' . $d['email'] . '>', null, current_user()['name'] ?? $d['name']);
    return true;
}

function page_contact(): void
{
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $d = ['name' => post('name'), 'email' => post('email'), 'phone' => post('phone'), 'subject' => post('subject'), 'message' => post('message')];
        if (mb_strlen($d['name']) < 3) $errors[] = 'Adınızı girin.';
        if (!valid_email($d['email'])) $errors[] = 'Geçerli bir e-posta girin.';
        if (mb_strlen($d['message']) < 10) $errors[] = 'Mesajınız en az 10 karakter olmalıdır.';
        if (empty($_POST['kvkk'])) $errors[] = 'Aydınlatma metnini onaylayın.';
        if (!$errors) {
            save_message('iletisim', $d, 'İletişim formu');
            flash('success', 'Mesajınız bize ulaştı. En kısa sürede size dönüş yapacağız.');
            redirect('iletisim');
        }
    }
    $services = rows('SELECT title FROM services WHERE is_active = 1 ORDER BY sort');
    content_page('iletisim', function () use ($errors, $services) { ?>
<div class="contact-grid">
  <div class="contact-cards">
    <div class="contact-card"><?= icon('phone') ?><div><span>Telefon</span><a href="tel:<?= e(preg_replace('/\s+/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a></div></div>
    <div class="contact-card"><?= icon('mail') ?><div><span>E-posta</span><a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a></div></div>
    <div class="contact-card"><?= icon('pin') ?><div><span>Adres</span><?= e(setting('company_address')) ?></div></div>
    <div class="contact-card"><?= icon('clock') ?><div><span>Çalışma saatleri</span><?= e(setting('working_hours')) ?></div></div>
    <div class="contact-card"><?= icon('shield') ?><div><span>KEP</span><?= e(setting('company_kep')) ?></div></div>
  </div>
  <form method="post" class="card form">
    <h3>Bize yazın</h3>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <?= csrf_field() . honeypot() ?>
    <div class="grid-2">
      <label>Ad Soyad *<input name="name" required value="<?= old('name') ?>"></label>
      <label>E-posta *<input type="email" name="email" required value="<?= old('email') ?>"></label>
      <label>Telefon<input type="tel" name="phone" value="<?= old('phone') ?>"></label>
      <label>Konu<select name="subject">
        <option>Genel bilgi</option><option>Ücretsiz ön görüşme</option>
        <?php foreach ($services as $s): ?><option <?= ($_POST['subject'] ?? '') === $s['title'] ? 'selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?>
        <option>İş birliği</option><option>Öneri / Şikâyet</option>
      </select></label>
    </div>
    <label>Mesajınız *<textarea name="message" rows="5" required><?= old('message') ?></textarea></label>
    <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?= url('aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum.</span></label>
    <button class="btn btn-primary">Gönder</button>
  </form>
</div>
<?php
    });
}

function page_hr(): void
{
    $errors = [];
    $positions = ['Spor Bilimci / Antrenör', 'Spor Diyetisyeni', 'Spor Psikoloğu', 'Kulüp Yönetimi ve Proje Uzmanı', 'Satış Temsilcisi', 'Dijital Pazarlama Uzmanı', 'Stajyer', 'Diğer'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $d = ['name' => post('name'), 'email' => post('email'), 'phone' => post('phone'), 'subject' => post('position'), 'message' => post('message'),
              'extra' => 'Deneyim: ' . post('experience') . ' | Profil/CV bağlantısı: ' . post('link')];
        if (mb_strlen($d['name']) < 3) $errors[] = 'Adınızı girin.';
        if (!valid_email($d['email'])) $errors[] = 'Geçerli bir e-posta girin.';
        if (!in_array($d['subject'], $positions, true)) $errors[] = 'Pozisyon seçin.';
        if (mb_strlen($d['message']) < 20) $errors[] = 'Kendinizi kısaca tanıtın (en az 20 karakter).';
        if (empty($_POST['kvkk'])) $errors[] = 'Aydınlatma metnini onaylayın.';
        if (!$errors) {
            save_message('ik', $d, 'İK başvurusu (' . $d['subject'] . ')');
            flash('success', 'Başvurunuz alındı. İlginiz için teşekkür ederiz.');
            redirect('insan-kaynaklari');
        }
    }
    content_page('insan-kaynaklari', function () use ($errors, $positions) { ?>
<form method="post" class="card form">
  <h3>Genel başvuru formu</h3>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <?= csrf_field() . honeypot() ?>
  <div class="grid-2">
    <label>Ad Soyad *<input name="name" required value="<?= old('name') ?>"></label>
    <label>E-posta *<input type="email" name="email" required value="<?= old('email') ?>"></label>
    <label>Telefon<input type="tel" name="phone" value="<?= old('phone') ?>"></label>
    <label>Pozisyon *<select name="position" required><option value="">Seçiniz</option><?php foreach ($positions as $p): ?><option <?= ($_POST['position'] ?? '') === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select></label>
    <label>Deneyim<select name="experience"><option>0-1 yıl</option><option>1-3 yıl</option><option>3-5 yıl</option><option>5+ yıl</option></select></label>
    <label>LinkedIn / CV bağlantısı<input type="url" name="link" placeholder="https://" value="<?= old('link') ?>"></label>
  </div>
  <label>Kendinizi tanıtın *<textarea name="message" rows="5" required><?= old('message') ?></textarea></label>
  <label class="check"><input type="checkbox" name="kvkk" value="1" required> <span>Başvurum kapsamında kişisel verilerimin işlenmesine ilişkin <a href="<?= url('aydinlatma-metni') ?>" target="_blank">Aydınlatma Metni</a>'ni okudum.</span></label>
  <button class="btn btn-primary">Başvuruyu gönder</button>
</form>
<?php
    });
}

function page_kvkk(): void
{
    $errors = [];
    $requests = [
        'Kişisel verilerimin işlenip işlenmediğini öğrenmek istiyorum',
        'İşlenen kişisel verilerim hakkında bilgi talep ediyorum',
        'Verilerimin işlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenmek istiyorum',
        'Verilerimin aktarıldığı üçüncü kişileri öğrenmek istiyorum',
        'Eksik / yanlış işlenen verilerimin düzeltilmesini istiyorum',
        'Kişisel verilerimin silinmesini / yok edilmesini istiyorum',
        'Otomatik sistemlerle analiz sonucu aleyhime çıkan sonuca itiraz ediyorum',
        'Kanuna aykırı işleme nedeniyle zararın giderilmesini talep ediyorum',
    ];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $sel = array_values(array_intersect($requests, (array)($_POST['requests'] ?? [])));
        $d = ['name' => post('name'), 'email' => post('email'), 'phone' => post('phone'), 'subject' => post('relation'),
              'message' => post('message'), 'extra' => 'T.C.: ' . preg_replace('/\D/', '', post('tc')) . ' | Adres: ' . post('address') . ' | Talepler: ' . implode('; ', $sel) . ' | Yanıt: ' . post('reply')];
        if (mb_strlen($d['name']) < 5) $errors[] = 'Ad soyad girin.';
        if (strlen(preg_replace('/\D/', '', post('tc'))) !== 11) $errors[] = 'T.C. kimlik numarası 11 haneli olmalıdır.';
        if (!valid_email($d['email'])) $errors[] = 'Geçerli bir e-posta girin.';
        if (!$sel) $errors[] = 'En az bir talep seçin.';
        if (empty($_POST['confirm'])) $errors[] = 'Beyanı onaylayın.';
        if (!$errors) {
            save_message('kvkk', $d, 'KVKK ilgili kişi başvurusu');
            flash('success', 'Başvurunuz alındı. En geç 30 gün içinde tarafınıza yanıt verilecektir.');
            redirect('basvuru-formu');
        }
    }
    content_page('basvuru-formu', function () use ($errors, $requests) { ?>
<form method="post" class="card form">
  <h3>Başvuru sahibi bilgileri</h3>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <?= csrf_field() . honeypot() ?>
  <div class="grid-2">
    <label>Ad Soyad *<input name="name" required value="<?= old('name') ?>"></label>
    <label>T.C. Kimlik No *<input name="tc" required inputmode="numeric" maxlength="11" value="<?= old('tc') ?>"></label>
    <label>E-posta *<input type="email" name="email" required value="<?= old('email') ?>"></label>
    <label>Telefon<input type="tel" name="phone" value="<?= old('phone') ?>"></label>
    <label class="span-2">Adres<input name="address" value="<?= old('address') ?>"></label>
    <label>Şirketimizle ilişkiniz<select name="relation"><option>Müşteri / Danışan</option><option>Ziyaretçi</option><option>Çalışan adayı</option><option>Eski çalışan</option><option>İş ortağı</option><option>Diğer</option></select></label>
    <label>Yanıt yöntemi<select name="reply"><option>E-posta</option><option>Adresime posta</option><option>Elden teslim</option></select></label>
  </div>
  <fieldset><legend>Talebiniz (KVKK m.11) *</legend>
    <?php foreach ($requests as $r): ?><label class="check"><input type="checkbox" name="requests[]" value="<?= e($r) ?>" <?= in_array($r, (array)($_POST['requests'] ?? []), true) ? 'checked' : '' ?>> <span><?= e($r) ?></span></label><?php endforeach; ?>
  </fieldset>
  <label>Talebinize ilişkin açıklama<textarea name="message" rows="4"><?= old('message') ?></textarea></label>
  <label class="check"><input type="checkbox" name="confirm" value="1" required> <span>Bu formda verdiğim bilgilerin doğru olduğunu ve başvurunun şahsıma ait olduğunu beyan ederim.</span></label>
  <button class="btn btn-primary">Başvuruyu gönder</button>
</form>
<?php
    });
}

function page_order_track(): void
{
    $no = strtoupper(trim((string)($_REQUEST['no'] ?? '')));
    $email = trim((string)($_REQUEST['email'] ?? ''));
    $order = null; $searched = false;
    if ($no !== '' && $email !== '') {
        $searched = true;
        $order = row('SELECT * FROM orders WHERE order_no = ? AND lower(email) = lower(?)', [$no, $email]);
    }
    content_page('siparis-takibi', function () use ($no, $email, $order, $searched) { ?>
<form method="get" class="card form track-form">
  <div class="grid-2">
    <label>Sipariş numarası<input name="no" required placeholder="GS250101xxxxx" value="<?= e($no) ?>"></label>
    <label>E-posta adresi<input type="email" name="email" required value="<?= e($email) ?>"></label>
  </div>
  <button class="btn btn-primary">Siparişi sorgula</button>
</form>
<?php if ($searched && !$order): ?>
  <div class="alert alert-error">Bu bilgilerle eşleşen bir sipariş bulunamadı. Lütfen bilgileri kontrol edin.</div>
<?php elseif ($order):
    $steps = ['pending' => 'Sipariş alındı', 'paid' => 'Ödeme onaylandı', 'processing' => 'Hizmet başladı', 'completed' => 'Tamamlandı'];
    $keys = array_keys($steps);
    $idx = array_search($order['status'], $keys, true); ?>
  <div class="card order-status">
    <div class="os-head">
      <div><span class="muted small">Sipariş No</span><strong><?= e($order['order_no']) ?></strong></div>
      <div><?= status_badge($order['status']) ?></div>
    </div>
    <?php if ($idx !== false): ?>
    <ol class="timeline">
      <?php foreach ($steps as $k => $label): $i = array_search($k, $keys, true); ?>
        <li class="<?= $i <= $idx ? 'done' : '' ?>"><span></span><?= e($label) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>
    <dl class="sum-lines">
      <div><dt>Hizmet</dt><dd><?= e($order['service_title']) ?> — <?= e($order['package_name']) ?></dd></div>
      <div><dt>Tutar</dt><dd><?= money((int)$order['amount']) ?></dd></div>
      <div><dt>Sipariş tarihi</dt><dd><?= tr_date($order['created_at']) ?></dd></div>
      <?php if ($order['paid_at']): ?><div><dt>Ödeme tarihi</dt><dd><?= tr_date($order['paid_at']) ?></dd></div><?php endif; ?>
    </dl>
    <?php if (in_array($order['status'], ['pending', 'failed'], true)): ?>
      <p class="small muted">Ödemesi tamamlanmamış siparişler için yeni bir sipariş oluşturabilirsiniz.</p>
    <?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= url('ariza-takibi') ?>?order=<?= e($order['order_no']) ?>">Bu siparişle ilgili destek kaydı aç</a>
  </div>
<?php endif;
    });
}

const TICKET_STATUSES = ['open' => ['Açık', 'yellow'], 'answered' => ['Yanıtlandı', 'blue'], 'waiting' => ['Müşteri yanıtı bekleniyor', 'gray'], 'closed' => ['Çözüldü', 'green']];

function ticket_badge(string $s): string
{
    [$l, $c] = TICKET_STATUSES[$s] ?? [$s, 'gray'];
    return '<span class="badge badge-' . $c . '">' . e($l) . '</span>';
}

function page_tickets(): void
{
    $errors = [];
    $ticket = null;
    $mode = $_GET['tab'] ?? 'new';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (post('action') === 'reply') {
            $t = row('SELECT * FROM tickets WHERE ticket_no = ? AND lower(email) = lower(?)', [strtoupper(post('ticket_no')), post('email')]);
            if ($t && mb_strlen(post('message')) >= 2 && $t['status'] !== 'closed') {
                q('INSERT INTO ticket_replies(ticket_id, author, is_staff, message, created_at) VALUES(?,?,0,?,?)', [$t['id'], $t['name'], post('message'), now()]);
                q("UPDATE tickets SET status = 'open', updated_at = ? WHERE id = ?", [now(), $t['id']]);
                log_activity('ticket_reply', $t['ticket_no'] . ' (müşteri yanıtı)', null, $t['name']);
                flash('success', 'Yanıtınız iletildi.');
            }
            redirect('ariza-takibi?tab=query&no=' . urlencode(strtoupper(post('ticket_no'))) . '&email=' . urlencode(post('email')));
        }
        $d = ['order_no' => strtoupper(post('order_no')), 'name' => post('name'), 'email' => post('email'), 'subject' => post('subject'), 'message' => post('message')];
        if (mb_strlen($d['name']) < 3) $errors[] = 'Adınızı girin.';
        if (!valid_email($d['email'])) $errors[] = 'Geçerli bir e-posta girin.';
        if (mb_strlen($d['message']) < 10) $errors[] = 'Sorunu en az 10 karakterle açıklayın.';
        if (!$errors && !is_bot_submission()) {
            do { $tno = random_code('AT', 7); } while (val('SELECT 1 FROM tickets WHERE ticket_no = ?', [$tno]));
            q('INSERT INTO tickets(ticket_no, order_no, name, email, subject, message, status, created_at, updated_at) VALUES(?,?,?,?,?,?,?,?,?)',
                [$tno, $d['order_no'], $d['name'], $d['email'], $d['subject'], $d['message'], 'open', now(), now()]);
            log_activity('ticket_created', $tno . ' — ' . $d['subject'], null, current_user()['name'] ?? $d['name']);
            flash('success', 'Destek kaydınız oluşturuldu. Kayıt numaranız: ' . $tno . ' — bu numarayla kaydınızı takip edebilirsiniz.');
            redirect('ariza-takibi?tab=query&no=' . $tno . '&email=' . urlencode($d['email']));
        }
        $mode = 'new';
    }

    $qNo = strtoupper(trim((string)($_GET['no'] ?? '')));
    $qEmail = trim((string)($_GET['email'] ?? ''));
    if ($qNo && $qEmail) {
        $ticket = row('SELECT * FROM tickets WHERE ticket_no = ? AND lower(email) = lower(?)', [$qNo, $qEmail]);
        $mode = 'query';
    }
    $replies = $ticket ? rows('SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY id', [$ticket['id']]) : [];

    content_page('ariza-takibi', function () use ($errors, $ticket, $replies, $mode, $qNo, $qEmail) { ?>
<div class="tabs" data-tabs>
  <a href="?tab=new" class="<?= $mode === 'new' ? 'on' : '' ?>">Yeni kayıt oluştur</a>
  <a href="?tab=query" class="<?= $mode === 'query' ? 'on' : '' ?>">Kaydımı sorgula</a>
</div>
<?php if ($mode === 'new'): ?>
<form method="post" class="card form">
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <?= csrf_field() . honeypot() ?>
  <div class="grid-2">
    <label>Ad Soyad *<input name="name" required value="<?= old('name', current_user()['name'] ?? '') ?>"></label>
    <label>E-posta *<input type="email" name="email" required value="<?= old('email', current_user()['email'] ?? '') ?>"></label>
    <label>Sipariş numarası<input name="order_no" value="<?= old('order_no', $_GET['order'] ?? '') ?>" placeholder="Varsa"></label>
    <label>Konu *<select name="subject">
      <?php foreach (['Görüşme planlama sorunu', 'Danışmanıma ulaşamıyorum', 'Program / rapor teslimi', 'Online görüşme bağlantı sorunu', 'Ödeme / fatura', 'Cayma / iade talebi', 'Diğer'] as $s): ?>
        <option <?= ($_POST['subject'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select></label>
  </div>
  <label>Sorunu açıklayın *<textarea name="message" rows="5" required><?= old('message') ?></textarea></label>
  <button class="btn btn-primary">Kayıt oluştur</button>
</form>
<?php else: ?>
<form method="get" class="card form track-form">
  <input type="hidden" name="tab" value="query">
  <div class="grid-2">
    <label>Kayıt numarası<input name="no" required placeholder="AT0000000" value="<?= e($qNo) ?>"></label>
    <label>E-posta<input type="email" name="email" required value="<?= e($qEmail) ?>"></label>
  </div>
  <button class="btn btn-primary">Sorgula</button>
</form>
<?php if ($qNo && !$ticket): ?><div class="alert alert-error">Kayıt bulunamadı.</div><?php endif; ?>
<?php if ($ticket): ?>
<div class="card">
  <div class="os-head"><div><span class="muted small">Kayıt No</span><strong><?= e($ticket['ticket_no']) ?></strong></div><?= ticket_badge($ticket['status']) ?></div>
  <p><strong><?= e($ticket['subject']) ?></strong><?= $ticket['order_no'] ? ' · Sipariş: ' . e($ticket['order_no']) : '' ?></p>
  <div class="thread">
    <div class="msg"><div class="msg-meta"><?= e($ticket['name']) ?> · <?= tr_date($ticket['created_at']) ?></div><?= nl2br(e($ticket['message'])) ?></div>
    <?php foreach ($replies as $r): ?>
      <div class="msg<?= $r['is_staff'] ? ' staff' : '' ?>"><div class="msg-meta"><?= e($r['is_staff'] ? setting('site_name') . ' Destek' : $r['author']) ?> · <?= tr_date($r['created_at']) ?></div><?= nl2br(e($r['message'])) ?></div>
    <?php endforeach; ?>
  </div>
  <?php if ($ticket['status'] !== 'closed'): ?>
  <form method="post" class="form">
    <?= csrf_field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="ticket_no" value="<?= e($ticket['ticket_no']) ?>"><input type="hidden" name="email" value="<?= e($ticket['email']) ?>">
    <label>Yanıt yazın<textarea name="message" rows="3" required></textarea></label>
    <button class="btn btn-dark btn-sm">Yanıt gönder</button>
  </form>
  <?php endif; ?>
</div>
<?php endif; endif;
    });
}

function page_cookie_prefs(): void
{
    content_page('cerez-tercihleri', function () { ?>
<div class="card cookie-prefs" id="cookiePrefs">
  <div class="cp-row"><div><strong>Zorunlu çerezler</strong><p class="small muted">Oturum, güvenlik ve ödeme işlemleri için gereklidir.</p></div><label class="switch"><input type="checkbox" checked disabled><span></span></label></div>
  <div class="cp-row"><div><strong>Tercih çerezleri</strong><p class="small muted">Görünüm ve dil tercihlerinizi hatırlar.</p></div><label class="switch"><input type="checkbox" data-cat="p"><span></span></label></div>
  <div class="cp-row"><div><strong>Analitik çerezler</strong><p class="small muted">Siteyi nasıl kullandığınızı anonim olarak ölçer.</p></div><label class="switch"><input type="checkbox" data-cat="a"><span></span></label></div>
  <div class="cp-row"><div><strong>Pazarlama çerezleri</strong><p class="small muted">İlgi alanlarınıza uygun içerik göstermek için kullanılır.</p></div><label class="switch"><input type="checkbox" data-cat="m"><span></span></label></div>
  <div class="cp-actions">
    <button class="btn btn-ghost" data-cookie="reject">Tümünü reddet</button>
    <button class="btn btn-dark" data-cookie="save">Seçimleri kaydet</button>
    <button class="btn btn-primary" data-cookie="accept">Tümünü kabul et</button>
  </div>
  <p class="small muted" id="cookieSaved" hidden>✓ Tercihleriniz kaydedildi.</p>
</div>
<?php
    });
}
