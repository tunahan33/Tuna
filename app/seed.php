<?php
/**
 * Başlangıç içeriği: danışmanlık hizmetleri, paketler ve kurumsal/yasal sayfalar.
 * Kurulumdan sonra tüm içerik yönetim panelinden düzenlenebilir.
 * Fiyatlar TL ve KDV dahildir.
 */
function seed_data(): array
{
    $services = [
        [
            'slug' => 'sporcu-performans-danismanligi',
            'title' => 'Sporcu Performans Danışmanlığı',
            'icon' => 'bolt',
            'short' => 'Bilimsel testlere dayalı, branşınıza özel antrenman planlama ve performans takibi.',
            'description' => '<p>Sporcu performans danışmanlığı; kuvvet, sürat, dayanıklılık, çeviklik ve toparlanma parametrelerinin ölçülerek branşa ve bireye özel bir gelişim planına dönüştürülmesidir. Uzman antrenörlerimiz ve spor bilimcilerimiz, başlangıç testleriyle mevcut durumunuzu belirler, hedeflerinizi sezon takviminize göre periyotlar halinde planlar ve düzenli ölçümlerle ilerlemenizi raporlar.</p><p>Amatör sporculardan profesyonel liglerde mücadele eden oyunculara kadar her seviyeye uygun paketlerimizle; sakatlık riskini azaltırken sahadaki verimliliğinizi en üst seviyeye taşımayı hedefliyoruz.</p>',
            'packages' => [
                ['name' => 'Başlangıç', 'price' => 3500, 'duration' => '1 ay', 'short' => 'Performansını ölçmek ve doğru başlangıç yapmak isteyenler için.',
                 'description' => '<p>Başlangıç paketi, sporcunun mevcut fiziksel kapasitesini ortaya koyan temel test bataryası ve bu testlere göre hazırlanan 4 haftalık bireysel antrenman programından oluşur.</p><p>Program; ısınma, kuvvet, kondisyon ve toparlanma bloklarını içerir. Ay sonunda yapılan kontrol görüşmesiyle gelişiminiz değerlendirilir ve bir sonraki dönem için yol haritası çıkarılır.</p>',
                 'features' => ['Başlangıç performans testleri (5 parametre)', '4 haftalık kişisel antrenman programı', '2 adet online birebir görüşme (45 dk)', 'Haftalık mesaj desteği', 'Ay sonu gelişim raporu']],
                ['name' => 'Profesyonel', 'price' => 8900, 'duration' => '3 ay', 'short' => 'Sezona hazırlanan ve düzenli takip isteyen sporcular için.',
                 'description' => '<p>Profesyonel paket; 12 haftalık periyotlanmış antrenman planı, iki dönem ölçüm ve haftalık birebir takip içerir. Branşınızın ve pozisyonunuzun gerektirdiği özel yetiler (ör. sprint tekrar kapasitesi, sıçrama gücü) hedefli olarak geliştirilir.</p><p>Her 4 haftada bir program güncellenir; antrenman yükü, uyku ve toparlanma verileri takip edilerek aşırı yüklenme ve sakatlık riski minimize edilir.</p>',
                 'features' => ['Kapsamlı test bataryası (başlangıç + ara + final)', '12 haftalık periyotlanmış program', 'Haftalık online birebir görüşme', 'Antrenman yükü ve toparlanma takibi', 'Sakatlık önleme protokolü', 'Aylık detaylı performans raporu']],
                ['name' => 'Elit', 'price' => 16500, 'duration' => '6 ay', 'short' => 'Profesyonel kariyer hedefleyen sporcular için tam kapsamlı takip.',
                 'description' => '<p>Elit paket, profesyonel seviyede yarışan ya da bu seviyeye geçmeyi hedefleyen sporculara yönelik 6 aylık bütünleşik bir programdır. Yüz yüze saha ölçümleri, video analiz ve beslenme uzmanımızla koordineli çalışma içerir.</p><p>Sezon takviminize göre hazırlık, müsabaka ve geçiş dönemleri planlanır; sporcu, antrenör ve kulübü arasında ortak bir raporlama dili oluşturulur.</p>',
                 'features' => ['Yüz yüze saha ölçümleri (3 dönem)', '6 aylık sezon periyotlaması', 'Haftada 2 birebir görüşme', 'Video ile teknik-hareket analizi', 'Beslenme uzmanı ile koordinasyon', '7/24 öncelikli mesaj desteği', 'Kulüp/antrenöre özel raporlama']],
            ],
        ],
        [
            'slug' => 'spor-beslenme-danismanligi',
            'title' => 'Spor Beslenme Danışmanlığı',
            'icon' => 'apple',
            'short' => 'Antrenman ve müsabaka dönemlerine göre kişiye özel beslenme ve hidrasyon planları.',
            'description' => '<p>Doğru beslenme, antrenmanın kazanımlarını sahaya taşımanın en kısa yoludur. Spor diyetisyenlerimiz vücut kompozisyonu analizi, enerji ihtiyacı hesaplaması ve yaşam tarzınıza göre uygulanabilir beslenme planları hazırlar.</p><p>Müsabaka öncesi, sırası ve sonrası beslenme stratejileri, hidrasyon protokolleri ve güvenli takviye kullanımı konularında bilimsel ve uygulanabilir rehberlik sunarız.</p>',
            'packages' => [
                ['name' => 'Başlangıç', 'price' => 2250, 'duration' => '1 ay', 'short' => 'Beslenme alışkanlıklarını sporuna göre düzenlemek isteyenler için.',
                 'description' => '<p>Başlangıç paketinde detaylı beslenme anamnezi ve vücut analizi sonrasında size özel 4 haftalık beslenme planı hazırlanır. Plan, antrenman ve dinlenme günlerine göre ayrı ayrı düzenlenir.</p>',
                 'features' => ['Beslenme anamnezi ve vücut analizi', '4 haftalık kişisel beslenme planı', 'Antrenman / dinlenme günü menüleri', '2 online kontrol görüşmesi', 'Alışveriş ve hazırlık rehberi']],
                ['name' => 'Profesyonel', 'price' => 5900, 'duration' => '3 ay', 'short' => 'Kilo/kompozisyon hedefi olan ve düzenli takip isteyen sporcular için.',
                 'description' => '<p>Profesyonel pakette 12 hafta boyunca 2 haftada bir plan güncellemesi yapılır. Müsabaka günü beslenmesi, hidrasyon protokolü ve takviye değerlendirmesi pakete dahildir.</p>',
                 'features' => ['3 aylık dinamik beslenme planı', '2 haftada bir plan güncellemesi', 'Müsabaka günü beslenme stratejisi', 'Hidrasyon protokolü', 'Takviye (supplement) değerlendirmesi', 'Haftalık mesaj desteği']],
                ['name' => 'Elit', 'price' => 10900, 'duration' => '6 ay', 'short' => 'Profesyonel sporcular ve kamp dönemleri için tam destek.',
                 'description' => '<p>Elit paket; kamp, deplasman ve yoğun maç takvimi dönemleri için özel planlar, performans antrenörüyle koordinasyon ve kan tahlili sonuçlarına dayalı mikro besin değerlendirmesi içerir.</p>',
                 'features' => ['6 aylık sezon boyu beslenme yönetimi', 'Kamp ve deplasman beslenme planları', 'Kan tahlili bazlı mikro besin analizi', 'Performans antrenörü ile koordinasyon', 'Haftalık birebir görüşme', 'Öncelikli 7/24 destek']],
            ],
        ],
        [
            'slug' => 'mental-performans-danismanligi',
            'title' => 'Mental Performans Danışmanlığı',
            'icon' => 'brain',
            'short' => 'Odaklanma, motivasyon ve baskı altında performans için spor psikolojisi desteği.',
            'description' => '<p>Kritik anlarda doğru kararı vermek, hatadan hızla sıyrılmak ve baskı altında sakin kalabilmek antrenmanla geliştirilebilen becerilerdir. Spor psikoloğu danışmanlarımız, sporcunun zihinsel güçlü yönlerini ve gelişim alanlarını belirleyerek yapılandırılmış bir mental antrenman programı uygular.</p><p>Hedef belirleme, imgeleme, nefes ve odak teknikleri, müsabaka öncesi rutinler ve sakatlık sonrası sahaya dönüş süreçleri çalışma alanlarımız arasındadır.</p>',
            'packages' => [
                ['name' => 'Başlangıç', 'price' => 2750, 'duration' => '1 ay', 'short' => 'Mental antrenmanla tanışmak isteyen sporcular için.',
                 'description' => '<p>4 seanslık bu pakette sporcunun mental profili çıkarılır; odak, özgüven ve kaygı yönetimi konularında temel teknikler öğretilir.</p>',
                 'features' => ['Mental performans profili değerlendirmesi', '4 birebir seans (50 dk)', 'Nefes ve odak teknikleri', 'Kişisel müsabaka öncesi rutin', 'Seans özet notları']],
                ['name' => 'Profesyonel', 'price' => 7200, 'duration' => '3 ay', 'short' => 'Sezon boyunca istikrarlı zihinsel performans için.',
                 'description' => '<p>12 seanslık program; hedef belirleme, imgeleme, baskı yönetimi ve takım içi iletişim modüllerinden oluşur. Maç/müsabaka sonrası değerlendirme görüşmeleri pakete dahildir.</p>',
                 'features' => ['12 birebir seans', 'Hedef belirleme ve imgeleme çalışmaları', 'Baskı ve kaygı yönetimi', 'Müsabaka sonrası değerlendirme', 'Kişisel mental antrenman günlüğü', 'Mesaj desteği']],
                ['name' => 'Elit', 'price' => 13500, 'duration' => '6 ay', 'short' => 'Profesyonel sporcular ve takımlar için kapsamlı program.',
                 'description' => '<p>Elit paket; 24 birebir seans, 2 takım atölyesi ve sakatlık sonrası sahaya dönüş desteğini kapsar. İstenirse antrenör ve aile bilgilendirme görüşmeleri planlanır.</p>',
                 'features' => ['24 birebir seans', '2 takım/grup atölyesi', 'Sakatlık sonrası sahaya dönüş desteği', 'Antrenör ve aile bilgilendirme görüşmeleri', 'Müsabaka günü erişilebilirlik', 'Öncelikli destek']],
            ],
        ],
        [
            'slug' => 'sporcu-kariyer-danismanligi',
            'title' => 'Sporcu Kariyer & Menajerlik Danışmanlığı',
            'icon' => 'briefcase',
            'short' => 'Transfer, sözleşme, imaj ve kariyer planlamasında profesyonel yol arkadaşlığı.',
            'description' => '<p>Sporcu kariyeri; doğru kulüp seçimi, adil sözleşme koşulları, dijital imaj ve kariyer sonrası planlama gibi pek çok kritik kararı içerir. Kariyer danışmanlarımız, sporcunun hedefleri doğrultusunda stratejik bir yol haritası hazırlar ve her adımda yanında olur.</p><p>Sözleşme süreçlerinde lisanslı hukuk danışmanlarıyla birlikte çalışır, sporcunun haklarını ve geleceğini koruyan kararlar almasına yardımcı oluruz.</p>',
            'packages' => [
                ['name' => 'Başlangıç', 'price' => 4500, 'duration' => '1 ay', 'short' => 'Kariyerine yön vermek isteyen genç sporcular için.',
                 'description' => '<p>Kariyer analizi, güçlü/zayıf yön değerlendirmesi ve 12 aylık kariyer yol haritası hazırlanır. Profesyonel sporcu özgeçmişi (CV) ve tanıtım dosyası oluşturulur.</p>',
                 'features' => ['Kariyer analizi ve hedef belirleme', '12 aylık kariyer yol haritası', 'Profesyonel sporcu CV\'si', 'Tanıtım dosyası (PDF)', '2 birebir görüşme']],
                ['name' => 'Profesyonel', 'price' => 12500, 'duration' => '3 ay', 'short' => 'Transfer dönemine hazırlanan sporcular için.',
                 'description' => '<p>Video derlemesi (highlight), kulüp/scout ağına tanıtım, sözleşme ön incelemesi ve sosyal medya imaj danışmanlığı içerir.</p>',
                 'features' => ['Profesyonel video derlemesi (highlight)', 'Kulüp ve scout ağına tanıtım', 'Sözleşme ön incelemesi', 'Sosyal medya imaj danışmanlığı', 'Görüşme ve mülakat hazırlığı', 'Haftalık takip görüşmesi']],
                ['name' => 'Elit', 'price' => 24000, 'duration' => '6 ay', 'short' => 'Profesyonel sporcular için uçtan uca kariyer yönetimi.',
                 'description' => '<p>Transfer müzakerelerine danışmanlık, hukuk ekibiyle sözleşme incelemesi, sponsorluk fırsatları ve kariyer sonrası (eğitim, yatırım) planlaması pakete dahildir.</p>',
                 'features' => ['Transfer müzakere danışmanlığı', 'Hukuk ekibiyle sözleşme incelemesi', 'Kişisel sponsorluk fırsatları', 'Medya ve röportaj eğitimi', 'Kariyer sonrası planlama', 'Kişisel danışman ataması']],
            ],
        ],
        [
            'slug' => 'kulup-yonetimi-danismanligi',
            'title' => 'Kulüp Yönetimi & Kurumsal Yapılanma',
            'icon' => 'shield',
            'short' => 'Spor kulüpleri için organizasyon, bütçe, altyapı ve sürdürülebilir yönetim modeli.',
            'description' => '<p>Başarılı kulüpler sahada olduğu kadar yönetim masasında da kazanır. Kulüp yönetimi danışmanlığımızla organizasyon şeması, görev tanımları, bütçe ve gelir modeli, altyapı sistemi ve dijital dönüşüm alanlarında sürdürülebilir bir yapı kurmanıza destek oluyoruz.</p><p>Amatör kulüplerden profesyonel spor kulüplerine, federasyon ve spor kuruluşlarına kadar ölçeğe uygun çözümler sunuyoruz.</p>',
            'packages' => [
                ['name' => 'Analiz', 'price' => 18000, 'duration' => '1 ay', 'short' => 'Kulübün mevcut durumunun kapsamlı fotoğrafı.',
                 'description' => '<p>Kulübün yönetim, mali yapı, sportif organizasyon ve iletişim alanlarında mevcut durum analizi yapılır; öncelikli aksiyonları içeren bir rapor ve sunum hazırlanır.</p>',
                 'features' => ['Yönetim ve organizasyon analizi', 'Mali yapı ve gelir kalemleri incelemesi', 'Paydaş görüşmeleri (5 kişiye kadar)', 'Öncelikli aksiyon raporu', 'Yönetim kuruluna sunum']],
                ['name' => 'Yapılanma', 'price' => 45000, 'duration' => '3 ay', 'short' => 'Organizasyon ve süreçlerin yeniden kurgulanması.',
                 'description' => '<p>Analiz paketine ek olarak organizasyon şeması, görev tanımları, bütçe modeli ve altyapı (akademi) sistemi tasarlanır; uygulama süreci aylık toplantılarla takip edilir.</p>',
                 'features' => ['Analiz paketi dahil', 'Organizasyon şeması ve görev tanımları', 'Bütçe ve gelir modeli', 'Altyapı/akademi sistemi tasarımı', 'Süreç ve raporlama şablonları', 'Aylık yönetim toplantısı']],
                ['name' => 'Kurumsal', 'price' => 95000, 'duration' => '6 ay', 'short' => 'Uçtan uca dönüşüm ve uygulama desteği.',
                 'description' => '<p>Kurumsal paket, yapılanma planının sahada uygulanmasına yerinde destek verir; dijital dönüşüm (üye/sporcu yönetimi, bilet, e-ticaret) ve marka stratejisini kapsar.</p>',
                 'features' => ['Yapılanma paketi dahil', 'Yerinde uygulama desteği (aylık 4 gün)', 'Dijital dönüşüm yol haritası', 'Marka ve iletişim stratejisi', 'Personel eğitimleri', 'Performans göstergeleri (KPI) paneli']],
            ],
        ],
        [
            'slug' => 'spor-tesisi-proje-danismanligi',
            'title' => 'Spor Tesisi Proje Danışmanlığı',
            'icon' => 'stadium',
            'short' => 'Saha, salon ve tesis yatırımlarında fizibilite, proje ve işletme danışmanlığı.',
            'description' => '<p>Bir spor tesisinin başarısı; doğru konum, doğru ölçek ve doğru işletme modeliyle başlar. Tesis proje danışmanlığımızla fizibilite ve pazar analizi, konsept tasarım, standartlara (federasyon, UEFA/FIBA vb.) uygunluk, yatırım bütçesi ve işletme planlamasında yanınızdayız.</p><p>Belediyeler, okullar, özel yatırımcılar ve kulüpler için futbol sahası, kapalı spor salonu, fitness merkezi ve çok amaçlı tesis projelerinde deneyimliyiz.</p>',
            'packages' => [
                ['name' => 'Fizibilite', 'price' => 25000, 'duration' => '3 hafta', 'short' => 'Yatırım kararı öncesi fizibilite ve pazar analizi.',
                 'description' => '<p>Konum, rakip ve talep analiziyle tesisin fizibilitesi değerlendirilir; yatırım tutarı ve geri dönüş süresi tahmini içeren rapor sunulur.</p>',
                 'features' => ['Konum ve talep analizi', 'Rakip tesis incelemesi', 'Yatırım tutarı tahmini', 'Geri dönüş (ROI) projeksiyonu', 'Fizibilite raporu ve sunumu']],
                ['name' => 'Proje', 'price' => 65000, 'duration' => '2 ay', 'short' => 'Konsept tasarım ve teknik şartname desteği.',
                 'description' => '<p>Fizibilite paketine ek olarak konsept yerleşim planı, federasyon standartlarına uygunluk kontrolü, zemin/aydınlatma/ekipman teknik şartnameleri hazırlanır.</p>',
                 'features' => ['Fizibilite paketi dahil', 'Konsept yerleşim planı', 'Standartlara uygunluk kontrolü', 'Zemin, aydınlatma, ekipman şartnameleri', 'Tedarikçi teklif karşılaştırması', 'Proje takvimi']],
                ['name' => 'Anahtar Teslim', 'price' => 140000, 'duration' => '6 ay', 'short' => 'Yatırımdan işletmeye tam süreç yönetimi.',
                 'description' => '<p>Proje paketine ek olarak uygulama sürecinde saha denetimi, açılış öncesi işletme planı, fiyatlandırma, personel yapısı ve pazarlama stratejisi hazırlanır.</p>',
                 'features' => ['Proje paketi dahil', 'Uygulama süreci saha denetimi', 'İşletme modeli ve fiyatlandırma', 'Personel yapısı ve eğitim', 'Açılış pazarlama stratejisi', 'Açılış sonrası 1 ay destek']],
            ],
        ],
        [
            'slug' => 'sponsorluk-ve-spor-pazarlama',
            'title' => 'Sponsorluk & Spor Pazarlama Danışmanlığı',
            'icon' => 'megaphone',
            'short' => 'Kulüp, sporcu ve markalar için sponsorluk stratejisi ve spor pazarlaması.',
            'description' => '<p>Spor, markalar için en güçlü duygusal bağ kurma alanlarından biridir. Sponsorluk ve spor pazarlama danışmanlığımızla kulüplerin ve sporcuların sponsorluk değerini ölçüyor, markalar için doğru eşleşmeleri buluyor ve ölçülebilir aktivasyon kampanyaları tasarlıyoruz.</p><p>Sponsorluk dosyası hazırlığından marka görüşmelerine, dijital içerik stratejisinden taraftar deneyimine kadar uçtan uca destek veriyoruz.</p>',
            'packages' => [
                ['name' => 'Başlangıç', 'price' => 12000, 'duration' => '1 ay', 'short' => 'Sponsorluk değerinizi ortaya koyun.',
                 'description' => '<p>Kulüp veya sporcunun medya, taraftar ve dijital erişim verileriyle sponsorluk değeri hesaplanır; profesyonel sponsorluk dosyası hazırlanır.</p>',
                 'features' => ['Sponsorluk değer analizi', 'Profesyonel sponsorluk dosyası', 'Hedef marka listesi (20 marka)', 'Sponsorluk paket kurgusu', '2 strateji görüşmesi']],
                ['name' => 'Profesyonel', 'price' => 32000, 'duration' => '3 ay', 'short' => 'Marka görüşmeleri ve dijital strateji.',
                 'description' => '<p>Başlangıç paketine ek olarak markalarla tanıştırma ve görüşme süreci yönetilir; dijital içerik ve sosyal medya stratejisi hazırlanır.</p>',
                 'features' => ['Başlangıç paketi dahil', 'Marka tanıştırma ve görüşme yönetimi', 'Sponsorluk sözleşmesi ön incelemesi', 'Dijital içerik ve sosyal medya stratejisi', 'Aylık raporlama']],
                ['name' => 'Kurumsal', 'price' => 68000, 'duration' => '6 ay', 'short' => 'Aktivasyon kampanyaları ve ölçümleme.',
                 'description' => '<p>Sponsorluk aktivasyon kampanyaları, taraftar deneyimi projeleri ve yatırım geri dönüşü (ROI) ölçümlemesi ile sponsorluk ilişkisinin sürdürülebilir hale gelmesi sağlanır.</p>',
                 'features' => ['Profesyonel paket dahil', 'Sponsorluk aktivasyon kampanyaları', 'Taraftar deneyimi projeleri', 'Sponsorluk ROI ölçümlemesi', 'Kriz iletişimi desteği', 'Kişisel proje yöneticisi']],
            ],
        ],
    ];

    $pages = [
        'hakkimizda' => ['Hakkımızda', <<<'HTML'
<p class="lead">{{site}}, sporun her alanında bilimsel, ölçülebilir ve sonuç odaklı danışmanlık hizmeti sunmak amacıyla kurulmuş bir spor danışmanlık şirketidir.</p>
<h2>Biz kimiz?</h2>
<p>Ekibimiz; spor bilimcileri, antrenörler, spor diyetisyenleri, spor psikologları, kulüp yöneticileri, mimar ve mühendisler ile pazarlama uzmanlarından oluşur. Sporcudan kulübe, tesis yatırımcısından markaya kadar sporun tüm paydaşlarına tek çatı altında hizmet veriyoruz.</p>
<h2>Misyonumuz</h2>
<p>Sporcuların potansiyellerini en üst düzeye çıkarmalarına, kulüplerin ve spor kuruluşlarının sürdürülebilir bir yapıya kavuşmalarına bilimsel yöntemlerle destek olmak.</p>
<h2>Vizyonumuz</h2>
<p>Türkiye'de spor danışmanlığı denildiğinde akla gelen ilk, en güvenilir ve en yenilikçi marka olmak.</p>
<h2>Değerlerimiz</h2>
<ul>
<li><strong>Bilimsellik:</strong> Her önerimiz ölçüm ve veriye dayanır.</li>
<li><strong>Şeffaflık:</strong> Hizmet kapsamı ve fiyatlarımız nettir; sürpriz maliyet yoktur.</li>
<li><strong>Gizlilik:</strong> Sporcu ve kurum verileri KVKK kapsamında titizlikle korunur.</li>
<li><strong>Takım ruhu:</strong> Danışanlarımızı ekibimizin bir parçası olarak görürüz.</li>
</ul>
<h2>Şirket Bilgileri</h2>
<table class="info-table">
<tr><th>Ticari Unvan</th><td>{{unvan}}</td></tr>
<tr><th>Adres</th><td>{{adres}}</td></tr>
<tr><th>Vergi Dairesi / No</th><td>{{vergi_dairesi}} / {{vergi_no}}</td></tr>
<tr><th>MERSİS No</th><td>{{mersis}}</td></tr>
<tr><th>Ticaret Sicil</th><td>{{sicil}}</td></tr>
<tr><th>Telefon</th><td>{{telefon}}</td></tr>
<tr><th>E-posta</th><td>{{eposta}}</td></tr>
<tr><th>KEP Adresi</th><td>{{kep}}</td></tr>
</table>
HTML],

        'insan-kaynaklari' => ['İnsan Kaynakları', <<<'HTML'
<p class="lead">Sporu seven, bilgiyle çalışan ve fark yaratmak isteyen ekip arkadaşları arıyoruz.</p>
<h2>Neden {{site}}?</h2>
<ul>
<li>Profesyonel sporcular, kulüpler ve markalarla doğrudan çalışma fırsatı</li>
<li>Sürekli eğitim ve sertifika programlarına destek</li>
<li>Esnek ve hibrit çalışma modeli</li>
<li>Performansa dayalı prim sistemi</li>
</ul>
<h2>Çalışma alanlarımız</h2>
<ul>
<li>Spor Bilimci / Kuvvet ve Kondisyon Antrenörü</li>
<li>Spor Diyetisyeni</li>
<li>Spor Psikoloğu</li>
<li>Kulüp Yönetimi ve Proje Uzmanı</li>
<li>Satış Temsilcisi / Müşteri İlişkileri</li>
<li>Dijital Pazarlama Uzmanı</li>
</ul>
<h2>Başvuru süreci</h2>
<p>Aşağıdaki formu doldurarak genel başvuru yapabilirsiniz. Başvurunuz İnsan Kaynakları ekibimiz tarafından incelenir; uygun bir pozisyon olduğunda sizinle iletişime geçilir. Başvuru kapsamında paylaştığınız kişisel veriler, <a href="/aydinlatma-metni">Genel Aydınlatma Metni</a> kapsamında yalnızca işe alım süreci için işlenir ve en fazla 1 yıl süreyle saklanır.</p>
HTML],

        'sss' => ['Sıkça Sorulan Sorular', <<<'HTML'
<details open><summary>Danışmanlık hizmetleri nasıl veriliyor?</summary><p>Hizmetlerimiz paket içeriğine göre online (görüntülü görüşme) veya yüz yüze verilir. Satın alma sonrası 1 iş günü içinde danışmanınız sizinle iletişime geçerek ilk görüşmeyi planlar.</p></details>
<details><summary>Fiyatlara KDV dahil mi?</summary><p>Evet. Sitemizde yer alan tüm fiyatlar Türk Lirası (₺) cinsinden olup KDV dahildir. Ek bir ücret talep edilmez.</p></details>
<details><summary>Hangi ödeme yöntemlerini kabul ediyorsunuz?</summary><p>Visa, Mastercard ve Troy logolu tüm kredi kartları ve banka kartları ile Garanti BBVA güvencesinde 3D Secure ödeme yapabilirsiniz.</p></details>
<details><summary>Kart bilgilerim güvende mi?</summary><p>Kart bilgileriniz sitemizde saklanmaz; ödeme işlemi 256 bit SSL şifreleme ile doğrudan Garanti BBVA'nın 3D Secure altyapısında gerçekleşir. Detaylar için <a href="/guvenli-alisveris">Güvenli Alışveriş</a> sayfasını inceleyebilirsiniz.</p></details>
<details><summary>Satın aldığım paketi iptal edebilir miyim?</summary><p>Hizmet ifasına başlanmadan önce 14 gün içinde cayma hakkınızı kullanabilirsiniz. Detaylar için <a href="/iade-kosullari">İade ve iade çeki koşulları</a> sayfasına bakabilirsiniz.</p></details>
<details><summary>Siparişimin durumunu nasıl öğrenirim?</summary><p><a href="/siparis-takibi">Sipariş takibi</a> sayfasından sipariş numaranız ve e-posta adresinizle ya da üye girişi yaparak "Hesabım" alanından siparişlerinizi görüntüleyebilirsiniz.</p></details>
<details><summary>Hizmetle ilgili bir sorun yaşarsam ne yapmalıyım?</summary><p><a href="/ariza-takibi">Arıza takibi</a> sayfasından destek kaydı oluşturabilir ve kaydınızın durumunu aynı sayfadan takip edebilirsiniz.</p></details>
<details><summary>Fatura kesiliyor mu?</summary><p>Tüm satışlarımız için e-Arşiv / e-Fatura düzenlenir ve ödeme sonrası e-posta adresinize gönderilir. Kurumsal fatura için sipariş sırasında şirket bilgilerinizi girebilirsiniz.</p></details>
<details><summary>Taksit imkânı var mı?</summary><p>Kampanya dönemlerinde bankanızın sunduğu taksit seçenekleri ödeme sayfasında görüntülenir.</p></details>
HTML],

        'siparis-takibi' => ['Sipariş Takibi', <<<'HTML'
<p>Siparişinizin güncel durumunu öğrenmek için sipariş onay e-postanızda yer alan <strong>sipariş numarasını</strong> ve siparişte kullandığınız <strong>e-posta adresini</strong> giriniz. Üye iseniz tüm siparişlerinizi <a href="/hesabim">Hesabım</a> sayfasından da görebilirsiniz.</p>
HTML],

        'ariza-takibi' => ['Arıza Takibi', <<<'HTML'
<p>Satın aldığınız danışmanlık hizmetiyle ilgili yaşadığınız her türlü sorun, aksaklık veya talep için destek kaydı oluşturabilirsiniz. Kaydınız en geç 1 iş günü içinde ilgili ekibimiz tarafından yanıtlanır. Size verilen <strong>kayıt numarası</strong> ile kaydınızın durumunu bu sayfadan takip edebilirsiniz.</p>
HTML],

        'iade-kosullari' => ['İade ve İade Çeki Koşulları', <<<'HTML'
<h2>1. Cayma hakkı</h2>
<p>6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, tüketici sıfatıyla satın aldığınız danışmanlık hizmetlerinde <strong>sözleşmenin kurulduğu tarihten itibaren 14 (on dört) gün</strong> içinde herhangi bir gerekçe göstermeksizin cayma hakkınızı kullanabilirsiniz.</p>
<p>Mesafeli Sözleşmeler Yönetmeliği'nin 15. maddesi uyarınca, cayma süresi dolmadan önce tüketicinin onayı ile ifasına başlanan hizmetlerde (ör. ilk danışmanlık görüşmesinin yapılması, kişiye özel program teslim edilmesi) cayma hakkı kullanılamaz.</p>
<h2>2. Cayma hakkının kullanılması</h2>
<p>Cayma bildiriminizi süre içinde <strong>{{eposta}}</strong> adresine e-posta göndererek, <a href="/ariza-takibi">Arıza takibi</a> sayfasından kayıt oluşturarak ya da {{adres}} adresine yazılı olarak iletebilirsiniz. Bildiriminizde sipariş numaranızı belirtmeniz yeterlidir.</p>
<h2>3. Ücret iadesi</h2>
<p>Cayma bildiriminin tarafımıza ulaşmasından itibaren en geç <strong>14 gün</strong> içinde, ödemenin yapıldığı kredi kartına / banka kartına tam iade yapılır. İade tutarının hesabınıza yansıma süresi bankanıza bağlı olarak değişebilir. Taksitli ödemelerde iade, bankanız tarafından taksitler halinde yapılır.</p>
<h2>4. Kısmi iade</h2>
<p>Hizmet ifasına başlandıktan sonra, danışanın haklı sebeplerle hizmeti sonlandırmak istemesi halinde, kullanılmamış seans/dönem bedeli üzerinden kısmi iade veya iade çeki seçenekleri sunulur.</p>
<h2>5. İade çeki koşulları</h2>
<ul>
<li>İade çeki, danışanın talebi üzerine nakit iade yerine düzenlenen ve {{site}} bünyesindeki tüm danışmanlık paketlerinde kullanılabilen bir alışveriş kredisidir.</li>
<li>İade çekinin tutarı, iade edilecek tutar kadardır; talep edilmesi halinde ek %5 bonus tanımlanabilir.</li>
<li>İade çeki düzenlendiği tarihten itibaren <strong>12 ay</strong> geçerlidir.</li>
<li>İade çeki kişiye özeldir, nakde çevrilemez ve üçüncü kişilere devredilemez.</li>
<li>İade çeki tutarından yüksek bir paket alınması halinde aradaki fark kredi kartı ile ödenir; düşük bir paket alınması halinde kalan tutar çekte saklı kalır.</li>
<li>İade çeki tercihi tamamen isteğe bağlıdır; tüketicinin yasal nakit iade hakkını ortadan kaldırmaz.</li>
</ul>
<h2>6. İptal edilen siparişler</h2>
<p>Ödemesi tamamlanmış ancak hizmet ifasına başlanmamış siparişler, danışanın talebiyle iptal edilerek ücret iadesi yapılır. Ödeme adımında tamamlanmayan siparişlerde kartınızdan herhangi bir tutar tahsil edilmez.</p>
HTML],

        'teslimat-kosullari' => ['Teslimat Koşulları', <<<'HTML'
<p>{{site}} tarafından sunulan hizmetler fiziksel ürün değil, <strong>danışmanlık hizmetidir</strong>. Bu nedenle kargo ile teslimat yapılmaz; hizmet aşağıdaki şekilde ifa edilir.</p>
<h2>1. Hizmetin başlaması</h2>
<p>Ödemeniz onaylandıktan sonra sipariş onayınız ve faturanız e-posta adresinize gönderilir. En geç <strong>1 iş günü</strong> içinde danışmanınız sizinle telefon veya e-posta yoluyla iletişime geçerek ilk görüşme tarihini birlikte planlar.</p>
<h2>2. Hizmetin ifa şekli</h2>
<ul>
<li><strong>Online görüşmeler:</strong> Güvenli görüntülü görüşme platformları üzerinden, bağlantı bilgisi görüşmeden önce e-posta ile iletilir.</li>
<li><strong>Yüz yüze görüşmeler / saha çalışmaları:</strong> Paket kapsamında belirtilen yerlerde, önceden planlanan tarihlerde yapılır.</li>
<li><strong>Dijital teslimatlar:</strong> Antrenman programı, beslenme planı, rapor ve benzeri çıktılar PDF formatında e-posta ile ve Hesabım alanı üzerinden teslim edilir.</li>
</ul>
<h2>3. Hizmet süresi</h2>
<p>Her paketin hizmet süresi paket açıklamasında belirtilmiştir (ör. 1 ay, 3 ay, 6 ay). Süre, ilk görüşmenin yapıldığı tarihten itibaren başlar.</p>
<h2>4. Erteleme</h2>
<p>Planlanmış görüşmeler en az 24 saat önceden haber verilerek ücretsiz olarak ertelenebilir. Danışmanımızdan kaynaklanan ertelemelerde görüşme ek süre verilerek telafi edilir.</p>
<h2>5. Teslimat sorunları</h2>
<p>Hizmetinize ilişkin herhangi bir gecikme veya sorun yaşamanız halinde <a href="/ariza-takibi">Arıza takibi</a> sayfasından kayıt oluşturabilir veya {{telefon}} numaralı telefondan bize ulaşabilirsiniz.</p>
HTML],

        'guvenli-alisveris' => ['Güvenli Alışveriş', <<<'HTML'
<p class="lead">{{site}} üzerinden yaptığınız tüm ödemeler, Garanti BBVA Sanal POS altyapısı ve 3D Secure doğrulaması ile korunmaktadır.</p>
<h2>256 bit SSL şifreleme</h2>
<p>Sitemizdeki tüm sayfalar SSL sertifikası ile şifrelenir. Tarayıcınızın adres çubuğundaki kilit simgesi, bilgilerinizin güvenli bir bağlantı üzerinden iletildiğini gösterir.</p>
<h2>3D Secure ile ödeme</h2>
<p>Ödeme sırasında kartınızı veren bankanın doğrulama ekranına yönlendirilirsiniz. Cep telefonunuza gelen tek kullanımlık şifreyi girmeden işlem tamamlanmaz. Böylece kartınız başkası tarafından kullanılamaz.</p>
<h2>Kart bilgileriniz saklanmaz</h2>
<p>Kart numaranız, son kullanma tarihi ve güvenlik kodu (CVV) <strong>sunucularımızda hiçbir şekilde kaydedilmez</strong>; bu bilgiler doğrudan Garanti BBVA'nın PCI-DSS sertifikalı ödeme altyapısına iletilir.</p>
<h2>Kabul edilen kartlar</h2>
<p>Visa, Mastercard ve Troy logolu tüm kredi kartları ve banka kartları ile ödeme yapabilirsiniz.</p>
<h2>Kişisel verilerin korunması</h2>
<p>Kişisel verileriniz 6698 sayılı KVKK kapsamında işlenir. Detaylar için <a href="/aydinlatma-metni">Genel Aydınlatma Metni</a> ve <a href="/gizlilik-politikasi">Gizlilik Politikası</a> sayfalarını inceleyebilirsiniz.</p>
<h2>Şüpheli işlem bildirimi</h2>
<p>Hesabınızda veya kartınızda şüpheli bir işlem fark etmeniz halinde derhal bankanızla ve {{telefon}} numarası üzerinden bizimle iletişime geçiniz.</p>
HTML],

        'iletisim' => ['İletişim', <<<'HTML'
<p>Sorularınız, iş birliği teklifleriniz ve danışmanlık talepleriniz için bize aşağıdaki kanallardan ulaşabilir ya da formu doldurabilirsiniz.</p>
HTML],

        'uyelik-sozlesmesi' => ['Üyelik Sözleşmesi', <<<'HTML'
<h2>1. Taraflar</h2>
<p>İşbu Üyelik Sözleşmesi ("Sözleşme"), {{adres}} adresinde mukim <strong>{{unvan}}</strong> ("{{site}}") ile {{alan_adi}} internet sitesine ("Site") üye olan kişi ("Üye") arasında, Üye'nin elektronik ortamda onay vermesiyle kurulmuştur.</p>
<h2>2. Konu</h2>
<p>Sözleşmenin konusu, Site'de sunulan hizmetlerden Üye'nin yararlanma şartlarının ve tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>
<h2>3. Üyelik şartları</h2>
<ul>
<li>Üye olabilmek için 18 yaşını doldurmuş olmak veya yasal temsilci onayına sahip olmak gerekir.</li>
<li>Üye, kayıt sırasında verdiği bilgilerin doğru ve güncel olduğunu kabul eder.</li>
<li>Üyelik hesabı kişiseldir; şifrenin gizliliğinden Üye sorumludur.</li>
</ul>
<h2>4. Tarafların hak ve yükümlülükleri</h2>
<ul>
<li>Üye, Site'yi hukuka ve genel ahlaka uygun şekilde kullanmayı kabul eder.</li>
<li>Üye, Site üzerinden satın aldığı hizmetlere ilişkin Ön Bilgilendirme Formu ve Mesafeli Satış Sözleşmesi'ni ayrıca onaylar.</li>
<li>{{site}}, Site içeriğinde ve hizmetlerde önceden bildirimde bulunarak değişiklik yapma hakkını saklı tutar.</li>
<li>{{site}}, Üye'nin kişisel verilerini KVKK ve Genel Aydınlatma Metni kapsamında işler.</li>
</ul>
<h2>5. Fikri mülkiyet</h2>
<p>Site'deki tüm içerik, logo, tasarım ve danışmanlık kapsamında teslim edilen programlar {{site}}'e aittir; izinsiz çoğaltılamaz ve ticari amaçla kullanılamaz.</p>
<h2>6. Sözleşmenin feshi</h2>
<p>Üye dilediği zaman üyeliğini sonlandırabilir. {{site}}, Sözleşme'ye aykırı kullanım halinde üyeliği askıya alma veya sonlandırma hakkına sahiptir.</p>
<h2>7. Uyuşmazlıkların çözümü</h2>
<p>İşbu Sözleşme'den doğan uyuşmazlıklarda Türkiye Cumhuriyeti kanunları uygulanır; Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</p>
<h2>8. Yürürlük</h2>
<p>Üye, kayıt formundaki onay kutusunu işaretleyerek Sözleşme'nin tüm maddelerini okuduğunu ve kabul ettiğini beyan eder.</p>
HTML],

        'aydinlatma-metni' => ['Genel Aydınlatma Metni', <<<'HTML'
<p>6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") uyarınca, veri sorumlusu sıfatıyla <strong>{{unvan}}</strong> olarak kişisel verilerinizi aşağıda açıklanan kapsamda işlemekteyiz.</p>
<h2>1. Veri sorumlusu</h2>
<p>{{unvan}} — {{adres}} — MERSİS: {{mersis}} — E-posta: {{eposta}} — KEP: {{kep}}</p>
<h2>2. İşlenen kişisel veriler</h2>
<ul>
<li><strong>Kimlik:</strong> Ad, soyad, T.C. kimlik numarası (fatura için)</li>
<li><strong>İletişim:</strong> E-posta, telefon, adres</li>
<li><strong>Müşteri işlem:</strong> Sipariş, fatura, talep ve şikâyet bilgileri</li>
<li><strong>İşlem güvenliği:</strong> IP adresi, oturum ve log kayıtları</li>
<li><strong>Sağlık verileri (özel nitelikli):</strong> Performans ve beslenme danışmanlığı kapsamında yalnızca açık rızanız ile</li>
<li><strong>Pazarlama:</strong> Çerez kayıtları, tercihleriniz (açık rızanız ile)</li>
</ul>
<h2>3. İşleme amaçları</h2>
<ul>
<li>Danışmanlık hizmetinin sunulması ve sözleşmenin ifası</li>
<li>Ödeme ve faturalandırma işlemlerinin yürütülmesi</li>
<li>Talep ve şikâyetlerin yönetilmesi</li>
<li>Yasal yükümlülüklerin yerine getirilmesi</li>
<li>Bilgi güvenliğinin sağlanması</li>
<li>Açık rızanız halinde kampanya ve bilgilendirme iletileri gönderilmesi</li>
</ul>
<h2>4. Hukuki sebepler ve toplama yöntemi</h2>
<p>Kişisel verileriniz; Site formları, e-posta, telefon ve ödeme altyapısı aracılığıyla elektronik ortamda toplanır ve KVKK m.5/2 (c) sözleşmenin ifası, (ç) hukuki yükümlülük, (f) meşru menfaat ile m.5/1 ve m.6 kapsamında açık rıza hukuki sebeplerine dayanılarak işlenir.</p>
<h2>5. Aktarım</h2>
<p>Kişisel verileriniz; ödeme işlemleri için Garanti BBVA'ya, faturalandırma için entegratör ve mali müşavirimize, yasal zorunluluk halinde yetkili kamu kurumlarına ve hizmetin ifası için iş ortağı danışmanlarımıza, KVKK m.8 ve m.9'a uygun şekilde aktarılabilir.</p>
<h2>6. Haklarınız</h2>
<p>KVKK m.11 uyarınca; verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, amacına uygun kullanılıp kullanılmadığını öğrenme, aktarıldığı üçüncü kişileri bilme, düzeltme, silme veya yok edilmesini isteme, itiraz etme ve zararın giderilmesini talep etme haklarına sahipsiniz.</p>
<p>Başvurularınızı <a href="/basvuru-formu">İlgili kişi başvuru formu</a> aracılığıyla, {{kep}} KEP adresine veya {{adres}} adresine yazılı olarak iletebilirsiniz. Başvurular en geç 30 gün içinde ücretsiz olarak sonuçlandırılır.</p>
HTML],

        'cerez-politikasi' => ['Çerez Politikası', <<<'HTML'
<p>{{site}} olarak, internet sitemizde size daha iyi bir deneyim sunmak amacıyla çerezler (cookies) kullanmaktayız. Bu politika, hangi çerezleri neden kullandığımızı ve tercihlerinizi nasıl yönetebileceğinizi açıklar.</p>
<h2>1. Çerez nedir?</h2>
<p>Çerezler, ziyaret ettiğiniz internet siteleri tarafından tarayıcınıza kaydedilen küçük metin dosyalarıdır.</p>
<h2>2. Kullandığımız çerez türleri</h2>
<table class="info-table">
<tr><th>Zorunlu çerezler</th><td>Oturum açma, sepet/ödeme ve güvenlik (CSRF) işlemleri için gereklidir. Devre dışı bırakılamaz.</td></tr>
<tr><th>Tercih çerezleri</th><td>Dil ve görünüm tercihleriniz gibi ayarları hatırlar.</td></tr>
<tr><th>Analitik çerezler</th><td>Siteyi nasıl kullandığınızı anonim olarak ölçerek hizmetlerimizi geliştirmemize yardımcı olur. Açık rızanız ile kullanılır.</td></tr>
<tr><th>Pazarlama çerezleri</th><td>İlgi alanlarınıza uygun içerik ve kampanyalar göstermek için kullanılır. Açık rızanız ile kullanılır.</td></tr>
</table>
<h2>3. Kullandığımız çerezler</h2>
<table class="info-table">
<tr><th>gsp_sid</th><td>Zorunlu — oturum çerezi, tarayıcı kapanınca silinir.</td></tr>
<tr><th>gsp_cookie_consent</th><td>Zorunlu — çerez tercihlerinizi 12 ay saklar.</td></tr>
</table>
<h2>4. Tercihlerinizi yönetme</h2>
<p>Zorunlu olmayan çerezlere ilişkin tercihlerinizi dilediğiniz zaman <a href="/cerez-tercihleri">Çerez tercihleri</a> sayfasından değiştirebilirsiniz. Ayrıca tarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz.</p>
HTML],

        'cerez-tercihleri' => ['Çerez Tercihleri', <<<'HTML'
<p>Aşağıdan hangi çerez kategorilerine izin verdiğinizi seçebilirsiniz. Zorunlu çerezler sitenin çalışması için gerekli olduğundan kapatılamaz. Detaylı bilgi için <a href="/cerez-politikasi">Çerez Politikası</a>'nı inceleyebilirsiniz.</p>
HTML],

        'basvuru-formu' => ['İlgili Kişi Başvuru Formu', <<<'HTML'
<p>6698 sayılı Kişisel Verilerin Korunması Kanunu'nun ("KVKK") 11. maddesinde sayılan haklarınıza ilişkin taleplerinizi, Veri Sorumlusuna Başvuru Usul ve Esasları Hakkında Tebliğ uyarınca aşağıdaki form ile iletebilirsiniz.</p>
<p>Başvurunuz, niteliğine göre en kısa sürede ve <strong>en geç 30 (otuz) gün</strong> içinde ücretsiz olarak sonuçlandırılacaktır. Kimliğinizin doğrulanması amacıyla ek bilgi talep edilebilir.</p>
<p>Alternatif olarak başvurunuzu ıslak imzalı şekilde {{adres}} adresine, ya da güvenli elektronik imza ile <strong>{{kep}}</strong> KEP adresine iletebilirsiniz.</p>
HTML],

        'mesafeli-satis-sozlesmesi' => ['Mesafeli Satış Sözleşmesi', <<<'HTML'
<h2>Madde 1 — Taraflar</h2>
<p><strong>SATICI (Hizmet Sağlayıcı)</strong><br>Unvan: {{unvan}}<br>Adres: {{adres}}<br>Telefon: {{telefon}}<br>E-posta: {{eposta}}<br>Vergi Dairesi / No: {{vergi_dairesi}} / {{vergi_no}}<br>MERSİS: {{mersis}}</p>
<p><strong>ALICI (Danışan)</strong><br>Ad Soyad / Unvan: {{alici_ad}}<br>Adres: {{alici_adres}}<br>Telefon: {{alici_telefon}}<br>E-posta: {{alici_eposta}}</p>
<h2>Madde 2 — Konu</h2>
<p>İşbu sözleşmenin konusu, ALICI'nın SATICI'ya ait {{alan_adi}} internet sitesi üzerinden elektronik ortamda siparişini verdiği aşağıda nitelikleri ve satış fiyatı belirtilen danışmanlık hizmetinin satışı ve ifası ile ilgili olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>
<h2>Madde 3 — Sözleşme konusu hizmet</h2>
<table class="info-table">
<tr><th>Sipariş No</th><td>{{siparis_no}}</td></tr>
<tr><th>Hizmet</th><td>{{hizmet}}</td></tr>
<tr><th>Paket</th><td>{{paket}}</td></tr>
<tr><th>Toplam Bedel (KDV dahil)</th><td>{{tutar}}</td></tr>
<tr><th>Ödeme Şekli</th><td>Kredi kartı / Banka kartı (3D Secure)</td></tr>
<tr><th>Sipariş Tarihi</th><td>{{tarih}}</td></tr>
</table>
<h2>Madde 4 — Hizmetin ifası</h2>
<p>Hizmet, ödemenin onaylanmasından itibaren en geç 1 iş günü içinde ALICI ile iletişime geçilerek planlanır ve paket açıklamasında belirtilen süre ve kapsamda online ve/veya yüz yüze olarak ifa edilir. Detaylar <a href="/teslimat-kosullari">Teslimat Koşulları</a>'nda yer almaktadır.</p>
<h2>Madde 5 — Genel hükümler</h2>
<p>5.1. ALICI, sözleşme konusu hizmetin temel nitelikleri, satış fiyatı, ödeme şekli ve ifasına ilişkin ön bilgileri okuyup bilgi sahibi olduğunu ve elektronik ortamda gerekli teyidi verdiğini kabul eder.</p>
<p>5.2. SATICI, hizmeti eksiksiz, siparişte belirtilen niteliklere uygun ve mesleki özen yükümlülüğü çerçevesinde ifa etmekle yükümlüdür.</p>
<p>5.3. Hizmet bedelinin kartın hamili olmayan kişilerce haksız ve hukuka aykırı olarak kullanılması nedeniyle ilgili banka tarafından SATICI'ya ödenmemesi halinde, sözleşme konusu hizmet ifa edilmez.</p>
<p>5.4. Danışmanlık hizmetleri sonuç taahhüdü değil, özen yükümlülüğü içerir; sportif başarı, transfer veya sponsorluk anlaşması gibi sonuçlar garanti edilmez.</p>
<h2>Madde 6 — Cayma hakkı</h2>
<p>ALICI, sözleşmenin kurulduğu tarihten itibaren 14 (on dört) gün içinde herhangi bir gerekçe göstermeksizin ve cezai şart ödemeksizin sözleşmeden cayma hakkına sahiptir. Cayma bildirimi {{eposta}} adresine yazılı olarak yapılır. Cayma hakkının kullanılması halinde bedel 14 gün içinde ödemenin yapıldığı karta iade edilir.</p>
<p>Mesafeli Sözleşmeler Yönetmeliği m.15/1-(ğ) uyarınca, cayma hakkı süresi sona ermeden önce ALICI'nın onayı ile ifasına başlanan hizmetlerde cayma hakkı kullanılamaz. Detaylar <a href="/iade-kosullari">İade ve iade çeki koşulları</a>'nda yer almaktadır.</p>
<h2>Madde 7 — Temerrüt hali</h2>
<p>Tarafların işbu sözleşmeden doğan yükümlülüklerini yerine getirmemesi halinde Türk Borçlar Kanunu hükümleri uygulanır.</p>
<h2>Madde 8 — Yetkili mahkeme</h2>
<p>İşbu sözleşmeden doğan uyuşmazlıklarda, Ticaret Bakanlığınca her yıl ilan edilen parasal sınırlar dahilinde ALICI'nın yerleşim yerindeki veya işlemin yapıldığı yerdeki Tüketici Hakem Heyetleri ile Tüketici Mahkemeleri yetkilidir.</p>
<h2>Madde 9 — Yürürlük</h2>
<p>ALICI, sipariş sayfasındaki onay kutusunu işaretleyerek işbu sözleşmenin tüm koşullarını kabul etmiş sayılır. SATICI, siparişin gerçekleşmesinden önce işbu sözleşmenin okunup kabul edildiğine dair teyidi almakla yükümlüdür.</p>
HTML],

        'on-bilgilendirme-formu' => ['Ön Bilgilendirme Formu', <<<'HTML'
<h2>1. Satıcı bilgileri</h2>
<p>Unvan: {{unvan}}<br>Adres: {{adres}}<br>Telefon: {{telefon}}<br>E-posta: {{eposta}}<br>MERSİS: {{mersis}}</p>
<h2>2. Alıcı bilgileri</h2>
<p>Ad Soyad / Unvan: {{alici_ad}}<br>Adres: {{alici_adres}}<br>Telefon: {{alici_telefon}}<br>E-posta: {{alici_eposta}}</p>
<h2>3. Hizmetin temel nitelikleri ve fiyatı</h2>
<table class="info-table">
<tr><th>Hizmet</th><td>{{hizmet}}</td></tr>
<tr><th>Paket</th><td>{{paket}}</td></tr>
<tr><th>Toplam Bedel (KDV dahil)</th><td>{{tutar}}</td></tr>
<tr><th>Ödeme</th><td>Kredi kartı / Banka kartı ile peşin veya bankanın sunduğu taksit seçenekleriyle</td></tr>
</table>
<h2>4. İfa şekli ve süresi</h2>
<p>Hizmet, ödeme onayından itibaren en geç 1 iş günü içinde planlanarak paket açıklamasında belirtilen süre boyunca online ve/veya yüz yüze olarak ifa edilir.</p>
<h2>5. Cayma hakkı</h2>
<p>Alıcı, sözleşmenin kurulmasından itibaren 14 gün içinde gerekçe göstermeksizin cayma hakkına sahiptir. Cayma süresi içinde alıcının onayıyla ifasına başlanan hizmetlerde cayma hakkı kullanılamaz (Mesafeli Sözleşmeler Yönetmeliği m.15/1-ğ). Cayma bildirimi {{eposta}} adresine yapılabilir.</p>
<h2>6. Şikâyet ve itirazlar</h2>
<p>Şikâyetlerinizi {{telefon}} numarasından, {{eposta}} adresinden veya <a href="/ariza-takibi">Arıza takibi</a> sayfasından iletebilirsiniz. Uyuşmazlık halinde Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri'ne başvurabilirsiniz.</p>
<p>İşbu ön bilgilendirme formu, elektronik ortamda Alıcı tarafından okunarak kabul edildikten sonra sipariş onaylanır ve bir kopyası Alıcı'nın e-posta adresine gönderilir.</p>
HTML],

        'gizlilik-politikasi' => ['Gizlilik Politikası', <<<'HTML'
<p>{{site}} olarak ziyaretçilerimizin ve danışanlarımızın gizliliğine önem veriyoruz. Bu politika, Site üzerinden toplanan bilgilerin nasıl kullanıldığını ve korunduğunu açıklar.</p>
<h2>Toplanan bilgiler</h2>
<p>Üyelik, sipariş, iletişim ve başvuru formları aracılığıyla ad, e-posta, telefon, adres gibi bilgileri; teknik olarak ise IP adresi ve çerez bilgilerini topluyoruz.</p>
<h2>Kart bilgileri</h2>
<p>Ödeme sırasında girilen kredi kartı bilgileri sunucularımızda saklanmaz, doğrudan Garanti BBVA ödeme altyapısına iletilir.</p>
<h2>Bilgilerin kullanımı</h2>
<p>Toplanan bilgiler yalnızca hizmet sunumu, faturalandırma, yasal yükümlülükler ve (rızanız halinde) bilgilendirme amacıyla kullanılır; üçüncü kişilere satılmaz.</p>
<h2>Güvenlik</h2>
<p>Verileriniz SSL şifreleme, erişim yetkilendirmesi ve işlem kayıtları (log) ile korunur. Yönetim paneline erişim rol bazlı yetkilendirme ile sınırlandırılmıştır.</p>
<h2>İletişim</h2>
<p>Gizlilik ile ilgili sorularınız için {{eposta}} adresine yazabilirsiniz.</p>
HTML],
    ];

    return ['services' => $services, 'pages' => $pages];
}
