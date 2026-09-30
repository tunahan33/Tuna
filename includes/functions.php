<?php
/** Genel yardımcı fonksiyonlar */

function config(string $key)
{
    $c = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $k) {
        if (!is_array($c) || !array_key_exists($k, $c)) {
            return null;
        }
        $c = $c[$k];
    }
    return $c;
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = file_exists($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $to): never
{
    if (!preg_match('#^https?://#', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, 303);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function money($amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' ₺';
}

function tr_date(?string $dt, bool $time = true): string
{
    if (!$dt) {
        return '-';
    }
    $ts = strtotime($dt);
    return date($time ? 'd.m.Y H:i' : 'd.m.Y', $ts);
}

function tr_day_name(string $date): string
{
    $days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    return $days[(int) date('w', strtotime($date))];
}

function slugify(string $s): string
{
    $s = str_replace(['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'i', 'g', 'g', 'u', 'u', 's', 's', 'o', 'o', 'c', 'c'], $s);
    $s = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $s));
    return trim($s, '-');
}

/* ---------- Ayarlar ---------- */

function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT skey, svalue FROM settings') as $r) {
                $cache[$r['skey']] = $r['svalue'];
            }
        } catch (Throwable $e) {
        }
    }
    return $cache[$key] ?? $default;
}

function save_setting(string $key, string $value): void
{
    if (row('SELECT skey FROM settings WHERE skey = ?', [$key])) {
        q('UPDATE settings SET svalue = ? WHERE skey = ?', [$value, $key]);
    } else {
        q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
    }
}

/** Sayfa içeriklerindeki {{degisken}} yer tutucularını firma bilgileriyle doldurur */
function fill_placeholders(string $html, array $extra = []): string
{
    $map = [
        'firma_unvan'   => setting('company_title'),
        'firma_adi'     => setting('site_name', 'GS Projeler'),
        'firma_adres'   => setting('company_address'),
        'firma_telefon' => setting('company_phone'),
        'firma_eposta'  => setting('company_email'),
        'vergi_dairesi' => setting('tax_office'),
        'vergi_no'      => setting('tax_number'),
        'mersis_no'     => setting('mersis_number'),
        'kep_adresi'    => setting('kep_address'),
        'site_adresi'   => config('base_url'),
        'bugun'         => date('d.m.Y'),
    ] + $extra;
    return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function ($m) use ($map) {
        return array_key_exists($m[1], $map) ? e($map[$m[1]]) : $m[0];
    }, $html);
}

/* ---------- Flash mesajları ---------- */

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flashes(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $out .= '<div class="alert alert-' . e($type) . '">' . e($msg) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['_token'] ?? '';
        if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
            http_response_code(419);
            exit('Oturum süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.');
        }
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

/* ---------- Aktivite kaydı (süper admin akışı) ---------- */

function log_activity(string $action, string $details = '', ?string $entity = null, ?int $entityId = null, ?array $actor = null): void
{
    $u = $actor ?? current_user();
    try {
        insert('activity_log', [
            'user_id'    => $u['id'] ?? null,
            'user_name'  => $u['name'] ?? 'Ziyaretçi',
            'user_role'  => $u['role'] ?? 'guest',
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'details'    => mb_substr($details, 0, 1000),
            'ip'         => client_ip(),
            'created_at' => now(),
        ]);
    } catch (Throwable $e) {
    }
}

/* ---------- Siparişler ---------- */

const ORDER_STATUSES = [
    'pending'    => ['Ödeme Bekliyor', 'gray'],
    'paid'       => ['Ödendi', 'green'],
    'processing' => ['Hizmet Sürüyor', 'blue'],
    'completed'  => ['Tamamlandı', 'dark'],
    'failed'     => ['Ödeme Başarısız', 'red'],
    'cancelled'  => ['İptal Edildi', 'red'],
    'refunded'   => ['İade Edildi', 'yellow'],
];

/** Satış sayılan durumlar */
const SALE_STATUSES = ['paid', 'processing', 'completed'];

function status_badge(string $status): string
{
    [$label, $color] = ORDER_STATUSES[$status] ?? [$status, 'gray'];
    return '<span class="badge badge-' . $color . '">' . e($label) . '</span>';
}

function generate_order_no(): string
{
    return 'GSP' . date('ymdHis') . strtoupper(bin2hex(random_bytes(2)));
}

function order_access_token(array $order): string
{
    return substr(hash_hmac('sha256', $order['order_no'] . '|' . $order['id'], config('app_key')), 0, 32);
}

function features_list(?string $text): array
{
    return array_values(array_filter(array_map('trim', explode("\n", (string) $text))));
}

function service_icon(string $name): string
{
    $icons = [
        'performance' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z"/>',
        'nutrition'   => '<path d="M12 21c-4.4 0-8-3.6-8-8 0-3.3 2-6.2 5-7.4.6 2.4 2.1 3.4 3 3.4s2.4-1 3-3.4c3 1.2 5 4.1 5 7.4 0 4.4-3.6 8-8 8z"/><path d="M12 9V3"/>',
        'club'        => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3z"/><path d="m9 12 2 2 4-4"/>',
        'career'      => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
        'facility'    => '<path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M9 21v-6h6v6"/>',
        'mental'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
        'rehab'       => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.8-8.8a5.5 5.5 0 0 0 0-7.8z"/><path d="M8 12h2l1-2 2 4 1-2h2"/>',
        'default'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
    ];
    $p = $icons[$name] ?? $icons['default'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

const SERVICE_ICONS = ['performance' => 'Performans (şimşek)', 'nutrition' => 'Beslenme', 'club' => 'Kulüp (kalkan)', 'career' => 'Kariyer (çanta)', 'facility' => 'Tesis (bina)', 'mental' => 'Mental (hedef)', 'rehab' => 'Sağlık (kalp)', 'default' => 'Genel'];

/* ---------- E-posta ---------- */

function send_mail(string $to, string $subject, string $html, ?string &$error = null): bool
{
    $from = setting('smtp_from') ?: setting('company_email') ?: 'no-reply@' . (parse_url(config('base_url'), PHP_URL_HOST) ?: 'localhost');
    $name = setting('site_name', 'GS Projeler');
    $body = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;border-top:4px solid #C8102E">'
        . '<div style="background:#2B2F33;color:#fff;padding:16px 24px;font-weight:bold;font-size:18px">' . e($name) . '</div>'
        . '<div style="padding:24px;color:#2B2F33;line-height:1.6">' . $html . '</div>'
        . '<div style="padding:12px 24px;font-size:12px;color:#6B7178;background:#f4f5f6">' . e(setting('company_title')) . ' · ' . e(setting('company_phone')) . '</div></div>';

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz alıcı adresi.';
        return false;
    }
    try {
        if (setting('mail_driver') === 'smtp' && setting('smtp_host') !== '') {
            require_once __DIR__ . '/mailer.php';
            return smtp_send([
                'host' => setting('smtp_host'), 'port' => setting('smtp_port', '465'), 'secure' => setting('smtp_secure', 'ssl'),
                'user' => setting('smtp_user'), 'pass' => setting('smtp_pass'),
            ], $to, $subject, $body, $name, $from);
        }
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: =?UTF-8?B?' . base64_encode($name) . '?= <' . $from . '>',
            'Reply-To: ' . $from,
        ];
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
        if (!$ok) $error = 'PHP mail() fonksiyonu e-postayı gönderemedi. SMTP ayarlarını kullanın.';
        return $ok;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        return false;
    }
}

/** Panelden girilen HTML içeriği güvenli etiketlere indirger (XSS koruması) */
function sanitize_html(string $html): string
{
    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'blockquote', 'span', 'div'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) {
        return strip_tags($html, '<p><br><strong><em><ul><ol><li><h3><h4>');
    }
    $walk = function (DOMNode $node) use (&$walk, $allowed) {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button', 'svg', 'math'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                $walk($child);
                if (!in_array($tag, $allowed, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    $keep = ($tag === 'a' && in_array($name, ['href', 'target', 'rel'], true));
                    if ($keep && $name === 'href' && !preg_match('#^(https?:|mailto:|tel:|/|\#|\{\{)#i', trim($attr->value))) {
                        $keep = false;
                    }
                    if (!$keep) {
                        $child->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener');
                }
            } elseif ($child instanceof DOMComment) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return trim($out);
}

/** config.php dosyasını yeniden yazar (site adresi değişikliği vb.) */
function write_config(array $changes): bool
{
    $file = ROOT . '/config.php';
    if (!is_writable($file)) {
        return false;
    }
    $cfg = array_replace_recursive($GLOBALS['__config'], $changes);
    $php = "<?php\n// GS Projeler yapılandırması - son güncelleme: " . date('d.m.Y H:i') . "\nreturn " . var_export($cfg, true) . ";\n";
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        return false;
    }
    $GLOBALS['__config'] = $cfg;
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }
    return true;
}

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';
}
