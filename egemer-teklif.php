<?php
/*
Plugin Name: Egemer Teklif ve Ürün Yönetimi V.2.0
Description: Müşterilerinizin web sitenizden, ürün gruplarınıza özgü marka, renk, işçilik, kategori, süpürgelik yüksekliği, eviye tipi gibi seçimlerle çok adımlı ve yönetilebilir bir teklif talep formunu kolayca doldurabilmesini sağlar. Tüm içerikler admin panelinden yönetilebilir ve dinamik olarak güncellenir.
Version: 2.1.1
Author: Ercan CEVIZ (info@ercanceviz.com.tr)
Author URI: https://ercanceviz.com.tr
Requires at least: 6.0
Tested up to: 6.8.1
Requires PHP: 8.2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EGEMER_OFFER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EGEMER_OFFER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Composer autoload
require_once EGEMER_OFFER_PLUGIN_DIR . 'vendor/autoload.php';

// Modülleri dahil et
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/activation.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/scripts.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/shortcode.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/helpers.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/pdf.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/rest-api.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-menu.php';

// Admin panel sayfaları
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-pages/dashboard.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-pages/offers-list.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-pages/products-page.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-pages/brands-page.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-pages/colors-page.php';
require_once EGEMER_OFFER_PLUGIN_DIR . 'includes/admin-pages/xlsx-import-page.php';

// XLSX şablon dosyasını indirmek için admin endpoint
add_action('admin_post_egemer_download_xlsx_template', function() {
    $template_path = EGEMER_OFFER_PLUGIN_DIR . 'assets/egemer-sablon.xlsx';
    if (file_exists($template_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="egemer-sablon.xlsx"');
        header('Content-Length: ' . filesize($template_path));
        readfile($template_path);
        exit;
    } else {
        wp_die('Şablon dosyası bulunamadı.');
    }
});

// Admin paneli için JS/CSS dosyalarını ekle
add_action('admin_enqueue_scripts', function($hook) {
    // Sadece eklenti admin sayfalarında yükle
    if (strpos($hook, 'egemer-offer') !== false) {
        wp_enqueue_style('egemer-offer-admin-style', EGEMER_OFFER_PLUGIN_URL . 'build/static/css/admin.css', [], null);
        wp_enqueue_script('egemer-offer-admin-js', EGEMER_OFFER_PLUGIN_URL . 'build/static/js/admin.js', ['jquery'], null, true);
        // Gerekirse nonce ve ajaxurl'u JS'e aktar
        wp_localize_script('egemer-offer-admin-js', 'egemerAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }
});