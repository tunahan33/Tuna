<?php
/**
 * Veritabanı güncellemeleri. Site her açıldığında çalışır, yalnızca eksik adımları uygular.
 * Mevcut verileriniz (siparişler, üyeler, değişiklikleriniz) korunur.
 */

const DB_VERSION = 5;

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

    // v5: ZIP'ten gelen fotoğraflar — yeni kategoriler, yeni ürünler, mevcut ürünlere ek fotoğraflar
    if ($current < 5) {
        $cat = function (string $slug, string $name, string $desc) {
            return val('SELECT id FROM categories WHERE slug = ?', [$slug])
                ?: insert('categories', ['slug' => $slug, 'name' => $name, 'description' => $desc, 'sort' => (int) val('SELECT COALESCE(MAX(sort),0)+1 FROM categories')]);
        };
        $cats = [
            'tisort-atlet' => $cat('tisort-atlet', 'Tişört & Atlet', 'Antrenman ve günlük kullanım için tişört ve atletler'),
            'kadin' => $cat('kadin', 'Kadın', 'Kadınlar için eşofman takımı, tayt ve spor sütyeni'),
            'mont' => $cat('mont', 'Mont & Yağmurluk', 'Tribün ve dış saha için montlar'),
            'alt-giyim' => $cat('alt-giyim', 'Eşofman Altı & Şort', 'Jogger eşofman altları ve antrenman şortları'),
            'aksesuar' => $cat('aksesuar', 'Aksesuar', 'Kemer, şapka ve tamamlayıcı ürünler'),
            'motorsport' => $cat('motorsport', 'Motorsport', 'Pist, karting ve tribün için motorsport ürünleri'),
        ];
        $t = now();
        foreach (zip_photo_products() as [$c, $name, $short, $desc, $features, $price, $old, $stock, $sizes, $art, $color, $featured, $images]) {
            $slug = slugify($name);
            if (val('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
                continue;
            }
            insert('products', ['category_id' => $cats[$c], 'slug' => $slug, 'name' => $name, 'short_desc' => $short, 'description' => $desc,
                'features' => $features, 'price' => $price, 'old_price' => $old, 'stock' => $stock, 'sizes' => $sizes, 'art' => $art, 'color' => $color,
                'images' => json_encode(array_map(fn($f) => 'assets/urunler/' . $f . '.webp', $images)), 'featured' => $featured, 'active' => 1, 'created_at' => $t, 'updated_at' => $t]);
        }

        // Kategorileri mantıklı sıraya koy (giyim → ayakkabı → ekipman → motorsport)
        $order = ['formalar', 'tisort-atlet', 'esofman-ceket', 'sweatshirt', 'mont', 'alt-giyim', 'kadin', 'ayakkabi', 'ekipman', 'aksesuar', 'motorsport'];
        foreach ($order as $i => $slug) {
            q('UPDATE categories SET sort = ? WHERE slug = ?', [$i + 1, $slug]);
        }

        // Mevcut ürünlere fotoğraf ekle: [ürün adresi, fotoğraflar, nereye (sona / başa / boşsa)]
        $attach = [
            ['gs-hakiki-deri-kemer', ['gs-hakiki-deri-kemer-2'], 'sona'],
            ['gs-heritage-fg-krampon', ['gs-heritage-fg-krampon-ic'], 'sona'],
            ['gs-antrenman-sortu', ['gs-antrenman-sortu-on'], 'basa'],
            ['gs-resmi-mac-topu', ['gs-resmi-mac-topu'], 'bossa'],
            ['gs-hali-saha-ayakkabisi', ['gs-halisaha-ayakkabisi-yan', 'gs-halisaha-ayakkabisi-on'], 'bossa'],
            ['gs-racing-surucu-eldiveni', ['gs-motorsport-deri-eldiven'], 'bossa'],
            ['gs-motorsport-sirt-cantasi', ['gs-motorsport-rulo-kapakli-canta'], 'bossa'],
        ];
        foreach ($attach as [$slug, $files, $where]) {
            $p = row('SELECT id, images FROM products WHERE slug = ?', [$slug]);
            if (!$p) {
                continue;
            }
            $current_imgs = json_decode((string) $p['images'], true) ?: [];
            $new = array_values(array_diff(array_map(fn($f) => 'assets/urunler/' . $f . '.webp', $files), $current_imgs));
            if (!$new || ($where === 'bossa' && $current_imgs)) {
                continue;
            }
            $list = $where === 'basa' ? array_merge($new, $current_imgs) : array_merge($current_imgs, $new);
            q('UPDATE products SET images = ? WHERE id = ?', [json_encode($list), $p['id']]);
        }

        // Fotoğrafı eklenen iki motorsport ürününün açıklamasını fotoğrafa uygun hâle getir (panelden düzenlenmediyse)
        $texts = [
            'gs-racing-surucu-eldiveni' => ['Eklem korumalı, uzun bilekli deri sürüş eldiveni.',
                '<p>Pistte ve yolda ellerinizi koruyan <strong>uzun bilekli deri sürüş eldiveni</strong>. Parmak eklemlerinin üzerindeki sert koruma plakaları darbelere karşı ek güvenlik sağlar.</p><p>Bilek kısmını kaplayan uzun yapısı mont kolunun altına ya da üstüne rahatça oturur. Üst yüzeyde ve bilekte altın ve kırmızı tonlarında GS logoları bulunur.</p><h3>Detaylar</h3><ul><li>Parmak eklemlerinde sert koruma</li><li>Uzun, ayarlanabilir bilek</li><li>Esneme panelli parmaklar</li></ul>',
                "Deri gövde\nEklem koruması\nUzun bilek\nAyarlanabilir bilek kapama"],
            'gs-motorsport-sirt-cantasi' => ['Rulo kapaklı, tokalı motorsport sırt çantası.',
                '<p>Pist gününe gereken her şeyi tek çantada toplayın. <strong>Rulo kapaklı ağzı</strong> tokayla kilitlenir; kapağı birkaç kez katlayarak çantanın hacmini ihtiyacınıza göre ayarlayabilirsiniz.</p><p>Yanlardaki sıkıştırma askıları yükü sabit tutar. Ön yüzde altın ve kırmızı tonlarında büyük GS logosu bulunur.</p><h3>Detaylar</h3><ul><li>Rulo kapak ve tokalı kapama</li><li>Yan sıkıştırma askıları</li><li>Suya dayanıklı, silinebilir yüzey</li></ul>',
                "Rulo kapak\nTokalı kapama\nYan sıkıştırma askıları\nSilinebilir yüzey"],
        ];
        foreach ($texts as $slug => [$short, $desc, $features]) {
            q('UPDATE products SET short_desc = ?, description = ?, features = ? WHERE slug = ? AND created_at = updated_at', [$short, $desc, $features, $slug]);
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

/** ZIP ile gelen fotoğraflı ürünler: [kategori, ad, kısa açıklama, açıklama, özellikler, fiyat, eski fiyat, stok, bedenler, çizim, renk, öne çıkan, fotoğraflar] */
function zip_photo_products(): array
{
    $S = 'S,M,L,XL,XXL';
    $K = 'XS,S,M,L,XL';
    return [
        ['tisort-atlet', 'GS Yeşil Arma Tişört', 'Göğsünde büyük bakır renkli GS armalı, koyu yeşil tişört.',
            '<p>Koyu yeşil zemin üzerinde göğsü kaplayan <strong>bakır tonlarındaki büyük GS arması</strong> ile dikkat çeken tişört. Rahat kesimi sayesinde antrenmanda da günlük hayatta da kullanılabilir.</p><h3>Detaylar</h3><ul><li>Bisiklet yaka, kısa kol</li><li>Rahat kesim</li><li>Arka yüz düz, baskısız</li></ul>',
            "Büyük GS arma baskısı\nBisiklet yaka\nRahat kesim", 2549.90, null, 40, $S, 'forma', '#1F5A3A', 1,
            ['gs-yesil-arma-tisort-on', 'gs-yesil-arma-tisort-arka']],
        ['tisort-atlet', 'GS Siyah Logo Tişört', 'Göğüste küçük turuncu GS logolu, sade siyah tişört.',
            '<p>Her kombine uyan <strong>sade siyah tişört</strong>. Sol göğüsteki küçük turuncu GS logosu ince bir marka dokunuşu katar.</p><h3>Detaylar</h3><ul><li>Bisiklet yaka, kısa kol</li><li>Sol göğüste küçük GS logosu</li><li>Rahat kesim</li></ul>',
            "Küçük GS logo\nBisiklet yaka\nRahat kesim", 2549.90, null, 60, $S, 'forma', '#111315', 0,
            ['gs-siyah-logo-tisort-on', 'gs-siyah-logo-tisort-arka']],
        ['tisort-atlet', 'GS Uzun Kollu Antrenman Tişörtü', 'Reglan kollu, GS logolu siyah uzun kollu tişört.',
            '<p>Serin havalarda antrenmanın ve ısınmanın vazgeçilmezi. <strong>Reglan kol kesimi</strong> omuzlarda geniş hareket alanı sağlar, tek başına ya da mont altında giyilebilir.</p><h3>Detaylar</h3><ul><li>Reglan uzun kol</li><li>Sol göğüste GS logosu</li><li>Bisiklet yaka</li></ul>',
            "Reglan uzun kol\nGS logo\nBisiklet yaka", 2749.90, null, 35, $S, 'forma', '#111315', 0,
            ['gs-uzun-kollu-antrenman-tisortu-on', 'gs-uzun-kollu-antrenman-tisortu-arka']],
        ['tisort-atlet', 'GS Yeşil Kolsuz Atlet', 'Büyük GS armalı, unisex koyu yeşil kolsuz antrenman atleti.',
            '<p>Salon ve sıcak hava antrenmanları için <strong>unisex kolsuz atlet</strong>. Geniş kol oyuntusu kollarınızı serbest bırakır; ön yüzdeki bakır renkli büyük GS arması güçlü bir görünüm verir.</p><h3>Detaylar</h3><ul><li>Unisex kalıp</li><li>Geniş kol oyuntusu</li><li>Büyük GS arma baskısı</li></ul>',
            "Unisex kalıp\nGeniş kol oyuntusu\nBüyük GS arma", 2549.90, null, 30, $S, 'forma', '#1F5A3A', 0,
            ['gs-yesil-kolsuz-atlet-on', 'gs-yesil-kolsuz-atlet-arka']],
        ['mont', 'GS Yeşil Kapüşonlu Rüzgârlık', 'Tam fermuarlı, kapüşonlu, GS armalı yeşil rüzgârlık.',
            '<p>Rüzgârlı ve serin günler için hafif, <strong>kapüşonlu ve tam fermuarlı</strong> rüzgârlık. Lastikli kol ağızları ve bel rüzgârın içeri girmesini engeller.</p><p>Sol göğüsteki bakır tonlu GS arması koyu yeşil renkle şık bir uyum yakalar.</p><h3>Detaylar</h3><ul><li>Kapüşon ve tam boy fermuar</li><li>Fermuarlı iki yan cep</li><li>Lastikli kol ağzı ve bel</li></ul>',
            "Kapüşonlu\nTam boy fermuar\nFermuarlı yan cepler\nLastikli kol ağzı ve bel", 3299.90, 3799.90, 25, $S, 'mont', '#1F5A3A', 1,
            ['gs-yesil-kapusonlu-ruzgarlik-on', 'gs-yesil-kapusonlu-ruzgarlik-arka']],
        ['kadin', 'GS Kadın Eşofman Takımı', 'Turuncu biyeli, vücuda oturan siyah kadın eşofman takımı.',
            '<p>Antrenmanda ve günlük hayatta şık ve rahat bir görünüm için <strong>uzun kollu üst ve jogger alttan oluşan</strong> kadın eşofman takımı. Kollar ve paçalar boyunca uzanan turuncu biyeler siluete sportif bir hava katar.</p><h3>Detaylar</h3><ul><li>Uzun kollu üst + jogger alt</li><li>Turuncu biye detayları</li><li>Üstte ve altta GS logosu</li><li>Manşetli paça</li></ul>',
            "Üst + alt takım\nTuruncu biye detayı\nGS logolu\nManşetli paça", 4299.90, 4799.90, 20, $K, 'esofman', '#111315', 1,
            ['gs-kadin-esofman-takimi-on', 'gs-kadin-esofman-takimi-arka']],
        ['kadin', 'GS Kadın Yüksek Bel Tayt', 'Yüksek belli, GS logolu siyah antrenman taytı.',
            '<p>Yoga, pilates ve fitness için <strong>yüksek belli tayt</strong>. Geniş bel bandı hareket sırasında yerinde kalır ve karın bölgesini toparlar.</p><h3>Detaylar</h3><ul><li>Geniş, yüksek bel bandı</li><li>Bilekte biten tam boy</li><li>Belde küçük GS logosu</li></ul>',
            "Yüksek bel\nGeniş bel bandı\nTam boy", 2649.90, null, 35, $K, 'sort', '#111315', 0,
            ['gs-kadin-yuksek-bel-tayt-on', 'gs-kadin-yuksek-bel-tayt-arka']],
        ['kadin', 'GS Kadın Spor Sütyeni', 'Sporcu sırtlı, GS logolu siyah spor sütyeni.',
            '<p>Antrenman boyunca destek ve rahatlık için tasarlanan <strong>sporcu sırtlı (racerback)</strong> spor sütyeni. Geniş alt bant yerinde kalır, sırt kesimi kolların serbestçe hareket etmesini sağlar.</p><h3>Detaylar</h3><ul><li>Racerback sırt kesimi</li><li>Geniş alt bant</li><li>Önde GS logosu</li></ul>',
            "Racerback sırt\nGeniş alt bant\nGS logo", 2549.90, null, 30, $K, 'forma', '#111315', 0,
            ['gs-kadin-spor-sutyeni-on', 'gs-kadin-spor-sutyeni-arka']],
        ['alt-giyim', 'GS Slim Fit Antrenman Eşofman Altı', 'Antrasit renkli, dar kesim antrenman eşofman altı.',
            '<p>Antrenmandan şehre kadar her yerde şık duran <strong>dar kesim (slim fit)</strong> eşofman altı. Antrasit rengi ve paçaya doğru daralan kesimiyle modern bir görünüm sunar.</p><h3>Detaylar</h3><ul><li>Slim fit kesim</li><li>Lastikli bel</li><li>Yan cepler</li><li>Bacakta GS logosu</li></ul>',
            "Slim fit kesim\nLastikli bel\nYan cepler", 2799.90, null, 30, $S, 'sort', '#3A3F44', 0,
            ['gs-slim-fit-antrenman-esofman-alti-on', 'gs-slim-fit-antrenman-esofman-alti-arka']],
        ['aksesuar', 'GS Performans Futbol Çorabı', 'Turuncu şimşek desenli, GS logolu uzun futbol çorabı.',
            '<p>Sahada fark edilmek isteyenler için <strong>dize kadar uzanan futbol çorabı</strong>. Siyah zemin üzerindeki turuncu ve krem şimşek deseni ile GS logosu takım kombininizi tamamlar.</p><h3>Detaylar</h3><ul><li>Dize kadar uzun boy</li><li>Şimşek desen ve GS logosu</li></ul>',
            "Uzun boy\nŞimşek desen\nGS logo", 2549.90, null, 50, '36-39,40-43,44-46', 'sort', '#111315', 0,
            ['gs-performans-futbol-coraplari-on', 'gs-performans-futbol-coraplari-arka']],
        ['aksesuar', 'GS Pirinç Arma Bileklik', 'Pirinç plakalı, kabartma GS armalı silikon bileklik.',
            '<p>Sporda da günlük hayatta da takabileceğiniz <strong>silikon bileklik</strong>. Ön yüzdeki antik pirinç plakada kabartma GS arması bulunur.</p><h3>Detaylar</h3><ul><li>Esnek silikon kayış</li><li>Kabartma armalı pirinç plaka</li><li>Ayarlanabilir delikli kapama</li></ul>',
            "Silikon kayış\nPirinç plaka\nKabartma GS arma\nAyarlanabilir", 2549.90, null, 40, '', 'canta', '#111315', 0,
            ['gs-pirinc-arma-bileklik', 'gs-pirinc-arma-bileklik-2']],
        ['motorsport', 'GS Motorsport Korumalı Sürüş Montu', 'Sırt koruma bölmeli, deri detaylı siyah sürüş montu.',
            '<p>Pist günleri ve uzun sürüşler için tasarlanan <strong>sürüş montu</strong>. Ön yüzdeki deri paneller ve dik yaka sportif bir görünüm verir; sırttaki belirgin koruma bölmesi ve omuz-dirsek pedleri vücudu destekler.</p><p>Arkada büyük GS logosu ve gri yansıtıcı şeritler bulunur.</p><h3>Detaylar</h3><ul><li>Sırt koruma bölmesi, omuz ve dirsek pedleri</li><li>Fermuarlı cepler</li><li>Cırt bantlı ayarlanabilir bel ve kol ağzı</li><li>Yansıtıcı şerit detayları</li></ul>',
            "Sırt koruma bölmesi\nOmuz ve dirsek pedleri\nAyarlanabilir bel\nYansıtıcı detaylar", 9999.90, 11499.90, 10, $S, 'mont', '#111315', 1,
            ['gs-motorsport-korumali-surus-montu-on', 'gs-motorsport-korumali-surus-montu-arka']],
        ['motorsport', 'GS Motorsport Sürüş Botu', 'Kaval ve bilek korumalı, cırt bantlı siyah sürüş botu.',
            '<p>Pistte ve yolda ayaklarınızı koruyan <strong>uzun konçlu sürüş botu</strong>. Kaval ve bilek bölgesindeki sert paneller darbelere karşı destek sağlar; cırt bantlı kayış konçu bacağınıza göre ayarlar.</p><h3>Detaylar</h3><ul><li>Kaval ve bilek koruma panelleri</li><li>Cırt bantlı ayar kayışı</li><li>Kaymaz, kalın taban</li><li>Altın ve kırmızı GS logoları</li></ul>',
            "Kaval ve bilek koruması\nCırt bantlı ayar\nKaymaz taban", 5999.90, null, 12, '39,40,41,42,43,44,45,46', 'ayakkabi', '#111315', 0,
            ['gs-motorsport-surus-botu']],
        ['motorsport', 'GS Motorsport Sert Kabuk Çanta', 'Karbon desenli, sert kabuklu ve fermuarlı ekipman çantası.',
            '<p>Eldiven, şapka ve küçük ekipmanlarınızı darbelere karşı koruyan <strong>sert kabuklu çanta</strong>. Karbon desenli kabuğu şeklini korur, çift fermuarlı kapağı kolayca açılır.</p><h3>Detaylar</h3><ul><li>Sert, şeklini koruyan kabuk</li><li>Çift fermuarlı kapak</li><li>Taşıma tutamağı</li><li>Altın ve kırmızı GS logosu</li></ul>',
            "Sert kabuk\nKarbon desen\nÇift fermuar\nTaşıma tutamağı", 3499.90, null, 15, '', 'canta', '#111315', 0,
            ['gs-motorsport-sert-kabuk-canta']],
        ['motorsport', 'GS Motorsport Şapka', 'Önde altın-kırmızı GS işlemeli siyah beyzbol şapkası.',
            '<p>Tribünde ve pit alanında takımınızı gösterin. Siyah <strong>beyzbol şapkasının</strong> önünde altın ve kırmızı tonlarında GS logosu bulunur.</p><h3>Detaylar</h3><ul><li>Kavisli siperlik</li><li>Önde GS logosu</li></ul>',
            "Kavisli siperlik\nGS logo", 2549.90, null, 40, '', 'canta', '#111315', 0,
            ['gs-motorsport-sapka']],
    ];
}
