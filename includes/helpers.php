<?php
// Yardımcı Fonksiyonlar: Sıralama Parametrelerini Al ve Sıralanabilir Başlık Oluştur

/**
 * Mevcut sıralama parametrelerini (sütun ve yön) alır ve doğrular.
 *
 * @param array  $allowed_columns İzin verilen sıralanabilir sütunların dizisi.
 * @param string $default_column  Varsayılan sıralama sütunu.
 * @param string $default_direction Varsayılan sıralama yönü ('asc' veya 'desc').
 * @return array Sıralama sütunu ve yönünü içeren dizi.
 */
function egemer_offer_get_current_sort_params( $allowed_columns, $default_column = 'id', $default_direction = 'desc' ) {
    $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : $default_column;
    $order = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : $default_direction;

    // Sütunu izin verilenler listesinde doğrula
    if ( ! in_array( $orderby, $allowed_columns ) ) {
        $orderby = $default_column;
    }

    // Sıralama yönünü doğrula
    if ( ! in_array( strtolower( $order ), array( 'asc', 'desc' ) ) ) {
        $order = $default_direction;
    }

    return array( 'orderby' => $orderby, 'order' => $order );
}

/**
 * Sıralanabilir bir tablo başlığı (<th>) oluşturur.
 *
 * @param string $column_key      Sıralanacak sütunun veritabanı anahtarı (örn: 'id', 'name').
 * @param string $column_label    Tabloda gösterilecek sütun etiketi (örn: 'ID', 'Adı').
 * @param string $current_orderby Şu anki sıralama sütunu.
 * @param string $current_order   Şu anki sıralama yönü.
 * @param string $page_slug       Mevcut admin sayfasının slug'ı (örn: 'egemer-offer-products').
 * @param string $search_term     Mevcut arama terimi.
 * @return string Oluşturulan <th> etiketi.
 */
function egemer_offer_get_sortable_column_header( $column_key, $column_label, $current_orderby, $current_order, $page_slug, $search_term = '' ) {
    // Yeni sıralama yönünü belirle: eğer mevcut sütunsa yönü tersine çevir, değilse varsayılan olarak 'asc' yap.
    $new_order = ( $current_orderby === $column_key && $current_order === 'asc' ) ? 'desc' : 'asc';
    
    // Sıralama göstergesini belirle (yukarı/aşağı ok)
    $sort_indicator = '';
    if ( $current_orderby === $column_key ) {
        $sort_indicator = ( $current_order === 'asc' ) ? ' &#9650;' : ' &#9660;'; // Unicode oklar
    }
    
    // Sıralama URL'sini oluştur
    $query_args = array(
        'page'    => $page_slug,
        'orderby' => $column_key,
        'order'   => $new_order,
    );
    // Arama terimini URL'ye ekle (eğer sunucu tarafında arama hala kullanılacaksa)
    if ( ! empty( $search_term ) ) {
        $query_args['s'] = $search_term;
    }

    $url = add_query_arg( $query_args, admin_url( 'admin.php' ) );

    return '<th class="manage-column column-' . esc_attr( $column_key ) . ' sorted ' . esc_attr( $current_order ) . '"><a href="' . esc_url( $url ) . '"><span>' . esc_html( $column_label ) . '</span><span class="sorting-indicator">' . $sort_indicator . '</span></a></th>';
}

/**
 * Teklif gönderimlerini işler, veritabanına kaydeder ve PDF oluşturur.
 *
 * @param array $data Teklif verileri.
 * @return array İşlem sonucu ve diğer ilgili bilgiler.
 */
use Dompdf\Dompdf;

function egemer_process_offer_submission($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'egemer_offers';

    // Frontend'den gelen anahtarları doğru eşle ve güvenli hale getir
    $user_name    = sanitize_text_field($data['userName'] ?? '');
    $user_surname = sanitize_text_field($data['userSurname'] ?? '');
    $user_email   = sanitize_email($data['userEmail'] ?? '');
    $user_phone   = sanitize_text_field($data['userPhone'] ?? '');
    $user_address = sanitize_textarea_field($data['userAddress'] ?? '');
    $tax_office   = sanitize_text_field($data['taxOffice'] ?? '');
    $tax_number   = sanitize_text_field($data['taxNumber'] ?? '');
    
    // Teklif detaylarını güvenli şekilde işle
    $offer_items = [];
    if (isset($data['offerData'])) {
        // stripslashes kaldırıldı, çünkü FormData genellikle slash eklemez.
        // Eğer JSON geçerli değilse, json_decode null döner.
        $decoded = json_decode($data['offerData'], true); 
        $offer_items = is_array($decoded) ? $decoded : [];
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[Egemer Teklif] JSON decode hatası: ' . json_last_error_msg());
            error_log('[Egemer Teklif] Hatalı offerData stringi: ' . $data['offerData']);
        }
    }
    $offer_data   = !empty($offer_items) ? wp_json_encode($offer_items) : null;
    $grand_total_price = isset($data['grandTotalOfferPrice']) ? (float)$data['grandTotalOfferPrice'] : 0;

    // Kayıt tarihi ve saati
    $created_at = current_time('mysql');
    $kayit_tarihi = date_i18n('d.m.Y', strtotime($created_at));
    $kayit_saati  = date_i18n('H:i', strtotime($created_at));
    $year = date_i18n('Y', strtotime($created_at));
    $week = date_i18n('W', strtotime($created_at));
    
    // Haftanın ilk ve son gününü doğru hesapla (ISO 8601 haftası)
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $hafta_ilk_gun = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $hafta_son_gun = $dto->format('Y-m-d');

    // Kayıt numarası oluşturma
    // Bu hafta içinde daha önce kaydedilen en yüksek sıra numarasını bul
    $son_kayit = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(CAST(SUBSTRING(kayit_numarasi, 7, 2) AS UNSIGNED)) FROM $table WHERE created_at >= %s AND created_at <= %s AND kayit_numarasi LIKE %s",
        $hafta_ilk_gun . ' 00:00:00', $hafta_son_gun . ' 23:59:59', $year . $week . '%'
    ));
    $sira = $son_kayit ? min(intval($son_kayit) + 1, 99) : 1; // 99'u geçmemesi için min() kullanıldı
    $kayit_numarasi = $year . $week . str_pad($sira, 2, '0', STR_PAD_LEFT);

    // Kayıt ekle
    $insert_data = [
        'user_name'         => $user_name,
        'user_surname'      => $user_surname,
        'user_email'        => $user_email,
        'user_phone'        => $user_phone,
        'user_address'      => $user_address,
        'tax_office'        => $tax_office,
        'tax_number'        => $tax_number,
        'offer_data'        => $offer_data,
        'grand_total_price' => $grand_total_price,
        'pdf_url'           => '',
        'kayit_numarasi'    => $kayit_numarasi,
        'created_at'        => $created_at
    ];

    // Debugging için insert edilecek veriyi logla
    error_log('[Egemer Teklif] Veritabanına eklenecek veri: ' . print_r($insert_data, true));

    $inserted = $wpdb->insert($table, $insert_data);

    if (!$inserted) {
        error_log('[Egemer Teklif] Teklif veritabanına eklenemedi! Hata: ' . $wpdb->last_error);
        error_log('[Egemer Teklif] Son sorgu: ' . $wpdb->last_query);
        return [
            'success' => false,
            'message' => 'Teklif kaydedilemedi. Detaylı hata için logları kontrol edin.'
        ];
    }
    $offer_id = $wpdb->insert_id;
    error_log('[Egemer Teklif] Teklif başarıyla kaydedildi, ID: ' . $offer_id);


    // PDF oluşturma
    $pdf_dir = WP_CONTENT_DIR . '/uploads/teklifler/';
    if (!file_exists($pdf_dir)) {
        if (!mkdir($pdf_dir, 0755, true)) {
            error_log('[Egemer Teklif] PDF dizini oluşturulamadı: ' . $pdf_dir);
            return [
                'success' => false,
                'message' => 'PDF dizini oluşturulamadı.'
            ];
        }
    }
    $pdf_filename = 'teklif_' . $offer_id . '.pdf';
    $pdf_path = $pdf_dir . $pdf_filename;
    $pdf_url  = content_url('uploads/teklifler/' . $pdf_filename);

    // PDF içeriğini oluşturmak için pdf.php dosyasındaki fonksiyonu kullan
    // EGEMER_OFFER_PLUGIN_DIR tanımlı olduğundan emin olun
    if (!defined('EGEMER_OFFER_PLUGIN_DIR')) {
        define('EGEMER_OFFER_PLUGIN_DIR', plugin_dir_path(__FILE__) . '../'); // Varsayılan olarak bir üst dizin olarak ayarla
    }
    require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/pdf.php'; // pdf.php dosyasını dahil et

    $offer_details_for_pdf = [
        'products'          => $offer_items,
        'grand_total_price' => $grand_total_price,
    ];
    $user_data_for_pdf = [
        'user_name'         => $user_name,
        'user_surname'      => $user_surname,
        'user_email'        => $user_email,
        'user_phone'        => $user_phone,
        'user_address'      => $user_address,
        'tax_office'        => $tax_office,
        'tax_number'        => $tax_number,
        'kayit_tarihi'      => $kayit_tarihi,
        'kayit_saati'       => $kayit_saati,
        'kayit_numarasi'    => $kayit_numarasi,
    ];

    $html = egemer_generate_offer_html($offer_details_for_pdf, $user_data_for_pdf);

    try {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($pdf_path, $dompdf->output());
        error_log('[Egemer Teklif] PDF başarıyla oluşturuldu: ' . $pdf_url);
    } catch (\Exception $e) {
        error_log('[Egemer Teklif] DOMPDF hata: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'PDF oluşturulurken hata oluştu: ' . $e->getMessage()
        ];
    }

    $wpdb->update($table, ['pdf_url' => $pdf_url], ['id' => $offer_id]);
    error_log('[Egemer Teklif] PDF URL veritabanına güncellendi.');

    return [
        'success' => true,
        'pdf_url' => $pdf_url,
        'registration_number' => $kayit_numarasi // Kayıt numarasını da döndür
    ];
}

// Buraya başka yardımcı fonksiyonlar da ekleyebilirsiniz.
