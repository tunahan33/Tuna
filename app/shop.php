<?php
/** Mağaza: ürün görselleri, sepet, sipariş ve talep yardımcıları */

const ORDER_STATUSES = [
    'yeni'        => ['Yeni Sipariş', 'yellow'],
    'hazirlaniyor' => ['Hazırlanıyor', 'blue'],
    'kargoda'     => ['Kargoya Verildi', 'purple'],
    'teslim'      => ['Teslim Edildi', 'green'],
    'iptal'       => ['İptal Edildi', 'gray'],
    'iade'        => ['İade Edildi', 'red'],
];

/** Satış sayılan durumlar (raporlarda ciro hesabına girer) */
const SALE_STATUSES = ['yeni', 'hazirlaniyor', 'kargoda', 'teslim'];

const REQUEST_TYPES = [
    'iletisim' => ['İletişim Mesajı', 'MSJ'],
    'ariza'    => ['Arıza Kaydı', 'ARZ'],
    'kvkk'     => ['KVKK Başvurusu', 'KVK'],
    'ik'       => ['İş Başvurusu', 'IK'],
];

const REQUEST_STATUSES = [
    'yeni'      => ['Yeni', 'yellow'],
    'inceleniyor' => ['İnceleniyor', 'blue'],
    'serviste'  => ['Teknik Serviste', 'purple'],
    'cozuldu'   => ['Çözüldü / Yanıtlandı', 'green'],
    'kapandi'   => ['Kapandı', 'gray'],
];

const CARGO_COMPANIES = ['Yurtiçi Kargo', 'Aras Kargo', 'MNG Kargo', 'PTT Kargo', 'Sürat Kargo'];

function badge(string $label, string $color): string
{
    return '<span class="badge badge-' . e($color) . '">' . e($label) . '</span>';
}

function order_badge(string $status): string
{
    [$l, $c] = ORDER_STATUSES[$status] ?? [$status, 'gray'];
    return badge($l, $c);
}

function request_badge(string $status): string
{
    [$l, $c] = REQUEST_STATUSES[$status] ?? [$status, 'gray'];
    return badge($l, $c);
}

/* ---------- Ürün görseli (fotoğraf yerine marka renklerinde çizim) ---------- */

const PRODUCT_ARTS = [
    'forma'    => 'Forma',
    'esofman'  => 'Eşofman / Ceket',
    'kapuson'  => 'Sweatshirt / Hoodie',
    'mont'     => 'Mont / Yağmurluk',
    'sort'     => 'Şort / Tayt',
    'ayakkabi' => 'Ayakkabı / Krampon',
    'top'      => 'Top',
    'canta'    => 'Çanta',
];

function product_art(array $p, string $class = ''): string
{
    $c = preg_match('/^#[0-9a-fA-F]{6}$/', $p['color'] ?? '') ? $p['color'] : '#C8102E';
    $y = '#FDB913';
    $d = '#2B2F33';
    $shapes = [
        'forma'    => "<path d='M70 40 L50 48 L28 78 L46 92 L58 80 L58 165 L142 165 L142 80 L154 92 L172 78 L150 48 L130 40 Q100 62 70 40Z' fill='$c'/><path d='M70 40 Q100 62 130 40 L124 38 Q100 54 76 38Z' fill='$d'/><path d='M58 110 L142 95 L142 108 L58 123Z' fill='$y'/><text x='100' y='150' font-size='26' font-weight='800' text-anchor='middle' fill='#fff' font-family='Arial'>GS</text>",
        'esofman'  => "<path d='M72 36 L48 46 L30 120 L48 124 L60 78 L60 160 L140 160 L140 78 L152 124 L170 120 L152 46 L128 36 L112 44 L88 44Z' fill='$c'/><rect x='98' y='44' width='4' height='116' fill='$d'/><path d='M48 46 L36 118 L42 119 L54 50Z M152 46 L164 118 L158 119 L146 50Z' fill='$y'/><rect x='60' y='150' width='80' height='10' fill='$d'/>",
        'kapuson'  => "<path d='M80 30 Q100 18 120 30 L134 44 L156 52 L172 122 L154 126 L142 84 L142 162 L58 162 L58 84 L46 126 L28 122 L44 52 L66 44Z' fill='$c'/><path d='M80 30 Q100 60 120 30 Q112 52 100 54 Q88 52 80 30Z' fill='$d'/><rect x='78' y='118' width='44' height='24' rx='6' fill='$d' opacity='.35'/><path d='M58 96 H142 V104 H58Z' fill='$y'/>",
        'mont'     => "<path d='M74 32 L46 44 L28 128 L48 132 L60 86 L60 166 L140 166 L140 86 L152 132 L172 128 L154 44 L126 32 L100 40Z' fill='$c'/><path d='M74 32 L100 40 L126 32 L120 24 L100 30 L80 24Z' fill='$d'/><rect x='97' y='40' width='6' height='126' fill='$d'/><path d='M60 100 H97 M103 100 H140' stroke='$y' stroke-width='6'/><rect x='68' y='116' width='20' height='4' fill='$d'/><rect x='112' y='116' width='20' height='4' fill='$d'/>",
        'sort'     => "<path d='M50 48 H150 L160 150 L110 156 L100 96 L90 156 L40 150Z' fill='$c'/><rect x='50' y='48' width='100' height='14' fill='$d'/><path d='M44 120 L54 150 L48 150 Z M156 120 L146 150 L152 150Z' fill='$y'/><path d='M138 70 L152 140' stroke='$y' stroke-width='6'/>",
        'ayakkabi' => "<path d='M28 128 Q30 92 56 86 L92 80 Q104 64 118 70 L150 108 Q176 114 176 132 L176 142 L28 142Z' fill='$c'/><path d='M28 136 H176 V148 Q100 156 28 148Z' fill='$d'/><path d='M70 110 Q100 96 130 108' stroke='$y' stroke-width='7' fill='none'/><circle cx='46' cy='154' r='4' fill='$d'/><circle cx='80' cy='155' r='4' fill='$d'/><circle cx='130' cy='155' r='4' fill='$d'/><circle cx='162' cy='154' r='4' fill='$d'/>",
        'top'      => "<circle cx='100' cy='100' r='62' fill='#fff' stroke='$d' stroke-width='4'/><path d='M100 74 L124 92 L115 120 L85 120 L76 92Z' fill='$c'/><path d='M100 38 L100 74 M124 92 L156 80 M115 120 L134 150 M85 120 L66 150 M76 92 L44 80' stroke='$d' stroke-width='3'/><path d='M60 60 Q100 40 140 60' stroke='$y' stroke-width='6' fill='none'/>",
        'canta'    => "<path d='M70 70 Q70 40 100 40 Q130 40 130 70' stroke='$d' stroke-width='8' fill='none'/><rect x='34' y='68' width='132' height='86' rx='16' fill='$c'/><rect x='34' y='100' width='132' height='10' fill='$y'/><rect x='88' y='120' width='24' height='16' rx='3' fill='$d'/>",
    ];
    $art = $shapes[$p['art'] ?? 'forma'] ?? $shapes['forma'];
    $label = e($p['name'] ?? '');
    return "<svg class='art $class' viewBox='0 0 200 200' role='img' aria-label='$label'><defs><linearGradient id='bg' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='#f6f6f4'/><stop offset='1' stop-color='#e9e8e4'/></linearGradient></defs><rect width='200' height='200' fill='url(#bg)'/><circle cx='168' cy='32' r='54' fill='$y' opacity='.18'/>$art</svg>";
}

function discount_percent(array $p): int
{
    return ($p['old_price'] ?? 0) > $p['price'] ? (int) round(100 - $p['price'] / $p['old_price'] * 100) : 0;
}

function features_list(?string $text): array
{
    return array_values(array_filter(array_map('trim', explode("\n", (string) $text))));
}

function product_sizes(array $p): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $p['sizes']))));
}

/* ---------- Sepet (oturumda tutulur) ---------- */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart(), 'qty'));
}

function cart_add(int $productId, string $size, int $qty): ?string
{
    $p = row('SELECT * FROM products WHERE id = ? AND active = 1', [$productId]);
    if (!$p) {
        return 'Ürün bulunamadı.';
    }
    $sizes = product_sizes($p);
    if ($sizes && !in_array($size, $sizes, true)) {
        return 'Lütfen beden seçin.';
    }
    $key = $productId . '|' . $size;
    $inCart = $_SESSION['cart'][$key]['qty'] ?? 0;
    $qty = max(1, min(20, $qty));
    if ($inCart + $qty > $p['stock']) {
        return $p['stock'] > 0 ? 'Bu üründen en fazla ' . $p['stock'] . ' adet alabilirsiniz.' : 'Ürün tükendi.';
    }
    $_SESSION['cart'][$key] = ['product_id' => $productId, 'size' => $size, 'qty' => $inCart + $qty];
    return null;
}

/** Sepeti güncel fiyat ve stoklarla hesaplar */
function cart_summary(): array
{
    $lines = [];
    $subtotal = 0;
    foreach (cart() as $key => $c) {
        $p = row('SELECT * FROM products WHERE id = ? AND active = 1', [$c['product_id']]);
        if (!$p) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $qty = min($c['qty'], max(0, (int) $p['stock']));
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $line = $p['price'] * $qty;
        $subtotal += $line;
        $lines[] = ['key' => $key, 'product' => $p, 'size' => $c['size'], 'qty' => $qty, 'total' => $line];
    }
    $limit = (float) setting('free_shipping_limit', '0');
    $shipping = !$lines || ($limit > 0 && $subtotal >= $limit) ? 0.0 : (float) setting('shipping_fee', '0');
    return ['lines' => $lines, 'subtotal' => $subtotal, 'shipping' => $shipping, 'total' => $subtotal + $shipping,
        'free_left' => $limit > 0 ? max(0, $limit - $subtotal) : 0];
}

function new_order_no(): string
{
    do {
        $no = 'GS' . date('ymd') . random_int(1000, 9999);
    } while (val('SELECT 1 FROM orders WHERE order_no = ?', [$no]));
    return $no;
}

function new_ticket_no(string $type): string
{
    $prefix = REQUEST_TYPES[$type][1] ?? 'TLP';
    do {
        $no = $prefix . '-' . date('ymd') . '-' . random_int(100, 999);
    } while (val('SELECT 1 FROM requests WHERE ticket_no = ?', [$no]));
    return $no;
}

function order_history(int $orderId, string $text, ?string $who = null): void
{
    $u = current_user();
    insert('order_history', ['order_id' => $orderId, 'user_name' => $who ?? ($u['name'] ?? 'Sistem'), 'text' => $text, 'created_at' => now()]);
}

/** Kredi kartı numarası geçerli mi? (Luhn) — kart bilgisi hiçbir yerde saklanmaz */
function luhn_ok(string $num): bool
{
    $num = preg_replace('/\D/', '', $num);
    if (strlen($num) < 15 || strlen($num) > 19) {
        return false;
    }
    $sum = 0;
    foreach (array_reverse(str_split($num)) as $i => $d) {
        $d = (int) $d;
        if ($i % 2) {
            $d *= 2;
            if ($d > 9) $d -= 9;
        }
        $sum += $d;
    }
    return $sum % 10 === 0;
}

/** Ürün kartı (ana sayfa ve listelerde) */
function product_card(array $p): string
{
    $off = discount_percent($p);
    $html = '<a class="product-card' . ($p['stock'] <= 0 ? ' soldout' : '') . '" href="' . e(url('urun.php?u=' . $p['slug'])) . '">';
    $html .= '<div class="pc-media">' . product_art($p) . '<div class="pc-tags">';
    if ($off) $html .= '<span class="tag tag-red">%' . $off . ' indirim</span>';
    if ($p['stock'] <= 0) $html .= '<span class="tag tag-dark">Tükendi</span>';
    elseif ($p['stock'] <= 5) $html .= '<span class="tag tag-yellow">Son ' . (int) $p['stock'] . ' ürün</span>';
    $html .= '</div><span class="pc-quick">İncele →</span></div><div class="pc-body"><h3>' . e($p['name']) . '</h3><p>' . e($p['short_desc']) . '</p>';
    $html .= '<div class="pc-price"><strong>' . money($p['price']) . '</strong>' . ($off ? '<del>' . money($p['old_price']) . '</del>' : '') . '</div></div></a>';
    return $html;
}

/** Ziyaretçi formlarından (iletişim, arıza, KVKK, İK) talep kaydı oluşturur, kayıt numarasını döner */
function create_request(string $type, string $name, string $email, string $phone, string $subject, string $message, array $extra = []): string
{
    $no = new_ticket_no($type);
    insert('requests', [
        'ticket_no' => $no, 'type' => $type, 'name' => mb_substr($name, 0, 120), 'email' => mb_strtolower(mb_substr($email, 0, 190)),
        'phone' => mb_substr($phone, 0, 30), 'subject' => mb_substr($subject, 0, 200), 'message' => mb_substr($message, 0, 5000),
        'extra' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null, 'status' => 'yeni', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $u = current_user();
    log_activity(REQUEST_TYPES[$type][0] . ' gönderdi', $no . ' · ' . $subject, 'admin/talepler.php?no=' . $no, $u ?? ['id' => null, 'name' => $name, 'role' => 'ziyaretci']);
    return $no;
}

/** Basit doğrulama: ad, e-posta, mesaj ve KVKK onayı. Hata metni veya null döner. */
function validate_contact(string $name, string $email, string $message, int $minMsg = 10): ?string
{
    if (input('website') !== '') {
        return 'Gönderilemedi.';
    }
    if (mb_strlen($name) < 3) return 'Adınızı ve soyadınızı girin.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Geçerli bir e-posta adresi girin.';
    if (mb_strlen($message) < $minMsg) return "Lütfen en az $minMsg karakterlik bir açıklama yazın.";
    if (!input('kvkk')) return 'Devam etmek için Aydınlatma Metni\'ni onaylayın.';
    return null;
}

/** Müşteriye gösterilen sipariş durum çubuğu ve geçmişi */
function order_track_html(array $o): string
{
    $steps = ['yeni' => 'Sipariş Alındı', 'hazirlaniyor' => 'Hazırlanıyor', 'kargoda' => 'Kargoda', 'teslim' => 'Teslim Edildi'];
    $keys = array_keys($steps);
    $pos = array_search($o['status'], $keys, true);
    $html = '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center"><h3 style="margin:0">Sipariş ' . e($o['order_no']) . '</h3>' . order_badge($o['status']) . '</div>';
    $html .= '<p class="small muted">' . tr_date($o['created_at']) . ' · ' . money($o['total']) . '</p>';
    if ($pos !== false) {
        $html .= '<div class="track">';
        foreach ($keys as $i => $k) {
            $html .= '<div class="' . ($i < $pos ? 'done' : ($i === $pos ? 'current' : '')) . '">' . $steps[$k] . '</div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<p class="alert alert-info">Bu sipariş ' . e(mb_strtolower(ORDER_STATUSES[$o['status']][0])) . '. Sorularınız için bizimle iletişime geçebilirsiniz.</p>';
    }
    if ($o['tracking_no']) {
        $html .= '<p><strong>Kargo:</strong> ' . e($o['cargo_company']) . ' · Takip No: <strong>' . e($o['tracking_no']) . '</strong></p>';
    }
    $html .= '<table class="data-table">';
    foreach (rows('SELECT * FROM order_items WHERE order_id = ?', [$o['id']]) as $i) {
        $html .= '<tr><td>' . e($i['name']) . ($i['size'] ? ' · ' . e($i['size']) : '') . '</td><td>' . $i['qty'] . ' adet</td><td style="text-align:right">' . money($i['price'] * $i['qty']) . '</td></tr>';
    }
    return $html . '</table>';
}
