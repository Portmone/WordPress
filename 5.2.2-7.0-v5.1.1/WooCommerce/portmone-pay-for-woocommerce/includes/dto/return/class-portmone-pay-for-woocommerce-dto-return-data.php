<?php

defined( 'ABSPATH' ) || exit;

/**
 * Data structure for requesting a refund
 * https://docs.portmone.com.ua/docs/en/PaymentGatewayEng#9-refund
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Return_Data  implements JsonSerializable
{
    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $payeeId;

    /**
     * @var string
     */
    private $shopOrderNumber;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $returnAmount;

    /**
     * @var string
     */
    private $attribute5;

    /**
     * @var array
     */
    private $goods;


    public function jsonSerialize(): array
    {
        $result =  [
           'payeeId' =>  $this->payeeId,
            'login' =>  $this->login,
            'password' =>  $this->password,
            'shopOrderNumber' =>  $this->shopOrderNumber,
        ];

        if ( ! empty( $this->returnAmount ) ) {
            $result['returnAmount'] = $this->returnAmount;
            $result['attribute5'] = $this->attribute5;
            $result['message'] = $this->message;
        }

        if ( ! empty( $this->goods ) ) {
            $result['goods'] = $this->goods;
        }

        return $result;
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

    public function setGoods(array $goods)
    {
        $this->goods = $goods;
    }

}

