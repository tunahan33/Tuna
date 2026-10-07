<?php
/**
 * Veritabanı güncellemeleri. Site her açıldığında çalışır, yalnızca eksik adımları uygular.
 * Mevcut verileriniz (siparişler, üyeler, değişiklikleriniz) korunur.
 */

const DB_VERSION = 3;

function migrate_database(PDO $pdo): void
{
    $current = (int) ($pdo->query("SELECT svalue FROM settings WHERE skey = 'db_version'")->fetchColumn() ?: 1);
    if ($current >= DB_VERSION) {
        return;
    }

    // v2: ürün fotoğrafları + yeni kategoriler ve fotoğraflı ürünler
    if ($current < 2) {
        $cols = array_column($pdo->query('PRAGMA table_info(products)')->fetchAll(), 'name');
        if (!in_array('images', $cols, true)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN images TEXT NOT NULL DEFAULT '[]'");
        }
        $cat = function (string $slug, string $name, string $desc) {
            $id = val('SELECT id FROM categories WHERE slug = ?', [$slug]);
            return $id ?: insert('categories', ['slug' => $slug, 'name' => $name, 'description' => $desc, 'sort' => (int) val('SELECT COALESCE(MAX(sort),0)+1 FROM categories')]);
        };
        $altGiyim = $cat('alt-giyim', 'Eşofman Altı & Şort', 'Jogger eşofman altları ve antrenman şortları');
        $aksesuar = $cat('aksesuar', 'Aksesuar', 'Kemer, şapka ve tamamlayıcı ürünler');
        $ayakkabi = $cat('ayakkabi', 'Ayakkabı & Krampon', 'Krampon, halı saha ve koşu ayakkabıları');
        $t = now();
        $products = photo_products(['alt-giyim' => $altGiyim, 'aksesuar' => $aksesuar, 'ayakkabi' => $ayakkabi]);
        foreach ($products as [$catId, $name, $short, $desc, $features, $price, $old, $stock, $sizes, $art, $color, $featured, $images]) {
            $slug = slugify($name);
            if (val('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
                continue;
            }
            insert('products', ['category_id' => $catId, 'slug' => $slug, 'name' => $name, 'short_desc' => $short, 'description' => $desc,
                'features' => $features, 'price' => $price, 'old_price' => $old, 'stock' => $stock, 'sizes' => $sizes, 'art' => $art, 'color' => $color,
                'images' => json_encode($images), 'featured' => $featured, 'active' => 1, 'created_at' => $t, 'updated_at' => $t]);
        }
    }

    // v3: yazım düzeltmeleri. Yalnızca panelden hiç düzenlenmemiş ürün ve sayfalar güncellenir.
    if ($current < 3) {
        require_once __DIR__ . '/seed.php';
        $renamed = ['gs-uzun-kollu-kaleci-formasi' => 'gs-kaleci-formasi-uzun-kol'];
        $fresh = [];
        foreach (demo_products() as $d) {
            $fresh[] = ['name' => $d[1], 'short_desc' => $d[2], 'description' => $d[3], 'features' => $d[4]];
        }
        foreach (photo_products(['alt-giyim' => 0, 'aksesuar' => 0, 'ayakkabi' => 0]) as $d) {
            $fresh[] = ['name' => $d[1], 'short_desc' => $d[2], 'description' => $d[3], 'features' => $d[4]];
        }
        foreach ($fresh as $f) {
            $slug = slugify($f['name']);
            $p = row('SELECT id, created_at, updated_at FROM products WHERE slug = ?', [$slug])
                ?? (isset($renamed[$slug]) ? row('SELECT id, created_at, updated_at FROM products WHERE slug = ?', [$renamed[$slug]]) : null);
            if ($p && $p['created_at'] === $p['updated_at']) {
                q('UPDATE products SET name = ?, short_desc = ?, description = ?, features = ? WHERE id = ?',
                    [$f['name'], $f['short_desc'], $f['description'], $f['features'], $p['id']]);
            }
        }
        foreach (require __DIR__ . '/seed_pages.php' as $slug => [$title, $content]) {
            q("UPDATE pages SET title = ?, content = ? WHERE slug = ? AND updated_by = 'Sistem'", [$title, $content, $slug]);
        }
        q("UPDATE settings SET svalue = ? WHERE skey = 'company_title' AND svalue = ?", ['GS Sportif Ürünler (Ticari unvanınızı girin)', 'GS Sportif Ürünler (Ticari ünvanınızı girin)']);
    }

    save_setting('db_version', (string) DB_VERSION);
}

/** Fotoğraflı ürünler: [kategori id, ad, kısa açıklama, açıklama, özellikler, fiyat, eski fiyat, stok, bedenler, çizim, renk, öne çıkan, fotoğraflar] */
function photo_products(array $cats): array
{
    $S = 'S,M,L,XL,XXL';
    return [
        [$cats['alt-giyim'], 'GS Jogger Eşofman Altı', 'İşlemeli GS armalı, manşet paçalı siyah jogger.',
            '<p>Günlük hayatta da, ısınmada da üzerinizden çıkarmak istemeyeceğiniz bir eşofman altı. İçi yumuşak <strong>şardonlu pamuk karışımı</strong> kumaşı sıcak tutar, nefes alır ve gün boyu rahat ettirir.</p><p>Sol üst bacaktaki <strong>işlemeli GS arması</strong>, bakır ve turuncu tonlarıyla siyah zemin üzerinde öne çıkar. Bağcıklı lastikli bel tam oturur, manşet paçalar ayakkabı üstünde temiz bir görünüm verir.</p><h3>Detaylar</h3><ul><li>Ön tarafta iki yan cep, arkada iki geniş arka cep</li><li>Ayarlanabilir bağcıklı, geniş lastikli bel</li><li>Ribanalı manşet paça</li></ul><h3>Kumaş ve bakım</h3><p>%80 pamuk, %20 polyester, 320 gr/m². 30°C\'de ters çevirerek yıkayın; arma bölgesini ütülemeyin.</p>',
            "İşlemeli GS arma\nİçi şardonlu pamuk karışımı\nBağcıklı lastikli bel\n2 yan + 2 arka cep\nManşetli paça", 2649.90, 2999.90, 40, $S, 'sort', '#2B2F33', 1,
            ['assets/urunler/gs-jogger-esofman-alti-on.webp', 'assets/urunler/gs-jogger-esofman-alti-arka.webp']],
        [$cats['aksesuar'], 'GS Hakiki Deri Kemer', 'Kabartma GS armalı antik pirinç tokalı, hakiki deri kemer.',
            '<p>Sportif ruhu şıklıkla buluşturan bir tamamlayıcı. <strong>%100 hakiki dana derisinden</strong> üretilen kemer, el yapımı kenar dikişleri ve doğal kahverengi tonuyla yıllarca kullanıma dayanır; zamanla kazandığı patina ile daha da güzelleşir.</p><p><strong>Antik pirinç kaplama toka</strong> üzerinde kabartma GS arması bulunur. Kemer ucunda da aynı arma deri üzerine preslenmiştir.</p><h3>Detaylar</h3><ul><li>Genişlik: 3,8 cm — kot ve kumaş pantolonla uyumlu</li><li>Kolay takılıp çıkarılan iğneli toka</li><li>Hediye kutusunda gönderilir</li></ul><h3>Beden seçimi</h3><p>Bel ölçünüzün 10 cm fazlasını seçin. Örneğin bel ölçünüz 90 cm ise 100 cm kemer tercih edin.</p>',
            "%100 hakiki dana derisi\nAntik pirinç toka, kabartma GS arma\nEl yapımı kenar dikişi\nGenişlik 3,8 cm\nHediye kutusunda", 2899.90, null, 25, '90 cm,95 cm,100 cm,105 cm,110 cm,115 cm', 'canta', '#7A4A2A', 0,
            ['assets/urunler/gs-hakiki-deri-kemer.webp']],
        [$cats['ayakkabi'], 'GS Heritage FG Krampon', 'Zümrüt yeşili deri, bakır GS amblemli premium krampon.',
            '<p>Klasik krampon zanaatını modern performansla buluşturan <strong>Heritage</strong> serisi. Zümrüt yeşili, yumuşak <strong>dana derisi sayası</strong> ayağınıza kısa sürede uyum sağlar ve topla temasta eşsiz bir his verir.</p><p>Yan yüzeydeki <strong>bakır renkli GS amblemi</strong> ve bakır tonlu dişleriyle sahada ilk bakışta fark edilir. Doğal çim sahalar için tasarlanan karma diş yapısı ani dönüşlerde güçlü tutuş, sprintlerde hızlı çıkış sağlar.</p><h3>Detaylar</h3><ul><li>Doğal çim (FG) için konik + bıçak karma diş dizilimi</li><li>Yastıklamalı topuk ve anatomik iç taban</li><li>Klasik bağcık sistemi ve deri dil</li></ul><h3>Bakım</h3><p>Maç sonrası nemli bezle silin, gazeteyle doldurup gölgede kurutun. Deri bakım kremi ile düzenli besleyin.</p>',
            "Hakiki dana derisi saya\nBakır renkli GS amblem ve dişler\nDoğal çim (FG) karma diş yapısı\nYastıklamalı topuk\nSınırlı üretim Heritage serisi", 6499.90, 7299.90, 15, '39,40,41,42,43,44,45', 'ayakkabi', '#1F5A3A', 1,
            ['assets/urunler/gs-heritage-fg-krampon.webp']],
        [$cats['alt-giyim'], 'GS Antrenman Şortu', 'Hafif, hızlı kuruyan, bağcıklı belli siyah antrenman şortu.',
            '<p>Antrenmanda, koşuda ve salonda tam hareket özgürlüğü sunar. <strong>Hafif ve esnek dokuma kumaşı</strong> hızlı kurur, terlediğinizde bile vücuda yapışmaz.</p><p>Geniş lastikli ve bağcıklı bel yoğun hareketlerde yerinde kalır. Diz üstü boyu ve rahat kalıbı ile hem saha hem günlük kullanım için idealdir.</p><h3>Detaylar</h3><ul><li>Gizli fermuarlı yan cep — anahtar ve kart için</li><li>Bağcıklı geniş lastikli bel</li><li>Diz üstü boy, rahat kalıp</li></ul><h3>Kumaş ve bakım</h3><p>%88 polyester, %12 elastan. 30°C\'de yıkayın, yumuşatıcı kullanmayın.</p>',
            "Hızlı kuruyan esnek kumaş\nBağcıklı lastikli bel\nFermuarlı yan cep\nDiz üstü boy", 2549.90, null, 50, $S, 'sort', '#2B2F33', 0,
            ['assets/urunler/gs-antrenman-sortu.webp']],
    ];
}
