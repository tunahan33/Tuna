<?php
/**
 * Kurumsal ve yasal sayfaların başlangıç metinleri. Panelden (Sayfalar menüsü) düzenlenebilir.
 * {{firma_unvan}} gibi alanlar Ayarlar > Firma Bilgileri'nden otomatik doldurulur.
 * Not: Metinler genel şablondur; yayına almadan önce bir hukukçuya kontrol ettirmeniz önerilir.
 */

$satici = '<p><strong>Ünvan:</strong> {{firma_unvan}}<br><strong>Adres:</strong> {{firma_adres}}<br><strong>Telefon:</strong> {{firma_telefon}}<br><strong>E-posta:</strong> {{firma_eposta}}<br><strong>Vergi Dairesi / No:</strong> {{vergi_dairesi}} / {{vergi_no}}<br><strong>MERSİS No:</strong> {{mersis_no}}<br><strong>KEP:</strong> {{kep_adresi}}</p>';
$alici = '<p><strong>Adı Soyadı:</strong> {{alici_ad}}<br><strong>Teslimat Adresi:</strong> {{alici_adres}}<br><strong>Telefon:</strong> {{alici_telefon}}<br><strong>E-posta:</strong> {{alici_eposta}}</p>';
$urunler = '{{urun_listesi}}<p><strong>Ara Toplam:</strong> {{ara_toplam}}<br><strong>Kargo:</strong> {{kargo_bedeli}}<br><strong>Toplam (KDV dahil):</strong> {{toplam_tutar}}<br><strong>Ödeme Şekli:</strong> Kredi kartı / banka kartı<br><strong>Sipariş Tarihi:</strong> {{siparis_tarihi}}</p>';

return [
    'hakkimizda' => ['Hakkımızda', '<p class="lead"><strong>{{site_adi}}</strong>, sahada ve tribünde sarı-kırmızı ruhu taşıyanlar için kaliteli spor ürünleri sunan bir e-ticaret mağazasıdır.</p>
<p>Maç formalarından antrenman eşofmanlarına, kramponlardan maç toplarına kadar tüm ürünlerimizi dayanıklılık, konfor ve performans odağıyla seçiyoruz. Her ürünü depomuzda kontrol ederek, özenle paketleyip kapınıza kadar ulaştırıyoruz.</p>
<h3>Değerlerimiz</h3>
<ul><li><strong>Kalite:</strong> Teknik kumaşlar, sağlam dikiş, uzun ömürlü ürünler.</li><li><strong>Şeffaflık:</strong> Net fiyat, gizli ücret yok. Tüm fiyatlara KDV dahildir.</li><li><strong>Güven:</strong> 3D Secure ile güvenli ödeme, 14 gün içinde koşulsuz iade.</li><li><strong>Hız:</strong> Siparişleriniz {{kargo_suresi}} içinde kargoda.</li></ul>
<h3>Firma Bilgileri</h3>' . $satici],

    'insan-kaynaklari' => ['İnsan Kaynakları', '<p class="lead">Spora tutkuyla bağlı, müşteri memnuniyetini her şeyin önünde tutan ekip arkadaşları arıyoruz.</p>
<h3>Neden {{site_adi}}?</h3>
<ul><li>Hızla büyüyen bir e-ticaret ekibinde sorumluluk alma fırsatı</li><li>Personel indirimi ve maç günü etkinlikleri</li><li>Eğitim ve kariyer gelişim programları</li></ul>
<h3>Açık Pozisyonlar</h3>
<ul><li><strong>Müşteri Temsilcisi</strong> – Telefon, e-posta ve canlı destek kanallarında müşterilerimize yardımcı olacak.</li><li><strong>Depo ve Sevkiyat Sorumlusu</strong> – Sipariş hazırlama, paketleme ve kargo süreçleri.</li><li><strong>E-ticaret İçerik Editörü</strong> – Ürün açıklamaları, kampanya metinleri ve görseller.</li></ul>
<p>Açık pozisyonlarımız veya genel başvuru için aşağıdaki formu doldurabilirsiniz. Başvurunuz İK ekibimize iletilir; uygun bir pozisyon olduğunda sizinle iletişime geçeriz.</p>'],

    'sss' => ['Sıkça Sorulan Sorular', '<details open><summary>Siparişim ne zaman kargoya verilir?</summary><p>Ödemesi onaylanan siparişler {{kargo_suresi}} içinde kargoya teslim edilir. Kargoya verildiğinde kargo firması ve takip numarası sipariş takibi sayfasında görünür.</p></details>
<details><summary>Siparişimi nasıl takip edebilirim?</summary><p>Altbilgideki <a href="siparis-takibi.php">Sipariş takibi</a> sayfasına sipariş numaranızı ve e-posta adresinizi yazmanız yeterlidir. Üyeyseniz <a href="hesabim.php">Hesabım</a> sayfasından tüm siparişlerinizi görebilirsiniz.</p></details>
<details><summary>Kargo ücreti ne kadar?</summary><p>Kargo ücreti {{kargo_ucreti}}\'dir. {{ucretsiz_kargo}} ve üzeri siparişlerde kargo ücretsizdir.</p></details>
<details><summary>Hangi ödeme yöntemlerini kabul ediyorsunuz?</summary><p>Visa, Mastercard ve Troy logolu tüm kredi ve banka kartlarıyla 3D Secure doğrulamalı ödeme yapabilirsiniz.</p></details>
<details><summary>Ürünü iade edebilir miyim?</summary><p>Evet. Teslimattan itibaren 14 gün içinde, kullanılmamış ve etiketi sökülmemiş ürünleri iade edebilirsiniz. Ayrıntılar için <a href="sayfa.php?s=iade-ve-iade-ceki-kosullari">İade ve iade çeki koşulları</a> sayfasına bakın.</p></details>
<details><summary>İade çeki nedir?</summary><p>İade ettiğiniz ürünün bedelini kartınıza iade yerine, bir sonraki alışverişinizde kullanabileceğiniz iade çeki olarak almayı tercih edebilirsiniz. İade çeki 1 yıl geçerlidir.</p></details>
<details><summary>Ürünüm arızalı / kusurlu çıktı, ne yapmalıyım?</summary><p><a href="ariza-takibi.php">Arıza takibi</a> sayfasından arıza kaydı oluşturun. Size verilen kayıt numarasıyla sürecin her adımını aynı sayfadan takip edebilirsiniz.</p></details>
<details><summary>Beden değişimi yapabilir miyim?</summary><p>Stok durumuna göre aynı ürünün farklı bedeniyle ücretsiz değişim yapılır. İletişim sayfasından talebinizi iletmeniz yeterlidir.</p></details>
<details><summary>Fatura bilgilerimi nasıl değiştiririm?</summary><p>Sipariş kargoya verilmeden önce <a href="iletisim.php">İletişim</a> sayfasından bize ulaşın; faturanızı güncelleyelim.</p></details>'],

    'iade-ve-iade-ceki-kosullari' => ['İade ve İade Çeki Koşulları', '<h3>14 Gün İçinde Kolay İade</h3>
<p>Satın aldığınız ürünleri teslim aldığınız tarihten itibaren <strong>14 gün</strong> içinde herhangi bir gerekçe göstermeden iade edebilirsiniz.</p>
<ul><li>Ürün kullanılmamış, yıkanmamış, etiketi sökülmemiş ve orijinal kutusu/ambalajı ile gönderilmelidir.</li><li>Fatura veya sipariş numarası iade paketine eklenmelidir.</li><li>Ayakkabı ve kramponlar, kutusu hasar görmeden ve yalnızca kapalı alanda denenmiş olarak iade edilmelidir.</li></ul>
<h3>İade Süreci</h3>
<ol><li><a href="iletisim.php">İletişim</a> sayfasından “İade talebi” konusuyla sipariş numaranızı iletin.</li><li>Size gönderilen anlaşmalı kargo kodu ile ürünü <strong>ücretsiz</strong> olarak gönderin.</li><li>Ürün depomuza ulaşıp kontrol edildikten sonra en geç <strong>14 gün</strong> içinde iade işleminiz tamamlanır.</li></ol>
<h3>İade Çeki</h3>
<p>İade bedelini kartınıza almak yerine <strong>iade çeki</strong> olarak almayı tercih edebilirsiniz:</p>
<ul><li>İade çeki, iade edilen ürün bedelinin tamamı kadar düzenlenir ve e-posta adresinize kod olarak gönderilir.</li><li>Düzenlendiği tarihten itibaren <strong>1 yıl</strong> geçerlidir.</li><li>Tüm ürünlerde, tek seferde veya birden fazla alışverişte kullanılabilir; kalan bakiye çekte saklanır.</li><li>Nakde çevrilemez, başkasına devredilemez.</li><li>İade çekiyle alınan ürünün iadesinde bedel yeniden iade çeki olarak tanımlanır.</li></ul>
<h3>İade Edilemeyen Ürünler</h3>
<ul><li>Kişiye özel üretilen (isim-numara baskılı) ürünler</li><li>Hijyen gereği ambalajı açılmış çorap, tozluk ve iç giyim ürünleri</li><li>Kullanılmış, yıkanmış veya hasar görmüş ürünler</li></ul>
<h3>Ödeme İadesi</h3>
<p>Kartla yapılan ödemelerin iadesi aynı karta yapılır. İadenin hesabınıza yansıma süresi bankanıza bağlıdır (genellikle 2-10 iş günü). Taksitli alışverişlerde iade bankanız tarafından taksitler halinde yansıtılabilir.</p>'],

    'teslimat-kosullari' => ['Teslimat Koşulları', '<h3>Kargoya Verilme Süresi</h3>
<p>Ödemesi onaylanan siparişler <strong>{{kargo_suresi}}</strong> içinde kargoya teslim edilir. Hafta sonu ve resmi tatillerde verilen siparişler ilk iş günü işleme alınır.</p>
<h3>Teslimat Süresi</h3>
<p>Kargoya verilen siparişler bulunduğunuz ile göre genellikle 1-3 iş gününde adresinize ulaşır. Yasal azami teslimat süresi 30 gündür.</p>
<h3>Kargo Ücreti</h3>
<p>Standart kargo ücreti <strong>{{kargo_ucreti}}</strong>\'dir. <strong>{{ucretsiz_kargo}}</strong> ve üzeri siparişlerde kargo ücretsizdir. Kargo ücreti sepet ve ödeme adımında açıkça gösterilir.</p>
<h3>Teslimat Bölgesi</h3>
<p>Türkiye\'nin 81 iline teslimat yapılmaktadır. Yurt dışı gönderimi bulunmamaktadır.</p>
<h3>Teslim Alırken</h3>
<ul><li>Paketi kargo görevlisinin yanında kontrol edin.</li><li>Hasarlı, ezik veya açılmış paketleri <strong>tutanak tutturarak</strong> teslim almayın ve bize bildirin.</li><li>Adreste bulunamamanız halinde paket en yakın kargo şubesine bırakılır ve size SMS ile bilgi verilir.</li></ul>'],

    'guvenli-alisveris' => ['Güvenli Alışveriş', '<p class="lead">{{site_adi}}\'nde yaptığınız tüm alışverişler uçtan uca güvence altındadır.</p>
<h3>🔒 256-bit SSL Şifreleme</h3><p>Sitemizle tarayıcınız arasındaki tüm veri trafiği SSL sertifikası ile şifrelenir. Adres çubuğundaki kilit simgesi bunu gösterir.</p>
<h3>💳 3D Secure Ödeme</h3><p>Kartla yapılan tüm ödemeler bankanızın 3D Secure doğrulamasından (cep telefonunuza gelen tek kullanımlık şifre) geçer. Kart bilgileriniz doğrudan bankaya iletilir; <strong>sitemizde kaydedilmez ve saklanmaz.</strong></p>
<h3>✅ Orijinal Ürün Garantisi</h3><p>Sattığımız tüm ürünler faturalı ve orijinaldir. Kusurlu ürünlerde <a href="ariza-takibi.php">Arıza takibi</a> sayfasından kayıt açabilirsiniz.</p>
<h3>↺ Kolay İade</h3><p>14 gün içinde ücretsiz iade ve iade çeki seçeneği. <a href="sayfa.php?s=iade-ve-iade-ceki-kosullari">Koşullar</a></p>
<h3>Dolandırıcılığa Karşı Uyarı</h3><p>{{site_adi}} sizden hiçbir zaman telefon, SMS veya e-posta ile kart bilgisi ya da şifre istemez. Bu tür taleplerle karşılaşırsanız lütfen {{firma_telefon}} numarasından bize bildirin.</p>'],

    'uyelik-sozlesmesi' => ['Üyelik Sözleşmesi', '<h3>1. Taraflar</h3><p>İşbu sözleşme, {{firma_unvan}} (“Şirket”) ile {{site_adi}} internet sitesine üye olan kişi (“Üye”) arasında, Üye\'nin kayıt formunu onaylamasıyla elektronik ortamda kurulmuştur.</p>
<h3>2. Konu</h3><p>Sözleşmenin konusu, Üye\'nin siteden yararlanma şartlarının ve tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>
<h3>3. Üyelik Koşulları</h3><ul><li>Üye, kayıt sırasında verdiği bilgilerin doğru ve güncel olduğunu kabul eder.</li><li>Üyelik için 18 yaşını doldurmuş olmak gerekir.</li><li>Kullanıcı adı ve şifrenin gizliliğinden Üye sorumludur; şifrenin üçüncü kişilerce kullanılmasından doğan zararlardan Şirket sorumlu değildir.</li></ul>
<h3>4. Hak ve Yükümlülükler</h3><ul><li>Üye, siteyi hukuka ve genel ahlaka aykırı amaçlarla kullanamaz.</li><li>Şirket, sitedeki ürün, fiyat ve kampanyaları önceden bildirmeksizin değiştirebilir. Sipariş verilmiş ürünlerde sipariş anındaki fiyat geçerlidir.</li><li>Şirket, sözleşmeye aykırı davranan Üye\'nin üyeliğini askıya alabilir veya sonlandırabilir.</li></ul>
<h3>5. Kişisel Veriler</h3><p>Üye\'nin kişisel verileri <a href="sayfa.php?s=genel-aydinlatma-metni">Genel Aydınlatma Metni</a> ve <a href="sayfa.php?s=gizlilik-politikasi">Gizlilik Politikası</a> kapsamında işlenir.</p>
<h3>6. Ticari İleti</h3><p>Üye, onay vermesi halinde kampanya ve duyurulardan e-posta/SMS ile haberdar edilir. Bu onayı dilediği zaman geri alabilir.</p>
<h3>7. Fesih</h3><p>Üye, dilediği zaman {{firma_eposta}} adresine yazarak üyeliğini sonlandırabilir.</p>
<h3>8. Uyuşmazlıklar</h3><p>Bu sözleşmeden doğan uyuşmazlıklarda Türkiye Cumhuriyeti kanunları uygulanır; Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</p>'],

    'genel-aydinlatma-metni' => ['Genel Aydınlatma Metni', '<p>6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca, veri sorumlusu sıfatıyla <strong>{{firma_unvan}}</strong> olarak kişisel verilerinizi aşağıda açıklanan kapsamda işlemekteyiz.</p>
<h3>1. İşlenen Kişisel Veriler</h3><ul><li><strong>Kimlik:</strong> Ad, soyad</li><li><strong>İletişim:</strong> E-posta, telefon, teslimat ve fatura adresi</li><li><strong>Müşteri İşlem:</strong> Sipariş, iade, arıza ve talep kayıtları</li><li><strong>İşlem Güvenliği:</strong> IP adresi, giriş-çıkış kayıtları</li><li><strong>Pazarlama:</strong> (yalnızca açık rızanız varsa) alışveriş tercihleri</li></ul>
<h3>2. İşleme Amaçları</h3><p>Siparişlerin alınması, teslimi ve faturalandırılması; iade, değişim ve arıza süreçlerinin yürütülmesi; müşteri taleplerinin yanıtlanması; üyelik işlemleri; bilgi güvenliğinin sağlanması ve yasal yükümlülüklerin yerine getirilmesi.</p>
<h3>3. Hukuki Sebepler</h3><p>KVKK m.5/2 kapsamında; sözleşmenin kurulması ve ifası, hukuki yükümlülüğün yerine getirilmesi, bir hakkın tesisi ve korunması ile meşru menfaat. Pazarlama faaliyetleri yalnızca açık rızanıza dayanır.</p>
<h3>4. Aktarım</h3><p>Kişisel verileriniz; teslimat için kargo firmalarına, ödeme için bankalara ve ödeme kuruluşlarına, muhasebe için mali müşavirimize ve talep halinde yetkili kamu kurum ve kuruluşlarına aktarılabilir.</p>
<h3>5. Toplama Yöntemi</h3><p>Verileriniz internet sitemizdeki formlar, üyelik ve sipariş adımları ile çerezler aracılığıyla elektronik ortamda toplanır.</p>
<h3>6. Haklarınız (KVKK m.11)</h3><p>Kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltilmesini veya silinmesini isteme, aktarıldığı üçüncü kişileri bilme ve zarara uğramanız halinde tazminat talep etme haklarına sahipsiniz. Başvurularınızı <a href="basvuru-formu.php">İlgili Kişi Başvuru Formu</a> ile iletebilirsiniz. Başvurular en geç 30 gün içinde sonuçlandırılır.</p>'],

    'cerez-politikasi' => ['Çerez Politikası', '<p>{{site_adi}} olarak sitemizi ziyaretiniz sırasında deneyiminizi iyileştirmek için çerezler kullanıyoruz. Çerezler, tarayıcınıza kaydedilen küçük metin dosyalarıdır.</p>
<h3>Kullandığımız Çerezler</h3>
<table><tr><th>Tür</th><th>Amaç</th><th>Süre</th></tr>
<tr><td><strong>Zorunlu</strong> (gs_oturum)</td><td>Oturum, sepet ve güvenli giriş için gereklidir. Kapatılamaz.</td><td>Oturum boyunca</td></tr>
<tr><td><strong>Tercih</strong> (gs_cerez)</td><td>Çerez tercihlerinizi hatırlar.</td><td>1 yıl</td></tr>
<tr><td><strong>Analitik</strong></td><td>Site kullanımını anonim olarak ölçer (yalnızca izin verirseniz).</td><td>En fazla 1 yıl</td></tr>
<tr><td><strong>Pazarlama</strong></td><td>İlgi alanınıza uygun kampanyalar göstermek için (yalnızca izin verirseniz).</td><td>En fazla 1 yıl</td></tr></table>
<h3>Çerezleri Yönetme</h3><p>Zorunlu olmayan çerezlere ilişkin tercihinizi dilediğiniz zaman <a href="cerez-tercihleri.php">Çerez tercihleri</a> sayfasından değiştirebilirsiniz. Tarayıcı ayarlarınızdan da çerezleri silebilir veya engelleyebilirsiniz; ancak zorunlu çerezlerin engellenmesi halinde sepet ve üyelik özellikleri çalışmayabilir.</p>'],

    'mesafeli-satis-sozlesmesi' => ['Mesafeli Satış Sözleşmesi', '<h3>MADDE 1 – TARAFLAR</h3><h4>1.1 Satıcı</h4>' . $satici . '<h4>1.2 Alıcı</h4>' . $alici . '
<h3>MADDE 2 – KONU</h3><p>İşbu sözleşmenin konusu, Alıcı\'nın Satıcı\'ya ait {{site_adi}} internet sitesinden elektronik ortamda sipariş verdiği, aşağıda nitelikleri ve satış fiyatı belirtilen ürünlerin satışı ve teslimine ilişkin olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerinin belirlenmesidir.</p>
<h3>MADDE 3 – SÖZLEŞME KONUSU ÜRÜNLER</h3>' . $urunler . '
<h3>MADDE 4 – TESLİMAT</h3><p>4.1 Ürünler, ödemenin onaylanmasından itibaren {{kargo_suresi}} içinde kargoya verilir ve Alıcı\'nın belirttiği adrese teslim edilir. Teslim süresi yasal 30 günlük süreyi aşamaz.</p><p>4.2 Alıcı, teslim aldığı paketi kargo görevlisinin yanında kontrol etmeli; hasarlı paketleri tutanak tutturarak teslim almamalıdır.</p>
<h3>MADDE 5 – GENEL HÜKÜMLER</h3><p>5.1 Alıcı, ürünün temel nitelikleri, satış fiyatı, ödeme şekli ve teslimata ilişkin ön bilgileri okuyup bilgi sahibi olduğunu ve elektronik ortamda gerekli onayı verdiğini kabul eder.</p><p>5.2 Ödeme, 3D Secure doğrulamalı olarak alınır. Kart bilgileri Satıcı tarafından görülmez ve saklanmaz.</p><p>5.3 Ödemenin bankaca onaylanmaması halinde sözleşme kurulmamış sayılır.</p><p>5.4 Ürünün tedarik edilememesi halinde Alıcı en geç 3 gün içinde bilgilendirilir ve ödediği bedel 14 gün içinde iade edilir.</p>
<h3>MADDE 6 – CAYMA HAKKI</h3><p>6.1 Alıcı, ürünün kendisine tesliminden itibaren 14 (on dört) gün içinde hiçbir gerekçe göstermeksizin ve cezai şart ödemeksizin sözleşmeden cayma hakkına sahiptir.</p><p>6.2 Cayma hakkı kullanılan ürünler kullanılmamış, etiketi sökülmemiş ve orijinal ambalajında olmalıdır.</p><p>6.3 Mesafeli Sözleşmeler Yönetmeliği m.15 uyarınca kişiye özel hazırlanan ürünlerde ve ambalajı açılmış hijyen ürünlerinde cayma hakkı kullanılamaz.</p><p>6.4 Cayma bildirimi {{firma_eposta}} adresine yapılır. Satıcı, bildirimin ulaşmasından itibaren 14 gün içinde ürün bedelini ödemenin yapıldığı karta iade eder; Alıcı tercih ederse bedel iade çeki olarak tanımlanır.</p>
<h3>MADDE 7 – UYUŞMAZLIKLAR</h3><p>Uyuşmazlıklarda, Ticaret Bakanlığı\'nca ilan edilen parasal sınırlar dahilinde Tüketici Hakem Heyetleri, bu sınırları aşan durumlarda Tüketici Mahkemeleri yetkilidir.</p>
<h3>MADDE 8 – YÜRÜRLÜK</h3><p>Alıcı, siparişi onaylayarak işbu sözleşmenin tüm koşullarını kabul etmiş sayılır. Sözleşme {{siparis_tarihi}} tarihinde elektronik ortamda kurulmuştur.</p>'],

    'on-bilgilendirme-formu' => ['Ön Bilgilendirme Formu', '<p>İşbu form, 6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca sözleşme kurulmadan önce Alıcı\'nın bilgilendirilmesi amacıyla hazırlanmıştır.</p><h3>1. Satıcı Bilgileri</h3>' . $satici . '<h3>2. Alıcı Bilgileri</h3>' . $alici . '<h3>3. Ürünler ve Fiyat</h3>' . $urunler . '<p>Tüm fiyatlara KDV dahildir.</p>
<h3>4. Teslimat</h3><p>Ürünler ödeme onayından itibaren {{kargo_suresi}} içinde kargoya verilir.</p><h3>5. Cayma Hakkı</h3><p>Alıcı, teslimden itibaren 14 gün içinde cayma hakkına sahiptir. Kişiye özel üretilen ve ambalajı açılmış hijyen ürünlerinde cayma hakkı kullanılamaz.</p><h3>6. Şikayet ve İtiraz</h3><p>Şikayetlerinizi {{firma_telefon}} veya {{firma_eposta}} üzerinden iletebilir; parasal sınırlar dahilinde Tüketici Hakem Heyeti\'ne başvurabilirsiniz.</p>'],

    'gizlilik-politikasi' => ['Gizlilik Politikası', '<p>{{site_adi}} olarak ziyaretçilerimizin ve müşterilerimizin gizliliğini korumayı taahhüt ediyoruz.</p>
<h3>Hangi Bilgileri Topluyoruz?</h3><p>Üyelik ve sipariş sırasında ad-soyad, e-posta, telefon ve adres bilgileriniz; site kullanımı sırasında IP adresi ve çerez bilgileri toplanır.</p>
<h3>Kart Bilgileri</h3><p>Ödeme sırasında girdiğiniz kart bilgileri doğrudan bankaya iletilir. <strong>Kart numarası, son kullanma tarihi ve CVV sitemizde hiçbir şekilde kaydedilmez.</strong></p>
<h3>Bilgileri Nasıl Kullanıyoruz?</h3><ul><li>Siparişinizi hazırlamak, faturalandırmak ve teslim etmek</li><li>Size sipariş, iade ve arıza süreçleri hakkında bilgi vermek</li><li>Talep ve şikayetlerinizi yanıtlamak</li><li>Yasal yükümlülüklerimizi yerine getirmek</li></ul>
<h3>Paylaşım</h3><p>Bilgileriniz yalnızca teslimat için kargo firmasıyla, ödeme için bankayla ve yasal zorunluluk halinde yetkili kurumlarla paylaşılır; üçüncü kişilere satılmaz veya kiralanmaz.</p>
<h3>Güvenlik</h3><p>Verileriniz SSL ile şifrelenerek iletilir; şifreleriniz geri döndürülemez biçimde (hash) saklanır. Yönetim paneline yalnızca yetkili personel erişebilir ve tüm işlemler kayıt altına alınır.</p>
<h3>Haklarınız</h3><p>KVKK kapsamındaki haklarınız için <a href="basvuru-formu.php">İlgili Kişi Başvuru Formu</a>\'nu kullanabilirsiniz.</p>
<h3>İletişim</h3><p>{{firma_eposta}} · {{firma_telefon}}</p>'],
];
