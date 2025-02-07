<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;


/**
 * This class defines all the code needed to support the block.
 *
 * @since      5.0.1
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes
 * @author     Portmone
 */
final class Portmone_Pay_For_Woocommerce__Block extends AbstractPaymentMethodType
{
    protected $name = 'portmone';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_portmone_settings', []);
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'portmone-blocks-integration',
            PORTMONE_PAY_FOR_WOOCOMMERCE_URL . 'assets/js/blocks/portmone_checkout_block.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        return ['portmone-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->settings['title'] ?? 'Portmone',
            'description' => $this->settings['description'] ?? 'Portmone',
        ];
    }
}

