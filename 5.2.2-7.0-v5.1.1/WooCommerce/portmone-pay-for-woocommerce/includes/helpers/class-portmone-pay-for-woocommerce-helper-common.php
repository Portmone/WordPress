<?php

defined( 'ABSPATH' ) || exit;

/**
 * Common purpose functionality
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Helper_Common
{
    /**
     * @access private
     * @var    array    $portmone_plugin_data    The Portmone plugin data.
     */
    private $portmone_plugin_data;

    public function __construct() {
        $this->portmone_plugin_data = $this->get_portmone_plugin_data();
    }

    /**
     * Compares wc version and portmone plugin version
     *
     * @return string
     */
    public function get_wc_actual() {
        if ( version_compare( WC()->version, $this->portmone_plugin_data["WC requires at least"], '>=' ) && version_compare( WC()->version, $this->portmone_plugin_data["WC tested up to"], '<=' ) ) {
            return '<span style="color: green">('.__( 'версія актуальна для плагіна', 'portmone-pay-for-woocommerce' ).')</span>';
        } else {
            return '<span style="color: #e7a511;">('.__( 'на цій версії плагін НЕ перевірений і може працювати нестабільно', 'portmone-pay-for-woocommerce' ).')</span>';
        }
    }

    /**
     * Compares wordpress version and portmone plugin version
     *
     * @return string
     */
    public function get_wp_actual() {
        if ( version_compare(get_bloginfo('version'), $this->portmone_plugin_data["RequiresWP"], '>=' ) ) {
            return '<span style="color: green">('.__( 'версія актуальна для плагіна', 'portmone-pay-for-woocommerce' ).')</span>';
        } else {
            return '<span style="color: #e7a511;">('._( 'на цій версії плагін НЕ перевірений і може працювати нестабільно', 'portmone-pay-for-woocommerce' ).')</span>';
        }
    }

    /**
     * Return Portmone plugin data.
     *
     * @return array
     */
    public function get_portmone_plugin_data()
    {
        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        return get_plugin_data(PORTMONE_PAY_FOR_WOOCOMMERCE_FILE);
    }

    /**
     *  Get attribute5
     *
     * @param array $settings
     * @param WC_Order $order
     *
     * @return string|WP_Error
     */
    public function get_attribute5( array $settings, WC_Order $order )
    {
        $attribute5 = '';
        if ( $settings['split_payment_flag'] == 'yes' ) {

            $split_payments = [];
            foreach ( $order->get_items() as $item ) {
                $payee_id = 0;
                $product = $item->get_product();
                foreach ( $item->get_product()->get_attributes() as $key => $value ) {
                    if ( $value->get_data()['name'] != 'payee_id' ) {
                        continue;
                    }

                    $payee_id = $value->get_data()['options'][0];
                    if ( ! empty( $split_payments[$payee_id] ) ) {
                        $split_payments[$payee_id] += (float) $item->get_total();
                    } else {
                        $split_payments[$payee_id] = (float) $item->get_total();
                    }

                    break;
                }

                if ( $payee_id == 0 ) {
                    $message = sprintf( __( "Сталася помилка. Не вказана компанія одержувач у товарі &ldquo;%s&rdquo;. Будь ласка, зв'яжіться з нами, щоб отримати допомогу", 'portmone-pay-for-woocommerce' ), $product->get_name() );
                    return new WP_Error( 'error', $message);
                }
            }
            unset( $payee_id );

            if ( ! empty( $split_payments ) ) {
                foreach ( $split_payments as $payee_id => $amount ) {
                    $attribute5 .= ':' . $payee_id .';'. $amount . ';';
                }
            }

        }

        return $attribute5;
    }

    /**
     *  Get goods
     *
     * @param array $settings
     * @param WC_Order $order
     *
     * @return array<Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item>
     */
    public function get_goods( array $settings, WC_Order $order )
    {
        $goods = [];

        if ( empty( $settings['fiscalization_flag'] ) || $settings['fiscalization_flag'] !== 'yes' || get_woocommerce_currency() !== 'UAH' ) {
            return $goods;
        }

        if ( empty( $settings['internal_code'] ) &&  empty( $settings['tax_rate_codes'] ) ) {
            $mes = '#22P ' . __( "Сталася помилка. Не задана значення для Код продавця та Цифровий код ставки податку. Будь ласка, зв'яжіться з нами, щоб отримати допомогу", 'portmone-pay-for-woocommerce' );
            $order->add_order_note( $mes );
            $order->save();
            throw new \Exception( $mes );
        }

        foreach ( $order->get_items() as  $item ) {
            $product = $item->get_product();
            $goodtem = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item();

            $quantity = $item->get_quantity();
            // Базова ціну товару без знижки
            $price = $product->get_regular_price();
            $subtotal = $item->get_subtotal();

            $goodtem
                ->setInternalCode( $settings['internal_code'] )
                ->setTaxRateCodes( $settings['tax_rate_codes'] )
                ->setName( $item->get_name() )
                ->setPrice( $price )
                ->setQuantity( $quantity )
                ->setAmount( $subtotal )
                ->setBarcode( $product->get_sku() );

            $discount =  round( $quantity * $price - $subtotal , 2 );

            if ( $discount > 0 ) {
                $goodtem->setDiscount( $discount );
                $goodtem->setDiscountName( 'Знижка' );
            }

            $goods[] = $goodtem;
        }

        // Отримання чистої вартості доставки (без податку)
        $shipping_net = $order->get_shipping_total();
        if (  (float) $shipping_net > 0 ) {
            $goodtem = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item();

            $goodtem
                ->setInternalCode( $settings['internal_code'] )
                ->setTaxRateCodes( $settings['tax_rate_codes'] )
                ->setName( 'Компенсація транспортних витрат' )
                ->setPrice( $shipping_net )
                ->setQuantity( 1 )
                ->setAmount( $shipping_net )
                ->setIsProduct( false );

            $goods[] = $goodtem;
        }

        $coupons_discount = 0;
        // Отримуємо всі купони, які прикріплені до цього замовлення
        foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
            // get_discount() повертає суму знижки, яку дав цей конкретний купон
            $coupons_discount += (float) $coupon_item->get_discount();
        }

        if ( $coupons_discount > 0 ) {
            $goodtem = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item();

            $goodtem
                ->setInternalCode( $settings['internal_code'] )
                ->setTaxRateCodes( $settings['tax_rate_codes'] )
                ->setName( 'Знижка' )
                ->setPrice( (-1) * $coupons_discount )
                ->setQuantity( 1 )
                ->setAmount( (-1) * $coupons_discount )
                ->setIsProduct( false );

            $goods[] = $goodtem;
        }

        return $goods;
    }


    /**
     *  Get goods
     *
     * @param array $settings
     * @param WC_Order $order
     *
     * @return array<Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item>|WP_Error
     */
    public function get_goods_for_return( array $settings, WC_Order $order )
    {
        $goods = [];

        if ( empty( $settings['fiscalization_flag'] ) || $settings['fiscalization_flag'] !== 'yes' || get_woocommerce_currency() !== 'UAH' ) {
            return $goods;
        }


        if ( empty( $_POST['line_item_qtys'] ) || empty( $_POST['line_item_totals'] ) ) {
            return $goods;
        }

        $line_item_qtys = json_decode( str_replace(["\\"], "", $_POST['line_item_qtys'] ) , true );
        $line_item_totals = json_decode( str_replace(["\\"], "", $_POST['line_item_totals'] ) , true);
        
        foreach ( $order->get_items() as $item_id => $item ) {

            if ( empty( $line_item_totals[$item_id] )  ) {
                continue;
            }
            $return_amount =  $line_item_totals[$item_id];

            $return_quantity = 1;
            if ( isset( $line_item_qtys[$item_id] ) ) {
                $return_quantity = $line_item_qtys[$item_id];
            }

            $quantity = $item->get_quantity();
            $total = $item->get_total();
            if ( $return_quantity != $quantity || $total != $return_amount ) {
                return new WP_Error( 'error',  '#65P ' . __( 'Скасувати можна лише всю позицію. Змінити кількість товару в окремій позиції неможливо. Позиція повертається тільки цілком.', 'portmone-pay-for-woocommerce' ) );
            }

            $product = $item->get_product();
            $goodtem = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item();

            // Базова ціну товару без знижки
            $price = $product->get_regular_price();

            $goodtem
                ->setInternalCode( $settings['internal_code'] )
                ->setTaxRateCodes( $settings['tax_rate_codes'] )
                ->setName( $item->get_name() )
                ->setPrice( $price )
                ->setQuantity( $return_quantity )
                ->setAmount( $return_amount )
                ->setBarcode( $product->get_sku() );

            $goods[] = $goodtem;
        }

        // Отримання чистої вартості доставки (без податку)
        $shipping_net = $order->get_shipping_total();
        // Отримуємо масив об'єктів доставки для цього замовлення
        $shipping_items = $order->get_items( 'shipping' );
        foreach ( $shipping_items as $shipping_item_id => $shipping_item ) {
            if ( empty( $line_item_totals[$shipping_item_id] ) ) {
                continue;
            }

            if ( $line_item_totals[$shipping_item_id] != $shipping_net ) {
                return new WP_Error( 'error',  '#65P ' . __( 'Скасувати можна лише всю позицію. Змінити кількість товару в окремій позиції неможливо. Позиція повертається тільки цілком.', 'portmone-pay-for-woocommerce' ) );
            }

            $goodtem = new Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item();

            $goodtem
                ->setInternalCode( $settings['internal_code'] )
                ->setTaxRateCodes( $settings['tax_rate_codes'] )
                ->setName( 'Компенсація транспортних витрат' )
                ->setPrice( $line_item_totals[$shipping_item_id] )
                ->setQuantity( 1 )
                ->setAmount( $line_item_totals[$shipping_item_id] )
                ->setIsProduct( false );

            $goods[] = $goodtem;
        }

        return $goods;
    }

    /**
     * @param array $settings
     * @param WC_Order $order
     * @return float
     */
    public function get_order_total(array $settings, WC_Order $order )
    {
        $order_total = $order->get_total();
        if (isset($settings['convert_money']) &&
            isset($settings['exchange_rates']) &&
            $settings['convert_money'] == 'yes' &&
            $settings['exchange_rates'] > 0 &&
            get_woocommerce_currency() !== 'UAH'
        ) {
            return round( $order_total * $settings['exchange_rates'] , 2 );
        }

        return $order_total;
    }

    /**
     * Add or update meta data.
     *
     * @param WC_Order     $order
     * @param string       $key Meta key.
     * @param string|array $value Meta value.
     *
     */
    public function add_meta_data(WC_Order $order, string $key, $value)
    {
        if ( $order->meta_exists( $key ) ) {
            $order->update_meta_data( $key, $value );
        } else {
            $order->add_meta_data( $key, $value );
        }
    }

    /**
     * get portmone payment statuses
     *
     * @return array[]
     */
    public function get_portmone_payment_statuses()
    {
        return  [
            'paid'          => ['#109b00', '#FFFFFF', __( 'Оплачено з Portmone.com', 'portmone-pay-for-woocommerce' )],
            'paidnotve'     => ['#0a4e03', '#FFFFFF', __( 'Оплачено з Portmone.com (але не перевірено)', 'portmone-pay-for-woocommerce' )],
            'preauth'       => ['#ffe000', '#000000', __( 'Оплачено з Portmone.com (блокування коштів)', 'portmone-pay-for-woocommerce' )],
            'error'         => ['#bb0f0f', '#FFFFFF', __( 'Оплата з Portmone.com НЕ вдалась', 'portmone-pay-for-woocommerce' )]
        ];
    }
}
