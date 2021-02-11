<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface RequestInterface
{
    public const SELL_OPERATION_TYPE = 1;
    public const REFUND_OPERATION_TYPE = 2;
    public const RESELL_REFUND_OPERATION_TYPE = 4;
    public const RESELL_SELL_OPERATION_TYPE = 5;

    public const EXTERNAL_ID_KEY = 'external_id';

    /**
     * @return mixed
     */
    public function __toArray();

    /**
     * @return string
     */
    public function getSno(): string;

    /**
     * @param string $sno
     * @return $this
     */
    public function setSno(string $sno): self;

    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail($email): self;

    /**
     * @return string|null
     */
    public function getClientName(): ?string;

    /**
     * @param string|null $clientName
     * @return $this
     */
    public function setClientName($clientName): self;

    /**
     * @return string|null
     */
    public function getClientInn(): ?string;

    /**
     * @param string|null $clientInn
     * @return $this
     */
    public function setClientInn($clientInn): self;

    /**
     * @return string
     */
    public function getPhone(): string;

    /**
     * @param string|null $phone
     * @return $this
     */
    public function setPhone($phone): self;

    /**
     * @return \Mygento\Kkm\Api\Data\ItemInterface[]
     */
    public function getItems(): array;

    /**
     * @param ItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;

    /**
     * @return \Mygento\Kkm\Api\Data\PaymentInterface[]
     */
    public function getPayments(): array;

    /**
     * @param \Mygento\Kkm\Api\Data\PaymentInterface[] $payments
     * @return $this
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
     * @return $this
     */
    public function setExternalId($externalId): self;

    /**
     * @return string
     */
    public function getInn();

    /**
     * @param string $inn
     * @return $this
     */
    public function setInn($inn): self;

    /**
     * @return string
     */
    public function getPaymentAddress();

    /**
     * @param string $paymentAddress
     * @return $this
     */
    public function setPaymentAddress($paymentAddress): self;

    /**
     * @return string
     */
    public function getCallbackUrl();

    /**
     * @param string $callbackUrl
     * @return $this
     */
    public function setCallbackUrl($callbackUrl): self;

    /**
     * @return string
     */
    public function getCompanyEmail();

    /**
     * @param string $companyEmail
     * @return $this
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

    /** Aux methods to use this entity in Magento Queue Framework (MQF)   */

    /**
     * Method only for inner using in Magento Queue Framework
     * @param float $total
     * @return $this
     */
    public function setTotal(float $total): self;

    /**
     * Method only for inner using in Magento Queue Framework
     * @param string $timestamp
     * @return $this
     */
    public function setTimestamp(string $timestamp): self;

    /**
     * Specify the operation type (sell|refund)
     * @param int $type
     * @return $this
     */
    public function setOperationType(int $type): self;

    /**
     * Get the operation type (sell|refund)
     * @return int
     */
    public function getOperationType(): int;

    /**
     * Set id of basic entity (Invoice|Creditmemo|Order)
     * @param int|string $id
     * @return $this
     */
    public function setSalesEntityId($id): self;

    /**
     * Get id of basic entity (Invoice|Creditmemo|Order)
     * @return int
     */
    public function getSalesEntityId(): int;

    /**
     * @return bool
     */
    public function isIgnoreTrialsNum();

    /**
     * @param bool $ignore
     * @return $this
     */
    public function setIgnoreTrialsNum($ignore);

    /**
     * @return \Mygento\Kkm\Api\Data\UserPropInterface|null
     */
    public function getAdditionalUserProps(): ?\Mygento\Kkm\Api\Data\UserPropInterface;

    /**
     * @param \Mygento\Kkm\Api\Data\UserPropInterface $userProp
     * @return $this
     */
    public function setAdditionalUserProps(\Mygento\Kkm\Api\Data\UserPropInterface $userProp): self;

    /**
     * @return string
     */
    public function getAdditionalCheckProps(): ?string;

    /**
     * @param string $checkProps
     * @return $this
     */
    public function setAdditionalCheckProps($checkProps): self;
}
