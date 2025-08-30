<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\CashlessPaymentInterface;

class CashlessPayment implements \JsonSerializable, CashlessPaymentInterface
{
    private float $sum = 0.00;
    private int $paymentMethod;
    private string $id;
    private string $additionalInfo = '';

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'sum' => $this->getSum(),
            'method' => $this->getPaymentMethod(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id): CashlessPaymentInterface
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSum(): float
    {
        return $this->sum;
    }

    /**
     * @inheritDoc
     */
    public function setSum($sum): CashlessPaymentInterface
    {
        $this->sum = $sum;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod(string $paymentMethod): CashlessPaymentInterface
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInfo(): string
    {
        return $this->additionalInfo;
    }

    /**
     * @param string|null $additionalInfo
     *
     * @return $this
     */
    public function setAdditionalInfo(?string $additionalInfo): CashlessPaymentInterface
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }
}
