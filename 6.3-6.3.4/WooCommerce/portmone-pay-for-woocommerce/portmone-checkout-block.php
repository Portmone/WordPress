<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Portmone_Blocks extends AbstractPaymentMethodType
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
            plugin_dir_url(__FILE__) . 'portmone_checkout_block.js',
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
            'title' => $this->settings['title'],
            'description' => $this->settings['description'],
        ]; 
    }
}
