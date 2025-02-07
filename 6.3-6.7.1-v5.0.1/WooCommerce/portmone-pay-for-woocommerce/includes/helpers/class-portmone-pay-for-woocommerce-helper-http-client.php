<?php

defined( 'ABSPATH' ) || exit;

/**
 * Http_Client functions
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Helper_Http_Client
{

    public function create_link_Payment( Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment $data, WC_Order $order )
    {
        $create_link_payment = $this->curl_json_request($data);
        if ( is_wp_error( $create_link_payment ) ) {
            $order->add_order_note( '#22P ' . $create_link_payment->get_error_message() );
            $order->save();
            throw new \Exception( $create_link_payment->get_error_message() );
        }

        $result = json_decode( $create_link_payment, true );
        if ( $result['errorCode'] != '0' ) {
            $order->add_order_note( '#23P ' . $result['error'] );
            $order->save();
            throw new \Exception($result['error']);
        }

        if ( empty( $result['linkPayment'] ) ) {
            $order->add_order_note( '#24P ' . __( 'Помилка отримання посилання на оплату', 'portmone-pay-for-woocommerce' ) );
            $order->save();
            throw new \Exception(__('Помилка отримання посилання на оплату', 'portmone-pay-for-woocommerce' ) );                ;
        }

        return $result['linkPayment'];
    }

    private function curl_json_request( $data )
    {
        $json_data = json_encode( $data );
        $url = 'https://www.portmone.com.ua/gateway/';

        $ch = curl_init( $url );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Content-Length: ' . strlen($json_data)]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec( $ch );
        $http_code = (int)curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( 200 !== intval( $http_code ) ) {
            return new WP_Error( 'error', __( 'Помилка при надсиланні запиту', 'portmone-pay-for-woocommerce' ) );;
        }
        return $response;
    }
}
