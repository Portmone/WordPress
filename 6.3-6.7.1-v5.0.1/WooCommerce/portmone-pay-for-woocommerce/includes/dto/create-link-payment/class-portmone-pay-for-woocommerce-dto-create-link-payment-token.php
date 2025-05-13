<?php

defined( 'ABSPATH' ) || exit;

/**
 * Token class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token  implements JsonSerializable
{
    private $tokenFlag = 'N';
    private $returnToken = 'N';
    private $token ='';
    private $cardMask = '';
    private $otherPaymentMethods = '';

    public function jsonSerialize(): array
    {
        return get_object_vars( $this );
    }
}
