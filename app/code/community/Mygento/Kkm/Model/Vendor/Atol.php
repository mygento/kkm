<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Model_Vendor_Atol extends Mygento_Kkm_Model_Abstract {

    const _URL = 'https://online.atol.ru/possystem/v3/';
    const _code = 'atol';

    /**
     * 
     * @param type $invoice
     */
    public function sendCheque($invoice) {
        $post = [];

        $token = $this->getToken();

        if (!$token) {
            return false;
        }

        $operation = 'sell';

        $url = self::_URL . $this->getConfig('group') . '/' . $operation . '?' . $token;

        $post['service'] = [
            'payment_address' => $this->getConfig('payment_address'),
            'callback_url' => 'http://testbalance',
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
    public function cancelCheque($order) {

        /* process next */
    }

    /**
     * 
     * @param type $invoice
     */
    public function updateCheque($invoice) {
        
    }

    /**
     * 
     * @return string
     */
    public function getToken() {
        $tokenModel = Mage::getModel('kkm/token');

        $token = $tokenModel->load(self::_code, 'vendor');

        if ($token->getId() && strtotime($token->getExpireDate()) > time()) {
            return $token->getToken();
        }

        $data = [
            'login' => $this->getConfig('general/login'),
            'pass' => Mage::helper('core')->decrypt($this->getConfig('general/password'))
        ];

        $getRequest = Mage::helper('kkm')->requestApiPost(self::_URL . 'getToken', json_encode($data));

        if (!$getRequest) {
            return false;
        }

        $decodedResult = json_decode($getRequest);

        if (!$decodedResult->token || $decodedResult->token == '') {
            return false;
        }

        $tokenValue = $decodedResult->token;

        if (!$token->getId()) {
            $tokenModel->setVendor(self::_code);
            $tokenModel->setToken($tokenValue);
            $tokenModel->setExpireDate(time() + (24 * 60 * 60))->save();
        } else {
            $token->setToken($tokenValue);
            $token->setExpireDate(time() + (24 * 60 * 60))->save();
        }

        return $tokenValue;
    }

}
