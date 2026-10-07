<?php
/**
 * Veritabanı güncellemeleri. Site her açıldığında çalışır, yalnızca eksik adımları uygular.
 * Mevcut verileriniz (siparişler, üyeler, değişiklikleriniz) korunur.
 */

const DB_VERSION = 4;

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

    // v4: Motorsport kategorisi ve ürünleri
    if ($current < 4) {
        $catId = val("SELECT id FROM categories WHERE slug = 'motorsport'")
            ?: insert('categories', ['slug' => 'motorsport', 'name' => 'Motorsport', 'description' => 'Pist, karting ve tribün için motorsport ürünleri', 'sort' => (int) val('SELECT COALESCE(MAX(sort),0)+1 FROM categories')]);
        $t = now();
        foreach (motorsport_products() as [$name, $short, $desc, $features, $price, $old, $stock, $sizes, $art, $color, $featured]) {
            $slug = slugify($name);
            if (val('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
                continue;
            }
            insert('products', ['category_id' => $catId, 'slug' => $slug, 'name' => $name, 'short_desc' => $short, 'description' => $desc,
                'features' => $features, 'price' => $price, 'old_price' => $old, 'stock' => $stock, 'sizes' => $sizes, 'art' => $art, 'color' => $color,
                'images' => '[]', 'featured' => $featured, 'active' => 1, 'created_at' => $t, 'updated_at' => $t]);
        }
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

/** Motorsport ürünleri: [ad, kısa açıklama, açıklama, özellikler, fiyat, eski fiyat, stok, bedenler, çizim, renk, öne çıkan] */
function motorsport_products(): array
{
    $S = 'S,M,L,XL,XXL';
    return [
        ['GS Racing Karting Kaskı', 'Karting ve pist günleri için hafif, havalandırmalı kapalı kask.',
            '<p>Karting ve pist günleri için tasarlanan <strong>kapalı tip kask</strong>. Hafif kabuğu uzun sürüşlerde boyun yorgunluğunu azaltır, ön ve üst hava kanalları kask içini serin tutar.</p><p>Sarı-kırmızı yarış grafiği, antrasit vizör ve GS logosuyla pistte ilk bakışta tanınır.</p><h3>Detaylar</h3><ul><li>Ön ve üst havalandırma kanalları</li><li>Çıkarılıp yıkanabilen iç astar</li><li>Hızlı açılan çene kilidi</li><li>Çizilmeye dayanıklı vizör</li></ul><h3>Beden seçimi</h3><p>Kaşlarınızın hemen üzerinden baş çevrenizi ölçün ve ölçünüze karşılık gelen bedeni seçin. Kask sıkı oturmalı ama baskı yapmamalıdır.</p>',
            "Hafif kabuk\nHavalandırma kanalları\nYıkanabilir iç astar\nHızlı açılan çene kilidi", 7499.90, 8299.90, 12, 'XS (53-54 cm),S (55-56 cm),M (57-58 cm),L (59-60 cm),XL (61-62 cm)', 'kask', '#C8102E', 1],
        ['GS Motorsport Takım Ceketi', 'Pit alanı ve tribün için su itici softshell takım ceketi.',
            '<p>Pistte, pit alanında ve tribünde takımınızı temsil etmeniz için tasarlanan <strong>softshell takım ceketi</strong>. Su itici dış yüzeyi hafif yağmurda sizi korur, içindeki ince polar katman serin pist sabahlarında sıcak tutar.</p><p>Antrasit zemin üzerinde omuzdan bileğe uzanan sarı-kırmızı yarış şeritleri ve göğüste işlemeli GS Motorsport arması bulunur.</p><h3>Detaylar</h3><ul><li>Su itici ve rüzgâr kesen softshell kumaş</li><li>Fermuarlı iki yan cep ve bir iç cep</li><li>Ayarlanabilir kol manşetleri</li><li>Dik yaka, tam boy fermuar</li></ul>',
            "Su itici softshell kumaş\nİçi ince polar\nİşlemeli GS Motorsport arması\nFermuarlı cepler", 3999.90, 4599.90, 30, $S, 'mont', '#2B2F33', 1],
        ['GS Motorsport Pit Polo Tişört', 'Teknik kumaştan, yarış şeritli takım polo tişörtü.',
            '<p>Yarış ekiplerinin pit alanında giydiği polo tişörtlerden ilham alındı. Hızlı kuruyan <strong>teknik piké kumaşı</strong> uzun yarış günlerinde bile ferah tutar.</p><p>Omuzlardaki sarı-kırmızı şeritler ve göğüsteki GS Motorsport arması ile hem pistte hem günlük hayatta şık bir görünüm sağlar.</p><h3>Detaylar</h3><ul><li>Düğmeli polo yaka</li><li>Nefes alan, hızlı kuruyan kumaş</li><li>Omuzlarda yarış şeridi detayı</li></ul>',
            "Teknik piké kumaş\nHızlı kuruma\nDüğmeli polo yaka\nYarış şeridi detayı", 2649.90, null, 50, $S, 'forma', '#C8102E', 0],
        ['GS Racing Sürücü Eldiveni', 'Avuç içi tutuş artırıcı, esnek yarış eldiveni.',
            '<p>Direksiyonla aranızdaki bağlantıyı güçlendiren <strong>sürücü eldiveni</strong>. Avuç içindeki tutuş artırıcı yüzey, eller terlediğinde bile direksiyonun kaymasını önler.</p><p>Dikişleri dışa alınmış parmak yapısı sürtünmeyi azaltır, esnek bilek bandı eldiveni yerinde tutar.</p><h3>Detaylar</h3><ul><li>Tutuş artırıcı avuç içi</li><li>Dış dikişli, ön kıvrımlı parmaklar</li><li>Cırt bantlı bilek</li></ul>',
            "Tutuş artırıcı avuç içi\nDış dikişli parmaklar\nCırt bantlı bilek\nEsnek kumaş", 2899.90, null, 25, 'S,M,L,XL', 'eldiven', '#2B2F33', 0],
        ['GS Karting Yarış Tulumu', 'Karting için dayanıklı, esnek panelli tek parça yarış tulumu.',
            '<p>Karting pistleri için tasarlanan <strong>tek parça yarış tulumu</strong>. Dayanıklı dış kumaşı sürtünmeye karşı direnç gösterir, sırt ve dirseklerdeki esnek paneller koltukta rahat hareket etmenizi sağlar.</p><p>Kırmızı zemin üzerindeki sarı yan şeritler, antrasit bel ve yaka detayları ile GS Motorsport kimliğini taşır.</p><h3>Detaylar</h3><ul><li>Sırtta ve dirseklerde esnek paneller</li><li>Ayarlanabilir bel</li><li>İki yönlü ön fermuar</li><li>Fermuarlı göğüs cebi</li></ul>',
            "Tek parça tulum\nEsnek sırt ve dirsek panelleri\nAyarlanabilir bel\nİki yönlü fermuar", 8999.90, 9999.90, 10, $S, 'tulum', '#C8102E', 1],
        ['GS Motorsport Sırt Çantası', 'Kask bölmeli, laptop gözlü pist günü sırt çantası.',
            '<p>Pist gününe gereken her şeyi tek çantada toplayın. Alt kısımdaki <strong>ayrı kask bölmesi</strong> kaskınızı korur, dolgulu iç gözüne 15,6 inç dizüstü bilgisayar sığar.</p><p>Su itici kumaşı, yansıtıcı detayları ve dolgulu sırt paneli ile hem pistte hem şehirde kullanıma uygundur.</p><h3>Detaylar</h3><ul><li>Ayrı kask bölmesi</li><li>Dolgulu dizüstü bilgisayar gözü</li><li>Su itici kumaş, yansıtıcı detaylar</li><li>Dolgulu sırt ve omuz askıları</li></ul>',
            "Kask bölmesi\nDizüstü bilgisayar gözü\nSu itici kumaş\nYansıtıcı detaylar", 2749.90, null, 20, '', 'canta', '#2B2F33', 0],
    ];
}
