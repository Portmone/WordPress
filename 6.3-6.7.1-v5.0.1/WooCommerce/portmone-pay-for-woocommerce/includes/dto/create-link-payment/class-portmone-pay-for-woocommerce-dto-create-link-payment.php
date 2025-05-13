<?php

defined( 'ABSPATH' ) || exit;

/**
 * Create_Link_Payment class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment implements JsonSerializable
{
    /**
     * @access private
     * @var string
     */
    private $method = 'createLinkPayment';

    /**
     * @access private
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee
     */
    private $payee;

    /**
     * @access private
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order
     */
    private $order;

    /**
     * @access private
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token
     */
    private $token;

    /**
     * @access private
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payer
     */
    private $payer;

    public function set_signature( array $settings )
    {
        $signature = $this->payee->getPayeeId().$this->payee->getDt().bin2hex( $this->order->getShopOrderNumber() ).$this->order->getBillAmount();
        $signature = strtoupper( $signature ).strtoupper( bin2hex( $settings['login'] ) );
        $this->payee->setSignature( strtoupper( hash_hmac( 'sha256', $signature, $settings['key'] ) ) ) ;
    }

    public function jsonSerialize(): array
    {
        return [
            'method' => $this->method,
            'payee' => $this->payee,
            'order' => $this->order,
            'token' => $this->token,
            'payer' => $this->payer,
        ];
    }

    public function setPayee(Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee $payee)
    {
        $this->payee = $payee;
    }

    public function setOrder(Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order $order)
    {
        $this->order = $order;
    }

    public function setToken(Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token $token)
    {
        $this->token = $token;
    }

    public function setPayer(Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payer $payer)
    {
        $this->payer = $payer;
    }
}

