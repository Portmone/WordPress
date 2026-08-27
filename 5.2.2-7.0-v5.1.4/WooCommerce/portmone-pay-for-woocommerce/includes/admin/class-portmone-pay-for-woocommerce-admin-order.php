<?php

defined( 'ABSPATH' ) || exit;

/**
 * Plugin functionality specific to working with orders in the admin panel.
 *
 * @package    portmone-pay-for-woocommerce
 * @subpackage portmone-pay-for-woocommerce/admin
 * @author     Portmone
 */
class Portmone_Pay_For_WooCommerce_Admin_Order
{
    /**
     * The ID of this plugin.
     *
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * @var Portmone_Pay_For_WooCommerce_Helper_Common
     */
    private $helper_common;

    /**
     * @var Portmone_Pay_For_WooCommerce_Helper_Http_Client
     */
    private $helper_http_client;

    public function __construct( string $plugin_name )
    {
        $this->plugin_name = $plugin_name;

        $this->helper_common = new Portmone_Pay_For_WooCommerce_Helper_Common();
        $this->helper_http_client = new Portmone_Pay_For_WooCommerce_Helper_Http_Client();
    }

    /**
     * Register the JavaScript for the admin area.
     *
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        $handle = $this->plugin_name . '-admin-order';
        wp_enqueue_script( $handle, PORTMONE_PAY_FOR_WOOCOMMERCE_URL . 'assets/js/portmone-pay-for-woocommerce-admin-order.js', array( 'jquery', 'wp-i18n' ), time(), true );

        // Безпечне отримання ID замовлення з URL
        $order_id = 0;
        if ( isset( $_GET['id'] ) ) {
            $order_id = absint( $_GET['id'] );
        } elseif ( isset( $_GET['post'] ) ) {
            $order_id = absint( $_GET['post'] );
        }

        wp_localize_script( $handle, 'portmone_pay_for_WooCommerce_admin_order_params', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'order_id' => $order_id,
                'nonce'    => wp_create_nonce( 'portmone_pay_for_woocommerce_admin_order_preauth_nonce' ),

                'i18n'     => array(
                        'confirm_capture' => __( 'Ви впевнені, що хочете cписати заблоковану суму з картки покупця. Підтверджується фінальна сума, вона може бути меншою за заблоковану.', 'portmone-pay-for-woocommerce' ),
                        'loading'         => __( 'Обробка платежу...', 'portmone-pay-for-woocommerce' ),
                        'button_text'     => __( 'Підтвердити оплату', 'portmone-pay-for-woocommerce' ),
                        'success_title'   => __( 'Успішно!', 'portmone-pay-for-woocommerce' ),
                        'error_title'     => __( 'Помилка списання: ', 'portmone-pay-for-woocommerce' ),
                        'critical_error'  => __( 'Сталася критична помилка запиту до сервера. Спробуйте оновити сторінку.', 'portmone-pay-for-woocommerce' )
                )
        ) );
    }


    public function add_button_confirm_payment( WC_Order $order )
    {
        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

        // Перевірка методу та статусу (без wc-)
        if ( 'portmone' === $order->get_payment_method() && 'status-preauth' === $order->get_status() ) {
            ?>
            <button type="button"
                    class="button button-primary portmone-fast-capture-btn"
                    style="margin-left: 5px; vertical-align: middle;">
                <?php echo esc_html( __( 'Підтвердити оплату', 'portmone-pay-for-woocommerce' ) ); ?>
            </button>
            <?php
        }
    }

    public function allow_editing_in_status_preauth_status( $is_editable, $order )
    {
        // Перевіряємо, чи це об'єкт замовлення і чи його статус відповідає нашому кастомному (без wc-)
        if ( $order && 'status-preauth' === $order->get_status() ) {
            return true;
        }

        return $is_editable;
    }

    public function ajax_handle_process_preauth()
    {
        // Перевірка безпеки (сувора вимога WordPress)
        check_ajax_referer( 'portmone_pay_for_woocommerce_admin_order_preauth_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( array( 'message' => '#58P ' . __( 'У Вас немає прав для цієї дії', 'portmone-pay-for-woocommerce' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => '#59P ' . __( 'Замовлення не знайдено', 'portmone-pay-for-woocommerce' ) ) );
        }

        $paymentMethod = $order->get_payment_method();
        if ( $paymentMethod != 'portmone' ) {
            wp_send_json_error( array( 'message' => '#60P ' . __( 'Замовлення не було сплачено через систему Portmone', 'portmone-pay-for-woocommerce' ) ) );
        }

        if ( 'status-preauth' !== $order->get_status() ) {
            wp_send_json_error( array( 'message' => '#61P ' . __( 'Підтвердження можна зробити лише для замовлення у статусі Оплачено з Portmone.com (блокування коштів)', 'portmone-pay-for-woocommerce' ) ) );
        }

        $settings = get_option('woocommerce_portmone_settings', null);

        $shop_order_number = $order->get_meta( '_shop_order_number' );
        if ( empty( $shop_order_number ) ) {
            wp_send_json_error( array( 'message' => '#62P ' . __( 'Значення для shop_order_number не можуть бути порожніми', 'portmone-pay-for-woocommerce' ) ) );
        }

        $data = new Portmone_Pay_For_WooCommerce_Dto_Confirm_Preauth_Data();
        $data->setPayeeId( $settings['payee_id'] );
        $data->setLogin( $settings['login'] );
        $data->setPassword( $settings['password'] );
        $data->setShopOrderNumber( $shop_order_number );
        $data->setPostauthAmount( $this->helper_common->get_order_total( $settings, $order ) );

        $goods = $this->helper_common->get_goods( $settings, $order );
        $data->setGoods( $goods );

        if ( isset($settings['test_mode_flag']) && $settings['test_mode_flag'] == 'yes' ) {
            $this->helper_common->add_meta_data( $order, 'confirm_preauth_data',  json_encode( $data ) );
            $order->save();
        }

        $body = new  Portmone_Pay_For_WooCommerce_Dto_Body();
        $body->setMethod('confirmPreauth' );

        $params = new stdClass();
        $params->data = $data;
        $body->setParams( $params );

        $result = $this->helper_http_client->portmone_confirm_preauth( $body );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => '#63P ' . $result->get_error_message() ) );
        }

        if ( $result['status'] === 'PAYED' ) {
            $order->update_status( 'processing' );
            $order->add_order_note( '#64P ' .  __( 'Платіж підтверджено успішно через Portmone.com', 'portmone-pay-for-woocommerce' ) );
            $order->save();
            $order->payment_complete();
            if (isset($settings['update_count_products']) && $settings['update_count_products'] == 'yes') {
                wc_reduce_stock_levels( $order->get_id() );
            }


            wp_send_json_success( array( 'message' => '#64P ' .  __( 'Платіж підтверджено успішно через Portmone.com', 'portmone-pay-for-woocommerce' ) ) );
        }

        wp_send_json_error( array( 'message' => '#63P ' .  __( 'Невідома помилка', 'portmone-pay-for-woocommerce' ) ) );
    }

}
