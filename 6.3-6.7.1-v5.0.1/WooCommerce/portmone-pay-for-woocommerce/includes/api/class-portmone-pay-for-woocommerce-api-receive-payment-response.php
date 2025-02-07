<?php

defined( 'ABSPATH' ) || exit;

/**
 * receive payment response method
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Api_Receive_Payment_Response
{
    /**
     * @var Portmone_Pay_For_WooCommerce_Helper_Payment
     */
    private $helper_payment;

    public function __construct(Portmone_Pay_For_WooCommerce_Helper_Payment $helper_payment)
    {
        $this->helper_payment = $helper_payment;
    }

    public function receive_payment_response()
    {
        if (empty($_REQUEST['SHOPORDERNUMBER'])) {
            return false;
        }

        $order_id = $this->helper_payment->get_order_id($_REQUEST['SHOPORDERNUMBER']);
        $order = wc_get_order( $order_id );
        if ( ( ! $order instanceof WC_Order ) ) {
            return false;
        }

        // todo

        $this->helper_payment->process_payment_response($_REQUEST, $order);

        return true;
    }
}
