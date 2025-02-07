<?php

defined( 'ABSPATH' ) || exit;

/**
 * The payment gateway plugin class.
 *
 *
 * @since      5.0.1
 * @package    portmone-pay-for-woocommerce
 * @subpackage portmone-pay-for-woocommerce/includes
 * @author     Portmone
 */
class WC_Portmone extends WC_Payment_Gateway
{


    /**
     * Supported features such as 'default_credit_card_form', 'refunds'.
     *
     * @var array
     */
    public $supports = array(
        'products',
        'refunds'
    );

    /**
     * Can be set to true if you want payment fields
     * to show on the checkout (if doing a direct integration).
     * @var boolean
     */
    public $has_fields = false;

    /**
     * Unique ID for the gateway
     *
     * @var string
     */
    public $id = 'portmone';

    /**
     * Title of the payment method shown on the admin page.
     *
     * @var string
     */
    public $method_title = 'Portmone';


    /**
     * Description of the payment method shown on the admin page.
     *
     * @var  string
     */
    public $method_description = 'Allow customers to securely pay via Portmone (Credit/Debit Cards, NetBanking, UPI, Wallets)';

    /**
     * Icon URL, set in constructor
     *
     * @var string
     */
    public $icon;

    /**
     * hpos enabled check
     *
     * @var bool
     */
    public $is_hpos_enabled;

    /**
     * @var Portmone_Pay_For_WooCommerce_Helper_Common
     */
    private $helper_common;

    /**
     * @var Portmone_Pay_For_WooCommerce_Helper_Http_Client
     */
    private $helper_http_client;


    public function __construct()
    {
        $this->helper_common = new Portmone_Pay_For_WooCommerce_Helper_Common();
        $this->helper_http_client = new Portmone_Pay_For_WooCommerce_Helper_Http_Client();


        $this->is_hpos_enabled = false;

        // file added in woocommerce v7.1.0, maybe removed later
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') and
            Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $this->is_hpos_enabled = true;
        }

        // Plugin options and settings
        $this->init_form_fields();
        $this->init_settings();
    }

    /**
     * Initializing the configuration form in the admin panel
     */
    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'              => array(
                'title'            => __( 'Включити прийом оплати через Portmone.com', 'portmone-pay-for-woocommerce' ),
                'type'             => 'checkbox',
                'label'            => __( 'Включити Portmone.com модуль оплати', 'portmone-pay-for-woocommerce' ),
                'default'          => 'no'),
            'payee_id'             => array(
                'title'            => __( 'Ідентифікатор магазину в системі Portmone.com (Payee ID)', 'portmone-pay-for-woocommerce' ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ) .'" class="red">*</span>',
                'type'             => 'text',
                'description'      => __( 'ID Інтернет-магазину, повідомлений менеджером Portmone.com', 'portmone-pay-for-woocommerce'  ),
                'desc_tip'         => true),
            'login'                => array(
                'title'            => __( 'Логин Интернет-магазина в системе Portmone.com', 'portmone-pay-for-woocommerce'  ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ) .'" class="red">*</span>',
                'type'             => 'text',
                'description'      => __( 'Логін Інтернет-магазину, повідомлений менеджером Portmone.com', 'portmone-pay-for-woocommerce'  ),
                'desc_tip'         => true),
            'password'             => array(
                'title'            => __( 'Пароль Інтернет-магазину в системі Portmone.com', 'portmone-pay-for-woocommerce' ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ).'" class="red">*</span>',
                'type'             => 'password',
                'description'      => __( 'Пароль для Інтернет-магазину, повідомлений менеджером Portmone.com', 'portmone-pay-for-woocommerce' ),
                'desc_tip'         => true),
            'exp_time'             => array(
                'title'            => __( 'Час на сплату', 'portmone-pay-for-woocommerce' ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ).'"></span>',
                'type'             => 'text',
                'description'      => __( 'Час відведенний на оплату через Portmone.com', 'portmone-pay-for-woocommerce' ),
                'desc_tip'         => true),
            'key'             => array(
                'title'            => __( 'Ключ для підпису', 'portmone-pay-for-woocommerce' ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ) .'" class="red">*</span>',
                'type'             => 'text',
                'description'      => __( 'Ключ для підпису узгодженний з Portmone.com', 'portmone-pay-for-woocommerce' ),
                'desc_tip'         => true),
        );
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     **/
    public function admin_options()
    {
        ob_start();
        $this->generate_settings_html();
        $settings = ob_get_contents();
        ob_end_clean();

        $variables = array(
            '{version}'           => PORTMONE_PAY_FOR_WOOCOMMERCE_VERSION,
            '{logo}'              => '<h3><img src="'. PORTMONE_PAY_FOR_WOOCOMMERCE_URL . 'assets/images/portmonepay.svg' . '" alt="' . $this->method_title . '"></h3>',
            '{configuration}'     => __( 'Налаштування', 'portmone-pay-for-woocommerce' ),
            '{information}'       => __( 'Інформація', 'portmone-pay-for-woocommerce' ),
            '{settings}'          => $settings ,
            '{error}'             => '',
            '{upgrade_notice}'    => '',
            '{payment_module}'    => __( 'Платіжний плагін Portmone.com', 'portmone-pay-for-woocommerce' ),
            '{IC_version}'        => __( 'Версія плагіну', 'portmone-pay-for-woocommerce' ),
            '{WC_version_label}'  => __('WC Version', 'woocommerce'),
            '{WC_version}'        => WC()->version,
            '{WC_actual}'         => $this->helper_common->get_wc_actual(),
            '{WP_version_label}'  => __('WP Version', 'woocommerce'),
            '{WP_version}'        => get_bloginfo('version') . ' (' . get_bloginfo('language') . ')',
            '{WP_actual}'         => $this->helper_common->get_wp_actual()
        );

        $template = file_get_contents(PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/admin/views/portmone-pay-for-woocommerce-admin-display.php');
        foreach ($variables as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        echo $template;
    }

    /**
     * Process Payment.
     *
     * Process the payment. Override this in your gateway. When implemented, this should.
     * return the success and redirect in an array. e.g:
     *
     *        return array(
     *            'result'   => 'success',
     *            'redirect' => $this->get_return_url( $order )
     *        );
     *
     * @param int $order_id Order ID.
     * @return array
     */
    function process_payment( $order_id )
    {
        $order = wc_get_order( $order_id );
        if ( ( ! $order instanceof WC_Order ) ) {
            throw new \Exception(__( 'Замовлення не знайдено', 'portmone-pay-for-woocommerce' ));;
        }

        $settings = get_option('woocommerce_portmone_settings', null);

        $attribute5  = $this->helper_common->get_attribute5($settings, $order);
        if ( is_wp_error( $attribute5 ) ) {
            $order->add_order_note('#21P ' . $attribute5->get_error_message());
            $order->save();
            throw new \Exception($attribute5->get_error_message());
        }

        $createLinkPayment = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment();

        $createLinkPayment->payee = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee();
        $createLinkPayment->payee->set_properties($settings);

        $createLinkPayment->order = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order();
        $createLinkPayment->order->set_properties($settings, $order);
        $createLinkPayment->order->set_attribute5($attribute5);

        $createLinkPayment->token = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token();

        $createLinkPayment->payer = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payer();
        $createLinkPayment->payer->set_properties($order);

        $createLinkPayment->set_signature($settings);

        $linkPayment = $this->helper_http_client->create_link_Payment($createLinkPayment, $order);

        if ($order->meta_exists('_shop_order_number')) {
            $order->update_meta_data( '_shop_order_number', $createLinkPayment->order->shopOrderNumber );
        } else {
            $order->add_meta_data( '_shop_order_number', $createLinkPayment->order->shopOrderNumber );
        }
        $order->save();

        return array('result' => 'success', 'redirect' => $linkPayment);
    }

    /**
     * Process refund.
     *
     * If the gateway declares 'refunds' support, this will allow it to refund.
     * a passed in amount.
     *
     * @param  int        $order_id Order ID.
     * @param  float|null $amount Refund amount.
     * @param  string     $reason Refund reason.
     * @return bool|\WP_Error True or false based on success, or a WP_Error object.
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        return true;
    }



}