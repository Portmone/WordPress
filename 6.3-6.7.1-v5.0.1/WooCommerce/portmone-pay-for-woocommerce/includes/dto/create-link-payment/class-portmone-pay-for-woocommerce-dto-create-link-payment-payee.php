<?php

defined( 'ABSPATH' ) || exit;

/**
 * Payee class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee  implements JsonSerializable
{
    /**
     * @access private
     * @var string
     */
    private $payeeId;

    /**
     * @access private
     * @var string
     */
    private $login;

    /**
     * @access private
     * @var string
     */
    private $dt;

    /**
     * @access private
     * @var string
     */
    private $signature = '';

    /**
     * @access private
     * @var string
     */
    private $shopSiteId = '';

    public function set_properties( array $settings )
    {
        $this->payeeId = $settings['payee_id'];
        $this->login = $settings['login'];
        $this->dt = date('Ymdhis');
    }

    public function jsonSerialize(): array
    {
        return [
            'payeeId' => $this->payeeId,
            'login' => $this->login,
            'dt' => $this->dt,
            'signature' => $this->signature,
            'shopSiteId' => $this->shopSiteId
        ];
    }

    public function setSignature( string $signature )
    {
        $this->signature = $signature;
    }

    public function getPayeeId(): string
    {
        return $this->payeeId;
    }

    public function getDt(): string
    {
        return $this->dt;
    }
}
