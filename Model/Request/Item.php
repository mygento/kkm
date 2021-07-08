<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Request;

use Mygento\Kkm\Api\Data\ItemInterface;

abstract class Item implements \JsonSerializable, ItemInterface
{
    // phpcs:disable
    protected $name = '';

    protected $price = 1.0;

    protected $quantity = 1;

    protected $sum = 0.0;

    protected $tax = '';

    protected $taxSum = 0.0;

    protected $paymentMethod = '';

    protected $paymentObject = '';

    protected $countryCode = '';

    protected $customsDeclaration = '';

    protected $shouldHaveMarking = false;

    protected $marking;

    // phpcs:enable

    /**
     * @return array
     */
    abstract public function jsonSerialize(): array;

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
    public function getPrice()
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
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @inheritdoc
     */
    public function setTax($tax)
    {
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

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomsDeclaration()
    {
        return $this->customsDeclaration;
    }

    /**
     * @param string $customsDeclaration
     * @return $this
     */
    public function setCustomsDeclaration($customsDeclaration)
    {
        $this->customsDeclaration = $customsDeclaration;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMarking()
    {
        return $this->marking;
    }

    /**
     * @inheritdoc
     */
    public function isMarkingRequired()
    {
        return $this->shouldHaveMarking;
    }

    /**
     * @inheritdoc
     */
    public function setMarking($value)
    {
        $this->marking = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setMarkingRequired($value)
    {
        $this->shouldHaveMarking = (bool) $value;

        return $this;
    }
}
