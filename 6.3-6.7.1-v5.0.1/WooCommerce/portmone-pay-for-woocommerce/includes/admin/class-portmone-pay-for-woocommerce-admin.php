<?php

defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    portmone-pay-for-woocommerce
 * @subpackage portmone-pay-for-woocommerce/admin
 * @author     Portmone
 */
class Portmone_Pay_For_WooCommerce_Admin
{
    /**
     * The ID of this plugin.
     *
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    public function __construct( string $plugin_name )
    {
        $this->plugin_name = $plugin_name;
    }

    /**
    * Register the stylesheets for the admin area.
    *
    */
    public function enqueue_styles()
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

        wp_enqueue_style( $this->plugin_name, PORTMONE_PAY_FOR_WOOCOMMERCE_URL . 'assets/css/portmone-pay-for-woocommerce-admin.css', array());

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

        wp_enqueue_script( $this->plugin_name, PORTMONE_PAY_FOR_WOOCOMMERCE_URL . 'assets/js/portmone-pay-for-woocommerce-admin.js', array( 'jquery' ) );

    }

    /**
     * Adds a top-level menu page.
     */
    public function add_menu_page()
    {
        $position = 1;
        $settings = get_option('woocommerce_portmone_settings', null);
        if ( ! empty($settings['show_admin_menu'] ) ) {
            $position = $settings['show_admin_menu'];
        }

        add_menu_page(
            'Portmone.com',
            'Portmone.com',
            'manage_options',
            'wc-settings&tab=checkout&section=portmone#tab1',
            [
                __CLASS__,
                'page_options',
            ],
            PORTMONE_PAY_FOR_WOOCOMMERCE_URL . 'assets/images/logo_200x200.png',
            $position
        );
    }

    /**
     * Link to settings page from plugins screen
     *
     * @param array $links
     */
    public function plagin_actions( $links )
    {
        return array_merge( array(
            'settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=portmone">' .
                __( 'Налаштування', 'portmone-pay-for-woocommerce' ) . '</a>' ), $links );
    }
}
