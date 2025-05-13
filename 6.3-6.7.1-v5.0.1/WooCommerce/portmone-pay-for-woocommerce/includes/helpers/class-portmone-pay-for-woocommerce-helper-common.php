<?php

defined( 'ABSPATH' ) || exit;

/**
 * Common functions
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Helper_Common
{
    /**
     * @access private
     * @var    array    $portmone_plugin_data    The Portmone plugin data.
     */
    private $portmone_plugin_data;

    public function __construct() {
        $this->portmone_plugin_data = $this->get_portmone_plugin_data();
    }

    /**
     * Compares wc version and portmone plugin version
     *
     * @return string
     */
    public function get_wc_actual() {
        if (WC()->version >= $this->portmone_plugin_data["WC requires at least"] && WC()->version <= $this->portmone_plugin_data["WC tested up to"]) {
            return '<span style="color: green">('.__( 'версія актуальна для плагіна', 'portmone-pay-for-woocommerce' ).')</span>';
        } else {
            return '<span style="color: #e7a511;">('.__( 'на цій версії плагін НЕ перевірений і може працювати нестабільно', 'portmone-pay-for-woocommerce' ).')</span>';
        }
    }

    /**
     * Compares wordpress version and portmone plugin version
     *
     * @return string
     */
    public function get_wp_actual() {
        if (get_bloginfo('version') >= $this->portmone_plugin_data["RequiresWP"]) {
            return '<span style="color: green">('.__( 'версія актуальна для плагіна', 'portmone-pay-for-woocommerce' ).')</span>';
        } else {
            return '<span style="color: #e7a511;">('._( 'на цій версії плагін НЕ перевірений і може працювати нестабільно', 'portmone-pay-for-woocommerce' ).')</span>';
        }
    }

    /**
     * Return Portmone plugin data.
     *
     * @return array
     */
    public function get_portmone_plugin_data()
    {
        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        return get_plugin_data(PORTMONE_PAY_FOR_WOOCOMMERCE_FILE);
    }

    /**
     *  Get attribute5
     *
     * @param array $settings
     * @param WC_Order $order
     *
     * @return string|WP_Error
     */
    public function get_attribute5( array $settings, WC_Order $order )
    {
        $attribute5 = '';
        if ( $settings['split_payment_flag'] == 'yes' ) {

            $split_payments = [];
            foreach ( $order->get_items() as $item ) {
                $payee_id = 0;
                $product = $item->get_product();
                foreach ( $item->get_product()->get_attributes() as $key => $value ) {
                    if ( $value->get_data()['name'] != 'payee_id' ) {
                        continue;
                    }

                    $payee_id = $value->get_data()['options'][0];
                    if ( ! empty( $split_payments[$payee_id] ) ) {
                        $split_payments[$payee_id] += (float) $item->get_total();
                    } else {
                        $split_payments[$payee_id] = (float) $item->get_total();
                    }

                    break;
                }

                if ( $payee_id == 0 ) {
                    $message = sprintf( __( 'Сталася помилка. Не вказана компанія одержувач у товарі &ldquo;%s&rdquo;. Будь ласка, зв\'яжіться з нами, щоб отримати допомогу' ), $product->get_name() );
                    return new WP_Error( 'error', $message);
                }
            }
            unset( $payee_id );

            if ( ! empty( $split_payments ) ) {
                foreach ( $split_payments as $payee_id => $amount ) {
                    $attribute5 .= ':' . $payee_id .';'. $amount . ';';
                }
            }

        }

        return $attribute5;
    }

    public function add_meta_data(WC_Order $order, string $key, $value)
    {
        if ( $order->meta_exists( $key ) ) {
            $order->update_meta_data( $key, $value );
        } else {
            $order->add_meta_data( $key, $value );
        }
    }
}
