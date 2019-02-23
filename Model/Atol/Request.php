<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\RequestInterface;

abstract class Request implements \JsonSerializable, RequestInterface
{
    const PAYMENT_TYPE_BASIC = 1;
    const PAYMENT_TYPE_AVANS = 2;

    protected $sno = '';
    protected $externalId = '';
    protected $email = '';
    protected $companyEmail = '';
    protected $phone = '';
    protected $items = [];
    protected $payments = [];
    protected $total = 0.0;
    protected $inn = '';
    protected $paymentAddress = '';
    protected $callbackUrl = '';
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone|string
     */
    protected $date = '';

    /**
     * Request constructor.
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $date
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\Timezone $date
    ) {
        $this->date = $date;
    }

    /**
     * @inheritdoc
     */
    public function getSno(): string
    {
        return $this->sno;
    }

    /**
     * @inheritdoc
     */
    public function setSno(string $sno): RequestInterface
    {
        $this->sno = $sno;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function setEmail(string $email): RequestInterface
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @inheritdoc
     */
    public function setPhone(string $phone): RequestInterface
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @inheritdoc
     */
    public function setItems(array $items): RequestInterface
    {
        foreach ($items as $element) {
            $this->addItem($element);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @inheritdoc
     */
    public function setPayments(array $payments): RequestInterface
    {
        $this->payments = $payments;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getTotal(): float
    {
        if (empty($this->getItems())) {
            throw new \Exception(
                'Can not calculate totals. No items in the request'
            );
        }

        return $this->total;
    }

    /**
     * @inheritdoc
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @inheritdoc
     */
    public function setExternalId($externalId): RequestInterface
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInn()
    {
        return $this->inn;
    }

    /**
     * @inheritdoc
     */
    public function setInn($inn): RequestInterface
    {
        $this->inn = $inn;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentAddress()
    {
        return $this->paymentAddress;
    }

    /**
     * @inheritdoc
     */
    public function setPaymentAddress($paymentAddress): RequestInterface
    {
        $this->paymentAddress = $paymentAddress;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @inheritdoc
     */
    public function setCallbackUrl($callbackUrl): RequestInterface
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCompanyEmail()
    {
        return $this->companyEmail;
    }

    /**
     * @inheritdoc
     */
    public function setCompanyEmail($companyEmail): RequestInterface
    {
        $this->companyEmail = $companyEmail;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addItem(Item $item): RequestInterface
    {
        $this->items[] = $item;
        $this->addTotal($item->getSum());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addTotal($sum)
    {
        $this->total += $sum;
    }

    /**
     * @inheritdoc
     */
    public function addPayment($payment): RequestInterface
    {
        $this->payments[] = $payment;

        return $this;
    }

    public function getTimestamp()
    {
        return $this->date->date()->format('d-m-Y H:i:s');
    }
}
