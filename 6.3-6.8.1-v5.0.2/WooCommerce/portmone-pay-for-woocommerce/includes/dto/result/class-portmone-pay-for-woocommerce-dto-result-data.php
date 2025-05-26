<?php

defined( 'ABSPATH' ) || exit;

/**
 * Data structure for request to get order data
 * https://docs.portmone.com.ua/docs/en/PaymentGatewayEng/#8-getting-authorization-results
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Result_Data implements JsonSerializable
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
    private $shopbillId;

    /**
     * @var string
     */
    private $shopOrderNumber;

    /**
     * @var string
     */
    private $status = 'PAYED';


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

    public function setShopbillId(string $shopbillId)
    {
        $this->shopbillId = $shopbillId;
    }

    public function setShopOrderNumber(string $shopOrderNumber)
    {
        $this->shopOrderNumber = $shopOrderNumber;
    }

    public function jsonSerialize(): array
    {
        $result = [
            'login' => $this->login,
            'password' => $this->password,
            'payeeId' => $this->payeeId,
        ];

        if ( ! empty( $this->shopbillId ) ) {
            $result['shopbillId'] = $this->shopbillId;
            return $result;
        }

        if ( ! empty( $this->shopOrderNumber ) ) {
            $result['shopOrderNumber'] = $this->shopOrderNumber;
            $result['status'] = $this->status;
        }

        return $result;
    }
}

