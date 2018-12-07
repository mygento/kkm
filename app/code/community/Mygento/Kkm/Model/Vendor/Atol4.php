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
    const URL      = 'https://online.atol.ru/possystem/v4/';
    const TEST_URL = 'https://testonline.atol.ru/possystem/v4/';

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
        return $this->getUrl().$this->getConfig('general/group_code').'/'.$operation;
    }

    protected function getUpdateStatusUrl($uuid)
    {
        return $this->getUrl().$this->getConfig(
                'general/group_code'
            ).'/'.self::OPERATION_GET_REPORT.'/'.$uuid;
    }


    /**
     * @param $receipt entity (Order, Invoice or Creditmemo)
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

        $recalculatedReceiptData          = $discountHelper->getRecalculated(
            $receipt,
            $tax_value,
            $attribute_code,
            $shipping_tax
        );
        $recalculatedReceiptData['items'] = array_values($recalculatedReceiptData['items']);

        $callbackUrl = $this->getConfig('general/callback_url') ?: Mage::getUrl(
            'kkm/index/callback',
            ['_secure' => true]
        );

        $client = [
            'email' => $order->getCustomerEmail(),
        ];
        $company = [
            'email'           => $order->getCustomerEmail(),
            'sno'             => $this->getSno(),
            'inn'             => $this->getInn(),
            'payment_address' => $this->getPaymentAddress(),
        ];

        $now_time = Mage::getModel('core/date')->timestamp(time());
        $post     = [
            'external_id' => $this->generateExternalId($receipt, $externalIdPostfix),
            'service'     => [
                'payment_address' => $this->getConfig('general/payment_address'),
                'callback_url'    => $callbackUrl,
                'inn'             => $this->getConfig('general/inn'),
            ],
            'timestamp'   => date('d-m-Y H:i:s', $now_time),
            'receipt'     => [],
        ];

        $receiptTotal = round($receipt->getGrandTotal(), 2);

        $post['receipt'] = [
            'attributes' => [
                'sno'   => $this->getConfig('general/sno'),
                'phone' => $this->getConfig('general/send_phone') ? $order->getShippingAddress(
                )->getTelephone() : '',
                'email' => $order->getCustomerEmail(),
            ],
            'total'      => $receiptTotal,
            'payments'   => [],
            'items'      => [],
        ];

        $post['receipt']['payments'][] = [
            'sum'  => $receiptTotal,
            'type' => 1,
        ];

        $recalculatedReceiptData['items'] = array_map(
            [$this, 'sanitizeItem'],
            $recalculatedReceiptData['items']
        );
        $post['receipt']['items']         = $recalculatedReceiptData['items'];

        return json_encode($post);
    }
}
