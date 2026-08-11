<?php

defined( 'ABSPATH' ) || exit;

/**
 * Data structure for requesting a confirmPreauth
 * https://docs.portmone.com.ua/docs/en/PaymentGatewayEng/#622-json-request
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Confirm_Preauth_Data  implements JsonSerializable
{

    private $login;

    private $password;

    private $payeeId;

    private $shopOrderNumber;

    private $postauthAmount;

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
            'postauthAmount' =>  $this->postauthAmount,
        ];

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



    public function setShopOrderNumber(string $shopOrderNumber)
    {
        $this->shopOrderNumber = $shopOrderNumber;
    }

    public function setGoods(array $goods)
    {
        $this->goods = $goods;
    }

    public function setPostauthAmount( $postauthAmount )
    {
        $this->postauthAmount = $postauthAmount;
    }
}

