<?php
/*
Plugin Name: Portmone pay for woocommerce
Plugin URI: https://github.com/Portmone/WordPress
Description: Portmone Payment Gateway for WooCommerce.
Version: 2.0.7
Author: glib.yuriiev@portmone.me
Author URI: https://www.portmone.com.ua
Domain Path: /
License: Payment Card Industry Data Security Standard (PCI DSS)
License URI: https://www.portmone.com.ua/r3/uk/security/
WC requires at least: 3.7.1
WC tested up to: 4.1.0
*/

    /**
     * Connecting language files
     */
    add_action("init", "portmone_languages");
    function portmone_languages() {
        load_plugin_textdomain("portmone-pay-for-woocommerce", false, basename(dirname(__FILE__))."/languages");
    }

    /**
     * Connecting images
     */
    define('PORTMONE_IMGDIR', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');

    /**
     * Hook plug-in Portmone
     */
    add_action('plugins_loaded', 'woocommerce_portmone_init', 0);

    function plagin_links($links, $file) {
        $base = plugin_basename(__FILE__);
        if ($file == $base) {
            $links[] = '<a href="https://www.portmone.com.ua/r3/uk/security/" target="_blank">' .
                __('License') . '</a>';
        }
        return $links;
    }

    function plagin_actions($links) {
        return array_merge(array(
            'settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=portmone">' .
                __( 'Настройки', 'portmone-pay-for-woocommerce' ) . '</a>'), $links);
    }


    add_filter('plugin_row_meta', 'plagin_links', 10, 2);
    add_filter('plugin_action_links_' . plugin_basename( __FILE__ ),'plagin_actions');

function woocommerce_portmone_init() {

    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    function portmone_scripts () {
        wp_enqueue_script('portmone-js', plugin_dir_url(__FILE__) . 'assets/js/portmone.js', array('jquery'));
    }
    function portmone_css () {
        if (isset($_GET['message-portmone']) && !empty($_GET['message-portmone'])) {
            wp_enqueue_style('portmone-checkout', plugin_dir_url(__FILE__) . 'assets/css/portmone_styles.css');
            add_action('the_content', 'showPortmoneMessage');
        }
    }
    function portmone_admin_css () {
        wp_enqueue_style('portmone-checkout', plugin_dir_url(__FILE__) . 'assets/css/portmone_styles_admin.css');
    }

    // Добавить функции в список загрузки WP.
    add_action ('init', 'portmone_scripts');
    add_action ('init', 'portmone_css');
    add_action ('admin_init', 'portmone_scripts');
    add_action ('admin_init', 'portmone_admin_css');

    function showPortmoneMessage($content) {
        return '<div id="message-portmone" class="successful-message-portmone is-style-wide " style="display: block;">
                    <div>
                        <a onclick="openbox();" title="Закрыть" class="close">X</a>
                        <img class="img-portmone" src="' . PORTMONE_IMGDIR . 'portmonepay.svg">
                    <div>' . $_GET['message-portmone'] . '</div>
                    </div>
               </div>' . $content;
    }

    /**
     * Gateway class
     **/
    class WC_portmone extends WC_Payment_Gateway {
        const ORDER_PAYED       = 'PAYED';
        const ORDER_CREATED     = 'CREATED';
        const ORDER_REJECTED    = 'REJECTED';
        const ORDER_PREAUTH     = 'PREAUTH';
        const GATEWAY_URL       = 'https://www.portmone.com.ua/gateway/';
        private $t_lan          = array();  // массив переведенных текстов
        private $m_lan          = array();  // массив дефолтных текстов
        private $m_settings     = array();  // массив полученых настроек
        private $order_total    = 0;

        public function __construct() {
            $this->version = '2.0.7';
            $this->currency = get_woocommerce_currencies();
            $this->m_lan = array(
                'enabled_title'                 => 'Включить прием оплаты через Portmone.com',
                'enabled_label'                 => 'Включить Portmone.com модуль оплаты',
                'payee_id_title'                => 'Идентификатор магазина в системе Portmone.com (Payee ID)',
                'payee_id_span_title'           => 'Обязательно для заполнения',
                'payee_id_description'          => 'ID Интернет-магазина, предоставленный менеджером Portmone.com',
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
            );

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
            $this->m_settings = array(
                'enabled',
                'payee_id',
                'login',
                'password',
                'title',
                'description',
                'preauth_flag',
                'showlogo'
            );

            if ($this->settings['showlogo'] == "yes") {
                $this->icon = PORTMONE_IMGDIR . 'portmonepay.svg';
            }

            foreach ($this->m_settings as  $value) {
                $this->$value = $this->settings[$value];
            }

            $this->message['message']   = "";
            $this->message['class']     = "";
            $this->init_form();

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action( 'woocommerce_thankyou_portmone', array($this, 'check_response') );
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('init', array(&$this, 'check_response'));
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

            add_action('woocommerce_receipt_portmone', array(&$this, 'receipt_page'));
            add_action('woocommerce_thankyou', array($this, 'pending_new_order_notification'), 20, 1);
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
                'preauth_flag'          => array('title' => $this->t_lan['preauth_flag_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['preauth_flag_label'],
                    'default'          => 'no',
                    'description'      => $this->t_lan['preauth_flag_description'],
                    'desc_tip'         => true),
                'showlogo'             => array('title' => $this->t_lan['showlogo_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['showlogo_label'],
                    'default'          => 'yes',
                    'description'      => $this->t_lan['showlogo_description'],
                    'desc_tip'         => true)
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
                '{version}'           => $this->version,
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
                '{WP_version_label}'  => __('WP Version', 'woocommerce'),
                '{WP_version}'        => get_bloginfo('version') . ' (' . get_bloginfo('language') . ')'
            );

            $template = file_get_contents(plugin_dir_path(__FILE__) . 'view/admin.php');
            foreach ($variables as $key => $value) {
                $template = str_replace($key, $value, $template);
            }

            echo $template;
        }

    /**
     * Receipt Page
     **/
        function receipt_page($order) {
            echo $this->generate_portmone_form($order);
        }

    /**
     * Generate payu button link
     **/
        function generate_portmone_form($order_id) {
            $description_order = '';
            $order = new WC_Order($order_id);
            /*$items = $order->get_items();
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (version_compare( WOOCOMMERCE_VERSION , '3.0.0', '>=')) {
                        $description = '('.$this->t_lan['IDproduct'].' ' . $item['product_id'].' ) ('.$this->t_lan['quantity'].' '. $item['quantity'] . ' ) ('.$this->t_lan['price'].' '. $item['total'].' ) | ';
                    } else {
                        $description = '('.$this->t_lan['IDproduct'].' ' . $item["item_meta"]["_product_id"][0].' ) ('.$this->t_lan['quantity'].' '. $item["item_meta"]["_qty"][0] . ' ) ('.$this->t_lan['price'].' '. $item["item_meta"]["_line_subtotal"][0].' ) | ';
                    }
                    $description_order .= $description;
                }
                $description_order .= 'TOTAL '. $order->order_total;
            }*/

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

            $portmone_args = array(
                'payee_id'           => $this->payee_id,
                'shop_order_number'  => $order_id,
                'bill_amount'        => $this->order_total,
                'bill_currency'      => $this->bill_currency,
                'success_url'        => $order->get_checkout_order_received_url().'&status=success',
                'failure_url'        => $order->get_checkout_order_received_url().'&status=failure',
                'lang'               => $this->getLanguage(),
                'preauth_flag'       => $this->getPreauthFlag(),
                'encoding'           => 'UTF-8'
            );
            if ($description_order != '') {
                $portmone_args['description'] = $description_order;
            }
            $out = '';
                foreach ($portmone_args as $key => $value) {
                    $portmone_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
                }
                $out .= '<form action="' . self::GATEWAY_URL . '" method="post" id="portmone_payment_form">
                    ' . implode('', $portmone_args_array) . '
                <input type="submit" id="submit_portmone_payment_form" value="' . $this->t_lan['submit_portmone'] . '" /></form>';

            return $out;
        }

        /**
         * @param int $order_id
         *
         * @return array
         */
        function process_payment($order_id) {
            $order = new WC_Order($order_id);

            if (version_compare( WOOCOMMERCE_VERSION , '2.1.0', '>=')) {
                $payment_url = $order->get_checkout_payment_url(true);
            } else {
                $payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }
               return array('result' => 'success', 'redirect' => add_query_arg('order_pay', $order_id, $payment_url));
        }

    /**
     * Return page after payment
     **/
        private function getCurrency() {
            if(in_array(get_woocommerce_currency(), ['UAH', 'USD', 'EUR', 'GBP', 'BYN', 'KZT', 'RUB'])) {
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

    /**
     * Definition of the WP language
     **/
        private function getPreauthFlag() {
            return ($this->preauth_flag == 'yes')? 'Y' : 'N' ;
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
     * @param $order_id
     */
        function pending_new_order_notification($order_id) {
            $order = new WC_Order($order_id);
            // Only for "pending" order status
            if (!$order->has_status('pending')) return;

            // Get an instance of the WC_Email_New_Order object
            $wc_email = WC()->mailer()->get_emails()['WC_Email_New_Order'];
            $wc_email->settings['subject'] = __('{site_title} - Новый заказ ({order_number}) - {order_date}');
            $wc_email->settings['heading'] = __('Новый заказ');
            // $wc_email->settings['recipient'] .= ',name@email.com'; // Add email recipients (coma separated)

            // Send "New Email" notification (to admin)
            $wc_email->recipient = $order->get_billing_email();
            $wc_email->trigger($order_id);
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
            $order_all = new WC_Order($orderId);
            $order_true = wc_get_order($orderId);
            $data = array(
                "method" => "result",
                "payee_id" => $this->payee_id,
                "login" => $this->login,
                "password" => $this->password,
                "shop_order_number" => $response['SHOPORDERNUMBER'],
            );

            if (empty($order_true)) {
                return $this->t_lan['error_orderid'];
            }

            $result_portmone = $this->curlRequest(self::GATEWAY_URL, $data);
            if ($result_portmone === false) {
                $order_all->add_order_note('curl request error');
            }
            $parseXml = $this->parseXml($result_portmone);

            if ($parseXml === false) {
                $order_all->add_order_note('Portmone Xml empty');
                if ($response['RESULT'] == '0') {
                    $status = 'wc-status-paidnotve';
                    $result = $this->t_lan['successful_pay'];
                } else {
                    $status = 'wc-status-error';
                    $result = $response['RESULT'] . ' ' .$this->lang['error_auth'];
                }
                $this->update_order($order_all, null, $status, $result);
                return $result;
            }
            $payee_id_return = (array)$parseXml->request->payee_id;
            $order_data = (array)$parseXml->orders->order;

            if ($response['RESULT'] !== '0') {
                $result = $response['RESULT']. ' ' . $this->t_lan['number_pay'] .': '. $orderId;
                $this->update_order($order_all, null, 'wc-status-error', $result);
                return $result;
            }

            if ($payee_id_return[0] != $this->payee_id) {
                $order_all->update_status('pending');
                $order_all->add_order_note($this->t_lan['order_rejected']);
                return $this->t_lan['error_merchant'];
            }

            if (count($parseXml->orders->order) == 0) {
                return $this->t_lan['error_order_in_portmone'];
            } elseif (count($parseXml->orders->order) > 1){
                $no_pay = false;
                foreach($parseXml->orders->order as $order ){
                    $status = (array)$order->status;
                    if ($status[0] == self::ORDER_PAYED){
                        $order_all->update_status('wc-status-paid');
                        $no_pay = true;
                        break;
                    }
                }
                if ($no_pay == false) {
                    $order_all->update_status('wc-status-error');
                    return $this->t_lan['error_order_in_portmone'];
                } else {
                    $order_all->add_order_note($this->t_lan['order_rejected']);
                    $order_all->payment_complete();
                    return false;
                }
            }

            if ($order_data['status'] == self::ORDER_REJECTED) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-status-error', $this->t_lan['order_rejected']);
                return $this->t_lan['order_rejected']. ' ' . $this->t_lan['number_pay'] .': '. $orderId;
            }

            if ($order_data['status'] == self::ORDER_PREAUTH) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-status-preauth', $this->t_lan['preauth_pay']);
                //return $this->t_lan['preauth_pay']. ' ' . $this->t_lan['number_pay'] .': '. $orderId;
            }

            if ($order_data['status'] == self::ORDER_CREATED) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-status-error', $this->t_lan['order_rejected']);
                return $this->t_lan['order_rejected'];
            }

            if ($order_data['status'] == self::ORDER_PAYED) {
                $this->update_order($order_all, $order_data['pay_date'], 'wc-status-paid', $this->t_lan['successful_pay']);
            }

            return false;
        }

        function update_order(\WC_Order $order_all, $pay_date, $status, $note) {
            $order_all->update_status($status);
            $order_all->add_order_note($note);
            if ( ! $order_all->get_date_paid( 'edit' )  && $pay_date !== null) {
                $order_all->set_date_paid(strtotime($pay_date));
                $order_all->save();
            }
            $order_all->payment_complete();
        }
    /**
     * We display the answer on payment
     **/
        function check_response() {
            $paymentInfo = $this->isPaymentValid($_REQUEST);
            $orderId = $this->portmone_get_order_id($_REQUEST['SHOPORDERNUMBER']);
            if ($paymentInfo == false) {
                if ($_REQUEST['RESULT'] == '0') {
                    $this->message['message'] = $this->t_lan['thankyou_text'] . ' ' . $this->t_lan['number_pay'] . ' ' .$orderId ;
                } else {
                    $this->message['message'] = $_REQUEST['RESULT']. ' ' . $this->t_lan['number_pay'] . ' ' .$orderId ;
                }
                $this->message['class'] = 'message-portmone';
            } else {
                $this->message['class'] = 'message-portmone';
                $this->message['message'] = $paymentInfo;
                $order = wc_get_order($orderId);
                $redirect_url = add_query_arg(array($this->message['class'] => urlencode($this->message['message'])), $order->get_cancel_order_url());

                wp_redirect($redirect_url);
                exit;
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

    /**
     * @param $methods
     *
     * @return array
     */
        function woocommerce_add_portmone_gateway($methods) {
            $methods[] = 'WC_portmone';
            return $methods;
        }
        add_filter('woocommerce_payment_gateways', 'woocommerce_add_portmone_gateway');
        load_plugin_textdomain("portmone-pay-for-woocommerce", false, basename(dirname(__FILE__))."/languages");

        define("PORTMONE_STATUSES",
            [
                'paid'          => ['#109b00', '#FFFFFF', __('Оплачено с Portmone.com', 'portmone-pay-for-woocommerce')],
                'paidnotve'     => ['#0a4e03', '#FFFFFF', __('Оплачено с Portmone.com (но не проверено)', 'portmone-pay-for-woocommerce')],
                'preauth'       => ['#ffe000', '#000000', __('Оплачено с Portmone.com (блокировка средств)', 'portmone-pay-for-woocommerce')],
                'error'         => ['#bb0f0f', '#FFFFFF', __('Оплата с Portmone.com НЕ удалась', 'portmone-pay-for-woocommerce')]
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