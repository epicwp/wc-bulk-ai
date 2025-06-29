<?php
/**
 * Is WooCommerce active?
 *
 * @return bool
 */
function wc_bulk_ai_has_wc(): bool {
    return did_action( 'woocommerce_loaded' ) || function_exists( 'WC' );
}