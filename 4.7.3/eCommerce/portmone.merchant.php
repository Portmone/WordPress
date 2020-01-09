<?php

    /**
     * Version plugin
     */
    class version_portmone_wpsc_merchant {
        var $version_portmone_wpsc_merchant = '1.0.0';
        function __construct(){
        }
    }

    /**
     * Connecting files
     */
    add_action("init", "portmone_file");
    function portmone_file() {
        load_plugin_textdomain("wp-e-commerce-portmone", false, dirname( plugin_basename( __FILE__ ) ) ."/portmone_merchant/languages");
        wp_enqueue_style('portmone-css', plugin_dir_url(__FILE__) . '/portmone_merchant/assets/css/e-commerce-portmone.css');
        wp_enqueue_script('portmone-js', plugin_dir_url(__FILE__) . '/portmone_merchant/assets/js/e-commerce-portmone.js', array('jquery'));
    }

    /**
     * Show logo when paying
     */
     $portmone_showlogo = get_option('portmone_showlogo');
    if (!empty($portmone_showlogo) && $portmone_showlogo == 'portmone_showlogo' ) {
        $logo = 'https://www.portmone.com.ua/r3/i/logos/portmone.svg';
    } else {
        $logo = '';
    }

    /**
     * init plugin
     */
    $nzshpcrt_gateways[$num] = array(
        'name' =>  __( ' Portmone', 'wpsc' ),
        'api_version' => 2.0,
        'image' => $logo,
        'class_name' => 'wpsc_merchant_portmone',
        'has_recurring_billing' => false,
        'wp_admin_cannot_cancel' => true,
        'display_name' => __( 'Portmone', 'wpsc' ),
        'requirements' => array(
            // so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
            'php_version' => 4.3,
            // for modules that may not be present, like curl
            'extra_modules' => array()
        ),
        'internalname' => 'wpsc_merchant_portmone',
        // All array members below here are legacy, and use the code in paypal_multiple.php
        'form' => 'form_portmone',
        'submit_function' => 'submit_portmone',
        'payment_type' => 'portmone'
    );

    class wpsc_merchant_portmone extends wpsc_merchant {
        const ORDER_PAYED       = 'PAYED';
        const ORDER_CREATED     = 'CREATED';
        const ORDER_REJECTED    = 'REJECTED';
        const GATEWAY_URL       = 'https://www.portmone.com.ua/gateway/';

        function __construct( $purchase_id = null, $is_receiving = false ) {
            parent::__construct( $purchase_id, $is_receiving );
            $this->redirect_page_id = get_option('portmone_redirect');
            $this->f_lan = 'wp-e-commerce-portmone';
        }

        function languages($param) {
            $this->m_lan = array(
            'submit_portmone'               => 'Оплатить через Portmone',
            'error_auth'                    => 'Ошибка авторизации. Введен не верный логин или пароль',
            'payee_id_title'                => 'Идентификатор магазина в системе Portmone(Payee ID)',
            'payee_id_span_title'           => 'Обязательно для заполнения',
            'payee_id_description'          => 'ID Интернет-магазина, предоставленный менеджером Portmone',
            'login_title'                   => 'Логин Интернет-магазина в системе Portmone',
            'login_span_title'              => 'Обязательно для заполнения',
            'login_description'             => 'Логин Интернет-магазина, предоставленный менеджером Portmone',
            'password_title'                => 'Пароль Интернет-магазина в системе Portmone',
            'password_span_title'           => 'Обязательно для заполнения',
            'password_description'          => 'Пароль для Интернет-магазина, предоставленный менеджером Portmone',
            'description_title'             => 'Комментарий для клиента',
            'description_default'           => 'Сервис проведения платежей обеспечивается системой Portmone.com с использованием современного и безопасного механизма авторизации платежных карт. Служба поддержки Portmone.com: телефон +380(44)200 09 02, электронная почта: support@portmone.com',
            'description_description'       => 'Описание для клиента на странице оплаты заказа',
            'showlogo_title'                => 'Показать логотип на странице оплаты',
            'showlogo_description'          => 'Отметьте, чтобы показать логотип Portmone',
            'redirect_page_id_title'        => 'Страница ответа',
            'redirect_page_id_description'  => 'URL страницы успеха',
            'configuration'                 => 'Настройки',
            'information'                   => 'Информация',
            'IC_version'                    => 'Версия плагина',
            'payment_module'                => 'Платежный плагина Portmone',
            'thankyou_text'                 => 'Спасибо за покупку!',
            'error_orderid'                 => 'При совершенииоплаты возникла ошибка. Свяжитесь с Интернет-магазином для проверки заказа',
            'error_order_in_portmone'       => 'В системе Portmone данного платежа нет, он возвращен или создан некорректно',
            'error_merchant'                => 'При совершении оплаты возникла ошибка. Данные Интернет-магазина некорректны',
            'order_rejected'                => 'При совершении оплаты возникла ошибка. Проверьте данные вашей карты и попробуйте провести оплату еще раз!',
            'number_pay'                    => 'Номер вашего заказа',
            'convert_money_title'           => 'Включить конвертацию в Гривны',
            'convert_money_label'           => 'Конвертировать валюту магазина из %1$s %2$s > в > %3$s, при оформлении заказа',
            'convert_money_description'     => 'Portmone принимает только Украинские гривны',
            'exchange_rates_title'          => 'Курс валюты',
            'exchange_rates_description'    => 'Курс %1$s за 1 %2$s %3$s',
            'WP_EC_version_label'           => 'eCommerce Версия',
            'WP_EC_version_no'              => 'Не определено',
            'WP_version_label'              => 'WP версия',
            'IDproduct'                     => 'ID товара',
            'quantity'                      => 'кол.',
            'price'                         => 'цена',
            'close'                         => 'Закрыть',
            );
            $t_lan = __($this->m_lan[$param], $this->f_lan);
        return $t_lan;
        }

        /**
         *
         **/
        function submit() {
            wpsc_update_customer_meta( 'portmone_sessionid', $this->cart_data['session_id'] );
            $this->set_purchase_processed_by_purchid(2);
            $this->go_to_transaction_results($this->cart_data['session_id']);
            exit();
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
         * @return string
         */
        private function getCallbackUrl() {
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
        return add_query_arg('wp-api', get_class($this), $redirect_url);
        }

        /**
         *   The form of sending information
         **/
        function gateway_portmone($sessionid) {
            global $wpdb;
            $description_order = '';
            $purchase_log_sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= " . $sessionid . " LIMIT 1";
            $purchase_log = $wpdb->get_results($purchase_log_sql, ARRAY_A);
            $cart_sql = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`='" . $purchase_log[0]['id'] . "'";
            $cart = $wpdb->get_results($cart_sql, ARRAY_A);

            if (is_array($purchase_log) && !empty($purchase_log[0]['totalprice'])) {
                foreach ($cart as $item){
                     $description = '('.$this->languages('IDproduct').' ' . $item['id'].' ) ('.$this->languages('quantity').' '. $item['quantity'] . ' ) ('.$this->languages('price').' '. $item['price'] * $item['quantity'].' ) | ';
                     $description_order .= $description;
                }
                $description_order .= 'TOTAL '. $purchase_log[0]['totalprice'];
            }

            $portmone_convert_money = get_option('portmone_convert_money');
            $portmone_exchange_rates = get_option('portmone_exchange_rates');
            if (!empty($portmone_convert_money) && !empty($portmone_exchange_rates) && $portmone_convert_money == 'portmone_convert_money' ) {
                $amount = $portmone_exchange_rates * $purchase_log[0]['totalprice'];
            } else {
                $amount = $purchase_log[0]['totalprice'];
            }

            $portmone_args = array(
                'payee_id'           => get_option('portmone_payee_id'),
                'shop_order_number'  => $purchase_log[0]['id'].'_'.time(),
                'bill_amount'        => $amount ,
                'description'        => $description_order,
                'success_url'        => $this->getCallbackUrl(),
                'failure_url'        => $this->getCallbackUrl(),
                'lang'               => $this->getLanguage(),
                'encoding'           => 'UTF-8'
            );

            $form = '';
            foreach ($portmone_args as $key => $value) {
                $portmone_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
            }
            $form .= '<form action="' . self::GATEWAY_URL . '" method="post" id="portmone_payment_form">
                        ' . implode('', $portmone_args_array) . '
                    <input type="submit" id="submit_portmone_payment_form" value="' . $this->languages('submit_portmone') . '" /></form>';
        return $form;
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
        function isPaymentValid($response) {
            global $wpdb;
            $shopnumber = $response['SHOPORDERNUMBER'];
            $shopnumbercount = strpos($shopnumber, "_");
            $orderId = substr($shopnumber, 0, $shopnumbercount);
            $purchase_log = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`= " . $orderId . " LIMIT 1", ARRAY_A);
            $data = array(
                "method"            => "result",
                "payee_id"          => get_option('portmone_payee_id'),
                "login"             => get_option('portmone_login'),
                "password"          => get_option('portmone_password'),
                "shop_order_number" => $response['SHOPORDERNUMBER'],
            );


            if (count($purchase_log)==0) {

                return $this->languages('error_orderid');
            }

            $result_portmone = $this->curlRequest( self::GATEWAY_URL , $data);
            $parseXml = $this->parseXml($result_portmone);

            if ($parseXml === false) {
                  preg_match_all('#<div class="response error">(.+?)</div>#is', $result_portmone, $arr);
                $this->updateStatuse(6, $orderId);
                return $this->languages('error_auth');
            }

              $payee_id_return = (array)$parseXml->request->payee_id;
              $order_data = (array)$parseXml->orders->order;
            if ($response['RESULT'] !== '0') {
                $this->updateStatuse(6, $orderId);
                return  $response['RESULT']. ' ' . $this->t_lan['number_pay'] .': '. $orderId ;
            }

            if ($payee_id_return[0] != get_option('portmone_payee_id')) {
                $this->updateStatuse(6, $orderId);
                return $this->languages('error_merchant');
            }

            if (count($parseXml->orders->order) == 0) {
                $this->updateStatuse(6, $orderId);
                return $this->languages('error_order_in_portmone');
            } elseif (count($parseXml->orders->order) > 1){
                $no_pay = false;
                foreach($parseXml->orders->order as $order ){
                    $status = (array)$order->status;
                    if ($status[0] == self::ORDER_PAYED){
                        $this->updateStatuse(3, $orderId);
                        $no_pay = true;
                        break;
                    }
                }
                if ($no_pay == false) {
                    $this->updateStatuse(6, $orderId);
                    return $this->languages('error_order_in_portmone');
                } else {
                    $this->updateStatuse(3, $orderId);
                    return false;
                }
            }

            if ($order_data['status'] == self::ORDER_REJECTED) {
                $this->updateStatuse(2, $orderId);
                return $this->languages('order_rejected');
            }

            if ($order_data['status'] == self::ORDER_CREATED) {
                $this->updateStatuse(2, $orderId);
                return $this->languages('order_rejected');
            }

            if ($order_data['status'] == self::ORDER_PAYED) {
                $this->updateStatuse(3, $orderId);
            }
        return false;
        }

        function updateStatuse($processed , $pay_for) {
            global $wpdb;
            $wpdb->update(WPSC_TABLE_PURCHASE_LOGS, array('processed' => $processed, 'date' => time()), array('id' => $pay_for), array('%d', '%s'), array('%d'));
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
    }

    /**
     * We display the answer on payment
     **/
    function nzshpcrt_portmone_callback($content){
        if(!empty($_REQUEST['SHOPORDERNUMBER'])){
            $wpsc_merchant_portmone = new wpsc_merchant_portmone();
            $paymentInfo = $wpsc_merchant_portmone->isPaymentValid($_REQUEST);
            if ($paymentInfo == false) {
                $shopnumber = $_REQUEST['SHOPORDERNUMBER'];
                $shopnumbercount = strpos($shopnumber, "_");
                $orderId = substr($shopnumber, 0, $shopnumbercount);
                if ($_REQUEST['RESULT'] == '0') {
                    $msg['message'] = $wpsc_merchant_portmone->languages('thankyou_text') . ' ' . $wpsc_merchant_portmone->languages('number_pay') . ' ' . $orderId;
                } else {
                    $msg['message'] = $_REQUEST['RESULT'] . ' ' . $wpsc_merchant_portmone->language('number_pay') .
                        $orderId;
                }
                $msg['class'] = 'message-portmone';
            } else {
                $msg['class'] = 'message-portmone';
                $msg['message'] = $paymentInfo;
            }
        $content = '<div id="message-portmone" class="successful-message-portmone" style="display: block;">
                            <div>
                            <a onclick="openbox();" title="'.$wpsc_merchant_portmone->languages('close').'" class="close">X</a>
                            <img class="img-portmone" src="https://www.portmone.com.ua/r3/i/logos/portmone.svg">
                        <div>' . $msg['message'] . '</div>
                        </div>
                   </div>' . $content;
          }
    return $content;
    }
    add_action('the_content',  'nzshpcrt_portmone_callback');

    /**
     * update settings in admin panel
     **/
    function submit_portmone() {
        $option_name = array(
            'portmone_payee_id',
            'portmone_login',
            'portmone_password',
            'portmone_exchange_rates',
            'portmone_redirect',
            'portmone_convert_money',
            'portmone_payment_instructions',
            'portmone_showlogo',
            'portmone_redirect',
        );

        foreach($option_name as $val){
            if (isset($_POST[$val])) {
                update_option($val, $_POST[$val]);
            } else {
                update_option($val, '');
            }
        }
    return true;
    }

    /**
     *   The form in admin back
     **/
    function form_portmone() {
        global $wpdb;
        $version_portmone = new version_portmone_wpsc_merchant;
        $version_portmone_wpsc_merchant = $version_portmone->version_portmone_wpsc_merchant;
        $wpsc_portmone = new wpsc_merchant_portmone;
        $currency = $wpdb->get_results("SELECT `code`,`currency` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . get_option('currency_type') . "'  OR `id`='225'", ARRAY_A);
        $letters_convert_money_label = array('%1$s', '%2$s', '%3$s');
        $fruit_convert_money_label   = array(
            '<b>' . $currency[0]['currency'] ,
            '('. $currency[0]['code'] .')</b>',
            '<b>' . $currency[1]['currency'] . ' (' . $currency[1]['code'] .')</b>'
        );
        $convert_money_label = str_replace(
            $letters_convert_money_label,
            $fruit_convert_money_label,
            $wpsc_portmone->languages('convert_money_label')
        );

        $letters_exchange_rates_description = array('%1$s', '%2$s', '%3$s');
        $fruit_exchange_rates_description   = array(
            '<b>' . $currency[1]['currency'] . ' (' . $currency[1]['code'] .')</b>' ,
            '<b>' . $currency[0]['currency'] ,
            '('. $currency[0]['code'] .')</b>'
        );
        $exchange_rates_description = str_replace(
            $letters_exchange_rates_description,
            $fruit_exchange_rates_description,
            $wpsc_portmone->languages('exchange_rates_description')
        );

        $get_plugins_date = get_plugins();
        if (!empty($get_plugins_date['wp-e-commerce/wp-shopping-cart.php']['Version'])) {
            $WP_EC_version = $get_plugins_date['wp-e-commerce/wp-shopping-cart.php']['Version'];
        } else {
            $WP_EC_version = $wpsc_portmone->languages('WP_EC_version_no');
        }

        $select_page = '';
        foreach (select_get_pages('Select Page') as $key => $val){
            $select_page .= '<option value="'.$key.'" ';
            $portmone_redirect = get_option('portmone_redirect');
            if (!empty($portmone_redirect) && $portmone_redirect == $key) {
                $select_page .= 'selected="selected"';
            }
            $select_page .= '>'.$val.'</option>';
        }

        $get_option_portmone_convert_money = get_option('portmone_convert_money');
        if (!empty($get_option_portmone_convert_money) && $get_option_portmone_convert_money == 'portmone_convert_money' ) {
            $portmone_convert_money = 'checked="checked"';
        } else {
            $portmone_convert_money = '';
        }

        $get_option_portmone_showlogo = get_option('portmone_showlogo');
        if (!empty($get_option_portmone_showlogo) && $get_option_portmone_showlogo == 'portmone_showlogo' ) {
            $showlogo_checked = 'checked="checked"';
        } else {
            $showlogo_checked = '';
        }

        if (get_option('portmone_payment_instructions') === false) {
            $portmone_payment_instructions = $wpsc_portmone->languages('description_default');
        } else {
            $portmone_payment_instructions = get_option('portmone_payment_instructions');
        }

        $variables = array(
            '{img_src}'                     => 'https://www.portmone.com.ua/r3/i/logos/portmone.svg',
            '{img_title}'                   => 'Portmone',
            '{configuration}'               => $wpsc_portmone->languages('configuration'),
            '{information}'                 => $wpsc_portmone->languages('information'),
            '{payee_id}'                    => get_option('portmone_payee_id'),
            '{payee_id_title}'              => $wpsc_portmone->languages('payee_id_title'),
            '{payee_id_span_title}'         => $wpsc_portmone->languages('payee_id_span_title'),
            '{payee_id_description}'        => $wpsc_portmone->languages('payee_id_description'),
            '{login}'                       => get_option('portmone_login'),
            '{login_title}'                 => $wpsc_portmone->languages('login_title'),
            '{login_span_title}'            => $wpsc_portmone->languages('login_span_title'),
            '{login_description}'           => $wpsc_portmone->languages('login_description'),
            '{password}'                    => get_option('portmone_password'),
            '{password_title}'              => $wpsc_portmone->languages('password_title'),
            '{password_span_title}'         => $wpsc_portmone->languages('password_span_title'),
            '{password_description}'        => $wpsc_portmone->languages('password_description'),
            '{payment_module}'              => $wpsc_portmone->languages('payment_module'),
            '{IC_version}'                  => $wpsc_portmone->languages('IC_version'),
            '{version}'                     => $version_portmone_wpsc_merchant,
            '{WP_EC_version_label}'         => $wpsc_portmone->languages('WP_EC_version_label'),
            '{WP_EC_version}'               => $WP_EC_version,
            '{WP_version_label}'            => $wpsc_portmone->languages('WP_version_label'),
            '{WP_version}'                  => get_bloginfo('version') . ' (' . get_bloginfo('language') . ')',
            '{convert_money_title}'         => $wpsc_portmone->languages('convert_money_title'),
            '{convert_money_label}'         => $convert_money_label,
            '{convert_money_description}'   => $wpsc_portmone->languages('convert_money_description'),
            '{exchange_rates_title}'        => $wpsc_portmone->languages('exchange_rates_title'),
            '{portmone_exchange_rates}'     => get_option('portmone_exchange_rates'),
            '{exchange_rates_description}'  => $exchange_rates_description,
            '{description_title}'           => $wpsc_portmone->languages('description_title'),
            '{description_description}'     => $wpsc_portmone->languages('description_description'),
            '{portmone_description}'        => $portmone_payment_instructions,
            '{redirect_page_id_title}'      => $wpsc_portmone->languages('redirect_page_id_title'),
            '{portmone_redirect}'           => $select_page,
            '{redirect_page_id_description}'=> $wpsc_portmone->languages('redirect_page_id_description'),
            '{showlogo_title}'              => $wpsc_portmone->languages('showlogo_title'),
            '{showlogo_description}'        => $wpsc_portmone->languages('showlogo_description'),
            '{showlogo_checked}'            => $showlogo_checked,
            '{convert_money_checked}'       => $portmone_convert_money,
        );

        $template = file_get_contents(plugin_dir_path(__FILE__) . 'portmone_merchant/view/portmone_admin.php');
        foreach ($variables as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        if (get_option('currency_type') == '225'){
            $template = preg_replace('#<tr id="convert_money_title">.*?</tr id="exchange_rates_title">#s', "", $template);
        }
    return $template;
    }

    /**
     * result pay
     **/
    function _wpsc_merchant_portmone_raw_message( $message, $notification ) {
        $purchase_log = $notification->get_purchase_log();
        $sessionid = (string) wpsc_get_customer_meta( 'portmone_sessionid' );

        $WPSR_portmone = new wpsc_merchant_portmone();

        if ( $purchase_log->get( 'gateway' ) == 'wpsc_merchant_portmone' ) {
            $return = $message;
            $return .= $WPSR_portmone->gateway_portmone($sessionid);
            $return .= "\r\n" . get_option( 'portmone_payment_instructions', '' );
        }
    return $return;
    }

    add_filter(
        'wpsc_purchase_log_customer_notification_raw_message',
        '_wpsc_merchant_portmone_raw_message',
        10,
        2
    );
    add_filter(
        'wpsc_purchase_log_customer_html_notification_raw_message',
        '_wpsc_merchant_portmone_raw_message',
        10,
        2
    );

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