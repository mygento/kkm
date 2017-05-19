<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Vendor_Atol extends Mygento_Kkm_Model_Abstract
{

    const _URL                 = 'https://online.atol.ru/possystem/v3/';
    const _code                = 'atol';
    const _operationSell       = 'sell';
    const _operationSellRefund = 'sell_refund';

    /**
     * 
     * @param type $invoice
     * @param type $order
     */
    public function sendCheque($invoice, $order)
    {
        $token = $this->getToken();
        $type  = 'invoice_';

        if (!$token) {
            return false;
        }

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . self::_operationSell . '?tokenid=' . $token;
        Mage::helper('kkm')->addLog('sendCheque url: ' . $url);

        $jsonPost = $this->_generateJsonPost($type, $invoice, $order);
        Mage::helper('kkm')->addLog('sendCheque jsonPost: ' . $jsonPost);

        $getRequest = Mage::helper('kkm')->requestApiPost($url, $jsonPost);

        if ($getRequest) {
            Mage::helper('kkm')->addLog('sendCheque getRequest ' . $getRequest);
            $request     = json_decode($getRequest);
            $statusModel = Mage::getModel('kkm/status');
            $statusModel->setVendor(self::_code);
            $statusModel->setUuid($request->uuid);
            $statusModel->setExternalId($type . $invoice->getIncrementId());
            $statusModel->setOperation(self::_operationSell);
            $statusModel->setStatus($getRequest)->save();
        }
    }

    /**
     * 
     * @param type $creditmemo
     * @param type $order
     */
    public function cancelCheque($creditmemo, $order)
    {
        $token = $this->getToken();
        $type  = 'creditmemo_';

        if (!$token) {
            return false;
        }

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . self::_operationSellRefund . '?tokenid=' . $token;
        Mage::helper('kkm')->addLog('cancelCheque url: ' . $url);

        $jsonPost = $this->_generateJsonPost($type, $creditmemo, $order);
        Mage::helper('kkm')->addLog('cancelCheque jsonPost: ' . $jsonPost);

        $getRequest = Mage::helper('kkm')->requestApiPost($url, $jsonPost);

        if ($getRequest) {
            Mage::helper('kkm')->addLog('cancelCheque getRequest ' . $getRequest);
            $request     = json_decode($getRequest);
            $statusModel = Mage::getModel('kkm/status');
            $statusModel->setVendor(self::_code);
            $statusModel->setUuid($request->uuid);
            $statusModel->setExternalId($type . $creditmemo->getIncrementId());
            $statusModel->setOperation(self::_operationSellRefund);
            $statusModel->setStatus($getRequest)->save();
        }
    }

    /**
     * 
     * @param type $invoice
     */
    public function updateCheque($invoice)
    {
        
    }

    /**
     * 
     * @return boolean || string
     */
    public function getToken()
    {
        $tokenModel = Mage::getModel('kkm/token');

        $token = $tokenModel->load(self::_code, 'vendor');

        if ($token->getId() && strtotime($token->getExpireDate()) > time()) {
            return $token->getToken();
        }

        $data = [
            'login' => $this->getConfig('general/login'),
            'pass'  => Mage::helper('core')->decrypt($this->getConfig('general/password'))
        ];

        $getRequest = Mage::helper('kkm')->requestApiPost(self::_URL . 'getToken',
            json_encode($data));

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
        }
        $token->setToken($tokenValue);
        $token->setExpireDate(time() + (24 * 60 * 60));
        $token->save();

        return $tokenValue;
    }

    /**
     * 
     * @param type $type || string
     * @param type $receipt
     * @param type $order
     * @return type json
     */
    protected function _generateJsonPost($type, $receipt, $order)
    {
        $discountHelper = Mage::helper('kkm/discount');

        $shipping_tax   = $this->getConfig('general/shipping_tax');
        $tax_value      = $this->getConfig('general/tax_options');
        $attribute_code = '';
        if (!$this->getConfig('general/tax_all')) {
            $attribute_code = $this->getConfig('general/product_tax_attr');
        }

        if (!$this->getConfig('general/default_shipping_name')) {
            $receipt->getOrder()->setShippingDescription($this->getConfig('general/custom_shipping_name'));
        }

        $recalculatedReceiptData = $discountHelper->getRecalculated($receipt, $tax_value, $attribute_code, $shipping_tax);
        $recalculatedReceiptData['items'] = array_values($recalculatedReceiptData['items']);

        $now_time = Mage::getModel('core/date')->timestamp(time());
        $post = [
            'external_id' => $type . $receipt->getIncrementId(),
            'service' => [
                'payment_address' => $this->getConfig('general/payment_address'),
                'callback_url'    => Mage::getUrl('kkm/index/callback'),
                'inn'             => $this->getConfig('general/inn')
            ],
            'timestamp' => date('d-m-Y H:i:s', $now_time),
            'receipt' => [],
        ];

        $receiptTotal = round($receipt->getGrandTotal(), 2);

        $post['receipt'] = [
            'attributes' => [
                'sno'   => $this->getConfig('general/sno'),
                'phone' => $order->getShippingAddress()->getTelephone(),
                'email' => $order->getCustomerEmail(),
            ],
            'total'    => $receiptTotal,
            'payments' => [],
            'items' => [],
        ];

        $post['receipt']['payments'][] = [
            'sum'  => $receiptTotal,
            'type' => 1
        ];

        $post['receipt']['items'] = $recalculatedReceiptData['items'];

        return json_encode($post);
    }

}
