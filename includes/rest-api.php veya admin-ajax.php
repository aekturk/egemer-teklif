add_action('wp_ajax_egemer_submit_offer', 'egemer_handle_submit_offer');
add_action('wp_ajax_nopriv_egemer_submit_offer', 'egemer_handle_submit_offer');

function egemer_handle_submit_offer() {
    // ...gerekli kontroller ve nonce doğrulama...
    $result = egemer_process_offer_submission($_POST);

    if (is_array($result) && isset($result['success']) && $result['success']) {
        wp_send_json_success($result);
    } else {
        $error_message = (is_array($result) && isset($result['message'])) ? $result['message'] : 'Bilinmeyen Hata';
        wp_send_json_error($error_message);
    }
    wp_die();
}
