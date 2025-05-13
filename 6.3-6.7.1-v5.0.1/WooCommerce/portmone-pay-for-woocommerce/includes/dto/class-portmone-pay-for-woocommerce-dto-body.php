<?php

defined( 'ABSPATH' ) || exit;

/**
 * Body class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Body implements JsonSerializable
{
    /**
     * @access public
     * @var string
     */
    public $method;

    /**
     * @access public
     * @var stdClass
     */
    public $params;

    /**
     * @access public
     * @var string
     */
    public $id = '1';

    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    public function setParams(stdClass $params)
    {
        $this->params = $params;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars( $this );
    }

}

