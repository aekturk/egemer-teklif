<?php
/**
 * Admin menülerini oluştur.
 */
function egemer_offer_admin_menu() {
    add_menu_page(
        'Egemer Teklif',
        'Egemer Teklif',
        'manage_options',
        'egemer-offer',
        'egemer_offer_dashboard_page',
        'dashicons-clipboard',
        20
    );

    add_submenu_page(
        'egemer-offer',
        'Teklifler',
        'Teklifler',
        'manage_options',
        'egemer-offer-list',
        'egemer_offer_list_page'
    );

    add_submenu_page(
        'egemer-offer',
        'Ürünler',
        'Ürünler',
        'manage_options',
        'egemer-offer-products',
        'egemer_offer_products_page'
    );

    add_submenu_page(
        'egemer-offer',
        'Markalar',
        'Markalar',
        'manage_options',
        'egemer-offer-brands',
        'egemer_offer_brands_page'
    );

    add_submenu_page(
        'egemer-offer',
        'Renkler',
        'Renkler',
        'manage_options',
        'egemer-offer-colors',
        'egemer_offer_colors_page'
    );

    // Yeni XLSX Toplu Ekleme sayfası eklendi
    add_submenu_page(
        'egemer-offer',
        'XLSX Toplu Ekleme',
        'XLSX Toplu Ekleme',
        'manage_options',
        'egemer-offer-xlsx-import',
        'egemer_offer_xlsx_bulk_import_page'
    );
}
add_action( 'admin_menu', 'egemer_offer_admin_menu' );