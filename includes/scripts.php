<?php
/**
 * React uygulamasının stil ve script dosyalarını kuyruğa ekle.
 * asset-manifest.json dosyasını okuyarak dinamik olarak hash'li dosyaları bulur.
 */
function egemer_offer_enqueue_scripts() {
    $asset_manifest_path = EGEMER_OFFER_PLUGIN_DIR . 'build/asset-manifest.json';
    
    // Fallback URLs (Eğer asset-manifest.json okunamazsa veya beklenen dosyalar bulunamazsa kullanılır)
    $main_js_url_fallback = EGEMER_OFFER_PLUGIN_URL . 'build/static/js/main.js';
    $main_css_url_fallback = EGEMER_OFFER_PLUGIN_URL . 'build/static/css/main.css';
    $chunk_js_url_fallback = EGEMER_OFFER_PLUGIN_URL . 'build/static/js/453.606eb262.chunk.js'; // Bu hash manuel olarak güncellenmelidir eğer React build çıktısı değişirse

    $main_js = $main_js_url_fallback;
    $main_css = $main_css_url_fallback;
    $chunk_js = $chunk_js_url_fallback; 

    if ( file_exists( $asset_manifest_path ) ) {
        $asset_manifest_content = file_get_contents( $asset_manifest_path );
        $asset_manifest = json_decode( $asset_manifest_content, true );
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log( '[Egemer Teklif] asset-manifest.json JSON parse hatası: ' . json_last_error_msg() );
            // JSON parse hatası varsa, fallback URL'ler zaten ayarlandığı için burada ek bir işlem yapmaya gerek yok.
        } else {
            // asset-manifest.json içeriğini logla
            error_log( '[Egemer Teklif] asset-manifest.json içeriği: ' . print_r($asset_manifest, true) );

            // main.js ve main.css dosyalarını asset-manifest'teki "files" içinden bulma
            // React build çıktısı değişebilir, bu nedenle daha esnek bir arama yapalım
            foreach ($asset_manifest['files'] as $key => $value) {
                if (strpos($key, 'main.js') !== false && strpos($value, '.js') !== false) {
                    $main_js = EGEMER_OFFER_PLUGIN_URL . 'build' . $value;
                }
                if (strpos($key, 'main.css') !== false && strpos($value, '.css') !== false) {
                    $main_css = EGEMER_OFFER_PLUGIN_URL . 'build' . $value;
                }
                if (strpos($key, '.chunk.js') !== false && strpos($value, '.js') !== false && strpos($key, 'main') === false) {
                    $chunk_js = EGEMER_OFFER_PLUGIN_URL . 'build/' . $entrypoint;
                }
            }

            // Fallback olarak entrypoints'i de kontrol edelim (bazı CRA versiyonlarında direkt hash'li isimler burada olabilir)
            if (isset($asset_manifest['entrypoints']) && is_array($asset_manifest['entrypoints'])) {
                foreach ($asset_manifest['entrypoints'] as $entrypoint) {
                    if (strpos($entrypoint, 'static/js/main') !== false && strpos($entrypoint, '.js') !== false) {
                        $main_js = EGEMER_OFFER_PLUGIN_URL . 'build/' . $entrypoint;
                    } elseif (strpos($entrypoint, 'static/css/main') !== false && strpos($entrypoint, '.css') !== false) {
                        $main_css = EGEMER_OFFER_PLUGIN_URL . 'build/' . $entrypoint;
                    } elseif (strpos($entrypoint, '.chunk.js') !== false) {
                        $chunk_js = EGEMER_OFFER_PLUGIN_URL . 'build/' . $entrypoint;
                    }
                }
            }

            error_log( '[Egemer Teklif] asset-manifest.json başarıyla okundu. Dinamik URL\'ler:' );
            error_log( '[Egemer Teklif] main_js URL: ' . $main_js );
            error_log( '[Egemer Teklif] main_css URL: ' . $main_css );
            if ($chunk_js) {
                error_log( '[Egemer Teklif] chunk_js URL: ' . $chunk_js );
            }
        }

    } else {
        error_log( '[Egemer Teklif] asset-manifest.json dosyası bulunamadı: ' . $asset_manifest_path . '. Fallback URL\'ler kullanılıyor.' );
        // Fallback URL'ler zaten $main_js, $main_css, $chunk_js değişkenlerine atanmıştır.
    }
    
    // CSS dosyasını kuyruğa ekle
    wp_enqueue_style( 'egemer-offer-style', $main_css, array(), null );
    error_log( '[Egemer Teklif] CSS kuyruğa eklendi: ' . $main_css );

    // Main JS dosyasını kuyruğa ekle
    wp_enqueue_script( 'egemer-offer-main', $main_js, array( 'wp-element' ), null, true );
    error_log( '[Egemer Teklif] Main JS kuyruğa eklendi: ' . $main_js );
    
    // Chunk JS dosyasını kuyruğa ekle (main.js yüklendikten sonra)
    wp_enqueue_script( 'egemer-offer-chunk', $chunk_js, array( 'egemer-offer-main' ), null, true );
    error_log( '[Egemer Teklif] Chunk JS kuyruğa eklendi: ' . $chunk_js );
    
    // React uygulamasına geçilecek verileri localize et
    // Bu kısım, 'egemer-offer-main' scripti yüklendikten sonra çalışmalıdır.
    wp_localize_script( 'egemer-offer-main', 'egemerOfferData', array(
        'apiUrl'  => get_rest_url( null, 'egemer/v1/' ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'isAdmin' => current_user_can('manage_options') // Kullanıcının yönetici olup olmadığını kontrol et
    ) );
    error_log( '[Egemer Teklif] egemerOfferData lokalize edildi. API URL: ' . get_rest_url( null, 'egemer/v1/' ) );
}
add_action( 'wp_enqueue_scripts', 'egemer_offer_enqueue_scripts' );