<?php

defined( 'ABSPATH' ) || exit;

/**
 * Order class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order
{
    public $description = '';
    public $shopOrderNumber;
    public $billAmount;
    public $attribute1;
    public $attribute2;
    public $attribute3;
    public $attribute4 = '';
    public $attribute5;
    public $successUrl;
    public $failureUrl;
    public $preauthFlag;
    public $billCurrency;
    public $expTime;
    public $encoding = 'UTF-8';

    public function set_properties( array $settings, WC_Order $order )
    {
        $this->shopOrderNumber = $order->get_order_number() . '_' . time();
        $this->billAmount = $this->get_order_total( $settings, $order );
        $this->attribute1 = $this->get_attribute1( $settings, $order );
        $this->attribute2 = $this->get_attribute2( $settings, $order );
        $this->attribute3 = $this->get_attribute3( $settings, $order );
        $this->successUrl = esc_url_raw( add_query_arg( array(), WC()->api_request_url( 'portmone_payment' ) ) );
        $this->failureUrl = esc_url_raw( add_query_arg( array(), WC()->api_request_url( 'portmone_payment' ) ) );
        $this->preauthFlag = $this->get_preauth_flag( $settings );
        $this->billCurrency = $this->get_order_currency( $settings );
        $this->expTime = $settings['exp_time'];
    }

    public function set_attribute5( string $attribute5 )
    {
        $this->attribute5 = $attribute5;
    }

    private function get_order_total( array $settings, WC_Order $order )
    {
        $order_total = $order->get_total();
        if (isset($settings['convert_money']) &&
            isset($settings['exchange_rates']) &&
            $settings['convert_money'] == 'yes' &&
            $settings['exchange_rates'] > 0
        ) {
            return round( $order_total * $settings['exchange_rates'] , 2 );
        }

        return $order_total;
    }

    private function get_order_currency( array $settings )
    {
        if ( isset($settings['convert_money']) &&
             isset($settings['exchange_rates']) &&
             $settings['convert_money'] == 'yes' &&
             $settings['exchange_rates'] > 0
        ) {
            return 'UAH';
        }

        $woocommerce_currency = get_woocommerce_currency();
        if( in_array( $woocommerce_currency, ['UAH', 'USD', 'EUR', 'GBP', 'PLN', 'KZT'] ) ) {
            return $woocommerce_currency;
        }

        return 'UAH';
    }

    private function get_attribute1( array $settings, WC_Order $order )
    {
        return ( $settings['save_client_first_last_name_flag'] == 'yes' ) ? $order->get_billing_first_name()  . ' ' . $order->get_billing_last_name() : '';
    }

    private function get_attribute2( array $settings, WC_Order $order )
    {
        return ( $settings['save_client_phone_number_flag'] == 'yes' ) ? $order->get_billing_phone() : '';
    }

    private function get_attribute3( array $settings, WC_Order $order )
    {
        return ( $settings['save_client_email_flag'] == 'yes' ) ? $order->get_billing_email() : '';
    }

    private function get_preauth_flag( array $settings )
    {
        return ( $settings['preauth_flag'] == 'yes' ) ? 'Y' : 'N' ;
    }
}
