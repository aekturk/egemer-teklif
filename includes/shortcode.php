<?php
/**
 * Teklif formunu bir kısa kod ile göster.
 * [egemer_teklif_formu]
 */
function egemer_offer_shortcode() {
    // React uygulamasının render edileceği div.
    return '<div id="egemer-offer-root"></div>';
}
add_shortcode( 'egemer_teklif_formu', 'egemer_offer_shortcode' );