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
        if ( empty( $_REQUEST['SHOPORDERNUMBER'] ) ) {
           wp_safe_redirect( $this->get_return_url( null ) );
        }

        $order_id = $this->helper_payment->get_order_id($_REQUEST['SHOPORDERNUMBER']);
        $order = wc_get_order( $order_id );
        if ( ( ! $order instanceof WC_Order ) ) {
            wp_safe_redirect( $this->get_return_url( null ) );
        }


        $settings = get_option( 'woocommerce_portmone_settings', null );

        // Receive notifications about successful payment
        if ( $settings['receive_notifications_flag'] == 'yes' && $_REQUEST['RESULT'] == "0" ) {
            WC()->cart->empty_cart();
            wp_safe_redirect( $this->get_return_url( $order ) );
            return;
        }

        $message = '';
        $paymentInfo = $this->helper_payment->process_payment_response( $_REQUEST, $order );
        if ( is_wp_error( $paymentInfo ) ) {
           $message =  $paymentInfo->get_error_message();
        }

        if ( empty( $message ) && $_REQUEST['RESULT'] == '0' ) {
            WC()->cart->empty_cart();
        }

        wp_safe_redirect( $this->get_return_url( $order ) );
    }

    /**
     * Get the return url (thank you page).
     *
     * @param WC_Order|null $order Order object.
     * @return string
     */
    public function get_return_url( $order = null ) {
        if ( $order ) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
        }

        return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
    }
}
