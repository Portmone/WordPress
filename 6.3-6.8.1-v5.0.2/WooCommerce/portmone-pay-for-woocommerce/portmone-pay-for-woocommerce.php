<?php

/**
 * Plugin Name: portmone-pay-for-woocommerce
 * Plugin URI: https://github.com/Portmone/WordPress
 * Description: Portmone Payment Gateway for WooCommerce
 * Version: 5.0.2
 * Author: Portmone
 * Author URI: https://www.portmone.com.ua
 * Domain Path: /
 * License: Payment Card Industry Data Security Standard (PCI DSS)
 * License URI: https://www.portmone.com.ua/r3/uk/security/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.6
 * WC tested up to: 9.8.5
 *
 * @package Portmone_Pay_For_Woocommerce
 */


defined( 'ABSPATH' ) || exit;


/**
 * Currently plugin version.
 * Start at version 5.0.1 and use SemVer - https://semver.org
 */
define( 'PORTMONE_PAY_FOR_WOOCOMMERCE_VERSION', '5.0.2' );
define( 'PORTMONE_PAY_FOR_WOOCOMMERCE_NAME', 'portmone-pay-for-woocommerce' );
define( 'PORTMONE_PAY_FOR_WOOCOMMERCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PORTMONE_PAY_FOR_WOOCOMMERCE_URL', plugin_dir_url( __FILE__ ) );
define( 'PORTMONE_PAY_FOR_WOOCOMMERCE_FILE',  __FILE__ );

// Removes all cache items.
// wp_cache_flush();

function activate_portmone_pay_for_woocommerce() {
    require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/class-portmone-pay-for-woocommerce-activator.php';
    Portmone_Pay_For_Woocommerce_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_portmone_pay_for_woocommerce' );


// Declaring extension compatibility with HPOS
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

add_action('woocommerce_blocks_loaded', 'portmone_pay_for_woocommerce_block_support');
function portmone_pay_for_woocommerce_block_support()
{
   if( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/blocks/class-portmone-pay-for-woocommerce-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new Portmone_Pay_For_Woocommerce__Block() );
        }
    );
}


require PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/class-portmone-pay-for-woocommerce.php';

add_action( 'plugins_loaded', 'portmone_pay_for_woocommerce_init', 10 );

/**
 * Initialize the plugin.
 */
function portmone_pay_for_woocommerce_init() {

    load_plugin_textdomain("portmone-pay-for-woocommerce", false, plugin_basename(dirname(__FILE__))."/languages" );

    $locale                  = apply_filters( 'plugin_locale', determine_locale(), PORTMONE_PAY_FOR_WOOCOMMERCE_NAME );
    $custom_translation_path = PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . '/languages/'. PORTMONE_PAY_FOR_WOOCOMMERCE_NAME .'-' . $locale . '.mo';
    if ( is_readable( $custom_translation_path ) ) {
        unload_textdomain( PORTMONE_PAY_FOR_WOOCOMMERCE_NAME );
        load_textdomain( PORTMONE_PAY_FOR_WOOCOMMERCE_NAME, $custom_translation_path );
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'portmone_pay_for_woocommerce_missing_wc_notice' );
        return;
    }

    $plugin = new Portmone_Pay_For_Woocommerce();
    $plugin->run();
}

/**
 * WooCommerce fallback notice.
 */
function portmone_pay_for_woocommerce_missing_wc_notice() {
    /* translators: %s WC download URL link. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Portmone Payment Gateway for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'portmone-pay-for-woocommerce' ), '<a href="https://woo.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

