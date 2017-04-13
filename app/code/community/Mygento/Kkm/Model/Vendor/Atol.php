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

    const _URL = 'https://online.atol.ru/possystem/v3/';
    const _code = 'atol';
    const _callbackUrl = 'http://testbalance';

    /**
     * 
     * @param type $invoice
     */
    public function sendCheque($invoice, $order)
    {
        $post = [];

        $token = $this->getToken();

        if (!$token) {
            return false;
        }
        $operation = 'sell';

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . $operation . '?tokenid=' . $token;

        $post['external_id'] = $order->getIncrementId();

        $post['service'] = [
            'payment_address' => $this->getConfig('general/payment_address'),
            'callback_url' => self::_callbackUrl,
            'inn' => $this->getConfig('general/inn')
        ];

        $now_time = Mage::getModel('core/date')->timestamp(time());
        $post['timestamp'] = date('d-m-Y H:i:s', $now_time);

        $post['receipt'] = [
            'attributes' => [
                'sno' => $this->getConfig('general/sno'),
                'phone' => $order->getShippingAddress()->getTelephone(),
                'email' => $order->getCustomerEmail(),
            ],
            'total' => Mage::helper('kkm')->calcSum($invoice)
        ];

        $post['receipt']['payments'][] = [
            'sum' => Mage::helper('kkm')->calcSum($invoice),
            'type' => 1
        ];

        $items = $invoice->getAllItems();

        foreach ($items as $item) {
            if (!$item->getRowTotal()) {
                continue;
            }
            $orderItem = [];

            $tax_value = $this->getConfig('general/tax_options');

            if (!$this->getConfig('general/tax_all')) {
                $attribute_code = $this->getConfig('general/product_tax_attr');
                $tax_value = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), $attribute_code, $order->getStore());
            }

            $itemSum = $item->getRowTotal();

            if ($item->getDiscountAmount()) {
                $itemSum = $itemSum - $item->getDiscountAmount();
            }

            $orderItem['price'] = round($item->getPrice(), 2);
            $orderItem['name'] = $item->getName();
            $orderItem['quantity'] = round($item->getQty(), 2);
            $orderItem['sum'] = round($itemSum, 2);
            $orderItem['tax'] = $tax_value;
            $post['receipt']['items'][] = $orderItem;
        }

        $shippingItem = [];
        $shippingItem['name'] = $this->getConfig('general/default_shipping_name') ? $order->getShippingDescription() : $this->getConfig('general/custom_shipping_name');
        $shippingItem['price'] = round($invoice->getShippingAmount(), 2);
        $shippingItem['quantity'] = 1.0;
        $shippingItem['sum'] = round($invoice->getShippingAmount(), 2);
        $shippingItem['tax'] = $this->getConfig('general/shipping_tax');
        $post['receipt']['items'][] = $shippingItem;

//        $getRequest = Mage::helper('kkm')->requestApiPost($url, json_encode($post));

        return json_encode($post);

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

    /**
     * 
     * @return string
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
