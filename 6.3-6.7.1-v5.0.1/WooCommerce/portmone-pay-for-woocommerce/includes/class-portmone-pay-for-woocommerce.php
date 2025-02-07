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
         * The class helpers.
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/helpers/class-portmone-pay-for-woocommerce-helper-common.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/helpers/class-portmone-pay-for-woocommerce-helper-http-client.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/helpers/class-portmone-pay-for-woocommerce-helper-payment.php';

        /**
         * The class dto.
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-payee.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-order.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-token.php';
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/dto/create-link-payment/class-portmone-pay-for-woocommerce-dto-create-link-payment-payer.php';



        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/class-portmone-pay-for-woocommerce-payment-gateway.php';


        /**
         * The class api.
         */
        require_once PORTMONE_PAY_FOR_WOOCOMMERCE_DIR . 'includes/api/class-portmone-pay-for-woocommerce-api-receive-payment-response.php';



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
     * Add payment method hook
     */
    private function gateway_hooks()
    {
        $this->loader->add_action( 'woocommerce_payment_gateways', $this, 'woocommerce_add_portmone_gateway' );
    }

    private function api_hooks()
    {
        $helper_payment = new Portmone_Pay_For_WooCommerce_Helper_Payment();
        $receive_payment_response = new Portmone_Pay_For_WooCommerce_Api_Receive_Payment_Response($helper_payment);
        $this->loader->add_action( 'woocommerce_api_portmone_payment', $receive_payment_response, 'receive_payment_response' );
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
}
