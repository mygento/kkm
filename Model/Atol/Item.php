<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

class Item extends \Mygento\Kkm\Model\Request\Item
{
    public const PAYMENT_METHOD_FULL_PAYMENT = 'full_payment';
    public const PAYMENT_METHOD_FULL_PREPAYMENT = 'full_prepayment';
    public const PAYMENT_METHOD_ADVANCE = 'advance';

    public const PAYMENT_OBJECT_BASIC = 'commodity';
    public const PAYMENT_OBJECT_SERVICE = 'service';
    public const PAYMENT_OBJECT_PAYMENT = 'payment'; //Аванс, Бонус, Подарочная карта
    public const PAYMENT_OBJECT_ANOTHER = 'another';

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $item = [
            'name' => $this->getName(),
            'price' => $this->getPrice(),
            'quantity' => $this->getQuantity(),
            'sum' => $this->getSum(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_object' => $this->getPaymentObject(),
            'vat' => [
                'type' => $this->getTax(), //for API v4
            ],
        ];

        if ($this->isMarkingRequired()) {
            $item['nomenclature_code'] = $this->getMarking();
        }

        if ($this->getTaxSum()) {
            $item['vat']['sum'] = $this->getTaxSum();//for API v4
        }

        if ($this->getCountryCode()) {
            $item['country_code'] = $this->getCountryCode();//for API v4
        }

        if ($this->getCustomsDeclaration()) {
            $item['declaration_number'] = $this->getCustomsDeclaration();//for API v4
        }

        return $item;
    }
}
