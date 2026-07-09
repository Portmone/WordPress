<?php

defined( 'ABSPATH' ) || exit;

/**
 * Payee class
 *
 * @package    Portmone_Pay_For_Woocommerce
 * @subpackage Portmone_Pay_For_Woocommerce/includes/hepers
 * @author     portmone
 */
class Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item  implements JsonSerializable
{

    private $internalCode;

    private $name;

    private $price;

    private $quantity;

    private $amount;

    private $taxRateCodes;

    private $discount = 0;

    private $discountName = '';

    private $isProduct = true;

    private $barcode = '';

    public function jsonSerialize(): array
    {
        $result = [
            'internalCode' => $this->internalCode,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'taxRateCodes' => $this->taxRateCodes,
            'barcode' => $this->barcode,
        ];

        if ( $this->isProduct ) {
            $result['discount'] = $this->discount;
            $result['discountName'] = $this->discountName;
        }

        return $result;
    }

    /**
     * @param mixed $internalCode
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setInternalCode($internalCode)
    {
        $this->internalCode = $internalCode;
        return $this;
    }

    /**
     * @param mixed $name
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param mixed $price
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @param mixed $quantity
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @param mixed $amount
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setDiscount(int $discount)
    {
        $this->discount = $discount;
    }

    public function setDiscountName(string $discountName)
    {
        $this->discountName = $discountName;
    }

    /**
     * @param bool $isProduct
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setIsProduct(bool $isProduct)
    {
        $this->isProduct = $isProduct;
        return $this;
    }

    /**
     * @param mixed $taxRateCodes
     * @return Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
     */
    public function setTaxRateCodes($taxRateCodes)
    {
        $this->taxRateCodes = $taxRateCodes;
        return $this;
    }

    public function setBarcode(string $barcode): Portmone_Pay_For_WooCommerce_Dto_Create_Link_Payment_Good_Item
    {
        $this->barcode = $barcode;
        return $this;
    }

}
