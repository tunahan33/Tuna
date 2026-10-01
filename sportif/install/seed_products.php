<?php
/** Başlangıç kataloğu: kategoriler ve ürünler (fiyatlar KDV dahildir). Panelden düzenlenebilir. */

$S = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
$SA = ['S', 'M', 'L', 'XL', 'XXL'];
$K = ['6-8 Yaş', '9-11 Yaş', '12-14 Yaş'];
$red = ['Kırmızı', '#C8102E'];
$yel = ['Sarı', '#FDB913'];
$ant = ['Antrasit', '#2B2F33'];
$wht = ['Beyaz', '#F4F5F6'];
$blk = ['Siyah', '#111315'];
$gry = ['Gri Melanj', '#9AA0A6'];

return [
    'categories' => [
        ['formalar', 'Formalar', 'Maç ve antrenman formaları, isim-numara baskı seçeneğiyle.', '#C8102E'],
        ['antrenman', 'Antrenman Giyim', 'Nefes alan, hızlı kuruyan tişört ve atletler.', '#FDB913'],
        ['esofman', 'Eşofman Takımları', 'Isınma, seyahat ve günlük kullanım için eşofmanlar.', '#2B2F33'],
        ['sort-tayt', 'Şort & Tayt', 'Hareket özgürlüğü sağlayan şort ve taytlar.', '#C8102E'],
        ['sweatshirt', 'Sweatshirt & Hoodie', 'Soğuk havalarda antrenman öncesi ve sonrası.', '#FDB913'],
        ['aksesuar', 'Aksesuar', 'Çorap, çanta, şapka ve daha fazlası.', '#2B2F33'],
    ],
    'products' => [
        ['formalar', 'GS Pro Maç Forması', 'Hafif, nefes alan kumaştan profesyonel maç forması. İsim ve numara baskısı eklenebilir.',
            '<p>Profesyonel kulüplerin tercih ettiği teknik kumaş yapısıyla üretilen maç forması, yoğun tempoda bile teri hızla dışarı atar ve vücudu kuru tutar.</p><p>Sarı-kırmızı diyagonal şerit detayı ve antrasit yaka ile GS kimliğini taşır. Sipariş sırasında forma arkasına isim ve numara baskısı ekleyebilirsiniz.</p><h3>Kumaş ve bakım</h3><ul><li>%100 geri dönüştürülmüş polyester, 140 gr/m²</li><li>30°C\'de ters çevirerek yıkayın, ütülemeyin</li></ul>',
            "Nem transferli teknik kumaş\nGeri dönüştürülmüş polyester\nİsim-numara baskı seçeneği\nAtletik kesim", 'unisex', 649.90, 799.90, 'jersey', '#C8102E', 1, 150, 1, $S, [$red, $yel], 12],
        ['formalar', 'GS Pro Kaleci Forması', 'Dirsek pedli, uzun kollu kaleci forması.', '<p>Dirsek bölgesinde darbe emici ped bulunan uzun kollu kaleci forması. Esnek kumaşı sayesinde atlayış ve uzanmalarda kısıtlama yapmaz.</p>',
            "Dirsek koruma pedi\nUzun kol, esnek kumaş\nİsim-numara baskı seçeneği", 'unisex', 749.90, null, 'jersey', '#2B2F33', 1, 150, 0, $S, [$ant, $yel], 6],
        ['formalar', 'GS Çocuk Maç Forması', 'Küçük sporcular için yumuşak dokulu maç forması.', '<p>Çocukların hassas cildi için yumuşak dokulu, terletmeyen kumaş. Okul ve kulüp takımları için idealdir.</p>',
            "Yumuşak dokulu kumaş\nÇocuk kalıbı\nİsim-numara baskı seçeneği", 'cocuk', 449.90, 529.90, 'jersey', '#FDB913', 1, 120, 0, $K, [$red, $yel], 10],
        ['antrenman', 'Dry-Fit Antrenman Tişörtü', 'Hızlı kuruyan, hafif antrenman tişörtü.', '<p>Günlük antrenmanların vazgeçilmezi. Nem transfer teknolojisi ile teri hızla buharlaştırır, dikişsiz omuz yapısı sürtünmeyi azaltır.</p>',
            "Hızlı kuruma\nDikişsiz omuz\nYansıtıcı logo", 'unisex', 349.90, null, 'tshirt', '#2B2F33', 0, 0, 1, $S, [$ant, $red, $wht], 15],
        ['antrenman', 'Performans Atlet', 'Yoğun antrenmanlar için geniş kol oyuntulu atlet.', '<p>Fitness, koşu ve kondisyon çalışmaları için geniş kol oyuntulu, hafif atlet.</p>',
            "Geniş kol oyuntusu\nHafif kumaş\nAnti-bakteriyel", 'erkek', 279.90, null, 'tank', '#C8102E', 0, 0, 0, $SA, [$red, $blk], 10],
        ['antrenman', 'Kadın Crop Antrenman Üstü', 'Esnek, vücuda oturan kadın antrenman üstü.', '<p>Yoga, pilates ve fitness için esnek, vücudu saran kesim. Yumuşak elastik bant ile hareket sırasında yerinde kalır.</p>',
            "4 yönlü esneme\nVücuda oturan kesim\nYumuşak elastik bant", 'kadin', 299.90, 359.90, 'tank', '#FDB913', 0, 0, 0, ['XS', 'S', 'M', 'L', 'XL'], [$yel, $blk], 8],
        ['esofman', 'GS Takım Eşofmanı', 'Fermuarlı ceket ve dar paça alttan oluşan eşofman takımı.', '<p>Isınma, seyahat ve maç öncesi için tasarlanmış takım eşofmanı. Fermuarlı cepler, dar paça ve ayarlanabilir bel bağcığı.</p><p>Kulüp ve okul takımları için toplu sipariş indirimi uygulanır.</p>',
            "Ceket + alt takım\nFermuarlı cepler\nDar paça kesim\nToplu siparişe uygun", 'unisex', 1199.90, 1399.90, 'jacket', '#2B2F33', 0, 0, 1, $S, [$ant, $red], 8],
        ['esofman', 'Çocuk Eşofman Takımı', 'Okul ve kulüp için dayanıklı çocuk eşofmanı.', '<p>Dayanıklı ve kolay yıkanabilir kumaşı ile çocuklar için günlük kullanıma uygun eşofman takımı.</p>',
            "Dayanıklı kumaş\nKolay bakım\nÇocuk kalıbı", 'cocuk', 799.90, null, 'jacket', '#C8102E', 0, 0, 0, $K, [$red, $ant], 8],
        ['esofman', 'Rüzgarlık Ceket', 'Su itici, hafif ve katlanabilir rüzgarlık.', '<p>Yağmurlu ve rüzgarlı havalarda antrenman için su itici, ultra hafif rüzgarlık. Kendi cebine katlanabilir.</p>',
            "Su itici yüzey\nKatlanabilir\nYansıtıcı detaylar", 'unisex', 899.90, null, 'jacket', '#FDB913', 0, 0, 0, $SA, [$yel, $ant], 6],
        ['sort-tayt', 'Maç Şortu', 'Formayla uyumlu hafif maç şortu.', '<p>Pro Maç Forması ile kombinlenen hafif ve nefes alan maç şortu. İç astarlı, bağcıklı bel.</p>',
            "İç astar\nBağcıklı bel\nForma ile uyumlu", 'unisex', 299.90, null, 'shorts', '#2B2F33', 1, 75, 1, $S, [$ant, $wht, $red], 12],
        ['sort-tayt', 'Koşu Şortu 2\'si 1 Arada', 'İç taytlı, telefon cepli koşu şortu.', '<p>İç kısımda kompresyon tayt bulunan, telefon cepli koşu şortu. Uzun koşularda sürtünmeyi önler.</p>',
            "İç kompresyon tayt\nTelefon cebi\nYansıtıcı detay", 'erkek', 449.90, 529.90, 'shorts', '#C8102E', 0, 0, 0, $SA, [$red, $blk], 7],
        ['sort-tayt', 'Kadın Yüksek Bel Tayt', 'Toparlayıcı, yüksek bel antrenman taytı.', '<p>Yüksek bel tasarımı ve toparlayıcı kumaşıyla squat-proof antrenman taytı. Yan cepli.</p>',
            "Yüksek bel\nSquat-proof kumaş\nYan cep", 'kadin', 549.90, null, 'leggings', '#2B2F33', 0, 0, 0, ['XS', 'S', 'M', 'L', 'XL'], [$ant, $blk], 9],
        ['sweatshirt', 'GS Kapüşonlu Sweatshirt', 'İçi şardonlu, kanguru cepli hoodie.', '<p>Soğuk havalarda antrenman öncesi ve sonrası için içi şardonlu, kalın dokulu kapüşonlu sweatshirt.</p>',
            "İçi şardonlu\nKanguru cep\nAyarlanabilir kapüşon", 'unisex', 899.90, 1049.90, 'hoodie', '#C8102E', 0, 0, 1, $S, [$red, $ant, $gry], 9],
        ['sweatshirt', 'Yarım Fermuarlı Antrenman Üstü', 'İnce, uzun kollu ısınma üstü.', '<p>Isınma ve serin hava antrenmanları için yarım fermuarlı, ince ve esnek üst.</p>',
            "Yarım fermuar\nBaşparmak delikli kol\nİnce ve esnek", 'unisex', 599.90, null, 'hoodie', '#2B2F33', 0, 0, 0, $SA, [$ant, $yel], 7],
        ['aksesuar', 'Spor Çorap 3\'lü Paket', 'Tabanı destekli, terletmeyen spor çorap.', '<p>Taban bölgesi destekli, terletmeyen pamuk karışımlı spor çorap. 3 çift bir arada.</p>',
            "3 çift\nTaban destek\nTerletmeyen yapı", 'unisex', 179.90, null, 'socks', '#FDB913', 0, 0, 0, ['36-39', '40-43', '44-46'], [$wht, $blk], 25],
        ['aksesuar', 'GS Spor Çantası', 'Ayakkabı bölmeli, su geçirmez tabanlı spor çanta.', '<p>Ayrı ayakkabı bölmesi, su geçirmez taban ve ayarlanabilir omuz askısı ile antrenman ve seyahat çantası. 40 litre hacim.</p>',
            "40 litre\nAyakkabı bölmesi\nSu geçirmez taban", 'unisex', 699.90, 799.90, 'bag', '#2B2F33', 0, 0, 0, ['Standart'], [$ant, $red], 10],
        ['aksesuar', 'GS Şapka', 'Ayarlanabilir arkalıklı nakış logolu şapka.', '<p>Nakış logolu, nefes alan, ayarlanabilir arkalıklı spor şapka.</p>',
            "Nakış logo\nAyarlanabilir\nNefes alan", 'unisex', 229.90, null, 'cap', '#C8102E', 0, 0, 0, ['Standart'], [$red, $ant, $yel], 15],
    ],
];
