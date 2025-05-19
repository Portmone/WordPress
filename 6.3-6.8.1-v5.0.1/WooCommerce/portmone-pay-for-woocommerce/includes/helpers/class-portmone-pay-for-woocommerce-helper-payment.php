<?php

use Automattic\WooCommerce\Enums\OrderInternalStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Payment Process Functionality
 *  - Processing requests from the payment gateway
 *  - Order status changes
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Helper_Payment
{
    const ORDER_PAYED       = 'PAYED';
    const ORDER_CREATED     = 'CREATED';
    const ORDER_REJECTED    = 'REJECTED';
    const ORDER_PREAUTH     = 'PREAUTH';
    const ORDER_RETURN      = 'RETURN';

    /**
     * get order_id
     *
     * @param string $shop_number
     *
     * @return bool|string
     */
    public function get_order_id( string $shop_number )
    {
        $shop_number_count = strpos($shop_number, "_");
        if ( $shop_number_count == false ) {
            return $shop_number;
        }
        return substr( $shop_number, 0, $shop_number_count );
    }

    /**
     * Processing requests from the payment gateway
     * Transition from the payment page to the partner's website
     *
     * @param array $response
     * @param WC_Order $order
     * @return void|WP_Error
     * @throws WC_Data_Exception
     */
    public function process_payment_response(array $response, WC_Order $order )
    {
        if ( ! $order->needs_payment() ) {
            $note = '#1P ' . sprintf( __( 'Статус цього замовлення &ldquo;%s&rdquo; &mdash; його неможливо оплатити повторно', 'portmone-pay-for-woocommerce' ), wc_get_order_status_name( $order->get_status() ) );
            $order->add_order_note( $note );
            $order->save();
            return new WP_Error('error', $note );
        }

        if ( $response['RESULT'] !== '0' ) {
            $result = $response['RESULT'] . ' ' . __( 'Номер вашого замовлення', 'portmone-pay-for-woocommerce' ) . ': ' . $order->get_id();
            $this->update_order($order, null, OrderInternalStatus::FAILED, '#2P ' . $result);
            return new WP_Error('error', '#2P ' . $result);
        }

        $settings = get_option( 'woocommerce_portmone_settings', null );

        $portmone_order_data = $this->get_portmone_order_data( $response['SHOPBILLID'], $settings );
        if ( is_wp_error( $portmone_order_data ) ) {
            $note = '#3P ' . $portmone_order_data->get_error_message();
            $order->add_order_note( $note );
            $order->save();
            return new WP_Error('error', $note );
        }

        $order->set_transaction_id( $response['SHOPBILLID'] );

        $check =  $this->check_portmone_order_data( $portmone_order_data, $settings, $order );
        if ( is_wp_error( $check ) ) {
            $note = '#20P ' . $check->get_error_message();
            $order->add_order_note( $note );
            $order->save();
            return new WP_Error('error', $note );
        }

        $this->change_order_status( $portmone_order_data, $order, $settings );
    }

    /**
     * Processing requests from the payment gateway
     * -Notification processing (api request)
     *
     * @param array $notification
     * @param WC_Order $order
     * @param array $settings
     * @return void|WP_Error
     * @throws WC_Data_Exception
     */
    public function process_payment_notification( array $notification, WC_Order $order, array $settings )
    {
        if ( ! $order->needs_payment() ) {
            $note = '#102P ' . sprintf( __( 'Статус цього замовлення &ldquo;%s&rdquo; &mdash; його неможливо оплатити повторно', 'portmone-pay-for-woocommerce' ), wc_get_order_status_name( $order->get_status() ) );
            $order->add_order_note( $note );
            $order->save();
            return new WP_Error('error', $note );
        }

        $portmone_order_data = $this->get_portmone_order_data( $notification['shopBillId'], $settings );
        if ( is_wp_error( $portmone_order_data ) ) {
            $note = '#103P ' . $portmone_order_data->get_error_message();
            $order->add_order_note( $note );
            $order->save();
            return new WP_Error('error', $note );
        }

        $order->set_transaction_id( $notification['shopBillId'] );

        $check =  $this->check_portmone_order_data( $portmone_order_data, $settings, $order );
        if ( is_wp_error( $check ) ) {
            $note = '#104P ' . $check->get_error_message();
            $order->add_order_note( $note );
            $order->save();
            return new WP_Error('error', $note );
        }

        $this->change_order_status( $portmone_order_data, $order, $settings );
    }

    /**
     * Change order status
     *
     * @param array $portmone_order_data
     * @param WC_Order $order
     * @param array $settings
     * @return void|WP_Error
     */
    public function change_order_status( array $portmone_order_data, WC_Order $order, array $settings )
    {
        if ( $portmone_order_data['status'] == self::ORDER_REJECTED ) {
            $this->update_order($order, $portmone_order_data['pay_date'], OrderInternalStatus::FAILED, '#9P ' . __( 'Під час здійснення оплати виникла помилка. Перевірте дані вашої картки та спробуйте здійснити оплату ще раз!', 'portmone-pay-for-woocommerce' ));
            return new WP_Error('error', '#9P ' . __( 'Під час здійснення оплати виникла помилка. Перевірте дані вашої картки та спробуйте здійснити оплату ще раз!', 'portmone-pay-for-woocommerce' ) . ' ' .  __( 'Номер вашого замовлення', 'portmone-pay-for-woocommerce' )  . ': ' . $order->get_id());
        }

        if ( $portmone_order_data['status'] == self::ORDER_PREAUTH ) {
            $this->update_order($order, $portmone_order_data['pay_date'], 'wc-status-preauth', '#10P ' .  __( 'Оплачено за допомогою Portmone.com (блокування коштів)', 'portmone-pay-for-woocommerce' ));
            $this->send_notification_email( $order, 'WC_Email_Customer_Processing_Order' );
        }

        if ( $portmone_order_data['status'] == self::ORDER_CREATED ) {
            $this->update_order( $order, $portmone_order_data['pay_date'], OrderInternalStatus::FAILED, '#13P ' . __( 'Під час здійснення оплати виникла помилка. Перевірте дані вашої картки та спробуйте здійснити оплату ще раз!', 'portmone-pay-for-woocommerce' ) );
            return new WP_Error('error', '#13P ' . __( 'Під час здійснення оплати виникла помилка. Перевірте дані вашої картки та спробуйте здійснити оплату ще раз!', 'portmone-pay-for-woocommerce' ) );
        }

        if ( $portmone_order_data['status'] == self::ORDER_PAYED ) {
            $this->update_order( $order, $portmone_order_data['pay_date'], OrderInternalStatus::PROCESSING, '#14P ' .  __( 'Оплату здійснено успішно через Portmone.com', 'portmone-pay-for-woocommerce' ) ) ;
            $this->update_count_products( $order, $settings );
            $this->send_notification_email( $order, 'WC_Email_Customer_Processing_Order' );
        }
    }

    /**
     * Receiving order data in the portmone system
     *
     * @param string $shop_bill_id
     * @param array $settings
     * @return array|mixed|WP_Error
     */
    private function get_portmone_order_data( string $shop_bill_id, array $settings )
    {
        $data = new Portmone_Pay_For_WooCommerce_Dto_Result_Data();
        $data->setPayeeId( $settings['payee_id'] );
        $data->setLogin( $settings['login'] );
        $data->setPassword( $settings['password'] );
        $data->setShopbillId( $shop_bill_id );

        $body = new  Portmone_Pay_For_WooCommerce_Dto_Body();
        $body->setMethod('result' );
        $params = new stdClass();
        $params->data = $data;
        $body->setParams( $params );

        $http_client = new Portmone_Pay_For_WooCommerce_Helper_Http_Client();

        return $http_client->get_portmone_order_data( $body );
    }

    /**
     * Check order data
     *
     * @param array $portmone_order_data
     * @param array $settings
     * @param WC_Order $order
     * @return void|WP_Error
     */
    private function check_portmone_order_data( array $portmone_order_data, array $settings, WC_Order $order )
    {
        if ( $settings['payee_id'] !=  $portmone_order_data['payee_id'] ) {
            return new WP_Error('error', '#17P ' . __( 'Під час здійснення оплати виникла помилка.', 'portmone-pay-for-woocommerce')  . ' '. __( 'Дані Інтернет-магазину некоректні', 'portmone-pay-for-woocommerce' ) );
        }

        $shop_order_number = $order->get_meta( '_shop_order_number' );
        if ( empty( $shop_order_number ) || $shop_order_number != $portmone_order_data['shopOrderNumber'] ) {
            return new WP_Error('error', '#18P ' . __( 'Під час здійснення оплати виникла помилка.', 'portmone-pay-for-woocommerce')  . ' '. __( 'Номер замовлення некоректний', 'portmone-pay-for-woocommerce' ) );
        }

        if ( $order->get_currency() == 'UAH' || $settings['convert_money'] == 'yes' ) {
            $bill_amount = $order->get_meta( '_create_link_payment_bill_amount' );
            if ( empty( $bill_amount ) || $bill_amount != $portmone_order_data['billAmount'] ) {
                return new WP_Error('error', '#19P ' . __( 'Під час здійснення оплати виникла помилка.', 'portmone-pay-for-woocommerce')  . ' '. __( 'Сплачена сума некоректна', 'portmone-pay-for-woocommerce' ) );
            }
        }

    }

    /**
     * Update order
     *
     * @param WC_Order $order
     * @param $pay_date
     * @param string $status
     * @param string $note
     * @return void
     * @throws WC_Data_Exception
     */
    private function update_order( WC_Order $order, $pay_date, string $status, string $note )
    {
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


    /**
     * Sending a letter to the user after successful payment
     *
     * @param WC_Order $order
     * @param $view
     * @return void
     */
    private function send_notification_email(WC_Order $order, $view )
    {
        $wc_email = WC()->mailer()->get_emails()[$view];
        $wc_email_admin = WC()->mailer()->get_emails()['WC_Email_New_Order'];

        $wc_email_admin->settings['subject'] = $wc_email->settings['subject'] = __( '{site_title}' );
        $wc_email_admin->settings['heading'] = $wc_email->settings['heading'] = __( 'Новый заказ' );

        $wc_email->recipient = $order->get_billing_email();
        $wc_email->trigger($order->get_id(), $order);
        $order->add_order_note( __( 'Email користувачеві доданий у чергу на відправку', 'portmone-pay-for-woocommerce' ) ) ;

        $wc_email_admin->recipient = get_option('admin_email');
        $wc_email_admin->trigger($order->get_id(), $order);
    }


    /**
     * We write off goods from the warehouse
     *
     * @param WC_Order $order
     * @param array $settings
     * @return void
     */
    private function update_count_products(WC_Order $order, array $settings )
    {
        if (isset($settings['update_count_products']) && $settings['update_count_products'] == 'yes') {
            wc_reduce_stock_levels( $order->get_id() );
        }
    }
}
