<?php

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Model\Source\Tax;

class Item implements \JsonSerializable
{
    const PAYMENT_METHOD_FULL_PAYMENT = 'full_payment';
    const PAYMENT_METHOD_ADVANCE      = 'advance';

    const PAYMENT_OBJECT_BASIC   = 'commodity';
    const PAYMENT_OBJECT_PAYMENT = 'payment'; //Аванс, Бонус, Подарочная карта
    const PAYMENT_OBJECT_ANOTHER = 'another';

    private $name          = '';
    private $price         = 1.0;
    private $quantity      = 1;
    private $sum           = 0.0;
    private $tax           = '';
    private $taxSum        = 0.0;
    private $paymentMethod = '';
    private $paymentObject = '';

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $item = [
            'name'           => $this->getName(),
            'price'          => $this->getPrice(),
            'quantity'       => $this->getQuantity(),
            'sum'            => $this->getSum(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_object' => $this->getPaymentObject(),
            'tax' => $this->getTax(),//for API v3
            'vat'            => [
                'type' => $this->getTax(),//for API v4
            ],
        ];

        if ($this->getTaxSum()) {
            $item['tax_sum'] = $this->getTaxSum();//for API v3
            $item['vat']['sum'] = $this->getTaxSum();//for API v4
        }

        return $item;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Item
     */
    public function setName(string $name): Item
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return Item
     */
    public function setPrice($price): Item
    {
        $this->price = (float)$price;

        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return Item
     */
    public function setQuantity($quantity): Item
    {
        $this->quantity = (float)$quantity;

        return $this;
    }

    /**
     * @return float
     */
    public function getSum(): float
    {
        return $this->sum;
    }

    /**
     * @param float $sum
     * @return Item
     */
    public function setSum($sum): Item
    {
        $this->sum = (float)$sum;

        return $this;
    }

    /**
     * @return string
     */
    public function getTax(): string
    {
        return $this->tax;
    }

    /**
     * @param string $tax
     * @return Item
     * @throws \Exception
     */
    public function setTax(string $tax): Item
    {
        if (!in_array($tax, Tax::getAllTaxes(), true)) {
            throw new \Exception("Incorrect tax {$tax} for Item {$this->getName()}");
        }

        $this->tax = $tax;
/*        switch ($tax) {
            case (self::TAX_VAT110):
                $this->setTaxSum($this->getPrice() * $this->getQuantity() * 10 / 110);
                break;
            case (self::TAX_VAT118):
                $this->setTaxSum($this->getPrice() * $this->getQuantity() * 18 / 118);
                break;
            case (self::TAX_VAT10):
                $this->setTaxSum($this->getPrice() * $this->getQuantity() * 0.1);
                break;
            case (self::TAX_VAT18):
                $this->setTaxSum($this->getPrice() * $this->getQuantity() * 0.18);
                break;
            case (self::TAX_VAT0):
            case (self::TAX_NONE):
            default:
                $this->setTaxSum(0);
        }*/

        return $this;
    }

    /**
     * @return float
     */
    public function getTaxSum(): float
    {
        return $this->taxSum;
    }

    /**
     * @param float $taxSum
     * @return Item
     */
    public function setTaxSum($taxSum): Item
    {
        $this->taxSum = round($taxSum, 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return Item
     */
    public function setPaymentMethod($paymentMethod): Item
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentObject()
    {
        return $this->paymentObject;
    }

    /**
     * @param string $paymentObject
     * @return Item
     */
    public function setPaymentObject($paymentObject): Item
    {
        $this->paymentObject = $paymentObject;

        return $this;
    }
}