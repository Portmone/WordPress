<?php

defined( 'ABSPATH' ) || exit;

/**
 * Token class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token
{
    public $tokenFlag = 'N';
    public $returnToken = 'N';
    public $token ='';
    public $cardMask = '';
    public $otherPaymentMethods = '';
}
