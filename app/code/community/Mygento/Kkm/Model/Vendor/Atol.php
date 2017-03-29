<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Model_Vendor_Atol extends Mygento_Kkm_Model_Abstract
{

    /**
     * 
     * @param type $invoice
     */
    public function sendCheque($invoice)
    {
        $post = [];

        $post['service'] = [
            'payment_address' => $this->getConfig('payment_address'),
            'callback_url' => $this->getConfig('callback_url'),
            'inn' => $this->getConfig('inn')
        ];
        $post['timestamp'] = date('d-m-YY', time());
        $post['receipt'] = [
            'attributes' => [
                $this->getConfig('sno'),
                'phone' => $this->getConfig('phone'),
                'email' => $this->getConfig('email'),
            ],
            'total' => null,
            'payments' => [
                'sum' => $invoice->getAmount(),
                'type' => null
            ]
        ];
        $post['receipt']['items'] = [];
        /* process next */
    }

    /**
     * 
     * @param type $order
     */
    public function cancelCheque($order)
    {

        /* process next */
    }

    /**
     * 
     * @param type $invoice
     */
    public function updateCheque($invoice)
    {
        
    }
}
