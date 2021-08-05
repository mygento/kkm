<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

/**
 * @deprecated
 * @see RequestForVersion4
 */
class RequestForVersion3 extends Request
{
    /**
     * @throws \Exception
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'external_id' => $this->getExternalId(),
            'receipt' => [
                'attributes' => [
                    'email' => $this->getEmail(),
                    'phone' => $this->getPhone(),
                    'sno' => $this->getSno(),
                ],
                'items' => $this->getItems(),
                'payments' => $this->getPayments(),
                'total' => $this->getTotal(),
            ],
            'service' => [
                'callback_url' => $this->getCallbackUrl(),
                'inn' => $this->getInn(),
                'payment_address' => $this->getPaymentAddress(),
            ],
            'timestamp' => $this->getTimestamp(),
        ];
    }
}
