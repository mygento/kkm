<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

class ItemForVersion5 extends ItemForVersion4
{
    public const PAYMENT_OBJECT_BASIC = 1;
    public const PAYMENT_OBJECT_SERVICE = 4;
    public const PAYMENT_OBJECT_PAYMENT = 10;
    public const PAYMENT_OBJECT_ANOTHER = 13;
    public const MEASURE_DEFAULT = 0;

    public function jsonSerialize(): array
    {
        $item = parent::jsonSerialize();
        $item['measure'] = $this->getMeasure();

        return $item;
    }
}
