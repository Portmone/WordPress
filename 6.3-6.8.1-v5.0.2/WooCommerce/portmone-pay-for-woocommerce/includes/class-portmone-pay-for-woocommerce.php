<?php

defined( 'ABSPATH' ) || exit;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      5.0.1
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes
 * @author     Portmone
 */
class Portmone_Pay_For_Woocommerce
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @access   private
     * @var      Portmone_Pay_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    private $loader;

    public function __construct()
    {

        $this->version = PORTMONE_PAY_FOR_WOOCOMMERCE_VERSION;
        $this->plugin_name = PORTMONE_PAY_FOR_WOOCOMMERCE_NAME;

        $this->includes();
        $this->define_admin_hooks();

        $this->gateway_hooks();
        $this->api_hooks();
        $this->payment_status_hooks();
    }

    /**
     * Add payment methods
     *
     * @return array
     */
    public function woocommerce_add_portmone_gateway($methods)
    {
        $methods[] = 'WC_Portmone';
        return $methods;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }

    /*
     * Adding new order statuses.
     */
    public function register_new_order_statuses()
    {
        $helper_common = new Portmone_Pay_For_WooCommerce_Helper_Common();
        $portmone_payment_statuses = $helper_common->get_portmone_payment_statuses();

        foreach ( $portmone_payment_statuses as $kay => $val ) {
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

    /*
     * Adding new order statuses.
     */
    public function new_wc_order_statuses( $order_statuses )
    {
        $helper_common = new Portmone_Pay_For_WooCommerce_Helper_Common();
        $portmone_payment_statuses = $helper_common->get_portmone_payment_statuses();

        foreach ( $portmone_payment_statuses as $kay => $val ) {
            $order_statuses['wc-status-'.$kay] = _x( $val[2], 'Order status', 'textdomain' );
        }
        return $order_statuses;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
     * - Plugin_Name_Admin. Defines all hooks for the admin area.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     */
    private function includes()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/class-portmone-pay-for-woocommerce-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/admin/class-portmone-pay-for-woocommerce-admin.php';

        /**
         * Helper classes
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/helpers/class-portmone-pay-for-woocommerce-helper-common.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/helpers/class-portmone-pay-for-woocommerce-helper-http-client.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/helpers/class-portmone-pay-for-woocommerce-helper-payment.php';

        /**
         * DTO classes
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-payee.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-order.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-token.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-payer.php';

        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/class-portmone-pay-for-woocommerce-dto-body.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/result/class-portmone-pay-for-woocommerce-dto-result-data.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/return/class-portmone-pay-for-woocommerce-dto-return-data.php';

        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/class-portmone-pay-for-woocommerce-payment-gateway.php';


        /**
         * The class responsible for processing the response (user transition)
         * from the wallet site after payment (page with payment form).
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/api/class-portmone-pay-for-woocommerce-api-receive-payment-response.php';

        /**
         * The class rest api. Processing external requests via REST API
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/api/class-portmone-pay-for-woocommerce-api-rest.php';

        $this->loader = new Portmone_Pay_For_WooCommerce_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Portmone_Pay_For_Woocommerce_Admin(PORTMONE_PAY_FOR_WOOCOMMERCE_NAME);

        $this->loader->add_action( 'admin_init', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_page' );
        $this->loader->add_action( 'plugin_action_links_' .PORTMONE_PAY_FOR_WOOCOMMERCE_NAME. '/' .PORTMONE_PAY_FOR_WOOCOMMERCE_NAME. '.php' , $plugin_admin, 'plagin_actions' );
    }

    /**
     * Register a hook related to the functionality of the plugin's admin area and payment gateway.
     */
    private function gateway_hooks()
    {
        $this->loader->add_action( 'woocommerce_payment_gateways', $this, 'woocommerce_add_portmone_gateway' );
    }

    /**
     * Registering a hook associated with adding new order statuses.
     */
    private function payment_status_hooks()
    {
        $this->loader->add_action( 'init', $this, 'register_new_order_statuses' );
        $this->loader->add_filter( 'wc_order_statuses', $this, 'new_wc_order_statuses' );
    }

    /*
     * Registering API related hooks.
     */
    private function api_hooks()
    {
        $helper_payment = new Portmone_Pay_For_WooCommerce_Helper_Payment();
        $receive_payment_response = new Portmone_Pay_For_WooCommerce_Api_Receive_Payment_Response($helper_payment);
        $this->loader->add_action( 'woocommerce_api_portmone_payment', $receive_payment_response, 'receive_payment_response' );

        $rest = new Portmone_Pay_For_WooCommerce_Api_Rest();
        $this->loader->add_action( 'rest_api_init', $rest, 'portmone_register_routes' );
    }
}
