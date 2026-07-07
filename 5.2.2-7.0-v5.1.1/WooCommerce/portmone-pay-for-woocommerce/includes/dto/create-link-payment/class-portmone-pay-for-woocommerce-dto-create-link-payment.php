<?php

defined( 'ABSPATH' ) || exit;

/**
 * Data structure for requesting a link to the payment page
 * https://docs.portmone.com.ua/docs/en/PaymentGatewayEng/#125-creating-a-payment-link
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment implements JsonSerializable
{
    /**
     * @var string
     */
    private $method = 'createLinkPayment';

    /**
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee
     */
    private $payee;

    /**
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order
     */
    private $order;

    /**
     * @var Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token
     */
    private $token;

    /**
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

