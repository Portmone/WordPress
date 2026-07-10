<?php

defined( 'ABSPATH' ) || exit;

/**
 * Types class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Types  implements JsonSerializable
{
    private $installment = 'N';

    public function jsonSerialize(): array
    {
        return get_object_vars( $this );
    }

    public function setInstallment(array $settings)
    {
        $this->installment = ( !empty( $settings['installment_flag'] ) && $settings['installment_flag'] == 'yes' ) ? 'Y' : 'N';
    }
}
