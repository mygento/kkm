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
            $tokenModel->setToken($tokenValue);
            $tokenModel->setExpireDate(time() + (24 * 60 * 60))->save();
        } else {
            $token->setToken($tokenValue);
            $token->setExpireDate(time() + (24 * 60 * 60))->save();
        }

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
        $post = [];

        $post['external_id'] = $type . $receipt->getIncrementId();

        $post['service'] = [
            'payment_address' => $this->getConfig('general/payment_address'),
            'callback_url'    => Mage::getUrl('kkm/index/callback'),
            'inn'             => $this->getConfig('general/inn')
        ];

        $now_time          = Mage::getModel('core/date')->timestamp(time());
        $post['timestamp'] = date('d-m-Y H:i:s', $now_time);

        $receiptTotal = Mage::helper('kkm')->calcSum($receipt);

        $post['receipt'] = [
            'attributes' => [
                'sno'   => $this->getConfig('general/sno'),
                'phone' => $order->getShippingAddress()->getTelephone(),
                'email' => $order->getCustomerEmail(),
            ],
            'total'      => $receiptTotal
        ];

        $post['receipt']['payments'][] = [
            'sum'  => $receiptTotal,
            'type' => 1
        ];

        $sumWithCartRuleDiscount = $receipt->getSubtotal() + $receipt->getDiscountAmount();
        $sumWithAllDiscount      = $receipt->getGrandTotal() - $receipt->getShippingAmount();

        $items = $receipt->getAllItems();

        /**
         * получаем 
         */
        $itemsSum = 0;
        foreach ($items as $item) {
            if (!$item->getRowTotal()) {
                continue;
            }
            $orderItem = [];

            $tax_value = $this->getConfig('general/tax_options');

            if (!$this->getConfig('general/tax_all')) {
                $attribute_code = $this->getConfig('general/product_tax_attr');
                $tax_value      = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(),
                    $attribute_code, $order->getStore());
            }

            $itemSum = $item->getRowTotal();

            $procentDiscountValue = ($itemSum - $item->getDiscountAmount()) / $sumWithCartRuleDiscount;
            $itemAfterDiscount    = $sumWithAllDiscount * $procentDiscountValue;

            $orderItem['price']         = round($item->getPrice(), 2);
            $orderItem['name']          = $item->getName();
            $orderItem['quantity']      = round($item->getQty(), 2);
            $orderItem['sum']           = round($itemAfterDiscount, 2);
            $orderItem['tax']           = $tax_value;
            $post['receipt']['items'][] = $orderItem;
            $itemsSum                   += $orderItem['sum'];
        }

        $itemsSumDiff = round($sumWithAllDiscount - $itemsSum, 2);

        /* change sum in first item */
        $firstItem                   = $post['receipt']['items'][0];
        $firstItem['sum']            = $firstItem['sum'] + $itemsSumDiff;
        $post['receipt']['items'][0] = $firstItem;

        $shippingItem               = [];
        $shippingItem['name']       = $this->getConfig('general/default_shipping_name') ? $order->getShippingDescription() : $this->getConfig('general/custom_shipping_name');
        $shippingItem['price']      = round($receipt->getShippingAmount(), 2);
        $shippingItem['quantity']   = 1.0;
        $shippingItem['sum']        = round($receipt->getShippingAmount(), 2);
        $shippingItem['tax']        = $this->getConfig('general/shipping_tax');
        $post['receipt']['items'][] = $shippingItem;

        return json_encode($post);
    }

}
