<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Observer
{

    protected function getVendorModel()
    {
        return Mage::getModel('kkm/vendor_' . Mage::helper('kkm')->getConfig('general/vendor'));
    }
    /**
     *
     * @param type $observer
     */
    public function sendCheque($observer)
    {
        $helper = Mage::helper('kkm');
        $helper->addLog('sendCheque');

        $invoice         = $observer->getEvent()->getInvoice();

        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/auto_send_after_invoice') || $invoice->getOrderCurrencyCode() != 'RUB') {
            return;
        }

        $order           = $invoice->getOrder();
        $paymentMethod   = $order->getPayment()->getMethod();
        $invoiceOrigData = $invoice->getOrigData();
        $paymentMethods  = explode(',', $helper->getConfig('general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            $helper->addLog('paymentMethod: ' . $paymentMethod);
            return;
        }

        if ($invoice->getOrigData() && isset($invoiceOrigData['increment_id'])) {
            return;
        }

        $this->getVendorModel()->sendCheque($invoice, $order);
    }

    /**
     *
     * @param type $observer
     */
    public function cancelCheque($observer)
    {
        $helper = Mage::helper('kkm');
        $helper->addLog('cancelCheque');

        $creditmemo = $observer->getEvent()->getCreditmemo();
        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/auto_send_after_cancel') || $creditmemo->getOrderCurrencyCode()) {
            return;
        }

        $order              = $creditmemo->getOrder();
        $paymentMethod      = $order->getPayment()->getMethod();
        $creditmemoOrigData = $creditmemo->getOrigData();
        $paymentMethods     = explode(',', $helper->getConfig('general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            $helper->addLog('paymentMethod: ' . $paymentMethod);
            return;
        }
        if ($creditmemo->getOrigData() && isset($creditmemoOrigData['increment_id'])) {
            return;
        }

        $this->getVendorModel()->cancelCheque($creditmemo, $order);
    }
}
