<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
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
     * @return float|int
     */
    public function getPrice();

    /**
     * @param float|string $price
     * @return $this
     */
    public function setPrice($price);

    /**
     * @return float|int
     */
    public function getQuantity();

    /**
     * @param float|int|string $quantity
     * @return $this
     */
    public function setQuantity($quantity);

    /**
     * @return float|int
     */
    public function getSum();

    /**
     * @param float|string $sum
     * @return $this
     */
    public function setSum($sum);

    /**
     * @return int|string
     */
    public function getTax();

    /**
     * @param int|string $tax
     * @return $this
     */
    public function setTax($tax);

    /**
     * @return float|int
     */
    public function getTaxSum();

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

    /**
     * @return string
     */
    public function getCountryCode();

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCode);

    /**
     * @return string
     */
    public function getCustomsDeclaration();

    /**
     * @param string $customsDeclaration
     * @return $this
     */
    public function setCustomsDeclaration($customsDeclaration);

    /**
     * @return bool
     */
    public function isMarkingRequired();

    /**
     * @param bool $value
     * @return $this
     */
    public function setMarkingRequired($value);

    /**
     * @return string
     */
    public function getMarking();

    /**
     * @param string $value
     * @return $this
     */
    public function setMarking($value);
}
