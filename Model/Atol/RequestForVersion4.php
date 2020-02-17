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
     * @throws \Exception
     * @return array
     */
    public function jsonSerialize(): array
    {
        $client = [
            'email' => $this->getEmail(),
        ];

        if ($this->getClientName()) {
            $client['name'] = $this->getClientName();
        }

        if ($this->getClientInn()) {
            $client['inn'] = $this->getClientInn();
        }

        $company = [
            'email' => $this->getCompanyEmail(),
            'sno' => $this->getSno(),
            'inn' => $this->getInn(),
            'payment_address' => $this->getPaymentAddress(),
        ];

        $data = [
            'external_id' => $this->getExternalId(),
            'receipt' => [
                'client' => $client,
                'company' => $company,
                'items' => $this->getItems(),
                'payments' => $this->getPayments(),
                'total' => $this->getTotal(),
            ],
            'service' => [
                'callback_url' => $this->getCallbackUrl(),
            ],
            'timestamp' => $this->getTimestamp(),
        ];

        if ($this->getAdditionalUserProps()) {
            $data['receipt']['additional_user_props'] = [
                'name' => $this->getAdditionalUserProps()->getName(),
                'value' => $this->getAdditionalUserProps()->getValue(),
            ];
        }

        return $data;
    }
}
