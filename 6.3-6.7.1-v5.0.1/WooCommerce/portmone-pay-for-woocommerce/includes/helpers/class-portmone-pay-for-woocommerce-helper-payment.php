<?php

use Automattic\WooCommerce\Enums\OrderInternalStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Payment functions
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Helper_Payment
{
    /**
     * @param $shop_number
     *
     * @return bool|string
     */
    public function get_order_id( $shop_number )
    {
        $shop_number_count = strpos($shop_number, "_");
        if ( $shop_number_count == false ) {
            return $shop_number;
        }
        return substr( $shop_number, 0, $shop_number_count );
    }

    public function process_payment_response(array $response, WC_Order $order)
    {
        if ( ! $order->needs_payment() ) {
            return new WP_Error('error', '#1P ' . sprintf( __( 'Статус цього замовлення &ldquo;%s&rdquo; &mdash; його неможливо оплатити повторно', 'portmone-pay-for-woocommerce' ), wc_get_order_status_name( $order->get_status() ) ) );
        }

        if ($response['RESULT'] !== '0') {
            $result = $response['RESULT'] . ' ' . __( 'Номер вашого замовлення', 'portmone-pay-for-woocommerce' ) . ': ' . $order->get_id();
            $this->update_order($order, null, OrderInternalStatus::FAILED, '#5P ' . $result);
            return new WP_Error('error', '#5P ' . $result);
        }

    }


    public  function update_order(WC_Order $order, $pay_date, string $status, string $note) {
        $order->update_status( $status );
        $order->add_order_note( $note );
        if ( ! $order->get_date_paid( 'edit' ) && $pay_date !== null) {
            $order->set_date_paid( strtotime( $pay_date . '+02' ) );
        }

        $order->save();
        if ($status == 'wc-processing') {
            $order->payment_complete();
        }
    }
}
