<?php

defined( 'ABSPATH' ) || exit;

/**
 * Payee class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee
{
    public $payeeId;
    public $login;
    public $dt;
    public $signature = '';
    public $shopSiteId = '';

    public function set_properties( array $settings )
    {
        $this->payeeId = $settings['payee_id'];
        $this->login = $settings['login'];
        $this->dt = date('Ymdhis');
    }
}
