<?php
/**
 * WordPress REST API endpointlerini kaydet.
 */
function egemer_offer_register_rest_routes() {
    register_rest_route( 'egemer/v1', '/products', array(
        'methods'             => 'GET',
        'callback'            => 'egemer_offer_get_products',
        'permission_callback' => '__return_true', // İhtiyaca göre düzenlenebilir
    ) );

    register_rest_route( 'egemer/v1', '/brands', array(
        'methods'             => 'GET',
        'callback'            => 'egemer_offer_get_brands',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'egemer/v1', '/colors', array(
        'methods'             => 'GET',
        'callback'            => 'egemer_offer_get_colors',
        'permission_callback' => '__return_true',
    ) );

    // Admin API Rotaları - Ürünler
    register_rest_route( 'egemer/v1', '/admin/products', array(
        'methods'             => 'GET',
        'callback'            => 'egemer_offer_admin_get_products',
        'permission_callback' => 'egemer_offer_admin_permission_check',
    ) );
    register_rest_route( 'egemer/v1', '/admin/products/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'egemer_offer_admin_update_product',
        'permission_callback' => 'egemer_offer_admin_permission_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
    register_rest_route( 'egemer/v1', '/admin/products/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'egemer_offer_admin_delete_product',
        'permission_callback' => 'egemer_offer_admin_permission_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
    register_rest_route( 'egemer/v1', '/admin/products/bulk', array(
        'methods'             => 'POST',
        'callback'            => 'egemer_offer_admin_bulk_add_products',
        'permission_callback' => 'egemer_offer_admin_permission_check',
    ) );

    // Admin API Rotaları - Markalar
    register_rest_route( 'egemer/v1', '/admin/brands', array(
        'methods'             => 'GET',
        'callback'            => 'egemer_offer_admin_get_brands',
        'permission_callback' => 'egemer_offer_admin_permission_check',
    ) );
    register_rest_route( 'egemer/v1', '/admin/brands/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'egemer_offer_admin_update_brand',
        'permission_callback' => 'egemer_offer_admin_permission_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
    register_rest_route( 'egemer/v1', '/admin/brands/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'egemer_offer_admin_delete_brand',
        'permission_callback' => 'egemer_offer_admin_permission_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
    register_rest_route( 'egemer/v1', '/admin/brands/bulk', array(
        'methods'             => 'POST',
        'callback'            => 'egemer_offer_admin_bulk_add_brands',
        'permission_callback' => 'egemer_offer_admin_permission_check',
    ) );

    // Admin API Rotaları - Renkler
    register_rest_route( 'egemer/v1', '/admin/colors', array(
        'methods'             => 'GET',
        'callback'            => 'egemer_offer_admin_get_colors',
        'permission_callback' => 'egemer_offer_admin_permission_check',
    ) );
    register_rest_route( 'egemer/v1', '/admin/colors/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'egemer_offer_admin_update_color',
        'permission_callback' => 'egemer_offer_admin_permission_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
    register_rest_route( 'egemer/v1', '/admin/colors/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'egemer_offer_admin_delete_color',
        'permission_callback' => 'egemer_offer_admin_permission_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
    register_rest_route( 'egemer/v1', '/admin/colors/bulk', array(
        'methods'             => 'POST',
        'callback'            => 'egemer_offer_admin_bulk_add_colors',
        'permission_callback' => 'egemer_offer_admin_permission_check',
    ) );

    error_log( '[Egemer Teklif] REST API rotaları kaydedildi.' );
}
add_action( 'rest_api_init', 'egemer_offer_register_rest_routes' );

/**
 * Yönetici yetkilendirme kontrolü.
 */
function egemer_offer_admin_permission_check() {
    return current_user_can( 'manage_options' );
}

/**
 * Ürünleri veritabanından getirir.
 */
function egemer_offer_get_products( $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_products';
    $products = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
    error_log( '[Egemer Teklif] Ürünler REST API çağrısı. ' . count( $products ) . ' ürün bulundu.' );
    return new WP_REST_Response( $products, 200 );
}

/**
 * Markaları veritabanından getirir.
 */
function egemer_offer_get_brands( $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_brands';
    $brands = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
    error_log( '[Egemer Teklif] Markalar REST API çağrısı. ' . count( $brands ) . ' marka bulundu.' );
    return new WP_REST_Response( $brands, 200 );
}

/**
 * Renkleri veritabanından getirir.
 */
function egemer_offer_get_colors( $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_colors';
    $colors = $wpdb->get_results( "SELECT id, brand_id, name, image_url, h1_2_cm, h4_cm_default, h5_6_cm, h7_8_cm, h9_10_cm, h11_15_cm, h16_20_cm FROM $table_name", ARRAY_A );
    error_log( '[Egemer Teklif] Renkler REST API çağrısı. ' . count( $colors ) . ' renk bulundu.' );
    return new WP_REST_Response( $colors, 200 );
}

// Teklif gönderme AJAX handler (frontend form için)
add_action('wp_ajax_egemer_submit_offer', 'egemer_handle_submit_offer');
add_action('wp_ajax_nopriv_egemer_submit_offer', 'egemer_handle_submit_offer');

function egemer_handle_submit_offer() {
    $result = egemer_process_offer_submission($_POST);
    if (is_array($result) && isset($result['success']) && $result['success']) {
        wp_send_json_success($result);
    } else {
        $error_message = (is_array($result) && isset($result['message'])) ? $result['message'] : 'Bilinmeyen Hata';
        wp_send_json_error($error_message);
    }
    wp_die();
}

/**
 * (Admin panel REST API callback fonksiyonlarının tamamı burada yer almalıdır!)
 * ... (Uzun olduğu için burada kısaltıldı. Orijinal dosyanızdaki tüm ilgili callback fonksiyonları bu dosyada olmalı.) ...
 */