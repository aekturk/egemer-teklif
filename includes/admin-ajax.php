add_action('wp_ajax_egemer_save_product', 'egemer_save_product');
add_action('wp_ajax_egemer_delete_product', 'egemer_delete_product');
add_action('wp_ajax_egemer_bulk_add_products', 'egemer_bulk_add_products');
add_action('wp_ajax_egemer_bulk_add_brands', 'egemer_bulk_add_brands');
add_action('wp_ajax_egemer_bulk_add_colors', 'egemer_bulk_add_colors');

function egemer_save_product() {
    // $_POST ile gelen verileri kaydet/güncelle
    // Başarılıysa wp_send_json_success(), hata varsa wp_send_json_error()
}

function egemer_delete_product() {
    // $_POST['product_id'] ile sil
}

function egemer_bulk_add_products() {
    // $_POST['bulk'] ile toplu ürün ekleme işlemini burada yap
    // Başarılıysa wp_send_json_success(), hata varsa wp_send_json_error()
}

function egemer_bulk_add_brands() {
    // $_POST['bulk'] ile toplu marka ekleme işlemini burada yap
}

function egemer_bulk_add_colors() {
    // $_POST['bulk'] ile toplu renk ekleme işlemini burada yap
}
