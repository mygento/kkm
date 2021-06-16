<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface PaymentInterface
{
    const PAYMENT_TYPE_BASIC = 1;
    const PAYMENT_TYPE_AVANS = 2;

    /**
     * @return int
     */
    public function getType(): int;

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type);

    /**
     * @return float
     */
    public function getSum(): float;

    /**
     * @param float|string $sum
     * @return $this
     */
    public function setSum($sum);
}
