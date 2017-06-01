<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
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

        $this->saveTransaction($getRequest, $invoice, $order);
    }

    /**Method saves status entity and writes info to order
     * @param $getRequest
     * @param $entity
     * @param $order
     */
    public function saveTransaction($getRequest, $entity, $order)
    {
        $type      = $entity::HISTORY_ENTITY_NAME . '_';
        $operation = $entity::HISTORY_ENTITY_NAME == 'invoice' ? self::_operationSell : self::_operationSellRefund;

        Mage::helper('kkm')->addLog(ucwords($entity::HISTORY_ENTITY_NAME) . 'Cheque getRequest ' . $getRequest);

        if ($getRequest) {
            $request     = json_decode($getRequest);
            $statusModel = Mage::getModel('kkm/status')->load($type . $entity->getIncrementId(), 'external_id');

            if (!$statusModel->getId()) {
                $statusModel->setVendor(self::_code);
                $statusModel->setExternalId($type . $entity->getIncrementId());
                $statusModel->setOperation($operation);
            }

            $statusModel->setUuid($request->uuid);
            $statusModel->setStatus($getRequest)->save();

            //Save info about transaction
            Mage::helper('kkm')->saveTransactionInfoToOrder($getRequest, $entity, $order);
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

        $this->saveTransaction($getRequest, $creditmemo, $order);
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
//            'external_id' => 'invoice_100000026',
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
