<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

class Item extends \Mygento\Kkm\Model\Request\Item
{
    const PAYMENT_METHOD_FULL_PAYMENT = 4;
    const PAYMENT_METHOD_FULL_PREPAYMENT = 1;
    const PAYMENT_METHOD_ADVANCE = 3;

    const PAYMENT_OBJECT_BASIC = 1;
    const PAYMENT_OBJECT_SERVICE = 4;
    const PAYMENT_OBJECT_PAYMENT = 10; //Аванс, Бонус, Подарочная карта

    const TAX_MAPPING = [
        'none' => 4,
        'vat0' => 3,
        'vat10' => 2,
        'vat20' => 1,
        'vat110' => 6,
        'vat120' => 5,
    ];

    protected $price = 1;

    protected $quantity = 1000;

    protected $sum = 0;

    public function jsonSerialize(): array
    {
        $item = [
            'Description' => $this->getName(),
            'Price' => $this->getPrice(),
            'Qty' => $this->getQuantity(),
            'TaxId' => $this->getTax(),
            'PayAttribute' => $this->getPaymentMethod(),
            'LineAttribute' => $this->getPaymentObject(),
        ];

        if ($this->isMarkingRequired()) {
            $item['CGNFloat'] = $this->getMarking();
        }

        return $item;
    }

    /**
     * @inheritdoc
     */
    public function getTax()
    {
        return (int) $this->tax;
    }
}
