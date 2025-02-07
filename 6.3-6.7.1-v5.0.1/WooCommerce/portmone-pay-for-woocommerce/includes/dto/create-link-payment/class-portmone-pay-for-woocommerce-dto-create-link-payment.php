<?php

defined( 'ABSPATH' ) || exit;

/**
 * Create_Link_Payment class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment
{
    /**
     * @access public
     * @var string
     */
    public $method = 'createLinkPayment';

    /**
     * @access public
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee
     */
    public $payee;

    /**
     * @access public
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order
     */
    public $order;

    /**
     * @access public
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token
     */
    public $token;

    /**
     * @access public
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payer
     */
    public $payer;

    public function set_signature(array $settings)
    {
        $signature = $this->payee->payeeId.$this->payee->dt.bin2hex( $this->order->shopOrderNumber ).$this->order->billAmount;
        $signature = strtoupper( $signature ).strtoupper( bin2hex( $settings['login'] ) );
        $this->payee->signature = strtoupper(hash_hmac( 'sha256', $signature, $settings['key'] ) );
    }
}

