<?php
/*
Plugin Name: Portmone pay for woocommerce
Plugin URI: https://github.com/Portmone/WordPress
Description: Portmone Payment Gateway for WooCommerce.
Version: 1.0.2
Author: glib.yuriiev@portmone.me
Author URI: https://www.portmone.com.ua
Domain Path: /
License: Payment Card Industry Data Security Standard (PCI DSS)
License URI: https://www.portmone.com.ua/r3/uk/security/
*/

    add_action("init", "portmone_languages");
    function portmone_languages() {
        load_plugin_textdomain("portmone-pay-for-woocommerce", false, basename(dirname(__FILE__))."/languages");
    }

    define('PORTMONE_IMGDIR', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');

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
                __( 'Settings', 'woocommerce' ) . '</a>'), $links);
    }

    add_filter('plugin_row_meta', 'plagin_links', 10, 2);
    add_filter('plugin_action_links_' . plugin_basename( __FILE__ ),'plagin_actions');

function woocommerce_portmone_init() {

    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    wp_enqueue_script('portmone-js', plugin_dir_url(__FILE__) . 'assets/js/portmone.js', array('jquery'));
    if (isset($_GET['message-portmone']) && !empty($_GET['message-portmone'])) {
        wp_enqueue_style('portmone-checkout', plugin_dir_url(__FILE__) . 'assets/css/portmone_styles.css');
        add_action('the_content', 'showPortmoneMessage');
    }

    function showPortmoneMessage($content) {
        return '<div id="message-portmone" class="successful-message-portmone" style="display: block;">
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
        const GATEWAY_URL       = 'https://www.portmone.com.ua/gateway/';
        private $t_lan  = array();  // массив переведенных текстов
        private $m_lan  = array();  // массив дефолтных текстов
        private $m_settings  = array();  // массив полученых настроек
        private $order_total = 0;

        public function __construct() {
            $this->version = '1.0.2';
            $this->currency = get_woocommerce_currencies();
            $this->m_lan = array(
                'enabled_title'                 => 'Включить прием оплаты через Portmone',
                'enabled_label'                 => 'Включить Portmone модуль оплаты',
                'payee_id_title'                => 'Идентификатор магазина в системе Portmone(Payee ID)',
                'payee_id_span_title'           => 'Обязательно для заполнения',
                'payee_id_description'          => 'ID Интернет-магазина, предоставленный менеджером Portmone',
                'login_title'                   => 'Логин Интернет-магазина в системе Portmone',
                'login_span_title'              => 'Обязательно для заполнения',
                'login_description'             => 'Логин Интернет-магазина, предоставленный менеджером Portmone',
                'password_title'                => 'Пароль Интернет-магазина в системе Portmone',
                'password_span_title'           => 'Обязательно для заполнения',
                'password_description'          => 'Пароль для Интернет-магазина, предоставленный менеджером Portmone',
                'title_title'                   => 'Название компании',
                'title_default'                 => 'Оплата заказа через Portmone',
                'title_description'             => 'Название Интернет-магазина, которое видит клиент при оплате',
                'description_title'             => 'Комментарий для клиента',
                'description_default'           => 'Сервис проведения платежей обеспечивается системой Portmone.com с использованием современного и безопасного механизма авторизации платежных карт. Служба поддержки Portmone.com: телефон +380(44)200 09 02, электронная почта: support@portmone.com',
                'description_description'       => 'Описание для клиента на странице оплаты заказа',
                'showlogo_title'                => 'Показать логотип на странице оплаты',
                'showlogo_label'                => 'Показать логотип Portmone при оформлении заказа',
                'showlogo_description'          => 'Отметьте, чтобы показать логотип Portmone',
                'page_mode_title'               => 'Включить режим показа на странице',
                'page_mode_label'               => 'Включить на странице способ оплаты',
                'page_mode_description'         => 'Включить режим страницы без перенаправления',
                'redirect_page_id_title'        => 'Страница ответа',
                'redirect_page_id_description'  => 'URL страницы успеха',
                'method_description'            => 'Платежный шлюз',
                'configuration'                 => 'Настройки',
                'information'                   => 'Информация',
                'IC_version'                    => 'Версия плагина',
                'payment_module'                => 'Платежный плагина Portmone',
                'submit_portmone'               => 'Оплатить через Portmone',
                'thankyou_text'                 => 'Спасибо за покупку!',
                'error_orderid'                 => 'При совершенииоплаты возникла ошибка. Свяжитесь с Интернет-магазином для проверки заказа',
                'error_order_in_portmone'       => 'В системе Portmone данного платежа нет, он возвращен или создан некорректно',
                'error_merchant'                => 'При совершении оплаты возникла ошибка. Данные Интернет-магазина некорректны',
                'order_rejected'                => 'При совершении оплаты возникла ошибка. Проверьте данные вашей карты и попробуйте провести оплату еще раз!',
                'number_pay'                    => 'Номер вашего заказа',
                'successful_pay'                => 'Оплата совершена успешно!',
                'convert_money_title'           => 'Включить конвертацию в Гривны',
                'convert_money_label'           => 'Конвертировать валюту магазина из %1$s %2$s > в > %3$s, при оформлении заказа',
                'convert_money_description'     => 'Portmone принимает только Украинские гривны',
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
                'showlogo',
                'redirect_page_id'
            );

            if ($this->settings['showlogo'] == "yes") {
                $this->icon = PORTMONE_IMGDIR . 'portmonepay.svg';
            }

            foreach ($this->m_settings as  $value) {
                $this->$value = $this->settings[$value];
            }

            $this->msg['message'] = "";
            $this->msg['class'] = "";
            $this->init_form();

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_response'));
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('init', array(&$this, 'check_response'));
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

            add_action('woocommerce_receipt_portmone', array(&$this, 'receipt_page'));

            if (is_admin()) {
                wp_enqueue_style('portmone-checkout', plugin_dir_url(__FILE__) . 'assets/css/portmone_styles_admin.css');
            }
        }

    /**
     * Конфиг формы в админ панеле
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
                    'type'             => 'text',
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
                'showlogo'             => array('title' => $this->t_lan['showlogo_title'],
                    'type'             => 'checkbox',
                    'label'            => $this->t_lan['showlogo_label'],
                    'default'          => 'yes',
                    'description'      => $this->t_lan['showlogo_description'],
                    'desc_tip'         => true),
                'redirect_page_id'     => array('title' => $this->t_lan['redirect_page_id_title'],
                    'type'             => 'select',
                    'options'          => $this->select_get_pages('Select Page'),
                    'description'      => $this->t_lan['redirect_page_id_description'],
                    'desc_tip'         => true)
            );

            $this->form_fields = array_merge ($array1, $array2, $array3);
        }

    /**
     * Свойства для админ панели
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
                '{settings}'          =>  $settings ,
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
     * @param $order
     */
        function receipt_page($order) {
            echo $this->generate_portmone_form($order);
        }

    /**
     * свойства для отправки на сервер портмоне
     **/
        function generate_portmone_form($order_id) {
            $description_order = '';
            $order = new WC_Order($order_id);
            $items = $order->get_items();
            if (is_array($items)) {
                foreach ($items as $item) {
                    $description = '('.$this->t_lan['IDproduct'].' ' . $item["item_meta"]["_product_id"][0].' ) ('.$this->t_lan['quantity'].' '. $item["item_meta"]["_qty"][0] . ' ) ('.$this->t_lan['price'].' '. $item["item_meta"]["_line_subtotal"][0].' ) | ';
                    $description_order .= $description;
                }
                $description_order .= 'TOTAL '. $order->order_total;
            }

            if (isset($this->settings['convert_money']) &&
                isset($this->settings['exchange_rates']) &&
                $this->settings['convert_money']== 'yes' &&
                (int)$this->settings['exchange_rates'] > 0
            ){
                 $this->order_total = round($order->order_total * $this->settings['exchange_rates'] , 2);
            } else {
                 $this->order_total = $order->order_total;
            }

            $portmone_args = array(
                'payee_id'           => $this->payee_id,
                'shop_order_number'  => $order_id.'_'.time(),
                'bill_amount'        => $this->order_total ,
                'description'        => $description_order,
                'success_url'        => $this->getCallbackUrl(),
                'failure_url'        => $this->getCallbackUrl(),
                'lang'               => $this->getLanguage(),
                'encoding'           => 'UTF-8'
            );
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
     * Возвращение результата
     **/
        function process_payment($order_id) {
            $order = new WC_Order($order_id);

            if (version_compare( WOOCOMMERCE_VERSION , '2.1.0', '>=')) {
                $checkout_payment_url = $order->get_checkout_payment_url(true);
            } else {
                $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }
               return array('result' => 'success',
               'redirect' => add_query_arg('order_pay', $order_id, $checkout_payment_url));
        }

    /**
     * редирект после обработки
     **/
        private function getCallbackUrl() {
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
            /*For wooCoomerce 2.0*/
            return add_query_arg('wc-api', get_class($this), $redirect_url);
        }

    /**
     * Возврат актуальной локализации
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
     * Запрос на сервер портмоне для проверки действительности оплаты
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
     * обработка XML ответа с Portmone
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
     * @param $response
     *
     * @return bool|mixed
     */
        protected function isPaymentValid($response) {
            global $woocommerce;
            $shopnumber = $response['SHOPORDERNUMBER'];
            $shopnumbercount = strpos($shopnumber, "_");
            $orderId = substr($shopnumber, 0, $shopnumbercount);
            $order_all = new WC_Order($orderId);
            $order_true = $order_all->get_order($orderId);
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

            $result_portmone = $this->curlRequest( self::GATEWAY_URL , $data);
            
            $parseXml = $this->parseXml($result_portmone);
            print_r($parseXml);
            
            if ($parseXml === false) {
                preg_match_all('#<div class="response error">(.+?)</div>#is', $result_portmone, $arr);
                return $this->t_lan['error_auth']; 
            }
            $payee_id_return = (array)$parseXml->request->payee_id;
            $order_data = (array)$parseXml->orders->order;

            if ($response['RESULT'] !== '0') {
                return  $response['RESULT']. ' ' . $this->t_lan['number_pay'] .': '. $orderId ;
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
                        $order_all->update_status('processing');
                        $no_pay = true;
                        break;
                    }
                }
                if ($no_pay == false) {
                    $order_all->update_status('failed');
                    return $this->languages('error_order_in_portmone');
                } else {
                    $order_all->update_status('failed');
                    return false;
                }
            }

            if ($order_data['status'] == self::ORDER_REJECTED) {
                $order_all->update_status('failed');
                $order_all->add_order_note($this->t_lan['order_rejected']);
                return $this->t_lan['order_rejected'];
            }
            if ($order_data['status'] == self::ORDER_CREATED) {
                $order_all->update_status('pending');
                $order_all->add_order_note($this->t_lan['order_rejected']);
                return $this->t_lan['order_rejected'];
            }

            if ($order_data['status'] == self::ORDER_PAYED) {
                $order_all->update_status('processing');
                $order_all->add_order_note($this->t_lan['successful_pay']);
                $order_all->payment_complete();
            }
           $woocommerce->cart->empty_cart();

            return false;
        }

        function check_response() {
            $paymentInfo = $this->isPaymentValid($_REQUEST);
            if ($paymentInfo == false) {
                $shopnumber = $_REQUEST['SHOPORDERNUMBER'];
                $shopnumbercount = strpos($shopnumber, "_");
                $orderId = substr($shopnumber, 0, $shopnumbercount);
                if ($_REQUEST['RESULT'] == '0') {
                    $this->msg['message'] = $this->t_lan['thankyou_text'] . ' ' . $this->t_lan['number_pay'] . ' ' .$orderId ;
                } else {
                    $this->msg['message'] = $_REQUEST['RESULT']. ' ' . $this->t_lan['number_pay'] . ' ' .$orderId ;
                }
                $this->msg['class'] = 'message-portmone';
            } else {
                $this->msg['class'] = 'message-portmone';
                $this->msg['message'] = $paymentInfo;
            }
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
            $redirect_url = add_query_arg(array($this->msg['class'] => urlencode($this->msg['message'])), $redirect_url);

           wp_redirect($redirect_url);
           exit;
        }

    /**
     * @param bool $title
     * @param bool $indent
     *
     * @return array
     */
        function select_get_pages($title = false, $indent = true) {
            $pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) {
                $page_list[] = $title;
            }

            foreach ($pages as $page) {
                $prefix = '';
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_portmone_gateway($methods) {
        $methods[] = 'WC_portmone';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_portmone_gateway');
}