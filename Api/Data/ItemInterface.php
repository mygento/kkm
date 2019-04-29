<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface ItemInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * @return float
     */
    public function getPrice(): float;

    /**
     * @param float|string $price
     * @return $this
     */
    public function setPrice($price);

    /**
     * @return float
     */
    public function getQuantity(): float;

    /**
     * @param float|int|string $quantity
     * @return $this
     */
    public function setQuantity($quantity);

    /**
     * @return float
     */
    public function getSum(): float;

    /**
     * @param float|string $sum
     * @return $this
     */
    public function setSum($sum);

    /**
     * @return string
     */
    public function getTax(): string;

    /**
     * @param string $tax
     * @return $this
     */
    public function setTax($tax);

    /**
     * @return float
     */
    public function getTaxSum(): float;

    /**
     * @param float|string $taxSum
     * @return $this
     */
    public function setTaxSum($taxSum);

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * @return string
     */
    public function getPaymentObject();

    /**
     * @param string $paymentObject
     * @return $this
     */
    public function setPaymentObject($paymentObject);
}
