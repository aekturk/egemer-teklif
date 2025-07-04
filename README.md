Egemer Teklif ve Ürün Yönetimi V.2.1.1
Bu doküman, Egemer Teklif ve Ürün Yönetimi WordPress eklentisi hakkında detaylı bilgi, kurulum talimatları ve kullanım kılavuzu sunmaktadır.

Eklenti Bilgileri
Eklenti Adı: Egemer Teklif ve Ürün Yönetimi V.2.1.1

Açıklama: Müşterilerinizin web sitenizden, ürün gruplarınıza özgü marka, renk, işçilik, kategori, süpürgelik yüksekliği, eviye tipi gibi seçimlerle çok adımlı ve yönetilebilir bir teklif talep formunu kolayca doldurabilmesini sağlar. Tüm içerikler admin panelinden yönetilebilir ve dinamik olarak güncellenir.

Versiyon: 2.1.1

Yazar: Ercan CEVIZ (info@ercanceviz.com.tr)

Yazar URI: https://ercanceviz.com.tr

Minimum WordPress Sürümü: 6.0

Test Edilen WordPress Sürümü: 6.8.1

Minimum PHP Sürümü: 8.2

Genel Bakış
Egemer Teklif ve Ürün Yönetimi eklentisi, özel ürün veya hizmetler sunan işletmeler için kapsamlı bir teklif talep sistemidir. Müşterilerinizin web siteniz üzerinden kolayca detaylı teklifler oluşturmasını ve size göndermesini sağlar. Yönetim paneli sayesinde ürünler, markalar, renkler ve teklif detayları kolayca yönetilebilir, fiyatlandırma dinamik olarak güncellenebilir ve gönderilen tekliflerin PDF'i otomatik olarak oluşturulabilir.

Özellikler
Çok Adımlı Teklif Formu: Kullanıcı dostu ve modüler adımlarla teklif oluşturma süreci.

Dinamik Ürün ve Kategori Yönetimi: Ürünler, markalar ve renkler admin panelinden kolayca eklenebilir, düzenlenebilir ve silinebilir.

Detaylı Fiyatlandırma: Tezgah derinliği, panel ve süpürgelik gibi ek özelliklere göre dinamik fiyatlandırma.

Otomatik PDF Oluşturma: Gönderilen her teklif için detaylı bir PDF raporu otomatik olarak oluşturulur ve sunucuya kaydedilir.

Teklif Takibi: Yönetim panelinden tüm gönderilen teklifleri görüntüleme, detaylarını inceleme ve PDF'lerini indirme.

Toplu Veri Ekleme: Ürün, marka ve renkleri toplu olarak ekleyebilme özelliği.

Görsel Destek: Ürün, marka ve renklere görsel ekleyebilme.

Responsive Tasarım: Formun tüm cihazlarda (masaüstü, tablet, mobil) düzgün çalışması.

REST API Entegrasyonu: Güvenli ve ölçeklenebilir veri iletişimi için WordPress REST API kullanılır.

AJAX Destekli İşlemler: Admin panelindeki çoğu işlem sayfa yenilemesi olmadan (AJAX ile) gerçekleşir.

Kurulum Adımları
Eklentiyi İndirin: Eklenti dosyalarını (genellikle bir .zip arşivi olarak) edinin.

WordPress'e Yükleyin:

WordPress yönetici panelinize giriş yapın.

Sol menüden Eklentiler > Yeni Ekle'ye gidin.

Eklenti Yükle butonuna tıklayın.

Dosya Seç butonuna tıklayarak indirdiğiniz .zip dosyasını seçin ve Şimdi Kur'a tıklayın.

Kurulum tamamlandığında Eklentiyi Etkinleştir'e tıklayın.

Dompdf Kurulumu (Gerekliyse):
Bu eklenti PDF oluşturmak için dompdf/dompdf kütüphanesini kullanır. Eğer composer ile bağımlılıkları yüklemediyseniz, eklenti klasörünüzün içinde vendor/autoload.php dosyasının var olduğundan emin olun. Genellikle eklentiyi .zip olarak indirdiğinizde bu dosya dahil edilmiş olabilir. Eğer değilse:

Eklentinin ana dizinine (wp-content/plugins/egemer-teklif) gidin.

Terminali açın ve şu komutu çalıştırın: composer install

Composer yüklü değilse, Composer web sitesinden kurulumu yapmanız gerekecektir.

Veritabanı Tablolarının Oluştuğunu Kontrol Edin: Eklenti etkinleştirildiğinde otomatik olarak gerekli veritabanı tablolarını oluşturacaktır. Yönetim panelinde "Egemer Teklif" menüsünü görmelisiniz.

Kullanım Kılavuzu
1. Teklif Formunu Sayfaya Ekleme
Teklif formunu herhangi bir WordPress sayfasına veya gönderisine eklemek için aşağıdaki kısa kodu kullanın:

[egemer_teklif_formu]

Bu kısa kod, React tabanlı çok adımlı teklif formunuzu sitenizde görüntüleyecektir.

2. Yönetim Paneli
WordPress yönetim panelinizde sol menüde "Egemer Teklif" adında yeni bir menü göreceksiniz. Bu menü altında aşağıdaki sayfalara erişebilirsiniz:

Teklifler: Gönderilen tüm teklifleri listeleyen sayfadır. Her teklifin detaylarını görüntüleyebilir ve otomatik oluşturulan PDF'lerini indirebilirsiniz. Arama ve sıralama özellikleri mevcuttur.

Ürünler: Teklif formunda görünecek ana ürün kategorilerini (örneğin Granit Tezgah, Mermer Tezgah) yönettiğiniz yerdir. Ürünleri ekleyebilir, düzenleyebilir veya silebilirsiniz. Toplu ekleme seçeneği de mevcuttur.

Markalar: Her bir ürüne ait markaları yönettiğiniz yerdir (örneğin Granit Tezgah için Marmara Granit, Anadolu Granit). Markaları ekleyebilir, düzenleyebilir veya silebilirsiniz. Toplu ekleme seçeneği de mevcuttur.

Renkler: Her bir markaya ait renkleri ve ilgili H değerlerini (fiyat çarpanları) yönettiğiniz yerdir. Renkleri ekleyebilir, düzenleyebilir veya silebilirsiniz. Toplu ekleme seçeneği de mevcuttur.

Yönetim Paneli - Renkler Tablosundaki H Değerleri
"Renkler" yönetim sayfasında, her rengin farklı kalınlık veya işleme seviyeleri için tanımlanmış H değerleri bulunur. Bu değerler, formdaki fiyat hesaplamalarında kullanılan çarpanlardır.

H1.2cm

H4cm (Varsayılan)

H5-6cm

H7-8cm

H9-10cm

H11-15cm

H16-20cm

Bu değerleri doğrudan yönetim panelinden güncelleyebilirsiniz.

3. Teklif Oluşturma Süreci (Frontend)
Müşterileriniz web sitenizdeki kısa kodun bulunduğu sayfaya giderek aşağıdaki adımları takip ederek teklif talebi oluşturabilirler:

Ürün Seçimi: Sunulan ürün kategorilerinden birini seçerler.

Marka Seçimi: Seçilen ürüne ait markalardan birini seçerler.

Renk Seçimi: Seçilen markaya ait renklerden birini seçerler.

Tezgah Detayları: Tezgah kalınlığını (H değeri), derinliklerini (mtül cinsinden) ve diğer opsiyonları girerler.

Panel/Süpürgelik: İhtiyaç duyarlarsa panel ve süpürgelik bilgilerini girerler.

Müşteri Bilgileri: Ad, soyad, telefon, e-posta, adres ve opsiyonel olarak vergi dairesi/numarası bilgilerini girerler.

Teklifi Gönder: Formu gönderdiklerinde, sistem otomatik olarak bir kayıt numarası oluşturur, teklif detaylarını ve genel toplam fiyatı veritabanına kaydeder ve teklifin bir PDF'ini oluşturup sunucuya kaydeder. Müşteriye bir PDF indirme linki sunulur.

Sorun Giderme
PDF Oluşturma Hatası:

Eklenti dizininizdeki (wp-content/plugins/egemer-teklif) vendor/autoload.php dosyasının var olduğundan emin olun. Eğer yoksa, eklenti dizininde composer install komutunu çalıştırarak Dompdf bağımlılıklarını yükleyin.

Sunucunuzun PHP versiyonunun en az 8.2 olduğundan emin olun.

WordPress uploads dizinine (genellikle wp-content/uploads/) yazma izinlerinin olduğundan emin olun (CHMOD 755 veya 777 - daha güvenli olan 755'i tercih edin).

WordPress hata loglarını (wp-content/debug.log veya sunucunuzun hata logları) kontrol edin. [Egemer Teklif] DOMPDF Hatası: ile başlayan mesajlar size yol gösterecektir.

Frontend Formu Görüntülenmiyor/Çalışmıyor:

Sayfanıza [egemer_teklif_formu] kısa kodunu doğru bir şekilde eklediğinizden emin olun.

Tarayıcınızın konsolunu (F12 ile açılır) kontrol edin. JavaScript hataları veya ağ isteklerindeki (Network tab) sorunlar size bilgi verebilir.

WordPress "Ayarlar > Kalıcı Bağlantılar" sayfasına gidip "Değişiklikleri Kaydet" butonuna tıklayarak permalinkleri yenilemeyi deneyin.

wp-config.php dosyanızda WP_DEBUG ve WP_DEBUG_LOG değerlerinin true olarak ayarlandığından emin olun.

AJAX/REST API Hataları (400, 403, 500 hataları):

Tarayıcınızın geliştirici araçlarındaki "Network" sekmesini kontrol edin. AJAX isteklerinin doğru URL'ye (örn. /wp-json/egemer/v1/submit-offer) gittiğinden ve başarılı bir yanıt döndüğünden emin olun.

Nonce hataları (Nonce doğrulama hatası) alıyorsanız, tarayıcınızın çerezlerini temizlemeyi veya farklı bir tarayıcıda denemeyi deneyin.

PHP hata loglarını kontrol edin.

İletişim
Herhangi bir sorun veya özellik talebi için lütfen yazar ile iletişime geçin:
Ercan CEVIZ
E-Posta: info@ercanceviz.com.tr
Web Sitesi: https://ercanceviz.com.tr