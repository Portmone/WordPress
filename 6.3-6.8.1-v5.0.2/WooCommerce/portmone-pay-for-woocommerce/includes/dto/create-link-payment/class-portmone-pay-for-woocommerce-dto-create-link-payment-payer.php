<?php

defined( 'ABSPATH' ) || exit;

/**
 * Payer class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payer implements JsonSerializable
{
    private $lang;
    private $emailAddress;
    private $showEmail ='Y';

    public function set_properties( WC_Order $order )
    {
        $this->lang = $this->get_language();
        $this->emailAddress = $order->get_billing_email();
    }

    public function jsonSerialize(): array
    {
        return get_object_vars( $this );
    }

    /**
     * Definition of the WP language
     *
     * @return string
     */
    private function get_language()
    {
        $lang = substr( get_bloginfo( 'language' ), 0, 2 );
        if ( $lang == 'ru' || $lang == 'en' || $lang == 'uk' ) {
            return  $lang;
        } else {
            return  'en';
        }
    }
}
