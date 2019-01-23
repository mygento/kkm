<?php

namespace Mygento\Kkm\Model\Atol;

/**
 * Class RequestForVersion3 deprecated since 2019
 * @deprecated
 * @package Mygento\Kkm\Model\Atol
 */
class RequestForVersion3 extends Request
{

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'external_id' => $this->getExternalId(),
            'receipt'     => [
                'attributes' => [
                    'email' => $this->getEmail(),
                    'phone' => $this->getPhone(),
                    'sno'   => $this->getSno(),
                ],
                'items'      => $this->getItems(),
                'payments'   => $this->getPayments(),
                'total'      => $this->getTotal(),
            ],
            'service'     => [
                'callback_url'    => $this->getCallbackUrl(),
                'inn'             => $this->getInn(),
                'payment_address' => $this->getPaymentAddress(),
            ],
            'timestamp'   => $this->getTimestamp(),
        ];
    }
}