<?php
/** Genel yardımcı fonksiyonlar */

/** Sitenin kök adresi (alt klasöre kurulsa bile çalışır) */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim(preg_replace('#/admin$#', '', $dir), '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . $path;
    return url('assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . (preg_match('#^https?://#', $to) ? $to : url($to)));
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function money($n): string
{
    return number_format((float) $n, 2, ',', '.') . ' ₺';
}

const TR_MONTHS = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
const TR_DAYS = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];

function tr_date(?string $dt, bool $time = true): string
{
    if (!$dt) {
        return '-';
    }
    $t = strtotime($dt);
    return date('j', $t) . ' ' . TR_MONTHS[(int) date('n', $t)] . ' ' . date('Y', $t) . ($time ? ' ' . date('H:i', $t) : '');
}

function tr_day(string $date): string
{
    return TR_DAYS[(int) date('w', strtotime($date))];
}

function slugify(string $s): string
{
    $s = strtr(mb_strtolower(strtr($s, ['İ' => 'i', 'I' => 'ı'])), ['i̇' => 'i', 'ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'û' => 'u']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'urun';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'cli';
}

/* ---------- Ayarlar ---------- */

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (rows('SELECT skey, svalue FROM settings') as $r) {
            $cache[$r['skey']] = (string) $r['svalue'];
        }
    }
    return $cache[$key] ?? $default;
}

function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON CONFLICT(skey) DO UPDATE SET svalue = excluded.svalue', [$key, $value]);
}

/* ---------- Bildirim mesajları ---------- */

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

/* ---------- Form güvenliği (CSRF) ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Oturum süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}

/* ---------- Aktivite akışı ---------- */

/** Kim, ne zaman, ne yaptı. Süper admin "Aktivite Akışı" sayfasında görür. */
function log_activity(string $action, string $details = '', string $link = '', ?array $actor = null): void
{
    $u = $actor ?? current_user();
    insert('activity', [
        'user_id'   => $u['id'] ?? null,
        'user_name' => $u['name'] ?? 'Ziyaretçi',
        'role'      => $u['role'] ?? 'ziyaretci',
        'action'    => $action,
        'details'   => mb_substr($details, 0, 500),
        'link'      => $link,
        'ip'        => client_ip(),
        'created_at' => now(),
    ]);
}

/* ---------- Sayfalama ---------- */

function paginate(int $total, int $per = 25): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page = min($pages, max(1, (int) input('sayfa', 1)));
    return [$page, $pages, ($page - 1) * $per, $per];
}

function pager(int $page, int $pages): string
{
    if ($pages < 2) {
        return '';
    }
    $qs = $_GET;
    $out = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs['sayfa'] = $i;
        $out .= '<a class="' . ($i === $page ? 'active' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}

/** Firma bilgilerini sözleşme metinlerine yerleştirir: {{firma_unvan}} vb. */
function fill_placeholders(string $html, array $extra = []): string
{
    $map = [
        'site_adi'      => setting('site_name'),
        'firma_unvan'   => setting('company_title'),
        'firma_adres'   => setting('company_address'),
        'firma_telefon' => setting('company_phone'),
        'firma_eposta'  => setting('company_email'),
        'vergi_dairesi' => setting('tax_office'),
        'vergi_no'      => setting('tax_number'),
        'mersis_no'     => setting('mersis_number'),
        'kep_adresi'    => setting('kep_address'),
        'kargo_suresi'  => setting('shipping_days'),
        'kargo_ucreti'  => money(setting('shipping_fee')),
        'ucretsiz_kargo' => money(setting('free_shipping_limit')),
    ] + $extra;
    return preg_replace_callback('/\{\{\s*([a-z_0-9]+)\s*\}\}/', fn($m) => isset($map[$m[1]]) ? e($map[$m[1]]) : $m[0], $html);
}

/** Panelde yazılan sayfa içeriğinden tehlikeli etiketleri temizler */
function clean_html(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('#<(script|iframe|object|embed)[^>]*/?>#i', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    return preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*/i', '$1=$2#', $html);
}
