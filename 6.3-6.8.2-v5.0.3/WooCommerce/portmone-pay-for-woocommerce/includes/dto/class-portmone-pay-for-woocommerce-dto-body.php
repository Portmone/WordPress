<?php

defined( 'ABSPATH' ) || exit;

/**
 * General wrapper for requests to the portmone system
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Body implements JsonSerializable
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var stdClass
     */
    private $params;

    /**
     * @var string
     */
    private $id = '1';

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

