<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

class RequestForVersion4 extends Request
{

    /**
     * @return array
     * @throws \Exception
     */
    public function jsonSerialize(): array
    {
        $client = [
            'email' => $this->getEmail(),
        ];
        $company = [
            'email'           => $this->getEmail(),
            'sno'             => $this->getSno(),
            'inn'             => $this->getInn(),
            'payment_address' => $this->getPaymentAddress(),
        ];

        return [
            'external_id' => $this->getExternalId(),
            'receipt'     => [
                'client'   => $client,
                'company'  => $company,
                'items'    => $this->getItems(),
                'payments' => $this->getPayments(),
                'total'    => $this->getTotal(),
            ],
            'service'     => [
                'callback_url' => $this->getCallbackUrl(),
            ],
            'timestamp'   => $this->getTimestamp(),
        ];
    }
}
