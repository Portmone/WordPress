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
     *
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

        // file added in woocommerce v7.1.0, maybe removed later
        $this->is_hpos_enabled = false;
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') and
            Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $this->is_hpos_enabled = true;
        }

        // Plugin options and settings
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
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
                'title'            => __( 'Логін Інтернет-магазину в системі Portmone.com', 'portmone-pay-for-woocommerce'  ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ) .'" class="red">*</span>',
                'type'             => 'text',
                'description'      => __( 'Логін Інтернет-магазину, повідомлений менеджером Portmone.com', 'portmone-pay-for-woocommerce'  ),
                'desc_tip'         => true),
            'password'             => array(
                'title'            => __( 'Пароль Інтернет-магазину в системі Portmone.com', 'portmone-pay-for-woocommerce' ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ).'" class="red">*</span>',
                'type'             => 'password',
                'description'      => __( 'Пароль для Інтернет-магазину, повідомлений менеджером Portmone.com', 'portmone-pay-for-woocommerce' ),
                'desc_tip'         => true),
            'exp_time'             => array(
                'title'            => __( 'Час на сплату', 'portmone-pay-for-woocommerce' ),
                'type'             => 'text',
                'description'      => __( 'Час відведенний на оплату через Portmone.com', 'portmone-pay-for-woocommerce' ),
                'default'          => 400,
                'desc_tip'         => true),
            'key'                  => array(
                'title'            => __( 'Ключ для підпису', 'portmone-pay-for-woocommerce' ) . ' <span title="'. __( 'Обов’язкове поле', 'portmone-pay-for-woocommerce' ) .'" class="red">*</span>',
                'type'             => 'text',
                'description'      => __( 'Ключ для підпису узгодженний з Portmone.com', 'portmone-pay-for-woocommerce' ),
                'desc_tip'         => true),
        );

        if ( get_woocommerce_currency() !== 'UAH' ) {
            $currency = get_woocommerce_currencies();

            $letters_convert_money_label = array('%1$s', '%2$s', '%3$s');
            $fruit_convert_money_label   = array( '<b>'.$currency[get_woocommerce_currency()] , '('.get_woocommerce_currency().')</b>', '<b>'.$currency['UAH'].' (UAH)</b>');
            $convert_money_label = str_replace($letters_convert_money_label, $fruit_convert_money_label, __( 'Конвертувати валюту магазину з %1$s %2$s > в > %3$s, при оформленні замовлення використовуючи власний курс конвертації який вказати нижче (стандартно, конвертація проходить за курсом НБУ, на сайті портмоне)', 'portmone-pay-for-woocommerce' ));

            $letters_exchange_rates_description = array('%1$s', '%2$s', '%3$s');
            $fruit_exchange_rates_description   = array( '<b>'.$currency['UAH'].' (UAH)</b>' , '<b>'.$currency[get_woocommerce_currency()], '('.get_woocommerce_currency().')</b>' );
            $exchange_rates_description = str_replace($letters_exchange_rates_description, $fruit_exchange_rates_description, __( 'Курс %1$s за 1 %2$s %3$s', 'portmone-pay-for-woocommerce' ));

            $this->form_fields = array_merge(
                $this->form_fields,
                array(
                    'convert_money'    => array('
                        title'         => __( 'Включити конвертацію в Гривні', 'portmone-pay-for-woocommerce' ),
                        'type'         => 'checkbox',
                        'label'        => $convert_money_label,
                        'default'      => 'no',
                        'description'  => __( 'Portmone.com приймає тільки Українські гривні. Ваша сума буде автоматично конвертована в UAH в Portmone.com за курсом НБУ. Якщо ви хочете вказати власний курс конвертації, виберіть цей пункт.', 'portmone-pay-for-woocommerce' ),
                        'desc_tip'     => true),
                    'exchange_rates'   => array(
                        'title'        => __( 'Курс валюти', 'portmone-pay-for-woocommerce' ),
                        'type'         => 'number',
                        'default'      => O,
                        'description'  => $exchange_rates_description,
                        'desc_tip'     => true)
                )
            );
        }

        $this->form_fields = array_merge(
            $this->form_fields,
            array(
                'title'                => array(
                    'title'            => __( 'Назва компанії', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'text',
                    'default'          => __( 'Оплата замовлення через Portmone.com', 'portmone-pay-for-woocommerce' ),
                    'description'      => __( 'Назва Інтернет-магазину, що відображається клієнту при оплаті', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'description'          => array(
                    'title'            => __( 'Коментар для клієнта', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'textarea',
                    'default'          => __( 'Сервіс проведення платежів забезпечується системою Portmone.com з використанням сучасного й безпечного механізму авторизації платіжних карт. Служба підтримки Portmone.com: телефон +380(44)200 09 02, електронна пошта: support@portmone.com', 'portmone-pay-for-woocommerce' ),
                    'description'      => __( 'Інформація для клієнта на сторінці оплати замовлення', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'preauth_flag'         => array(
                    'title'            => __( 'Режим преавторизаціі', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            => __( 'Засоби тільки блокуються на карті клієнта, але фінансового списання з рахунку клієнта не відбувається', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'no',
                    'description'      => __( 'Відзначте, щоб кошти тільки блокуються на карті клієнта, але фінансового списання з рахунку клієнта не відбувається', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                /*'showlogo'             => array(
                    'title'            => __( 'Включити режим показу на сторінці', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            => __( 'Включити на сторінці спосіб оплати', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'yes',
                    'description'      => __( 'Включити режим сторінки без перенаправлення', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),*/
                'show_admin_menu'      => array(
                    'title'            => __( 'Показати Portmone.com в списку меню адміністративної панелі', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'number',
                    'default'          => 1,
                    'description'      => __( 'Вкажіть порядковий номер відображення в списку меню. Якщо значення <1, Portmone.com перегляньте в списку меню не буде', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'update_count_products'=> array(
                    'title'            => __( 'Змінити кількість товарів на складі після оплати', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            => __( 'Від загальної суми товарів на складі відняти товари оплаченого замовленн', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'yes',
                    'description'      => __( 'Відзначте, щоб змінювати кількість товарів', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'save_client_first_last_name_flag'   => array(
                    'title'            => __( 'Зберегти ім\'я та прізвище клієнта', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            =>  __( 'Ім\'я та прізвище клієнта береться з адреси, вказаної в замовленні. Узгоджується з менеджером Portmone', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'no',
                    'description'      => __( 'Відзначте, щоб зберегти ім\'я та прізвище клієнта' ),
                    'desc_tip'         => true),
                'save_client_phone_number_flag'      => array(
                    'title'            => __( 'Зберегти телефон клієнта', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            => __( 'Телефон клієнта береться з адреси, зазначеної в замовленні. Узгоджується з менеджером Portmone', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'no',
                    'description'      => __( 'Відзначте, щоб зберегти телефон клієнта', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'save_client_email_flag'             => array(
                    'title'            => __( 'Зберегти email клієнта', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            => __( 'Email клієнта береться з адреси, зазначеної в замовленні. Узгоджується з менеджером Portmone', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'no',
                    'description'      => __( 'Відзначте, щоб зберегти email клієнта', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'split_payment_flag'                 => array(
                    'title'            => __( 'Розщеплення платежу', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            =>  __( 'Платіжна система Portmone.com надає можливість розщеплення 1 (одного) карткового платежу на декілька компаній (юридичних осіб). Продавець повинен додати в товар атрибут з іменем payee_id і значенням рівного індексу компанії в системі Portmone.com', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'no',
                    'description'      => __( 'Відзначте, щоб зробити розщеплення платежу', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
                'receive_notifications_flag'        => array(
                    'title'            => __( 'Отримувати повідомлення про успішну оплату', 'portmone-pay-for-woocommerce' ),
                    'type'             => 'checkbox',
                    'label'            => __( 'Отримувати повідомлення про успішну оплату у форматі JSON. Для активації даного функціоналу, будь ласка, напишіть на пошту b2bsupport@portmone.me', 'portmone-pay-for-woocommerce' ),
                    'default'          => 'no',
                    'description'      => __( 'Відзначте, щоб отримувати повідомлення про успішну оплату', 'portmone-pay-for-woocommerce' ),
                    'desc_tip'         => true),
            )
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
            throw new \Exception( __( 'Замовлення не знайдено', 'portmone-pay-for-woocommerce' ) );
        }

        $settings = get_option( 'woocommerce_portmone_settings', null );

        $attribute5  = $this->helper_common->get_attribute5( $settings, $order );
        if ( is_wp_error( $attribute5 ) ) {
            $order->add_order_note( '#21P ' . $attribute5->get_error_message() );
            $order->save();
            throw new \Exception( $attribute5->get_error_message() );
        }

        $createLinkPayment = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment();

        $createLinkPaymentPayee = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payee();
        $createLinkPaymentPayee->set_properties( $settings );
        $createLinkPayment->setPayee( $createLinkPaymentPayee );

        $createLinkPaymentOrder = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Order();
        $createLinkPaymentOrder->set_properties( $settings, $order );
        $createLinkPaymentOrder->set_attribute5( $attribute5 );
        $createLinkPayment->setOrder( $createLinkPaymentOrder );

        $createLinkPaymentToken = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Token();
        $createLinkPayment->setToken( $createLinkPaymentToken );

        $createLinkPaymentPayer = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Payer();
        $createLinkPaymentPayer->set_properties( $order );
        $createLinkPayment->setPayer( $createLinkPaymentPayer );

        $createLinkPayment->set_signature($settings);

        $linkPayment = $this->helper_http_client->create_link_payment( $createLinkPayment, $order );

        $this->helper_common->add_meta_data( $order, '_shop_order_number',  $createLinkPaymentOrder->getShopOrderNumber() );
        $this->helper_common->add_meta_data( $order, '_create_link_payment_bill_amount', $createLinkPaymentOrder->getBillAmount() );

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

        $order = wc_get_order( $order_id );
        if ( ( ! $order instanceof WC_Order ) ) {
            return new WP_Error( 'error',  '#46P ' . __( 'Замовлення не знайдено', 'portmone-pay-for-woocommerce' ) );
        }

        if ( ! $this->can_refund_order( $order ) ) {
            return new WP_Error( 'error',  '#47P ' . __( 'Refund failed.', 'woocommerce' ) );
        }

        $paymentMethod = $order->get_payment_method();
        if ( $paymentMethod != 'portmone' ) {
            return new WP_Error( 'error', '#48P ' . __( 'Замовлення не було сплачено через систему Portmone', 'portmone-pay-for-woocommerce' ) );
        }

        $settings = get_option('woocommerce_portmone_settings', null);

        if ( $settings['split_payment_flag'] == 'yes' && $order->get_total() != $amount ) {
            return new WP_Error( 'error',  '#49P ' . __( 'Для проведення часткового повернення, будь ласка, зверніться в службу підтримки Portmone.com', 'portmone-pay-for-woocommerce' ) );
        }

        $orderCurrency = $order->get_currency();
        if ( $orderCurrency !== 'UAH' ) {
            return new WP_Error( 'error',  '#50P ' . sprintf( __( 'Для повернення у валюті %s, будь ласка, зверніться до служби підтримки Portmone.com', 'portmone-pay-for-woocommerce' ), $orderCurrency ) );
        }

        $shop_order_number = $order->get_meta( '_shop_order_number' );
        if ( empty( $shop_order_number ) ) {
            return new WP_Error( 'error',  '#51P ' . __( 'Значення для shop_order_number не можуть бути порожніми', 'portmone-pay-for-woocommerce' ) );
        }

        $attribute5  = $this->helper_common->get_attribute5($settings, $order);
        if ( is_wp_error( $attribute5 ) ) {
            return new WP_Error( 'error',  '#54P ' .$attribute5->get_error_message() );
        }

        $data = new Portmone_Pay_For_WooCommerce_Dto_Return_Data();
        $data->setPayeeId( $settings['payee_id'] );
        $data->setLogin( $settings['login'] );
        $data->setPassword( $settings['password'] );
        $data->setShopOrderNumber( $shop_order_number );
        $data->setReturnAmount( $amount );
        $data->setAttribute5( $attribute5 );
        $data->setMessage( $reason );

        $body = new  Portmone_Pay_For_WooCommerce_Dto_Body();
        $body->setMethod('return' );
        $params = new stdClass();
        $params->data = $data;
        $body->setParams( $params );

        $portmone_order_data = $this->helper_http_client->get_portmone_order_data( $body );
        if ( is_wp_error( $portmone_order_data ) ) {
            return new WP_Error( 'error',  '#55P ' .$portmone_order_data->get_error_message() );
        }


        if ( $portmone_order_data['status'] == 'RETURN' ) {
            $order->add_order_note(
            /* translators: 1: Refund amount, 2: Refund ID */
                '#43P ' . sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'woocommerce' ), $portmone_order_data['billAmount'], $portmone_order_data['shopBillId'] )
            );
            return true;
        }

        return new WP_Error( 'error', '#56P ' . __('Невідома помилка', 'portmone-pay-for-woocommerce' ) );;
    }
}
