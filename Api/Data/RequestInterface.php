<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface RequestInterface
{
    const SELL_OPERATION_TYPE   = 1;
    const REFUND_OPERATION_TYPE = 2;

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
     * @return \Mygento\Kkm\Api\Data\ItemInterface[]
     */
    public function getItems(): array;

    /**
     * @param ItemInterface[] $items
     * @return RequestInterface
     */
    public function setItems(array $items): self;

    /**
     * @return \Mygento\Kkm\Api\Data\PaymentInterface[]
     */
    public function getPayments(): array;

    /**
     * @param \Mygento\Kkm\Api\Data\PaymentInterface[] $payments
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
     * @param ItemInterface $item
     * @return RequestInterface
     */
    public function addItem(ItemInterface $item): self;

    /**
     * @param float $sum
     * @return void
     */
    public function addTotal($sum);

    /**
     * @param \Mygento\Kkm\Api\Data\PaymentInterface $payment
     * @return $this
     */
    public function addPayment($payment): self;

    /**
     * @return string
     */
    public function getTimestamp();

    /** Aux methods to use this entity in Magento Queue Framework (MQF)   **/

    /**
     * Method only for inner using in Magento Queue Framework
     * @param float $total
     * @return RequestInterface
     */
    public function setTotal(float $total): self;

    /**
     * Method only for inner using in Magento Queue Framework
     * @param string $timestamp
     * @return RequestInterface
     */
    public function setTimestamp(string $timestamp): self;

    /**
     * Specify the operation type (sell|refund)
     * @param int $type
     * @return RequestInterface
     */
    public function setOperationType(int $type): self;

    /**
     * Get the operation type (sell|refund)
     * @return int
     */
    public function getOperationType(): int;

    /**
     * Set id of basic entity (Invoice|Creditmemo|Order)
     * @param string|int $id
     * @return RequestInterface
     */
    public function setSalesEntityId($id): self;

    /**
     * Get id of basic entity (Invoice|Creditmemo|Order)
     * @return int
     */
    public function getSalesEntityId(): int;

    /**
     *
     * @param int $count
     * @return RequestInterface
     */
    public function setRetryCount($count): self;

    /**
     * @return int|null
     */
    public function getRetryCount();
}
