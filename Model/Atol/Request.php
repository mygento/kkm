<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

abstract class Request implements \JsonSerializable
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
     * @return array
     */
    abstract public function jsonSerialize(): array;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\Timezone $date
    ) {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getSno(): string
    {
        return $this->sno;
    }

    /**
     * @param string $sno
     * @return Request
     */
    public function setSno(string $sno): Request
    {
        $this->sno = $sno;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Request
     */
    public function setEmail(string $email): Request
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return Request
     */
    public function setPhone(string $phone): Request
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     * @return Request
     */
    public function setItems(array $items): Request
    {
        foreach ($items as $element) {
            $this->addItem($element);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @param array $payments
     * @return Request
     */
    public function setPayments(array $payments): Request
    {
        $this->payments = $payments;

        return $this;
    }

    /**Invoke this method AFTER addItem() method
     * @return float
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
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     * @return Request
     */
    public function setExternalId($externalId): Request
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return string
     */
    public function getInn()
    {
        return $this->inn;
    }

    /**
     * @param string $inn
     * @return Request
     */
    public function setInn($inn): Request
    {
        $this->inn = $inn;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentAddress()
    {
        return $this->paymentAddress;
    }

    /**
     * @param string $paymentAddress
     * @return Request
     */
    public function setPaymentAddress($paymentAddress): Request
    {
        $this->paymentAddress = $paymentAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     * @return Request
     */
    public function setCallbackUrl($callbackUrl): Request
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompanyEmail()
    {
        return $this->companyEmail;
    }

    /**
     * @param string $companyEmail
     * @return Request
     */
    public function setCompanyEmail($companyEmail): Request
    {
        $this->companyEmail = $companyEmail;

        return $this;
    }
    /**
     * @param Item $item
     * @return $this
     */
    public function addItem(Item $item): Request
    {
        $this->items[] = $item;
        $this->addTotal($item->getSum());

        return $this;
    }

    /**
     * @param float $sum
     */
    public function addTotal($sum)
    {
        $this->total += $sum;
    }

    /**
     * @param float $sum
     * @param mixed $payment
     */
//    public function setTotal($sum): Request
//    {
//        $this->total = $sum;
//
//        return $this;
//    }

    /**
     * @param array $payment
     * @return $this
     */
    public function addPayment($payment): Request
    {
        $this->payments[] = $payment;

        return $this;
    }

    public function getTimestamp()
    {
        return $this->date->date()->format('d-m-Y H:i:s');
    }
}
