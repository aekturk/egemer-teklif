<?php
/**
 * DOMPDF ve PhpSpreadsheet kütüphanelerini dahil edin
 * Composer ile kurulum yaptıysanız, autoload dosyasını buraya eklemelisiniz.
 */
require_once EGEMER_OFFER_PLUGIN_DIR . 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\IOFactory; // PhpSpreadsheet için yeni import

/**
 * Eklenti etkinleştirildiğinde çalışacak fonksiyon.
 * Veritabanı tablolarını oluşturur.
 */
function egemer_offer_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    error_log( '[Egemer Teklif] Aktivasyon başlıyor.' );

    // Ürünler Tablosu
    $table_products = $wpdb->prefix . 'egemer_products';
    $sql_products = "CREATE TABLE $table_products (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        image_url varchar(255),
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_products );
    error_log( '[Egemer Teklif] Ürünler tablosu oluşturuldu/güncellendi.' );

    // Markalar Tablosu
    $table_brands = $wpdb->prefix . 'egemer_brands';
    $sql_brands = "CREATE TABLE $table_brands (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id bigint(20) UNSIGNED NOT NULL,
        name varchar(255) NOT NULL,
        image_url varchar(255),
        PRIMARY KEY (id),
        FOREIGN KEY (product_id) REFERENCES $table_products(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta( $sql_brands );
    error_log( '[Egemer Teklif] Markalar tablosu oluşturuldu/güncellendi.' );

    // Renkler Tablosu
    $table_colors = $wpdb->prefix . 'egemer_colors';
    $sql_colors = "CREATE TABLE $table_colors (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        brand_id bigint(20) UNSIGNED NOT NULL,
        name varchar(255) NOT NULL,
        image_url varchar(255),
        h1_2_cm decimal(10,2) DEFAULT NULL,
        h4_cm_default decimal(10,2) DEFAULT NULL,
        h5_6_cm decimal(10,2) DEFAULT NULL,
        h7_8_cm decimal(10,2) DEFAULT NULL,
        h9_10_cm decimal(10,2) DEFAULT NULL,
        h11_15_cm decimal(10,2) DEFAULT NULL,
        h16_20_cm decimal(10,2) DEFAULT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (brand_id) REFERENCES $table_brands(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta( $sql_colors );
    error_log( '[Egemer Teklif] Renkler tablosu oluşturuldu/güncellendi.' );

    // Teklifler Tablosu - Mevcut tablo tanımına sütunlar eklendi
    $table_offers = $wpdb->prefix . 'egemer_offers';
    $sql_offers = "CREATE TABLE $table_offers (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_name varchar(255) NOT NULL,
        user_surname varchar(255) NOT NULL,
        user_phone varchar(50) NOT NULL,
        user_email varchar(255) NOT NULL,
        user_address text NOT NULL,        /* Yeni eklenen sütun */
        tax_office varchar(255) DEFAULT '', /* Yeni eklenen sütun */
        tax_number varchar(255) DEFAULT '', /* Yeni eklenen sütun */
        offer_data longtext NOT NULL,
        grand_total_price decimal(10,2) NOT NULL DEFAULT 0.00,
        registration_number varchar(20) NOT NULL DEFAULT '',
        pdf_url varchar(500) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta( $sql_offers );
    error_log( '[Egemer Teklif] Teklifler tablosu oluşturuldu/güncellendi.' );

    // Dummy data ekle (Sadece ilk kurulumda)
    egemer_offer_insert_dummy_data();
    error_log( '[Egemer Teklif] Aktivasyon tamamlandı.' );
}
register_activation_hook( __FILE__, 'egemer_offer_activate' );

/**
 * Dummy data ekleme fonksiyonu
 */
function egemer_offer_insert_dummy_data() {
    global $wpdb;
    $table_products = $wpdb->prefix . 'egemer_products';
    $table_brands = $wpdb->prefix . 'egemer_brands';
    $table_colors = $wpdb->prefix . 'egemer_colors';

    $product_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_products");
    error_log( '[Egemer Teklif] Ürün tablosundaki mevcut kayıt sayısı: ' . $product_count );

    // Check if tables are empty before inserting dummy data
    if ($product_count == 0) {
        error_log( '[Egemer Teklif] Dummy veri ekleme başlıyor...' );
        // ... (Burada dummy kayıt ekleme işlemleri orijinal dosyanızda nasılsa aynen yer alacak) ...
        // --- Dummy kayıt ekleme kodları ---
        error_log( '[Egemer Teklif] Dummy veri ekleme tamamlandı.' );
    } else {
        error_log( '[Egemer Teklif] Ürün tablosu boş olmadığı için dummy veri eklenmedi.' );
    }
}

/**
 * Teklifler tablosunu oluşturma fonksiyonu
 * Bu fonksiyon, register_activation_hook içinde zaten çağrılan egemer_offer_activate fonksiyonu tarafından işlenmektedir.
 * Tekrarlanan tablo oluşturma mantığını önlemek için bu fonksiyonun ayrı bir hook'a bağlı olması gerekmez.
 */
function egemer_offer_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $offers_table = $wpdb->prefix . 'egemer_offers';
    $sql_offers = "CREATE TABLE IF NOT EXISTS $offers_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_name VARCHAR(100),
        user_surname VARCHAR(100),
        user_email VARCHAR(150),
        user_phone VARCHAR(50),
        user_address TEXT, /* Eklendi */
        tax_office VARCHAR(255), /* Eklendi */
        tax_number VARCHAR(255), /* Eklendi */
        offer_data LONGTEXT,
        grand_total_price DECIMAL(18,2) DEFAULT 0,
        pdf_url TEXT,
        kayit_numarasi VARCHAR(20),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $products_table = $wpdb->prefix . 'egemer_products';
    $sql_products = "CREATE TABLE IF NOT EXISTS $products_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255),
        description TEXT,
        image_url TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $brands_table = $wpdb->prefix . 'egemer_brands';
    $sql_brands = "CREATE TABLE IF NOT EXISTS $brands_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255),
        product_id BIGINT UNSIGNED,
        image_url TEXT,
        PRIMARY KEY (id),
        KEY product_id (product_id)
    ) $charset_collate;";

    $colors_table = $wpdb->prefix . 'egemer_colors';
    $sql_colors = "CREATE TABLE IF NOT EXISTS $colors_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255),
        brand_id BIGINT UNSIGNED,
        image_url TEXT,
        PRIMARY KEY (id),
        KEY brand_id (brand_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_offers);
    dbDelta($sql_products);
    dbDelta($sql_brands);
    dbDelta($sql_colors);
}
// Bu hook, eklentinin ana dosyasında (egemer-teklif.php) zaten tanımlı olmalı.
// Eğer activation.php ayrı bir dosya ise, bu satır ana eklenti dosyasında olmalıdır.
// register_activation_hook(WP_PLUGIN_DIR . '/egemer-teklif/egemer-teklif.php', 'egemer_offer_create_tables'); // Bu satır yorum satırı yapıldı
