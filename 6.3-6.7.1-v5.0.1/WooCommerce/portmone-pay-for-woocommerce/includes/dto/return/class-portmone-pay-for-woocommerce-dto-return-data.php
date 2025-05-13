<?php

defined( 'ABSPATH' ) || exit;

/**
 * Result_Data class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Return_Data  implements JsonSerializable
{
    /**
     * @access public
     * @var string
     */
    private $login;

    /**
     * @access public
     * @var string
     */
    private $password;

    /**
     * @access public
     * @var string
     */
    private $payeeId;

    /**
     * @access public
     * @var string
     */
    private $shopOrderNumber;

    /**
     * @access public
     * @var string
     */
    private $message;

    /**
     * @access public
     * @var string
     */
    private $returnAmount;

    /**
     * @access public
     * @var string
     */
    private $attribute5;


    public function jsonSerialize(): array
    {
        return get_object_vars( $this );
    }

    public function setLogin(string $login)
    {
        $this->login = $login;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    public function setPayeeId(string $payeeId)
    {
        $this->payeeId = $payeeId;
    }

    public function setAttribute5(string $attribute5)
    {
        $this->attribute5 = $attribute5;
    }

    public function setShopOrderNumber(string $shopOrderNumber)
    {
        $this->shopOrderNumber = $shopOrderNumber;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function setReturnAmount(string $returnAmount)
    {
        $this->returnAmount = $returnAmount;
    }

}

