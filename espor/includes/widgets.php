<?php
/**
 * Bazı sayfaların altında çalışan formlar (sayfa.php tarafından kullanılır).
 * Sayfa metinleri panelden düzenlenir; formlar burada tanımlıdır.
 *   siparis-takibi            Sipariş no + e-posta ile sipariş durumu
 *   ariza-takibi              Arıza kaydı oluşturma ve takip numarasıyla sorgulama
 *   ilgili-kisi-basvuru-formu KVKK m.11 başvurusu
 *   insan-kaynaklari          İş başvurusu
 *   cerez-tercihleri          İsteğe bağlı çerez izinleri (tarayıcıda saklanır)
 */

const MESSAGE_TYPES = [
    'iletisim' => 'İletişim',
    'ariza'    => 'Arıza Kaydı',
    'kvkk'     => 'KVKK Başvurusu',
    'ik'       => 'İş Başvurusu',
];

const ARIZA_CATEGORIES = [
    'ders'     => 'Ders bağlantısı / Discord sorunu',
    'ses'      => 'Ses veya görüntü sorunu',
    'dokuman'  => 'Analiz / doküman erişim sorunu',
    'odeme'    => 'Ödeme sayfası hatası',
    'uyelik'   => 'Üyelik / giriş sorunu',
    'diger'    => 'Diğer teknik sorun',
];

const KVKK_REQUESTS = [
    'Kişisel verilerimin işlenip işlenmediğini öğrenmek istiyorum.',
    'Kişisel verilerim işlenmişse buna ilişkin bilgi talep ediyorum.',
    'Kişisel verilerimin işlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenmek istiyorum.',
    'Kişisel verilerimin aktarıldığı üçüncü kişileri bilmek istiyorum.',
    'Eksik veya yanlış işlenen kişisel verilerimin düzeltilmesini istiyorum.',
    'Kişisel verilerimin silinmesini veya yok edilmesini istiyorum.',
    'Düzeltme/silme işlemlerinin aktarılan üçüncü kişilere bildirilmesini istiyorum.',
    'Otomatik sistemlerle analiz sonucu aleyhime çıkan sonuca itiraz ediyorum.',
    'Kanuna aykırı işleme nedeniyle uğradığım zararın giderilmesini talep ediyorum.',
];

const HR_POSITIONS = ['E-Spor Koçu', 'Mental Performans Koçu', 'Satış ve Müşteri Temsilcisi', 'İçerik Editörü', 'Genel Başvuru'];

function widget_slugs(): array
{
    return ['siparis-takibi', 'ariza-takibi', 'ilgili-kisi-basvuru-formu', 'insan-kaynaklari', 'cerez-tercihleri'];
}

function make_ticket_no(string $prefix): string
{
    return $prefix . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
}

/** Aynı IP'den kısa sürede çok fazla deneme yapılmasını engeller */
function too_many_attempts(string $action, int $max, int $seconds = 900): bool
{
    $since = date('Y-m-d H:i:s', time() - $seconds);
    return (int) val('SELECT COUNT(*) FROM activity_log WHERE action = ? AND ip = ? AND created_at >= ?', [$action, client_ip(), $since]) >= $max;
}

/** Form gönderimini işler; görünümde kullanılacak durumu döner (gerekirse yönlendirir) */
function widget_handle(string $slug): array
{
    $state = ['old' => [], 'result' => null, 'error' => null];
    if (!is_post() || !in_array($slug, widget_slugs(), true)) {
        return $state;
    }
    verify_csrf();
    if (input('website') !== '') { // bot tuzağı
        redirect('sayfa.php?s=' . $slug);
    }
    $back = 'sayfa.php?s=' . $slug;

    switch ($slug) {
        case 'siparis-takibi':
            $no = strtoupper(preg_replace('/\s+/', '', input('order_no')));
            $email = mb_strtolower(input('email'));
            $state['old'] = ['order_no' => $no, 'email' => $email];
            if (too_many_attempts('Sipariş sorgulama başarısız', 10)) {
                $state['error'] = 'Çok fazla deneme yapıldı. Lütfen 15 dakika sonra tekrar deneyin.';
                break;
            }
            $o = row('SELECT * FROM orders WHERE order_no = ? AND LOWER(customer_email) = ?', [$no, $email]);
            if (!$o) {
                log_activity('Sipariş sorgulama başarısız', $no);
                $state['error'] = 'Bu bilgilerle eşleşen bir sipariş bulunamadı. Sipariş numaranızı ve e-posta adresinizi kontrol edin.';
            } else {
                log_activity('Sipariş durumunu sorguladı', $o['order_no'], 'order', (int) $o['id']);
                $state['result'] = $o;
            }
            break;

        case 'ariza-takibi':
            if (input('action') === 'lookup') {
                $no = strtoupper(preg_replace('/\s+/', '', input('ticket_no')));
                $email = mb_strtolower(input('email'));
                $state['old'] = ['ticket_no' => $no, 'lookup_email' => $email, 'tab' => 'lookup'];
                if (too_many_attempts('Arıza sorgulama başarısız', 10)) {
                    $state['error'] = 'Çok fazla deneme yapıldı. Lütfen 15 dakika sonra tekrar deneyin.';
                    break;
                }
                $m = row("SELECT * FROM messages WHERE type = 'ariza' AND ticket_no = ? AND LOWER(email) = ?", [$no, $email]);
                if (!$m) {
                    log_activity('Arıza sorgulama başarısız', $no);
                    $state['error'] = 'Bu bilgilerle eşleşen bir arıza kaydı bulunamadı.';
                } else {
                    $state['result'] = $m;
                }
                break;
            }
            $old = ['name' => input('name'), 'email' => input('email'), 'phone' => input('phone'), 'order_no' => strtoupper(input('order_no')), 'category' => input('category'), 'message' => input('message')];
            $state['old'] = $old;
            if (mb_strlen($old['name']) < 3 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['message']) < 10 || !isset(ARIZA_CATEGORIES[$old['category']])) {
                $state['error'] = 'Lütfen ad soyad, geçerli e-posta, sorun türü ve en az 10 karakterlik açıklama girin.';
            } elseif (!input('kvkk')) {
                $state['error'] = 'Devam etmek için Genel Aydınlatma Metni\'ni onaylamanız gerekir.';
            } else {
                $ticket = make_ticket_no('ARZ');
                $body = ($old['order_no'] ? 'Sipariş No: ' . $old['order_no'] . "\n" : '') . 'Sorun türü: ' . ARIZA_CATEGORIES[$old['category']] . "\n\n" . $old['message'];
                $id = insert('messages', [
                    'type' => 'ariza', 'ticket_no' => $ticket, 'name' => mb_substr($old['name'], 0, 120), 'email' => mb_substr($old['email'], 0, 190),
                    'phone' => mb_substr($old['phone'], 0, 30), 'subject' => ARIZA_CATEGORIES[$old['category']],
                    'message' => mb_substr($body, 0, 5000), 'status' => 'new', 'ip' => client_ip(), 'created_at' => now(),
                ]);
                log_activity('Arıza kaydı oluşturuldu', $ticket . ' · ' . ARIZA_CATEGORIES[$old['category']], 'message', $id);
                send_mail(setting('notify_email'), 'Yeni arıza kaydı: ' . $ticket, '<p><b>' . e($old['name']) . '</b> (' . e($old['email']) . ')</p><p>' . nl2br(e($body)) . '</p>');
                send_mail($old['email'], 'Arıza kaydınız alındı: ' . $ticket, '<p>Merhaba ' . e($old['name']) . ',</p><p>Arıza kaydınız oluşturuldu. Takip numaranız: <b>' . e($ticket) . '</b></p><p>Kaydınızın durumunu <a href="' . e(url('sayfa.php?s=ariza-takibi')) . '">Arıza Takibi</a> sayfasından sorgulayabilirsiniz.</p>');
                flash('success', 'Arıza kaydınız oluşturuldu. Takip numaranız: ' . $ticket . ' — Bu numara ile kaydınızın durumunu sorgulayabilirsiniz.');
                redirect($back);
            }
            break;

        case 'ilgili-kisi-basvuru-formu':
            $old = ['name' => input('name'), 'tckn' => preg_replace('/\D/', '', input('tckn')), 'email' => input('email'), 'phone' => input('phone'),
                'address' => input('address'), 'relation' => input('relation'), 'requests' => (array) ($_POST['requests'] ?? []), 'message' => input('message'), 'reply_by' => input('reply_by', 'eposta')];
            $state['old'] = $old;
            $reqs = array_values(array_intersect_key(KVKK_REQUESTS, array_flip(array_map('intval', $old['requests']))));
            if (mb_strlen($old['name']) < 3 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['address']) < 10) {
                $state['error'] = 'Lütfen ad soyad, geçerli e-posta ve tebligat adresinizi eksiksiz girin.';
            } elseif (!tckn_valid($old['tckn'])) {
                $state['error'] = 'Lütfen geçerli bir T.C. kimlik numarası girin (yabancı uyruklular için pasaport no açıklama alanına yazılabilir, bu alana 11111111110 girilebilir).';
            } elseif (!$reqs && mb_strlen($old['message']) < 10) {
                $state['error'] = 'Lütfen en az bir talep seçin veya talebinizi açıklayın.';
            } elseif (!input('confirm')) {
                $state['error'] = 'Başvurunun doğruluğunu onaylamanız gerekir.';
            } else {
                $ticket = make_ticket_no('KVK');
                $body = 'T.C. Kimlik No: ' . substr($old['tckn'], 0, 3) . '*****' . substr($old['tckn'], -3) . "\n"
                    . 'Şirketle ilişkisi: ' . $old['relation'] . "\nAdres: " . $old['address'] . "\nYanıt yöntemi: " . ($old['reply_by'] === 'posta' ? 'Posta ile adrese' : 'E-posta ile')
                    . "\n\nTalepler:\n- " . implode("\n- ", $reqs ?: ['(Açıklamaya bakınız)']) . "\n\nAçıklama:\n" . $old['message'];
                $id = insert('messages', [
                    'type' => 'kvkk', 'ticket_no' => $ticket, 'name' => mb_substr($old['name'], 0, 120), 'email' => mb_substr($old['email'], 0, 190),
                    'phone' => mb_substr($old['phone'], 0, 30), 'subject' => 'KVKK İlgili Kişi Başvurusu',
                    'message' => mb_substr($body, 0, 5000), 'status' => 'new', 'ip' => client_ip(), 'created_at' => now(),
                ]);
                log_activity('KVKK başvurusu alındı', $ticket . ' · ' . $old['name'], 'message', $id);
                send_mail(setting('notify_email'), 'Yeni KVKK başvurusu: ' . $ticket, '<p><b>' . e($old['name']) . '</b> (' . e($old['email']) . ')</p><p>' . nl2br(e($body)) . '</p><p>Yasal yanıt süresi: 30 gün.</p>');
                send_mail($old['email'], 'KVKK başvurunuz alındı: ' . $ticket, '<p>Sayın ' . e($old['name']) . ',</p><p>6698 sayılı Kanun kapsamındaki başvurunuz <b>' . e($ticket) . '</b> numarasıyla kayda alınmıştır. Başvurunuz en geç 30 gün içinde sonuçlandırılacaktır.</p>');
                flash('success', 'Başvurunuz ' . $ticket . ' numarasıyla alındı. En geç 30 gün içinde tarafınıza yanıt verilecektir.');
                redirect($back);
            }
            break;

        case 'insan-kaynaklari':
            $old = ['name' => input('name'), 'email' => input('email'), 'phone' => input('phone'), 'position' => input('position'),
                'games' => input('games'), 'link' => input('link'), 'message' => input('message')];
            $state['old'] = $old;
            if (mb_strlen($old['name']) < 3 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['phone']) < 10 || !in_array($old['position'], HR_POSITIONS, true)) {
                $state['error'] = 'Lütfen ad soyad, geçerli e-posta, telefon ve pozisyon bilgilerini girin.';
            } elseif (mb_strlen($old['message']) < 30) {
                $state['error'] = 'Lütfen kendinizi ve deneyiminizi en az 30 karakterle anlatın.';
            } elseif ($old['link'] !== '' && !filter_var($old['link'], FILTER_VALIDATE_URL)) {
                $state['error'] = 'Özgeçmiş / portfolyo bağlantısı geçerli bir adres olmalıdır (https:// ile başlamalı).';
            } elseif (!input('kvkk')) {
                $state['error'] = 'Devam etmek için Genel Aydınlatma Metni\'ni onaylamanız gerekir.';
            } else {
                $body = 'Pozisyon: ' . $old['position'] . "\nOyun / rank / deneyim: " . $old['games'] . "\nÖzgeçmiş / portfolyo: " . ($old['link'] ?: '-') . "\n\n" . $old['message'];
                $id = insert('messages', [
                    'type' => 'ik', 'ticket_no' => make_ticket_no('IK'), 'name' => mb_substr($old['name'], 0, 120), 'email' => mb_substr($old['email'], 0, 190),
                    'phone' => mb_substr($old['phone'], 0, 30), 'subject' => 'İş başvurusu: ' . $old['position'],
                    'message' => mb_substr($body, 0, 5000), 'status' => 'new', 'ip' => client_ip(), 'created_at' => now(),
                ]);
                log_activity('İş başvurusu alındı', $old['name'] . ' · ' . $old['position'], 'message', $id);
                send_mail(setting('notify_email'), 'Yeni iş başvurusu: ' . $old['position'], '<p><b>' . e($old['name']) . '</b> (' . e($old['email']) . ', ' . e($old['phone']) . ')</p><p>' . nl2br(e($body)) . '</p>');
                flash('success', 'Başvurunuz alındı. Profiliniz uygun bulunursa en geç 15 iş günü içinde sizinle iletişime geçeceğiz.');
                redirect($back);
            }
            break;
    }
    return $state;
}

function tckn_valid(string $t): bool
{
    if (!preg_match('/^[1-9]\d{10}$/', $t)) {
        return false;
    }
    $d = array_map('intval', str_split($t));
    $d10 = ((($d[0] + $d[2] + $d[4] + $d[6] + $d[8]) * 7) - ($d[1] + $d[3] + $d[5] + $d[7])) % 10;
    if ($d10 < 0) {
        $d10 += 10;
    }
    return $d10 === $d[9] && (array_sum(array_slice($d, 0, 10)) % 10) === $d[10];
}

/** Sipariş durum adımları */
function order_steps(string $status): string
{
    $steps = ['pending' => 'Sipariş alındı', 'paid' => 'Ödeme onaylandı', 'processing' => 'Koçluk sürüyor', 'completed' => 'Tamamlandı'];
    $order = array_keys($steps);
    if (!in_array($status, $order, true)) {
        return '';
    }
    $cur = array_search($status, $order, true);
    $out = '<ol class="track-steps">';
    foreach ($order as $i => $k) {
        $out .= '<li class="' . ($i < $cur ? 'done' : ($i === $cur ? 'active' : '')) . '">' . e($steps[$k]) . '</li>';
    }
    return $out . '</ol>';
}

function widget_render(string $slug, array $state): void
{
    if (!in_array($slug, widget_slugs(), true)) {
        return;
    }
    $o = $state['old'];
    $v = fn($k, $d = '') => e($o[$k] ?? $d);
    $err = $state['error'] ? '<div class="alert alert-error">' . e($state['error']) . '</div>' : '';
    $kvkk = '<label class="check"><input type="checkbox" name="kvkk" value="1" required> <span><a href="' . url('sayfa.php?s=genel-aydinlatma-metni') . '" target="_blank">Genel Aydınlatma Metni</a>\'ni okudum, kişisel verilerimin talebimin yanıtlanması amacıyla işlenmesini kabul ediyorum.</span></label>';
    $hp = '<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">';

    echo '<div class="card widget mt-2" id="form">';
    switch ($slug) {
        case 'siparis-takibi':
            echo '<h3>Sipariş Sorgula</h3>' . $err; ?>
            <form method="post" action="#form" class="form">
                <?= csrf_field() . $hp ?>
                <div class="grid-2">
                    <label>Sipariş Numarası *<input name="order_no" value="<?= $v('order_no') ?>" placeholder="GSE..." required autocomplete="off"></label>
                    <label>E-posta Adresi *<input type="email" name="email" value="<?= $v('email') ?>" required></label>
                </div>
                <button class="btn btn-primary">Siparişi Sorgula</button>
            </form>
            <?php if ($r = $state['result']): ?>
                <div class="track-result">
                    <div class="track-head"><div><small>Sipariş No</small><strong><?= e($r['order_no']) ?></strong></div><?= status_badge($r['status']) ?></div>
                    <?= order_steps($r['status']) ?>
                    <dl class="track-dl">
                        <dt>Koçluk</dt><dd><?= e($r['service_title']) ?> · <?= e($r['package_name']) ?></dd>
                        <dt>Tutar</dt><dd><?= money($r['amount']) ?> (KDV dahil)</dd>
                        <dt>Sipariş Tarihi</dt><dd><?= tr_date($r['created_at']) ?></dd>
                        <?php if ($r['paid_at']): ?><dt>Ödeme Tarihi</dt><dd><?= tr_date($r['paid_at']) ?></dd><?php endif; ?>
                        <dt>Son Güncelleme</dt><dd><?= tr_date($r['updated_at']) ?></dd>
                    </dl>
                    <?php if ($r['status'] === 'pending' || $r['status'] === 'failed'): ?>
                        <p class="small">Ödemeniz tamamlanmamış görünüyor. <a href="<?= url('odeme.php?paket=' . (int) $r['package_id']) ?>">Yeniden satın almak için tıklayın</a>.</p>
                    <?php elseif (in_array($r['status'], ['paid', 'processing'], true)): ?>
                        <p class="small">Koçunuz sizinle iletişime geçmediyse lütfen <a href="<?= url('iletisim.php?konu=' . urlencode('Sipariş ' . $r['order_no'])) ?>">bize yazın</a>.</p>
                    <?php endif; ?>
                </div>
            <?php endif;
            break;

        case 'ariza-takibi':
            $tab = ($o['tab'] ?? '') === 'lookup' ? 'lookup' : 'new'; ?>
            <div data-tabs>
                <div class="tabs"><button type="button" class="tab <?= $tab === 'new' ? 'active' : '' ?>" data-tab="new">Yeni Arıza Kaydı</button><button type="button" class="tab <?= $tab === 'lookup' ? 'active' : '' ?>" data-tab="lookup">Kayıt Sorgula</button></div>
                <?= $err ?>
                <div data-pane="new" <?= $tab === 'new' ? '' : 'hidden' ?>>
                    <form method="post" action="#form" class="form">
                        <?= csrf_field() . $hp ?><input type="hidden" name="action" value="create">
                        <div class="grid-2">
                            <label>Ad Soyad *<input name="name" value="<?= $v('name') ?>" required></label>
                            <label>E-posta *<input type="email" name="email" value="<?= $v('email') ?>" required></label>
                            <label>Telefon<input name="phone" value="<?= $v('phone') ?>"></label>
                            <label>Sipariş No <small>(varsa)</small><input name="order_no" value="<?= $v('order_no') ?>"></label>
                        </div>
                        <label>Sorun Türü *<select name="category" required><option value="">Seçin</option><?php foreach (ARIZA_CATEGORIES as $k => $l): ?><option value="<?= $k ?>" <?= ($o['category'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
                        <label>Sorunun Açıklaması *<textarea name="message" rows="5" required placeholder="Sorunu ne zaman, hangi cihaz/uygulamada yaşadığınızı ve aldığınız hata mesajını yazın."><?= $v('message') ?></textarea></label>
                        <?= $kvkk ?>
                        <button class="btn btn-primary">Arıza Kaydı Oluştur</button>
                    </form>
                </div>
                <div data-pane="lookup" <?= $tab === 'lookup' ? '' : 'hidden' ?>>
                    <form method="post" action="#form" class="form">
                        <?= csrf_field() . $hp ?><input type="hidden" name="action" value="lookup">
                        <div class="grid-2">
                            <label>Takip Numarası *<input name="ticket_no" value="<?= $v('ticket_no') ?>" placeholder="ARZ..." required></label>
                            <label>E-posta *<input type="email" name="email" value="<?= $v('lookup_email') ?>" required></label>
                        </div>
                        <button class="btn btn-primary">Kaydı Sorgula</button>
                    </form>
                    <?php if ($r = $state['result']): $st = message_statuses()[$r['status']] ?? ['-', 'gray']; ?>
                        <div class="track-result">
                            <div class="track-head"><div><small>Takip No</small><strong><?= e($r['ticket_no']) ?></strong></div><span class="badge badge-<?= $st[1] ?>"><?= e($st[0]) ?></span></div>
                            <dl class="track-dl"><dt>Konu</dt><dd><?= e($r['subject']) ?></dd><dt>Oluşturulma</dt><dd><?= tr_date($r['created_at']) ?></dd></dl>
                            <?php if ($r['reply']): ?><div class="reply-box"><strong>Ekibimizin yanıtı</strong> <small class="muted"><?= tr_date($r['replied_at']) ?></small><p><?= nl2br(e($r['reply'])) ?></p></div>
                            <?php else: ?><p class="small muted">Kaydınız inceleniyor. Yanıt verildiğinde burada görünecek ve e-posta ile bildirilecektir.</p><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php break;

        case 'ilgili-kisi-basvuru-formu':
            echo '<h3>Başvuru Formu</h3>' . $err; ?>
            <form method="post" action="#form" class="form">
                <?= csrf_field() . $hp ?>
                <h4>1. Başvuru Sahibi Bilgileri</h4>
                <div class="grid-2">
                    <label>Ad Soyad *<input name="name" value="<?= $v('name') ?>" required></label>
                    <label>T.C. Kimlik No *<input name="tckn" value="<?= $v('tckn') ?>" inputmode="numeric" maxlength="11" required></label>
                    <label>E-posta *<input type="email" name="email" value="<?= $v('email') ?>" required></label>
                    <label>Telefon<input name="phone" value="<?= $v('phone') ?>"></label>
                </div>
                <label>Tebligat Adresi *<textarea name="address" rows="2" required><?= $v('address') ?></textarea></label>
                <h4>2. Şirketimizle İlişkiniz</h4>
                <div class="radio-row">
                    <?php foreach (['Müşteri', 'Üye', 'Site ziyaretçisi', 'Çalışan adayı', 'Eski çalışan', 'Diğer'] as $rel): ?>
                        <label class="check"><input type="radio" name="relation" value="<?= e($rel) ?>" <?= ($o['relation'] ?? 'Müşteri') === $rel ? 'checked' : '' ?>> <span><?= e($rel) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <h4>3. Talebiniz (KVKK m.11)</h4>
                <div class="check-col">
                    <?php foreach (KVKK_REQUESTS as $i => $req): ?>
                        <label class="check"><input type="checkbox" name="requests[]" value="<?= $i ?>" <?= in_array((string) $i, array_map('strval', $o['requests'] ?? []), true) ? 'checked' : '' ?>> <span><?= e($req) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <label>Talebinizin Açıklaması<textarea name="message" rows="4"><?= $v('message') ?></textarea></label>
                <h4>4. Yanıt Yöntemi</h4>
                <div class="radio-row">
                    <label class="check"><input type="radio" name="reply_by" value="eposta" <?= ($o['reply_by'] ?? 'eposta') === 'eposta' ? 'checked' : '' ?>> <span>E-posta adresime gönderilsin</span></label>
                    <label class="check"><input type="radio" name="reply_by" value="posta" <?= ($o['reply_by'] ?? '') === 'posta' ? 'checked' : '' ?>> <span>Adresime posta ile gönderilsin</span></label>
                </div>
                <label class="check"><input type="checkbox" name="confirm" value="1" required> <span>Yukarıda verdiğim bilgilerin doğru olduğunu, başvurunun tarafıma ait olduğunu ve kimliğimin doğrulanması amacıyla ek bilgi istenebileceğini kabul ediyorum.</span></label>
                <button class="btn btn-primary">Başvuruyu Gönder</button>
            </form>
            <?php break;

        case 'insan-kaynaklari':
            echo '<h3>Başvuru Formu</h3>' . $err; ?>
            <form method="post" action="#form" class="form">
                <?= csrf_field() . $hp ?>
                <div class="grid-2">
                    <label>Ad Soyad *<input name="name" value="<?= $v('name') ?>" required></label>
                    <label>E-posta *<input type="email" name="email" value="<?= $v('email') ?>" required></label>
                    <label>Telefon *<input name="phone" value="<?= $v('phone') ?>" required></label>
                    <label>Pozisyon *<select name="position" required><option value="">Seçin</option><?php foreach (HR_POSITIONS as $p): ?><option <?= ($o['position'] ?? '') === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select></label>
                </div>
                <label>Oyun, rank ve deneyim <small>(örn. Valorant Ölümsüz 3, 2 yıl amatör takım koçluğu)</small><input name="games" value="<?= $v('games') ?>"></label>
                <label>Özgeçmiş / LinkedIn / portfolyo bağlantısı<input type="url" name="link" value="<?= $v('link') ?>" placeholder="https://"></label>
                <label>Kendinizden bahsedin *<textarea name="message" rows="5" required><?= $v('message') ?></textarea></label>
                <?= $kvkk ?>
                <button class="btn btn-primary">Başvur</button>
            </form>
            <?php break;

        case 'cerez-tercihleri': ?>
            <div data-cookie-prefs>
                <h3>Çerez Tercihlerim</h3>
                <div class="pref-row"><div><strong>Zorunlu Çerezler</strong><p class="small muted">Oturum, güvenlik ve ödeme adımlarının çalışması için gereklidir. Kapatılamaz.</p></div><label class="switch"><input type="checkbox" checked disabled><span></span></label></div>
                <div class="pref-row"><div><strong>Analitik Çerezler</strong><p class="small muted">Sitenin nasıl kullanıldığını anonim olarak ölçerek hizmetimizi geliştirmemize yardımcı olur.</p></div><label class="switch"><input type="checkbox" data-pref="analytics"><span></span></label></div>
                <div class="pref-row"><div><strong>Pazarlama Çerezleri</strong><p class="small muted">İlgi alanlarınıza uygun kampanya ve duyuruların gösterilmesini sağlar.</p></div><label class="switch"><input type="checkbox" data-pref="marketing"><span></span></label></div>
                <div class="pref-actions">
                    <button type="button" class="btn btn-outline" data-pref-none>Tümünü Reddet</button>
                    <button type="button" class="btn btn-dark" data-pref-save>Seçimleri Kaydet</button>
                    <button type="button" class="btn btn-primary" data-pref-all>Tümünü Kabul Et</button>
                </div>
                <div class="alert alert-success mt-1" data-pref-msg hidden></div>
            </div>
            <?php break;
    }
    echo '</div>';
}

function message_statuses(): array
{
    return [
        'new'      => ['Yeni', 'red'],
        'read'     => ['İnceleniyor', 'gray'],
        'progress' => ['İşlemde', 'yellow'],
        'replied'  => ['Yanıtlandı / Çözüldü', 'green'],
    ];
}
