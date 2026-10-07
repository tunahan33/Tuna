<?php
/**
 * İlk açılışta veritabanını doldurur: ayarlar, kullanıcılar, kategoriler, ürünler, sayfalar ve örnek siparişler.
 * Her şeyi sıfırlamak için: php app/sifirla.php  (veya data/gs.sqlite dosyasını silin)
 */

function seed_database(): void
{
    $t = now();

    // Ayarlar (Panel > Ayarlar'dan değiştirilir)
    $settings = [
        'site_name'          => 'GS Sportif Ürünler',
        'site_slogan'        => 'Sarı-kırmızı ruhun sportif adresi',
        'site_url'           => 'https://www.gssportifurunler.net',
        'company_title'      => 'Nida Coşkun',
        'company_address'    => 'Mecidiyeköy Mah. Eski Osmanlı Sk. Arıkan İş Merkezi No: 30 İç Kapı No: 10 Şişli / İstanbul',
        'company_phone'      => '+90 543 107 23 27',
        'company_email'      => 'info@gssportifurunler.net',
        'tax_office'         => 'Zincirlikuyu',
        'tax_number'         => '25529273476',
        'mersis_number'      => '',
        'kep_address'        => '',
        'working_hours'      => 'Hafta içi 09:00 - 18:00',
        'shipping_fee'       => '149.90',
        'free_shipping_limit' => '5000',
        'shipping_days'      => '1-2 iş günü',
        'announcement'       => '5.000 ₺ ve üzeri siparişlerde kargo bedava · 14 gün içinde ücretsiz iade',
    ];
    foreach ($settings as $k => $v) {
        save_setting($k, $v);
    }

    // Kullanıcılar
    $users = [
        ['Süper Admin', 'admin@gssportif.local', 'Admin123!', 'super_admin'],
        ['Ayşe Yönetici', 'yonetici@gssportif.local', 'Test1234!', 'admin'],
        ['Emre Editör', 'editor@gssportif.local', 'Test1234!', 'editor'],
        ['Selin Satış', 'satis@gssportif.local', 'Test1234!', 'satis'],
        ['Murat Satış', 'satis2@gssportif.local', 'Test1234!', 'satis'],
        ['Deniz Müşteri', 'uye@gssportif.local', 'Test1234!', 'uye'],
    ];
    $uid = [];
    foreach ($users as [$name, $email, $pass, $role]) {
        $uid[$role][] = insert('users', ['name' => $name, 'email' => $email, 'phone' => '05' . random_int(30, 55) . ' ' . random_int(100, 999) . ' ' . random_int(10, 99) . ' ' . random_int(10, 99),
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $role, 'active' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-40 days'))]);
    }

    // Kategoriler
    $cats = [
        ['formalar', 'Formalar', 'Maç ve antrenman formaları'],
        ['esofman-ceket', 'Eşofman & Ceket', 'Antrenman ve seyahat için eşofman takımları'],
        ['sweatshirt', 'Sweatshirt & Hoodie', 'Soğuk günler için kapüşonlu üstler'],
        ['mont', 'Mont & Yağmurluk', 'Tribün ve dış saha için montlar'],
        ['ayakkabi', 'Ayakkabı & Krampon', 'Krampon, halı saha ve koşu ayakkabıları'],
        ['ekipman', 'Top & Ekipman', 'Maç topları, çantalar ve ekipmanlar'],
    ];
    $catId = [];
    foreach ($cats as $i => [$slug, $name, $desc]) {
        $catId[$slug] = insert('categories', ['slug' => $slug, 'name' => $name, 'description' => $desc, 'sort' => $i + 1]);
    }

    $products = demo_products();
    $productRows = [];
    foreach ($products as [$cat, $name, $short, $desc, $features, $price, $old, $stock, $sizes, $art, $color, $featured]) {
        $id = insert('products', ['category_id' => $catId[$cat], 'slug' => slugify($name), 'name' => $name, 'short_desc' => $short, 'description' => $desc,
            'features' => $features, 'price' => $price, 'old_price' => $old, 'stock' => $stock, 'sizes' => $sizes, 'art' => $art, 'color' => $color,
            'featured' => $featured, 'active' => 1, 'created_at' => $t, 'updated_at' => $t]);
        $productRows[] = ['id' => $id, 'name' => $name, 'price' => $price, 'sizes' => $sizes];
    }

    // Sayfalar
    foreach (require __DIR__ . '/seed_pages.php' as $slug => [$title, $content]) {
        insert('pages', ['slug' => $slug, 'title' => $title, 'content' => $content, 'updated_by' => 'Sistem', 'updated_at' => $t]);
    }

    seed_demo_orders($productRows, $uid);

    insert('activity', ['user_id' => null, 'user_name' => 'Sistem', 'role' => 'sistem', 'action' => 'Mağaza kuruldu',
        'details' => 'Veritabanı oluşturuldu, demo veriler yüklendi', 'link' => '', 'ip' => client_ip(), 'created_at' => $t]);
}

/** Demo ürün kataloğu: [kategori, ad, kısa açıklama, uzun açıklama, özellikler, fiyat, eski fiyat, stok, bedenler, çizim, renk, öne çıkan] */
function demo_products(): array
{
    $S = 'XS,S,M,L,XL,XXL';
    $N = '39,40,41,42,43,44,45';
    $R = '#C8102E';
    $Y = '#FDB913';
    $A = '#2B2F33';

    // [kategori, ad, kısa açıklama, uzun açıklama, özellikler, fiyat, eski fiyat, stok, bedenler, çizim, renk, öne çıkan]
    return [
        ['formalar', 'GS Pro İç Saha Forması', 'Sarı-kırmızı parçalı, nefes alan profesyonel maç forması.',
            '<p>Sahada ilk düdükten son düdüğe kadar serin ve kuru kalmanız için tasarlandı. Mikro delikli <strong>Dry-Tech</strong> kumaş teri hızla dışarı atar, vücut ısınızı dengede tutar.</p><p>Göğüsteki dokuma arması, omuzlardaki sarı-kırmızı şeridi ve antrasit yakasıyla klasik iç saha tasarımını modern bir kesimle buluşturur. Profesyonel oyuncuların tercih ettiği <em>atletik kesimi</em> sayesinde vücudu sarar ama hareketi kısıtlamaz.</p><h3>Kumaş ve bakım</h3><p>%100 geri dönüştürülmüş polyester, 140 gr/m². 30°C\'de ters çevirerek yıkayın, ütülemeyin, kurutma makinesine atmayın.</p>',
            "Mikro delikli Dry-Tech kumaş\nDokuma arma, ısı transfer sponsor baskısı\nAtletik (dar) kesim\nGeri dönüştürülmüş polyester", 3499.90, 3999.90, 60, $S, 'forma', $R, 1],
        ['formalar', 'GS Pro Deplasman Forması', 'Antrasit zeminde sarı detaylı deplasman forması.',
            '<p>Deplasmanda da aynı tutku: Antrasit zemin üzerinde sarı-kırmızı diyagonal şerit ile tasarlanan deplasman forması, şehir dışında da takımınızı gururla temsil etmeniz için hazırlandı.</p><p>Hafif yapısı ve esnek yan panelleri ile maç boyunca konfor sağlar. Yaka ve kol ağızlarındaki ribanalar, formanın biçimini uzun süre korur.</p>',
            "Hafif ve esnek yan paneller\nRibana yaka ve kol ağzı\nNem transfer teknolojisi\nStandart kesim", 3299.90, null, 45, $S, 'forma', $A, 1],
        ['formalar', 'GS Uzun Kollu Kaleci Forması', 'Dirsek pedli, kavrama artırıcı kaleci forması.',
            '<p>Kaleciler için özel olarak geliştirilen uzun kollu forma; dirseklerde darbe emici pedler ve göğüste top kontrolünü artıran dokulu panel içerir.</p><p>Esnek kumaşı sayesinde uzanma ve plonjonlarda hareket özgürlüğü sağlar.</p>',
            "Dirsek koruma pedleri\nDokulu göğüs paneli\n4 yönlü esneyen kumaş\nUzun kol", 3799.90, null, 18, $S, 'forma', $Y, 0],
        ['formalar', 'GS Retro 1905 Forması', 'Kuruluş yılı anısına pamuklu retro forma.',
            '<p>Kulübün köklü tarihine selam: 1905 retro forma, dönemin klasik yakalı tasarımını yumuşak pamuklu kumaşla yeniden yorumlar. Tribünde, sokakta ve koleksiyonunuzda şık bir parça.</p><p>Arma işlemeli, yaka düğmeli ve sınırlı sayıda üretilmiştir.</p>',
            "Sınırlı üretim\n%100 pamuk pike kumaş\nİşlemeli arma\nDüğmeli klasik yaka", 2899.90, 3299.90, 12, $S, 'forma', $Y, 1],
        ['esofman-ceket', 'GS Antrenman Eşofman Takımı', 'Fermuarlı ceket ve dar paçalı eşofman altından oluşan takım.',
            '<p>Isınmadan seyahate, antrenmandan günlük kullanıma kadar her yerde rahatlık. Fermuarlı ceket ve dar paçalı eşofman altından oluşan takım, kolları boyunca uzanan sarı-kırmızı şeritlerle takım ruhunu yansıtır.</p><p>Fermuarlı yan cepler eşyalarınızı güvende tutar; bel bağcığı ve paça fermuarı sayesinde tam size göre ayarlanır.</p>',
            "Ceket + alt takım\nFermuarlı cepler\nDar paça, paça fermuarı\nHafif örme kumaş", 4499.90, 5199.90, 40, $S, 'esofman', $R, 1],
        ['esofman-ceket', 'GS Takım Marşı Ceketi', 'Maç öncesi seremoni ceketi, parlak saten detaylı.',
            '<p>Futbolcuların sahaya çıkarken giydiği marş ceketinin taraftar versiyonu. Saten görünümlü kumaşı, işlemeli arması ve yüksek yakasıyla tribünlerin en şık parçası.</p>',
            "Yüksek yaka\nİşlemeli arma\nSaten görünümlü kumaş\nİç cep", 3899.90, null, 25, $S, 'esofman', $A, 0],
        ['sweatshirt', 'GS Kapüşonlu Sweatshirt', 'İçi şardonlu, kanguru cepli kalın hoodie.',
            '<p>Soğuk maç akşamlarının vazgeçilmezi. İçi yumuşak şardonlu, 380 gr/m² kalın dokulu kumaşı ile sıcak tutar. Ön kanguru cebi ve ayarlanabilir kapüşon bağcığı sayesinde kullanımı pratiktir.</p>',
            "380 gr/m² kalın kumaş\nİçi şardonlu\nKanguru cep\nAyarlanabilir kapüşon", 2799.90, null, 70, $S, 'kapuson', $R, 1],
        ['sweatshirt', 'GS Yarım Fermuarlı Antrenman Üstü', 'İnce, esnek ve başparmak delikli ısınma üstü.',
            '<p>Serin havada antrenman ve koşular için yarım fermuarlı, ince ve esnek üst. Başparmak delikli kolları ellerinizi sıcak tutar, yansıtıcı logosu akşam koşularında görünürlük sağlar.</p>',
            "Yarım fermuar\nBaşparmak delikli kol\nYansıtıcı logo\nHızlı kuruma", 2549.90, 2899.90, 35, $S, 'kapuson', $A, 0],
        ['mont', 'GS Tribün Montu', 'Su geçirmez, kapüşonlu ve içi polarlı kışlık mont.',
            '<p>Yağmurda, karda, ayazda tribünden kalkmayanlar için. Su geçirmez dış yüzeyi, bantlanmış dikişleri ve içi polar astarıyla soğuk kış maçlarında bile sıcak ve kuru kalırsınız.</p><p>Çıkarılabilir kapüşonu, fermuarlı iç cebi ve rüzgâr geçirmez kol manşetleriyle tam koruma sağlar.</p>',
            "Su geçirmez (5.000 mm)\nİçi polar astar\nÇıkarılabilir kapüşon\nBantlanmış dikişler", 6999.90, 7999.90, 22, $S, 'mont', $A, 1],
        ['mont', 'GS Yağmurluk', 'Hafif, katlanabilir ve su itici yağmurluk.',
            '<p>Çantanızda yer kaplamayan, kendi cebine katlanabilen ultra hafif yağmurluk. Su itici yüzeyi ani yağmurlarda sizi korur.</p>',
            "Kendi cebine katlanır\nSu itici kaplama\nAyarlanabilir kapüşon\n180 gram", 2699.90, null, 30, $S, 'mont', $Y, 0],
        ['ayakkabi', 'GS Strike FG Krampon', 'Doğal çim için hafif, çekişi yüksek krampon.',
            '<p>Hızlı ve çevik oyuncular için geliştirilen Strike FG, doğal çim sahalarda maksimum çekiş sağlayan konik ve bıçak karışımı dişlere sahiptir.</p><p>İnce ve dokulu sentetik sayası, topla temas anında mükemmel bir top hissi verir. Çorap tipi bilek yapısı ayağı sararak denge sağlar.</p>',
            "Doğal çim (FG) taban\nDokulu sentetik saya\nÇorap tipi bilek\n195 gram", 5499.90, 6299.90, 28, $N, 'ayakkabi', $R, 1],
        ['ayakkabi', 'GS Halı Saha Ayakkabısı', 'Suni çim sahalar için kauçuk tabanlı ayakkabı.',
            '<p>Halı saha maçları için tasarlanan ayakkabının çok sayıda küçük kauçuk dişe sahip tabanı, suni çimde dengeli tutuş sağlar. Yastıklamalı iç taban uzun maçlarda bile konfor sunar.</p>',
            "Suni çim (TF) taban\nYastıklamalı iç taban\nGüçlendirilmiş burun\nNefes alan saya", 3699.90, null, 40, $N, 'ayakkabi', $A, 0],
        ['ayakkabi', 'GS Run Koşu Ayakkabısı', 'Hafif, amortisörlü günlük koşu ayakkabısı.',
            '<p>Günlük koşu ve antrenmanlar için hafif ve esnek. Köpük orta taban her adımda darbeyi emer, örme üst yüzey ayağın nefes almasını sağlar.</p>',
            "Köpük amortisör\nÖrme üst yüzey\nYansıtıcı detaylar\n240 gram", 4299.90, 4799.90, 4, $N, 'ayakkabi', $Y, 0],
        ['ekipman', 'GS Resmi Maç Topu', 'FIFA Quality Pro standartlarında maç topu.',
            '<p>Profesyonel maçlarda kullanılan standartlarda üretilen resmi maç topu. Isıyla birleştirilmiş paneller su almayı önler, dokulu yüzey isabetli vuruş ve kontrol sağlar.</p>',
            "5 numara\nIsıyla birleştirilmiş paneller\nDokulu yüzey\nFIFA Quality Pro", 2599.90, null, 50, '', 'top', $R, 1],
        ['ekipman', 'GS Spor Çantası 45 L', 'Ayakkabı bölmeli, su geçirmez tabanlı spor çanta.',
            '<p>Antrenman ve seyahat için 45 litre hacimli çanta. Ayrı havalandırmalı ayakkabı bölmesi, su geçirmez taban ve dolgulu omuz askısı ile tüm ekipmanınız düzenli ve güvende.</p>',
            "45 litre\nHavalandırmalı ayakkabı bölmesi\nSu geçirmez taban\nDolgulu omuz askısı", 2749.90, 3199.90, 33, '', 'canta', $A, 0],
        ['ekipman', 'GS Antrenman Seti (Top + Çanta + Matara)', 'Başlangıç için her şey bir arada.',
            '<p>Antrenman topu, spor çantası ve 750 ml çelik mataradan oluşan avantajlı set. Hediye kutusunda gönderilir; sporsever dostlarınız için ideal bir seçimdir.</p>',
            "Antrenman topu (5 no)\n30 L spor çanta\n750 ml çelik matara\nHediye kutusu", 3999.90, 4699.90, 15, '', 'canta', $Y, 0],
    ];
}

/** Raporlar ve aktivite akışı boş görünmesin diye son 21 güne yayılmış örnek siparişler */
function seed_demo_orders(array $products, array $uid): void
{
    $names = ['Ahmet Yılmaz', 'Zeynep Kaya', 'Mehmet Demir', 'Elif Şahin', 'Can Öztürk', 'Burak Aydın', 'Merve Arslan', 'Emre Koç', 'Seda Kurt', 'Oğuz Çelik', 'Gizem Aksoy', 'Kerem Polat', 'Derya Yıldız', 'Hakan Özdemir'];
    $cities = [['İstanbul', 'Kadıköy'], ['İstanbul', 'Beşiktaş'], ['Ankara', 'Çankaya'], ['İzmir', 'Bornova'], ['Bursa', 'Nilüfer'], ['Antalya', 'Muratpaşa'], ['Trabzon', 'Ortahisar'], ['Eskişehir', 'Tepebaşı']];
    $staff = array_merge($uid['satis'] ?? [], $uid['admin'] ?? []);
    $staffNames = [];
    foreach ($staff as $sid) {
        $staffNames[$sid] = val('SELECT name FROM users WHERE id = ?', [$sid]);
    }
    $member = $uid['uye'][0] ?? null;

    for ($day = 20; $day >= 0; $day--) {
        $count = $day === 0 ? 3 : random_int(1, 5);
        for ($k = 0; $k < $count; $k++) {
            $created = date('Y-m-d H:i:s', strtotime("-$day days") - random_int(0, $day === 0 ? 3 * 3600 : 12 * 3600));
            if ($day === 0) {
                $created = date('Y-m-d') . ' ' . sprintf('%02d:%02d:00', min((int) date('H'), 9 + $k * 2), random_int(0, 59));
            }
            $name = $names[array_rand($names)];
            [$city, $district] = $cities[array_rand($cities)];
            $lines = [];
            foreach ((array) array_rand($products, random_int(1, 2)) as $pi) {
                $p = $products[$pi];
                $sizes = array_filter(explode(',', $p['sizes']));
                $lines[] = [$p, $sizes ? $sizes[array_rand($sizes)] : '', random_int(1, 2)];
            }
            $subtotal = array_sum(array_map(fn($l) => $l[0]['price'] * $l[2], $lines));
            $shipping = $subtotal >= 5000 ? 0 : 149.90;
            $age = $day;
            $status = match (true) {
                $age === 0 => 'yeni',
                $age <= 1 => ['yeni', 'hazirlaniyor'][random_int(0, 1)],
                $age <= 3 => ['hazirlaniyor', 'kargoda'][random_int(0, 1)],
                default => random_int(1, 12) === 1 ? 'iptal' : (random_int(1, 15) === 1 ? 'iade' : 'teslim'),
            };
            $rep = $staff ? $staff[array_rand($staff)] : null;
            $orderNo = 'GS' . date('ymd', strtotime($created)) . random_int(1000, 9999);
            $oid = insert('orders', [
                'order_no' => $orderNo, 'user_id' => ($k === 0 && $day % 6 === 0) ? $member : null,
                'customer_name' => $name, 'email' => slugify(explode(' ', $name)[0]) . random_int(10, 99) . '@ornek.com', 'phone' => '05' . random_int(30, 55) . ' ' . random_int(100, 999) . ' ' . random_int(10, 99) . ' ' . random_int(10, 99),
                'city' => $city, 'district' => $district, 'address' => 'Örnek Mah. Spor Cad. No:' . random_int(1, 120) . ' D:' . random_int(1, 20),
                'subtotal' => $subtotal, 'shipping' => $shipping, 'total' => $subtotal + $shipping, 'status' => $status,
                'cargo_company' => in_array($status, ['kargoda', 'teslim', 'iade'], true) ? CARGO_COMPANIES[array_rand(CARGO_COMPANIES)] : null,
                'tracking_no' => in_array($status, ['kargoda', 'teslim', 'iade'], true) ? (string) random_int(100000000000, 999999999999) : null,
                'assigned_to' => $status === 'yeni' ? null : $rep, 'card_last4' => (string) random_int(1000, 9999),
                'created_at' => $created, 'updated_at' => $created,
            ]);
            foreach ($lines as [$p, $size, $qty]) {
                insert('order_items', ['order_id' => $oid, 'product_id' => $p['id'], 'name' => $p['name'], 'size' => $size, 'price' => $p['price'], 'qty' => $qty]);
            }
            insert('order_history', ['order_id' => $oid, 'user_name' => $name, 'text' => 'Sipariş oluşturuldu, ödeme alındı', 'created_at' => $created]);
            insert('activity', ['user_id' => null, 'user_name' => $name, 'role' => 'ziyaretci', 'action' => 'Sipariş verdi',
                'details' => $orderNo . ' · ' . money($subtotal + $shipping), 'link' => 'admin/siparis.php?id=' . $oid, 'ip' => '85.10' . random_int(0, 9) . '.' . random_int(1, 254) . '.' . random_int(1, 254), 'created_at' => $created]);
            if ($status !== 'yeni' && $rep) {
                $later = date('Y-m-d H:i:s', min(time(), strtotime($created) + random_int(1800, 6 * 3600)));
                $text = 'Durum: ' . ORDER_STATUSES[$status][0];
                insert('order_history', ['order_id' => $oid, 'user_name' => $staffNames[$rep], 'text' => $text, 'created_at' => $later]);
                insert('activity', ['user_id' => $rep, 'user_name' => $staffNames[$rep], 'role' => val('SELECT role FROM users WHERE id = ?', [$rep]),
                    'action' => 'Sipariş durumunu güncelledi', 'details' => $orderNo . ' → ' . ORDER_STATUSES[$status][0], 'link' => 'admin/siparis.php?id=' . $oid, 'ip' => '10.0.0.' . random_int(2, 40), 'created_at' => $later]);
            }
        }
    }

    // Örnek talepler
    $reqs = [
        ['ariza', 'Burak Aydın', 'GS Strike FG Krampon - taban ayrılması', 'İki maç sonra sol ayakkabının burnunda taban ayrılması oluştu.', 'serviste', '{"siparis_no":"GS-ornek","urun":"GS Strike FG Krampon"}'],
        ['iletisim', 'Zeynep Kaya', 'Beden değişimi', 'Eşofman takımını M yerine L beden ile değiştirmek istiyorum.', 'yeni', null],
        ['kvkk', 'Can Öztürk', 'Kişisel verilerimin silinmesi', 'Üyeliğimin ve kişisel verilerimin silinmesini talep ediyorum.', 'inceleniyor', '{"talep_turu":"Silme / yok etme"}'],
        ['ik', 'Seda Kurt', 'Müşteri Temsilcisi', 'E-ticarette 3 yıllık müşteri hizmetleri deneyimim var.', 'yeni', '{"pozisyon":"Müşteri Temsilcisi"}'],
    ];
    foreach ($reqs as $i => [$type, $name, $subject, $msg, $status, $extra]) {
        $at = date('Y-m-d H:i:s', strtotime('-' . ($i + 1) . ' days 10:00'));
        insert('requests', ['ticket_no' => REQUEST_TYPES[$type][1] . '-' . date('ymd', strtotime($at)) . '-' . (100 + $i), 'type' => $type, 'name' => $name,
            'email' => slugify(explode(' ', $name)[0]) . '@ornek.com', 'phone' => '0532 000 00 0' . $i, 'subject' => $subject, 'message' => $msg, 'extra' => $extra,
            'status' => $status, 'reply' => $status === 'serviste' ? 'Ürününüz teknik servise ulaştı, inceleme 3-5 iş günü sürecektir.' : null,
            'created_at' => $at, 'updated_at' => $at]);
    }
}
