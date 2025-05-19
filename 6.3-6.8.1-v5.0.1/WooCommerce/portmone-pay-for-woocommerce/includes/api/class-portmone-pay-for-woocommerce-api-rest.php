<?php

defined( 'ABSPATH' ) || exit;

/**
 * Rest API
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/api
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Api_Rest
{

    /**
     * This function is where we register our routes for our example endpoint.
     */
    public function portmone_register_routes() {
        // register_rest_route() handles more arguments but we are going to stick to the basics for now.
        register_rest_route( 'portmone-pay/v1', '/notification', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' =>  [
                __CLASS__,
                'portmone_endpoint_notification',
            ],
            'permission_callback' => '__return_true',
        ) );
    }

    /*
     * processing notification from the wallet in case of successful payment
     */
    public function portmone_endpoint_notification( $request )
    {
        $responseData = [
            'errorCode'  => "0",
            'reason'     => "OK",
            'responseId' => (string)time()
        ];

        $settings = get_option( 'woocommerce_portmone_settings', null );
        if ( empty( $settings['receive_notifications_flag'] ) || $settings['receive_notifications_flag'] == 'no' ) {
            return rest_ensure_response( $responseData );
        }

        $request_params = $request->get_json_params();
        if ( empty( $request_params ) || empty( $request_params['shopOrderNumber'] ) || empty( $request_params['shopBillId'] ) ) {
            return rest_ensure_response( $responseData );
        }

        $helper_payment = new Portmone_Pay_For_WooCommerce_Helper_Payment();

        $order_id = $helper_payment->get_order_id( $request_params['shopOrderNumber'] );
        $order = wc_get_order( $order_id );
        if ( ( ! $order instanceof WC_Order ) ) {
            return rest_ensure_response( $responseData );
        }

        $result =  $helper_payment->process_payment_notification( $request_params, $order, $settings );
        if ( is_wp_error( $result ) ) {
            $order->add_order_note( '#101P ' . __( 'Помилка під час обробки нотифікації', 'portmone-pay-for-woocommerce' ) . '. '. $result->get_error_message() );
            $order->save();
        }

        return rest_ensure_response($responseData);
    }
}
