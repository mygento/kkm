<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Vendor_Atol4 extends Mygento_Kkm_Model_Vendor_AtolAbstract implements Mygento_Kkm_Model_Vendor_VendorInterface
{
    const URL = 'https://online.atol.ru/possystem/v4/';
    const TEST_URL = 'https://testonline.atol.ru/possystem/v4/';

    const PAYMENT_METHOD_FULL_PAYMENT = 'full_payment';
    const PAYMENT_OBJECT_BASIC = 'commodity';

    protected function getUrl()
    {
        $isTest = (bool)$this->getConfig('general/test_mode');

        return $isTest ? self::TEST_URL : self::URL;
    }

    /**
     * @param string $operation part of the url
     * @return string
     * @throws \Exception
     */
    protected function getSendUrl($operation)
    {
        return $this->getUrl() . $this->getConfig('general/group_code') . '/' . $operation;
    }

    protected function getUpdateStatusUrl($uuid)
    {
        return $this->getUrl() . $this->getConfig(
                'general/group_code'
            ) . '/' . self::OPERATION_GET_REPORT . '/' . $uuid;
    }


    /**
     * @param $receipt Invoice|Creditmemo
     * @param $externalIdPostfix
     * @return string
     */
    public function generateJsonPost($receipt, $externalIdPostfix)
    {
        $discountHelper = Mage::helper('kkm/discount');

        $order = defined(get_class($receipt) . '::HISTORY_ENTITY_NAME') && $receipt::HISTORY_ENTITY_NAME == 'order'
            ? $receipt
            : $receipt->getOrder();

        $shipping_tax   = $this->getConfig('general/shipping_tax');
        $tax_value      = $this->getConfig('general/tax_options');
        $attribute_code = '';
        if (!$this->getConfig('general/tax_all')) {
            $attribute_code = $this->getConfig('general/product_tax_attr');
        }

        if (!$this->getConfig('general/default_shipping_name')) {
            $order->setShippingDescription($this->getConfig('general/custom_shipping_name'));
        }

        //Set mode flags for Discount logic
        $discountHelper->setDoCalculation($this->getConfig('general/apply_algorithm'));
        if ($this->getConfig('general/apply_algorithm')) {
            $discountHelper->setSpreadDiscOnAllUnits($this->getConfig('general/spread_discount'));
            $discountHelper->setIsSplitItemsAllowed($this->getConfig('general/split_allowed'));
        }

        $recalculatedReceiptData = $discountHelper->getRecalculated(
            $receipt,
            $tax_value,
            $attribute_code,
            $shipping_tax
        );
        $recalculatedReceiptData['items'] = array_values($recalculatedReceiptData['items']);
        $items = $this->getPreparedItems($recalculatedReceiptData['items']);

        $callbackUrl = $this->getConfig('general/callback_url') ?: Mage::getUrl(
            'kkm/index/callback',
            ['_secure' => true]
        );
        $now_time = Mage::getModel('core/date')->timestamp(time());

        $client = [];
        $this->getConfig('general/send_phone') && $order->getShippingAddress()->getTelephone()
            ? $client['phone'] = $order->getShippingAddress()->getTelephone()
            : $client['email'] = $order->getCustomerEmail();
        $company = [
            'email' => Mage::getStoreConfig('trans_email/ident_general/email'),
            'sno' => $this->getConfig('general/sno'),
            'inn' => $this->getConfig('general/inn'),
            'payment_address' => $this->getConfig('general/payment_address'),
        ];

        $total = round($receipt->getGrandTotal(), 2);
        $payments[] = [
            'sum' => $total,
            'type' => self::PAYMENT_TYPE_BASIC,
        ];

        $post = [
            'external_id' => $this->generateExternalId($receipt, $externalIdPostfix),
            'receipt' => [
                'client' => $client,
                'company' => $company,
                'items' => $items,
                'payments' => $payments,
                'total' => $total,
            ],
            'service' => [
                'callback_url' => $callbackUrl,
            ],
            'timestamp' => date('d-m-Y H:i:s', $now_time),
        ];

        return json_encode($post);
    }

    protected function getPreparedItems($items)
    {
        $items = array_map(
            [$this, 'sanitizeItem'],
            $items
        );

        foreach ($items as $key => $item) {
            $item['vat']['type'] = $item['tax'];
            $item['payment_method'] = self::PAYMENT_METHOD_FULL_PAYMENT;
            $item['payment_object'] = self::PAYMENT_OBJECT_BASIC;
            unset($item['tax']);
            $items[$key] = $item;
        }

        return $items;
    }
}
