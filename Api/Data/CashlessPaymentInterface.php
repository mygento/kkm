<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

declare(strict_types=1);

namespace Mygento\Kkm\Api\Data;

interface CashlessPaymentInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @param string $id
     * @return $this
     */
    public function setId(string $id): \Mygento\Kkm\Api\Data\CashlessPaymentInterface;

    /**
     * @return float
     */
    public function getSum(): float;

    /**
     * @param float|string $sum
     * @return $this
     */
    public function setSum($sum): \Mygento\Kkm\Api\Data\CashlessPaymentInterface;

    /**
     * @return string
     */
    public function getPaymentMethod(): string;

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod(string $paymentMethod): \Mygento\Kkm\Api\Data\CashlessPaymentInterface;

    /**
     * @return string
     */
    public function getAdditionalInfo(): string;

    /**
     * @param string|null $additionalInfo
     *
     * @return $this
     */
    public function setAdditionalInfo(?string $additionalInfo): \Mygento\Kkm\Api\Data\CashlessPaymentInterface;
}
