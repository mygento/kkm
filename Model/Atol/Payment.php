<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\PaymentInterface;

class Payment implements \JsonSerializable, PaymentInterface
{
    private $type = 0;
    private $sum  = 0.00;

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->getType(),
            'sum' => $this->getSum()
        ];
    }


    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        $this->type = $type;

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
    public function setSum($sum)
    {
        $this->sum = $sum;

        return $this;
    }
}