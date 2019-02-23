<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api;

use Mygento\Kkm\Model\Atol\Item;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getSno(): string;

    /**
     * @param string $sno
     * @return RequestInterface
     */
    public function setSno(string $sno): self;

    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @param string $email
     * @return RequestInterface
     */
    public function setEmail(string $email): self;

    /**
     * @return string
     */
    public function getPhone(): string;

    /**
     * @param string $phone
     * @return RequestInterface
     */
    public function setPhone(string $phone): self;

    /**
     * @return array
     */
    public function getItems(): array;

    /**
     * @param Item[] $items
     * @return RequestInterface
     */
    public function setItems(array $items): self;

    /**
     * @return array
     */
    public function getPayments(): array;

    /**
     * @param array $payments
     * @return RequestInterface
     */
    public function setPayments(array $payments): self;

    /**
     * Invoke this method AFTER addItem() method
     * @return float
     */
    public function getTotal(): float;

    /**
     * @return string
     */
    public function getExternalId();

    /**
     * @param string $externalId
     * @return RequestInterface
     */
    public function setExternalId($externalId): self;

    /**
     * @return string
     */
    public function getInn();

    /**
     * @param string $inn
     * @return RequestInterface
     */
    public function setInn($inn): self;

    /**
     * @return string
     */
    public function getPaymentAddress();

    /**
     * @param string $paymentAddress
     * @return RequestInterface
     */
    public function setPaymentAddress($paymentAddress): self;

    /**
     * @return string
     */
    public function getCallbackUrl();

    /**
     * @param string $callbackUrl
     * @return RequestInterface
     */
    public function setCallbackUrl($callbackUrl): self;

    /**
     * @return string
     */
    public function getCompanyEmail();

    /**
     * @param string $companyEmail
     * @return RequestInterface
     */
    public function setCompanyEmail($companyEmail): self;

    /**
     * @param Item $item
     * @return RequestInterface
     */
    public function addItem(Item $item): self;

    /**
     * @param float $sum
     */
    public function addTotal($sum);

    /**
     * @param array $payment
     * @return $this
     */
    public function addPayment($payment): self;

    public function getTimestamp();
}