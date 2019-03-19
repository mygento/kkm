<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\ItemInterface;
use Mygento\Kkm\Model\Source\Tax;

class Item implements \JsonSerializable, ItemInterface
{
    const PAYMENT_METHOD_FULL_PAYMENT = 'full_payment';
    const PAYMENT_METHOD_ADVANCE = 'advance';

    const PAYMENT_OBJECT_BASIC = 'commodity';
    const PAYMENT_OBJECT_PAYMENT = 'payment'; //Аванс, Бонус, Подарочная карта
    const PAYMENT_OBJECT_ANOTHER = 'another';

    // phpcs:disable
    private $name = '';

    private $price = 1.0;

    private $quantity = 1;

    private $sum = 0.0;

    private $tax = '';

    private $taxSum = 0.0;

    private $paymentMethod = '';

    private $paymentObject = '';

    // phpcs:enable

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $item = [
            'name' => $this->getName(),
            'price' => $this->getPrice(),
            'quantity' => $this->getQuantity(),
            'sum' => $this->getSum(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_object' => $this->getPaymentObject(),
            'vat' => [
                'type' => $this->getTax(), //for API v4
            ],
        ];

        if ($this->getTaxSum()) {
            $item['vat']['sum'] = $this->getTaxSum();//for API v4
        }

        return $item;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @inheritdoc
     */
    public function setPrice($price)
    {
        $this->price = (float) $price;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @inheritdoc
     */
    public function setQuantity($quantity)
    {
        $this->quantity = (float) $quantity;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSum(): float
    {
        return $this->sum;
    }

    /**
     * @inheritdoc
     */
    public function setSum($sum)
    {
        $this->sum = (float) $sum;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTax(): string
    {
        return $this->tax;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function setTax(string $tax)
    {
        if (!in_array($tax, Tax::getAllTaxes(), true)) {
            throw new \Exception("Incorrect tax {$tax} for Item {$this->getName()}");
        }

        $this->tax = $tax;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTaxSum(): float
    {
        return $this->taxSum;
    }

    /**
     * @inheritdoc
     */
    public function setTaxSum($taxSum)
    {
        $this->taxSum = round($taxSum, 2);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @inheritdoc
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentObject()
    {
        return $this->paymentObject;
    }

    /**
     * @inheritdoc
     */
    public function setPaymentObject($paymentObject)
    {
        $this->paymentObject = $paymentObject;

        return $this;
    }
}
